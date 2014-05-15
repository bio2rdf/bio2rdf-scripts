<?php

/**
Copyright (C) 2012 Alison Callahan, Jose Cruz-Toledo, Michel Dumontier

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
 * Script to query a specified Bio2RDF Virtuoso SPARQL endpoint 
 * (identified by endpoint URL but accessed via its iSQL port) 
 * for dataset statistics and serialize statistics in RDF
*/

//command line options
$options = array(
 "port" => "1111",
 "user" => "dba",
 "pass" => "dba",
 "odir" => "/data/rdf/statistics/",
 "ofile" => "endpoint.statistics",
 "isql" => "/usr/local/virtuoso-opensource/bin/isql",
 "graphs" => "",
 "dataset" => "",
 "instance" => "",
 "quad_uri" => "",
 "version" => "3"
);

// show command line options
if($argc == 1) {
 echo "Usage: php $argv[0] ".PHP_EOL;
 foreach($options AS $key => $value) {
  echo " $key=$value ".PHP_EOL;
 }
}

// set options from user input
foreach($argv AS $i => $arg) {
 if($i==0) continue;
 $b = explode("=",$arg);
 if(isset($options[$b[0]])) $options[$b[0]] = $b[1];
 else {echo "unknown key $b[0]";exit;}
}

// set the right isql
if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
 $isql_windows = "/software/virtuoso-opensource/bin/isql.exe";
 $options['isql'] = $isql_windows;
}
if(!file_exists($options['isql'])) {
	trigger_error("ISQL could not be found at ".$options['isql'],E_USER_ERROR);
}

@mkdir($options['odir']);

if($options['instance']) {
	$file = "instances.tab";
	$fp = fopen($file,"r");
	if(!$fp) {trigger_error("Unable to open $file",USER_ERROR);exit;}
	fgets($fp); // header
	while($l = fgets($fp)) {
		$a = explode("\t",trim($l));
		if(isset($a[2])) {
			$name = $a[2];
			if($options['instance'] == $name) {
				$options['port'] = $a[0];
				$options['dataset'] = $name;
				if($options['ofile'] == 'endpoint.statistics') $options['ofile'] = "bio2rdf.$name.statistics.nt"; 
				if(!$options['graphs']) {
					$options['graphs'] = "http://bio2rdf.org/bio2rdf.dataset:bio2rdf-$name-r".$options['version'];
				}
				if(!$options['quad_uri']) $options['quad_uri'] = $options['graphs']."-statistics";
				break;
			}
		}
	}
	fclose($fp);
}

if($options['graphs'] == '') {
	echo "set graphs argument to 'all' or a comma-separated list of graph names".PHP_EOL;
	exit;
}

if($options['graphs'] == 'all') {
	try {
		$options['graphs'] = getGraphs();
	} catch(Exception $e) {
		trigger_error("Error in getting graphs from endpoint. Stopping here");
		exit;
	}
} else {
	$options['graphs'] = explode(",",$options['graphs']);
}

$fnx = array(
	"triples", 
	"distinctEntities", 
	"distinctSubjects",
	"distinctObjects",
	"distinctProperties",
	"distinctClasses", 
	"distinctLiterals", 
	"distinctInstances",
	"propertyCount", 
	"propertyObjectCount", 
	"propertyDistinctObjectCount", 
	"propertyDistinctObjectAndTypeCount", 
	"typePropertyTypeCount", 
	"datasetPropertyDatasetCount"
);
	
foreach($options['graphs'] AS $i => $graph) {
	echo "processing $graph ...";
	$options['uri'] = $graph;
	$options['filter'] = "FILTER (?g = <$graph>)";

	if($options['ofile'] == 'endpoint.statistics') 	$options['ofile'] .= '.'.$i.'.nq';
	//create file for writing /*"compress.zlib://".*/ 
	$options['fp'] = fopen($options['odir'].$options['ofile'], 'wb');

	foreach($fnx AS $f) {
		try {
			echo $f."...";
			$z = "add".ucFirst($f);
			$z();
			echo "done".PHP_EOL;
		} catch(Exception $e) {
			trigger_error("Problem! ".$e);
			continue;
		}
	}
	fclose($options['fp']);
}
exit;

