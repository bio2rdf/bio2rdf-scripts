<?php

define('BIO2RDF_URI','http://bio2rdf.org/');

// namespace declarations
$gns = array(
  'xsd' => 'http://www.w3.org/2001/XMLSchema#',
  'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
  'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
  'owl' => 'http://www.w3.org/2002/07/owl#',
  'dc' => 'http://purl.org/dc/terms/',
  'skos' => 'http://www.w3.org/2004/02/skos/core#',
  'foaf' => 'http://xmlns.com/foaf/0.1/',
  'sio' => 'http://semanticscience.org/resource/',
  'bio2rdf' => BIO2RDF_URI,
  'bio2rdf_vocabulary' = > BIO2RDF_URI.'bio2rdf_vocabulary:'
);

// valid dataset namespaces
$gdataset_ns = array('afcs', 'apo','bind','biogrid','blastprodom','candida','cas','chebi','ctd','dbsnp','dip','ddbj','drugbank','ec','embl','ensembl','eco','euroscraf','flybase','fprintscan','kegg','gene3d','germonline','go','gp','grid','hmmsmart','hmmpanther','hmmpfam','hmmpir','hmmtigr','iubmb','intact','ipi','irefindex','mesh','metacyc','mi','mint','mips','geneid','refseq','omim','ophid','patternscan','pato','pharmgkb','pir','prf','profilescan','pdb','pubmed','pubchem','seg','sgd','so','superfamily','swissprot','taxon','tcdb','tigr','tpg','trembl','uniparc','uniprot','uo','registry','registry_dataset');
	
// add the valid namespaces to the global namespace array
foreach($gdataset_ns AS $ns) {
  $gns[$ns] = BIO2RDF_URI.$ns.':';
}

/** Generate the N3 prefix header **/
function N3NSHeader()
{
	global $gns;
	$buf = '';
	foreach($gns AS $ns => $url) {
		$buf .= "@prefix $ns: <$url>.".PHP_EOL;
	}
	return $buf;
}

/** to download files */
function DownloadFiles($host, $files, $ldir)
{
 foreach($files AS $file) {
  echo 'creating local download path '.$ldir.PHP_EOL;
  @mkdir($ldir,null,true);
  
  echo "Downloading $host$file ... ";
  if(!copy($host.$file,$ldir.$file)) {
    $errors= error_get_last();
    echo $errors['type']." : ".$errors['message'].PHP_EOL;
  } else {
    echo "$file copied to $ldir".PHP_EOL;
  }
 }
 return 0;
} // download

function BreakPath($path, &$dir, &$file)
{
  $rpos = strrpos($path,'/');
  if($rpos !== FALSE) {
	$dir = substr($path,0,$rpos+1);
	$file = substr($path,$rpos+1);
   } else {
	$dir = "";
	$file = $path;
   }
   return 0;
}


?>