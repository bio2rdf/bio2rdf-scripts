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
 "instances_file" => "/path/to/instances.tab", //virtuoso instances file
 "lsr_file" => "/path/to/lsr.csv", //dataset registry csv file
);

// show command line options
if($argc == 1) {
 echo "Usage: php $argv[0] ".PHP_EOL;
 foreach($options AS $key => $value) {
  echo " $key=$value ".PHP_EOL;
 }
 exit;
}

// set options from user input
foreach($argv AS $i => $arg) {
 if($i==0) continue;
 $b = explode("=",$arg);
 if(isset($options[$b[0]])) $options[$b[0]] = $b[1];
 else {echo "unknown key $b[0]";exit;}
}

if($options['instances_file'] == '/path/to/instances.tab'){
	echo "** Specify a valid Virtuoso instances file. **".PHP_EOL;
	exit;
}

if($options['lsr_file'] == '/path/to/lsr.csv'){
	echo "** Specify a valid LSR CSV file. **".PHP_EOL;
	exit;
}

$arr = parseBoth($options['instances_file'], $options['lsr_file']);
$table = htmlPrinter($arr);
echo $table;

/**
* This function will first parse the instances.tab file and 
* get the data metrics for each endpoint. The returned array will then 
* be merged by prefix name with the LSR and one multidimensional array will 
* be returned
*/
function parseBoth($instances_fn, $lsr_fn){
	$returnMe = array();
	$instances_arr = parseInstancesTabFile($instances_fn);
	$lsr_arr = parseLSR($lsr_fn);
	$returnMe = joinLSRAndInstancesTab($instances_arr, $lsr_arr);
	return $returnMe;
}

function joinLSRAndInstancesTab($instances_arr, $lsr_arr){
	$returnMe = array();
	foreach($instances_arr as $k => $v){
		if(array_key_exists($k, $lsr_arr)){
			$returnMe[$k]["metrics"] = $v;
			$returnMe[$k]["details"] = $lsr_arr[$k];
		} else {
			$returnMe[$k]["metrics"] = $v;
			if($k == "atlas"){
				$details = array("name" => "Bio2RDF Atlas", "namespace" => "atlas", "description" => "The Bio2RDF Atlas mashup", "homepage" => "", "id" => "");
				$returnMe[$k]["details"] = $details;
			} elseif($k == "hhpid"){
				$details = array("name" => "Bio2RDF  HIV-1 human protein interactions", "namespace" => "hhpid", "description" => "A database of HIV-1 human protein interactions that was created to catalog all interactions between HIV-1 and human proteins published in the peer-reviewed literature. The database serves the scientific community exploring the discovery of novel HIV vaccine candidates and therapeutic targets.", "homepage" => "", "id" => "");
				$returnMe[$k]["details"] = $details;
			} elseif($k == "toxkb"){
				$details = array("name" => "Toxicogenomics Knowledge Base", "namespace" => "toxkb", "description" => "A toxicogenomics database mashup.", "homepage" => "", "id" => "");
				$returnMe[$k]["details"] = $details;
			}
		}
	}
	return $returnMe;
}

function getMetricsCSV($anEndpointURL){
	$query = "?default-graph-uri=&query=SELECT+DISTINCT+%3Fdataset+%3Ftc+%3Fsc+%3Fpc+%3Foc+%3Fdate+where+%7B%0D%0A%3Fdataset+a+<http%3A%2F%2Fbio2rdf.org%2Fdataset_vocabulary%3AEndpoint>+.%0D%0A%3Fdataset+<http%3A%2F%2Fbio2rdf.org%2Fdataset_vocabulary%3Ahas_triple_count>+%3Ftc+.%0D%0A%3Fdataset+<http%3A%2F%2Fbio2rdf.org%2Fdataset_vocabulary%3Ahas_unique_subject_count>+%3Fsc+.%0D%0A%3Fdataset+<http%3A%2F%2Fbio2rdf.org%2Fdataset_vocabulary%3Ahas_unique_predicate_count>+%3Fpc+.%0D%0A%3Fdataset+<http%3A%2F%2Fbio2rdf.org%2Fdataset_vocabulary%3Ahas_unique_object_count>+%3Foc+.%0D%0A%3Fdataset+<http%3A%2F%2Fbio2rdf.org%2Fdataset_vocabulary%3Ahas_url>+%3FsparqlURL+.%0D%0A%3Fprov+<http%3A%2F%2Frdfs.org%2Fns%2Fvoid%23sparqlEndpoint>+%3FsparqlURL+.%0D%0A%3Fprov+<http%3A%2F%2Fpurl.org%2Fdc%2Fterms%2Fcreated>+%3Fdate+.%0D%0A%7D&format=text%2Fcsv&timeout=0&debug=on";
	if(($str = @file_get_contents($anEndpointURL.$query)) !== FALSE){
		return $str;
	}
	return $str;
}

