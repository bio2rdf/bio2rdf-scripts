<?php
/*
1.   Primary SGDID (mandatory)
2.   Feature type (mandatory)
3.   Feature qualifier (optional)
4.   Feature name (optional)
5.   Standard gene name (optional)
6.   Alias (optional, multiples separated by |)
7.   Parent feature name (optional)
8.   Secondary SGDID (optional, multiples separated by |)
9.   Chromosome (optional)
10.  Start_coordinate (optional)
11.  Stop_coordinate (optional)
12.  Strand (optional)
13.  Genetic position (optional)
14.  Coordinate version (optional)
15.  Sequence version (optional)
16.  Description (optional)
*/
class SGD_FEATURES {

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
		global $gns;
		$buf = N3NSHeader();
		$z = 0;		
		while($l = fgets($this->_in,2048)) {
			if($l[0] == '!') continue;
			$a = explode("\t",$l);
			
			$other = '';
			$id = $oid = $a[0];
			$id =urlencode($id);	

//if($z++ == 100) {echo $buf;$buf = '';}
			$buf .= QQuadL("sgd_resource:record_$id",'dc:identifier',"sgd:record_$id");
			$buf .= QQuadL("sgd_resource:record_$id","dc:title","Record for entity identified by sgd:$id");
			$buf .= QQuadL("sgd_resource:record_$id","rdfs:label","Record for entity identified by sgd:$id [sgd:record_$id]");
			$buf .= QQuad("sgd_resource:record_$id","rdf:type","sio:Record");
			$buf .= QQuad("sgd_resource:record_$id","sio:is-about", "sgd:$id");
			$buf .= QQuad("registry_dataset:sgd","sio:has-component-part","sgd_resource:record_$id");

						
			$buf .= QQuadL("sgd:$id","dc:identifier","sgd:$oid");
			$buf .= QQuadL("sgd:$id","rdfs:label","$a[1] [sgd:$id]");
			if($a[15]) $buf .= QQuadL("sgd:$id","dc:description",'""'.trim($a[15]).'""');
			$feature_type = $this->GetFeatureType($a[1]);
			$buf .= QQuad("sgd:$id","rdf:type",strtolower($feature_type));

			unset($type);
			if($a[1] == "ORF") $type = "p";
			elseif(stristr($a[1],"rna")) $type = "r";

			if(isset($type)) {
				unset($p1);unset($p2);
				$gp = 'sgd_resource:'.$id."gp";
				$buf .= QQuad("sgd:$id","sgd_vocabulary:encodes",$gp);
				$buf .= QQuadL($gp,'rdfs:label',"$id"."gp [$gp]");
				if($type == "p") $buf .= QQuad($gp,'rdf:type','sgd_vocabulary:Protein'); 
				elseif($type == "r") $buf .= QQuad($gp,'rdf:type','sgd_vocabulary:RNA'); 

				if($a[1] == "ORF" && $a[3] != '') {
					$p1 = ucfirst(strtolower(str_replace(array("(",")"), array("%28","%29"), $a[3])))."p";
					$buf .= QQuad("sgd:$id","sgd_vocabulary:encodes","sgd:$p1");
					$buf .= QQuad("sgd:$p1","owl:sameAs","$gp");
					$buf .= QQuadL("sgd:$p1","rdfs:label","$p1 [sgd:$p1]");
					$buf .= QQuad("sgd:$p1","rdf:type","sgd_vocabulary:Protein");
				}
				if($a[1] == "ORF" && $a[4] != '') {
					$p2 = ucfirst(strtolower(str_replace(array("(",")"), array("%28","%29"), $a[4])))."p";
					$buf .= QQuad("sgd:$id","sgd_vocabulary:encodes","sgd:$p2");
					$buf .= QQuad("sgd:$p2","owl:sameAs","$gp");
					$buf .= QQuadL("sgd:$p2","rdfs:label","$p2 [sgd:$p2]");
					$buf .= QQuad("sgd:$p2","rdf:type","sgd_vocabulary:Protein");
				}
				if(isset($p1) && isset($p2)) 
					$buf .= QQuad("sgd:$p1","owl:sameAs","sgd:$p2");
			}

			// feature qualifiers (uncharacterized, verified, silenced_gene, dubious)
			if($a[2]) {
				$qualifiers = explode("|",$a[2]);
				foreach($qualifiers AS $q) {
					$buf .= QQuadL("sgd:$id","sgd_vocabulary:status",$q);
				}
			}
			
			// unique feature name
			if($a[3]) {
				$buf .= QQuadL("sgd:$id","sgd_vocabulary:prefLabel",$a[3]);
				$nid = str_replace(array("(",")"), array("%28","%29"),$a[3]);
				$buf .= QQuad("sgd:$id","owl:sameAs","sgd:$nid");
			}
			
			// common names
			if($a[4]) {
				$buf .= QQuadL("sgd:$id","sgd_vocabulary:standardName",$a[4]);
				$nid = str_replace(array("(",")"), array("%28","%29"), $a[4]);
				$buf .= QQuad("sgd:$id","owl:sameAs","sgd:$nid");
			}
			if($a[5]) {
				$b = explode("|",$a[5]);
				foreach($b AS $name) {
					$buf .= QQuadL("sgd:$id","sgd_vocabulary:alias",str_replace('"','',$name));
				}
			}
			// parent feature
			$parent_type = '';
			if($a[6]) {
				$parent = str_replace(array("(",")"," "), array("%28","%29","_"), $a[6]);
//				$parent = urlencode($a[6]);

				$buf .= QQuad("sgd:$id","sgd_vocabulary:is-proper-part-of","sgd_resource:$parent");
				if(strstr($parent,"chromosome")) {
					$parent_type = 'c';
					if(!isset($chromosomes[$parent])) $chromosomes[$parent] = '';
					else {
						$other .= QQuad("sgd_resource:$parent","rdf:type","sgd_vocabulary:Chromosome");
						$other .= QQuadL("sgd_resource:$parent","rdfs:label",$a[6]);
					}
				}
			}
			// secondary sgd id (starts with an L)
			if($a[7]) {
				if($a[3]) {
					$b = explode("|",$a[7]);
					foreach($b AS $c) {
						$buf .= QQuad("sgd:$id","owl:sameAs","sgd:$c");
					}
				}
			}
			// chromosome
			unset($chr);
			if($a[8] && $parent_type != 'c') {
				$chr = "chromosome_".$a[8];
				$buf .= QQuad("sgd:$id","sgd_vocabulary:is-proper-part-of","sgd_resource:$chr");
			}
			// watson or crick strand of the chromosome
			unset($strand);
			if($a[11]) {
				$chr = "chromosome_".$a[8];
				$strand_type = ($a[11]=="w"?"WatsonStrand":"CrickStrand");
				$strand = $chr."_".$strand_type;
				$buf .= QQuad("sgd:$id","sgd_vocabulary:is-proper-part-of","sgd_resource:$strand");
				if(!isset($strands[$strand])) {
					$strands[$strand] = '';
					$other .= QQuad("sgd_resource:$strand","rdf:type","sgd_vocabulary:$strand_type");
					$other .= QQuadL("sgd_resource:$strand","rdfs:label","$strand_type for $chr");
					$other .= QQuad("sgd_resource:$strand","sgd_vocabulary:is-proper-part-of","sgd_resource:$chr");
				}
			}
			
			// position
			if($a[9]) {
				$loc = $id."loc";
				$buf .= QQuad("sgd:$id","sgd_vocabulary:location","sgd_resource:$loc");
				$buf .= QQuadL("sgd_resource:$loc","dc:identifier","sgd_resource:$loc");
				$buf .= QQuadL("sgd_resource:$loc","rdfs:label","Genomic location of sgd:$id");
				$buf .= QQuad("sgd_resource:$loc","rdf:type","sgd_vocabulary:Location");
				$buf .= QQuadL("sgd_resource:$loc","sgd_vocabulary:has-start-position",$a[9]);
				$buf .= QQuadL("sgd_resource:$loc","sgd_vocabulary:has-stop-position",$a[10]);
				if(isset($chr)) $buf .= QQuad("sgd_resource:$loc","sgd_vocabulary:chromosome","sgd_resource:$chr");
				if(isset($strand)) $buf .= QQuad("sgd_resource:$loc","sgd_vocabulary:strand","sgd_resource:$strand");
				/*
				if($a[13]) {
					$b = explode("|",$a[13]);
					foreach($b AS $c) {
						$buf .= QQuadL("sgd_resource:$loc","sgd_vocabulary:modified",$c);
					}
				}
				*/
			}
			/*
			if($a[14]) {
				$b = explode("|",$a[14]);
				foreach($b AS $c) {
					$buf .= QQuadL("sgd_resource:record_$id","sgd_vocabulary:modified",$c);
				}
			}
			*/
		}
		fwrite($this->_out, $buf.$other);
		
