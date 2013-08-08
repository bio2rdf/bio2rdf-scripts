<?php
/**
Copyright (C) 2013 Jose Cruz-Toledo and Alison Callahan

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
 * @version 2.0
 * @author Alison Callahan
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ncbi.nlm.nih.gov/genbank/README.genbank
*/
/**
* REQUIREMENTS 
*	1. ncftpget
*/
//TODO: add the following parsers: FEATURES, SEQUENCE, ORIGIN and CONTIG
class GenbankParser extends Bio2RDFizer{
	function __construct($argv){
		parent::__construct($argv, "genbank");
		parent::addParameter('files', true, 'all', 'all', 'files to process');
		parent::addParameter('workspace', false,null,'/data/download/genbank/rsync/', 'directory to place FTP files');
		parent::addParameter('download_url',false,null,'ftp://ftp.ncbi.nih.gov/genbank/');
		parent::initialize();
	}

	function Run(){
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		//iterate over the files
		$paths = $this->getFilePaths($ldir, 'gz');
		$lfile = null;
		foreach($paths as $aPath){
			$lfile = $aPath;
			$ofile = $odir.basename($aPath,".gz").".".parent::getParameterValue('output_format');
			$gz = false;
			if(strstr(parent::getParameterValue('output_format'), "gz")){$gz = true;}
			parent::setWriteFile($ofile, $gz);
			parent::setReadFile($ldir.$lfile, true);
			echo "processing $aPath ...";
			$this->process();
			echo "done!".PHP_EOL;
			parent::getWriteFile()->close();


		}//foreach

	}//run

	function sync_files(){
		$this->setup_ftp();
		$files = parent::getParameterValue('files');
		if($files == 'all'){
			$this->sync_all_files();
		}
	}

	function sync_all_files(){
		$dir = $this->getParameterValue('indir');
		if($dir == null || strlen($dir) == 0){
			trigger_error("Could not find input directory!\n");
			exit;
		}
		echo "syncing genbank files...";
		exec("ncftpget ".parent::getParameterValue('download_url')."*.gz ");
	}

