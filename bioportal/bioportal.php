<?php
/**
Copyright (C) 2011-2012 Michel Dumontier

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
 * An RDF generator for NCBO Bioportal listed ontologies
 * documentation: 
 * @version 1.0
 * @author Michel Dumontier
*/
require_once(__DIR__.'/../../arc2/ARC2.php'); // available on git @ https://github.com/semsol/arc2.git

class BioportalParser extends Bio2RDFizer
{
	function __construct($argv) {
		parent::__construct($argv,'bioportal');
		parent::addParameter('files',true,null,'all','all or comma-separated list of ontology short names to process');
		parent::addParameter('download_url',false,null,'http://rest.bioontology.org/bioportal/ontologies');
		
		parent::addParameter('exclude',false,null,'gaz,cco','ontologies to ignore');
		parent::addParameter('ncbo_api_key',false,null,'24e19c82-54e0-11e0-9d7b-005056aa3316','BioPortal API key (please use your own)');
		parent::addParameter('detail',false,'min|min+|max','max','min:generate rdfs:label and rdfs:subClassOf axioms; min+: min + owl axioms');
		
		parent::initialize();
		return TRUE;
	}
	
	
	function Run()
	{
		$idir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		
		// get the list of ontologies from bioportal
		$olist = $idir."ontolist.xml";		
		if(!file_exists($olist) || parent::getParameterValue('download') == 'true') {
			echo "downloading ontology list...";
			$r_olist = parent::getParameterValue('download_url').'?apikey='.parent::getParameterValue('ncbo_api_key');
			file_put_contents($olist, file_get_contents($r_olist));
			echo "done".PHP_EOL;
		}
		
		// include
		if(parent::getParameterValue('files') == 'all') {
			$include_list = array('all');
		} else {
			$include_list = explode(",",parent::getParameterValue('files'));
		}
		
		// exclude
		if(parent::getParameterValue('exclude') != '') {
			$exclude_list = explode(",",parent::getParameterValue('exclude'));
		}
		
		// now go through the list of ontologies
		$c = file_get_contents($olist);
		if(($root = simplexml_load_string($c)) === FALSE) {
			trigger_error("Error in reading $olist",E_USER_ERROR);
			return FALSE;
		}

		$data = $root->data->list;
		foreach($data->ontologyBean AS $d) {
			$oid = (string) $d->ontologyId;			
			$label = (string) $d->displayLabel;
			$abbv = (string) strtolower($d->abbreviation);
			$format = strtolower((string) $d->format);
			$filename = (string) $d->filenames->string;
			if(strstr($filename,".zip")) $zip = true;
			else $zip = false;
			
			$ns = $this->getRegistry()->getNamespaceFromId($oid,'bioportal');
			if(!$ns) $ns = $abbv;
			// echo "$oid [$ns] -- $abbv --  $label".PHP_EOL;

			if($include_list[0] != 'all') {
				// ignore if we don't find it in the include list OR we do find it in the exclude list
				if( (array_search($ns,$include_list) === FALSE)
					|| (array_search($ns,$exclude_list) !== FALSE) ) {
					 //echo "skipping $label ($abbv id=$oid format=$format)".PHP_EOL;
					continue;
				}
			}
			
			$suf = '';
			if($format == 'obo') $suf = 'obo';
			if($format == "owl") $suf = "owl";
			if($format == "owl-dl") $format = $suf = "owl";
			if($format == "owl-full") $format = $suf = "owl";
			if($format == "LEXGRID-XML") $format = $suf = "xml";
			if($format == 'protege' || $format == 'umls-rela' || $format == "rrf") {
				continue;  // we can't manage these yet
			}
			
			// check and see if there is more than one file, if so, zip.
			unset($ofile);
			$files = count($d->filenames->string);
			if($files == 1) {
				if(!$zip) $lfile = $idir.$ns.".".$suf.".gz";
				else $lfile = $idir.$ns.".".$suf;
				$ofile = $odir.$ns.".".parent::getParameterValue('output_format');
			} else {
				// probably a zipfile
				$zip = true;
				$lfile = $idir.$ns.".zip";
			}

			// download
			if(!file_exists($lfile)|| parent::getParameterValue('download') == 'true') {
				if(in_array($oid, array(1114,1029,1144,1052,1013,1011,1369,1249,1490,1544,1576,1578,1627,1630,1649,1655,1656,1661,1670,1694,1697,3007,3017,3032,3038,3043,3045,3047,3062,3092,3094,3104,3136,3146,3147,3157,3167,3184,3185,3186,3191,3192,3194,3195,3197,3199,3200,3205,3206,3211,3212,3224,3230,3231,3232,3237,3241,3258,3261,3264))) {
					// skip
					echo "$label ($abbv id=$oid) : not permitted, skipping".PHP_EOL;
					continue;
				}
				echo "Downloading $label ($abbv id=$oid) ... ";
				$rfile = 'http://rest.bioontology.org/bioportal/virtual/download/'.$oid.'?apikey='.parent::getParameterValue('ncbo_api_key');
				
				if($zip) $lz = $lfile;
				else $lz = "compress.zlib://".$lfile;
				$ret = Utils::DownloadSingle($rfile,$lz,true);
				if($ret === false) {
					echo "Unable to download $label".PHP_EOL;
					continue;
				}
				echo " download complete.".PHP_EOL;
			}
		
			if(isset($ofile)) {
				parent::setReadFile($lfile, true);
				$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
				parent::setWriteFile($ofile,$gz);
			
				// process
				echo "Processing $label ($abbv id=$oid format=$format) into $ofile ... ";
				if($format == 'obo') {
					$this->OBO2RDF($abbv);
				} else if($format == 'owl') {
					$this->OWL2RDF($abbv);
					print_r($this->unmapped_uri);
					unset($this->unmapped_uri);
				} else {
					echo "no processor for $label (format $format)".PHP_EOL;
				}
				
				// @todo process owl files
				echo "Done!".PHP_EOL;
				parent::getReadFile()->close();
				parent::writeRDFBufferToWriteFile();
				parent::getWriteFile()->close();
				parent::clear();
			}
		}
	}

