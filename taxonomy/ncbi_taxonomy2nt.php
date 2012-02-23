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

//Unzip the contents of ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdmp.zip
$names_path = "/home/jose/tmp/taxonomy/names.dmp";
$nodes_path = "/home/jose/tmp/taxonomy/nodes.dmp";
$division_path = "/home/jose/tmp/taxonomy/division.dmp";
$gencodes_path = "/home/jose/tmp/taxonomy/gencode.dmp";
$citations_path = "/home/jose/tmp/taxonomy/citations.dmp";

parse_names_file($names_path);
parse_nodes_file($nodes_path);
parse_divisions_file($division_path);
parse_gencode_file($gencodes_path);

function parse_citations_file($path){
	$handle = fopen($path, "r") or die("Could not open file $path!\n");
	if ($handle) {
		$base = "http://bio2rdf.org/taxon:";
		$vocab = "http://bio2rdf.org/taxon_vocabulary:";
		while (($aLine = fgets($handle, 4096)) !== false) {
			$a = explode("|",$aLine);
			$gencode = str_replace("\t","",trim($a[0]));
			$abbr = str_replace("\t","",trim($a[1]));
			$name = str_replace("\t","",trim($a[2]));
			$translation_table = str_replace("\t","",trim($a[3]));
			$start_codons = str_replace("\t","",trim($a[4]));
			$divUri = "http://bio2rdf.org/taxon_resource:gencode_$gencode";
			$buf = "";
			$buf .= "<$divUri> <".$vocab."has_abbreviation> \"".$abbr."\" .\n";
			$buf .= "<$divUri> <".$vocab."has_name> \"".$name."\" .\n";
			$buf .= "<$divUri> <".$vocab."has_translation_table> \"".$translation_table."\" .\n";
			$buf .= "<$divUri> <".$vocab."has_start_codons> \"".$start_codons."\" .\n";
			
			
			echo $buf;
		}
		if (!feof($handle)) {
			echo "Error: unexpected fgets() fail\n";
		}
		fclose($handle);	
	}
}

function parse_gencode_file($path){
	$handle = fopen($path, "r") or die("Could not open file $path!\n");
	if ($handle) {
		$base = "http://bio2rdf.org/taxon:";
		$vocab = "http://bio2rdf.org/taxon_vocabulary:";
		while (($aLine = fgets($handle, 4096)) !== false) {
			$a = explode("|",$aLine);
			$gencode = str_replace("\t","",trim($a[0]));
			$abbr = str_replace("\t","",trim($a[1]));
			$name = str_replace("\t","",trim($a[2]));
			$translation_table = str_replace("\t","",trim($a[3]));
			$start_codons = str_replace("\t","",trim($a[4]));
			$divUri = "http://bio2rdf.org/taxon_resource:gencode_$gencode";
			$buf = "";
			$buf .= "<$divUri> <".$vocab."has_abbreviation> \"".$abbr."\" .\n";
			$buf .= "<$divUri> <".$vocab."has_name> \"".$name."\" .\n";
			$buf .= "<$divUri> <".$vocab."has_translation_table> \"".$translation_table."\" .\n";
			$buf .= "<$divUri> <".$vocab."has_start_codons> \"".$start_codons."\" .\n";
			
			
			echo $buf;
		}
		if (!feof($handle)) {
			echo "Error: unexpected fgets() fail\n";
		}
		fclose($handle);

	}
}

