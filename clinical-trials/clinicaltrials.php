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
 @author :: Michel Dumontier
 @version :: 0.2
 @description ::  clinicaltrials.gov parser
*/

require('../../php-lib/rdfapi.php');
require('../../php-lib/xmlapi.php');
class ClinicalTrialsParser extends RDFFactory{

	private $version = null;

	function __construct($argv){
		parent::__construct();

		$this->SetDefaultNamespace("clinicaltrials");
		$this->AddParameter('process',true,'crawl|download|local','local','select how to process files from the clinical open trials website');
		$this->AddParameter('files',false,'all|study|results','all','files to process');
		$this->AddParameter('indir',false,null,'/data/download/clinicaltrials/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/clinicaltrials/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'specify a graph uri to generate nquads');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('remote_server',false,null,'http://clinicaltrials.gov/ct2/crawl');

		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}

		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
		
		return TRUE;
	}

	function run(){
		switch($this->GetParameterValue('process')) {
			case "crawl":
				$this->crawl();
				break;
			case "download":
				$this->crawl();
				break;
			case  "local" :
				echo "parsing local directory:\n";
				$this->parse_dir();
				break;
		}
	}

	/**
	* parse directory of files
	**/
	function parse_dir(){
		$indir = $this->GetParameterValue('indir');

		if( $handle = opendir($indir)) {
			echo "processing directory $indir\n";
			echo "Parsing entries\n";

			$ignore = array("..",'.','.DS_STORE',"0");
			
			while(($entry = readdir($handle)) !== false){
				if (in_array($entry, $ignore) || is_dir($entry) ) continue;
				
				echo "Processing $entry".PHP_EOL;
				$sub_dir = $this->get_sub_dir($entry);
				$this->process_result($entry,$sub_dir);
			}
		
			echo "Finished\n.";
			closedir($handle);
		}
	}

	/**
	* generate the proper subdir based on the file name
	**/
	function get_sub_dir($entry){
		$bin_range = 10;

		preg_match('/NCT[0]+(\d+)\.xml$/', $entry,$matches);
		$record_number = $matches[1];
		
		// find last multiple of bin_range
		$count = -strlen($bin_range);
		$marker = substr($record_number, $count);

		$curr_bin = substr($marker, 0,1). str_repeat(0,intval(strlen($bin_range))-1);

		$sub_dir = substr($record_number, 0,$count).$curr_bin;

		return $sub_dir;
	}
	/**
	* scape the clinical gov site for the links to invididual records
	**/
	function crawl(){
		$crawl_url = $this->GetParameterValue("remote_server"); //"http://clinicaltrials.gov/ct2/crawl";
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

		for ($i = 0; $i < $hrefs->length; $i++) {
			$href = $hrefs->item($i);
			if(preg_match("/ct2\/show\//",$href->getAttribute('href'))){

				$page_uri = "http://clinicaltrials.gov/".$href->getAttribute('href')."?resultsxml=true";
				$this->fetch_page($page_uri);
			}	
		}
	}

	/**
	* fetch the individual record page using 
	**/
	function fetch_page($url){
		preg_match("/show\/(NCT[0-9]+)/",$url,$m);
		$file = $m[1];
		$outfile = $this->GetParameterValue("indir")."/".$file.".xml";
		$xml = file_get_contents($url);
		
		# save the file
		file_put_contents($outfile,$xml);

		// only parse the page if the process was set
		if($this->GetParameterValue("process") == "crawl"){
			$sub_dir = $this->get_sub_dir($outfile);
			$this->process_result($outfile,$sub_dir);
		}
	}
	
