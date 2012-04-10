<?php
###############################################################################
#Copyright (C) 2012 Jose Cruz-Toledo
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

$base_path = "/media/twotb/bio2rdf/data/ipi/";
$out_path = "/tmp/";

$xref_species_files = array("ipi.ARATH.xrefs.gz","ipi.CHICK.xrefs.gz","ipi.BOVIN.xrefs.gz", "ipi.DANRE.xrefs.gz", "ipi.HUMAN.xrefs.gz","ipi.MOUSE.xrefs.gz","ipi.RAT.xrefs.gz");
$gi2ipi = "gi2ipi.xrefs.gz";

$i =0;
foreach($xref_species_files as $aF){
	parse_ipi_OSCODE_xref_file($base_path.$aF, $out_path.substr($aF, 0,-3).".nt");
	$i++;
}

parse_ipi_gi2ipi("/media/twotb/bio2rdf/data/ipi/gi2ipi.xrefs.gz", "/tmp/caca");


function parse_ipi_OSCODE_xref_file($inpath,$outpath){
	$infh = gzopen($inpath,'r') or die("Cannot open $inpath !\n");
	$f = strrpos($inpath, "/");
	$fn = substr($inpath, $f+1);
	$outfh = fopen($outpath, 'w');
	if($infh){
		while(!gzeof($infh)){
			$aLine = gzgets($infh, 4096);
			$tLine = explode("\t", $aLine);
			//see http://www.ebi.ac.uk/IPI/xrefs.html
			//get the Database from which master entry of this IPI entry has been taken. 
			$master_db =null; //key is code, value is bio2rdf namespace
			if($tLine[0] == "SP"|| $tLine[0] == "TR"||$tLine[0] =="ENSEMBL"||$tLine[0] =="ENSEMBL_HAVANA"||$tLine[0] =="REFSEQ_STATUS"||$tLine[0] =="VEGA"||$tLine[0] =="TAIR"||$tLine[0]=="HINV"){
				if($tLine[0] == "SP"){
					$master_db["SP"] = "swissprot";
				}
				if($tLine[0] == "TR"){
					$master_db["TR"] = "uniprot";
				}
				if($tLine[0] == "ENSEMBL"){
					$master_db["ENSEMBL"] = "ensembl";
				}
				if($tLine[0] == "ENSEMBL_HAVANA"){
					$master_db["ENSEMBL_HAVANA"] = "ensembl";
				}
				if($tLine[0] == "REFSEQ_STATUS"){
					$master_db["REFSEQ_STATUS"] = "refseq";
				}
				if($tLine[0] == "VEGA"){
					$master_db["VEGA"] = "vega";
				}
				if($tLine[0] == "TAIR"){
					$master_db["TAIR"] = "tair";
				}
				if($tLine[0] == "HINV"){
					$master_db["HINV"] = "hinv";
				}
			}
			
			
			$ipi_id = null;
			if(count(isset($tLine[2]))){
				@$ipi_id = $tLine[2];
			}

			$refseq_ids = null;
			if(count(isset($tLine[6]))){
				@$refseq_ids = getRefseqIds($tLine[6]);
			}
			
			$tair_ids =null;
			if(count(isset($tLine[7]))){
				@$tair_ids = getTairIds($tLine[7]);
			}
			
			$hinv_ids = null;
			if(count(isset($tLine[8]))){
				@$hinv_ids = getHinvIds($tLine[8]);
			}
			
			$genbank_ids = null;
			if(count(isset($tLine[9]))){
				@$genbank_ids = getGenbankIds($tLine[9]);
			}
			
			$hgnc_ids =null;
			$mgi_ids = null;
			$rat_ids =null;
			$danre_ids =null;
			$arath_ids =null;
			if(@count($tLine[10])){
				if($fn =="ipi.MOUSE.xrefs.gz"){
					$mgi_ids = getMGIIds($tLine[10]);
				}elseif($fn =="ipi.RAT.xrefs.gz"){
					$rat_ids = getRATIds($tLine[10]);
				}elseif($fn =="ipi.DANRE.xrefs.gz"){
					$danre_ids = getDANREIds($tLine[10]);
				}elseif($fn =="ipi.ARATH.xrefs.gz"){
					$arath_ids = getARATHIds($tLine[10]);
				}else{
					$hgnc_ids = getHgncIds($tLine[10]);	
				}
			}
	
			$gene_ids =null;
			if(@count($tLine[11])){
				$gene_ids = getGeneIds($tLine[11]);
			}
			
			$uniparc_ids =null;
			if(@count($tLine[12])){
				$uniparc_ids = getUniparcIds($tLine[12]);
			}
			
			$unigene_ids =null;
			if(@count($tLine[13])){
				$unigene_ids = getUnigeneIds($tLine[13]);
			}

			$entryURI = "http://bio2rdf.org/ipi:$ipi_id";
			$buf = "";
			if(count($refseq_ids)){
				foreach($refseq_ids as $r){
					if($r != ""){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_refseq_id> <http://bio2rdf.org/refseq:".$r."> .\n";
					}
				}
			}
			
			if(count($tair_ids)){
				foreach($tair_ids as $t){
					if($t != ""){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_tair_id> <http://bio2rdf.org/tair:".$t."> .\n";
					}
				}
			}
			if(count($hinv_ids)){
				foreach($hinv_ids as $t){
					if($t != ""){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_hinv_id> <http://bio2rdf.org/hinv:".$t."> .\n";
					}
				}
			}
			
			if(count($genbank_ids)){
				foreach($genbank_ids as $t){
					if($t != ""){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_genbank_id> <http://bio2rdf.org/genbank:".$t."> .\n";
					}
				}
			}
			
			if(count($hgnc_ids)){
				foreach($hgnc_ids as $t){
					if($t != ""){
						//check if there is a comma in $t
						if(!strstr($t,",")){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_hgnc_id> <http://bio2rdf.org/hgnc:".$t."> .\n";
						}else{
							$x = explode(",",$t);
							foreach($x as $y){
								$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_hgnc_id> <http://bio2rdf.org/hgnc:".$y."> .\n";
							}
						}
					}
				}
			}
			
			if(count($mgi_ids)){
				foreach($mgi_ids as $t){
					if($t != ""){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_mgi_id> <http://bio2rdf.org/mgi:".$t."> .\n";
					}
				}
			}
			
			if(count($rat_ids)){
				foreach($rat_ids as $t){
					if($t != ""){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_rgd_id> <http://bio2rdf.org/rgd:".$t."> .\n";
					}
				}
			}
			
			if(count($danre_ids)){
				foreach($danre_ids as $t){
					if($t != ""){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_zfin_id> <http://bio2rdf.org/zfin:".$t."> .\n";
					}
				}
			}
			
			if(count($arath_ids)){
				foreach($arath_ids as $t){
					if($t != ""){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_arath_id> <http://bio2rdf.org/tair:".$t."> .\n";
					}
				}
			}
			
			
			fwrite($outfh, $buf);
		
			
		}
	}
	if(!feof($infh)){
		echo "Error: unexpected gzgets() fail!\n";
	}
	gzclose($infh);
	fclose($outfh);
	
}

