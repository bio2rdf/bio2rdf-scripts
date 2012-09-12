<?php
/**
Copyright (C) 2012 Michel Dumontier, Alison Callahan

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
 * @version 1.0
 * @author Michel Dumontier
 * @author Alison Callahan
*/

require('../../php-lib/rdfapi.php');
require('../common/php/oboparser.php');

class SGDParser extends RDFFactory {
	private $version = null;

	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("sgd");
		
		// set and print application parameters
		$this->AddParameter('files',true,'all|dbxref|features|domains|protein|goa|goslim|complex|interaction|phenotype|pathways|mapping','all','all or comma-separated list of files to process');
		$this->AddParameter('indir',false,null,'/data/download/sgd/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/sgd/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://downloads.yeastgenome.org/');
		$this->AddParameter('ncbo_api_key', false, null, '24e19c82-54e0-11e0-9d7b-005056aa3316');
		$this->AddParameter('ncbo_download_dir', false, null, '/data/download/ncbo', 'directory of ncbo ontologies');
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
				$lfile = $ldir.$file.".tab";
			} elseif($ext = "gz"){
				$lfile = $ldir.$file.".tab.gz";
			}

			if(!file_exists($lfile) && $this->GetParameterValue('download') == false) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
			}
			
			//download all files [except mapping file]
			if($this->GetParameterValue('download') == true && $file !== "mapping") {
				$rfile = $rdir.$rfiles[$file];
				echo "downloading $lfile ... ";
				file_put_contents($lfile,file_get_contents($rfile));
			}

			$ofile = $odir.$file.'.ttl'; 
			$gz=false;
			
			if($this->GetParameterValue('gzip')) {
				$ofile .= '.gz';
				$gz = true;
			}
			
			$this->SetWriteFile($ofile, $gz);

			//parse file
			if($ext !== "gz"){
				$this->SetReadFile($lfile, FALSE);
			} else {
				$this->SetReadFile($lfile, TRUE);
			}
			
			$fnx = $file;	
			echo "processing $file... ";
			$this->$fnx();
			echo "done!";

			//write RDF to file
			$this->WriteRDFBufferToWriteFile();

			//close write file
			$this->GetWriteFile()->Close();
			echo PHP_EOL;
			
		}//foreach

		// generate the dataset release file
		echo "generating dataset release file... ";
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/sgd/sgd.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()),
			"http://yeastgenome.org",
			array("use"),
			"http://yeastgenome.org",
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		echo "done!".PHP_EOL;
 		
	}

	function dbxref(){
		while($l = $this->GetReadFile()->Read(2048)) {

			list($id, $ns, $type, $name, $sgdid) = explode("\t",trim($l));;
					
			$sameas = 'owl:sameAs';
			$seealso = 'rdfs:seeAlso';
			$suf = "";
			$rel = "";

			switch($ns) {
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
						$ns='swissprot';
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
					$ns = 'ncbi';
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
						$ns='ncbi'; 
						$rel=$sameas;  
						break;
					}

					if($type == "Gene ID") {
						$ns='geneid';
						$rel=$sameas;
						break;
					}

					if($type == "NCBI protein GI") {
						$ns='ncbi';
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
					//if the entity is not an sgd entity but a bio2rdf sgd entity, use the sgd_resource namespace
					$this->AddRDF($this->QQuad("sgd_resource:$sgdid$suf", $rel, "$ns:$id"));
					$this->AddRDF($this->QQuad("sgd_resource:$sgdid$suf", "void:inDataset", $this->GetDatasetURI()));
				} else {
					//otherwise use the sgd namespace
					$this->AddRDF($this->QQuad("sgd:$sgdid$suf", $rel, "$ns:$id"));
					$this->AddRDF($this->QQuad("sgd_resource:$sgdid$suf", "void:inDataset", $this->GetDatasetURI()));
				}//else
			}//if
				
		}//while

		return TRUE;
	}//dbxref

	function features(){

		while($l = $this->GetReadFile()->Read(2048)) {
			if($l[0] == '!') continue;
			$a = explode("\t",$l);
			
			$other = '';
			$id = $oid = $a[0];
			$id =urlencode($id);	

			$this->AddRDF($this->QQuadL("sgd_resource:record_$id",'dc:identifier',"sgd:record_$id"));
			$this->AddRDF($this->QQuad("sgd_resource:record_$id", "void:inDataset", $this->GetDatasetURI()));
			$this->AddRDF($this->QQuadL("sgd_resource:record_$id","dc:title","Record for entity identified by sgd:$id"));
			$this->AddRDF($this->QQuadL("sgd_resource:record_$id","rdfs:label","Record for entity identified by sgd:$id [sgd_resource:record_$id]"));
			$this->AddRDF($this->QQuad("sgd_resource:record_$id","rdf:type","sgd_vocabulary:Record")); //was sio:Record
			$this->AddRDF($this->QQuad("sgd_resource:record_$id","sgd_vocabulary:is-about", "sgd:$id")); //was sio:is-about
						
			$this->AddRDF($this->QQuadL("sgd:$id","dc:identifier","sgd:$oid"));
			$this->AddRDF($this->QQuad("sgd:$id", "void:inDataset", $this->GetDatasetURI()));
			$this->AddRDF($this->QQuadL("sgd:$id","rdfs:label","$a[1] [sgd:$id]"));
			if($a[15]){
				$this->AddRDF($this->QQuadL("sgd:$id","dc:description",'"'.trim($a[15]).'"'));
			}
			
			$feature_type = $this->GetFeatureType($a[1]);
			$this->AddRDF($this->QQuad("sgd:$id","rdf:type",strtolower($feature_type)));

			unset($type);

			if($a[1] == "ORF") {
				$type = "p";
			} elseif(stristr($a[1],"rna")){
				$type = "r";
			}

			if(isset($type)) {
				unset($p1);
				unset($p2);
				$gp = 'sgd_resource:'.$id."gp";
				$this->AddRDF($this->QQuad("sgd:$id","sgd_vocabulary:encodes",$gp));
				$this->AddRDF($this->QQuadL($gp,'rdfs:label',"$id"."gp [$gp]"));
				$this->AddRDF($this->QQuad($gp, "void:inDataset", $this->GetDatasetURI()));
				
				if($type == "p") {
					$this->AddRDF($this->QQuad($gp,'rdf:type','sgd_vocabulary:Protein'));
				} elseif($type == "r") {
					$this->AddRDF($this->QQuad($gp,'rdf:type','sgd_vocabulary:RNA')); 
				}

				if($a[1] == "ORF" && $a[3] != '') {
					$p1 = ucfirst(strtolower(str_replace(array("(",")"), array("%28","%29"), $a[3])))."p";
					$this->AddRDF($this->QQuad("sgd:$id","sgd_vocabulary:encodes","sgd:$p1"));
					$this->AddRDF($this->QQuad("sgd:$p1","owl:sameAs","$gp"));
					$this->AddRDF($this->QQuadL("sgd:$p1","rdfs:label","$p1 [sgd:$p1]"));
					$this->AddRDF($this->QQuad("sgd:$p1","rdf:type","sgd_vocabulary:Protein"));
				}
				if($a[1] == "ORF" && $a[4] != '') {
					$p2 = ucfirst(strtolower(str_replace(array("(",")"), array("%28","%29"), $a[4])))."p";
					$this->AddRDF($this->QQuad("sgd:$id","sgd_vocabulary:encodes","sgd:$p2"));
					$this->AddRDF($this->QQuad("sgd:$p2","owl:sameAs","$gp"));
					$this->AddRDF($this->QQuadL("sgd:$p2","rdfs:label","$p2 [sgd:$p2]"));
					$this->AddRDF($this->QQuad("sgd:$p2","rdf:type","sgd_vocabulary:Protein"));
				}
				if(isset($p1) && isset($p2)){
					$this->AddRDF($this->QQuad("sgd:$p1","owl:sameAs","sgd:$p2"));
				} 
					
			}

			// feature qualifiers (uncharacterized, verified, silenced_gene, dubious)
			if($a[2]) {
				$qualifiers = explode("|",$a[2]);
				foreach($qualifiers AS $q) {
					$this->AddRDF($this->QQuadL("sgd:$id","sgd_vocabulary:status","$q"));
				}
			}
			
			// unique feature name
			if($a[3]) {
				$this->AddRDF($this->QQuadL("sgd:$id","sgd_vocabulary:prefLabel",$a[3]));
				$nid = str_replace(array("(",")"), array("%28","%29"),$a[3]);
				$this->AddRDF($this->QQuad("sgd:$id","owl:sameAs","sgd:$nid"));
			}
			
			// common names
			if($a[4]) {
				$this->AddRDF($this->QQuadL("sgd:$id","sgd_vocabulary:standardName",$a[4]));
				$nid = str_replace(array("(",")"), array("%28","%29"), $a[4]);
				$this->AddRDF($this->QQuad("sgd:$id","owl:sameAs","sgd:$nid"));
			}
			if($a[5]) {
				$b = explode("|",$a[5]);
				foreach($b AS $name) {
					$this->AddRDF($this->QQuadL("sgd:$id","sgd_vocabulary:alias",str_replace('"','',$name)));
				}
			}
			// parent feature
			$parent_type = '';
			if($a[6]) {
				$parent = str_replace(array("(",")"," "), array("%28","%29","_"), $a[6]);
				//$parent = urlencode($a[6]);

				$this->AddRDF($this->QQuad("sgd:$id","sgd_vocabulary:is-proper-part-of","sgd_resource:$parent"));
				if(strstr($parent,"chromosome")) {
					$parent_type = 'c';
					if(!isset($chromosomes[$parent])) $chromosomes[$parent] = '';
					else {
						$this->AddRDF($this->QQuad("sgd_resource:$parent","rdf:type","sgd_vocabulary:Chromosome"));
						$this->AddRDF($this->QQuadL("sgd_resource:$parent","rdfs:label",$a[6]));
					}
				}
			}
			// secondary sgd id (starts with an L)
			if($a[7]) {
				if($a[3]) {
					$b = explode("|",$a[7]);
					foreach($b AS $c) {
						$this->AddRDF($this->QQuad("sgd:$id","owl:sameAs","sgd:$c"));
					}
				}
			}
			// chromosome
			unset($chr);
			if($a[8] && $parent_type != 'c') {
				$chr = "chromosome_".$a[8];
				$this->AddRDF($this->QQuad("sgd:$id","sgd_vocabulary:is-proper-part-of","sgd_resource:$chr"));
			}
			// watson or crick strand of the chromosome
			unset($strand);
			if($a[11]) {
				$chr = "chromosome_".$a[8];
				$strand_type = ($a[11]=="w"?"WatsonStrand":"CrickStrand");
				$strand = $chr."_".$strand_type;
				$this->AddRDF($this->QQuad("sgd:$id","sgd_vocabulary:is-proper-part-of","sgd_resource:$strand"));
				if(!isset($strands[$strand])) {
					$strands[$strand] = '';
					$this->AddRDF($this->QQuad("sgd_resource:$strand","rdf:type","sgd_vocabulary:$strand_type"));
					$this->AddRDF($this->QQuadL("sgd_resource:$strand","rdfs:label","$strand_type for $chr"));
					$this->AddRDF($this->QQuad("sgd_resource:$strand","sgd_vocabulary:is-proper-part-of","sgd_resource:$chr"));
				}
			}
			
			// position
			if($a[9]) {
				$loc = $id."loc";
				$this->AddRDF($this->QQuad("sgd:$id","sgd_vocabulary:location","sgd_resource:$loc"));
				$this->AddRDF($this->QQuadL("sgd_resource:$loc","dc:identifier","sgd_resource:$loc"));
				$this->AddRDF($this->QQuadL("sgd_resource:$loc","rdfs:label","Genomic location of sgd:$id"));
				$this->AddRDF($this->QQuad("sgd_resource:$loc","rdf:type","sgd_vocabulary:Location"));
				$this->AddRDF($this->QQuadL("sgd_resource:$loc","sgd_vocabulary:has-start-position",$a[9]));
				$this->AddRDF($this->QQuadL("sgd_resource:$loc","sgd_vocabulary:has-stop-position",$a[10]));
				if(isset($chr)){
					$this->AddRDF($this->QQuad("sgd_resource:$loc","sgd_vocabulary:chromosome","sgd_resource:$chr"));
				}
				if(isset($strand)){
					$this->AddRDF($this->QQuad("sgd_resource:$loc","sgd_vocabulary:strand","sgd_resource:$strand"));
				}
				/*
				if($a[13]) {
					$b = explode("|",$a[13]);
					foreach($b AS $c) {
						$buf .= QQuadL("sgd_resource:$loc","sgd_vocabulary:modified",$c);
					}
				}
				*/
			}
			/*
			if($a[14]) {
				$b = explode("|",$a[14]);
				foreach($b AS $c) {
					$buf .= QQuadL("sgd_resource:record_$id","sgd_vocabulary:modified",$c);
				}
			}
			*/
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
			$this->AddRDF($this->QQuad($id,'sgd_vocabulary:has-proper-part',$domain));

			$da = "sgd_resource:da_".$a[0]."p_$a[4]_$a[6]_$a[7]";
			$this->AddRDF($this->QQuadL($da,'rdfs:label',"domain alignment between $id and $domain [$da]"));
			$this->AddRDF($this->QQuad($da,'rdf:type','sgd_vocabulary:DomainAlignment'));
			$this->AddRDF($this->QQuad($da, "void:inDataset", $this->GetDatasetURI()));
			$this->AddRDF($this->QQuad($da,'sgd_vocabulary:query',$id));
			$this->AddRDF($this->QQuad($da,'sgd_vocabulary:target',$domain));
			$this->AddRDF($this->QQuadL($da,'sgd_vocabulary:query-start', $a[6]));
			$this->AddRDF($this->QQuadL($da,'sgd_vocabulary:query-stop', $a[7]));
			$this->AddRDF($this->QQuadL($da,'sgd_vocabulary:e-value', $a[8]));	
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
				
				$this->AddRDF($this->QQuad("sgd:$id","sgd_vocabulary:is-described-by","sgd_resource:$pid"));
				$this->AddRDF($this->QQuad("sgd_resource:$pid","rdf:type","sgd_vocabulary:$type"));
				$this->AddRDF($this->QQuad("sgd_resource:$pid", "void:inDataset", $this->GetDatasetURI()));
				$this->AddRDF($this->QQuadL("sgd_resource:$pid","rdfs:label","$type for sgd:$id [sgd_resource:$pid]"));
				$this->AddRDF($this->QQuadL("sgd_resource:$pid","sgd_vocabulary:has-value",$a[$i]));
			}
		}
		return TRUE;
	}//protein

	function goa(){
		$goterms = array(
			//Function, hasFunction
			"F" => array("type" => "SIO_000017", "p" => "SIO_000225", "plabel" => "has function", "sgd_vocabulary" => "has-function"),
			//Location, isLocatedIn
			"C" => array("type" => "SIO_000003", "p" => "SIO_000061", "plabel" => "is located in" , "sgd_vocabulary" => "is-located-in"),
			//Process, isParticipantIn
			"P" => array("type" => "SIO_000006", "p" => "SIO_000062", "plabel" => "is participant in", "sgd_vocabulary" => "is-participant-in")
		);
		
		while($l = $this->GetReadFile()->Read(2048)) {
			if($l[0] == '!') continue;
			$a = explode("\t",trim($l));

			$id = $a[1]."gp";
			$term = substr($a[4],3);
			
			$subject   = "sgd_resource:$id";
			$predicate = "sgd_vocabulary:".$goterms[$a[8]]['sgd_vocabulary'];
			$object    = "go:".$term;
			$this->AddRDF($this->QQuad($subject,$predicate,$object));

			// now for the GO annotation
			$goa = "sgd_resource:goa_".$id."_".$term;
			$this->AddRDF($this->QQuad($goa,"rdf:type","sgd_vocabulary:GO-Annotation"));
			$this->AddRDF($this->QQuad($goa, "void:inDataset", $this->GetDatasetURI()));
			$this->AddRDF($this->QQuad($goa,"rdf:subject",$subject));
			$this->AddRDF($this->QQuad($goa,"rdf:predicate",$predicate));
			$this->AddRDF($this->QQuad($goa,"rdf:object",$object));
			if(isset($a[5])) {
				$b = explode("|",$a[5]);
				foreach($b as $c) {
					$d = explode(":",$c);
					if($d[0] == "pmid") {
						$this->AddRDF($this->QQuad($goa,"sgd_vocabulary:article","pubmed:".$d[1]));
					}
				}
			}
			if(isset($a[6])) {
				$code = $this->MapECO($a[6]);
				if($code){
						$this->AddRDF($this->QQuad($goa,"sgd_vocabulary:evidence","eco:$code"));
				}//if
			}//if
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
			
			$subject   = "sgd_resource:$id";
			$predicate = "sgd_vocabulary:".$goterms[$a[3]]['sgd_vocabulary'];
			$object    = "go:".$term;
			$this->AddRDF($this->QQuad($subject,$predicate,$object));
			
		}		
		return TRUE;
	}//goslim

	function complex(){
		while($l = $this->GetReadFile()->Read(96000)){
			$a = explode("\t",trim($l));
			
			$b = explode("/",$a[0]);
			$id = "sgd:".$b[count($b)-1];
			$this->AddRDF($this->QQuadL($id,'rdfs:label', "$b[0] [$id]"));
			$this->AddRDF($this->QQuad($id, "void:inDataset", $this->GetDatasetURI()));
			$this->AddRDF($this->QQuad($id, 'rdf:type',"sgd_vocabulary:Complex"));
			
			$b = explode("/|",$a[1]);
			foreach($b AS $c) {
				$d = explode("/",$c);
				$this->AddRDF($this->QQuad($id,'sgd_vocabulary:has-proper-part',"sgd_resource:$d[3]gp"));
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
			trigger_error("Unable to open $apofile");
			exit;
		}
		$terms = OBOParser($apoin);
		fclose($apoin);
		BuildNamespaceSearchList($terms,$searchlist);


		while($l = $this->GetReadFile()->Read(2048)) {
			list($id1,$id1name, $id2, $id2name, $method, $interaction_type, $src, $htpORman, $notes, $phenotype, $ref, $cit) = explode("\t",trim($l));
			
			$id = md5($id1.$id2.$method.$cit);

			$exp_type = array_search($interaction_type, $searchlist['experiment_type']);
			$this->AddRDF($this->QQuad("sgd_resource:$id","rdf:type",strtolower($exp_type)));
			$this->AddRDF($this->QQuad("sgd_resource:$id", "void:inDataset", $this->GetDatasetURI()));
			$this->GetMethodID($method,$oid,$type);
			$id1 = str_replace(array("(",")"), array("",""), $id1);
			$id2 = str_replace(array("(",")"), array("",""), $id2);
			if($type == "protein") {
				$id1 = ucfirst(strtolower($id1))."p";
				$id2=ucfirst(strtolower($id2))."p";
			}
			
			$this->AddRDF($this->QQuadL("sgd_resource:$id","rdfs:label","$htpORman ".substr($interaction_type,0,-1)." between $id1 and $id2 [sgd_resource:$id]"));
			
			$this->AddRDF($this->QQuad("sgd_resource:$id","sgd_vocabulary:bait","sgd:$id1"));
			$this->AddRDF($this->QQuad("sgd_resource:$id","sgd_vocabulary:hit","sgd:$id2"));
			$this->AddRDF($this->QQuad("sgd_resource:$id","sgd_vocabulary:method", strtolower($oid)));
			
			if($phenotype) {
				$this->AddRDF($this->QQuad("sgd_resource:$id","rdf:type",strtolower($exp_type)));
				$p = explode(":",$phenotype);
				if(count($p) == 1) {
					// straight match to observable
					$observable = array_search($p[0], $searchlist['observable']);
				} else if(count($p) == 2) {
					// p[0] is the observable and p[1] is the qualifier
					$observable = array_search($p[0], $searchlist['observable']);
					$qualifier = array_search($p[1], $searchlist['qualifier']);
					$this->AddRDF($this->QQuad("sgd_resource:$id","sgd_vocabulary:qualifier",strtolower($qualifier)));
				}
				$this->AddRDF($this->QQuad("sgd_resource:$id","sgd_vocabulary:phenotype",strtolower($observable)));
			}//if

			if($htpORman){
				$this->AddRDF($this->QQuadL("sgd_resource:$id","sgd_vocabulary:throughput",($htpORman=="manually curated"?"manually curated":"high throughput")));
			}
			$b = explode("|",$ref);
			foreach($b AS $c) {
				$d = explode(":",$c);
				if($d[0]=="PMID"){
					$this->AddRDF($this->QQuad("sgd_resource:$id","sgd_vocabulary:article","pubmed:".$d[1]));
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
			trigger_error("Unable to open $apofile");
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
			$this->AddRDF($this->QQuadL("sgd_resource:$eid","rdfs:label","$label [sgd_resource:$eid]"));
			$this->AddRDF($this->QQuad("sgd_resource:$eid", "void:inDataset", $this->GetDatasetURI()));
			$this->AddRDF($this->QQuad("sgd_resource:$eid","rdf:type","sgd_vocabulary:Phenotype_Experiment"));
			$this->AddRDF($this->QQuad("sgd_resource:$eid","sgd_vocabulary:has-participant","sgd:$a[3]"));
			
			// reference
			// PMID: 12140549|SGD_REF: S000071347
			$b = explode("|",$a[4]);
			foreach($b AS $c) {
				$d = explode(" ",$c);
				if($d[0] == "PMID:") $ns = "pubmed";
				else $ns = "sgd";
				$this->AddRDF($this->QQuad("sgd_resource:$eid","sgd_vocabulary:article","$ns:".$d[1]));
			}
			
			// experiment type [5]
			$p = strpos($a[5],'(');
			if($p !== FALSE) {
				$label = substr($a[5],0,$p-1);
				$details = substr($a[5],$p+1);
				$this->AddRDF($this->QQuadL("sgd_resource:$eid","dc:description","$details"));
			} else {
				$label = $a[5];
			}
			$id = array_search($label, $searchlist['experiment_type']);	
			if($id !== FALSE){
				$this->AddRDF($this->QQuad("sgd_resource:$eid","sgd_vocabulary:experiment_type",strtolower($id)));
			} else {
				trigger_error("No match for experiment type $label");
			}

			// mutant type [6]
			$id = array_search($a[6], $searchlist['mutant_type']);
			if($id !== FALSE){
				$this->AddRDF($this->QQuad("sgd_resource:$eid","sgd_vocabulary:mutant_type",strtolower($id)));
			}			
			// phenotype  [9]
			// presented as observable: qualifier
			$b = explode(": ",$a[9]);
			$id = array_search($b[0], $searchlist['observable']);
			if($id !== FALSE){
				$this->AddRDF($this->QQuad("sgd_resource:$eid","sgd_vocabulary:observable",strtolower($id)));
			}
			if(!empty($b[1])){
				$id = array_search($b[1], $searchlist['qualifier']);
				if($id !== FALSE){
					$this->AddRDF($this->QQuad("sgd_resource:$eid","sgd_vocabulary:qualifier",strtolower($id)));
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
				$this->AddRDF($this->QQuadL("sgd_resource:$eid","sgd_vocabulary:allele",$a[7]));
			} 

			if(trim($a[8]) != ''){
				$this->AddRDF($this->QQuadL("sgd_resource:$eid","sgd_vocabulary:background",$a[8]));
			}

			if(trim($a[10]) != ''){
				$this->AddRDF($this->QQuadL("sgd_resource:$eid","sgd_vocabulary:chemical",$a[10]));
			}

			if(trim($a[11]) != ''){
				$this->AddRDF($this->QQuadL("sgd_resource:$eid","sgd_vocabulary:condition",$a[11]));
			}

			if(trim($a[12]) != ''){
				$this->AddRDF($this->QQuadL("sgd_resource:$eid","sgd_vocabulary:details",str_replace('"','\"',$a[12])));
			} 
			//if($a[13] != '') $buf .= "sgd:$eid sgd_vocabulary:reporter \"$a[13]\".".PHP_EOL;
			
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
				$this->AddRDF($this->QQuadL("sgd_resource:$pid","rdfs:label","$a[0] [sgd_resource:$pid]"));
				$this->AddRDF($this->QQuad("sgd_resource:$pid", "void:inDataset", $this->GetDatasetURI()));
				$this->AddRDF($this->QQuadL("sgd_resource:$pid","dc:title",$a[0]));

				if(!$sp){
					$this->AddRDF($this->QQuad("sgd_resource:$pid","rdf:type","sgd_vocabulary:Pathway"));
				} else {
					$this->AddRDF($this->QQuad("sgd_resource:$pid","rdf:type","sgd_vocabulary:Superpathway"));
				}
			}
			if($sp) { // add the pathway to the superpathway
				$pathway = substr($a[1],0,-(strlen($a[1])-strrpos($a[1]," ")));
				$this->AddRDF($this->QQuad("sgd_resource:$pid","sgd_vocabulary:has-proper-part","sgd_resource:".md5($pathway)));
				continue;
			}

			$eid = '';
			if($a[3]) { // there is a protein
				$eid = ucfirst(strtolower($a[3]))."p";
				$this->AddRDF($this->QQuad("sgd_resource:$pid","sgd_vocabulary:has-participant", "sgd_resource:$eid"));
			}				
			$cid = '';
			if($a[1]) { // enzyme complex
				$cid = md5($a[1]);
				if(!isset($e[$cid])) {
					$e[$cid] = $cid;
					$this->AddRDF($this->QQuadL("sgd_resource:$cid","rdfs:label","$a[1] [sgd_resource:$cid]"));
					$this->AddRDF($this->QQuad("sgd_resource:$cid","rdf:type","sgd_vocabulary:Enzyme"));
					$this->AddRDF($this->QQuad("sgd_resource:$cid", "void:inDataset", $this->GetDatasetURI()));
				}
				$this->AddRDF($this->QQuad("sgd_resource:$pid","sgd_vocabulary:has-participant","sgd_resource:$cid"));
				if($eid){
					$this->AddRDF($this->QQuad("sgd_resource:$cid","sgd_vocabulary:has-proper-part","sgd_resource:$eid"));
				}
			}
			if($a[2]) { // EC reaction
				$this->AddRDF($this->QQuad("sgd_resource:$pid","sgd_vocabulary:has-proper-part","ec:$a[2]"));
				$this->AddRDF($this->QQuadL("ec:$a[2]","rdfs:label","$a[2] [ec:$a[2]]"));
				$this->AddRDF($this->QQuad("ec:$a[2]","rdf:type","sgd_vocabulary:Reaction"));
				$this->AddRDF($this->QQuad("ec:$a[2]","sgd_vocabulary:has-participant","sgd_resource:$eid"));
				if($cid){
					$this->AddRDF($this->QQuad("ec:$a[2]","sgd_vocabulary:has-participant","sgd_resource:$cid"));
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
					$this->AddRDF($this->QQuad("sgd_resource:$pid","sgd_vocabulary:article","$ns:$d[1]"));
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
		
			$this->AddRDF($this->QQuadL("sgd_resource:$id","rdfs:label","psiblast alignment between $id1 and $id2 [sgd_resource:$id]"));
			$this->AddRDF($this->QQuad("sgd_resource:$id", "void:inDataset", $this->GetDatasetURI()));
			$this->AddRDF($this->QQuad("sgd_resource:$id","rdf:type","sgd_vocabulary:PSIBLASTAlignment"));
			$this->AddRDF($this->QQuad("sgd_resource:$id","sgd_vocabulary:query","sgd:$id1"));
			$this->AddRDF($this->QQuad("sgd_resource:$id","sgd_vocabulary:target","sgd:$id2"));
			$this->AddRDF($this->QQuadL("sgd_resource:$id","sgd_vocabulary:query_start",$a[1]));
			$this->AddRDF($this->QQuadL("sgd_resource:$id","sgd_vocabulary:query_stop",$a[2]));
			$this->AddRDF($this->QQuadL("sgd_resource:$id","sgd_vocabulary:target_start",$a[3]));
			$this->AddRDF($this->QQuadL("sgd_resource:$id","sgd_vocabulary:target_stop",$a[4]));
			$this->AddRDF($this->QQuadL("sgd_resource:$id","sgd_vocabulary:percent_aligned",$a[5]));
			$this->AddRDF($this->QQuadL("sgd:resource:$id","sgd_vocabulary:score",$a[6]));
			$this->AddRDF($this->QQuad("sgd:$id2","sgd_vocabulary:is-encoded-by","taxon:".$a[8]));
		}//while
		return TRUE;
	}//psiblast

	function mapping(){
		$this->AddRDF($this->QQuad("sgd_vocabulary:has-proper-part","owl:equivalentProperty","sio:SIO_000053"));
		$this->AddRDF($this->QQuad("sgd_vocabulary:encodes","owl:equivalentProperty","sio:SIO_010078"));
		$this->AddRDF($this->QQuad("sgd_vocabulary:is-about","owl:equivalentProperty","sio:SIO_000332"));
		$this->AddRDF($this->QQuad("sgd_vocabulary:is-proper-part-of","owl:equivalentProperty","sio:SIO_000093"));
		$this->AddRDF($this->QQuad("sgd_vocabulary:article","owl:equivalentProperty","sio:SIO_000212"));
		$this->AddRDF($this->QQuad("sgd_vocabulary:has-participant","owl:equivalentProperty","sio:SIO_000132"));
		$this->AddRDF($this->QQuad("sgd_vocabulary:is-described-by","owl:equivalentProperty","sio:SIO_000557"));
	
		$this->AddRDF($this->QQuad("sgd_vocabulary:Protein","owl:equivalentClass","chebi:36080"));
		$this->AddRDF($this->QQuad("sgd_vocabulary:RNA","owl:equivalentClass","chebi:33697"));
		$this->AddRDF($this->QQuad("sgd_vocabulary:Chromosome","owl:equivalentClass","so:0000340"));	
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
	  	file_put_contents($target_filepath, file_get_contents('http://rest.bioontology.org/bioportal/virtual/download/'.$ontology_id.'?apikey='.$apikey));
	}
}//SGDParser

set_error_handler('error_handler');
$parser = new SGDParser($argv);
$parser->Run();

?>
