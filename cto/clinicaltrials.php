<?php

/*Copyright (C) 2011-2012 Dana Klassen and Michel Dumontier

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

 @author :: Dana Klasen
 @version :: 0.1
 @description :: The Clinical Trials Parser database parser
*/

require('../../php-lib/rdfapi.php');
require('../../php-lib/xmlapi.php');
class ClinicalTrialsParser extends RDFFactory{

	private $version = null;

	function __construct($argv){
		parent::__construct();

		$this->SetDefaultNamespace("clinicaltrials");
		$this->AddParameter('download',false,'true|false','false','download the files from the clinical open trials website');
		$this->AddParameter('files',true,'all|study|results','all','files to process');
		$this->AddParameter('indir',false,null,'../../download/clinical_trials/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'../../data/clinical_trials/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('remote_server',false,null,'http://clinicaltrials.gov/ct2/crawl');

		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}

		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		#if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
		
		return TRUE;

	}

	function run(){
		$this->crawl();
	}

	/**
	* scape the clinical gov site for the links to invididual records
	**/
	function crawl(){
		$crawl_url = "http://clinicaltrials.gov/ct2/crawl";
		$html = file_get_contents($crawl_url);

		$dom = new DOMDocument();
		@$dom->loadHTML($html);

		// grab all the links on the page
		$xpath = new DOMXPath($dom);
		$hrefs = $xpath->evaluate("/html/body//a");

		for ($i = 0; $i < $hrefs->length; $i++) {
			$href = $hrefs->item($i);
			if(preg_match("/crawl\/([0-9]+)/",$href->getAttribute('href'))){
				
				$record_block_url = "http://clinicaltrials.gov".$href->getAttribute('href');
				$this->fetch_record_block($record_block_url);
			}	
		}
	}

	/**
	* Fetch the page holding a block of records
	**/
	function fetch_record_block($url){
		$html = file_get_contents($url);
		$dom = new DOMDocument();
		@$dom->loadHTML($html);

		$xpath = new DOMXPath($dom);
		$hrefs = $xpath->evaluate("/html/body//a");

		// this is the name of the folder where this block will be placed
		$block_no = explode('/', rtrim($url, '/'));
		$curr_block = "nct".$block_no[5];

		for ($i = 0; $i < $hrefs->length; $i++) {
			$href = $hrefs->item($i);
			if(preg_match("/ct2\/show\//",$href->getAttribute('href'))){

				$page_uri = "http://clinicaltrials.gov/".$href->getAttribute('href')."?resultsxml=true";
				$this->fetch_page($page_uri,$curr_block);
			}	
		}
	}
	/**
	* fetch the individual record page using 
	**/
	function fetch_page($url,$curr_block){
		preg_match("/show\/(NCT[0-9]+)/",$url,$m);
		$file = $m[1];
		$outfile = $this->GetParameterValue("indir")."/".$file.".xml";
		$xml = file_get_contents($url);
		
		# save the file
		file_put_contents($outfile,$xml);

		# convert the file to RDF
		$this->process_result($outfile,$curr_block);
	}
	
