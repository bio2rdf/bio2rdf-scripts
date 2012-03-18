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
  'bio2rdf_resource' => BIO2RDF_URI.'bio2rdf_resource:',
  'bio2rdf_vocabulary' => BIO2RDF_URI.'bio2rdf_vocabulary:'
);

// valid dataset namespaces
$gdataset_ns = array('afcs', 'apo','atc','bind','biogrid','blastprodom','candida','cas','chebi','coil','corum','ctd','cygd','dbsnp','dip','ddbj','drugbank','ec','embl','ensembl','eco','euroscarf','flybase','fprintscan','kegg','gene3d','geneid','germonline','go','gp','grid','hprd','innatedb','intact','ipi','irefindex','iubmb',"rogid","irogid","rigid","irigid","crigid","crogid","icrogid","icrigid",'iupharligand','matrixdb','mesh','metacyc','mi','mint','mips','mpact','mpi','ncbi','refseq','obo','omim','ophid','patternscan','pato','panther','pfam','pharmgkb','pir','prf','prodom','profilescan','pdb','pubmed','pubchem','pubchemcompound','pubchemsubstance','reactome','registry','registry_dataset','seg','sgd','smart','snomedct','so','superfamily','swissprot','taxon','tcdb','tigr','tpg','trembl','umls','uniparc','uniprot','uo');
	
// add the valid namespaces to the global namespace array
foreach($gdataset_ns AS $ns) {
  AddToGlobalNS($ns, true);	
}

