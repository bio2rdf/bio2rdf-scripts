<?php
/**
Copyright (C) 2011 Michel Dumontier

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
 * An RDF generator for PharmGKB (http://pharmgkb.org)
 * @version 1.0
 * @author Michel Dumontier
*/

require('../../php-lib/rdfapi.php');

class PharmGKBParser extends RDFFactory 
{
	function __construct($argv) {
		parent::__construct();
		// set and print application parameters
		$this->AddParameter('files',true,'all|drugs|genes|diseases|relationships|pathways|rsid|variant_annotations|offsides|twosides','all','all or comma-separated list of files to process');
		$this->AddParameter('indir',false,null,'/data/download/pharmgkb/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/pharmgkb/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://www.pharmgkb.org/commonFileDownload.action?filename=');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		$this->SetReleaseFileURI("pharmgkb");
		
		return TRUE;
	}
	
	function Run()
	{
		// get the file list
		if($this->GetParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",$this->GetParameterValue('files'));
		}

		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rdir = $this->GetParameterValue('download_url');
		foreach($files AS $file) {
			if($file == 'variant_annotations') {
				$lfile = $ldir."annotations.zip";
				if(!file_exists($lfile)) {
					echo "Contact PharmGKB to get access to variants/clinical variants; save file as annotations.zip".PHP_EOL;
					continue;
				}
			} else {
				// check if exists
				$lfile = $ldir.$file.".zip";
				if(!file_exists($lfile)) {
					trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
					$this->SetParameterValue('download',true);
				}
			}
			
			// download
			if($this->GetParameterValue('download') == true) { 
				$rfile = $rdir.$file.".zip";
				echo "downloading $file...";
				if($file == 'offsides') {
					file_put_contents($lfile,file_get_contents('http://www.pharmgkb.org/redirect.jsp?p=ftp%3A%2F%2Fftpuserd%3AGKB4ftp%40ftp.pharmgkb.org%2Fdownload%2Ftatonetti%2F3003377s-offsides.zip'));
				} elseif($file == 'twosides') {
					file_put_contents($lfile,file_get_contents('http://www.pharmgkb.org/redirect.jsp?p=ftp%3A%2F%2Fftpuserd%3AGKB4ftp%40ftp.pharmgkb.org%2Fdownload%2Ftatonetti%2F3003377s-twosides.zip'));
				} elseif($file == 'pathways') {
					file_put_contents($lfile,file_get_contents('http://www.pharmgkb.org/commonFileDownload.action?filename='.$file.'-tsv.zip'));
				} else {
					file_put_contents($lfile,file_get_contents('http://www.pharmgkb.org/commonFileDownload.action?filename='.$file.'.zip'));
				}
			}
			
			// get a pointer to the file in the zip archive
			$zin = new ZipArchive();
			if ($zin->open($lfile) === FALSE) {
				trigger_error("Unable to open $lfile");
				exit;
			}
			if($file == "variant_annotations")
				$zipentries = array('clinical_ann_metadata.tsv','var_drug_ann.tsv','var_pheno_ann.tsv','var_fa_ann.tsv'); //'study_parameters.tsv'
			else if($file == "relationships") $zipentries = array("relationships/$file.tsv");
			else if($file == 'offsides') $zipentries = array('3003377s-offsides.tsv');
			else if($file == 'twosides') $zipentries = array('3003377s-twosides.tsv');
			else $zipentries = array($file.".tsv");
			
			// set the write file, parse, write and close
			$outfile = $odir.$file.'.ttl'; $gz=false;
			if($this->GetParameterValue('gzip')) {$outfile .= '.gz';$gz = true;}
			$this->SetWriteFile($outfile, $gz);
			
			foreach($zipentries AS $zipentry) {
				if(($fp = $zin->getStream($zipentry)) === FALSE) {
					trigger_error("Unable to get $file.tsv in ziparchive $lfile");
					return FALSE;
				}
				$this->SetReadFile($lfile);
				$this->GetReadFile()->SetFilePointer($fp);
				
				if($file == "variant_annotations") {
					if($zipentry == "clinical_ann_metadata.tsv") $fnx = "clinical_ann_metadata";
					else $fnx = 'variant_annotation';
					echo "processing $zipentry..";
				} else {
					$fnx = $file;	
					echo "processing $fnx..";
				}
					
				$this->$fnx();
				$this->WriteRDFBufferToWriteFile();
				echo PHP_EOL;
			}
			$this->GetWriteFile()->Close();

		}
		return TRUE;
	}
 
//PharmGKB Accession Id   Entrez Id       Ensembl Id      Name    Symbol  Alternate Names Alternate Symbols       Is VIP  Has Variant Annotation  Cross-references
	/*
	0 PharmGKB Accession Id	
	1 Entrez Id	
	2 Ensembl Id	
	3 Name	
	4 Symbol	
	5 Alternate Names	
	6 Alternate Symbols	
	7 Is Genotyped	
	9 Is VIP	
	12 Has Variant Annotation
	*/
	function genes()
	{
		if(($n = count(explode("\t",$this->GetReadFile()->Read()))) != 10) {
			trigger_error("Found $n columns in gene file - expecting 10!");
			return FALSE;
		}
		while($l = $this->GetReadFile()->Read(200000)) {
			$a = explode("\t",$l);
			
			$id = "pharmgkb:$a[0]";
			$this->AddRDF($this->QQuadL($id,"rdfs:label","$a[3] [$id]"));
			$this->AddRDF($this->QQuad($id,"rdf:type","pharmgkb_vocabulary:Gene"));
			
			// link to release				
			$this->AddRDF($this->Quad($this->GetNS()->getFQURI($id), "bio2rdf_vocabulary:version", $this->GetReleaseFileURI()));
			$this->AddRDF($this->Quad($this->GetReleaseFileURI(), $this->GetNS()->getFQURI("dc:subject"), $this->GetNS()->getFQURI($id)));
			
			// link data
			$this->AddRDF($this->Quad($this->GetNS()->getFQURI($id),$this->GetNS()->getFQURI("rdfs:seeAlso"),"http://pharmgkb.org/gene/".$a[0]));
			$this->AddRDF($this->Quad($this->GetNS()->getFQURI($id),$this->GetNS()->getFQURI("owl:sameAs"),"http://www4.wiwiss.fu-berlin.de/diseasome/resource/genes/$a[0]"));
			$this->AddRDF($this->Quad($this->GetNS()->getFQURI($id),$this->GetNS()->getFQURI("owl:sameAs"),"http://dbpedia.org/resource/$a[0]"));
			$this->AddRDF($this->Quad($this->GetNS()->getFQURI($id),$this->GetNS()->getFQURI("owl:sameAs"),"http://purl.org/net/tcm/tcm.lifescience.ntu.edu.tw/id/gene/$a[0]"));
			
			if($a[1]) $this->AddRDF($this->QQuad($id,"owl:sameAs","geneid:$a[1]"));
			if($a[2]) $this->AddRDF($this->QQuad($id,"owl:sameAs","ensembl:$a[2]"));
			if($a[3]) $this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:name",$a[3]));
			if($a[4]) $this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:symbol","symbol:$a[4]"));
			if($a[5]) {
				$b = explode('","',substr($a[5],1,-2));
				foreach($b AS $alt_name) {
					$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:alternative-name",$this->SafeLiteral(trim(stripslashes($alt_name)))));
				}
			}
			if($a[6]) { // these are not hgnc symbols
				$b = explode('","',substr($a[6],1,-2));
				foreach($b as $alt_symbol) {
					$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:alternate-symbol", trim($alt_symbol)));
				}
			}
		
			if($a[7]) $this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:is-vip",$a[7]));
			if($a[8]) $this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:is-genotyped",$a[8]));
			if($a[9]) {
				$b = explode(",",$a[9]);
				foreach($b AS $xref) {
					$xref = trim($xref);
					if(!$xref) continue;
					
					$url = false;
					$x = $this->MapXrefs($xref, $url);
					if($url == true) {
						$this->AddRDF($this->QQuadO_URL($id,"pharmgkb_vocabulary:xref",$x));
					} else {
						$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:xref",$x));
					}
				}
			}
		}
		return TRUE;
	}

