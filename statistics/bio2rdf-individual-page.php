<?php
/**
* Copyright (C) 2013 Jose Cruz-Toledo, Michel Dumontier
* 
* Permission is hereby granted, free of charge, to any person obtaining a copy of
* this software and associated documentation files (the "Software"), to deal in
* the Software without restriction, including without limitation the rights to
* use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
* of the Software, and to permit persons to whom the Software is furnished to do
* so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/

/**
*This script generates an HTML page summarizing the details for all Bio2RDF endpoints.
*It reads in an instances.tab file as used by our servers
**/

$options = array(
	"sparql" => "http://localhost:8890/sparql",
	"isql" => "/usr/local/virtuoso-opensource/bin/isql",
	"use" => "isql",
	"port" => "1111",
	"user" => "dba",
	"pass" => "dba",
	"target.endpoint" => "", // target endpoint
	"graph" => "", // statistics graph
	"dataset.name" => "", // dataset
	"bio2rdf.version" => "", // specify a bio2rdf version #
	"o" => "", // output filepath
	"download" => "false", // download registry
);

// set options from user input
foreach($argv AS $i => $arg) {
	if($i==0){
		continue;
	} 
 	$b = explode("=",$arg);
 	if(isset($options[$b[0]])){
 		$options[$b[0]] = $b[1];
 	} else {
 		echo "Unknown option: $b[0]";
 		exit;
 	}//else
}//foreach

// set the right isql
if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
 $isql_windows = "/software/virtuoso-opensource/bin/isql.exe";
 $options['isql'] = $isql_windows;
}
if(!file_exists($options['isql'])) {
	trigger_error("ISQL could not be found at ".$options['isql'],E_USER_ERROR);
}


if($options['dataset.name']) {
	$dataset = $options['dataset.name'];
		
	// using the virtuoso instances; 
	$registry_file = "registry.csv";
	if(!file_exists($registry_file) or $options['download'] == "true") {
		echo "Downloading registry".PHP_EOL;
		file_put_contents(
			$registry_file,
			file_get_contents('https://docs.google.com/spreadsheet/pub?key=0AmzqhEUDpIPvdFR0UFhDUTZJdnNYdnJwdHdvNVlJR1E&single=true&gid=0&output=csv')
		);
	}
	
	$registry  = getRegistry($registry_file);
	$entry     = getRecord($registry,$dataset);
	$endpoint  = getEndpointInfo($dataset);

	$entry['sparql'] = "http://localhost:".$endpoint['sparql']."/sparql";
	$entry['target.endpoint'] = $entry['sparql'];
	if($options['target.endpoint']) $entry['target.endpoint'] = $options['target.endpoint']; 
	
	if($options['bio2rdf.version'] != '') {
		$entry['graph'] = "http://bio2rdf.org/bio2rdf.dataset:bio2rdf-$dataset-R".$options['bio2rdf.version']."-statistics";
	}
	if($options['graph']) $entry['graph'] = $options['graph'];
	$entry['from'] = "FROM <".$entry['graph'].">";
	$entry['describe'] = '';
	$outfile = $dataset.'.html';
	if($options['o']) $outfile = $options['o'];
} else {
	if($options['graph'] == '') {
		echo "please specify a graph!".PHP_EOL;
		exit;
	}
	$entry['name'] = "default";
	$entry['sparql']  = $options['sparql'];
	$entry['target.endpoint'] = $options['sparql'];
	$entry['graph'] = $options['graph'];
	$entry['from'] = "FROM <".$entry['graph'].">";
	$entry['describe'] = substr($options['sparql'], 0, strpos($options['sparql'],"/sparql"))."/describe?url=";
	$outfile = 'statistics.html';
	if($options['o']) $outfile = $options['o'];

}

makeHTML($entry,$outfile);




function getRegistry($file)
{
	$fh = fopen($file, "r") or die("Could not open File ". $file);
	$h = fgetcsv($fh,10000,",");
	while(($a = fgetcsv($fh, 10000, ","))!== FALSE){
		$r[$a[0]] = $a;
	}
	fclose($fh);
	return $r;
}

function getRecord($registry,$dataset)
{
	if(!isset($registry[$dataset])) {
		echo "unable to find $dataset in registry".PHP_EOL;
		return null;
	}
	$a = $registry[$dataset];
	
	$r['prefix'] = $a[0];
	$r['name']   = $a[9];
	$r['description']  = $a[10];
	$r['organization'] = $a[12];
	$r['keywords'] = $a[14];
	$r['homepage'] = $a[15];
	$r['license_url'] = $a[19];
	$r['ident_regex_patt'] = $a[22];
	$r['provider_html_url'] = $a[24];
	return $r;
}


