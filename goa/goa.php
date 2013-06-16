<?php

/**
Copyright (C) 2012 Alison Callahan, Jose Cruz-Toledo, Michel Dumontier

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
 * An RDF generator for GOA (http://www.ebi.ac.uk/GOA/)
 * @version 1.0
 * @author Alison Callahan
 * @author Jose Cruz-Toledo
 * @author Michel Dumontier
*/

require('../../php-lib/rdfapi.php');

class GOAParser extends RDFFactory {
	private $version = null;

	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("goa");
		
		// set and print application parameters
		$this->AddParameter('files',true,'all|arabidopsis|chicken|cow|dicty|dog|fly|human|mouse|pdb|pig|rat|uniprot|worm|yeast|zebrafish','all','all or comma-separated list of files to process');
		$this->AddParameter('indir',false,null,'/data/download/goa/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/goa/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'ftp://ftp.ebi.ac.uk/pub/databases/GO/goa/');
		
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
		
		return TRUE;
	}

	function Run(){

		if($this->GetParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",$this->GetParameterValue('files'));
		}

		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rdir = $this->GetParameterValue('download_url');
		
		//make sure directories end with slash
		if(substr($ldir, -1) !== "/"){
			$ldir = $ldir."/";
		}
		
		if(substr($odir, -1) !== "/"){
			$odir = $odir."/";
		}

		foreach($files as $file){

			$lfile = $ldir."goa_".$file.".gz";
			if(!file_exists($lfile) && $this->GetParameterValue('download') == false) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
			}

			//download all files
			if($this->GetParameterValue('download') == true) {
				$rfile = $rdir.strtoupper($file)."/gene_association.goa_".$file.".gz";
				echo "downloading $file ... ";
				file_put_contents($lfile,file_get_contents($rfile));
			}

			$ofile = $odir."goa_".$file.'.nt'; $gz=false;	
			if($this->GetParameterValue('graph_uri')) {$ofile = $odir."goa_".$file.'.nq';}
			if($this->GetParameterValue('gzip')) {$ofile .= '.gz';$gz = true;}
			
			$this->SetReadFile($lfile, TRUE);
			$this->SetWriteFile($ofile, $gz);
			
			echo "processing $file ... ";
			$this->process($file);
			echo "done!";

			//close write file
			$this->GetWriteFile()->Close();
			echo PHP_EOL;
			
		}//foreach