	function MapXrefs($xref, &$url = false)
	{
		$xrefs = array(
			"humancycgene" => "humancyc",
			"entrezgene" => "geneid",
			"refseqdna" => "refseq",
			"refseqprotein" => "refseq",
			"refseqrna" => "refseq",
			"ucscgenomebrowser" => "refseq",
			"uniprotkb" => "uniprot",
			'genecard'=>'genecards'
		);
		$this->GetNS()->ParsePrefixedName($xref,$ns,$id);
		if(isset($xrefs[$ns])) {
			$ns = $xrefs[$ns];
		}
		$url = false;
		if($ns == "url") {
			$url = true;
			return $id;
		}
		$this->GetNS()->ParsePrefixedName($id,$ns2,$id2);
		if($ns2) {
			$id = $id2;
		}
		return $ns.":".$id;
	}
	/*
	0 PharmGKB Accession Id	
	1 Name	
	2 Generic Names	
	3 Trade Names	
	4 Brand Mixtures	
	5 Type	
	6 Cross References	
	7 SMILES
	8 External Vocabulary

	0 PA164748388	
	1 diphemanil methylsulfate
	2 
	3 Prantal		
	4 
	5 Drug/Small Molecule	
	6 drugBank:DB00729,pubChemCompound:6126,pubChemSubstance:149020		
	7 
	8 ATC:A03AB(Synthetic anticholinergics, quaternary ammonium compounds)

	*/
	function drugs()
	{
		$declared = '';
		$this->GetReadFile()->Read(1000); // first line is header
		while($l = $this->GetReadFile()->Read(200000)) {
			$a = explode("\t",$l);
			$id = "pharmgkb:$a[0]";

			$this->AddRDF($this->QQuadL($id,"rdfs:label","$a[1] [$id]"));
			$this->AddRDF($this->QQuad($id,"rdf:type", "pharmgkb_vocabulary:Drug"));

			$this->AddRDF($this->Quad($this->GetReleaseFileURI(), $this->GetNS()->getFQURI("dc:subject"), $this->GetNS()->getFQURI($id)));
			
			if(trim($a[2])) { 
				// generic names
				// Entacapona [INN-Spanish],Entacapone [Usan:Inn],Entacaponum [INN-Latin],entacapone
				$b = explode(',',trim($a[2]));
				foreach($b AS $c) {
					$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:generic_name", str_replace('"','',$c)));
				}
			}
			if(trim($a[3])) { 
				// trade names
				//Disorat,OptiPranolol,Trimepranol
				$b = explode(',',trim($a[3]));
				foreach($b as $c) {
					$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:trade_name", str_replace(array("'", "\""), array("\\\'", "") ,$c)));
				}
			}
			if(trim($a[4])) {
				// Brand Mixtures	
				// Benzyl benzoate 99+ %,"Dermadex Crm (Benzoic Acid + Benzyl Benzoate + Lindane + Salicylic Acid + Zinc Oxide + Zinc Undecylenate)",
				$b = explode(',',trim($a[4]));
				foreach($b as $c) {
					$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:brand_mixture", str_replace(array("'", "\""),array("\\\'",""), $c)));
				}
			}
			if(trim($a[5])) {
				// Type	
				$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:drug_class", str_replace(array("'", "\""),array("\\\'",""), $a[5])));
			}
			if(trim($a[6])) {
				// Cross References	
				// drugBank:DB00789,keggDrug:D01707,pubChemCompound:55466,pubChemSubstance:192903,url:http://en.wikipedia.org/wiki/Gadopentetate_dimeglumine
				$b = explode(',',trim($a[6]));
				foreach($b as $c) {
					$this->GetNS()->ParsePrefixedName($c,$ns,$id1);
					$ns = str_replace(array('keggcompound','keggdrug','drugbank','uniprotkb'), array('kegg','kegg','drugbank', 'uniprot'), strtolower($ns));
					if($ns == "url") {
						$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:xref", $id));
					} else {
						$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:xref", $ns.":".$id1));
					}
				}
			}
			if(trim($a[8])) {
				// External Vocabulary
				// ATC:H01AC(Somatropin and somatropin agonists),ATC:V04CD(Tests for pituitary function)
				// ATC:D07AB(Corticosteroids, moderately potent (group II)) => this is why you don't use brackets and commas as separators.
				$b = explode(',',trim($a[8]),2);
				foreach($b as $c) {
					preg_match_all("/ATC:([A-Z0-9]+)\((.*)\)$/",$c,$m);
					if(isset($m[1][0])) {
						$atc = "atc:".$m[1][0];
						$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:xref", $atc));	
						if(!isset($declared[$atc])) {
							$declared[$atc] = '';
							$this->AddRDF($this->QQuadL($atc,"rdfs:label", $m[2][0]));	
						}
					}
				}
				
			}
		}
		return TRUE;
	}

