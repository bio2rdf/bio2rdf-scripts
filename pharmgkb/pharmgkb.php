<?php
// command line flag
$download = false;


$dl_url = "http://www.pharmgkb.org/commonFileDownload.action?filename=";
$indir = "/data/pharmgkb/tsv/";
$outdir = "/data/pharmgkb/n3/";
$date = date("d-m-y"); 
$releasefile = "pharmgkb-$date.n3.tgz";
$releasefile_uri = "http://download.bio2rdf.org/pharmgkb/".$releasefile;


require_once (dirname(__FILE__).'/../common/php/libphp.php');
global $gns;
$gns['pharmgkb_vocabulary'] = BIO2RDF_URL."pharmgkb_vocabulary:";
$gns['pharmgkb_resource'] = BIO2RDF_URL."pharmgkb_resource:";

@mkdir($indir,null,true);
@mkdir($outdir,null,true);

$files = array(
"relationships",
"diseases",
"drugs",
"genes",
// "rsid",
// "variantAnnotations"
);


// download the files
if($download) {
  foreach($files AS $file) {
   $myfiles[] = $file.".zip";
  }
  DownloadFiles($dl_url,$myfiles,$indir);
  
  // unzip the files
  foreach($files AS $file) {
	$zip = zip_open($indir.$file.".zip");
	if (is_resource($zip)) {
      while ($zip_entry = zip_read($zip)) {
        if (zip_entry_open($zip, $zip_entry, "r")) {
			echo 'expanding '.zip_entry_name($zip_entry).PHP_EOL;
            file_put_contents($indir.zip_entry_name($zip_entry), zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
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
	echo "processing $indir$file.tsv...";	
    $infile = $file.".tsv";
	$n3file = $file.".n3";
	$fp = fopen($indir.$infile,"r");
	if($fp === FALSE) {
		trigger_error("Unable to open ".$indir.$file."tsv"." for writing.");
		exit;
	}
	$buf = $file($fp);
	fclose($fp);
	
	
	$out = fopen($outdir.$n3file,"w");
	if($out === FALSE) {
		trigger_error("Unable to open ".$outdir.$file.".n3"." for writing.");
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
		$buf .= "$id rdfs:label \"$a[4] [$id]\".".PHP_EOL;
		$buf .= "$id a pharmgkb_vocabulary:Gene.".PHP_EOL;
		$buf .= "<$releasefile> dc:subject $id.".PHP_EOL;;
		if($a[1]) $buf .= "$id owl:sameAs geneid:$a[1].".PHP_EOL;
		if($a[2]) $buf .= "$id owl:sameAs ensembl:$a[2].".PHP_EOL;
		if($a[3]) $buf .= "$id rdfs:seeAlso uniprot:$a[3].".PHP_EOL;
		if($a[4]) $buf .= "$id pharmgkb_vocabulary:name \"$a[4]\".".PHP_EOL;
		if($a[5]) {
			$buf .= "$id pharmgkb_vocabulary:symbol \"$a[5]\".".PHP_EOL;
			$aid = "<http://bio2rdf.org/pharmgkb:$a[5]>";
			$buf .= "$id owl:sameAs $aid.".PHP_EOL;
			$buf .= "$aid dc:identifier \"$a[5]\".".PHP_EOL;
			$buf .= "$aid rdfs:label \"$a[5] [pharmgkb:$a[5]]\".".PHP_EOL;

			// link data
			$buf .= "$aid owl:sameAs <http://www4.wiwiss.fu-berlin.de/diseasome/resource/genes/$a[5]>.".PHP_EOL;
			$buf .= "$aid owl:sameAs <http://dbpedia.org/resource/$a[5]>.".PHP_EOL;
			$buf .= "$aid owl:sameAs <http://purl.org/net/tcm/tcm.lifescience.ntu.edu.tw/id/gene/$a[5]>.".PHP_EOL;

		}
		if($a[6]) {
			$b = explode('",',$a[6]);
			foreach($b as $c) {
				if($c) $buf .= "$id pharmgkb_vocabulary:synonym \"".addslashes(stripslashes(substr($c,1)))."\".".PHP_EOL;
			}
		}
		if($a[7]) {
			$b = explode('",',$a[7]);
			foreach($b as $c) {
				if($c) $buf .= "$id pharmgkb_vocabulary:alternate_symbol $c\".".PHP_EOL;
			}
		}
		
		if($a[8]) $buf .= "$id pharmgkb_vocabulary:is_genotyped \"$a[8]\".".PHP_EOL;
		if($a[9]) $buf .= "$id pharmgkb_vocabulary:is_vip \"$a[9]\".".PHP_EOL;
		if($a[10] && $a[10] != '-') $buf .= "$id pharmgkb_vocabulary:pharmacodynamics \"true\".".PHP_EOL;
		if($a[11] && $a[11] != '-') $buf .= "$id pharmgkb_vocabulary:pharmacokinetics \"true\".".PHP_EOL;
		if(trim($a[12]) != '') $buf .= "$id pharmgkb_vocabulary:variant_annotation \"".trim($a[12])."\".".PHP_EOL;
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

		$buf .= "<$releasefile> dc:subject $id.".PHP_EOL;;

		$buf .= "$id a pharmgkb_vocabulary:Drug.".PHP_EOL;
		$buf .= "$id rdfs:label \"$a[1] [$id]\".".PHP_EOL;
		if($a[2] != '') {
			$b = explode('",',$a[2]);
			foreach($b AS $c) {
				if($c != '') $buf .= "$id pharmgkb_vocabulary:synonym \"".str_replace('"','',$c)."\".".PHP_EOL;
			}
		}
		if($a[3]) {
			$b = explode('",',$a[3]);
			foreach($b as $c) {
				if($c) $buf .= "$id pharmgkb_vocabulary:drugclass \"".addslashes(str_replace('"','',$c))."\".".PHP_EOL;
			}
		}
		if(trim($a[4]) != '') {
			$buf .= "$id owl:sameAs drugbank:".trim($a[4]).".".PHP_EOL;
		}
		$buf .= "$id owl:sameAs pharmgkb:".md5($a[1]).".".PHP_EOL;
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
	$buf .= "<$releasefile> dc:subject $id.".PHP_EOL;;

	$buf .= "$id rdfs:subClassOf pharmgkb:Disease.".PHP_EOL;
	$buf .= "$id rdfs:label \"".addslashes($a[1])." [$id]\".".PHP_EOL;
	$buf .= "$id pharmgkb_vocabulary:name \"".addslashes($a[1])."\".".PHP_EOL;

	if(!isset($a[2])) continue;
	if($a[2] != '') {
		$names = explode('",',$a[2]);
		foreach($names AS $name) {
			if($name != '') $buf .= "$id pharmgkb_vocabulary:synonym \"".str_replace('"','',$name)."\".".PHP_EOL;
		}
	}
	$buf .= "$id owl:sameAs pharmgkb:".md5($a[1]).".".PHP_EOL;
	if(isset($a[4]) && trim($a[4]) != '') {
	  $b = explode(',',trim($a[4]));
	  foreach($b AS $c) {
		$d = preg_split('/[:()]+/',$c);
		if(!isset($d[1])) continue;
		$id2 = strtolower($d[0]).':'.$d[1];
		$buf .= "$id rdfs:seeAlso ".$id2.'.'.PHP_EOL;
		if(isset($d[2])) $buf .= "$id2 rdfs:label \"".$d[2]."\".".PHP_EOL;
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

	$buf .= "<$releasefile> dc:subject $id.".PHP_EOL;;
	$buf .= "$id a pharmgkb:DrugGeneVariantInteraction .".PHP_EOL;	
	$buf .= "$id pharmgkb:variant dbsnp:$a[1].".PHP_EOL;
	//$buf .= "$id rdfs:label \"variant [dbsnp:$a[1]]\"".PHP_EOL;
	if($a[2] != '') $buf .= "$id pharmgkb:variant_description \"".addslashes($a[2])."\".".PHP_EOL;
	
	if($a[3] != '' && $a[3] != '-') {
		$genes = explode(", ",$a[3]);
		foreach($genes AS $gene) {
			$gene = str_replace("@","",$gene);
			$buf .= "$id pharmgkb_vocabulary:gene pharmgkb:$gene.".PHP_EOL;
		}
	}
	
	if($a[4] != '') {
		$features = explode(", ",$a[4]);
		array_unique($features);
		foreach($features AS $feature) {
			$z = md5($feature); if(!isset($hash[$z])) $hash[$z] = $feature;
			$buf .= "$id pharmgkb_vocabulary:feature pharmgkb:$z.".PHP_EOL;
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
			if($key == "PubMed ID") $buf .= "$id bio2rdf_vocabulary:article pubmed:$value.".PHP_EOL;
			else if($key == "Web Resource") $buf .= "$id bio2rdf_vocabulary:url <$value>.".PHP_EOL;
			else {
				// echo "$b[0]".PHP_EOL;
			}
		}
	}
	if($a[6] != '') { //annotation
		$buf .= "$id pharmgkb_vocabulary:description \"".addslashes($a[6])."\".".PHP_EOL;
	}
	if($a[7] != '') { //drugs
		$drugs = explode("; ",$a[7]);
		foreach($drugs AS $drug) {
			$z = md5($drug); if(!isset($hash[$z])) $hash[$z] = $drug;
			$buf .= "$id pharmgkb_vocabulary:drug pharmgkb:$z.".PHP_EOL;		
		}
	}

	if($a[8] != '') {
		$diseases = explode("; ",$a[8]);
		foreach($diseases AS $disease) {
			$z = md5($disease); if(!isset($hash[$z])) $hash[$z] = $disease;
			$buf .= "$id pharmgkb_vocabulary:disease pharmgkb:$z.".PHP_EOL;				
		}
	}
	if(trim($a[9]) != '') {
		$buf .= "$id pharmgkb_vocabulary:curation_status \"".trim($a[9])."\".".PHP_EOL;
	}	
  }
  foreach($hash AS $h => $label) {
	$buf .= "pharmgkb:$h rdfs:label \"$label\".".PHP_EOL;
  }
  return $buf;
}


/*
0 PharmGKB Accession Id
1 Resource Id (PMID or URL)
2 Relationship Type (discussed, related, postiviely related, negatively related)
3 Related Genes 
4 Related Drugs
5 Related Diseases
6 Categories of Evidence
7 PharmGKB Curated
*/
function relationships(&$fp)
{
  global $releasefile;

  $buf = '';
  fgets($fp); // first line is header
  
  $hash = ''; // md5 hash list
  while($l = fgets($fp,10000)) {
	
	$a = explode("\t",$l);

	
	$id = "pharmgkb:".$a[0];
	$buf .= "<$releasefile> dc:subject $id.".PHP_EOL;;

	$buf .= "$id a pharmgkb_vocabulary:Association .".PHP_EOL;
	$buf .= "$id rdfs:label \"\".".PHP_EOL;
	if($a[1] != '' && is_numeric($a[1])) $buf .= "$id owl:sameAs pubmed:".$a[1].".".PHP_EOL;
	
	$buf .= "$id pharmgkb_vocabulary:status \"".$a[2]."\".".PHP_EOL;
	$genes = explode(";",$a[3]);
	foreach($genes AS $gene) {
		$gene = str_replace("@","",$gene);
		$buf .= "$id pharmgkb_vocabulary:gene pharmgkb:$gene.".PHP_EOL;
	}
	
	if($a[4] != '') {
		$drugs = explode(";",$a[4]);
		foreach($drugs AS $drug) {
			$z = md5($drug);
			if(!isset($hash[$z])) $hash[$z] = $drug;
			$buf .= "$id pharmgkb_vocabulary:drug pharmgkb:$z.".PHP_EOL;
		}
	}
	if($a[5] != '') {
		$diseases = explode(";",$a[5]);
		foreach($diseases AS $disease) {
			$z = md5($disease);
			if(!isset($hash[$z])) $hash[$z] = $disease;
			$buf .= "$id pharmgkb_vocabulary:disease pharmgkb:$z.".PHP_EOL;
		}
	}
	if($a[6] != '') {
		$associations = explode(";",$a[6]);
		foreach($associations AS $association) {
			$z = md5($association);
			if(!isset($hash2[$z])) $hash2[$z] = $association;
			$buf .= "$id pharmgkb_vocabulary:relationship pharmgkb_vocabulary:$association.".PHP_EOL;
		}
	}
	
	if(trim($a[7]) != '') {
		$buf .= "$id pharmgkb_vocabulary:curated \"".trim($a[7])."\".".PHP_EOL;
	}
	
  }
  
  foreach($hash AS $h => $label) {
	$buf .= "pharmgkb:$h rdfs:label \"$label\".".PHP_EOL;
  }
  foreach($hash2 AS $h => $id) {
	if($id == "FA") $label = "Molecular and Cellular Functional Assay";
	else if($id == "CO") $label = "Clinical Outcome";
	else if($id == "PD") $label = "Pharmcodynamics and Drug Response";
	else if($id == "PK") $label = "Pharmacokinetics";

	$buf .= "pharmgkb_vocabulary:$id rdfs:label \"$label [pharmgkb:$id]\".".PHP_EOL;
	$buf .= "pharmgkb_vocabulary:$id dc:title \"$label\".".PHP_EOL;
  }

  return $buf;  
}

?>
