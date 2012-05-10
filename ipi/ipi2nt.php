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


//Specify the path where the raw IPI files are located
$input_path = "/tmp/ipi/ipi/";
//Specify the path where the N-Triples files should be created
$output_path = "/tmp/ipi/nt/";

//TThe following are the files that this parser can handle
$species_xrefs = array(
 "ipi.ARATH.xrefs.gz",
 "ipi.CHICK.xrefs.gz",
 "ipi.BOVIN.xrefs.gz", 
 "ipi.DANRE.xrefs.gz", 
 "ipi.HUMAN.xrefs.gz",
 "ipi.MOUSE.xrefs.gz",
 "ipi.RAT.xrefs.gz"
);

$gene_xrefs = array( 
 "ipi.genes.ARATH.xrefs.gz",
 "ipi.genes.BOVIN.xrefs.gz",
 "ipi.genes.CHICK.xrefs.gz",
 "ipi.genes.DANRE.xrefs.gz"
);

$gi2ipi = array("gi2ipi.xrefs.gz");
 
 
 
/******************/
/* FUNCTION CALLS */
/******************/
//iterate over the $species_xrefs array and generate the ntriple files
//for each entry

foreach($species_xrefs as $sp){
	parser_ipi_OSCODE_xref_gz_file($input_path, $sp, $output_path);
}
foreach($gene_xrefs as $sp){
	parser_ipi_gene_OSCODE_xref_gz_file($input_path,$sp, $output_path);
}
parse_gi2ipi($input_path, $gi2ipi[0], $output_path);
/*************/
/* FUNCTIONS */
/*************/

function parse_gi2ipi($anInputPath, $filename, $outputPath){
	//check if inputpath has a trailing slash
	if(strrpos($anInputPath, "/") != count($anInputPath)){
		//no trailing slash
		$anInputPath .= "/";
	}
	if(strrpos($outputPath, "/") != count($outputPath)){
		//no trailing slash
		$outputPath .= "/";
	}
	
	$out_fileName = substr($filename, 0, strrpos($filename, "."));
	$ifh = gzopen($anInputPath.$filename, 'r') or die("Could not open ".$anInputPath.$filename."\n");
	$outfh = fopen($outputPath.$out_fileName.".nt", 'w') or die ("Could not open file here!\n");
	
	if($ifh){
		while(!gzeof($ifh)){
			$aLine = gzgets($ifh, 4096);
			$tLine = explode("\t", $aLine);
			if(!startsWith($tLine[0], "#")){
				if(count($tLine) ==2){
					$entryURI = "http://bio2rdf.org/geneid:".$tLine[0];
					$buf = "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_ipi> <http://bio2rdf.org/ipi:".trim($tLine[1])."> .\n";
					fwrite($outfh, $buf);
				}
			}//if
		}//while
	}//if
	if(!feof($ifh)){
		echo "Error: unexpected gzgets() fail!\n";
	}
	gzclose($ifh);
	fclose($outfh);
	
}

