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
 @version :: 0.3
 @description ::  clinicaltrials.gov parser
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../../php-lib/xmlapi.php');

class ClinicalTrialsParser extends Bio2RDFizer
{
	function __construct($argv){
		parent::__construct($argv,"clinicaltrials");
	
		parent::addParameter('files',true,'all|study|results','all','files to process');
		parent::addParameter('download_url',false,null,'http://clinicaltrials.gov/ct2/crawl');
		
		parent::initialize();
	}

	function run(){
		if(parent::getParameterValue('download') === true) 
		{
			$this->crawl();
		}
		if(parent::getParameterValue('process') === true) 
		{
			$this->parse_dir();
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
		$crawl_url = parent::getParameterValue("download_url"); //"http://clinicaltrials.gov/ct2/crawl";
		echo "Fetching clinical trial list...".PHP_EOL;
		$html = file_get_contents($crawl_url);
		if($html === FALSE) {
			trigger_error("unable to get crawl file");
			return false;
		}
		echo "done.".PHP_EOL;

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
		echo "Fetching record block...".PHP_EOL;
		$html = file_get_contents($url);
		if($html === FALSE) {
			trigger_error("unable to fetch record block at $url",E_USER_ERROR);
			return false;
		}
		echo "done.".PHP_EOL;
		
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
		$outfile = parent::getParameterValue("indir")."/".$file.".xml";
		echo "fetching $url".PHP_EOL;
		$xml = file_get_contents($url);
		
		# save the file
		$ret = file_put_contents($outfile,$xml);
		if($ret === FALSE) {
			trigger_error("unable to save $outfile");
			return false;
		}
	}
	
	
	/** parse directory of files */
	function parse_dir(){
		$ignore = array("..",'.','.DS_STORE',"0");
		$this->setCheckPoint('dataset');
		
		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");

		$dataset_file = parent::getParameterValue("outdir").parent::getBio2RDFReleaseFile();
		$fp = fopen($dataset_file,"w");
		if($fp === FALSE) {
			trigger_error("Unable to open $dataset_file",E_USER_ERROR);
			return false;
		}
		$ids = explode(",",parent::getParameterValue('id_list'));
		
		$indir = parent::getParameterValue('indir');
		if($handle = opendir($indir)) {
			echo "Processing directory $indir\n";

			$outfile = "clinicaltrials.".parent::getParameterValue('output_format');
			$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
			parent::setWriteFile(parent::getParameterValue("outdir").$outfile,$gz);
					
			while(($file = readdir($handle)) !== false){
				if (in_array($file,$ignore) || is_dir($file) ) continue;
				$trial_id = basename($file,'.xml');
				if(parent::getParameterValue('id_list') == '' || in_array($trial_id, $ids)) {
					echo "Processing $file".PHP_EOL;					
					$this->process_file($file);
		
					// make the dataset description
					$ouri = parent::getGraphURI(parent::getDatasetURI());
					parent::setGraphURI(parent::getDatasetURI());
					
					$rfile = "http://clinicaltrials.gov/ct2/show/".$trial_id."?resultsxml=true";
					$source_version = parent::getDatasetVersion();
					// dataset description
					$source_file = (new DataResource($this))
					->setURI($rfile)
					->setTitle("Clinicaltrials")
					->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($indir.$file)))
					->setFormat("application/xml")
					->setPublisher("http://clinicaltrials.gov/")
					->setHomepage("http://clinicaltrials.gov/")
					->setRights("use")
					->setRights("by-attribution")
					->setLicense("http://clinicaltrials.gov/ct2/about-site/terms-conditions")
					->setDataset("http://identifiers.org/clinicaltrials/");
					
					parent::writeToReleaseFile($source_file->toRDF());
					parent::setGraphURI(parent::setDatasetURI($ouri));
				}
			}
			echo "Finished\n.";
			closedir($handle);
			
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2df.org/release/$bVersion/$prefix/$outfile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix v$source_version")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/clinicaltrials/clinicaltrials.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")			
				->setRights("use-share-modify")
				->setRights("by-attribution")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons.org/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());

			$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
			if($gz) $output_file->setFormat("application/gzip");
			if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
			else $output_file->setFormat("application/n-quads");
			
			parent::writeToReleaseFile($output_file->toRDF());
			parent::closeReleaseFile();

			// write the dataset description file
			fclose($fp);
		}
	}
	
	/**
	* process a results xml file from the download directory
	**/
	function process_file($infile) {
		$indir = parent::getParameterValue('indir');

		$xml = new CXML($indir,basename($infile));
		$this->setCheckPoint('file');
		while($xml->Parse("clinical_study") == TRUE) {
			$this->setCheckPoint('record');
			$this->root = $root = $xml->GetXMLRoot();
			
			$nct_id = $this->getString("//id_info/nct_id");
			$study_id = parent::getNamespace()."$nct_id";
			
			##########################################################################################
			#brief title
			##########################################################################################
			$brief_title = $this->getString("//brief_title");
			if($brief_title != ""){
				parent::addRDF(
					parent::triplifyString($study_id, parent::getVoc()."brief-title",$brief_title)
				);
			}

			##########################################################################################
			#official title
			##########################################################################################
			$official_title = $this->getString("//official_title");
			if($official_title != "") {
				parent::addRDF(
					parent::triplifyString($study_id,parent::getVoc()."official-title",$official_title)
				);
			}
			if($brief_title != '') $label = $brief_title;
			if(!$label && $official_title != '') $label = $official_title;
			if(!$label) $label = "clinical trial #".$nct_id;
			
			###################################################################################
			#brief summary
			###################################################################################
			$brief_summary = $this->getString('//brief_summary/textblock');
			$brief_summary = str_replace(array("\r","\n","\t","      "),"",trim($brief_summary));
			if($brief_summary) {
				parent::addRDF(
					parent::triplifyString($study_id,$this->getVoc()."brief-summary",$brief_summary)
				);
			}
			
			// we have enough to describe the study
			parent::addRDF(
				parent::describeIndividual($study_id,$label,parent::getVoc()."Clinical-Study", $official_title,$brief_summary).
				parent::describeClass(parent::getVoc()."Clinical-Study","Clinical Study")
			);

			#########################################################################################
			# study ids
			#########################################################################################
			$org_study_id = $this->getString("//id_info/org_study_id");
			$secondary_id = $this->getString("//id_info/secondary_id");

			parent::addRDF(
				parent::triplifyString($study_id,parent::getVoc()."organization-study-identifier",$org_study_id).
				parent::triplifyString($study_id,parent::getVoc()."secondary-identifier",$secondary_id)
			);

			#########################################################################################
			#acronym
			#########################################################################################
			$acronym = $this->getString("//acronym");
			if($acronym != ""){
				parent::addRDF(
					parent::triplifyString($study_id,parent::getVoc()."acronym",$acronym)
				);
			}

			########################################################################################
			#lead_sponsor
			########################################################################################
			try {
				$lead_sponsor = @array_shift($root->xpath('//sponsors/lead_sponsor'));
				$lead_sponsor_id = parent::getRes().md5($lead_sponsor->asXML());
				
				$agency       = $this->getString("//sponsors/lead_sponsor/agency");
				$agency_class = $this->getString("//sponsors/lead_sponsor/agency_class");
				
                parent::addRDF(
					parent::triplify($study_id, parent::getVoc()."lead-sponsor", $lead_sponsor_id).
					parent::describeClass($lead_sponsor_id,$agency, parent::getVoc()."Organization",$agency).
					parent::triplifyString($lead_sponsor_id,parent::getVoc()."agency-class",$agency_class)
				);
			}catch( Exception $e){
				echo "There was an error in the lead sponsor element: $e\n";
			}
	
			######################################################################################
			# oversight
			######################################################################################
			try {
				$oversight = @array_shift($root->xpath('//oversight_info'));
				$oversight_id = parent::getRes().md5($oversight->asXML());
				$authority = $this->getString('//oversight_info/authority');
                parent::addRDF(	
					parent::triplify($study_id,$this->getVoc()."oversight-authority",$oversight_id).
					parent::describeIndividual($oversight_id,$authority,parent::getVoc()."Organization",$authority)
				);
			} catch(Exception $e){
				echo "There was an error in the oversight info element: $e\n";
			}
			
			####################################################################################
			# dmc
			####################################################################################
			$dmc   = $this->getString('//has_dmc');
			if($dmc != ""){
				parent::addRDF(
					parent::triplifyString($study_id,parent::getVoc()."dmc",$dmc)
				);
			}	

			####################################################################################
			# detailed description
			####################################################################################
			$detailed_description = $this->getString('//detailed_description/textblock');
			if($detailed_description) {
				$d = str_replace(array("\r","\n","\t","      "),"",trim($detailed_description));
				parent::addRDF(
					parent::triplifyString($study_id,parent::getVoc()."detailed-description",$d)
				);
			}

			#################################################################################
			# overall status
			#################################################################################
			$overall_status = @array_shift($root->xpath('//overall_status'));
			if($overall_status) {
				$status_id = "clinicaltrials_resource:".md5($overall_status);
				parent::addRDF(
					parent::triplify($study_id,parent::getVoc()."overall-status",$status_id).
					parent::describeIndividual($status_id,$overall_status,parent::getVoc()."Status", $overall_status)
				);
			}

			##################################################################################
			# start date
			##################################################################################
			$start_date = $this->getString('//start_date');
			if($start_date) {
				// July 2002
				$datetime = $this->getDatetimeFromDate($start_date);
				if(isset($datetime)) {
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."start-date",$datetime)
					);
				} else {
					trigger_error("unable to parse start date: $start_date",E_USER_ERROR);
				}
			}
			

			###################################################################################
			# completion date
			##################################################################################
			$completion_date = $this->getString('//completion_date');
			if($completion_date){
				$datetime = $this->getDatetimeFromDate($completion_date);
				if(isset($datetime)) {
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."completion-date",$datetime)
					);
				} else {
					trigger_error("unable to parse completion date: $completion_date",E_USER_ERROR);
				}
			}

			####################################################################################
			# primary completion date
			###################################################################################
			$primary_completion_date = $this->getString('//primary_completion_date');
			if($primary_completion_date){
				$datetime = $this->getDatetimeFromDate($primary_completion_date);
				if(isset($datetime)) {
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."primary-completion-date",$datetime)
					);
				} else {
					trigger_error("unable to parse completion date: $primary_completion_date",E_USER_ERROR);
				}
			}

			####################################################################################
			# study type
			####################################################################################
			$study_type = $this->getString('//study_type');
			if($study_type){
				$study_type_id = $this->getRes().md5($study_type);
				parent::addRDF(
					parent::describeClass($study_type_id,$study_type,parent::getVoc()."Study").
					parent::triplify($study_id,parent::getVoc()."study-type",$study_type_id)
				);
			}

			####################################################################################
			# phase
			####################################################################################
			$phase = $this->getString('//phase');
			if($phase && $phase != "N/A") {
				$phase_id = $this->getRes().md5($phase);
				parent::addRDF(
					parent::describeIndividual($phase_id,$phase,parent::getVoc()."Clinical-Phase",$phase).
					parent::triplify($study_id,parent::getVoc()."phase",$phase_id)
				);
			}

			###############################################################################
			# study design
			###############################################################################
			$study_design = $this->getString('//study_design');
			if($study_design) {
				$study_design_id = parent::getRes().md5($study_design);
				parent::addRDF(
					parent::describeIndividual($study_design_id,"study design for $study_id",parent::getVoc()."Study-Design").
					parent::triplify($study_id,parent::getVoc()."study-design",$study_design_id)
				);
				// Intervention Model: Parallel Assignment, Masking: Double-Blind, Primary Purpose: Treatment
				foreach(explode(", ",$study_design) AS $b) {
					$c = explode(":  ",$b);
					$key = parent::getRes().md5($c[0]);
					if(isset($c[1])) {
					$value = parent::getRes().md5($c[1]);
					parent::addRDF(
						parent::describeClass($value,$c[1],parent::getVoc()."Study-Design-Parameter",$c[1]).
						parent::describeObjectProperty($key,$c[0],null,$c[0]).
						parent::triplify($study_design_id,$key, $value)
					);
					}
				}
			}

			################################################################################
			#primary outcome
			###############################################################################
			$primary_outcome = @array_shift($root->xpath('//primary_outcome'));
			if($primary_outcome){
				try{
					$po_id = parent::getRes().md5($nct_id.$primary_outcome->asXML());
					
					$measure         = $this->getString('//primary_outcome/measure');
					$time_frame      = $this->getString('//primary_outcome/time_frame');
					$safety_issue    = $this->getString('//primary_outcome/saftey_issue');	
					$description     = $this->getString('//primary_outcome/description');
					
					parent::addRDF(
						parent::describeClass($po_id,$measure." ".$time_frame, parent::getVoc()."Primary-Outcome",$description).
						parent::triplify($study_id, parent::getVoc()."primary-outcome",$po_id).
						parent::triplifyString($po_id, parent::getVoc()."measure", $measure)
					);					
					if($time_frame) {
						parent::addRDF(
							parent::triplifyString($po_id,parent::getVoc()."time-frame",$time_frame)
						);
					}
					if($safety_issue) {
						parent::addRDF(
							parent::triplifyString($po_id,parent::getVoc()."safety-issue",$safety_issue)
						);
					}
				}catch(Exception $e){
					echo "There was an error parsing the primary outcome element: $e \n";
				}
			}

			#################################################################################
			#secondary outcome
			#################################################################################
			try{
				$secondary_outcomes = $root->xpath('//secondary_outcome');
				foreach($secondary_outcomes as $so){
					$so_id = parent::getRes().md5($nct_id.$so->asXML());
					
					$measure = $this->getString('//measure',$so);
					$time_frame = $this->getString('//time_frame',$so);
					$safety_issue = $this->getString('//safety_issue',$so);
					
					parent::addRDF(
						parent::describeClass($so_id,$measure." ".$time_frame,parent::getVoc()."Secondary-Outcome").
						parent::triplify($study_id, parent::getVoc()."secondary-outcome",$so_id).
						parent::triplifyString($study_id, parent::getVoc()."measure",$measure)
					);

					if($time_frame) {
						parent::addRDF(
							parent::triplifyString($so_id,parent::getVoc()."time-frame",$time_frame)
						);
					}
					if($safety_issue) {
						parent::addRDF(
							parent::triplifyString($so_id,parent::getVoc()."safety-issue",$safety_issue)
						);
					}
				}
			}catch (Exception $e){
				"There was an exception parsing the secondary outcomes element: $e\n";
			}

			##############################################################################
			#number of arms
			##############################################################################
			try {
				$no_of_arms = $this->getString('//number_of_arms');
				if($no_of_arms){
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."number-of-arms",$no_of_arms)
					);
				}
			}catch(Exception $e){
				echo "There was an exception parsing the number of arms element: $e\n";
			}
		
			##############################################################################
			#enrollment
			##############################################################################
			try{
				$enrollment = $this->getString('//enrollment');
				if($enrollment) { 
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."enrollment",$enrollment)
					);
				}
			} catch(Exception $e){
				echo "There was an exception parsing the enrollment element: $e\n";
			}
			
			###############################################################################
			#condition
			###############################################################################
			try {
				$conditions = $root->xpath('//condition');
				foreach($conditions as $condition){
					$mesh_label_id = parent::getRes().md5($condition);
					parent::addRDF(
						parent::triplify($study_id,parent::getVoc()."condition",$mesh_label_id).
						parent::describeClass($mesh_label_id,$condition,parent::getVoc()."Condition")
					);
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
					$arm_group_id = parent::getRes().md5($arm_group->asXML());
					$arm_group_label = $this->getString('./arm_group_label',$arm_group);
					$arm_group_type = ucfirst(str_replace(" ","_",$this->getString('./arm_group_type',$arm_group)));
					if(!$arm_group_type) $arm_group_type = "Clinical-Arm";
					$description = $this->getString('./description',$arm_group);

                    parent::addRDF(
						parent::triplify($study_id,parent::getVoc()."arm-group",$arm_group_id).
						parent::describeIndividual($arm_group_id,$arm_group_label,parent::getVoc().$arm_group_type,$arm_group_label,$description)
					);
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
					$intervention_id = parent::getRes().md5($intervention->asXML());
					$intervention_name = $this->getString('./intervention_name',$intervention);
					$intervention_type = ucfirst(str_replace(" ","_",$this->getString('./intervention_type',$intervention)));
					$description = $this->getString('./description',$intervention);
					
					parent::addRDF(
						parent::triplify($study_id,parent::getvoc()."intervention",$intervention_id).
						parent::describeClass($intervention_id,$intervention_name,parent::getVoc().$intervention_type,$intervention_name,$description)
					);
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
					$eligibility_label = "eligibility for ".$study_id;
					$eligibility_id = parent::getRes().md5($eligibility->asXML());
					parent::addRDF(
						parent::describeIndividual($eligibility_id,$eligibility_label,parent::getVoc()."Eligibility").
						parent::triplify($study_id,parent::getVoc()."eligibility",$eligibility_id)			
					);

					if($criteria = @array_shift($eligibility->xpath('./criteria'))){
						$text = str_replace("\n\n","",@array_shift($criteria->xpath('./textblock')));
						$c = preg_split("/(Inclusion Criteria\:|Exclusion Criteria\:)/",$text);
						//inclusion
						if(isset($c[1])) {
							$d = explode(" - ",$c[1]); // the lists are separated by a hyphen
							foreach($d AS $inclusion) {
								$inc = trim($inclusion);
								if($inc != '') {
									$inc_id = parent::getRes().md5($inc);
									parent::addRDF(
										parent::triplify($eligibility_id,parent::getVoc()."inclusion-criteria",$inc_id).
										parent::describeIndividual($inc_id,$inc,parent::getVoc()."Inclusion-Criteria")
									);
								}
							}
						}
						//exclusion
						if(isset($c[2])) {
							$d = explode(" - ",$c[1]);
							foreach($d AS $exclusion) {
								$exc = trim($exclusion);
								if($exc != '') {
									$exc_id = parent::getRes().md5($exc);
									parent::addRDF(
										parent::triplify($eligibility_id,parent::getVoc()."exclusion-criteria",$exc_id).
										parent::describeIndividual($exc_id,$exc,parent::getVoc()."Exclusion-Criteria")
									);
								}
							}
						}					
					}
					
					if($gender = $this->getString('./gender',$eligibility)) {
						parent::addRDF(
							parent::triplifyString($eligibility_id,parent::getVoc()."gender",$gender)
						);
					}
					if($healthy_volunteers = $this->getString('./healthy_volunteers',$eligibility)){
						parent::addRDF(
							parent::triplifyString($eligibility_id,parent::getVoc()."healthy-volunteers",$healthy_volunteers)
						);
					}

					$attributes = array('minimum_age','maximum_age');
					foreach($attributes AS $a) {
						$s = $this->getString('./'.$a,$eligibility);
						if($s != 'N/A') {
							$age = trim(str_replace("Years","",$s));
							parent::addRDF(
								parent::triplifyString($eligibility_id,parent::getVoc().str_replace("_","-",$a),$age)
							);
						}
					}

					$attributes = array("study_pop"=>"study-population","sampling_method"=>"sampling-method");
					foreach($attributes AS $a => $r) {
						$e = @array_shift($eligibility->xpath('./'.$a));
						if($s = $this->getString('./'.$a,$eligibility)){
							parent::addRDF(
								parent::triplifyString($eligibility_id,parent::getVoc().$r,$this->getString('./textblock',$e))
							);
						}
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
					$overall_official = parent::getRes().md5($overall_official->asXML());
					$last_name   = $this->getString('//overall_official/last_name');
					$role        = $this->getString('//overall_official/role');
					$affiliation = $this->getString('//overall_official/affiliation');

					parent::addRDF(parent::triplify($study_id,parent::getVoc()."overall-official",$overall_official));
					parent::addRDF(parent::triplify($overall_official,"rdf:type",parent::getVoc()."Overall-Official"));
					parent::addRDF(parent::triplifyString($overall_official,parent::getVoc()."lastname",$last_name));
					parent::addRDF(parent::triplifyString($overall_official,parent::getVoc()."role",$role));
					parent::addRDF(parent::triplifyString($overall_official,parent::getVoc()."affiliation",$affiliation));
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
					$location_id = parent::getRes().md5($location->asXML());
					$title = $this->getString('//name',$location);
					$facility = $location->xpath('./facility');
					$address  = @array_shift($location->xpath('//address'));
					
					parent::addRDF(parent::triplify($study_id,parent::getVoc()."location",$location_id));
					parent::addRDF(parent::describeIndividual($location_id,$title,parent::getVoc()."Location",$title));

					if($address && ($city =  $this->getString('./city',$address)) != null){
						parent::addRDF(parent::triplifyString($location_id,parent::getVoc()."city",$city));
					}

					if($address && ($state = $this->getString('./state',$address)) != null){
						parent::addRDF(parent::triplifyString($location_id,parent::getVoc()."state",$state));
					}
					if($address && ($zip =  $this->getString('./zip',$address)) != null){
						parent::addRDF(parent::triplifyString($location_id,parent::getVoc()."zipcode",$zip));
					}

					if( $address && ($country =  $this->getString('./country',$address)) != null ){
						parent::addRDF(parent::triplifyString($location_id,parent::getVoc()."country",$country));
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
					$group_id = parent::getRes().md5($group->asXML());
					
					$title = $this->getString('./title',$group);
					$description = $this->getString('./description',$group);
					$id = $group->attributes()->group_id;
					parent::addRDF(parent::triplify($study_id,parent::getVoc()."group",$group_id));
					parent::addRDF(parent::describeIndividual($group_id,$title,parent::getVoc()."Group",$title,$description));
				}
			}catch(Exception $e){
				echo "There was an exception parsing groups xml element: $e\n";
			}

			######################################################################
			#reference
			######################################################################
			try {
				$references = $root->xpath('//reference');
				foreach($references as $reference){
					$p = $this->getString('./PMID',$reference);
					if($p) {
						$pmid = "pubmed:$p";
						parent::addRDF(parent::triplify($study_id,parent::getVoc()."reference",$pmid));
						parent::addRDF(parent::triplifyString($pmid,"rdfs:comment",$this->getString('./citation',$reference)));
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
					$p = $this->getString('./PMID',$result_reference);
					if($p) {
						$pmid = "pubmed:".$p;
						parent::addRDF(parent::triplify($study_id,parent::getVoc()."results-reference",$pmid));
						parent::addRDF(parent::triplifyString($pmid,"rdfs:comment",$this->getString('./citation',$result_reference)));
					}
				}
			}catch(Exception $e){
				echo "There was an error parsing results_references element: $e\n";
			}

			##########################################################################
			#verification date
			#########################################################################
			try{
				$verification_date  = $this->getString('//verification_date');
				
				if($verification_date){
					$date = $this->getDatetimeFromDate($verification_date);
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."verification-date",$date)
					);
				}
				
				$lastchanged_date   = $this->getString('//lastchanged_date');
				if($lastchanged_date) {
					$date = $this->getDatetimeFromDate($lastchanged_date);
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."last-changed-date",$date)
					);
				}
				
				$firstreceived_date = $this->getString('//firstreceived_date');
				if($firstreceived_date) {
					$date = $this->getDatetimeFromDate($firstreceived_date);
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."first-received-date",$date)
					);
				}
				
			} catch(Exception $e){
				echo "There was an error parsing the verification_date element: $e\n";
			}

			############################################################################
			#responsible party
			############################################################################
			try{
				$responsible_party = @array_shift($root->xpath('//responsible_party'));
				if($responsible_party){
					$rp_id = parent::getRes().md5($responsible_party->asXML());
					$name_title        = $this->getString('//responsible_party/name_title');
					$organization      = $this->getString('//responsible_party/organization');
					$party_type        = $this->getString('//responsible_party/party_type');
					$label = '';
					if($name_title)   $label  = $name_title;
					if($organization) $label .= (($name_title !== '')?", ":"").$organization;
					if(!$label && $party_type) $label = $party_type;
					
					parent::addRDF(
						parent::triplify($study_id,parent::getVoc()."responsible-party",$rp_id).
						parent::describeIndividual($rp_id,$label,parent::getVoc()."Responsible-Party")
					);
					if($party_type) parent::addRDF(parent::triplifyString($rp_id,parent::getVoc()."party-type",$party_type));
					if($name_title) parent::addRDF(parent::triplifyString($rp_id,parent::getVoc()."name-title",$name_title));
					if($organization) parent::addRDF(parent::triplifyString($rp_id,parent::getVoc()."organization",$organization));
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
					parent::addRDF(parent::triplifyString($study_id,parent::getVoc()."keyword",(string)$keyword));
				}
			}catch(Exception $e){
				echo "There was an error parsing the keywords element: $e";
			}

			# mesh terms 
			# note: mesh terms are assigned using an imperfect algorithm
			try{
				$mesh_terms = $root->xpath('//condition_browse/mesh_term');
				foreach($mesh_terms as $mesh_term){
					$term = (string)$mesh_term;
					$mesh_id = parent::getRes().md5($term);
					parent::addRDF(parent::triplify($study_id,parent::getVoc()."condition-mesh",$mesh_id));
					parent::addRDF(parent::triplifyString($mesh_id,"rdfs:label",$term));
				}
			}catch(Exception $e){
				echo "There was an error in mesh_terms: $e\n";
			}

			###############################################################################
			# mesh terms for the invervention browse
			###############################################################################
			try {
				$mesh_terms = $root->xpath('//intervention_browse/mesh_term');
				foreach($mesh_terms as $mesh_label){
					$term = (string)$mesh_label;
					$mesh_label_id = parent::getRes().md5($term);
					parent::addRDF(parent::triplify($study_id,parent::getVoc()."intervention_mesh",$mesh_label_id));
					parent::addRDF(parent::triplifyString($mesh_label_id,"rdfs:label",$term));
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
				$regulated = $this->getString('is_fda_regulated');
				if($regulated != ""){
					parent::addRDF(parent::triplifyString($study_id,parent::getVoc()."is-fda-regulated",$regulated));
				}
			} catch (Exception $e){
				echo "There was an error parsing the is_fda_regulated element: $e\n";
			}
			
			parent::writeRDFBufferToWriteFile();
		}
		$this->setCheckPoint('record');
		$this->setCheckPoint('dataset');
	}
	
	function getString($xpath,$element = null)
	{
		$o = $this->root;
		if(isset($element)) $o = $element;
		$r = @array_shift($o->xpath($xpath));
		return ((string)$r[0]);
	}
	
	public function getMonthNumber($month)
	{
		$months = array(
			"January"  => "01",
			"February" => "02",
			"March" => "03",
			"April" => "04",
			"May" => "05",
			"June" => "06",
			"July" => "07",
			"August" => "08",
			"September" => "09",
			"October" =>  10,
			"November" => 11,
			"December" => 12
		);
		if(isset($months[$month])) {
			return $months[$month];
		} else {
			trigger_error("don't recognize $month",E_USER_ERROR);
			return null;
		}
	}
	
	public function getDatetimeFromDate($date)
	{
		preg_match("/([A-Za-z]+)( [0-9]+,)? ([0-9]{4})/",$date,$m);
		$month = "01"; $day = "01"; 
		if(isset($m[1]) && $m[1]) $month = $this->getMonthNumber($m[1]);
		if(isset($m[2]) && $m[2]) {
			$day = substr($m[2], 1,-1);
			$day = str_pad($day,2,"0",STR_PAD_LEFT);
		}
		if(isset($m[3])) {
			$year = $m[3];
			$datetime = $year.'-'.$month.'-'.$day.'T00:00:00Z';
			return $datetime;
		} 
		trigger_error("unable to get date from $date",E_USER_ERROR);
		return null;
	}
}
?>