	/**
	* Create workspace and mount genbank
	**/
	function setup_ftp(){
		//create workspace if doesn't already exist
		if($this->CreateDirectory($this->GetParameterValue('workspace')) === TRUE){
			echo "set up workspace ".$this->GetParameterValue('workspace')."\n";
		}else{
			echo "failed to create workspace exiting program";
			exit;
		}

		echo "Setting up FTP mount:\n";
		exec("curlftpfs ".$this->getParameterValue('download_url')." ".$this->getParameterValue('workspace'));
	}
	function process(){
		$gb_record_str = "";
		while ($aLine = $this->getReadFile()->Read(4096)) {
		    preg_match("/^\/\/$/", $aLine, $matches);
		    if(count($matches)){
		    	//now remove the header if it is there
		    	
		    	$gb_record_str = $this->removeHeader($gb_record_str);
		    	$sectionsRaw = $this->parseGenbankRaw($gb_record_str);
		    	/**
		    	* SECTIONS being parsed:
		    	* locus, definition, accession, version, keywords, segment, source, reference,
		    	*/
		    	//get locus section(s)
		    	$locus = $this->retrieveSections("LOCUS", $sectionsRaw);
		    	$parsed_locus_arr = $this->parseLocus($locus);
		    	//get the definition section
		    	$definition = $this->retrieveSections("DEFINITION", $sectionsRaw);
		    	$parsed_definition_arr = $this->parseDefinition($definition);
		    	//get the accession 
		    	$accessions = $this->retrieveSections("ACCESSION", $sectionsRaw);
		    	$parsed_accession_arr = $this->parseAccession($accessions);
		    	//get the version
		    	$versions = $this->retrieveSections("VERSION", $sectionsRaw);
		    	$parsed_version_arr = $this->parseVersion($versions);
		    	//get the keywords
		    	$keywords = $this->retrieveSections("KEYWORDS", $sectionsRaw);
		    	$parsed_keyword_arr = $this->parseKeywords($keywords);
		    	//may not be any segment section
		    	$segments = $this->retrieveSections("SEGMENT", $sectionsRaw);
		    	if(!empty($segments)){
		    		$parsed_segments_arr = $this->parseSegment($segments);
		    	}
		    	//get the source section
		    	$source = $this->retrieveSections("SOURCE", $sectionsRaw);
		    	$parsed_source_arr = $this->parseSource($source);
		    	//get the reference section
		    	$references = $this->retrieveSections("REFERENCE", $sectionsRaw);
		    	$parsed_refs_arr = $this->parseReferences($references);

				$gb_res = "gi:".$parsed_version_arr['gi'];
				$gb_label = utf8_encode(htmlspecialchars($parsed_definition_arr[0]));

				parent::AddRDF(
					parent::describeIndividual($gb_res, $gb_label, $this->getVoc()."genbank_record").
					parent::triplifyString($gb_res, $this->getVoc().'sequence-length', $parsed_locus_arr[0]['sequence_length']).
					parent::triplifyString($gb_res, $this->getVoc().'strandedness', $parsed_locus_arr[0]['strandedness']).
					parent::triplify($gb_res, "rdf:type", $this->getRes().$parsed_locus_arr[0]['mol_type']).
					parent::triplifyString($gb_res, $this->getVoc().'chromosome-shape', $parsed_locus_arr[0]['chromosome_shape']).
					parent::triplifyString($gb_res, $this->getVoc().'division-name', $parsed_locus_arr[0]['division_name']).
					parent::triplifyString($gb_res, $this->getVoc().'date-of-entry', $parsed_locus_arr[0]['date']).
					parent::triplifyString($gb_res, $this->getVoc().'source', utf8_encode($parsed_source_arr[0]))
				);
				
				foreach($parsed_accession_arr[0] as $acc ){
					parent::AddRDF(
						parent::triplifyString($gb_res, $this->getVoc()."accession-number", $acc)
					);
				}
				
				if(isset($parsed_version_arr['versioned_accession'])){
					parent::AddRDF(
						parent::triplifyString($gb_res, $this->getVoc()."versioned-accession", $parsed_version_arr['versioned_accession'])
					);
				}
				
				foreach($parsed_keyword_arr as $akw){
					parent::AddRDF(
						parent::triplifyString($gb_res, $this->getVoc()."keyword", $akw)
					);
				}
				
				if(isset($parsed_segments_arr)){
					foreach($parsed_segments_arr as $aSeg){
						parent::AddRDF(
							parent::triplifyString($gb_res, $this->getVoc()."segment-number", $aSeg['segment_number']).
							parent::triplifyString($gb_res, $this->getVoc()."total-segments", $aSeg['total_segments'])
						);
					}
				}

				foreach($parsed_refs_arr as $aRef){
					$r = rand();
					$ref_res = $this->getRes().md5($r);
					if(isset($aRef['TITLE'])){
						parent::AddRDF(
							parent::triplifyString($ref_res, $this->getVoc()."title", $aRef['TITLE'])
						);
					}
					if(isset($aRef['PUBMED'])){
						parent::AddRDF(
							parent::triplify($ref_res, $this->getVoc()."x-pubmed", $aRef['PUBMED'])
						);
					}
					if(isset($aRef['AUTHORS'])){
						parent::AddRDF(
							parent::triplifyString($ref_res, $this->getVoc()."authors", $aRef['AUTHORS'])
						);
					}
					parent::AddRDF(
						parent::triplify($gb_res, $this->getVoc()."reference", $ref_res).
						parent::triplifyString($ref_res, $this->getVoc()."coordinates", $aRef['COORDINATES']).
						parent::triplifyString($ref_res, $this->getVoc()."journal", $aRef['JOURNAL'])
					);
				}
		    	$gb_record_str = "";
		    	$this->WriteRDFBufferToWriteFile();
		    	continue;
		    }

		    preg_match("/^\n$/", $aLine, $matches);
		    if(count($matches) == 0){
		    	$gb_record_str .= $aLine;
		    }
		}//while
			
	}
	/**
	* Parse the reference section according to section 3.4.11 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseReferences($ref_arr){
		$rm = array();

		$reference_fields = array("AUTHORS", "TITLE", "JOURNAL", "MEDLINE", "PUBMED", "REMARK");

		foreach($ref_arr as $reference){
			$ref_raw = utf8_encode(trim($reference['value']));
			if(strlen($ref_raw)){

				$ref_raw = utf8_encode(trim(preg_replace('/\s\s+/', ' ', $ref_raw)));
				$regex_string = "(.*)";
				$regex_groups = array("COORDINATES");
				//construct regular expression based on the fields in this reference
				foreach($reference_fields as $field){
					if(strpos($ref_raw, $field)){
						$regex_string .= "\s+".$field."\s+(.*)";
						$regex_groups[] = $field;
					}
				}
				$regex = "/".$regex_string."/";
				//search with constructed regex
				preg_match($regex, $ref_raw, $matches);
				$tmp_ref = array();
				//get output of preg_match search
				if(count($matches)){
					foreach($regex_groups as $i => $field){
						if($field == "COORDINATES"){
							$tmp_coord = $matches[$i+1];
							preg_match('/.*\((.*)\)/', $tmp_coord, $matchesc);
							$tmp_ref[$field] = $matchesc[1];
						} else {
							$tmp_ref[$field] = $matches[$i+1];
						}
					}
					$rm[] = $tmp_ref;
				}
			} else {
				trigger_error("Empty reference line!", E_USER_ERROR);
				exit;
			}
		}

		return $rm;
	}

	/**
	* Parse $gb_record_sections and return an array containing
	* the sections of type $aSectionType
	*/
	function retrieveSections($aSectionType, $gb_record_sections){
		$rm = array();
		if(strlen($aSectionType)){
			foreach ($gb_record_sections as $section) {
				if($section['type'] == $aSectionType){
					$rm[] = $section;
				}
			}
		} else {
			trigger_error("Section type not provided!", E_USER_ERROR);
			exit;
		}
		
		return $rm;
	}