	/*
	0 PharmGKB Accession Id	
	1 Name	
	2 Alternate Names
	*/
	function diseases()
	{
	  $this->GetReadFile()->Read(10000);
	  while($l = $this->GetReadFile()->Read(10000)) {
		$a = explode("\t",$l);
			
		$id = "pharmgkb:".$a[0];
		$this->AddRDF($this->QQuadL($id,'rdfs:label',str_replace("'", "\\\'", $a[1])." [$id]"));
		$this->AddRDF($this->QQuad($id,'rdf:type','pharmgkb_vocabulary:Disease'));
		$this->AddRDF($this->Quad($this->GetReleaseFileURI(), $this->GetNS()->getFQURI("dc:subject"), $this->GetNS()->getFQURI($id)));

		$this->AddRDF($this->QQuadL($id,'pharmgkb_vocabulary:name',str_replace("'","\\\'", $a[1])));

		if(!isset($a[2])) continue;
		if($a[2] != '') {
			$names = explode('",',$a[2]);
			foreach($names AS $name) {
				if($name != '') $this->AddRDF($this->QQuadL($id,'pharmgkb_vocabulary:synonym',str_replace('"','',$name)));
			}
		}
		
	//  MeSH:D001145(Arrhythmias, Cardiac),SnoMedCT:195107004(Cardiac dysrhythmia NOS),UMLS:C0003811(C0003811)
		
		$this->AddRDF($this->QQuad($id,'owl:sameAs',"pharmgkb:".md5($a[1])));
		if(isset($a[4]) && trim($a[4]) != '') {	  
			$d = preg_match_all('/(MeSH|SnoMedCT|UMLS):([A-Z0-9]+)\(([^\)]+)\)/',$a[4],$m, PREG_SET_ORDER);
			foreach($m AS $n) {
				$n[1] = strtolower($n[1]);
				if($n[1] == 'snomedct') $n[1] = 'snomed';
				$id2 = $n[1].':'.$n[2];
				$this->AddRDF($this->QQuad($id,'rdfs:seeAlso',$id2));
				if(isset($n[3]) && $n[2] != $n[3]) $this->AddRDF($this->QQuadL($id2,'rdfs:label',str_replace(array("\'", "\""),array("\\\'", ""),$n[3])));
			}	  
		}
		
	  }
	  return TRUE;
	}