function query($sparql)
{
	global $options;
	$verbose = "off";
	//isql commands pre and post
	$cmd_pre = $options['isql']." -S ".$options['port']." -U ".$options['user']." -P ".$options['pass']." verbose=$verbose banner=off prompt=off echo=ON errors=stdout exec=\"Set Blobs On;SPARQL define output:format 'JSON' "; 
	$cmd_post = '"';
	$cmd = $cmd_pre.addslashes($sparql).$cmd_post;
	$out = shell_exec($cmd);
	if(strstr($out,"*** Error")) {	
		throw new Exception($out);
	}
	// otherwise read in the json
	if($verbose == "on") {
		$start = '{ "head"';
		preg_match('/\{ "head/',$out,$s,PREG_OFFSET_CAPTURE);
		preg_match("/[0-9]+ Rows/",$out,$e,PREG_OFFSET_CAPTURE);
		$json = json_decode( substr($out,$s[0][1], ($e[0][1]-$s[0][1]-1)));
	} else {
		$json = json_decode($out);
	}
	return $json->results->bindings;
}
function getID($obj)
{
	return "http://bio2rdf.org/bio2rdf.dataset_resource:".uniqid().md5(json_encode($obj));
}
function Quad($subject_uri, $predicate_uri, $object_uri, $graph_uri = null)
{
	global $options;
	if($options['quad_uri']) $graph_uri = $options['quad_uri'];
	return "<$subject_uri> <$predicate_uri> <$object_uri> ".(isset($graph_uri)?"<$graph_uri>":"")." .".PHP_EOL;
}

function QuadLiteral($subject_uri, $predicate_uri, $literal, $dt = null, $lang = null, $graph_uri = null)
{
	global $options;
	if($options['quad_uri']) $graph_uri = $options['quad_uri'];
	$xsd = "http://www.w3.org/2001/XMLSchema#";
	$dt = "^^<".$xsd.(isset($dt)?$dt:"string").">";
	return "<$subject_uri> <$predicate_uri> \"$literal\"".(isset($lang)?"@$lang":$dt).(isset($graph_uri)?" <$graph_uri>":"")." .".PHP_EOL;
}
function write($content)
{
	global $options;
	fwrite($options['fp'],$content);
//	echo $content;
}

function getGraphs()
{
	global $options;
	$sparql = "SELECT DISTINCT ?g WHERE {GRAPH ?g {?s ?p ?o} ".$options['filter']."}";
	$r = query($sparql);
	foreach($r AS $g) {
		$graphs[] = $g->g->value;
	}
	return $graphs;
}

