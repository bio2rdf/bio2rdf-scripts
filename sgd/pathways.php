<?php

class SGD_PATHWAYS {

	function __construct($infile, $outfile)
	{
		$this->_in = fopen($infile,"r");
		if(!isset($this->_in)) {
			trigger_error("Unable to open $infile");
			return 1;
		}
		$this->_out = fopen($outfile,"w");
		if(!isset($this->_out)) {
			trigger_error("Unable to open $outfile");
			return 1;
		}
		
	}
	function __destruct()
	{
		fclose($this->_in);
		fclose($this->_out);
	}

/*
pathway        [0] => 2-ketoglutarate dehydrogenase complex 
enzyme name    [1] => 
EC number      [2] => 1.8.1.4
Geme           [3] => LPD1
publication    [4] => PMID:2072900
*/
	function Convert2RDF()
	{
		$buf = N3NSHeader();
		
		$sp = false;
		$e = '';
		while($l = fgets($this->_in,96000)) {
			$a = explode("\t",$l);
			
			$pid = md5($a[0]);
			if(stristr($a[0],"superpathway")) $sp = true;
			else $sp = false;

			if(!isset($e[$pid])) {
				$e[$pid] = '1';
				$buf .= QQuadL("sgd_resource:$pid","rdfs:label","$a[0] [sgd_resource:$pid]");
				$buf .= QQuadL("sgd_resource:$pid","dc:title",$a[0]);

				if(!$sp) $buf .= QQuad("sgd_resource:$pid","rdf:type","sgd_vocabulary:Pathway");
				else $buf .= QQuad("sgd_resource:$pid","rdf:type","sgd_vocabulary:Superpathway");
			}
			if($sp) { // add the pathway to the superpathway
				$pathway = substr($a[1],0,-(strlen($a[1])-strrpos($a[1]," ")));
				$buf .= QQuad("sgd_resource:$pid","sgd_vocabulary:has-proper-part","sgd:".md5($pathway));
				continue;
			}

			$eid = '';
			if($a[3]) { // there is a protein
				$eid = ucfirst(strtolower($a[3]))."p";
				$buf .= QQuad("sgd_resource:$pid","sgd_vocabulary:has-participant", "sgd_resource:$eid");
			}				
			$cid = '';
			if($a[1]) { // enzyme complex
				$cid = md5($a[1]);
				if(!isset($e[$cid])) {
					$e[$cid] = $cid;
					$buf .= QQuadL("sgd_resource:$cid","rdfs:label","$a[1] [sgd_resource:$cid]");
					$buf .= QQuad("sgd_resource:$cid","rdf:type","sgd_vocabulary:Enzyme");
				}
				$buf .= QQuad("sgd_resource:$pid","sgd_vocabulary:has-participant","sgd_resource:$cid");
				if($eid) $buf .= QQuad("sgd_resource:$cid","sgd_vocabulary:has-proper-part","sgd_resource:$eid");
			}
			if($a[2]) { // EC reaction
				$buf .= QQuad("sgd_resource:$pid","sgd_vocabulary:has-proper-part","ec:$a[2]");
				$buf .= QQuadL("ec:$a[2]","rdfs:label","$a[2] [ec:$a[2]]");
				$buf .= QQuad("ec:$a[2]","rdf:type","sgd_vocabulary:Reaction");
				$buf .= QQuad("ec:$a[2]","sgd_vocabulary:has-participant","sgd:$eid");
				if($cid) $buf .= QQuad("ec:$a[2]","sgd_vocabulary:has-participant","sgd_resource:$cid");
			}
	
			if(trim($a[4]) != '') { // publications
				$b = explode("|",trim($a[4]));
				foreach($b AS $c) {
					$d = explode(":",$c);
					$ns = "sgd";
					if($d[0] == "PMID") $ns = "pubmed";
					$buf .= QQuad("sgd_resource:$pid","sgd_vocabulary:article","$ns:$d[1]");
				}
			}

//			echo $buf;exit;
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
