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
	"i" => "instances.tab",
	"l" => "registry.csv",
	"o" => "/www/virtuoso/",
	"r" => "3",
#	"b" => "http://ns.bio2rdf.org/sparql", // use bio2rdf endpoints or specified server uri + port from instances file 
	"b" => "http://localhost", 
	"d" => "false", // download registry
	"s" => "", // specify dataset 
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
 		echo "Uknown option: $b[0]";
 		exit;
 	}//else
}//foreach

$options = print_usage($argv, $argc);
if($options['d'] == "true") {
 echo "downloading registry".PHP_EOL;
 file_put_contents("registry.csv",file_get_contents('https://docs.google.com/spreadsheet/pub?key=0AmzqhEUDpIPvdFR0UFhDUTZJdnNYdnJwdHdvNVlJR1E&single=true&gid=0&output=csv'));
}

$endpoints = getEndpoints($options['i']);
$lsr = getLSR($options['l']);
$desc = getDescriptions($endpoints, $lsr);
$endpoint_stats = retrieveStatistics($endpoints);
makeHTML($endpoint_stats, $desc, $options['o'].$options['r']."/");

/***************/
/** FUNCTIONS **/
/***************/
function print_usage($argv, $argc){
	global $options;

	// show command line options
	if($argc == 1) {
		echo "Usage: php $argv[0] ";
		foreach($options AS $key => $value) {
	  		echo "$key=$value ".PHP_EOL;
	 	}
	}
	// set options from user input
	foreach($argv AS $i => $arg) {
		if($i==0){
			continue;
		} 
	 	$b = explode("=",$arg);
	 	if(isset($options[$b[0]])){
	 		$options[$b[0]] = $b[1];
	 	} else {
	 		echo "Uknown option: $b[0]";
	 		exit;
	 	}//else
	}//foreach
	if($options['i'] == '/instances/file/path/'){
		echo "** Please specify a valid instances file **".PHP_EOL;
		exit;
	}
	if($options['l'] == '/path/to/lsr.csv'){
		echo "** Specify a valid LSR CSV file path. **".PHP_EOL;
		exit;
	}
	return $options;
}

function getDescriptions($endpoints, $lsr){
	$r = array();
	foreach ($endpoints as $e => $v) {
		if(isset($lsr[$e])) {
			$r[$e]['prefix'] = $lsr[$e][0];
			$r[$e]['name'] = $lsr[$e][9];
			$r[$e]['description'] = $lsr[$e][10];
			$r[$e]['organization'] = $lsr[$e][12];
			$r[$e]['keywords'] = $lsr[$e][14];
			$r[$e]['homepage'] = $lsr[$e][15];
			$r[$e]['license_url'] = $lsr[$e][19];
			$r[$e]['ident_regex_patt'] = $lsr[$e][22];
			$r[$e]['provider_html_url'] = $lsr[$e][24];
		}else{
			trigger_error("$e not found in registry",E_USER_WARNING);
		}
	}
	return $r;
}

/**
* This function parses the LSR and returns a multidimensional assoc array
*/
function getLSR($file){
	$fh = fopen($file, "r") or die("Could not open File ". $file);
	if($fh){
		$h = fgetcsv($fh,10000,",");
		while(($a = fgetcsv($fh, 10000, ","))!== FALSE){
			$r[$a[0]] = $a;
		}
	}
	fclose($fh);
	return $r;
}



