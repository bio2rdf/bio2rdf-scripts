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
 * International Protein Index parser
 * @version 2.0
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ebi.ac.uk/pub/databases/IPI/last_release/current/Final%20Release%20of%20IPI
*/
class IPIParser extends Bio2RDFizer{
	private static $packageMap = array(
		"species_xrefs" =>array(
			"ipi.ARATH.xrefs.gz",
			"ipi.CHICK.xrefs.gz",
			"ipi.BOVIN.xrefs.gz", 
			"ipi.DANRE.xrefs.gz", 
			"ipi.HUMAN.xrefs.gz",
			"ipi.MOUSE.xrefs.gz",
			"ipi.RAT.xrefs.gz"
		),
		"gene_xrefs" => array(
			"ipi.genes.ARATH.xrefs.gz",
			"ipi.genes.BOVIN.xrefs.gz",
			"ipi.genes.CHICK.xrefs.gz",
			"ipi.genes.DANRE.xrefs.gz"
		),
		"gi2ipi" => array(
			"gi2ipi.xrefs.gz"
		)
	);

	function __construct($argv){
		parent::__construct($argv, "ipi");
		parent::addParameter('files', true, null, 'all|species_xrefs|gene_xrefs|gi2ipi', '', 'files to process');
		parent::addParameter('download_url', false,null, 'ftp://ftp.ebi.ac.uk/pub/databases/IPI/last_release/current/');
		parent::initialize();
	}
	public function Run(){
		$dataset_description = '';

		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		//first get the files that are to be processed
		$selectedPackage = trim(parent::getParameterValue('files'));
		if($selectedPackage == 'all') {
			$files = $this->getPackageMap();
		} else {
			$sel_arr = explode(",",$selectedPackage);
			$pm = $this->getPackageMap();
			$files = array();
			foreach($sel_arr as $a){
				if(array_key_exists($a, $pm)){
					$files[$a] = $pm[$a];

				}
			}	
		}
		//download 
		if($this->getParameterValue('download')){
			foreach ($files as $aP => $fn) {
				foreach ($fn as $aFn ) {
					echo "downloading file $aFn :".parent::getParameterValue('download_url').$aFn."...".PHP_EOL;
					file_put_contents($ldir.$aFn,file_get_contents(parent::getParameterValue('download_url').$aFn));
				}
			}
		}
		//iterate over the files
		$paths = $this->getFilePaths($ldir, 'gz');
		$lfile = null;
		foreach($files as $k => $val){
			foreach($val as $fn){
				if(in_array($fn, $paths)){
					$lfile = $fn;
					$ofile = $odir.basename($fn,".gz").".".parent::getParameterValue('output_format');
					$gz = false;
					if(strstr(parent::getParameterValue('output_format'), "gz")){$gz = true;}
					parent::setWriteFile($ofile, $gz);
					parent::setReadFile($ldir.$lfile, true);
					$source_file = (new DataResource($this))
						->setURI(parent::getParameterValue('download_url').basename($fn))
						->setTitle('International Protein Index filename: '.basename($fn))
						->setRetrievedDate(date("Y-m-d\TG:i:s\Z", filemtime($ldir.$lfile)))
						->setFormat('text/ipi-format')
						->setFormat('application/zip')
						->setPublisher('https://www.ebi.ac.uk')
						->setHomepage('https://www.ebi.ac.uk/IPI')
						->setRights('use')
						->setRights('attribution')
						->setLicense('https://www.ebi.ac.uk')
						->setDataset(parent::getDatasetURI());
					$prefix = parent::getPrefix();
					$bVersion = parent::getParameterValue('bio2rdf_release');
					$date = date("Y-m-d\TG:i:s\Z");
					$output_file = (new DataResource($this))
						->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix")
						->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
						->setSource($source_file->getURI())
						->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/ipi/ipi.php")
						->setCreateDate($date)
						->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
						->setPublisher("http://bio2rdf.org")
						->setRights("use-share-modify")
						->setRights("restricted-by-source-license")
						->setLicense("http://creativecommons/licenses/by/3.0/")
						->setDataset(parent::getDatasetURI());
					$dataset_description .= $output_file->toRDF().$source_file->toRDF();
					echo "processing $fn ...";
					$this->$k();
					echo "done!".PHP_EOL;
					$this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
					$this->getWriteFile()->write($dataset_description);
					$this->getWriteFile()->close();
				}
				
			}
		}
	}

