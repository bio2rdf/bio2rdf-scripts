<?php
/**
Copyright (C) 2012 Michel Dumontier

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

/**
 * OMIM RDFizer
 * @version 1.0
 * @author Michel Dumontier
 * @description http://www.fda.gov/Drugs/InformationOnDrugs/ucm142454.htm
*/
require('../../php-lib/rdfapi.php');
class OMIMParser extends RDFFactory 
{
	private $ns = null;
	private $named_entries = array();
	
	private $file2fnx = array(
		'genemap' => 'genemap',
		'mim2gene.txt' => 'mim2gene',
		'morbidmap' => 'morbidmap',
		'omim.txt.Z' => 'omim',
		'omim.txt' => 'omim'
	);
	
	function __construct($argv) {
		parent::__construct();
		// set and print application parameters
		$this->AddParameter('files',true,'all|genemap|genemap.key|mim2gene.txt|morbidmap|omim.txt.Z','all','files to process');
		$this->AddParameter('indir',false,null,'/data/download/omim/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/omim/','directory to place rdfized files');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'ftp://grcf.jhmi.edu/OMIM/');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		return TRUE;
	}
	
	function Run()
	{
		
		$ldir = $this->GetParameterValue('indir');
		@mkdir($ldir,'0755',true);
		$odir = $this->GetParameterValue('outdir');
		@mkdir($odir,'0755',true);
		$rurl = $this->GetParameterValue('download_url');
		
		// get the file list
		if($this->GetParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode("|",$this->GetParameterValue('files'));
		}

		foreach($files AS $file) {
			// check if exists
			if(!file_exists($ldir.$file)) {
				trigger_error($ldir.$file." not found. Will attempt to download. ", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
			}
			
			if($this->GetParameterValue('download')==true) {
				// connect
				if(!isset($ftp)) {
					$host = 'grcf.jhmi.edu';
					echo "connecting to $host ...";
					$ftp = ftp_connect($host);
					ftp_login($ftp, 'anonymous', 'bio2rdf@gmail.com');
					echo "success!".PHP_EOL;			
				}
				// download
				echo "Downloading $file ...";
				if(ftp_get($ftp, $ldir.$file, 'omim/'.$file, FTP_BINARY) === FALSE) {
					trigger_error("Error in downloading $file");
					continue;
				}
				echo "success!".PHP_EOL;
			}
			
			// process
			if(isset($this->file2fnx[$file])) {
				echo "Processing $file...";
				if($file == 'omim.txt.Z') {
					if(!file_exists($ldir."omim.txt")) {
						// gotta use the system call: gz and ziparchive don't work
						system("gunzip -c ".$ldir.$file." > ".$ldir."omim.txt");
					}
					$file = "omim.txt"; // this is the new file to process
				}
				$fp = fopen($ldir.$file,"r");
				if($fp === FALSE) {
					trigger_error("Unable to open $ldir.$file");
					exit;
				}
			
				$fnx = $this->file2fnx[$file];
				$this->$fnx($fp);
			
				if(!isset($gzout)) {
					$gzoutfile = $odir.'omim.ttl.gz';
					if (($gzout = gzopen($gzoutfile,"w"))=== FALSE) {
						trigger_error("Unable to open $odir.$gzoutfile");
						exit;
					}
				}
				gzwrite($gzout,$this->GetRDF());
				$this->DeleteRDF();
				
				//if($file == "omim.txt") unlink($ldir.$file);
				echo "done!".PHP_EOL;
			}
		}
		if(isset($ftp))
			ftp_close($ftp);
		if(isset($gzout)) 
			gzclose($gzout);
	
	}
	
	function genemap($fp)
	{
		$types='';
		
		$this->get_phenotype_mapping_method_type(null,true);
		$this->get_method_type(null,true);
		
		while($l = fgets($fp)) {
			$a = explode("|",$l);
			$omim_id = $a[9];
			$omim_uri = "omim:$omim_id";
			
			// create the record
			$record_uri = 'omim_resource:'.'record_'.$omim_id;
			$this->AddRDF($this->QQuadL($record_uri,"rdfs:label","Record for omim:$omim_id [$record_uri]"));
			$this->AddRDF($this->QQuad($record_uri,"rdf:type","omim_vocabulary:Record"));
			$this->AddRDF($this->QQuadL($record_uri,"omim_vocabulary:created",$a[3].'-'.$a[2].'-'.$a[1]));
			$this->AddRDF($this->QQuad($omim_uri,"rdfs:isDefinedBy",$record_uri));

			// describe the disease
			$this->AddRDF($this->QQuadL($omim_uri, "rdfs:label", $a[7].$a[8]." [$omim_uri]"));
			$this->AddRDF($this->QQuadL($omim_uri, "omim_vocabulary:identifier", $a[0]));
			$this->AddRDF($this->QQuadL($omim_uri, "omim_vocabulary:cytogenic-location", $a[4]));
			if($a[5]) {
				$b = explode(",",str_replace( array(" ",".",";"), ",", $a[5]));
				foreach($b AS $symbol) {
					$symbol = trim($symbol);
					if($symbol) 
						$this->AddRDF($this->QQuad($omim_uri, "omim_vocabulary:gene-symbol", "symbol:".$symbol));
				}
			}
			if($a[6]) {
				$status_codes = array(
					"C" => array("status"=> "confirmed", 
							"description" => "observed in at least two laboratories or in several families."),
					"P" => array("status"=>"provisional", 
							"description"  => "based on evidence from one laboratory or one family"),
					"I" => array("status"=>"inconsistent", 
							"description"  => "results of different laboratories disagree"),
					"L" => array("status"=>"tentative", 
							"description"  => "evidence not as strong as that provisional, but included for heuristic reasons")
				);
				$b = $status_codes[$a[6]];
				$status_uri = "omim_vocabulary:".$b['status'];
				$this->AddRDF($this->QQuad($omim_uri, "omim_vocabulary:gene-status", $status_uri));
				if(!isset($types[$status_uri])) {
					$types[$status_uri] = '';
					$this->AddRDF($this->QQuadL($status_uri, "rdfs:label", $b['status']." [$status_uri]"));
					$this->AddRDF($this->QQuadL($status_uri,"dc:description",$b['description']));
				}
				
			}
			if($a[10]) {

				$b = explode(",",$a[10]); // mv
				foreach($b AS $c) {
					$method = trim($c);
					$method_uri = "omim_vocabulary:$method";

					if(isset($methods[$method])) {
						$this->AddRDF($this->QQuad($omim_uri, "omim_vocabulary:method", $method_uri));
					}
				}
			}
		
			if($a[11]) {
				$this->AddRDF($this->QQuadL($omim_uri, "omim_vocabulary:comment", addslashes($a[11].$a[12])));
				// parse out rs* mentions
			}
			if($a[13]) { // associated phenotypes
				$t = $a[13].$a[14].$a[15];
				$b = explode(";",$t);
				foreach($b AS $c) {
					preg_match("/([0-9]+)? \(([1-4])\)/",$c,$m);
					
					$did = $omim_id;
					$did_uri = $omim_uri;
					if(isset($m[1]) && $m[1] != '') {
						// phenotype id
						$did = $m[1];
						$did_uri = 'omim:'.$m[1];
					}
					
					if(isset($m[2])) {
						// phenotype mapping method
						$pid = $m[2];

						$pa = "omim_resource:".$omim_id."_".$did;
						$this->AddRDF($this->QQuadL($pa, "rdfs:label", "$omim_uri - $did_uri genotype-phenotype mapping [$pa]"));
						$this->AddRDF($this->QQuad($pa, "rdf:type", "omim_vocabulary:Genotype-Phenotype-Association"));
						$this->AddRDF($this->QQuadL($pa, "omim_vocabulary:genotype", $a[4]));
						$this->AddRDF($this->QQuad($pa, "omim_vocabulary:source-phenotype", $omim_uri));
						$this->AddRDF($this->QQuad($pa, "omim_vocabulary:target-phenotype", $did_uri));
						$this->AddRDF($this->QQuad($pa, "omim_vocabulary:evidence", $this->get_phenotype_mapping_method_type($pid)));
					}
				}
			}
			
		}
	}
	
	function get_phenotype_mapping_method_type($id = null, $generate_declaration = false)
	{
		$pmm = array(
			"1" => array("name"=>"association",
					"description" => "the disorder is placed on the map based on its association with a gene"),
			"2" => array("name" => "linkage",
					"description" => "the disorder is placed on the map by linkage"),
			"3" => array("name" => "mutation",
					"description" => "the disorder is placed on the map and a mutation has been found in the gene"),
			"4" => array("name" => "copy-number-variation",
					"description" => "the disorder is caused by one or more genes deleted or duplicated")
		);
		
		if($generate_declaration == true) {
			foreach($pmm AS $i => $o) {
				$pmm_uri = "omim_vocabulary:".$pmm[$i]['name'];
				$this->AddRDF($this->QQuadL($pmm_uri, "rdfs:label", $pmm[$pid]['description']." [$pmm_uri]"));
			}
		}
			
		if(isset($id)) {
			if(isset($pmm[$id])) return 'omim_vocabulary:'.$pmm[$id]['name'];
			else return false;
		}
		return true;
	}
	
	function get_method_type($id = null, $generate_declaration = false)
	{
		$methods = array(
			"A" => "in situ DNA-RNA or DNA-DNA annealing (hybridization)",
			"AAS" => "inferred from the amino acid sequence",
			"C" => "chromosome mediated gene transfer",
			"Ch" => "chromosomal change associated with phenotype and not linkage (Fc), deletion (D), or virus effect (V)",
			"D" => "deletion or dosage mapping, trisomy mapping, or gene dosage effects",
			"EM" => "exclusion mapping",
			"F" => "linkage study in families",
			"Fc" => "linkage study - chromosomal heteromorphism or rearrangement is one trait",
			"Fd" => "linkage study - one or both of the linked loci are identified by a DNA polymorphism",
			"H" =>  "based on presumed homology",
			"HS" => "DNA/cDNA molecular hybridization in solution (Cot analysis)",
			"L" => "lyonization",
			"LD" => "linkage disequilibrium",
			"M" => "Microcell mediated gene transfer",
			"OT" => "ovarian teratoma (centromere mapping)",
			"Pcm" => "PCR of microdissected chromosome segments (see REl)",
			"Psh" => "PCR of somatic cell hybrid DNA",
			"R" => "irradiation of cells followed by rescue through fusion with nonirradiated (nonhuman) cells (Goss-Harris method of radiation-induced gene segregation)",
			"RE" => "Restriction endonuclease techniques",
			"REa" => "combined with somatic cell hybridization",
			"REb" => "combined with chromosome sorting",
			"REc" => "hybridization of cDNA to genomic fragment (by YAC, PFGE, microdissection, etc.)",
			"REf" => "isolation of gene from genomic DNA; includes exon trapping",
			"REl" => "isolation of gene from chromosome-specific genomic library (see Pcm)",
			"REn" => "neighbor analysis in restriction fragments",
			"S" => "segregation (cosegregation) of human cellular traits and human chromosomes (or segments of chromosomes) in particular clones from interspecies somatic cell hybrids",
			"T" => "TACT telomere-associated chromosome fragmentation",
			"V" => "induction of microscopically evident chromosomal change by a virus",
			"X/A" => "X-autosome translocation in female with X-linked recessive disorder"
		);
		if($generate_declaration == true) {
			foreach($methods AS $k => $v) {
				$method_uri = "omim_vocabulary:$k";
				$this->AddRDF($this->QQuadL($method_uri, "rdfs:label", $method_uri[$k]." [$method_uri]"));
			}
		}
		
		if(isset($id)) {
			if(isset($methods[$id])) return 'omim_vocabulary:'.$methods[$id];
			else return false;
		}
		return true;
	}	
	
	/*
	0 Mim Number
	1 Type
	2 Gene IDs
	3 Approved Gene Symbols
	*/
	
	function mim2gene($fp)
	{
		$i =0;
		fgets($fp);
		while($l = fgets($fp)) {
			$a = explode("\t",$l);
			$omim_uri = "omim:".$a[0];
			$type = $a[1];
			$this->AddRDF($this->QQuad($omim_uri, "rdf:type", "omim_vocabulary:".ucfirst($type)));
			
			if(trim($a[2]) != '-') {
				$gene_uri = "geneid:".$a[2];
				$this->AddRDF($this->QQuad($omim_uri, "omim_vocabulary:gene", $gene_uri));
			}
			if(trim($a[3]) != '-') {
				$symbol_uri = "symbol:".trim($a[3]);
				$this->AddRDF($this->QQuad($omim_uri, "omim_vocabulary:gene-symbol", $symbol_uri));
			}
			//if($i++ == 10) {echo $this->GetRDF();exit;}
		}
		
	}
	
	/*
		0  - Disorder, <disorder MIM no.> (<phene mapping key>)
		1  - Gene/locus symbols
		2  - Gene/locus MIM no.
		3  - cytogenetic location
	*/
	function morbidmap($fp)
	{
		while($l = fgets($fp)) {
			$a = explode("|",$l);
			
			$b = explode(",",$a[0]);
			$d = trim(array_pop($b));
			$disorder_id = ''; $phene_key = '';
			if(FALSE != ($pos1 = strrpos($d,"("))) {
				$pos2 = strrpos($d,")");
				$disorder_id = substr($d,0,$pos1-1);
				$phene_key = substr($d,$pos1+1,-1);
			}
			if($disorder_id == '' || !is_numeric($disorder_id)) {
				continue;
			}
			$id ++;
			// generate the mapping statement and evidence
			$id_uri = "omim_resource:morbidmap_".$id;
			$this->AddRDF($this->QQuad($id_uri, "rdf:type", "omim_vocabulary:Disease-to-Gene-Mapping"));
			$this->AddRDF($this->QQuadL($id_uri, "rdfs:label", "disease to gene mapping"));
			
			if($phene_key) {
				$this->AddRDF($this->QQuad($id_uri, "omim_vocabulary:evidence", $this->get_phenotype_mapping_method_type($phene_key)));
			}
			$this->AddRDF($this->QQuad($id_uri, "omim_vocabulary:disorder", "omim:".$disorder_id));
			if($a[1]) {
				$b = explode(",",str_replace( array(" ",".",";"), ",", $a[1]));
				foreach($b AS $symbol) {
					$symbol = trim($symbol);
					if($symbol)
						$this->AddRDF($this->QQuad($id_uri, "omim_vocabulary:gene-symbol", "symbol:".$symbol));	
				}
			}
			$this->AddRDF($this->QQuad($id_uri, "omim_vocabulary:gene-locus-omim", "omim:".$a[2]));		
			$this->AddRDF($this->QQuadL($id_uri, "omim_vocabulary:cytogenic-location", trim($a[3])));		
		}
		
	}
	
	function omim($fp)
	{
		$i = 0;
		while($l = fgets($fp,8000)) {			
			if(strstr($l,"*RECORD*")) {
				// new entry
				if(isset($o)) {
					foreach($o AS $k => $v) {
						$o[$k] = trim($v);
					}
					$this->process_omim_entry($o);
					if($i++ == 25) break;
					unset($o);
				}
				continue;
			}
			
			if(strstr($l,"*FIELD*")) {
				$a = explode(" ",$l);
				$field = trim($a[1]);
				$fields[$field] = $field;
				continue;
			}
			$o[$field] .= $l;
		}
		return true;
	}
	
	/*
		NO - number
		TI - title
		TX - text
		RF - reference
		CS - clinical synopsis
		CD - curator
		ED - edits 
		SA - see also (references)
		CN - curator note
		AV - allelic variants
	*/
	
	function process_omim_entry($entry)
	{
		$omim_uri = "omim:".$entry['NO'];
		$this->AddRDF($this->QQuad($omim_uri, "rdf:type", "omim_vocabulary:Phenotype"));		
		if(isset($entry['TI'])) {
			// break apart the title, into primary and secondary
			$a = explode(";;",$entry['TI']);
			$primary_titles = explode(";",$a[0]);
			foreach($primary_titles AS $i => $t) {
				$this->AddRDF($this->QQuadText($omim_uri, "omim_vocabulary:primary-title", $t));
			}
			$secondary_titles = explode(";",$a[1]);
			foreach($secondary_titles AS $t) {
				$this->AddRDF($this->QQuadText($omim_uri, "omim_vocabulary:secondary-title", $t));
			}			
			
			$this->AddRDF($this->QQuadText($omim_uri, "rdfs:label", $a[0]." [$omim_uri]"));
		}
		if(isset($entry['TX'])) {
			$this->AddRDF($this->QQuadText($omim_uri, "dc:description", $entry['TX']));
			
			// parse the omim references
			preg_match_all("/\(([0-9]{6})\)/",$entry['TX'],$m);
			if(isset($m[1][0])) {
				foreach($m[1] AS $oid) {
					$this->AddRDF($this->QQuad($omim_uri, "omim_vocabulary:refers-to", "omim:$oid" ));
				}
			}
			
			
			
			// we can parse this out into sections
			// DESCRIPTION, CLINICAL FEATURES, INHERITANCE, PATHOGENESIS, MAPPING, MOLECULAR GENETICS, CLONING, GENE STRUCTURE, ANIMAL MODEL, GENE FUNCTION, BIOCHEMICAL FEATURES, HISTORY, EVOLUTION, GENE FAMILY, GENETIC VARIABILITY, CYTOGENETICS, DIAGNOSIS, POPULATION GENETICS, GENOTYPE/PHENOTYPE CORRELATIONS, NOMENCLATURE, OTHER FEATURES, HETEROGENEITY, PHENOTYPE, GENE THERAPY, CLINICAL MANAGEMENT, GENOTYPE
			$ll = ''; $lsection = null; $sbuf = '';
			$b = explode("\n",$entry['TX']);
			foreach($b AS $c) {				
				if(isset($b[$c+1])) $nl=$b[$c+1];
				else $nl = null;
				
				if($c != '') {
					if(preg_match("/[0-9\.\(\)\-]/",$c,$m) == 0) { // get rid of garbage
						if(isset($ll) && $ll == '' && isset($nl) && $nl == ''  // make sure that it's a header that sits with empty lines before and after
							&& strcmp(strtoupper($c), $c) === 0) {
							$section = $c;
							
							if(isset($lsection)) {
								$p = strtolower(str_replace(array(" ","/"),"-",$lsection));
								$this->AddRDF($this->QQuadText($omim_uri, "omim_vocabulary:".$p, $sbuf));
								$sbuf = '';
							}
							$lsection = $section;
						}
					}
				}
				$ll = $c;
				$sbuf .= $c."\n";
			}
			if(!isset($lsection)) {
				$this->AddRDF($this->QQuadText($omim_uri, "omim_vocabulary:text", $sbuf));
			} else {
				$p = strtolower(str_replace(array(" ","/"),"-",$lsection));
				$this->AddRDF($this->QQuadText($omim_uri, "omim_vocabulary:".$p, $sbuf));
			}
			
		}
		
		if(isset($entry['RF'])) $this->AddRDF($this->QQuadText($omim_uri, "omim_vocabulary:reference", $entry['RF']));
		if(isset($entry['CS'])) $this->AddRDF($this->QQuadText($omim_uri, "omim_vocabulary:clinical-synopsis", $entry['CS']));
		if(isset($entry['AV'])) $this->AddRDF($this->QQuadText($omim_uri, "omim_vocabulary:allelic-variants", $entry['AV']));
		if(isset($entry['SA'])) $this->AddRDF($this->QQuadText($omim_uri, "omim_vocabulary:see-also", $entry['SA']));
		
		
	}
} 


$parser = new OMIMParser($argv);
$parser->Run();
?>