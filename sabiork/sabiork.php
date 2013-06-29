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
 * SABIORK RDFizer
 * @version 1.0
 * @author Michel Dumontier
 * @description http://sabio.villa-bosch.de/layouts/content/docuRESTfulWeb/manual.gsp
*/
class SABIORKParser extends RDFFactory 
{		
	private $version = null;
	
	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("sabiork");
		
		// set and print application parameters
		$this->AddParameter('files',true,null,'all','entries to process: comma-separated list or hyphen-separated range');
		$this->AddParameter('indir',false,null,'/data/download/'.$this->GetNamespace().'/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/'.$this->GetNamespace().'/','directory to place rdfized files');
		$this->AddParameter('download',false,'true|false','false','Force data download');
		$this->AddParameter('download_url',false,null,'http://sabiork.h-its.org/sabioRestWebServices/','Download API');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
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
		$idir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$files = $this->GetParameterValue('files');
	
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
		if(!file_exists($reaction_list_file) || $this->GetParameterValue('download') == 'true') {
			$xml = file_get_contents($getReactionIds_url);
			if(FALSE === $reaction_ids) {
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
		foreach($xml->SABIOReactionID AS $rid) {
			if(isset($myfiles)) {
				if(!in_array($rid,$myfiles)) continue;
			}
			$i++;
			echo "$i / $total : reaction $rid";
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
			$p = new BioPAX2Bio2RDF();
			$p->SetBuffer($buf)
				->SetBioPAXVersion(3)
				->SetBaseNamespace("http://sabio.h-its.org/biopax#")
				->SetBio2RDFNamespace("http://bio2rdf.org/sabiork:")
				->SetDatasetURI($this->GetDatasetURI());
			$rdf = $p->Parse();
			
			$ofile = "sabiork_$rid.nt";	$gz = false;
			if($this->GetParameterValue("graph_uri")) {$ofile = "sabiork_$rid.nq";}
			if($this->GetParameterValue("gzip")) {
				$gz = true;
				$ofile .= ".gz";
			}
			$this->SetWriteFile($odir.$ofile,$gz);
			$this->GetWriteFile()->Write($rdf);
			$this->GetWriteFile()->Close();
			
			$bio2rdf_download_files[] = $this->GetBio2RDFDownloadURL($this->GetNamespace()).$ofile; 
			echo PHP_EOL;
		}
			

		// generate the release file
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/sabiork/sabiork.php", 
			$bio2rdf_download_files,
			"sabiork.h-its.org",
			array("use-share-modify","no-commercial"),
			null, // license
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
	} // run
}
$start = microtime(true);

set_error_handler('error_handler');
$parser = new SABIORKParser($argv);
$parser->Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"
?>