function AddToGlobalNS($ns, $add_voc_and_resource = false)
{
  global $gns;
  if(!isset($gns[$ns])) {
	  $gns[$ns] = BIO2RDF_URI.$ns.':';
	  if($add_voc_and_resource) {
		$gns[$ns.'_vocabulary'] = BIO2RDF_URI.$ns.'_vocabulary:';
		$gns[$ns.'_resource'] = BIO2RDF_URI.$ns.'_resource:';
	  }
	  return "@prefix $ns: <http://bio2rdf.org/$ns:> .".PHP_EOL;
   }
   return '';   
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

/** Generate an n-triple statement */
function QQuad($subject, $predicate, $object, $graph = null)
{
	global $gns;
	$s = explode(":",$subject);
	$p = explode(":",$predicate);
	$o = explode(":",$object);
	
	if(!isset($gns[$s[0]])) {trigger_error("Invalid subject qname ".$s[0]); exit;}
	if(!isset($gns[$p[0]])) {trigger_error("Invalid predicate qname ".$p[0]); exit;}
	if(!isset($gns[$o[0]])) {trigger_error("Invalid object qname ".$o[0]); exit;}
	
	return Quad($gns[$s[0]].$s[1], $gns[$p[0]].$p[1], $gns[$o[0]].$o[1]);	
}

function QQuadL($subject, $predicate, $literal, $lang = null, $graph = null) 
{
	global $gns;
	$s = explode(":",$subject);
	$p = explode(":",$predicate);
	
	if(!isset($gns[$s[0]])) {trigger_error("Invalid subject qname ".$s[0]); exit;}
	if(!isset($gns[$p[0]])) {trigger_error("Invalid predicate qname ".$s[0]); exit;}
	
	return QuadLiteral($gns[$s[0]].$s[1], $gns[$p[0]].$p[1], $literal, $lang, $graph);	
}

function Quad($subject_uri, $predicate_uri, $object_uri, $graph_uri = null)
{
	return "<$subject_uri> <$predicate_uri> <$object_uri> ".(isset($graph_uri)?"<$graph_uri>":"")." .".PHP_EOL;
}

function QuadLiteral($subject_uri, $predicate_uri, $literal, $lang = null, $graph_uri = null)
{
	return "<$subject_uri> <$predicate_uri> \"$literal\"".(isset($lang)?"@$lang ":' ').(isset($graph_uri)?"<$graph_uri>":"")." .".PHP_EOL;
}

function GetFQURI($qname)
{
	global $gns;
	$q = explode(":",$qname);
	if(isset($gns[$q[0]])) return $gns[$q[0]].$q[1];
	trigger_error("Unable to get FQURI for qname $qname");
	exit;
}

function GetFQURITTL($qname)
{
	return '<'.GetFQURI($qname).'>';
}

function ParseQNAME($string,&$ns,&$id)
{
	$a = explode(":",$string,2);
	if(count($a) == 1) {
		$ns = '';
		$id = $string;
	} else {
		$ns = strtolower($a[0]);
		$id = $a[1];
	}
	return true;
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

function GetDirFiles($dir,$pattern = null)
{
 if(!is_dir($dir)) {
  echo "$dir not a directory".PHP_EOL;
  return 1;
 }

 $dh = opendir($dir);
 while (($file = readdir($dh)) !== false) {
  if($file == '.' || $file == '..') continue;
  if(isset($pattern)) {
	if(strstr($file,$pattern)) {
		$files[] = $file;
	}
  } else {
	$files[] = $file;
  }
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



/**

options block structure

options = array (
   'key' => array('default' => '', 
				  'mandatory' => true/false
		    ),
);

*/

/**
 * Add an option to the option data structure
 *
 * @author      Michel Dumontier <michel.dumontier@gmail.com>
 * @version     1.0
 * @param       object	$options    The options datastructure to be updated with command line arguments
 * @param		string	$key		The name of the parameter to set
 * @param		string	$default	The default value for the parameter
 * @param		bool	$mandatory	Whether the parameter must be set by the user
 * @return      bool     Returns TRUE on success, FALSE on failure
*/
function AddOption(&$options, $key, $list = '', $default = '', $mandatory = false)
{
	if(!isset($key) || $key == '') {
		trigger_error('invalid key for options');
		return FALSE;
	}
	$options[$key] = array('list' => $list, 'default' => $default, 'mandatory' => $mandatory);
	return TRUE;
}

/**
 * Set options data structure from command line arguments
 *
 * @author      Michel Dumontier <michel.dumontier@gmail.com>
 * @version     1.0
 * @param       object   $options    The options datastructure to be updated with command line arguments
 * @return      bool     Returns TRUE on success, FALSE on failure
*/
function SetCMDlineOptions($argv, &$options)
{
	// get rid of the script argument
	array_shift ($argv);
	// build a new parameter - value array
	foreach($argv AS $value) {
		list($key,$value) = explode("=",$value);
		if(!isset($options[$key])) {
			echo "ERROR: invalid parameter - $key".PHP_EOL;
			return FALSE;
		}
		if($value == '') {
			echo "ERROR: no value for mandatory parameter $key".PHP_EOL;
			return FALSE;
		}
		$myargs[$key] = $value;
	}

	// now iterate over all parameters in the option block and set their user/default value
	foreach($options AS $key => $a) {
		if(isset($myargs[$key])) {
			// use the supplied value
			
			// first check that it is a valid choice
			if($options[$key]['list']) {
				$m = explode('|',$options[$key]['list']);
				if(!in_array($myargs[$key],$m)) {
					echo "ERROR: input for $key parameter does not match any of the listed options".PHP_EOL;
					return FALSE;
				}
			}
			
			$options[$key]['value'] = $myargs[$key];
		} else if(!isset($myargs[$key]) && $options[$key]['mandatory']) {
			echo "ERROR: $key is a mandatory argument!".PHP_EOL;
			return FALSE;
		} else {
			// use the default
			$options[$key]['value'] = $options[$key]['default'];
		}
	}
	
	return TRUE;
}

define('USE_CONF_FILE','use-conf-file');
define('CONF_FILE_PATH','conf-file-path');
function SetCMDlineOptionsFromRDFConfFile(&$options)
{
	if(isset($options[USE_CONF_FILE]) && $options[USE_CONF_FILE] == "T"
	   && isset($options[CONF_FILE_PATH]) && $options[CONF_FILE_PATH] != '') {
		// check to see if the file is there
		if(!file_exists($options[CONF_FILE_PATH])) {
			echo $options[CONF_FILE_PATH].' not found'.PHP_EOL;
			return FALSE;
		}
		// read the file
		
		// set the options block
	}
	return FALSE;
}


/**
 * Print the command line options
 *
 * @author      Michel Dumontier <michel.dumontier@gmail.com>
 * @param       object   $options    The options datastructure
 * @return      bool     Returns TRUE on success, FALSE on failure
*/
function PrintCMDlineOptions($argv, $options)
{
	echo PHP_EOL;
	echo "Usage: php $argv[0] ".PHP_EOL;
	echo " Default values as follows, * mandatory".PHP_EOL;
	foreach($options AS $key => $a) {
	    echo '  ';
	    if($a['mandatory'] == true) echo "*";
		echo $key."=";
		if($a['list'] != '') echo $a['list'];
		if($a['default'] != '') echo PHP_EOL.'    default='.$a['default'];
		echo PHP_EOL;
	}
	return TRUE;
}


function error_handler($level, $message, $file, $line, $context) {
    //Handle user errors, warnings, and notices ourself	
    if($level === E_USER_ERROR || $level === E_USER_WARNING || $level === E_USER_NOTICE) {
		global $gns; $gns = null;
		debug_print_backtrace();
       
        return(true); //And prevent the PHP error handler from continuing
    }
    return(false); //Otherwise, use PHP's error handler
}



//Use our custom handler
set_error_handler('error_handler');

?>