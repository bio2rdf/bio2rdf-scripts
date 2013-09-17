<?php
/**
Copyright (C) 2013 Jose Cruz-Toledo

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
* Bio2RDF PDB PHP rdfizer
* Uses pdb2rdf.sh
* @version 1.0
* @author Jose Cruz-Toledo
**/

class PDBParser extends Bio2RDFizer{
	function __construct($argv){
		parent::__construct($argv, "pdb");
		parent::addParameter('files',true,'all','all','files to process');
		parent::initialize();
	}

	function Run(){
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		//check dependencies
		$d = $this->checkDependencies();
		if($d){
			//build pdb2rdf
			$cmd = "mvn clean install -DskipTests -f ".__DIR__."/pom.xml";
			$build_out = shell_exec($cmd);
			$out_ver = $this->verifyMavenBuildOutput($build_out);
			if($out_ver){
				//now check if download is desired
				if($this->getParameterValue('download')){
					if(!$this->downloadFiles($ldir)){
						trigger_error("Not all files downloaded!", E_USER_WARNING);
					}
				}
				//extract pdb2rdf-cli from the target directory
				if(!$this->extractCli()){
					trigger_error("Could not extract pdb2rdf!", E_USER_ERROR);
				}
				//now get ready to run pdb2rdf.sh
				
			}else{
				trigger_error("Could not build pdb2rdf. Please try manually!", E_USER_ERROR);
			}
			
		}else{
			trigger_error("Dependencies not met!", E_USER_ERROR);
			exit;
		}
	}

	private function extractCli(){
		//first find the filename and version of the cli
		$dir = __DIR__."/pdb2rdf-cli/target";
		$dh = opendir($dir);
		$version = null;
		while(false !== ($fn = readdir($dh))) {
			$pattern = "/pdb2rdf-cli-(.*)-bin.zip/";
			preg_match($pattern, $fn, $matches);
			if(count($matches)){
				$version = $matches[1];
			}
		}
		if($version != null){
			$d = __DIR__."/pdb2rdf-cli/target/pdb2rdf-cli-".$version."-bin.zip";
			$zip = new ZipArchive;
			$res = $zip->open($d);
			if($res === TRUE){
				$zip->extractTo(__DIR__."/pdb2rdf-cli/target/");
				$zip->close();
				chmod(__DIR__."/pdb2rdf-cli/target/pdb2rdf-cli-".$version."/pdb2rdf.sh", 0777);
				echo 'extracted pdb2rdf-cli'.PHP_EOL;
				return true;
			}else{
				trigger_error("Could not extract pdb2rdf-cli", E_USER_ERROR);
				exit;
			}
		}		
		return false;
	}

	private function downloadFiles($destinationPath){
		$rm = false;
		//check if destination path is directory
		if(is_dir($destinationPath)){
			//download the files
			echo "Starting to sync files with PDB...";
			$cmd = "rsync -rlpt -v -z --delete --port=33444 rsync.wwpdb.org::ftp_data/structures/divided/XML/ ".$destinationPath." >/dev/null";
			exec($cmd);
			$rm = true;
		}else{
			trigger_error("Invalid local directory!", E_USER_ERROR);
		}
		return $rm;
	}

	private function verifyMavenBuildOutput($anOutput){
		//check if output contains  the string:
		// BUILD SUCCESSFUL
		$o = explode("\n", $anOutput);
		foreach ($o as $aLine) {
			preg_match("/BUILD\sSUCCESSFUL/", $aLine, $matches);
			if(count($matches)){
				return true;
			}
		}
		return false;
	}

	private function checkDependencies(){
		$java =  shell_exec("which java");
		$maven = shell_exec("which mvn");
		if(!strlen($java)){
			trigger_error("JAVA not found!", E_USER_ERROR);
			return false;
		}
		if(!strlen($maven)){
			trigger_error("Maven not found", E_USER_ERROR);
			return false;
		}
		return true;
	}
}
?>
