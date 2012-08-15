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
 * BioModels RDFizer
 * @version 1.0
 * @author Michel Dumontier
 * @description http://www.ebi.ac.uk/biomodels-main/
*/
require('../../php-lib/rdfapi.php');
class BiomodelsParser extends RDFFactory 
{		
	function __construct($argv) {
		parent::__construct();
		// set and print application parameters
		$this->AddParameter('files',true,null,'all|curated|biomodel#|start#-end#','entries to process: comma-separated list or hyphen-separated range');
		$this->AddParameter('indir',false,null,'/data/download/biomodels/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/biomodels/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://www.ebi.ac.uk/biomodels/models-main/publ/');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		
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
			try {  
				$x = @new SoapClient("http://www.ebi.ac.uk/biomodels-main/services/BioModelsWebServices?wsdl");  
			} catch (Exception $e) {  
				echo $e->getMessage(); 
			} 
			$entries = $x->getAllModelsId();
		} elseif($list == 'curated') {
			// call the getAllCuratedModelsId webservice
			try {  
				$x = @new SoapClient("http://www.ebi.ac.uk/biomodels-main/services/BioModelsWebServices?wsdl");  
			} catch (Exception $e) {  
				echo $e->getMessage(); 
			} 
			$entries = $x->getAllCuratedModelsId();
			
		} else {
			// check if a hyphenated list was provided
			if(($pos = strpos($list,"-")) !== FALSE) {
				$start_range = substr($list,0,$pos);
				$end_range = substr($list,$pos+1);
				for($i=$start_range;$i<=$end_range;$i++) {
					$entries[] = $i;					
				}
			} else {
				// for comma separated list
				$b = explode(",",$this->GetParameterValue('files'));
				foreach($b AS $e) {
					$entries[] = $e;
				}
			}		
		}
		
		// set the write file
		$outfile = $odir.'biomodels.ttl'; $gz=false;
		if($this->GetParameterValue('gzip')) {
			$outfile .= '.gz';
			$gz = true;
		}
		$this->SetWriteFile($outfile, $gz);
		
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
			$this->SetReadFile($download_file,true);
			$this->Parse($id);
			$this->GetReadFile()->Close();
			
			$this->WriteRDFBufferToWriteFile();
		
			echo PHP_EOL;
		}
		$this->GetWriteFile()->Close();
		return true;
	}
	
	function Parse($id)
	{
		$buf = '';
		while($l = $this->GetReadFile()->Read()) {
			$buf .= $l;
		}
	
		// read into rdf model
		require_once('../../arc2/ARC2.php');
		$parser = ARC2::getRDFXMLParser();
		$parser->parse('http://bio2rdf.org/',$buf);
		
		$index = $parser->getSimpleIndex(0);
		$base_uri = 'http://bio2rdf.org/biomodels:'.$id.'_';
		
		// print_r($index);
		foreach($index AS $s => $p_list) {
			$s_uri = str_replace(
				array('http://bio2rdf.org/'),
				array($base_uri),
				$s);
			
			// make the original uri the same as the bio2rdf uri
			// $this->AddRDF($this->Quad($s_uri,$nso->GetFQURI("owl:sameAs"),$s));
			
			foreach($p_list AS $p => $o_list) {
				$p_uri = $p;
				
				foreach($o_list AS $o) {
					if($o['type'] == 'uri') {
						$o_uri = str_replace(
							array("http://bio2rdf.org/"),
							array($base_uri),
							$o['value']);						
						$this->AddRDF($this->Quad($s_uri,$p_uri,$o_uri));
					} else {
						// literal
						$literal = $this->SafeLiteral($o['value']);
						$datatype = null;
						if(isset($o['datatype'])) {
							if(strstr($o['datatype'],"http://")) {
								$datatype = $o['datatype'];
							} else {
								$datatype = $nso->GetFQURI($o['datatype']);
							}
						}
						$this->AddRDF($this->QuadL($s_uri,$p_uri,$literal,null,$datatype));
					}
				}
			}
		
		}
		
		
	}
}

set_error_handler('error_handler');
$parser = new BiomodelsParser($argv);
$parser->Run();

