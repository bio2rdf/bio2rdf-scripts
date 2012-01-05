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
			"F" => array("type" => "SIO_000017", "p" => "SIO_000225", "plabel" => "has function"),
			//Location, isLocatedIn
			"C" => array("type" => "SIO_000003", "p" => "SIO_000061", "plabel" => "is located in"),
			//Process, isParticipantIn
			"P" => array("type" => "SIO_000006", "p" => "SIO_000062", "plabel" => "is participant in")
		);
		
		$z = 0;
		while($l = gzgets($this->_in,2048)) {
			if($l[0] == '!') continue;
			$a = explode("\t",trim($l));

			$id = $a[1]."gp";

			$term = substr($a[4],3);
			$goi = $id."_".$term;
			$got = $goterms[$a[8]];

			$buf .= "sgd_resource:$id sio:".$got['p']." sgd_resource:$goi .".PHP_EOL;
			$buf .= "sgd_resource:$goi a go:$term .".PHP_EOL;
			$buf .= "sgd_resource:$goi rdfs:label \"sgd_reource:$id ".$got['plabel']." go:$term \" .".PHP_EOL;
			$buf .= "go:$term rdfs:subClassOf sio:".$got['type']." .".PHP_EOL;
			
			$goa = "goa_".($z++);
			$buf .= "sgd_resource:$goa rdfs:label \"Evidence of ".strtolower($got['type'])." for sgd_resource:$id \".".PHP_EOL;
			$buf .= "sgd_resource:$goa sio:SIO_000773 sgd:$goi .".PHP_EOL;
			$buf .= "sgd_resource:$goi sio:SIO_000772 sgd:$goa .".PHP_EOL;

			if(isset($a[5])) {
				$b = explode("|",$a[5]);
				foreach($b as $c) {
					$d = explode(":",$c);
					if($d[0] == "pmid") {
						$buf .= "sgd_resource:$goa sio:SIO_000212 pubmed:$d[1] .".PHP_EOL;
					}
				}
			}
			if(isset($a[6])) {
				$code = MapECO($a[6]);
				if($code) $buf .= "sgd_resource:$goa a eco:$code .".PHP_EOL;
				else echo "No mapping for $a[6]".PHP_EOL;
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