		return 0;
	}

	function GetFeatureType($feature_id)
	{
		$feature_map = array (
		'ACS' => 'SO:0000436',
		'ARS consensus sequence' => 'SO:0000436',
		'binding_site' => 'SO:0000409',
		'CDEI' => 'SO:0001493',
		'CDEII' => 'SO:0001494',
		'CDEIII' => 'SO:0001495',
		'CDS' => 'SO:0000316',
		'centromere' => 'SO:0000577',
		'external_transcribed_spacer_region' => 'SO:0000640',
		'internal_transcribed_spacer_region' => 'SO:0000639',
		'intron' => 'SO:0000188',
		'long_terminal_repeat' => 'SO:0000286',
		'ncRNA' => 'SO:0000655',
		'noncoding_exon' => 'SO:0000445',
		'non_transcribed_region' => 'SO:0000183',
//	not in systematic sequence of S288C
//		'not physically mapped' => 'NotPhysicallyMappedFeature',
		'ORF' => 'SO:0000236',
		'plus_1_translational_frameshift' => 'SO:0001211',
		'pseudogene' => 'SO:0000336',
		'repeat_region' => 'SO:0000657',
		'retrotransposon' => 'SO:0000180',
		'rRNA' => 'SO:0000573',
		'snoRNA' => 'SO:0000578',
		'snRNA' => 'SO:0000623',
		'telomere' => 'SO:0000624',
		'telomeric_repeat' => 'SO:0001496',
		'transposable_element_gene' => 'SO:0000180',
		'tRNA' => 'SO:0000663',
		'X_element_combinatorial_repeats' => 'SO:0001484',
		'X_element_core_sequence' => 'SO:0001497',
		"Y_element" => 'SO:0001485'
		);

	if(isset($feature_map[$feature_id])) return $feature_map[$feature_id];
	else return "SO:0000830";
}
};

/*
The SGD database uses the following terms :

label: Centromere DNA Element I
synonym: CDEI
definition: A Centromere DNA Element I (CDEI) is a DNA consensus region composed of 8-11bp which enables binding by the centromere binding factor 1 (Cbf1p)
 
label: Centromere DNA Element II
synonym: CDEII
definition: A Centromere DNA Element II (CDEII) is a DNA consensus region that is AT-rich and ~ 75-100 bp in length.

CDEIII - Centromere DNA Element III
synonym: CDEIII
definition: A Centromere DNA Element III (CDEIII) is a DNA consensus region that consists of a 25-bp which enables binding by the centromere DNA binding factor 3 (CBF3) complex.


*/
?>
