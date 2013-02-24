<?php
/**
Copyright (C) 2012 Jose Cruz-Toledo

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
This script generates an HTML page summarizing the details for all Bio2RDF endpoints.
It reads in an instances.tab file as used by our servers
**/
/*
$options = array(
	"instances_file" => "instances/file/path/",
);

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

if($options['instances_file'] == 'instances/file/path/'){
	echo "** Please specify a valid instances file **".PHP_EOL;
	exit;
}
*/

/********************/
/** FUNCTION CALLS **/
/********************/

//$endpoints =  makeEndpoints($options['instances_file']);
$endpoints = makeEndpoints('/tmp/instances.tab');
print_r($endpoints);
//$endpoint_stats = retrieveStatistics($endpoints);
//makeHTML($endpoint_stats);




/***************/
/** FUNCTIONS **/
/***************/
function makeEndpoints ($aFileName){
	//return an array with the endpoint information
	$returnMe = array();
	$fh = fopen($aFileName, "r") or die("Could not open file: ".$aFileName."!\n");
	if($fh){
		while(($aLine =  fgets($fh, 4096)) !== false){
			if(!(preg_match('/^\s*#.*$/',$aLine))){
				$al = trim($aLine);
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
					if(strlen($info['http_port']) && strlen($info['ns']) && strlen($info['isql_port'])){
						$returnMe[$info['ns']] = array(
							'endpoint_url' => 'http://cu.'.$info['ns'].".bio2rdf.org/sparql",
							'graph_uri' => "http://bio2rdf.org/bio2rdf-".$info['ns']."-statistics",
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
	return $returnMe;

}

function makeEndpointsTmp ($aFileName){
	//return an array with the endpoint information
	$returnMe = array();
	$fh = fopen($aFileName, "r") or die("Could not open file: ".$aFileName."!\n");
	if($fh){
		while(($aLine =  fgets($fh, 4096)) !== false){
			if(!(preg_match('/^\s*#.*$/',$aLine))){
				$al = trim($aLine);
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
					if(strlen($info['http_port']) && strlen($info['ns']) && strlen($info['isql_port'])){
						print_r($info);
					}
				}else{
					continue;
				}
			}
		}
		fclose($fh);
	}
	return $returnMe;


}

function makeHTML($endpoint_stats){
	//create one html file per endpoint
	foreach($endpoint_stats as $endpoint => $d){
		if(count($d) > 2){
			//create an output file
			$fo = fopen($endpoint.".html", "w") or die("Could not create file!");
			if($fo){
				$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html>';
				$html .= addHeader($endpoint);
				$html .= "<body><h1> Summary data metrics for the Bio2RDF ".$endpoint." endpoint</h1>";
				$html .= "<div id='container'> <div id='items'></div>";
				$html .= addBasicStatsTable($d['endpoint_url'],$d['triples'],$d['unique_subjects'],$d['unique_predicates'],$d['unique_objects'] );
				$html .= addUniqueTypesTable($d['endpoint_url'],$d['unique_types']);
				$html .= addPredicateObjLinks($d['endpoint_url'],$d['predicate_object_links']);
				$html .= addPredicateLiteralLinks($d['endpoint_url'],$d['predicate_literals']);
				$html .= addSubjectCountPredicateObjectCount($d['endpoint_url'],$d['subject_count_predicate_object_count']);
				$html .= addSubjectPredicateUniqueLits($d['endpoint_url'],$d['subject_count_predicate_literal_count']);
				$html .= addSubjectTypePredType($d['endpoint_url'],$d['subject_type_predicate_object_type']);
				$html .= "</div></body></html>";
				fwrite($fo, $html);
			}
			fclose($fo);
		}
	}
}

function addPredicateObjLinks($eURL, $predArr){
	$rm = "<hr><h2>List of the unique predicate-object links and their counts</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Predicate URI</th><th>Object Count</th></tr></thead><tbody>";
	foreach($predArr as $p => $c){
		$rm .= "<tr><td><a href='".makeFCTURL($eURL,$p)."'>".$p."</a></td><td>".$c."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addSubjectPredicateUniqueLits($eURL, $arr){
	$rm = "<hr><h2>List of the total number of unique subject-predicate-unique literal links</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Subject Count</th><th>Predicate URI</th><th>Literal Count</th></tr></thead><tbody>";
	foreach($arr as $x => $y){
		$rm .= "<tr><td>".$y['subject_count']."</td><td><a href='".makeFCTURL($eURL,$x)."'>".$x."</a></td><td>".$y['literal_count']."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addSubjectCountPredicateObjectCount($eURL,$arr){
	$rm = "<hr><h2>List of the total number of unique subject-predicate-unique object links</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Subject Count</th><th>Predicate URI</th><th>Object Count</th></tr></thead><tbody>";
	foreach($arr as $x => $y){
		$rm .= "<tr><td>".$y['subject_count']."</td><td><a href='".makeFCTURL($eURL,$x)."'>".$x."</a></td><td>".$y['object_count']."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addSubjectTypePredType($eURL,$arr){
	$rm = "<hr><h2>List of the total number of subject type-predicate-object type links</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Subject Type</th><th>Subject Count</th><th>Predicate</th><th>Object Type</th><th>Object Count</th></tr></thead><tbody>";
	foreach($arr as $x => $y){
		$rm .= "<tr><td><a href='".makeFCTURL($eURL,$y['subject_type'])."'>".$y['subject_type']."</a></td><td>".$y['subject_count']."</td><td><a href='".makeFCTURL($eURL,$x)."'>".$x."</a></td><td><a href='".makeFCTURL($eURL,$y['object_type'])."'>".$y['object_type']."</a></td><td>".$y['object_count']."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addPredicateLiteralLinks($eURL,$predLitArr){
	$rm = "<hr><h2>List of the unique predicate-literal links and their counts</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Predicate URI</th><th>Literal Count</th></tr></thead><tbody>";
	foreach($predLitArr as $p => $c){
		$rm .= "<tr><td><a href='".makeFCTURL($eURL,$p)."'>".$p."</a></td><td>".$c."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addUniqueTypesTable($endpointURL, $typeArray){
	$rm = "<hr><h2> List of unique types and their frequencies</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Type URI</th><th>Count</th></tr></thead><tbody>";
	foreach($typeArray as $t => $c){
		$rm .= "<tr><td><a href='".makeFCTURL($endpointURL,$t)."'>".$t."</a></td><td>".$c."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
}
function addBasicStatsTable($endpoint_url, $numOfTriples, $unique_subjects, $unique_predicates, $unique_objects){
	$rm ="<h2>Basic data metrics</h2><table><thead><th>Metric</th><th>Value</th></thead><tbody>";
	$rm .= "<tr><td>Enpoint URL</td><td><a href=\"".$endpoint_url."\">".$endpoint_url."</a></td></tr>";
	$rm .= "<tr><td>Number of Triples</td><td>".$numOfTriples."</td></tr>";
	$rm .= "<tr><td>Unique Subject count</td><td>".$unique_subjects."</td></tr>";
	$rm .= "<tr><td>Unique Predicate count</td><td>".$unique_predicates."</td></tr>";
	$rm .= "<tr><td>Unique Object count</td><td>".$unique_objects."</td></tr>";
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
	$rm .= '<link rel="stylesheet" type="text/css" href="http://134.117.53.12/lib/datatables/css/jquery.dataTables.css">';
	$rm .= '<link rel="stylesheet" type="text/css" href="http://134.117.53.12/lib/datatables/css/stoc.css">';
	$rm .= '<link rel="stylesheet" type="text/css" href="http://134.117.53.12/lib/datatables/css/code.css">';

	//add some js
	$rm .= '<script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script>';
	$rm .= '<script type="text/javascript"  src="http://134.117.53.12/lib/datatables/js/jquery.stoc.js"></script>';
	$rm .='<script type="text/javascript"  src="http://134.117.53.12/lib/datatables/js/jquery.dataTables.js"></script>
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
function retrieveStatistics(&$endpoint_arr){
	$warn = "";
	if(count($endpoint_arr)){
		foreach($endpoint_arr as $name => $details){
			$endpoint_url = $details["endpoint_url"];
			$graph_uri = $details["graph_uri"];
			if(strlen($endpoint_url) != 0 && strlen($graph_uri) != 0){
				
				//now retrieve each of the stats
				//numOfTriples
				$numOfTriplesJson = trim(@file_get_contents(q1($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["triples"] = getNumOfTriples($numOfTriplesJson);
				//numOfSubjects
				$numOfSubjectsJson = trim(@file_get_contents(q2($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["unique_subjects"] = getNumOfSubjects($numOfSubjectsJson);
				//numOfPredicates
				$numOfPredicatesJson = trim(@file_get_contents(q3($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["unique_predicates"] = getNumOfPredicates($numOfPredicatesJson);
				//numOfUniqueObjects
				$numOfObjectsJson = trim(@file_get_contents(q4($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["unique_objects"] = getNumOfObjects($numOfObjectsJson);
				//numOfTypes
				$numOfTypesJson = trim(@file_get_contents(q5($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["unique_types"] = getNumOfTypes($numOfTypesJson);
				//unique predicate-object links and their frequencies
				$numOfPredObjectFreqsJson = trim(@file_get_contents(q6($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["predicate_object_links"] = getPredObjFreq($numOfPredObjectFreqsJson);
				//unique predicate-literal links and their frequencies
				$numOfUniquePredicateLiteralLinksandFreqsJson = trim(@file_get_contents(q7($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["predicate_literals"] = getPredLitLinks($numOfUniquePredicateLiteralLinksandFreqsJson);
				//unique subject-predicate-unique object links and their frequencies
				$numOfSubjectPredicateUniqueObjectJson = trim(@file_get_contents(q8($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["subject_count_predicate_object_count"] = getSubPredObjLinks($numOfSubjectPredicateUniqueObjectJson);
				//unique subject-predicate-unique literal links and their frequencies
				$numOfSubjectPredUniqueLitJson = trim(@file_get_contents(q9($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["subject_count_predicate_literal_count"] = getSubPredLitLinks($numOfSubjectPredUniqueLitJson);
				//unique subject type-predicate-object type links and their frequencies
				$numOfSubjectTypePredicateObjectJson = trim(@file_get_contents(q10($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["subject_type_predicate_object_type"] = getSubTypePredObjType($numOfSubjectTypePredicateObjectJson);
			}else{
				$warn .= "WARNING :: Endpoint ".$name." does not have all of required information! (missing either the endpoint or graph uri!)!\n";
			}
		}
		if(strlen($warn)){
			echo $warn;
		}
	}
	return $endpoint_arr;
}

function getSubTypePredObjType($aJSON){
	$returnMe = array();
	$decoded = json_decode($aJSON);
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->aPred->value;
			$oCount = $ab->objCount->value;
			$objType = $ab->objType->value;
			$subCount = $ab->subjectCount->value;
			$subType = $ab->subjectType->value;
			$returnMe[$aP]["object_type"] = $objType;
			$returnMe[$aP]["object_count"] = $oCount;
			$returnMe[$aP]["subject_type"] = $subType;
			$returnMe[$aP]["subject_count"] = $subCount;
		}
	}
	return $returnMe;
}
function getSubPredLitLinks($aJSON){
	$returnMe = array();
	$decoded = json_decode($aJSON);

	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->p->value;
			$sC = $ab->sc->value;
			$lC = $ab->lc->value;
			$returnMe[$aP]["subject_count"] = $sC;
			$returnMe[$aP]["literal_count"] = $lC;
		}
	}
	return $returnMe;
}
function getSubPredObjLinks($aJSON){
	$returnMe = array();
	$decoded = json_decode($aJSON);
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->aP->value;
			$oC = $ab->oC->value;
			$sC = $ab->sC->value;
			$returnMe[$aP]["object_count"] = $oC;
			$returnMe[$aP]["subject_count"] = $sC;
		}
	}
	return $returnMe;
}
function getPredLitLinks($aJSON){
	$returnMe = array();
	$decoded = json_decode($aJSON);

	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->aP->value;
			$count = $ab->aC->value;
			$returnMe[$aP] = $count;
		}
	}
	return $returnMe;
}
function getPredObjFreq($aJSON){
	$returnMe = array();
	$decoded = json_decode($aJSON);
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aP = $ab->aP->value;
			$count = $ab->aC->value;
			$returnMe[$aP] = $count;
		}
	}
	return $returnMe;
}
function getNumOfTypes($aJSON){
	$returnMe = array();
	$decoded = json_decode($aJSON);
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		foreach($results_raw->bindings as $ab){
			$aT = $ab->at->value;
			$count = $ab->tc->value;
			$returnMe[$aT] = $count;
		}
	}
	return $returnMe;
}
function getNumOfObjects($aJSON){
	$decoded = json_decode($aJSON);
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
function getNumOfPredicates($aJSON){
	$decoded = json_decode($aJSON);
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
function getNumOfSubjects($aJSON){
	$decoded = json_decode($aJSON);	
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
function getNumOfTriples($aJSON){
	$decoded = json_decode($aJSON);
	$count = -1;
	if(isset($decoded->results)){
		$results_raw = $decoded->results;
		if(isset($results_raw->bindings[0])){
			$count = $results_raw->bindings[0]->tc->value;
		}else{
			$count = -1;
		}
	}
	return $count;
}

function q1($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE {  ?endpoint a data_vocab:Endpoint. ?endpoint data_vocab:has_triple_count ?tc .}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}
function q2($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE { ?endpoint a data_vocab:Endpoint. ?endpoint data_vocab:has_unique_subject_count ?sc .}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}
function q3($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE { ?endpoint a data_vocab:Endpoint. ?endpoint data_vocab:has_unique_predicate_count ?pc .}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}
function q4($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE { ?endpoint a data_vocab:Endpoint. ?endpoint data_vocab:has_unique_object_count ?oc .}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}
function q5($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE { ?endpoint a data_vocab:Endpoint. ?endpoint <http://bio2rdf.org/dataset_vocabulary:has_type_count> ?atype . ?atype <http://bio2rdf.org/dataset_vocabulary:has_count> ?tc. ?atype <http://bio2rdf.org/dataset_vocabulary:has_type> ?at.}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}
function q6($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE { ?endpoint a data_vocab:Endpoint. ?endpoint <http://bio2rdf.org/dataset_vocabulary:has_predicate_object_count> ?anObject. ?anObject <http://bio2rdf.org/dataset_vocabulary:has_count> ?aC. ?anObject <http://bio2rdf.org/dataset_vocabulary:has_predicate> ?aP.}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}
function q7($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE { ?endpoint a data_vocab:Endpoint. ?endpoint <http://bio2rdf.org/dataset_vocabulary:has_predicate_literal_count> ?anObject. ?anObject <http://bio2rdf.org/dataset_vocabulary:has_count> ?aC. ?anObject <http://bio2rdf.org/dataset_vocabulary:has_predicate> ?aP.}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}
function q8($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE { ?endpoint a data_vocab:Endpoint. ?endpoint <http://bio2rdf.org/dataset_vocabulary:has_predicate_unique_subject_unique_object_count> ?anObject . ?anObject <http://bio2rdf.org/dataset_vocabulary:has_predicate> ?aP. ?anObject <http://bio2rdf.org/dataset_vocabulary:has_object_count> ?oC . ?anObject <http://bio2rdf.org/dataset_vocabulary:has_subject_count> ?sC .}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}
function q9($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE {  ?endpoint a data_vocab:Endpoint. ?endpoint <http://bio2rdf.org/dataset_vocabulary:has_predicate_unique_subject_unique_literal_count> ?anObj. ?anObj <http://bio2rdf.org/dataset_vocabulary:has_predicate> ?p. ?anObj <http://bio2rdf.org/dataset_vocabulary:has_subject_count> ?sc. ?anObj <http://bio2rdf.org/dataset_vocabulary:has_literal_count> ?lc.}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}
function q10($endpoint_url, $graph_url){
	$returnMe = "";
	if(strlen($endpoint_url) != 0 && strlen($graph_url)){
		$template = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT * FROM <";
		$template .= $graph_url."> WHERE { ?endpoint a data_vocab:Endpoint. ?endpoint <http://bio2rdf.org/dataset_vocabulary:has_type_relation_type_count> ?anObj. ?anObj <http://bio2rdf.org/dataset_vocabulary:has_subject_type> ?subjectType. ?anObj <http://bio2rdf.org/dataset_vocabulary:has_subject_count> ?subjectCount. ?anObj <http://bio2rdf.org/dataset_vocabulary:has_predicate> ?aPred. ?anObj <http://bio2rdf.org/dataset_vocabulary:has_object_count> ?objCount. ?anObj <http://bio2rdf.org/dataset_vocabulary:has_object_type> ?objType.}";
		$returnMe .= $endpoint_url."?default-graph-uri=&query=".urlencode($template)."&format=json";
		return $returnMe;
	}else{
		return false;
	}
}

function makeFCTURL($endpointURL, $aURL){
	//remove sparql and replace with describe
	$url = str_replace("sparql", "describe", $endpointURL);
	$url .= "/?url=".urlencode($aURL);
	return $url;
}
?>