<?php
/**
Copyright (C) 2011 Michel Dumontier

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

require_once(dirname(__FILE__).'/../common/php/libphp.php');

$options = null;
AddOption($options, 'indir', null, '/data/download/ncbo/', false);
AddOption($options, 'outdir',null, '/data/rdf/ncbo/', false);
AddOption($options, 'files',null,'all|ontology-short-name',true);
AddOption($options, 'exclude',null,'gaz.obo',false);
AddOption($options, 'ncbo_api_key',null,'24e19c82-54e0-11e0-9d7b-005056aa3316', false);
AddOption($options, 'download','true|false','false',false);
AddOption($options, 'overwrite','true|false','true',false);
AddOption($options, 'minimal','true|false','false',false);
AddOption($options, 'minimal+','true|false','false',false);
AddOption($options, CONF_FILE_PATH, null,'/bio2rdf-scripts/common/bio2rdf_conf.rdf', false);
AddOption($options, USE_CONF_FILE,'true|false','false', false);

if(SetCMDlineOptions($argv, $options) == FALSE) {
	PrintCMDlineOptions($argv, $options);
	exit;
}

$exclude_list = explode(",",$options['exclude']['value']);
@mkdir($options['indir']['value'],0777,true);
@mkdir($options['outdir']['value'],0777,true);

if($options['download']['value'] == 'true') {
   $files = Download($options['indir']['value']); 
}
if($options['files']['value']  != 'all') {
	// single file specified
	OBO2TTL($options['indir']['value'],$options['outdir']['value'],$options['files']['value']);
} else {	
	// directory
	$files = GetDirFiles($options['indir']['value'],".obo");
	if(isset($files)) {
		foreach($files AS $f) {
			if(!in_array($f,$exclude_list)) {
				if(($options['overwrite']['value'] == 'true')
					|| !file_exists($options['outdir']['value'].$f.'.ttl')) {
					OBO2TTL($options['indir']['value'],$options['outdir']['value'],$f);
				} else {
					echo "$f exists ... skipping".PHP_EOL;
				}
			} 
		}
		echo "Done!";
	} else {
		echo "No files to process in ".$options['indir']['value'].PHP_EOL;
	}
}


function OBO2TTL($indir,$outdir,$file)
{
 global $gns, $options;
 
 $infile = $indir.$file;
 $outfile = $outdir.$file.'.ttl';
 
 $in = fopen($infile,"r");
 if(FALSE === $in) {
	trigger_error("unable to open ".$infile);
	exit;
 }
 $out = fopen($outfile,"w");
 if(FALSE === $out) {
	trigger_error("unable to open ".$outfile);
	exit;
 }
 
 echo "Converting $infile to $outfile".PHP_EOL;

 if(FALSE !== ($pos = strrpos($infile,'\\'))) {
	$file = substr($infile,$pos+1);
 }else if(FALSE !== ($pos = strrpos($infile,'/'))) {
	$file = substr($infile,$pos+1);
 } else $file = $infile;
 $file .= ".ttl";
 $pos = strpos($file,".");
 $ontology = substr($file,0,$pos);
 
 $furi = "bio2rdf_resource:file/$file";
 $ouri = "registry:$ontology";

 $header = N3NSHeader();
 $buf = QQuad($furi,"rdf:type", "sio:Document");
 $buf .= QQuadL($furi,"rdfs:label","Turtle converted OBO file for $ontology ontology (obtained through NCBO Bioportal) [bio2rdf_resource:$file]");
 $buf .= QQuadL($furi,"dc:creator","Michel Dumontier");
 $buf .= QQuadL($furi,"sio:encodes",$ouri);
 $buf .= QQuad($ouri,"rdf:type","owl:Ontology");
 $buf .= QQuadL($ouri,"rdfs:label","$ontology ontology");
 $buf .= QQuad($ouri,"sio:is-encoded-by",$furi);
  
 $tid = '';
 $first = true;
 $is_a = false;
 $is_deprecated = false;
 $min = $buf;
 while($l = fgets($in)) {
	$lt = trim($l);
	if(strlen($lt) == 0) continue;
	if($lt[0] == '!') continue;
	
	if(strstr($l,"[Term]")) {	
		// top level node?
		if($first == true) { // ignore the first case
			$first = false;
		} else {
			if($tid != '' && $is_a == false && $is_deprecated == false) {
				$t = QQuad($tid,"rdfs:subClassOf","bio2rdf_vocabulary:Entity");
				$buf .= $t;
				$min .= $t;
			}
		}
		$is_a = false;
		$is_deprecated = false;
		
		unset($typedef);
		$term = '';
		$tid = '';
		continue;
	} else if(strstr($l,"[Typedef]")) {
		$is_a = false;
		$is_deprecated = false;
		
		unset($term);
		$tid = '';
		$typedef = '';
		continue;
	} 
	// to fix error in obo generator
	$lt = str_replace("synonym ","synonym: ",$lt);
	$a = explode(" !", $lt);
	if(isset($a[1])) $exc = $a[1];
	$a = explode(": ",$a[0],2);

	// let's go
	if(isset($intersection_of)) {
		if($a[0] != "intersection_of") {
			$intersection_of .= ")].".PHP_EOL;
			$buf .= $intersection_of;
			if($options['minimal+']['value'] == 'true') $min .= $intersection_of;
			unset($intersection_of);
		}
	}
	if(isset($relationship)) {
		if($a[0] != "relationship") {
			$relationship .= ")].".PHP_EOL;
			$buf .= $relationship;
			if($options['minimal+']['value'] == 'true') $min .= $relationship;
			unset($relationship);
		}
	}

	if(isset($typedef)) {	
		if($a[0] == "id") {
			$c = explode(":",$a[1]);
			if(count($c) == 1) {$ns = "obo";$id=$c[0];}
			else {$ns = strtolower($c[0]);$id=$c[1];}
			$id = str_replace( array("(",")"), array("_",""), $id);
			$tid = $ns.":".$id;
			$header .= AddToGlobalNS($ns);
			$buf .= QQuadL($tid,"dc:identifier",$tid);
		} else if($a[0] == "name") {
			$buf .= QQuadL($tid,"rdfs:label", addslashes(stripslashes($a[1]))." [$tid]");
		} else if($a[0] == "is_a") {
			if(FALSE !== ($pos = strpos($a[1],"!"))) $a[1] = substr($a[1],0,$pos-1);
			$buf .= QQuad($tid,"rdfs:subPropertyOf","obo:".strtolower($a[1]));
		} else if($a[0] == "is_obsolete") {
			$buf .= QQuad($tid, "rdf:type", "owl:DeprecatedClass");
			$is_deprecated = true;
		} else {
			if($a[0][0] == "!") $a[0] = substr($a[0],1);
			$buf .= QQuadL($tid,"obo:$a[0]", str_replace('"','',stripslashes($a[1])));
		}

	} else if(isset($term)) {
		if($a[0] == "is_obsolete" && $a[1] == "true") {
			$t = QQuad($tid, "rdf:type", "owl:DeprecatedClass");
			$t .= QQuad($tid, "rdfs:subClassOf", "owl:DeprecatedClass");
			
			$min .= $t;
			$buf .= $t;
			$is_deprecated = true;
		} else if($a[0] == "id") {	
			ParseQNAME($a[1],$ns,$id);
			$header .= AddToGlobalNS($ns);
			$tid = $ns.":".$id;
			$buf .= QQuad($tid,"rdfs:isDefinedBy",$ouri);
			$buf .= QQuadL($tid,"dc:identifier",$tid);
			
		} else if($a[0] == "name") {
			$t = QQuadL($tid,"rdfs:label",str_replace(array("\"", "'"), array("","\\\'"), stripslashes($a[1]))." [$tid]");
			$min .= $t;
			$buf .= $t;
			
		} else if($a[0] == "def") {
			$t = str_replace(array("'", "\"", "\\","\\\'"), array("\\\'", "", "",""), $a[1]);
			$min .= QQuadL($tid,"dc:description",$t);
			$buf .= QQuadL($tid,"dc:description",$t);
			
		} else if($a[0] == "property_value") {
			$b = explode(" ",$a[1]);
			$buf .= QQuadL($tid,"obo_vocabulary:".strtolower($b[0]),str_replace("\"", "", strtolower($b[1])));
		} else if($a[0] == "xref") {
		// http://upload.wikimedia.org/wikipedia/commons/3/34/Anatomical_Directions_and_Axes.JPG
		// Medical Dictionary:http\://www.medterms.com/
		// KEGG COMPOUND:C02788 "KEGG COMPOUND"
		
			// first get the comment
			if(FALSE !== ($pos = strpos($a[1],'"'))) {
				$comment = substr($a[1],$pos+1,-1);
				$identifier = substr($a[1],0,$pos-1);
			} else {
				$identifier = $a[1];
			}
			// next identify the namespace and identifier
			if(FALSE !== ($pos = strpos($identifier,":"))) {
				$id = substr($identifier,$pos+1);
				$raw_ns = strtolower(substr($identifier,0,$pos));
			
				// the raw ns is likely to be very dirty
				// should map to the registry
				// but for now, just add this namespace
				$ns = str_replace(" ","_",$raw_ns);
				$header .= AddToGlobalNS($ns);
				if(strstr($id,"http")) {
					$buf .= Quad(GetFQURI($tid),GetFQURI("rdfs:seeAlso"), stripslashes($id));
				} else {
					$buf .= QQuad($tid,"rdfs:seeAlso", strtolower($ns).":".str_replace(" ","&nbsp;",stripslashes($id)));
				}
			}	
			
		} else if($a[0] == "synonym") {
			// synonym: "entidades moleculares" RELATED [IUPAC:]
			// synonym: "molecular entity" EXACT IUPAC_NAME [IUPAC:]
			// synonym: "Chondrococcus macrosporus" RELATED synonym [NCBITaxonRef:Krzemieniewska_and_Krzemieniewski_1926]
			
			//grab string inside double quotes			
			preg_match('/"(.*)"(.*)/', $a[1], $matches);
			
			if(!empty($matches)){
				$a[1] = str_replace(array("\\", "\"", "'"),array("", "", "\\\'"), $matches[1].$matches[2]);
			} else {
				$a[1] = str_replace(array("\"", "'"), array("", "\\\'"), $a[1]);
			}
			
			$rel = "SYNONYM";
			$list = array("EXACT","BROAD","RELATED","NARROW");
			$found = false;
			foreach($list AS $keyword) {
			  // get everything after the keyword up until the bracket [
			  if(FALSE !== ($k_pos = strpos($a[1],$keyword))) {
				$str_len = strlen($a[1]);
				$keyword_len = strlen($keyword);
				$keyword_end_pos = $k_pos+$keyword_len;
				$b1_pos = strrpos($a[1],"[");
				$b2_pos = strrpos($a[1],"]");					
				$b_text = substr($a[1],$b1_pos+1,$b2_pos-$b1_pos-1);					
				$diff = $b1_pos-$keyword_end_pos-1;
				if($diff != 0) {
					// then there is more stuff here
					$k = substr($a[1],$keyword_end_pos+1,$diff);
					$rel = trim($k);
				} else {
					// create the long predicate
					$rel = $keyword."_SYNONYM";
				}
				$found=true;
				$str = substr($a[1],0,$k_pos-1);
				break;
			   }
			}	

			// check to see if we still haven't found anything
			if($found === false) {
				// we didn't find one of the keywords
				// so take from the start to the bracket
				$b1_pos = strrpos($a[1],"[");
				$str = substr($a[1],0,$b1_pos-1);
			 } 
			   
			$rel = str_replace(" ","_",$rel);
			// $lit = addslashes($str.($b_text?" [".$b_text."]":""));
			$l = QQuadL($tid,"obo_vocabulary:".strtolower($rel), $str);
			$buf .= $l;
			
		} else if($a[0] == "alt_id") {
			ParseQNAME($a[1],$ns,$id);
			if($id != 'curators') {
				$header .= AddToGlobalNS($ns);	
				$buf .= QQuad("$ns:$id","rdfs:seeAlso",$tid);
			}
			
		} else if($a[0] == "is_a") {
			// do subclassing
			ParseQNAME($a[1],$ns,$id);
			$header .= AddToGlobalNS($ns);	
			$t = QQuad($tid,"rdfs:subClassOf","$ns:$id");
			$buf .= $t;
			$min .= $t;
			$is_a = true;
			
		} else if($a[0] == "intersection_of") {
			if(!isset($intersection_of)) {
				$intersection_of = GetFQURITTL($tid).' '.GetFQURITTL('owl:equivalentClass').' ['.GetFQURITTL('rdf:type').' '.GetFQURITTL('owl:Class').'; '.GetFQURITTL('owl:intersectionOf');
			}
			
			/*
			intersection_of: develops_from VAO:0000092 ! chondrogenic condensation
			intersection_of: OBO_REL:has_part VAO:0000040 ! cartilage tissue
			*/
			$c = explode(" ",$a[1]);
			if(count($c) == 1) { // just a class					
				ParseQNAME($c[0],$ns,$id);
				$header .= AddToGlobalNS($ns);
				$intersection_of .= GetFQURITTL("$ns:$id");
			} else if(count($c) == 2) { // an expression						
				ParseQNAME($c[0],$pred_ns,$pred_id);
				$header .= AddToGlobalNS($pred_ns);
				ParseQNAME($c[1],$obj_ns,$obj_id);
				$header .= AddToGlobalNS($obj_ns);

				$intersection_of .= ' ['.GetFQURITTL('owl:onProperty').' '.GetFQURITTL("obo:".$pred_id).'; '.GetFQURITTL('owl:someValuesFrom').' '.GetFQURITTL("$obj_ns:$obj_id").'] ';
			}

		} else if ($a[0] == "relationship") {
			if(!isset($relationship)) {
				$relationship = GetFQURITTL($tid).' '.GetFQURITTL('rdfs:subClassOf').' ['.GetFQURITTL('rdf:type').' '.GetFQURITTL('owl:Class').'; '.GetFQURITTL('owl:intersectionOf');
			}
			
			/*
			relationship: develops_from VAO:0000092 ! chondrogenic condensation
			relationship: OBO_REL:has_part VAO:0000040 ! cartilage tissue
			*/
			$c = explode(" ",$a[1]);
			if(count($c) == 1) { // just a class	
				ParseQNAME($c[0],$ns,$id);
				$header .= AddToGlobalNS($ns);
				$relationship .= GetFQURITTL("$ns:$id");

				} else if(count($c) == 2) { // an expression						
				ParseQNAME($c[0],$pred_ns,$pred_id);
				$header .= AddToGlobalNS($pred_ns);
				ParseQNAME($c[1],$obj_ns,$obj_id);
				$header .= AddToGlobalNS($obj_ns);

				$relationship .= ' ['.GetFQURITTL('owl:onProperty').' '.GetFQURITTL("obo:".$pred_id).'; '.GetFQURITTL('owl:someValuesFrom').' '.GetFQURITTL("$obj_ns:$obj_id").'] ';
			}
		} else {
			// default handler
			$buf .= QQuadL($tid,"obo:$a[0]", str_replace(array("\"", "'"), array("", "\\\'") ,stripslashes($a[1])));
		}
	} else {
		//header
		//format-version: 1.0
		$a = explode(": ",trim($l));
		$buf .= QQuadL($ouri,"obo:$a[0]",str_replace( array('"','\:'), array('\"',':'), isset($a[1])?$a[1]:""));
	}
	fwrite($out,$header);
	if($options['minimal']['value'] == 'true' || $options['minimal+']['value'] == 'true') fwrite($out,$min);
	else fwrite($out,$buf);
	$min = '';$buf ='';$header='';
 }
 if(isset($intersection_of))  $buf .= $intersection_of.")].".PHP_EOL;
 if(isset($relationship))  $buf .= $relationship.")].".PHP_EOL;

 fclose($in);
 if($options['minimal']['value'] == 'true' || $options['minimal+']['value'] == 'true') fwrite($out,$min);
 else fwrite($out,$buf);
 fclose($out);
}


