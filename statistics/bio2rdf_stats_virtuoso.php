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

//path to isql executable
$isql = "/usr/local/virtuoso-opensource/bin/isql";

//command line options
$options = array(
 "url" => "endpoint_url",
 "port" => "isql_port",
 "user" => "dba",
 "pass" => "dba",
 "outfile" => "bio2rdf_stats.n3"
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

if($options['url'] == 'endpoint_url'){
	echo "** Specify a valid endpoint URL. **".PHP_EOL;
	exit;
}

if($options['port'] == 'isql_port'){
	echo "** Specify a valid iSQL port. **".PHP_EOL;
	exit;
}

//isql commands pre and post
$cmd_pre = "$isql -S ".$options['port']." -U ".$options['user']." -P ".$options['pass']." verbose=on banner=off prompt=off echo=ON errors=stdout exec="."'SPARQL "; 
$cmd_post = "'";


$dataset_uri = get_void_dataset_uri();
echo '# of triples'.PHP_EOL;
$triples = get_number_of_triples();
echo '# of unique subjects'.PHP_EOL;
$subjects = get_unique_subject_count();
echo '# of unique predicates'.PHP_EOL;
$predicates = get_unique_predicate_count();
echo '# of unique objects'.PHP_EOL;
$objects = get_unique_object_count();
echo '# of unique literals'.PHP_EOL;
$literals = get_unique_literal_count();
echo '# of types'.PHP_EOL;
$types = get_type_counts();
echo '# of predicate-literals'.PHP_EOL;
$pred_literals = get_predicate_literal_counts();
echo '# of preidcate-objects'.PHP_EOL;
$pred_objects = get_predicate_object_counts();
echo '# of unique subject-predicate-literals'.PHP_EOL;
$pred_subj_literals = get_unique_subject_predicate_unique_object_literal_counts();
echo '# of unique subject-predicate-objects'.PHP_EOL;
$pred_subj_objects = get_unique_subject_predicate_unique_object_counts();
echo '# of type-relation-types'.PHP_EOL;
$type_relations = get_type_relation_type_counts();

//create file for writing
$out_file = $options['outfile'];
$out_handle = fopen($out_file, 'a');

//write stats N3 to outfile
write_endpoint_details($out_handle);
write_triple_count($out_handle, $triples);
write_unique_subject_count($out_handle, $subjects);
write_unique_predicate_count($out_handle, $predicates);
write_unique_object_count($out_handle, $objects);
write_type_counts($out_handle, $types);
write_predicate_literal_counts($out_handle, $pred_literals);
write_predicate_object_counts($out_handle, $pred_objects);
write_unique_subject_predicate_unique_object_literal_counts($out_handle, $pred_subj_literals);
write_unique_subject_predicate_unique_object_counts($out_handle, $pred_subj_objects);
write_type_relation_type_counts($out_handle, $type_relations);

//close outfile
fclose($out_handle);

//get total number of triples
function get_number_of_triples(){
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	$qry = "select count(*) where {  graph ?g  {?x ?y ?z}  FILTER regex(?g, \"bio2rdf\") }";
	$cmd = $cmd_pre.$qry.$cmd_post;
	$out = "";
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}
	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	$results = trim($split_results_2[0]);
	if (preg_match("/^0 Rows./is", $results) === 0) {
		return $results;
	} else {
		return null;
	}
}

function get_void_dataset_uri(){
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	$q = "select ?x where {?w <http://rdfs.org/ns/void#inDataset> ?x.}LIMIT 1";
	$cmd = $cmd_pre.$q.$cmd_post;
	$out = "";
	try{
		$out = execute_isql_command($cmd);
	}catch(Exception $e){
		echo 'iSQL error: '.$e->getMessage();
		return null;
	}
	$sr = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$sr2 = explode("\n\n", $sr[1]);
	$r = trim($sr2[0]);
	if(strlen($r)){
		return $r;
	}else{
		throw new Exception("Could not find a valid dataset uri! Terminating program.");
		exit;
	}
}

