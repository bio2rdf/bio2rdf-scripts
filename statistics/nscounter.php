<?php 
/**
Copyright (C) 2012 Michel Dumontier, Jose Cruz-Toledo

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
 * 1. Counts unique occurences in a Bio2RDF namespace
 * 2. Counts all occurences of a ns-ns predicate
*/


// show command line options
if($argc < 4) {
 echo "Usage: php $argv[0] dataset input-ntriples-file output-directory".PHP_EOL;
 exit;
}
$d = $argv[1];
$i = $argv[2];
$o = $argv[3];

if(!file_exists($i)) {
	trigger_error("Unable to open $i");
	exit;
}
if(!@is_dir($o)) {
	trigger_error("$o not a directory!");
	exit;
}

NSCount($d,$i,$o);

function NSCount($dataset, $inputFileName, $outdir){
	$pi = pathinfo($inputFileName);
	if($pi["extension"] == "gz"){
		$fh = gzopen($inputFileName, "r") or die("Could not open file ".$inputFileName);
	}elseif ($pi["extension"] != "gz" && $pi["extension"] !="zip") {
		$fh = fopen($inputFileName, "r") or die ("Could not open file ".$inputFileName);
	}
	
	while(($l = fgets($fh, 100000)) !== false){
		// get the triple
		preg_match_all('/\<([^\>]+)\>/',$l,$m);
		if(count($m) < 2) continue; 
		$s = $m[1][0];
		$p = $m[1][1];
		$o = null;
		if(isset($m[1][2])) $o = $m[1][2];
		
		// parse bio2rdf namespaces from the subject / object uris
		// increment the namespace counter if we haven't already seen this uri
		// subject
		if(isset($s)) {
			preg_match('/http\:\/\/bio2rdf\.org\/([^:]+)/',$s,$m);
			if(!isset($m[1])) continue; // not a bio2rdf uri
			else $ns1 = $m[1]; // str_replace(array("_resource","_vocabulary"),"",$m[1]);
			if(!isset($entity[$s])) {
				$entity[$s] = true;
				if(!isset($ns[$ns1])) $ns[$ns1] = 1;
				else $ns[$ns1] += 1;
			}
		}

		// object
		if(isset($o)) {
			preg_match('/http\:\/\/bio2rdf\.org\/([^:]+)/',$o,$m);
			if(!isset($m[1])) continue; // not a bio2rdf uri
			else $ns2 = $m[1];	
			
			if(!isset($entity[$o])) {
				$entity[$o] = true;
				if(!isset($ns[$ns2])) $ns[$ns2] = 1;
				else $ns[$ns2] += 1;
			}
	
			// increment the ns-ns counter
			$key = $ns1.$ns2;
			if(!isset($nsns[$key])) $nsns[$key] = array("ns1"=>$ns1,"ns2"=>$ns2,"count"=>1);
			else $nsns[$key]['count'] += 1;
			
			// increment the ns-ns-predicate counter
			$key = $ns1.$ns2.$p;
			if(!isset($nsnsp[$key])) $nsnsp[$key] = array("ns1"=>$ns1,"ns2" =>$ns2,"p" =>$p,"count"=>1);
			else $nsnsp[$key]['count'] += 1;
		}
	}//while
	fclose($fh);

	ksort($ns);
	$buf = '';
	foreach($ns AS $k => $v) $buf .= "$k\t$v\n";
	file_put_contents($outdir.$dataset."_ns.tab",$buf);
	
	ksort($nsns);
	$buf = '';
	foreach($nsns AS $k => $o) $buf .= $o['ns1']."\t".$o['ns2']."\t".$o['count']."\n";
	file_put_contents($outdir.$dataset."_nsns.tab",$buf);
	
	ksort($nsnsp);
	$buf = '';
	foreach($nsnsp AS $k => $o) $buf .= $o['ns1']."\t".$o['ns2']."\t".$o['p']."\t".$o['count']."\n";
	file_put_contents($outdir.$dataset."_nsnsp.tab",$buf);
	
} // NSCOUNT


?>