function getEndpoints($filename){
	global $options;
	//return an array with the endpoint information
	$a = array();
	$fh = fopen($filename, "r") or die("Could not open file: filename!".PHP_EOL);
	if($fh){
		while(($l =  fgets($fh, 4096)) !== false){
			if(!(preg_match('/^\s*#.*$/',$l))){
				$al = trim($l);
				if(strlen($al)){
					$tal = explode("\t", $al);
					$info = array();
					if(isset($tal[0])){
						$info['isql_port'] = $tal[0];
					}
					if(isset($tal[1])){
						$info['http_port'] = $tal[1];
					}
					if(isset($tal[2])){
						$info['ns'] = $tal[2];
					}

					if($options['s'] && $options['s'] != $info['ns']) continue;

					if(strlen($info['http_port']) && strlen($info['ns']) && strlen($info['isql_port'])){
						$url = ($options['b']=='http://ns.bio2rdf.org/sparql')?
							('http://'.$info['ns'].'.bio2rdf.org/sparql'):($options['b'].':'.$info['http_port']."/sparql");
						$a[$info['ns']] = array(
							'endpoint_url' => $url,
							'graph_uri' => "http://bio2rdf.org/bio2rdf-statistics-".$info['ns'],
							'isql_port' => $info['isql_port'],
						);
					}
				}else{
					continue;
				}
			}
		}
		fclose($fh);
	}
	return $a;
}

function makeHTML($endpoint_stats, $endpoint_desc, $output_dir){
	global $options;
	//create one html file per endpoint
	foreach($endpoint_stats as $endpoint => $d){
		if(count($d) > 2){
			$desc = @$endpoint_desc[$endpoint];
			//create an output file
			$fo = fopen($output_dir.$endpoint.".html", "w") or die("Could not create file!");
			if($fo){
				$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html>';
				$html .= addHeader($endpoint);
				$html .= "<body>";
				$html .= addBio2RDFLogo();
				$html .= "<div id='description'>";
				$html .= addDatasetDescription($desc);
				$html .= addBio2RDFDetails($d['endpoint_url'], $desc['prefix']);
				$html .= "</div>";
				$html .= "<div id='container'> <div id='items'></div>";
if(isset($d['triples'])) {
				$html .= addBasicStatsTable($d['endpoint_url'],$d['triples'],$d['unique_subjects'],$d['unique_predicates'],$d['unique_objects'] , $d['unique_literals']);
				$html .= addUniqueTypesTable($d['endpoint_url'],$d['type_counts']);
				$html .= addPredicateObjLinks($d['endpoint_url'],$d['predicate_object_links']);
				$html .= addPredicateLiteralLinks($d['endpoint_url'],$d['predicate_literals']);
				$html .= addSubjectCountPredicateObjectCount($d['endpoint_url'],$d['subject_count_predicate_object_count']);
				$html .= addSubjectPredicateUniqueLits($d['endpoint_url'],$d['subject_count_predicate_literal_count']);
				$html .= addSubjectTypePredType($d['endpoint_url'],$d['subject_type_predicate_object_type']);
				$html .= addNSNSCounts($d['endpoint_url'], $d['nsnscounts']);
}
				$html .= "</div></body></html>";
				fwrite($fo, $html);
			}
			fclose($fo);
		}
	}
}

