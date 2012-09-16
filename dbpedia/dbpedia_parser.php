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
 * Bio2RDF DBpedia RDFizer
 * @version 0.1
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ncbi.nih.gov/gene/DATA/
 * http://wiki.dbpedia.org/Downloads38
*/

require('../../php-lib/rdfapi.php');
class DBpedia extends RDFFactory{
	private  $bio2rdf_base = "http://bio2rdf.org/";
	private  $dbpedia_vocab ="dbpedia_vocab:";
	private  $dbpedia_resource = "dbpedia_resource:";
	private static $version = 3.8;

	private static $packageMap = array(
		"infobox_properties_en" => array(
			"filename" => "infobox_properties_en.nt",
			"file_url" => "http://downloads.dbpedia.org/3.8/en/infobox_properties_en.nt.bz2"
			)
		);

	function __construct($argv) {
			parent::__construct();
			$this->SetDefaultNamespace("dbpedia");
			// set and print application parameters
			$this->AddParameter('files',true,null,'all','','files to process');
			$this->AddParameter('indir',false,null,'/data/download/dbpedia/','directory to download into and parse from');
			$this->AddParameter('outdir',false,null,'/data/rdf/dbpedia/','directory to place rdfized files');
			$this->AddParameter('gzip',false,'true|false','true','gzip the output');
			$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
			$this->AddParameter('download',false,'true|false','false','set true to download files');
			$this->AddParameter('download_url',false,null,'http://downloads.dbpedia.org/3.8/en/infobox_properties_en.nt.bz2');
			if($this->SetParameters($argv) == FALSE) {
				$this->PrintParameters($argv);
				exit;
			}
			if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
			if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
			if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
		return TRUE;
	}//constructor

	public function Run(){
		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');

		$selectedPackage = trim($this->GetParameterValue('files'));
		if($selectedPackage == 'all'){
			$files = $this->getPackageMap();
		}else{
			echo "Invalid package selection\n";
			exit;
		}
		foreach ($files as $key => $value) {
			if(substr($ldir, -1) == "/"){
				$lfile = $ldir.$value;
			} else {
				$lfile = $ldir."/".$value;
			}

			if(!file_exists($lfile) && $this->GetParameterValue('download') == false) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
			}

			if($this->GetParameterValue('download') == true) {
				$rfile = $value["file_url"];
				echo "downloading ".var_dump($value["file_url"])." ... ";
				file_put_contents($lfile,file_get_contents($rfile));
			}

			if($key == "all"){
				$lfile = $value["filename"];
				$bzin = bzopen($ldir.$lfile, "r") or die("Could not open ".$ldir.$lfile."\n");

			}

			//ensure that there is a slash between directory name and filename
			if(substr($odir, -1) == "/"){
				$gzoutfile = $odir.$k.".ttl";
			} else {
				$gzoutfile = $odir."/".$k.".ttl";
			}
			//set the write file
			$gz=false;
			if($this->GetParameterValue('gzip')) {
				$gzoutfile .= '.gz';
				$gz = true;
			}
			$this->SetReadFile($ldir.$lfile);
			$this->GetReadFile()->SetFilePointer($fpin);
			$this->SetWriteFile($gzoutfile, $gz);
			if(!file_exists($gzoutfile)){
				if (($gzout = gzopen($gzoutfile,"a"))=== FALSE) {
					trigger_error("Unable to open $odir.$gzoutfile");
					exit;
				}
				echo "processing $fn...\n";
				//process
				$this->$k();
				$this->GetWriteFile()->Close();
				echo "done!".PHP_EOL;
			}else{
				echo "file $gzoutfile already there!\nPlease remove file and try again\n";
				exit;
			}//else

		}


	}

	private function infobox_properties_en(){
		echo "hello world\n";
	}

	public function getPackageMap(){
		return self::$packageMap;
	}//getpackagemap
}

$p = new DBpedia($argv);
$p->Run();



?>