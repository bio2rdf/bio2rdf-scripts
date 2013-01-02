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
			"sparql_endpoint_url" => "http://s4.semanticscience.org:16002/sparql",
			"statistics_graph_uri" => "",
		),
	"atlas" => array(
			"sparql_endpoint_url" => "http://s4.semanticscience.org:16052/sparql",
			"statistics_graph_uri" => "http://bio2rdf.org/bio2rdf-atlas-statistics",
		),
	"biomodels" => array(
			"sparql_endpoint_url" => "http://s4.semanticscience.org:16041/sparql",
			"statistics_graph_uri" => "http://bio2rdf.org/bio2rdf-biomodels-statistics",
		),
	"bioportal" => array(
			"sparql_endpoint_url" => "http://s4.semanticscience.org:16017/sparql",
			"statistics_graph_uri" => "http://bio2rdf.org/bio2rdf-bioportal-statistics",
		),

);

/********************/
/** FUNCTION CALLS **/
/********************/
$q = makeNumberOfUniqueSubjectsQueryURL("http://s4.semanticscience.org:16052/sparql", "http://bio2rdf.org/bio2rdf-atlas-statistics");
echo $q."\n";


/***************/
/** FUNCTIONS **/
/***************/

function makeNumberOfTriplesQueryURL($endpoint_url, $graph_url){
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
function makeNumberOfUniqueSubjectsQueryURL($endpoint_url, $graph_url){
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
function makeNumberOfUniquePredicatesQueryURL($endpoint_url, $graph_url){
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

?>