function parser_ipi_gene_OSCODE_xref_gz_file($anInputPath, $anOSCODEFile, $outputPath){
	//check if inputpath has a trailing slash
	if(strrpos($anInputPath, "/") != count($anInputPath)){
		//no trailing slash
		$anInputPath .= "/";
	}
	if(strrpos($outputPath, "/") != count($outputPath)){
		//no trailing slash
		$outputPath .= "/";
	}
	
	$out_fileName = substr($anOSCODEFile, 0, strrpos($anOSCODEFile, "."));
	$ifh = gzopen($anInputPath.$anOSCODEFile, 'r') or die("Could not open ".$anInputPath.$anOSCODEFile."\n");
	$outfh = fopen($outputPath.$out_fileName.".nt", 'w') or die ("Could not open file here!\n");

	if($ifh){
		while(!gzeof($ifh)){
			$aLine = gzgets($ifh, 4096);
			$tLine = explode("\t", $aLine);
			if(!startsWith($tLine[0], "#")){			
				$chromosome = null;
				$cosmid = array();
				$start_coord = null;
				$gene_symbol = null;
				$end_coord = null;
				$strand = array();
				$gene_location = array();
				$ensembl_id = array();
				$gene_id = null;
				$ipi_ids = array();
				$uniprotkb_ids = array();
				$uniprot_tre = array();
				$ensembl_peptide_id = array();
				$refseq_ids = array();
				$tair_ids = array();
				$hinv_ids = array();
				$unigene_ids = array();
				$ccds_ids = array();
				$refseq_gis = array();
				$vega_genes = array();
				$vega_peptides = array();
				
				
				if(count(isset($tLine[0]))){
					@$chr_arr = readIdentifiers($tLine[0]);
					if($chr_arr[0] != "Un"){
						$chromosome = $chr_arr[0];
					}
				}
				
				if(count(isset($tLine[1]))){
					@$cosmid = readIdentifiers($tLine[1]);
				}
				
				if(count(isset($tLine[2]))){
					@$start_coord_t = readIdentifiers($tLine[2]);
					if(count($start_coord_t) == 1){
						$start_coord = $start_coord_t[0];
					}
				}

				if(count(isset($tLine[3]))){
					@$end_coord_t = readIdentifiers($tLine[3]);
					if(count($end_coord_t) == 1){
						$end_coord = $end_coord_t[0];
					}
				}
				if(count(isset($tLine[4]))){
					@$strand = readIdentifiers($tLine[4]);
				}
				
				if(count(isset($tLine[5]))){
					@$gene_location = readIdentifiers($tLine[5]);
				}
			
				if(count(isset($tLine[6]))){
					@$ensembl_id = readIdentifiers($tLine[6]);
				}
				if(count(isset($tLine[8]))){
					@$gene_id_t = readIdentifiers($tLine[8]);
					if (count($gene_id_t) == 2){
						$gene_id  = $gene_id_t[0];
						$gene_symbol = $gene_id_t[1];
					}
				}
				if(count(isset($tLine[9]))){
					@$ipi_ids = readIdentifiers($tLine[9]);
				}
				if(count(isset($tLine[10]))){
					@$uniprotkb_ids = readIdentifiers($tLine[10]);
				}
				if(count(isset($tLine[11]))){
					@$uniprot_tre = readIdentifiers($tLine[11]);
				}
				if(count(isset($tLine[12]))){
					@$ensembl_peptide_id = readIdentifiers($tLine[12]);
				}
				if(count(isset($tLine[13]))){
					@$refseq_ids = readIdentifiers($tLine[13]);
				}
				if(count(isset($tLine[14]))){
					@$tair_ids = readIdentifiers($tLine[14]);
				}
				if(count(isset($tLine[15]))){
					@$hinv_ids = readIdentifiers($tLine[15]);
				}
				if(count(isset($tLine[16]))){
					@$unigene_ids = readIdentifiers($tLine[16]);
				}
				if(count(isset($tLine[17]))){
					@$ccds_ids = readIdentifiers($tLine[17]);
				}
				if(count(isset($tLine[18]))){
					@$refseq_gis = readIdentifiers($tLine[18]);
				}
				if(count(isset($tLine[19]))){
					@$vega_genes = readIdentifiers($tLine[19]);
				}
				if(count(isset($tLine[20]))){
					@$refseq_ids = readIdentifiers($tLine[20]);
				}
				
				//lets make some rdf
				if(count($gene_id)){
					$entryURI = "http://bio2rdf.org/gene:".$gene_id;
					$buf = "";
					//gene symbol
					$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_gene_symbol> \"$gene_symbol\" .\n";
					//chromosome
					$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_chromosome> \"$chromosome\" .\n";
					//start coord
					$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_start_coordinate> \"$start_coord\" .\n";
					//end coord
					$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_end_coordinate> \"$end_coord\" .\n";
					//strand
					if(count($strand)){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_strand> \"".$strand[0]."\" .\n";
					}
					//ensembl id
					if(count($ensembl_id)){
						foreach ($ensembl_id as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_ensembl> <http://bio2rdf.org/ensembl:".$x."> .\n";
						}
					}
					//gene location
					if(count($gene_location)){
						foreach ($gene_location as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_gene_location> \"$x\" .\n";
						}
					}
					//ipi ids
					if(count($ipi_ids)){
						foreach ($ipi_ids as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_ipi_id> <http://bio2rdf.org/ipi:".$x."> .\n";
						}
					}
					//uniprotkb_ids
					if(count($uniprotkb_ids)){
						foreach ($uniprotkb_ids as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_uniprot_id> <http://bio2rdf.org/uniprot:".$x."> .\n";
						}
					}
					//uniprot trembl
					if(count($uniprot_tre)){
						foreach ($uniprot_tre as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_uniprot_id> <http://bio2rdf.org/uniprot:".$x."> .\n";
						}
					}
					//ensembl peptide
					if(count($ensembl_peptide_id)){
						foreach ($ensembl_peptide_id as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_ensembl> <http://bio2rdf.org/ensembl:".$x."> .\n";
						}
					}
					//refseqs
					if(count($refseq_ids)){
						foreach ($refseq_ids as $x){
							if(count($x) != 0 && $x != "\n" && $x != ""){
								$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_refseq_id> <http://bio2rdf.org/refseq:".$x."> .\n";
							}
						}
					}
					//tair
					if(count($tair_ids)){
						foreach ($tair_ids as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_tair_id> <http://bio2rdf.org/tair:".$x."> .\n";
						}
					}
					//cosmid
					if(count($cosmid)){
						foreach ($cosmid as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_cosmid>  \"$x\" .\n";
						}
					}
					//hinv ids
					if(count($hinv_ids)){
						foreach ($hinv_ids as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_hinv_id>  <http://bio2rdf.org/hinv:".$x."> .\n";
						}
					}
					//unigene ids
					if(count($unigene_ids)){
						foreach ($unigene_ids as $x){
							$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_unigene_id>  <http://bio2rdf.org/unigene:".$x."> .\n";
						}
					}
					//refseq gis
					if(count($refseq_gis)){
						foreach ($refseq_gis as $x){
							if(count($x) != 0 && $x != "\n" && $x != ""){
								$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_refseq_id> <http://bio2rdf.org/refseq:".$x."> .\n";
							}
						}
					}
					fwrite($outfh, $buf);
				
				}//if
			}//if
		}//while
	}//if
	if(!feof($ifh)){
		echo "Error: unexpected gzgets() fail!\n";
	}
	gzclose($ifh);
	fclose($outfh);
	
}

