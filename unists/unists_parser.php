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
 * UniSTS RDFizer
 * @version 0.1
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ncbi.nih.gov/repository/UniSTS/README
*/
require("../../php-lib/rdfapi.php");
class UniSTSParser extends RDFFactory{
	private $bio2rdf_base = "http://bio2rdf.org/";
	private $unists_vocab = "unists_vocabulary:";
	private $unists_resource = "unists_resource:";
	private $version = 0.1;

	private static $packageMap = array(
		"markers" =>  "UniSTS.sts",
		"map_reports" => "ftp.ncbi.nlm.nih.gov/repository/UniSTS/UniSTS_MapReports/",
		"pcr_reports" => "ftp.ncbi.nih.gov/repository/UniSTS/UniSTS_ePCR.Reports/"
	);

	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("unists");
		// set and print application parameters
		$this->AddParameter('files',true,null,'all|markers|map_reports|pcr_reports','','files to process');
		$this->AddParameter('indir',false,null,'/home/jose/tmp/unists/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/home/jose/tmp/n3/unists/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'ftp://ftp.ncbi.nih.gov/repository/UniSTS/');
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

		foreach ($files as $key => $value) {
			if($key == "markers"){
				//ensure that there is a slash between directory name and filename
				if(substr($ldir, -1) == "/"){
					$lfile = $ldir.$value;
				} else {
					$lfile = $ldir."/".$value;
				}
				//create a file pointer
				$fp = gzopen($lfile, "r") or die("Could not open file ".$value."!\n");
				//ensure that there is a slash between directory name and filename
				if(substr($odir, -1) == "/"){
					$gzoutfile = $odir.$key.".ttl";
				} else {
					$gzoutfile = $odir."/".$key.".ttl";
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
					echo "processing $value... ";
					$this->$key();
					echo "done!\n";
					$this->GetReadFile()->Close();
					$this->GetWriteFile()->Close();
				}else{
					echo "file $gzoutfile already there!\nPlease remove file and try again\n";
					exit;
				}
			}//if key == markers
			elseif($key == "map_reports"){
				//ensure that there is a slash between directory name and filename
				if(substr($ldir, -1) == "/"){
					$lfile = $ldir.$value;
				} else {
					$lfile = $ldir."/".$value;
				}
				//get a list of files to process
				$f_arr = $this->getFileR($lfile);
				foreach ($f_arr as $k => $v) {
					$q = rand();
					//create a file pointer
					$fp = gzopen($v, "r") or die ("Could not open file ".$v."!\n");
					//get the name of the species
					$pi = pathinfo($v);
					$species_name_dir = utf8_encode(htmlspecialchars((substr(strrchr($pi['dirname'], "/"),1))));
					$fn = $pi['filename'];
					//remove the extension
					$fnt = explode(".txt", $fn);
					$fn_no_ext = $fnt[0];
					//ignore readme files
					if($fn_no_ext != "README" && $fn_no_ext != "README~" ){
						//echo $species_name_dir."\t".$fn_no_ext."\n";
						//ensure that there is a slash between directory name and filename
						if(substr($odir, -1) == "/"){
							$gzoutfile = $odir.$species_name_dir.$q."-".$fn_no_ext.".ttl";
						} else {
							$gzoutfile = $odir."/".$species_name_dir.$q."-".$fn_no_ext.".ttl";
						}
						$gz=false;
						if($this->GetParameterValue('gzip')){
							$gzoutfile .= '.gz';
							$gz = true;
						}
						$this->SetReadFile($lfile);
						$this->GetReadFile()->SetFilePointer($fp);
						$this->SetWriteFile($gzoutfile, $gz);
						if(!file_exists($gzoutfile)){
							if (($gzout = gzopen($gzoutfile,"a"))=== FALSE) {
								trigger_error("Unable to open $odir.$gzoutfile");
								exit;
							}
							echo "processing $v... ";
							$this->map_reports();
							echo "done!\n";
							$this->GetReadFile()->Close();
							$this->GetWriteFile()->Close();
						}else{
							echo "file $gzoutfile already there!\nPlease remove file and try again\n";
							exit;
						}
					}
					
				}

			}
		}
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/unists/unists_parser.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()),
			"http://www.nlm.nih.gov/unists/",
			array("use"),
			"http://www.ncbi.nlm.nih.gov/About/disclaimer.html",
			"ftp://ftp.ncbi.nih.gov/repository/UniSTS/",
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		return TRUE;
	  }//run

	  private function map_reports(){
	  	$map_metadata = "";
	  	$map_complete_record = array();
	  	while($aLine = $this->GetReadFile()->Read(200000)){
	  		//check if the line starts with #
	  		preg_match("/^#.*$/", $aLine, $matches);
	  		if(count($matches)){
	  			$m = explode("#", $matches[0]);
	  			$map_metadata .= $m[1]."\n";
	  		}else{
	  			$map_record_row = $this->parseMapRecordRow($aLine);
	  			if($map_record_row != null){
	  				$map_complete_record[] = $map_record_row;
	  			}
	  		}
	  	}//while
	  	$map_record_metadata = $this->parseMapRecordMetadata($map_metadata);
	  	$this->processMapRecord($map_record_metadata, $map_complete_record);
	  	
	  }

	  private function processMapRecord($record_metadata, $map_record_rows){
	  	$r = rand();
		//create a resource for the map record <. .>
		$this->AddRDF($this->QQuad(
			"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"]),
			"rdf:type",
			"unists_vocabulary:map_report"
		));
		//add label
		$this->AddRDF($this->QQuadL(
				"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"]),
				"rdfs:label",
				utf8_encode($record_metadata["Map_Title"])
			));
		//add name
		$this->AddRDF($this->QQuadL(
				"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"]),
				"unists_vocabulary:has_name",
				utf8_encode($record_metadata["Map_Name"])
			));
		//add map type
		$this->AddRDF($this->QQuadL(
				"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"]),
				"unists_vocabulary:has_type",
				utf8_encode($record_metadata["Map_Type"])
			));
		//add source
		$this->AddRDF($this->QQuadL(
				"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"]),
				"unists_vocabulary:has_source",
				utf8_encode($record_metadata["Data_Source"])
			));
		//add taxon
		$this->AddRDF($this->QQuad(
				"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"]),
				"unists_vocabulary:has_taxid",
				"taxon:".utf8_encode($record_metadata["Taxonomic_ID"])
			));
		//add map units
		$this->AddRDF($this->QQuadL(
				"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"]),
				"unists_vocabulary:has_map_units",
				utf8_encode($record_metadata["Map_Units"])
			));
		//link to pmid
		if(isset($record_metadata["PMID"])){
			$this->AddRDF($this->QQuad(
					"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"]),
					"unists_vocabulary:has_publication",
					"pubmed:".$record_metadata["PMID"]
				));
		}
		
		

		//iterate over the map record rows and link them to the map report
		foreach ($map_record_rows as $key => $value) {			
				//create an sts mapping with the info on this row
				$this->AddRDF($this->QQuad(
					"unists_resource:".md5($value["unists_id"].$value["marker_name"].$value["chromosome"].$value["coordinate"]),
					"rdf:type",
					"unists_vocabulary:sts_mapping"
				));
				//add label
				$this->AddRDF($this->QQuadL(
					"unists_resource:".md5($value["unists_id"].$value["marker_name"].$value["chromosome"].$value["coordinate"]),
					"rdfs:label",
					utf8_encode($value["marker_name"])
				));
				//add chromosome
				$this->AddRDF($this->QQuadL(
					"unists_resource:".md5($value["unists_id"].$value["marker_name"].$value["chromosome"].$value["coordinate"]),
					"unists_vocabulary:has_chromosome",
					utf8_encode($value["chromosome"])
				));
				//add coordinate
				$this->AddRDF($this->QQuadL(
					"unists_resource:".md5($value["unists_id"].$value["marker_name"].$value["chromosome"].$value["coordinate"]),
					"unists_vocabulary:has_coordinate",
					utf8_encode($value["coordinate"])
				));
				//link this mapping to the map record
				$this->AddRDF($this->QQuad(
					"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"]),
					"unists_vocabulary:has_mapping",
					"unists_resource:".md5($value["unists_id"].$value["marker_name"].$value["chromosome"].$value["coordinate"])
				));			
				//link map report to unists
				$this->AddRDF($this->QQuad(
					"unists:".$value["unists_id"],
					"unists_vocabulary:has_map_report",
					"unists_resource:".md5($r.$record_metadata["Total_Length"].$record_metadata["Taxonomic_ID"].$record_metadata["Map_Name"])
				));
			$this->WriteRDFBufferToWriteFile();
		}
	}