//get number of unique subjects
function get_unique_subject_count(){
	
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	
	$qry = "select count(distinct ?x) where { graph ?g {?x ?y ?z} FILTER regex(?g, \"bio2rdf\") } ";
	
	$cmd = $cmd_pre.$qry.$cmd_post;
	
	$out = "";
	
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}

	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	$results = trim($split_results_2[0]);
	
	if (preg_match("/^0 Rows./is", $results) === 0) {
		return $results;
	} else {
		return null;
	}
}

//get number of unique predicates
function get_unique_predicate_count(){
	
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	
	$qry = "select count(distinct ?y) where { graph ?g {?x ?y ?z} FILTER regex(?g, \"bio2rdf\") } ";
	
	$cmd = $cmd_pre.$qry.$cmd_post;
	
	$out = "";
	
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}
	
	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	$results = trim($split_results_2[0]);
	
	if (preg_match("/^0 Rows./is", $results) === 0) {
		return $results;
	} else {
		return null;
	}
}

//get the number of unique literals
function get_unique_literal_count(){
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	$q = "select count(distinct ?z) where { graph ?g { ?x ?y ?z. FILTER isLiteral(?z).}FILTER regex(?g , \"bio2rdf\")}";
	$cmd = $cmd_pre.$q.$cmd_post;
	$out = "";
	try{
		$out =execute_isql_command($cmd);
	}catch (Exception $e){
		echo 'iSQL error: '.$e->getMessage();
		return null;
	}
}

//get number of unique objects
function get_unique_object_count(){
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	$qry = "select count(distinct ?z) where { graph ?g {?x ?y ?z} FILTER regex(?g, \"bio2rdf\") } ";
	$cmd = $cmd_pre.$qry.$cmd_post;
	$out = "";
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}
	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	$results = trim($split_results_2[0]);
	if (preg_match("/^0 Rows./is", $results) === 0) {
		return $results;
	} else {
		return null;
	}
}

//get number of unique types
function get_type_counts(){
	
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	
	$qry = "select ?type (COUNT(?s) AS ?c) where  { graph ?g {?s a ?type} FILTER regex(?g, \"bio2rdf\") } order by DESC(?c)";
	
	$cmd = $cmd_pre.$qry.$cmd_post;
	
	$out = "";
	
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}
	
	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	
	$results = trim($split_results_2[0]);
	
	if (preg_match("/^0 Rows./is", $results) === 0) {

		$results_arr = array();
	
		$lines = explode("\n", $results);
		foreach($lines as $line){
				$split_line = preg_split('/[[:space:]]+/', $line);
				$results_arr[$split_line[0]] = $split_line[1];
		}//foreach
		return $results_arr;
	} else {
		return null;		
	}//else
}

//get predicates and the number of unique literals they link to
function get_predicate_literal_counts(){
	
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	
	$qry = "select ?p (COUNT(?o) AS ?c) where { graph ?g { ?s ?p ?o . FILTER isLiteral(?o) . } FILTER regex(?g, \"bio2rdf\") } ORDER BY DESC(?c)";
	
	$cmd = $cmd_pre.$qry.$cmd_post;
	
	$out = "";
	
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}
	
	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	$results = trim($split_results_2[0]);
	
	if (preg_match("/^0 Rows./is", $results) === 0) {			
		$results_arr = array();
		
		$lines = explode("\n", $results);
		foreach($lines as $line){
				$split_line = preg_split('/[[:space:]]+/', $line);
				$results_arr[$split_line[0]] = $split_line[1];
		}

		return $results_arr;
	} else {
			return null;
	}
}

