<?php

/**
* Copyright (C) 2013 Jose Cruz-Toledo, Alison Callahan, Michel Dumontier
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
 * Script to generate an HTML summary page of Bio2RDF endpoints
 * by querying for basic metrics and dataset descriptions
*/

$options = array(
 "i" => "instances.tab", //virtuoso instances file
 "l" => "registry.csv", //dataset registry csv file
 "s" => "http://localhost", // use s3 or bio2rdf
 "r" => "3",
 "legacy" => "false",
 "o" => "/web/virtuoso/",
 "endpoint" => ""
);


// show command line options
if($argc == 1) {
 $buf = "Usage: php $argv[0] ".PHP_EOL;
 foreach($options AS $key => $value) {
  $buf .= " $key=$value ".PHP_EOL;
 }
}

// set options from user input
$buf = '';
foreach($argv AS $i => $arg) {
 if($i==0) continue;
 $b = explode("=",$arg);
 if(isset($options[$b[0]])) $options[$b[0]] = $b[1];
 else {$buf .= "unknown key $b[0]";echo $buf; exit;}
}


if(!file_exists($options['i']) && !file_exists($options['l'])) {
	echo $buf;
	exit;
}

$a = parser($options['i'], $options['l']);
$table = htmlPrinter($a);
$odir = $options['o'].$options['r']."/";
@mkdir($odir);
if(is_dir($odir)) {
	file_put_contents($odir.'index.html', $table);
} else {
	file_put_contents('index.html',$table);
}
/**
* This function will first parse the instances.tab file and 
* get the data metrics for each endpoint. The returned array will then 
* be merged by prefix name with the LSR and one multidimensional array will 
* be returned
*/
function parser($instances, $lsr)
{
	$i = parseInstancesTabFile($instances);
	$r = parseLSR($lsr);
	$a = joinLSRAndInstancesTab($i, $r);
	return $a;
}

function joinLSRAndInstancesTab($instances, $lsr)
{
	foreach($instances as $k => $v){
		$a[$k]["details"] = $v;
		$metrics = getMetrics($a[$k]["details"]['sparql']);
		if(isset($metrics['results']['bindings'][0])) {
			$m = $metrics['results']['bindings'][0];

			$a[$k]['metrics']["triples"]  =  $m['triples']['value'];
			$a[$k]['metrics']["entities"] =  $m['entities']['value'];
			$a[$k]['metrics']["subjects"] =  $m['subjects']['value'];
			if(isset($m['date']['value'])) {
				$d = new DateTime($m['date']['value']);
				$a[$k]['metrics']["date"] = date_format($d, 'Y-m-d');
			}
		}
					
		if(array_key_exists($k, $lsr)){
			$a[$k]["metrics"] = $v;
			$a[$k]["description"] = $lsr[$k];
		}
	}
	return $a;
}

function getMetrics($anEndpointURL){
	global $options;
	$query = 
"SELECT *
WHERE {
	?dataset <http://rdfs.org/ns/void#entities> ?entities .
	?dataset <http://rdfs.org/ns/void#triples> ?triples .
	?dataset <http://rdfs.org/ns/void#distinctSubjects> ?subjects .	
	OPTIONAL {
         ?dataset <http://bio2rdf.org/dcat:distribution> [ <http://purl.org/dc/terms/created> ?date ] .
    }
}
LIMIT 1";
	$url = $anEndpointURL."?query=".urlencode($query)."&format=json";
	if(($str = @file_get_contents($url)) === FALSE){
		return null;
	}
	return json_decode($str,true);
}

