<?php


class SGD_PSIBLAST {

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
		fclose($this->_in);
		fclose($this->_out);
	}
	function Convert2RDF()
	{
		$buf = N3NSHeader();
		
		$z = 0;
		while($l = gzgets($this->_in,2048)) {
			$a = explode("\t",trim($l));
			
			$id1 = $a[0];
			$id2 = $a[7];
			$id = "aln/$id1/$id2";
		
			$buf .= "sgd_resource:$id $rdfs:label \"psiblast alignment between $id1 and $id2 [sgd_resource:$id]\" .".PHP_EOL;
			$buf .= "sgd_resource:$id a sgd_resource:PSIBLASTAlignment .".PHP_EOL;
			$buf .= "sgd_resource:$id sgd_resource:query sgd:$id1 .".PHP_EOL;
			$buf .= "sgd_resource:$id sgd_resource:target sgd:$id2 .".PHP_EOL;
			$buf .= "sgd_resource:$id sgd_resource:query_start \"$a[1]\" .".PHP_EOL;
			$buf .= "sgd_resource:$id sgd_resource:query_stop \"$a[2]\" .".PHP_EOL;
			$buf .= "sgd_resource:$id sgd_resource:target_start \"$a[3]\" .".PHP_EOL;
			$buf .= "sgd_resource:$id sgd_resource:target_stop \"$a[4]\" .".PHP_EOL;
			$buf .= "sgd_resource:$id sgd_resource:percent_aligned \"$a[5]\" .".PHP_EOL;
			$buf .= "sgd:resource:$id sgd_resource:score \"$a[6]\" .".PHP_EOL;
			$buf .= "sgd:$id2 $ss:SIO_010079 $taxon:".$a[8]." .".PHP_EOL;
			//echo $buf;exit;

			if(++$z % 10000 == 1) {
				echo '.';
				fwrite($this->_out, $buf);
				$buf = '';
			}
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
