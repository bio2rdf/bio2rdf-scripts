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
AddOption($options, 'files','all|drugs|genes|diseases|relationships','',true);
AddOption($options, 'remote_base_url',null,'http://www.pharmgkb.org/commonFileDownload.action?filename=', false);
AddOption($options, 'download','true|false','false', false);
AddOption($options, CONF_FILE_PATH, null,'/bio2rdf-scripts/common/bio2rdf_conf.rdf', false);
AddOption($options, USE_CONF_FILE,'true|false','false', false);

if(SetCMDlineOptions($argv, $options) == FALSE) {
	PrintCMDlineOptions($argv, $options);
	exit;
}

$date = date("d-m-y"); 
$releasefile = "pharmgkb-$date.n3.tgz";
$releasefile_uri = "http://download.bio2rdf.org/pharmgkb/".$releasefile;


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


$buf = N3NSHeader();
$buf .= "<$releasefile> a sio:Document .".PHP_EOL;
$buf .= "<$releasefile> rdfs:label \"Bio2RDF PharmGKB release in RDF/N3 [bio2rdf:file/pharmgkb.n3.tgz]\".".PHP_EOL;
$buf .= "<$releasefile> rdfs:comment \"RDFized from PharmGKB tab data files\".".PHP_EOL;
$buf .= "<$releasefile> dc:date \"".date("D M j G:i:s T Y")."\".".PHP_EOL;
$buf .= "<$releasefile> dc:url \"$releasefile\".".PHP_EOL;
file_put_contents($outdir."release.n3",$buf);

foreach($files AS $file) {
	$indir = $options['indir']['value'];
	$outdir = $options['outdir']['value'];
	echo "processing $indir$file.tsv...";	
    $infile = $file.".tsv";
	$n3file = $file.".ttl";
	$fp = fopen($indir.$infile,"r");
	if($fp === FALSE) {
		trigger_error("Unable to open ".$indir.$file."tsv"." for writing.");
		exit;
	}
	$buf = $file($fp);
	fclose($fp);
	
	
	$out = fopen($outdir.$n3file,"w");
	if($out === FALSE) {
		trigger_error("Unable to open ".$outdir.$file.".ttl"." for writing.");
		exit;
	}
		
	$head = N3NSHeader();
	fwrite($out,$head.$buf);
	
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
function genes(&$fp)
{
	global $releasefile;
	$buf = '';
	fgets($fp);
	while($l = fgets($fp,10000)) {
		$a = explode("\t",$l);
		
		$id = "pharmgkb_vocabulary:$a[0]";
		$buf .= QQuadL($id,"rdfs:label","$a[4] [$id]");
		$buf .= QQuad($id,"rdf:type","pharmgkb_vocabulary:Gene");
		$buf .= Quad($releasefile, GetFQURI("dc:subject"), GetFQURI($id));
		
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
	return $buf;
}

/*
PharmGKB Accession Id
Name	
Alternate Names	
Type	
DrugBank Id
*/
function drugs(&$fp)
{
	global $releasefile;
	fgets($fp);
	$buf = '';
	while($l = fgets($fp,200000)) {
		$a = explode("\t",$l);
		$id = "pharmgkb:$a[0]";

		$buf .= Quad($releasefile, GetFQURI("dc:subject"), GetFQURI($id));

		$buf .= QQuad($id,"rdf:type", "pharmgkb_vocabulary:Drug");
		$buf .= QQuadL($id,"rdfs:label","$a[1] [$id]");
		if($a[2] != '') {
			$b = explode('",',$a[2]);
			foreach($b AS $c) {
				if($c != '') $buf .= QQuadL($id,"pharmgkb_vocabulary:synonym", str_replace('"','',$c));
			}
		}
		if($a[3]) {
			$b = explode('",',$a[3]);
			foreach($b as $c) {
				if($c) $buf .= QQuadL($id,"pharmgkb_vocabulary:drugclass", addslashes(str_replace('"','',$c)));
			}
		}
		if(trim($a[4]) != '') {
			$buf .= QQuad($id,"owl:sameAs","drugbank:".trim($a[4]));
		}
		$buf .= QQuad($id,"owl:sameAs","pharmgkb:".md5($a[1]));
	}
	return $buf;
}

/*
0 PharmGKB Accession Id	
1 Name	
2 Alternate Names
*/
function diseases(&$fp)
{
  global $releasefile;
  $buf = '';
  fgets ($fp);
  while($l = fgets($fp,10000)) {
	$a = explode("\t",$l);
		
	$id = "pharmgkb:".$a[0];
	$buf .= Quad($releasefile, GetFQURI("dc:subject"), GetFQURI($id));

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
  return $buf;

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
function variantAnnotations(&$fp)
{
  global $releasefile;
  $buf = '';
  fgets($fp); // first line is header
  
  $hash = ''; // md5 hash list
  while($l = fgets($fp,10000)) {
	$a = explode("\t",$l);
	$id = "pharmgkb:$a[11]";

	$buf .= Quad($releasefile, GetFQURI('dc:subject'), GetFQURI($id));
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
  return $buf;
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
function relationships(&$fp)
{
  global $releasefile;

  $buf = '';
  fgets($fp); // first line is header
  
  $hash = ''; // md5 hash list
  while($l = fgets($fp,10000)) {
	$a = explode("\t",$l);
	
	ParseQNAME($a[0],$ns,$id1);
	ParseQNAME($a[2],$ns,$id2);
	$id = "pharmgkb_resource:association_".$id1."_".$id2;
	$buf .= Quad($releasefile, GetFQURI('dc:subject'), GetFQURI($id));
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
  
  return $buf;  
}

?>
