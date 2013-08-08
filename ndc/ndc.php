<?php
/**
Copyright (C) 2013 Michel Dumontier, Alison Callahan

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
 * NDC RDFizer
 * @version 2.0
 * @author Michel Dumontier
 * @author Alison Callahan
 * @description http://www.fda.gov/Drugs/InformationOnDrugs/ucm142454.htm
*/
class NDCParser extends Bio2RDFizer 
{
	private $version = null;
	
	function __construct($argv) {
		
		parent::__construct($argv, "ndc");
		
		$this->AddParameter('files',true,'all|product|package','all','files to process');
		$this->AddParameter('download_url',false,null,'http://www.fda.gov/downloads/Drugs/DevelopmentApprovalProcess/UCM070838.zip');
		parent::initialize();
	}
	
	function Run()
	{

		if(parent::getParameterValue('download') === true) 
		{
			$this->download();
		}
		if(parent::getParameterValue('process') === true) 
		{
			$this->process();
		}

		
	}

	function download(){

		$ldir = $this->GetParameterValue('indir');
		$rfile = $this->GetParameterValue('download_url');
		$lfile = substr($rfile, strrpos($rfile,"/")+1);
		
		echo "Downloading $rfile ...";
		Utils::DownloadSingle($rfile, $ldir.$lfile);
		echo " done!".PHP_EOL;

	}

