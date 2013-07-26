<?php
/*
Copyright (C) 2013 Jose Cruz-Toledo, Alison Callahan

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
 * An RDF generator for Homologene (http://www.genenames.org/)
 * @version 2.0
 * @author Alison Callahan
 * @author Jose Cruz-Toledo
*/

require(__DIR__.'/../../php-lib/bio2rdfapi.php');

class HomologeneParser extends Bio2RDFizer{
	
	private $version = 2.0;

	function __construct($argv){
		parent::__construct($argv, "homologene");
		parent::addParameter('files', true, 'homologene.data', 'homologene.data', 'The filename of the complete Homologene dataset');
		parent::addParameter('download_url', false, null,'ftp://ftp.ncbi.nih.gov/pub/HomoloGene/current/' );
		parent::initialize();
	}

 	function Run(){
 		$file = "homologene.data";
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
		
		$lfile = $ldir.$file;
		
		if(!file_exists($lfile) && $this->GetParameterValue('download') == false) {
				trigger_error($file." not found. Will attempt to download.", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
		}

		//download
		if($this->GetParameterValue('download') == true){
			$rfile = $rdir.$file;
			echo "downloading $file ... ";
			file_put_contents($lfile,file_get_contents($rfile));
		}

		$ofile = $odir.$file.'.nt'; $gz=false;
		if($this->GetParameterValue('graph_uri')) {$ofile = $odir.$file.'.nq';}
		if($this->GetParameterValue('gzip')) {
			$ofile .= '.gz';
			$gz = true;
		}
			
		$this->SetReadFile($lfile);
		$this->SetWriteFile($ofile, $gz);

		echo "processing $file... ";
		$this->process();	
		echo "done!".PHP_EOL;
		$this->GetWriteFile()->Close();

		// generate the dataset release file
		echo "generating dataset release file... ";
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/homologene/homologene.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()),
			"http://www.genenames.org",
			array("use"),
			"http://www.genenames.org/about/overview",
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		echo "done!".PHP_EOL;

		return TRUE;
	}//run
	
	function process(){		
		while($aLine = $this->GetReadFile()->Read(4096)){
			$parsed_line = $this->parse_homologene_tab_line($aLine);
			$hid = "homologene:".$parsed_line["hid"];
			$geneid = "geneid:".$parsed_line["geneid"];
			$taxid = "taxon:".$parsed_line["taxid"];
			$gi = "gi:".$parsed_line["gi"];
			$genesymbol = str_replace("\\", "", $parsed_line["genesymbol"]);
			$refseq = "refseq:".$parsed_line["refseq"];
			$this->AddRDF($this->QQuad($hid, "homologene_vocabulary:has_taxid",  $taxid));
			$this->AddRDF($this->QQuad($hid, "rdf:type", "homologene_vocabulary:HomoloGene_Group"));
			$this->AddRDF($this->QQuadL($hid, "rdfs:label", "HomoloGene Group $hid [".$hid."]"));
			$this->AddRDF($this->QQuad($hid, "homologene_vocabulary:has_gene", $geneid));
			$this->AddRDF($this->QQuadL($hid, "homologene_vocabulary:has_gene_symbol", $genesymbol));
			$this->AddRDF($this->QQuad($hid, "homologene_vocabulary:has_gi", $gi));
			$this->AddRDF($this->QQuad($hid, "homologene_vocabulary:has_refseq", $refseq));
			
			$this->WriteRDFBufferToWriteFile();
		}

	}

	function parse_homologene_tab_line($aLine){
		$retrunMe = array();
		$r = explode("\t", $aLine);
		$returnMe["hid"] = trim($r[0]);
		$returnMe["taxid"] = trim($r[1]);
		$returnMe["geneid"] = trim($r[2]);
		$returnMe["genesymbol"] = trim($r[3]);
		$returnMe["gi"] = trim($r[4]);
		$returnMe["refseq"] = trim($r[5]);
		return $returnMe;
	}
}

$start = microtime(true);

$parser = new HomologeneParser($argv);
$parser->Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"
?>