	/**
	* Parse the source section according to section 3.4.10 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseSource($source_arr){
		$rm = array();
		foreach($source_arr as $source){
			$source_raw = utf8_encode(trim($source['value']));
			if(strlen($source_raw)){
				$s_arr = preg_split('/\s+ORGANISM/', $source_raw);
				if(strlen($s_arr[0])){
					$rm[] = $s_arr[0];
				}
			}else{
				trigger_error("Empty source line!", E_USER_ERROR);
				exit;
			}
		}
		return $rm;
	}


	/**
	* Parse the segment section according to section 3.4.9 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseSegment($segment_arr){
		$rm = array();
		foreach($segment_arr as $segments){
			$segment_raw = utf8_encode(trim($segments['value']));
			if(strlen($segment_raw)){
				$s_arr = explode(' of ', $segment_raw);
				$rm['segment_number'] = $s_arr[0];
				$rm['total_segments'] = $s_arr[1];
			}
		}
		
		return $rm;
	}


	/**
	* Parse the Keyword section according to section 3.4.8 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/

	function parseKeywords($keywords_arr){
		$rm = array();
		foreach($keywords_arr as $keywords){
			$keywords_raw = utf8_encode(trim($keywords['value']));
			if(strlen($keywords_raw)){
				//remove the periods
				$kw_no_dots = str_replace('.', '', $keywords_raw);
				$kw_arr = explode(';',$kw_no_dots);
				$tmp_keywords = array();
				foreach ($kw_arr as $aKw) {
					$tmp_keywords[] = trim($aKw);
				}
				$rm = $tmp_keywords;
			}else{
				trigger_error("Empty keywords line!", E_USER_ERROR);
				exit;
			}
		}
		return $rm;
	}


	/**
	* Parse the Version section according to section 3.4.7 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseVersion($version_arr){
		$rm = array();

		foreach($version_arr as $version){
			$version_raw = utf8_encode(trim($version['value']));
			if(strlen($version_raw)){
				$version_split = preg_split('/\s+/', $version_raw);
				$tmp_version = array();
				if(count($version_split)){
					$tmp_version['versioned_accession'] = $version_split[0];
					$tmp_version['gi'] = substr($version_split[1], 3);
				}
				$rm = $tmp_version;
			}else{
				trigger_error("Empty version line!", E_USER_ERROR);
				exit;
			}
		}
		
		return $rm;
	}	

	/**
	* This method parses the definition line according to section 3.4.6 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseAccession($accessions){
		$rm = array();
		foreach($accessions as $accession){
			$acc_raw = trim($accession['value']);
			if(strlen($acc_raw)){
				$acc_raw = utf8_encode(trim(preg_replace('/\s\s+/', ' ', $acc_raw)));
				//now split by spaces
				$acc_arr = explode(' ', $acc_raw);
				$tmp_accs = array();
				foreach($acc_arr as $anAcc){
					$tmp_accs[] = $anAcc;
				}
				$rm[] = $tmp_accs;
			}else{
				trigger_error("Empty acccession line!", E_USER_ERROR);
				exit;
			}
		}
		
		return $rm;
	}

	function parseDefinition($definition_array){
		$rm = array();
		foreach($definition_array as $definition){
			$def_raw = trim($definition['value']);
			if(strlen($def_raw)){
				$rm[] = utf8_encode(trim(preg_replace('/\s\s+/', ' ', $def_raw)));
			}else{
				trigger_error("Empty definition line !", E_USER_ERROR);
				exit;
			}
		}
		
		return $rm;
	}

	/**
	* This method parses the locus line according to section 3.4.4 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseLocus($locus_array){
		$rm = array();
		foreach($locus_array as $locus){
			$intervals = array(16,1,11,1,2,1,3,6,2,8,1,3,1,11);
			$locus_raw = trim($locus['value']);
			if(strlen($locus_raw)){
				$start = 0;
				$parts = array();
				foreach($intervals as $i){
					$parts[] = mb_substr($locus_raw,$start, $i);
					$start += $i;
				}
				$d = date_parse(trim($parts[13]));
				$locus_details = array();
				$locus_details['locus_name'] = trim($parts[0]);
				$locus_details['sequence_length'] = trim($parts[2]);
				$locus_details['strandedness'] = $this->getStrandedness($parts[6]);
				$locus_details['mol_type'] = trim($parts[7]);
				$locus_details['chromosome_shape'] = trim($parts[9]);
				$locus_details['division_name'] = $this->getDivisionName(trim($parts[11]));
				$locus_details['date'] = $d['year'].'-'.$d['month'].'-'.$d['day'];

				$rm[] = $locus_details;
			}else{
				trigger_error("Empty locus line !", E_USER_ERROR);
				exit;
			}
		}
		return $rm;
	}
	/**
	* maps the strandedness string to a meaningful
	* description
	*/
	function getStrandedness($aStr){
		$s = array(
			'   ' => 'not specified',
			'ss-' => 'single stranded',
			'ds-' => 'double stranded',
			'ms-' => 'mixed stranded'
		);
		if(strlen($aStr)){
			if(array_key_exists($aStr, $s)){
				return $s[$aStr];
			}else{
				trigger_error("Strandedness key not found !", E_USER_ERROR);
				exit;
			}
		}else{
			trigger_error("null strandedness found!",E_USER_ERROR);
			exit;
		}
	}

