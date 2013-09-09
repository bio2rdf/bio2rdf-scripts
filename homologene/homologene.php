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

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class HomologeneParser extends Bio2RDFizer{
	
	private $version = 2.0;

	function __construct($argv){
		parent::__construct($argv, "homologene");
		parent::addParameter('files',true,'all','all','files to process');
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
				parent::setParameterValue('download',true);
		}
		//download
		if($this->GetParameterValue('download') == true){
			$rfile = $rdir.$file;
			echo "downloading $file ... ";
			file_put_contents($lfile,file_get_contents($rfile));
		}

		$ofile = $odir.$file.'.nt'; $gz=false;
		if(strstr(parent::getParameterValue('output_format'), "gz")) {
			$ofile .= '.gz';
			$gz = true;
		}
		parent::setReadFile($lfile);
		parent::setWriteFile($ofile, $gz);
		echo "processing $file... ";
		$this->process();	
		echo "done!".PHP_EOL;
		parent::getWriteFile()->close();

		// generate the dataset release file
		echo "generating dataset release file... ";
		$desc = parent::getBio2RDFDatasetDescription(
			$this->getPrefix(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/homologene/homologene.php", 
			$this->getBio2RDFDownloadURL($this->getNamespace()),
			"http://www.genenames.org",
			array("use"),
			"http://www.genenames.org/about/overview",
			parent::getParameterValue('download_url'),
			parent::getDatasetVersion()
		);
		parent::setWriteFile($odir.$this->getBio2RDFReleaseFile($this->GetNamespace()));
		parent::getWriteFile()->write($desc);
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;
	}//run
	
	function process(){		
		while($aLine = $this->GetReadFile()->Read(200000)){
			$parsed_line = $this->parse_homologene_tab_line($aLine);
			$hid = "homologene:".$parsed_line["hid"];
			$hid_res = $this->getNamespace().$hid;
			$hid_label = "homologene id";
			$hid_label_class = "homologene group for ".$hid_res;

			parent::AddRDF(
				parent::describeIndividual($hid_res, $hid_label, $this->getVoc()."Homologene-Group").
				parent::describeClass($this->getVoc()."Homologene-Group", $hid_label_class )
			);

			$geneid = "geneid:".$parsed_line["geneid"];
			$taxid = "taxon:".$parsed_line["taxid"];
			$gi = "gi:".$parsed_line["gi"];
			$genesymbol = str_replace("\\", "", $parsed_line["genesymbol"]);
			$refseq = "refseq:".$parsed_line["refseq"];

			parent::AddRDF(
				parent::triplify($hid_res, $this->getVoc()."x-taxid", "$taxid").
				parent::describeProperty($this->getVoc()."x-taxid", "Link to NCBI taxonomy")
			);
			parent::AddRDF(
				parent::triplify($hid_res, $this->getVoc()."x-ncbigene", "$geneid").
				parent::describeProperty($this->getVoc()."x-ncbigene", "Link to NCBI GeneId")
			);
			parent::AddRDF(
				parent::triplifyString($hid_res, $this->getVoc()."gene_symbol",  utf8_encode(htmlspecialchars($genesymbol))).
				parent::describeProperty($this->getVoc()."gene_symbol", "The gene symbol used")
			);
			parent::AddRDF(
				parent::triplify($hid_res, $this->getVoc()."x-gi", "$gi").
				parent::describeProperty($this->getVoc()."x-gi", "Link to NCBI GI")
			);
			parent::AddRDF(
				parent::triplify($hid_res, $this->getVoc()."x-refseq", "$refseq").
				parent::describeProperty($this->getVoc()."x-refseq", "Link to NCBI Refseq")
			);	
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

?>