function parse_divisions_file($path){
	$handle = fopen($path, "r") or die("Could not open file $path!\n");
	if ($handle) {
		$base = "http://bio2rdf.org/taxon:";
		$vocab = "http://bio2rdf.org/taxon_vocabulary:";
		while (($aLine = fgets($handle, 4096)) !== false) {
			$a = explode("|",$aLine);
			$division_id = str_replace("\t","",trim($a[0]));
			$division_code = str_replace("\t","",trim($a[1]));
			$name = str_replace("\t","",trim($a[2]));
			$comments = str_replace("\t","",trim($a[3]));
			$divUri = "http://bio2rdf.org/taxon_resource:div_id_$division_id";
			$buf = "";
			$buf .= "<$divUri> <".$vocab."has_division_code> \"".$division_code."\" .\n";
			$buf .= "<$divUri> <".$vocab."has_division_name> \"".$name."\" .\n";
			if(strlen($comments)){
				$buf .= "<$divUri> <".$vocab."has_comments> \"".$comments."\" .\n";
			}
			echo $buf;
		}
		if (!feof($handle)) {
			echo "Error: unexpected fgets() fail\n";
		}
		fclose($handle);

	}
}
function parse_nodes_file($path){
	$handle = fopen($path, "r") or die("Could not open file $path!\n");
	if ($handle) {
		$base = "http://bio2rdf.org/taxon:";
		$vocab = "http://bio2rdf.org/taxon_vocabulary:";
		while (($aLine = fgets($handle, 4096)) !== false) {
			$a = explode("|",$aLine);
			$taxid =str_replace("\t","",trim($a[0]));
			$parent_taxid = str_replace("\t","",trim($a[1]));
			$rank = str_replace("\t","",trim($a[2]));
			$embl_code = str_replace("\t","",trim($a[3]));
			$division_id = str_replace("\t","",trim($a[4]));
			$inherited_div_flag = str_replace("\t","",trim($a[5]));
			$genetic_code_id = str_replace("\t","",trim($a[6]));
			$inherited_gc_flag = str_replace("\t","",trim($a[7]));
			$mitochondrial_genetic_code_id = str_replace("\t","",trim($a[8]));
			$inherited_mgc_flag = str_replace("\t","",trim($a[9]));
			$genbank_hidden_flag = str_replace("\t","",trim($a[10]));
			$hidden_st_root_flag = str_replace("\t","",trim($a[11]));
			$comments = str_replace("\t","",trim($a[12]));
			
			$entryUri = $base.$taxid;
			$buf ="";
			if(strlen($parent_taxid)){
				$buf .= "<$entryUri> <".$vocab."has_parent> <".$base.$parent_taxid."> .\n";
				$buf .= "<$entryUri>  <http://www.w3.org/2000/01/rdf-schema#subClassOf> <".$base.$parent_taxid."> .\n";
			}
			if(strlen($rank)){
				$buf .= "<$entryUri> <".$vocab."has_rank> \"".$rank."\" .\n";
			}
			if(strlen($embl_code)){
				$buf .= "<$entryUri> <".$vocab."has_embl> \"".$embl_code."\" .\n";
			}
			if(strlen($division_id)){
				$buf .= "<$entryUri> <".$vocab."has_division_id> \"".$division_id."\" .\n";
				$buf .= "<$entryUri> <".$vocab."has_division> <http://bio2rdf.org/taxon_resource:div_id_$division_id> .\n";
			}
			if(strlen($inherited_div_flag)){
				$buf .= "<$entryUri> <".$vocab."has_inherited_division_flag> \"".$inherited_div_flag."\" .\n";
			}
			if(strlen($genetic_code_id)){
				$buf .= "<$entryUri> <".$vocab."has_genetic_code_id> \"".$genetic_code_id."\" .\n";
				$buf .= "<$entryUri> <".$vocab."has_gencode> <http://bio2rdf.org/taxon_resource:gencode_$genetic_code_id> .\n";
			}
			if(strlen($inherited_gc_flag)){
				$buf .= "<$entryUri> <".$vocab."has_inherited_gc_flag> \"".$inherited_gc_flag."\" .\n";
			}
			if(strlen($mitochondrial_genetic_code_id)){
				$buf .= "<$entryUri> <".$vocab."has_mitochondrial_genetic_code_id> \"".$mitochondrial_genetic_code_id."\" .\n";
			}
			if(strlen($inherited_mgc_flag)){
				$buf .= "<$entryUri> <".$vocab."has_inherited_mgc_flag> \"".$inherited_mgc_flag."\" .\n";
			}
			if(strlen($genbank_hidden_flag)){
				$buf .= "<$entryUri> <".$vocab."has_genbank_hidden_flag> \"".$genbank_hidden_flag."\" .\n";
			}
			if(strlen($hidden_st_root_flag)){
				$buf .= "<$entryUri> <".$vocab."has_st_root_flag> \"".$hidden_st_root_flag."\" .\n";
			}
			if(strlen($comments)){
				$buf .= "<$entryUri> <".$vocab."has_comments> \"".$comments."\" .\n";
			}
			
			
			echo $buf;
			
			
		}
		if (!feof($handle)) {
			echo "Error: unexpected fgets() fail\n";
		}
		fclose($handle);
	}
}

function parse_names_file($path){
	$handle = fopen($path, "r") or die("Could not open file $path!\n");
	if ($handle) {
		$base = "http://bio2rdf.org/taxon";
		while (($aLine = fgets($handle, 4096)) !== false) {
			$a = explode("|", $aLine);
			$taxid = str_replace("\t","",trim($a[0]));
			$name = str_replace("\t","",trim($a[1]));
			$unique_name = str_replace("\t","",trim($a[2]));
			$name_class = str_replace("\t","",trim($a[3]));
			
			$entryUri = $base.":".$taxid;
			$buf ="";
			if (strlen($name)){
				$buf .= "<$entryUri> <http://bio2rdf.org/taxon_vocabulary:has_name> \"".str_replace("\"","",utf8_encode($name))."\".\n";
			}
			if(strlen($unique_name)){
				$buf .= "<$entryUri> <http://bio2rdf.org/taxon_vocabulary:has_unique_name> \"".str_replace("\"","",utf8_encode($unique_name))."\".\n";
			}
			if(strlen($name_class)){
				$buf .= "<$entryUri> <http://bio2rdf.org/taxon_vocabulary:has_name_class> \"".utf8_encode($name_class)."\".\n";
			}
			
			
			echo $buf;
			
		}
		if (!feof($handle)) {
			echo "Error: unexpected fgets() fail\n";
		}
		fclose($handle);
	}
	
}


?>
