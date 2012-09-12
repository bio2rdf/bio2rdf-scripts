<?php

require('../../php-lib/rdfapi.php');

class UniProtParser extends RDFFactory {
	private $version = null;

	private $nslist = array(
		"2dbase-ecoli" => "2dbaseecoli",
		"aarhus_ghent_2dpage" => "aarhus_ghent_2dpage",
		"aarhus-ghent-2dpage" => "aarhus_ghent_2dpage",
		"database/Aarhus" => "aarhus_ghent_2dpage",
		"agd" => "agd",
		"agricola" => "agricola",
		"allergome" => "allergome",
		"anu-2dpage" => "anu2dpage",
		"arac-xyls" => "aracxyls",
		"arachnoserver" => "arachnoserver",
		"arrayexpress" => "arrayexpress",
		"bgee" => "bgee",
		"bindingdb" => "bindingdb",
		"biocyc" => "biocyc",
		"brenda" => "brenda",
		"cazy" => "cazy",
		"cgd" => "cgd",
		"citations" => "pubmed",
		"cleanex" => "cleanex",
		"compluyeast-2dpage" => "compluyeast2dpage",
		"conoserver" => "conoserver",
		"cornea-2dpage" => "cornea2dpage",
		"ctd" => "ctd",
		"cygd" => "cygd",
		"dbsnp" => "dbsnp",
		"ddbj" => "ddbj",
		"dictybase" => "dictybase",
		"dip" => "dip",
		"disprot" => "disprot",
		"dmdm" => "dmdm",
		"dnasu" => "dnasu",
		"dosac-cobs-2dpage" => "dosaccobs2dpage",
		"drugbank" => "drugbank",
		"echobase" => "echobase",
		"eco2dbase" => "eco2dbase",
		"ecogene" => "ecogene",
		"eggnog" => "eggnog",
		"embl" => "embl",
		"embl-cds" => "embl",
		"embl_con" => "embl",
		"embl_tpa" => "embl",
		"emblwgs" => "embl",
		"ensembl" => "ensembl",
		"ensemblbacteria" => "ensembl",
		"ensemblfungi" => "ensembl",
		"ensemblmetazoa" => "ensembl",
		"ensemblplants" => "ensembl",
		"ensemblprotists" => "ensembl",
		"enzyme" => "ec",
		"epo" => "epo_prt",
		"euhcvdb" => "euhcvdb",
		"eupathdb" => "eupathdb",
		"evolutionarytrace" => "evolutionarytrace",
		"flybase" => "flybase",
		"genatlas" => "genatlas",
		"gene3d" => "gene3d",
		"genecards" => "genecards",
		"genefarm" => "genefarm",
		"geneid" => "geneid",
		"genetree" => "genetree",
		"genevestigator" => "genevestigator",
		"genolist" => "genolist",
		"genomereviews" => "genomereviews",
		"genomernai" => "genomernai",
		"germonline" => "germonline",
		"glycosuitedb" => "glycosuitedb",
		"go" => "go",
		"goa-projects" => "goa",
		"gpcrdb" => "gpcrdb",
		"gramene" => "gramene",
		"h_inv" => "hinvdb",
		"h-invdb" => "hinvdb",
		"hamap" => "hamap",
		"hgnc" => "hgnc",
		"hogenom" => "hogenom",
		"hovergen" => "hovergen",
		"hpa" => "hpa",
		"hssp" => "hssp",
		"huge" => "huge",
		"imgt" => "imgt",
		"intact" => "intact",
		"interpro" => "interpro",
		"inparanoid" => "inparanoid",
		"ipi" => "ipi",
		"isoforms" => "uniprot",
		"jpo" => "jpo_prt",
		"kegg" => "kegg",
		"kipo" => "kipo_prt",
		"ko" => "ko",
		"legiolist" => "legiolist",
		"leproma" => "leproma",
		"maizegdb" => "maizegdb",
		"medline" => "pubmed",
		"merops" => "merops",
		"mgi" => "mgi",
		"micado" => "micado",
		"mim" => "omim",
		"mint" => "mint",
		"modbase" => "modbase",
		"nextbio" => "nextbio",
		"nextprot" => "nexprot",
		"ogp" => "ogp",
		"oma" => "oma",
		"orphanet" => "orphanet",
		"orthodb" => "orthodb",
		"panther" => "panther",
		"pathway-interaction-db" => "pathway_interaction_db",
		"patric" => "patric",
		"pdb" => "pdb",
		"pdbj" => "pdb",
		"pdbsum" => "pdb",
		"peptideatlas" => "peptideatlas",
		"peroxibase" => "peroxibase",
		"pfam" => "pfam",
		"pharmgkb" => "pharmgkb",
		"phci-2dpage" => "phci2dpage",
		"phosphosite" => "phosphosite",
		"phossite" => "phossite",
		"phylomedb" => "phylomedb",
		"pir" => "pir",
		"pirsf" => "pirsf",
		"pmap-cutdb" => "pmapcutdb",
		"pmma-2dpage" => "pmma2dpage",
		"pombase" => "pombase",
		"pptasedb" => "pptasedb",
		"pride" => "pride",
		"prf" => "prf",
		"prints" => "prints",
		"prodom" => "prodom",
		"promex" => "promex",
		"prosite" => "prosite",
		"protclustdb" => "protclustdb",
		"proteinmodelportal" => "proteinmodelportal",
		"protonet" => "protonet",
		"pseudocap" => "pseudocap",
		"pubmed" => "pubmed",
		"rat-heart-2dpage" => "ratheart2dpage",
		"reactome" => "reactome",
		"rebase" => "rebase",
		"refseq" => "refseq",
		"reproduction-2dpage" => "reproduction2dpage",
		"rgd" => "rgd",
		"rouge" => "rouge",
		"sbkb" => "sbkb",
		"sgd" => "sgd",
		"siena-2dpage" => "siena2dpage",
		"smart" => "smart",
		"smr" => "smr",
		"source" => "source",
		"string" => "string",
		"supfam" => "supfam",
		"swiss-2dpage" => "swiss2dpage",
		"tair" => "tair",
		"tair_arabidopsis" => "tair",
		"tcdb" => "tcdb",
		"tigr" => "tigr",
		"tigrfams" => "tigrfams",
		"trome" => "trome",
		"tuberculist" => "tuberculist",
		"ucd-2dpage" => "ucd2dpage",
		"ucsc" => "ucsc",
		"unigene" => "unigene",
		"unimes" => "unimes",
		"unipathway" => "unipathway",
		"uspto" => "uspto_prt",
		"vectorbase" => "vectorbase",
		"vega" => "vega",
		"world-2dpage" => "world2dpage",
		"wormbase" => "wormbase",
		"xenbase" => "xenbase",
		"zfin" => "zfin",
	);

	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("uniprot");

