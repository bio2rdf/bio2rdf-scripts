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
* NCBI RefSeq Parser
* @version 1.0
* @author Jose Cruz-Toledo
* @description 
*/

class RefSeqParser extends Bio2RDFizer{
	function __construct($argv){
		parent::__construct($argv, "refseq");
		parent::addParameter('files', true, 'all', 'all', 'files to process');
		parent::addParameter('download_url',false,null,'ftp://ftp.ncbi.nlm.nih.gov/refseq/release/complete/');
		parent::initialize();
	}//construct

	function Run(){
		$dataset_description = '';
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		//download
		if($this->GetParameterValue('download') == true){
			$list = $this->getFtpFileList('ftp.ncbi.nlm.nih.gov', '/refseq/release/complete/','seq.gz');
			$total = count($list);
			$counter = 1;
			foreach($list as $f){
				echo "downloading file $counter out of $total :".parent::getParameterValue('download_url').$f."... ".PHP_EOL;
				file_put_contents($ldir.$f,file_get_contents(parent::getParameterValue('download_url').$f));
				$counter++;
			}
		}//if download
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
				->setFormat('text/refseq-format')
				->setFormat('application/zip')
				->setPublisher('https://www.ncbi.nlm.nih.gov')
				->setHomepage('https://www.ncbi.nlm.nih.gov/refseq')
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
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/refseq/refseq.php")
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
		}//for

	}//run


	function process(){
		$refseq_record_str = "";
		while($aLine = $this->getReadFile()->Read(4096)){
			 preg_match("/^\/\/$/", $aLine, $matches);
		    if(count($matches)){
		    	//now remove the header if it is there
		    	
		    	$gb_record_str = $this->removeHeader($gb_record_str);
		    	$sectionsRaw = $this->parseGenbankRaw($gb_record_str);
		    	print_r($gb_record_str);
		    	print_r($sectionsRaw);

		   	}
		   	preg_match("/^\n$/", $aLine, $matches);
		    if(count($matches) == 0){
		    	$gb_record_str .= $aLine;
		    }
		}//while
	}//process


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
	* located inside a given path
	*/
	function getFtpFileList($ftp_uri, $path,  $extension){
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
		$contents = ftp_nlist($conn_id, $path);
		foreach($contents as $aFile){
			$reg_exp = "/.*\/(.*".$extension.")/";
			preg_match($reg_exp, $aFile, $matches);
			if(count($matches)){
				$rm[] = $matches[1];
			}
		}
		return $rm;
	}	
}//class


?>