/*This function reads the $anOSCODEFile found in $anInputPath
 * and create N-Triple files which are to be stored in the
 *  directory specified by $outputPath 
 * see: http://www.ebi.ac.uk/IPI/xrefs.html
 * 
 **/ 
function parser_ipi_OSCODE_xref_gz_file($anInputPath, $anOSCODEFile, $outputPath){
	//check if inputpath has a trailing slash
	if(strrpos($anInputPath, "/") != count($anInputPath)){
		//no trailing slash
		$anInputPath .= "/";
	}
	if(strrpos($outputPath, "/") != count($outputPath)){
		//no trailing slash
		$outputPath .= "/";
	}
	$out_fileName = substr($anOSCODEFile, 0, strrpos($anOSCODEFile, "."));
	$ifh = gzopen($anInputPath.$anOSCODEFile, 'r') or die("Could not open ".$anInputPath.$anOSCODEFile."\n");
	$outfh = fopen($outputPath.$out_fileName.".nt", 'w') or die ("Could not open file here!\n");

	if($ifh){
		while(!gzeof($ifh)){
			$aLine = gzgets($ifh, 4096);
			$tLine = explode("\t", $aLine);
			//see http://www.ebi.ac.uk/IPI/xrefs.html
			//get the Database from which master entry of this IPI entry has been taken. 
			$master_db =null; //key is code, value is bio2rdf namespace
			if($tLine[0] == "SP"|| $tLine[0] == "REFSEQ_REVIEWED"|| $tLine[0] == "TR"||$tLine[0] =="ENSEMBL"||$tLine[0] =="ENSEMBL_HAVANA"||$tLine[0] =="REFSEQ_STATUS"||$tLine[0] =="VEGA"||$tLine[0] =="TAIR"||$tLine[0]=="HINV"){
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
			$sup_uniprots_sps = array();
			$uniprotkb_id = null;
			$sup_uniprots_tre = array();
			$sup_ensembl = array();
			$sup_refseq = array();
			$sup_tair = array();
			$sup_hinv = array();
			$xref_embl_genbank_ddbj = array();
			$hgnc_ids = array();
			$ncbi_ids = array();
			$uniparc_ids = array();
			$unigene_ids = array();
			$ccds_ids = array();
			$refseq_gis = array();
			$vega_ids = array();
			//UniProtKB accession number or Vega ID or Ensembl ID or RefSeq ID or TAIR Protein ID or H-InvDB ID
			if(count(isset($tLine[1]))){
				@$uniprotkb_id = getFirstId($tLine[1]);
			}
			//ipi id
			if(count(isset($tLine[2]))){
				@$ipi_id = $tLine[2];
			}
			//Supplementary UniProtKB/Swiss-Prot entries associated with this IPI entry.
			if(count(isset($tLine[3]))){
				@$sup_uniprots_sps = readIdentifiers($tLine[3]);
			}
			//Supplementary UniProtKB/TrEMBL entries associated with this IPI entry.
			if(count(isset($tLine[4]))){
				@$sup_uniprots_tre = readIdentifiers($tLine[4]);
			}
			//Supplementary Ensembl entries associated with this IPI entry. Havana curated transcripts preceeded by the key HAVANA: (e.g. HAVANA:ENSP00000237305;ENSP00000356824;).
			if(count(isset($tLine[5]))){
				@$sup_ensembl = readIdentifiers($tLine[5]);
			}
			//Supplementary list of RefSeq STATUS:ID couples (separated by a semi-colon ';') associated with this IPI entry (RefSeq entry revision status details).
			if(count(isset($tLine[6]))){
				@$sup_refseq = readIdentifiers($tLine[6]);
			}
			//Supplementary TAIR Protein entries associated with this IPI entry.
			if(count(isset($tLine[7]))){
				@$sup_tair = readIdentifiers($tLine[7]);
			}
			//Supplementary H-Inv Protein entries associated with this IPI entry.
			if(count(isset($tLine[8]))){
				@$sup_hinv = readIdentifiers($tLine[8]);
			}
			//Protein identifiers (cross reference to EMBL/Genbank/DDBJ nucleotide databases).
			if(count(isset($tLine[9]))){
				@$xref_embl_genbank_ddbj = readIdentifiers($tLine[9]);
			}
			//List of HGNC number, HGNC official gene symbol couples (separated by by a semi-colon ';') associated with this IPI entry.
			if(count(isset($tLine[10]))){
				@$hgnc_ids = readIdentifiers($tLine[10]);
			}
			////List of NCBI Entrez Gene gene number, Entrez Gene Default Gene Symbol couples (separated by a semi-colon ';') associated with this IPI entry.
			if(count(isset($tLine[11]))){
				@$ncbi_ids = readIdentifiers($tLine[11]);
			}
			//UNIPARC identifier associated with the sequence of this IPI entry.
			if(count(isset($tLine[12]))){
				@$uniparc_ids = readIdentifiers($tLine[12]);
			}	
			//UniGene identifiers associated with this IPI entry.
			if(count(isset($tLine[13]))){
				@$unigene_ids = readIdentifiers($tLine[13]);
			}
			//CCDS identifiers associated with this IPI entry.
			if(count(isset($tLine[14]))){
				@$ccds_ids = readIdentifiers($tLine[14]);
			}
			//RefSeq GI protein identifiers associated with this IPI entry.
			if(count(isset($tLine[15]))){
				@$refseq_gis = readIdentifiers($tLine[15]);
			}
			//Supplementary Vega entries associated with this IPI entry.
			if(count(isset($tLine[16]))){
				@$vega_ids = readIdentifiers($tLine[16]);
			}
			//now lets print some rdf
			$entryURI = "http://bio2rdf.org/ipi:".$ipi_id;
			$buf = "";
			if(count($sup_refseq)){
				foreach ($sup_refseq as $r){
					if($r != "" && $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_refseq_id> <http://bio2rdf.org/refseq:".$r."> .\n";
					}
				}
			}
			if($uniprotkb_id != "" && $uniprotkb_id!= "\n"&& count($uniprotkb_id) > 1 && isset($uniprotkb_id)){
				$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_uniprot_id> <http://bio2rdf.org/uniprot:".$r."> .\n";
			}
			if(count($sup_uniprots_sps)){
				foreach ($sup_uniprots_sps as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_uniprot_id> <http://bio2rdf.org/uniprot:".$r."> .\n";
					}
				}
			}
			if(count($sup_uniprots_tre)){
				foreach ($sup_uniprots_tre as $r){
					if($r != "" && $r != "\n" && count($r) > 1 && isset($r)){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_uniprot_id> <http://bio2rdf.org/uniprot:".$r."> .\n";
					}
				}
			}
			if(count($sup_ensembl)){
				foreach ($sup_ensembl as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_ensembl_id> <http://bio2rdf.org/ensembl:".$r."> .\n";
					}
				}
			}
			
			if(count($sup_tair)){
				foreach ($sup_tair as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_tair_id> <http://bio2rdf.org/tair:".$r."> .\n";
					}
				}
			}
			if(count($sup_hinv)){
				foreach ($sup_hinv as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_hinv_id> <http://bio2rdf.org/hinv:".$r."> .\n";
					}
				}
			}
			if(count($xref_embl_genbank_ddbj)){
				foreach ($xref_embl_genbank_ddbj as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_embl_id> <http://bio2rdf.org/embl:".$r."> .\n";
					}
				}
			}
			if(count($hgnc_ids)){
				foreach ($hgnc_ids as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_hgnc_id> <http://bio2rdf.org/hgnc:".$r."> .\n";
					}
				}
			}
			if(count($ncbi_ids)){
				foreach ($ncbi_ids as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_gene_id> <http://bio2rdf.org/gene:".$r."> .\n";
					}
				}
			}
			if(count($uniparc_ids)){
				foreach ($uniparc_ids as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_uniparc_id> <http://bio2rdf.org/uniparc:".$r."> .\n";
					}
				}
			}
			if(count($unigene_ids)){
				foreach ($unigene_ids as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_unigene_id> <http://bio2rdf.org/unigene:".$r."> .\n";
					}
				}
			}
			if(count($ccds_ids)){
				foreach ($ccds_ids as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_ccds_id> <http://bio2rdf.org/ccds:".$r."> .\n";
					}
				}
			}
			if(count($refseq_gis)){
				foreach ($refseq_gis as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_gene_id> <http://bio2rdf.org/gene:".$r."> .\n";
					}
				}
			}
			if(count($vega_ids)){
				foreach ($vega_ids as $r){
					if($r != ""&& $r != "\n"){
						$buf .= "<$entryURI> <http://bio2rdf.org/ipi_vocabulary:has_vega_id> <http://bio2rdf.org/vega:".$r."> .\n";
					}
				}
			}
		fwrite($outfh, $buf);
		}//while
	}//if
	if(!feof($ifh)){
		echo "Error: unexpected gzgets() fail!\n";
	}
	gzclose($ifh);
	fclose($outfh);
}