//get predicates and the number of unique IRIs they link to
function get_predicate_object_counts(){
	
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	
	$qry = "select ?p (COUNT(?o) AS ?c) where { graph ?g { ?s ?p ?o . FILTER isIRI(?o) . } FILTER regex(?g, \"bio2rdf\") } ORDER BY DESC(?c)";
	
	$cmd = $cmd_pre.$qry.$cmd_post;
	
	$out = "";
	
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}
	
	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	
	$results = trim($split_results_2[0]);
	
	if (preg_match("/^0 Rows./is", $results) === 0) {	
		$results_arr = array();
		
		$lines = explode("\n", $results);
		foreach($lines as $line){
				$split_line = preg_split('/[[:space:]]+/', $line);
				$results_arr[$split_line[0]] = $split_line[1];
		}

		return $results_arr;
	} else {
		return null;
	}
}

//get number of unique subjects and object literals for each predicate
function get_unique_subject_predicate_unique_object_literal_counts(){
	
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	
	$qry = "select ?p COUNT(DISTINCT ?s) COUNT(DISTINCT ?o) where { graph ?g { ?s ?p ?o . FILTER isLiteral(?o) } FILTER regex(?g, \"bio2rdf\") }";
	
	$cmd = $cmd_pre.$qry.$cmd_post;
	
	$out = "";
	
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}
	
	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	
	$results = trim($split_results_2[0]);
	
	if (preg_match("/^0 Rows./is", $results) === 0) {	
		$results_arr = array();
		
		$lines = explode("\n", $results);
		foreach($lines as $line){
				$split_line = preg_split('/[[:space:]]+/', $line);
				$results_arr[$split_line[0]]["count"]["subject_count"] = $split_line[1];
				$results_arr[$split_line[0]]["count"]["object_count"] = $split_line[2];
		}

		return $results_arr;
	} else {
		return null;
	}
	
}

//get number of unique subjects and object IRIs for each predicate
function get_unique_subject_predicate_unique_object_counts(){
	
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	
	$qry = "select ?p COUNT(DISTINCT ?s) COUNT(DISTINCT ?o) where { graph ?g { ?s ?p ?o . FILTER isIRI(?o) } FILTER regex(?g, \"bio2rdf\") }";
	
	$cmd = $cmd_pre.$qry.$cmd_post;
	
	$out = "";
	
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}
	
	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	$results = trim($split_results_2[0]);
	
	if (preg_match("/^0 Rows./is", $results) === 0) {
	
		$results_arr = array();
		
		$lines = explode("\n", $results);
		foreach($lines as $line){
				$split_line = preg_split('/[[:space:]]+/', $line);
				$results_arr[$split_line[0]]["count"]["subject_count"] = $split_line[1];
				$results_arr[$split_line[0]]["count"]["object_count"] = $split_line[2];
		}

		return $results_arr;
	} else {
		return null;
	}
	
}

//get the number of distinct subject and object types for each predicate
function get_type_relation_type_counts(){
	GLOBAL $cmd_pre;
	GLOBAL $cmd_post;
	$qry = "select ?p ?stype COUNT(DISTINCT ?s) ?otype COUNT(DISTINCT ?o) where { graph ?g { ?s a ?stype .  ?s ?p ?o . ?o a ?otype . } FILTER regex(?g, \"bio2rdf\") }";
	$cmd = $cmd_pre.$qry.$cmd_post;
	$out = "";
	try {
		$out = execute_isql_command($cmd);
	} catch (Exception $e){
		echo 'iSQL error: ' .$e->getMessage();
		return null;
	}	
	$split_results = explode("Type HELP; for help and EXIT; to exit.\n", $out);
	$split_results_2 = explode("\n\n", $split_results[1]);
	$results = trim($split_results_2[0]);
	if (preg_match("/^0 Rows./is", $results) === 0) {
		$results_arr = array();		
		$lines = explode("\n", $results);
		foreach($lines as $line){
				$split_line = preg_split('/[[:space:]]+/', $line);
				$results_arr[$split_line[0]]["count"]["subject_type"] = $split_line[1];
				$results_arr[$split_line[0]]["count"]["subject_count"] = $split_line[2];
				$results_arr[$split_line[0]]["count"]["object_type"] = $split_line[3];
				$results_arr[$split_line[0]]["count"]["object_count"] = $split_line[4];
		}

		return $results_arr;
	} else {
		return null;
	}
}

