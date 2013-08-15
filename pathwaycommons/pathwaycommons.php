<?php
/**
Copyright (C) 2012 Michel Dumontier, Alison Callahan

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

require_once(__DIR__.'/../../php-lib/biopax2bio2rdf.php');
/**
 * Pathwaycommons RDFizer 
 * @version 2.0
 * @author Michel Dumontier
 * @author Alison Callahan
 * @description http://www.pathwaycommons.org
*/
class PathwaycommonsParser extends Bio2RDFizer 
{		
	function __construct($argv) {
		parent::__construct($argv, "pathwaycommons");
		parent::addParameter('files',true,'all|homo-sapiens|hprd|humancyc|nci-nature|panther-pathway|phosphositeplus|reactome','all','biopax OWL files to process');
		parent::addParameter('download_url',false,null,'http://www.pathwaycommons.org/pc2/downloads/');
		parent::initialize();
	}
	
	function Run()
	{			
		// get the work
		if($this->GetParameterValue('files') == 'all') {
			$sources = explode("|", parent::getParameterList('files'));
			array_shift($sources);
		} else {
			// comma separated list
			$sources = explode(",", parent::getParameterValue('files'));
		}					

		$download_files = array(
			"homo-sapiens" => "Pathway%20Commons%202%20homo%20sapiens.BIOPAX.owl.gz",
			"hprd" => "Pathway%20Commons%202%20HPRD.BIOPAX.owl.gz",
			"humancyc" => "Pathway%20Commons%202%20HumanCyc.BIOPAX.owl.gz",
			"nci-nature" => "Pathway%20Commons%202%20NCI_Nature.BIOPAX.owl.gz",
			"panther-pathway" => "Pathway%20Commons%202%20PANTHER%20Pathway.BIOPAX.owl.gz",
			"phosphositeplus" => "Pathway%20Commons%202%20PhosphoSitePlus.BIOPAX.owl.gz", 
			"reactome" => "Pathway%20Commons%202%20Reactome.BIOPAX.owl.gz",
		);

		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		$dataset_description = '';

		// iterate over the requested data
		foreach($sources AS $source) {
			echo "processing $source... ";

			$ldir = parent::getParameterValue('indir');
			$odir = parent::getParameterValue('outdir');
			$rdir = parent::getParameterValue('download_url');
			
			// set the remote and input files
			$file  = $source.".owl";
			$zfile = $source.".owl.gz";
			$rfile = $rdir.$download_files[$source];
			$lfile = $ldir.$zfile;

			// download if if the file doesn't exist locally or we are told to
			if(!file_exists($lfile) || $this->GetParameterValue('download') == 'true') {
				// download 
				echo "downloading... ";
				file_put_contents($lfile, file_get_contents($rfile));
			}
			
			// extract the file out of the ziparchive
			// and load into a buffer
			echo 'extracting... ';

			if (($fpin = gzopen($lfile, "r")) === FALSE) {
				trigger_error("Unable to open $lfile", E_USER_ERROR);
				exit;
			}

			$data = '';
			while (!gzeof($fpin)) {
			   $buffer = gzgets($fpin, 4096);
			   $data .= $buffer;
			}
			gzclose($fpin);

			// set the output file
			$suffix = parent::getParameterValue('output_format');
			$outfile = $source.'.'.$suffix;

			$gz = false;
			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}

			parent::setWriteFile($odir.$outfile, $gz);
			
			// send for parsing
			$p = new BioPAX2Bio2RDF($this);	
			$p->SetBuffer($data)
				->SetBioPAXVersion(3)
				->SetBaseNamespace("http://purl.org/pc2/3/")
				->SetBio2RDFNamespace("http://bio2rdf.org/pathwaycommons:")
				->SetDatasetURI(parent::getDatasetURI());
			$rdf = $p->Parse();
			parent::addRDF($rdf);
			
			// write to output
			parent::writeRDFBufferToWriteFile();
			parent::getWriteFile()->Close();

			echo "done!".PHP_EOL;

			//generate dataset description
			echo "Generating dataset description for $zfile... ";
			$source_file = (new DataResource($this))
			->setURI($rfile)
			->setTitle("Pathway Commons")
			->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
			->setFormat("rdf/xml")
			->setPublisher("http://www.pathwaycommons.org/")
			->setHomepage("http://www.pathwaycommons.org/")
			->setRights("use")
			->setRights("restricted-by-source-license")
			->setLicense("http://www.pathwaycommons.org/pc2/home.html#data_sources")
			->setDataset("http://identifiers.org/pathwaycommons/");
			
			$dataset_description .= $source_file->toRDF();
			echo "done!".PHP_EOL;
		}

		echo "Generating dataset description for Bio2RDF Pathways Commons dataset... ";

		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");
		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
			->setSource($source_file->getURI())
			->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/pathwaycommons/pathwaycommons.php")
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
