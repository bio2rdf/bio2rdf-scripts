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
require('../../php-lib/rdfapi.php');
/**
 * Affymetrix RDFizer
 * @version 1.0 
 * @author Michel Dumontier
 * @description http://www.affymetrix.com/support/technical/manual/taf_manual.affx
*/
class AffymetrixParser extends RDFFactory 
{	
	private $version = null;
	
	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("affymetrix");
		
		// set and print application parameters
		$this->AddParameter('files',true,null,'all','');
		$this->AddParameter('indir',false,null,'/data/download/'.$this->GetNamespace().'/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/'.$this->GetNamespace().'/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://www.affymetrix.com/support/technical/annotationfilesmain.affx','');

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
		
		// get the listings page
		$url = trim($this->GetParameterValue('download_url'));
		$listing_file = $ldir."probeset_list.html";
		if(!file_exists($listing_file) || $this->GetParameterValue("download") == "true") {
			echo "Downloading $listing_file".PHP_EOL;
			Utils::DownloadSingle ($url, $listing_file);
		}
		$listings = file_get_contents($listing_file);
		
		// get the csv.zip files
		preg_match_all("/\"([^\"]+)\.csv\.zip\"/",$listings,$m);
		if(count($m[1]) == 0) {
			trigger_error("could not find any .csv.zip files in $url");
			exit;
		}
		if($this->GetParameterValue("files") == 'all') {
			$myfiles = $m[1];
		} else {
			$a = explode(",",$this->GetParameterValue("files"));
			foreach($a AS $f) {
				$found  = false;
				foreach($m[1] AS $n) {	
					if(strstr($n,$f)) {
						$found = true;
						$myfiles[] = $n;
						break;
					}
				}
				if($found === false) {
					echo "cannot find $f in list".PHP_EOL;
				}
			}
		}
		if(!isset($myfiles)) exit;

		// print_r($myfiles);
		foreach($myfiles AS $rfile) {
			// download
			$base_file = substr($rfile,strrpos($rfile,"/")+1);
			$base_url = substr($rfile,0, strrpos($rfile,"/"));
			echo "processing $base_file, from $base_url".PHP_EOL;
			$csv_file = $base_file.".csv";
			$zip_file = $csv_file.".zip";
		
			$lfile = $ldir.$zip_file;

			if(!file_exists($lfile) || $this->GetParameterValue('download') == true) { 
				$rfile = $url.$zip_file;
				trigger_error("Downloading $zip_file from $rfile", E_USER_NOTICE);
				if(Utils::Download($base_url,array($zip_file),$ldir) === FALSE) {
					trigger_error("Unable to download $file. skipping", E_USER_WARNING);
					continue;
				}
			}
			
			// open the zip file
			$zin = new ZipArchive();
			if ($zin->open($lfile) === FALSE) {
				trigger_error("Unable to open $lfile");
				exit;
			}

			if(($fp = $zin->getStream($csv_file)) === FALSE) {
				trigger_error("Unable to get $csv_file in ziparchive $lfile");
				return FALSE;
			}
			$this->SetReadFile($lfile);
			$this->GetReadFile()->SetFilePointer($fp);

			// set the write file
			$outfile = $base_file.'.nt'; $gz=false;
			if($this->GetParameterValue('graph_uri')) {$outfile = $base_file.'.nq';}
			if($this->GetParameterValue('gzip')) {
				$outfile .= '.gz';
				$gz = true;
			}
			$bio2rdf_download_files[] = $this->GetBio2RDFDownloadURL($this->GetNamespace()).$outfile; 
			
			$this->SetWriteFile($odir.$outfile, $gz);
			$this->Parse();		
			
			$this->GetWriteFile()->Close();
			$this->GetReadFile()->Close();
		}
		

