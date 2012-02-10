<?php

class SGD_INTERACTION {

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
interactions_data.tab

Contains interaction data incorporated into SGD from BioGRID (http://www.thebiogrid.org/).  Tab-separated columns are:

1) Feature Name (Bait) (Required)       	- The feature name of the gene used as the bait
2) Standard Gene Name (Bait) (Optional) 	- The standard gene name of the gene used as the bait
3) Feature Name (Hit) (Required)        	- The feature name of the gene that interacts with the bait
4) Standard Gene Name (Hit) (Optional)  	- The standard gene name of the gene that interacts with the bait
5) Experiment Type (Required)   		- A description of the experimental used to identify the interaction
6) Genetic or Physical Interaction (Required)   - Indicates whether the experimental method is a genetic or physical interaction
7) Source (Required)    			- Lists the database source for the interaction
8) Manually curated or High-throughput (Required)	- Lists whether the interaction was manually curated from a publication or added as part of a high-throughput dataset
9) Notes (Optional)     			- Free text field that contains additional information about the interaction
10) Phenotype (Optional)        		- Contains the phenotype of the interaction
11) Reference (Required)        		- Lists the identifiers for the reference as an SGDID (SGD_REF:) or a PubMed ID (PMID:)
12) Citation (Required) 			- Lists the citation for the reference
*/
	function Convert2RDF()
	{
		$buf = N3NSHeader();

		require('../common/php/oboparser.php');
			
		/** get the ontology terms **/
		global $ncbo_dl_dir;
		global $ncbo_apikey;
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

		$z = 0;
		while($l = fgets($this->_in,2048)) {
			list($id1,$id1name, $id2, $id2name, $method, $interaction_type, $src, $htpORman, $notes, $phenotype, $ref, $cit) = explode("\t",trim($l));;
			
			$id = md5($id1.$id2.$method.$cit);

			$exp_type = array_search($interaction_type, $searchlist['experiment_type']);
			$buf .= QQuad("sgd_resource:$id","rdf:type",strtolower($exp_type));
			
			$this->GetMethodID($method,$oid,$type);
			$id1 = str_replace(array("(",")"), array("",""), $id1);
			$id2 = str_replace(array("(",")"), array("",""), $id2);
			if($type == "protein") {$id1 = ucfirst(strtolower($id1))."p";$id2=ucfirst(strtolower($id2))."p";}
			
			$buf .= QQuadL("sgd_resource:$id","rdfs:label","$htpORman ".substr($interaction_type,0,-1)." between $id1 and $id2 [sgd:$id]");
			
			$buf .= QQuad("sgd_resource:$id","sgd_vocabulary:bait","sgd:$id1");
			$buf .= QQuad("sgd_resource:$id","sgd_vocabulary:hit","sgd:$id2");
			
			$eid = $id."exp";
			$buf .= QQuad("sgd_resource:$id","sgd_vocabulary:method","sgd_resource:$eid");
			$buf .= QQuad("sgd_resource:$eid","rdf:type",strtolower($oid));
			
			if($phenotype) {
				$buf .= QQuad("sgd_resource:$id","rdf:type",strtolower($exp_type));
				$p = explode(":",$phenotype);
				if(count($p) == 1) {
					// straight match to observable
					$observable = array_search($p[0], $searchlist['observable']);
				} else if(count($p) == 2) {
					// p[0] is the observable and p[1] is the qualifier
					$observable = array_search($p[0], $searchlist['observable']);
					$qualifier = array_search($p[1], $searchlist['qualifier']);
					$buf .= QQuad("sgd_resource:$id","sgd_vocabulary:qualifier",strtolower($qualifier));
				}
				$buf .= QQuad("sgd_resource:$id","sgd_vocabulary:phenotype",strtolower($observable));
			}

			if($htpORman)  $buf .= QQuadL("sgd_resource:$id","sgd_vocabulary:throughput",($htpORman=="manually curated"?"manually curated":"high throughput"));
			$b = explode("|",$ref);
			foreach($b AS $c) {
				$d = explode(":",$c);
				if($d[0]=="PMID") $buf .= QQuad("sgd_resource:$id","sgd_vocabulary:article","pubmed:$d[1]");
			}
			/*
			$buf .= "sgd:$id1 sgd:interactsWith sgd:$id2 .".PHP_EOL;
			$buf .= "sgd:$id2 sgd:interactsWith sgd:$id1 .".PHP_EOL;
			*/
			
			//if($z++ == 1000) {echo $buf;exit;}
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}


	function GetMethodID($label, &$id, &$type) {
	$gi = array(
		'Dosage Rescue' => 'APO:0000173',
		'Dosage Lethality' => 'APO:0000172',
		'Dosage Growth Defect' => 'APO:0000171',
		'Epistatic MiniArray Profile' => 'APO:0000174',
		'Synthetic Lethality' => 'APO:0000183',
		'Synthetic Growth Defect' => 'APO:000018',
		'Synthetic Rescue' => 'APO:0000184',
		'Synthetic Haploinsufficiency'=> 'APO:0000272',
		'Phenotypic Enhancement' => 'APO:0000177',
		'Phenotypic Suppression' => 'APO:0000178',
		'Negative Genetic' => 'APO:0000322',
		'Positive Genetic' => 'APO:0000323',
	);
	$pi = array(
		'Affinity Capture-Luminescence' => 'APO:0000318',
		'Affinity Capture-MS' => 'APO:0000162',
		'Affinity Capture-Western' => 'APO:0000165',
		'Affinity Capture-RNA' => 'APO:0000163',
		'Affinity Chromatography' => 'APO:0000188',
		'Affinity Precipitation' => 'APO:0000189',
		'Biochemical Activity' => 'APO:0000166',
		'Biochemical Assay' => 'APO:0000190',
		'Co-crystal Structure' => 'APO:0000167',
		'Co-fractionation' => 'APO:0000168',
		'Co-localization' => 'APO:0000169',
		'Co-purification' => 'APO:0000170',
		'Far Western' => 'APO:0000176',
		'FRET' => 'APO:0000175',
		'PCA' => 'APO:0000244',
		'Protein-peptide' => 'APO:0000180',
		'Protein-RNA' => 'APO:0000179',
		'Purified Complex' => 'APO:0000191',
		'Reconstituted Complex' => 'APO:0000181',
		'Two-hybrid' => 'APO:0000185',
	);
	if(isset($gi[$label])) {$id=$gi[$label];$type='gene';return;}
	if(isset($pi[$label])) {$id=$pi[$label];$type='protein';return;}
	echo "No match for $label\n";
	}
};



?>