	function process(){

		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rfile = $this->GetParameterValue('download_url');
		$lfile = substr($rfile, strrpos($rfile,"/")+1);
		
		// check if exists
		if(!file_exists($ldir.$lfile)) {
			trigger_error($ldir.$lfile." not found. Will attempt to download. ", E_USER_NOTICE);
			Utils::DownloadSingle($rfile, $ldir.$lfile);
		}
		
		// make sure we have the zip archive
		$zin = new ZipArchive();
		if ($zin->open($ldir.$lfile) === FALSE) {
			trigger_error("Unable to open $ldir$lfile");
			exit;
		}
		
		// get the work
		if($this->GetParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode("|",$this->GetParameterValue('files'));
		}

		//set graph URI to be dataset URI
		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		//start generating dataset description file
		$dataset_description = '';
		$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("FDA National Drug Code Directory")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($ldir.$lfile)))
				->setFormat("text/tab-separated-value")
				->setFormat("application/zip")	
				->setPublisher("http://www.fda.gov")
				->setHomepage("http://www.fda.gov/Drugs/InformationOnDrugs/ucm142438.htm")
				->setRights("use-share")
				->setLicense(null)
				->setDataset("http://identifiers.org/ndc/");

		$dataset_description .= $source_file->toRDF();

		// now go through each item in the zip file and process
		foreach($files AS $file) {
			echo "Processing $file... ";

			// the file name in the zip archive is Product not product
			/*if($file == "product"){
				$file = ucfirst($file);
			}*/

			$fpin = $zin->getStream($file.".txt");
			if(!$fpin) {
				trigger_error("Unable to get pointer to $file in $ldir$lfile", E_USER_ERROR);
			}
			
			// set the write file
			$suffix = parent::getParameterValue('output_format');
			$outfile = $file.'.'.$suffix; 
			$gz=false;
			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}

			parent::setWriteFile($odir.$outfile, $gz);
			
			// process
			$this->$file($fpin);
			
			// write to file
			parent::writeRDFBufferToWriteFile();
			parent::getWriteFile()->close();
			
			echo "done!".PHP_EOL;

			echo "Generating dataset description for $outfile... ";
			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$outfile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix $file data (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/ndc/ndc.php")
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
			
			$dataset_description .= $output_file->toRDF();
			echo "done!".PHP_EOL;
		}
		
		//set graph URI back to default value
		parent::setGraphURI($graph_uri);

		//write dataset description to file
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
	}
	
	/* a relation between a product and it's packaging */
	function package($fpin)
	{
		$types='';
		// PRODUCTNDC	NDCPACKAGECODE	PACKAGEDESCRIPTION
		fgets($fpin); // header
		while($l = fgets($fpin,4096)) {
			$a = explode("\t",trim($l));
			$ndc_product = parent::getNamespace().$a[0];
			$ndc_package = parent::getNamespace().$a[1];
			$package_label = $a[2];
			parent::addRDF(
				parent::describeIndividual($ndc_package, $package_label, parent::getVoc()."Package").
				parent::triplify($ndc_product, parent::getVoc()."has-package", $ndc_package).
				parent::describeClass(parent::getVoc()."Package", "National Drug Code Package").
				parent::describeProperty(parent::getVoc()."has-package", "Relationship between an NDC product and its packaging")
			);
		
			// now parse out the types
			// multi-level packaging
			$b = explode(" > ",$a[2]);
			foreach($b AS $i => $c) {
				// get the type label by removing the inserted product code
				$type_label = preg_replace("/ \([0-9\-]+\) /","",$c);			
				
				// get the identifier preg_match("/ \([0-9\-]+\) /",$c,$type_id);	
				$type_uri   = parent::getVoc().md5($type_label);
				if(!isset($types[$type_uri])) {
					$types[$type_uri] = '';
					parent::addRDF(
						parent::describeClass($type_uri, $type_label, parent::getVoc()."Package")
					);
				}
				if($i == 0){
					parent::addRDF(
						parent::triplify($ndc_package, "rdf:type", $type_uri)
					);
				} else {
					parent::addRDF(
						parent::triplify($ndc_package, parent::getVoc()."has-part", $type_uri).
						parent::describeProperty(parent::getVoc()."has-part", "Relationship between an NDC entity and its part")
					);
				} 
				parent::WriteRDFBufferToWriteFile();
			}
		}
	}
	
	/* 
	0 PRODUCTNDC	
	1 PRODUCTTYPENAME	
	2 PROPRIETARYNAME	
	3 PROPRIETARYNAMESUFFIX	
	4 NONPROPRIETARYNAME	
	5 DOSAGEFORMNAME	
	6 ROUTENAME	
	7 STARTMARKETINGDATE	
	8 ENDMARKETINGDATE	
	9 MARKETINGCATEGORYNAME	
	10 APPLICATIONNUMBER	
	11 LABELERNAME	
	12 SUBSTANCENAME	
	13 ACTIVE_NUMERATOR_STRENGTH	
	14 ACTIVE_INGRED_UNIT	
	15 PHARM_CLASSES	
	16 DEASCHEDULE
	*/
	// 0002-1200	HUMAN PRESCRIPTION DRUG	Amyvid		Florbetapir F 18	INJECTION, SOLUTION	INTRAVENOUS	20120601		NDA	NDA202008	Eli Lilly and Company	FLORBETAPIR F-18	51	mCi/mL		
	function product($fpin)
	{
		$z = 0;
		$list = '';
		fgets($fpin); // header
		while($l = fgets($fpin, 10000)) {
			//if($z++ == 10) break;
			$a = explode("\t",$l);
			$ndc_product = parent::getNamespace().$a[0];
			// tradename + suffix + dosageform + strength + unit
			$label = $a[2];
			 
			parent::addRDF(
				parent::triplifyString($ndc_product, parent::getVoc()."trade-name", $a[2]).
				parent::describeProperty(parent::getVoc()."trade-name", "Relationship between an NDC product and its trade name")
			);

			if($a[3]) {
				parent::addRDF(
					parent::triplifyString($ndc_product, parent::getVoc()."trade-name-suffix", $a[3]).
					parent::describeProperty(parent::getVoc()."trade-name-suffix", "Relationship between an NDC product and its trade name suffix")
				);
				$label .= " ".$a[3];
			}

			if($a[4]) { // MV
				$b = explode(";",$a[4]);
				foreach($b AS $c) {
					parent::addRDF(
						parent::triplifyString($ndc_product, parent::getVoc()."non-proprietary-name", trim($c)).
						parent::describeProperty(parent::getVoc()."non-proprietary-name", "Relationship betweeen an NDC product and its non-proprietary name")
					);
				}
			}
			if($a[5]) {
				$dosageform = strtolower($a[5]);
				$dosageform_id = parent::getVoc().md5($dosageform);
				if(!isset($list[$dosageform_id])) {
					$list[$dosageform_id] = '';
					parent::addRDF(
						parent::describeClass($dosageform_id, $dosageform, parent::getVoc()."Dosage-Form").
						parent::describeClass(parent::getVoc()."Dosage-Form", "National Drug Code Directory Dosage Form")
					);
				}

				parent::addRDF(
					parent::triplify($ndc_product, parent::getVoc()."dosage-form", $dosageform_id).
					parent::describeProperty(parent::getVoc()."dosage-form", "Relationship between an NDC product and its dosage form")
				);
			}
			if($a[6]) { //  MV
				$b = explode("; ",$a[6]);
				foreach($b AS $c) {
					$route = strtolower(trim($c));
					$route_id = "ndc_vocabulary:".md5($route);
					if(!isset($list[$route_id])) {
						$list[$route_id] = '';

						parent::addRDF(
							parent::describeClass($route_id, $route, parent::getVoc()."Route").
							parent::describeClass(parent::getVoc()."Route", "National Drug Code Drug Route")
						);
					}

					parent::addRDF(
						parent::triplify($ndc_product, parent::getVoc()."route", $route_id).
						parent::describeProperty(parent::getVoc()."route", "Relationship between an NDC product and a route")
					);
				}
			}

			if($a[7]){
				parent::addRDF(
					parent::triplifyString($ndc_product, parent::getVoc()."start-marketing-date", $a[7]).
					parent::describeProperty(parent::getVoc()."start-marketing-date", "Relationship between an NDC product and its start marketing date")
				);
			}

			if($a[8]){
				parent::addRDF(
					parent::triplifyString($ndc_product, parent::getVoc()."end-marketing-date", $a[8]).
					parent::describeProperty(parent::getVoc()."end-marketing-date", "Relationship between an NDC product and its end marketing date")
				);
			}

			if($a[9]){
				parent::addRDF(
					parent::triplifyString($ndc_product, parent::getVoc()."marketing-category", $a[9]).
					parent::describeProperty(parent::getVoc()."marketing-category", "Relationship between an NDC product and its marketing category")
				);
			}
			if($a[10]){
				parent::addRDF(
					parent::triplifyString($ndc_product, parent::getVoc()."application-number", $a[10]).
					parent::describeProperty(parent::getVoc()."application-number", "Relationship between an NDC product and its application number")
				);
			}
			
			// create a labeller node
			if($a[11]) {
				$labeller_id = parent::getRes().md5($a[11]);
				$label = addslashes($a[11]);
				if(!isset($list[$labeller_id])) {
					$list[$labeller_id] = '';
					parent::addRDF(
						parent::describeIndividual($labeller_id, $label, parent::getVoc()."Labeller").
						parent::describeClass(parent::getVoc()."Labeller", "National Drug Code Directory Labeller")
					);
				}
				parent::addRDF(
					parent::triplify($ndc_product, parent::getVoc()."labeller", $labeller_id).
					parent::describeProperty(parent::getVoc()."labeller", "Relationship between an NDC product and a labeller")
				);
			}
			
			// the next three are together
			if($a[12]) { // MV
				$substances = explode(";",$a[12]);
				$strengths  = explode(";",$a[13]);
				$units      = explode(";",$a[14]);
				
				$l = '';
				foreach($substances AS $i => $substance) {
					// list the active ingredient
					$ingredient_label = strtolower($substance);
					$strength = '';
					if(isset($strengths[$i])) $strength= $strengths[$i];
					$unit = $units[$i];
					
					$ingredient_id = parent::getRes().md5($ingredient_label);
					if(!isset($list[$ingredient_id])) {
						$list[$ingredient_id] = '';
						parent::addRDF(
							parent::describeIndividual($ingredient_id, $ingredient_label, parent::getVoc()."Ingredient").
							parent::describeClass(parent::getVoc()."Ingredient", "National Drug Code Directory Ingredient")
						);
					}
					parent::addRDF(
						parent::triplify($ndc_product, parent::getVoc()."ingredient", $ingredient_id).
						parent::describeProperty(parent::getVoc()."ingredient", "Relationship between an NDC product and an ingredient")
					);
					
					// describe the substance composition
					$substance_label = "$strength $unit $ingredient_label";
					
					$substance_id = parent::getRes().md5($substance_label);
					if(!isset($list[$substance_id])) {
						$list[$substance_id] = '';
						parent::addRDF(
							parent::describeIndividual($substance_id, $substance_label, parent::getVoc()."Substance").
							parent::triplifyString($substance_id, parent::getVoc()."amount", $strength).
							parent::describeClass(parent::getVoc()."Substance", "National Drug Code Directory Substance").
							parent::describeProperty(parent::getVoc()."amount", "Relationship between and NDC substance and an amount")
						);

						$unit_id = parent::getVoc().md5($unit);
						if(!isset($list[$unit_id])) {
							$list[$unit_id] = '';
							parent::addRDF(
								parent::describeClass($unit_id, $unit, parent::getVoc()."Unit").
								parent::describeClass(parent::getVoc()."Unit", "National Drug Code Directory Unit")
							);
						}
						parent::addRDF(
							parent::triplify($substance_id, parent::getVoc()."amount_unit", $unit_id).
							parent::describeProperty(parent::getVoc()."amount_unit", "Relationship between an NDC substance and its unit")
						);
					}
					parent::addRDF(
						parent::triplify($ndc_product, parent::getVoc()."has-part", $substance_id)
					);
					
					$l .= $substance_label.", ";
				}
				$label .= " (".substr($l,0,-2).")";
			}	
			
			if($a[15]) { // MV
				$b = explode(", ",$a[15]);
				foreach($b AS $c) {
					$cat_id = parent::getVoc().md5($c);
					if(!isset($list[$cat_id])) {
						$list[$cat_id] = '';
						parent::addRDF(
							parent::describeClass($cat_id, $c, parent::getVoc()."Pharmacological-Class").
							parent::describeClass(parent::getVoc()."Pharmacological-Class", "National Drug Code Directory Pharmacological Class")
						);
					}
					parent::addRDF(
						parent::triplify($ndc_product, parent::getVoc()."pharmacological-class", $cat_id).
						parent::describeProperty(parent::getVoc()."pharmacological-class", "Relationship between and NDC product and its pharmacological class")
					);
				}
			}

			parent::addRDF(
				parent::describeIndividual($ndc_product, $label, parent::getVoc()."Product").
				parent::triplify($ndc_product, "rdf:type", parent::getVoc().str_replace(" ","-",strtolower($a[1]))).
				parent::describeClass(parent::getVoc()."Product", "National Drug Code Directory Drug Product").
				parent::describeClass(parent::getVoc().str_replace(" ","-",strtolower($a[1])), $a[1])
			);
			parent::WriteRDFBufferToWriteFile();
		}
	}
}
?>
