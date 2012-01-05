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
				$buf .= "sgd:$pid rdfs:label \"$a[0] [sgd:$pid]\" .".PHP_EOL;
				$buf .= "sgd:$pid dc:title \"$a[0]\" .".PHP_EOL;

				if(!$sp) $buf .= "sgd:$pid a sgd_vocabulary:Pathway .".PHP_EOL;
				else $buf .= "sgd:$pid a sgd_vocabulary:Superpathway .".PHP_EOL;
			}
			if($sp) { // add the pathway to the superpathway
				$pathway = substr($a[1],0,-(strlen($a[1])-strrpos($a[1]," ")));
				$buf .= "sgd:$pid sio:SIO_000053 sgd:".md5($pathway).".".PHP_EOL;
				continue;
			}

			$eid = '';
			if($a[3]) { // there is a protein
				$eid = ucfirst(strtolower($a[3]))."p";
				$buf .= "sgd:$pid sio:SIO_000132 <http://bio2rdf.org/sgd:$eid>.".PHP_EOL; 
			}				
			$cid = '';
			if($a[1]) { // enzyme complex
				$cid = md5($a[1]);
				if(!isset($e[$cid])) {
					$e[$cid] = $cid;
					$buf .= "sgd:$cid rdfs:label \"$a[1] [sgd:$cid]\".".PHP_EOL;
					$buf .= "sgd:$cid a sgd_vocabulary:Enzyme .".PHP_EOL;
				}
				$buf .= "sgd:$pid sio:SIO_000132 <http://bio2rdf.org/sgd:$cid>.".PHP_EOL;
				if($eid) $buf .= "sgd:$cid sio:SIO_000053 <http://bio2rdf.org/sgd:$eid> .".PHP_EOL;	
			}
			if($a[2]) { // EC reaction
				$buf .= "sgd:$pid sio:SIO_000053 ec:$a[2] .".PHP_EOL;
				$buf .= "ec:$a[2] rdfs:label \"$a[2] [ec:$a[2]]\" .".PHP_EOL;
				$buf .= "ec:$a[2] a sgd_vocabulary:Reaction .".PHP_EOL;
				$buf .= "ec:$a[2] sio:SIO_000132 <http://bio2rdf.org/sgd:$eid>.".PHP_EOL;
				if($cid) $buf .= "ec:$a[2] sio:SIO_000132 <http://bio2rdf.org/sgd:$cid> .".PHP_EOL;				
			}
	
			if(trim($a[4]) != '') { // publications
				$b = explode("|",trim($a[4]));
				foreach($b AS $c) {
					$d = explode(":",$c);
					$ns = "sgd";
					if($d[0] == "PMID") $ns = "pubmed";
					$buf .= "sgd:$pid sio:SIO_000212 $ns:$d[1].".PHP_EOL;
				}
			}

//			echo $buf;exit;
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