/**
* This function parses the LSR and returns a multidimensional assoc array
*/
function parseLSR($aFn){
	$fh = fopen($aFn, "r") or die("Could not open File ". $aFn);
	$returnMe = array();
	if($fh){
		while(($data = fgetcsv($fh, 1000, ","))!== FALSE){
			//now parse the data that we need
			$prefix =  @$data[0];
			$title = @$data[8];
			$description = @$data[9];
			$homepage = @$data[14];
			$example_id = @$data[22];
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

/**
* This function returns an array of instance names
*/
function parseInstancesTabFile($instances_tab_fn){
	$returnMe = array();
	$fh = fopen($instances_tab_fn, "r") or die("Could not open file ".$instances_tab_fn);
	if($fh){
		while(($aLine = fgets($fh, 4096)) !== false ){
			//only read those lines that do not start with "#"
			if(substr($aLine, 0, 1) != "#"){
				$lineArr = explode("\t", $aLine);
				if(count($lineArr) == 4){
					$name = trim($lineArr[2]);
					if($name == "atlas"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "30993206";
						$returnMe[$name]["subjects"] = "1558650";
						$returnMe[$name]["predicates"] = "256";
						$returnMe[$name]["objects"] = "12291465";
						$returnMe[$name]["date"] = "2012-10-15";
					} elseif($name == "bioportal") {
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "15384622";
						$returnMe[$name]["subjects"] = "4425342";
						$returnMe[$name]["predicates"] = "191";
						$returnMe[$name]["objects"] = "7668644";
						$returnMe[$name]["date"] = "2012-10-07";
					} elseif($name == "gene"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "394026267";
						$returnMe[$name]["subjects"] = "12543449";
						$returnMe[$name]["predicates"] = "60";
						$returnMe[$name]["objects"] = "121538103";
						$returnMe[$name]["date"] = "2012-10-06";
					} elseif($name == "genbank"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "";
						$returnMe[$name]["subjects"] = "";
						$returnMe[$name]["predicates"] = "";
						$returnMe[$name]["objects"] = "";
						$returnMe[$name]["date"] = "2010-08-03";
					} elseif($name == "hhpid"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "241317";
						$returnMe[$name]["subjects"] = "18687";
						$returnMe[$name]["predicates"] = "14";
						$returnMe[$name]["objects"] = "9882";
						$returnMe[$name]["date"] = "2012-10-15";
					} elseif($name == "kegg"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "49850774";
						$returnMe[$name]["subjects"] = "11816543";
						$returnMe[$name]["predicates"] = "33";
						$returnMe[$name]["objects"] = "8338160";
						$returnMe[$name]["date"] = "2012-10-15";
					} elseif($name == "mgi"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "2454589";
						$returnMe[$name]["subjects"] = "250933";
						$returnMe[$name]["predicates"] = "27";
						$returnMe[$name]["objects"] = "1178016";
						$returnMe[$name]["date"] = "2012-10-15";
					} elseif($name == "pubmed"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "370812866";
						$returnMe[$name]["subjects"] = "72563751";
						$returnMe[$name]["predicates"] = "245";
						$returnMe[$name]["objects"] = "90175085";
						$returnMe[$name]["date"] = "2012-09-24";
					} elseif($name == "taxonomy"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "17814216";
						$returnMe[$name]["subjects"] = "965020";
						$returnMe[$name]["predicates"] = "33";
						$returnMe[$name]["objects"] = "2467675";
						$returnMe[$name]["date"] = "2012-10-06";
					} elseif($name == "toxkb"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "1838495";
						$returnMe[$name]["subjects"] = "551854";
						$returnMe[$name]["predicates"] = "39";
						$returnMe[$name]["objects"] = "576416";
						$returnMe[$name]["date"] = "";
					} elseif($name == "refseq"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "4242289458";
						$returnMe[$name]["subjects"] = "399870058";
						$returnMe[$name]["predicates"] = "";
						$returnMe[$name]["objects"] = "";
						$returnMe[$name]["date"] = "2010-08-04";
					} elseif($name == "uniprot"){
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "cu.http://download.bio2rdf.org/release/2/".$name."/";
						$returnMe[$name]["triples"] = "";
						$returnMe[$name]["subjects"] = "";
						$returnMe[$name]["predicates"] = "";
						$returnMe[$name]["objects"] = "";
						$returnMe[$name]["date"] = "2010-08-05";
					} else {
						$returnMe[$name]["sparql"] = "http://cu.".$name.".bio2rdf.org/sparql";
						$returnMe[$name]["fct"] = "http://cu.".$name.".bio2rdf.org/fct";
						$returnMe[$name]["download"] = "http://download.bio2rdf.org/release/2/".$name."/";
						//now add the metrics
						$metrics = getMetricsCSV($returnMe[$name]["sparql"]);
						if(strlen($metrics)){
							$ma = explode("\n", $metrics);
							$x = explode(",", $ma[1]);
							if(count($x) == 6){
								$returnMe[$name]["triples"] = str_replace("\"", "", $x[1]);
								$returnMe[$name]["subjects"] = str_replace("\"", "", $x[2]);
								$returnMe[$name]["predicates"] = str_replace("\"", "", $x[3]);
								$returnMe[$name]["objects"] = str_replace("\"", "", $x[4]);
								$returnMe[$name]["date"] = str_replace("\"", "", $x[5]);
							}//if
						}//if
					}//else
				}
			}
		}//while
	}//if
	fclose($fh);
	return $returnMe;
}//parseInstancesTabFile


function htmlPrinter($endpoints){
	$returnMe = "<html><head><title>Bio2RDF endpoint details</title></head><style type=\"text/css\">

body
{
	line-height: 1.6em;
	width: 80%;
	margin-left:auto;
	margin-right:auto;
}

table{
    table-layout: fixed;
}

#description {
	width: 200px;
}

table.hor-minimalist-a
{
	font-family: \"Lucida Sans Unicode\", \"Lucida Grande\", Sans-Serif;
	font-size: 12px;
	background: #fff;
	margin: 45px;
	width: 480px;
	border-collapse: collapse;
	text-align: left;
}

table.hor-minimalist-a th
{
	font-size: 14px;
	font-weight: normal;
	color: #039;
	padding: 10px 8px;
	border-bottom: 2px solid #6678b1;
}

table.hor-minimalist-a td
{
	color: #669;
	padding: 9px 8px 0px 8px;
	vertical-align:text-top;
}

table.hor-minimalist-a tr.d0 td {
	background-color: #FFFFFF;
}

table.hor-minimalist-a tr.d1 td {
	background-color: #F0F8FF;
}

table.hor-minimalist-a tbody tr:hover td
{
	color: #009;
}

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

#links{
	font-family: \"Lucida Sans Unicode\", \"Lucida Grande\", Sans-Serif;
	font-size: 12px;
	color:#039;
	background: #fff;
	margin-left:auto;
	margin-right:auto;
	text-align: center;
}

</style><body>";
	$returnMe .= "<div id=\"links\">";
	$returnMe .= "<h2>Homepage: <a href=\"http://bio2rdf.org\">http://bio2rdf.org</a></h2>".PHP_EOL;
	$returnMe .= "<h2>GitHub: <a href=\"http://github.com/bio2rdf\">http://github.com/bio2rdf</a></h2>".PHP_EOL;
	$returnMe .= "<h2>Wiki: <a href=\"http://github.com/bio2rdf/bio2rdf-scripts/wiki\">http://github.com/bio2rdf/bio2rdf-scripts/wiki</a></h2>".PHP_EOL;
	$returnMe .= "</div>";
	$returnMe .= "<table class=\"hor-minimalist-a\">".PHP_EOL;
	$returnMe .= "<thead><tr><th width=\"40\"></th><th width=\"300\">Dataset</th><th width=\"100\">Date generated</th><th width=\"100\"># of triples</th><th width=\"100\"># of unique subjects</th><th width=\"100\"># of unique predicates</th><th width=\"100\"># of unique objects</th></tr></thead>".PHP_EOL;
	$i = 0;
	foreach ($endpoints as $endpoint => $endpoint_details) {
		$i++;
		$returnMe .= tablePrinter($endpoint_details, $i);
	}
	$returnMe .= "</table>";
	return $returnMe."</body></html>";
}

function tablePrinter($rawData, $rowNum){
	if( $odd = $rowNum%2 ){
		$rm = "<tr class=\"d1\">";
	} else {
   		$rm = "<tr class=\"d0\">";
	}
	
	$rm .= "<td>".$rowNum."</td>";
	$rm .= "<td width=\"300\"><strong>".$rawData["details"]["name"]." [".$rawData["details"]["namespace"]."]</strong><br />";
	$rm .= $rawData["details"]["description"]."<br />";
	if(!empty($rawData["details"]["homepage"])){
		$rm .= "<strong>Homepage:</strong> <a href=\"".$rawData["details"]["homepage"]."\">".$rawData["details"]["homepage"]."</a><br />";
	}
	if(!empty($rawData["details"]["id"])){
		$rm .= "<strong>Example ID:</strong> ".$rawData["details"]["id"]."<br />";
	}
	$rm .= "<strong>Links:</strong> "."<a href=\"".$rawData["metrics"]["fct"]."\">SEARCH</a>";
	$rm .= " <a href=\"".$rawData["metrics"]["sparql"]."\">QUERY</a>";
	$rm .= " <a href=\"".$rawData["metrics"]["download"]."\">DOWNLOAD</a>";
	$rm .= " <a href=\"".$rawData["metrics"]["download"].$rawData["details"]["namespace"].".html\" target=\"_blank\">METRICS</a>";
	$rm .= "</td>";
	$rm .= "<td width=\"100\">".$rawData["metrics"]["date"]."</td>";
	$rm .= "<td width=\"100\">".$rawData["metrics"]["triples"]."</td>";
	$rm .= "<td width=\"100\">".$rawData["metrics"]["subjects"]."</td>";
	$rm .= "<td width=\"100\">".$rawData["metrics"]["predicates"]."</td>";
	$rm .= "<td width=\"100\">".$rawData["metrics"]["objects"]."</td>";
	
	$rm .= "</tr>".PHP_EOL;
	return $rm;
}
?>