function getARATHIds($s){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		$c = explode(",",$b);
		foreach($c as $d){
			if(strlen($d) >1){
				$returnMe[]  = $d;
			}
		}
	}
	return $returnMe;
}
function getDANREIds($s){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		$c = explode(",",$b);
		foreach($c as $d){
			if(strlen($d) >1){
				$returnMe[]  = $d;
			}
		}
	}
	return $returnMe;
}
function getRATIds($s){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		$c = explode(",",$b);
		foreach($c as $d){
			if(strlen($d) >1){
				$returnMe[]  = $d;
			}
		}
	}
	return $returnMe;
}

function getMGIIds($s){
$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		$c = explode(",",$b);
		foreach($c as $d){
			if(strlen($d) >1){
				$returnMe[]  = $d;
			}
		}
	}
	return $returnMe;
}

function getRefseqIds($somerefseqs){
	$returnMe = array();
	if(count($somerefseqs)){
		//split by semicolon
		$a = explode(";", $somerefseqs);
		foreach($a as $b){
			$c = explode(":", $b);
			if(isset($c[1])){
				$returnMe[] = $c[1];
			}
		}
		return $returnMe;
	}
	return null;
	
}

function getHinvIds($s){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		$returnMe[] = $b;
	}
	return $returnMe;
}

function getUniparcIds($s){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		$returnMe[] = $b;
	}
	return $returnMe;
}

function getGeneIds($s){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		$returnMe[] = $b;
	}
	return $returnMe;
}

function getUnigeneIds($s){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		if(strlen($b)>1){
			$returnMe[] = $b;
		}
	}
	return $returnMe;
}

function getHgncIds($s){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		$returnMe[] = $b;
	}
	return $returnMe;
}

function getGenbankIds($s){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $s);
	foreach($a as $b){
		$returnMe[] = $b;
	}
	return $returnMe;
}
function getTairIds($someTairIds){
	$returnMe = array();
	//split by semicolon
	$a = explode(";", $someTairIds);
	foreach($a as $b){
		$returnMe[] = $b;
	}
	return $returnMe;
}
function parse_ipi_gi2ipi($inpath, $outpath){
	$infh = gzopen($inpath,'r') or die("Cannot open $inpath !\n");
	$outfh = fopen($outpath, 'w');
	
	if($infh){
		while(!gzeof($infh)){
			$aLine = gzgets($infh, 4096);
			//if the line starts with an integer
			preg_match("/(\d+)\t(\w+)/", $aLine, $matches);
			if(count($matches)){
				$giURI = "http://bio2rdf.org/gi:".$matches[1];
				$ipiURI = "http://bio2rdf.org/ipi:".$matches[2];
				$buf = "<$giURI> <http://bio2rdf.org/ipi_vocabulary:has_gi> <$ipiURI> .\n";
				//write buffer to file
				fwrite($outfh, $buf);
			}
		}
	}
	if(!feof($infh)){
		echo "Error: unexpected gzgets() fail!\n";
	}
	gzclose($infh);
	fclose($outfh);
}


?>
