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
 * MeSH Gene RDFizer
 * @version 0.1
 * @author Jose Cruz-Toledo
 * @author Jose Miguel Vives
 * @description ftp://ftp.ncbi.nih.gov/gene/DATA/
*/


require('../../php-lib/rdfapi.php');
class MeshParser extends RDFFactory{
	private static $packageMap = array(
			"descriptor_records" => "d2012.bin",
			"qualifier_records" => "q2012.bin",
			"supplentary_records" => "c2012.bin"					
		);

	private static $qualifier_data_elements = array(
		"AN"=>"has_annotation";
		"DA"=> "has_date_of_entry",
		"DQ"=> "has_date_qualifier_established",
		"GM"=>"has_grateful_med_note",
		"HN"=>"has_histrory_note",
		"MR"=>"has_major_revision_date",
		"MS"=> "has_mesh_scope_note",
		"OL"=> "has_online_note",
		"QA"=> "has_topical_qualifier_abbreviation",
		"QA"=>	"has_topical_qualifier_abbreviation",
		"QE"=>	"has_qualifier_entry_version",
		"QS"=>	"has_qualifier_sort_version",
		"QT"=>	"has_qualifier_type",
		"QX"=>	"has_qualifier_cross_reference",
		"RECTYPE"	=>"has_record_type",
		"SH"=>	"has_subheading",
		"TN"=>	"has_tree_node_allowed",
		"UI"=>	"has_unique_identifier"

	);

	private  $bio2rdf_base = "http://bio2rdf.org/";
	private  $mesh_vocab ="mesh_vocabulary:";
	private  $mesh_resource = "mesh_resource:";
	function __construct($argv) {
			parent::__construct();
			// set and print application parameters
			$this->AddParameter('files',true,'all|descriptor_records|qualifier_records|supplentary_records','','files to process');
			$this->AddParameter('indir',false,null,'/home/jose/tmp/mesh/','directory to download into and parse from');
			$this->AddParameter('outdir',false,null,'/home/jose/tmp/mesh/n3','directory to place rdfized files');
			$this->AddParameter('gzip',false,'true|false','true','gzip the output');
			$this->AddParameter('download',false,'true|false','false','set true to download files');
			$this->AddParameter('download_url',false,null,'http://www.nlm.nih.gov/cgi/request.meshdata');
			if($this->SetParameters($argv) == FALSE) {
				$this->PrintParameters($argv);
				exit;
			}
			if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
			if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
			$this->SetReleaseFileURI("mesh");
		return TRUE;
	  }//constructor

	  function Run(){
	  	$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		//which files are to be converted?
		$selectedPackage = trim($this->GetParameterValue('files'));		 
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
	  
	  //now iterate over the files array
		foreach ($files as $k => $aFile){	
			//ensure that there is a slash between directory name and filename
			if(substr($ldir, -1) == "/"){
				$lfile = $ldir.$aFile;
			} else {
				$lfile = $ldir."/".$aFile;
			}
			//create a file pointer
			$fp = gzopen($lfile, "r") or die("Could not open file ".$aFile."!\n");

			//ensure that there is a slash between directory name and filename
			if(substr($odir, -1) == "/"){
				$gzoutfile = $odir.$k.".ttl";
			} else {
				$gzoutfile = $odir."/".$k.".ttl";
			}
			$gz=false;

			if($this->GetParameterValue('gzip')){
				$gzoutfile .= '.gz';
				$gz = true;
			}

			$this->SetReadFile($lfile);
			$this->GetReadFile()->SetFilePointer($fp);
			$this->SetWriteFile($gzoutfile, $gz);
			//first check if the file is there
			if(!file_exists($gzoutfile)){
				if (($gzout = gzopen($gzoutfile,"a"))=== FALSE) {
					trigger_error("Unable to open $odir.$gzoutfile");
					exit;
				}
				echo "processing $aFile... ";

				$this->$k();

				echo "done!\n";
			}else{
				echo "file $gzoutfile already there!\nPlease remove file and try again\n";
				exit;
			}

		}//foreach
		$this->GetWriteFile()->Close();		
		return TRUE;
	}//run


	private function qualifier_records(){
		$qualifier_record = "";
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^\n$/", $aLine, $matches);
			if(count($matches)){
				echo $qualifier_record;
				$qualifier_record = "";
				continue;
			}
			preg_match("/\*NEWRECORD/", $aLine, $matches);
			if(count($matches) == 0){
				$qualifier_record .= $aLine;
			}			
		}
	}

	public function getPackageMap(){
		return self::$packageMap;
	}

	public function getQualifierDataElements(){
		return self::$qualifier_data_elements;
	}
}

$p = new MeshParser($argv);
$p->Run();
?>