	private function OWL2RDF($abbv)
	{
		$filename = parent::getReadFile()->getFilename();
		$buf = file_get_contents("compress.zlib://".$filename);

		$parser = ARC2::getRDFXMLParser('file://'.$filename);
		$parser->parse("http://bio2rdf.org/bioportal#", $buf);
		$triples = $parser->getTriples();
		foreach($triples AS $i => $a) {
			$this->TriplifyMap($a, $abbv);
			parent::writeRDFBufferToWriteFile();
		}
		parent::clear();
	}
	
	// parse the URI into the base and fragment. find the corresponding prefix and bio2rdf_uri. 
	public function parseURI($uri)
	{
		$a['uri'] = $uri;
		$delims = array("#","_","/");
		foreach($delims AS $delim) {
			if(($pos = strrpos($uri,$delim)) !== FALSE) {
				$a['base_uri'] = substr($uri,0,$pos+1);
				$a['fragment'] = substr($uri,$pos+1);

				$a['prefix'] = parent::getRegistry()->getPrefixFromURI($a['base_uri']);
				if(isset($a['prefix'])) {
					$a['bio2rdf_uri'] = 'http://bio2rdf.org/'.$a['prefix'].':'.$a['fragment'];
					$p_uri = parent::getRegistry()->getEntryValueByKey($a['prefix'], 'providerURI');
					if(isset($p_uri)) {
						if($p_uri == $a['base_uri']) {
							$a['is_provider_uri'] = true;
						}
						$a['provider_uri'] = $p_uri;
					}
					break;
				}
				
			}
		}
		if(!isset($a['base_uri'])) $a['base_uri'] = $uri;
		return $a;
	}
	

