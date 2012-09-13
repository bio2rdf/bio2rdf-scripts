<?php

require('../../php-lib/rdfapi.php');

class UniProtParser extends RDFFactory {
	private $version = null;

	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("uniprot");

		// set and print application parameters
		$this->AddParameter('files',true,'all|citations|keywords|locations|taxonomy|tissues|uniprot|uniparc|uniref','all','all or comma-separated list of files to process');
		$this->AddParameter('indir',false,null,'/data/download/uniprot/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/uniprot/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://www.uniprot.org/');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}

		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));

		return TRUE;
	}

	function Run(){
		if($this->GetParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",$this->GetParameterValue('files'));
		}

		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rdir = $this->GetParameterValue('download_url');

		//make sure directories end with slash
		if(substr($ldir, -1) !== "/"){
			$ldir = $ldir."/";
		}

		if(substr($odir, -1) !== "/"){
			$odir = $odir."/";
		}

		$file_list = array(
			"citations" => array("local" => "citations.n3.gz", "remote" => "citations/?query=*&format=nt&compress=yes"),
			"keywords" => array("local" =>"keywords.n3.gz", "remote" => "keywords/?query=*&format=nt&compress=yes" ),
			"locations" => array("local" => "locations.n3.gz", "remote" => "locations/?query=*&format=nt&compress=yes"),
			"taxonomy" => array("local" => "taxonomy.n3.gz", "remote" => "taxonomy/?query=*&format=nt&compress=yes"),
			"tissues" => array("local" => "tissues.n3.gz", "remote" => "tissues/?query=*&format=nt&compress=yes"),
			"uniprot" => array("local" => "uniprot.n3.gz", "remote" => "uniprot/?query=*&format=nt&compress=yes"),
			"uniparc" => array("local" => "uniparc.n3.gz", "remote" => "uniparc/?query=*&format=nt&compress=yes"),
			"uniref" => array("local" => "uniref.n3.gz", "remote" => "uniref/?query=*&format=nt&compress=yes")
		);

		foreach($files as $file){

			$lfile = $ldir.$file_list[$file]["local"];

			if(!file_exists($lfile) && $this->GetParameterValue('download') == false) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
			}

					//download all files [except mapping file]
			if($this->GetParameterValue('download') == true) {
				$rfile = $rdir.$file_list[$file]["remote"];
				echo "downloading $file ... ";
				file_put_contents($lfile,file_get_contents($rfile));
			}

			$ofile = $odir.$file.'.nt'; 
			$gz=false;

			if($this->GetParameterValue('gzip')) {
				$ofile .= '.gz';
				$gz = true;
			}

			$this->SetWriteFile($ofile, $gz);
			$this->SetReadFile($lfile, TRUE);

			$rn = rand();
			echo "processing $file... ";
			$this->process($rn);
			echo "done!";

			//close write file
			$this->GetWriteFile()->Close();
			echo PHP_EOL;

		}//foreach

		// generate the dataset release file
		echo "generating dataset release file... ";
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/uniprot/uniprot.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()),
			"http://uniprot.org",
			array("use"),
			"http://uniprot.org",
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		echo "done!".PHP_EOL;
		
	}//Run

	function process($random_number){

		$namespace = $this->GetNS();

		while($l = $this->GetReadFile()->Read(4096)) {

			$subject = null;
			$predicate = null;
			$object = null;

			preg_match("/<(.*?)> <(.*?)> <(.*)> \.$/", $l, $matches);
			if(!empty($matches)){
				$subject_tmp = $matches[1];
				$predicate_tmp = $matches[2];
				$object_tmp = $matches[3];

				preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/uniprot\\/(.*)/", $subject_tmp, $subj_matches);
				preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/core\\/(.*)/", $predicate_tmp, $pred_matches);
				preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/uniprot\\/(.*)/", $object_tmp, $obj_matches);

				if(!empty($subj_matches)){
					$subject = "http://bio2rdf.org/uniprot:".$subj_matches[1];
				} else {
					preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/(.*)\\/(.*)/", $subject_tmp, $subj_matches);
					if(!empty($subj_matches)){
						$sns = $subj_matches[1];
						$sid = $subj_matches[2];
						if($sns == "citations"){
							$subject = "http://bio2rdf.org/pubmed:".$sid;
						} elseif($sns == "taxonomy"){
							$subject = "http://bio2rdf.org/taxon:".$sid;
						} elseif($sns == "annotation" || $sns == "database"|| $sns == "isoforms" || $sns == "keywords" || $sns == "locations" || $sns == "patents" || $sns == "tissues"){
							$subject = "http://bio2rdf.org/uniprot_resource:".$sns."_".$sid;
						} else {
							$sqname = $namespace->MapQName($sns.":".$sid); //get canonical namespace from ns.php
							$subject = "http://bio2rdf.org/".$sqname;
						}
					} else {
						preg_match("/#_(.*)/", $subject_tmp, $bn_matches);
						if(!empty($bn_matches)){
							$subject = "http://bio2rdf.org/uniprot_resource:".$random_number."_".$bn_matches[1];
						}
					}
				}
				
				if(!empty($pred_matches)){
					$predicate = "http://bio2rdf.org/uniprot_vocabulary:".$pred_matches[1];
				} else {
					$predicate = $predicate_tmp;
				}

				if(!empty($obj_matches)){
					$object = "http://bio2rdf.org/uniprot:".$obj_matches[1];
					$this->AddRDF($this->Quad($object, "http://www.w3.org/2000/01/rdf-schema#seeAlso", $object_tmp));
				} else {
					preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/core\\/(.*)/", $object_tmp, $obj_matches);
					if(!empty($obj_matches)){
						$object = "http://bio2rdf.org/uniprot_vocabulary:".$obj_matches[1];
					} else {
						preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/(.*)\\/(.*)/", $object_tmp, $obj_matches);
						if(!empty($obj_matches)){
							$ons = $obj_matches[1];
							$oid = $obj_matches[2];
							if($ons == "citations"){
								$object = "http://bio2rdf.org/pubmed:".$oid;
							} elseif($ons == "taxonomy"){
								$object = "http://bio2rdf.org/taxon:".$oid;
							} elseif($ons == "annotation" || $ons == "database"|| $ons == "isoforms" || $ons == "keywords" || $ons == "locations" || $ons == "patents" || $ons == "tissues"){
								$object = "http://bio2rdf.org/uniprot_resource:".$ons."_".$oid;
							} else {
								$oqname = $namespace->MapQName($ons.":".$oid); //get canonical namespace from ns.php
								$object = "http://bio2rdf.org/".$oqname;
							}
							$this->AddRDF($this->Quad($object, "http://www.w3.org/2000/01/rdf-schema#seeAlso", $object_tmp));
						} else {
							$object = $object_tmp;
						}
					}
				}
				$this->AddRDF($this->Quad($subject, $predicate, $object));
			} else {
				preg_match("/<(.*?)> <(.*?)> \"(.*)\" \.$/", $l, $matches);
				if(!empty($matches)){
					$subject_tmp = $matches[1];
					$predicate_tmp = $matches[2];
					$literal_tmp = $matches[3];

					preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/uniprot\\/(.*)/", $subject_tmp, $subj_matches);
					preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/core\\/(.*)/", $predicate_tmp, $pred_matches);

					if(!empty($subj_matches)){
						$subject = "http://bio2rdf.org/uniprot:".$subj_matches[1];
					} else {
						preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/(.*)\\/(.*)/", $subject_tmp, $subj_matches);
						if(!empty($subj_matches)){
							$sns = $subj_matches[1];
							$sid = $subj_matches[2];
							if($sns == "citations"){
								$subject = "http://bio2rdf.org/pubmed:".$sid;
							} elseif($sns == "taxonomy"){
								$subject = "http://bio2rdf.org/taxon:".$sid;
							} elseif($sns == "annotation" || $sns == "database"|| $sns == "isoforms" || $sns == "keywords" || $sns == "locations" || $sns == "patents" || $sns == "tissues"){
								$subject = "http://bio2rdf.org/uniprot_resource:".$sns."_".$sid;
							} else {
								$sqname = $namespace->MapQName($sns.":".$sid); //get canonical namespace from ns.php
								$subject = "http://bio2rdf.org/".$sqname;
							}
						} else {
							preg_match("/#_(.*)/", $subject_tmp, $bn_matches);
							if(!empty($bn_matches)){
								$subject = "http://bio2rdf.org/uniprot_resource:".$random_number."_".$bn_matches[1];
							}
						}
					}

					if(!empty($pred_matches)){
						$predicate = "http://bio2rdf.org/uniprot_vocabulary:".$pred_matches[1];
					} else {
						$predicate = $predicate_tmp;
					}

					$object = $literal_tmp;
					$this->AddRDF($this->QuadL($subject, $predicate, $object));
				}
				$this->AddRDF($this->Quad($subject, "http://www.w3.org/2000/01/rdf-schema#seeAlso", $subject_tmp));
				$this->AddRDF($this->Quad($subject, "http://rdfs.org/ns/void#inDataset", "http://bio2rdf.org/".$this->GetDatasetURI()));
			}
			$this->WriteRDFBufferToWriteFile();
		}
	}//process
}

set_error_handler('error_handler');
$parser = new UniProtParser($argv);
$parser->Run();

?>