function addBio2RDFDetails($u, $ns){
	global $options;
	$fct = substr($u,0, strpos($u,"/sparql"))."/fct";
	$rm = "";
	if($u != null && $ns != null){
		$rm .= "<p><strong>SPARQL Endpoint URL:</strong> <a href=\"$u\">$u</a></p>";
		$rm .= "<p><strong>Faceted Browser URL:</strong> <a href=\"$fct\">$fct</a></p>";
		$rm .= "<p><strong>Conversion Script URL:</strong> <a href=\"http://github.com/bio2rdf/bio2rdf-scripts/tree/master/".$ns."\">http://github.com/bio2rdf/bio2rdf-scripts/tree/master/".$ns."</a></p>";
		$rm .= "<p><strong>Download URL:</strong> <a href=\"http://download.bio2rdf.org/release/".$options['r']."/".$ns."\">http://download.bio2rdf.org/release/".$options['r']."/".$ns."</a></p>";
	}
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


function addBio2RDFLogo(){
	global $options;
	$rm = "";
	$rm .= '<div id="logo">
				<a  href="http://bio2rdf.org"><img src="https://googledrive.com/host/0B3GgKfZdJasrRnB0NDNNMFZqMUk/bio2rdf_logo.png" alt="Bio2RDF logo" /></a>
			</div>';
	$rm .= '<div id ="link">';
	$rm .= "<h1>Linked Data for the Life Sciences</h1>".PHP_EOL;
	$rm .= '<h2>-Release '.$options['r'].'-</h2>';
	$rm .= '<h2>[<a href="http://bio2rdf.org" target="_blank">website</a>][<a href="http://download.bio2rdf.org/release/'.$options['r'].'/release.html" target="_blank">datasets</a>][<a href="http://github.com/bio2rdf/bio2rdf-scripts/wiki" target="_blank">documentation</a>]</h2>';
	$rm .= "</div>";
	return $rm;
}


function addNSNSCounts($eURL, $arr){
	$rm = "<hr><h2>Inter and Intra dataset links</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>dataset</th><th>dataset</th><th>Counts</th></tr></thead><tbody>";
	foreach($arr as $p => $c){
		$rm .= "<tr><td>".$c['ns1']."</td><td>".$c['ns2']."</td><td>".$c['count']."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addPredicateObjLinks($eURL, $predArr){
	$rm = "<hr><h2>Unique predicate-object pairs</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Predicate URI</th><th>Object Count</th></tr></thead><tbody>";
	foreach($predArr as $p => $c){
		$rm .= "<tr><td><a href='".makeFCTURL($eURL,$p)."'>".$p."</a></td><td>".$c."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addSubjectPredicateUniqueLits($eURL, $arr){
	$rm = "<hr><h2>Unique subject-predicate-unique literal links</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Subject Count</th><th>Predicate URI</th><th>Literal Count</th></tr></thead><tbody>";
	foreach($arr as $x => $y){
		$rm .= "<tr><td>".$y['subject_count']."</td><td><a href='".makeFCTURL($eURL,$x)."'>".$x."</a></td><td>".$y['literal_count']."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addSubjectCountPredicateObjectCount($eURL,$arr){
	$rm = "<hr><h2>Unique subject-predicate-unique object links</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Subject Count</th><th>Predicate URI</th><th>Object Count</th></tr></thead><tbody>";
	foreach($arr as $x => $y){
		$rm .= "<tr><td>".$y['subject_count']."</td><td><a href='".makeFCTURL($eURL,$x)."'>".$x."</a></td><td>".$y['object_count']."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addSubjectTypePredType($eURL,$arr){
	$rm = "<hr><h2>Subject type-predicate-object type links</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Subject Type</th><th>Subject Count</th><th>Predicate</th><th>Object Type</th><th>Object Count</th></tr></thead><tbody>";
	foreach($arr as $x => $y){
		$rm .= "<tr><td><a href='".makeFCTURL($eURL,$y['subject_type'])."'>".$y['subject_type']."</a></td><td>".$y['subject_count']."</td><td><a href='".makeFCTURL($eURL,$x)."'>".$x."</a></td><td><a href='".makeFCTURL($eURL,$y['object_type'])."'>".$y['object_type']."</a></td><td>".$y['object_count']."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addPredicateLiteralLinks($eURL,$predLitArr){
	$rm = "<hr><h2>Unique predicate-literal pairs</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Predicate URI</th><th>Literal Count</th></tr></thead><tbody>";
	foreach($predLitArr as $p => $c){
		$rm .= "<tr><td><a href='".makeFCTURL($eURL,$p)."'>".$p."</a></td><td>".$c."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addUniqueTypesTable($endpointURL, $typeArray){
	$rm = "<hr><h2>Types</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Type URI</th><th>Count</th></tr></thead><tbody>";
	foreach($typeArray as $t => $c){
		$rm .= "<tr><td><a href='".makeFCTURL($endpointURL,$t)."'>".$t."</a></td><td>".$c."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addBasicStatsTable($endpoint_url, $numOfTriples, $unique_subjects, $unique_predicates, $unique_objects, $unique_literals){
	$rm ="<h2>Basic data metrics</h2><table><thead><th></th><th></th></thead><tbody>";
	$rm .= "<tr><td>Triples</td><td>".$numOfTriples."</td></tr>";
	$rm .= "<tr><td>Unique Subjects</td><td>".$unique_subjects."</td></tr>";
	$rm .= "<tr><td>Unique Predicates</td><td>".$unique_predicates."</td></tr>";
	$rm .= "<tr><td>Unique Objects</td><td>".$unique_objects."</td></tr>";
	$rm .= "<tr><td>Unique Literals</td><td>".$unique_literals."</td></tr>";
	$rm .= "</tbody></table>";
	return $rm;
}
function addBody($contents){
	$rm = "<body>".$contents."</body>";
	return $rm;
}

function addHeader($aTitle){
	$rm = "<head>";
	if(strlen($aTitle)){
		$rm .= "<title> Summary data metrics for the Bio2RDF ".$aTitle." endpoint</title>";
	}
	//add css
	$rm .= '<link rel="stylesheet" type="text/css" href="http://download.bio2rdf.org/lib/datatables/css/jquery.dataTables.css">';
	$rm .= '<link rel="stylesheet" type="text/css" href="http://download.bio2rdf.org/lib/datatables/css/stoc.css">';
	$rm .= '<link rel="stylesheet" type="text/css" href="http://download.bio2rdf.org/lib/datatables/css/code.css">';
	$rm .= '<style>
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
	$rm .= '<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>';
	$rm .= '<script type="text/javascript" src="http://download.bio2rdf.org/lib/datatables/js/jquery.stoc.js"></script>';
	$rm .= '<script type="text/javascript" src="http://download.bio2rdf.org/lib/datatables/js/jquery.dataTables.js"></script>
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
		</script>';
return $rm."</head>";
}

/**
This function modifies the $endpoint_arr and adds each 
of the statistics found here https://github.com/bio2rdf/bio2rdf-scripts/wiki/Bio2RDF-Dataset-Metrics
to the array
**/
function retrieveStatistics(&$endpoints)
{
	foreach($endpoints as $name => $details) {
		echo "\nprocessing $name";
		$e = $details["endpoint_url"];
		$g = $details["graph_uri"];
		if(!strlen($e)) {
			trigger_error("Invalid endpoint_url $e",E_USER_ERROR);
			continue;
		}
		if(!strlen($g)) {
			trigger_error("Invalid graph uri $g",E_USER_ERROR);
			continue;
		} 
		$d = getDatasetGraphUri($e);
		if($d == null) {
			trigger_error("unable to get dataset graph uri");
			continue;
		}
		//numOfTriples
		$endpoints[$name]["triples"] = getTriples($e,$g,$d);
		$endpoints[$name]["distinct_entities"]   = getDistinctSubjects($e,$g,$d);
		$endpoints[$name]["distinct_subjects"]   = getDistinctSubjects($e,$g,$d);
		$endpoints[$name]["distinct_predicates"] = getDistinctPredicates($e,$g,$d);
		$endpoints[$name]["distinct_objects"]    = getDistinctObjects($e,$d);
		$endpoints[$name]["distinct_literals"]   = getDistinctLiterals($e,$d);
		$endpoints[$name]["type_counts"]         = getDistinctTypes($e,$d);
		$endpoints[$name]["pred_counts"]         = getPredicateCounts($e,$d);
		$endpoints[$name]["predicate_object_links"] = getPredObjFreq($e,$d);
		$endpoints[$name]["predicate_literals"]      = getPredLitLinks($e,$d);
		$endpoints[$name]["subject_count_predicate_literal_count"] = getSubPredLitLinks($e,$d);
		$endpoints[$name]["subject_count_predicate_object_count"] = getSubPredObjLinks($e,$d);
		$endpoints[$name]["subject_type_predicate_object_type"] = getSubTypePredObjType($e,$d);
		$endpoints[$name]["nsnscounts"] = getNSNSCounts($e,$d);
	}
	return $endpoints;
}

function getTriples($e,$g)
{
	$q = 'SELECT * WHERE{ ?d <http://rdfs.org/ns/void#triples> ?v }';

	$ret = file_get_contents($q);
	$decoded = json_decode($ret);
	$count = -1;
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		if(isset($results_raw->bindings[0])){
			$count = $results_raw->bindings[0]->v->value;
		}
	}
	return $count;
}

function getDistinctEntities($e,$g)
{
	$q = 'SELECT * WHERE{ ?d <http://rdfs.org/ns/void#entities> ?v }';

	$ret = file_get_contents($q);
	$decoded = json_decode($ret);
	$count = -1;
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		if(isset($results_raw->bindings[0])){
			$count = $results_raw->bindings[0]->v->value;
		}
	}
	return $count;
}


function getDistinctSubjects($g)
{
	$q = 'SELECT * WHERE{ ?d <http://rdfs.org/ns/void#entities> ?v }';

	$ret = file_get_contents($q);
	$decoded = json_decode($ret);
	$count = -1;
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		if(isset($results_raw->bindings[0])){
			$count = $results_raw->bindings[0]->v->value;
		}
	}
	return $count;
}






///////
function getNSNSCounts($e,$d){
	$aJSON = file_get_contents(getQueryURL($e,nsQ($d)));
	$decoded = json_decode($aJSON);

	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->pred->value;
			$sT = $ab->sT->value;
			$oT = $ab->oT->value;
			$count = $ab->triples->value;
			$rm[$aP] = array(
				'count' => $count,
				'ns1' => $sT,
				'ns2' => $oT,
			);
		}
	}
	return $rm;
}

function getSubTypePredObjType($e,$d){
	$aJSON = file_get_contents(getQueryURL($e,q10($d)));
	$decoded = json_decode($aJSON);
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->pred->value;
			$oCount = $ab->oc->value;
			$objType = $ab->objectType->value;
			$subCount = $ab->sc->value;
			$subType = $ab->subjectType->value;
			$returnMe[$aP]["object_type"] = $objType;
			$returnMe[$aP]["object_count"] = $oCount;
			$returnMe[$aP]["subject_type"] = $subType;
			$returnMe[$aP]["subject_count"] = $subCount;
		}
	}
	return $returnMe;
}
function getSubPredLitLinks($e,$d){
	$aJSON = file_get_contents(getQueryURL($e,q9($d)));
	$decoded = json_decode($aJSON);

	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->pred->value;
			$sC = $ab->sc->value;
			$lC = $ab->lc->value;
			$returnMe[$aP]["subject_count"] = $sC;
			$returnMe[$aP]["literal_count"] = $lC;
		}
	}
	return $returnMe;
}
function getSubPredObjLinks($e,$d){
	$aJSON = file_get_contents(getQueryURL($e,q8($d)));
	$decoded = json_decode($aJSON);
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->pred->value;
			$oC = $ab->oc->value;
			$sC = $ab->sc->value;
			$returnMe[$aP]["object_count"] = $oC;
			$returnMe[$aP]["subject_count"] = $sC;
		}
	}
	return $returnMe;
}
function getPredLitLinks($e,$d){
	$aJSON = file_get_contents(getQueryURL($e,q7($d)));
	$decoded = json_decode($aJSON);

	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->pred->value;
			$count = $ab->lc->value;
			$returnMe[$aP] = $count;
		}
	}
	return $returnMe;
}
function getPredObjFreq($e,$d){
	$aJSON = file_get_contents(getQueryURL($e,q6($d)));
	$decoded = json_decode($aJSON);
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->pred->value;
			$count = $ab->oc->value;
			$returnMe[$aP] = $count;
		}
	}
	return $returnMe;
}

