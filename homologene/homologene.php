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
 * @author Michel Dumontier
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

		$lfile = $ldir.$file;
		if(!file_exists($lfile)) {
			trigger_error($file." not found. Will attempt to download.", E_USER_NOTICE);
			parent::setParameterValue('download',true);
		}
		//download
		$rfile = $rdir.$file;
		if($this->GetParameterValue('download') == true){
			echo "downloading $file ... ";
			file_put_contents($lfile,file_get_contents($rfile));
		}

		$ofile = $file.'.'.parent::getParameterValue('output_format'); 
		$gz= strstr(parent::getParameterValue('output_format'), "gz")?$gz=true:$gz=false;

		parent::setReadFile($lfile);
		parent::setWriteFile($odir.$ofile, $gz);
		echo "processing $file... ";
		$this->process();	
		echo "done!".PHP_EOL;
		parent::getWriteFile()->close();

		// generate the dataset release file
                      $source_file = (new DataResource($this))
                                ->setURI($rfile)
                                ->setTitle("NCBI Homologene")
                                ->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
                                ->setFormat("text/tab-separated-value")
                                ->setPublisher("http://www.ncbi.nlm.nih.gov")
                                ->setHomepage("http://www.ncbi.nlm.nih.gov/homologene")
                                ->setRights("use-share-modify")
                                ->setLicense("http://www.ncbi.nlm.nih.gov/About/disclaimer.html")
                                ->setDataset("http://identifiers.org/homologene/");

                        $prefix = parent::getPrefix();
                        $bVersion = parent::getParameterValue('bio2rdf_release');
                        $date = date ("Y-m-d\TG:i:s\Z");
                        $output_file = (new DataResource($this))
                                ->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
                                ->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
                                ->setSource($source_file->getURI())
                                ->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/homologene/homologene.php")
                                ->setCreateDate($date)
                                ->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
                                ->setPublisher("http://bio2rdf.org")
                                ->setRights("use-share-modify")
                                ->setRights("by-attribution")
                                ->setRights("restricted-by-source-license")
                                ->setLicense("http://creativecommons.org/licenses/by/3.0/")
                                ->setDataset(parent::getDatasetURI());

                        if($gz) $output_file->setFormat("application/gzip");
                        if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
                        else $output_file->setFormat("application/n-quads");

                $dataset_description = $source_file->toRDF().$output_file->toRDF();
                echo "Generating dataset description... ";
                parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
                parent::getWriteFile()->write($dataset_description);
                parent::getWriteFile()->close();

		echo "done!".PHP_EOL;
	}//run
	
	function process(){		
		while($aLine = $this->GetReadFile()->Read(200000)){
			$parsed_line = $this->parse_homologene_tab_line($aLine);
			$hid = "homologene:".$parsed_line["hid"];
			$hid_label = "homologene group ".$parsed_line['hid'];

			parent::AddRDF(
				parent::describeIndividual($hid, $hid_label, $this->getVoc()."Homologene-Group").
				parent::describeClass($this->getVoc()."Homologene-Group", "Homologene Group" )
			);

			$geneid = "ncbigene:".$parsed_line["geneid"];
			$taxid = "taxid:".$parsed_line["taxid"];
			$gi = "gi:".$parsed_line["gi"];
			$genesymbol = str_replace("\\", "", $parsed_line["genesymbol"]);
			$refseq = "refseq:".$parsed_line["refseq"];

			parent::AddRDF(
				parent::triplify($hid, $this->getVoc()."x-taxid", $taxid).
				parent::describeProperty($this->getVoc()."x-taxid", "Link to NCBI taxonomy")
			);
			parent::AddRDF(
				parent::triplify($hid, $this->getVoc()."x-ncbigene", $geneid).
				parent::describeProperty($this->getVoc()."x-ncbigene", "Link to NCBI GeneId")
			);
			parent::AddRDF(
				parent::triplifyString($hid, $this->getVoc()."gene-symbol",  utf8_encode(htmlspecialchars($genesymbol))).
				parent::describeProperty($this->getVoc()."gene-symbol", "Link to gene symbol")
			);
			parent::AddRDF(
				parent::triplify($hid, $this->getVoc()."x-gi", $gi).
				parent::describeProperty($this->getVoc()."x-gi", "Link to NCBI GI")
			);
			parent::AddRDF(
				parent::triplify($hid, $this->getVoc()."x-refseq", $refseq).
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
