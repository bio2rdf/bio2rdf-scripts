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

require_once (dirname(__FILE__).'/../common/php/libphp.php');

$options = null;
AddOption($options, 'indir', null, '/data/download/pharmgkb/', false);
AddOption($options, 'outdir',null, '/data/rdf/pharmgkb/', false);
AddOption($options, 'files','all|drugs|genes|diseases|relationships|rsid|clinical_ann_metadata|var_drug_ann','',true);
AddOption($options, 'remote_base_url',null,'http://www.pharmgkb.org/commonFileDownload.action?filename=', false);
AddOption($options, 'download','true|false','false', false);
AddOption($options, CONF_FILE_PATH, null,'/bio2rdf-scripts/common/bio2rdf_conf.rdf', false);
AddOption($options, USE_CONF_FILE,'true|false','false', false);

if(SetCMDlineOptions($argv, $options) == FALSE) {
	PrintCMDlineOptions($argv, $options);
	exit;
}

$date = date("d-m-y"); 
$releasefile_uri = "pharmgkb-$date.ttl";
$releasefile_uri = "http://download.bio2rdf.org/pharmgkb/".$releasefile_uri;


@mkdir($options['indir']['value'],null,true);
@mkdir($options['outdir']['value'],null,true);
if($options['files']['value'] == 'all') {
	$files = explode("|",$options['files']['list']);
	array_shift($files);
} else {
	$files = explode("|",$options['files']['value']);
}