function getNumOfTypes($e,$d){
	$ret = file_get_contents(getQueryURL($e,q5($d)));
	$decoded = json_decode($ret);
	$returnMe = '';
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aT = $ab->type->value;
			$count = $ab->tc->value;
			$returnMe[$aT] = $count;
		}
	}
	return $returnMe;
}
function getNumOfObjects($e,$d){
	$ret = file_get_contents(getQueryURL($e,q4($d)));
	$decoded = json_decode($ret);
	$count = -1;
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		if(isset($results_raw->bindings[0])){
			$count = $results_raw->bindings[0]->oc->value;
		}else{
			$count = -1;
		}
	}
	return $count;
}
function getNumOfPredicates($e,$d){
	$ret = file_get_contents(getQueryURL($e,q3($d)));
	$decoded = json_decode($ret);
	$count = -1;
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		if(isset($results_raw->bindings[0])){
			$count = $results_raw->bindings[0]->pc->value;
		}else{
			$count = -1;
		}
	}
	return $count;
}
function getNumOfSubjects($e,$d){
	$ret = file_get_contents(getQueryURL($e,q2($d)));
	$decoded = json_decode($ret);
	$count = -1;
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		if(isset($results_raw->bindings[0])){
			$count = $results_raw->bindings[0]->sc->value;
		}else{
			$count = -1;
		}
	}
	return $count;
}
function getDate2($aJSON){
	$decoded = json_decode($aJSON);
	$count = -1;
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		if(isset($results_raw->bindings[0])){
			$count = $results_raw->bindings[0]->date->value;
		}else{
			$count = -1;
		}
	}
	return $count;
}


