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


		// iterate over the files

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
			
			// set the dataset version
			if(parent::getDatasetVersion() == null) {
				preg_match("/\.na([0-9]{2})\.annot/",$base_file,$m);
				if(isset($m[1])) {
					$this->setDatasetVersion($m[1]);
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
			$this->setWriteFile($odir.$outfile, $gz);
			
			// parse the file
			$this->parse();		
			
			parent::getWriteFile()->close();
			parent::getReadFile()->close();
			
			$bio2rdf_download_files[] = $this->getBio2RDFDownloadURL($this->getNamespace()).$outfile; 
			
			parent::clear();
		}
		

		// generate the release file
		$desc = $this->getBio2RDFDatasetDescription(
			$this->getNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/affymetrix/affymetrix.php", 
			$bio2rdf_download_files,
			"dsfsdfs",
			"http://affymetrix.com/",
			array("use-share-modify","no-commercial"),
			null, // license
			parent::getParameterValue('download_url'),
			parent::getDatasetVersion()
		);
		$this->setWriteFile($odir.$this->getBio2RDFReleaseFile($this->getNamespace()));
		$this->getWriteFile()->write($desc);
		$this->getWriteFile()->close();
		
		return true;
	}	
	
	function Parse()
	{	
		parent::getReadFile()->read(); // skip the first comment line
		$line = 1;
		$first = true;
		while($l = parent::getReadFile()->read(500000)) {			
			if($l[0] == "#") {
				// dataset attributes
				$a = explode('=',trim($l));
				$r = $this->getVoc().substr($a[0],2);
				parent::addRDF( 
					parent::triplifyString( parent::getDatasetURI(), $r, $a[1]).
					parent::describe($r,"$r")
				);
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
			parent::writeRDFBufferToWriteFile();
			
			$id = $a[0];
			$qname = "affymetrix:$id";
			$label = "probeset $a[0] on GeneChip $a[1] ($a[2])";
			parent::addRDF( 
				parent::describeClass($qname,$label,null,null,"en",$this->getVoc()."Probeset").
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
							$array_id = "affymetrix_resource:".str_replace(" ","-",$v);
							parent::addRDF(
								parent::triplify($qname, $this->getVoc()."genechip-array", $array_id).
								parent::describeClass($array_id,"Affymetrix GeneChip array",null,null,"en",$this->getVoc()."Genechip-Array"));
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
								if($prefix == 'gb' || $prefix == 'gb_htc') $prefix = 'genbank';
								if($prefix == 'ncbibacterial') $prefix = 'gi';
								if($prefix == 'ens') $prefix = 'ensembl';
								if($prefix == 'ncbi_mito' || $prefix == 'ncbi_organelle') $prefix = 'refseq';
								if($prefix == 'affx' || $prefix == 'unknown') $prefix = 'affymetrix';
								
								parent::addRDF(
									parent::triplify($qname,$this->getVoc()."transcript-assignment", "$prefix:$id").
									parent::describeProperty($this->getVoc()."transcript-assignment","transcript assignment"));
							}
							break;							
					
					
						case 'Annotation Transcript Cluster':
/*
							$id = substr($v,0,strpos($v,"("));
								

							$rel = str_replace(" ","-",strtolower($label));
							$this->AddRDF($this->QQuad($qname,"affymetrix_vocabulary:$rel", "refseq:$id"));
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


