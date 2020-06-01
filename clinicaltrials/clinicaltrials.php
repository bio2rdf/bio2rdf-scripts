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
 @version :: 0.4
 @description ::  clinicaltrials.gov parser
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../../php-lib/xmlapi.php');

class ClinicalTrialsParser extends Bio2RDFizer
{
	function __construct($argv)
	{
		parent::__construct($argv,"clinicaltrials");
		parent::addParameter('files',true,'all','all','files to process');
		parent::addParameter('download_url',false,null,'https://clinicaltrials.gov/AllPublicXML.zip');
		parent::initialize();
	}

	function run()
	{
		$ldir = parent::getParameterValue('indir');
		$tdir = $ldir."clinicaltrials";
		$odir = parent::getParameterValue('outdir');

		$lfile = $ldir.'clinicaltrials.zip'; # giving it this local file name
		$rfile = parent::getParameterValue('download_url');
		if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
			#download and extract to temp dir
			$ret = utils::downloadSingle($rfile,$lfile);
			if($ret === false) {
				trigger_error("unable to download $file", E_USER_ERROR);
			}
			$zip = new ZipArchive();
			if ($zip->open($lfile) === FALSE) {
				trigger_error("Unable to open $lfile");
				exit;
			}
			$zip->extractTo($tdir);
			$zip->close();
		}

		$file_set = false;
		$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
		if(parent::getParameterValue('id_list') != '') {
			$id_list = explode(",",parent::getParameterValue('id_list'));
			$ofile = "bio2rdf-clinicaltrials-selected-ids.".parent::getParameterValue('output_format');
			parent::setWriteFile($odir.$ofile, $gz);
			$file_set = true;
		}
		
		#$ofile = "bio2rdf-clinicaltrials.".parent::getParameterValue('output_format');
		#parent::setWriteFile($odir.$ofile, $gz);

		$finished = false;
		$d = dir($tdir);
		$n = 0; $ftotal = 0;
		while (false !== ($dir = $d->read())) {
			if($dir == '.' or $dir == '..' or $dir == "Contents.txt") continue;

			$edir = $tdir."/".$dir;
			
			$d2 = dir($edir);
			while (false !== ($e2 = $d2->read())) {
				if($e2 == '.' or $e2 == '..') continue;
				
				$f = $edir."/$e2";
				$e = basename($e2,'.xml');
				if(!isset($id_list)) {
					$n++;
					if(($n % 10000) == 1) {
						if(parent::getWriteFile() != null) {
							#if($ftotal == 3) {$finished=true;break;}
							parent::getWriteFile()->close();
						}
						$ftotal ++;
						$ofile = "bio2rdf-clinicaltrials-".str_pad($ftotal, 3, "0", STR_PAD_LEFT).".".parent::getParameterValue('output_format');
						parent::setWriteFile($odir.$ofile, $gz);
						echo $ofile.PHP_EOL;
					}
					$this->process_file($f);
				} else if(in_array($e, $id_list)) {
					echo "processing $e2".PHP_EOL;
					$this->process_file($f);
					$key = array_search($e, $id_list);
					unset($id_list[$key]);
					if(count($id_list) == 0) $finished = true;
				}
				if($finished == true) break;
			}
			$d2->close();
			if($finished == true) break;
		}
		$d->close();


		echo "Finished.".PHP_EOL;
		parent::getWriteFile()->close();
		exit;
		// make the dataset description
		parent::setGraphURI(parent::getDatasetURI());

		$source_version = parent::getDatasetVersion();
		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");		

		// dataset description
		$source_file = (new DataResource($this))
			->setURI($rfile)
			->setTitle("Clinicaltrials")
			->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
			->setFormat("application/xml")
			->setPublisher("http://clinicaltrials.gov/")
			->setHomepage("http://clinicaltrials.gov/")
			->setRights("use")
			->setRights("by-attribution")
			->setLicense("http://clinicaltrials.gov/ct2/about-site/terms-conditions")
			->setDataset("http://identifiers.org/clinicaltrials/");

		parent::writeToReleaseFile($source_file->toRDF());

		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
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


