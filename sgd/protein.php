<?php

/** SGD's protein information */
class SGD_PROTEIN {

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
		
		$properties = array(
			"2" => array( 'id' => "MW", 'type' => "MolecularWeight"),
			"3" => array( 'id' => "PI", 'type' => "IsolectricPoint"),
			"4" => array( 'id' => "CAI", 'type' => "CodonAdaptationIndex"),
			"5" => array( 'id' => "Length", 'type' => "SequenceLength"),
			"8" => array( 'id' => "CB", 'type' => "CodonBias"),
			"9" => array( 'id' => "ALA", 'type' => "AlanineCount"),
			"10" => array( 'id' => "ARG", 'type' => "ArginineCount"),
			"11" => array( 'id' => "ASN", 'type' => "AsparagineCount"),
			"12" => array( 'id' => "ASP", 'type' => "AspartateCount"),
			"13" => array( 'id' => "CYS", 'type' => "CysteinCount"),
			"14" => array( 'id' => "GLN", 'type' => "GlutamineCount"),
			"15" => array( 'id' => "GLU", 'type' => "GlutamateCount"),
			"16" => array( 'id' => "GLY", 'type' => "GlycineCount"),
			"17" => array( 'id' => "HIS", 'type' => "HistineCount"),
			"18" => array( 'id' => "ILE", 'type' => "IsoleucineCount"),
			"19" => array( 'id' => "LEU", 'type' => "LeucineCount"),
			"20" => array( 'id' => "LYS", 'type' => "LysineCount"),
			"21" => array( 'id' => "MET", 'type' => "MethionineCount"),
			"22" => array( 'id' => "PHE", 'type' => "PhenylalanineCount"),
			"23" => array( 'id' => "PRO", 'type' => "ProlineCount"),
			"24" => array( 'id' => "SER", 'type' => "SerineCount"),
			"25" => array( 'id' => "THR", 'type' => "ThreonineCount"),
			"26" => array( 'id' => "TRP", 'type' => "TryptophanCount"),
			"27" => array( 'id' => "TYR", 'type' => "TyrosineCount"),
			"28" => array( 'id' => "VAL", 'type' => "ValineCount"),
			
			"29" => array( 'id' => "FOP", 'type' => "FrequencyOfOptimalCodons"),
			"30" => array( 'id' => "GRAVY", 'type' => "GRAVYScore"),
			"31" => array( 'id' => "AROMATICITY", 'type' => "AromaticityScore")
		);
		
		
		$buf = N3NSHeader();
		while($l = fgets($this->_in,2048)) {
			$a = explode("\t",$l);
			$id = $a[1];
			
			foreach($properties AS $i => $p) {
				$pid =  "$id"."_".$p["id"];
				$type = $p["type"];
				
				$buf .= "sgd:$id sio:SIO_000557 sgd_resource:$pid .".PHP_EOL;
				$buf .= "sgd_resource:$pid a sgd_vocabulary:$type .".PHP_EOL;
				$buf .= "sgd_resource:$pid rdfs:label \"$type for sgd:$id [sgd_resource:$pid]\".".PHP_EOL;
				$buf .= "sgd_resource:$pid sio:SIO_000300 \"$a[$i]\".".PHP_EOL;
			}
			//echo $buf;exit;
		}
		fwrite($this->_out,$buf);
		return 0;
	}

};

?>
