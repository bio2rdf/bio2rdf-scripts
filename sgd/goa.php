<?php

class SGD_GOA {

	function __construct($infile, $outfile)
	{
		$this->_in = gzopen($infile,"r");
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
		gzclose($this->_in);
		fclose($this->_out);
	}
	function Convert2RDF()
	{
		$buf = N3NSHeader();
		
		$goterms = array(
			//Function, hasFunction
			"F" => array("type" => "SIO_000017", "p" => "SIO_000225", "plabel" => "has function", "sgd_vocabulary" => "has-function"),
			//Location, isLocatedIn
			"C" => array("type" => "SIO_000003", "p" => "SIO_000061", "plabel" => "is located in" , "sgd_vocabulary" => "is-located-in"),
			//Process, isParticipantIn
			"P" => array("type" => "SIO_000006", "p" => "SIO_000062", "plabel" => "is participant in", "sgd_vocabulary" => "is-participant-in")
		);
		
		$z = 0;
		while($l = gzgets($this->_in,2048)) {
			if($l[0] == '!') continue;
			$a = explode("\t",trim($l));

			$id = $a[1]."gp";
			$term = substr($a[4],3);
			
			$subject   = "sgd_resource:$id";
			$predicate = "sgd_vocabulary:".$goterms[$a[8]]['sgd_vocabulary'];
			$object    = "go:".$term;
			$buf .= QQuad($subject,$predicate,$object);
			
			// now for the GO annotation
			$goa = "sgd_resource:goa_".$id."_".$term;
			$buf .= QQuad($goa,"rdf:type","sgd_vocabulary:GOAnnotation");
			$buf .= QQuad($goa,"rdf:subject",$subject);
			$buf .= QQuad($goa,"rdf:predicate",$predicate);
			$buf .= QQuad($goa,"rdf:object",$object);
			if(isset($a[5])) {
				$b = explode("|",$a[5]);
				foreach($b as $c) {
					$d = explode(":",$c);
					if($d[0] == "pmid") {
						$buf .= QQuad($goa,"sgd_vocabulary:article","pubmed:$d[1]");
					}
				}
			}
			if(isset($a[6])) {
				$code = MapECO($a[6]);
				if($code) $buf .= QQuad($goa,"sgd_vocabulary:evidence","eco:$code");
			}
			
//			echo $buf;exit;
		}
		fwrite($this->_out, $buf);
		return 0;
	}

};


function MapECO($eco)
{
 $c = array(
"ISS" => "0000027", 
"IGI" => "0000011",
"IMP" => "0000015",
"IDA" => "0000002",
"IEA" => "00000067",
"TAS" => "0000033",
"RCA" => "0000053",
"ISA" => "00000057",
"IEP" => "0000008",
"ND" => "0000035",
"IC" => "0000001",
"IPI" => "0000021",
"NAS" =>"0000034",
"ISM" => "00000063",
"ISO" =>"00000060",
"IBA" => "0000318",
"IRD" => "0000321",
);
  if(isset($c[$eco])) return $c[$eco];
  else return NULL;
}

?>