function execute_isql_command($cmd){
	$out = shell_exec($cmd);
	if(strstr($out,"Error")) {
		throw new Exception($out);
	}	
	return $out;
}

//functions for writing statistics RDF to file
function write_endpoint_details($fh){
	GLOBAL $options;
	fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://bio2rdf.org/dataset_vocabulary:Endpoint"));
	fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#Dataset"));
	fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://www.w3.org/2000/01/rdf-schema#label", $options['url']." SPARQL endpoint"));
	fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://bio2rdf.org/dataset_vocabulary:has_url", $options['url']));
}

function write_triple_count($fh, $triple_count){
	GLOBAL $options;
	if($triple_count !== null){
		fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://rdfs.org/ns/void#triples", $triple_count));
	}
}

function write_unique_subject_count($fh, $subj_count){
	GLOBAL $options;
	if($subj_count !== null){
		fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://rdfs.org/ns/void#distinctSubjects", $subj_count));
	}
}

function write_unique_predicate_count($fh, $pred_count){
	GLOBAL $options;
	if($pred_count !== null){
		fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://rdfs.org/ns/void#properties", $pred_count));

	}
}

function write_unique_object_count($fh, $obj_count){
	GLOBAL $options;
	if($obj_count !== null){
		fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://rdfs.org/ns/void#distinctObjects", $obj_count));
	}
}


/*function write_unique_literal_count($fh, $lit_count){
	GLOBAL $options;
	if($lit_count !== null){
		fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://rdfs.org/ns/void#y"))
	}
}*/

function write_type_counts($fh, $type_counts){
	GLOBAL $options;
	GLOBAL $dataset_uri;
	if($type_counts !== null){
		foreach($type_counts as $type => $count){
			#now create a resource for the class partition
			$partition_res = "http://bio2rdf.org/dataset_resource:".md5($options['url']).md5($options['url'].$type.$count."type_count");
			fwrite($fh, Quad($partition_res, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#Dataset"));
			fwrite($fh, Quad($partition_res, "http://rdfs.org/ns/void#class", $type));
			fwrite($fh, QuadLiteral($partition_res, "http://rdfs.org/ns/void#entities", $count));
			#now connect it back to the corresponding void:dataset
			fwrite($fh, Quad($dataset_uri, "http://rdfs.org/ns/void#classPartition", $partition_res));
		}//foreach
	}//if
}

function write_predicate_literal_counts($fh, $pred_literal_counts){
	GLOBAL $options;
	GLOBAL $dataset_uri;
	if($pred_literal_counts !== null){
		foreach($pred_literal_counts as $pred => $count){
			//now create a resource for the property partition
			$partition_res = "http://bio2rdf.org/dataset_resource:".md5($options['url']).md5($options['url'].$pred.$count."predicate_literal_count");
			#add the dataset type
			fwrite($fh, Quad($partition_res, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#Dataset"));
			fwrite($fh, Quad($partition_res, "http://rdfs.org/ns/void#property", $pred));
			fwrite($fh, QuadLiteral($partition_res, "http://rdfs.org/ns/void#entities", $count));
			//now connect it back to the void:dataset
			fwrite($fh, Quad($dataset_uri, "http://rdfs.org/ns/void#propertyPartition", $partition_res));
		}//foreach
	}//if
}

function write_predicate_object_counts($fh, $pred_obj_counts){
	GLOBAL $dataset_uri;
	GLOBAL $options;
	if($pred_obj_counts !== null){
		foreach($pred_obj_counts as $pred => $count){
			#create a resource for the property partition
			$partition_res = "http://bio2rdf.org/dataset_resource:".md5($options['url']).md5($options['url'].$pred.$count."predicate_object_count");
			#add the dataset type
			fwrite($fh, Quad($partition_res, "http://www.w3.org/1999/02/22-rdf-syntax-ns#type", "http://rdfs.org/ns/void#Dataset"));
			fwrite($fh, Quad($partition_res, "http://rdfs.org/ns/void#property", $pred));
			fwrite($fh, QuadLiteral($partition_res, "http://rdfs.org/ns/void#entities", $count));
			#now connect it back to the void:dataset 
			fwrite($fh, Quad($dataset_uri, "http://rdfs.org/ns/void#propertyPartition", $partition_res));
		}
	}
}

function write_unique_subject_predicate_unique_object_literal_counts($fh, $counts){
	GLOBAL $dataset_uri;
	GLOBAL $options;
	if($counts !== null){
		foreach($counts as $pred => $count){
			fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://bio2rdf.org/dataset_vocabulary:has_predicate_unique_subject_unique_literal_count", "http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."predicate_subject_literal_count")));
			fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."predicate_subject_literal_count"), "http://bio2rdf.org/dataset_vocabulary:has_predicate", $pred));
			fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."predicate_subject_literal_count"), "http://bio2rdf.org/dataset_vocabulary:has_subject_count", $count["count"]["subject_count"]));
			fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."predicate_subject_literal_count"), "http://bio2rdf.org/dataset_vocabulary:has_literal_count", $count["count"]["object_count"]));
		}
	}
}

