<?php
/**
Copyright (C) 2012 Jose Cruz-Toledo

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
 * NCBI GenBank parser
 * @version 0.1
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ncbi.nlm.nih.gov/genbank/README.genbank
*/

$gb_fields = array(
	"LOCUS" => array(
		"description" => "The LOCUS field contains a number of different data elements, including locus name, sequence length, molecule type, GenBank division, and modification date",
		"sub-fileds" => array(
			"Locus Name" => array(
				"description" => "The locus name was originally designed to help group entries with similar sequences: the first three characters usually designated the organism; the fourth and fifth characters were used to show other group designations, such as gene product; for segmented entries, the last character was one of a series of sequential integers"
			),
			"Sequence Length" => array(
				"description" => "Number of nucleotide base pairs (or amino acid residues) in the sequence record"
			),
			"Molecule Type" => array(
				"description" => "The type of molecule that was sequenced. Can include genomic DNA, genomic RNA, precursor RNA, mRNA (cDNA), ribosomal RNA, transfer RNA, small nuclear RNA, and small cytoplasmic RNA."
			),
			"GenBank Division" => array(
				"description" => "The GenBank division to which a record belongs is indicated with a three letter abbreviation"
			),
			"Modification Date" => array(
				"description" => "The date in the LOCUS field is the date of last modification"
			)
		),
	),
	"DEFINITION" => array(
		"description" => "Brief description of sequence; includes information such as source organism, gene name/protein name, or some description of the sequence's function (if the sequence is non-coding)."
	),
	"ACCESSION" => array(
		"description" => "The unique identifier for a sequence record. An accession number applies to the complete record and is usually a combination of a letter(s) and numbers, such as a single letter followed by five digits (e.g., U12345) or two letters followed by six digits (e.g., AF123456). Some accessions might be longer, depending on the type of sequence record."
	),
	"VERSION" => array(
		"description" => "A nucleotide sequence identification number that represents a single, specific sequence in the GenBank database. This identification number uses the accession.version format implemented by GenBank/EMBL/DDBJ in February 1999.",
		"sub-fileds" => array(
			"GI" => array(
				"description" => "GeneInfo Identifier, sequence identification number, in this case, for the nucleotide sequence. If a sequence changes in any way, a new GI number will be assigned."
			)
		)
	),
	"KEYWORDS" => array(
		"description" => "Word or phrase describing the sequence. If no keywords are included in the entry, the field contains only a period."
	),
	"SOURCE" => array(
		"description" => "Free-format information including an abbreviated form of the organism name, sometimes followed by a molecule type. ",
		"sub-fileds" => array(
			"Organism" => array(
				"description" => "The formal scientific name for the source organism (genus and species, where appropriate) and its lineage, based on the phylogenetic classification scheme used in the NCBI Taxonomy Database"
			)
		)
	),
	"REFERENCE" => array(
		"description" => "Publications by the authors of the sequence that discuss the data reported in the record. References are automatically sorted within the record based on date of publication, showing the oldest references first.",
		"sub-fileds" =>array(
			"AUTHORS" => array(
				"description" => "List of authors in the order in which they appear in the cited article."
			),
			"TITLE" => array(
				"description" => "Title of the published work or tentative title of an unpublished work."
			),
			"JOURNAL" => array(
				"description" => "MEDLINE abbreviation of the journal name."
			),
			"PUBMED" => array(
				"description" => "PubMed Identifier (PMID)."
			),
			"Direct Sumbission" => array(
				"description" => "Contact information of the submitter, such as institute/department and postal address. This is always the last citation in the References field."
			)
		)
	),
	"FEATURES" => array(
		"description" => "Information about genes and gene products, as well as regions of biological significance reported in the sequence. These can include regions of the sequence that code for proteins and RNA molecules, as well as a number of other features.",
		"sub-fileds" => array(
			"source" => array(
				"description" => "Mandatory feature in each record that summarizes the length of the sequence, scientific name of the source organism, and Taxon ID number. Can also include other information such as map location, strain, clone, tissue type, etc., if provided by submitter."
			),
			"CDS" => array(
				"description" => "Coding sequence; region of nucleotides that corresponds with the sequence of amino acids in a protein (location includes start and stop codons)."
			),
			"gene" =>  array(
				"description" => "A region of biological interest identified as a gene and for which a name has been assigned. The base span for the gene feature is dependent on the furthest 5' and 3' features."
			)
		)
	),
	"ORIGIN" => array(
		"description" => " may give a local pointer to the sequence start, usually involving an experimentally determined restriction cleavage site or the genetic locus (if available). This information is present only in older records."
	)
);
function parseRecordFromString($aGenbank_str){
	global $gb_fields;
	$nl_arr = explode("\n", $aGenbank_str);
	$fields = "";
	foreach(array_keys($gb_fields) as $af){
		$fields .= $af."|";
	}
	$fileds = substr($fields, 0, -1)."\n";

	foreach ($nl_arr as $aLine) {
		
		preg_match("/(LOCUS\s+.*$)|(DEFINITION\s+.*$)/", $aLine, $matches);
		if(count($matches)){
			echo $aLine."\n";
			print_r($matches);
			echo "----\n";
		}
		/*$re = '/^(\w+)\s+.*|^(\w+)$/';
		preg_match($re, $aLine, $matches);
		if(count($matches)){
			//get the heading
			$heading = "";
			if(count($matches) == 2){
				$heading = $matches[1];
			}elseif (count($matches) == 3) {
				$heading = $matches[2];
			}
			echo $heading."\n";
			//if locus
		}//if count*/
		
		
	}
}



function extractGI($gb_str){
	$rm = null;
	$p = "/VERSION\s+(.*?)\s+GI:(.*)/";
	preg_match($p, $gb_str,$matches);
	if(isset($matches[2])){
		$rm = $matches[2];
	}
	return $rm;
}

function extractVersion($gb_str){
	$rm = null;
	$p = "/VERSION\s+(.*?)\s+GI:(.*)/";
	preg_match($p, $gb_str,$matches);
	if(isset($matches[1])){
		$rm = $matches[1];
	}
	return $rm;
}

function extractAccession($gb_str){
	$rm = null;
	$p = "/ACCESSION\s+(.*)\n/";
	preg_match($p, $gb_str, $matches);
	if(isset($matches[1])){
		$rm = $matches[1];
	}
	return $rm;
}
/**
* get an array of keywords
*/
function extractKeywords($gb_str){
	$rm = null;
	$p = "/KEYWORDS\s+(.*?)/";
	$p1 = preg_split($p, $gb_str);
	$p = "/SOURCE\s(.*?)/";
	if(isset($p1[1])){
		$p2 = preg_split($p, $p1[1]);
		$rm = $p2[0];
		$rm = preg_replace("/\s\s+/", " ", $rm);
		$rm = trim(str_replace("\n", "", $rm));
		$rm = explode("; ", $rm);
	}
	return  $rm;
}


function extractDefinition($gb_str){
	$rm = null;
	$p = "/DEFINITION\s+(.*?)/";
	$p1 = preg_split($p, $gb_str);
	$p = "/ACCESSION\s+(.*?)/";
	if(isset($p1[1])){
		$p2 = preg_split($p, $p1[1]);
		$rm = $p2[0];
		$rm = preg_replace("/\s\s+/", " ", $rm);
		$rm = trim(str_replace("\n", "", $rm));
	}
	return $rm;
	
}

$str = file_get_contents("/home/jose/tmp/genbank/tmp.gb");

$x = extractKeywords($str);
print_r($x);
//parseRecordFromString($str);
//print_r($gb_fields);
?>