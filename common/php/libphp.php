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
  'bio2rdf_vocabulary' => BIO2RDF_URI.'bio2rdf_vocabulary:'
);

// valid dataset namespaces
$gdataset_ns = array('afcs', 'apo','bind','biogrid','blastprodom','candida','cas','chebi','coil','ctd','dbsnp','dip','ddbj','drugbank','ec','embl','ensembl','eco','euroscraf','flybase','fprintscan','kegg','gene3d','germonline','go','gp','grid','hmmsmart','hmmpanther','hmmpfam','hmmpir','hmmtigr','iubmb','intact','ipi','irefindex','mesh','metacyc','mi','mint','mips','geneid','refseq','omim','ophid','patternscan','pato','pharmgkb','pir','prf','profilescan','pdb','pubmed','pubchem','seg','sgd','so','superfamily','swissprot','taxon','tcdb','tigr','tpg','trembl','uniparc','uniprot','uo','registry','registry_dataset');
	
// add the valid namespaces to the global namespace array
foreach($gdataset_ns AS $ns) {
  $gns[$ns] = BIO2RDF_URI.$ns.':';
  $gns[$ns.'_vocabulary'] = BIO2RDF_URI.$ns.'_vocabulary:';
  $gns[$ns.'_resource'] = BIO2RDF_URI.$ns.'_resource:';
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
 foreach($files AS $filepath) {
  echo 'creating local download path '.$ldir.PHP_EOL;
  @mkdir($ldir,null,true);
  
  BreakPath($filepath, $dir, $file);
  
  echo "Downloading $host$dir$file ... ";
  if(!copy($host.$dir.$file,$ldir.$file)) {
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


/**
 * Copy a file, or recursively copy a folder and its contents
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.1
 * @link        http://aidanlister.com/repos/v/function.copyr.php
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @return      bool     Returns TRUE on success, FALSE on failure
 */
function copyr($source, $dest)
{
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        copyr("$source/$entry", "$dest/$entry");
    }

    // Clean up
    $dir->close();
    return true;
}

function GetDirFiles($dir,$pattern)
{
 if(!is_dir($dir)) {
  echo "$dir not a directory".PHP_EOL;
  return 1;
 }

 $dh = opendir($dir);
 while (($file = readdir($dh)) !== false) {
  if($file == '.' || $file == '..') continue;
  $files[] = $file;
 }
 sort($files);
 closedir($dh);
 return $files; 
}

function GetLatestNCBOOntology($ontology_id,$apikey,$target_filepath)
{
  @mkdir($dl_dir,null,true);
  file_put_contents($target_filepath, 
     file_get_contents('http://rest.bioontology.org/bioportal/virtual/download/'.$ontology_id.'?apikey='.$apikey));
}


?>