		// generate the release file
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/affymetrix/affymetrix.php", 
			$bio2rdf_download_files,
			"dsfsdfs",
			"http://affymetrix.com/",
			array("use-share-modify","no-commercial"),
			null, // license
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		
		return true;
	}	
	
	function Parse()
	{
		$resource = $this->GetNS()->getNSURI("affymetrix_resource");
		$vocab = $this->GetNS()->getNSURI("affymetrix_vocabulary");
		
		$this->GetReadFile()->Read(); // skip the first comment line
		$line = 1;
		$first = true;
		while($l = $this->GetReadFile()->Read(500000)) {
			$line++;
			if($l[0] == "#") {
				// dataset attributes
				$a = explode('=',trim($l));
				$this->AddRDF($this->QuadL($this->GetDatasetURI(),$vocab.substr($a[0],2),$a[1]));
				continue;
			}
			if($first == true) {			
				$first = false;
				// header
				$header = explode(",",str_replace('"','',trim($l)));
				$n = count($header);
				if($n != 41) {
					trigger_error("Expecting 41 columns, found $n in header on line $line!");
					exit;
				}
				continue;
			}
			$a = explode('","',substr($l,1,-2));
			$n = count($a);
			if($n != 41) {
				trigger_error("Expecting 41 columns, found $n on line $line!");
				exit;
			}
			$this->WriteRDFBufferToWriteFile();
			
			$id = $a[0];
			$qname = "affymetrix:$id";
			$this->AddRDF($this->QQuad($qname,"rdf:type","affymetrix_vocabulary:Probeset"));
			$this->AddRDF($this->QQuadL($qname,"rdfs:label","probeset $a[0] on GeneChip $a[1] ($a[2]) [$qname]"));
			$this->AddRDF($this->QQuad($qname,"void:inDataset",$this->GetDatasetURI()));
			
			// now process the entries
			foreach($a AS $k => $v) {
				if(trim($v) == '---') continue;
				
				// multi-valued entries are separated by ////
				$b = explode(" /// ",$v);
				$r = $this->Map($k);
				if(isset($r)) {
					foreach($b AS $c) {
						$d = explode(" // ",$c);
						if($r == 'symbol') $d[0] = str_replace(" ","-",$d[0]);
						$this->AddRDF($this->QQuad($qname,"affymetrix_vocabulary:x-$r", "$r:".$d[0]));
					}
				} else {
					// we handle manually
					unset($rel);
					$label = $header[$k];
					
					switch ($label) {		
						case 'GeneChip Array':
							$this->AddRDF($this->QQuad($qname,"affymetrix_vocabulary:genechip-array", "affymetrix_resource:".str_replace(" ","-",$v)));
							break;
						case 'Gene Ontology Biological Process':
							if(!isset($rel)) {$rel = "process"; $prefix = "go";}
						case 'Gene Ontology Cellular Component':
							if(!isset($rel)) {$rel = 'location'; $prefix = "go";}
						case 'Gene Ontology Molecular Function':
							if(!isset($rel)) {$rel = 'function'; $prefix = "go";}
							$b = explode(" /// ",$v);
							foreach($b AS $c) {
								$d = explode(" // ",$c);
								$this->AddRDF($this->QQuad($qname,"affymetrix_vocabulary:".$rel, "$prefix:".$d[0]));								
							}
							break;
							
						case 'Transcript Assignments':
							$b = explode(" /// ",$v);
							foreach($b AS $c) {
								$d = explode(" // ",$c);
								$id = $d[0];
								$prefix = $d[2];
								if($prefix == '---' || $id == '---') continue;
								if($prefix == 'gb') $prefix = 'genbank';
								if($prefix == 'ens') $prefix = 'ensembl';
								if($prefix == 'affx' || $prefix == 'unknown') $prefix = 'affymetrix';
								
								$this->AddRDF($this->QQuad($qname,"affymetrix_vocabulary:transcript-assignment", $this->GetNS()->MapQName("$prefix:$id")));
							}
							break;
						
					
					
						case 'Annotation Transcript Cluster':
/*
							$id = substr($v,0,strpos($v,"("));
								

							$rel = str_replace(" ","-",strtolower($label));
							$this->AddRDF($this->QQuad($qname,"affymetrix_vocabulary:$rel", "refseq:$id"));
*/
							break;
						
							
					
						case 'Transcript ID(Array Design)':
							if(!isset($rel)) $rel = 'transcript';
						// date
						case 'Annotation Date':
							// Jun 9, 2011
							// should reformat to xsd:date
						// literals
						case 'Species Scientific Name':
						
						// types
						case 'Sequence type';							
						default:
							if(!isset($rel)) $rel = str_replace(" ","-",strtolower($label));
							$b = explode(" /// ",$v);
							foreach($b AS $c) {
								$this->AddRDF($this->QQuadL($qname,"affymetrix_vocabulary:".$rel,$this->SafeLiteral(stripslashes($c))));
							}
							
						// multi-valued 
					
					}
				}
				
			}			
		}
	}

	function Map($k)
	{
		$list = array(
			9 => 'unigene',
			10 => 'unigene',
			14 => 'symbol',
			17 => 'ensembl',
			18 => 'geneid',
			19 => 'uniprot',
			20 => 'ec',
			21 => 'omim',
			22 => 'refseq',
			23 => 'refseq',
			24 => 'flybase',
			25 => 'agi',
			26 => 'wormbase',
			27 => 'mgi',
			28 => 'rgd',
			29 => 'sgd',
			34 => 'interpro',	
		);
		if(isset($list[$k])) return $list[$k];
		return null;
	}
}

$start = microtime(true);

set_error_handler('error_handler');
$parser = new AffymetrixParser($argv);
$parser->Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"

?>