function readIdentifiers($str){
	$returnMe = array();
	if(count(isset($str))){		
		@$ev = evaluateSeparators($str);
		if($ev["SEMICOLON"] == 0 && $ev["COMMA"]==0 && $ev["ALL"] == 0 && $ev["COLON"] == 0){
			if(isset($str) && $str != ""){
				@$returnMe[] = $str;
			}
		}else if($ev["SEMICOLON"] == 1 && $ev["COMMA"]==0 && $ev["ALL"] == 0 && $ev["COLON"] == 0){
			if($pos = strpos($str, ";")){						
				$dirty = substr($str, 0, $pos);
				$returnMe[] = $dirty;
			}
		}else if($ev["SEMICOLON"] == 0 && $ev["COMMA"] > 0 && $ev["COLON"] == 0){
			$returnMe = explode(",", $str);
		}else if($ev["SEMICOLON"] == 1 && $ev["COMMA"]==0 && $ev["COLON"] == 1){
			if($pos = strpos($str, ";")){						
				$dirty = substr($str, 0, $pos);
				$a = explode(":", $dirty);
				$returnMe[] = $a[1];
			}
		}else if($ev["SEMICOLON"] > 1 && $ev["COMMA"]==0 && $ev["ALL"] > 1 && $ev["COLON"] == 0){
			$tmp = explode(";", $str);
			//remove any empty elements
			$tmp = removeEmptyElements($tmp);
			//remove things before the :
			foreach ($tmp as $x){
				$a = explode(":", $x);
				if(count ($a) == 2){
					$returnMe[] = $a[1];
				}else{
					$returnMe[] = $x;
				}
			}
		}else if($ev["SEMICOLON"] > 1 && $ev["COMMA"]==0 && $ev["ALL"] > 1 && $ev["COLON"] > 1){
			$tmp = explode(";", $str);
			//remove any empty elements
			$tmp = removeEmptyElements($tmp);
			//remove things before the :
			foreach ($tmp as $x){
				$a = explode(":", $x);
				if(count ($a) == 2){
					$returnMe[] = $a[1];
				}else{
					$returnMe[] = $x;
				}
			}
		}else if($ev["SEMICOLON"] > 1 && $ev["COMMA"] > 1 && $ev["BOTH"] > 1 && $ev["COLON"] > 1){
			//use multiple explode
			$delims = array(",", ";",);
			$tmp = multipleExplode($delims, $str);
			//remove any empty elements
			$tmp = removeEmptyElements($tmp);
			$tmp2 = array();
			foreach($tmp as $x){
				$a = explode(":", $x);
				if(count ($a) == 2){
					$tmp2[] = $a[1];
				}else{
					$tmp2[] = $x;
				}
			}
			$returnMe = array_merge($returnMe, $tmp2);
		}
	}
	return $returnMe;
}