	/**
	* process a results xml file from the download directory
	**/
	function process_result($infile,$curr_block){
		$indir = $this->GetParameterValue('indir');

		$outfile = $this->GetParameterValue("outdir");
		$this->CreateDirectory($outfile); 
		$outfile.=basename($infile,".xml").".nt";
		
		$gz = false;
		if($this->GetParameterValue('gzip')) {$outfile .= '.gz';$gz = true;}
		
		$this->SetWriteFile($outfile,$gz);

		$xml = new CXML($indir,basename($infile));
		while($xml->Parse("clinical_study") == TRUE) {

			$root = $xml->GetXMLRoot();
			#########################################################################################
			# study ids
			#########################################################################################
			$nct_id       = @array_shift($root->xpath("//id_info/nct_id"));
			$org_study_id = @array_shift($root->xpath("//id_info/org_study_id"));
			$secondary_id = @array_shift($root->xpath("//id_info/secondary_id"));

			$study_id = "clinicaltrials:".$nct_id;
			$this->AddRDF($this->QQuad($study_id,"rdf:type","clinicaltrials_vocabulary:Clinical-Study"));
			$this->AddRDF($this->QQuadl($study_id,"dc:identifier",$nct_id));
			
			$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:org-study-identifier",$org_study_id));
			$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:secondary-identifier",$secondary_id));

			##########################################################################################
			#brief trial
			##########################################################################################
			$brief_title = @array_shift($root->xpath("//brief_title"));
			if($brief_title != ""){
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:brief-title",$this->SafeLiteral($brief_title)));
				$this->AddRDF($this->QQuadl($study_id,"rdfs:label",$this->SafeLiteral($brief_title)." [$study_id]"));
			}

			##########################################################################################
			#official title
			##########################################################################################
			$official_title = @array_shift($root->xpath("//official_title"));
			if($official_title != "") {
				$this->AddRDF($this->QQuadl($study_id,"dc:title",$this->SafeLiteral($official_title)));
				if(!$brief_title) $this->AddRDF($this->QQuadl($study_id,"rdfs:label",$this->SafeLiteral($official_title)." [$study_id]"));
			}
			

			#########################################################################################
			#acronym
			#########################################################################################
			$acronym = @array_shift($root->xpath("//acronym"));
			if($acronym != ""){
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:acronym",$this->SafeLiteral($acronym)));
			}

			########################################################################################
			#lead_sponser
			########################################################################################
			try {
				$lead_sponsor = @array_shift($root->xpath('//sponsors/lead_sponsor'));
				$agency       = @array_shift($lead_sponsor->xpath("//agency"));
				$agency_class = @array_shift($lead_sponsor->xpath("//agency_class"));
				$lead_sponsor_id = "clinicaltrials_resource:".md5($lead_sponsor->asXML());
                $this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:lead-sponsor",$lead_sponsor_id));
                $this->AddRDF($this->QQuad($lead_sponsor_id,"rdf:type","clinicaltrials_vocabulary:Lead-Sponsor"));
				$this->AddRDF($this->QQuadl($lead_sponsor_id,"dc:title",$this->SafeLiteral($agency)));
				$this->AddRDF($this->QQuadl($lead_sponsor_id,"rdfs:label",$this->SafeLiteral($agency)." [$lead_sponsor_id]"));
				$this->AddRDF($this->QQuadl($lead_sponsor_id,"clinicaltrials_vocabulary:agency-class",$this->SafeLiteral($agency_class)));
			}catch( Exception $e){
				echo "There was an error in the lead sponsor element: $e\n";
			}

			######################################################################################
			#oversight info
			######################################################################################
			try {
				$over_site = @array_shift($root->xpath('//oversight_info'));
				$authority = @array_shift($over_site->xpath('//authority'));
				$os_id = "clinicaltrials_resource:".md5($over_site->asXML());
                $this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:oversight-authority",$os_id));
                $this->AddRDF($this->QQuad($os_id,"rdf:type","clinicaltrials_vocabulary:Oversight-Authority"));
				$this->AddRDF($this->QQuadl($os_id,"rdfs:label",$authority." [$os_id]"));
				$this->AddRDF($this->QQuadl($os_id,"dc:title",$authority));
			} catch(Exception $e){
				echo "There was an error in the oversight info element: $e\n";
			}
			####################################################################################
			# has_dmc
			####################################################################################
			$has_dmc   = @array_shift($over_site->xpath('//has_dmc'));
			if($has_dmc != ""){
				$this->AddRDF($this->QQuadl($os_id,"clinicaltrials_vocabulary:dmc",$has_dmc)); // what's a dmc?
			}

			###################################################################################
			#brief summary
			###################################################################################
			$brief_summary = @array_shift($root->xpath('//brief_summary/textblock'));
			if($brief_summary) $this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:brief-summary",$this->SafeLiteral($brief_summary)));

			####################################################################################
			# detailed description
			####################################################################################
			$detailed_description = @array_shift($root->xpath('//detailed_description/textblock'));
			if($detailed_description) $this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:detailed-description",$this->SafeLiteral($detailed_description)));

			#################################################################################
			# overall status
			#################################################################################
			$overall_status = @array_shift($root->xpath('//overall_status'));
			if($overall_status) {
				$status_id = "clinicaltrials_resource:".md5($overall_status);
				$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:overall-status",$status_id));
				$this->AddRDF($this->QQuad($status_id,"rdf:type","clinicaltrials_vocabulary:Status"));
				$this->AddRDF($this->QQuadL($status_id,"rdfs:label",$overall_status));
			}

			##################################################################################
			# start date
			##################################################################################
			$start_date = @array_shift($root->xpath('//start_date'));
			if($start_date) {
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:start-date",$this->SafeLiteral($start_date)));
			}

			###################################################################################
			# completion date
			##################################################################################
			$completion_date = @array_shift($root->xpath('//completion_date'));
			if($completion_date != ""){
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:completion-date",$this->SafeLiteral($completion_date)));
			}

			####################################################################################
			# primary completion date
			###################################################################################
			$primary_completion_date = @array_shift($root->xpath('//primary_completion_date'));
			if($primary_completion_date != ""){
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:primary-completion-date",$this->SafeLiteral($primary_completion_date)));
			}

			####################################################################################
			# study type
			####################################################################################
			$study_type = @array_shift($root->xpath('//study_type'));
			if($study_type != ""){
				$study_type_id = "clinicaltrials_resource:".md5($study_type);
				$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:study-type",$study_type_id));
				$this->AddRDF($this->QQuad($study_type_id,"rdf:type", "clinicaltrials_vocabulary:Study-Type"));
				$this->AddRDF($this->QQuadL($study_type_id,"rdfs:label", $this->SafeLiteral($study_type)." [$study_type_id]"));
			}

			####################################################################################
			# phase
			####################################################################################
			$phase = @array_shift($root->xpath('//phase'));
			if($phase && $phase != "N/A") $this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:phase",$this->SafeLiteral($phase)));

			###############################################################################
			# study design
			###############################################################################
			$study_design = @array_shift($root->xpath('//study_design'));
			if($study_design) $this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:study-design",$this->SafeLiteral($study_design)));

			################################################################################
			#primary outcome
			###############################################################################
			$primary_outcome = @array_shift($root->xpath('//primary_outcome'));
			if($primary_outcome){
				try{
					$measure         = @array_shift($root->xpath('//primary_outcome/measure'));
					$time_frame      = @array_shift($root->xpath('//primary_outcome/time_frame'));
					$safety_issue    = @array_shift($root->xpath('//primary_outcome/saftey_issue'));	
					$description     = @array_shift($root->xpath('//primary_outcome/description'));
					
					$po_id = "clinicaltrials_resource:".md5($nct_id.$primary_outcome->asXML());
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:primary-outcome",$po_id));
					$this->AddRDF($this->QQuad($po_id,"rdf:type","clinicaltrials_vocabulary:Primary-Outcome"));					
					$this->AddRDF($this->QQuadl($po_id,"rdfs:label",$this->SafeLiteral($measure." ".$time_frame)." [$po_id]"));					
					$this->AddRDF($this->QQuadl($po_id,"clinicaltrials_vocabulary:measure",$this->SafeLiteral($measure)));
					if($description) $this->AddRDF($this->QQuadl($po_id,"dc:description",$this->SafeLiteral($description)));
					if($time_frame) $this->AddRDF($this->QQuadl($po_id,"clinicaltrials_vocabulary:time-frame",$this->SafeLiteral($time_frame)));
					if($safety_issue) $this->AddRDF($this->QQuadl($po_id,"clinicaltrials_vocabulary:safety-issue",$this->SafeLiteral($safety_issue)));
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
					$measure = @array_shift($secondary_outcome->xpath('//measure'));
					$time_frame = @array_shift($secondary_outcome->xpath('//time_frame'));
					$safety_issue = @array_shift($secondary_outcome->xpath('//safety_issue'));
					$so_id = "clinicaltrials_resource:".md5($nct_id.$secondary_outcome->asXML());

					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:secondary-outcome",$so_id));
					$this->AddRDF($this->QQuad($so_id,"rdf:type","clinicaltrials_vocabulary:Secondary-Outcome"));
					$this->AddRDF($this->QQuadl($so_id,"rdfs:label",$this->SafeLiteral($measure." ".$time_frame). "[$so_id]"));					
					$this->AddRDF($this->QQuadl($so_id,"clinicaltrials_vocabulary:measure",$this->SafeLiteral($measure)));
					if($time_frame) $this->AddRDF($this->QQuadl($so_id,"clinicaltrials_vocabulary:time-frame",$this->SafeLiteral($time_frame)));
					if($safety_issue)$this->AddRDF($this->QQuadl($so_id,"clinicaltrials_vocabulary:safety-issue",$this->SafeLiteral($safety_issue)));
				}
			}catch (Exception $e){
				"There was an exception parsing the secondary outcomes element: $e\n";
			}
			##############################################################################
			#number of arms
			##############################################################################
			try {
				$no_of_arms = @array_shift($root->xpath('//number_of_arms'));
				if($no_of_arms != ""){
					$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:number-of-arms",$no_of_arms));
				}
			}catch(Exception $e){
				echo "There was an exception parsing the number of arms element: $e\n";
			}
			##############################################################################
			#enrollment
			##############################################################################
			try{
				$enrollment = @array_shift($root->xpath('//enrollment'));
				if($enrollment) { $this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:enrollment",$this->SafeLiteral($enrollment)));}
			} catch(Exception $e){
				echo "There was an exception parsing the enrollment element: $e\n";
			}
			###############################################################################
			#condition
			###############################################################################
			try {
				$conditions = $root->xpath('//condition');
				foreach($conditions as $condition){
					$mesh_label_id = "clinicaltrials_resource:".md5($this->SafeLiteral($condition));
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:condition",$mesh_label_id));
					$this->AddRDF($this->QQuadl($mesh_label_id,"rdfs:label",$this->SafeLiteral($condition)));
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
					$arm_group_label = @array_shift($arm_group->xpath('./arm_group_label'));
					$arm_group_type = ucfirst(str_replace(" ","_",@array_shift($arm_group->xpath('./arm_group_type'))));
					$description = @array_shift($arm_group->xpath('./description'));

					$arm_group_id = "clinicaltrials_resource:".md5($arm_group->asXML());
                    $this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:arm-group",$arm_group_id));
					$this->AddRDF($this->QQuadl($arm_group_id,"rdfs:label",$this->SafeLiteral($arm_group_label). "[$arm_group_id]"));
					$this->AddRDF($this->QQuad($arm_group_id,"rdf:type","clinicaltrials_vocabulary:".$arm_group_type));
					$this->AddRDF($this->QQuadl($arm_group_id,"rdfs:comment",$this->SafeLiteral($description)));
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
					$intervention_name = @array_shift($intervention->xpath('./intervention_name'));
					$intervention_type = ucfirst(str_replace(" ","_",@array_shift($intervention->xpath('./intervention_type'))));

					$intervention_id = "clinicaltrials_resource:".md5($intervention->asXML());
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:intervention",$intervention_id));
					$this->AddRDF($this->QQuad($intervention_id,"rdf:type","clinicaltrials_vocabulary:".$intervention_type));
					$this->AddRDF($this->QQuadl($intervention_id,"rdfs:label",$this->SafeLiteral($intervention_name)));

					$description = @array_shift($intervention->xpath('./description'));
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
				$eligibility = @array_shift($root->xpath('//eligibility'));

				if($eligibility != null) {

					$eligibility_id = "clinicaltrials_resource:".md5($eligibility->asXML());
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:eligibility",$eligibility_id));
                    $this->AddRDF($this->QQuad($eligibility_id,"rdf:type","clinicaltrials_vocabulary:Eligibility"));

					if($criteria = @array_shift($eligibility->xpath('./criteria'))){
						$text = str_replace("\n\n","",@array_shift($criteria->xpath('./textblock')));
						$c = preg_split("/(Inclusion Criteria\:|Exclusion Criteria\:)/",$text);
						//inclusion
						if(isset($c[1])) {
							$d = explode(" - ",$c[1]); // the lists are separated by a hyphen
							foreach($d AS $inclusion) {
								$inc = trim($inclusion);
								if($inc != '') {
									$inc_id = "clinicaltrials_resource:".md5($inc);
									$this->AddRDF($this->QQuad($eligibility_id,"clinicaltrials_vocabulary:inclusion-criteria",$inc_id));
									$this->AddRDF($this->QQuad($inc_id,"rdf:type","clinicaltrials_vocabulary:Inclusion-Criteria"));
									$this->AddRDF($this->QQuadL($inc_id,"rdfs:label",trim($this->SafeLiteral($inc))));
								}
							}
						}
						//exclusion
						if(isset($c[2])) {
							$d = explode(" - ",$c[1]);
							foreach($d AS $exclusion) {
								$exc = trim($exclusion);
								if($exc != '') {
									$exc_id = "clinicaltrials_resource:".md5($exc);
									$this->AddRDF($this->QQuad($eligibility_id,"clinicaltrials_vocabulary:exclusion-criteria",$exc_id));
									$this->AddRDF($this->QQuad($exc_id,"rdf:type","clinicaltrials_vocabulary:Exclusion-Criteria"));
									$this->AddRDF($this->QQuadL($exc_id,"rdfs:label",trim($this->SafeLiteral($exc))));
								}
							}
						}					
					}

					if($gender = @array_shift($eligibility->xpath('./gender'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:gender",$gender));
					}

					if($minimum_age = @array_shift($eligibility->xpath('./minimum_age'))){
						if($minimum_age != 'N/A') $this->AddRDF($this->QQuadL($eligibility_id,"clinicaltrials_vocabulary:minimum-age",trim(str_replace("Years","",$minimum_age))));
					}

					if($maximum_age = @array_shift($eligibility->xpath('./maximum_age'))){
						if($maximum_age != 'N/A')  $this->AddRDF($this->QQuadL($eligibility_id,"clinicaltrials_vocabulary:maximum-age",trim(str_replace("Years","",$maximum_age))));
					}
					if($healthy_volunteers = @array_shift($eligibility->xpath('./healthy_volunteers'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:healthy-volunteers",$healthy_volunteers));
					}

					if($study_pop = @array_shift($eligibility->xpath('./study_pop'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:study-population",$study_pop->xpath('./textblock')));
					}

					if($sampling_method = @array_shift($eligibility->xpath('./sampling_method'))){
						$this->AddRDF($this->QQuadl($eligibility_id,"clinicaltrials_vocabulary:sampling-method",$sampling_method->xpath('./textblock')));
					}
				}
			}catch(Exception $e){
				echo "There was an error in eligibility: $e\n";
			}

			######################################################################################
			#overall official - the person in charge
			#####################################################################################
			try {
				$overall_official = @array_shift($root->xpath('//overall_official'));
				if($overall_official) {
					$overall_official = "clinicaltrials_resource:".md5($overall_official->asXML());
					$last_name   = @array_shift($root->xpath('//overall_official/last_name'));
					$role        = @array_shift($root->xpath('//overall_official/role'));
					$affiliation = @array_shift($root->xpath('//overall_official/affiliation'));

					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:overall-official",$overall_official));
					$this->AddRDF($this->QQuad($overall_official,"rdf:type","clinicaltrials_vocabulary:Overall-Official"));
					$this->AddRDF($this->QQuadl($overall_official,"clinicaltrials_vocabulary:lastname",$last_name));
					$this->AddRDF($this->QQuadl($overall_official,"clinicaltrials_vocabulary:role",$role));
					$this->AddRDF($this->QQuadl($overall_official,"clinicaltrials_vocabulary:affiliation",$this->SafeLiteral($affiliation)));
				}
			}catch (Exception $e){
				echo "There was an error parsing the overal_official: $e\n";
			}

			##############################################################
			# location of facility doing the testing
			##############################################################
			try {	
				$location = @array_shift($root->xpath('//location'));
				if($location){
					$location_id = "clinicaltrials_resource:".md5($location->asXML());
					$title = @array_shift($location->xpath('//name'));
					$facility = $location->xpath('./facility');
					$address  = @array_shift($location->xpath('//address'));
					
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:location",$location_id));
					$this->AddRDF($this->QQuadl($location_id,"dc:title",$title));
					$this->AddRDF($this->QQuadl($location_id,"rdfs:label",$title." [$location_id]"));
					$this->AddRDF($this->QQuad($location_id,"rdfs:type","clinicaltrials_vocabulary:Location"));

					if($address && ($city = @array_shift($address->xpath('./city'))) != null){
						$this->AddRDF($this->QQuadl($location_id,"clinicaltrials_vocabulary:city",$city));
					}

					if($address && ($state = @array_shift($address->xpath('./state'))) != null){
						$this->AddRDF($this->QQuadl($location_id,"clinicaltrials_vocabulary:state",$state));
					}
					if($address && ($zip = @array_shift($address->xpath('./zip'))) != null){
						$this->AddRDF($this->QQuadl($location_id,"clinicaltrials_vocabulary:zipcode",$zip));
					}

					if( $address && ($country = @array_shift($address->xpath('./country'))) != null ){
						$this->AddRDF($this->QQuadl($location_id,"clinicaltrials_vocabulary:country",$country));
					}


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
					$group_id = "clinicaltrials_resource:".md5($group->asXML());
					$title = @array_shift($group->xpath('./title'));
					$description = @array_shift($group->xpath('./description'));
					$id = $group->attributes()->group_id;
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:group",$group_id));
					$this->AddRDF($this->QQuad($group_id,"rdf:type","clinicaltrials_vocabulary:Group"));
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
					$p = @array_shift($reference->xpath('./PMID'));
					if($p) {
						$pmid = "pubmed:$p";
						$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:reference",$pmid));
						$this->AddRDF($this->QQuadl($pmid,"rdfs:comment",$this->SafeLiteral(@array_shift($reference->xpath('./citation')))));
					}
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
					$p = @array_shift($result_reference->xpath('./PMID'));
					if($p) {
						$pmid = "pubmed:".$p;
						$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:results-reference",$pmid));
						$this->AddRDF($this->QQuadl($pmid,"rdfs:comment",$this->SafeLiteral(@array_shift($result_reference->xpath('./citation')))));
					}
				}
			}catch(Exception $e){
				echo "There was an error parsing results_references element: $e\n";
			}

			##########################################################################
			#verification date
			#########################################################################
			try{
				$verification_date  = @array_shift($root->xpath('//verification_date'));
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:verification-date",$verification_date));
				
				$lastchanged_date   = @array_shift($root->xpath('//lastchanged_date'));
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:last-changed-date",$lastchanged_date));
				
				$firstreceived_date = @array_shift($root->xpath('//firstreceived_date'));
				$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:first-received-date",$firstreceived_date));
			} catch(Exception $e){
				echo "There was an error parsing the verification_date element: $e\n";
			}

			############################################################################
			#responsible party
			############################################################################
			try{
				$responsible_party = @array_shift($root->xpath('//responsible_party'));
				if($responsible_party){
					$name_title        = $root->xpath('//responsible_party/name_title');
					$organization      = $root->xpath('//responsible_party/organization');

					$rp_id = "clinicaltrials_resource:".md5($responsible_party->asXML());

					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:responsible-party",$rp_id));
					$this->AddRDF($this->QQuad($rp_id,"rdf:type","clinicaltrials_vocabulary:Responsible-Party"));
					$this->AddRDF($this->QQuadl($rp_id,"rdfs:label","$name_title, $organization [$rp_id]"));
					$this->AddRDF($this->QQuadl($rp_id,"clinicaltrials_vocabulary:name-title",$name_title));
					$this->AddRDF($this->QQuadl($rp_id,"clinicaltrials_vocabulary:organization",$organization));
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
					$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:keyword",$keyword));
				}
			}catch(Exception $e){
				echo "There was an error parsing the keywords element: $e";
			}

			# mesh terms 
			# note: mesh terms are assigned using an imperfect algorithm
			try{
				$mesh_terms = $root->xpath('//condition_browse/mesh_term');
				foreach($mesh_terms as $mesh_term){
					$mesh_id = "clinicaltrials_resource:".md5($mesh_term);
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:condition-mesh",$mesh_id));
					$this->AddRDF($this->QQuadl($mesh_id,"rdfs:label",$mesh_term));
				}
			}catch(Exception $e){
				echo "There was an error in mesh_terms: $e\n";
			}

			###############################################################################
			# mesh terms for hte invervention browse
			###############################################################################
			try {
				$mesh_terms = $root->xpath('//intervention_browse/mesh_term');
				foreach($mesh_terms as $mesh_label){
					$mesh_label_id = "clinicaltrials_resource:".md5($mesh_label);
					$this->AddRDF($this->QQuad($study_id,"clinicaltrials_vocabulary:intervention_mesh",$mesh_label_id));
					$this->AddRDF($this->QQuadl($mesh_label_id,"rdfs:label",$this->SafeLiteral($mesh_label)));
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
				$regulated = @array_shift($root->xpath('is_fda_regulated'));
				if($regulated != ""){
					$this->AddRDF($this->QQuadl($study_id,"clinicaltrials_vocabulary:is-fda-regulated",$regulated));
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
