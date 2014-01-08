<?php
/**
Copyright (C) 2012 Jose Cruz-Toledo and Michel Dumontier

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
 * NCBI Taxonomy parser
 * @version 2.0
 * @author Jose Cruz-Toledo
 * @author Michel Dumontier
 * @description ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdump_readme.txt
*/
/**
*   ***RELEASE NOTES***
* -the files merged.dmp and delnodes.dmp are not parsed by this version
**/

class TaxonomyParser extends Bio2RDFizer{
	

	private static $packageMap = array(
		"taxdmp" => array(
			"filename" => "taxdmp.zip",
			"contents" => array(
				"names" => "names.dmp",
				"nodes" => "nodes.dmp",
				"citations" => "citations.dmp",
				"gencode" => "gencode.dmp",
				"division" => "division.dmp"
			),
			"file_url" => "ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdmp.zip"
		),
		"gi2taxid_protein" => array(
			"filename" => "gi_taxid_prot.zip",
			"contents" => array(
				"gi_taxid_prot" => "gi_taxid_prot.dmp",
			),
			"file_url" => "ftp://ftp.ncbi.nih.gov/pub/taxonomy/gi_taxid_prot.zip"
		) ,
		"gi2taxid_nucleotide" => array(
			"filename" => "gi_taxid_nucl.zip",
			"contents" => array(
				"gi_taxid_nucl" => "gi_taxid_nucl.dmp",
			),
			"file_url" => "ftp://ftp.ncbi.nih.gov/pub/taxonomy/gi_taxid_nucl.zip"
		) 
	);

	function __construct($argv) {
		parent::__construct($argv, "taxonomy");
		parent::addParameter('files',true,null,'all|taxdmp|gi2taxid_nucleotide|gi2taxid_protein','','files to process');
		parent::addParameter('download', false, null, 'true|false');
		parent::addParameter('download_url',false,null,'ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdmp.zip');
		parent::initialize();
	}//constructor

	public function Run(){
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		// make sure we have the zip archive
		//which files are to be converted?
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


		$dataset_description = '';

		foreach ($files as $key => $value) {

			$lfile = $ldir.$value['filename'];
			if(!file_exists($lfile) && parent::getParameterValue('download') == false) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
			}

			//download all files [except mapping file]
			if($this->GetParameterValue('download') == true) {
				$rfile = $value["file_url"];
				echo "downloading ".var_dump($value["file_url"])." ... ";
				file_put_contents($lfile,file_get_contents($rfile));
			}

			if($key == "taxdmp" || $key == "gi2taxid_protein" || $key == "gi2taxid_nucleotide"){
				//get the name of the zip archive
				$lfile = $value["filename"];
				// make sure we have the zip archive
				$zinfile = $ldir.$lfile;
				
				$zin = new ZipArchive();
				if ($zin->open($zinfile) === FALSE) {
					trigger_error("Unable to open $zinfile");
					exit;
				}
				//now iterate over the files in the ziparchive
				$source_file = (new DataResource($this))
					->setURI($value['file_url'])
					->setTitle('NCBI Taxonomy - '.$key)
					->setRetrievedDate(date("Y-m-d\TG:i:s\Z", filemtime($ldir.$lfile)))
					->setFormat('text/tab-separated-value')
					->setFormat('application/zip')
					->setPublisher('http://www.ncbi.nlm.nih.gov')
					->setHomepage('http://www.ncbi.nlm.nih.gov/taxonomy')
					->setRights('use')
					->setRights('attribution')
					->setLicense('https://www.nlm.nih.gov/copyright.html')
					->setDataset(parent::getDatasetURI());

				$prefix = parent::getPrefix();
				$bVersion = parent::getParameterValue('bio2rdf_release');
				$date = date("Y-m-d\TG:i:s\Z");
				$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix - $key")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/taxonomy/ncbi_taxonomy_parser.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")
				->setRights("use-share-modify")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());

