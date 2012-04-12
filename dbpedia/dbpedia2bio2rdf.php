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


#specify the location of the downloaded files
$input_file ="/media/twotb/bio2rdf/data/dbpedia/infobox_properties_en.nt.bz2";
#specify the location of the output
$output_file ="/media/twotb/bio2rdf/n3/dbpedia/dbpedia_bio2rdf.nt";

read_dbpedia_properties($input_file, $output_file);
function read_dbpedia_properties($inFile, $outFile){
		//create a file handle for the output file
		$out_fh = fopen($outFile, "w") or die("Cannot open $outFile for writting!\n");
        $bz = bzopen($inFile, "r") or die("Could not open file $inFile!\n");
        while(!feof($bz)){
			$aLine = bzread($bz, 4096);
			preg_match("/(.*)property\\/meshnumberProperty(.*)/", $aLine, $meshnumberProp);
			preg_match("/(.*)property\\/meshid(.*)/", $aLine, $meshid);
			preg_match("/(.*)property\\/meshname(.*)/", $aLine, $meshname);
			preg_match("/(.*)property\\/iupacname(.*)/", $aLine, $iupacname);
			preg_match("/(.*)property\\/mgiid(.*)/", $aLine, $mgiid);
			preg_match("/(.*)property\\/symbol(.*)/", $aLine, $symbol);
			preg_match("/(.*)property\\/scop(.*)/", $aLine, $scop);
			preg_match("/(.*)property\\/interpro(.*)/", $aLine, $interpro);
			preg_match("/(.*)property\\/hgncid(.*)/", $aLine, $hgncid);
			preg_match("/(.*)property\\/kegg(.*)/", $aLine, $kegg);
			preg_match("/(.*)property\\/pdb(.*)/", $aLine, $pdb);
			preg_match("/(.*)property\\/pfam(.*)/", $aLine, $pfam);
			preg_match("/(.*)property\\/prosite(.*)/", $aLine, $prosite);                
			preg_match("/(.*)property\\/inchi(.*)/", $aLine, $inchi);
			preg_match("/(.*)property\\/smiles(.*)/", $aLine, $smiles);
			preg_match("/(.*)property\\/casNumer(.*)/", $aLine, $casNumber);
			preg_match("/(.*)property\\/chebi(.*)/", $aLine, $chebi);
			preg_match("/(.*)property\\/ecnumber(.*)/", $aLine, $ecnumber);
			preg_match("/(.*)property\\/entrezgene(.*)/", $aLine, $entrezgene);
			preg_match("/(.*)property\\/omim(.*)/", $aLine, $omim);
			preg_match("/(.*)property\\/pubchem(.*)/", $aLine, $pubchem);
			preg_match("/(.*)property\\/refseq(.*)/", $aLine, $refseq);
			preg_match("/(.*)property\\/uniprot(.*)/", $aLine, $uniprot);
			preg_match("/(.*)property\\/drugbank(.*)/", $aLine, $drugbank);
			
			//check if a line matched
			if(count($meshnumberProp)){
				//get the triple
				$t = getLiteralTripleFromString($meshnumberProp[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_mesh_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($meshid)){
				//get the triple
				$t = getLiteralTripleFromString($meshid[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_mesh_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($meshname)){
				//get the triple
				$t = getLiteralTripleFromString($meshname[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_mesh_name> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($iupacname)){
				//get the triple
				$t = getLiteralTripleFromString($iupacname[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_iupac_name> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($mgiid)){
				//get the triple
				$t = getLiteralTripleFromString($mgiid[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_mgi_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($symbol)){
				//get the triple
				$t = getLiteralTripleFromString($symbol[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_symbol> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($scop)){
				//get the triple
				$t = getLiteralTripleFromString($scop[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_scop_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($interpro)){
				//get the triple
				$t = getLiteralTripleFromString($interpro[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_interpro_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($hgncid)){
				//get the triple
				$t = getLiteralTripleFromString($hgncid[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_hgnc_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($kegg)){
				//get the triple
				$t = getLiteralTripleFromString($kegg[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_kegg_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($pdb)){
				//get the triple
				$t = getLiteralTripleFromString($pdb[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_pdb_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($pfam)){
				//get the triple
				$t = getLiteralTripleFromString($pfam[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_pfam_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($prosite)){
				//get the triple
				$t = getLiteralTripleFromString($iupacname[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_prosite_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($inchi)){
				//get the triple
				$t = getLiteralTripleFromString($inchi[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_inchi> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($smiles)){
				//get the triple
				$t = getLiteralTripleFromString($smiles[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_smiles> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($casNumber)){
				//get the triple
				$t = getLiteralTripleFromString($casNumber[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_cas> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($chebi)){
				//get the triple
				$t = getLiteralTripleFromString($chebi[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_chebi_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($ecnumber)){
				//get the triple
				$t = getLiteralTripleFromString($ecnumber[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_ec_number> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($entrezgene)){
				//get the triple
				$t = getLiteralTripleFromString($entrezgene[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_entrez_gene_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($omim)){
				//get the triple
				$t = getLiteralTripleFromString($omim[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_omim_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($pubchem)){
				//get the triple
				$t = getLiteralTripleFromString($pubchem[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_pubchem_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($refseq)){
				//get the triple
				$t = getLiteralTripleFromString($refseq[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_refseq_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($uniprot)){
				//get the triple
				$t = getLiteralTripleFromString($uniprot[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_uniprot_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}elseif(count($drugbank)){
				//get the triple
				$t = getLiteralTripleFromString($drugbank[0]);
				if(count($t)==3){
					//make a valid bio2rdf triple
					$triple = $t["subject"]." <http://bio2rdf.org/dbpedia_vocabulary:has_drugbank_id> ".$t["object"].".\n";
					//write the triple to the output file
					writeTripleToFile($triple, $out_fh);
				}
			}

        }
        fclose($out_fh);
        bzclose($bz);
}

function writeTripleToFile($triple, $outFh){

	if(fwrite($outFh, $triple) === FALSE){
		echo "Cannot write to file  !\n";
		exit;
	}

}

function getLiteralTripleFromString($aLine){
	$returnMe = array();
	$r = explode(" ", $aLine);
	if(count($r) == 4){
		$returnMe["subject"] = $r[0];
		$returnMe["predicate"] = $r[1];
		$returnMe["object"] = $r[2];
	}
	return $returnMe;
}




?>
