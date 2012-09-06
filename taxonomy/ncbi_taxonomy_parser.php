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
 * NCBI Taxonomy parser
 * @version 0.1
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdump_readme.txt
*/
/**
*   ***RELEASE NOTES***
* -the files merged.dmp and delnodes.dmp are not parsed by this version
**/
require("../../php-lib/rdfapi.php");
class NCBITaxonomyParser extends RDFFactory{
	private $bio2rdf_base = "http://bio2rdf.org/";
	private $unists_vocab = "taxon_vocabulary:";
	private $unists_resource = "taxon_resource:";
	private $version = 0.1;

	private static $packageMap = array(
		"taxdmp" => array(
			"filename" => "taxdmp.zip",
			"contents" => array(
				"names" => "names.dmp",
				"nodes" => "nodes.dmp",
				"citations" => "citations.dmp",
				"gencode" => "gencode.dmp",
				"division" => "division.dmp"
			)
		),
		"gi2taxid_protein" => array(
			"filename" => "gi_taxid_prot.zip",
			"contents" => array(
				"gi_taxid_prot" => "gi_taxid_prot.dmp",
			)
		) ,
		"gi2taxid_nucleotide" => array(
			"filename" => "gi_taxid_nucl.zip",
			"contents" => array(
				"gi_taxid_nucl" => "gi_taxid_nucl.dmp",
			)
		) 
	);

	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("taxon");
		// set and print application parameters
		$this->AddParameter('files',true,null,'all|taxdmp|gi2taxid_nucleotide|gi2taxid_protein','','files to process');
		$this->AddParameter('indir',false,null,'/home/jose/tmp/taxonomy/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/home/jose/tmp/n3/taxonomy/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdmp.zip');
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
		// make sure we have the zip archive
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
			if(substr($ldir, -1) == "/"){
				$lfile = $ldir.$value;
			} else {
				$lfile = $ldir."/".$value;
			}
			if($key == "taxdmp" || $key == "gi2taxid_protein" || $key == "gi2taxid_nucleotide"){
				//get the name of the zip archive
				$lfile = $value["filename"];
				// make sure we have the zip archive
				$zin = new ZipArchive();
				if ($zin->open($ldir.$lfile) === FALSE) {
					trigger_error("Unable to open $ldir$lfile");
					exit;
				}
				//now iterate over the files in the ziparchive
				foreach($value["contents"] as $k => $fn){
					if($k == "names" || $k == "nodes" || $k == "citations" 
						|| $k == "gencode" || $k == "division" 
						|| $k == "gi2taxid_protein" || $k == "gi2taxid_nucleotide"){
						$fpin = $zin->getStream($fn);
						if(!$fpin){
							trigger_error("Unable to get pointer to $fn in $zinfile");
							exit("failed\n");
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
					}//if $k
				}//foreach
				// generate the release file
				$desc = $this->GetBio2RDFDatasetDescription(
					$this->GetNamespace(),
					"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/taxonomy/ncbi_taxonomy_parser.php", 
					$this->GetBio2RDFDownloadURL($this->GetNamespace()),
					"http://www.ncbi.nlm.nih.gov/taxon",
					array("use-share-modify"),
					"http://www.ncbi.nlm.nih.gov/About/disclaimer.html",
					"ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdmp.zip",
					$this->version
				);
				$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
			}//if key taxdmp
		}
	}//run

	private function gi2taxid_protein(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = explode("\t", $aLine);
			$gi = trim($a[0]);
			$txid = trim($a[1]);
			$this->AddRDF($this->QQuad(
				"gi:".$gi,
				"taxon_vocabulary:has_taxid",
				"taxon:".$txid
			));
			$this->AddRDF($this->QQuad("gi:".$gi, "void:inDataset", $this->getDatasetURI()));
			$this->WriteRDFBufferToWriteFile();
		}//while
	}
	private function gi2taxid_nucleotide(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = explode("\t", $aLine);
			$gi = trim($a[0]);
			$txid = trim($a[1]);
			$this->AddRDF($this->QQuad(
				"gi:".$gi,
				"taxon_vocabulary:has_taxid",
				"taxon:".$txid
			));
			$this->AddRDF($this->QQuad("gi:".$gi, "void:inDataset", $this->getDatasetURI()));
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
			//create a resource
			$this->AddRDF($this->QQuadL(
				"taxon:".$taxid,
				"rdfs:label",
				str_replace("\"","",utf8_encode($name))."[taxon:".$taxid."]"
			));
			$this->AddRDF($this->QQuad("taxon:".$taxid, "void:inDataset", $this->getDatasetURI()));
			//type it
			$this->AddRDF($this->QQuad(
				"taxon:".$taxid,
				"rdf:type",
				"taxon_vocabulary:taxon"
			));
			//add unique name
			if($unique_name != "" && $unique_name != null){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_unique_name",
					str_replace("\"","",utf8_encode($unique_name))
				));
			}
			//add name class
			if($name_class != "" && $name_class != null){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_name_class",
					str_replace("\"","",utf8_encode($name_class))
				));
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
			//create a resource
			$this->AddRDF($this->QQuadL(
				"taxon:".$taxid,
				"rdfs:label",
				"taxon [taxon:".$taxid."]"
			));
			$this->AddRDF($this->QQuad("taxon:".$taxid, "void:inDataset", $this->getDatasetURI()));
			//type it
			$this->AddRDF($this->QQuad(
				"taxon:".$taxid,
				"rdf:type",
				"taxon_vocabulary:taxon"
			));
			if($parent_taxid != ""){
				$this->AddRDF($this->QQuad(
					"taxon:".$taxid,
					"taxon_vocabulary:has_parent",
					"taxon:".$parent_taxid
				));
			}
			if($rank != ""){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_rank",
					str_replace("\"","",utf8_encode($rank))
				));
			}
			if($embl_code != ""){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_embl_code",
					str_replace("\"","",utf8_encode($rank))
				));
			}
			if($division_id != ""){
				$this->AddRDF($this->QQuad(
					"taxon:".$taxid,
					"taxon_vocabulary:has_division",
					"taxon_resource:".md5("division_id_".$division_id)
				));
			}
			if($inherited_div_flag != ""){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_inherited_division_flag",
					str_replace("\"","",utf8_encode($inherited_div_flag))
				));
			}
			if($gencode_id != ""){
				$this->AddRDF($this->QQuad(
					"taxon:".$taxid,
					"taxon_vocabulary:has_genetic_code",
					"taxon_resource:".md5("gencode_id_".$gencode_id)
				));
			}
			if($inherited_gc_flag != ""){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_inherited_gc_flag",
					str_replace("\"","",utf8_encode($inherited_gc_flag))
				));
			}
			if($mitochondrial_genetic_code_id != ""){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_mitochondrial_genetic_code_id",
					str_replace("\"","",utf8_encode($mitochondrial_genetic_code_id))
				));
			}
			if($inherited_mgc_flag != ""){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_inherited_mgc_flag",
					str_replace("\"","",utf8_encode($inherited_mgc_flag))
				));
			}
			if($genbank_hidden_flag != ""){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_genbank_hidden_flag",
					str_replace("\"","",utf8_encode($genbank_hidden_flag))
				));
			}
			if($hidden_st_root_flag != ""){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:has_hidden_st_root_flag",
					str_replace("\"","",utf8_encode($hidden_st_root_flag))
				));
			}
			if($comments != ""){
				$this->AddRDF($this->QQuadL(
					"taxon:".$taxid,
					"taxon_vocabulary:comments",
					str_replace("\"","",utf8_encode($comments))
				));
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
			//create a resource
			$this->AddRDF($this->QQuadL(
				"taxon_resource:".md5("division_id_".$division_id),
				"rdfs:label",
				str_replace("\"","",utf8_encode($name))."[taxon_resource:".$division_id."]"
			));
			$this->AddRDF($this->QQuad("taxon_resource:".md5("division_id_".$division_id), "void:inDataset", $this->getDatasetURI()));
			//type it
			$this->AddRDF($this->QQuad(
				"taxon_resource:".md5("division_id_".$division_id),
				"rdf:type",
				"taxon_vocabulary:division"	
			));
			//add division code
			$this->AddRDF($this->QQuadL(
				"taxon_resource:".md5("division_id_".$division_id),
				"taxon_vocabulary:has_division_code",
				str_replace("\"","",utf8_encode($division_code))
			));
			//add comments
			if($comments != ""){
				$this->AddRDF($this->QQuadL(
					"taxon_resource:".md5("division_id_".$division_id),
					"taxon_vocabulary:has_comments",
					str_replace("\"","",utf8_encode($comments))
				));
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
			//create resource
			$this->AddRDF($this->QQuadL(
				"taxon_resource:".md5("gencode_id_".$gencode),
				"rdfs:label",
				str_replace("\"","",utf8_encode($name))."[taxon_resource:".$gencode."]"
			));
			$this->AddRDF($this->QQuad("taxon_resource:".md5("gencode_id_".$gencode), "void:inDataset", $this->getDatasetURI()));

			//type it
			$this->AddRDF($this->QQuad(
				"taxon_resource:".md5("gencode_id_".$gencode),
				"rdf:type",
				"taxon_vocabulary:genetic_code"
			));
			if($abbr != ""){
				$this->AddRDF($this->QQuadL(
					"taxon_resource:".md5("gencode_id_".$gencode),
					"taxon_vocabulary:has_abbreviation",
					str_replace("\"","",utf8_encode($abbr))
				));
			}
			if ($translation_table != "") {
				$this->AddRDF($this->QQuadL(
					"taxon_resource:".md5("gencode_id_".$gencode),
					"taxon_vocabulary:has_translation_table",
					str_replace("\"","",utf8_encode($translation_table))
				));
			}
			if ($start_codons != "") {
				$this->AddRDF($this->QQuadL(
					"taxon_resource:".md5("gencode_id_".$gencode),
					"taxon_vocabulary:has_start_codons",
					str_replace("\"","",utf8_encode($start_codons))
				));
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
			//create a resource
			$this->AddRDF($this->QQuadL(
				"taxon_resource:".md5("citation_id_".$cit_id),
				"rdfs:label",
				"citation [taxon_resource:citation_id_".$cit_id."]"
			));
			$this->AddRDF($this->QQuad("taxon_resource:".md5("citation_id_".$cit_id), "void:inDataset", $this->getDatasetURI()));

			//type it
			$this->AddRDF($this->QQuad(
				"taxon_resource:".md5("citation_id_".$cit_id),
				"rdf:type",
				"taxon_vocabulary:citation"
			));
			if($cit_key != ""){
				$this->AddRDF($this->QQuadL(
					"taxon_resource:".md5("citation_id_".$cit_id),
					"taxon_vocabulary:has_citation_key",
					str_replace("\"","",utf8_encode($cit_key))
				));
			}
			if ($pubmed_id != 0 && $pubmed_id != "") {
				$this->AddRDF($this->QQuad(
					"taxon_resource:".md5("citation_id_".$cit_id),
					"taxon_vocabulary:has_pubmed",
					"pubmed:".$pubmed_id
				));
			}
			if($url != 0 && $url != ""){
				$this->AddRDF($this->QQuadO_URL(
					"taxon_resource:".md5("citation_id_".$cit_id),
					"rdfs:seeAlso",
					$url
				));
			}
			if($text != 0 && $text != ""){
				$this->AddRDF($this->QQuadL(
					"taxon_resource:".md5("citation_id_".$cit_id),
					"taxon_vocabulary:has_text",
					str_replace("\"","",utf8_encode($text))
				));
			}
			if(count($taxid_list)){
				foreach ($taxid_list as $aTxid) {
					$aTxid = trim($aTxid);
					$this->AddRDF($this->QQuad(
						"taxon:".$aTxid,
						"taxon_vocabulary:has_citation",
						"taxon_resource:".md5("citation_id_".$cit_id)
					));
				}
			}
			$this->WriteRDFBufferToWriteFile();
		}//while
	}//citations
	public function getPackageMap(){
		return self::$packageMap;
	}//getpackagemap

}//class
$p = new NCBITaxonomyParser($argv);
$p->Run();
?>