function getNumOfUniqueLiterals($e,$d){
	$aJSON = file_get_contents(getQueryURL($e,q14($d)));
	$decoded = json_decode($aJSON);
	$count = -1;
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		if(isset($results_raw->bindings[0])){
			$count = $results_raw->bindings[0]->lc->value;
		}else{
			$count = -1;
		}
	}
	return $count;
}

function getPredicateCounts($e,$d){
	$aJSON = file_get_contents(getQueryURL($e,q15($d)));
	$decoded = json_decode($aJSON);
	
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->pred->value;
			$count = $ab->pc->value;
			$returnMe[$aP] = $count;
		}
	}
	return $returnMe;

}



function getDatasetGraphUri($endpoint_url)
{
	if(strlen($endpoint_url) == 0) {
		trigger_error("Invalid endpoint URL");
		return null;
	}
	$g = "test-stats";
	$q = "select ?g from <$g> where { graph ?g {?x a ?y.} } LIMIT 1";
	$url = $endpoint_url."?default-graph-uri=&query=".urlencode($q)."&format=json";
	
	$ret = @file_get_contents($url);
	if($ret === FALSE) {
		// some connection error
		trigger_error("unable to get dataset graph URI",E_USER_WARNING);
		return null;
	}
	$decoded = json_decode(trim($ret));
	$g = '';
	if(isset($decoded->results->bindings[0])){

	} else {
		$q = "select ?g where { graph ?g {?x a ?y.} FILTER regex(?g,'bio2rdf.dataset') } LIMIT 1";
		$url = $endpoint_url."?default-graph-uri=&query=".urlencode($q)."&format=json";
		$ret = @file_get_contents($url);
		$decoded = json_decode(trim($ret));
		if(!isset($decoded->results->bindings[0])){
			trigger_error("no graphs found in $endpoint_url",E_USER_ERROR);
			return null;
		}
	}
	$rr = $decoded->results->bindings;
	$g = $rr[0]->g->value;

	return $g;
}