// download the files
if($options['download']['value'] == 'true') {
  foreach($files AS $file) {
   $myfiles[] = $file.".zip";
  }
  if($file == 'clinical_ann_metadata' || $file == 'var_drug_ann') {
	echo "Obtain clinical annotations from PharmGKB (license required)".PHP_EOL;
  } else {
		DownloadFiles($options['remote_base_url']['value'],$myfiles,$options['indir']['value']);
  
	  // unzip the files
	  foreach($files AS $file) {
		$zip = zip_open($options['indir']['value'].$file.".zip");
		if (is_resource($zip)) {
		  while ($zip_entry = zip_read($zip)) {
			if (zip_entry_open($zip, $zip_entry, "r")) {
				echo 'expanding '.zip_entry_name($zip_entry).PHP_EOL;
				file_put_contents($options['indir']['value'].zip_entry_name($zip_entry), zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
				zip_entry_close($zip_entry);
			}
		  }
		  zip_close($zip);
		}
	  }
  }
}


$header = N3NSHeader();
$header .= "<$releasefile_uri> a sio:Document .".PHP_EOL;
$header .= "<$releasefile_uri> rdfs:label \"Bio2RDF PharmGKB release in RDF/N3 [bio2rdf_file:pharmgkb.n3.tgz]\".".PHP_EOL;
$header .= "<$releasefile_uri> rdfs:comment \"RDFized from PharmGKB tab data files\".".PHP_EOL;
$header .= "<$releasefile_uri> dc:date \"".date("D M j G:i:s T Y")."\".".PHP_EOL;
file_put_contents($options['outdir']['value']."pharmgkb-$date.ttl",$header);

foreach($files AS $file) {
	$indir = $options['indir']['value'];
	$outdir = $options['outdir']['value'];
	echo "processing $indir$file.tsv...";	
    $infile = $file.".tsv";
	$outfile = $file.".ttl";
	$in = fopen($indir.$infile,"r");
	if($in === FALSE) {
		trigger_error("Unable to open ".$indir.$infile." for reading.");
		exit;
	}
	$out = fopen($outdir.$outfile,"w");
	if($out === FALSE) {
		trigger_error("Unable to open ".$outdir.$outfile." for writing.");
		exit;
	}
	$head = N3NSHeader();
	fwrite($out,$head);
	
	$file($in,$out);
	
	fclose($in);		
	fclose($out);
	echo "done!".PHP_EOL;
}

/*
0 PharmGKB Accession Id	
1 Entrez Id	
2 Ensembl Id	
3 UniProt Id	
4 Name	
5 Symbol	
6 Alternate Names	
7 Alternate Symbols	
8 Is Genotyped	
9 Is VIP	
10 PD	
11 PK	
12 Has Variant Annotation
*/
function genes(&$in, &$out)
{
	global $releasefile_uri;
	$buf = '';
	fgets($in);
	while($l = fgets($in,10000)) {
		$a = explode("\t",$l);
		
		$id = "pharmgkb_vocabulary:$a[0]";
		$buf .= QQuadL($id,"rdfs:label","$a[4] [$id]");
		$buf .= QQuad($id,"rdf:type","pharmgkb_vocabulary:Gene");
		$buf .= Quad($releasefile_uri, GetFQURI("dc:subject"), GetFQURI($id));
		
		if($a[1]) $buf .= QQuad($id,"owl:sameAs","geneid:$a[1]");
		if($a[2]) $buf .= QQuad($id,"owl:sameAs","ensembl:$a[2]");
		if($a[3]) $buf .= QQuad($id,"rdfs:seeAlso","uniprot:$a[3]");
		if($a[4]) $buf .= QQuadL($id,"pharmgkb_vocabulary:name",$a[4]);
		if($a[5]) {
			$buf .= QQuadL($id,"pharmgkb_vocabulary:symbol",$a[5]);
			$aid = "pharmgkb:$a[5]";
			$buf .= QQuad($id,"owl:sameAs",$aid);
			$buf .= QQuadL($aid,"dc:identifier",$a[5]);
			$buf .= QQuadL($aid,"rdfs:label","$a[5] [pharmgkb:$a[5]]");

			// link data
			$buf .= Quad(GetFQURI($aid),GetFQURI("owl:sameAs"),"http://www4.wiwiss.fu-berlin.de/diseasome/resource/genes/$a[5]");
			$buf .= Quad(GetFQURI($aid),GetFQURI("owl:sameAs"),"http://dbpedia.org/resource/$a[5]");
			$buf .= Quad(GetFQURI($aid),GetFQURI("owl:sameAs"),"http://purl.org/net/tcm/tcm.lifescience.ntu.edu.tw/id/gene/$a[5]");

		}
		if($a[6]) {
			$b = explode('",',$a[6]);
			foreach($b as $c) {
				if($c) $buf .= QQuadL($id,"pharmgkb_vocabulary:synonym", addslashes(stripslashes(substr($c,1))));
			}
		}
		if($a[7]) {
			$b = explode('",',$a[7]);
			foreach($b as $c) {
				if($c) $buf .= QQuadL($id,"pharmgkb_vocabulary:alternate_symbol",str_replace('"','',$c));
			}
		}
		
		if($a[8]) $buf .= QQuadL($id,"pharmgkb_vocabulary:is_genotyped",$a[8]);
		if($a[9]) $buf .= QQuadL($id,"pharmgkb_vocabulary:is_vip",$a[9]);
		if($a[10] && $a[10] != '-') $buf .= QQuadL($id,"pharmgkb_vocabulary:pharmacodynamics","true");
		if($a[11] && $a[11] != '-') $buf .= QQuadL($id,"pharmgkb_vocabulary:pharmacokinetics","true");
		if(trim($a[12]) != '') $buf .= QQuadL($id,"pharmgkb_vocabulary:variant_annotation",trim($a[12]));
	}
	fwrite($out,$buf);
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
function drugs(&$in, &$out)
{
	global $releasefile_uri;
	fgets($in);
	$buf = '';
	while($l = fgets($in,200000)) {
		$a = explode("\t",$l);
		$id = "pharmgkb:$a[0]";

		$buf .= Quad($releasefile_uri, GetFQURI("dc:subject"), GetFQURI($id));

		$buf .= QQuad($id,"rdf:type", "pharmgkb_vocabulary:Drug");
		$buf .= QQuadL($id,"rdfs:label","$a[1] [$id]");
		if(trim($a[2])) { 
			// generic names
			// Entacapona [INN-Spanish],Entacapone [Usan:Inn],Entacaponum [INN-Latin],entacapone
			$b = explode(',',trim($a[2]));
			foreach($b AS $c) {
				$buf .= QQuadL($id,"pharmgkb_vocabulary:generic_name", str_replace('"','',$c));
			}
		}
		if(trim($a[3])) { 
			// trade names
			//Disorat,OptiPranolol,Trimepranol
			$b = explode(',',trim($a[3]));
			foreach($b as $c) {
				$buf .= QQuadL($id,"pharmgkb_vocabulary:trade_name", addslashes(str_replace('"','',$c)));
			}
		}
		if(trim($a[4])) {
			// Brand Mixtures	
			// Benzyl benzoate 99+ %,"Dermadex Crm (Benzoic Acid + Benzyl Benzoate + Lindane + Salicylic Acid + Zinc Oxide + Zinc Undecylenate)",
			$b = explode(',',trim($a[4]));
			foreach($b as $c) {
				$buf .= QQuadL($id,"pharmgkb_vocabulary:brand_mixture", addslashes(str_replace('"','',$c)));
			}
		}
		if(trim($a[5])) {
			// Type	
			$buf .= QQuadL($id,"pharmgkb_vocabulary:drug_class", addslashes(str_replace('"','',$a[5])));
		}
		if(trim($a[6])) {
			// Cross References	
			// drugBank:DB00789,keggDrug:D01707,pubChemCompound:55466,pubChemSubstance:192903,url:http://en.wikipedia.org/wiki/Gadopentetate_dimeglumine
			$b = explode(',',trim($a[6]));
			foreach($b as $c) {
				ParseQNAME($c,$ns,$id1);
				$ns = str_replace(array('keggcompound','keggdrug'), array('kegg','kegg'), strtolower($ns));
				if($ns == "url") {
					$buf .= QQuad($id,"pharmgkb_vocabulary:xref", $id );
				} else {
					$buf .= QQuad($id,"pharmgkb_vocabulary:xref", $ns.":".$id1);
				}
			}
		}
		if(trim($a[8])) {
			// External Vocabulary
			// ATC:H01AC(Somatropin and somatropin agonists),ATC:V04CD(Tests for pituitary function)
			$b = explode(',',trim($a[8]));
			foreach($b as $c) {
				ParseQNAME($c,$ns,$id1);
				$ns = strtolower($ns);
				$pos = strpos($id1,"(");
				if($pos !== FALSE) {
					$id1 = substr($id1,0,$pos-1);
					$label = substr($id1,$pos+1,-1);
				}
				if($ns == "url") {
					$buf .= QQuad($id,"pharmgkb_vocabulary:xref", $id );
				} else {
					$buf .= QQuad($id,"pharmgkb_vocabulary:xref", $ns.":".$id1 );
				}
			}
			
		}
	}
	fwrite($out,$buf);
}

/*
0 PharmGKB Accession Id	
1 Name	
2 Alternate Names
*/
function diseases(&$in, &$out)
{
  global $releasefile_uri;
  $buf = '';
  fgets ($in);
  while($l = fgets($in,10000)) {
	$a = explode("\t",$l);
		
	$id = "pharmgkb:".$a[0];
	$buf .= Quad($releasefile_uri, GetFQURI("dc:subject"), GetFQURI($id));

	$buf .= QQuad($id,'rdf:type','pharmgkb_vocabulary:Disease');
	$buf .= QQuadL($id,'rdfs:label',addslashes($a[1])." [$id]");
	$buf .= QQuadL($id,'pharmgkb_vocabulary:name',addslashes($a[1]));

	if(!isset($a[2])) continue;
	if($a[2] != '') {
		$names = explode('",',$a[2]);
		foreach($names AS $name) {
			if($name != '') $buf .= QQuadL($id,'pharmgkb_vocabulary:synonym',str_replace('"','',$name));
		}
	}
	
//  MeSH:D001145(Arrhythmias, Cardiac),SnoMedCT:195107004(Cardiac dysrhythmia NOS),UMLS:C0003811(C0003811)
	
	$buf .= QQuad($id,'owl:sameAs',"pharmgkb:".md5($a[1]));
	if(isset($a[4]) && trim($a[4]) != '') {	  
		$d = preg_match_all('/(MeSH|SnoMedCT|UMLS):([A-Z0-9]+)\(([^\)]+)\)/',$a[4],$m, PREG_SET_ORDER);
		foreach($m AS $n) {
			$id2 = strtolower($n[1]).':'.$n[2];
			$buf .= QQuad($id,'rdfs:seeAlso',$id2);
			if(isset($n[3]) && $n[2] != $n[3]) $buf .= QQuadL($id2,'rdfs:label',str_replace('"','',$n[3]));
		}	  
	}
  }
  fwrite($out,$buf);
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
function variantAnnotations(&$in, &$out)
{
  global $releasefile_uri;
  $buf = '';
  fgets($in); // first line is header
  
  $hash = ''; // md5 hash list
  while($l = fgets($in,10000)) {
	$a = explode("\t",$l);
	$id = "pharmgkb:$a[11]";

	$buf .= Quad($releasefile_uri, GetFQURI('dc:subject'), GetFQURI($id));
	$buf .= QQuad($id,'rdf:type','pharmgkb:DrugGeneVariantInteraction');
	$buf .= QQuad($id,'pharmgkb:variant',"dbsnp:$a[1]");
	//$buf .= "$id rdfs:label \"variant [dbsnp:$a[1]]\"".PHP_EOL;
	if($a[2] != '') $buf .= QQuadL($id,'pharmgkb:variant_description',addslashes($a[2]));
	
	if($a[3] != '' && $a[3] != '-') {
		$genes = explode(", ",$a[3]);
		foreach($genes AS $gene) {
			$gene = str_replace("@","",$gene);
			$buf .= QQuad($id,'pharmgkb_vocabulary:gene',"pharmgkb:$gene");
		}
	}
	
	if($a[4] != '') {
		$features = explode(", ",$a[4]);
		array_unique($features);
		foreach($features AS $feature) {
			$z = md5($feature); if(!isset($hash[$z])) $hash[$z] = $feature;
			$buf .= QQuad(id,'pharmgkb_vocabulary:feature',"pharmgkb:$z");
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
			if($key == "PubMed ID") $buf .= QQuad($id,'bio2rdf_vocabulary:article',"pubmed:$value");
			else if($key == "Web Resource") $buf .= Quad(GetFQURI($id),GetFQURI('bio2rdf_vocabulary:url'),$value);
			else {
				// echo "$b[0]".PHP_EOL;
			}
		}
	}
	if($a[6] != '') { //annotation
		$buf .= QQuadL($id,'pharmgkb_vocabulary:description', addslashes($a[6]));
	}
	if($a[7] != '') { //drugs
		$drugs = explode("; ",$a[7]);
		foreach($drugs AS $drug) {
			$z = md5($drug); if(!isset($hash[$z])) $hash[$z] = $drug;
			$buf .= QQuad($id,'pharmgkb_vocabulary:drug',"pharmgkb:$z");
		}
	}

	if($a[8] != '') {
		$diseases = explode("; ",$a[8]);
		foreach($diseases AS $disease) {
			$z = md5($disease); if(!isset($hash[$z])) $hash[$z] = $disease;
			$buf .= QQuad($id,'pharmgkb_vocabulary:disease',"pharmgkb:$z");
		}
	}
	if(trim($a[9]) != '') {
		$buf .= QQuadL($id,'pharmgkb_vocabulary:curation_status',trim($a[9]));
	}	
  }
  foreach($hash AS $h => $label) {
	$buf .= QQuadL("pharmgkb:$h",'rdfs:label', $label);
  }
  fwrite($out,$buf);
}


/*
Entity1_id        - Gene:PA267
Entity1_name      - ABCB1
Entity2_id	      - Drug:PA165110729
Entity2_name	  - rhodamine 123
Evidence	      - RSID:rs1045642,RSID:rs1045642,RSID:rs2032582,PMID:..
Evidence Sources  - Publication,Variant
Pharmacodynamic	  - Y
Pharmacokinetic   - Y
*/
function relationships(&$in, &$out)
{
  global $releasefile_uri;

  $buf = '';
  fgets($in); // first line is header
  
  $hash = ''; // md5 hash list
  while($l = fgets($in,10000)) {
	$a = explode("\t",$l);
	
	ParseQNAME($a[0],$ns,$id1);
	ParseQNAME($a[2],$ns,$id2);
	$id = "pharmgkb_resource:association_".$id1."_".$id2;
	$buf .= Quad($releasefile_uri, GetFQURI('dc:subject'), GetFQURI($id));
	$buf .= QQuad($id,'rdf:type','pharmgkb_vocabulary:Association');
	$buf .= QQuad($id,'pharmgkb_vocabulary:entity',"pharmgkb:$id1");
	$buf .= QQuad($id,'pharmgkb_vocabulary:entity',"pharmgkb:$id2");
	$b = explode(',',$a[4]);
	foreach($b AS $c) {
		$d = str_replace(array("PMID","RSID","Pathway"),array("pubmed","dbsnp","pharmgkb"),$c);
		$buf .= QQuad($id,'pharmgkb_vocabulary:evidence',$d);	
	}
	$b = explode(',',$a[5]);
	foreach($b AS $c) {
		$buf .= QQuadL($id,'pharmgkb_vocabulary:evidence_type',strtolower($c));	
	}
	if($a[6] == 'Y') $buf .= QQuadL($id,'pharmgkb_vocabulary:pharmacodynamic_association',"true");	
	if($a[7] == 'Y') $buf .= QQuadL($id,'pharmgkb_vocabulary:pharmacokinetic_association',"true");		
  }
  
  fwrite($out,$buf);
}


/*
THIS FILE ONLY INCLUDES RSIDs IN GENES
RSID	Gene IDs	Gene Symbols
rs8331	PA27674;PA162375713	EGR2;ADO
*/
function rsid(&$in,&$out)
{
	fgets($in);fgets($in);
	while($l = fgets($in)) {
		$a = explode("\t",$l);
		$rsid = $a[0];
		$genes = explode(";",$a[1]);
		$buf .= QQuad("dbsnp:$rsid","rdf:type","pharmgkb_vocabulary:variant");
		foreach($genes AS $gene) {
			$buf .= QQuad("dbsnp:$rsid","pharmgkb_vocabulary:gene","pharmgkb:$gene");
		}
		fwrite($out,$buf);
		$buf = '';
	}
}


function clinical_ann_metadata(&$in,&$out)
{
	fgets($in);
	while($l = fgets($in,20000)) {
		$a = explode("\t",$l);
		
		// [0] => Clinical Annotation Id
		$id = "pharmgkb:$a[0]";
		$buf .= QQuad($id,"rdf:type", "pharmgkb_vocabulary:clinical_annotation");
		
		// [1] => RSID
		$rsid = "dbsnp:$a[1]";
		$buf .= QQuad($id,"rdf:type", "pharmgkb_vocabulary:variant");
		$buf .= QQuadL($rsid,"rdfs:label", "rsid:$rsid");
		
		// [2] => Variant Names
		if($a[2]) { 
			$names = explode(";",$a[2]);
			foreach($names AS $name) {
				$buf .= QQuadL($rsid,"pharmgkb_vocabulary:variant_name", addslashes(trim($name)));
			}
		}
		// [3] => Location
		if($a[3]) { 
			$buf .= QQuadL($rsid,"pharmgkb_vocabulary:location", $a[3]);
		}
		// [4] => Gene
		if($a[4]){
			$genes = explode(";",$a[4]);
			foreach($genes AS $gene) {
				preg_match("/\(([A-Za-z0-9]+)\)/",$gene,$m);
				$buf .= QQuad($rsid,"pharmgkb_vocabulary:in", "pharmgkb:$m[1]");
				$buf .= QQuad("pharmgkb:$m[1]","rdf:type", "pharmgkb_vocabulary:gene");
			}
		}

		$buf .= QQuadL($id,"rdfs:label", "clinical annotation for $rsid");
		$buf .= QQuad($id,"pharmgkb_vocabulary:snp", $rsid);
		// [5] => Evidence Strength
		if($a[5]) {
			$buf .= QQuadL($id,"pharmgkb_vocabulary:evidence_strength", $a[5]);
		}
		// [6] => Clinical Annotation Types
		if($a[6]) {
			$types = explode(";",$a[6]);
			foreach($types AS $t) {
				$buf .= QQuadL($id,"pharmgkb_vocabulary:annotation_type", $t);
				$buf .= QQuad($id,"rdf:type","pharmgkb_resource:".strtolower($t)."_annotation");
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
				$buf .= QQuad($id,"pharmgkb_vocabulary:genotype_phenotype", "pharmgkb:$gp");
				$buf .= QQuadL("pharmgkb:$gp","rdfs:label", $gp_text);
				$buf .= QQuad("pharmgkb:$gp","rdf:type", "pharmgkb_vocabulary:genotype");
				$b = explode(":",$gp_text,2);
				$buf .= QQuadL("pharmgkb:$gp","pharmgkb_vocabulary:genotype",trim($b[0]));
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
				$buf .= QQuad($id,"pharmgkb_vocabulary:variant", "pharmgkb:$variant");
				$buf .= QQuadL("pharmgkb:$variant","rdfs:label", $variant_text);
				$buf .= QQuad("pharmgkb:$variant","rdf:type", "pharmgkb_vocabulary:variant");			
			}
		}
		// [11] => PMIDs
		if($a[11]) {
			$b = explode(";",$a[11]);
			foreach($b AS $i => $pmid) {
				$pmid = trim($pmid);
				$buf .= QQuad($id,"pharmgkb_vocabulary:article", "pubmed:$pmid");
				$buf .= QQuad("pubmed:$pmid","rdf:type", "pharmgkb_vocabulary:article");			
			}
		}
		// [12] => Evidence Count
		if($a[12]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:evidence_count", $a[12]);
		}
		
		// [13] => # Cases
		if($a[13]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:cases_count", $a[13]);
		}
		// [14] => # Controlled
		if($a[14]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:controlled_count", $a[14]);
		}
		// [15] => Related Genes
		if($a[15]) {
			$b = explode(";",$a[15]);
			foreach($b AS $gene_label) {
				// find the gene_id from the label
				$lid = '-1';
				$buf .= QQuad("pharmgkb:$id","pharmgkb_vocabulary:related_gene", "pharmgkb:$lid");
			}
		}

		// [16] => Related Drugs
		if($a[16]) {
			$b = explode(";",$a[16]);
			foreach($b AS $drug_label) {
				// find the id from the label
				$lid = '-1';
				$buf .= QQuad("pharmgkb:$id","pharmgkb_vocabulary:related_drug", "pharmgkb:$lid");
			}
		}
		// [17] => Related Diseases
		if($a[17]) {
			$b = explode(";",$a[17]);
			foreach($b AS $disease_label) {
				// find the id from the label
				$lid = '-1';
				$buf .= QQuad("pharmgkb:$id","pharmgkb_vocabulary:related_disease", "pharmgkb:$lid");
			}
		}
		// [18] => OMB Races
		if($a[18]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:race", $a[18]);
		}
		// [19] => Is Unknown Race
		if($a[19]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:race", (($a[19] == "TRUE")?"race known":"race unknown"));
		}
		// [20] => Is Mixed Population
		if($a[20]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:mixed", (($a[20] == "TRUE")?"mixed population":"homogeneous population"));
		}
		// [21] => Custom Race
		if($a[21]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:special_source", $a[21]);
		}
		
		
	}
	fwrite($out,$buf);
}

function var_drug_ann(&$in,&$out)
{
	fgets($in);
	while($l = fgets($in,20000)) {
		$a = explode("\t",$l);
		//[0] => Annotation ID
		$id = "pharmgkb:$a[0]";
		$buf .= QQuad($id,"rdf:type", "pharmgkb_vocabulary:variant_drug_annotation");
		
		//[1] => RSID
		$rsid = "dbsnp:$a[1]";
		$buf .= QQuad($id,"pharmgkb_vocabulary:variant", $rsid);
		//[2] => Gene
		//CYP3A (PA27114),CYP3A4 (PA130)
		if($a[2]) {
			$genes = explode(",",$a[2]);
			foreach($genes AS $gene) {
				preg_match("/\(([A-Za-z0-9]+)\)/",$gene,$m);
				$buf .= QQuad($id,"pharmgkb_vocabulary:gene", "pharmgkb:$m[1]");
				$buf .= QQuad("pharmgkb:$m[1]","rdf:type", "pharmgkb_vocabulary:gene");
			}
		}
		
		//[3] => Drug
		if($a[3]) {
			$drugs = explode(",",$a[3]);
			foreach($drugs AS $drug) {
				preg_match("/\(([A-Za-z0-9]+)\)/",$drug,$m);
				$buf .= QQuad($id,"pharmgkb_vocabulary:drug", "pharmgkb:$m[1]");
				$buf .= QQuad("pharmgkb:$m[1]","rdf:type", "pharmgkb_vocabulary:drug");
			}
		}
		// [4] => Literature Id
		if($a[4]) {
			$b = explode(";",$a[4]);
			foreach($b AS $i => $pmid) {
				$pmid = trim($pmid);
				$buf .= QQuad($id,"pharmgkb_vocabulary:article", "pubmed:$pmid");
				$buf .= QQuad("pubmed:$pmid","rdf:type", "pharmgkb_vocabulary:article");			
			}
		}
		
		//[5] => Secondary Category
		if($a[5]) {
			$types = explode(";",$a[5]);
			foreach($types AS $t) {
				$buf .= QQuadL($id,"pharmgkb_vocabulary:annotation_type", $t);
				$buf .= QQuad($id,"rdf:type","pharmgkb_resource:".strtolower($t)."_annotation");
			}
		}
		// [6] => Significance
		if($a[6]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:significant", $a[6]);
		}
		// [7] => Notes
		if($a[7]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:note", addslashes($a[7]));
		}
	
		//[8] => Sentence
		if($a[8]) {
			$buf .= QQuadL("pharmgkb:$id","pharmgkb_vocabulary:comment", addslashes($a[8]));
		}
		//[9] => StudyParameters
		if($a[9]) {
			$sps = explode(";",$a[9]);
			foreach($sps AS $sp) {
				$t = "pharmgkb:$sp";
				$buf .= QQuad($id,"pharmgkb_vocabulary:study_parameters", $t);
				$buf .= QQuad($t,"rdf:type","pharmgkb_resource:study_parameter");
			}
		}
		//[10] => KnowledgeCategories
		if($a[10]) {
			$cats = explode(";",$a[10]);
			foreach($cats AS $cat) {
				$t = "pharmgkb:$cat";
				$buf .= QQuad($id,"pharmgkb_vocabulary:categories", $t);
				$buf .= QQuadL($t,"rdfs:label",$cat);
			}
		}	
	}
	fwrite($out,$buf);
}
?>