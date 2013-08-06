<?php
/**
Copyright (C) 2011-2013 Dana Klassen, Alison Callahan

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
*/


/**
* An RDF generator for PubChem
* @author  Dana Klassen
* @author  Alison Callahan
* @version 0.2
**/

/**
* REQUIREMENTS
*	1. curlftpfs
*	2. 
* NOTES
* Download path for compounds: ftp://ftp.ncbi.nlm.nih.gov/pubchem/Compound/
* for current full dataset ftp://ftp.ncbi.nlm.nih.gov/pubchem/Compound/CURRENT-Full/SDF/
* for weekly dataset 
* for daily dataset
*
* will also need to take care of killed cid numbers
* how to properly download the very long file list from a pubchem directory
* could use rsync to process download of files - allowing stop and starting 
* of download script
*
**/
require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../../php-lib/xmlapi.php');

class PubChemParser extends Bio2RDFizer {

	private $pcbPrefix = "pubchem.bioassay";
	private $pccPrefix = "pubchem.compound";
	private $pcsPrefix = "pubchem.substance";

	function __construct($argv){
		parent::__construct($argv, "pubchem");
		parent::addParameter('files',true,'all|compounds|substances|bioassay','all','files to process');
		parent::addParameter('workspace',false,null,'../../workspace/pubchem/','directory to mount pubchem FTP server');
		parent::addParameter('download_url',false,null,'ftp://ftp.ncbi.nlm.nih.gov/pubchem/');
		parent::initialize();
	}

	function run(){
		if($this->checkRequirements() == FALSE){ 
			echo PHP_EOL."--> Missing requirements: see above for details. Consult readme for installation."; 
			exit;
		}
	
		if(parent::getParameterValue('download') === true){
 			$this->sync_files();		
 		}

		if(parent::getParameterValue('process') === true){
			$this->process();
		}
			
	}