function getQueryURL($endpoint_url, $query)
{
	return $endpoint_url."?default-graph-uri=&query=".urlencode($query)."&format=json";
}	


function q2($graph_url){
	return 'SELECT * FROM <'.$graph_url.'> WHERE{ ?dataset <http://rdfs.org/ns/void#distinctSubjects> ?sc. FILTER regex(?dataset,"bio2rdf.dataset")}';
}
function q3($graph_url){
	return  'SELECT * FROM <'.$graph_url.'> WHERE{ ?dataset <http://rdfs.org/ns/void#properties> ?pc. FILTER regex(?dataset,"bio2rdf.dataset")}';
}
function q4($graph_url){
	return 'SELECT * FROM <'.$graph_url.'> WHERE{ ?dataset <http://rdfs.org/ns/void#distinctObjects> ?oc. FILTER regex(?dataset,"bio2rdf.dataset")}'; 
}

function q14($graph_url){
	$t = "SELECT * FROM <$graph_url> WHERE { ?dataset <http://rdfs.org/ns/void#classPartition> ?partition . ";
//	$t .= "?partition a <http://bio2rdf.org/bio2rdf.dataset:Dataset-Literal-Count> .";
	$t .= "?partition <http://rdfs.org/ns/void#class> <http://www.w3.org/2000/01/rdf-schema#Literal> .";
	$t .= "?partition <http://rdfs.org/ns/void#entities> ?lc .";
	$t .= ' FILTER regex (?dataset, "bio2rdf.dataset")}';
	return $t;
}