function getEndpointInfo($entry)
{
	global $options;
	//return an array with the endpoint information
	$filename = 'instances.tab';
	$fh = fopen($filename, "r") or die("Could not open file: $filename!".PHP_EOL);
	while(($l = fgets($fh, 4096)) !== false){
		$a = explode("\t",trim($l));
		if($a[0] == '#' or $a[0] == '') continue;	
		if($a[2] == $entry) {
			$info['isql'] = $a[0];
			$info['sparql'] = $a[1];
			$info['name'] = $a[0];
			break;
		}
	}
	fclose($fh);
	if(!isset($info)) {
		echo "unable to find $entry in endpoinds".PHP_EOL;
		return null;
	}
	return $info;
}





function addHeader($aTitle){
	$b = "<head>";
	if(strlen($aTitle)){
		$b .= "<title>Statistics for ".$aTitle."</title>";
	}
	//add css
	$b .= '<link rel="stylesheet" type="text/css" href="http://download.bio2rdf.org/lib/datatables/css/jquery.dataTables.css">';
	$b .= '<link rel="stylesheet" type="text/css" href="http://download.bio2rdf.org/lib/datatables/css/stoc.css">';
	$b .= '<link rel="stylesheet" type="text/css" href="http://download.bio2rdf.org/lib/datatables/css/code.css">';
	$b .= '<style>
			#logo img
			{
			display: block;
  			margin-left: auto;
 			margin-right: auto;
 			height: 80px;
			}
			#link {
			 margin: 0 auto;
   			 text-align: center;
   			 margin-right:auto;
   			 margin-left:auto;
   			 font-size:12px; !important
			}
			#description {
				margin: 0 auto;
   				padding-bottom: 20px;
    			top: 50px;
				width: 960px;
			}
			body{
			   font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
               font-size: 14px;
               color:#174e74;
			}
			</style>';
	//add some js
	$b .= '<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>';
	$b .= '<script type="text/javascript" src="http://download.bio2rdf.org/lib/datatables/js/jquery.stoc.js"></script>';
	$b .= '<script type="text/javascript" src="http://download.bio2rdf.org/lib/datatables/js/jquery.dataTables.js"></script>
	<script type="text/javascript" charset="utf-8">	
	$(document).ready(function() {
		$("table").dataTable({
			"bInfo":false, 
			"bPaginate": false,
			"aaSorting": [[1,"desc"]]
		});
		$("#items").stoc({
			search: "#container"
		});
		});
		</script>
	</head>';
	return $b;
}

function addBio2RDFLogo(){
	global $options;

	$rm = '<div id="logo">
				<a  href="http://bio2rdf.org"><img src="https://googledrive.com/host/0B3GgKfZdJasrRnB0NDNNMFZqMUk/bio2rdf_logo.png" alt="Bio2RDF logo" /></a>
			</div>';
	$rm .= '<div id ="link">';
	$rm .= "<h1>Linked Data for the Life Sciences</h1>".PHP_EOL;
	$rm .= '<h2>-Release '.$options['bio2rdf.version'].'-</h2>';
	$rm .= '<h2>
	[<a href="http://bio2rdf.org" target="_blank">website</a>]
	[<a href="http://download.bio2rdf.org/release/'.$options['bio2rdf.version'].'/release.html" target="_blank">datasets</a>]
	[<a href="http://github.com/bio2rdf/bio2rdf-scripts/wiki" target="_blank">documentation</a>]</h2>';
	$rm .= "</div>";
	return $rm;
}