	function process(){

		$idir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$files = parent::getParameterValue('files');

		if(substr($idir, -1) !== "/"){
			$idir = $idir."/";
		}
		
		if(substr($odir, -1) !== "/"){
			$odir = $odir."/";
		}

		if($files == 'all') {
			$files = explode('|', parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(',', parent::getParameterValue('files'));
		}

		parent::setCheckpoint('dataset');

		foreach($files as $file){
			switch($file){
				case "compounds" :
					$this->parse_compounds();
					break;
				case "substances" :
					$this->parse_substances();
					break;
				case "bioassay";
					$this->parse_bioassay();
					break;
				case "all";
					$this->parse_compounds();
					$this->parse_substances();
					$this->parse_bioassay();
					break;
			}
		}
	}

	/**
	* check that the requirements to run the script are met
	**/
	function checkRequirements(){
		echo "Checking script requirements:";

		$curlftpfs = exec("which curlftpfs");
		
		if($curlftpfs != ""){
			echo "-> curlftpfs ".$curlftpfs."\n";
		}else{
			return FALSE;
		}
		return TRUE;
	}

	/**
	* Create workspace and mount pubchem
	**/
	function setup_ftp(){
		//create workspace if doesn't already exist
		if($this->CreateDirectory($this->GetParameterValue('workspace')) === TRUE){
			echo "set up workspace ".$this->GetParameterValue('workspace')."\n";
		}else{
			echo "failed to create workspace exiting program";
			exit;
		}

		echo "Setting up FTP mount:\n";
		exec("curlftpfs ".$this->getParameterValue('download_url')." ".$this->getParameterValue('workspace'));
	}

	/**
	* close the FTP connection and unmount the workspace
	**/
	function close_ftp(){
		exec("fusermount -u ".$this->getParameterValue("workspace"));
	}

	/**
	* create the directories we need to sync the files 
	**/
	function sync_files(){

		$this->setup_ftp();
		$files = parent::getParameterValue('files');

		if($files == 'all') {
			$files = explode('|', parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(',', parent::getParameterValue('files'));
		}

		foreach($files as $file){
			switch($file) {
				case "substances"   :
					$this->sync_substances();
					break;
				case "compounds"    :
					$this->sync_compounds();
					break;
				case "bioassay" :
					$this->sync_bioassay();
					break;
				case "all" :
					$this->sync_substances();
					$this->sync_compounds();
					$this->sync_bioassay();
					break;
			}
		}
		
		$this->close_ftp();
	}

	/**
	* sync the local directory with the remote pubchem FTP server
	**/
	function sync_bioassay(){
		$dir = $this->getParameterValue('indir')."/bioassay" ;
		if($this->CreateDirectory($dir) === FALSE) exit;

		echo "syncing bioassay directory with remote\n";
		exec("rsync -r -t -v --progress --include='*/' --include='*.zip' --exclude='*' ".$this->getParameterValue('workspace')."/Bioassay/XML/ ".$dir);
	}
	/**
	* sync the local directory with the remote pubchem FTP server
	**/
	function sync_substances(){
		$substances_dir = $this->getParameterValue('indir')."/substances" ;
		if($this->CreateDirectory($substances_dir) === FALSE) exit;

		echo "syncing substances directory with remote\n";
		exec("rsync -r -t -v --progress --include='*/' --include='*.xml.gz' --exclude='*' ".$this->getParameterValue('workspace')."/Substance/CURRENT-Full/XML/ ".$substances_dir);
	}

	/**
	* sync the local directory with the remote pubchem FTP server
	**/
	function sync_compounds(){
		$compounds_dir = $this->getParameterValue('indir')."/compounds" ;
		if($this->CreateDirectory($compounds_dir) === FALSE) exit;

		echo "syncing compound directory with remote\n";
		exec("rsync -r -t -v --progress --include='*/' --include='*.xml.gz' --exclude='*' ".$this->getParameterValue('workspace')."/Compound/CURRENT-Full/XML/ ".$compounds_dir);
	}

	/**
	*	process the local copy of the pubchem bioassay directory
	**/
	function parse_bioassay(){

		$ignore = array(".","..");
		$input_dir = $this->getParameterValue('indir')."/bioassay" ; $gz=false;
		$tmp = '/tmp/pubchem';
		$this->CreateDirectory($tmp);
		$this->CreateDirectory($this->getParameterValue('outdir')."/bioassay/");

		parent::setDatasetURI("bio2rdf_dataset:bio2rdf-".$this->getPcbPrefix()."-".date("Ymd"));

		$graph_uri = parent::getGraphURI();
		//set graph URI to dataset uri
        if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		$dataset_description = '';

		if($handle = opendir($input_dir)){
			while(false !== ($dir = readdir($handle))){
				if(in_array($dir, $ignore)) continue;
				$zip = new ZipArchive;

				if($zip->open($input_dir."/".$dir) === TRUE) {
					$zip->extractTo($tmp);
					$this->CreateDirectory($this->getParameterValue('outdir')."/bioassay/".array_shift(explode(".",$dir)));

					$read_dir = $tmp."/".array_shift(explode(".",$dir))."/";
					if($files = opendir($read_dir)){
						while(false != ($file = readdir($files))){
							if(in_array($file, $ignore)) continue;

							echo "Processing file: ".$read_dir.$file.PHP_EOL;
							$outfile = realpath($this->getParameterValue('outdir'))."/bioassay/".array_shift(explode(".",$dir))."/".basename($file,".xml.gz").".nt";
				
							if(strstr(parent::getParameterValue('output_format'), "gz")) {
								$outfile .= '.gz';
								$gz = true;
							}							
							echo "... into ".$outfile.PHP_EOL;

							parent::setWriteFile($outfile,$gz);
							parent::setCheckpoint('file');
							$this->parse_bioassay_file($read_dir,$file);
							parent::getWriteFile()->close();
							//parent::clear();

							
						}
						rmdir($tmp);
					}else{

						echo "unable to open directory to read files.\n";
					}

					$zip->close();
				}
			}
			closedir($handle);

			$source_file = (new DataResource($this))
				->setURI("http://www.ncbi.nlm.nih.gov/pcassay")
				->setTitle("PubChem BioAssay")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($input_dir)))
				->setFormat("text/xml")
				->setFormat("application/zip")	
				->setPublisher("http://ncbi.nlm.nih.gov/")
				->setHomepage("http://pubchem.ncbi.nlm.nih.gov/")
				->setRights("use")
				->setRights("restricted-by-source-license")
				->setLicense("ftp://ftp.ncbi.nlm.nih.gov/pubchem/README")
				->setDataset("http://identifiers.org/pubchem.bioassay/");

			$prefix = $this->getPcbPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2df.org/release/$bVersion/$prefix")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/pubchem/pubchem.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")			
				->setRights("use-share-modify")
				->setRights("by-attribution")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons.org/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());

			if($gz) $output_file->setFormat("application/gzip");
			if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
			else $output_file->setFormat("application/n-quads");

			$dataset_description .= $source_file->toRDF().$output_file->toRDF();
			//set graph URI back to default
			parent::setGraphURI($graph_uri);