	private function species_xrefs(){
		while($aLine = $this->getReadFile()->Read(4096)){
			$tLine = explode("\t", $aLine);
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
				@$uniprotkb_id = $this->getFirstId($tLine[1]);
			}
			//ipi id
			if(count(isset($tLine[2]))){
				@$ipi_id = $tLine[2];
			}
			//Supplementary UniProtKB/Swiss-Prot entries associated with this IPI entry.
			if(count(isset($tLine[3]))){
				@$sup_uniprots_sps = $this->readIdentifiers($tLine[3]);
			}
			//Supplementary UniProtKB/TrEMBL entries associated with this IPI entry.
			if(count(isset($tLine[4]))){
				@$sup_uniprots_tre = $this->readIdentifiers($tLine[4]);
			}
			//Supplementary Ensembl entries associated with this IPI entry. Havana curated transcripts preceeded by the key HAVANA: (e.g. HAVANA:ENSP00000237305;ENSP00000356824;).
			if(count(isset($tLine[5]))){
				@$sup_ensembl = $this->readIdentifiers($tLine[5]);
			}
			//Supplementary list of RefSeq STATUS:ID couples (separated by a semi-colon ';') associated with this IPI entry (RefSeq entry revision status details).
			if(count(isset($tLine[6]))){
				@$sup_refseq = $this->readIdentifiers($tLine[6]);
			}
			//Supplementary TAIR Protein entries associated with this IPI entry.
			if(count(isset($tLine[7]))){
				@$sup_tair = $this->readIdentifiers($tLine[7]);
			}
			//Supplementary H-Inv Protein entries associated with this IPI entry.
			if(count(isset($tLine[8]))){
				@$sup_hinv = $this->readIdentifiers($tLine[8]);
			}
			//Protein identifiers (cross reference to EMBL/Genbank/DDBJ nucleotide databases).
			if(count(isset($tLine[9]))){
				@$xref_embl_genbank_ddbj = $this->readIdentifiers($tLine[9]);
			}
			//List of HGNC number, HGNC official gene symbol couples (separated by by a semi-colon ';') associated with this IPI entry.
			if(count(isset($tLine[10]))){
				@$hgnc_ids = $this->readIdentifiers($tLine[10]);
			}
			////List of NCBI Entrez Gene gene number, Entrez Gene Default Gene Symbol couples (separated by a semi-colon ';') associated with this IPI entry.
			if(count(isset($tLine[11]))){
				@$ncbi_ids = $this->readIdentifiers($tLine[11]);
			}
			//UNIPARC identifier associated with the sequence of this IPI entry.
			if(count(isset($tLine[12]))){
				@$uniparc_ids = $this->readIdentifiers($tLine[12]);
			}	
			//UniGene identifiers associated with this IPI entry.
			if(count(isset($tLine[13]))){
				@$unigene_ids = $this->readIdentifiers($tLine[13]);
			}
			//CCDS identifiers associated with this IPI entry.
			if(count(isset($tLine[14]))){
				@$ccds_ids = $this->readIdentifiers($tLine[14]);
			}
			//RefSeq GI protein identifiers associated with this IPI entry.
			if(count(isset($tLine[15]))){
				@$refseq_gis = $this->readIdentifiers($tLine[15]);
			}
			//Supplementary Vega entries associated with this IPI entry.
			if(count(isset($tLine[16]))){
				@$vega_ids = $this->readIdentifiers($tLine[16]);
			}
			//lets make some rdf!
			if(strlen($ipi_id)){
				$ipi_res = $this->getNamespace().$ipi_id;
				if(count($sup_refseq)){
					foreach($sup_refseq as $r){
						if($r != "" && $r != "\n"){
							parent::AddRDF(
								parent::triplify($ipi_res, $this->getVoc()."x-refseq", "refseq:".$r)
							);
						}
					}
				}
			}
			if($uniprotkb_id != "" && $uniprotkb_id!= "\n"&& count($uniprotkb_id) > 1 && isset($uniprotkb_id)){
				parent::AddRDF(
					parent::triplify($ipi_res, $this->getVoc()."x-uniprot", "uniprot:".$r)
				);
			}
			if(count($sup_uniprots_sps)){
				foreach ($sup_uniprots_sps as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-uniprot", "uniprot:".$r)
						);
					}
				}
			}
			if(count($sup_uniprots_tre)){
				foreach ($sup_uniprots_tre as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-uniprot", "uniprot:".$r)
						);
					}
				}
			}
			if(count($sup_ensembl)){
				foreach ($sup_ensembl as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-ensembl", "uniprot:".$r)
						);
					}
				}
			}
			if(count($sup_tair)){
				foreach ($sup_tair as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-tair", "tair:".$r)
						);
					}
				}
			}
			if(count($sup_hinv)){
				foreach ($sup_hinv as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-hinv", "hinv:".$r)
						);
					}
				}
			}
			if(count($xref_embl_genbank_ddbj)){
				foreach ($xref_embl_genbank_ddbj as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-embl", "embl:".$r)
						);
					}
				}
			}
			if(count($hgnc_ids)){
				foreach ($hgnc_ids as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-hgnc", "hgnc:".$r)
						);
					}
				}
			}
			if(count($ncbi_ids)){
				foreach ($ncbi as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-ncbi-gene", "geneid:".$r)
						);
					}
				}
			}
			if(count($uniparc_ids)){
				foreach ($uniparc_ids as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-uniparc", "uniparc:".$r)
						);
					}
				}
			}
			if(count($unigene_ids)){
				foreach ($unigene_ids as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-unigene", "unigene:".$r)
						);
					}
				}
			}
			if(count($ccds_ids)){
				foreach ($ccds_ids as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-ccds", "ccds:".$r)
						);
					}
				}
			}
			if(count($refseq_gis)){
				foreach ($refseq_gis as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-ncbi-gene", "geneid:".$r)
						);
					}
				}
			}
			if(count($vega_ids)){
				foreach ($vega_ids as $r){
					if($r != "" && $r!= "\n"&& count($r) > 1 && isset($r)){
						parent::AddRDF(
							parent::triplify($ipi_res, $this->getVoc()."x-vega", "vega:".$r)
						);
					}
				}
			}



			
			$this->WriteRDFBufferToWriteFile();

		}//while
	}

	private function gene_xrefs(){
		echo "c";
	}

	private function gi2ipi(){
		echo "b";
	}

	private function getPackageMap(){
		return self::$packageMap;
	}//getpackagemap

	private function startsWith($haystack, $needle){
	    $length = strlen($needle);
	    return (substr($haystack, 0, $length) === $needle);
	}
	private function getFirstId($s){
		$ev = $this->evaluateSeparators($s);
		if($ev["ALL"] == 0 && $ev["SEMICOLON"]==0 && $ev["COMMA"]== 0 && $ev["COLON"] == 0){
			return trim($s);
		}
		else{
			return "";
		}
	}
	/*
	* This function checks $str for the existence of ";" and ","
	* it returns an associative array that has as key one of
	* SEMICOLON, COMMA, COLON or ALL and the respective counts
	* */
	private function evaluateSeparators($str){
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

	private function multipleExplode($delimiters = array(), $string = ''){ 
	    $mainDelim = $delimiters[count($delimiters)-1]; 
	    array_pop($delimiters); 
	    foreach($delimiters as $delimiter){ 
	        $string= str_replace($delimiter, $mainDelim, $string); 
	    } 
	    $result= explode($mainDelim, $string); 
		return $result; 
	} 

	//remove the empty elements from an array
	private function removeEmptyElements($anArray){
		$returnMe = array();
		foreach($anArray as $a){
			if(count($a) && $a != null && $a != ""){
				$returnMe[] = $a;
			}
		}
		return $returnMe;
	}

	private function readIdentifiers($str){
		$returnMe = array();
		if(count(isset($str))){		
			@$ev = $this->evaluateSeparators($str);
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
				$tmp = $this->removeEmptyElements($tmp);
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
				$tmp = $this->removeEmptyElements($tmp);
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
				$tmp = $this->multipleExplode($delims, $str);
				//remove any empty elements
				$tmp = $this->removeEmptyElements($tmp);
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

	/**
	* return an array of paths to the files with extension $ext found in $dir
	*/
	private function getFilePaths($dir, $ext){
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
}



?>