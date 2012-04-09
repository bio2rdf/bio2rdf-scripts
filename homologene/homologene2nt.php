<?php
###############################################################################
#Copyright (C) 2011 Jose Cruz-Toledo, Alison Callahan
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


parse_homologene_tab_file("/tmp/homologene.data", "/tmp/test.nt");



function parse_homologene_tab_file($inpath, $outpath){
	$homologene = "http://bio2rdf.org/homologene";
	$taxid = "http://bio2rdf.org/taxon:";
	$geneid = "http://bio2rdf.org/geneid:";
	$gi = "http://bio2rdf.org/gi:";
	$refseq = "http://bio2rdf.org/refseq:";
	$label = "http://www.w3.org/2000/01/rdf-schema#label";
	$type = "http://www.w3.org/1999/02/22-rdf-syntax-ns#type";
	
	
	$infh = fopen($inpath, 'r') or die("Cannot open $inpath!\n");
	$outfh = fopen($outpath, 'w') or die("Cannot open $outpath\n");
	
	if($infh){
		while(($aLine = fgets($infh, 4096)) !== false){
			$parsed_line = parse_homologene_tab_line($aLine);
			$buf = "<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_taxid> <".$taxid.$parsed_line["taxid"].">.\n";
			$buf .= "<$homologene:".$parsed_line["hid"]."> <".$type."> <".$homologene."_vocabulary:HomoloGene_Group>.\n";
			$buf .= "<$homologene:".$parsed_line["hid"]."> <".$label."> \"HomoloGene Group\".\n";
			$buf .="<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_geneid> <".$geneid.$parsed_line["geneid"].">.\n";
			$buf .="<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_geneSymbol> \"".str_replace("\\","", $parsed_line["genesymbol"])."\".\n";
			$buf .="<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_gi> <".$gi.$parsed_line["gi"].">.\n";
			$buf .="<$homologene:".$parsed_line["hid"]."> <".$homologene."_vocabulary:has_refseq> <".$refseq.$parsed_line["refseq"].">.\n";
			fwrite($outfh, utf8_encode($buf));
			
		}
		if(!feof($infh)){
			echo "Error : unexpected fgets() fail\n";
		}
	}
	fclose($infh);
	fclose($outfh);
}

function parse_homologene_tab_line($aLine){
	$retrunMe = array();
	$r = explode("\t", $aLine);
	$returnMe["hid"] = trim($r[0]);
	$returnMe["taxid"] = trim($r[1]);
	$returnMe["geneid"] = trim($r[2]);
	$returnMe["genesymbol"] = trim($r[3]);
	$returnMe["gi"] = trim($r[4]);
	$returnMe["refseq"] = trim($r[5]);
	return $returnMe;
}


?>
