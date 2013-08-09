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
 * @description ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
*/

//TODO: add the following parsers: FEATURES (http://www.insdc.org/documents/feature-table)
class GenbankParser extends Bio2RDFizer{
	function __construct($argv){
		parent::__construct($argv, "genbank");
		parent::addParameter('files', true, 'all', 'all', 'files to process');
		parent::addParameter('workspace', false,null,'/data/download/genbank/rsync/', 'directory to place FTP files');
		parent::addParameter('download_url',false,null,'ftp://ftp.ncbi.nih.gov/genbank/');
		parent::initialize();
	}

	function Run(){
		$dataset_description = '';
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');

		//download
		if($this->GetParameterValue('download') == true){
			$list = $this->getFtpFileList('ftp.ncbi.nlm.nih.gov', 'seq.gz');
			$total = count($list);
			$counter = 1;
			foreach($list as $f){
				echo "downloading file $counter out of $total :".parent::getParameterValue('download_url').$f."... ".PHP_EOL;
				file_put_contents($ldir.$f,file_get_contents(parent::getParameterValue('download_url').$f));
				$counter++;
			}
		}
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

			$source_file = (new DataResource($this))
				->setURI(parent::getParameterValue('download_url').basename($aPath))
				->setTitle('NCBI Genbank filename: '.basename($aPath))
				->setRetrievedDate(date("Y-m-d\TG:i:s\Z", filemtime($ldir.$lfile)))
				->setFormat('text/genbank-format')
				->setFormat('application/zip')
				->setPublisher('https://www.ncbi.nlm.nih.gov')
				->setHomepage('https://www.ncbi.nlm.nih.gov/genbank')
				->setRights('use')
				->setRights('attribution')
				->setLicense('https://www.nlm.nih.gov/copyright.html')
				->setDataset(parent::getDatasetURI());
			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/genbank/genbank.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")
				->setRights("use-share-modify")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());
			$dataset_description .= $output_file->toRDF().$source_file->toRDF();

			echo "processing $aPath ...";
			$this->process();
			echo "done!".PHP_EOL;

			$this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
			$this->getWriteFile()->write($dataset_description);
			$this->getWriteFile()->close();
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

		   		//$features = $this->retrieveSections("FEATURES", $sectionsRaw);
		   		//$parsed_features_arr = $this->parseFeatures($features);
		    	//get the source section
		    	$source = $this->retrieveSections("SOURCE", $sectionsRaw);
		    	$parsed_source_arr = $this->parseSource($source);
		    	//get the reference section
		    	$references = $this->retrieveSections("REFERENCE", $sectionsRaw);
		    	$parsed_refs_arr = $this->parseReferences($references);
				$gb_res = "gi:".$parsed_version_arr['gi'];
				$gb_label = utf8_encode(htmlspecialchars($parsed_definition_arr[0]));

				parent::AddRDF(
					parent::describeIndividual($gb_res, $gb_label, $this->getVoc()."genbank- record").
					parent::triplifyString($gb_res, $this->getVoc().'sequence-length', $parsed_locus_arr[0]['sequence_length']).
					parent::triplifyString($gb_res, $this->getVoc().'strandedness', $parsed_locus_arr[0]['strandedness']).
					parent::triplify($gb_res, "rdf:type", $this->getRes().$parsed_locus_arr[0]['mol_type']).
					parent::triplifyString($gb_res, $this->getVoc().'chromosome-shape', $parsed_locus_arr[0]['chromosome_shape']).
					parent::triplifyString($gb_res, $this->getVoc().'division-name', $parsed_locus_arr[0]['division_name']).
					parent::triplifyString($gb_res, $this->getVoc().'date-of-entry', $parsed_locus_arr[0]['date']).
					parent::triplifyString($gb_res, $this->getVoc().'source', utf8_encode($parsed_source_arr[0])).
					parent::QQuadO_URL($gb_res, $this->getVoc().'fasta-seq', 'https://www.ncbi.nlm.nih.gov/sviewer/viewer.cgi?sendto=on&db=nucest&dopt=fasta&val='.$parsed_version_arr['gi'])
				);


				
				foreach($parsed_accession_arr[0] as $acc ){
					parent::AddRDF(
						parent::triplifyString($gb_res, $this->getVoc()."accession", $acc)
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
							parent::triplify($ref_res, $this->getVoc()."x-pubmed", 'pubmed:'.$aRef['PUBMED'])
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
						parent::triplifyString($ref_res, $this->getVoc()."citation", $aRef['JOURNAL'])
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
	*
	*/
	function parseFeatures($feature_arr){
		$rm = array();
		foreach($feature_arr as $feat){
			$feature_raw = utf8_encode(trim($feat['value']));
			//print_r($feature_raw);
			echo "\n***\n";
		}
		return $rm;
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

	/**
	* Given an FTP uri get a non recursive list of all files of a given extension
	*/
	function getFtpFileList($ftp_uri, $extension){
		$rm = array();
		// set up basic connection
		$conn_id = ftp_connect($ftp_uri);
		$ftp_user = 'anonymous';
		if (@ftp_login($conn_id, $ftp_user, '')) {
		  
		} else {
		    echo "Couldn't connect as $ftp_user\n";
		    exit;
		}

		// get contents of the current directory
		$contents = ftp_nlist($conn_id, "/genbank");
		foreach($contents as $aFile){

			preg_match("/.*\/(.*seq\.gz)/", $aFile, $matches);
			if(count($matches)){
				$rm[] = $matches[1];
			}
		}
		return $rm;
	}
}//class

?>