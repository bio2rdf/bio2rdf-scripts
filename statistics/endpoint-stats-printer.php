<?php
/**
* Copyright (C) 2013 Jose Cruz-Toledo
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


//TODO: add lsr_file to parser

/********************/
/** FUNCTION CALLS **/
/********************/
$options = print_usage($argv, $argc);
$endpoints =  makeEndpoints($options['instances_file']);
$lsr_arr = readLSRIntoArr($options['lsr_file']);
//search the lsr for the descriptions of the endpoints found in endpoints
$endpoints_desc = parseDescriptions($endpoints, $lsr_arr);
$endpoint_stats = retrieveStatistics($endpoints);
makeHTML($endpoint_stats, $endpoints_desc);




/***************/
/** FUNCTIONS **/
/***************/
function print_usage($argv, $argc){
	$options = array(
		"instances_file" => "/instances/file/path/",
		"lsr_file" => "/path/to/lsr",
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
	if($options['instances_file'] == '/instances/file/path/'){
		echo "** Please specify a valid instances file **".PHP_EOL;
		exit;
	}
	if($options['lsr_file'] == '/path/to/lsr.csv'){
		echo "** Specify a valid LSR CSV file path. **".PHP_EOL;
		exit;
	}
	return $options;
}

function parseDescriptions($anEndpointArr, $anLsr_arr){
	$rm = array();

	foreach ($anEndpointArr as $endpoint_name => $val) {
		//search for $endpoint_name in anLsrArr and attach
		//its description to $rm
		//print_r($anLsr_arr);exit;
		if(array_key_exists($endpoint_name, $anLsr_arr)){
			$rm[$endpoint_name]['description'] = $anLsr_arr[$endpoint_name]['description'];
			$rm[$endpoint_name]['name'] = $anLsr_arr[$endpoint_name]['name'];
			$rm[$endpoint_name]['namespace'] = $anLsr_arr[$endpoint_name]['namespace'];
			$rm[$endpoint_name]['homepage'] = $anLsr_arr[$endpoint_name]['homepage'];
			$rm[$endpoint_name]['ident_regex_patt'] = $anLsr_arr[$endpoint_name]['ident_regex_patt'];
			$rm[$endpoint_name]['provider_html_url'] = $anLsr_arr[$endpoint_name]['provider_html_url'];
			$rm[$endpoint_name]['license_url'] = $anLsr_arr[$endpoint_name]['license_url'];
			$rm[$endpoint_name]['keywords'] = $anLsr_arr[$endpoint_name]['keywords'];
			$rm[$endpoint_name]['organization'] = $anLsr_arr[$endpoint_name]['organization'];
			$rm[$endpoint_name]['prefix'] = $anLsr_arr[$endpoint_name]['prefix'];
		}else{
			continue;
		}
	}
	return $rm;
}



/**
* This function parses the LSR and returns a multidimensional assoc array
*/
function readLSRIntoArr($aFn){
$fh = fopen($aFn, "r") or die("Could not open File ". $aFn);
	$returnMe = array();
	if($fh){
		while(($data = fgetcsv($fh, 1000, ","))!== FALSE){
			//now parse the data that we need
			$prefix =  @$data[0];
			$organization = @$data[11];
			$keywords = @$data[13];
			$license_url = @$data[19];
			$provider_html_url = @$data[23];
			$ident_regex_patt = @$data[21];
			$title = @$data[8];
			$description = @$data[9];
			$homepage = @$data[14];
			$example_id = @$data[22];
			$returnMe[$prefix]["ident_regex_patt"] = $ident_regex_patt;
			$returnMe[$prefix]["provider_html_url"] = $provider_html_url;
			$returnMe[$prefix]["license_url"] = $license_url;
			$returnMe[$prefix]["keywords"] = $keywords;
			$returnMe[$prefix]["organization"] = $organization;
			$returnMe[$prefix]["prefix"] = $prefix;
			$returnMe[$prefix]["name"] = $title;
			$returnMe[$prefix]["namespace"] = $prefix;
			$returnMe[$prefix]["description"] = $description;
			$returnMe[$prefix]["homepage"] = $homepage;
			$returnMe[$prefix]["id"] = $example_id;
		}
	}
	fclose($fh);
	return $returnMe;
}



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




function makeHTML($endpoint_stats, $endpoint_desc){
	//create one html file per endpoint
	foreach($endpoint_stats as $endpoint => $d){
		if(count($d) > 2){
			$desc = @$endpoint_desc[$endpoint];
			//create an output file
			$fo = fopen($endpoint.".html", "w") or die("Could not create file!");
			if($fo){
				$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"><html>';
				$html .= addHeader($endpoint);
				//add Bio2RDF logo
				//add Endpoint description
				//add link back to the statistics page
				$html .= "<body>";
				//add the logo
				$html .= addBio2RDFLogo();
				$html .= "<div id='description'>";
				$html .= addDatasetDescription($desc);
				$html .= addBio2RDFDetails($d['endpoint_url'], $desc['namespace']);
				$html .= "</div>";
				$html .= "<div id='container'> <div id='items'></div>";
				$html .= addBasicStatsTable($d['endpoint_url'],$d['triples'],$d['unique_subjects'],$d['unique_predicates'],$d['unique_objects'] );
				$html .= addUniqueTypesTable($d['endpoint_url'],$d['unique_types']);
				$html .= addPredicateObjLinks($d['endpoint_url'],$d['predicate_object_links']);
				$html .= addPredicateLiteralLinks($d['endpoint_url'],$d['predicate_literals']);
				$html .= addSubjectCountPredicateObjectCount($d['endpoint_url'],$d['subject_count_predicate_object_count']);
				$html .= addSubjectPredicateUniqueLits($d['endpoint_url'],$d['subject_count_predicate_literal_count']);
				$html .= addSubjectTypePredType($d['endpoint_url'],$d['subject_type_predicate_object_type']);
				$html .= addNSNSCounts($d['endpoint_url'], $d['nsnscounts']);
				$html .= "</div></body></html>";
				fwrite($fo, $html);
			}
			fclose($fo);
		}
	}
}

function addBio2RDFDetails($u, $ns){
	$rm = "";
	if($u != null && $ns != null){
		$rm .= "<p><strong>SPARQL Endpoint URL:</strong> <a href=\"".$u."\">".$u."</a></p>";
		$rm .= "<p><strong>Faceted Browser URL:</strong> <a href=\"http://cu.".$ns.".bio2rdf.org/fct\">http://cu.".$ns.".bio2rdf.org/fct</a></p>";
		$rm .= "<p><strong>Conversion Script URL:</strong> <a href=\"http://github.com/bio2rdf/bio2rdf-scripts/tree/master/".$ns."\">http://github.com/bio2rdf/bio2rdf-scripts/tree/master/".$ns."</a></p>";
		$rm .= "<p><strong>Download URL:</strong> <a href=\"http://download.bio2rdf.org/release/2/".$ns."\">http://download.bio2rdf.org/release/2/".$ns."</a></p>";
	}
	return $rm;
}

function addDatasetDescription($aDesc){
	$rm = "";
	if($aDesc != null && count($aDesc) > 0){
		if(isset($aDesc['namespace'])&&strlen($aDesc['name'])){
			$rm .= "<h3>".$aDesc['name']."</h3>";
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
			$rm .= "<p><strong>Homepage:</strong> <a href=\"".$aDesc['homepage']."\">".$aDesc['homepage']."</a></p>";	
		}
		if (isset($aDesc['organization'])&&strlen($aDesc['organization'])) {
			$rm .= "<p><strong>Organization:</strong> ".$aDesc['organization']."</p>";	
		}
		if (isset($aDesc['license_url'])&&strlen($aDesc['license_url'])) {
			$rm .= "<p><strong>License:</strong> ".$aDesc['license_url']."</p>";
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
	$rm = "";
	$rm .= '<div id="logo">
				<a  href="http://bio2rdf.org"><img src="https://googledrive.com/host/0B3GgKfZdJasrRnB0NDNNMFZqMUk/bio2rdf_logo.png" alt="Bio2RDF logo" /></a>
			</div>';
	$rm .= '<div id ="link">';
	$rm .= "<h1>Linked Data for the Life Sciences</h1>".PHP_EOL;
	$rm .= '<h2>-Release 2-</h2>';
	$rm .= '<h2>[<a href="http://bio2rdf.org" target="_blank">website</a>][<a href="http://download.bio2rdf.org/release/2/release.html" target="_blank">datasets</a>][<a href="http://github.com/bio2rdf/bio2rdf-scripts/wiki" target="_blank">documentation</a>]</h2>';
	$rm .= "</div>";
	return $rm;
}


function addNSNSCounts($eURL, $arr){
	$rm = "<hr><h2>Inter and Intra dataset namespace links</h2>";
	$rm .= "<table id='t'>";
	$rm .= "<thead><tr><th>Namespace</th><th>Namespace</th><th>Counts</th></tr></thead><tbody>";
	foreach($arr as $p => $c){
		$rm .= "<tr><td>".$c['ns1']."</td><td>".$c['ns2']."</td><td>".$c['count']."</td></tr>";
	}
	$rm .= "</tbody></table>";
	return $rm;
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
	$rm ="<h2>Basic data metrics</h2><table><thead><th></th><th></th></thead><tbody>";
	$rm .= "<tr><td>Endpoint URL</td><td><a href=\"".$endpoint_url."\">".$endpoint_url."</a></td></tr>";
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
				//nsns counts
				$nsnsJSON = trim(@file_get_contents(nsQ($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["nsnscounts"] = getNSNSCounts($nsnsJSON);
				//numOfTriples
				$numOfTriplesJson = trim(@file_get_contents(q1($endpoint_url,$graph_uri)));
				$endpoint_arr[$name]["triples"] = getNumOfTriples($numOfTriplesJson);
				//get the date
				$dateJson = trim(@file_get_contents(getDatasetDateQuery($endpoint_url)));
				$endpoint_arr[$name]['date'] = getDate2($dateJson);
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
function getNSNSCounts($aJS){
	$rm = array();
	$decoded = json_decode($aJS);
	if(isset($decoded->results)){
		$rr = $decoded->results;
		foreach($rr->bindings as $r){
			$key = $r->x->value;
			if(!array_key_exists($key, $rm)){
				$count = $r->count->value;
				$ns1 = $r->ns1->value;
				$ns2 = $r->ns2->value;
				$rm[$key] = array(
					'count' => $count,
					'ns1' => $ns1,
					'ns2' => $ns2,
					);
			}
		}
	}
	return $rm;
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

function nsQ($endpoint_url, $graph_url){
	$rm = "";
	if(strlen($endpoint_url) !=0 && strlen($graph_url) != 0){
		$t = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> select * from <";
		$t .= $graph_url."> where { ?x a data_vocab:Namespace_Namespace_Count.";
		$t .= " ?x <http://bio2rdforg/dataset_vocabulary:has_nsns_count_value> ?count.";
		$t .= " ?x data_vocab:namespace ?ns1.";
		$t .= " ?x data_vocab:namespace ?ns2.";
		$t .= " FILTER (?ns1 != ?ns2).}";
		$rm = $endpoint_url."?default-graph-uri=&query=".urlencode($t)."&format=json";
		return $rm;
	}else{
		return false;
	}
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

function getDatasetDateQuery($endpoint_url){
	$rm = "";
	if(strlen($endpoint_url) != 0){
		$t = "PREFIX data_vocab: <http://bio2rdf.org/dataset_vocabulary:> SELECT DISTINCT ?date";
		$t .= " WHERE { ?d a data_vocab:Endpoint. ?d data_vocab:has_url ?u. ?p <http://rdfs.org/ns/void#sparqlEndpoint> ?u. ?p <http://purl.org/dc/terms/created> ?date.}";
		$rm .= $endpoint_url."?default-graph-uri=&query=".urlencode($t)."&format=json";
		return $rm;
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
