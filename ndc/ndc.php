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
		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rfile = $this->GetParameterValue('download_url');
		$lfile = substr($rfile, strrpos($rfile,"/")+1);

		// check if exists
		if(!file_exists($ldir.$lfile) or parent::getParameterValue('download') == 'true') {
			echo "dowloading $rfile ...";
			trigger_error("Will attempt to download ", E_USER_NOTICE);
			Utils::DownloadSingle($rfile, $ldir.$lfile);
			echo "done".PHP_EOL;
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


		$gz=false;
		if(strstr(parent::getParameterValue('output_format'), "gz")) $gz = true;
		$outfile = "ndc.".parent::getParameterValue('output_format');
		parent::setWriteFile($odir.$outfile, $gz);


		// now go through each item in the zip file and process
		foreach($files AS $file) {
			echo "Processing $file... ";

			$fpin = $zin->getStream($file.".txt");
			if(!$fpin) {
				trigger_error("Unable to get pointer to $file in $ldir$lfile", E_USER_ERROR);
				return FALSE;
			}

			$this->$file($fpin);
			parent::writeRDFBufferToWriteFile();
			echo "done!".PHP_EOL;
		}

		parent::getWriteFile()->close();

		echo "Generating dataset description for $outfile... ";

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


		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");
		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$outfile")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix")
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

		$dataset_description = $source_file->toRDF(). $output_file->toRDF();

		//write dataset description to file
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;
	}

	/* a relation between a product and it's packaging */
	function package($fpin)
	{
		$types='';
		// PRODUCTID PRODUCTNDC	NDCPACKAGECODE	PACKAGEDESCRIPTION
		fgets($fpin); // header
		while($l = fgets($fpin,4096)) {
			$a = explode("\t",rtrim($l));
			if(count($a) != 4) {trigger_error("Expecting 4 columns, instead found ".count($a));}

			$ndc_product = parent::getNamespace().$a[0];
			$ndc_package = parent::getNamespace().$a[2];
			$package_label = $a[3];
			parent::addRDF(
				parent::describeIndividual($ndc_package, $package_label, parent::getVoc()."Package").
				parent::triplify($ndc_product, parent::getVoc()."has-package", $ndc_package).
				parent::triplifyString($ndc_product, parent::getVoc()."ndc-product-id", $a[1]).
				parent::describeClass(parent::getVoc()."Package", "NDC Package")
			);

			// now parse out the components
			// multi-level packaging
			$b = explode(" > ",$package_label);
			foreach($b AS $i => $label) {
				// get the type label by removing the inserted product code
				$id = parent::getRes().md5($label);

				parent::addRDF(
					parent::describeIndividual($id, $label, parent::getVoc()."Package-Component").
					parent::describeClass(parent::getVoc()."Package-Component","NDC Package Component").
					parent::triplify($ndc_package, parent::getVoc()."has-part", $id)
				);
			}
			parent::WriteRDFBufferToWriteFile();

		}
	}
	
	/* 
	0 PRODCUTID
	1 PRODUCTNDC	
	2 PRODUCTTYPENAME	
	3 PROPRIETARYNAME	
	4 PROPRIETARYNAMESUFFIX	
	5 NONPROPRIETARYNAME	
	6 DOSAGEFORMNAME	
	7 ROUTENAME	
	8 STARTMARKETINGDATE	
	9 ENDMARKETINGDATE	
	10 MARKETINGCATEGORYNAME	
	11 APPLICATIONNUMBER	
	12 LABELERNAME
	13 SUBSTANCENAME
	14 STRENGTHNUMBER
	15 UNIT
	16 PHARM_CLASSES
	17 DEASCHEDULE
	*/
	// 0002-1200	HUMAN PRESCRIPTION DRUG	Amyvid		Florbetapir F 18	INJECTION, SOLUTION	INTRAVENOUS	20120601		NDA	NDA202008	Eli Lilly and Company	FLORBETAPIR F-18	51	mCi/mL		
	function product($fpin)
	{
		$z = 0;
		$list = '';
		fgets($fpin); // header
		while($l = fgets($fpin, 100000)) {
			$a = explode("\t",$l);

			if(count($a) != 18) {trigger_error("Expected 18 coloumns, instead found".count($a)); continue;}
			$product_id = parent::getNamespace().$a[0];
			$product_label = $a[3];
			$product_type_label = ucfirst(strtolower($a[2]));
			$product_type = parent::getVoc().str_replace(" ","-",$product_label);

			parent::addRDF(
				parent::describeIndividual($product_id, $a[3], parent::getVoc()."Product").
				parent::describeClass(parent::getVoc()."Product","NDC Product").
				parent::triplify($product_id, parent::getVoc()."product-type", $product_type).
				parent::describeIndividual($product_type, $product_type_label, parent::getVoc()."Product-Type").
				parent::describeClass(parent::getVoc()."Product-Type","Product Type").

				parent::triplifyString($product_id, parent::getVoc()."product-id", $a[1]).
				parent::triplifyString($product_id, parent::getVoc()."proprietary-name", $a[3]).
				parent::triplifyString($product_id, parent::getVoc()."trade-name-suffix", $a[4])
			);

			if($a[5]) {
				$b = explode(";",$a[5]);
				foreach($b AS $c) {
					parent::addRDF(
						parent::triplifyString($product_id, parent::getVoc()."non-proprietary-name", trim($c))
					);
				}
			}
			if($a[6]) {
				$b = explode(",", $a[6]);
				foreach($b AS $c) {
					$dosageform = strtolower($c);
					$dosageform_id = parent::getVoc().str_replace(" ","-",ucfirst(strtolower($c)));
					parent::addRDF(
						parent::describeIndividual($dosageform_id, $dosageform, parent::getVoc()."Dosage-Form").
						parent::describeClass(parent::getVoc()."Dosage-Form", "NDC Dosage Form").
						parent::triplify($product_id, parent::getVoc()."dosage-form", $dosageform_id)
					);
				}
			}
			if($a[7]) { //  MV
				$b = explode("; ",$a[7]);
				foreach($b AS $c) {
					$route = strtolower(trim($c));
					$route_id = parent::getVoc().str_replace(" ","-",ucfirst(strtolower($c)));
					parent::addRDF(
						parent::describeIndividual($route_id, $route, parent::getVoc()."Route").
						parent::describeClass(parent::getVoc()."Route", "NDC Drug Route").
						parent::triplify($product_id, parent::getVoc()."route", $route_id)
					);
				}
			}

			if($a[8]){
				$date = substr(0,4,$a[8])."-".substr(4,2,$a[8])."-".substr(6,2,$a[8]);
				parent::addRDF(
					parent::triplifyString($product_id, parent::getVoc()."start-marketing-date", $date)
				);
			}

			if($a[9]){
				$date = substr(0,4,$a[9])."-".substr(4,2,$a[9])."-".substr(6,2,$a[9]);
				parent::addRDF(
					parent::triplifyString($product_id, parent::getVoc()."end-marketing-date", $date)
				);
			}

			if($a[10]){
				parent::addRDF(
					parent::triplifyString($product_id, parent::getVoc()."marketing-category", $a[10])
				);
			}
			if($a[11]){
				parent::addRDF(
					parent::triplifyString($product_id, parent::getVoc()."application-number", $a[11])
				);
			}

			// create a labeller node
			if($a[12]) {
				$labeller_id = parent::getRes().md5($a[12]);
				$label = addslashes($a[12]);
				parent::addRDF(
					parent::describeIndividual($labeller_id, $label, parent::getVoc()."Labeller").
					parent::describeClass(parent::getVoc()."Labeller", "NDC Labeller").
					parent::triplify($product_id, parent::getVoc()."labeller", $labeller_id)
				);
			}

			// the next three are together
			if($a[13]) { // MV
				$substances = explode(";",$a[13]);
				$strengths  = explode(";",$a[14]);
				$units      = explode(";",$a[15]);

				$l = '';
				foreach($substances AS $i => $substance) {
					// list the active ingredient
					$ingredient_label = strtolower($substance);
					$strength = '';
					if(isset($strengths[$i])) $strength= $strengths[$i];
					$unit = $units[$i];

					$ingredient_id = parent::getRes().md5($ingredient_label);
					parent::addRDF(
						parent::describeIndividual($ingredient_id, $ingredient_label, parent::getVoc()."Ingredient").
						parent::describeClass(parent::getVoc()."Ingredient", "NDC Ingredient").
						parent::triplify($product_id, parent::getVoc()."ingredient", $ingredient_id)
					);

					// describe the substance composition
					$substance_label = "$strength $unit $ingredient_label";
					$substance_id = parent::getRes().md5($substance_label);
					parent::addRDF(
						parent::describeIndividual($substance_id, $substance_label, parent::getVoc()."Substance").
						parent::triplifyString($substance_id, parent::getVoc()."amount", $strength).
						parent::describeClass(parent::getVoc()."Substance", "NDC Substance")
					);

					$unit_id = parent::getVoc().md5($unit);
					parent::addRDF(
						parent::describeIndividual($unit_id, $unit, parent::getVoc()."Unit").
						parent::describeClass(parent::getVoc()."Unit", "NDC Unit").
						parent::triplify($substance_id, parent::getVoc()."amount_unit", $unit_id).
						parent::triplify($product_id, parent::getVoc()."has-part", $substance_id)
					);
				}
			}

			if($a[16]) { // MV
				$b = explode(",",$a[16]);
				foreach($b AS $c) {
					$cat_id = parent::getVoc().md5($c);
					parent::addRDF(
						parent::describeIndividual($cat_id, $c, parent::getVoc()."Pharmacological-Class").
						parent::describeClass(parent::getVoc()."Pharmacological-Class", "NDC Pharmacological Class").
						parent::triplify($product_id, parent::getVoc()."pharmacological-class", $cat_id)
					);
				}
			}
			parent::WriteRDFBufferToWriteFile();
		}
	}
}
?>