	/**
	* Retrieve the full name of a division code
	* given its 3 letter abbreviation
	* i.e.: MAM returns: other mammalian sequences
	*/
	function getDivisionName($aDivisionCode){
		$codes = array(
			'PRI' => 'primate sequences',
			'ROD' => 'rodent sequences',
			'MAM' => 'other mammalian sequences',
			'VRT' => 'other vertebrate sequences',
			'INV' => 'invertebrate sequences',
			'PLN' => 'plant, fungal, and algal sequences',
			'BCT' => 'bacterial sequences',
			'VRL' => 'viral sequences',
			'PHG' => 'bacteriophage sequences',
			'SYN' => 'synthetic sequences',
			'UNA' => 'unannotated sequences',
			'EST' => 'EST sequences (Expressed Sequence Tags)', 
			'PAT' => 'patent sequences',
			'STS' => 'STS sequences (Sequence Tagged Sites)', 
			'GSS' => 'GSS sequences (Genome Survey Sequences)', 
			'HTG' => 'HTGS sequences (High Throughput Genomic sequences)', 
			'HTC' => 'HTC sequences (High Throughput cDNA sequences)', 
			'ENV' => 'Environmental sampling sequences',
			'CON' => 'Constructed sequences',
			'TSA' => 'Transcriptome Shotgun Assembly sequences',
		);
		if(strlen($aDivisionCode)){
			if(array_key_exists($aDivisionCode, $codes)){
				return $codes[$aDivisionCode];
			}else{
				trigger_error("Division code key not found !", E_USER_ERROR);
				exit;
			}
		}else{
			trigger_error("null division code found!",E_USER_ERROR);
			exit;
		}
	}


	/**
	* This function separates the genbank record into its sections.
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseGenbankRaw($gb_record){
		$sections = array();
		$gb_arr = split("\n", $gb_record);
		$aSection = "";
		$section_name = "";
		$record_counter = 0;
		for ($i=0; $i < count($gb_arr); $i++) { 
			if(preg_match('/^(\w+)(.*)/', $gb_arr[$i], $matches) == 1){
				if(count($matches)){
					$type = $matches[1];
					$value = $matches[2];
					$sections[$record_counter]['type'] = $type;
					$sections[$record_counter]['value'] = $value.PHP_EOL;
				}
				$record_counter++;
			} else {
				preg_match('/^(\s+)(.*)/', $gb_arr[$i], $matches);
				if(count($matches)){
					if(array_key_exists($record_counter-1, $sections)){
						$sections[$record_counter-1]['value'] .= $matches[0].PHP_EOL;	
					}
				}
			}
		}//for 
		return $sections;
	}
	/**
	* Pass in a text file containing multiple GB records
	* returns an array with one genbank record per elment
	* it removes the header at the top of the file
	*/
	function removeHeader($aGbRecord){
		$gb_arr = split("\n", $aGbRecord);
		for($i=0;$i<count($gb_arr);$i++){
			preg_match("/^LOCUS/", $gb_arr[$i], $matches);
			if(count($matches)){
				if($i == 0){
					//locus is the first line everything is ok
					return $aGbRecord;
				}else{
					$arr = array_slice($gb_arr, $i);
					return implode("\n", $arr);
				}
			}
		}
	}

