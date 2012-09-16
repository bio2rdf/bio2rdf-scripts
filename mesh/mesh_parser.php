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
		"supplementary_records" => "c2012.bin"					
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
	private static $supplementary_concept_records = array(
		"DA" => "date-of-entry",
		"FR" =>	"frequency",
		"HM" => "heading-mapped-to",
		"II" =>	"indexing-information",
		"MR" => "major-revision-date",
		"N1" =>	"cas-type-1-name",
		"NM" =>	"name-of-substance",
		"NM_TH" => "nm-term-thesaurus-id",
		"NO" =>	"note",
		"PA" =>	"pharmacological-action",
		"PI" => "previous-indexing",
		"RECTYPE" => "record-type",
		"RN" => "cas-registry-number-or-ec-number",
		"RR" =>	"related-cas-registry-number",
		"SO" => "source",
		"ST" => "semantic-type",
		"SY" => "synonym",
		"TH" => "thesaurus-id",
		"UI" => "unique-identifier"
	);
	private  $bio2rdf_base = "http://bio2rdf.org/";
	private  $mesh_vocab ="mesh_vocabulary:";
	private  $mesh_resource = "mesh_resource:";
	private $version = 2012;
	function __construct($argv) {
			parent::__construct();
			$this->SetDefaultNamespace("mesh");
			// set and print application parameters
			$this->AddParameter('files',true,null,'all|descriptor_records|qualifier_records|supplementary_records','','files to process');
			$this->AddParameter('indir',false,null,'/data/download/mesh/','directory to download into and parse from');
			$this->AddParameter('outdir',false,null,'/data/rdf/mesh/','directory to place rdfized files');
			$this->AddParameter('gzip',false,'true|false','true','gzip the output');
			$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
			$this->AddParameter('download',false,'true|false','false','set true to download files');
			$this->AddParameter('download_url',false,null,'http://www.nlm.nih.gov/cgi/request.meshdata');
			if($this->SetParameters($argv) == FALSE) {
				$this->PrintParameters($argv);
				exit;
			}
			if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
			if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
			if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
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
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/mesh/mesh_parser.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()),
			"http://www.nlm.nih.gov/mesh/",
			array("use"),
			"http://www.ncbi.nlm.nih.gov/About/disclaimer.html",
			"http://www.nlm.nih.gov/databases/download.html",
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		return TRUE;
	}//run

	private function supplementary_records(){
		$sup_rec = "";
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^\n$/", $aLine, $matches);
			if(count($matches)){
				$dR = $this->readRecord($sup_rec);
				$this->makeSupplementaryRecord($dR);
				$sup_rec = "";
				continue;
			}
			preg_match("/\*NEWRECORD/", $aLine, $matches);
			if(count($matches) == 0){
				$sup_rec .= $aLine;
			}			
		}
	}
	private function descriptor_records(){
		$descriptor_record = "";
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^\n$/", $aLine, $matches);
			if(count($matches)){
				$dR = $this->readRecord($descriptor_record);
				$this->makeDescriptorRecord($dR);
				$descriptor_record = "";
				continue;
			}
			preg_match("/\*NEWRECORD/", $aLine, $matches);
			if(count($matches) == 0){
				$descriptor_record .= $aLine;
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
	* @$desc_record_arr is an assoc array with the contents of one qualifier record
	*/
	private function makeSupplementaryRecord($sup_record_arr){
		if(array_key_exists("NM", $sup_record_arr)){
			$tqa = $sup_record_arr["NM"][0];
		}else{
			return ;
		}
		$sr_id = md5($tqa);
		//create a resource for a mesh supplementary record and type it as such
		$this->AddRDF($this->QQuad("mesh:".$sr_id, 
				"rdf:type", 
				"mesh_vocabulary:supplementary_record"
				));
		//add the lablel
		$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
				"rdfs:label",
				"$tqa [mesh:".$sr_id."]"
				));
		$this->AddRDF($this->QQuad("mesh:".$sr_id, "void:inDataset", $this->getDatasetURI()));
		foreach($sup_record_arr as $k => $v){
			if(array_key_exists($k, $this->getSupplementaryConceptRecords())){
				//date of entry
				if($k == "DA"){
					$date = date_parse($v[0]);
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["DA"], 
						$date["month"]."-".$date["day"]."-".$date["year"],
						null,
						"xsd:date"
					));
				}//if
				//frequency
				if($k == "FR"){
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["FR"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//heading mapped to
				if($k == "HM"){
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["HM"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//indexing information
				if($k == "II"){
					$x = $this->getSupplementaryConceptRecords();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
							"mesh_vocabulary:".$x["II"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//major revision date
				if($k == "MR"){
					$date = date_parse($v[0]);
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["MR"], 
						$date["month"]."-".$date["day"]."-".$date["year"],
						null,
						"xsd:date"
					));
				}//if
				//CAS TYPE 1 NAME
				if($k == "N1"){
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["N1"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//name of substance
				if($k == "NM_TH"){
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["NM_TH"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//note
				if($k == "NO"){
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["NO"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//pharmacological action
				if($k == "PA"){
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["PA"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//previous index
				if($k == "PI"){
					$x = $this->getSupplementaryConceptRecords();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
							"mesh_vocabulary:".$x["PI"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//cas registry number/ ec number
				if($k == "RN"){
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["RN"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//related cas registry number
				if($k == "RR"){
					$x = $this->getSupplementaryConceptRecords();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
							"mesh_vocabulary:".$x["RR"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//source
				if($k == "SO"){
					$x = $this->getSupplementaryConceptRecords();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
							"mesh_vocabulary:".$x["SO"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//synonym
				if($k == "SY"){
					$x = $this->getSupplementaryConceptRecords();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
							"mesh_vocabulary:".$x["SY"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//semantic type
				if($k == "ST"){
					$x = $this->getSupplementaryConceptRecords();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
							"mesh_vocabulary:".$x["ST"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//unique identifier
				if($k == "UI"){
					$x = $this->getSupplementaryConceptRecords();
					$this->AddRDF($this->QQuadL("mesh:".$sr_id, 
						"mesh_vocabulary:".$x["UI"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
			}//if
			$this->WriteRDFBufferToWriteFile();
		}//foreach
	}
	/**
	* add an RDF representation of the incoming param to the model.
	* @$desc_record_arr is an assoc array with the contents of one qualifier record
	*/
	private function makeDescriptorRecord($desc_record_arr){
		
		//I will use the mesh heading as the 
		//seed of the md5 hash for the uri
		//see: http://www.nlm.nih.gov/mesh/dtype.html
		if(array_key_exists("MH", $desc_record_arr)){
			$tqa = $desc_record_arr["MH"][0];
		}else{
			return ;
		}
		$dr_id = md5($tqa);
	
		//create a resource for a mesh descriptor record and type it as such
		$this->AddRDF($this->QQuad("mesh:".$dr_id, 
				"rdf:type", 
				"mesh_vocabulary:descriptor_record"
				));

		//add the lablel
		$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
				"rdfs:label",
				"$tqa [mesh:".$dr_id."]"
				));
		$this->AddRDF($this->QQuad("mesh:".$dr_id, "void:inDataset", $this->getDatasetURI()));

		//iterate over the remaining properties
		foreach($desc_record_arr as $k =>$v){
			if(array_key_exists($k, $this->getDescriptorDataElements())){
				//add annotations
				if($k == "AN"){
					$x = $this->getDescriptorDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
								"mesh_vocabulary:".$x["AN"],
								utf8_encode(htmlspecialchars($vv))
								));
					}//foreach
				}//if
				//add allowable topical qualifiers
				if($k == "AQ"){
					$x = $this->getDescriptorDataElements();
					foreach($v as $kv => $vv){
						$vvrar = explode(" ", $vv);
						foreach($vvrar as $aq){
							$this->AddRDF($this->QQuad("mesh:".$dr_id, 
									"mesh_vocabulary:".$x["AQ"],
									"mesh:".md5(trim($aq))
									));
						}//foreach
					}//foreach
				}//if
				//add CATALOGING SUBHEADINGS LIST NAME
				if($k == "CATSH"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["CATSH"], 
						utf8_encode(htmlspecialchars($v[0]))
					));					
				}//if
				//add CONSIDER ALSO XREF
				if($k == "CX"){
					$x = $this->getDescriptorDataElements();
					$tmp = explode("consider also terms at", $v[0]);
					$gmp = $tmp[1];
					$gmpa = explode(",",$gmp);
					foreach($gmpa as $g){
						$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
							"mesh_vocabulary:".$x["CX"], 
							$g
						));	
					}				
				}//if
				//add date of entry
				if($k == "DA"){
					$date = date_parse($v[0]);
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["DA"], 
						$date["month"]."-".$date["day"]."-".$date["year"],
						null,
						"xsd:date"
					));
				}//if
				//descriptor class
				if($k == "DC"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["DC"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//descriptor entry version
				if($k == "DE"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["DE"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//descriptor sort version
				if($k == "DS"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["DS"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if

				//date major descriptor established 
				if($k == "DX"){
					$date = date_parse($v[0]);
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["DX"], 
						$date["month"]."-".$date["day"]."-".$date["year"],
						null,
						"xsd:date"
					));
				}//if

				//entry combination
				if($k == "EC"){
					$x = $this->getDescriptorDataElements();
					foreach ($v as $kv => $vv){
						$y = explode(":", $vv);
						if(count($y) == 2 || count($y) == 3){
							$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
								"mesh_vocabulary:".$x["EC"], 
								md5($y[1])
							));
						}
					}
				}//if
				//print entry
				if($k == "PRINT ENTRY"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["PRINT ENTRY"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//entry
				if($k == "ENTRY"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["ENTRY"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//forward cross reference
				if($k == "FX"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuad("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["FX"], 
						"mesh:".md5($v[0])
					));
				}//if
				//grateful med note
				if($k == "GM"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["GM"], 
						$v[0]
					));
				}//if
				//history note
				if($k == "HN"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["HN"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//backfile postings
				//history note
				if($k == "MED" || $k == "M94" 
					|| $k == "M90" || $k == "M85" 
					|| $k == "M80" || $k == "M75" 
					|| $k == "M66"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["MED"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//mesh heading 
				if($k == "HN"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["HN"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//mesh heading thesaurus id
				if($k == "MH_TH"){
					$x = $this->getDescriptorDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
							"mesh_vocabulary:".$x["MH_TH"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//mesh tree number
				if($k == "MN"){
					$x = $this->getDescriptorDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
							"mesh_vocabulary:".$x["MN"], 
							$vv
						));
					}
				}//if
				//major revision date
				if($k == "MR"){
					$date = date_parse($v[0]);
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["MR"], 
						$date["month"]."-".$date["day"]."-".$date["year"],
						null,
						"xsd:date"
					));
				}//if
				//mesh scope note
				if($k == "MS"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["MS"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//CAS TYPE 1 NAME
				if($k == "N1"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["N1"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//online note
				if($k == "OL"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["OL"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//pharmacological action
				if($k == "PA"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["PA"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//previous index
				if($k == "PI"){
					$x = $this->getDescriptorDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
							"mesh_vocabulary:".$x["PI"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//public mesh note
				if($k == "PM"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["PM"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//pre explosion
				if($k == "PX"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["PX"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//running head, mesh tree structures
				if($k == "RH"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["RH"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//cas registry number/ ec number
				if($k == "RN"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["RN"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//related cas registry number
				if($k == "RR"){
					$x = $this->getDescriptorDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
							"mesh_vocabulary:".$x["RR"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//semantic type
				if($k == "ST"){
					$x = $this->getDescriptorDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
							"mesh_vocabulary:".$x["ST"], 
							utf8_encode(htmlspecialchars($vv))
						));
					}
				}//if
				//unique identifier
				if($k == "UI"){
					$x = $this->getDescriptorDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$dr_id, 
						"mesh_vocabulary:".$x["UI"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
			}//if
			$this->WriteRDFBufferToWriteFile();
		}//foreach
	}
	/**
	* add an RDF representation of the incoming param to the model.
	* @$qual_record_arr is an assoc array with the contents of one qualifier record
	*/
	private function makeQualifierRecordRDF($qual_record_arr){
		//I will use the topical qualifier abbreviation as the 
		//seed of the md5 hash for the uri
		//see: http://www.nlm.nih.gov/mesh/qtype.html
		if(array_key_exists("QA", $qual_record_arr)){
			$tqa = $qual_record_arr["QA"][0];
		}else{
			return ;
		}
		$qr_id = md5($tqa);
		//create a resource for a mesh qualifier record and type it as such
		$this->AddRDF($this->QQuad("mesh:".$qr_id, 
				"rdf:type", 
				"mesh_vocabulary:qualifier_record"
				));
		$this->AddRDF($this->QQuad("mesh:".$qr_id, "void:inDataset", $this->getDatasetURI()));

		//iterate over the remaining properties
		foreach($qual_record_arr as $k => $v){
			if(array_key_exists($k, $this->getQualifierDataElements())){
				//add allowed tree nodes
				if($k == "TN"){
					$x = $this->getQualifierDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
							"mesh_vocabulary:".$x["TN"], 
							$vv
						));
					}//foreach
				}//if
				//add qualifier cross reference
				if($k == "QX"){
					$x = $this->getQualifierDataElements();
					foreach($v as $kv => $vv){
						$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
							"mesh_vocabulary:".$x["QX"], 
							$vv
						));
					}//foreach
				}//if
				//add qualifier sort version
				if($k == "QS"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["QS"], 
						$v[0]
					));
				}//if
				//add online note
				if($k == "ON"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["ON"], 
						$v[0]
					));
				}//if
				//add major revision date 
				if($k == "MR"){
					$date = date_parse($v[0]);
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["MR"], 
						$date["month"]."-".$date["day"]."-".$date["year"],
						null,
						"xsd:date"
					));
				}//if
				//add backfile postings
				if($k == "MED" || $k == "M94" || $k == "M90" || $k == "M85" || $k == "M80" || $k == "75" || $k == "M66"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["MED"], 
						$v[0]));
				}
				//add history note
				if($k == "HN"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["HN"], 
						$v[0]
					));
				}//if
				//add date qualifier established
				if($k == "DQ"){
					$date = date_parse($v[0]);
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["DQ"], 
						$date["month"]."-".$date["day"]."-".$date["year"],
						null,
						"xsd:date"
					));
				}//if
				//add date of entry
				if($k == "DA"){
					$date = date_parse($v[0]);
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["DA"], 
						$date["month"]."-".$date["day"]."-".$date["year"],
						null,
						"xsd:date"
					));
				}//if
				//add annotation
				if($k == "AN"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["AN"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//add qualifier type
				if($k == "QT"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["QT"], 
						$v[0]
					));
				}//if
				//Add  topical qualifier abbreviation
				if($k == "QA"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["QA"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//add mesh scope note
				if($k == "MS"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["MS"], 
						utf8_encode(htmlspecialchars($v[0]))
					));
				}//if
				//add the label
				if($k == "SH"){
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"rdfs:label", 
						$v[0]." [mesh:".$qr_id."]"
					));
				}//if
				//add unique identifier
				//add qualifier entry version
				if($k == "UI"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["UI"], 
						$v[0]
					));
				}
				//add qualifier entry version
				if($k == "QE"){
					$x = $this->getQualifierDataElements();
					$this->AddRDF($this->QQuadL("mesh:".$qr_id, 
						"mesh_vocabulary:".$x["QE"], 
						utf8_encode(htmlspecialchars($v[0]))
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

	public function getSupplementaryConceptRecords(){
		return self::$supplementary_concept_records;
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