/**
* This function parses the LSR and returns a multidimensional assoc array
*/
function parseLSR($lsr_file){
	$fh = fopen($lsr_file, "r") or die("Could not open File $lsr_file");
	$a = array();
	if($fh){
		fgetcsv($fh, 10000, ",");
		while(($data = fgetcsv($fh, 10000, ","))!== FALSE){
			$prefix = $data[0];
			$a[$prefix]["name"] = $data[9];
			$a[$prefix]["namespace"] = $data[0];
			$a[$prefix]["description"] = $data[10];
			$a[$prefix]["homepage"] = $data[15];
			$a[$prefix]["id"] = $data[23];
		}
	}
	fclose($fh);
	return $a;
}	

/**
* This function returns an array of instance names
*/
function parseInstancesTabFile($instance_file){
	global $options;
	$fh = fopen($instance_file, "r") or die("Could not open file ".$instance_file);
	if($fh){
		while(($l = fgets($fh, 4096)) !== false ){
			if(trim($l) == '') continue;
			//only read those lines that do not start with "#"
			if(substr($l, 0, 1) != "#"){
				$a = explode("\t", trim($l));
				if(count($a) >= 3){	
					$name = trim($a[2]);		
					echo "$name".PHP_EOL;
					$instances[$name]["id"] = $name;
					$instances[$name]["sparql"] = $options['s'].":".$a[1]."/sparql";
					$instances[$name]["fct"] = $options['s'].":".$a[1]."/fct";
					$instances[$name]["describe"] = $options['s'].":".$a[1]."/describe/?url=";
					$instances[$name]["download"] = "http://download.bio2rdf.org/release/".$options['r']."/$name/";
				}
			}
		}//while
	}//if
	fclose($fh);
	return $instances;
}//parseInstancesTabFile


function htmlPrinter($endpoints){
    global $options;
	$a = '<html>
 <head>
  <title>Bio2RDF Release '.$options['r'].'</title>
 	<link rel="stylesheet" type="text/css" href="http://134.117.53.12/lib/datatables/css/jquery.dataTables.css"/>
	<link rel="stylesheet" type="text/css" href="http://134.117.53.12/lib/datatables/css/code.css"/>

	<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>
	<script type="text/javascript" src="http://134.117.53.12/lib/datatables/js/jquery.dataTables.js"></script>
	<script type="text/javascript" charset="utf-8">
		$(document).ready(function(){
			$("table").dataTable({
				"bInfo":false,
				"bPaginate":false,
				"aaSorting":[[0,"asc"]]
			});
		});
	</script>

	<style type="text/css">



	#logo{
	display:block;
	margin-left:auto;
	margin-right:auto;
	}

	#release{
	margin-left:20%;
	margin-right:20%;
	font-size: 120%;
	color:#039;
	font-weight:bold;
	}

	body {
		font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
		font-size: 10px;
		color:#174e74;
	}
	
	#links{
		background: #fff;
		margin-left:auto;
		margin-right:auto;
		text-align: center;
	}

	#tableContainer {
		margin:0 auto;
		padding-bottom: 20px;
		padding-top: 50px;
		width: 80%;
	}
</style>
</head>
<body>
<a href="http://bio2rdf.org"><img alt="Bio2RDF Homepage" id="logo" src="https://googledrive.com/host/0B3GgKfZdJasrRnB0NDNNMFZqMUk/bio2rdf_logo.png" height="80px"/></a>
<div id="links">
 <h3>Linked Data for the Life Sciences</h3>
 <h2>-Release '.$options['r'].'-</h2>
 <h2>[<a href="http://bio2rdf.org" target="_blank">website</a>]
    [<a href="http://download.bio2rdf.org/release/'.$options['r'].'/release.html" target="_blank">datasets</a>]
	[<a href="http://github.com/bio2rdf/bio2rdf-scripts/wiki" target="_blank">documentation</a>]
 </h2>
</div>
<div id="tableContainer">

<table class=\"hor-minimalist-a\">
 <thead>
  <tr>
   <th width=\"40\"></th>
   <th width=\"300\">Dataset</th>
   <th width=\"100\">Date generated</th>
   <th width=\"100\"># of triples</th>
   <th width=\"100\"># of unique entities</th>
   <th width=\"100\"># of unique subjects</th>
  </tr>
 </thead>