function q5($graph_url){
	$t = "SELECT * FROM <".$graph_url."> WHERE { ?dataset <http://rdfs.org/ns/void#classPartition> ?partition ."; 
//	$t .= "?partition a <http://bio2rdf.org/bio2rdf.dataset:Dataset-Type-Count> .";
	$t .= "?partition <http://rdfs.org/ns/void#class> ?type .";
	$t .= "?partition <http://rdfs.org/ns/void#entities> ?tc .";
	$t .= ' FILTER regex (?dataset, "bio2rdf.dataset")';
//	$t .= ' FILTER (?c != <http://www.w3.org/2000/01/rdf-schema#Literal)';
	$t .= '}';
	return $t;
}

function q15($graph_url){
	$t = "SELECT * FROM <".$graph_url."> WHERE { ?dataset <http://rdfs.org/ns/void#propertyPartition> ?partition ."; 
//	$t .= "?partition a <http://rdfs.org/ns/void#Dataset> .";
	$t .= "?partition <http://rdfs.org/ns/void#property> ?pred .";
	$t .= "?partition <http://rdfs.org/ns/void#entities> ?pc .";
	$t .= ' FILTER regex (?dataset, "bio2rdf.dataset")}';
	return $t;
}

function q6($graph_url){
	$t = "SELECT * FROM <".$graph_url."> WHERE { "; 
	$t .= "?linkset a <http://rdfs.org/ns/void#LinkSet>.";
	$t .= "?linkset <http://rdfs.org/ns/void#target> ?dataset .";
	$t .= "?linkset <http://rdfs.org/ns/void#linkPredicate> ?pred .";
	$t .= "?linkset <http://rdfs.org/ns/void#objectsTarget> ?ot .";
	$t .= "?ot <http://rdfs.org/ns/void#class> <http://www.w3.org/2000/01/rdf-schema#Resource> .";
	$t .= "?ot <http://rdfs.org/ns/void#entities> ?oc .";
	$t .= ' FILTER regex (?dataset, "bio2rdf.dataset")}';
	return $t;
}
function q7($graph_url){
	$t = "SELECT * FROM <".$graph_url."> WHERE { "; 
	$t .= "?linkset a <http://rdfs.org/ns/void#LinkSet>.";
	$t .= "?linkset <http://rdfs.org/ns/void#target> ?dataset .";
	$t .= "?linkset <http://rdfs.org/ns/void#linkPredicate> ?pred .";
	$t .= "?linkset <http://rdfs.org/ns/void#objectsTarget> ?ot .";
	$t .= "?ot <http://rdfs.org/ns/void#class> <http://www.w3.org/2000/01/rdf-schema#Literal> .";
	$t .= "?ot <http://rdfs.org/ns/void#entities> ?lc .";
	$t .= ' FILTER regex (?dataset, "bio2rdf.dataset")}';
	return $t;
}
function q8($graph_url){
	$t = "SELECT * FROM <".$graph_url."> WHERE { "; 
	$t .= "?linkset <http://rdfs.org/ns/void#target> ?dataset .";
	$t .= "?linkset <http://rdfs.org/ns/void#subjectsTarget> ?sT .";
	$t .= "?sT <http://rdfs.org/ns/void#entities> ?sc .";
	$t .= "?linkset <http://rdfs.org/ns/void#objectsTarget> ?oT .";
	$t .= "?oT <http://rdfs.org/ns/void#class> <http://www.w3.org/2000/01/rdf-schema#Resource> .";
	$t .= "?oT <http://rdfs.org/ns/void#entities> ?oc .";
	$t .= "?linkset <http://rdfs.org/ns/void#linkPredicate> ?pred .";
	$t .= ' FILTER regex (?dataset, "bio2rdf.dataset")}';
	return $t;
}
function q9($graph_url){
	$t = "SELECT * FROM <".$graph_url."> WHERE { "; 
	$t .= "?linkset <http://rdfs.org/ns/void#target> ?dataset .";
	$t .= "?linkset <http://rdfs.org/ns/void#subjectsTarget> ?sT .";
	$t .= "?sT <http://rdfs.org/ns/void#entities> ?sc .";
	$t .= "?linkset <http://rdfs.org/ns/void#objectsTarget> ?oT .";
	$t .= "?oT <http://rdfs.org/ns/void#class> <http://www.w3.org/2000/01/rdf-schema#Literal> .";
	$t .= "?oT <http://rdfs.org/ns/void#entities> ?lc .";
	$t .= "?linkset <http://rdfs.org/ns/void#linkPredicate> ?pred .";
	$t .= ' FILTER regex (?dataset, "bio2rdf.dataset")}';
	return $t;
}
function q10($graph_url){
	$t = "SELECT * FROM <".$graph_url."> WHERE { "; 
	$t .= "?linkset <http://rdfs.org/ns/void#target> ?dataset .";
	$t .= "?linkset <http://rdfs.org/ns/void#subjectsTarget> ?sT .";
	$t .= "?sT <http://rdfs.org/ns/void#entities> ?sc .";
	$t .= "?sT <http://rdfs.org/ns/void#class> ?subjectType .";
	$t .= "?linkset <http://rdfs.org/ns/void#objectsTarget> ?oT .";
	$t .= "?oT <http://rdfs.org/ns/void#class> ?objectType .";
	$t .= "?oT <http://rdfs.org/ns/void#entities> ?oc .";
	$t .= "?linkset <http://rdfs.org/ns/void#linkPredicate> ?pred .";
	$t .= ' FILTER regex (?dataset, "bio2rdf.dataset")}';
	return $t;
}

function nsQ($graph_url){
	$t = "SELECT * FROM <$graph_url> WHERE { "; 
	$t .= "?linkset <http://rdfs.org/ns/void#target> ?dataset .";
	$t .= "?linkset <http://rdfs.org/ns/void#subjectsTarget> ?sT .";
	$t .= "?linkset <http://rdfs.org/ns/void#objectsTarget> ?oT .";
	$t .= "?linkset <http://rdfs.org/ns/void#linkPredicate> ?pred .";
	$t .= "?linkset <http://rdfs.org/ns/void#triples> ?triples.";
	$t .= ' FILTER regex (?dataset, "bio2rdf.dataset")}';
	return $t;
}

function getDatasetDateQuery($endpoint_url){
	$t = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT DISTINCT ?date";
	$t .= " WHERE { ?d a data_vocab:Endpoint. ?d data_vocab:has_url ?u. ?p <http://rdfs.org/ns/void#sparqlEndpoint> ?u. ?p <http://purl.org/dc/terms/created> ?date.}";
	return $t;
}

function makeFCTURL($endpointURL, $aURL){
	//remove sparql and replace with describe
	$url = str_replace("sparql", "describe", $endpointURL);
	$url .= "/?url=".urlencode($aURL);
	return $url;
}
?>
