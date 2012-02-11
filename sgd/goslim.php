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
			//Function, hasFunction
			"F" => array("type" => "SIO_000017", "p" => "SIO_000225", "plabel" => "has function", "sgd_vocabulary" => "has-function"),
			//Location, isLocatedIn
			"C" => array("type" => "SIO_000003", "p" => "SIO_000061", "plabel" => "is located in" , "sgd_vocabulary" => "is-located-in"),
			//Process, isParticipantIn
			"P" => array("type" => "SIO_000006", "p" => "SIO_000062", "plabel" => "is participant in", "sgd_vocabulary" => "is-participant-in")
		);
		
		while($l = fgets($this->_in,2048)) {
			$a = explode("\t",$l);
			
			if(!isset($a[5]) || $a[5] == '') continue;
			
			$id = $a[2]."gp";
			$term = substr($a[5],3);
			
			$subject   = "sgd_resource:$id";
			$predicate = "sgd_vocabulary:".$goterms[$a[3]]['sgd_vocabulary'];
			$object    = "go:".$term;
			$buf .= QQuad($subject,$predicate,$object);
			
//			echo $buf;exit;
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
