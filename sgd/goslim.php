<?php

class SGD_GOSLIM {

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
	function Convert2RDF()
	{
		$buf = N3NSHeader();

		$goterms = array(
			"F" => array("type" => "SIO_000017", "p" => "SIO_000225", "plabel" => "has function"),
			"C" => array("type" => "SIO_000003", "p" => "SIO_000061", "plabel" => "is located in"),
			"P" => array("type" => "SIO_000006", "p" => "SIO_000062", "plabel" => "is participant in")
		);
		
		while($l = fgets($this->_in,2048)) {
			$a = explode("\t",trim($l));
			
			$id = $a[2]."gp";
			$term = substr($a[5],3);
			$goi  = "goslim_".$id."_".$term;

			//$buf .= "$sgd:$id rdfs:label \"[$sgd:$id]\".".PHP_EOL;
			if(strstr($a[6],"ORF")) $type = "ORF";
			else $type = $a[6];
			//$buf .= "$sgd:$id a $sgd:$type .".PHP_EOL;

			$got = $goterms[$a[3]];
			$buf .= "sgd:$id sio:".$got['p']." sgd:$goi .".PHP_EOL;			
			$buf .= "sgd:$goi rdfs:label \"sgd:$id ".$got['plabel']." $a[4] (".strtolower($got['type']).") [sgd:$goi]\".".PHP_EOL;
			$buf .= "sgd:$goi a go:$term .".PHP_EOL;
			$buf .= "go:$term rdfs:label \"$a[4] [go:$term]\".".PHP_EOL;
//			$buf .= "go:$term rdfs:subClassOf $io:".$go_entity[$a[3]]." .".PHP_EOL;
	
//			echo $buf;exit;
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
