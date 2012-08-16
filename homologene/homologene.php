<?php
//require (dirname(__FILE__).'/../common/php/lipphp.php')
require('../../php-lib/rdfapi.php');
###############################################################################
#Copyright (C) 2011 Jose Cruz-Toledo, Alison Callahan
#
#Permission is hereby granted, free of charge, to any person obtaining a copy of
#this software and associated documentation files (the "Software"), to deal in
#the Software without restriction, including without limitation the rights to
#use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
#of the Software, and to permit persons to whom the Software is furnished to do
#so, subject to the following conditions:
#
#The above copyright notice and this permission notice shall be included in all
#copies or substantial portions of the Software.
#
#THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
#SOFTWARE.
###############################################################################

class HomologeneParser extends RDFFactory{
	private $ns = null;
	private $named_entries = array();

	private $bio2rdf_base = "http://bio2rdf.org/";
	private $homogene_vocab = "homologene_vocabulary:";
	private $homogene_resource = "homologene_resource:";

	function __construct($argv){
		parent::__construct();

		//set and print the application options
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('indir',false,null,'/media/twotb/bio2rdf/data/homolgene','directory to download files');
		$this->AddParameter('outdir',false,null,'/media/twotb/bio2rdf/n3/gene/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('force',false,'true|false','true','remove old files and copy over');
		$this->AddParameter('download_url',false,null,'ftp://ftp.ncbi.nih.gov/pub/HomoloGene/current/homologene.data');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}

		return TRUE;
	}

 function Run(){
		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$infile = $ldir."/homologene.data";
		$outfile = $odir."/homologene.nt";
		
		//download necessary files
		$download = $this->GetParameterValue('download');
		if($download == 'true'){
			$file = $this->GetParameterValue('download_url');
			$l = $ldir.'homologene.data';
			echo "Downloading ".$file." to ".$infile;
			copy($file,$infile);
			echo "-> done <-\n";
		}

		if($this->GetParameterValue('gzip')){
			$outfile.='.gz';
		}
		
		// check there is no outfile that exists
		if(!file_exists($outfile)){
			$outfile = gzopen($outfile,"a") or die("Could not open file $outfile");
		}else{
			$force = $this->GetParameterValue("force");
			if($force == true){
				echo "Removing existing ".$outfile."\n";
				unlink($outfile);
				$outfile = gzopen($outfile,"a") or die("Could not open file $outfile");
			}else{
				echo "file $outfile already exists.\n Please remove the file and try again\n";
				exit;
			}
		}

		if(file_exists($infile)){
			$infile = fopen($infile,"r") or die("Could not open file $infile");
		}else{
			echo "the infile does not exist. You may need to download it first.";
			exit;
		}
		//create necessary directories if they don't exist			
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
	
		$this->parse_homologene_tab_file($infile,$outfile);		
		return TRUE;
	}//run
	
	function parse_homologene_tab_file($infile,$outfile){
		$homologene = "http://bio2rdf.org/homologene";
		$taxid = "http://bio2rdf.org/taxon:";
		$geneid = "http://bio2rdf.org/geneid:";
		$gi = "http://bio2rdf.org/gi:";
		$refseq = "http://bio2rdf.org/refseq:";
		$label = "http://www.w3.org/2000/01/rdf-schema#label";
		$type = "http://www.w3.org/1999/02/22-rdf-syntax-ns#type";
		
		
		//$infh = fopen($inpath, 'r') or die("Cannot open $inpath!\n");
		//$outfh = fopen($outpath, 'w') or die("Cannot open $outpath\n");
		
		if($infile){
			while(($aLine = fgets($infile, 4096)) !== false){
				$parsed_line = $this->parse_homologene_tab_line($aLine);
				$buf = "<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_taxid> <".$taxid.$parsed_line["taxid"].">.\n";
				$buf .= "<$homologene:".$parsed_line["hid"]."> <".$type."> <".$homologene."_vocabulary:HomoloGene_Group>.\n";
				$buf .= "<$homologene:".$parsed_line["hid"]."> <".$label."> \"HomoloGene Group\".\n";
				$buf .="<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_geneid> <".$geneid.$parsed_line["geneid"].">.\n";
				$buf .="<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_geneSymbol> \"".str_replace("\\","", $parsed_line["genesymbol"])."\".\n";
				$buf .="<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_gi> <".$gi.$parsed_line["gi"].">.\n";
				$buf .="<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_refseq> <".$refseq.$parsed_line["refseq"].">.\n";
				gzwrite($outfile, utf8_encode($buf));
				
			}
			if(!feof($infile)){
				echo "Error : unexpected fgets() fail\n";
			}
		}
		fclose($infile);
		gzclose($outfile);
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

$parser = new HomologeneParser($argv);
$parser->Run();
?>