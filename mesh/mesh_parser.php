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
	private static $descriptor_data_elements = array(
		"AN" =>	"annotation",
		"AQ" =>	"allowable-topical-qualifiers",
		"CATSH" => "cataloging-subheadings-list-name",
		"CX"	=> "consider-also-xref",
		"DA"	=>	"date-of-entry",
		"DC"	=> "descriptor-class",
		"DE"	=> "descriptor-entry-version",
		"DS"	=>	"descriptor-sort-version",	
		"DX"	=>	"date-major-descriptor-established",
		"EC"	=>	"entry-combination",
		"PRINT ENTRY"	=> "entry-term",
		"ENTRY"	=> "entry-term",
		"FX" =>	"forward-xref",
		"GM" =>	"grateful-med-note",
		"HN" => "history-note",
		"MED" => "backfile-posting",
		"MH" =>	"mesh-heading",
		"MH_TH" =>	"mesh-heading-thesaurus-id",
		"MN" =>	"mesh-tree-number",
		"MR" =>	"major-revision-date",
		"MS" =>	"mesh-scope-note",
		"N1" => "cas-type-1-name",
		"OL" =>	"online-note",
		"PA" =>	"pharmacological-action",
		"PI" =>	"previous-indexing", 
		"PM" =>	"public-mesh-note",
		"PX" =>	"pre-explosion",
		"RECTYPE" =>	"record-type",
		"RH" =>	"running-head",
		"RN" => "cas-registry-number-or-ec-number",
		"RR" =>	"related-cas-registry-number",
		"ST" =>	"semantic-type",
		"UI" =>	"unique-identifier"
	);

	private static $qualifier_data_elements = array(
		"AN"=> "annotation",
		"DA"=> "date-of-entry",
		"DQ"=> "date-qualifier-established",
		"GM"=>"grateful-med-note",
		"HN"=>"histrory-note",
		"MR"=>"major-revision-date",
		"MS"=> "scope-note",
		"OL"=> "online-note",
		"QA"=> "topical-qualifier-abbreviation",
		"QA"=>	"topical-qualifier-abbreviation",
		"QE"=>	"qualifier-entry-version",
		"QS"=>	"qualifier-sort-version",
		"QT"=>	"qualifier-type",
		"QX"=>	"qualifier-cross-reference",
		"RECTYPE"	=>"record-type",
		"SH"=>	"subheading",
		"TN"=>	"tree-node-allowed",
		"UI"=>	"unique-identifier",
		"MED" => "backfile-posting"

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
				$this->GetReadFile()->Close();
				$this->GetWriteFile()->Close();
			}else{
				echo "file $gzoutfile already there!\nPlease remove file and try again\n";
				exit;
			}

		}//foreach
		return TRUE;
	}//run

	private function descriptor_records(){
		$descriptor_record = "";
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^\n$/", $aLine, $matches);
			if(count($matches)){
				$dR = $this->readRecord($qualifier_record);
				$this->makeDescriptorRecord($dR);
				exit;
				$qualifier_record = "";
				continue;
			}
			preg_match("/\*NEWRECORD/", $aLine, $matches);
			if(count($matches) == 0){
				$qualifier_record .= $aLine;
			}			
		}
	}

	private function qualifier_records(){
		$qualifier_record = "";
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^\n$/", $aLine, $matches);
			if(count($matches)){
				$qR = $this->readRecord($qualifier_record);
				$this->makeQualifierRecordRDF($qR);
				$qualifier_record = "";
				continue;
			}
			preg_match("/\*NEWRECORD/", $aLine, $matches);
			if(count($matches) == 0){
				$qualifier_record .= $aLine;
			}			
		}
	}
	/**
	* add an RDF representation of the incoming param to the model.
	* @$qual_record_arr is an assoc array with the contents of one qualifier record
	*/
	private function makeDescriptorRecord($desc_record_arr){
		//First make sure that there is a unique identifer
		$q_ui = "";
		//first get the qualifier record unique identifier
		if(array_key_exists("UI", $desc_record_arr)){
			$q_ui = $desc_record_arr["UI"][0];
			unset($desc_record_arr["UI"]);
		}else{
			return ;
		}
		//create a resource for a mesh qualifier record and type it as such
		$this->AddRDF($this->QQuad("mesh:".$q_ui, 
				"rdf:type", 
				"mesh_vocabulary:descriptor_record"
				));
		//iterate over the remaining properties
		foreach($desc_record_arr as $k =>$v){
			if(array_key_exists($k, $this->getDescriptorDataElements())){
				
			}//if
			$this->WriteRDFBufferToWriteFile();
		}//foreach
	}
	/**
	* add an RDF representation of the incoming param to the model.
	* @$qual_record_arr is an assoc array with the contents of one qualifier record
	*/
	private function makeQualifierRecordRDF($qual_record_arr){
		//First make sure that there is a unique identifer
		$q_ui = "";
		//first get the qualifier record unique identifier
		if(array_key_exists("UI", $qual_record_arr)){
			$q_ui = $qual_record_arr["UI"][0];
			unset($qual_record_arr["UI"]);
		}else{
			return ;
		}
		//create a resource for a mesh qualifier record and type it as such
		$this->AddRDF($this->QQuad("mesh:".$q_ui, 
				"rdf:type", 
				"mesh_vocabulary:qualifier_record"
				));
		//iterate over the remaining properties
		foreach($qual_record_arr as $k => $v){
			if(array_key_exists($k, $this->getQualifierDataElements())){
				//add allowed tree nodes
				if($k == "TN"){
					$x = $this->getQualifierDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
							"mesh_vocabulary:".$x["TN"], 
							$vv
						));
					}//foreach
				}//if
				//add qualifier cross reference
				if($k == "QX"){
					$x = $this->getQualifierDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
							"mesh_vocabulary:".$x["QX"], 
							$vv
						));
					}//foreach
				}//if
				//add qualifier sort version
				if($k == "QS"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["QS"], 
						$v[0]
					));
				}//if
				//add online note
				if($k == "ON"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["ON"], 
						$v[0]
					));
				}//if
				//add major revision date 
				if($k == "MR"){
					$date = date_parse($v[0]);
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["MR"], 
						$date["month"]."-".$date["day"]."-".$date["year"]
					));
				}//if
				//add backfile postings
				if($k == "MED" || $k == "M94" || $k == "M90" || $k == "M85" || $k == "M80" || $k == "75" || $k == "M66"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["MED"], 
						$v[0]));
				}
				//add history note
				if($k == "HN"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["HN"], 
						$v[0]
					));
				}//if
				//add date qualifier established
				if($k == "DQ"){
					$date = date_parse($v[0]);
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["DQ"], 
						$date["month"]."-".$date["day"]."-".$date["year"]
					));
				}//if
				//add date of entry
				if($k == "DA"){
					$date = date_parse($v[0]);
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["DA"], 
						$date["month"]."-".$date["day"]."-".$date["year"]
					));
				}//if
				//add annotation
				if($k == "AN"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["AN"], 
						$v[0]
					));
				}//if
				//add qualifier type
				if($k == "QT"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["QT"], 
						$v[0]
					));
				}//if
				//Add  topical qualifier abbreviation
				if($k == "QA"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["QA"], 
						$v[0]
					));
				}//if
				//add mesh scope note
				if($k == "MS"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["MS"], 
						$v[0]
					));
				}//if
				//add the label
				if($k == "SH"){
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"rdfs:label", 
						$v[0]." [mesh:".$q_ui."]"
					));
				}//if
				//add qualifier entry version
				if($k == "QE"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$q_ui, 
						"mesh_vocabulary:".$x["QE"], 
						$v[0]
					));
				}
			}//if
			$this->WriteRDFBufferToWriteFile();
		}//foreach

	}//makeQualifierRecord


	/**
	* Return an assoc array with the contents of the qualifier record
	*/
	private function readRecord($aRecord){
		$returnMe = array();
		$recArr = explode("\n", $aRecord);
		foreach($recArr as $ar){
			$al = explode(" = ", $ar);
			if(count($al) == 2){
				if(!array_key_exists($al[0], $returnMe)){
					$returnMe[$al[0]] = array($al[1]);
				}else{
					$b = $returnMe[$al[0]];
					$returnMe[$al[0]] = $b;
					$returnMe[$al[0]][] = $al[1];
				}
			}
			
		}
		return $returnMe;
	}

	public function getPackageMap(){
		return self::$packageMap;
	}

	public function getQualifierDataElements(){
		return self::$qualifier_data_elements;
	}

	public function getDescriptorDataElements(){
		return self::$descriptor_data_elements;
	}
}

$p = new MeshParser($argv);
$p->Run();
?>