function addDatasetDescription($aDesc){
	$rm = "";
	if($aDesc != null && count($aDesc) > 0){
		if(isset($aDesc['name'])&&strlen($aDesc['name'])){
			$rm .= "<h2>".$aDesc['name']."</h2>";
		}
		if (isset($aDesc['description'])&&strlen($aDesc['description'])) {
			$rm .= "<p>".$aDesc['description']."</p>";
		}
		if (isset($aDesc['keywords'])&&strlen($aDesc['keywords'])) {
			$rm .= "<p><strong>Keywords: </strong>".$aDesc['keywords']."</p>";
		}
		if (isset($aDesc['namespace'])&&strlen($aDesc['namespace'])) {
			$rm .= "<p><strong>Namespace: </strong>".$aDesc['namespace']."</p>";
		}
		if (isset($aDesc['homepage'])&&strlen($aDesc['homepage'])) {
			$rm .= "<p><strong>Homepage:</strong> <a target=\"_blank\" href=\"".$aDesc['homepage']."\">".$aDesc['homepage']."</a></p>";	
		}
		if (isset($aDesc['organization'])&&strlen($aDesc['organization'])) {
			$rm .= "<p><strong>Organization:</strong> ".$aDesc['organization']."</p>";	
		}
		if (isset($aDesc['license_url'])&&strlen($aDesc['license_url'])) {
			$rm .= "<p><strong>License:</strong> <a target=\"_blank\" href=\"".$aDesc['license_url']."\">license</a></p>";
		}
		if (isset($aDesc['id'])&&strlen($aDesc['id']) && isset($aDesc['provider_html_url'])&&strlen($aDesc['provider_html_url'])) {
			//construct a record url
			$s = str_replace('$id', $aDesc['id'], $aDesc['provider_html_url']);
			$rm .= "<p><strong>Example Identifier:</strong> <a href=\"".$s."\">".$aDesc['id']."</a></p>";
		}
		if (isset($aDesc['ident_regex_patt'])&&strlen($aDesc['ident_regex_patt'])) {
			$rm .= "<p><strong>Identifier Regex Pattern:</strong> ".$aDesc['ident_regex_patt']."</p>";	
		}
	}
	return $rm;
}

function addBio2RDFDetails($u, $ns)
{
	global $options;
	$fct = substr($u,0, strpos($u,"/sparql"))."/fct";
	$rm = "";
	if($u != null && $ns != null){
		$rm .= "<p><strong>SPARQL Endpoint URL:</strong> <a href=\"$u\">$u</a></p>";
		$rm .= "<p><strong>Faceted Browser URL:</strong> <a href=\"$fct\">$fct</a></p>";
		$rm .= "<p><strong>Conversion Script URL:</strong> <a href=\"http://github.com/bio2rdf/bio2rdf-scripts/tree/master/".$ns."\">http://github.com/bio2rdf/bio2rdf-scripts/tree/master/".$ns."</a></p>";
		$rm .= "<p><strong>Download URL:</strong> <a href=\"http://download.bio2rdf.org/release/".$options['bio2rdf.version']."/".$ns."\">http://download.bio2rdf.org/release/".$options['bio2rdf.version']."/".$ns."</a></p>";
	}
	return $rm;
}


function query($sparql)
{
	global $options;
	$sparql = str_replace(array("\r","\n"),"",$sparql);
	if($options['use'] == 'isql') {
		//isql commands pre and post
		$cmd_pre = $options['isql']." -S ".$options['port']." -U ".$options['user']." -P ".$options['pass']." verbose=off banner=off prompt=off echo=ON errors=stdout exec=\"Set Blobs On;SPARQL define output:format 'JSON' "; 
		$cmd_post = '"';
		$cmd = $cmd_pre.addslashes($sparql).$cmd_post;
		$out = shell_exec($cmd);
		if(strstr($out,"*** Error")) {	
			throw new Exception($out);
		}
	} else {
		$cmd = $options['sparql']."?query=".urlencode($sparql)."&format=application%2Fsparql-results%2Bjson&timeout=0";
		$out = file_get_contents($cmd);
	}
	$json = json_decode($out);
	return $json->results->bindings;
}

function makeHTML($entry, $ofile){
	global $options;
	
	$fp = fopen($ofile, "w") or die("Could not create $ofile!");
	
	$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>';
	
	$html .= addHeader($entry['name']);
	$html .= "<body>";
	if($options['bio2rdf.version']) {
		$html .= addBio2RDFLogo();
	}
	$html .= "<div id='description'>";
	$html .= addDatasetDescription($entry);
	if($options['bio2rdf.version']) {
		$html .= addBio2RDFDetails($entry['sparql'], $entry['prefix']);
	}
	$html .= "</div>";
	$html .= "<div id='container'> <div id='items'></div>";

	$html .= addBasicStatistics($entry);
	echo "type counts".PHP_EOL;
	$html .= addTypeCountTable($entry);
	echo "property counts".PHP_EOL;
	$html .= addPropertyCountTable($entry);
	echo "object property counts".PHP_EOL;
	$html .= addObjectPropertyCountTable($entry);
	echo "datatype property counts".PHP_EOL;
	$html .= addDatatypePropertyCountTable($entry);
	echo "property object type counts".PHP_EOL;
	$html .= addPropertyObjectTypeCountTable($entry);
	echo "subject property object counts".PHP_EOL;
	$html .= addSubjectPropertyObjectCountTable($entry);
	echo "type property type counts".PHP_EOL;
	$html .= addTypePropertyTypeCountTable($entry);
	
	$html .= "</div>";
	$html .= "</body>";
	$html .= "</html>";
	fwrite($fp, $html);
	fclose($fp);
}


