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
 * Affymetrix RDFizer
 * @version 1.0 
 * @author Michel Dumontier
 * @description http://www.affymetrix.com/support/technical/manual/taf_manual.affx
*/
class AffymetrixParser extends Bio2RDFizer 
{	
	function __construct($argv) {
		parent::__construct($argv,"affymetrix");
		parent::addParameter('files',true,null,'all','');
		parent::addParameter('version',false,null,'33','to set another version to parse from');
		parent::addParameter('download_url',false,null,'http://www.affymetrix.com/support/technical/annotationfilesmain.affx','');
		parent::initialize();
	}
	
	function Run()
	{	
		// directory shortcuts
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		
		// get the listings page
		$url = trim(parent::getParameterValue('download_url'));
		$listing_file = $ldir."probeset_list.html";
		if(!file_exists($listing_file) || parent::getParameterValue("download") == "true") {
			echo "Downloading $listing_file".PHP_EOL;
			Utils::DownloadSingle ($url, $listing_file);
		}
		$listings = file_get_contents($listing_file);
		
		// make a list of the csv.zip files
		preg_match_all("/\"([^\"]+)\.csv\.zip\"/",$listings,$m);
		if(count($m[1]) == 0) {
			trigger_error("could not find any .csv.zip files in $url");
			exit;
		}
		if(parent::getParameterValue("files") == 'all') {
			$myfiles = $m[1];
		} else {
			$a = explode(",",parent::getParameterValue("files"));
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
		if(!isset($myfiles)) exit; // nothing to do
		$dataset_description = '';
		
		// iterate over the files
		foreach($myfiles AS $rfile) {
			$base_file = substr($rfile,strrpos($rfile,"/")+1);
			$base_url = substr($rfile,0, strrpos($rfile,"/"));
			
			// get and set the dataset version
			if(parent::getDatasetVersion() == null) {
				preg_match("/\.na([0-9]{2})\.annot/",$base_file,$m);
				if(isset($m[1])) {
					$this->setDatasetVersion($m[1]);
				}
			}
			if(parent::getDatasetVersion() != parent::getParameterValue('version')) {
				$base_file = str_replace(
					"na".parent::getDatasetVersion(),
					"na".parent::getParameterValue('version'),
					$base_file);
			}
			
			$csv_file = $base_file.".csv";
			$zip_file = $csv_file.".zip";

			$lfile = $ldir.$zip_file;
			if(!file_exists($lfile)) {
				echo "skipping: $lfile does not exist".PHP_EOL;
				continue;
			}
			echo "processing $lfile".PHP_EOL;
			
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

			parent::setReadFile($lfile);
			parent::getReadFile()->setFilePointer($fp);

			// set the write file
			$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
			$outfile = 'affymetrix-'.$base_file.".".parent::getParameterValue('output_format');	
			
			$this->setWriteFile($odir.$outfile, $gz);
			$this->parse($base_file);		
			parent::getWriteFile()->close();
			parent::getReadFile()->close();
			parent::clear();
			
			// dataset description
			$source_file = (new DataResource($this))
			->setURI($rfile)
			->setTitle("Affymetrix Probeset : $base_file")
			->setRetrievedDate( date ("Y-m-d\TH:i:sP", filemtime($lfile)))
			->setFormat("text/tab-separated-value")
			->setFormat("application/zip")	
			->setPublisher("http://affymetrix.com")
			->setHomepage("http://www.affymetrix.com/support/technical/annotationfilesmain.affx")
			->setRights("use")
			->setRights("no-commercial")
			->setRights("registration-required")
			->setLicense("http://www.affymetrix.com/about_affymetrix/legal/index.affx")
			->setDataset("http://identifiers.org/affy.probeset/");
			
			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TH:i:sP");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$outfile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/affymetrix/affymetrix.php")
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
			
			$dataset_description .= $source_file->toRDF().$output_file->toRDF();
		}
		// write the dataset description
		$this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
		$this->getWriteFile()->write($dataset_description);
		$this->getWriteFile()->close();
		
		return true;
	}
	
