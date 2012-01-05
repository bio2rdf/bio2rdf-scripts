<?php

class SGD_COMPLEX {

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

		while($l = fgets($this->_in,96000)) {
			$a = explode("\t",trim($l));
			
			$b = explode("/",$a[0]);
			$id = $b[count($b)-1];
			$buf .= "sgd:$id rdfs:label \"$b[0] [sgd:$id]\" .".PHP_EOL;
			$buf .= "sgd:$id a sgd_vocabulary:Complex .".PHP_EOL;
			
			$b = explode("/|",$a[1]);
			foreach($b AS $c) {
				$d = explode("/",$c);
				$buf .= "sgd:$id sio:SIO_000053 sgd_resource:$d[3]"."gp .".PHP_EOL;
			}
			
			//echo $buf;exit;
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