				$dataset_description .= $output_file->toRDF().$source_file->toRDF();
				foreach($value["contents"] as $k => $fn){
					if($k == "names" || $k == "nodes" || $k == "citations" 
						|| $k == "gencode" || $k == "division" 
						|| $k == "gi_taxid_prot" || $k == "gi_taxid_nucl"){
						$fpin = $zin->getStream($fn);

						if(!$fpin){
							trigger_error("Unable to get pointer to $fn in $zinfile");
							exit("failed\n");
						}
						//ensure that there is a slash between directory name and filename
						if(substr($odir, -1) == "/"){
							$gzoutfilename = $odir.$k;
						} else {
							$gzoutfilename = $odir."/".$k;
						}
						$gzoutfile = $gzoutfilename.".nt";

						//set the write file
						$gz=false;
						if(parent::getParameterValue('output_format', 'gz')) {
							$gzoutfile .= '.gz';
							$gz = true;
						}
						parent::setReadFile($ldir.$lfile);
						parent::getReadFile()->SetFilePointer($fpin);
						parent::setWriteFile($gzoutfile, $gz);
						echo "processing $fn...\n";
						$this->$k();
						$this->GetWriteFile()->Close();
						echo "done!".PHP_EOL;

					}//if $k
				}//foreach
				
			}//if key taxdmp
			$this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
			$this->getWriteFile()->write($dataset_description);
			$this->getWriteFile()->close();

		}
	}//run

	private function gi_taxid_prot(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = explode("\t", $aLine);
			$gi = trim($a[0]);
			$txid = trim($a[1]);
			parent::AddRDF(
				parent::triplify("gi:".$gi, $this->getVoc()."x-taxid", $this->GetNamespace().$txid)
			);
			$this->WriteRDFBufferToWriteFile();
		}//while
	}
	private function gi_taxid_nucl(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = explode("\t", $aLine);
			$gi = trim($a[0]);
			$txid = trim($a[1]);
			parent::AddRDF(
				parent::triplify("gi".$gi, $this->getVoc()."x-taxid", $this->GetNamespace().$txid)
			);
			$this->WriteRDFBufferToWriteFile();
		}//while
	}
	private function names(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = explode("|", $aLine);
			$taxid = str_replace("\t","",trim($a[0]));
			$name = str_replace("\t","",trim($a[1]));
			$unique_name = str_replace("\t","",trim($a[2]));
			$name_class = str_replace("\t","",trim($a[3]));

			if($name_class == "scientific name"){
				parent::AddRDF(
					parent::triplifyString(parent::GetNamespace().$taxid, "rdfs:label", str_replace("\"","",utf8_encode($name)))
				);
			}else{
				$r = rand();
				$name_res = $this->getRes().md5($r.$taxid);
				$name_label = str_replace("\"","",utf8_encode($name));
				$name_label_class = "ncbi taxonomy name class";
				
				parent::AddRDF(
					parent::triplify($name_res, "rdf:type", "owl:Class").
					parent::triplifyString($name_res, $this->getVoc()."has-value", $name_label).
					parent::describeClass($this->getVoc()."name-class", $name_label_class).
					parent::triplify($name_res, "rdf:type", $this->getVoc().preg_replace('/\s+/','',str_replace("\"","",utf8_encode($name_class))))
				);
			}

			//add unique name
			if($unique_name != "" && $unique_name != null){
				parent::AddRDF(
					parent::triplifyString(parent::GetNamespace().$taxid, $this->getVoc()."unique-name", str_replace("\"","",utf8_encode($unique_name)))
				);
			}
			
			$this->WriteRDFBufferToWriteFile();
		}//while
	}//names

	private function nodes(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = explode("|",$aLine);
			$taxid =str_replace("\t","",trim($a[0]));
			$parent_taxid = str_replace("\t","",trim($a[1]));
			$rank = str_replace("\t","",trim($a[2]));
			$embl_code = str_replace("\t","",trim($a[3]));
			$division_id = str_replace("\t","",trim($a[4]));
			$inherited_div_flag = str_replace("\t","",trim($a[5]));
			$gencode_id = str_replace("\t","",trim($a[6]));
			$inherited_gc_flag = str_replace("\t","",trim($a[7]));
			$mitochondrial_genetic_code_id = str_replace("\t","",trim($a[8]));
			$inherited_mgc_flag = str_replace("\t","",trim($a[9]));
			$genbank_hidden_flag = str_replace("\t","",trim($a[10]));
			$hidden_st_root_flag = str_replace("\t","",trim($a[11]));
			$comments = str_replace("\t","",trim($a[12]));
			
			if($parent_taxid != "" && $taxid != "1"){
				parent::AddRDF(
					parent::triplify(parent::GetNamespace().$taxid, "rdfs:subClassOf", parent::GetNamespace().$parent_taxid).
					parent::triplify(parent::GetNamespace().$taxid, "rdf:type", "owl:Class")
				);
			}
			if($rank != ""){
				parent::AddRDF(
					parent::triplifyString(
						parent::GetNamespace().$taxid,
						$this->getVoc()."rank",
						str_replace("\"","",utf8_encode($rank))
					)
				);
			}
			if($embl_code != ""){
				parent::AddRDF(
					parent::triplifyString(
						parent::GetNamespace().$taxid,
						$this->getVoc()."embl_code",
						str_replace("\"","",utf8_encode($rank))
					)
				);
			}
			if($division_id != ""){
				parent::AddRDF(
					parent::triplify(
						parent::GetNamespace().$taxid,
						$this->getVoc()."division",
						$this->getRes().md5("division_id_".$division_id)
					)
				);
			}
			if($inherited_div_flag != ""){
				parent::AddRDF(
					parent::triplifyString(
						parent::GetNamespace().$taxid,
						$this->getVoc()."inherited_division_flag",
						str_replace("\"","",utf8_encode($inherited_div_flag))
					)
				);
			}
			if($gencode_id != ""){
				parent::AddRDF(
					parent::triplify(
						parent::GetNamespace().$taxid,
						$this->getVoc()."genetic_code",
						$this->getRes().md5("gencode_id_".$gencode_id)
					)
				);
			}
			if($inherited_gc_flag != ""){
				parent::AddRDF(
					parent::triplifyString(
						parent::GetNamespace().$taxid,
						$this->getVoc()."inherited_gc_flag",
						str_replace("\"","",utf8_encode($inherited_gc_flag))
					)
				);
			}
			if($mitochondrial_genetic_code_id != ""){
				parent::AddRDF(
					parent::triplifyString(
						parent::GetNamespace().$taxid,
						$this->getVoc()."mitochondrial_genetic_code_id",
						str_replace("\"","",utf8_encode($mitochondrial_genetic_code_id))
					)
				);
			}
			if($inherited_mgc_flag != ""){
				parent::AddRDF(
					parent::triplifyString(
						parent::GetNamespace().$taxid,
						$this->getVoc()."inherited_mgc_flag",
						str_replace("\"","",utf8_encode($inherited_mgc_flag))
					)
				);
			}
			if($genbank_hidden_flag != ""){
				parent::AddRDF(
					parent::triplifyString(
						parent::GetNamespace().$taxid,
						$this->getVoc()."genbank_hidden_flag",
						str_replace("\"","",utf8_encode($genbank_hidden_flag))
					)
				);
			}
			if($hidden_st_root_flag != ""){
				parent::AddRDF(
					parent::triplifyString(
						parent::GetNamespace().$taxid,
						$this->getVoc()."hidden_st_root_flag",
						str_replace("\"","",utf8_encode($hidden_st_root_flag))
					)
				);
			}
			if($comments != ""){
				parent::AddRDF(
					parent::triplifyString(
						parent::GetNamespace().$taxid,
						$this->getVoc()."comments",
						str_replace("\"","",utf8_encode($comments))
					)
				);
			}
			$this->WriteRDFBufferToWriteFile();
		}//while
	}//nodes

	private function division(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = explode("|",$aLine);
			$division_id = str_replace("\t","",trim($a[0]));
			$division_code = str_replace("\t","",trim($a[1]));
			$name = str_replace("\t","",trim($a[2]));
			$comments = str_replace("\t","",trim($a[3]));

			$div_res = $this->getRes().md5("division_id_".$division_id);
			$div_label = str_replace("\"","",utf8_encode($name));
			$div_label_class = "ncbi genbank division code for ".$div_res;

			parent::AddRDF(
				parent::describeIndividual($div_res, $div_label, $this->getVoc()."division").
				parent::describeClass($this->getVoc()."division", $div_label_class)
			);
			//add division code
			parent::AddRDF(
				parent::triplifyString($div_res, $this->getVoc()."division_code", str_replace("\"","",utf8_encode($division_code)))
			);
			//add comments
			if($comments != ""){
				parent::AddRDF(
					parent::triplifyString($div_res, $this->getVoc()."comments", str_replace("\"","",utf8_encode($comments)))
				);
			}
			$this->WriteRDFBufferToWriteFile();
		}//while
	}//division

	private function gencode(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = explode("|",$aLine);
			$gencode = str_replace("\t","",trim($a[0]));
			$abbr = str_replace("\t","",trim($a[1]));
			$name = str_replace("\t","",trim($a[2]));
			$translation_table = str_replace("\t","",trim($a[3]));
			$start_codons = str_replace("\t","",trim($a[4]));

			$gen_res = $this->getres().md5("gencode_id_".$gencode);
			$gen_label = str_replace("\"","",utf8_encode($name));
			$gen_label_class = "genetic code";

			//create resource
			parent::AddRDF(
				parent::describeIndividual($gen_res, $gen_label, $this->getVoc()."genetic_code").
				parent::describeClass($this->getVoc()."genetic_code", $gen_label_class)
			);
			if($abbr != ""){
				parent::AddRDF(
					parent::triplifyString($gen_res, $this->getVoc()."abbreviation", str_replace("\"","",utf8_encode($abbr)))
				);
			}
			if ($translation_table != "") {
				parent::AddRDF(
					parent::triplifyString($gen_res, $this->getVoc()."translation_table", str_replace("\"","",utf8_encode($translation_table)))
				);
			}
			if ($start_codons != "") {
				parent::AddRDF(
					parent::triplifyString($gen_res, $this->getVoc()."start_codons", str_replace("\"","",utf8_encode($start_codons)))
				);
			}
			$this->WriteRDFBufferToWriteFile();
		}//while
	}//gencode
	private function citations(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = explode("|",$aLine);
			$cit_id = str_replace("\t","",trim($a[0]));
			$cit_key = str_replace("\t","",trim($a[1]));
			$pubmed_id = str_replace("\t","",trim($a[2]));
			$medline_id = str_replace("\t","",trim($a[3]));
			$url = str_replace("\t","",trim($a[4]));
			$text = str_replace("\t","",trim($a[5]));
			$taxid_list = explode(" ", str_replace("\t","",trim($a[6])));

			$cit_res = $this->getRes().md5("citation_id_".$cit_id);
			$cit_label = "citation identifier for ".$cit_key;
			$cit_label_class = "citation identifier";

			parent::AddRDF(
				parent::describeIndividual($cit_res, $cit_label, $this->getVoc()."citation").
				parent::describeClass($this->getVoc()."citation", $cit_label_class)
			);
			if($cit_key != ""){
				parent::AddRDF(
					parent::triplifyString($cit_res, $this->getVoc()."citation_key", str_replace(array('"','\\'),"",utf8_encode($cit_key)))
				);
			}
			if ($pubmed_id != 0 && $pubmed_id != "") {
				parent::AddRDF(
					parent::triplify($cit_res, $this->getVoc()."x-pubmed", "pubmed:".$pubmed_id)
				);
			}
			if($url != 0 && $url != ""){
				parent::AddRDF(
					parent::triplify($cit_res, "rdfs:seeAlso", $url)
				);
			}
			if($text != 0 && $text != ""){
				parent::AddRDF(
					parent::triplifyString($cit_res, $this->getVoc()."text", str_replace("\"","",utf8_encode($text)))
				);
			}
			if(count($taxid_list)){
				foreach ($taxid_list as $aTxid) {
					$aTxid = trim($aTxid);
					parent::AddRDF(
						parent::triplify($this->GetNamespace().$aTxid, $this->getVoc()."citation", $cit_res)
					);
				}
			}
			$this->WriteRDFBufferToWriteFile();
		}//while
	}//citations
	public function getPackageMap(){
		return self::$packageMap;
	}//getpackagemap

}//class

?>
