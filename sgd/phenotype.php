<?php

class SGD_PHENOTYPE {

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
0) Feature Name (Mandatory)     		-The feature name of the gene
1) Feature Type (Mandatory)     		-The feature type of the gene	
2) Gene Name (Optional) 			-The standard name of the gene
3) SGDID (Mandatory)    			-The SGDID of the gene
4) Reference (SGD_REF Required, PMID optional)  -PMID: #### SGD_REF: #### (separated by pipe)(one reference per row)
5) Experiment Type (Mandatory)     		-The method used to detect and analyze the phenotype
6) Mutant Type (Mandatory)      		-Description of the impact of the mutation on activity of the gene product
7) Allele (Optional)    			-Allele name and description, if applicable
8) Strain Background (Optional) 		-Genetic background in which the phenotype was analyzed
9) Phenotype (Mandatory)       		-The feature observed and the direction of change relative to wild type
10) Chemical (Optional) 			-Any chemicals relevant to the phenotype
11) Condition (Optional)        		-Condition under which the phenotype was observed
12) Details (Optional)  			-Details about the phenotype
13) Reporter (Optional) 			-The protein(s) or RNA(s) used in an experiment to track a process 

AUT6	not physically mapped	AUT6	S000029048	PMID: 8663607|SGD_REF: S000057871	classical genetics	reduction of function		Other	autophagy: absent		nitrogen starvation + 1 mM PMSF	autophagosomes not observed	
*/
	function Convert2RDF()
	{
		require_once ('../common/php/oboparser.php');
		
		/** get the ontology terms **/
		global $ncbo_apikey;
		global $ncbo_dl_dir;
		$file = $ncbo_dl_dir."apo.obo";
		if(!file_exists($file)) {
			GetLatestNCBOOntology('1222',$ncbo_apikey,$file);
		}
		
		$in = fopen($file, "r");
		if($in === FALSE) {
			trigger_error("Unable to open $file");
			exit;
		}
		$terms = OBOParser($in);
		fclose($in);
		BuildNamespaceSearchList($terms,$searchlist);
		
		$buf = N3NSHeader();		
		while($l = fgets($this->_in,96000)) {
			if(trim($l) == '') continue;		
			$a = explode("\t",trim($l));
					
			$eid =  md5($a[3].$a[5].$a[6].$a[9]);
			
			$label = "$a[0] - $a[5] experiment with $a[6] resulting in phenotype of $a[9]";
			$buf .= "sgd_resource:$eid rdfs:label \"$label [sgd_resource:$eid]\" .".PHP_EOL;
			$buf .= "sgd_resource:$eid a sgd_vocabulary:Phenotype_Experiment .".PHP_EOL;
			
			$buf .= "sgd_resource:$eid sio:SIO_000132 sgd:$a[3].".PHP_EOL;
			
			// reference
			// PMID: 12140549|SGD_REF: S000071347
			$b = explode("|",$a[4]);
			foreach($b AS $c) {
				$d = explode(" ",$c);
				if($d[0] == "PMID:") $ns = "pubmed";
				else $ns = "sgd";
				$buf .= "sgd_resource:$eid sio:SIO_000212 $ns:$d[1].".PHP_EOL;
			}
			
			// experiment type [5]
			$p = strpos($a[5],'(');
			if($p !== FALSE) {
				$label = substr($a[5],0,$p-1);
				$details = substr($a[5],$p+1);
				$buf .= "sgd_resource:$eid dc:description \"$details\".".PHP_EOL;
			} else {
				$label = $a[5];
			}
			$id = array_search($label, $searchlist['experiment_type']);	
			if($id !== FALSE) 
				$buf .= "sgd_resource:$eid sgd_vocabulary:experiment_type ".strtolower($id).".".PHP_EOL;
			else 
				trigger_error("No match for experiment type $label");

			// mutant type [6]
			$id = array_search($a[6], $searchlist['mutant_type']);
			if($id !== FALSE) 
				$buf .= "sgd_resource:$eid sgd_vocabulary:mutant_type ".strtolower($id).".".PHP_EOL;
			
			// phenotype  [9]
			// presented as observable: qualifier
			$b = explode(": ",$a[9]);
			$id = array_search($b[0], $searchlist['observable']);
			if($id !== FALSE) 
				$buf .= "sgd_resource:$eid sgd_vocabulary:observable ".strtolower($id).".".PHP_EOL;
			
			$id = array_search($b[1], $searchlist['qualifier']);
			if($id !== FALSE) 
				$buf .= "sgd_resource:$eid sgd_vocabulary:qualifier ".strtolower($id).".".PHP_EOL;
			
/*
7) Allele (Optional)    			-Allele name and description, if applicable
8) Strain Background (Optional) 		-Genetic background in which the phenotype was analyzed
10) Chemical (Optional) 			-Any chemicals relevant to the phenotype
11) Condition (Optional)        		-Condition under which the phenotype was observed
12) Details (Optional)  			-Details about the phenotype
13) Reporter (Optional) 			-The protein(s) or RNA(s) used in an experiment to track a process 
*/

			if($a[7] != '') $buf .= "sgd_resource:$eid sgd_vocabulary:allele \"$a[7]\".".PHP_EOL;
			if($a[8] != '') $buf .= "sgd_resource:$eid sgd_vocabulary:background \"$a[8]\".".PHP_EOL;
			if($a[10] != '') $buf .= "sgd_resource:$eid sgd_vocabulary:chemical \"$a[10]\".".PHP_EOL;
			if($a[11] != '') $buf .= "sgd_resource:$eid sgd_vocabulary:condition \"$a[11]\".".PHP_EOL;
			if($a[12] != '') $buf .= "sgd_resource:$eid sgd_vocabulary:details \"".str_replace('"','\"',$a[12])."\".".PHP_EOL;
			//if($a[13] != '') $buf .= "sgd:$eid sgd_vocabulary:reporter \"$a[13]\".".PHP_EOL;
			
		}		
		fwrite($this->_out, $buf);
		return 0;
	}
};

?>