function Download($dir)
{
 global $options;
 $remote_ontolist = 'http://rest.bioontology.org/bioportal/ontologies?apikey='.$options['ncbo_api_key']['value'];
 $local_ontolist = "ontolist.xml";
 if(!file_exists($local_ontolist)) {
	$c = file_get_contents($remote_ontolist);
	file_put_contents($local_ontolist,$c);
 } else {
	$c = file_get_contents($local_ontolist);
 }
 
 $root = simplexml_load_string($c);
 if($root === FALSE) {
  trigger_error("Error in opening $ontolist");
  exit;
 }

 $data = $root->data->list;
 foreach($data->ontologyBean AS $d) {
   $oid = (string) $d->ontologyId;
   $label = (string) $d->displayLabel;
   $abbv = (string) strtolower($d->abbreviation);
   if(!$abbv) {
	$a = explode(" ",$label);
	if(count($a) == 1) $abbv = strtolower($label);
	else {
 	 foreach($a AS $b) {
	  $abbv .= $b[0];
     }
     $abbv = strtolower($abbv);
	}
   }
   $format = (string) $d->format;
   if($format == "PROTEGE") continue;
   $suf = strtolower($format);
   if($suf == "owl-dl") $suf = "owl";
   if($suf == "owl-full") $suf = "owl";
   if($suf == "umls-rela" || $suf == "rrf") {
	$suf = "zip";
	continue;  // we can't manage these yet
   }

   echo "Downloading $label ($abbv id=$oid) ... ";
   $onto_url = 'http://rest.bioontology.org/bioportal/virtual/download/'.$oid.'?apikey='.$options['ncbo_api_key']['value'];
   $onto = file_get_contents($onto_url);
   if($onto !== FALSE) {
     $file = $dir.$abbv.".".$suf;
     file_put_contents($file,$onto);
	 echo " to ".$file;
   }
   echo "\n";
 
 }
}

?>