function addDistinctGraphs()
{
	global $options;
	$sparql = "SELECT (COUNT(distinct ?g) AS ?n) {GRAPH ?g {?s ?p ?o} ".$options['filter']."}";
	$r = query($sparql);

	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->n->value." unique graphs in ".$options['dataset'];
		write( 
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#classPartition", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#class", "http://www.w3.org/ns/sparql-service-description#Graph").
			QuadLiteral($id, "http://rdfs.org/ns/void#entities", $c->n->value, "integer").

			// enhanced
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Graph-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Graph-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addTriples()
{
	global $options;
	$sparql = "SELECT (COUNT(*) AS ?n) {GRAPH ?g {?s ?p ?o} ".$options['filter']."}";
	$r = query($sparql);
	$id = getId($r);
	
	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#triples", $r[0]->n->value, "integer").
		
		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Triples").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." triples in ".$options['dataset'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "integer").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Triples", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}

function addDistinctEntities()
{	
	global $options;
	$sparql = "SELECT (COUNT(distinct ?s) AS ?n) {GRAPH ?g {?s a []} ".$options['filter']."}";
	$r = query($sparql);
	$id = getId($r);
	
	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#entities", $r[0]->n->value, "integer").
		
		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Entities").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." distinct entities in ".$options['dataset'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "integer").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Entities", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}

function addDistinctSubjects()
{
	global $options;
	$sparql = "SELECT (COUNT(distinct ?s) AS ?n) {GRAPH ?g {?s ?p ?o} ".$options['filter']."}";
	$r = query($sparql);
	$id = getId($r);
	
	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#distinctSubjects", $r[0]->n->value, "integer").
		
		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Subjects").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." distinct subjects in ".$options['dataset'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "integer").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Subjects", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}

function addDistinctProperties()
{
	global $options;
	$sparql = "SELECT (COUNT(distinct ?p) AS ?n) {GRAPH ?g {?s ?p ?o} ".$options['filter']."}";
	$r = query($sparql);
	$id = getId($r);
	
	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#properties", $r[0]->n->value, "integer").

		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Properties").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." distinct subjects in ".$options['dataset'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "integer").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Properties", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}

function addDistinctObjects()
{
	global $options;
	$sparql = "SELECT (COUNT(distinct ?o) AS ?n) {GRAPH ?g {?s ?p ?o FILTER(!isLiteral(?o))} ".$options['filter']."}";
	$r = query($sparql);
	$id = getId($r);

	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#distinctObjects", $r[0]->n->value, "integer").
	
		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Objects").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." distinct objects in ".$options['dataset'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "integer").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Objects", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}

function addDistinctLiterals()
{
	global $options;
	
	$sparql = "SELECT (COUNT(distinct ?o) AS ?n) {GRAPH ?g {?s ?p ?o FILTER(isLiteral(?o))} ".$options['filter']."}";
	$r = query($sparql);	
	
	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->n->value." distinct literals in ".$options['dataset'];
		write(
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#classPartition", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#class", "http://www.w3.org/2000/01/rdf-schema#Literal").
			QuadLiteral($id, "http://rdfs.org/ns/void#entities", $c->n->value, "integer").
			
			// enhanced
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Literal-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Literal-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addDistinctClasses()
{
	global $options;
	$sparql = "SELECT ?type (COUNT(?type) AS ?n) str(?label) {GRAPH ?g {?s a ?type OPTIONAL {?type rdfs:label ?label}} ".$options['filter']."}";
	$r = query($sparql);
	
	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->n->value." ".(isset($c->label)?$c->label->value:$c->type->value)." in ".$options['dataset'];
		write( 
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#classPartition", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#class", $c->type->value).
			QuadLiteral($id, "http://rdfs.org/ns/void#entities", $c->n->value, "integer").
			
			// enhanced
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Type-Count").
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Type-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}



function addDistinctInstances()
{
	global $options;
	$sparql = "SELECT ?type str(?label) (COUNT(distinct ?s) AS ?n) {GRAPH ?g {?s a ?type OPTIONAL {?type rdfs:label ?label} } ".$options['filter']." } GROUP BY ?type ?label";
	$r = query($sparql);
	
	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->n->value." unique instances of ".(isset($c->label)?$c->label->value:$c->type->value)." in ".$options['dataset'];
		write( 
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#classPartition", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#class", $c->type->value).
			QuadLiteral($id, "http://rdfs.org/ns/void#entities", $c->n->value, "integer").
			
			// enhanced
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Instance-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Instance-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}


function addPropertyCount()
{
	global $options;
	$sparql = "SELECT ?p str(?label) (COUNT(?p) AS ?n) {GRAPH ?g {?s ?p ?o OPTIONAL {?p rdfs:label ?label} } ".$options['filter']." } GROUP BY ?p ?label";
	$r = query($sparql);

	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->n->value." triples with ".(isset($c->label)?$c->label->value:$c->p->value)." in ".$options['dataset'];
		write(
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#propertyPartition", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#property", $c->p->value).
			QuadLiteral($id, "http://rdfs.org/ns/void#triples", $c->n->value, "integer").
			
			// enhanced
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addPropertyObjectCount()
{
	global $options;
	$sparql = "SELECT ?p str(?label) (COUNT(?o) AS ?n) {GRAPH ?g {?s ?p ?o OPTIONAL {?p rdfs:label ?label} } ".$options['filter']." } GROUP BY ?p ?label";
	$r = query($sparql);
	
	foreach($r AS $c) {
		$id = getID($c);
		$oid = getID($c);
		$label = $c->n->value." unique objects with property ".(isset($c->label)?$c->label->value:$c->p->value)." in ".$options['dataset'];
		write( 
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $oid).
			Quad($oid, "http://rdfs.org/ns/void#class", "http://www.w3.org/2000/01/rdf-schema#Resource").
			QuadLiteral($oid, "http://rdfs.org/ns/void#entities", $c->n->value, "integer").

			// enhanced
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Object-Count").
			Quad($oid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}


function addPropertyDistinctObjectCount()
{
	global $options;
	$sparql = "SELECT ?p str(?label) (COUNT(distinct ?o) AS ?n) {GRAPH ?g {?s ?p ?o OPTIONAL {?p rdfs:label ?label} } ".$options['filter']." } GROUP BY ?p ?label";
	$r = query($sparql);
	
	foreach($r AS $c) {
		$id = getID($c);
		$oid = getID($c);
		$label = $c->n->value." unique objects with property ".(isset($c->label)?$c->label->value:$c->p->value)." in ".$options['dataset'];
		write( 
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $oid).
			Quad($oid, "http://rdfs.org/ns/void#class", "http://www.w3.org/2000/01/rdf-schema#Resource").
			QuadLiteral($oid, "http://rdfs.org/ns/void#entities", $c->n->value, "integer").
			
			// enhanced
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Distinct-Object-Count").
			Quad($oid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Object-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Distinct-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addPropertyDistinctObjectAndTypeCount()
{
	global $options;
	$sparql = "SELECT ?p str(?plabel) ?otype str(?otype_label) (COUNT(distinct ?o) AS ?n)
	{ GRAPH ?g {
		?s ?p ?o . 
		?o a ?otype .
		OPTIONAL {?p rdfs:label ?label} 
		OPTIONAL {?otype rdfs:label ?otype_label} 
	} ".$options['filter']." }
	GROUP BY ?p ?otype ?plabel ?otype_label";
	$r = query(str_replace("\n","",$sparql));

	foreach($r AS $c) {
		$id = getID($c);
		$sid = getID($c);
		$oid = getID($c);
		$label = $c->n->value." unique ".$c->otype->value." linked by ".(isset($c->label)?$c->label->value:$c->p->value)." in ".$options['dataset'];
		write( 
			// enhanced
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $oid).
			Quad($oid, "http://rdfs.org/ns/void#class", $c->otype->value).
			QuadLiteral($oid, "http://rdfs.org/ns/void#entities", $c->n->value, "integer").
			
			// enhanced
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Distinct-Object-And-Type-Count").
			Quad($oid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Object-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Object-And-Type-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}


function addTypePropertyTypeCount()
{
	global $options;
	$sparql = "SELECT ?stype str(?stype_label) (COUNT(distinct ?s) AS ?sn) ?p str(?plabel) ?otype str(?otype_label) (COUNT(distinct ?o) AS ?on)
{ 
GRAPH ?g {
?s ?p ?o . 
?s a ?stype .
?o a ?otype .
OPTIONAL {?stype rdfs:label ?stype_label} 
OPTIONAL {?p rdfs:label ?plabel} 
OPTIONAL {?otype rdfs:label ?otype_label} 
}
 ".$options['filter']."
}
GROUP BY ?stype ?otype ?p str(?stype_label) str(?plabel) str(?otype_label)";
	$r = query(str_replace("\n","",$sparql));

	foreach($r AS $c) {
		$id = getID($c);
		$sid = getID($c);
		$oid = getID($c);
		$label = $c->sn->value." unique ".$c->stype->value." linked by property "
			.(isset($c->plabel)?$c->plabel->value:$c->p->value)
			." to ".$c->on->value." unique ".$c->otype->value
			." in ".$options['dataset'];
		write(
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Type-Property-Type-Count").
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			
			Quad($id, "http://rdfs.org/ns/void#subjectsTarget", $sid).
			Quad($sid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Subject-Count").
			Quad($sid, "http://rdfs.org/ns/void#class", $c->stype->value).
			QuadLiteral($sid, "http://rdfs.org/ns/void#entities", $c->sn->value, "integer").
			
			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $oid).
			Quad($oid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Object-Count").
			Quad($oid, "http://rdfs.org/ns/void#class", $c->otype->value).
			QuadLiteral($oid, "http://rdfs.org/ns/void#entities", $c->on->value, "integer").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Type-Property-Type-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Subject-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addDatasetPropertyDatasetCount()
{
	global $options;
	$sparql = "SELECT DISTINCT ?p ?stype ?otype (COUNT(?s) AS ?n)
{
GRAPH ?g {
	?s ?p ?o .
	?s a ?stype .
	?o a ?otype .
	FILTER regex (?stype, \"vocabulary:Resource\")
	FILTER regex (?otype, \"vocabulary:Resource\")
	FILTER (?stype != ?otype)
}
	".$options['filter']."
}";
	$r = query(str_replace("\n","",$sparql));
	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->stype->value." connected to ".$c->otype->value." through ".$c->p->value." in ".$options['dataset'];
		write(
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Dataset-Property-Dataset-Count").
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			Quad($id, "http://rdfs.org/ns/void#subjectsTarget", $c->stype->value).
			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $c->otype->value).
			QuadLiteral($id, "http://rdfs.org/ns/void#triples", $c->n->value, "integer").
			
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Dataset-Property-Dataset-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")

		);
	}
}


?>
