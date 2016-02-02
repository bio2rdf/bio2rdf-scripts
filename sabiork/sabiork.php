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

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../../php-lib/biopax2bio2rdf.php');

/**
 * SABIORK RDFizer
 * @version 2.0
 * @author Michel Dumontier
 * @author Alison Callahan
 * @description http://sabio.villa-bosch.de/layouts/content/docuRESTfulWeb/manual.gsp
*/
class SABIORKParser extends Bio2RDFizer 
{		
	private $version = null;
	
	function __construct($argv) {
		parent::__construct($argv, "sabiork");
		parent::addParameter('files',true,null,'all','entries to process: comma-separated list or hyphen-separated range');
		parent::addParameter('download_url',false,null,'http://sabiork.h-its.org/sabioRestWebServices/','Download API');
		parent::initialize();
	}
	
	function Run()
	{
		$idir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$files = parent::getParameterValue('files');
	
		// set the work
		if($files != 'all') {
			// check if comma-separated, or hyphen-range
			$list = explode(",",$files);
			if(count($list) == 1) {
				// try hyphen separated
				$range = explode("-",$files);
				if(count($range) == 2) {
					for($i=$range[0];$i<=$range[1];$i++) {
						$myfiles[] = $i;
					}
				} else {
					// must a single entry
					$myfiles[] = $files;					
				}
			} else {
				$myfiles = $list;
			}
		}

		$rest_uri = 'http://sabiork.h-its.org/sabioRestWebServices/';
		$getReactionIds_url = $rest_uri."suggestions/SABIOReactionIDs";
		
		$reaction_list_file = $idir."reactions.xml";
		if(!file_exists($reaction_list_file) || parent::getParameterValue('download') == 'true') {
			$xml = file_get_contents($getReactionIds_url);
			if(FALSE === $reaction_list_file) {
				exit;
			} 
			$f = new FileFactory($reaction_list_file);
			$f->Write($xml);
			$f->Close();
		}
		
		$xml = simplexml_load_file($reaction_list_file);
		$total = count($xml->SABIOReactionID);
		if(isset($myfiles)) $total = count($myfiles);
		$i = 0;
		parent::setCheckpoint('dataset');

		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());
		$suffix = parent::getParameterValue('output_format');
		$ofile = "sabiork.".$suffix;
		$gz = strstr(parent::getParameterValue('output_format'), "gz")?true:false;
		parent::setWriteFile($odir.$ofile,$gz);

		foreach($xml->SABIOReactionID AS $rid) {
			parent::setCheckpoint('file');
			if(isset($myfiles)) {
				if(!in_array($rid,$myfiles)) continue;
			}
			$i++;
			echo "$i / $total : reaction $rid".PHP_EOL;
			$reaction_file = $idir."reaction_".$rid.".owl.gz";
			if(!file_exists($reaction_file) || $this->GetParameterValue('download') == 'true') {
				$url = $rest_uri.'searchKineticLaws/biopax?q=SabioReactionID:'.$rid;
				$data = file_get_contents($url);
				if($data === FALSE) {
					continue;
				}
				$f = new FileFactory($reaction_file, true);
				$f->Write($data);
				$f->Close();
			}

			$buf = file_get_contents("compress.zlib://".$reaction_file);

			// send for parsing
			$p = new BioPAX2Bio2RDF($this);
			$p->SetBuffer($buf)
				->SetBioPAXVersion(3)
				->SetBaseNamespace("http://sabio.h-its.org/biopax#")
				->SetBio2RDFNamespace("http://bio2rdf.org/sabiork:")
				->SetDatasetURI($this->GetDatasetURI());
			$rdf = $p->Parse();

			parent::getWriteFile()->Write($rdf);
		}
		parent::getWriteFile()->Close();

		//generate dataset description
		echo "Generating dataset description... ";
		$source_file = (new DataResource($this))
			->setURI("http://sabiork.h-its.org/sabioRestWebServices/searchKineticLaws/biopax")
			->setTitle("SABIO-RK Biochemical Reaction Kinetics Database")
			->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($odir.$ofile)))
			->setFormat("text/xml")
			->setPublisher("http://sabio.villa-bosch.de/")
			->setHomepage("http://sabio.villa-bosch.de/")
			->setRights("use-share-modify")
			->setRights("no-commercial")
			->setLicense("http://sabio.villa-bosch.de/layouts/content/termscondition.gsp")
			->setDataset("http://identifiers.org/sabiork.reaction/");

		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");
		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
			->setSource($source_file->getURI())
			->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/sabiork/sabiork.php")
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

		//write dataset description to file
		parent::setGraphURI($graph_uri);
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;
	} // run
}

?>