	function Parse($file)
	{	
		parent::getReadFile()->read(); // skip the first comment line
		$line = 1;
		$first = true;
		while($l = parent::getReadFile()->read(500000)) {			
			if($l[0] == "#") {
				// dataset attributes
				$a = explode('=',trim($l));
				$r = $this->getVoc().substr($a[0],2);
				if(isset($a[1])) {
					$v = $a[1];
					if($r == "affymetrix_vocabulary:genome-version-create_date") {
						$x = explode("-",$a[1]);
						if($x[2] == "00") $x[2] = "01";
						$v = implode("-",$x);
					}		

					parent::addRDF( 
						parent::triplifyString( parent::getDatasetURI(), $r, $v).
						parent::describe($r,"$r")
					);
				}
				continue;
			}
			if($first == true) {			
				$first = false;
				// header
				$header = explode(",",str_replace('"','',trim($l)));
//				print_r($header);exit;
				$n = count($header);
				if($n != 41) {
					trigger_error("Expecting 41 columns, found $n in header on line $line!",E_USER_ERROR);
					exit;
				}
				continue;
			}
			$a = explode('","',substr($l,1,-2));
			$n = count($a);
			if($n != 41) {
				trigger_error("Expecting 41 columns, found $n on line $line!", E_USER_ERROR);
				exit;
			}
			parent::writeRDFBufferToWriteFile();
			
			$id = $a[0];
			$qname = "affymetrix:$id";
			$label = "probeset $a[0] on GeneChip $a[1] ($a[2])";
			parent::addRDF( 
				parent::describeIndividual($qname,$label,$this->getVoc()."Probeset").
				parent::describeClass($this->getVoc()."Probeset","Affymetrix probeset")
			);
			trigger_error($id,E_USER_NOTICE);
			
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
						$s = $this->getRegistry()->getPreferredPrefix($r);
						if($s == "ec") {
							$e = explode(":",$d[0]);
							$d[0] = $e[1]; 
						}
						$this->addRDF(
							parent::triplify($qname,$this->getVoc()."x-$s", "$s:".$d[0]).
							parent::describeProperty($this->getVoc()."x-$s","a relation to $s")
						);
					}
				} else {
					// we handle manually
					unset($rel);
					$label = $header[$k];
					
					switch ($label) {		
						case 'GeneChip Array':
							$array_id = parent::getRes().str_replace(" ","-",$v);
							parent::addRDF(
								parent::triplify($qname, $this->getVoc()."genechip-array", $array_id).
								parent::describeIndividual($array_id,"Affymetrix $v GeneChip array",$this->getVoc()."Genechip-Array").
								parent::describeClass($this->getVoc()."Genechip-Array","Affymetrix GeneChip array")
							);
							break;
						case 'Gene Ontology Biological Process':
							if(!isset($rel)) {$rel = 'go-process'; $prefix = "go";}
						case 'Gene Ontology Cellular Component':
							if(!isset($rel)) {$rel = 'go-location'; $prefix = "go";}
						case 'Gene Ontology Molecular Function':
							if(!isset($rel)) {$rel = 'go-function'; $prefix = "go";}
							$b = explode(" /// ",$v);
							foreach($b AS $c) {
								$d = explode(" // ",$c);
								parent::addRDF(
									$this->triplify($qname,$this->getVoc().$rel, "$prefix:".$d[0]).
									$this->describeProperty($this->getVoc().$rel,"$rel"));								
							}
							break;
							
						case 'Transcript Assignments':
							$b = explode(" /// ",$v);
							foreach($b AS $c) {
								$d = explode(" // ",$c);
								$id = $d[0];
								$prefix = $d[2];
								if($prefix == '---' || $id == '---') continue;
								else if($prefix == 'gb' || $prefix == 'gb_htc') $prefix = 'genbank';
								else if($prefix == 'ncbibacterial') $prefix = 'gi';
								else if($prefix == 'ncbi_bacterial') $prefix = 'gi';
								else if($prefix == 'ens') $prefix = 'ensembl';
								else if($prefix == 'ncbi_mito' || $prefix == 'ncbi_organelle' || $prefix == 'organelle') $prefix = 'refseq';
								else if($prefix == 'affx' || $prefix == 'unknown' || $prefix == "prop") $prefix = 'affymetrix';
								else if($prefix == 'tigr_2004_08') $prefix = 'tigr';
								else if($prefix == 'tigr-plantta') $prefix = 'genbank';
								else if($prefix == 'newrs.gi') $prefix = 'gi';
								else if($prefix == 'newRS.gi') $prefix = 'gi';
								else if($prefix == 'primate_viral') $prefix = 'genbank';
								else if($prefix == 'jgi-bacterial') $prefix = 'ncbigene';
								else if($prefix == 'tb') $prefix = 'tuberculist';
								else if($prefix == 'pa') $prefix = 'pseudomonas';
								else if($prefix == 'gi|53267') {$prefix = 'gi';$id='53267';}
								else if($prefix == 'broad-tcup') {
									$e = explode("-",$id);
									$id = $e[0];
								}
								else if($prefix == 'organelle') {
									$e = explode("-",$id);
									$prefix = 'genbank';
									$id = $e[0];
								}
								parent::addRDF(
									parent::triplify($qname,$this->getVoc()."transcript-assignment", "$prefix:$id").
									parent::describeProperty($this->getVoc()."transcript-assignment","transcript assignment"));
							}
							break;							
					
					
						case 'Annotation Transcript Cluster':
/*
							$id = substr($v,0,strpos($v,"("));
								

							$rel = str_replace(" ","-",strtolower($label));
							$this->AddRDF($this->triplify($qname,parent::getVoc()."$rel", "refseq:$id"));
*/
							break;
						
							
						case 'Annotation Date':
							// Jun 9, 2011
							$rel = "annotation-date";
							preg_match("/^([A-Za-z]+) ([0-9]+), ([0-9]{4})$/",$v,$m);
							if(count($m) == 4) {
								array_shift($m);
								list($m,$day,$year) = $m;
								$month = $this->getMonth($m);
								if(!$day || $day == "0") $day = "01";
								$date = $year."-".$month."-".str_pad($day,2,"0",STR_PAD_LEFT)."T00:00:00Z";
								parent::addRDF(
									parent::triplifyString($qname,$this->getVoc().$rel,$date,"xsd:dateTime").
									parent::describeProperty($this->getVoc().$rel,"$rel"));
									
							} else {
								trigger_error("could not match date from $v",E_USER_ERROR);
							}
							break;

						case 'Species Scientific Name':
							break;
							
						case 'Transcript ID(Array Design)':
							if(!isset($rel)) $rel = 'transcript';
							
						
						case 'Sequence type';							
						default:
							if(!isset($rel)) $rel = str_replace(" ","-",strtolower($label));
							$b = explode(" /// ",$v);
							foreach($b AS $c) {
								parent::addRDF(
									parent::triplifyString($qname,$this->getVoc().$rel,stripslashes($c)).
									parent::describeProperty($this->getVoc().$rel,"$rel"));
							}
							break;
					
					} //  switch
				} // else
			}
			$this->WriteRDFBufferToWriteFile();
		}
	}
	
	function getMonth($m)
	{
		$months = array(
			"Jan" => "01",
			"Feb" => "02",
			"Mar" => "03",
			"Apr" => "04",
			"May" => "05",
			"Jun" => "06",
			"Jul" => "07",
			"Aug" => "08",
			"Sep" => "09",
			"Oct" => "10",
			"Nov" => "11",
			"Dec" => "12"
		);
		if(!isset($months[$m])) {
			trigger_error("Unable to get month: $m",E_USER_ERROR);
			return "00";
		}
		return $months[$m];
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

?>