	/**
	* process a results xml file from the download directory
	**/
	function process_result($infile,$curr_block){
		$indir = $this->GetParameterValue('indir');

		$outfile = $this->GetParameterValue("outdir");
		if($curr_block !=null ){ $outfile .= $curr_block."/"; $this->CreateDirectory($outfile); }
		$outfile.=basename($infile,".xml").".nt";
		
		$gz = false;
		if($this->GetParameterValue('gzip')) {$outfile .= '.gz';$gz = true;}
		
		echo $outfile."\n";
		$this->SetWriteFile($outfile,$gz);

		$xml = new CXML($indir,basename($infile));
		while($xml->Parse("clinical_study") == TRUE) {

			$root = $xml->GetXMLRoot();

			#########################################################################################
			# study ids
			#########################################################################################
			$nct_id       = array_shift($root->xpath("//id_info/nct_id"));
			$org_study_id = array_shift($root->xpath("//id_info/org_study_id"));
			$secondary_id = array_shift($root->xpath("//id_info/secondary_id"));

			$study_id = "clinicaltrials:".$nct_id;
			$this->AddRDF($this->QQuad($study_id,"rdf:type","clinicaltrials_vocabulary:ClinicalStudy"));
			$this->AddRDF($this->QQuadl($study_id,"dc:identifier",$nct_id));
			$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_org_study_id",$org_study_id));
			$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_secondary_id",$secondary_id));

			##########################################################################################
			#brief trial
			##########################################################################################
			$brief_title = array_shift($root->xpath("//brief_title"));
			if($brief_title != ""){
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_brief_title",$this->SafeLiteral($brief_title)));
			}

			##########################################################################################
			#official title
			##########################################################################################
			$official_title = array_shift($root->xpath("//official_title"));
			if($official_title != "") {
				$this->AddRDF($this->QQuadl($study_id,"dc:title",$this->SafeLiteral($official_title)));
			}

			#########################################################################################
			#acronym
			#########################################################################################
			$acronym = array_shift($root->xpath("//acronym"));
			if($acronym != ""){
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_acronym",$acronym));
			}

			########################################################################################
			#lead_sponser
			########################################################################################
			try {
				$lead_sponsor = array_shift($root->xpath('//sponsors/lead_sponsor'));
				$agency       = array_shift($lead_sponsor->xpath("//agency"));
				$agency_class = array_shift($lead_sponsor->xpath("//agency_class"));
				$lead_sponsor_id = "clinicaltrials:".md5($lead_sponsor->asXML());
				$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_lead_sponsor",$lead_sponsor_id));
				$this->AddRDF($this->QQuadl($lead_sponsor_id,"dc:title",$agency));
				$this->AddRDF($this->QQuadl($lead_sponsor_id,"clinicaltrials_vocabulary:has_agency_class",$agency_class));
			}catch( Exception $e){
				echo "There was an error in the lead sponsor element: $e\n";
			}

			######################################################################################
			#oversight info
			######################################################################################
			try {
				$over_site = array_shift($root->xpath('//oversight_info'));
				$authority = array_shift($over_site->xpath('//authority'));
				$os_id = "clinicaltrials:".md5($over_site->asXML());
				$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_over_sight",$os_id));
				$this->AddRDF($this->QQuadl($os_id,"clinicaltrials_vocabulary:has_authority",$authority));
			} catch(Exception $e){
				echo "There was an error in the oversight info element: $e\n";
			}
			####################################################################################
			# has_dmc
			####################################################################################
			$has_dmc   = array_shift($over_site->xpath('//has_dmc'));
			if($has_dmc != ""){
				$this->AddRDF($this->QQuadl($os_id,"clinicaltrials_vocabulary:has_dmc",$has_dmc));
			}

			###################################################################################
			#brief summary
			###################################################################################
			$brief_summary = array_shift($root->xpath('//brief_summary/textblock'));
			$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_brief_summary",$this->SafeLiteral($brief_summary)));

			####################################################################################
			# detailed description
			####################################################################################
			$detailed_description = array_shift($root->xpath('//detailed_description/textblock'));
			$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_detailed_description",$this->SafeLiteral($detailed_description)));

			#################################################################################
			# overall status
			#################################################################################
			$overall_status = array_shift($root->xpath('//overall_status'));
			$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_overall_status",$overall_status));

			##################################################################################
			# start date
			##################################################################################
			$start_date = array_shift($root->xpath('//start_date'));
			if($start_date) {
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_start_date",$start_date));
			}

			###################################################################################
			# completion date
			##################################################################################
			$completion_date = array_shift($root->xpath('//completion_date'));
			if($completion_date != ""){
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_completion_date",$completion_date));
			}

			####################################################################################
			# primary completion date
			###################################################################################
			$primary_completion_date = array_shift($root->xpath('//primary_completion_date'));
			if($primary_completion_date != ""){
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_primary_completion_date",$primary_completion_date));
			}

			####################################################################################
			# study type
			####################################################################################
			$study_type = array_shift($root->xpath('//study_type'));
			if($study_type != ""){
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_study_type",$this->SafeLiteral($study_type)));
			}

			####################################################################################
			# phase
			####################################################################################
			$phase = array_shift($root->xpath('//phase'));
			$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_phase",$phase));

			###############################################################################
			# study design
			###############################################################################
			$study_design = array_shift($root->xpath('//study_design'));
			$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_study_design",$this->SafeLiteral($study_design)));

			################################################################################
			#primary outcome
			###############################################################################
			$primary_outcome = array_shift($root->xpath('//primary_outcome'));
			$measure         = array_shift($root->xpath('//primary_outcome/measure'));
			$time_frame      = array_shift($root->xpath('//primary_outcome/time_frame'));
			$safety_issue    = array_shift($root->xpath('//primary_outcome/saftey_issue'));			

			if($primary_outcome){
				try{
					# create unique hash for the primary_outcome
					$po_id = "clinicaltrials:".md5($nct_id.$primary_outcome->asXML());
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_primary_outcome",$po_id));
					$this->AddRDF($this->QQuad($po_id,"rdf:type","clinicaltrials_vocabulary:Primary_Outcome"));					
					$this->AddRDF($this->QQuadl($po_id,"clinicaltrials_vocabulary:has_measure",$measure));
					$this->AddRDF($this->QQuadl($po_id,"clinicaltrials_vocabulary:has_time_frame",$time_frame));
					$this->AddRDF($this->QQuadl($po_id,"clinicaltrials_vocabulary:has_safety_issue",$safety_issue));
				}catch(Exception $e){
					echo "There was an error parsing the primary outcome element: $e \n";
				}
			}

			#################################################################################
			#secondary outcome
			#################################################################################
			try{
				$secondary_outcomes = $root->xpath('//secondary_outcome');
				foreach($secondary_outcomes as $secondary_outcome){
					$measure = array_shift($secondary_outcome->xpath('//measure'));
					$time_frame = array_shift($secondary_outcome->xpath('//time_frame'));
					$safety_issue = array_shift($secondary_outcome->xpath('//safety_issue'));
					$so_id = "clinicaltrials:".md5($nct_id.$secondary_outcome->asXML());

					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_secondary_outcome",$so_id));
					$this->AddRDF($this->QQuad($so_id,"rdf:type","clinicaltrials_vocabulary:Secondary_Outcome"));
					$this->AddRDF($this->QQuadl($so_id,"clinicaltrials_vocabulary:has_measure",$measure));
					$this->AddRDF($this->QQuadl($so_id,"clinicaltrials_vocabulary:has_time_frame",$time_frame));
					$this->AddRDF($this->QQuadl($so_id,"clinicaltrials_vocabulary:has_safety_issue",$safety_issue));
				}
			}catch (Exception $e){
				"There was an exception parsing the secondary outcomes element: $e\n";
			}
			##############################################################################
			#number of arms
			##############################################################################
			try {
				$no_of_arms = array_shift($root->xpath('//number_of_arms'));
				if($no_of_arms != ""){
					$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_number_of_arms",$no_of_arms));
				}
			}catch(Exception $e){
				echo "There was an exception parsing the number of arms element: $e\n";
			}
			##############################################################################
			#enrollment
			##############################################################################
			try{
				$enrollment = array_shift($root->xpath('//enrollment'));
				if($enrollment) { $this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_enrollment",$enrollment));}
			} catch(Exception $e){
				echo "There was an exception parsing the enrollment element: $e\n";
			}
			###############################################################################
			#condition
			###############################################################################
			try {
				$conditions = $root->xpath('//condition');
				foreach($conditions as $condition){
					$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_condition",$condition));
				}
			} catch(Exception $e) {
				echo "There was an exception parsing condition element: $e\n";
			}
			################################################################################
			# arm_group
			################################################################################
			
			try {
				$arm_groups = $root->xpath('//arm_group');
				foreach ($arm_groups as $arm_group) {
					$arm_group_label = $arm_group->xpath('./arm_group_label');
					$arm_group_type = ucfirst(str_replace(" ","_",array_shift($arm_group->xpath('./arm_group_type'))));
					$description = array_shift($arm_group->xpath('./description'));

					$arm_group_id = "clinicaltrials:".md5($arm_group->asXML());
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_arm_group",$arm_group_id));
					$this->AddRDF($this->QQuadl($arm_group_id,"rdfs:label",$arm_group_label));
					$this->AddRDF($this->QQuad($arm_group_id,"rdf:type","clinicaltrials_vocabulary:".$arm_group_type));
					$this->AddRDF($this->QQuadl($arm_group_id,"rdfs:comment",$description));
				}
			} catch (Exception $e){
				echo "There was an exception in arm groups: $e\n";
			}

			##############################################################################
			#intervention
			##############################################################################
			try {
				$interventions = $root->xpath('//intervention');
				foreach ($interventions as $intervention) {
					$intervention_name = array_shift($intervention->xpath('./intervention_name'));
					$intervention_type = ucfirst(str_replace(" ","_",array_shift($intervention->xpath('./intervention_type'))));

					$intervention_id = "clinicaltrials:".md5($intervention->asXML());
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_intervention",$intervention_id));
					$this->AddRDF($this->QQuad($intervention_id,"rdf:type","clinicaltrials_vocabulary:".$intervention_type));
					$this->AddRDF($this->QQuadl($intervention_id,"rdfs:label",$intervention_name));

					$description = array_shift($intervention->xpath('./description'));
					if($description != ""){
						$this->AddRDF($this->QQuadl($intervention_id,"rdfs:comment",$this->SafeLiteral($description)));
					}
				}
			}catch(Exception $e){
				echo "There was an error in interventions $e\n";
			}

			###############################################################################
			#eligibility
			################################################################################
			try{
				$eligibility = array_shift($root->xpath('//eligibility'));

				if($eligibility != null) {

					$eligibility_id = "clinicaltrials:".md5($eligibility->asXML());
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_eligibility",$eligibility_id));

					if($criteria = array_shift($eligibility->xpath('./criteria'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:has_criteria",$this->SafeLiteral(array_shift($criteria->xpath('./textblock')))));
					}

					if($gender = array_shift($eligibility->xpath('./gender'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:has_gender",$gender));
					}

					if($minimum_age = array_shift($eligibility->xpath('./minimum_age'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:has_minimum_age",$minimum_age));
					}

					if($maximum_age = array_shift($eligibility->xpath('./maximum_age'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:has_maximum_age",$maximum_age));
					}
					if($healthy_volunteers = array_shift($eligibility->xpath('./healthy_volunteers'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:has_healthy_volunteers",$healthy_volunteers));
					}

					if($study_pop = array_shift($eligibility->xpath('./study_pop'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:has_study_pop",$study_pop->xpath('./textblock')));
					}

					if($sampling_method = array_shift($eligibility->xpath('./sampling_method'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:has_sampling_method",$sampling_method->xpath('./textblock')));
					}
				}
			}catch(Exception $e){
				echo "There was an error in eligibility: $e\n";
			}

			######################################################################################
			#overall official - the person in charge
			#####################################################################################
			try {
				$overall_official = array_shift($root->xpath('//overall_official'));
				if($overall_official) {
					$overall_official = "clinicaltrials:".md5($overall_official->asXML());
					$last_name   = array_shift($root->xpath('//overall_official/last_name'));
					$role        = array_shift($root->xpath('//overall_official/role'));
					$affiliation = array_shift($root->xpath('//overall_official/affiliation'));

					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_overall_official",$overall_official));
					$this->AddRDF($this->QQuadl($overall_official,"clinicaltrials_vocabulary:has_last_name",$last_name));
					$this->AddRDF($this->QQuadl($overall_official,"clinicaltrials_vocabulary:has_role",$role));
					$this->AddRDF($this->QQuadl($overall_official,"clinicaltrials_vocabulary:has_affiliation",$affiliation));
				}
			}catch (Exception $e){
				echo "There was an error parsing the overal_official: $e\n";
			}

			##############################################################
			# location of facility doing the testing
			##############################################################
			try {	
				$location = array_shift($root->xpath('//location'));
				if($location){
					$location_id = "clinicaltrials:".md5($location->asXML());
					$facility = $location->xpath('./facility');
					$address  = array_shift($location->xpath('//address'));
					
					if(($city = array_shift($address->xpath('./city'))) != null){
						$this->AddRDF($this->QQuadl($location_id,"clinicaltrials_vocabulary:has_city",$city));
					}

					if(($state = array_shift($address->xpath('./state'))) != null){
						$this->AddRDF($this->QQuadl($location_id,"clinicaltrials_vocabulary:has_state",$state));
					}
					if(($zip = array_shift($address->xpath('./zip'))) != null){
						$this->AddRDF($this->QQuadl($location_id,"clinicaltrials_vocabulary:has_zip",$zip));
					}

					if(($country = array_shift($address->xpath('./country'))) != null ){
						$this->AddRDF($this->QQuadl($location_id,"clinicaltrials_vocabulary:has_country",$country));
					}

					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_location",$location_id));
					$this->AddRDF($this->QQuadl($location_id,"dc:title",array_shift($location->xpath('//name'))));
				}
			}catch (Exception $e){
				echo "There was an error parsing location: $e"."\n";
			}

			###################################################################
			# group
			###################################################################

			try {
				$groups = $root->xpath('//group');
				foreach ($groups as $group) {
					$group_id = "clinicaltrials:".md5($group->asXML());
					$title = array_shift($group->xpath('./title'));
					$description = array_shift($group->xpath('./description'));
					$id = $group->attributes()->group_id;
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_group",$group_id));
					$this->AddRDF($this->QQuadl($group_id,"dc:title",$title));
					$this->AddRDF($this->QQuadl($group_id,"rdfs:comment",$this->SafeLiteral($description)));
					$this->AddRDF($this->QQuadl($group_id,"dc:identifier",$id));
				}
			}catch(Exception $e){
				echo "There was an exception parsing groups xml element: $e\n";
			}

			######################################################################
			#results reference
			######################################################################
			try {
				$references = $root->xpath('//reference');
				foreach($references as $reference){
					$pmid = "pubmed:".array_shift($reference->xpath('./PMID'));
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_reference",$pmid));
					$this->AddRDF($this->QQuadl($pmid,"rdfs:comment",array_shift($reference->xpath('./citation'))));
					$this->AddRDF($this->QQuad($pmid,"rdf:type","clinicaltrials_vocabulary:Reference"));
				}
			} catch(Exception $e){
				echo "There was an error parsing references element: $e\n";
			}

			#######################################################################
			#results reference
			#######################################################################
			try{
				$results_references = $root->xpath('//results_reference');
				foreach($results_references as $result_reference){
					$pmid = "pubmed:".array_shift($result_reference->xpath('./PMID'));
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_result_reference",$pmid));
					$this->AddRDF($this->QQuadl($pmid,"rdfs:comment",array_shift($result_reference->xpath('./citation'))));
				}
			}catch(Exception $e){
				echo "There was an error parsing results_references element: $e\n";
			}

			##########################################################################
			#verification date
			#########################################################################
			try{
				$verification_date  = array_shift($root->xpath('//verification_date'));
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_verification_date",$verification_date));
				
				$lastchanged_date   = array_shift($root->xpath('//lastchanged_date'));
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_lastchanged_date",$lastchanged_date));
				
				$firstreceived_date = array_shift($root->xpath('//firstreceived_date'));
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_firstrecieved_date",$firstreceived_date));
			} catch(Exception $e){
				echo "There was an error parsing the verification_date element: $e\n";
			}

			############################################################################
			#responsible party
			############################################################################
			try{
				$responsible_party = array_shift($root->xpath('//responsible_party'));
				if($responsible_party){
					$name_title        = $root->xpath('//responsible_party/name_title');
					$organization      = $root->xpath('//responsible_party/organization');

					$rp_id = "clinicaltrials:".md5($responsible_party->asXML());

					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:has_reponsible_party",$rp_id));
					$this->AddRDF($this->QQuadl($rp_id,"clinicaltrials_vocabulary:has_name_title",$name_title));
					$this->AddRDF($this->QQuadl($rp_id,"clinicaltrials_vocabulary:has_organization",$organization));
				}
			}catch(Exception $e){
				echo "There was an error parsing the responsible_party element: $e\n";
			}

			##############################################################################
			# key words
			##############################################################################
			try{
				$keywords = $root->xpath('//keyword');
				foreach($keywords as $keyword){
					$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_keyword",$keyword));
				}
			}catch(Exception $e){
				echo "There was an error parsing the keywords element: $e";
			}

			# mesh terms 
			# note: mesh terms are assigned using an imperfect algorithm
			try{
				$mesh_terms = $root->xpath('//condition_browse/mesh_term');
				foreach($mesh_terms as $mesh_term){
					$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_conditional_mesh_term",$mesh_term));
				}
			}catch(Exception $e){
				echo "There was an error in mesh_terms: $e\n";
			}

			###############################################################################
			# mesh terms for hte invervention browse
			###############################################################################
			try {
				$mesh_terms = $root->xpath('//intervention_browse/mesh_term');
				foreach($mesh_terms as $mesh_term){
					$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:has_mesh_term",$mesh_term));
				}
			}
			catch(Exception $e){
				echo "There was an error parsing intervention_browse/mesh_term element: $e\n";
			}

			################################################################################
			# regulated by fda? 
			# boolean value yes or no
			################################################################################
			try {
				$regulated = array_shift($root->xpath('is_fda_regulated'));
				if($regulated != ""){
					$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:is_fda_regulated",$regulated));
				}
			} catch (Excepetion $e){
				echo "There was an error parsing the is_fda_regulated element: $e\n";
			}

			$this->WriteRDFBufferToWriteFile();

		}

		$this->getWriteFile()->close();

	}
}

$parser = new ClinicalTrialsParser($argv);
$parser->run();

?>
