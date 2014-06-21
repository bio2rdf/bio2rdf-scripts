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
 * Script to generate dataset statistics in RDF
 * queries a SPARQL endpoint either via HTTP query or iSQL  
 * use one or more graphs in combination, or separately
*/

$fnx = array(
	"triples", 
	"distinctEntities", 
	"distinctSubjects",
	"distinctObjects",
	"distinctProperties",
	"distinctLiterals", 
	"distinctTypes",
	"typeCount",
	"propertyCount", 
	"objectPropertyCount", 
	"datatypePropertyCount", 
	"propertyObjectTypeCount", 
	"subjectPropertyObjectCount",
	"typePropertyTypeCount", 
	"datasetPropertyDatasetCount"
);


//command line options
$options = array(
 "use" => "sparql",
 "sparql" => "http://localhost:8890/sparql",
 "isql" => "/usr/local/virtuoso-opensource/bin/isql",
 "port" => "1111",
 "user" => "dba",
 "pass" => "dba",
 "odir" => "/data/rdf/statistics/",
 "ofile" => "endpoint.statistics",
 "graphs" => "list|all|graph1,graph2 for individual processing or graph1+graph2 for combined processing",
 "dataset_name" => "",
 "dataset_uri" => "",
 "quad_uri" => "",
 "instance" => "",
 "version" => "",
 "function" => "all,".implode(",",$fnx)
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

system("mkdir -p ".$options['odir']);

if($options['instance']) {
	if($options['version'] == '') {
		echo "you must specify a version!";
		exit;
	}
	$file = "instances.tab";
	$fp = fopen($file,"r");
	if(!$fp) {trigger_error("Unable to open $file",USER_ERROR);exit;}
	while($l = fgets($fp)) {
		$a = explode("\t",trim($l));
		if(isset($a[2])) {
			$name = $a[2];
			if($options['instance'] == $name) {
				$options['port'] = $a[0];
				$options['dataset'] = $name;
				$options['sparql'] = 'http://localhost:'.$a[1].'/sparql';
				$version = $options['version'];
				$options['ofile'] = "bio2rdf-$name-R$version-statistics"; 
				$options['graphs'] = "http://bio2rdf.org/$name"."_resource:bio2rdf.dataset.$name.R$version";
				$options['quad_uri'] = $options['graphs'].".statistics";
				$options['dataset_uri'] = $options['graphs'];
				break;
			}
		}
	}
	fclose($fp);
}

if(strstr($options['graphs'],"list|all")) {
	echo "set graphs argument to 'all' or 'list' or 'graphname' or , or + separated list of graph names.".PHP_EOL;
	exit;
}

if($options['graphs'] == 'list' or $options['graphs'] == 'all') {
	try {
		$graphs = getGraphs();
	} catch(Exception $e) {
		trigger_error("Error in getting graphs from endpoint. $e");
		exit;
	}
	if($options['graphs'] == 'list') {
		echo "graphs:".PHP_EOL;
		foreach($graphs AS $g) {
			echo " ".$g.PHP_EOL;
		}
		exit;
	}
	$options['graphs'] = $graphs;
} else {
	$plusgraphs = explode("+",$options['graphs']);
	if(count($plusgraphs) >= 2) {
		$options['from-graph'] = '';
		foreach($plusgraphs AS $g) {
			$options['from-graph'] .= "FROM <$g> ";
		}
		$graphs = array($options['graphs']);
	} else {
		$graphs = explode(",",$options['graphs']);
	}
}

// validate the fxn list
$fnxs = explode(",",$options['function']);
if($fnxs[0] == 'all') array_shift($fnxs);
// get the set of valid results
$work = array_intersect($fnxs,$fnx); 
$diff = array_diff($fnxs,$work);
if(count($diff)) {
	// some elements did not match
	echo "The following functions were not found: ".implode(",",$diff).PHP_EOL;
	exit;
}
$dataset_name = $options['dataset_name'];
$outfile = $options['ofile'];

foreach($graphs AS $i => $graph) {
	echo "processing graph <$graph>".PHP_EOL;
	$options['uri'] = $graph;
	if(!isset($plusgraphs) or (count($plusgraphs) == 1)) $options['from-graph'] = "FROM <$graph>";
	if(!$dataset_name) $options['dataset_name'] = "<$graph>";
	
	if($outfile == 'endpoint.statistics' && count($graphs) >= 2) 	{
		$options['ofile'] = $outfile.'.'.($i+1);
	}
	
	//create file for writing /*"compress.zlib://".*/ 
	if(!isset($options['fp'])) {
		if($options['quad_uri']) $options['ofile'] .= '.nq';
		else $options['ofile'] .= '.nt';
		$options['fp'] = fopen($options['odir'].$options['ofile'], 'wb');
	}

	foreach($work AS $f) {
		try {
			echo " ".$f."...";
			$z = "add".ucFirst($f);
			$z();
			echo "done".PHP_EOL;
		} catch(Exception $e) {
			trigger_error("Problem! ".$e);
			continue;
		}
	}
	if(count($graphs) >= 2) {
		fclose($options['fp']);
		$options['fp'] = null;
	}
}
fclose($options['fp']);
exit;

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
		$ctx = stream_context_create(array( 
    			'http' => array( 
    			    'timeout' => 120000 
		        ) 
		    ) 
		); 
		$out = file_get_contents($cmd,null,$ctx);
	}
	$json = json_decode($out);
	return $json->results->bindings;
}
function getID($obj)
{
	$v4uuid = UUID::v4();
	return "http://bio2rdf.org/bio2rdf.dataset_resource:".$v4uuid;
}
function makeLabel($o, $uri, $label)
{
	return isset($o->$label)?"'".$o->$label->value."' <".$o->$uri->value.'>':'<'.$o->$uri->value.'>';
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
	return "<$subject_uri> <$predicate_uri> \"".addslashes($literal)."\"".(isset($lang)?"@$lang":$dt).(isset($graph_uri)?" <$graph_uri>":"")." .".PHP_EOL;
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
	$sparql = "SELECT DISTINCT ?g WHERE {GRAPH ?g {[] a ?o}}";
	$r = query($sparql);
	foreach($r AS $g) {
		$graphs[] = $g->g->value;
	}
	return $graphs;
}

