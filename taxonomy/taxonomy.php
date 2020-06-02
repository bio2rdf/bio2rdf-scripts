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
/*		"gi2taxid_protein" => array(
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
*/	);

	function __construct($argv) {
		parent::__construct($argv, "taxonomy");
		parent::addParameter('files',true,'all|taxdmp','taxdmp','files to process');
//		parent::addParameter('files',true,'all|taxdmp|gi2taxid_nucleotide|gi2taxid_protein','taxdmp','files to process');
		parent::addParameter('download', false, 'true|false','false');
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
				utils::downloadSingle($rfile,$lfile);
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
					->setRetrievedDate(date("Y-m-d\TH:i:sP", filemtime($ldir.$lfile)))
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
				$date = date("Y-m-d\TH:i:sP");
				$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix - $key")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/taxonomy/taxonomy.php")
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

//if($k !== 'citations') continue;
						$fpin = $zin->getStream($fn);

						if(!$fpin){
							trigger_error("Unable to get pointer to $fn in $zinfile");
							exit("failed\n");
						}
						$gzoutfile = $odir."bio2rdf-taxonomy-$k".".".parent::getParameterValue('output_format');

						//set the write file
						$gz= strstr(parent::getParameterValue('output_format'), 'gz')?true:false;
						parent::setReadFile($ldir.$lfile);
						parent::getReadFile()->SetFilePointer($fpin);
						parent::setWriteFile($gzoutfile, $gz);
						echo "processing $fn...\n";
						$this->$k();
						$this->GetWriteFile()->Close();
						echo "done!".PHP_EOL;
						parent::clear();
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
				parent::triplify("gi:".$gi, $this->getVoc()."x-taxonomy", $this->GetNamespace().$txid)
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
				parent::triplify("gi".$gi, $this->getVoc()."x-taxonomy", $this->GetNamespace().$txid)
			);
			$this->WriteRDFBufferToWriteFile();
		}//while
	}
	private function names(){
		while($l = $this->getReadFile()->read(200000)){
			$a = explode("\t|\t", trim($l,"|\t\r\n"));
			if(count($a) == 0) continue;
			$taxid = parent::getNamespace().trim($a[0]);
			$name = utf8_encode($a[1]);
			$rel = parent::getVoc().str_replace(" ","-",$a[3]);
			
			parent::addRDF(
				parent::triplifyString($taxid, $rel, addslashes($name)).
				parent::triplifyString($taxid, parent::getVoc()."unique-name", addslashes(utf8_encode($a[2])))
			);
			
			if($rel == "scientific-name") {
				parent::addRDF(
					parent::triplifyString($taxid, "dc:title", addslashes($name)).
					parent::triplifyString($taxid, "rdfs:label", addslashes($name))
				);
			}
			
			$this->writeRDFBufferToWriteFile();
		}//while
	}//names

	private function nodes()
	{
		$elements = array(
			// taxid, parent
			"rank","embl-code","division-id","inherited-division","genetic-code-id","inherited-genetic-code","mitochondrial-genetic-code-id","inherited-mitochondrial-genetic-code"); //ignore these,"genbank-hidden-flag","hidden-st-root-flag","comments");
		
		while($l = $this->getReadFile()->read(200000)){
			$a = explode("\t|\t", rtrim($l,"\t|\n"));
			$taxid = "taxonomy:".$a[0];

			if($a[1] != "" and $taxid != "1"){
				parent::addRDF(
					parent::triplify($taxid, "rdfs:subClassOf", "taxonomy:".$a[1]).
					parent::triplify($taxid, "rdf:type", "owl:Class")
				);
			}
			
			foreach($elements AS $i => $e) {
				$rel = $this->getVoc().$e;
				$val = $a[ $i + 2 ];
				switch( $e ) {
					case "division-id":
					case "genetic-code-id" :
					case 'mitochondrial-genetic-code-id':
						if($e == 'mitochondrial-genetic-code-id') $e = 'genetic-code-id';
						$eid = parent::getRes().$e."-".$val;
						parent::addRDF(
							parent::triplify($taxid, $rel, $eid)
						);
						break;
					case "rank":
					case "embl-code":
						$eid = parent::getRes().str_replace(" ","-",$val);
						parent::addRDF(
							parent::triplify($taxid,$rel, $eid).
							parent::describeIndividual($eid, $val, parent::getVoc().ucfirst($e)).
							parent::describeClass(parent::getVoc().ucfirst($e), ucfirst($e))
						);
						break;;
					default :
						if($val != '') {
							parent::addRDF(
								parent::triplifyString($taxid, $rel, ($val == 1)?"true":"false", "xsd:boolean")
							);
						}
				}
			}
			
			$this->writeRDFBufferToWriteFile();
		}//while
	}//nodes

	private function division()
	{
		while($l = $this->getReadFile()->read(200000)){
			$a = explode("\t|\t", rtrim($l,"\t|\n"));

			$division = parent::getRes()."division-id-".$a[0];
			parent::addRDF(
				parent::describeIndividual($division, $a[2], $this->getVoc()."Division").
				parent::describeClass($this->getVoc()."Division", "Taxonomic Division").
				parent::triplifyString($division, $this->getVoc()."division-code", $a[1]).
				(isset($a[3])?parent::triplifyString($division, $this->getVoc()."comment", $a[3]):"")
			);

			$this->writeRDFBufferToWriteFile();
		}//while
	}//division

	private function gencode()
	{
		while($l = $this->getReadFile()->read(200000)){
			$a = explode("\t|\t", rtrim($l,"\t|\n"));

			$gc = parent::getRes()."genetic-code-id-".$a[0];
			parent::addRDF(
				parent::describeIndividual($gc, $a[2], $this->getVoc()."Genetic-Code").
				parent::describeClass($this->getVoc()."Genetic-Code", "Genetic Code").
				parent::triplifyString($gc, parent::getVoc()."abbreviation", $a[1]).
				parent::triplifyString($gc, parent::getVoc()."translation-table", $a[3]).
				parent::triplifyString($gc, parent::getVoc()."start-codons", $a[4])
			);
			$this->writeRDFBufferToWriteFile();
		}//while
	}//gencode

	private function citations()
	{
		while($l = $this->getReadFile()->read(2000000)){
			$a = explode("\t|\t", rtrim($l,"\t|\n"));
			if(!isset($a[1]) or !isset($a[2])) {
				continue;
			}
			$c = parent::getRes()."citation-id-".$a[0];
/*			$seealso = isset($a[4])?trim($a[4]):"";
			if($seealso) {
				echo $seealso.PHP_EOL;
				$seealso = str_replace(array("lx: DOI ","http;//"), array("https://doi.org/","http://"), $seealso);
				if(strlen($seealso) > 2 and !strstr($seealso,"http")) $seealso = "http://".$seealso;
				$seealso = parent::triplifyString($c, "rdfs:seeAlso", addslashes($seealso)); # all kinds of garbarge in this field
			} 
*/
			$text = '';
			if(isset($a[5])) {
				$text = str_replace(array('"',"'","",'\\',),'',$a[5]); # get rid of garbage characters
			}
			
			parent::addRDF(
				parent::describeIndividual($c, $a[1], $this->getVoc()."Citation").
				parent::describeClass($this->getVoc()."Citation", "Citation").
				parent::triplifyString($c, parent::getVoc()."citation-key", $a[1]).
				($a[2]=="0"?"":parent::triplify($c, parent::getVoc()."x-pubmed", "pubmed:".$a[2])).
#				$seealso.
				$text?parent::triplifyString($c, parent::getVoc()."text", $text):""
			);
			if(isset($a[6])) {
				$taxids = explode(" ", trim($a[6]));
				if(count($taxids)){
					foreach($taxids as $taxid) {
						parent::addRDF(
							parent::triplify("taxonomy:$taxid", $this->getVoc()."citation", $c)
						);
					}
				}
			}
			$this->writeRDFBufferToWriteFile();
		}//while
	}//citations

	public function getPackageMap(){
		return self::$packageMap;
	}//getpackagemap

}//class

?>
