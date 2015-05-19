<?php
/**
Copyright (C) 2011 Michel Dumontier

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
 * An RDF generator for PharmGKB (http://pharmgkb.org)
 * @version 1.0
 * @author Michel Dumontier
*/

class PharmGKBParser extends Bio2RDFizer 
{
	private $version = null;

	private $drugs = array();
	private $diseases = array();
	private $genes = array();
	
	function __construct($argv) {
		parent::__construct($argv, "pharmgkb");
		$this->AddParameter('files',true,'all|drugs|genes|diseases|pathways|relationships|annotations|rsid','all','all or comma-separated list of files to process'); /** pathways **/
		$this->addParameter('additional',false,'none|offsides|twosides','none','process offsides and/or twosides');
		$this->AddParameter('download_url',false,null,'https://www.pharmgkb.org/download.do?dlCls=common&objId=');
		parent::initialize();
	}
	
	function download()
	{
		// get the file list
		if($this->GetParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",$this->GetParameterValue('files'));
		}
		if($this->getParameterValue('additional') != 'none') {
			$f = explode(",",$this->getParameterValue('additional'));
			$files = array_merge($files,$f);
		}

		$ldir = $this->GetParameterValue('indir');
		$rdir = $this->GetParameterValue('download_url');
		
		foreach($files AS $file) {
			$lfile = $ldir.$file.".zip";
			if($file == 'annotations' or $file == 'relationships') {
				if(!file_exists($lfile)) {
					echo "Unable to file $lfile . Contact PharmGKB to get access to license-restricted data".PHP_EOL;
					continue;
				}
			} 

			// download
			$rfile = $rdir.$file.".zip";
			echo "Downloading $file ...";
			if($file == 'offsides') {
				if(!file_exists($lfile)) {
					Utils::DownloadSingle('https://www.pharmgkb.org/redirect.jsp?p=ftp%3A%2F%2Fftpuserd%3AGKB4ftp%40ftp.pharmgkb.org%2Fdownload%2Ftatonetti%2F3003377s-offsides.zip', $lfile);
				}
			} elseif($file == 'twosides') {
				if(!file_exists($lfile)) {
					Utils::DownloadSingle('https://www.pharmgkb.org/redirect.jsp?p=ftp%3A%2F%2Fftpuserd%3AGKB4ftp%40ftp.pharmgkb.org%2Fdownload%2Ftatonetti%2F3003377s-twosides.zip', $lfile);
				}
			} elseif($file == 'pathways') {
				Utils::DownloadSingle('https://www.pharmgkb.org/download.do?dlCls=common&objId='.$file.'-tsv.zip', $lfile);
			} else {
				Utils::DownloadSingle('https://www.pharmgkb.org/download.do?dlCls=common&objId='.$file.'.zip', $lfile);
			}
			echo "done.".PHP_EOL;
		}
	}

