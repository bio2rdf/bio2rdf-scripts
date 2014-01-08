<?php
/**
Copyright (C) 2013 Michel Dumontier, Alison Callahan

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
 * An RDF generator for SGD (http://www.yeastgenome.org/)
 * @version 2.0
 * @author Michel Dumontier
 * @author Alison Callahan
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../common/php/oboparser.php');

class SGDParser extends Bio2RDFizer {
	private $version = null;

	function __construct($argv) {
		parent::__construct($argv,"sgd");
		parent::addParameter('files',true,'all|dbxref|features|domains|protein|goa|goslim|complex|interaction|phenotype|pathways|mapping','all','all or comma-separated list of files to process');
		parent::addParameter('download_url',false,null,'http://downloads.yeastgenome.org/');
		parent::addParameter('ncbo_download_dir', false, null, '/data/download/ncbo', 'directory of ncbo ontologies');
		parent::addParameter('ncbo_api_key',true,null,null,'your NCBO API key');
		parent::initialize();
	}

	function Run(){

		if(parent::getParameterValue('download') === true) 
		{
			$this->download();
		}
		if(parent::getParameterValue('process') === true) 
		{
			$this->process();
		}
 		
	}

	function download(){

		if(parent::getParameterValue('files') == 'all') {
			$files = explode("|",parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",parent::getParameterValue('files'));
		}

		$ldir = parent::getParameterValue('indir');
		$rdir = parent::getParameterValue('download_url');

		$rfiles = array(
 			 "dbxref"      => "curation/chromosomal_feature/dbxref.tab",
 			 "features"    => "curation/chromosomal_feature/SGD_features.tab",
 			 "domains"     => "curation/calculated_protein_info/domains/domains.tab",
 			 "protein"     => "curation/calculated_protein_info/protein_properties.tab",
			 "goa"         => "curation/literature/gene_association.sgd.gz",
			 "goslim"      => "curation/literature/go_slim_mapping.tab",
			 "complex"     => "curation/literature/go_protein_complex_slim.tab",
			 "interaction" => "curation/literature/interaction_data.tab",
			 "phenotype"   => "curation/literature/phenotype_data.tab",
			 "pathways"    => "curation/literature/biochemical_pathways.tab",
			 "mapping"     => "mapping"
 		);

		foreach($files as $file){

			$ext = substr(strrchr($rfiles[$file], '.'), 1);
			if($ext == "tab"){
				$lfile = $ldir."sgd_".$file.".tab";
			} elseif($ext = "gz"){
				$lfile = $ldir."sgd_".$file.".tab.gz";
			}
			
			//download all files [except mapping file]
			if($file !== "mapping") {
				$rfile = $rdir.$rfiles[$file];
				echo "Downloading $file ... ";
				Utils::DownloadSingle ($rfile, $lfile);
			}
		}
	}

	function process(){
		if(parent::getParameterValue('files') == 'all') {
			$files = explode("|",parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",parent::getParameterValue('files'));
		}

		$ldir = parent::getParameterValue('indir');
		$rdir = parent::getParameterValue('download_url');
		$odir = parent::getParameterValue('outdir');

		$rfiles = array(
 			 "dbxref"      => "curation/chromosomal_feature/dbxref.tab",
 			 "features"    => "curation/chromosomal_feature/SGD_features.tab",
 			 "domains"     => "curation/calculated_protein_info/domains/domains.tab",
 			 "protein"     => "curation/calculated_protein_info/protein_properties.tab",
			 "goa"         => "curation/literature/gene_association.sgd.gz",
			 "goslim"      => "curation/literature/go_slim_mapping.tab",
			 "complex"     => "curation/literature/go_protein_complex_slim.tab",
			 "interaction" => "curation/literature/interaction_data.tab",
			 "phenotype"   => "curation/literature/phenotype_data.tab",
			 "pathways"    => "curation/literature/biochemical_pathways.tab",
			 "mapping"     => "mapping"
 		);

		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		$dataset_description = '';

		foreach($files as $file){

			$ext = substr(strrchr($rfiles[$file], '.'), 1);
			if($ext == "tab"){
				$lfile = "sgd_".$file.".tab";
			} elseif($ext = "gz"){
				$lfile = "sgd_".$file.".tab.gz";
			}

			$rfile = $rdir.$rfiles[$file];

			if(!file_exists($lfile) && parent::getParameterValue('download') == false) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				
				Utils::DownloadSingle ($rfile, $ldir.$lfile);
			}
			
			$suffix = parent::getParameterValue('output_format');
			$ofile = "sgd_".$file.'.'.$suffix; 
			
			$gz = false;
						
			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}
			
			parent::setWriteFile($odir.$ofile, $gz);

			//parse file
			parent::setReadFile($ldir.$lfile, $gz);
			
			$fnx = $file;	
			echo "Processing $file... ";
			$this->$fnx();
			echo PHP_EOL."done!";

			//write RDF to file
			parent::writeRDFBufferToWriteFile();

			//close write file
			parent::getWriteFile()->close();
			echo PHP_EOL;

			// generate the dataset release file
			echo "Generating dataset description... ".PHP_EOL;
			// dataset description
			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("Saccharomyces Genome Database ($file)")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
				->setFormat("text/tab-separated-value")
				->setFormat("application/gzip")	
				->setPublisher("http://www.yeastgenome.org/")
				->setHomepage("http://www.yeastgenome.org/")
				->setRights("use")
				->setLicense("http://www.stanford.edu/site/terms.html")
				->setDataset("http://identifiers.org/sgd/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix - $file")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/sgd/sgd.php")
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
			
		}//foreach

		//set graph URI back to default
		parent::setGraphURI($graph_uri);

		//write dataset description to file
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;

	}

	function dbxref(){
		while($l = $this->getReadFile()->read(2048)) {

			list($id, $ns, $type, $name, $sgdid) = explode("\t",trim($l));;
					
			$sameas = 'owl:sameAs';
			$seealso = 'rdfs:seeAlso';
			$suf = "";
			$rel = "";

			switch($ns) {
				case "AspGD":
					$ns = 'aspgd';
					$rel = $seealso;
					break;
				case "BioGRID":
					$ns  = 'biogrid';
					$rel = $sameas;
					break;
				case "CGD":
					$ns = 'candida'; 
					$rel = $sameas;
					break;
				case "DIP":
					$ns = 'dip'; $rel = $sameas;
					$suf='gp';
					break;
				case "EBI":
					
					if($type == "UniParc ID") {
						$ns='uniparc'; 
						$rel = $sameas;
						$suf='gp';
						break;
					}

					if($type == "UniProt/Swiss-Prot ID") {
						$ns='uniprot';
						$rel=$sameas;
						$suf='gp';
						break;
					}
					
					if($type == "UniProt/TrEMBL ID") {
						$ns='trembl';
						$rel=$sameas;
						$suf='gp';
						break;
					}
					break;
				
				case "EUROSCARF":
					$ns = 'euroscarf';
					$rel=$sameas;
					break;
				case "GenBank/EMBL/DDBJ":
					$ns = 'genbank';
					$rel=$sameas;
					break;
				case "GermOnline":
					$ns = 'germonline';
					$rel=$sameas;
					break;
				case "IUBMB":
					$ns = 'ec';
					$rel=$seealso;
					break;
				case "MetaCyc":
					$ns = 'metacyc';
					$rel=$seealso;
					break;
				case "NCBI":
					if($type == "DNA accession ID") {
						$ns='genbank'; 
						$rel=$sameas;  
						break;
					}

					if($type == "Gene ID") {
						$ns='geneid';
						$rel=$sameas;
						break;
					}

					if($type == "NCBI protein GI") {
						$ns='gi';
						$rel=$sameas;
						$suf='gp';
						break;
					}
					
					if($type == "RefSeq Accession") {
						$ns='refseq';
						$rel=$sameas;
						$suf='gp';
						break;
					}
					
					if($type == "RefSeq protein version ID") {
						$ns='refseq';
						$rel=$sameas;
						$suf='gp';
						break;
					}
				case "TCDB":
					$ns = 'tcdb';$rel=$seealso;break;
				default:
					echo "unable to map $ns : $id to $sgdid".PHP_EOL;
			}
			
			if($rel) { 
				if($suf == 'gp'){
					$qname = "sgd_resource:".$sgdid.$suf;
					//if the entity is not an sgd entity but a bio2rdf sgd entity, use the sgd_resource namespace
					$this->addRDF(
							parent::triplify($qname,$this->getVoc().$rel, "$ns:$id")
					);
				
				} else {
					//otherwise use the sgd namespace
					$qname = "sgd:".$sgdid.$suf;
					$this->addRDF(
						parent::triplify($qname,$this->getVoc().$rel, "$ns:$id")
					);
				}//else
			}//if
				
		}//while

		return TRUE;
	}//dbxref

	function features(){

		while($l = $this->GetReadFile()->Read(4098)) {
			if($l[0] == '!') continue;
			$a = explode("\t",$l);

			$id = $oid = $a[0];
			$id = urlencode($id);
			$sid = "sgd:$id";

			/// record
			$rid = "sgd_resource:record_$id";
			$rid_label = "Record for $sid [$rid]";
			$rid_type = $this->getVoc()."Record";
			$this->AddRDF(
				parent::triplify($rid, $this->getVoc()."is-about", $sid).
				parent::describeIndividual($rid, $rid_label, $rid_type).
				parent::describeProperty($this->getVoc()."is-about", "Relationship between an SGD record and the SGD identifier it describes").
				parent::describeClass($this->getVoc()."Record", "An SGD record")
			);

			if($a[14]) {
				$b = explode("|",$a[14]);
				foreach($b AS $c) {
					$this->AddRDF(
						parent::triplifyString($rid, $this->getVoc()."modified", $c).
						parent::describeProperty($this->getVoc()."modified", "Relationship between an SGD record and the date it was modified")
					);
					//$this->AddRDF($this->QQuadL($rid,"sgd_vocabulary:modified",$c));
				}
			}
			
			/// entry
			$label = $a[4]." "; // standard name
			if($a[3]) $label .= "(".$a[3].")";    // 
			if(!$label && $a[6]) $label .= "$a[1] for $a[6]";
			$description = null;
			if(trim($a[15])){
				$description = addslashes(trim($a[15]));
			}
			
			$feature_type = $this->GetFeatureType($a[1]);
			$this->AddRDF(
				parent::describeIndividual($sid, $label, $feature_type, null, $description)
			);

			unset($type);

			if($a[1] == "ORF") {
				$type = $this->getVoc()."Protein";
			} elseif(stristr($a[1],"rna")){
				$type = $this->getVoc()."RNA";
			}

			if(isset($type)) {
				unset($p1);
				unset($p2);
				$gp = 'sgd_resource:'.$id."gp";
				$gplabel = "$id"."gp";
				$this->AddRDF(
					parent::triplify($sid, $this->getVoc()."encodes", $gp).
					parent::describeIndividual($gp, $gplabel, $type).
					parent::describeProperty($this->getVoc()."encodes", "Relationship between an SGD gene and the gene product it encodes")
				);

				if($a[1] == "ORF" && $a[3] != '') {
					$p1 = ucfirst(strtolower(str_replace(array("(",")"), array("%28","%29"), $a[3])))."p";
					$p1label = "$p1";
					$this->addRDF(
						parent::triplify($sid, $this->getVoc()."encodes", "sgd:$p1").
						parent::triplify("sgd:$p1", "owl:sameAs", $gp).
						parent::describeIndividual("sgd:$p1", $p1label, $this->getVoc()."Protein")
					);
				}
				if($a[1] == "ORF" && $a[4] != '') {
					$p2 = ucfirst(strtolower(str_replace(array("(",")"), array("%28","%29"), $a[4])))."p";
					$p2label = "$p2";
					$this->AddRDF(
						parent::triplify($sid, $this->getVoc()."encodes", "sgd:$p2").
						parent::triplify("sgd:$p2", "owl:sameAs", $gp).
						parent::describeIndividual("sgd:$p2", $p2label, $this->getVoc()."Protein")
					);
				}
				if(isset($p1) && isset($p2)){
					$this->AddRDF(
						parent::triplify("sgd:$p1", "owl:sameAs", "sgd:$p2")
					);
				} 
					
			}

			// feature qualifiers (uncharacterized, verified, silenced_gene, dubious)
			if($a[2]) {
				$qualifiers = explode("|",$a[2]);
				foreach($qualifiers AS $q) {
					$this->AddRDF(
						parent::triplifyString($sid, $this->getVoc()."status", "$q").
						parent::describeProperty($this->getVoc()."status", "Relationship between an SGD entry and it feature qualifier(s)")
					);
				}
			}
			
			// unique feature name
			if($a[3]) {
				$nid = str_replace(array("(",")"), array("%28","%29"),$a[3]);
				$this->AddRDF(
					parent::triplifyString($sid, $this->getVoc()."prefLabel", $a[3]).
					parent::triplify($sid, "owl:sameAs", "sgd:$nid").
					parent::describeProperty($this->getVoc()."prefLabel", "Relationship between an SGD entry and its unique feature name")
				);
			}
			
			// common names
			if($a[4]) {
				$nid = str_replace(array("(",")"), array("%28","%29"), $a[4]);
				$this->AddRDF(
					parent::triplifyString($sid, $this->getVoc()."standardName", $a[4]).
					parent::triplify($sid, "owl:sameAs", "sgd:$nid").
					parent::describeProperty($this->getVoc()."standardName", "Relationship between an SGD entry and its common name")
				);
			}
			if($a[5]) {
				$b = explode("|",$a[5]);
				foreach($b AS $name) {
					$this->AddRDF(
						parent::triplifyString($sid, $this->getVoc()."alias", str_replace('"', '', $name)).
						parent::describeProperty($this->getVoc()."alias", "Relationship between an SGD entry and its alias(es)")
					);	
				}
			}
			// parent feature
			$parent_type = '';
			if($a[6]) {
				$parent = str_replace(array("(",")"," "), array("%28","%29","_"), $a[6]);
				$this->addRDF(
					parent::triplify($sid, $this->getVoc()."is-proper-part-of", $this->getRes().$parent).
					parent::describeProperty($this->getVoc()."is-proper-part-of", "Relationship between an SGD entity and an entity it is a proper part of")
				);
				if(strstr($parent,"chromosome")) {
					$parent_type = 'c';
					if(!isset($chromosomes[$parent])) $chromosomes[$parent] = '';
					else {
						$this->AddRDF(
							parent::describeIndividual($this->getRes().$parent, $a[6], $this->getVoc()."Chromosome").
							parent::describeClass($this->getVoc()."Chromosome", "SGD Chromosome")
						);
					}
				}
			}
			// secondary sgd id (starts with an L)
			if($a[7]) {
				if($a[3]) {
					$b = explode("|",$a[7]);
					foreach($b AS $c) {
						$this->AddRDF(
							parent::triplify($sid, "owl:sameAs", "sgd:$c")
						);
					}
				}
			}
			// chromosome
			unset($chr);
			if($a[8] && $parent_type != 'c') {
				$chr = "chromosome_".$a[8];
				$this->AddRDF(
					parent::triplify($sid, $this->getVoc()."is-proper-part-of", $this->getRes().$chr)
				);
			}
			// watson or crick strand of the chromosome
			unset($strand);
			if($a[11]) {
				$chr = "chromosome_".$a[8];
				$strand_type = ($a[11]=="w"?"WatsonStrand":"CrickStrand");
				$strand = $chr."_".$strand_type;
				$this->AddRDF(
					parent::triplify($sid, $this->getVoc()."is-proper-part-of", $this->getRes().$strand)
				);
				if(!isset($strands[$strand])) {
					$strands[$strand] = '';
					$this->AddRDF(
						parent::describeIndividual($this->getRes().$strand, "$strand_type for $chr", $this->getVoc().$strand_type).
						parent::triplify($this->getRes().$strand, $this->getVoc()."is-proper-part-of", $this->getRes().$chr).
						parent::describeClass($this->getVoc().$strand_type, "$strand_type")
					);
				}
			}
			
			// position
			if($a[9]) {
				$loc = $id."loc";
				$this->AddRDF(
					parent::triplify($sid, $this->getVoc()."location", $this->getRes().$loc).
					parent::triplifyString($this->getRes().$loc, $this->getVoc()."has-start-position", $a[9]).
					parent::triplifyString($this->getRes().$loc, $this->getVoc()."has-stop-position", $a[10]).
					parent::describeIndividual($this->getRes().$loc, "Genomic location of $sid", $this->getVoc()."Location", null, null).
					parent::describeProperty($this->getVoc()."has-start-position", "Relationship between an SGD chromosomal location and its start position").
					parent::describeProperty($this->getVoc()."has-stop-position", "Relationship between an SGD chromosomal location and its stop position")
				);
				if(isset($chr)){
					$this->AddRDF(
						parent::triplify($this->getRes().$loc, $this->getVoc()."chromosome", $this->getRes().$chr).
						parent::describeProperty($this->getRes()."chromosome", "Relationship between an SGD chromosomal location and its chromosome")
					);
				}
				if(isset($strand)){
					$this->AddRDF(
						parent::triplify($this->getRes().$loc, $this->getVoc()."strand", $this->getRes().$strand).
						parent::describeProperty($this->getRes()."strand", "Relationship between an SGD chromosomal location and its strand")
					);
				}
			}
		}//while
		return TRUE;
	}//features

	function domains(){

		$domain_ns = array (
			"ProfileScan" => "profilescan",
			"superfamily" => "superfamily",
			"PatternScan" => "patternscan",
			"BlastProDom" => "prodom",
			"FPrintScan" => "fprintscan",
			"Gene3D" => "gene3d",
			"Coil" => "coil",
			"Seg" => "seg",
			"HMMSmart" => "smart",
			"HMMPanther" => "panther",
			"HMMPfam" => "pfam",
			"HMMPIR" => "pir",
			"HMMTigr" => "tigr"
		);
		
		while($l = $this->GetReadFile()->Read(2048)) {
			$a = explode("\t",$l);

			$id = "sgd:".$a[0]."p";
			
			$domain = $domain_ns[$a[3]].":".$a[4];
			//if a[4] = 'seg', URI not necessarily unique - adding protein, start and stop positions to domain identifier as well
			if($a[4] == 'seg'){
				$domain .= "-".$a[0]."-".$a[6]."-".$a[7];
			}

			$this->AddRDF(
				parent::triplify($id, $this->getVoc()."has-proper-part", $domain).
				parent::describeProperty($this->getVoc()."has-proper-part", "Relationship between an SGD entity and an entity that is its proper part")
			);

			$da = "sgd_resource:da_".$a[0]."p_$a[4]_$a[6]_$a[7]";
			$this->AddRDF(
				parent::describeIndividual($da, "domain alignment between $id and $domain", $this->getVoc()."Domain-Alignment").
				parent::triplify($da, $this->getVoc()."query", $id).
				parent::triplify($da, $this->getVoc()."target", $domain).
				parent::triplifyString($da, $this->getVoc()."query-start", $a[6]).
				parent::triplifyString($da, $this->getVoc()."query-stop", $a[7]).
				parent::triplifyString($da, $this->getVoc()."e-value", $a[8]).
				parent::describeProperty($this->getVoc()."query", "Relationship between an SGD domain alignment and its query SGD entity").
				parent::describeProperty($this->getVoc()."target", "Relationship between an SGD domain alignment and its target SGD domain").
				parent::describeProperty($this->getVoc()."query-start", "Relationship between an SGD domain alignment and its query start position").
				parent::describeProperty($this->getVoc()."query-stop", "Relationship between an SGD domain alignment and its query end position").
				parent::describeProperty($this->getVoc()."e-value", "Relationship between an SGD domain alignment and its e-value")
			);
		}
		return TRUE;
	}//domains

	function protein(){
		$properties = array(
			"2" => array( 'id' => "MW", 'type' => "MolecularWeight"),
			"3" => array( 'id' => "PI", 'type' => "IsolectricPoint"),
			"4" => array( 'id' => "CAI", 'type' => "CodonAdaptationIndex"),
			"5" => array( 'id' => "Length", 'type' => "SequenceLength"),
			"8" => array( 'id' => "CB", 'type' => "CodonBias"),
			"9" => array( 'id' => "ALA", 'type' => "AlanineCount"),
			"10" => array( 'id' => "ARG", 'type' => "ArginineCount"),
			"11" => array( 'id' => "ASN", 'type' => "AsparagineCount"),
			"12" => array( 'id' => "ASP", 'type' => "AspartateCount"),
			"13" => array( 'id' => "CYS", 'type' => "CysteinCount"),
			"14" => array( 'id' => "GLN", 'type' => "GlutamineCount"),
			"15" => array( 'id' => "GLU", 'type' => "GlutamateCount"),
			"16" => array( 'id' => "GLY", 'type' => "GlycineCount"),
			"17" => array( 'id' => "HIS", 'type' => "HistineCount"),
			"18" => array( 'id' => "ILE", 'type' => "IsoleucineCount"),
			"19" => array( 'id' => "LEU", 'type' => "LeucineCount"),
			"20" => array( 'id' => "LYS", 'type' => "LysineCount"),
			"21" => array( 'id' => "MET", 'type' => "MethionineCount"),
			"22" => array( 'id' => "PHE", 'type' => "PhenylalanineCount"),
			"23" => array( 'id' => "PRO", 'type' => "ProlineCount"),
			"24" => array( 'id' => "SER", 'type' => "SerineCount"),
			"25" => array( 'id' => "THR", 'type' => "ThreonineCount"),
			"26" => array( 'id' => "TRP", 'type' => "TryptophanCount"),
			"27" => array( 'id' => "TYR", 'type' => "TyrosineCount"),
			"28" => array( 'id' => "VAL", 'type' => "ValineCount"),
			
			"29" => array( 'id' => "FOP", 'type' => "FrequencyOfOptimalCodons"),
			"30" => array( 'id' => "GRAVY", 'type' => "GRAVYScore"),
			"31" => array( 'id' => "AROMATICITY", 'type' => "AromaticityScore")
		);
		
		while($l = $this->GetReadFile()->Read(2048)) {
			$a = explode("\t",$l);
			$id = $a[1];
			
			foreach($properties AS $i => $p) {
				$pid =  "$id"."_".$p["id"];
				$type = $p["type"];
				
				$this->AddRDF(
					parent::triplify($this->getNamespace().$id, $this->getVoc()."is-described-by", $this->getRes().$pid).
					parent::triplifyString($this->getRes().$pid, $this->getVoc()."has-value", $a[$i]).
					parent::describeIndividual($this->getRes().$pid, "$type for sgd:$id", $this->getVoc().$type).
					parent::describeProperty($this->getVoc()."is-described-by", "Relationship  between an SGD entity and a property it is described by").
					parent::describeProperty($this->getVoc()."has-value", "Relationship between an SGD entity and its value").
					parent::describeClass($this->getVoc().$type, "$type")
				);
			}
		}
		return TRUE;
	}//protein

	function goa(){
		$goterms = array(
			//Function, hasFunction
			"F" => array("type" => "SIO_000017", "p" => "SIO_000225", "plabel" => "has function", "sgd_vocabulary" => "function"),
			//Location, isLocatedIn
			"C" => array("type" => "SIO_000003", "p" => "SIO_000061", "plabel" => "is located in" , "sgd_vocabulary" => "component"),
			//Process, isParticipantIn
			"P" => array("type" => "SIO_000006", "p" => "SIO_000062", "plabel" => "is participant in", "sgd_vocabulary" => "process")
		);
		
		$z = 1;
		while($l = $this->GetReadFile()->Read(2048)) {
			if($l[0] == '!') continue;
			$a = explode("\t",trim($l));

			$id = $a[1]."gp";
			$term = substr($a[4],3);
			
			$subject   = "sgd_resource:$id";
			$predicate = "sgd_vocabulary:".$goterms[$a[8]]['sgd_vocabulary'];
			$object    = "go:".$term;
			//$this->AddRDF($this->QQuad($subject,$predicate,$object));
			$this->AddRDF(
				parent::triplify($subject, $predicate, $object).
				parent::describeProperty($predicate, "$predicate")
			);

			// now for the GO annotation
			$goa = "sgd_resource:goa_".($z);
			$this->AddRDF(
				parent::describeIndividual($goa, "SGD GO annotation $z", "goa_vocabulary:GO-Annotation").
				parent::describeClass("goa_vocabulary:GO-Annotation", "GOA GO annotation").
				parent::triplify($goa, "goa_vocabulary:target", $subject).
				parent::triplify($goa, "goa_vocabulary:go-term", $object).
				parent::triplifyString($goa, "goa_vocabulary:go-category", $goterms[$a[8]]['sgd_vocabulary']).
				parent::describeProperty("goa_vocabulary:target", "Relationship between a GO annotation and its target").
				parent::describeProperty("goa_vocabulary:go-term", "Relationship between a GO annotation and its GO term").
				parent::describeProperty("goa_vocabulary:go-category", "Relationship between a GO annotation and its GO category")
			);
			if(isset($a[5])) {
				$b = explode("|",$a[5]);
				foreach($b as $c) {
					$d = explode(":",$c);
					if($d[0] == "pmid") {
						$this->AddRDF(
							parent::triplify($goa, "goa_vocabulary:article", "pubmed:".$d[1]).
							parent::describeProperty("goa_vocabulary:article", "Relationship between a GO annotation and its source article")
						);
					}
				}
			}
			if(isset($a[6])) {
				$code = $this->MapECO($a[6]);
				if($code){
					$this->AddRDF(
						parent::triplify($goa, "goa_vocabulary:evidence", "eco:$code").
						parent::describeProperty("goa_vocabulary:evidence", "Relationship between a GO annotation and its evidence code")
					);
				}//if
			}//if
			$z++;
		}//while
		return TRUE;
	}//goa

	function goslim(){

		$goterms = array(
			//Function, hasFunction
			"F" => array("type" => "SIO_000017", "p" => "SIO_000225", "plabel" => "has function", "sgd_vocabulary" => "has-function"),
			//Location, isLocatedIn
			"C" => array("type" => "SIO_000003", "p" => "SIO_000061", "plabel" => "is located in" , "sgd_vocabulary" => "is-located-in"),
			//Process, isParticipantIn
			"P" => array("type" => "SIO_000006", "p" => "SIO_000062", "plabel" => "is participant in", "sgd_vocabulary" => "is-participant-in")
		);
		
		while($l = $this->GetReadFile()->Read(2048)) {
			$a = explode("\t",$l);
			
			if(!isset($a[5]) || $a[5] == '') continue;
			
			$id = $a[2]."gp";
			$term = substr($a[5],3);
			
			$subject   = $this->getRes().$id;
			$predicate = $this->getVoc().$goterms[$a[3]]['sgd_vocabulary'];
			$object    = "go:".$term;

			$this->AddRDF(
				parent::triplify($subject, $predicate, $object).
				parent::describeProperty($predicate, "$predicate")
			);			
		}		
		return TRUE;
	}//goslim

	function complex(){
		while($l = $this->GetReadFile()->Read(96000)){
			$a = explode("\t",trim($l));
			
			$b = explode("/",$a[0]);
			$id = "sgd:".$b[count($b)-1];

			$this->AddRDF(
				parent::describeIndividual($id, $b[0], $this->getVoc()."Complex").
				parent::describeClass($this->getVoc()."Complex", "SGD complex")
			);
			
			$b = explode("/|",$a[1]);
			foreach($b AS $c) {
				$d = explode("/",$c);
				$this->AddRDF(
					parent::triplify($id, $this->getVoc()."has-proper-part", $this->getRes().$d[3]."gp")
				);
			}
		}//while
		return TRUE;
	}//complex

	function interaction(){

		$apofile = $this->GetParameterValue('ncbo_download_dir')."apo.obo";
		if(!file_exists($apofile)) {
			$this->GetLatestNCBOOntology('1222',$this->GetParameterValue('ncbo_api_key'),$apofile);
		}
		
		$apoin = fopen($apofile, "r");
		if($apoin === FALSE) {
			trigger_error("Unable to open $apofile", E_USER_ERROR);
			exit;
		}
		$terms = OBOParser($apoin);
		fclose($apoin);
		BuildNamespaceSearchList($terms,$searchlist);


		while($l = $this->GetReadFile()->Read(2048)) {
			list($id1,$id1name, $id2, $id2name, $method, $interaction_type, $src, $htpORman, $notes, $phenotype, $ref, $cit) = explode("\t",trim($l));
			
			$id = md5($id1.$id2.$method.$cit);

			$exp_type = array_search($interaction_type, $searchlist['experiment_type']);

			$this->GetMethodID($method,$oid,$type);
			$id1 = str_replace(array("(",")"), array("",""), $id1);
			$id2 = str_replace(array("(",")"), array("",""), $id2);
			if($type == "protein") {
				$id1 = ucfirst(strtolower($id1))."p";
				$id2=ucfirst(strtolower($id2))."p";
			}
			
			$this->AddRDF(
				parent::describeIndividual($this->getRes().$id, "$htpORman ".substr($interaction_type,0,-1)." between $id1 and $id2", strtolower($exp_type)).
				parent::triplify($this->getRes().$id, $this->getVoc()."bait", $this->getNamespace().$id1).
				parent::triplify($this->getRes().$id, $this->getVoc()."hit", $this->getNamespace().$id2).
				parent::triplify($this->getRes().$id, $this->getVoc()."method", strtolower($oid)).
				parent::describeProperty($this->getVoc()."bait", "Relationship between an SGD interaction and its bait").
				parent::describeProperty($this->getVoc()."hit", "Relationship between an SGD interaction and its hit").
				parent::describeProperty($this->getVoc()."method", "Relationship between an SGD interaction and the method by which it was obtained")
			);
			
			if($phenotype) {
				$p = explode(":",$phenotype);
				if(count($p) == 1) {
					// straight match to observable
					$observable = array_search($p[0], $searchlist['observable']);
				} else if(count($p) == 2) {
					// p[0] is the observable and p[1] is the qualifier
					$observable = array_search($p[0], $searchlist['observable']);
					$qualifier = array_search($p[1], $searchlist['qualifier']);
					$this->AddRDF(
						parent::triplify($this->getRes().$id, $this->getVoc()."qualifier", strtolower($qualifier)).
						parent::describeProperty($this->getVoc()."qualifier", "Relationship between an SGD entity and its qualifier")
					);
				}
				$this->AddRDF(
					parent::triplify($this->getRes().$id, $this->getVoc()."phenotype", strtolower($observable)).
					parent::describeProperty($this->getVoc()."phenotype", "Relationship between an SGD entity and its phenotype")
				);
			}//if

			if($htpORman){
				$this->AddRDF(
					parent::triplifyString($this->getRes().$id, $this->getVoc()."throughput", ($htpORman=="manually curated"?"manually curated":"high throughput")).
					parent::describeProperty($this->getVoc()."throughput", "Relationship between an SGD entity and its throughput")
				);
			}
			$b = explode("|",$ref);
			foreach($b AS $c) {
				$d = explode(":",$c);
				if($d[0]=="PMID"){
					$this->AddRDF($this->QQuad("sgd_resource:$id","sgd_vocabulary:article","pubmed:".$d[1]));
					$this->AddRDF(
						parent::triplify($this->getRes().$id, $this->getVoc()."article", "pubmed:".$d[1]).
						parent::describeProperty($this->getVoc()."article", "Relationship between an SGD entity and a published article")
					);
				}
			}//foreach
		}//while
		return TRUE;
	}//interaction

	function phenotype(){

		/** get the ontology terms **/
		$apofile = $this->GetParameterValue('ncbo_download_dir')."apo.obo";
		if(!file_exists($apofile)) {
			GetLatestNCBOOntology('1222',$this->GetParameterValue('ncbo_api_key'),$apofile);
		}
		
		$apoin = fopen($apofile, "r");
		if($apoin === FALSE) {
			trigger_error("Unable to open $apofile", E_USER_ERROR);
			exit;
		}
		$terms = OBOParser($apoin);
		fclose($apoin);
		BuildNamespaceSearchList($terms,$searchlist);

		while($l = $this->GetReadFile()->Read(96000)) {
			if(trim($l) == '') continue;
			$a = explode("\t",$l);
			$eid =  md5($a[3].$a[5].$a[6].$a[9]);
			
			$label = "$a[0] - $a[5] experiment with $a[6] resulting in phenotype of $a[9]";

			$this->AddRDF(
				parent::triplify($this->getRes().$eid, $this->getVoc()."has-participant", $this->getNamespace().$a[3]).
				parent::describeClass($this->getVoc()."Phenotype_Experiment", "SGD phenotype experiment [".$this->getVoc()."Phenotype_Experiment]").
				parent::describeProperty($this->getVoc()."has-participant", "Relationship between an SGD entity and its participant")
			);
			
			// reference
			// PMID: 12140549|SGD_REF: S000071347
			$b = explode("|",$a[4]);
			foreach($b AS $c) {
				$d = explode(" ",$c);
				if($d[0] == "PMID:") $ns = "pubmed";
				else $ns = "sgd";
				$this->AddRDF(
					parent::triplify($this->getRes().$eid, $this->getVoc()."article", $ns.":".$d[1])
				);
			}
			
			// experiment type [5]
			$details=null;
			$p = strpos($a[5],'(');
			if($p !== FALSE) {
				$label = substr($a[5],0,$p-1);
				$details = substr($a[5],$p+1);
			} else {
				$label = $a[5];
			}

			$this->AddRDF(
				parent::describeIndividual($this->getRes().$eid, $label, $this->getVoc()."Phenotype_Experiment", null, $details, null)
			);

			$id = array_search($label, $searchlist['experiment_type']);	
			if($id !== FALSE){
				$this->AddRDF(
					parent::triplify($this->getRes().$eid, $this->getVoc()."experiment-type", strtolower($id)).
					parent::describeProperty($this->getVoc()."experiment-type", "Relationship between an SGD experiment and the experiment type")
				);
			} else {
				trigger_error("No match for experiment type $label", E_USER_WARNING);
			}

			// mutant type [6]
			$id = array_search($a[6], $searchlist['mutant_type']);
			if($id !== FALSE){
				$this->AddRDF(
					parent::triplify($this->getRes().$eid, $this->getVoc()."mutant-type", strtolower($id)).
					parent::describeProperty($this->getVoc()."mutant-type", "Relationship between an SGD experiment and the mutant type")
				);
			}			
			// phenotype  [9]
			// presented as observable: qualifier
			$b = explode(": ",$a[9]);
			$id = array_search($b[0], $searchlist['observable']);
			if($id !== FALSE){
				$this->AddRDF(
					parent::triplify($this->getRes().$eid, $this->getVoc()."observable", strtolower($id)).
					parent::describeProperty($this->getVoc()."observable", "Relationship between an SGD entity and its observable qualifier")
				);
			}
			if(!empty($b[1])){
				$id = array_search($b[1], $searchlist['qualifier']);
				if($id !== FALSE){
					$this->AddRDF(
						parent::triplify($this->getRes().$eid, $this->getVoc()."qualifier", strtolower($id))
					);
				} 
			}
					
			/*
			7) Allele (Optional)    			-Allele name and description, if applicable
			8) Strain Background (Optional) 		-Genetic background in which the phenotype was analyzed
			10) Chemical (Optional) 			-Any chemicals relevant to the phenotype
			11) Condition (Optional)        		-Condition under which the phenotype was observed
			12) Details (Optional)  			-Details about the phenotype
			13) Reporter (Optional) 			-The protein(s) or RNA(s) used in an experiment to track a process 
			*/

			if(trim($a[7]) != ''){
				$this->AddRDF(
					parent::triplifyString($this->getRes().$eid, $this->getVoc()."allele", $a[7]).
					parent::describeProperty($this->getVoc()."allele", "Relationship between an SGD experiment and an allele")
				);
			} 

			if(trim($a[8]) != ''){
				$this->AddRDF(
					parent::triplifyString($this->getRes().$eid, $this->getVoc()."background", $a[8]).
					parent::describeProperty($this->getVoc()."background", "Relationship betweeen an SGD experiment and its background")
				);
			}

			if(trim($a[10]) != ''){
				$this->AddRDF(
					parent::triplifyString($this->getRes().$eid, $this->getVoc()."chemical", $a[10]).
					parent::describeProperty($this->getVoc()."chemical", "Relationship between an SGD experiment and a chemical")
				);
			}

			if(trim($a[11]) != ''){
				$this->AddRDF(
					parent::triplifyString($this->getRes().$eid, $this->getVoc()."condition", $a[11]).
					parent::describeProperty($this->getVoc()."condition", "Relationship between an SGD experiment and a condition")
				);
			}

			if(trim($a[12]) != ''){
				$this->AddRDF(
					parent::triplifyString($this->getRes().$eid, $this->getVoc()."details", str_replace('"','\"',$a[12])).
					parent::describeProperty($this->getVoc()."details", "Relationship between an SGD experiment and its details")
				);
			} 			
		}//while
		return TRUE;
	}//phenotype

	function pathways(){
		$sp = false;
		$e = '';
		while($l = $this->GetReadFile()->Read(96000)) {
			$a = explode("\t",$l);
			
			$pid = md5($a[0]);
			if(stristr($a[0],"superpathway")) $sp = true;
			else $sp = false;

			if(!isset($e[$pid])) {
				$e[$pid] = '1';
				if(!$sp){
					$this->AddRDF(
						parent::describeIndividual($this->getRes().$pid, $a[0], $this->getVoc()."Pathway", $a[0])
					);
				} else {
					$this->AddRDF(
						parent::describeIndividual($this->getRes().$pid, $a[0], $this->getVoc()."SuperPathway", $a[0])
					);
				}
			}
			if($sp) { // add the pathway to the superpathway
				$pathway = substr($a[1],0,-(strlen($a[1])-strrpos($a[1]," ")));
				$this->AddRDF(
					parent::triplify($this->getRes().$pid, $this->getVoc()."has-proper-part", $this->getRes().md5($pathway))
				);
				continue;
			}

			$eid = '';
			if($a[3]) { // there is a protein
				$eid = ucfirst(strtolower($a[3]))."p";
				$this->AddRDF(
					parent::triplify($this->getRes().$pid, $this->getVoc()."has-participant", $this->getRes().$eid)
				);
			}				
			$cid = '';
			if($a[1]) { // enzyme complex
				$cid = md5($a[1]);
				if(!isset($e[$cid])) {
					$e[$cid] = $cid;
					$this->AddRDF(
						parent::describeIndividual($this->getRes().$cid, $a[1], $this->getVoc()."Enzyme").
						parent::describeClass($this->getVoc()."Enzyme", "SGD enzyme")
					);
				}
				$this->AddRDF(
					parent::triplify($this->getRes().$pid, $this->getVoc()."has-participant", $this->getRes().$cid)
				);
				if($eid){
					$this->AddRDF(
						parent::triplify($this->getRes().$cid, $this->getVoc()."has-proper-part", $this->getRes().$eid)
					);
				}
			}
			if($a[2]) { // EC reaction
				$this->AddRDF(
					parent::describeIndividual("ec:".$a[2], $a[2], $this->getVoc()."Reaction").
					parent::describeClass($this->getVoc()."Reaction", "Chemical reaction").
					parent::triplify("ec:".$a[2], $this->getVoc()."has-participant", $this->getRes().$eid)
				);
				if($cid){
					$this->AddRDF(
						parent::triplify("ec:".$a[2], $this->getVoc()."has-participant", $this->getRes().$cid)
					);
				}
			}
	
			if(trim($a[4]) != '') { // publications
				$b = explode("|",trim($a[4]));
				foreach($b AS $c) {
					$d = explode(":",$c);
					$ns = "sgd";
					if($d[0] == "PMID"){
						$ns = "pubmed";
					}
					$this->AddRDF(
						parent::triplify($this->getRes().$pid, $this->getVoc()."article", $ns.":".$d[1])
					); 
				}
			}//if
		}//while
		return TRUE;
	}//pathways

	function psiblast(){
		while($l = $this->GetReadFile()->Read(2048)) {
			$a = explode("\t",trim($l));
			
			$id1 = $a[0];
			$id2 = $a[7];
			$id = "aln_$id1_$id2";
			$this->AddRDF(
				parent::describeIndividual($this->getRes().$id, "psiblast alignment between $id1 and $id2", $this->getVoc()."PSI-BLAST-Alignment").
				parent::describeClass($this->getVoc()."PSI-BLAST-Alignment", "PSI-Blast Alignment").
				parent::triplify($this->getRes().$id, $this->getVoc()."query", $this->getNamespace().$id1).
				parent::triplify($this->getRes().$id, $this->getVoc()."target", $this->getNamespace().$id2).
				parent::triplifyString($this->getRes().$id, $this->getVoc()."query-start", $a[1]).
				parent::triplifyString($this->getRes().$id, $this->getVoc()."query-stop", $a[2]).
				parent::triplifyString($this->getRes().$id, $this->getVoc()."target-start", $a[3]).
				parent::triplifyString($this->getRes().$id, $this->getVoc()."target-stop", $a[4]).
				parent::triplifyString($this->getRes().$id, $this->getVoc()."percent-aligned", $a[5]).
				parent::triplifyString($this->getRes().$id, $this->getVoc()."score", $a[6]).
				parent::triplifyString($this->getRes().$id, $this->getVoc()."is-encoded-by", "taxon:".$a[8]).
				parent::describeProperty($this->getVoc()."target-start", "Relationship between an SGD sequence alignment and its target sequence start position").
				parent::describeProperty($this->getVoc()."target-stop", "Relationship between an SGD sequence alignment and its target sequence stop position").
				parent::describeProperty($this->getVoc()."score", "Relationship between an SGD sequence alignment and its score").
				parent::describeProperty($this->getVoc()."percent-aligned", "Relationship between an SGD sequence alignment and its percent-aligned value").
				parent::describeProperty($this->getVoc()."is-encoded-by", "Relationship between an SGD sequence alignment and the taxon the aligned sequences are encoded by")
			);
		}//while
		return TRUE;
	}//psiblast

	function mapping(){

		$this->AddRDF(
			parent::triplify($this->getVoc()."has-proper-part", "owl:equivalentProperty", "sio:SIO_000053").
			parent::triplify($this->getVoc()."encodes", "owl:equivalentProperty", "sio:SIO_010078").
			parent::triplify($this->getVoc()."is-about", "owl:equivalentProperty", "sio:SIO_000332").
			parent::triplify($this->getVoc()."is-proper-part-of", "owl:equivalentProperty", "sio:SIO_000093").
			parent::triplify($this->getVoc()."article", "owl:equivalentProperty", "sio:SIO_000212").
			parent::triplify($this->getVoc()."has-participant", "owl:equivalentProperty", "sio:SIO_000132").
			parent::triplify($this->getVoc()."is-described-by", "owl:equivalentProperty", "sio:SIO_000557").
			parent::triplify($this->getVoc()."Protein", "owl:equivalentClass", "chebi:36080").
			parent::triplify($this->getVoc()."RNA", "owl:equivalentClass", "chebi:33697").
			parent::triplify($this->getVoc()."Chromosome", "owl:equivalentClass", "so:0000340")
		);
	}//mapping

	function GetFeatureType($feature_id) {
		$feature_map = array (
		'ACS' => 'SO:0000436',
		'ARS consensus sequence' => 'SO:0000436',
		'binding_site' => 'SO:0000409',
		'CDEI' => 'SO:0001493',
		'CDEII' => 'SO:0001494',
		'CDEIII' => 'SO:0001495',
		'CDS' => 'SO:0000316',
		'centromere' => 'SO:0000577',
		'external_transcribed_spacer_region' => 'SO:0000640',
		'internal_transcribed_spacer_region' => 'SO:0000639',
		'intron' => 'SO:0000188',
		'long_terminal_repeat' => 'SO:0000286',
		'ncRNA' => 'SO:0000655',
		'noncoding_exon' => 'SO:0000445',
		'non_transcribed_region' => 'SO:0000183',
//	not in systematic sequence of S288C
//		'not physically mapped' => 'NotPhysicallyMappedFeature',
		'ORF' => 'SO:0000236',
		'plus_1_translational_frameshift' => 'SO:0001211',
		'pseudogene' => 'SO:0000336',
		'repeat_region' => 'SO:0000657',
		'retrotransposon' => 'SO:0000180',
		'rRNA' => 'SO:0000573',
		'snoRNA' => 'SO:0000578',
		'snRNA' => 'SO:0000623',
		'telomere' => 'SO:0000624',
		'telomeric_repeat' => 'SO:0001496',
		'transposable_element_gene' => 'SO:0000180',
		'tRNA' => 'SO:0000663',
		'X_element_combinatorial_repeats' => 'SO:0001484',
		'X_element_core_sequence' => 'SO:0001497',
		"Y_element" => 'SO:0001485'
		);
		if(isset($feature_map[$feature_id])) {
			return $feature_map[$feature_id];
		} else {
			return "SO:0000830";
		}
	}//GetFeatureType

	function MapECO($eco) {
	 	$c = array(
			"ISS" => "0000027", 
			"IGI" => "0000011",
			"IMP" => "0000015",
			"IDA" => "0000002",
			"IEA" => "00000067",
			"TAS" => "0000033",
			"RCA" => "0000053",
			"ISA" => "00000057",
			"IEP" => "0000008",
			"ND" => "0000035",
			"IC" => "0000001",
			"IPI" => "0000021",
			"NAS" =>"0000034",
			"ISM" => "00000063",
			"ISO" =>"00000060",
			"IBA" => "0000318",
			"IRD" => "0000321",
		);
	  	if(isset($c[$eco])){
	  		return $c[$eco];
	  	} else {
	  		return NULL;
	  	}
	}//MapECO

	function GetMethodID($label, &$id, &$type) {
		$gi = array(
			'Dosage Rescue' => 'APO:0000173',
			'Dosage Lethality' => 'APO:0000172',
			'Dosage Growth Defect' => 'APO:0000171',
			'Epistatic MiniArray Profile' => 'APO:0000174',
			'Synthetic Lethality' => 'APO:0000183',
			'Synthetic Growth Defect' => 'APO:000018',
			'Synthetic Rescue' => 'APO:0000184',
			'Synthetic Haploinsufficiency'=> 'APO:0000272',
			'Phenotypic Enhancement' => 'APO:0000177',
			'Phenotypic Suppression' => 'APO:0000178',
			'Negative Genetic' => 'APO:0000322',
			'Positive Genetic' => 'APO:0000323',
		);
		$pi = array(
			'Affinity Capture-Luminescence' => 'APO:0000318',
			'Affinity Capture-MS' => 'APO:0000162',
			'Affinity Capture-Western' => 'APO:0000165',
			'Affinity Capture-RNA' => 'APO:0000163',
			'Affinity Chromatography' => 'APO:0000188',
			'Affinity Precipitation' => 'APO:0000189',
			'Biochemical Activity' => 'APO:0000166',
			'Biochemical Assay' => 'APO:0000190',
			'Co-crystal Structure' => 'APO:0000167',
			'Co-fractionation' => 'APO:0000168',
			'Co-localization' => 'APO:0000169',
			'Co-purification' => 'APO:0000170',
			'Far Western' => 'APO:0000176',
			'FRET' => 'APO:0000175',
			'PCA' => 'APO:0000244',
			'Protein-peptide' => 'APO:0000180',
			'Protein-RNA' => 'APO:0000179',
			'Purified Complex' => 'APO:0000191',
			'Reconstituted Complex' => 'APO:0000181',
			'Two-hybrid' => 'APO:0000185',
		);
		if(isset($gi[$label])) {
			$id=$gi[$label];
			$type='gene';
			return;
		}
		
		if(isset($pi[$label])) {
			$id=$pi[$label];
			$type='protein';
			return;
		}

		echo "No match for $label\n";
	}//GetMethodID

	function GetLatestNCBOOntology($ontology_id,$apikey,$target_filepath){
		Utils::DownloadSingle('http://rest.bioontology.org/bioportal/virtual/download/'.$ontology_id.'?apikey='.$apikey, $target_filepath);
	}
}//SGDParser

?>