function multipleExplode($delimiters = array(), $string = ''){ 
    $mainDelim=$delimiters[count($delimiters)-1]; 
    array_pop($delimiters); 
    foreach($delimiters as $delimiter){ 
        $string= str_replace($delimiter, $mainDelim, $string); 
    } 
    $result= explode($mainDelim, $string); 
	return $result; 
} 

function getFirstId($s){
	$ev = evaluateSeparators($s);
	if($ev["ALL"] == 0 && $ev["SEMICOLON"]==0 && $ev["COMMA"]== 0 && $ev["COLON"] == 0){
		return trim($s);
	}
	else{
		return "";
	}
	
}
//remove the empty elements from an array
function removeEmptyElements($anArray){
	$returnMe = array();
	foreach($anArray as $a){
		if(count($a) && $a != null && $a != ""){
			$returnMe[] = $a;
		}
	}
	return $returnMe;
}


function getIdOnSecondCol($someStr){
	//check for ; or , characters
	if(strpos($someStr, ";")){
	}
	if(strpos($someStr, ",")){
	
	}
}


/*
 * This function checks $str for the existence of ";" and ","
 * it returns an associative array that has as key one of
 * SEMICOLON, COMMA, COLON or ALL and the respective counts
 * */
function evaluateSeparators($str){
	$returnMe = array();
	//now check how many are there
	$semi_count = substr_count($str, ";");
	//check if there are also any commas
	$comma_count = substr_count($str, ",");
	//check for colons
	$colon_count = substr_count($str, ":");
	$returnMe["SEMICOLON"] = $semi_count;
	$returnMe["COMMA"] = $comma_count;
	$returnMe["COLON"] = $colon_count;
	$returnMe["ALL"]= $semi_count+$comma_count+$colon_count;
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

function startsWith($haystack, $needle){
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}
?>