function write_unique_subject_predicate_unique_object_counts($fh, $counts){
	GLOBAL $options;
	if($counts !== null){
		foreach($counts as $pred => $count){
			fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://bio2rdf.org/dataset_vocabulary:has_predicate_unique_subject_unique_object_count", "http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."predicate_subject_object_count")));
			fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."predicate_subject_object_count"), "http://bio2rdf.org/dataset_vocabulary:has_predicate", $pred));
			fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."predicate_subject_object_count"), "http://bio2rdf.org/dataset_vocabulary:has_subject_count", $count["count"]["subject_count"]));
			fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."predicate_subject_object_count"), "http://bio2rdf.org/dataset_vocabulary:has_object_count", $count["count"]["object_count"]));
		}
	}
}

function write_type_relation_type_counts($fh, $counts){
	GLOBAL $options;
	if ($counts !== null){
		foreach($counts as $pred => $count){
			fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url']), "http://bio2rdf.org/dataset_vocabulary:has_type_relation_type_count", "http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."type_relation_type_count")));
			fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."type_relation_type_count"), "http://bio2rdf.org/dataset_vocabulary:has_predicate", $pred));
			fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."type_relation_type_count"), "http://bio2rdf.org/dataset_vocabulary:has_subject_type", $count["count"]["subject_type"]));
			fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."type_relation_type_count"), "http://bio2rdf.org/dataset_vocabulary:has_subject_count", $count["count"]["subject_count"]));
			fwrite($fh, Quad("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."type_relation_type_count"), "http://bio2rdf.org/dataset_vocabulary:has_object_type", $count["count"]["object_type"]));
			fwrite($fh, QuadLiteral("http://bio2rdf.org/dataset_resource:".md5($options['url'].$pred."type_relation_type_count"), "http://bio2rdf.org/dataset_vocabulary:has_object_count", $count["count"]["object_count"]));
		}
	}
}

function Quad($subject_uri, $predicate_uri, $object_uri, $graph_uri = null)
{
	return "<$subject_uri> <$predicate_uri> <$object_uri> ".(isset($graph_uri)?"<$graph_uri>":"")." .".PHP_EOL;
}

function QuadLiteral($subject_uri, $predicate_uri, $literal, $lang = null, $graph_uri = null)
{
	return "<$subject_uri> <$predicate_uri> \"$literal\"".(isset($lang)?"@$lang ":' ').(isset($graph_uri)?"<$graph_uri>":"")." .".PHP_EOL;
}

?>