/***
 ***  Table Generation Functions
 ***/
function addBasicStatistics($entry){
	$rm  = "<h2>Basic metrics</h2>";
	$rm .= "<table><thead><th></th><th></th></thead>";
	$rm .= "<tbody>";
	
	$fnxs = array("getTriples","getDistinctEntities","getDistinctProperties","getDistinctSubjects","getDistinctObjects","getDistinctLiterals","getDistinctTypes");
	foreach($fnxs AS $f) {
	    $s = preg_split("/([A-Z])/",$f,0,PREG_SPLIT_DELIM_CAPTURE);
		array_shift($s);
		$label = '';
		for($i=1;$i<count($s); $i = $i+2 ) {
				$label .= $s[ $i-1 ].$s[$i]." ";
		}
		echo $label.PHP_EOL;
		$r = $f($entry);
		$rm .= "<tr><td>".trim($label)."</td><td>".(isset($r[0])?$r[0]->n->value:"0")."</td></tr>";
	}
	$rm .= "</tbody>";
	$rm .= "</table>";
	return $rm;
}

function addTypeCountTable($entry)
{
	$rm = "<hr>";
	$rm .= "<h2>Types</h2>";
	$rm .= "<table id='tc'>";
	$rm .= "<thead><tr>
		<th>Type</th>
		<th>Label</th>
		<th>Count</th>
		</tr></thead><tbody>";
	$r = getTypeCount($entry);
	foreach($r as $t => $c){
		$rm .= '<tr>
			<td><a href="'.$entry['describe'].$c->e->value.'">'.$c->e->value.'</a></th>
			<td>'.(isset($c->label)?$c->label->value:"")."</td>
			<td>".$c->n->value."</td>
			</tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}

function addPropertyCountTable($entry)
{
	$rm = "<hr>";
	$rm .= "<h2>Properties</h2>";
	$rm .= "<table id='pc'>";
	$rm .= "<thead>
		<tr>
			<th>Property</th>
			<th>Label</th>
			<th>Count</th>
		</tr></thead><tbody>";
	$r = getPropertyCount($entry);
	foreach($r as $t => $c){
		$rm .= '
		<tr>
			<td><a href="'.$entry['describe'].$c->p->value.'">'.$c->p->value.'</a></td>
			<td>'.(isset($c->plabel)?$c->plabel->value:"")."</td>
			<td>".$c->n->value."</td>
		</tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}

function addObjectPropertyCountTable($entry)
{
	$rm = "<hr>";
	$rm .= "<h2>Object Properties</h2>";
	$rm .= "<table id='opc'>";
	$rm .= "<thead>
		<tr>
			<th>Object Property</th>
			<th>Label</th>
			<th>Distinct Objects</th>
			<th>Total Objects</th>
		</tr></thead><tbody>";
	$r = getObjectPropertyCount($entry);
	foreach($r as $t => $c){
		$rm .= '
		<tr>
			<td><a href="'.$entry['describe'].$c->p->value.'">'.$c->p->value.'</a></td>
			<td>'.(isset($c->plabel)?$c->plabel->value:"")."</td>
			<td>".$c->dn->value."</td>
			<td>".$c->n->value."</td>
		</tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}

function addDatatypePropertyCountTable($entry)
{
	$rm = "<hr>";
	$rm .= "<h2>Datatype Properties</h2>";
	$rm .= "<table id='dpc'>";
	$rm .= "<thead>
		<tr>
			<th>Datatype Property</th>
			<th>Label</th>
			<th>Distinct Literals</th>
			<th>Literals</th>
		</tr></thead><tbody>";
	$r = getDatatypePropertyCount($entry);
	foreach($r as $t => $c){
		$rm .= '
		<tr>
			<td><a href="'.$entry['describe'].$c->p->value.'">'.$c->p->value.'</a></td>
			<td>'.(isset($c->plabel)?$c->plabel->value:"")."</td>
			<td>".$c->dn->value."</td>
			<td>".$c->n->value."</td>
		</tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}

function addPropertyObjectTypeCountTable($entry)
{
	$rm = "<hr>";
	$rm .= "<h2>Property and Object Type</h2>";
	$rm .= "<table id='potc'>";
	$rm .= "<thead>
		<tr>
			<th>Property</th>
			<th>Property Label</th>
			<th>Object Type</th>
			<th>Object Type Label</th>
			<th>Unique Objects</th>
			<th>Total Objects</th>
		</tr></thead><tbody>";
	$r = getPropertyObjectTypeCount($entry);
	foreach($r as $t => $c){
		$rm .= '
		<tr>
			<td><a href="'.$entry['describe'].$c->p->value.'">'.$c->p->value.'</a></td>
			<td>'.(isset($c->plabel)?$c->plabel->value:"").'</td>
			<td><a href="'.$entry['describe'].$c->c->value.'">'.$c->c->value.'</a></td>
			<td>'.(isset($c->clabel)?$c->clabel->value:"")."</td>
			<td>".$c->dn->value."</td>
			<td>".$c->n->value."</td>
		</tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}

function addSubjectPropertyObjectCountTable($entry)
{
	$rm = "<hr>";
	$rm .= "<h2>Subject-Property-Object List</h2>";
	$rm .= "<table id='spoc'>";
	$rm .= "<thead>
		<tr>
			<th>Total Subjects</th>
			<th>Distinct Subjects</th>
			<th>Property</th>
			<th>Property Label</th>
			<th>Distinct Objects</th>
			<th>Total Objects</th>
		</tr></thead><tbody>";
	$r = getSubjectPropertyObjectCount($entry);
	foreach($r as $t => $c){
		$rm .= '
		<tr>
			<td>'.$c->sn->value.'</td>
			<td>'.$c->dsn->value.'</td>
			<td><a href="'.$entry['describe'].$c->p->value.'">'.$c->p->value.'</a></td>
			<td>'.(isset($c->plabel)?$c->plabel->value:'').'</td>
			<td>'.$c->don->value.'</td>
			<td>'.$c->on->value.'</td>
		</tr>';
	}
	$rm .= "</tbody></table>";
	return $rm;
}

function addTypePropertyTypeCountTable($entry)
{
	$rm = "<hr>";
	$rm .= "<h2>Type-Property-Type List</h2>";
	$rm .= "<table id='spoc'>";
	$rm .= "<thead>
		<tr>
			<th>Total Subjects</th>
			<th>Distinct Subjects</th>
			<th>Subject Type</th>
			<th>Subject Type Label</th>
			<th>Property</th>
			<th>Property Label</th>
			<th>Object Type</th>
			<th>Object type Label</th>
			<th>Distinct Objects</th>
			<th>Total Objects</th>
		</tr></thead><tbody>";
	$r = getTypePropertyTypeCount($entry);
	foreach($r as $t => $c){
		$rm .= '
		<tr>
			<td>'.$c->sn->value.'</td>
			<td>'.$c->dsn->value.'</td>
			<td><a href="'.$entry['describe'].$c->sc->value.'">'.$c->sc->value.'</a></td>
			<td>'.(isset($c->slabel)?$c->slabel->value:"").'</td>
			<td><a href="'.$entry['describe'].$c->p->value.'">'.$c->p->value.'</a></td>
			<td>'.(isset($c->plabel)?$c->plabel->value:"").'</td>
			<td><a href="'.$entry['describe'].$c->oc->value.'">'.$c->oc->value.'</a></td>
			<td>'.(isset($c->olabel)?$c->olabel->value:"").'</td>
			<td>'.$c->don->value.'</td>
			<td>'.$c->on->value.'</td>
		</tr>';
	}
	$rm .= "</tbody></table>";
	return $rm;
}




/***
 ***  SPARQL queries to get statistics
 ***/
function getTriples($e)
{
	$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?n 
'.$e['from'].' 
WHERE { 
	?d void:subset [ a v:Dataset-Triples; void:entities ?n ]
}';
	return query($q);
}

function getDistinctEntities($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?n 
 '.$e['from'].' 
WHERE { 
	?d void:subset [ a v:Dataset-Distinct-Entities; void:entities ?n ]
}';
	return query($q);
}

function getDistinctProperties($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?n 
'.$e['from'].' 
WHERE { 
	?d void:subset [ a v:Dataset-Distinct-Properties; void:entities ?n ]
}';
	return query($q);
}

function getDistinctSubjects($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?n 
'.$e['from'].' 
WHERE { 
	?d void:subset [ a v:Dataset-Distinct-Subjects; void:entities ?n ]
}';
	return query($q);
}

function getDistinctObjects($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?n 
 '.$e['from'].' 
WHERE { 
	?d void:subset [ a v:Dataset-Distinct-Objects; void:entities ?n ]
}';
	return query($q);
}

function getDistinctLiterals($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?n 
'.$e['from'].' 
WHERE { 
	?d void:subset [ a v:Dataset-Distinct-Literals; void:entities ?n ]
}';
	return query($q);
}


function getDistinctTypes($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?n 
'.$e['from'].' 
WHERE { 
	?d void:subset [ a v:Dataset-Distinct-Types; void:entities ?n ]
}';
	return query($q);
}


function getTypeCount($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?e ?label ?n 
 '.$e['from'].' 
WHERE { 
	?d void:subset [ 
		a v:Dataset-Type-Count; 
		void:class ?e ;
		void:entities ?n;
		void:distinctEntities ?dn 
	]
	
	OPTIONAL { ?e rdfs:label ?label}
}';
	return query($q);
}

function getPropertyCount($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?p ?plabel ?n 
'.$e['from'].' 
WHERE { 
	?d void:subset [ 
		a v:Dataset-Property-Count; 
		void:property ?p ;
		void:triples ?n
	]
	
	OPTIONAL { ?p rdfs:label ?plabel}
}';
	return query($q);
}


function getObjectPropertyCount($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?p ?plabel ?n ?dn 
'.$e['from'].' 
WHERE { 
	?d void:subset [ 
		a v:Dataset-Object-Property-Count; 
		void:linkPredicate ?p ;
		void:objectsTarget [
			void:entities ?n ;
			void:distinctEntities ?dn 
		]
	]
	
	OPTIONAL {?p rdfs:label ?plabel}
}';
	return query($q);
}


function getDatatypePropertyCount($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?p ?plabel ?n ?dn 
'.$e['from'].' 
WHERE { 
	?d void:subset [ 
		a v:Dataset-Datatype-Property-Count; 
		void:linkPredicate ?p ;
		void:objectsTarget [
			void:entities ?n ;
			void:distinctEntities ?dn 
		]
	]
	
	OPTIONAL {?p rdfs:label ?plabel}
}';
	return query($q);
}


function getPropertyObjectTypeCount($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?p ?plabel ?c ?clabel ?n ?dn 
 '.$e['from'].' 
WHERE { 
	?d void:subset [ 
		a v:Dataset-Property-Object-Type-Count; 
		void:linkPredicate ?p ;
		void:objectsTarget [
			void:class ?c;
			void:entities ?n ;
			void:distinctEntities ?dn 
		]
	]
	
	OPTIONAL {?p rdfs:label ?plabel}
	OPTIONAL {?c rdfs:label ?clabel}
}';
	return query($q);
}

function getSubjectPropertyObjectCount($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?p ?plabel ?sn ?dsn ?on ?don 
'.$e['from'].' 
WHERE { 
	?d void:subset [ 
		a v:Dataset-Subject-Property-Object-Count; 
		void:linkPredicate ?p ;

		void:subjectsTarget [
			void:entities ?sn ;
			void:distinctEntities ?dsn 
		];
		
		void:objectsTarget [
			void:entities ?on ;
			void:distinctEntities ?don 
		];
	]
	
	OPTIONAL {?p rdfs:label ?plabel}
}';
	return query($q);
}

function getTypePropertyTypeCount($e)
{
$q = '
PREFIX void: <http://rdfs.org/ns/void#>
PREFIX v: <http://bio2rdf.org/bio2rdf.dataset_vocabulary:>

SELECT ?sc ?slabel ?sn ?dsn ?p ?plabel ?oc ?olabel ?on ?don 
 '.$e['from'].' 
WHERE { 
	?d void:subset [ 
		a v:Dataset-Type-Property-Type-Count; 
		void:linkPredicate ?p ;

		void:subjectsTarget [
			void:class ?sc;
			void:entities ?sn ;
			void:distinctEntities ?dsn 
		];
		
		void:objectsTarget [
			void:class ?oc;
			void:entities ?on ;
			void:distinctEntities ?don 
		];
	]
	
	OPTIONAL {?p rdfs:label ?plabel}
	OPTIONAL {?sc rdfs:label ?slabel}
	OPTIONAL {?oc rdfs:label ?olabel}
}';
	return query($q);
}


?>
