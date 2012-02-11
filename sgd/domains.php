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
			"BlastProDom" => "prodom",
			"FPrintScan" => "fprintscan",
			"Gene3D" => "gene3d",
			"Coil" => "coil",
			"Seg" => "seg",
			"HMMSmart" => "smart",
			"HMMPanther" => "panther",
			"HMMPfam" => "pfam",
			"HMMPIR" => "pir",
			"HMMTigr" => "tigr"
		);
		
		$buf = N3NSHeader();

		while($l = fgets($this->_in,2048)) {
			$a = explode("\t",$l);
			
			$id = "sgd:".$a[0]."p";
			$domain = $domain_ns[$a[3]].":".$a[4];
			$buf .= QQuad($id,'sgd_vocabulary:has-proper-part',$domain);

			$da = "sgd_resource:da_".$a[0]."p_$a[4]_$a[6]_$a[7]";
			$buf .= QQuadL($da,'rdfs:label',"domain alignment between sgd:$id and $domain [$da]");
			$buf .= QQuad($da,'rdf:type','sgd_vocabulary:DomainAlignment');
			$buf .= QQuad($da,'sgd_vocabulary:query',$id);
			$buf .= QQuad($da,'sgd_vocabulary:target',$domain);
			$buf .= QQuadL($da,'sgd_vocabulary:query-start', $a[6]);
			$buf .= QQuadL($da,'sgd_vocabulary:query-stop',$a[7]);
			$buf .= QQuadL($da,'sgd_vocabulary:e-value',$a[8]);
			
//echo $buf;exit;
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
