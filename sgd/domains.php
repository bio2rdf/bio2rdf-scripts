<?php

class SGD_DOMAINS {

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
		$domain_ns = array (
			"ProfileScan" => "profilescan",
			"superfamily" => "superfamily",
			"PatternScan" => "patternscan",
			"BlastProDom" => "blastprodom",
			"FPrintScan" => "fprintscan",
			"Gene3D" => "gene3d",
			"Coil" => "coil",
			"Seg" => "seg",
			"HMMSmart" => "hmmsmart",
			"HMMPanther" => "hmmpanther",
			"HMMPfam" => "hmmpfam",
			"HMMPIR" => "hmmpir",
			"HMMTigr" => "hmmtigr"
		);
		
		$buf = N3NSHeader();

		while($l = fgets($this->_in,2048)) {
			$a = explode("\t",$l);
			

			$id = $a[0];
			$uid = '<http://bio2rdf.org/sgd:'.$id.'>';
			
			$domain = $domain_ns[$a[3]].":".$a[4];
			$udomain = '<http://bio2rdf.org/'.$domain.'>';

			$did   = "did/$id/$a[4]";
			$udid = '<http://bio2rdf.org/sgd:'.$did.'>';
			
			//uid ss:encodes udid
			$buf .= "$uid sio:SIO_010078 $udid .".PHP_EOL;	
			$buf .= "$udid a $udomain .".PHP_EOL;
			$buf .= "$udid rdfs:label \"$domain domain encoded by [sgd:$id]\" .".PHP_EOL;
			$buf .= "$udid a sgd_vocabulary:Domain .".PHP_EOL;

			$da = "da/$id/$a[4]/$a[6]/$a[7]";
			$uda = '<http://bio2rdf.org/sgd:'.$da.'>';
			
			$buf .= "$uda rdfs:label \"domain alignment between sgd:$id and $domain [sgd:$da]\" .".PHP_EOL;
			$buf .= "$uda a sgd_vocabulary:DomainAlignment .".PHP_EOL;
			$buf .= "$uda sgd_vocabulary:query $uid .".PHP_EOL;
			$buf .= "$uda sgd_vocabulary:target $udomain .".PHP_EOL;
			$buf .= "$uda sgd_vocabulary:query_start \"$a[6]\" .".PHP_EOL;
			$buf .= "$uda sgd_vocabulary:query_stop \"$a[7]\" .".PHP_EOL;
			$buf .= "$uda sgd_vocabulary:evalue \"$a[8]\" .".PHP_EOL;
			$buf .= "$udid sio:SIO_000772 $uda .".PHP_EOL;
			$buf .= "$uda sio:SIO_000773 $udid .".PHP_EOL;
//echo $buf;exit;
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