	public function TriplifyMap($a, $prefix)
	{
		$defaults = parent::getRegistry()->getDefaultURISchemes();
		$bio2rdf_priority = true;
		$mapping = false;

		// subject
		if($a['s_type'] == 'bnode') $a['s'] = 'http://bio2rdf.org/'.$prefix.'_resource:'.substr($a['s'],2);
		$u = $this->parseURI($a['s']);
		$s_uri = $u['uri'];
		if(isset($u['prefix'])) {
			if(!in_array($u['prefix'],$defaults)) {
				if($bio2rdf_priority) {
					$s_uri = $u['bio2rdf_uri'];
					if($mapping) {
						parent::addRDF(
							parent::triplify($s_uri,'owl:sameAs',$u['uri'])
						);
					}
				} else if($mapping) {
					parent::addRDF(
						parent::triplify($u['uri'],'owl:sameAs',$u['bio2rdf_uri'])
					);
				}
			}
		} else {
			// add to the registry of uris not found
			if(!isset($this->unmapped_uri[$u['base_uri']])) $this->unmapped_uri[$u['base_uri']] = 1;
			else $this->unmapped_uri[$u['base_uri']]++;
		}

		// predicate
		$u = $this->parseURI($a['p']);
		$p_uri = $u['uri'];
		if(isset($u['prefix'])) {
			if(!in_array($u['prefix'],$defaults)) {
				if($bio2rdf_priority) {
					$p_uri = $u['bio2rdf_uri'];
					if($mapping) {
						parent::addRDF(
							parent::triplify($p_uri,'owl:sameAs',$u['uri'])
						);
					}
				} else if($mapping) {
					parent::addRDF(
						parent::triplify($u['uri'],'owl:sameAs',$u['bio2rdf_uri'])
					);
				}
			}
		} else {
			// add to the registry of uris not found
			if(!isset($this->unmapped_uri[$u['base_uri']])) $this->unmapped_uri[$u['base_uri']] = 1;
			else $this->unmapped_uri[$u['base_uri']]++;
		}

		if($a['o_type'] == 'uri' || $a['o_type'] == 'bnode') {
			if($a['o_type'] == 'bnode') $a['o'] = 'http://bio2rdf.org/'.$prefix.'_resource:'.substr($a['o'],2);
			$u = $this->parseURI($a['o']);
			$o_uri = $u['uri'];
			if(isset($u['prefix'])) {
				if(!in_array($u['prefix'],$defaults)) {
					if($bio2rdf_priority) {
						$o_uri = $u['bio2rdf_uri'];
						if($mapping) {
							parent::addRDF(
								parent::triplify($o_uri,'owl:sameAs',$u['uri'])
							);
						}
					} else if($mapping) {
						parent::addRDF(
							parent::triplify($u['uri'],'owl:sameAs',$u['bio2rdf_uri'])
						);
					}						
				}
			} else {
				// add to the registry of uris not found
				if(!isset($this->unmapped_uri[$u['base_uri']])) $this->unmapped_uri[$u['base_uri']] = 1;
				else $this->unmapped_uri[$u['base_uri']]++;
			}
		
			// add the triple
			parent::addRDF(
				parent::triplify($s_uri,$p_uri,$o_uri)
			);
			
		} else {
			parent::addRDF(
				parent::triplifyString($s_uri,$p_uri,$a['o'],(($a['o_datatype'] == '')?null:$a['o_datatype']),(($a['o_lang'] == '')?null:$a['o_lang']))
			);			
		}
	
	}
			   
	
	function OBO2RDF($abbv)
	{
		$minimal = (parent::getParameterValue('detail') == 'min')?true:false;
		$minimalp = (parent::getParameterValue('detail') == 'min+')?true:false;
		
		$tid = '';
		$first = true;
		$is_a = false;
		$is_deprecated = false;
		$min = $buf = '';
		$ouri = "http://bio2rdf.org/$abbv";
		$buf = parent::triplify($ouri,"rdf:type","owl:Ontology");
		$graph_uri = '<'.parent::getRegistry()->getFQURI(parent::getGraphURI()).'>';
		$bid = 1;
		while($l = parent::getReadFile()->read()) {
			$lt = trim($l);
			if(strlen($lt) == 0) continue;
			if($lt[0] == '!') continue;

			if(strstr($l,"[Term]")) {
				// first node?
				if($first == true) { // ignore the first case
					$first = false;
				} else {
					if($tid != '' && $is_a == false && $is_deprecated == false) {
						$t = parent::triplify($tid,"rdfs:subClassOf","obo_vocabulary:Entity");
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

			//echo "LINE: $l".PHP_EOL;
			
			// to fix error in obo generator
			$lt = str_replace("synonym ","synonym: ",$lt);
			$lt = preg_replace("/\{.*\} !/"," !",$lt);
			$a = explode(" !", $lt);
			if(isset($a[1])) $exc = trim($a[1]);
			$a = explode(": ",trim($a[0]),2);

			// let's go
			if(isset($intersection_of)) {
				if($a[0] != "intersection_of") {
			//		$intersection_of .= ")].".PHP_EOL;
					$buf .= $intersection_of;
					if($minimalp) $min .= $intersection_of;
					unset($intersection_of);
				}
			}
			if(isset($relationship)) {
				if($a[0] != "relationship") {
				//	$relationship .= ")].".PHP_EOL;
					$buf .= $relationship;
					if($minimalp) $min .= $relationship;
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
				} else if($a[0] == "name") {
					$buf .= parent::describeClass($tid,addslashes(stripslashes($a[1])));
				} else if($a[0] == "is_a") {
					if(FALSE !== ($pos = strpos($a[1],"!"))) $a[1] = substr($a[1],0,$pos-1);
					$buf .= parent::triplify($tid,"rdfs:subPropertyOf","obo_vocabulary:".strtolower($a[1]));
				} else if($a[0] == "is_obsolete") {
					$buf .= parent::triplify($tid, "rdf:type", "owl:DeprecatedClass");
					$is_deprecated = true;
				} else {
					if($a[0][0] == "!") $a[0] = substr($a[0],1);
					$buf .= parent::triplifyString($tid,"obo_vocabulary:$a[0]", str_replace('"','',stripslashes($a[1])));
				}

			} else if(isset($term)) {
				if($a[0] == "is_obsolete" && $a[1] == "true") {
					$t = parent::triplify($tid, "rdf:type", "owl:DeprecatedClass");
					$t .= parent::triplify($tid, "rdfs:subClassOf", "owl:DeprecatedClass");
					
					$min .= $t;
					$buf .= $t;
					$is_deprecated = true;
				} else if($a[0] == "id") {	
					parent::getRegistry()->parseQName($a[1],$ns,$id);					
					$tid = "$ns:$id";
					$buf .= parent::triplify($tid,"rdfs:isDefinedBy",$ouri);
					
				} else if($a[0] == "name") {
					$t = parent::triplifyString($tid,"rdfs:label",str_replace(array("\"", "'"), array("","\\\'"), stripslashes($a[1]))." [$tid]");
					$min .= $t;
					$buf .= $t;
					
				} else if($a[0] == "def") {
					$t = str_replace(array("'", "\"", "\\","\\\'"), array("\\\'", "", "",""), $a[1]);
					$min .= parent::triplifyString($tid,"dc:description",$t);
					$buf .= parent::triplifyString($tid,"dc:description",$t);
					
				} else if($a[0] == "property_value") {
					$b = explode(" ",$a[1]);
					$buf .= parent::triplifyString($tid,"obo_vocabulary:".strtolower($b[0]),str_replace("\"", "", strtolower($b[1])));
				} else if($a[0] == "xref") {
				// http://upload.wikimedia.org/wikipedia/commons/3/34/Anatomical_Directions_and_Axes.JPG
				// Medical Dictionary:http\://www.medterms.com/
				// KEGG COMPOUND:C02788 "KEGG COMPOUND"
				// id-validation-regexp:\"REACT_[0-9\]\{1\,4}\\.[0-9\]\{1\,3}|[0-9\]+\"
				//$a[1] = 'id-validation-regexp:\"REACT_[0-9\]\{1\,4}\\.[0-9\]\{1\,3}|[0-9\]+\"';
					if(substr($a[1],0,4) == "http") {
						$buf .= parent::triplify($tid,"rdfs:seeAlso", $a[1]);
					} else {
						$b = explode(":",$a[1],2);
						if(substr($b[1],0,4) == "http") {
							$buf .= parent::triplify($tid,"rdfs:seeAlso", stripslashes($b[1]));
						} else {
							$ns = str_replace(" ","",strtolower($b[0]));
							$id = trim($b[1]);
														
							// there may be a comment to remove
							if(FALSE !== ($pos = strrpos($id,' "'))) {
								$comment = substr($id,$pos+1,-1);
								$id = substr($id,0,$pos);
							}
							$id = stripslashes($id);
				
							$buf .= parent::triplify($tid,"obo_vocabulary:x-$ns", "$ns:$id");
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
					$l = parent::triplifyString($tid,"obo_vocabulary:".strtolower($rel), $str);
					$buf .= $l;
					
				} else if($a[0] == "alt_id") {
					parent::getRegistry()->parseQname($a[1],$ns,$id);
					if($id != 'curators') {
						$buf .= parent::triplify("$ns:$id","rdfs:seeAlso",$tid);
					}
					
				} else if($a[0] == "is_a") {
					// do subclassing
					parent::getRegistry()->parseQName($a[1],$ns,$id);
					$t = parent::triplify($tid,"rdfs:subClassOf","$ns:$id");
					$buf .= $t;
					$min .= $t;
					$is_a = true;
					
				} else if($a[0] == "intersection_of") {
					if(!isset($intersection_of)) {
						// $intersection_of = '<'.parent::getRegistry()->getFQURI($tid).'> <'.parent::getRegistry()->getFQURI('owl:equivalentClass').'> [<'.parent::getRegistry()->getFQURI('rdf:type').'> <'.parent::getRegistry()->getFQURI('owl:Class').'>; <'.parent::getRegistry()->getFQURI('owl:intersectionOf').'> (';
						$intersection_of = '<'.parent::getRegistry()->getFQURI($tid).'> <'.parent::getRegistry()->getFQURI('owl:equivalentClass').'> _:b'.(++$bid)." $graph_uri .".PHP_EOL;
						$intersection_of .= '_:b'.$bid.' <'.parent::getRegistry()->getFQURI('rdf:type').'> <'.parent::getRegistry()->getFQURI('owl:Class')."> $graph_uri .".PHP_EOL;
						$intersection_of .= '_:b'.$bid.' <'.parent::getRegistry()->getFQURI('owl:intersectionOf').'> _:b'.(++$bid)." $graph_uri .".PHP_EOL;
					}
					
					/*
					intersection_of: ECO:0000206 ! BLAST evidence
					intersection_of: develops_from VAO:0000092 ! chondrogenic condensation
					intersection_of: OBO_REL:has_part VAO:0000040 ! cartilage tissue
					*/
					$c = explode(" ",$a[1]);
					if(count($c) == 1) { // just a class					
						parent::getRegistry()->parseQName($c[0],$ns,$id);
						$intersection_of .= '_:b'.$bid.' <'.parent::getRegistry()->getFQURI('rdfs:subClassOf').'> <'.parent::getRegistry()->getFQURI("$ns:$id")."> $graph_uri .".PHP_EOL;
						$buf .= parent::triplify($tid,"rdfs:subClassOf","$ns:$id");
					} else if(count($c) == 2) { // an expression						
						parent::getRegistry()->parseQName($c[0],$pred_ns,$pred_id);
						parent::getRegistry()->parseQName($c[1],$obj_ns,$obj_id);
						
						$intersection_of .= '_:b'.$bid.' <'.parent::getRegistry()->getFQURI('owl:onProperty').'> <'.parent::getRegistry()->getFQURI("obo_vocabulary:".$pred_id)."> $graph_uri .".PHP_EOL;
						$intersection_of .= '_:b'.$bid.' <'.parent::getRegistry()->getFQURI('owl:someValuesFrom').'> <'.parent::getRegistry()->getFQURI("$obj_ns:$obj_id").">  $graph_uri .".PHP_EOL;
						
						$buf .= parent::triplify($tid,"obo_vocabulary:$pred_id","$obj_ns:$obj_id");
					}

				} else if ($a[0] == "relationship") {
					if(!isset($relationship)) {
						$relationship = '<'.parent::getRegistry()->getFQURI($tid).'> <'.parent::getRegistry()->getFQURI('rdfs:subClassOf').'> _:b'.(++$bid)." $graph_uri .".PHP_EOL;
						$relationship .= '_:b'.$bid.' <'.parent::getRegistry()->getFQURI('rdf:type').'> <'.parent::getRegistry()->getFQURI('owl:Class')."> $graph_uri .".PHP_EOL;
						$relationship .= '_:b'.$bid.' <'.parent::getRegistry()->getFQURI('owl:intersectionOf').'> _:b'.(++$bid)." $graph_uri .".PHP_EOL;
					}
					
					/*
					relationship: develops_from VAO:0000092 ! chondrogenic condensation
					relationship: OBO_REL:has_part VAO:0000040 ! cartilage tissue
					*/
					$c = explode(" ",$a[1]);
					if(count($c) == 1) { // just a class	
						parent::getRegistry()->parseQName($c[0],$ns,$id);
						$relationship .= parent::getRegistry()->getFQURI("$ns:$id");
						$buf .= parent::triplify($tid,"rdfs:subClassOf","$ns:$id");

					} else if(count($c) == 2) { // an expression						
						parent::getRegistry()->parseQName($c[0],$pred_ns,$pred_id);
						parent::getRegistry()->parseQName($c[1],$obj_ns,$obj_id);

						$relationship .= '_:b'.$bid.' <'.parent::getRegistry()->getFQURI('owl:onProperty').'> <'.parent::getRegistry()->getFQURI("obo_vocabulary:".$pred_id).">  $graph_uri .".PHP_EOL;
						$relationship .= '_:b'.$bid.' <'.parent::getRegistry()->getFQURI('owl:someValuesFrom').'> <'.parent::getRegistry()->getFQURI("$obj_ns:$obj_id")."> $graph_uri .".PHP_EOL;
		
						$buf .= parent::triplify($tid,"obo_vocabulary:$pred_id","$obj_ns:$obj_id");
					}
				} else {
					// default handler
					if(isset($a[1])) $buf .= parent::triplifyString($tid,"obo_vocabulary:$a[0]", str_replace(array("\"", "'"), array("", "\\\'") ,stripslashes($a[1])));
				}
			} else {
				//header
				//format-version: 1.0
				$buf .= parent::triplifyString($ouri,"obo_vocabulary:$a[0]",str_replace( array('"','\:'), array('\"',':'), isset($a[1])?$a[1]:""));
			}

			if($minimal || $minimalp) parent::getWriteFile()->write($min);
			else parent::getWriteFile()->write($buf);

			$min = '';$buf ='';$header='';
		}
		//if(isset($intersection_of))  $buf .= $intersection_of.")].".PHP_EOL;
		//if(isset($relationship))  $buf .= $relationship.")].".PHP_EOL;

		if($minimal || $minimalp) parent::getWriteFile()->Write($min);
		else parent::getWriteFile()->write($buf);
	}
}

?>