		// generate the dataset release file
		echo "generating dataset release file ... ";
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/goa/goa.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()),
			"http://www.ebi.ac.uk/GOA/",
			array("use"),
			"http://www.ebi.ac.uk/GOA/goaHelp.html",
			$this->GetParameterValue('download_url'),
			$this->version
			);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		echo "done!".PHP_EOL;

	}

	function process($file){
		$z = 1;
		while($l = $this->GetReadFile()->Read(100000)) {
			$fields = $this->parse_goa_file_line($l);
			if($fields != null){
				//get the Go id
				$db = $fields[0];
				$id = $fields[1];
				$symbol = $fields[2];
				$qualifier = $fields[3];
				$goid = substr($fields[4],3);
				$refs = $this->getDbReferences($fields[5]);
				$eco = $this->getEvidenceCodeLabelArr($fields[6]);
				$aspect = $this->getAspect($fields[8]);
				$label = $fields[9];
				$synonyms = $this->getGeneSynonyms($fields[10]);
				$taxid = $fields[12];
				$date = $this->parseDate($fields[13]);	
				$assignedBy = $fields[14];

				$eid = $this->getdbURI($db,$id);
				if(!isset($declared[$eid])) {
					$declared[$eid] = '';
					$this->AddRDF($this->QQuadL($eid,"rdfs:label",addslashes($label)." [$eid]"));
					$this->AddRDF($this->QQuad($eid, "void:inDataset", $this->GetDatasetURI()));
					$this->AddRDF($this->QQuadL($eid, "goa_vocabulary:symbol", addslashes($symbol)));
					$this->AddRDF($this->QQuad($eid, "goa_vocabulary:taxid", $taxid));	
					foreach($synonyms as $s){
						if(!empty($s)){
							$this->AddRDF($this->QQuadL($eid, "goa_vocabulary:synonym", addslashes($s)));
						}
					}
				}

				$rel = $aspect;
				if($qualifier == 'NOT') {
					if($aspect == 'process') $rel = 'not-in-process';	
					if($aspect == 'function') $rel = 'not-has-function';
					if($aspect == 'component') $rel = 'not-in-component';	
				}

				$this->AddRDF($this->QQuad($eid, "goa_vocabulary:".$rel, "go:".$goid));

				$type = key($eco);
				$aid = "goa_resource:$file"."_".($z++);
				$this->AddRDF($this->QQuad($eid, "goa_vocabulary:go-annotation", $aid));
				$this->AddRDF($this->QQuadL($aid, "rdfs:label", "$id-go:$goid association [$aid]"));
				$this->AddRDF($this->QQuad($aid, "rdf:type", "goa_vocabulary:GO-Annotation"));
				$this->AddRDF($this->QQuad($aid,"void:inDataset",$this->GetDatasetURI()));

				$this->AddRDF($this->QQuad($aid, "goa_vocabulary:target", $eid));
				$this->AddRDF($this->QQuad($aid, "goa_vocabulary:go-term", "go:".$goid));
				$this->AddRDF($this->QQuadL($aid, "goa_vocabulary:go-category", "$aspect"));
				$this->AddRDF($this->QQuad($aid, "goa_vocabulary:evidence", "eco:".$eco[$type][1]));
				$this->AddRDF($this->QQuadL($aid, "goa_vocabulary:assigned-by", $assignedBy));
				$this->AddRDF($this->QQuadL($aid, "goa_vocabulary:entry-date", $date, null, "xsd:date"));	
				foreach($refs as $ref){
					$b = explode(":",$ref);
					if($b[0] == 'PMID') $this->AddRDF($this->QQuad($aid, "goa_vocabulary:article", "pubmed:".$b[1]));
				}

				//write RDF to file
				$this->WriteRDFBufferToWriteFile();
			}
		}
	}

	function parse_goa_file_line($aLine){
		$returnMe = array();
		$lineArr = explode("\t",$aLine);
		//parse only annotation lines
		if(count($lineArr) == 17){
			return $lineArr;		
		}else{
			return null;
		}
		return $returnMe;
	}

	function getAspect($anAspect){
		if(count($anAspect)){
			if($anAspect == "F"){
				return "function";
			}elseif($anAspect == "P"){
				return "process";
			}elseif($anAspect == "C"){
				return "component";
			}

		}else{
			return null;
		}
	}

	function getDbReferences($aDbReference){
		$a = explode("|",$aDbReference);
		return $a;
	}

	/**
	 * This function returns the corresponding 
	 * bio2rdf URI for the GOA entry for the given
	 * db and db_id
	 **/
	function getdbURI($db_id, $db_object_id){
		$returnMe = "";
		if($db_id == "UniProtKB"){
			$returnMe = "uniprot:".$db_object_id;
		} else if ($db_id == "PDB"){
			$split_object = explode("_", $db_object_id);
			$returnMe = "pdb:".$split_object[0]."/chain_".$split_object[1];
		}
		return $returnMe;
	}

	/**
 	* This function return an array that has as a key
	 * the name of the category to which the evidence code belongs
	 * to and as a value the label for the code.
	 * For example the evidence code "EXP" will return:
	 * ["Experimental Evidence Code" => ["Inferred from Experiment", "0000006"]]
	 * See: http://www.geneontology.org/GO.evidence.shtml
	 **/
	function getEvidenceCodeLabelArr($aec){
		if(count($aec)){
			//experimental code
			$ec = array(
				"EXP"=> array("Inferred from Experiment","0000006"),
				"IDA"=> array("Inferred from Direct Assay","0000314"),
				"IPI"=> array("Inferred from Physical Interaction","0000021"),
				"IMP"=> array("Inferred from Mutant Phenotype", "0000315"),
				"IGI"=> array("Inferred from Genetic Interaction","0000316"),
				"IEP"=> array("Inferred from Expression Pattern", "0000008")
				);
			//computational analysis codes
			$cac = array(
				"ISS"=> array("Inferred from Sequence or Structural Similarity","0000027"),
				"ISO"=> array("Inferred from Sequence Orthology", "0000201"),
				"ISA"=> array("Inferred from Sequence Alignment", "0000200"),
				"ISM"=> array("Inferred from Sequence Model", "0000202"),
				"IGC"=> array("Inferred from Genomic Context", "0000317"),
				"IBA"=> array("Inferred from Biological aspect of Ancestor","0000318"),
				"IBD"=> array("Inferred from Biological aspect of Desendant", "0000319"),
				"IKR"=> array("Inferred from Key Residues","0000320"),
				"IRD"=> array("Inferred from Rapid Divergence","0000321"),
				"RCA"=> array("Inferred from Reviewed Computational Analysis","0000245")
				);
				
				//author statement codes
			$asc = array(
				"TAS"=> array("Traceable Author Statement","0000304"),
				"NAS"=> array("Non-Traceable Author Statement","0000303")
			);
			//curator statement codes
			$csc = array(
				"IC"=> array("Inferred by Curator","0000001"),
				"ND"=> array("No biological Data available","0000035")
			);
			//automatically assigned codes
			$aac = array(
				"IEA"=>array("Inferred from Electronic Annotation", "0000203")
			);

			if(array_key_exists($aec, $ec)){
				return array("experimental evidence code"=>$ec[$aec]);
			}elseif(array_key_exists($aec, $cac)){
				return array("computational analysis code"=>$cac[$aec]);
			}elseif(array_key_exists($aec, $asc)){
				return array("author statement code"=>$asc[$aec]);
			}elseif(array_key_exists($aec, $csc)){
				return array("curator statement code"=>$csc[$aec]);
			}elseif(array_key_exists($aec, $aac)){
				return array("automatically assigned code"=>$aac[$aec]);
			}elseif(array_key_exists($aec, $oec)){
				return array("obsolete evidence code"=>$oec[$aec]);
			}else{
				return null;
			}
		} else {
			return null;
		}
	}

	function getGeneSynonyms($aSynLine){
		$a = explode("|", $aSynLine);
		return $a;
	}

	function parseDate($str){
		$year = substr($str, 0, 4);
		$month = substr($str, 4, 2);
		$day = substr($str, 6, 2);
		return "$year-$month-$day";
	}

}

$start = microtime(true);

set_error_handler('error_handler');
$parser = new GOAParser($argv);
$parser->Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"

?>