		// set and print application parameters
		$this->AddParameter('files',true,'all|citations|keywords|locations|taxonomy|tissues|uniprot|uniparc|uniref','all','all or comma-separated list of files to process');
		$this->AddParameter('indir',false,null,'/data/download/sgd/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/sgd/','directory to place rdfized files');
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

			$ofile = $odir.$file.'.ttl'; 
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
		while($l = $this->GetReadFile()->Read(4096)) {
			preg_match("/<(.*?)> <(.*?)> <(.*)> \.$/", $l, $matches);
			if(!empty($matches)){
				$subject_tmp = $matches[1];
				$predicate_tmp = $matches[2];
				$object_tmp = $matches[3];

				$subject = null;
				$predicate = null;
				$object = null;

				preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/uniprot\\/(.*)/", $subject_tmp, $subj_matches);
				preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/core\\/(.*)/", $predicate_tmp, $pred_matches);
				preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/uniprot\\/(.*)/", $object_tmp, $obj_matches);

				if(!empty($subj_matches)){
					$subject = "http://bio2rdf.org/uniprot:".$subj_matches[1];
					$this->AddRDF($this->Quad($subject, "http://www.w3.org/2000/01/rdf-schema#seeAlso", $subject_tmp));
				} else {
					preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/(.*)\\/(.*)/", $subject_tmp, $subj_matches);
					if(!empty($subj_matches)){
						if(!array_key_exists($subj_matches[1], $this->nslist)){
							if($subj_matches[1] == "citations"){
								$subject = "http://bio2rdf.org/pubmed:".$subj_matches[2];
							} elseif($subj_matches[2] == "taxonomy"){
								$subject = "http://bio2rdf.org/taxon:".$subj_matches[2];
							} else {
								$subject = "http://bio2rdf.org/uniprot_resource:".$subj_matches[1]."_".$subj_matches[2];
							}
						} else {
							$subject = "http://bio2rdf.org/".$this->nslist[$subj_matches[1]].":".$subj_matches[2];
						}
						$this->AddRDF($this->Quad($subject, "http://www.w3.org/2000/01/rdf-schema#seeAlso", $subject_tmp));
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
							if(!array_key_exists($obj_matches[1], $this->nslist)){
								if($obj_matches[1] == "citations"){
									$object = "http://bio2rdf.org/pubmed:".$obj_matches[2];
								} elseif($obj_matches[2] == "taxonomy"){
									$object = "http://bio2rdf.org/taxon:".$obj_matches[2];
								} else {
									$object = "http://bio2rdf.org/uniprot_resource:".$obj_matches[1]."_".$obj_matches[2];
								}
							} else {
								$object = "http://bio2rdf.org/".$this->nslist[$obj_matches[1]].":".$obj_matches[2];
							}							
							$this->AddRDF($this->Quad($object, "http://www.w3.org/2000/01/rdf-schema#seeAlso", $object_tmp));
						} else {
							$object = $object_tmp;
						}
					}
				}
				$this->AddRDF($this->Quad($subject, $predicate, $object));
				$this->AddRDF($this->Quad($subject, "http://rdfs.org/ns/void#inDataset", "http://bio2rdf.org/".$this->GetDatasetURI()));
			} else {
				preg_match("/<(.*?)> <(.*?)> \"(.*)\" \.$/", $l, $matches);
				if(!empty($matches)){
					$subject_tmp = $matches[1];
					$predicate_tmp = $matches[2];
					$literal_tmp = $matches[3];

					preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/uniprot\\/(.*)/", $subject_tmp, $subj_matches);
					preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/core\\/(.*)/", $predicate_tmp, $pred_matches);

					$subject = null;
					$predicate = null;
					$literal = null;

					if(!empty($subj_matches)){
						$subject = "http://bio2rdf.org/uniprot:".$subj_matches[1];
						$this->AddRDF($this->Quad($subject, "http://www.w3.org/2000/01/rdf-schema#seeAlso", $subject_tmp));
					} else {
						preg_match("/http:\\/\\/purl\\.uniprot\\.org\\/(.*)\\/(.*)/", $subject_tmp, $subj_matches);
						if(!empty($subj_matches)){
							if(!array_key_exists($subj_matches[1], $this->nslist)){
								if($subj_matches[1] == "citations"){
									$subject = "http://bio2rdf.org/pubmed:".$subj_matches[2];
								} elseif($subj_matches[2] == "taxonomy"){
									$subject = "http://bio2rdf.org/taxon:".$subj_matches[2];
								} else {
									$subject = "http://bio2rdf.org/uniprot_resource:".$subj_matches[1]."_".$subj_matches[2];
								}
							} else {
								$subject = "http://bio2rdf.org/".$this->nslist[$subj_matches[1]].":".$subj_matches[2];
							}
							$this->AddRDF($this->Quad($subject, "http://www.w3.org/2000/01/rdf-schema#seeAlso", $subject_tmp));
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

					$literal = $literal_tmp;

					$this->AddRDF($this->QuadL($subject, $predicate, $literal));
					$this->AddRDF($this->Quad($subject, "http://rdfs.org/ns/void#inDataset", "http://bio2rdf.org/".$this->GetDatasetURI()));
				}
			}
			$this->WriteRDFBufferToWriteFile();
		}
	}//process
}

set_error_handler('error_handler');
$parser = new UniProtParser($argv);
$parser->Run();

?>