	function run()
	{
		// get the file list
		if($this->GetParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",$this->GetParameterValue('files'));
		}
		if($this->getParameterValue('additional') != 'none') {
			$f = explode(",",$this->getParameterValue('additional'));
			$files = array_merge($files,$f);
		}

		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rdir = $this->GetParameterValue('download_url');

		$dataset_description = '';
		foreach($files AS $file) {
			$suffix = ".zip";

			$lfile = $ldir.$file.$suffix;
			$rfile = $rdir.$file.$suffix;
			if($file == "offsides" and !file_exists($lfile)){
				echo "downloading twosides...";
				$rfile = "http://www.pharmgkb.org/redirect.jsp?p=ftp%3A%2F%2Fftpuserd%3AGKB4ftp%40ftp.pharmgkb.org%2Fdownload%2Ftatonetti%2F3003377s-offsides.zip";
				utils::DownloadSingle($rfile,$lfile);
				echo "done".PHP_EOL;
			} elseif($file == "twosides" and !file_exists($lfile)){
				echo "downloading $file ...";
				$rfile = "http://www.pharmgkb.org/redirect.jsp?p=ftp%3A%2F%2Fftpuserd%3AGKB4ftp%40ftp.pharmgkb.org%2Fdownload%2Ftatonetti%2F3003377s-twosides.zip";
				utils::DownloadSingle($rfile,$lfile);
				echo "done".PHP_EOL;
			} elseif($file == 'annotations' or $file == 'relationships') {
				if(!file_exists($lfile)) {
					echo "Contact PharmGKB to get access to variants/clinical variants; save file as annotations.zip".PHP_EOL;
					continue;
				}
			} else {
				if(!file_exists($lfile) or parent::getParameterValue('download') == true) {
					echo "Downloading $lfile ... ";
					Utils::DownloadSingle('https://www.pharmgkb.org/download.do?objId='.$file.'.zip&dlCls=common', $lfile);
					echo "done".PHP_EOL;
				}
			}

			// get a pointer to the file in the zip archive
			if(!file_exists($lfile)) {echo "no local copy of $lfile . skipping".PHP_EOL;continue;}

			$zin = new ZipArchive();
			if ($zin->open($lfile) === FALSE) {
				trigger_error("Unable to open $lfile");
				exit;
			}
			$zipentries = array();
			if($file == "annotations") {
				// exclude: 'clinical_ann.tsv','study_parameters.tsv'
				$zipentries = array('clinical_ann_metadata.tsv','var_drug_ann.tsv','var_pheno_ann.tsv','var_fa_ann.tsv'); 
			} else if($file == "pathways") {
				for( $i = 0; $i < $zin->numFiles; $i++ ){ 
					$stat = $zin->statIndex( $i ); 
					$entry = $stat['name'];
					$ext = pathinfo($entry, PATHINFO_EXTENSION); 
					if($ext != "txt"){
						$zipentries[] = $entry;
					}
				}
			}
			else if($file == "relationships") $zipentries = array("relationships.tsv");
			else if($file == 'offsides') $zipentries = array('3003377s-offsides.tsv');
			else if($file == 'twosides') $zipentries = array('3003377s-twosides.tsv');
			else $zipentries = array($file.".tsv");

			// set the write file, parse, write and close
			$suffix = parent::getParameterValue('output_format');
			$outfile = $file.'.'.$suffix; 
			$gz=false;

			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}

			$this->SetWriteFile($odir.$outfile, $gz);

			foreach($zipentries AS $zipentry) {
				if(($fp = $zin->getStream($zipentry)) === FALSE) {
					trigger_error("Unable to get $file.tsv in ziparchive $lfile");
					return FALSE;
				}
				$this->SetReadFile($lfile);
				$this->GetReadFile()->SetFilePointer($fp);

				if($file == "annotations") {
					$fnx = substr($zipentry,0,strpos($zipentry,".tsv"));
					echo "processing $zipentry..";
				} else if($file == 'pathways') {
					$fnx = 'pathways';
					echo "processing $fnx ($zipentry)... ";
				} else {
					$fnx = $file;	
					echo "processing $fnx ... ";
				}
	
				$this->$fnx();
				parent::writeRDFBufferToWriteFile();
				parent::clear();
				echo "done!".PHP_EOL;

				// generate the dataset release file
				$source_file = (new DataResource($this))
					->setURI($rfile)
					->setTitle("Pharmacogenomics Knowledge Base ($zipentry)")
					->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
					->setFormat("text/tab-separated-value")
					->setFormat("application/zip")	
					->setPublisher("http://www.pharmgkb.org/")
					->setHomepage("http://www.pharmgkb.org/")
					->setRights("use")
					->setRights("no-commercial")
					->setLicense("http://www.pharmgkb.org/page/policies")
					->setDataset("http://identifiers.org/pharmgkb/");

				$prefix = parent::getPrefix();
				$bVersion = parent::getParameterValue('bio2rdf_release');
				$date = date ("Y-m-d\TG:i:s\Z");
				$output_file = (new DataResource($this))
					->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$outfile")
					->setTitle("Bio2RDF v$bVersion RDF version of $prefix $file (generated at $date)")
					->setSource($source_file->getURI())
					->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/pharmgkb/pharmgkb.php")
					->setCreateDate($date)
					->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
					->setPublisher("http://bio2rdf.org")			
					->setRights("use-share-modify")
					->setRights("by-attribution")
					->setRights("restricted-by-source-license")
					->setLicense("http://creativecommons.org/licenses/by/3.0/")
					->setDataset(parent::getDatasetURI());

				if($gz) $output_file->setFormat("application/gzip");
				if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
				else $output_file->setFormat("application/n-quads");

				$dataset_description .= $source_file->toRDF().$output_file->toRDF();
			}
			$this->GetWriteFile()->Close();
		} // foreach

