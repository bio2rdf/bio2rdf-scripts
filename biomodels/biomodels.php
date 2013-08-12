<?php
/**
Copyright (C) 2013 Michel Dumontier, Alison Callahan

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
 * @version 2.0 
 * @author Michel Dumontier
 * @author Alison Callahan
 * @description http://www.ebi.ac.uk/biomodels-main/
*/

require_once(__DIR__.'/../../php-lib/biopax2bio2rdf.php');

class BiomodelsParser extends Bio2RDFizer 
{	
	
	function __construct($argv) {
		parent::__construct($argv, "biomodels");
		
		// set and print application parameters
		parent::addParameter('files',true,null,'all|curated|biomodel#|start#-end#','entries to process: comma-separated list or hyphen-separated range');
		parent::addParameter('download_url',false,null,'http://www.ebi.ac.uk/biomodels/models-main/publ/');
		parent::initialize();
	}
	
	function Run()
	{	
		// directory shortcuts
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		
		// get the work specified
		$list = trim(parent::getParameterValue('files'));
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
					$entries[] =  "BIOMD".str_pad($i,10,"0",STR_PAD_LEFT);
				}
			} else {
				// for comma separated list
				$b = explode(",",$this->GetParameterValue('files'));
				foreach($b AS $e) {
					$entries[] = "BIOMD".str_pad($e,10,"0",STR_PAD_LEFT);
				}
			}		
		}

		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());
		
		// set the write file
		$suffix = parent::getParameterValue('output_format');
		$outfile = 'biomodels'.'.'.$suffix;
		$gz=false;

		if(strstr(parent::getParameterValue('output_format'), "gz")) {
			$gz = true;
		}
		
		$dataset_description = '';
		parent::setWriteFile($odir.$outfile, $gz);
		
		// iterate over the entries
		$i = 0;
		$total = count($entries);
		foreach($entries AS $id) {
			echo "processing ".(++$i)." of $total - biomodel# ".$id;
			$download_file = $ldir.$id.".owl.gz";
			$url =  parent::getParameterValue('download_url')."$id/$id-biopax3.owl";
			// download if the file doesn't exist or we are told to
			if(!file_exists($download_file) || $this->GetParameterValue('download') == 'true') {
				// download
				echo " - downloading";
				$buf = file_get_contents($url);
				if(strlen($buf) != 0)  {
					file_put_contents("compress.zlib://".$download_file, $buf);
					// usleep(500000); // limit of 4 requests per second
				}
			}
			
			// load entry, parse and write to file
			echo " - parsing... ";
			// $this->SetReadFile($download_file,true);
			$buf = file_get_contents("compress.zlib://".$download_file);

			$converter = new BioPAX2Bio2RDF($this);
			$converter->SetBuffer($buf)
				->SetBioPAXVersion(3)
				->SetBaseNamespace("http://identifiers.org/biomodels.db/$id/")
				->SetBio2RDFNamespace("http://bio2rdf.org/biomodels:".$id."_")
				->SetDatasetURI($this->GetDatasetURI());

			$rdf = $converter->Parse();
			parent::addRDF($rdf);
			parent::writeRDFBufferToWriteFile();
			parent::getWriteFile()->Close();
		
			echo "done!".PHP_EOL;

			//generate dataset description
			echo "Generating dataset description for BioModel # $id... ";
			$source_file = (new DataResource($this))
			->setURI($url)
			->setTitle("EBI BioModels Database - BioModel # $id")
			->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($download_file)))
			->setFormat("rdf/xml")
			->setPublisher("http://www.ebi.ac.uk/")
			->setHomepage("http://www.ebi.ac.uk/biomodels-main/")
			->setRights("use-share-modify")
			->setLicense("http://www.ebi.ac.uk/biomodels-main/termsofuse")
			->setDataset("http://identifiers.org/biomodels.db/");

			$dataset_description .= $source_file->toRDF();
			echo "done!".PHP_EOL;

		}//foreach

		echo "Generating dataset description for Bio2RDF BioModels... ";

		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");
		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
			->setSource($source_file->getURI())
			->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/biomodels/biomodels.php")
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
		
		$dataset_description .= $output_file->toRDF();

		//write dataset description to file
		parent::setGraphURI($graph_uri);
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;
	}	
}

?>