function addDistinctGraphs()
{
	global $options;
	$sparql = "SELECT (COUNT(distinct ?g) AS ?n) {GRAPH ?g {[] ?p ?o} ".$options['filter']."}";
	$r = query($sparql);

	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->n->value." unique graphs in ".$options['dataset'];
		write( 
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#classPartition", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#class", "http://www.w3.org/ns/sparql-service-description#Graph").
			QuadLiteral($id, "http://rdfs.org/ns/void#entities", $c->n->value, "long").

			// enhanced
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Graph-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Graph-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addTriples()
{
	global $options;
	$sparql = "SELECT (COUNT(*) AS ?n) ".$options['from-graph']." {?s ?p ?o}";
	$r = query($sparql);
	$id = getId($r);
	
	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#triples", $r[0]->n->value, "long").
		
		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Triples").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." triples in ".$options['dataset_name'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "long").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Triples", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}

function addDistinctEntities()
{	
	global $options;
	$sparql = "SELECT (COUNT(DISTINCT ?s) AS ?n) ".$options['from-graph']." {?s a []}";
	$r = query($sparql);
	$id = getId($r);
	
	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#entities", $r[0]->n->value, "long").
		
		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Entities").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." distinct entities in ".$options['dataset_name'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "long").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Entities", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}

function addDistinctSubjects()
{
	global $options;
	$sparql = "SELECT (COUNT(DISTINCT ?s) AS ?n) ".$options['from-graph']." {?s ?p ?o}";
	$r = query($sparql);
	$id = getId($r);
	
	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#distinctSubjects", $r[0]->n->value, "long").
		
		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Subjects").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." distinct subjects in ".$options['dataset_name'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "long").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Subjects", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}

function addDistinctProperties()
{
	global $options;
	$sparql = "SELECT (COUNT(DISTINCT ?p) AS ?n) ".$options['from-graph']." {?s ?p ?o}";
	$r = query($sparql);
	$id = getId($r);
	
	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#properties", $r[0]->n->value, "long").

		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Properties").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." distinct subjects in ".$options['dataset_name'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "long").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Properties", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}

function addDistinctObjects()
{
	global $options;
	$sparql = "SELECT (COUNT(DISTINCT ?o) AS ?n) ".$options['from-graph']." {?s ?p ?o FILTER(!isLiteral(?o))}";
	$r = query($sparql);
	$id = getId($r);

	write(
		// standard
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#distinctObjects", $r[0]->n->value, "long").
	
		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Objects").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." distinct objects in ".$options['dataset_name'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "long").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Objects", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}



function addDistinctLiterals()
{
	global $options;
	
	$sparql = "SELECT (COUNT(DISTINCT ?o) AS ?n) ".$options['from-graph']." {?s ?p ?o FILTER(isLiteral(?o))}";
	$r = query($sparql);	
	
	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->n->value." distinct literals in ".$options['dataset_name'];
		write(
			// standard
			QuadLiteral($options['uri'], "http://rdfs.org/ns/void#distinctLiterals", $r[0]->n->value, "long"). // we made this predicate up
			
			Quad($options['uri'], "http://rdfs.org/ns/void#classPartition", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#class", "http://www.w3.org/2000/01/rdf-schema#Literal").
			QuadLiteral($id, "http://rdfs.org/ns/void#entities", $c->n->value, "long").
			
			// enhanced
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Literals").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Literal-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addDistinctTypes()
{
	global $options;
	$sparql = "SELECT (COUNT(DISTINCT ?t) AS ?n) ".$options['from-graph']." {?s ?p ?o . ?o a ?t}";
	$r = query($sparql);
	$id = getId($r);

	write(	
		QuadLiteral($options['uri'], "http://rdfs.org/ns/void#distinctTypes", $r[0]->n->value, "long"). // we made this up
	
		// enhanced
		Quad($options['uri'],'http://rdfs.org/ns/void#subset', $id).
		Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Types").
		QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $r[0]->n->value." distinct types in ".$options['dataset_name'], null, "en").
		QuadLiteral($id, "http://rdfs.org/ns/void#entities", $r[0]->n->value, "long").
		Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Distinct-Types", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
	);
}


function addTypeCount()
{
	global $options;
	$sparql = "SELECT ?type ?n ?dn (str(?label) AS ?slabel)
".$options['from-graph']."
{
  { 
    SELECT ?type (COUNT(DISTINCT ?s) AS ?dn) (COUNT(?s) AS ?n)
    {?s a ?type} 
    GROUP BY ?type
  }
  OPTIONAL {?type rdfs:label ?label}
}";

	$r = query($sparql);
	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->n->value." ".makeLabel($c,'type','slabel')." in ".$options['dataset_name'];
		write( 
			Quad($options['uri'], "http://rdfs.org/ns/void#classPartition", $id).
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#class", $c->type->value).
			(isset($c->label)?QuadLiteral($c->type->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->label->value):"").
			QuadLiteral($id, "http://rdfs.org/ns/void#entities", $c->n->value, "long").
			QuadLiteral($id, "http://rdfs.org/ns/void#distinctEntities", $c->dn->value, "long"). // made this up
			
			// enhanced		
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Type-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Type-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}


function addPropertyCount()
{
	global $options;
	$sparql = "SELECT ?p str(?plabel) ?n 
".$options['from-graph']." 
{
	{ SELECT ?p (COUNT(?p) AS ?n) 
	  {?s ?p ?o}
	  GROUP BY ?p
	}
	OPTIONAL {?p rdfs:label ?plabel} 
}
";
	$r = query($sparql);

	foreach($r AS $c) {
		$id = getID($c);
		$label = $c->n->value." triples with ".makeLabel($c,'p','plabel')." in ".$options['dataset_name'];
		write(
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#propertyPartition", $id).
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Count").
			Quad($id, "http://rdfs.org/ns/void#property", $c->p->value).
			(isset($c->plabel)? QuadLiteral($c->p->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->plabel->value):'').
			QuadLiteral($id, "http://rdfs.org/ns/void#triples", $c->n->value, "long").
			
			// enhanced
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addObjectPropertyCount()
{
	global $options;
	$sparql = "SELECT ?p (str(?label) AS ?plabel) (?n AS ?n) (?dn AS ?dn)
".$options['from-graph']." 
{
	{ SELECT ?p (COUNT(?o) AS ?n) (COUNT(DISTINCT ?o) AS ?dn) 
	  { ?s ?p ?o FILTER (!isLiteral(?o)) }
	  GROUP BY ?p
	}
	OPTIONAL {?p rdfs:label ?label} 
}
";
	$r = query($sparql);
	foreach($r AS $c) {
		$id = getID($c);
		$oid = getID($c);
		$label = $c->n->value." (".$c->dn->value." unique) objects linked by property ".makeLabel($c, 'p', 'plabel')." in ".$options['dataset_name'];
		write( 
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Property-Count").
			
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			(isset($c->plabel)? QuadLiteral($c->p->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->plabel->value):'').
			
			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $oid).
			Quad($oid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count").

			Quad($oid, "http://rdfs.org/ns/void#class", "http://www.w3.org/2000/01/rdf-schema#Resource").
			QuadLiteral($oid, "http://rdfs.org/ns/void#entities", $c->n->value, "long").
			QuadLiteral($oid, "http://rdfs.org/ns/void#distinctEntities", $c->dn->value, "long").

			// enhanced			
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}


function addDatatypePropertyCount()
{
	global $options;
	$sparql = "SELECT ?p (str(?label) AS ?plabel) (?n AS ?n) (?dn AS ?dn)
".$options['from-graph']." 
{
	{ SELECT ?p (COUNT(?o) AS ?n) (COUNT(DISTINCT ?o) AS ?dn)
	  {?s ?p ?o FILTER (isLiteral(?o))} 
	  GROUP BY ?p
	}
	OPTIONAL {?p rdfs:label ?label} 
}";
	$r = query($sparql);
	foreach($r AS $c) {
		$id = getID($c);
		$oid = getID($c);
		
		$label = $c->n->value." (".$c->dn->value." unique) literals linked to datatype property ".makeLabel($c, 'p', 'plabel')." in ".$options['dataset_name'];
		write( 
			// standard
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Datatype-Property-Count").
			
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			(isset($c->plabel)? QuadLiteral($c->p->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->plabel->value):'').
		
			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $oid).
			Quad($oid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Literal-Count").
			Quad($oid, "http://rdfs.org/ns/void#class", "http://www.w3.org/2000/01/rdf-schema#Literal").
			QuadLiteral($oid, "http://rdfs.org/ns/void#entities", $c->n->value, "long").
			QuadLiteral($oid, "http://rdfs.org/ns/void#distinctEntities", $c->dn->value, "long").
			
			// enhanced
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Datatype-Property-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Datatype-Literal-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addPropertyObjectTypeCount()
{
	global $options;
	$sparql = "SELECT ?p (str(?plabel) AS ?plabel) ?otype (str(?otype_label) AS ?otype_label) (?n AS ?n) (?dn AS ?dn)
".$options['from-graph']." {
	{
		SELECT ?p ?otype (COUNT(?o) AS ?n) (COUNT(DISTINCT ?o) AS ?dn)
		{ 
			?s ?p ?o . 
			?o a ?otype .
		}
		GROUP BY ?p ?otype
	}
	OPTIONAL {?p rdfs:label ?plabel} 
	OPTIONAL {?otype rdfs:label ?otype_label} 
}";
	$r = query($sparql);	
	foreach($r AS $c) {
		$id = getID($c);
		$oid = getID($c);

		$label = $c->n->value." (".$c->dn->value." unique) objects of type ".makeLabel($c, 'otype', 'otype_label').
			" linked by ".makeLabel($c,'p','plabel')." in ".$options['dataset_name'];
		write( 
			// enhanced
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			(isset($c->plabel)? QuadLiteral($c->p->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->plabel->value):'').
			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $oid).
			Quad($oid, "http://rdfs.org/ns/void#class", $c->otype->value).
			(isset($c->otype_label)? QuadLiteral($c->otype->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->otype_label->value):'').
			QuadLiteral($oid, "http://rdfs.org/ns/void#entities", $c->n->value, "long").
			QuadLiteral($oid, "http://rdfs.org/ns/void#distinctEntities", $c->dn->value, "long").
			
			// enhanced
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Property-Object-Type-Count").
			Quad($oid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Type-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addSubjectPropertyObjectCount()
{
	global $options;
	$sparql = "SELECT ?p (str(?plabel) AS ?plabel) (?sn AS ?sn) (?dsn AS ?dsn) (?on AS ?on) (?don AS ?don)
".$options['from-graph']." {
	{
		SELECT ?p (COUNT(?s) AS ?sn) (COUNT(DISTINCT ?s) AS ?dsn) (COUNT(?o) AS ?on) (COUNT(DISTINCT ?o) AS ?don)
		{ 
			?s ?p ?o . 
		}
		GROUP BY ?p
	}
	OPTIONAL {?p rdfs:label ?plabel} 
}";
	$r = query($sparql);	
	foreach($r AS $c) {
		$id = getID($c);
		$sid = getID($c);
		$oid = getID($c);
		
		$label = $c->sn->value." (".$c->dsn->value." unique) subjects linked by ".makeLabel($c,'p','plabel')." to ".
			$c->on->value." (".$c->don->value." unique) objects in ".$options['dataset_name'];
		write( 
			// enhanced
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Subject-Property-Object-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Subject-Property-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			(isset($c->plabel)? QuadLiteral($c->p->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->plabel->value):'').
			
			Quad($id, "http://rdfs.org/ns/void#subjectsTarget", $sid).
			Quad($sid, "http://rdfs.org/ns/void#class", "http://www.w3.org/2000/01/rdf-schema#Resource").
			QuadLiteral($sid, "http://rdfs.org/ns/void#entities", $c->sn->value, "long").
			QuadLiteral($sid, "http://rdfs.org/ns/void#distinctEntities", $c->dsn->value, "long").
			Quad($sid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Subject-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Subject-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").

			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $oid).			
			Quad($oid, "http://rdfs.org/ns/void#class", "http://www.w3.org/2000/01/rdf-schema#Resource").
			QuadLiteral($oid, "http://rdfs.org/ns/void#entities", $c->on->value, "long").
			QuadLiteral($oid, "http://rdfs.org/ns/void#distinctEntities", $c->don->value, "long").
			Quad($oid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")	
		);
	}
}


function addTypePropertyTypeCount()
{
	global $options;
	$sparql = "SELECT 
		?stype (str(?stype_label) AS ?stype_label) (?sn AS ?sn) (?dsn AS ?dsn) 
		?p (str(?plabel) AS ?plabel) 
		?otype (str(?otype_label) AS ?otype_label) (?on AS ?on) (?don AS ?don)
	".$options['from-graph']." 
{
	{
		SELECT ?stype ?p ?otype (COUNT(?s) AS ?sn) (COUNT(DISTINCT ?s) AS ?dsn) (COUNT(?o) AS ?on) (COUNT(DISTINCT ?o) AS ?don)
		{
			?s ?p ?o . 
			?s a ?stype .
			?o a ?otype .
		}
		GROUP BY ?p ?stype ?otype 
	}
	OPTIONAL {?stype rdfs:label ?stype_label} 
	OPTIONAL {?p rdfs:label ?plabel} 
	OPTIONAL {?otype rdfs:label ?otype_label} 
}
";
	$r = query($sparql);
	foreach($r AS $c) {
		$id = getID($c);
		$sid = getID($c);
		$oid = getID($c);
		$label = $c->sn->value." (".$c->dsn->value." unique) ".makeLabel($c,'stype','stype_label')." linked by property ".makeLabel($c, 'p', 'plabel')
			." to ".$c->on->value." (".$c->don->value." unique) ".makeLabel($c,'otype','otype_label')." in ".$options['dataset_name'];
		write(
			Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
			Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Type-Property-Type-Count").
			QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
			Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
			(isset($c->plabel)? QuadLiteral($c->p->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->plabel->value):'').
			
			Quad($id, "http://rdfs.org/ns/void#subjectsTarget", $sid).
			Quad($sid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Subject-Count").
			Quad($sid, "http://rdfs.org/ns/void#class", $c->stype->value).
			(isset($c->stype_label)? QuadLiteral($c->stype->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->stype_label->value):'').
			QuadLiteral($sid, "http://rdfs.org/ns/void#entities", $c->sn->value, "long").
			QuadLiteral($sid, "http://rdfs.org/ns/void#distinctEntities", $c->dsn->value, "long").
			
			Quad($id, "http://rdfs.org/ns/void#objectsTarget", $oid).
			Quad($oid, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count").
			Quad($oid, "http://rdfs.org/ns/void#class", $c->otype->value).
			(isset($c->otype_label)? QuadLiteral($c->otype->value, "http://www.w3.org/2000/01/rdf-schema#label", $c->otype_label->value):'').
			QuadLiteral($oid, "http://rdfs.org/ns/void#entities", $c->on->value, "long").
			QuadLiteral($oid, "http://rdfs.org/ns/void#distinctEntities", $c->don->value, "long").
			
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Type-Property-Type-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Subject-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor").
			Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Object-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
		);
	}
}

function addDatasetPropertyDatasetCount()
{
	global $options;
	$sparql = "SELECT DISTINCT ?p ?stype ?otype (COUNT(?s) AS ?n)
".$options['from-graph']." 
{
	?s ?p ?o .
	?s a ?stype .
	?o a ?otype .
	FILTER regex (?stype, \"vocabulary:Resource\")
	FILTER regex (?otype, \"vocabulary:Resource\")
	FILTER (?stype != ?otype)
}";
	$r = query($sparql);
	foreach($r AS $c) {
		$id = getID($c);

		preg_match("/http:\/\/bio2rdf.org\/([^_]+)_vocabulary/",$c->stype->value,$m1);
		preg_match("/http:\/\/bio2rdf.org\/([^_]+)_vocabulary/",$c->otype->value,$m2);
		if(isset($m1[1]) and isset($m2[1])) {
			$d1 = $m1[1];
			$d2 = $m2[1];
			$r = $c->p->value;
			$label = "$d1 connected to $d2 through ".$c->n->value." <$r> in ".$options['dataset_name'];
			write(
				Quad($options['uri'], "http://rdfs.org/ns/void#subset", $id).
				Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#LinkSet").
				Quad($id, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Dataset-Property-Dataset-Count").
				QuadLiteral($id, "http://www.w3.org/2000/01/rdf-schema#label", $label, null, "en").
				Quad($id, "http://rdfs.org/ns/void#linkPredicate", $c->p->value).
				Quad($id, "http://rdfs.org/ns/void#subjectsTarget", $c->stype->value).
				Quad($id, "http://rdfs.org/ns/void#objectsTarget", $c->otype->value).
				QuadLiteral($id, "http://rdfs.org/ns/void#triples", $c->n->value, "long").
				Quad("http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Dataset-Property-Dataset-Count", "http://www.w3.org/2000/01/rdf-schema#subClassOf", "http://bio2rdf.org/bio2rdf.dataset_vocabulary:Dataset-Descriptor")
			);
		}
	}
}


class UUID {
  public static function v4() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

      // 32 bits for "time_low"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),

      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),

      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,

      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,

      // 48 bits for "node"
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
  }
}

?>
