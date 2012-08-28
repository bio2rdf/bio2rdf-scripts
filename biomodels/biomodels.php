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

require('../../php-lib/biopax2bio2rdf.php');

/**
 * BioModels RDFizer
 * @version 1.0
 * @author Michel Dumontier
 * @description http://www.ebi.ac.uk/biomodels-main/
*/
class BiomodelsParser extends RDFFactory 
{	
	private $version = null;
	
	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("biomodels");
		
		// set and print application parameters
		$this->AddParameter('files',true,null,'all|curated|biomodel#|start#-end#','entries to process: comma-separated list or hyphen-separated range');
		$this->AddParameter('indir',false,null,'/data/download/'.$this->GetNamespace().'/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/'.$this->GetNamespace().'/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://www.ebi.ac.uk/biomodels/models-main/publ/');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));		
		
		return TRUE;
	}
	
	function Run()
	{	
		// directory shortcuts
		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		
		// get the work specified
		$list = trim($this->GetParameterValue('files'));
		if($list == 'all') {
			// call the getAllModelsId webservice
			$file = $ldir."all_models.json";
			if(!file_exists($file)) {
				try {  
					$x = @new SoapClient("http://www.ebi.ac.uk/biomodels-main/services/BioModelsWebServices?wsdl");  
				} catch (Exception $e) {  
					echo $e->getMessage(); 
				} 
				$entries = $x->getAllModelsId();
				file_put_contents($file,json_encode($entries));
			} else {
				$entries = json_decode(file_get_contents($file));
			}
		} elseif($list == 'curated') {
			// call the getAllCuratedModelsId webservice
			$file = $ldir."curated_models.json";
			if(!file_exists($file)) {
				try {  
					$x = @new SoapClient("http://www.ebi.ac.uk/biomodels-main/services/BioModelsWebServices?wsdl");  
				} catch (Exception $e) {  
					echo $e->getMessage(); 
				} 
				$entries = $x->getAllCuratedModelsId();
				file_put_contents($file,json_encode($entries));
			} else {
				$entries = json_decode(file_get_contents($file));
			}			
		} else {
			// check if a hyphenated list was provided
			if(($pos = strpos($list,"-")) !== FALSE) {
				$start_range = substr($list,0,$pos);
				$end_range = substr($list,$pos+1);
				for($i=$start_range;$i<=$end_range;$i++) {
					$entries[] = $this->GeneratedBIOMD($i);
				}
			} else {
				// for comma separated list
				$b = explode(",",$this->GetParameterValue('files'));
				foreach($b AS $e) {
					$entries[] = $this->GeneratedBIOMD($e);
				}
			}		
		}
		
		// set the write file
		$outfile = 'biomodels.nt'; $gz=false;
		if($this->GetParameterValue('gzip')) {
			$outfile .= '.gz';
			$gz = true;
		}
		$bio2rdf_download_files[] = $this->GetBio2RDFDownloadURL($this->GetNamespace()).$outfile; 
		
		$this->SetWriteFile($odir.$outfile, $gz);
		
		// iterate over the entries
		$i = 0;
		$total = count($entries);
		foreach($entries AS $id) {
			echo "processing ".(++$i)." of $total - biomodel# ".$id;
			$download_file = $ldir.$id.".owl.gz";
			// download if the file doesn't exist or we are told to
			if(!file_exists($download_file) || $this->GetParameterValue('download') == 'true') {
				// download
				echo " - downloading";
				$url = $this->GetParameterValue('download_url')."$id/$id-biopax3.owl";
				$buf = file_get_contents($url);
				if(strlen($buf) != 0)  {
					file_put_contents("compress.zlib://".$download_file, $buf);
					// usleep(500000); // limit of 4 requests per second
				}
			}
			
			// load entry, parse and write to file
			echo " - parsing";
			// $this->SetReadFile($download_file,true);
			$buf = file_get_contents("compress.zlib://".$download_file);
			
			$converter = new BioPAX2Bio2RDF();
			$converter->SetBuffer($buf)
				->SetBioPAXVersion(3)
				->SetBaseNamespace("http://identifiers.org/biomodels.db/$id/")
				->SetBio2RDFNamespace("http://bio2rdf.org/biomodels:");
			$this->AddRDF($converter->Parse());
			$this->WriteRDFBufferToWriteFile();
		
			echo PHP_EOL;
		}
		$this->GetWriteFile()->Close();

		// generate the release file
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/biomodels/biomodels.php", 
			$bio2rdf_download_files,
			"http://www.ebi.ac.uk/biomodels-main/",
			array("use-share-modify"),
			null, // license
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		
		return true;
	}	
	
	function GeneratedBIOMD($id)
	{
		$n = strlen($id);
		$pad = '';
		for($i=0;$i<(10-$n);$i++) {$pad .= '0';}
		return 'BIOMD'.$pad.$id;					
	}
}

set_error_handler('error_handler');
$parser = new BiomodelsParser($argv);
$parser->Run();
