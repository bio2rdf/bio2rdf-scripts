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
			$buf .= "sgd:record_$id dc:identifier \"sgd:record_$id\" .".PHP_EOL;
			$buf .= "sgd:record_$id dc:title \"Record for entity identified by sgd:$id\" .".PHP_EOL;
			$buf .= "sgd:record_$id rdfs:label \"Record for entity identified by sgd:$id [sgd:record_$id]\" .".PHP_EOL;
			$buf .= "sgd:record_$id a sio:Record .".PHP_EOL;
			$buf .= "sgd:record_$id sio:SIO_000332 sgd:$id .".PHP_EOL;
			$buf .= "sgd:record_$id sio:SIO_000068 registry_dataset:sgd .".PHP_EOL;

						
			$buf .= "sgd:$id dc:identifier \"sgd:$oid\" .".PHP_EOL;
			$buf .= "sgd:$id rdfs:label \"$a[1] [sgd:$id]\" .".PHP_EOL;
			if($a[15]) $buf .= "sgd:$id dc:description ".'"""'.trim($a[15]).'""" .'.PHP_EOL;
			$feature_type = $this->GetFeatureType($a[1]);
			$buf .= "sgd:$id a ".strtolower($feature_type).". ".PHP_EOL;

			unset($type);
			if($a[1] == "ORF") $type = "p";
			elseif(stristr($a[1],"rna")) $type = "r";

			if(isset($type)) {
				unset($p1);unset($p2);
				$gp = 'sgd_resource:'.$id."gp";
				$buf .= "sgd:$id sio:SIO_010078 $gp.".PHP_EOL;
				$buf .= "<http://bio2rdf.org/$gp> rdfs:label \"$id"."gp [$gp]\".".PHP_EOL;
				if($type == "p") $buf .= "<http://bio2rdf.org/$gp> a chebi:36080 .".PHP_EOL;
				elseif($type == "r") $buf .= "<http://bio2rdf.org/$gp> a chebi:33697 .".PHP_EOL;

				if($a[1] == "ORF" && $a[3] != '') {
					$p1 = ucfirst(strtolower(str_replace(array("(",")"), array("%28","%29"), $a[3])))."p";
					$buf .= "sgd:$id sio:SIO_010078 <http://bio2rdf.org/sgd:$p1>.".PHP_EOL;
					$buf .= "<http://bio2rdf.org/sgd:$p1> owl:sameAs <http://bio2rdf.org/$gp>.".PHP_EOL;
					$buf .= "<http://bio2rdf.org/sgd:$p1> rdfs:label \"$p1 [sgd:$p1]\".".PHP_EOL;
					$buf .= "<http://bio2rdf.org/sgd:$p1> a chebi:36080 .".PHP_EOL;
				}
				if($a[1] == "ORF" && $a[4] != '') {
					$p2 = ucfirst(strtolower(str_replace(array("(",")"), array("%28","%29"), $a[4])))."p";
					$buf .= "sgd:$id sio:SIO_010078 <http://bio2rdf.org/sgd:$p2>.".PHP_EOL;
					$buf .= "<http://bio2rdf.org/sgd:$p2> owl:sameAs <http://bio2rdf.org/$gp>.".PHP_EOL;
					$buf .= "<http://bio2rdf.org/sgd:$p2> rdfs:label \"$p2 [sgd:$p2]\".".PHP_EOL;
					$buf .= "<http://bio2rdf.org/sgd:$p2> a chebi:36080 .".PHP_EOL;
				}
				if(isset($p1) && isset($p2)) 
					$buf .= "<http://bio2rdf.org/sgd:$p1> owl:sameAs <http://bio2rdf.org/sgd:$p2>.".PHP_EOL;
			}

			// feature qualifiers (uncharacterized, verified, silenced_gene, dubious)
			if($a[2]) {
				$qualifiers = explode("|",$a[2]);
				foreach($qualifiers AS $q) {
					$buf .= "sgd:$id sgd:status \"$q\" .".PHP_EOL;
				}
			}
			
			// unique feature name
			if($a[3]) {
				$buf .= "sgd:$id skos:prefLabel \"$a[3]\".".PHP_EOL;
				$nid = str_replace(array("(",")"), array("%28","%29"), $a[3]);
				$buf .= "sgd:$id owl:sameAs <http://bio2rdf.org/sgd:$nid> .".PHP_EOL;
			}
			
			// common names
			if($a[4]) {
				$buf .= "sgd:$id sgd:standardName \"$a[4]\".".PHP_EOL;
				$nid = str_replace(array("(",")"), array("%28","%29"), $a[4]);
				$buf .= "sgd:$id owl:sameAs <http://bio2rdf.org/sgd:$nid>.".PHP_EOL;
			}
			if($a[5]) {
				$b = explode("|",$a[5]);
				foreach($b AS $name) {
					$buf .= "sgd:$id sgd:alias \"".str_replace('"','',$name)."\".".PHP_EOL;
				}
			}
			// parent feature
			$parent_type = '';
			if($a[6]) {
				$parent = str_replace(array("(",")"," "), array("%28","%29","_"), $a[6]);
//				$parent = urlencode($a[6]);

				$buf .= "sgd:$id sio:SIO_000068 <http://bio2rdf.org/sgd:$parent> .".PHP_EOL;
				if(strstr($parent,"chromosome")) {
					$parent_type = 'c';
					if(!isset($chromosomes[$parent])) $chromosomes[$parent] = '';
					else {
						$other .= "sgd:$parent a so:0000340 .".PHP_EOL;
						$other .= "sgd:$parent rdfs:label \"$a[6]\" .".PHP_EOL;
					}
				}
			}
			// secondary sgd id (starts with an L)
			if($a[7]) {
				if($a[3]) {
					$b = explode("|",$a[7]);
					foreach($b AS $c) {
						$buf .= "sgd:$id owl:sameAs sgd:$c.".PHP_EOL;
					}
				}
			}
			// chromosome
			unset($chr);
			if($a[8] && $parent_type != 'c') {
				$chr = "chromosome_".$a[8];
				$buf .= "sgd:$id sio:SIO_000068 sgd:$chr .".PHP_EOL;
			}
			// watson or crick strand of the chromosome
			unset($strand);
			if($a[11]) {
				$chr = "chromosome_".$a[8];
				$strand_type = ($a[11]=="w"?"WatsonStrand":"CrickStrand");
				$strand = $chr."_".$strand_type;
				$buf .= "sgd:$id sio:SIO_000068 sgd_resource:$strand .".PHP_EOL;
				if(!isset($strands[$strand])) {
					$strands[$strand] = '';
					$other .= "sgd_resource:$strand a sgd_vocabulary:$strand_type .".PHP_EOL;
					$other .= "sgd_resource:$strand rdfs:label \"$strand_type for $chr\" .".PHP_EOL;
					$other .= "sgd_resource:$strand sgd:SIO_000068 sgd_resource:$chr .".PHP_EOL;
				}
			}
			
			// position
			if($a[9]) {
				$loc = $id."loc";
				$buf .= "sgd:$id sgd:location sgd_resource:$loc .".PHP_EOL;
				$buf .= "sgd_resource:$loc dc:identifier \"sgd_resource:$loc\" .".PHP_EOL;
				$buf .= "sgd_resource:$loc rdfs:label \"Genomic location of sgd:$id\" .".PHP_EOL;
				$buf .= "sgd_resource:$loc a sgd_vocabulary:Location .".PHP_EOL;
				$buf .= "sgd_resource:$loc sgd_vocabulary:hasStartPosition \"$a[9]\" .".PHP_EOL;
				$buf .= "sgd_resource:$loc sgd_vocabulary:hasStopPosition \"$a[10]\" .".PHP_EOL;
				if(isset($chr)) $buf .= "sgd_resource:$loc sgd_vocabulary:chromosome sgd_resource:$chr.".PHP_EOL;
				if(isset($strand)) $buf .= "sgd_resource:$loc sgd_vocabulary:strand sgd_resource:$strand.".PHP_EOL;
				if($a[13]) {
					$b = explode("|",$a[13]);
					foreach($b AS $c) {
						$buf .= "sgd_resource:$loc sgd_vocabulary:modified \"$c\" .".PHP_EOL;
					}
				}
			}
			if($a[14]) {
				$b = explode("|",$a[14]);
				foreach($b AS $c) {
					$buf .= "sgd_resource:record_$id sgd_vocabulary:modified \"$c\" .".PHP_EOL;
				}
			}
			
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