		echo "Generating dataset description... ";
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;
	}

	/*
	0 PharmGKB Accession Id	
	1 Entrez Id	
	2 Ensembl Id	
	3 Name	
	4 Symbol	
	5 Alternate Names	
	6 Alternate Symbols	
	7 Is VIP	
	8 Has Variant Annotation
	9 cross-references
	10 Has CPIC Dosing Guideline
	11 Chromosome
	12 Chromosome Start
	13 Chromosome End
	*/
	function genes()
	{
		$h = explode("\t",parent::getReadFile()->read());
		$expected_columns = 14;
		if(($n = count($h)) != $expected_columns) {
			trigger_error("Found $n columns in gene file - expecting $expected_columns!", E_USER_WARNING);
			return false;			
		}

		while($l = parent::getReadFile()->read(200000)) {
			$a = explode("\t",$l);
			$id = parent::getNamespace().$a[0];
			$label = $a[3];
			$this->genes[$a[0]] = $a[3];

			parent::addRDF(
				parent::describeIndividual($id, $label, parent::getVoc()."Gene").
				parent::describeClass(parent::getVoc()."Gene", "PharmGKB Gene")
			);
			
			// link data
			parent::addRDF(
				parent::triplify($id, "rdfs:seeAlso", "http://pharmgkb.org/gene/".$a[0]).
				parent::triplify($id, "rdfs:seeAlso", "http://www4.wiwiss.fu-berlin.de/diseasome/resource/genes/".$a[0]).
				parent::triplify($id, "rdfs:seeAlso", "http://dbpedia.org/resource/".$a[0])
			);
			
			if($a[1]){
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."x-ncbigene", "ncbigene:".$a[1])
				);
			} 

			if($a[2]){
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."x-ensembl", "ensembl:".$a[2])
				);
			}

			if($a[3]){
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."name", $a[3]).
					parent::describeProperty(parent::getVoc()."name", "Relationship between a PharmGKB entity and its name")
				);
			}

			if($a[4]){
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."symbol", "symbol:".$a[4]).
					parent::describeProperty(parent::getVoc()."symbol", "Relationship between a PharmGKB gene and a gene symbol")
				);
			}
			if($a[5]) {
				$b = explode('","',substr($a[5],1,-2));
				foreach($b AS $alt_name) {
					parent::addRDF(
						parent::triplifyString($id, parent::getVoc()."alternative-name", parent::safeLiteral(trim(stripslashes($alt_name))))
					);
				}
				parent::addRDF(
					parent::describeProperty(parent::getVoc()."alternative-name", "Relationship between a PharmGKB gene and an alternative name")
				);
			}
			if($a[6]) { // these are not hgnc symbols
				$b = explode('","',substr($a[6],1,-2));
				foreach($b as $alt_symbol) {
					parent::addRDF(
						parent::triplifyString($id, parent::getVoc()."alternate-symbol", trim($alt_symbol))
					);
				}
				parent::addRDF(
					parent::describeProperty($id, parent::getVoc()."alternate-symbol", "Relationship between a PharmGKB gene and an alternate gene symbol")
				);
			}
		
			if($a[7]){
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."is-vip", $a[7]).
					parent::describeProperty(parent::getVoc()."is-vip", "Relationship between a PharmGKB gene and its vip status")
				);
			}
			if($a[8]){
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."has-variant-annotation", $a[8]).
					parent::describeProperty(parent::getVoc()."has-variant-annotation", "Relationship between a PharmGKB gene and whether it has a variant annotation")
				);
			}

			if($a[9]) {
				$b = explode(",",$a[9]);
				foreach($b AS $xref) {
					$xref = trim($xref);
					if(!$xref) continue;
					
					$url = false;
					$x = $this->MapXrefs($xref, $url, $ns, $id2);
					if($url == true) {
						parent::addRDF(
							parent::QQuadO_URL($id, parent::getVoc()."x-$ns", $x)
						);
						
					} else {
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."x-$ns", $x)
						);
					}
				}
			}
			if($a[10]) {
				parent::addRDF(
					parent::triplifyString($id,parent::getVoc()."cpic-dosing-guideline",$a[10])
				);
			}

			if($a[11]) {
				parent::addRDF(
					parent::triplifyString($id,parent::getVoc()."chromosome",$a[11]).
					parent::describeProperty(parent::getVoc()."chrosomome","Relationship between a PharmGKB gene and its chromosomal position").
					parent::triplifyString($id,parent::getVoc()."chromosome-start",$a[12]).
					parent::triplifyString($id,parent::getVoc()."chromosome-end",$a[13])
				);
			}
			parent::WriteRDFBufferToWriteFile();

		}
	}

	function MapXrefs($xref, &$url = false, &$ns = null, &$id = null)
	{
		$xrefs = array(
			"humancycgene" => "humancyc",
			"entrezgene" => "ncbigene",
			"refseqdna" => "refseq",
			"refseqprotein" => "refseq",
			"refseqrna" => "refseq",
			"ucscgenomebrowser" => "refseq",
			"uniprotkb" => "uniprot",
			'genecard'=>'genecards'
		);
		$this->getRegistry()->ParseQName($xref,$ns,$id);
		if(isset($xrefs[$ns])) {
			$ns = $xrefs[$ns];
		}
		$url = false;
		if($ns == "url") {
			$url = true;
			return $id;
		}
		$this->getRegistry()->ParseQName($id,$ns2,$id2);
		if($ns2) {
			$id = $id2;
		}
		$qname = "$ns:$id";
		return $qname;
	}
/*
[0] => PharmGKB Accession Id
[1] => Name
[2] => Generic Names
[3] => Trade Names
[4] => Brand Mixtures
[5] => Type
[6] => Cross-references
[7] => SMILES
[8] => Dosing Guideline
[9] => External Vocabulary
*/
	function drugs()
	{
		$declared = '';
		$h = explode("\t",$this->GetReadFile()->Read(1000)); // first line is header
		if(count($h) != 10) {
			trigger_error("Change in number of columns for drugs file",E_USER_ERROR);
			return FALSE;
		}
		while($l = $this->GetReadFile()->Read(200000)) {
			$a = explode("\t",$l);
			$id = parent::getNamespace().$a[0];
			
			$this->drugs[$a[0]] = $a[1];

			parent::addRDF(
				parent::describeIndividual($id, $a[1], parent::getVoc()."Drug").
				parent::describeClass(parent::getVoc()."Drug", "PharmGKB Drug")
			);
			
			if(trim($a[2])) { 
				// generic names
				// Entacapona [INN-Spanish],Entacapone [Usan:Inn],Entacaponum [INN-Latin],entacapone
				$b = explode(',',trim($a[2]));
				foreach($b AS $c) {
					parent::addRDF(
						parent::triplifyString($id, parent::getVoc()."generic_name",  str_replace('"','',$c))
					);
				}
				parent::addRDF(
					parent::describeProperty(parent::getVoc()."generic_name", "Relationship between a PharmGKB drug and a generic name")
				);
			}
			if(trim($a[3])) { 
				// trade names
				//Disorat,OptiPranolol,Trimepranol
				$b = explode(',',trim($a[3]));
				foreach($b as $c) {
					parent::addRDF(
						parent::triplifyString($id, parent::getVoc()."trade_name", str_replace(array("'", "\""), array("\\\'", "") ,$c))
					);
				}
				parent::addRDF(
					parent::describeProperty(parent::getVoc()."trade_name", "Relationship between a PharmGKB drug and a trade name")
				);
			}
			if(trim($a[4])) {
				// Brand Mixtures	
				// Benzyl benzoate 99+ %,"Dermadex Crm (Benzoic Acid + Benzyl Benzoate + Lindane + Salicylic Acid + Zinc Oxide + Zinc Undecylenate)",
				$b = explode(',',trim($a[4]));
				foreach($b as $c) {
					parent::addRDF(
						parent::triplifyString($id, parent::getVoc()."brand_mixture", str_replace(array("'", "\""),array("\\\'",""), $c))
					);
				}
				parent::addRDF(
					parent::describeProperty(parent::getVoc()."brand_mixture", "Relationship between a PharmGKB drug and a brand mixture")
				);
			}
			if(trim($a[5])) {
				// Type	
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."drug_class", str_replace(array("'", "\""),array("\\\'",""), $a[5])).
					parent::describeProperty(parent::getVoc()."drug_class", "Relationship between a PharmGKB drug and its drug class")
				);
			}
			if(trim($a[6])) {
				// Cross References	
				// drugBank:DB00789,keggDrug:D01707,pubChemCompound:55466,pubChemSubstance:192903,url:http://en.wikipedia.org/wiki/Gadopentetate_dimeglumine
				$b = explode(',',trim($a[6]));
				foreach($b as $c) {
					$this->getRegistry()->parseQName($c,$ns,$id1);
					$ns = str_replace(array('keggcompound','keggdrug','drugbank','uniprotkb'), array('kegg','kegg','drugbank', 'uniprot'), strtolower($ns));
					if($ns == "url") {
						parent::addRDF(
							parent::QQuadO_URL($id, "rdfs:seeAlso", $id)
						);
					} else {
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."x-".$ns, $ns.":".$id1)
						);
					}
				}
			}
			if(trim($a[9])) {
				// External Vocabulary
				// ATC:H01AC(Somatropin and somatropin agonists),ATC:V04CD(Tests for pituitary function)
				// ATC:D07AB(Corticosteroids, moderately potent (group II)) => this is why you don't use brackets and commas as separators.
				$b = explode(',',trim($a[9]),2);
				foreach($b as $c) {
					preg_match_all("/ATC:([A-Z0-9]+)\((.*)\)$/",$c,$m);
					if(isset($m[1][0])) {
						$atc = "atc:".$m[1][0];
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."x-atc", $atc)
						);
						if(!isset($declared[$atc])) {
							$declared[$atc] = '';
							parent::addRDF(
								parent::triplifyString($atc, "rdfs:label", $m[2][0])
							);
						}
					}
				}
			}
			parent::WriteRDFBufferToWriteFile();
		}
	}