	/**
	* return an array of paths to the files with extension $ext found in $dir
	*/
	function getFilePaths($dir, $ext){
		$rm = array();
		if($h = opendir($dir)){
			while(false !== ($file = readdir($h))){
				if($file != '.' && $file != '..' && strtolower(substr($file, strrpos($file, '.')+1)) == $ext){
					$rm [] = $file;
				}
			}
		}else{
			trigger_error("Could not open directory ".$dir);
			exit;
		}
		return $rm;
	}
}//class



/*
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



function extractLocusLine($gb_str){
	$la = explode("\n", $gb_str);
	$line = trim($la[0]);
	$rm = null;
	$re1='((?:[a-z][a-z]+))';	# Word 1
	$re2='.*?';	# Non-greedy match on filler
	$re3='((?:[a-z][a-z]*[0-9]+[a-z0-9]*))';	# Alphanum 1
	$re4='.*?';	# Non-greedy match on filler
	$re5='(\\d+)';	# Integer Number 1
	$re6='.*?';	# Non-greedy match on filler
	$re7='(?:[a-z][a-z]+)';	# Uninteresting: word
	$re8='.*?';	# Non-greedy match on filler
	$re9='((?:[a-z][a-z]+))';	# Word 2
	$re10='.*?';	# Non-greedy match on filler
	$re11='((?:[a-z][a-z]+))';	# Word 3
	$re12='.*?';	# Non-greedy match on filler
	$re13='((?:(?:[0-2]?\\d{1})|(?:[3][01]{1}))[-:\\/.](?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Sept|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)[-:\\/.](?:(?:[1]{1}\\d{1}\\d{1}\\d{1})|(?:[2]{1}\\d{3})))(?![\\d])';	# DDMMMYYYY 1

	if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4.$re5.$re6.$re7.$re8.$re9.$re10.$re11.$re12.$re13."/is", $line, $matches)){
		$locus_name=$matches[2][0];
		$seq_len=$matches[3][0];
		$mol_type=$matches[4][0];
		$gb_div=$matches[5][0];
		$mod_date=$matches[6][0];
		$rm["Locus Name"] =  $locus_name;
		$rm["Sequence Length"] = $seq_len;
		$rm["Molecule Type"] = $mol_type;
		$rm["Genbank Division"] = $gb_div;
		$rm["Modification Date"] = $mod_date;

	}
	return $rm;
}
function extractAccession($gb_str){
	$rm = null;
	$p = "/ACCESSION\s+(.*)\n/";
	preg_match($p, $gb_str, $matches);
	if(isset($matches[1])){
		$y = preg_split("/\s+|\t+/", $matches[1]);
		if(count($y)){
			$rm = $y;
		}
	}
	return $rm;
}

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
	if(isset($p1[1])){
		$p = "/ACCESSION\s+(.*?)/";
		$p2 = preg_split($p, $p1[1]);
		$y = $p2[0];
		$y = preg_replace("/\s\s+/", " ", $y);
		$y = trim(str_replace("\n", "", $y));
		$rm["DEFINITION"] = $y;
	}
	return $rm;
	
}

function extractOrigin($gb_str){
	$rm = null;
	$p = "/ORIGIN\s+.*?/";
	$p1 = preg_split($p, $gb_str);
	if(isset($p1[1])){
		$p = "/\/\/.*?/";
		$p2 = preg_split($p, $p1[1]);
		$rm = $p2[0];
		//remove numbers
		$rm = str_replace(range(0, 9), "", $rm);
		//remove newlines
		$rm = trim(str_replace("\n", "", $rm));
		//remove spaces
		$rm = preg_replace("/\s\s+/", "", $rm);
		$rm = preg_replace("/\s/", "", $rm);
		$rm = strtoupper($rm);
	}
	return $rm;
}


function extractFeaturesRaw($gb_str){
	$rm = null;
	$p = "/FEATURES\s+.*?/";
	$p1 = preg_split($p, $gb_str);
	if(isset($p1[1])){
		$p = "/ORIGIN\s+.*?/";
		$p2 = preg_split($p, $p1[1]);
		$rm = $p2[0];
	}
	return $rm;
}


$str = file_get_contents("/home/jose/tmp/genbank/tmp.gb");


$x = extractVersion($str);
print_r($x);
//parseRecordFromString($str);
//print_r($gb_fields);
*/

?>