	/**
	* process a results xml file from the download directory
	**/
	function process_file($entry) {
		$xml = new CXML($entry);

		$this->setCheckPoint('file');
		while($xml->Parse("clinical_study") == TRUE) {
			$this->setCheckPoint('record');
			$this->root = $root = $xml->GetXMLRoot();
			$this->nct_id = $nct_id = $this->getString("//id_info/nct_id");
			$this->study_id = $study_id = parent::getNamespace()."$nct_id";

			### declare
			$label = $this->getString("//brief_title");
			if(!$label) $label = $this->getString("//official_title");
			if(!$label) $label = "Clinical trial #".$nct_id;

			$label = trim(preg_replace("/\s+/",' ',$label));
			parent::addRDF(
				parent::describeIndividual($study_id, $label, parent::getVoc()."Clinical-Study").
				parent::describeClass(parent::getVoc()."Clinical-Study","Clinical Study")
			);
			
			##########################################################################################
			#required header
			##########################################################################################
			parent::addRDF(
				parent::triplifyString($study_id, parent::getVoc()."download-date", $this->getString('//required_header/download_date')).
				parent::triplify($study_id, parent::getVoc()."url", $this->getString('//required_header/url'))
			);

			##########################################################################################
			#identifiers
			##########################################################################################
			parent::addRDF(
				parent::triplifyString($study_id, parent::getVoc()."nct-id", $this->getString('//id_info/nct_id'), "xsd:string").
				parent::triplifyString($study_id, parent::getVoc()."org-study-id", $this->getString('//id_info/org_study_id'), "xsd:string")
			);

			
			$sids = $root->xpath('//id_info/secondary_id');
			if(isset($sids)) {
				foreach($sids AS $id) {
					parent::addRDF(
						parent::triplifyString($study_id, parent::getVoc()."secondary-id", $this->safeString($id), "xsd:string")
					);
				}
			}
			$nctaliases = $root->xpath('//id_info/nct-alias');
			if(isset($nctaliases)) {
				foreach($nctaliases AS $id) {
					parent::addRDF(
						parent::triplifyString($study_id, parent::getVoc()."nct-alias", $this->safeString($id), "xsd:string")
					);
				}		
			}
			
			##########################################################################################
			#titles
			##########################################################################################
			$x = $this->getString("//brief_title");
			$brief_title = trim(preg_replace("/\s+/",' ',$x));

			$x = $this->getString("//official_title");
			$official_title = trim(preg_replace("/\s+/",' ',$x));

			parent::addRDF(
				parent::triplifyString($study_id, parent::getVoc()."brief-title",$brief_title).
				parent::triplifyString($study_id,parent::getVoc()."official-title",$official_title)
			);

			###################################################################################
			#brief summary
			###################################################################################
			$x = $this->getString('//brief_summary/textblock');     
			$brief_summary = trim(preg_replace("/\s+/",' ',$x));

			parent::addRDF(
				parent::triplifyString($study_id,$this->getVoc()."brief-summary",$brief_summary)
			);

			
			####################################################################################
			# detailed description
			####################################################################################
			$x = $this->getString('//detailed_description/textblock');
			$d = trim(preg_replace("/\s+/",' ',$x));

			parent::addRDF(
				parent::triplifyString($study_id,parent::getVoc()."detailed-description",$d)
			);
			
			#########################################################################################
			#acronym
			#########################################################################################
			parent::addRDF(
				parent::triplifyString($study_id,parent::getVoc()."acronym",$this->getString("//acronym"))
			);

			########################################################################################
			#sponsors
			########################################################################################
			try {
				$sponsors = array("lead_sponsor","collaborator");
				foreach($sponsors AS $sponsor) {
					$a = @array_shift($root->xpath('//sponsors/'.$sponsor));
					if($a == null) break;
					$agency  = $this->getString("//agency", $a);
					$agency_id = parent::getRes().md5($agency);
					$agency_class = $this->getString("//agency_class", $a);
					$agency_class_id = parent::getRes().md5($agency_class);
					
					parent::addRDF(
						parent::describeIndividual($agency_id,$agency,parent::getVoc()."Organization").
						parent::describeClass(parent::getVoc()."Organization","Organization").
						parent::triplify($study_id, parent::getVoc().str_replace("_","-",$sponsor), $agency_id).
						
						parent::describeIndividual($agency_class_id, $agency_class, parent::getVoc()."Organization").
						parent::describeClass(parent::getVoc()."Organization","Organization").
						parent::triplify($agency_id, parent::getVoc()."organization", $agency_class_id)
					);
				}
			}catch( Exception $e){
				echo "There was an error in the lead sponsor element: $e\n";
			}

			#################################################################################
			# source
			#################################################################################
			$source = $this->getString('//source');
			if($source) {
				$source_id = parent::getRes().md5($source);
				parent::addRDF(
					parent::describeIndividual($source_id,$source,parent::getVoc()."Organization").
					parent::triplify($study_id,parent::getVoc()."source",$source_id)
				);
			}

			######################################################################################
			# oversight
			######################################################################################
			try {
				$oversight = @array_shift($root->xpath('//oversight_info'));
				if($oversight !== null) {
					$oversight_id = parent::getRes().md5($oversight->asXML());
			
					$authority = $this->getString('//authority', $oversight);	
					$authority_id = parent::getRes().md5($authority);
					parent::addRDF(	
						parent::describeIndividual($oversight_id,$authority,parent::getVoc()."Organization").
						parent::triplify($study_id,$this->getVoc()."oversight",$oversight_id).
						parent::triplify($study_id,$this->getVoc()."authority",$authority_id).
						parent::triplifyString($oversight_id, parent::getVoc()."has-dmc", $this->getString('//has_dmc', $oversight))
					);
				}				
			} catch(Exception $e){
				echo "There was an error in the oversight info element: $e\n";

			}

			#################################################################################
			# overall status
			#################################################################################
			$overall_status = $this->getString('//overall_status');
			if($overall_status) {
				$status_id = parent::getRes().md5($overall_status);
				parent::addRDF(
					parent::describeIndividual($status_id,$overall_status,parent::getVoc()."Status").
					parent::describeClass(parent::getVoc()."Status","Status").
					parent::triplify($study_id,parent::getVoc()."overall-status",$status_id)
				);
			}

			#########################################################################################
			#why stopped
			#########################################################################################
			parent::addRDF(
				parent::triplifyString($study_id,parent::getVoc()."why-stopped",$this->getString("//why_stopped"))
			);
			
			##################################################################################
			# dates
			##################################################################################
			$dates = array("start_date","end_date","completion_date", "primary_completion_date", "verification_date","lastchanged_date","firstreceived_date","firstreceived_results_date");
			foreach ($dates AS $date) {
				$d = $this->getString('//'.$date);
				if($d) {
					$datetime = $this->getDatetimeFromDate($d);
					if(isset($datetime)) {
						parent::addRDF(
							parent::triplifyString($study_id,parent::getVoc().str_replace("_","-",$date), $datetime)
						);
					} else {
						trigger_error("unable to parse date: $d",E_USER_ERROR);
					}
				}
			}
		
			####################################################################################
			# phase
			####################################################################################
			$phase = $this->getString('//phase');
			if($phase && $phase != "N/A") {
				$phase_id = $this->getRes().md5($phase);
				parent::addRDF(
					parent::describeIndividual($phase_id,$phase,parent::getVoc()."Phase",$phase).
					parent::describeClass(parent::getVoc()."Phase",$phase).
					parent::triplify($study_id,parent::getVoc()."phase",$phase_id)
				);
			}

			###################################################################################
			# study type
			####################################################################################
			$study_type = $this->getString('//study_type');
			if($study_type){
				$study_type_id = $this->getRes().md5($study_type);
				parent::addRDF(
					parent::describeClass($study_type_id,$study_type,parent::getVoc()."Study-Type").
					parent::describeClass(parent::getVoc()."Study-Type","Study Type").
					parent::triplify($study_id,parent::getVoc()."study-type",$study_type_id)
				);
			}

			###############################################################################
			# study design
			###############################################################################
			$study_design = $this->getString('//study_design');
			if($study_design) {
				$study_design_id = parent::getRes().md5($study_id.$study_design);
				parent::addRDF(
					parent::describeIndividual($study_design_id,"$study_id study design",parent::getVoc()."Study-Design").
					parent::describeClass(parent::getVoc()."Study-Design","Study Design").
					parent::triplify($study_id,parent::getVoc()."study-design",$study_design_id)
				);

				// Intervention Model: Parallel Assignment, Masking: Double-Blind, Primary Purpose: Treatment
				foreach(explode(", ",$study_design) AS $i=>$b) {
					$c = explode(":  ",$b);
					if(isset($c[1])) {
						$sdp = $study_design_id."-".($i+1);
						$key = parent::getRes().md5($c[0]);
						$value = parent::getRes().md5($c[1]);
						parent::addRDF(
							parent::describeIndividual($sdp,$this->safeString($b),parent::getVoc()."Study-Design-Parameter").
							parent::describeClass(parent::getVoc()."Study-Design-Parameter","Study Design Parameter").
							parent::triplify($sdp,parent::getVoc()."key",$key).
							parent::describeClass($key,$c[0]).
							parent::triplify($sdp,parent::getVoc()."value",$value).
							parent::describeClass($value,$c[1]).
							parent::triplify($study_design_id,parent::getVoc()."study-design-parameter",$sdp)
						);
					}
				}
			}
			
			####################################################################################
			# target duration
			####################################################################################
			parent::addRDF(
				parent::triplifyString($study_id,parent::getVoc()."target-duration",$this->getString('//target_duration'))
			);

			################################################################################
			# outcomes
			###############################################################################
			$outcomes = array("primary_outcome","secondary_outcome","other_outcome");
			foreach($outcomes AS $outcome) {
				$o = $root->xpath('//'.$outcome);
				if($o){
					$os = $o;
					if(!is_array($o)) $os = array($o);
					foreach($os AS $o) {
						try{
							$po_id = parent::getRes().md5($nct_id.$o->asXML());
							$po_type = parent::getVoc().str_replace("_","-", $outcome);
							
							$measure         = $this->getString('//measure', $o);
							$time_frame      = $this->getString('//time_frame', $o);
							$safety_issue    = $this->getString('//saftey_issue', $o);	
							$description     = $this->getString('//description', $o);
							
							parent::addRDF(
								parent::describeIndividual($po_id,$measure." ".$time_frame, ucfirst($po_type)).
								parent::describeClass(ucfirst($po_type),str_replace("_"," ",ucfirst($outcome))).
								parent::triplifyString($po_id, "dc:description", $description).
								parent::triplifyString($po_id, parent::getVoc()."measure", $measure).
								parent::triplifyString($po_id,parent::getVoc()."time-frame",$time_frame).
								parent::triplifyString($po_id,parent::getVoc()."safety-issue",$safety_issue).
								parent::triplify($study_id, parent::getVoc().$po_type,$po_id)
							);					
						}catch(Exception $e){
							echo "There was an error parsing the primary outcome element: $e \n";
						}
					}
				}
			}

			##############################################################################
			#number of arms
			##############################################################################
			try {
				parent::addRDF(
					parent::triplifyString($study_id,parent::getVoc()."number-of-arms",$this->getString('//number_of_arms'))
				);
			}catch(Exception $e){
				echo "There was an exception parsing the number of arms element: $e\n";
			}

			##############################################################################
			#number of groups
			##############################################################################
			try {
				parent::addRDF(
					parent::triplifyString($study_id,parent::getVoc()."number-of-arms",$this->getString('//number_of_groups'))
				);
			}catch(Exception $e){
				echo "There was an exception parsing the number of groups: $e\n";
			}

			
			##############################################################################
			#enrollment
			##############################################################################
			try{
				$e = $root->xpath('//enrollment');
				if($e) { 
					$type = strtolower((string) $e[0]->attributes()->type); 
					$value = $this->getString('//enrollment');
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc().($type?$type."-":"")."enrollment",$value)
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
						parent::describeClass($mesh_label_id,$this->safeString($condition),parent::getVoc()."Condition").
						parent::describeClass(parent::getVoc()."Condition","Condition")
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
					$arm_group_id = $this->getString('./arm_group_label',$arm_group);
					$arm_group_id = md5($arm_group_id);
					$arm_group_uri = parent::getRes().$this->nct_id."/arm-group/".$arm_group_id;
					$arm_group_label = $this->nct_id." arm group ".$arm_group_id;
					$arm_group_type = ucfirst(str_replace(" ","_",$this->getString('./arm_group_type',$arm_group)));
					if(!$arm_group_type) $arm_group_type = "Clinical-Arm";
					$description = $this->getString('./description',$arm_group);

        				parent::addRDF(
						parent::describeIndividual($arm_group_uri,$arm_group_label,parent::getVoc().$arm_group_type).
						parent::describeClass(parent::getVoc().$arm_group_type,ucfirst(str_replace("_"," ",$arm_group_type))).
						parent::triplifyString($arm_group_uri, parent::getVoc()."description", $description).
						parent::describeIndividual($arm_group_uri,$arm_group,parent::getVoc()."Arm-Group").
						parent::describeClass(parent::getVoc()."Arm-Group","Arm Group").
						parent::triplify($study_id,parent::getVoc()."arm-group",$arm_group_uri)
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
					$intervention_type = $this->getString('./intervention_type',$intervention);
					$intervention_type_uri = parent::getVoc().ucfirst(str_replace(" ","_",$intervention_type));
					$intervention_desc = $this->getString('./description',$intervention);
					$intervention_on   = $this->getString('./other_name',$intervention);
					
					parent::addRDF(
						parent::describeIndividual($intervention_id,$intervention_name,$intervention_type_uri).
						parent::describeClass($intervention_type_uri,$intervention_type).
						parent::triplifyString($intervention_id, parent::getVoc()."intervention-name",$intervention_name).
						parent::triplifyString($intervention_id, parent::getVoc()."intervention-desc",$intervention_desc).
						parent::triplifyString($intervention_id, parent::getVoc()."other-name",$intervention_on).
						parent::triplify($study_id,parent::getvoc()."intervention",$intervention_id)
					);
					$agl = $intervention->xpath("./arm_group_label");
					foreach($agl AS $a) {
						$label = $this->safeString($a);
						
						$arm_group_id = md5($a);
						$ag = parent::getRes().$this->nct_id."/arm-group/".$arm_group_id;
						parent::addRDF(
							parent::describeIndividual($ag,$label,parent::getVoc()."Arm-Group").
							parent::describeClass(parent::getVoc()."Arm-Group","Arm Group").
							parent::triplify($intervention_id, parent::getVoc()."arm-group",$ag)
						);
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
				if($eligibility !== null) {
					$eligibility_label = "eligibility for ".$study_id;
					$eligibility_id = parent::getRes().md5($eligibility->asXML());

					parent::addRDF(
						parent::describeIndividual($eligibility_id,$eligibility_label,parent::getVoc()."Eligibility").
						parent::describeClass(parent::getVoc()."Eligibility","Eligibility").
						parent::triplify($study_id,parent::getVoc()."eligibility",$eligibility_id)
					);

					if($criteria = @array_shift($eligibility->xpath('./criteria'))){
						$text = @array_shift($criteria->xpath('./textblock'));
						$x = str_replace(array('"',"'",'\\','�'),'', $text);         
						$text = trim(preg_replace("/\s+/",' ',$x));

						parent::addRDF(
							parent::triplifyString($eligibility_id, parent::getVoc()."text",$text)
						);
						$c = preg_split("/(Inclusion Criteria\:|Exclusion Criteria\:)/",$text);
						//inclusion
						if(isset($c[1])) {
							$d = explode(" - ",$c[1]); // the lists are separated by a hyphen
							foreach($d AS $inclusion) {
								$inc = trim($inclusion);
								if($inc != '') {
									$inc_id = parent::getRes().md5($inc);
									parent::addRDF(
										parent::describeIndividual($inc_id,$inc,parent::getVoc()."Inclusion-Criteria").
										parent::describeClass(parent::getVoc()."Inclusion-Criteria","Inclusion Criteria").
										parent::triplify($eligibility_id,parent::getVoc()."inclusion-criteria",$inc_id)
									);
								}
							}
						}
						//exclusion
						if(isset($c[2])) {
							$d = explode(" - ",$c[1]);
							foreach($d AS $exclusion) {
								$exc = $this->safeString($exclusion);
								if($exc != '') {
									$exc_id = parent::getRes().md5($exc);
									parent::addRDF(
										parent::describeIndividual($exc_id,$exc,parent::getVoc()."Exclusion-Criteria").
										parent::describeClass(parent::getVoc()."Exclusion-Criteria","Exclusion Criteria").
										parent::triplify($eligibility_id,parent::getVoc()."exclusion-criteria",$exc_id)
									);
								}
							}
						}					
					}
					
					parent::addRDF(
						parent::triplifyString($eligibility_id,parent::getVoc()."gender",$this->getString('./gender',$eligibility))
					);
					parent::addRDF(
						parent::triplifyString($eligibility_id,parent::getVoc()."healthy-volunteers",$this->getString('./healthy_volunteers',$eligibility))
					);
					
					$attributes = array('minimum_age','maximum_age');
					foreach($attributes AS $a) {
						$s = $this->getString('./'.$a,$eligibility);
						if($s != 'N/A') {
							$age = trim(str_replace("Years","",$s));
							parent::addRDF(
								parent::triplifyString($eligibility_id,parent::getVoc().str_replace("_","-",$this->safeString($a)),$age)
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
			#biospec
			#####################################################################################
			parent::addRDF(
				parent::triplifyString($study_id,parent::getVoc()."biospec-retention",$this->getString('//biospec_retention'))
			);

				
			try {
				$b = @array_shift($root->xpath('//biospec_descr'));
				if($b) {
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."biospec_descr",$this->getString('./textblock',$b))
					);						
				}
			}catch(Exception $e){
				echo "There was an error in biospec_descr: $e\n";
			}
				
			###################################################################
			# contacts
			###################################################################
			$contacts = array("overall_official","overall_contact","overall_contact_backup");	
			try {
				foreach($contacts AS $c) {
					$d = @array_shift($root->xpath('//'.$c));
					if($d) {
						parent::addRDF(
							parent::triplify($study_id, parent::getVoc().str_replace("_","-",$this->safeString($c)), $this->makeContact($d))
						);
					}
				}
			}catch (Exception $e){
				echo "There was an error parsing overall contact: $e"."\n";
			}
		
			##############################################################
			# location of facility doing the testing
			##############################################################
			try {	
				$location = @array_shift($root->xpath('//location'));
				if($location){
					$location_uri = parent::getRes().md5($location->asXML());
					$name = $this->getString('//facility/name',$location);
					$address = @array_shift($location->xpath('//facility/address'));					
					$contact = @array_shift($location->xpath('//contact'));
					$backups = @array_shift($location->xpath('//contact_backup'));
					$investigators = @array_shift($location->xpath('//investigator'));
					
					parent::addRDF(
						parent::describeIndividual($location_uri,$name,parent::getVoc()."Location").
						parent::describeClass(parent::getVoc()."Location","Location").
						parent::triplifyString($location_uri,parent::getVoc()."status", $this->getString('//status',$location)).
						parent::triplify($study_id,parent::getVoc()."location",$location_uri).
						parent::triplify($location_uri, parent::getVoc()."address", $this->makeAddress($address)).
						($contact != null?parent::triplify($location_uri, parent::getVoc()."contact", $this->makeContact($contact)):"")
					);
					if($backups) {
						foreach($backups AS $backup) {
							parent::addRDF(
								parent::triplify($location_uri, parent::getVoc()."contact-backup", $this->makeContact($backup))
							);
						}
					}
					if($investigators) {
						foreach($investigators AS $investigator) {
							parent::addRDF(
								parent::triplify($location_uri, parent::getVoc()."investigator", $this->makeContact($investigator))
							);
						}
					}
				}
			}catch (Exception $e){
				echo "There was an error parsing location: $e"."\n";
			}

			######################################################################
			#countries
			######################################################################
			try {				
				$a = array("location_countries","removed_countries");
				foreach($a AS $country) {
					$lc = @array_shift($root->xpath('//'.$country));
					if($lc) {
						$label = $this->getString('//country',$lc);
						$cid = parent::getRes().md5($label);
						parent::addRDF(
							parent::describeIndividual($cid,$label,parent::getVoc()."Country").
							parent::describeClass(parent::getVoc()."Country","Country").
							parent::triplify($study_id,parent::getVoc()."country",$cid)
						);
					}
				}				
			}catch (Exception $e){
				echo "There was an error parsing country: $e"."\n";
			}

			######################################################################
			#reference
			######################################################################
			try {
				$a = array("reference","result_reference");
				foreach($a AS $ref_type) {
					$references = $root->xpath('//'.$ref_type);
					foreach($references as $reference){
						$p = $this->getString('./PMID',$reference);

						$ref = $this->getString('./citation',$reference);    
						$ref = trim(preg_replace("/\s+/",' ',$x));
						
						if($p) {
							$pmid = "pubmed:$p";
							parent::addRDF(
								parent::describeIndividual($pmid,$p,parent::getVoc()."Reference").
								parent::describeClass(parent::getVoc()."Reference", "Reference").
								parent::triplifyString($pmid, parent::getVoc()."citation", $ref).
								parent::triplify($study_id,parent::getVoc().str_replace("_","-",$ref_type),$pmid)
							);
						}
					}
				}
			} catch(Exception $e){
				echo "There was an error parsing references element: $e\n";
			}
			
			#######################################################################
			#link
			#######################################################################
			try{
				$links = $root->xpath('//link');
				foreach($links AS $i => $link) {
					$url = $this->getString('./url',$link);
					$url = preg_replace("/>.*$/","",$url);
					$lid = parent::getRes().md5($url);
					parent::addRDF(
						parent::describeIndividual($lid, $this->getString('./description',$link), parent::getVoc()."Link").
						parent::describeClass(parent::getVoc()."Link","Link").
						parent::triplify($lid,parent::getVoc()."url",$url).
						parent::triplify($study_id,parent::getVoc()."link",$lid)
					);
				}
			} catch(Exception $e){
				echo "There was an error parsing link element: $e\n";
			}
			
			############################################################################
			#responsible party
			############################################################################
			try{
				$rp = @array_shift($root->xpath('//responsible_party'));
				if($rp){
					$rp_id = parent::getRes().md5($rp->asXML());
					$label = $this->getString('./name_title',$rp);
					if(!$label) $label = $this->getString('./organization',$rp);
					else $label .= ", ".$this->getString('./organization',$rp);
					if(!$label) $label = $this->getString('./party_type',$rp);
					$org_id = parent::getRes().md5($this->getString('./organization',$rp));
					
					parent::addRDF(
						parent::describeIndividual($rp_id,$label,parent::getVoc()."Responsible-Party").
						parent::describeClass(parent::getVoc()."Responsible-Party","Responsible Party").
						parent::triplify($study_id,parent::getVoc()."responsible-party",$rp_id).
						parent::triplify($rp_id, parent::getVoc()."organization", $org_id).
						parent::describeIndividual($org_id, $this->getString('./organization',$rp), parent::getVoc()."Organization").
						parent::describeClass(parent::getVoc()."Organization","Organization").
						parent::triplifyString($rp_id, parent::getVoc()."name-title", $this->getString('./name_title',$rp)).
						parent::triplifyString($rp_id, parent::getVoc()."party-type", $this->getString('./party_type',$rp)).
						parent::triplifyString($rp_id, parent::getVoc()."investigator-affiliation", $this->getString('./investigator_affiliation',$rp)).
						parent::triplifyString($rp_id, parent::getVoc()."investigator-full-name", $this->getString('./investigator_full_name',$rp)).
						parent::triplifyString($rp_id, parent::getVoc()."investigator-title", $this->getString('./investigator_title',$rp))
					);
				}
			}catch(Exception $e){
				echo "There was an error parsing the responsible_party element: $e\n";
			}

			##############################################################################
			# keywords
			##############################################################################
			try{
				$keywords = $root->xpath('//keyword');
				foreach($keywords as $keyword){
					parent::addRDF(
						parent::triplifyString($study_id,parent::getVoc()."keyword",$this->safeString($keyword))
					);
				}
			}catch(Exception $e){
				echo "There was an error parsing the keywords element: $e";
			}

			# mesh terms 
			# note: mesh terms are assigned using an imperfect algorithm
			try{
				$mesh_terms = $root->xpath('//condition_browse/mesh_term');
				foreach($mesh_terms as $mesh_term){
					$term = $this->safeString($mesh_term);
					$mesh_id = parent::getRes().md5($term);
					parent::addRDF(parent::triplify($study_id,parent::getVoc()."condition-mesh",$mesh_id));
					parent::addRDF(parent::triplifyString($mesh_id,"rdfs:label",$term));
				}
			}catch(Exception $e){
				echo "There was an error in mesh_terms: $e\n";
			}

			################################################################################
			# regulated by fda?  is section 801? has expanded access?
			################################################################################
			try {
				parent::addRDF(
					parent::triplifyString($study_id,parent::getVoc()."is-fda-regulated",$this->getString('is_fda_regulated')).
					parent::triplifyString($study_id,parent::getVoc()."is-section-801",$this->getString('is_section_801')).
					parent::triplifyString($study_id,parent::getVoc()."has-expanded-access",$this->getString('has_expanded_access'))
				);
			} catch (Exception $e){
				echo "There was an error parsing the is_fda_regulated element: $e\n";
			}

			###############################################################################
			# mesh terms for the intervention browse
			###############################################################################
			try {
				$a = array("condition_browse","intervention_browse");
				foreach($a AS $browse_type) {
					$terms = $root->xpath("//$browse_type/mesh_term");
					foreach($terms as $term){
						$term_label = $this->safeString($term);
						$term_id = parent::getRes().md5($term);
						parent::addRDF(
							parent::describeIndividual($term_id,$term_label,parent::getVoc()."Term").
							parent::describeClass(parent::getVoc()."Term","Term").
							parent::triplify($study_id,parent::getVoc().str_replace("_","-",$browse_type),$term_id)
						);
					}
				}
			} catch(Exception $e){
				echo "There was an error parsing $browse_type/mesh_term element: $e\n";
			}

			################################################################################
			# clinical results 
			################################################################################
			try {
				$cr = @array_shift($root->xpath('//clinical_results'));
				if($cr) {
					$cr_id = parent::getRes().md5($study_id.$cr->asXML());
					parent::addRDF(
						parent::describeIndividual($cr_id,"clinical results for $study_id",parent::getVoc()."Clinical-Result").
						parent::describeClass(parent::getVoc()."Clinical-Result","Clinical Result").
						parent::triplifyString($cr_id,parent::getVoc()."description",$this->getString('./desc',$cr)).
						parent::triplifyString($cr_id,parent::getVoc()."restrictive-agreement",$this->getString('./restrictive_agreement',$cr)).
						parent::triplifyString($cr_id,parent::getVoc()."limitations-and-caveats",$this->getString('./limitations_and_caveats',$cr)).
						parent::triplify($study_id,parent::getVoc()."clinical-result",$cr_id)
					);
				}
			} catch(Exception $e){
				echo "There was an error parsing clinical results: $e\n";
			}

		

			################################################################################
			# Participant Flow
			################################################################################
			try {
				$pc = 1;
				$mc = 1;
				$wc = 1;
				
				$pf = @array_shift($root->xpath('//clinical_results/participant_flow'));
				if($pf) {
					$pf_id = parent::getRes().md5($pf->asXML());
					parent::addRDF(
						parent::describeIndividual($pf_id,"participant flow for $study_id",parent::getVoc()."Participant-Flow").
						parent::describeClass(parent::getVoc()."Participant-Flow","Participant-Flow").
						parent::triplify($study_id,parent::getVoc()."participant-flow",$pf_id).
						parent::triplifyString($pf_id,parent::getVoc()."recruitment-details", $this->getString('./recruitment_details',$pf)).
						parent::triplifyString($pf_id,parent::getVoc()."pre-assignment-details", $this->getString('./pre_assignment_details',$pf))
					);
					$groups = @array_shift($pf->xpath('./group_list'));
					foreach($groups AS $group) {
						parent::addRDF(
							parent::triplify($pf_id,parent::getVoc()."group", $this->makeGroup($group))
						);
					}
					//period_list
					$periods = @array_shift($pf->xpath('./period_list'));
					foreach($periods AS $period) {
						$period_id = parent::getRes().$nct_id."/period/".($pc++);
						$period_title = $this->getString('./title',$period);
						
						parent::addRDF(
							parent::describeIndividual($period_id, $period_title." for $nct_id", parent::getVoc()."Period").
							parent::describeClass(parent::getVoc()."Period", "Period").
							parent::triplify($pf_id,parent::getVoc()."period",$period_id)
						);
						// milestones
						$milestones = @array_shift($period->xpath('./milestone_list'));
						if($milestones) {
							foreach($milestones AS $milestone) {
								$milestone_id = parent::getRes().$nct_id."/milestone/".($mc++);
								$label = $this->getString('./title',$milestone);
								
								parent::addRDF( 
									parent::describeIndividual($milestone_id, $label, parent::getVoc()."Milestone").
									parent::describeClass(parent::getVoc()."Milestone","Milestone").
									parent::triplify($period_id,parent::getVoc()."milestone",$milestone_id)
								);
								
								// participants
								$p = 1;
								$ps_list = @array_shift($milestone->xpath('./participants_list'));
								foreach($ps_list AS $ps) {
									$ps_id  = $milestone_id."/p/".($p++);
									$group_id = parent::getRes().$this->nct_id."/group/".$ps->attributes()->group_id;
									$count = (string) $ps->attributes()->count;
									parent::addRDF(
										parent::describeIndividual($ps_id, "participant counts in ".$ps->attributes()->group_id." for milestone $mc of $nct_id",parent::getVoc()."Participant-Count").
										parent::describeClass(parent::getVoc()."Participant-Count", "Participant Count").
										parent::triplify($ps_id, parent::getVoc()."group",$group_id).
										parent::triplifyString($ps_id, parent::getVoc()."count",$count).
										parent::triplify($milestone_id, parent::getVoc()."participant-counts",$ps_id)
									);
								}
							}
						} // milestones

						$withdraws = @array_shift($period->xpath('./drop_withdraw_reason_list'));
						if($withdraws) {
							foreach($withdraws AS $withdraw) {
								$wid = parent::getRes().$this->nct_id."/withdraw/".($wc++);
								$label = $this->getString('./title',$withdraw);
								parent::addRDF( 
									parent::describeIndividual($wid, $label, parent::getVoc()."Withdraw-Reason").
									parent::describeClass(parent::getVoc()."Withdraw-Reason","Withdraw Reason")
								);
								// participants
								$ps_list = @array_shift($withdraw->xpath('./participants_list'));
								foreach($ps_list AS $ps) {
									$group_id = parent::getRes().$nct_id."/group/".$ps->attributes()->group_id;
									$count = (string) $ps->attributes()->count;
									parent::addRDF(
										parent::triplify($wid, parent::getVoc()."group",$group_id).
										parent::triplifyString($wid, parent::getVoc()."count",$count)
									);
								}
							}
						}
					}
				}
			} catch(Exception $e){
				echo "There was an error parsing participant flow element: $e\n";
			}

			################################################################################
			# baseline
			################################################################################
			try {
				$baseline = @array_shift($root->xpath('//baseline'));
				if($baseline) {
					$b_id = $this->nct_id."/baseline";
					$b_uri = parent::getRes().$b_id;

					// group list
					$groups = @array_shift($baseline->xpath('./group_list'));
					foreach($groups AS $group) {
						parent::addRDF(
							parent::describeIndividual($b_uri,"baseline for $nct_id",parent::getVoc()."Baseline").
							parent::describeClass(parent::getVoc()."Baseline","Baseline").
							parent::triplify($b_uri,parent::getVoc()."group", $this->makeGroup($group)).
							parent::triplify($study_id,parent::getVoc()."baseline",$b_uri)
						);
					}
					
					// measure list
					$measures = @array_shift($baseline->xpath('./measure_list'));
					foreach($measures AS $measure) {
						parent::addRDF(
							parent::triplify($b_uri,parent::getVoc()."measure", $this->makeMeasure($measure))
						);
					}
										
				}				
			} catch(Exception $e) {
				echo "Error in parsing baseline".PHP_EOL;
			}
			
			################################################################################
			# outcomes
			################################################################################
			try {
				$o_n = 1;
				$outcomes = @array_shift($root->xpath('//outcome_list'));
				if($outcomes) {
					
					foreach($outcomes AS $i => $outcome) {
						$outcome_id = $this->nct_id."/outcome/".($o_n++);
						$outcome_uri = parent::getRes().$outcome_id;
						$outcome_label = $this->getString("./title",$outcome);
						if(!$outcome_label) $outcome_label = "outcome for ".$this->nct_id;				
						parent::addRDF(
							parent::describeIndividual($outcome_uri, $outcome_label, parent::getVoc()."Outcome", $this->getString("./description",$outcome)).
							parent::describeClass(parent::getVoc()."Outcome","Outcome").
							parent::triplify($study_id,parent::getVoc()."outcome",$outcome_uri).
							parent::triplifyString($outcome_uri,parent::getVoc()."type", $this->getString("./type",$outcome)).
							parent::triplifyString($outcome_uri,parent::getVoc()."time-frame", $this->getString("./time_frame",$outcome)).
							parent::triplifyString($outcome_uri,parent::getVoc()."safety-issue",$this->getString("./safety_issue",$outcome)).
							parent::triplifyString($outcome_uri,parent::getVoc()."posting-date",$this->getString("./posting-date",$outcome)).
							parent::triplifyString($outcome_uri,parent::getVoc()."population",$this->getString("./population",$outcome))
						);
						$groups = @array_shift($outcome->xpath('./group_list'));
						if($groups) {
							foreach($groups AS $group) {
								parent::addRDF(
									parent::triplify($outcome_uri,parent::getVoc()."group", $this->makeGroup($group))
								);
							}
						}

						// measure list  # this has changed
						$measures = @array_shift($outcome->xpath('./measure_list'));
						if($measures) {
							foreach($measures AS $measure) {
								parent::addRDF(
									parent::triplify($outcome_uri,parent::getVoc()."measure", $this->makeMeasure($measure))
								);
							}
						}
						$measure = @array_shift($outcome->xpath('./measure'));
						if($measure) {
							parent::addRDF(
								parent::triplify($outcome_uri,parent::getVoc()."measure", $this->makeMeasure($measure))
							);
						}
						

						// analysis list
						$analyses = @array_shift($outcome->xpath('./analysis_list'));
						if($analyses) {
							foreach($analyses AS $analysis) {
								parent::addRDF(
									parent::triplify($outcome_uri,parent::getVoc()."analysis", $this->makeAnalysis($analysis))
								);
							}}						
					}
				}
			} catch(Exception $e) {
				echo "Error in parsing outcomes".PHP_EOL;
			}					
			
			################################################################################
			# events 
			################################################################################
			try{
				$c_ev =  $c_c = 1;
				$reported_events = @array_shift($root->xpath('//reported_events'));
				if($reported_events){
					$rp_id = parent::getRes().md5($reported_events->asXML());
					$groups = @array_shift($reported_events->xpath('./group_list'));
					parent::addRDF(
						parent::describeIndividual($rp_id,"Reported events for $nct_id",parent::getVoc()."Reported-Events").
						parent::describeClass(parent::getVoc()."Reported-Events","Reported Events").
						parent::triplify($study_id,parent::getVoc()."reported-events",$rp_id)
					);

					foreach($groups AS $group) {
						parent::addRDF(
							parent::triplify($rp_id,parent::getVoc()."group", $this->makeGroup($group))
						);
					}

					// events	
					$event_list = array("serious_events" => "Serious Event","other_events" => "Other Event");
					foreach($event_list AS $ev => $ev_label) {
						$et = @array_shift($reported_events->xpath('./'.$ev));
						if(!$et) continue;
						$ev_uri = parent::getVoc().str_replace(" ","-",$this->safeString($ev_label));

						$categories = @array_shift($et->xpath('./category_list'));
						foreach($categories AS $category) {
							$major_title = $this->getString('./title', $category);
							$major_title_uri = parent::getRes().md5($major_title);

							$events = @array_shift($category->xpath('./event_list'));
							foreach($events AS $event) {
								$e_uri = parent::getRes().$this->nct_id."/$ev/".($c_ev++);
								$subtitle = (string) $this->getString('./sub_title',$event)." for ".$this->nct_id;
								$subtitle_uri = parent::getRes().md5($subtitle);


								parent::addRDF(
									parent::describeIndividual($e_uri,$subtitle,$ev_uri).
									parent::describeClass($ev_uri,$ev_label).
									parent::triplify($e_uri, parent::getVoc()."sub-title", $subtitle_uri).
									parent::describeIndividual($subtitle_uri, $subtitle, parent::getVoc()."Event").
									parent::describeClass(parent::getVoc()."Event","Event").
									parent::triplify($e_uri, parent::getVoc()."major-title", $major_title_uri).
									parent::describeClass($major_title_uri,$major_title).
									parent::triplify($rp_id, parent::getVoc().str_replace("_","-",$ev),$e_uri)
								);
								$counts = $event->xpath('./counts');
								foreach($counts AS $c) {
									$group_id = $c->attributes()->group_id;
									$group_uri = parent::getRes().$nct_id."/group/".$group_id;
									$c_uri = $e_uri."/count/".($c_c++);
									parent::addRDF(
										parent::describeIndividual($c_uri, $subtitle." for ".$group_id." in ".$this->nct_id, parent::getVoc()."Event-Count").
										parent::describeClass(parent::getVoc()."Event-Count","Event Count").
										parent::triplify($c_uri,parent::getVoc()."group",$group_uri).
										parent::triplify($e_uri,parent::getVoc()."count",$c_uri).
										parent::triplifyString($c_uri,parent::getVoc()."default-vocabulary", $this->getString('./default_vocab',$et)).
										parent::triplifyString($c_uri,parent::getVoc()."frequency-threshold",$this->getString('./frequency_threshold', $et)).
										parent::triplifyString($c_uri,parent::getVoc()."default-assessment",$this->getString('./default_assessment', $et)).
										parent::triplifyString($c_uri,parent::getVoc()."number-events",$c->attributes()->events).
										parent::triplifyString($c_uri,parent::getVoc()."subjects-affected",$c->attributes()->subjects_affected).
										parent::triplifyString($c_uri,parent::getVoc()."subjects-at-risk",$c->attributes()->subjects_at_risk)
									);
								}
							}
						}
					}
				}
			} catch(Exception $e) {
				echo "Error in parsing reported events".PHP_EOL;
			}
			parent::writeRDFBufferToWriteFile();
		}
		parent::writeRDFBufferToWriteFile();
		$this->setCheckPoint('record');
		$this->setCheckPoint('dataset');
	}
	
	function getString($xpath,$element = null)
	{
		$o = $this->root;
		if(isset($element)) $o = $element;
		$r = @array_shift($o->xpath($xpath));
		return $this->safeString($r[0]);
	}
	
	function safeString($string)
	{
		return str_replace(array('"','\\'),array('','/'),(string)$string);
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
	
	public function makeContact($contact)
	{
		if($contact == null) return '';
		$contact_uri = parent::getRes().md5($contact->asXML());
		$contact_type_uri = parent::getVoc()."Contact";
		$contact_label = trim($this->getString('//first_name',$contact)." ".$this->getString('//last_name', $contact));
		parent::addRDF(
			parent::describeIndividual($contact_uri,$contact_label,$contact_type_uri).
			parent::describeClass($contact_type_uri,"Contact").
			parent::triplifyString($contact_uri,parent::getVoc()."first-name",$this->getString('//first_name',$contact)).
			parent::triplifyString($contact_uri,parent::getVoc()."middle-name",$this->getString('//middle_name',$contact)).
			parent::triplifyString($contact_uri,parent::getVoc()."last-name",$this->getString('//last_name',$contact)).
			parent::triplifyString($contact_uri,parent::getVoc()."degrees",$this->getString('//degrees',$contact)).						
			parent::triplifyString($contact_uri,parent::getVoc()."phone",$this->getString('//phone',$contact)).		
			parent::triplifyString($contact_uri,parent::getVoc()."phone-ext",$this->getString('//phone_ext',$contact)).	
			parent::triplifyString($contact_uri,parent::getVoc()."email",$this->getString('//email',$contact)).
			parent::triplifyString($contact_uri,parent::getVoc()."role",$this->getString('//role',$contact)).
			parent::triplify($contact_uri,parent::getVoc()."affiliation",
				$this->makeDescription( $this->getString('//affiliation',$contact), "Organization"))
		);
		return $contact_uri;
	}
	
	public function makeGroup($group)
	{
		if($group == null) return null;
		$group_id = $group->attributes()->group_id;
		$group_uri = parent::getRes().$this->nct_id."/group/".$group_id;
		$title = $this->getString('./title',$group);
		$description = $this->getString('./description', $group);
		$time_frame = $this->getString('./time_frame', $group);
		parent::addRDF(
			parent::describeIndividual($group_uri,$title,parent::getVoc()."Group",$title,$description).
			parent::describeClass(parent::getVoc()."Group","Group").
			parent::triplifyString($group_uri,parent::getVoc()."group-id",$group_id).
			parent::triplifyString($group_uri,parent::getVoc()."time-frame",$time_frame)
		);
		return $group_uri;
	}
	
	public function makeMeasure($measure) 
	{
		if($measure == null) return null;
		$measure_id = parent::getRes().$this->nct_id."/measure/".md5($measure->asXML());
		
		parent::addRDF(
			parent::describeIndividual($measure_id, $this->getString('./title', $measure), parent::getVoc()."Measure",$this->getString('./description', $measure)).
			parent::describeClass(parent::getVoc()."Measure","Measure").
			parent::triplifyString($measure_id, parent::getVoc()."unit", $this->getString('./units', $measure)).
			parent::triplifyString($measure_id, parent::getVoc()."parameter", $this->getString('./param', $measure)).
			parent::triplifyString($measure_id, parent::getVoc()."dispersion", $this->getString('./dispersion', $measure))
		);
		
		$categories = @array_shift($measure->xpath('./class_list/class/category_list'));
		if(isset($categories)) {
			foreach($categories AS $category) {
				$cid = parent::getRes().$this->nct_id."/category/".md5($category->asXML());
				$cat_label = $this->getString('./sub_title', $category);
				if(!$cat_label) $cat_label = "category for measure";
				parent::addRDF(
					parent::describeIndividual($cid, $cat_label, parent::getVoc()."Category").
					parent::describeClass(parent::getVoc()."Category","Category").
					parent::triplify($measure_id,parent::getVoc()."category",$cid)
				);
				$ml = @array_shift($category->xpath('./measurement_list'));
				if(isset($ml)) {
					foreach($ml AS $m) {
						$mid = parent::getRes().$this->nct_id."/measurement/".md5($m->asXML());
						parent::addRDF(
							parent::describeIndividual($mid, $this->nct_id." measurement", parent::getVoc()."Measurement").
							parent::describeClass(parent::getVoc()."Measurement","Measurement").
							parent::triplify($mid, parent::getVoc()."group-id", parent::getRes().$this->nct_id."/group/".$m->attributes()->group_id).
							parent::triplifyString($mid, parent::getVoc()."value", $m->attributes()->value).
							parent::triplifyString($mid, parent::getVoc()."spread", $m->attributes()->spread).
							parent::triplifyString($mid, parent::getVoc()."lower-limit", $m->attributes()->lower_limit).
							parent::triplifyString($mid, parent::getVoc()."upper-limit", $m->attributes()->upper_limit).
							parent::triplify($cid, parent::getVoc()."measurement",$mid)
						);
					}
				}
			}
		}
		return $measure_id;
	}

	public function makeAnalysis($analysis)
	{
		if($analysis == null) return null;
		$analysis_uri = parent::getRes().$this->nct_id."/analysis/".md5($analysis->asXML());
		
		parent::addRDF(
			parent::describeIndividual($analysis_uri,"analysis for ".$this->nct_id, parent::getVoc()."Analysis").
			parent::describeClass(parent::getVoc()."Analysis","Analysis")
		);
		
		$groups = @array_shift($analysis->xpath('./group_list'));
		if($groups) {
			foreach($groups AS $group) {
				parent::addRDF(
					parent::triplify($analysis_uri,parent::getVoc()."group", $this->makeGroup($group))
				);
			}
		}		
		$a = array("groups_desc","non_inferiority","non_inferiority_desc","p_value","p_value_desc","method","method_desc","param_type","param_value","dispersion_type","dispersion_value","ci_percent","ci_n_sides","ci_lower_limit","ci_upper_limit","ci_upper_limit_na_comment","estimate_desc");
		foreach($a AS $b) {
			parent::addRDF(
				parent::triplifyString($analysis_uri,parent::getVoc().str_replace("_","-",$b), $this->getString('./'.$b, $analysis))
			);
		}
		return $analysis_uri;
	}
	
	public function makeAddress($address)
	{
		if($address == null) return null;
		
		$address_uri = parent::getRes().md5($address->asXML());
		parent::addRDF(
			parent::describeIndividual($address_uri,"address",parent::getVoc()."Address").
			parent::describeClass(parent::getVoc()."Address","Address").
			parent::triplifyString($address_uri, parent::getVoc()."city",
				$this->makeDescription( $this->getString('./city',$address),"City")).
			parent::triplifyString($address_uri,parent::getVoc()."state", 
				$this->makeDescription( $this->getString('./state',$address), "State")).
			parent::triplifyString($address_uri,parent::getVoc()."zip", 
				$this->makeDescription( $this->getString('./zip',$address), "ZipCode")).
			parent::triplifyString($address_uri,parent::getVoc()."country", 
				$this->makeDescription( $this->getString('./country',$address), "Country"))
		);
		return $address_uri;
	}
	
	public function makeDescription($title,$type)
	{
		if(!$title) return null;
		$uri = parent::getRes().md5($title);
		$type_uri= parent::getVoc().str_replace(" ","-",$type);
		parent::addRDF(
			parent::describeIndividual($uri,$title,$type_uri).
			parent::describeClass($type_uri,$type)
		);
		return $uri;
	}
					
}
?>