/*
    [0] => PharmGKB Accession Id
    [1] => Name
    [2] => Alternate Names
    [3] => Cross-references
    [4] => External Vocabulary
*/
	function diseases()
	{
		$h = explode("\t",$this->GetReadFile()->Read(10000)); // first line is header
		if(count($h) != 5) {
			trigger_error("Change in number of columns for diseases file",E_USER_ERROR);
			return FALSE;
		}

	  while($l = $this->GetReadFile()->Read(10000)) {
		$a = explode("\t",$l);
			
		$id = parent::getNamespace().$a[0];
		$label = str_replace("'", "\\\'", $a[1]);

		//add disease to disease_names_array for cross referencing in variantAnnotations function
		$this->diseases[$a[0]] = $label;

		parent::addRDF(
			parent::describeIndividual($id, $label, parent::getVoc()."Disease").
			parent::triplifyString($id, parent::getVoc()."name", $label).
			parent::describeClass(parent::getVoc()."Disease", "PharmGKB Disease").
			parent::describeProperty(parent::getVoc()."name", "Relationship between a PharmGKB entity and its name")
		);

		if(!isset($a[2])) continue;
		if($a[2] != '') {
			$names = explode('",',$a[2]);
			foreach($names AS $name) {
				if($name != ''){
					parent::addRDF(
						parent::triplifyString($id, parent::getVoc()."synonym", str_replace('"','',$name)).
						parent::describeProperty(parent::getVoc()."synonym", "Relationship between a PharmGKB entity and a synonym")
					);
				}
			}
		}
		
	//  MeSH:D001145(Arrhythmias, Cardiac),SnoMedCT:195107004(Cardiac dysrhythmia NOS),UMLS:C0003811(C0003811)
		
		$sameID = parent::getRes().md5($a[1]);
		parent::addRDF(
			parent::triplify($id, "owl:sameAs", $sameID)
		);
		if(isset($a[4]) && trim($a[4]) != '') {	 
			$d = preg_match_all('/[,]?([^\:]+):([A-Za-z0-9]+)\(([^\)]+)\)/',$a[4],$m, PREG_SET_ORDER);
			foreach($m AS $n) {
				if(isset($n[1]) && isset($n[2]) && !strstr($n[1]," ")) {
					$n[1] = str_replace("),","",strtolower($n[1]));
					$id2 = $n[1].':'.$n[2];
					parent::addRDF(
						parent::triplify($id, "pharmgkb_vocabulary:x-".$n[1], $id2)
					);
					if(isset($n[3]) && $n[2] != $n[3]){
						parent::addRDF(
							parent::triplifyString($id2, "rdfs:label", str_replace(array("\'", "\""),array("\\\'", ""),$n[3]))
						);
					}
				}
			}
		}
		parent::writeRDFBufferToWriteFile();
	  }
	}

	/*
	0 Entity1_id        - PA267, rs5186, Haplotype for PA121
	1 Entity1_name  
	2 Entity1_type      - Drug, Gene, VariantLocation, Disease, Haplotype, Association     
	3 Entity2_id	      - PA267, rs5186, Haplotype for PA121
	4 Entity2_name  
	5 Entity2_type	  - Drug, Gene, VariantLocation, Disease, Haplotype, Association       
	6 Evidence	      - VariantAnnotation, Pathway, VIP, ClinicalAnnotation, DosingGuideline, DrugLabel, Annotation
	7 Association
	8 Pharmacokinetic		- Y
	9 Pharmacodynamic 	- Y
	10 PMIDS
	*/
	function relationships()
	{
		$declared = '';
		$hash = ''; // md5 hash list
		$h = explode("\t", $this->GetReadFile()->Read());

		if(count($h) != 11) {
			trigger_error("Change in number of columns for relationships file (again)", E_USER_ERROR);
			return FALSE;
		}
		$z = 1;
					
		while($l = $this->getReadFile()->read(100000)) {
			$a = explode("\t",$l);
	
			$id1_list = explode(",",trim($a[0]));
			$id1_names = explode(",",trim($a[1]));
			$type1 = $a[2];
			
			$id2_list = explode(",",trim($a[3]));
			$id2_names = explode(",",trim($a[4]));
			$type2 = $a[5];
			
			foreach($id1_list AS $i => $id1) {
				$id1 =  urlencode($id1);
				$prefix = substr($id1,0,2);
				if($prefix == "rs") $ns1 = "dbsnp";
				else if($prefix == "PA") $ns1 = "pharmgkb";
				else $ns1 = "pharmgkb_resource";
				$i1 = $ns1.':'.$id1;
				
				foreach($id2_list AS $j => $id2) {
					$id2 =  urlencode($id2);
					$prefix = substr($id2,0,2);
					if($prefix == "rs") $ns2 = "dbsnp";
					else if($prefix == "PA") $ns2 = "pharmgkb";
					else $ns2 = "pharmgkb_resource";
					$i2 = $ns2.':'.$id2;
				
					// association
					$z++;
					$id = parent::getRes().$z;
					$label = $id1_names[$i]." - ".$id2_names[$j]." association";
					parent::addRDF(
						parent::describeIndividual($id, $label, parent::getVoc().strtolower($type1)."-".strtolower($type2)."-Association").
						parent::triplify($id, parent::getVoc().strtolower($type1), $i1).
						parent::triplify($id, parent::getVoc().strtolower($type2), $i2).
						parent::triplify($i1, parent::getVoc().strtolower($type2), $i2).
						parent::triplify($i2, parent::getVoc().strtolower($type1), $i1).
						parent::describeClass(parent::getVoc().strtolower($type1)."-".strtolower($type2)."-Association", "PharmGKB $type1 $type2 Association").
						parent::describeProperty(parent::getVoc().strtolower($type1), "Relationship between a PharmGKB association and a $type1").
						parent::describeProperty(parent::getVoc().strtolower($type2), "Relationship between a PharmGKB association and a $type2")
					);
				
					$annotation_types = explode(',',$a[6]);
					foreach($annotation_types AS $annotation_type) {
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."annotation-type", parent::getVoc().$annotation_type)
						);
					}
					
					$associations = explode(',',$a[7]);
					foreach($associations AS $association) {
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."association", parent::getVoc().str_replace(' ','-',$association))
						);
					}


					if($a[8]){
						parent::addRDF(
							parent::triplifyString($id, parent::getVoc()."pk_relationship", "true", "xsd:boolean")
						);
					}
					if($a[9]){
						parent::addRDF(
							parent::triplifyString($id, parent::getVoc()."pd_relationship", "true" , "xsd:boolean")
						);
					}
					
					$a[10] = trim($a[10]);
					if($a[10]) {
						$b = explode(';',$a[10]);
						foreach($b AS $pubmed_id) {
							parent::addRDF(
								parent::triplify($id, parent::getVoc()."article", "pubmed:".$pubmed_id)
							);
						}
					}
					//echo parent::getRDF();
					
					parent::writeRDFBufferToWriteFile();
				}
			}
		}
	}


	/*
	THIS FILE ONLY INCLUDES RSIDs IN GENES
	RSID	Gene IDs	Gene Symbols
	rs8331	PA27674;PA162375713	EGR2;ADO
	*/
	function rsid()
	{
		$z = 0;
		$this->GetReadFile()->Read();
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read()) {
			if($z % 10000 == 0) {
				parent::writeRDFBufferToWriteFile();
			}
			$a = explode("\t",$l);
			$rsid = "dbsnp:".$a[0];
			$genes = explode(";",$a[1]);
			parent::addRDF(
				parent::describeIndividual($rsid, $rsid, parent::getVoc()."Variation").
				parent::describeClass(parent::getVoc()."Variation", "PharmGKB Variation")
			);
			$this->AddRDF($this->QQuad($rsid,"void:inDataset",$this->GetDatasetURI()));
			foreach($genes AS $gene) {
				parent::addRDF(
					parent::triplify($rsid, parent::getVoc()."gene", parent::getNamespace().$gene)
				);
			}
		}
	}

	function clinical_ann_metadata()
	{
		$header = array("Clinical Annotation Id","Location","Gene","Level of Evidence","Clinical Annotation Types","Genotype-Phenotype IDs","Annotation Text","Variant Annotations IDs","Variant Annotations","PMIDs","Evidence Count","Related Drugs","Related Diseases","Race");
		$this_header = explode("\t",$this->GetReadFile()->Read());
		if(count($this_header) != count($header)) {
			trigger_error("Change in the number of columns. Expected ".count($header).", but found ".count($this_header),E_USER_ERROR);
			return (-1);
		}
		while($l = $this->GetReadFile()->Read(200000)) {
			$a = explode("\t",$l);
			$id = parent::getNamespace().$a[0];
			$label = "clinical annotation for ".$a[1];
			// [0] => Clinical Annotation Id
			parent::addRDF(
				parent::describeIndividual($id, $label, parent::getVoc()."Clinical-Annotation").
				parent::describeClass(parent::getVoc()."Clinical-Annotation", "PharmGKB Clinical Annotation")
			);
			
			// [1] => RSID/allele
			if(substr($a[1],0,2) == "rs") {
				$rsid = "dbsnp:$a[1]";
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."x-dbsnp", $rsid).
					parent::describeProperty(parent::getVoc()."x-dbsnp", "Relationship between a PharmGKB entity and a dbSNP entry")
				);
			} else {
				// some kind of star allele
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."star-allele", $a[1]).
					parent::describeProperty(parent::getVoc()."star-allele", "Relationship between a PharmGKB entity and a star allele")
				);
			}
			
			// [2] => Gene
			if($a[2]){
				$genes = explode(",",$a[2]);
				foreach($genes AS $gene) {
					preg_match("/\(([A-Za-z0-9]+)\)/",$gene,$m);
					parent::addRDF(
						parent::triplify($id, parent::getVoc()."gene", parent::getNamespace().$m[1]).
						parent::triplify(parent::getNamespace().$m[1], "rdf:type", parent::getVoc()."Gene")
					);
				}
			}
			
			// [3] => Evidence Level
			if($a[3]) {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."evidence-level", $a[3]).
					parent::describeProperty(parent::getVoc()."evidence-level", "The level of evidence")
				);
			}

			// [6] => Clinical Annotation Types
			if($a[4]) {
				$types = explode(";",$a[4]);
				foreach($types AS $t) {
					parent::addRDF(
						parent::triplifyString($id, parent::getVoc()."annotation-type", strtolower($t))
					);
				}
			}
			// [5] => Genotype-Phenotypes IDs
			// [6] => Text
			if($a[5]) {
				$gps = explode(";",$a[5]);
				$gps_texts = explode(";",$a[6]);
				foreach($gps AS $i => $gp) {
					$gp = trim($gp);
					$gp_text = trim($gps_texts[$i]);
					$b = explode(":",$gp_text,2);

					parent::addRDF(
						parent::describeIndividual(parent::getNamespace().$gp, $gp_text, parent::getVoc()."Genotype-Phenotype").
						parent::triplify($id, parent::getVoc()."genotype_phenotype", parent::getNamespace().$gp).
						parent::triplifyString(parent::getNamespace().$gp, parent::getVoc()."genotype", trim($b[0])).
						parent::describeClass(parent::getVoc()."Genotype-Phenotype", "PharmGKB Genotype Phenotype").
						parent::describeProperty(parent::getVoc()."genotype_phenotype", "Relationship between a PharmGKB entity and a Genotype Phenotype").
						parent::describeProperty(parent::getVoc()."genotype", "Relationship between a PharmGKB Genotype Phenotype and a genotype")
					);
				}
			}
						
			// [7] => Variant Annotations IDs
			// [8] => Variant Annotations
			if($a[7]) {
				$b = explode(";",$a[7]);
				$b_texts =  explode(";",$a[8]);
				foreach($b AS $i => $variant) {
					$variant = trim($variant);
					$variant_text = trim ($b_texts[$i]);
					parent::addRDF(
						parent::describeIndividual(parent::getNamespace().$variant, $variant_text, parent::getVoc()."Variant").
						parent::triplify($id, parent::getVoc()."variant", parent::getNamespace().$variant)
					);
				}
			}
			
			// [9] => PMIDs
			if($a[9]) {
				$b = explode(";",$a[9]);
				foreach($b AS $i => $pmid) {
					$pmid = trim($pmid);
					parent::addRDF(
						parent::triplify($id, parent::getVoc()."article", "pubmed:".$pmid)
					);
				}
			}

			// [10] => Evidence Count
			if($a[10]) {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."evidence-count", $a[10]).
					parent::describeProperty(parent::getVoc()."evidence-count", "Relationship between a PharmGKB annotation and its count of evidence")
				);
			}

			// [11] => Related Drugs
			if($a[11]) {
				$b = explode(";",$a[11]);
				foreach($b AS $drug_label) {
					// find the id from the label
					$find = @array_search($drug_label, $this->drug_names_array);
					if($find !== FALSE and $find !== NULL){
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."related-drug", $find)
						);
					} else {
						$drug_id = parent::getRes().md5($drug_label);
						parent::addRDF(
							parent::describeIndividual($drug_id, $drug_label, parent::getVoc()."Drug").
							parent::triplify($id, parent::getVoc()."related-drug", $drug_id)
						);
					}
				}
				parent::addRDF(
					parent::describeProperty(parent::getVoc()."related-drug", "Relationship between a PharmGKB annotation and a related drug")
				);
			}
			// [12] => Related Diseases
			if($a[12]) {
				$b = explode(";",$a[12]);
				foreach($b AS $disease_label) {
					// find the id from the label
					$find = @array_search($disease_label, $this->disease_names_array);
					if($find !== FALSE and $find !== NULL){
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."related-disease", $find)
						);
					}else {
						$disease_id = parent::getRes().md5($disease_label);
						parent::addRDF(
							parent::describeIndividual($disease_id, $disease_label, parent::getVoc()."Disease").
							parent::triplify($id, parent::getVoc()."related-disease", $disease_id)
						);
					}
				}
				parent::addRDF(
					parent::describeProperty(parent::getVoc()."related-disease", "Relationship between a PharmGKB annotation and a related disease")
				);
			}
			// [13] => OMB Races
			if($a[13]) {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."race", $a[13]).
					parent::describeProperty(parent::getVoc()."race", "Relationship between a PharmGKB annotation and a race")
				);
			}
		}
		parent::writeRDFBufferToWriteFile();
	}

	function var_drug_ann() {return $this->variant_annotation();}
	function var_fa_ann() {return $this->variant_annotation();}
	function var_pheno_ann() {return $this->variant_annotation();}

		
	function variant_annotation()
	{
		$canonical_header = array("Annotation ID","Variant","Gene","Drug","Literature Id","Phenotype Category","Significance","Notes","Sentence","StudyParameters"," Alleles");
		$header = explode("\t",$this->getReadFile()->read(20000));
		if(count($header) != count($canonical_header)) {
			trigger_error("column mismatch! Expected ".count($canonical_header).",but found ".count($header),E_USER_ERROR);
			return (-1);
		}
		
		$declaration = '';
		while($l = $this->GetReadFile()->Read(20000)) {
			$a = explode("\t",$l);
			//[0] => Annotation ID
			$id = parent::getNamespace().$a[0];
			parent::addRDF(
				parent::describeIndividual($id, "Variant Annotation $a[0]", parent::getVoc()."Variant-Annotation").
				parent::describeClass(parent::getVoc()."Variant-Annotation", "PharmGKB Variant Annotation")
			);
			
			// [1] => RSID/allele
			if(substr($a[1],0,2) == "rs") {
				$rsid = "dbsnp:$a[1]";
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."x-dbsnp", $rsid).
					parent::describeProperty(parent::getVoc()."x-dbsnp", "Relationship between a PharmGKB entity and a dbSNP entry")
				);
			} else {
				// some kind of star allele
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."star-allele", $a[1]).
					parent::describeProperty(parent::getVoc()."star-allele", "Relationship between a PharmGKB entity and a star allele")
				);
			}

			//[2] => Gene
			//CYP3A (PA27114),CYP3A4 (PA130)
			if($a[2]) {
				$genes = explode(",",$a[2]);
				foreach($genes AS $gene) {
					preg_match("/\((PA[A-Za-z0-9]+)\)/",$gene,$m);
					if(isset($m[1])) {
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."gene", parent::getNamespace().$m[1]).
							parent::describeProperty(parent::getVoc()."gene", "Relationship between a PharmGKB variant annotation and a gene")
						);
					}
				}
			}
			
			//[3] => Drug
			if($a[3]) {
				$drugs = explode(",",$a[3]);
				foreach($drugs AS $drug) {
					preg_match("/\((PA[A-Za-z0-9]+)\)/",$drug,$m);
					if(isset($m[1])) {
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."drug", parent::getNamespace().$m[1]).
							parent::describeProperty(parent::getVoc()."drug", "Relationship between a PharmGKB variant annotation and a drug")
						);
					}
				}
			}

			// [4] => Literature Id
			if($a[4]) {
				$b = explode(";",$a[4]);
				foreach($b AS $i => $pmid) {
					$pmid = trim($pmid);
					parent::addRDF(
						parent::triplify($id, parent::getVoc()."article", "pubmed:".$pmid).
						parent::describeProperty(parent::getVoc()."article", "Relationship between a PharmGKB entity and a PubMed identifier")
					);
				}
			}
			
			//[5] => Phenotype
			if($a[5]) {
				$types = explode(";",$a[5]);
				foreach($types AS $t) {
					parent::addRDF(
						parent::triplifyString($id, parent::getVoc()."annotation-type", strtolower($t))
					);
				}
			}
			// [6] => Significance
			if($a[6]) {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."significant", $a[6]).
					parent::describeProperty(parent::getVoc()."significant", "Relationship between a PharmGKB annotation and its significance")
				);
			}

			// [7] => Notes
			if($a[7]) {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."note", addslashes($a[7])).
					parent::describeProperty(parent::getVoc()."note", "Relationship between a PharmGKB annotation and its note")
				);
			}
		
			//[8] => Sentence
			if($a[8]) {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."comment", addslashes($a[8])).
					parent::describeProperty(parent::getVoc()."comment", "Relationship between a PharmGKB annotation and a comment")
				);
			}

			//[9] => StudyParameters
			if($a[9]) {
				$sps = explode(";",$a[9]);
				foreach($sps AS $sp) {
					$t = parent::getNamespace().trim($sp);
					parent::addRDF(
						parent::describeIndividual($t, $sp, parent::getVoc()."Study-Parameter").
						parent::triplify($id, parent::getVoc()."study-parameter", $t)
					);
				}
			}
			//[10] => Alleles
			if($a[10]) {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."alleles", $a[10])
				);
			}	
		}
		return TRUE;
	}
	

	function pathways()
	{
		// needs to be finished
		return;
		
		while($l = $this->getReadFile()->read(50000)) {
			$a = explode("\t",trim($l));
			
			// From	To	Reaction Type	Controller	Control Type	Cell Type	PubMed Id	Genes	Drugs	Diseases
			// hmg coa reductase inhibitors	Active & Inactive metabolites	Biochemical Reaction	CYP2C19,CYP2C8,CYP2C9,CYP2D6,CYP3A4,CYP3A5,UGT1A1,UGT1A3,UGT2B7	Catalysis	hepatocyte		CYP3A4,CYP3A5,UGT1A3,CYP2C19,CYP2C9,CYP2C8,CYP2D6,UGT1A1,UGT2B7	hmg coa reductase inhibitors	

			$c1 = array_search($a[0],$this->drugs);
			if($c1 === FALSE) {
				$c1 = array_search($a[0],$this->genes);
				if($c1 === FALSE) {
					$c1 = parent::getRes().url_encode($c1);
				} else {
					$c1 = parent::getNamespace().$c1;
				}
			}
			
			$c2 = array_search($a[1],$this->drugs);
			if($c2 === FALSE) {
				$c2 = array_search($a[1],$this->genes);
				if($c2 === FALSE) {
					// not found
					$c2 = parent::getRes().url_encode($c2);
				} else {
					// actual id
					$c2 = parent::getNamespace().$c2;
				}
			}
			
			$id = md5($l);
			$uri = parent::getRes().$id;
			
			
			parent::writeRDFBufferToWriteFile();
		}
	}

	/*
	stitch_id	drug	umls_id	event	rr	log2rr	t_statistic	pvalue	observed	expected	bg_correction	sider	future_aers	medeffect
	CID000000076	dehydroepiandrosterone	C0000737	abdominal pain	2.25	1.169925001	6.537095128	6.16E-07	9	4	0.002848839	0	0	
	*/
	function offsides() 
	{
		$items = null;
		$z = 0;
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read(5096)) {
			list($stitch_id,$drug_name,$umls_id,$event_name,$rr,$log2rr,$t_statistic,$pvalue,$observed,$expected,$bg_correction,$sider,$future_aers,$medeffect) = explode("\t",$l);
			$z++;

			$id = 'offsides:'.$z;
			$cid = 'pubchemcompound:'.((int) sprintf("%d", substr($stitch_id,4,-1)));
			$eid = 'umls:'.str_replace('"','',$umls_id);
			$drug_name = str_replace('"','',$drug_name);
			$event_name = str_replace('"','',$event_name);
			$label = "$event_name as a predicted side-effect of $drug_name";
			parent::addRDF(
				parent::describeIndividual($id, $label, parent::getVoc()."Side-Effect").
				parent::describeProperty(parent::getVoc()."Side-Effect", "PharmGKB Offsides Side Effect")
			);
			
			parent::addRDF(
				parent::triplify($id, parent::getVoc()."chemical", $cid).
				parent::describeProperty(parent::getVoc()."chemical", "Relationship between a PharmGKB entity and a chemical")
			);
			if(!isset($items[$cid])) {
				$items[$cid] = '';
				parent::addRDF(
					parent::describeIndividual($cid, $drug_name, parent::getVoc()."Chemical").
					parent::describeClass(parent::getVoc()."Chemical", "PharmGKB Chemical")
				);
			}
			$this->AddRDF($this->QQuad($id,"pharmgkb_vocabulary:event",$eid));
			parent::addRDF(
				parent::triplify($id, parent::getVoc()."event", $eid).
				parent::describeProperty(parent::getVoc()."event", "Relationship between a PharmGKB entity and an side effect event")
			);
			if(!isset($items[$eid])) {
				$items[$eid] = '';
				parent::addRDF(
					parent::describeIndividual($eid, $event_name, parent::getVoc()."Event").
					parent::describeClass(parent::getVoc()."Event", "PharmGKB side effect event")
				);
			}
			parent::addRDF(
				parent::triplifyString($id, parent::getVoc()."p-value", $pvalue).
				parent::triplifyString($id, parent::getVoc()."in-sider", ($sider==0?"true":"false")).
				parent::triplifyString($id, parent::getVoc()."in-future-aers", ($future_aers==0?"true":"false")).
				parent::triplifyString($id, parent::getVoc()."in-medeffect", ($medeffect==0?"true":"false")).
				parent::describeProperty(parent::getVoc()."p-value", "Relationship between a side effect and its P value").
				parent::describeProperty(parent::getVoc()."in-sider", "Whether the side effect is in the SIDER resource").
				parent::describeProperty(parent::getVoc()."in-future-aers", "Whether the side effect is in the Future AERS resource").
				parent::describeProperty(parent::getVoc()."in-medeffect", "Whether the side effect is in the Med Effect resource")
			);
		
		}
		parent::writeRDFBufferToWriteFile();
	}

	function twosides()
	{
		$items = null;
		$id = 0;
		$this->GetReadFile()->Read();
		while($l = $this->GetReadFile()->Read()) {
			$a = explode("\t",$l);
			$id++;
			if($id % 10000 == 0) $this->WriteRDFBufferToWriteFile();
			
			$uid = "twosides:$id";
			$d1 = "pubchemcompound:".((int) sprintf("%d",substr($a[0],4)));
			$d1_name = $a[2];
			$d2 = "pubchemcompound:".((int) sprintf("%d",substr($a[1],4)));
			$d2_name = $a[3];
			$e  = "umls:".$a[4];
			$e_name = strtolower($a[5]);
			$uid_label  = "DDI between $d1_name and $d2_name leading to $e_name";

			if(!isset($items[$d1])) {
				parent::addRDF(
					parent::describeIndividual($d1, $d1_name, parent::getVoc()."Chemical").
					parent::describeClass(parent::getVoc()."Chemical", "PharmGKB Chemical")
				);
				$items[$d1] = '';
			}
			if(!isset($items[$d2])) {
				parent::addRDF(
					parent::describeIndividual($d2, $d2_name, parent::getVoc()."Chemical").
					parent::describeClass(parent::getVoc()."Chemical", "PharmGKB Chemical")
				);
				$items[$d2] = '';
			}
			if(!isset($items[$e])) {
				parent::addRDF(
					parent::describeIndividual($e, $e_name, parent::getVoc()."Event").
					parent::describeClass(parent::getVoc()."Event", "PharmGKB side effect event")
				);
				$items[$e] = '';
			}
			
			parent::addRDF(
				parent::describeIndividual($uid, $uid_label, parent::getVoc()."Drug-Drug-Association").
				parent::describeClass(parent::getVoc()."Drug-Drug-Association", "PharmGKB Twosides Drug-Drug Association").
				parent::triplify($uid, parent::getVoc()."chemical", $d1).
				parent::triplify($uid, parent::getVoc()."chemical", $d2).
				parent::triplify($uid, parent::getVoc()."event", $e).
				parent::triplifyString($uid, parent::getVoc()."p-value", $a[7])
			);
		}
		parent::writeRDFBufferToWriteFile();
	}
}
?>
