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
This script generates an HTML page summarizing the details for all Bio2RDF endpoints
**/

$endpoints = array(
	"affymetrix" => array(
		"endpoint_url" => "http://s4.semanticscience.org:16002/sparql",
		"graph_uri" => "",
		),
	"atlas" => array(
		"endpoint_url" => "http://s4.semanticscience.org:16052/sparql",
		"graph_uri" => "http://bio2rdf.org/bio2rdf-atlas-statistics",
		),
	"biomodels" => array(
		"endpoint_url" => "http://s4.semanticscience.org:16041/sparql",
		"graph_uri" => "http://bio2rdf.org/bio2rdf-biomodels-statistics",
		),
	"bioportal" => array(
		"endpoint_url" => "http://s4.semanticscience.org:16017/sparql",
		"graph_uri" => "http://bio2rdf.org/bio2rdf-bioportal-statistics",
		),

	);

/********************/
/** FUNCTION CALLS **/
/********************/
$endpoint_stats = retrieveStatistics($endpoints);
makeHTML($endpoint_stats);




/***************/
/** FUNCTIONS **/
/***************/

function makeHTML($endpoint_stats){
	//create one html file per endpoint
	foreach($endpoint_stats as $endpoint => $d){
		if(count($d) > 2){
			//create an output file
			$fo = fopen($endpoint.".html", "w") or die("Could not create file!");
			if($fo){
				$html = "<html>";
				$html .= addHeader($endpoint);
				$html .= "<h1>".$endpoint."</h1>";
				$html .= addBasicStatsTable($d['endpoint_url'],$d['triples'],$d['unique_subjects'],$d['unique_predicates'],$d['unique_objects'] );
				$html .= "</html>";
				fwrite($fo, $html);
			}
			fclose($fo);
		}
	}
}

function addBasicStatsTable($endpoint_url, $numOfTriples, $unique_subjects, $unique_predicates, $unique_objects){
	$rm ="<table>";
	$rm .= "<tr><td>Enpoint URL</td><td><a href=\"".$endpoint_url."\">".$endpoint_url."</a></td></tr>";
	$rm .= "<tr><td>Number of Triples</td><td>".$numOfTriples."</td></tr>";
	$rm .= "<tr><td>Unique Subject count</td><td>".$unique_subjects."</td></tr>";
	$rm .= "<tr><td>Unique Predicate count</td><td>".$unique_predicates."</td></tr>";
	$rm .= "<tr><td>Unique Object count</td><td>".$unique_objects."</td></tr>";
	$rm .= "</table>";
	return $rm;
}
function addBody($contents){
	$rm = "<body>".$contents."</body>";
	return $rm;
}

function addHeader($aTitle){
	$rm = "<head>";
	if(strlen($aTitle)){
		$rm .= "<title> Endpoint statistics for ".$aTitle."</title>";
	}
	return $rm."</head>";
}

/**
This function modifies the $endpoint_arr and adds each 
of the statistics found here https://github.com/bio2rdf/bio2rdf-scripts/wiki/Bio2RDF-Dataset-Metrics
to the array
**/
function retrieveStatistics(&$endpoint_arr){
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
			}
		}
	}
	return $endpoint_arr;
}

function getSubTypePredObjType($aJSON){
	$retrunMe = array();
	$decoded = json_decode($aJSON);
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
	return $returnMe;
}
function getSubPredLitLinks($aJSON){
	$retrunMe = array();
	$decoded = json_decode($aJSON);
	$results_raw = $decoded->results;
	foreach($results_raw->bindings as $ab){
		$aP = $ab->p->value;
		$sC = $ab->sc->value;
		$lC = $ab->lc->value;
		$returnMe[$aP]["subject_count"] = $sC;
		$returnMe[$aP]["literal_count"] = $lC;
	}
	return $returnMe;
}
function getSubPredObjLinks($aJSON){
	$retrunMe = array();
	$decoded = json_decode($aJSON);
	$results_raw = $decoded->results;
	foreach($results_raw->bindings as $ab){
		$aP = $ab->aP->value;
		$oC = $ab->oC->value;
		$sC = $ab->sC->value;
		$returnMe[$aP]["object_count"] = $oC;
		$returnMe[$aP]["subject_count"] = $sC;
	}
	return $returnMe;
}
function getPredLitLinks($aJSON){
	$returnMe = array();
	$decoded = json_decode($aJSON);
	$results_raw = $decoded->results;
	foreach($results_raw->bindings as $ab){
		$aP = $ab->aP->value;
		$count = $ab->aC->value;
		$returnMe[$aP] = $count;
	}
	return $returnMe;
}
function getPredObjFreq($aJSON){
	$returnMe = array();
	$decoded = json_decode($aJSON);
	$results_raw = $decoded->results;
	foreach($results_raw->bindings as $ab){
		$aP = $ab->aP->value;
		$count = $ab->aC->value;
		$returnMe[$aP] = $count;
	}
	return $returnMe;
}
function getNumOfTypes($aJSON){
	$returnMe = array();
	$decoded = json_decode($aJSON);
	$results_raw = $decoded->results;
	foreach($results_raw->bindings as $ab){
		$aT = $ab->at->value;
		$count = $ab->tc->value;
		$returnMe[$aT] = $count;
	}
	return $returnMe;
}
function getNumOfObjects($aJSON){
	$decoded = json_decode($aJSON);
	$results_raw = $decoded->results;
	$count = $results_raw->bindings[0]->oc->value;
	return $count;
}
function getNumOfPredicates($aJSON){
	$decoded = json_decode($aJSON);
	$results_raw = $decoded->results;
	$count = $results_raw->bindings[0]->pc->value;
	return $count;
}
function getNumOfSubjects($aJSON){
	$decoded = json_decode($aJSON);
	$results_raw = $decoded->results;
	$count = $results_raw->bindings[0]->sc->value;
	return $count;
}
function getNumOfTriples($aJSON){
	$decoded = json_decode($aJSON);
	$results_raw = $decoded->results;
	$count = $results_raw->bindings[0]->tc->value;
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

?>