	/*
	0 Position on hg18
	1 RSID
	2 Name(s)	
	3 Genes
	4 Feature
	5 Evidence
	6 Annotation	
	7 Drugs	
	8 Drug Classes	
	9 Diseases	
	10 Curation Level	
	11 PharmGKB Accession ID
	*/
	function variantAnnotations()
	{ 
		$hash = ''; // md5 hash list
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read(10000)) {
			$a = explode("\t",$l);
			$id = "pharmgkb:$a[11]";

			$this->AddRDF($this->QQuadL($id,'rdfs:label',"variant annotation for $a[1] [$id]"));
			$this->AddRDF($this->QQuad($id,'rdf:type','pharmgkb:Variant-Annotation'));
			$this->AddRDF($this->Quad($this->GetReleaseFileURI(), $this->GetNS()->getFQURI('dc:subject'), $this->GetNS()->getFQURI($id)));

			$this->AddRDF($this->QQuad($id,'pharmgkb:variant',"dbsnp:$a[1]"));

			if($a[2] != '') $this->AddRDF($this->QQuadL($id,'pharmgkb:variant_description',addslashes($a[2])));

			if($a[3] != '' && $a[3] != '-') {
				$genes = explode(", ",$a[3]);
				foreach($genes AS $gene) {
					$gene = str_replace("@","",$gene);
					$this->AddRDF($this->QQuad($id,'pharmgkb_vocabulary:gene',"pharmgkb:$gene"));
				}
			}
		
			if($a[4] != '') {
				$features = explode(", ",$a[4]);
				array_unique($features);
				foreach($features AS $feature) {
					$z = md5($feature); if(!isset($hash[$z])) $hash[$z] = $feature;
					$this->AddRDF($this->QQuad(id,'pharmgkb_vocabulary:feature',"pharmgkb:$z"));
				}
			}
			if($a[5] != '') {
				//PubMed ID:19060906; Web Resource:http://www.genome.gov/gwastudies/
				$evds = explode("; ",$a[5]);
				foreach($evds AS $evd) {
					$b = explode(":",$evd);
					$key = $b[0];
					array_shift($b);
					$value = implode(":",$b);
					if($key == "PubMed ID") $this->AddRDF($this->QQuad($id,'bio2rdf_vocabulary:article',"pubmed:$value"));
					else if($key == "Web Resource") $this->AddRDF($this->Quad($this->GetNS()->getFQURI($id),$this->GetNS()->getFQURI('bio2rdf_vocabulary:url'),$value));
					else {
						// echo "$b[0]".PHP_EOL;
					}
				}
			}
			if($a[6] != '') { //annotation
				$this->AddRDF($this->QQuadL($id,'pharmgkb_vocabulary:description', str_replace(array("'", "\\\'", $a[6]))));
			}
			if($a[7] != '') { //drugs
				$drugs = explode("; ",$a[7]);
				foreach($drugs AS $drug) {
					$z = md5($drug); if(!isset($hash[$z])) $hash[$z] = $drug;
					$this->AddRDF($this->QQuad($id,'pharmgkb_vocabulary:drug',"pharmgkb:$z"));
				}
			}

			if($a[8] != '') {
				$diseases = explode("; ",$a[8]);
				foreach($diseases AS $disease) {
					$z = md5($disease); if(!isset($hash[$z])) $hash[$z] = $disease;
					$this->AddRDF($this->QQuad($id,'pharmgkb_vocabulary:disease',"pharmgkb:$z"));
				}
			}
			if(trim($a[9]) != '') {
				$this->AddRDF($this->QQuadL($id,'pharmgkb_vocabulary:curation_status',trim($a[9])));
			}	
		}
		foreach($hash AS $h => $label) {
			$this->AddRDF($this->QQuadL("pharmgkb:$h",'rdfs:label', $label));
		}
		return TRUE;
	}

	/*
	Entity1_id        - PA267, rs5186, Haplotype for PA121
	Entity1_type      - Drug, Gene, VariantLocation, Disease, Haplotype, Association       
	Entity2_id	      - PA267, rs5186, Haplotype for PA121
	Entity2_type	  - Drug, Gene, VariantLocation, Disease, Haplotype, Association       
	Evidence	      - VariantAnnotation, Pathway, VIP, ClinicalAnnotation, DosingGuideline, DrugLabel, Annotation
	Evidence Sources        - Publication
	Pharmacodynamic 	- Y
	Pharmacokinetic		- Y

	Entity1_id      Entity1_type    Entity2_id      Entity2_type    Evidence        Association     PK      PD      PMIDs
	PA445738        Disease PA134866404     Gene    VariantAnnotation       associated              PD      21912425

	*/
	function relationships()
	{
		$declared = '';
		$hash = ''; // md5 hash list
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read(10000)) {
			$a = explode("\t",$l);
			if(count($a) != 9) {
				trigger_error("Change in number of columns for relationships file");
				return FALSE;
			}
		
			// id1
			$ns1 = 'pharmgkb';
			$id1 = $a[0];
			$id1 = str_replace(" ","_",$id1);
			$type1 = $a[1];
			if($id1[0] == 'r') {
				$ns = 'dbsnp';
			} else if($id1[0] == 'H') {
				$ns = 'pharmgkb_resource';
			}

			// id2
			$ns2 = 'pharmgkb';
			$id2 = $a[2];
			$id2 = str_replace(" ","_",$id2);

			$type2 = $a[3];
			if($id2[0] == 'r') {
				$ns = 'dbsnp';
			} else if($id2[0] == 'H') {
				$ns = 'pharmgkb_resource';
			}

			// let's ignore the duplicated entries
			if($type1[0] > $type2[0]) {
				continue;
			}

			$id = "pharmgkb_resource:association_".$id1."_".$id2;
			$association = $type1.' '.$type2.' Association';
			$this->AddRDF($this->QQuadL($id,'rdfs:label',"$association [$id]"));
			$this->AddRDF($this->QQuad($id,'rdf:type','pharmgkb_vocabulary:Association'));
			$this->AddRDF($this->QQuad($id,'rdf:type','pharmgkb_vocabulary:'.str_replace(" ","-",$association)));

			$this->AddRDF($this->Quad($this->GetReleaseFileURI(), $this->GetNS()->getFQURI('dc:subject'), $this->GetNS()->getFQURI($id)));

			$this->AddRDF($this->QQuad($id,'pharmgkb_vocabulary:'.strtolower($type1),"pharmgkb:$id1"));
			$this->AddRDF($this->QQuad($id,'pharmgkb_vocabulary:'.strtolower($type2),"pharmgkb:$id2"));
			$b = explode(',',$a[4]);
			foreach($b AS $c) {
				$this->AddRDF($this->QQuadL($id,'pharmgkb_vocabulary:association_type',$c));	
			}

			if($a[6]) $this->AddRDF($this->QQuadL($id,'pharmgkb_vocabulary:pk_relationship',"true"));
			if($a[7]) $this->AddRDF($this->QQuadL($id,'pharmgkb_vocabulary:pd_relationship',"true"));
			$a[8] = trim($a[8]);
			if($a[8]) {
				$b = explode(',',$a[8]);
				foreach($b AS $pubmed_id) {
					$this->AddRDF($this->QQuad($id,'pharmgkb_vocabulary:article',"pubmed:".$pubmed_id));
				}
			}
		}
		return TRUE;
	}


	/*
	THIS FILE ONLY INCLUDES RSIDs IN GENES
	RSID	Gene IDs	Gene Symbols
	rs8331	PA27674;PA162375713	EGR2;ADO
	*/
	function rsid()
	{
		$z = 0;
		$this->GetReadFile()->Read();
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read()) {
			if($z % 10000 == 0) {
				$this->WriteRDFBufferToWriteFile();
			}
			$a = explode("\t",$l);
			$rsid = "dbsnp:".$a[0];
			$genes = explode(";",$a[1]);
			$this->AddRDF($this->QQuadL($rsid,"rdfs:label","[$rsid]"));
			$this->AddRDF($this->QQuad($rsid,"rdf:type","pharmgkb_vocabulary:Variant"));
			foreach($genes AS $gene) {
				$this->AddRDF($this->QQuad($rsid,"pharmgkb_vocabulary:gene","pharmgkb:$gene"));
			}
		}
		return TRUE;
	}

	function clinical_ann_metadata()
	{
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read(20000)) {
			$a = explode("\t",$l);
			$rsid = "dbsnp:$a[1]";
			
			// [0] => Clinical Annotation Id
			$id = "pharmgkb:$a[0]";
			$this->AddRDF($this->QQuadL($id,"rdfs:label", "clinical annotation for $rsid [$id]"));
			$this->AddRDF($this->QQuad($id,"rdf:type", "pharmgkb_vocabulary:Clinical-Annotation"));

			
			// [1] => RSID
			$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:variant", $rsid));
			
			// [2] => Variant Names
			if($a[2]) { 
				$names = explode(";",$a[2]);
				foreach($names AS $name) {
					$this->AddRDF($this->QQuadL($rsid,"pharmgkb_vocabulary:variant-name", addslashes(trim($name))));
				}
			}
			// [3] => Location
			if($a[3]) { 
				$this->AddRDF($this->QQuadL($rsid,"pharmgkb_vocabulary:location", $a[3]));
				$chr = substr($a[3],0,strpos($a[3],":"));
				$this->AddRDF($this->QQuadL($rsid,"pharmgkb_vocabulary:chromosome", $chr));
			}
			// [4] => Gene
			if($a[4]){
				$genes = explode(";",$a[4]);
				foreach($genes AS $gene) {
					preg_match("/\(([A-Za-z0-9]+)\)/",$gene,$m);
					$this->AddRDF($this->QQuad($rsid,"pharmgkb_vocabulary:gene", "pharmgkb:$m[1]"));
					$this->AddRDF($this->QQuad("pharmgkb:$m[1]","rdf:type", "pharmgkb_vocabulary:Gene"));
				}
			}

			// [5] => Evidence Strength
			if($a[5]) {
				$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:evidence-strength", $a[5]));
			}
			// [6] => Clinical Annotation Types
			if($a[6]) {
				$types = explode(";",$a[6]);
				foreach($types AS $t) {
					$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:type", $t));
					$this->AddRDF($this->QQuad($id,"rdf:type","pharmgkb_vocabulary:".strtoupper($t)."-Annotation"));
				}
			}
			// [7] => Genotype-Phenotypes IDs
			// [8] => Text
			if($a[7]) {
				$gps = explode(";",$a[7]);
				$gps_texts = explode(";",$a[8]);
				foreach($gps AS $i => $gp) {
					$gp = trim($gp);
					$gp_text = trim($gps_texts[$i]);
					$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:genotype_phenotype", "pharmgkb:$gp"));
					$this->AddRDF($this->QQuadL("pharmgkb:$gp","rdfs:label", $gp_text." [pharmgkb:$gp]"));
					$this->AddRDF($this->QQuad("pharmgkb:$gp","rdf:type", "pharmgkb_vocabulary:Genotype"));
					$b = explode(":",$gp_text,2);
					$this->AddRDF($this->QQuadL("pharmgkb:$gp","pharmgkb_vocabulary:genotype",trim($b[0])));
				}
			}
			
			// [9] => Variant Annotations IDs
			// [10] => Variant Annotations
			if($a[9]) {
				$b = explode(";",$a[9]);
				$b_texts =  explode(";",$a[10]);
				foreach($b AS $i => $variant) {
					$variant = trim($variant);
					$variant_text = trim ($b_texts[$i]);
					$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:variant", "pharmgkb:$variant"));
					$this->AddRDF($this->QQuadL("pharmgkb:$variant","rdfs:label", $variant_text, "[pharmgkb:$variant]"));
					$this->AddRDF($this->QQuad("pharmgkb:$variant","rdf:type", "pharmgkb_vocabulary:Variant"));		
				}
			}
			// [11] => PMIDs
			if($a[11]) {
				$b = explode(";",$a[11]);
				foreach($b AS $i => $pmid) {
					$pmid = trim($pmid);
					$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:article", "pubmed:$pmid"));
					$this->AddRDF($this->QQuad("pubmed:$pmid","rdf:type", "pharmgkb_vocabulary:Article"));			
				}
			}
			// [12] => Evidence Count
			if($a[12]) {
				$this->AddRDF($this->QQuadL("pharmgkb:$id","pharmgkb_vocabulary:evidence-count", $a[12]));
			}
			
			// [13] => # Cases
			if($a[13]) {
				$this->AddRDF($this->QQuadL("pharmgkb:$id","pharmgkb_vocabulary:cases-count", $a[13]));
			}
			// [14] => # Controlled
			if($a[14]) {
				$this->AddRDF($this->QQuadL("pharmgkb:$id","pharmgkb_vocabulary:controlled-count", $a[14]));
			}
			// [15] => Related Genes
			if($a[15]) {
				$b = explode(";",$a[15]);
				foreach($b AS $gene_label) {
					// find the gene_id from the label
					$lid = '-1';
					$this->AddRDF($this->QQuad("pharmgkb:$id","pharmgkb_vocabulary:related-gene", "pharmgkb:$lid"));
				}
			}

			// [16] => Related Drugs
			if($a[16]) {
				$b = explode(";",$a[16]);
				foreach($b AS $drug_label) {
					// find the id from the label
					$lid = '-1';
					$this->AddRDF($this->QQuad("pharmgkb:$id","pharmgkb_vocabulary:related-drug", "pharmgkb:$lid"));
				}
			}
			// [17] => Related Diseases
			if($a[17]) {
				$b = explode(";",$a[17]);
				foreach($b AS $disease_label) {
					// find the id from the label
					$lid = '-1';
					$this->AddRDF($this->QQuad("pharmgkb:$id","pharmgkb_vocabulary:related-disease", "pharmgkb:$lid"));
				}
			}
			// [18] => OMB Races
			if($a[18]) {
				$this->AddRDF($this->QQuadL("pharmgkb:$id","pharmgkb_vocabulary:race", $a[18]));
			}
			// [19] => Is Unknown Race
			if($a[19]) {
				$this->AddRDF($this->QQuadL("pharmgkb:$id","pharmgkb_vocabulary:race", (($a[19] == "TRUE")?"race known":"race unknown")));
			}
			// [20] => Is Mixed Population
			if($a[20]) {
				$this->AddRDF($this->QQuadL("pharmgkb:$id","pharmgkb_vocabulary:population-homogeneity", (($a[20] == "TRUE")?"mixed":"homogeneous")));
			}
			// [21] => Custom Race
			if($a[21]) {
				$this->AddRDF($this->QQuadL("pharmgkb:$id","pharmgkb_vocabulary:special-source", $a[21]));
			}
		}
		return TRUE;
	}

	function variant_annotation()
	{
		$declaration = '';
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read(20000)) {
			$a = explode("\t",$l);
			//[0] => Annotation ID
			$id = "pharmgkb:$a[0]";
			$this->AddRDF($this->QQuad($id,"rdf:type", "pharmgkb_vocabulary:Variant-Annotation"));
			
			//[1] => RSID
			$rsid = "dbsnp:$a[1]";
			$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:variant", $rsid));
			//[2] => Gene
			//CYP3A (PA27114),CYP3A4 (PA130)
			if($a[2]) {
				$genes = explode(",",$a[2]);
				foreach($genes AS $gene) {
					preg_match("/\(([A-Za-z0-9]+)\)/",$gene,$m);
					$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:gene", "pharmgkb:$m[1]"));
					$this->AddRDF($this->QQuad("pharmgkb:$m[1]","rdf:type", "pharmgkb_vocabulary:Gene"));
				}
			}
			
			//[3] => Drug
			if($a[3]) {
				$drugs = explode(",",$a[3]);
				foreach($drugs AS $drug) {
					preg_match("/\(([A-Za-z0-9]+)\)/",$drug,$m);
					if(isset($m[1])) {
						$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:drug", "pharmgkb:$m[1]"));
						$this->AddRDF($this->QQuad("pharmgkb:$m[1]","rdf:type", "pharmgkb_vocabulary:Drug"));
					}
				}
			}
			// [4] => Literature Id
			if($a[4]) {
				$b = explode(";",$a[4]);
				foreach($b AS $i => $pmid) {
					$pmid = trim($pmid);
					$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:article", "pubmed:$pmid"));
					$this->AddRDF($this->QQuad("pubmed:$pmid","rdf:type", "pharmgkb_vocabulary:Article"));			
				}
			}
			
			//[5] => Secondary Category
			if($a[5]) {
				$types = explode(";",$a[5]);
				foreach($types AS $t) {
					$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:annotation-type", $t));
					$this->AddRDF($this->QQuad($id,"rdf:type","pharmgkb_vocabulary:".strtoupper($t)."-Annotation"));
				}
			}
			// [6] => Significance
			if($a[6]) {
				$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:significant", $a[6]));
			}
			// [7] => Notes
			if($a[7]) {
				$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:note", addslashes($a[7])));
			}
		
			//[8] => Sentence
			if($a[8]) {
				$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:comment", addslashes($a[8])));
			}
			//[9] => StudyParameters
			if($a[9]) {
				$sps = explode(";",$a[9]);
				foreach($sps AS $sp) {
					$t = "pharmgkb:".trim($sp);
					$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:study-parameters", $t));
					$this->AddRDF($this->QQuad($t,"rdf:type","pharmgkb_vocabulary:Study-Parameter"));
				}
			}
			//[10] => KnowledgeCategories
			if($a[10]) {
				$cats = explode(";",$a[10]);
				foreach($cats AS $cat) {
					$t = "pharmgkb:$cat";
					$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:article-category", $t));
					if(!isset($declaration[$t])) {
						$declaration[$t] = '';
						$this->AddRDF($this->QQuadL($t,"rdfs:label",$cat));
					}
				}
			}	
		}
		return TRUE;
	}
	

	function pathways()
	{
		$entry = false;
		while($l = $this->GetReadFile()->Read(20000)) {
			$a = explode("\t",trim($l));
			if(strlen(trim($l)) == 0) {
				// end of entry
				$entry = false;
			}

			if($entry == false && isset($a[0][0]) && $a[0][0] == 'P') {
				// start of entry
				$entry = true;
				$pos = strpos($a[0],':');
		
				$id = "pharmgkb:".substr($a[0],0,$pos);
				$title = substr($a[0],$pos+2);
				$this->AddRDF($this->QQuad($id,"rdf:type","pharmgkb_vocabulary:Pathway"));
				$this->AddRDF($this->QQuadL($id,"rdfs:label",$title." [$id]"));
				$x = substr($a[0],0,$pos);
				$y = $title;
				$p2 = strrpos($title," - ");
				if($p2 !== FALSE) {
					$y = substr($title,0,$p2);
					$n = strpos($title, " via Pathway ");
					$z = substr($title,$p2+4,$n-$p2-4);
				}
			}
			if($a[0] == 'Gene') {
				$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:protein","pharmgkb:".$a[1]));
			}
			if($a[0] == 'Drug') {
				$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:chemical","pharmgkb:".$a[1]));
			}
		}
		return TRUE;
	}

	/*
	stitch_id	drug	umls_id	event	rr	log2rr	t_statistic	pvalue	observed	expected	bg_correction	sider	future_aers	medeffect
	CID000000076	dehydroepiandrosterone	C0000737	abdominal pain	2.25	1.169925001	6.537095128	6.16E-07	9	4	0.002848839	0	0	
	*/
	function offsides() 
	{
		$items = null;$z = 0;
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read(5096)) {
			list($stitch_id,$drug_name,$umls_id,$event_name,$rr,$log2rr,$t_statistic,$pvalue,$observed,$expected,$bg_correction,$sider,$future_aers,$medeffect) = explode("\t",$l);
			$z++;

			$id = 'offsides:'.$z;
			$cid = 'pubchemcompound:'.((int) sprintf("%d", substr($stitch_id,4,-1)));
			$eid = 'umls:'.str_replace('"','',$umls_id);
			$drug_name = str_replace('"','',$drug_name);
			$event_name = str_replace('"','',$event_name);
			
			$this->AddRDF($this->QQuadL($id,"rdfs:label","$event_name as a predicted side-effect of $drug_name [$id]"));
			$this->AddRDF($this->QQuad($id,"rdf:type","pharmgkb_vocabulary:Side-Effect"));
			$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:chemical",$cid));
			if(!isset($items[$cid])) {
				$items[$cid] = '';
				$this->AddRDF($this->QQuadL($cid,'rdfs:label',$drug_name));
				$this->AddRDF($this->QQuad($cid,'rdf:type','pharmgkb_vocabulary:Chemical'));
			}
			$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:event",$eid));
			if(!isset($items[$eid])) {
				$items[$eid] = '';
				$this->AddRDF($this->QQuadL($eid,'rdfs:label',$event_name));
				$this->AddRDF($this->QQuad($eid,'rdf:type','pharmgkb_vocabulary:Event'));
			}
			$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:p-value",$pvalue));
			$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:in-sider",($sider==0?"true":"false")));
			$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:in-future-aers",($future_aers==0?"true":"false")));
			$this->AddRDF($this->QQuadL($id,"pharmgkb_vocabulary:in-medeffect",($medeffect==0?"true":"false")));
		}
		return TRUE;
	}

	function twosides()
	{
		$items = null;
		$id = 0;
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read()) {
			$a = explode("\t",$l);
			$id++;
			if($id % 10000 == 0) $this->WriteRDFBufferToWriteFile();
			
			$uid = "twosides:$id";
			$d1 = "pubchemcompound:".((int) sprintf("%d",substr($a[0],4)));
			$d1_name = $a[2];
			$d2 = "pubchemcompound:".((int) sprintf("%d",substr($a[1],4)));
			$d2_name = $a[3];
			$e  = "umls:".$a[4];
			$e_name = strtolower($a[5]);

			if(!isset($items[$d1])) {
				$this->AddRDF($this->QQuadL($d1,"rdf:label",$d1_name));
				$this->AddRDF($this->QQuad($d1,"rdf:type","pharmgkb_vocabulary:Chemical"));
				$items[$d1] = '';
			}
			if(!isset($items[$d2])) {
				$this->AddRDF($this->QQuadL($d2,"rdf:label",$d2_name));
				$this->AddRDF($this->QQuad($d2,"rdf:type","pharmgkb_vocabulary:Chemical"));
				$items[$d2] = '';
			}
			if(!isset($items[$e])) {
				$this->AddRDF($this->QQuadL($e,"rdf:label",$e_name));
				$this->AddRDF($this->QQuad($e,"rdf:type","pharmgkb_vocabulary:Event"));
				$items[$e] = '';
			}
			
			$this->AddRDF($this->QQuad($uid,"rdf:type","pharmgkb_vocabulary:Drug-Drug-Association"));
			$this->AddRDF($this->QQuadL($uid,"rdfs:label","DDI between $d1_name and $d2_name leading to $e_name [$uid]"));
			$this->AddRDF($this->QQuad($uid,"pharmgkb_vocabulary:chemical",$d1));
			$this->AddRDF($this->QQuad($uid,"pharmgkb_vocabulary:chemical",$d2));
			$this->AddRDF($this->QQuad($uid,"pharmgkb_vocabulary:event",$e));
			$this->AddRDF($this->QQuadL($uid,"pharmgkb_vocabulary:p-value",$a[7]));
		}
		return TRUE;
	}
}

set_error_handler('error_handler');
$parser = new PharmGKBParser($argv);
$parser->Run();

?>