//returns an assoc array from a tab delimited map record
	private function parseMapRecordRow($aRecord_line){
		$rm = array();
		$r = explode("\t", $aRecord_line);
		$rm["unists_id"] = $r[0];
		$rm["marker_name"] = $r[1];
		$rm["chromosome"] = $r[2];
		$rm["coordinate"] = $r[3];
		$rm["lod"] = $r[4];
		$rm["bin"] = $r[5];
		$rm["bin2"] = $r[6];

		//check if there is a unists_id
		if($rm["unists_id"] != ""){
			return $rm;
		}else{
			return null;
		}
	}

	  private function markers(){
	  	$this->GetReadFile()->Read(200000);
	  	while($aLine = $this->GetReadFile()->Read(200000)){
	  		$line_arr = explode("\t", $aLine);
	  		$unique_id = $line_arr[0];
	  		$forward_primer = $line_arr[1];
	  		$reverse_primer = $line_arr[2];
	  		$pcr_product_size = $line_arr[3];
	  		$name = $line_arr[4];
	  		$chr_number = $line_arr[5];
	  		$acc_number = $line_arr[6];
	  		$species_name = trim($line_arr[7]);
	  		//create a unique resource using $unique_id
	  		$this->AddRDF($this->QQuad(
	  			"unists:".$unique_id,
				"rdf:type",
				"unists_vocabulary:sts"
	  		));
	  		//add a label
	  		$this->AddRDF($this->QQuadL(
	  			"unists:".$unique_id,
				"rdfs:label",
				"sequence tagged site [unists:".$unique_id."]"
	  		));
	  		//now add the remaining data
	  		//only add info on sts with species
	  		if($species_name != "" && $species_name != "-"){
	  			//create a species resource
	  			$this->AddRDF($this->QQuad(
	  				"unists_resource:".md5($species_name),
	  				"rdf:type",
	  				"unists_vocabulary:species"
	  			));
	  			$this->AddRDF($this->QQuadL(
	  				"unists_resource:".md5($species_name),
	  				"rdfs:label",
	  				$species_name
	  			));
	  			//primer 1
	  			if($forward_primer != "" && $forward_primer != "-"){
	  				$this->AddRDF($this->QQuadL(
	  					"unists_resource:".md5($forward_primer.$species_name),
	  					"unists_vocabulary:has_value",
	  					utf8_encode(htmlspecialchars($forward_primer))
	  				));
	  				$this->AddRDF($this->QQuadL(
	  					"unists_resource:".md5($forward_primer.$species_name),
	  					"rdfs:label",
	  					utf8_encode(htmlspecialchars("forward_primer-".$forward_primer."-".$species_name))
	  				));
	  				//add the primer type
	  				$this->AddRDF($this->QQuad(
	  					"unists_resource:".md5($forward_primer.$species_name),
	  					"rdf:type",
	  					"unists_vocabulary:primer"
	  				));
	  				//link primer to record
	  				$this->AddRDF($this->QQuad(
	  					"unists:".$unique_id,
	  					"unists_vocabulary:forward_primer",
	  					"unists_resource:".md5($forward_primer.$species_name)
	  				));
	  				//link the primer to the species
	  				$this->AddRDF($this->QQuad(
	  					"unists_resource:".md5($forward_primer.$species_name),
	  					"unists_vocabulary:has_species",
	  					"unists_resource:".md5($species_name)
	  				));
	  			}
	  			//primer 2
	  			if($reverse_primer != "" && $reverse_primer != "-"){
	  				$this->AddRDF($this->QQuadL(
	  					"unists_resource:".md5($reverse_primer.$species_name),
	  					"unists_vocabulary:has_value",
	  					utf8_encode(htmlspecialchars($reverse_primer))
	  				));
	  				$this->AddRDF($this->QQuadL(
	  					"unists_resource:".md5($reverse_primer.$species_name),
	  					"rdfs:label",
	  					utf8_encode(htmlspecialchars("reverse_primer-".$reverse_primer."-".$species_name))
	  				));
	  				//add the primer type
	  				$this->AddRDF($this->QQuad(
	  					"unists_resource:".md5($forward_primer.$species_name),
	  					"rdf:type",
	  					"unists_vocabulary:primer"
	  				));
	  				//link primer to record
	  				$this->AddRDF($this->QQuad(
	  					"unists:".$unique_id,
	  					"unists_vocabulary:reverse_primer",
	  					"unists_resource:".md5($reverse_primer.$species_name)
	  				));
	  				//link the primer to the species
	  				$this->AddRDF($this->QQuad(
	  					"unists_resource:".md5($forward_primer.$species_name),
	  					"unists_vocabulary:has_species",
	  					"unists_resource:".md5($species_name)
	  				));
	  			}//if primer 2
	  			if($pcr_product_size != "-" && $pcr_product_size != ""){
	  				$this->AddRDF($this->QQuadL(
	  					"unists_resource:".md5($pcr_product_size.$species_name),
	  					"unists_vocabulary:has_value",
	  					$pcr_product_size
	  				));
	  				//add pcr_product size type
	  				$this->AddRDF($this->QQuad(
	  					"unists_resource:".md5($pcr_product_size.$species_name),
	  					"rdf:type",
	  					"unists_vocabulary:pcr_product_size"
	  				));
	  				//link primer to record
	  				$this->AddRDF($this->QQuad(
	  					"unists:".$unique_id,
	  					"unists_vocabulary:has_pcr_product_size",
	  					"unists_resource:".md5($pcr_product_size.$species_name)
	  				));
	  			}//if pcr product size
	  			if($name != ""&& $name != "-"){
	  				$this->AddRDF($this->QQuadL(
	  					"unists_resource:".md5($name.$species_name),
	  					"unists_vocabulary:has_value",
	  					utf8_encode((htmlspecialchars($name)))	
	  				));
	  				//add type
	  				$this->AddRDF($this->QQuad(
	  					"unists_resource:".md5($name.$species_name),
	  					"rdf:type",
	  					"unists_vocabulary:name"	
	  				));
	  				//link to record
	  				$this->AddRDF($this->QQuad(
  						"unists:".$unique_id,
  						"unists_vocabulary:has_name",
  						"unists_resource:".md5($name.$species_name)
  					));
	  			}//if name
	  			if($chr_number != "" && $chr_number != "-"){
	  				//create resource
	  				$this->AddRDF($this->QQuadL(
	  					"unists_resource:".md5($chr_number.$species_name),
	  					"unists_vocabulary:has_value",
	  					utf8_encode(htmlspecialchars($chr_number))
	  				));
	  				//add type
	  				$this->AddRDF($this->QQuad(
	  					"unists_resource:".md5($chr_number.$species_name),
	  					"rdf:type",
	  					"unists_vocabulary:chromosome_number"
	  				));
	  				//link to record
	  				$this->AddRDF($this->QQuad(
	  					"unists:".$unique_id,
	  					"unists_vocabulary:has_chromosome_number",
	  					"unists_resource:".md5($chr_number.$species_name)
	  				));
	  			}//if $chr_number
	  			if($acc_number != "" && $acc_number != "-"){
	  				$a_arr = explode(";", $acc_number);
	  				foreach ($a_arr as $key => $acc) {
	  					$this->AddRDF($this->QQuadL(
	  						"unists_resource:".md5($acc.$species_name),
	  						"rdfs:seeAlso",
	  						"http://www.ncbi.nlm.nih.gov/nucleotide/".$acc
	  					));
	  					//add type
		  				$this->AddRDF($this->QQuad(
		  					"unists_resource:".md5($acc.$species_name),
		  					"rdf:type",
		  					"unists_vocabulary:accession"
		  				));
		  				//link to record
		  				$this->AddRDF($this->QQuad(
		  					"unists:".$unique_id,
		  					"unists_vocabulary:has_accession",
		  					"unists_resource:".md5($chr_number.$species_name)
		  				));
	  				}//foreach
	  			}//if $acc_number
	  			if($species_name != "" && $species_name != "-"){
	  					$this->AddRDF($this->QQuadL(
	  						"unists_resource:".md5($species_name),
	  						"unists_vocabulary:has_value",
	  						utf8_encode(htmlspecialchars($species_name))
	  					));
	  				
	  					//add type
		  				$this->AddRDF($this->QQuad(
		  					"unists_resource:".md5($species_name),
		  					"rdf:type",
		  					"unists_vocabulary:species_name"
		  				));
		  				//link to record
		  				$this->AddRDF($this->QQuad(
		  					"unists:".$unique_id,
		  					"unists_vocabulary:has_species_name",
		  					"unists_resource:".md5($species_name)
		  				));
	  			}//if $species_name
  			}//if species name
  			$this->WriteRDFBufferToWriteFile();
  		}//while
	}//markers
	public function getPackageMap(){
		return self::$packageMap;
	}
	//get all files from directory recursively
	private function getFileR($directory, $recursive=true) {
		$array_items = array();
		if ($handle = opendir($directory)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					if (is_dir($directory. "/" . $file)) {
						if($recursive) {
								$array_items = array_merge($array_items, $this->getFileR($directory. "/" . $file, $recursive));
						}//if
						$file = $directory . "/" . $file;
						if(is_file($file)){
								$array_items[] = preg_replace("/\/\//si", "/", $file);
						}
					} else {
						$file = $directory . "/" . $file;
						if(is_file($file)){
							$array_items[] = preg_replace("/\/\//si", "/", $file);
						}
					}//else
				}//if
			}//while
			closedir($handle);
		}//if
		return $array_items;
	}//getFileR



	//makes an assoc array from the metadata of each map record
	private function parseMapRecordMetadata($record_metadata){
		$rm = array();
		$a = explode("\n", $record_metadata);
		foreach ($a as $key => $value) {
			$b = explode(": ", $value);
			if(isset($b[0]) && isset($b[1])){
				$rm[str_replace(" ", "_", trim($b[0]))] = utf8_encode(trim($b[1]));
			}
		}
		return $rm;
	}
}//class

$p = new UniSTSParser($argv);
$p->Run();
?>