';
	$i = 0;
	// initialize totals
	$options['totals']['triples'] = 0;
	$options['totals']['entities'] = 0;
	$options['totals']['subjects'] = 0;
	 
	ksort($endpoints);
	foreach($endpoints as $e => $endpoint_details) {
		if($options['endpoint'] && $options['endpoint'] != $e) continue;
		$i++;
		$a .= printRow($endpoint_details, $i);
	}

	$a .= "
  <tr class=\"total\">
	<td></td>
	<td></td>	
	<td></td>
	<td>".$options['totals']['triples']."</td>
	<td>".$options['totals']['entities']."</td>
	<td>".$options['totals']['subjects']."</td>
  </tr>";

	$a .= "
</table>
<br>last updated on ".date("d-m-Y", mktime())."
</body>
</html>";
	return $a;
}

function printRow($endpoint, $rowNum)
{
	global $options;
	$server = $options['s'].'/bio2rdf/'.$options['r'].'/';
	$id = '';
	if(isset($endpoint["description"]["id"])) {
		$i = trim($endpoint["description"]["id"]);
		if(strstr($i,"http")) {
			$id = $i;
		} else {
			$pos = strpos($i,":");
			if($pos !== FALSE) {
				$id = 'http://bio2rdf.org/'.$i;
			} else {
				$id = 'http://bio2rdf.org/'.$endpoint["description"]["namespace"].":".trim($endpoint["description"]["id"]);
			}
		}
	} else {
		$endpoint['description']['id'] = $endpoint['details']['id'];
		$endpoint['description']['name'] = $endpoint['details']['id'];
		$endpoint['description']['namespace'] = $endpoint['details']['id'];
		$endpoint['description']['description'] = "";
	}

	if( $odd = $rowNum%2 ){
		$rm = "
  <tr class=\"d1\">";
	} else {
   		$rm = "
  <tr class=\"d0\">";
	}
	
	$rm .= "
	<td>".$rowNum."</td>";
	$rm .= "
	<td width=\"300\">
	   <strong><a href=\"".$server.$endpoint["description"]["namespace"].".html\" target=\"dataset\">".
	   $endpoint["description"]["name"]." [".$endpoint["description"]["namespace"]."]</a>
	   </strong><br />";
	$rm .= $endpoint["description"]["description"]."<br />";
	if(!empty($endpoint["description"]["homepage"])){
		$rm .= " 
 <a href=\"".$endpoint["description"]["homepage"]."\">".$endpoint["description"]["homepage"]."</a><br />";
	}

	if(isset($endpoint['metrics'])) {
		$options['totals']['triples']  += $endpoint['metrics']['triples'];
		$options['totals']['entities'] += $endpoint['metrics']['entities'];
		$options['totals']['subjects'] += $endpoint['metrics']['subjects'];
	}
	if(@$endpoint['metrics']['triples'] != 0) {
	$rm .= "
 <strong>Links:</strong> 
 <a href=\"".$endpoint["details"]["fct"]."\">search</a>
 <a href=\"".$endpoint["details"]["sparql"]."\">query</a>".
 (!empty($endpoint["description"]["id"])?" 
 <a href=\"".$endpoint['details']['describe'].urlencode($id).'">example</a>':'')."
 <a href=\"".$endpoint["details"]["download"]."\">download</a>";	
	} else {
		$rm .= "<strong>not yet available</strong>";
	}
	$rm .= "
	</td>
	<td width=\"100\">".$endpoint["metrics"]["date"]."</td>
	<td width=\"100\">".$endpoint["metrics"]["triples"]."</td>
	<td width=\"100\">".$endpoint["metrics"]["entities"]."</td>
	<td width=\"100\">".$endpoint["metrics"]["subjects"]."</td>
   </tr>".PHP_EOL;
	return $rm;
}
?>