			// write the dataset description
			$this->setWriteFile($this->getParameterValue('outdir')."/bioassay/".$this->getBio2RDFReleaseFile());
			$this->getWriteFile()->write($dataset_description);
			$this->getWriteFile()->close();

		}else{
			echo "unable to read directory contents: ".$input_dir."\n";
			exit;
		}
	}

	/**
	*	process a single pubchem bioassay file
	**/
	function parse_bioassay_file($indir,$file){
		$xml = new CXML($indir,$file);
		while($xml->Parse("PC-AssaySubmit") == TRUE) {
			parent::setCheckpoint('record');
			$this->parse_bioassay_record($xml);
		}
	}

	/**
	* 	process a single pubchem bioactivity record
	**/
	function parse_bioassay_record(&$xml) {

		$root = $xml->GetXMLRoot();

		$root->registerXPathNamespace('x', 'http://www.ncbi.nlm.nih.gov');

		// internal identifier
		$aid = (string) array_shift($root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/x:PC-AssayDescription/x:PC-AssayDescription_aid/x:PC-ID/x:PC-ID_id'));
		$pid = $this->getPcbNs().$aid;

		// text based description
		$assay_name = (string) array_shift($root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/PC-AssayDescription/x:PC-AssayDescription_name'));		
		parent::addRDF(
			parent::describeIndividual($pid, $assay_name, $this->getPcbVoc()."Assay", null, $assay_name).
			parent::describeClass($this->getPcbVoc()."Assay", "PubChem BioAssay")
		);

		$version = (string) array_shift($root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/x:PC-AssayDescription/x:PC-AssayDescription_aid/x:PC-ID/x:PC-ID_version'));
		parent::addRDF(
			parent::triplifyString($pid, $this->getPcbVoc()."has-version", parent::safeLiteral($version)).
			parent::describeProperty($this->getPcbVoc()."has-version", "Relationship between a PubChem entity and a version")
		);

		// additional identifiers
		$source_desc   = (string) array_shift($root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/x:PC-AssayDescription/x:PC-AssayDescription_aid/x:PC-AssayDescription_aid-source/x:PC-Source/PC-Source_db/x:PC-DBTracking'));
		$tracking_name = (string) array_shift($root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/x:PC-AssayDescription/x:PC-AssayDescription_aid/x:PC-AssayDescription_aid-source/x:PC-Source/PC-Source_db/x:PC-DBTracking/PC-DBTracking_name'));
		$tracking_id   = (string) array_shift($root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/x:PC-AssayDescription/x:PC-AssayDescription_aid/x:PC-AssayDescription_aid-source/x:PC-Source/PC-Source_db/x:PC-DBTracking/PC-DBTracking_source-id/x:Object-id/Object-id_str'));
		$xid = $tracking_name.":".$tracking_id;

		parent::addRDF(
			parent::triplifyString($pid, $this->getPcbVoc()."xref", $xid)
		);

		$assay_descriptions = $root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/x:PC-AssayDescription/x:PC-AssayDescription_description/x:PC-AssayDescription_description_E');
		foreach($assay_descriptions as $assay_description) {
			$assay_description = (string) $assay_description;
			if($assay_description != ""){
				$assay_description = parent::safeLiteral($assay_description);
				parent::addRDF(
					parent::triplifyString($pid, "dc:description", $assay_description)
				);
			}
		}

		$assay_comments = $root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/x:PC-AssayDescription/x:PC-AssayDescription_comment/x:PC-AssayDescription_comment_E');
		foreach($assay_comments as $assay_comment) {
			$assay_comment = (string) $assay_comment;
			$comment = explode(":", $assay_comment);
			
			if(count($comment) <= 1) continue;
			
			$key   = $comment[0];
			$value = $comment[1];

			if($value == "") continue;

			switch($key) {
				case "Putative Target":
					break;
				case "Tax ID":
					 if($value != null){
					 	$value = trim($value);
					 	parent::addRDF(
					 		parent::triplify($pid, $this->getPcbVoc()."has-taxid", "taxon:".$value).
					 		parent::describeProperty($this->getPcbVoc()."has-taxid", "Relationship between a PubChem BioAssay and a taxonomic identifier")
					 	);
					 }
					break;
				default :
					if ($value != null) { 
						$value = trim(parent::safeLiteral($value));
						parent::addRDF(
							parent::triplifyString($pid, "rdfs:comment", $value)
						);
					}//if
					break;
			}//switch
		}

		// xrefs - these are database cross references to pubmed, ncbi gene, and pubchem substance
		$assay_xrefs = $root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/x:PC-AssayDescription/x:PC-AssayDescription_xref/x:PC-AnnotatedXRef');
		foreach($assay_xrefs as $xref) {
			//xref data
			$xref->registerXPathNamespace('x', 'http://www.ncbi.nlm.nih.gov');

			$pmids = $xref->xpath("./x:PC-AnnotatedXRef_xref/x:PC-XRefData/x:PC-XRefData_pmid");
			$this->db_xrefs($pid,$pmids,"pubmed");

			$taxons = $xref->xpath("./x:PC-AnnotatedXRef_xref/x:PC-XRefData/x:PC-XRefData_taxonomy");
			$this->db_xrefs($pid,$taxons,"taxon");

			$aids = $xref->xpath("./x:PC-AnnotatedXRef_xref/x:PC-XRefData/x:PC-XRefData_aid");
			$this->db_xrefs($pid,$aids, $this->getPcbPrefix());

			$omims = $xref->xpath("./x:PC-AnnotatedXRef_xref/x:PC-XRefData/x:PC-XRefData_mim");
			$this->db_xrefs($pid,$omims,"omim");
		}

		// definitions for allowed result types for a given assay
		$result_types = $root->xpath('//x:PC-AssaySubmit_assay/x:PC-AssaySubmit_assay_descr/x:PC-AssayDescription/x:PC-AssayDescription_results/x:PC-ResultType');
		foreach($result_types as $result_type) {
			$result_type->registerXPathNamespace('x', 'http://www.ncbi.nlm.nih.gov');

			$name        = (string) array_shift($result_type->xpath('./x:PC-ResultType_name'));
			$tid         = (string) array_shift($result_type->xpath('./x:PC-ResultType_tid'));
			$description = (string) array_shift($result_type->xpath('./x:PC-ResultType_description/x:PC-ResultType_description_E'));
			$type        = (string) array_shift($result_type->xpath('./x:PC-ResultType_type'));
			$unit        = array_shift($result_type->xpath('./x:PC-ResultType_unit'));

			// create the possible assay types that a result can be; may result in duplication with other experiments
			$rtid = $this->result_type_id($aid, $tid);

			parent::addRDF(
				parent::describeIndividual($rtid, $name, $this->getPcbVoc()."AssayResultType", $name).
				parent::describeClass($this->getPcbVoc()."AssayResultType", "Result type of a PubChem BioAssay")
			);

			if($description != ""){
				$description = parent::safeLiteral($description);
				parent::addRDF(
					parent::triplifyString($rtid, "dc:description", $description)
				);
			}

			if($unit != null){
				$value = $unit->attributes()->value;
				parent::addRDF(
					parent::triplifyString($rtid, $this->getPcbVoc()."has-unit", parent::safeLiteral($value)).
					parent::describeProperty($this->getPcbVoc()."has-unit", "Relationship between a PubChem BioAssay Result Type and its unit")
				);
			}
		}

		// project category e.g literature-extracted
		$project_category = (string) array_shift($root->xpath('//x:PC-AssaySubmit_assay/x:PC-Assay_descr/x:PC-AssayDescription_project-category'));
		//$this->AddRDF($this->QQuadl($pid,"pubchembioactivity_vocabulary:hasProjectCategory",$project_category));

		// result sets - these are containers for multiple assay result sets
		$results = $root->xpath('//x:PC-AssaySubmit_data/x:PC-AssayResults');
		$rsid    = $this->getPcbRes()."resultset_".md5(implode($results));
		$rsid_label = "BioAssay Result Set for $pid";

		parent::addRDF(
			parent::describeIndividual($rsid, $rsid_label, $this->getPcbVoc()."ResultSet").
			parent::describeClass($this->getPcbVoc()."ResultSet", "PubChem BioAssay Result Set")
		);

		parent::addRDF(
			parent::triplify($pid, $this->getPcbVoc()."has-result-set", $rsid).
			parent::describeProperty($this->getPcbVoc()."has-result-set", "Relationship between a PubChem BioAssay and its result set")
		);

		foreach($results as $result) {

			$result->registerXPathNamespace('x', 'http://www.ncbi.nlm.nih.gov');

			$rid = $this->getPcbRes()."result_".md5($result->asXML());
			$rid_label = "A PubChem BioAssay Result for $pid";
			parent::addRDF(
				parent::describeIndividual($rid, $rid_label, $this->getPcbVoc()."AssayResult").
				parent::describeClass($this->getPcbVoc()."AssayResult", "PubChem BioAssay Result")
			);

			parent::addRDF(
				parent::triplify($rsid, $this->getPcbVoc()."has-result", $rid).
				parent::describeProperty($this->getPcbVoc()."has-result", "Relationship between a PubChem BioAssay Result Set and a Result")
			);

			// substance id
			$sid  = (string) array_shift($result->xpath('./x:PC-AssayResults_sid'));
			$psid = $this->getPcsNs().$sid;

			parent::addRDF(
				parent::triplify($rid, $this->getPcbVoc()."has-substance", $psid).
				parent::describeProperty($this->getPcbVoc()."has-substance", "Relationship between a PubChem BioAssay Result and a PubChem substance")
			);

			// pubchem substance version
			$sid_version = (string) array_shift($result->xpath('./x:PC-AssayResults_version'));
				if($sid_version !== ""){
					parent::addRDF(
					parent::triplifyString($psid, $this->getPcbVoc()."has-version", parent::safeLiteral($sid_version))
				);
			}
			
			$assay_outcome = (string) array_shift($result->xpath('./x:PC-AssayResults_outcome'));
			parent::addRDF(
				parent::triplifyString($rid, $this->getPcbVoc()."has-outcome", parent::safeLiteral($assay_outcome)).
				parent::describeProperty($this->getPcbVoc()."has-outcome", "Relationship between a PubChem BioAssay and an outcome")
			);

			$year = (string) array_shift($result->xpath('./x:PC-AssayResults_date/x:Date/x:Date_std/x:Date-std/x:Date-std_year'));
			$month = (string) array_shift($result->xpath('./x:PC-AssayResults_date/x:Date/x:Date_std/x:Date-std/x:Date-std_month'));
			$day = (string) array_shift($result->xpath('./x:PC-AssayResults_date/x:Date/x:Date_std/x:Date-std/x:Date-std_day'));

			parent::addRDF(
				parent::triplifyString($rid, $this->getPcbVoc()."has-date", parent::safeLiteral($day."-".$month."-".$year), "xsd:date").
				parent::describeProperty($this->getPcbVoc()."has-date", "Relationship between a PubChem BioAssay and a date")
			);

			// individual result datapoints
			$assay_data_collection = $result->xpath('./x:PC-AssayResults_data/x:PC-AssayData');
			foreach($assay_data_collection as $assay_data) {
				// assay data id (what type is it?)
				$assay_data->registerXPathNamespace('x', 'http://www.ncbi.nlm.nih.gov');

				$atype  =  array_shift($assay_data->xpath('./x:PC-AssayData_tid'));
				$avalue = (string) array_shift($assay_data->xpath('./x:PC-AssayData_value/*'));

				$vid = $this->getPcbRes()."result_value_".md5($rid.$avalue);
				$vid_label = "Result value of type ".$atype." for PubChem BioAssay ".$aid;
				$vid_type = $this->result_type_id($aid, $atype);

				parent::addRDF(
					parent::describeIndividual($vid, $vid_label, $vid_type)
				);

				parent::addRDF(
					parent::triplify($rid, $this->getPcbVoc()."has-result-value", $vid).
					parent::describeProperty($this->getPcbVoc()."has-result-value", "Relationship between a PubChem BioAssay result and its value resource")
				);
				
				if($avalue != "" && $avalue != null ){
					parent::addRDF(
						parent::triplifyString($vid, "rdf:value", parent::safeLiteral($avalue))
					);
				}
			}
		}
	}

	/**
	* Create unique id for a result id
	**/
	function result_type_id($aid, $tid) {
		return $this->getPcbRes().$aid."_result_type_".$tid;
	}

	/**
	* Convert XML <xrefs> section into RDF
	**/
	function db_xrefs($pid,$xrefs,$vocab){

		if($xrefs != null) {
			foreach($xrefs as $xref) {
				$xref = (string) $xref;
				$xref = $vocab.":".$xref;
				parent::addRDF(
					parent::triplify($pid, $this->getPcbVoc()."xref", $xref)
				);
			}
		}
	}

	/**
	*	Function to start the conversion of the local copy of the pubchem
	*	compound directory.
	**/
	function parse_compounds(){
		$ignore = array(".","..");
		$input_dir = parent::getParameterValue('indir')."/compounds/" ; $gz=false;
		parent::createDirectory(parent::getParameterValue('outdir')."/compounds/");

		parent::setDatasetURI("bio2rdf_dataset:bio2rdf-".$this->getPccPrefix()."-".date("Ymd"));

		$graph_uri = parent::getGraphURI();
		//set graph URI to dataset uri
        if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		$dataset_description = '';

		if($handle = opendir($input_dir)){
			while(false !== ($file = readdir($handle))){
				if(in_array($file, $ignore))continue;
				echo "Processing file: ".$input_dir.$file.PHP_EOL;
				$outfile = realpath(parent::getParameterValue('outdir'))."/compounds/".basename($file,".xml.gz").".nt";
				
				if(strstr(parent::getParameterValue('output_format'), "gz")) {
					$outfile .= '.gz';
					$gz = true;
				}
				
				echo "... into ".$outfile.PHP_EOL;

				parent::setCheckpoint('file');
				$this->setWriteFile($outfile,$gz);
				$this->parse_compound_file($input_dir,$file);
				$this->getWriteFile()->close();
			}
			closedir($handle);

			$source_file = (new DataResource($this))
				->setURI("http://www.ncbi.nlm.nih.gov/pccompound")
				->setTitle("PubChem Compound")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($input_dir)))
				->setFormat("text/xml")
				->setFormat("application/zip")	
				->setPublisher("http://ncbi.nlm.nih.gov/")
				->setHomepage("http://pubchem.ncbi.nlm.nih.gov/")
				->setRights("use")
				->setRights("restricted-by-source-license")
				->setLicense("ftp://ftp.ncbi.nlm.nih.gov/pubchem/README")
				->setDataset("http://identifiers.org/pubchem.compound/");

			$prefix = $this->getPccPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2df.org/release/$bVersion/$prefix")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/pubchem/pubchem.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")			
				->setRights("use-share-modify")
				->setRights("by-attribution")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons.org/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());

			if($gz) $output_file->setFormat("application/gzip");
			if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
			else $output_file->setFormat("application/n-quads");

			$dataset_description .= $source_file->toRDF().$output_file->toRDF();
			//set graph URI back to default
			parent::setGraphURI($graph_uri);

			// write the dataset description
			$this->setWriteFile($this->getParameterValue('outdir')."/compounds/".$this->getBio2RDFReleaseFile());
			$this->getWriteFile()->write($dataset_description);
			$this->getWriteFile()->close();
		}else{
			echo "Unable to read directory contents: ".$input_dir."\n";
			exit;
		}
	}

	/**
	*	Begin parsing a single compound XML file
	**/
	function parse_compound_file($indir,$file){
		$xml = new CXML($indir,$file);
		while($xml->Parse("PC-Compound") == TRUE) {
			parent::setCheckpoint('record');
			$this->parse_compound_record($xml);
		}
	}

	/**
	*	Parse a single compound record from the compound xml file
	* 	handles the conversion of the raw XML to RDF.
	**/
	function parse_compound_record(&$xml){
		
		$root = $xml->GetXMLRoot();
		$cid_array = $root->xpath('//PC-Compound_id/PC-CompoundType/PC-CompoundType_id/PC-CompoundType_id_cid');
		if($cid_array === FALSE){
			trigger_error("Xpath did not work");
			exit;
		}
		$cid = array_shift($cid_array);
		$pcid = $this->getPccNs().$cid;
		$pcid_label = null;

		// AtomID/Type Information
		//$pc_atoms_aid     = $root->xpath('//PC-Compound_atoms/PC-Atoms/PC-Atoms_aid/PC-Atoms_aid_E');
		//$pc_atoms_element = $root->xpath('//PC-Compound_atoms/PC-Atoms/PC-Atoms_element/PC-Element');

		//foreach($pc_atoms_aid as $i => $atom_aid) {
			//echo "atom: ".$pcid." element: ".$pc_atoms_element[$i]->attributes()->value."\n";
		//	$this->AddRDF($this->QQuad($pcid,))
		//}

		// specific charge on an atom
		//$pc_atoms_charge  = $root->xpath('//PC-Compound_atoms/PC-Atoms/PC-Atoms_charge/PC-AtomInt');
		//foreach($pc_atoms_charge as $charge) {
		//	$atom_id     = $this->atom_id($pcid,array_shift($charge->xpath('//PC-AtomInt_aid')));
		//	$atom_charge = array_shift($charge->xpath('//PC-AtomInt_value'));

			//echo "atom_id: ".$atom_id." atom_charge: ".$atom_charge."\n";
		//}

		// atomic bonds aid1 is one atom aid2 is the aid of the adjacent atom
		//$pc_bonds_aid1    = $root->xpath('//PC-Compound_bonds/PC-Bonds/PC-Bonds_aid1/PC-Bonds_aid1_E');
		//$pc_bonds_aid2    = $root->xpath('//PC-Compound_bonds/PC-Bonds/PC-Bonds_ai2/PC-Bonds_aid2_E');
		//$pc_bond_type	  = $root->xpath('//PC-Compound_bonds/PC-Bonds/PC-Bonds_order/PC-BondType');

		//foreach($pc_bonds_aid1 as $i => $aid1) {
		//	$atom_id1  = $this->atom_id($pcid,$aid1);
		//	$atom_id2  = $this->atom_id($pcid,$pc_bonds_aid2[$i]);
		//	$bond_id   = $this->bond_id($id,$aid1,$pc_bonds_aid2[$i]);
		//	$bond_type = $pc_bond_type[$i];
		//}

		//PC-Compound_stero

		//PC-Compound_charge

		//PC-coords

		// Compound properties
		$property = $root->xpath('//PC-Compound_props/PC-InfoData');

		foreach($property as $prop) {

			$urn_label           = array_shift($prop->xpath('./PC-InfoData_urn/PC-Urn/PC-Urn_label'));
			$urn_name            = array_shift($prop->xpath('./PC-InfoData_urn/PC-Urn/PC-Urn_name'));
			$urn_implementation  = array_shift($prop->xpath('./PC-InfoData_urn/PC-Urn/PC-Urn_implementation'));
			$urn_software        = array_shift($prop->xpath('./PC-InfoData_urn/PC-Urn/PC-Urn_software'));
			$urn_version         = array_shift($prop->xpath('./PC-InfoData_urn/PC-Urn/PC-Urn_version'));
			$urn_release         = array_shift($prop->xpath('./PC-InfoData_urn/PC-Urn/PC-Urn_release'));
			$urn_data_type_value = array_shift($prop->xpath('./PC-InfoData_urn/PC-Urn/PC-Urn_datatype/PC-UrnDataType'));
			$urn_data_type       = $urn_data_type_value->attributes()->value;

			$value = array_shift($prop->xpath('./PC-InfoData_value/*'));

			if($urn_name == "Preferred" && $urn_label == "IUPAC Name"){
				$pcid_label = $value;
			}

			if($value  != ""){

				$urn_name = trim($urn_name);
				$urn_label = trim($urn_label);

				$pcid_type_string = "";
				if($urn_name != ""){
					$pcid_type_string .= $urn_name;
				}

				if($urn_label != ""){
					$pcid_type_string .= $urn_label;
				}

				$prop_id = $this->getPccRes().$this->info_id($cid,$prop->asXML());
				$prop_type = $this->getPccVoc().str_replace(" ", "_", $pcid_type_string);
				$prop_label = $urn_name." ".$urn_label." for PubChem compound ". $cid;

				parent::addRDF(
					parent::describeIndividual($prop_id, $prop_label, $prop_type).
					parent::triplifyString($prop_id, "rdf:value", parent::safeLiteral($value))
				);

				$pcid_predicate_string = "";
				if($urn_name != ""){
					$pcid_predicate_string .= $urn_name;
				}

				if($urn_label != ""){
					$pcid_predicate_string .= $urn_label;
				}

				$pcid_predicate_string = strtolower(str_replace(" ", "-", $pcid_predicate_string));
				parent::addRDF(
					parent::triplify($pcid, $this->getPccVoc()."has-".$pcid_predicate_string, $prop_id).
					parent::describeProperty($this->getPccVoc()."has-".$pcid_predicate_string, "Relationship between a PubChem compound and a $pcid_type_string")
				);

				$prov_id = $prop_id."_provenance";

				parent::addRDF(
					parent::triplify($prop_id, "prov:wasGeneratedBy", $prov_id)
				);

				if($urn_implementation != ""){
					parent::addRDF(
						parent::triplifyString($prov_id, $this->getPccVoc()."implementation", parent::safeLiteral($urn_implementation))
					);
				}

				if($urn_software != ""){
					parent::addRDF(
						parent::triplifyString($prov_id, $this->getPccVoc()."software", parent::safeLiteral($urn_software))
					);
				}

				if($urn_version != ""){
					parent::addRDF(
						parent::triplifyString($prov_id, $this->getPccVoc()."version", parent::safeLiteral($urn_version))
					);
				}
				
				if($urn_release != ""){
					parent::addRDF(
						parent::triplifyString($prov_id, $this->getPccVoc()."release", parent::safeLiteral($urn_release))
					);
				}
			}
		} 

		parent::addRDF(
			parent::describeIndividual($pcid, $pcid_label, $this->getPccVoc()."Compound")
		);

	}

	/**
	* Generate atom ID for a given compound or substance
	**/
	function atom_id($id,$aid){
		return $id."_pcatomid_".$aid;
	}

	/**
	* Generate bond ID for a given compound or substance
	**/
	function bond_id($id,$aid1,$aid2) {
		return $id."_pcbondid_".$bid;
	}

	/**
	*	Generate a unique id for a property of a compound
	*   we do this using a MD5 hash of the chemical identifier and xml record of the 
	*	property being identified.
	*/
	function info_id($cid,$info){
		return md5($cid.$info);
	}


	/**
	*  Function to begin parsing the local copy of the pubchem substances directory
	**/
	function parse_substances(){
		$ignore        = array(".","..");
		$input_dir = $this->getParameterValue('indir')."/substances/" ; $gz=false;
		$this->CreateDirectory($this->getParameterValue('outdir')."/substances/");
		parent::setDatasetURI("bio2rdf_dataset:bio2rdf-".$this->getPcsPrefix()."-".date("Ymd"));

		$graph_uri = parent::getGraphURI();
		//set graph URI to dataset uri
        if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		$dataset_description = '';

		if($handle = opendir($input_dir)){
			while(false !== ($file = readdir($handle))){
				if(in_array($file, $ignore)) continue;
				echo "Processing file: ".$input_dir.$file.PHP_EOL;
				$outfile = realpath($this->getParameterValue('outdir'))."/substances/".basename($file,".xml.gz").".nt";
				
				if(strstr(parent::getParameterValue('output_format'), "gz")) {
					$outfile .= '.gz';
					$gz = true;
				}
				
				echo "... into ".$outfile.PHP_EOL;

				parent::setCheckpoint('file');
				$this->setWriteFile($outfile,$gz);
				$this->parse_substance_file($input_dir,$file);
				$this->getWriteFile()->close();
			}
			closedir($handle);

			$source_file = (new DataResource($this))
				->setURI("http://www.ncbi.nlm.nih.gov/pcsubstance")
				->setTitle("PubChem Substance")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($input_dir)))
				->setFormat("text/xml")
				->setFormat("application/zip")	
				->setPublisher("http://ncbi.nlm.nih.gov/")
				->setHomepage("http://pubchem.ncbi.nlm.nih.gov/")
				->setRights("use")
				->setRights("restricted-by-source-license")
				->setLicense("ftp://ftp.ncbi.nlm.nih.gov/pubchem/README")
				->setDataset("http://identifiers.org/pubchem.substance/");

			$prefix = $this->getPcsPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2df.org/release/$bVersion/$prefix")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/pubchem/pubchem.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")			
				->setRights("use-share-modify")
				->setRights("by-attribution")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons.org/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());

			if($gz) $output_file->setFormat("application/gzip");
			if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
			else $output_file->setFormat("application/n-quads");

			$dataset_description .= $source_file->toRDF().$output_file->toRDF();
			//set graph URI back to default
			parent::setGraphURI($graph_uri);

			// write the dataset description
			$this->setWriteFile($this->getParameterValue('outdir')."/substances/".$this->getBio2RDFReleaseFile());
			$this->getWriteFile()->write($dataset_description);
			$this->getWriteFile()->close();
		}else{
			echo "unable to read directory contents: ".$input_dir."\n";
			exit;
		}
	}

	/**
	*  parse an individual pubchem substance file
	**/
	function parse_substance_file($indir,$file){
		$xml = new CXML($indir,$file);
		while($xml->Parse("PC-Substance") == TRUE) {
			parent::setCheckpoint('record');
			$this->parse_substance_record($xml);
		}
	}

	/**
	*	Convert pubchem substance XML record to RDF
	**/
	function parse_substance_record(&$xml){
		$root        = $xml->GetXMLRoot();

		// pubchem identifier and version
		$sid         = array_shift($root->xpath('//PC-Substance_sid/PC-ID/PC-ID_id'));
		$sid_version = array_shift($root->xpath('//PC-Substance_sid/PC-ID/PC-ID_version'));
		$psid        = $this->getPcsNs().$sid;

		parent::addRDF(
			parent::describeIndividual($psid, null, $this->getPcsVoc()."Substance")
		);

		parent::addRDF(
			parent::triplifyString($psid, $this->getPcsVoc()."version", parent::safeLiteral($sid_version))
		);

		// reference to pubchem compounds
		$pc_compounds = $root->xpath('//PC-Substance_compound/PC-Compounds/PC-Compound');
		foreach($pc_compounds as $compound) {
			$cid = array_shift($compound->xpath('./PC-Compound_id/PC-CompoundType/PC-CompoundType_id/PC-CompoundType_id_cid'));
			$cid_type = array_shift($compound->xpath('./PC-Compound_id/PC-CompoundType/PC-CompoundType_type'));

			if($cid != ""){
				$pcid = $this->getPccNs().$cid;
				parent::addRDF(
					parent::triplify($psid, $this->getPcsVoc()."compound", $pcid)
				);
			}
			

		}
		// database cross references (xref)

		// source identifier
		$source_id   = array_shift($root->xpath('//PC-Substance_source/PC-Source/PC-Source_db/PC-DBTracking/PC-DBTracking_source-id/Object-id/Object-id_str'));

		parent::addRDF(
			parent::triplifyString($psid, $this->getPcsVoc()."source-identifier", parent::safeLiteral($source_id))
		);

		// synonyms
		$synonyms   = $root->xpath('//PC-Substance_synonyms/PC-Substance_synonyms_E');

		foreach($synonyms as $synonym){
			parent::addRDF(
				parent::triplifyString($psid, $this->getPcsVoc()."synonym", parent::safeLiteral($synonym))
			);
		}

		//comment
		$comments     = $root->xpath('//PC-Substance_comment/PC-Substance_comment_E');
		foreach($comments as $comment) {
			if($comment !== ""){
				parent::addRDF(
					parent::triplifyString($psid, "rdfs:comment", parent::safeLiteral($comment))
				);
			}
		}
	}

	private function getPccPrefix(){
		return $this->pccPrefix;
	}
 
 	private function getPcbPrefix(){
		return $this->pcbPrefix;
	}
 
	private function getPcsPrefix(){
		return $this->pcsPrefix;
	}

	private function getPccNs(){
		return $this->getPccPrefix().":";
	}
 
	private function getPcbNs(){
		return $this->getPcbPrefix().":";
	}

	private function getPcsNs(){
		return $this->getPcsPrefix().":";
	}

	private function getPccVoc(){
		return $this->getPccPrefix()."_vocabulary:";
	}
 
	private function getPcbVoc(){
		return $this->getPcbPrefix()."_vocabulary:";
	}

	private function getPcsVoc(){
		return $this->getPcsPrefix()."_vocabulary:";
	}

	private function getPccRes(){
		return $this->getPccPrefix()."_resource:";
	}
 
	private function getPcbRes(){
		return $this->getPcbPrefix()."_resource:";
	}

	private function getPcsRes(){
		return $this->getPcsPrefix()."_resource:";
	}
}

?>
