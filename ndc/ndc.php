<?php
/**
Copyright (C) 2012 Michel Dumontier

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

require('../../php-lib/rdfapi.php');
/**
 * NDC RDFizer
 * @version 1.0
 * @author Michel Dumontier
 * @description http://www.fda.gov/Drugs/InformationOnDrugs/ucm142454.htm
*/
class NDCParser extends RDFFactory 
{
	private $version = null;
	
	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("ndc");
		
		// set and print application parameters
		$this->AddParameter('files',true,'all|product|package','all','files to process');
		$this->AddParameter('indir',false,null,'/data/download/ndc/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/ndc/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://www.fda.gov/downloads/Drugs/DevelopmentApprovalProcess/UCM070838.zip');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
		
		return TRUE;
	}
	
	function Run()
	{
		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rfile = $this->GetParameterValue('download_url');
		$lfile = substr($rfile, strrpos($rfile,"/")+1);
		
		// check if exists
		if(!file_exists($ldir.$lfile)) {
			trigger_error($ldir.$lfile." not found. Will attempt to download. ", E_USER_NOTICE);
			$this->SetParameterValue('download',true);
		}
		
		// download
		if($this->GetParameterValue('download') == true) {
			trigger_error("Downloading $rfile", E_USER_NOTICE);
			file_put_contents($ldir.$lfile, file_get_contents($rfile));
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
		
		// now go through each item in the zip file and process
		foreach($files AS $file) {
			echo "Processing $file ...";
			$fpin = $zin->getStream($file.".txt");
			if(!$fpin) {
				trigger_error("Unable to get pointer to $file in $zinfile");
				exit("failed\n");
			}
			
			// set the write file
			$outfile = $file.'.nt'; $gz=false;
			if($this->GetParameterValue('gzip')) {
				$outfile .= '.gz';
				$gz = true;
			}
			$bio2rdf_download_files[] = $this->GetBio2RDFDownloadURL($this->GetNamespace()).$outfile;
			$this->SetWriteFile($odir.$outfile, $gz);
			
			// process
			$this->$file($fpin);
			
			// write to file
			$this->WriteRDFBufferToWriteFile();
			$this->GetWriteFile()->Close();
			
			echo "done!".PHP_EOL;
		}
		
		
		// generate the release file
		$this->DeleteBio2RDFReleaseFiles($odir);
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/ndc/ndc.php", 
			$bio2rdf_download_files,
			"http://www.fda.gov/Drugs/InformationOnDrugs/ucm142438.htm",
			array("use-share"),
			null, //license
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
	}
	
	/* a relation between a product and it's packaging */
	function package($fpin)
	{
		$types='';
		// PRODUCTNDC	NDCPACKAGECODE	PACKAGEDESCRIPTION
		fgets($fpin); // header
		while($l = fgets($fpin,4096)) {
			$a = explode("\t",trim($l));
			$ndc_product = "ndc:$a[0]";
			$ndc_package = "ndc:$a[1]";
			$this->AddRDF($this->QQuad($ndc_product,  "ndc_vocabulary:package", $ndc_package));
			$this->AddRDF($this->QQuadL($ndc_package, "rdfs:label",$a[2]." [$ndc_package]"));
			$this->AddRDF($this->QQuad($ndc_package,  "rdf:type",  "ndc_vocabulary:Package"));
			$this->AddRDF($this->QQuad($ndc_package,  "void:inDataset",$this->GetDatasetURI()));
			
			// now parse out the types
			// multi-level packaging
			$b = explode(" > ",$a[2]);
			foreach($b AS $i => $c) {
				// get the type label by removing the inserted product code
				$type_label = preg_replace("/ \([0-9\-]+\) /","",$c);			
				
				// get the identifier preg_match("/ \([0-9\-]+\) /",$c,$type_id);	
				$type_uri   = "ndc_vocabulary:".md5($type_label);
				if(!isset($types[$type_uri])) {
					$types[$type_uri] = '';
					$this->AddRDF($this->QQuadL($type_uri, "rdfs:label", $type_label));
					$this->AddRDF($this->QQuad($type_uri, "rdfs:subClassOf", "ndc_vocabulary:Package"));
				}
				if($i == 0) $this->AddRDF($this->QQuad($ndc_package, "rdf:type", $type_uri));
				else $this->AddRDF($this->QQuad($ndc_package, "ndc_vocabulary:has-part", $type_uri));
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
			$ndc_product = "ndc:$a[0]";
			// tradename + suffix + dosageform + strength + unit
			$label = $a[2];
			 
			$this->AddRDF($this->QQuadL($ndc_product, "dc:identifier", $ndc_product));
			$this->AddRDF($this->QQuad($ndc_product,  "rdf:type",  "ndc_vocabulary:Product"));
			$this->AddRDF($this->QQuad($ndc_product,  "rdf:type",  "ndc_vocabulary:".str_replace(" ","-",strtolower($a[1]))));
			$this->AddRDF($this->QQuad($ndc_product,"void:inDataset",$this->GetDatasetURI()));
			
			$this->AddRDF($this->QQuadL($ndc_product,  "ndc_vocabulary:trade-name", $a[2]));
			if($a[3]) {
				$this->AddRDF($this->QQuadL($ndc_product,  "ndc_vocabulary:trade-name-suffix", $a[3]));
				$label .= " ".$a[3];
			}
			if($a[4]) { // MV
				$b = explode(";",$a[4]);
				foreach($b AS $c) {
					$this->AddRDF($this->QQuadL($ndc_product,  "ndc_vocabulary:non-proprietary-name", trim($c)));
				}
			}
			if($a[5]) {
				$dosageform = strtolower($a[5]);
				$dosageform_id = "ndc_vocabulary:".md5($dosageform);
				if(!isset($list[$dosageform_id])) {
					$list[$dosageform_id] = '';
					$this->AddRDF($this->QQuadL($dosageform_id,  "rdfs:label", $dosageform." [$dosageform_id]"));
					$this->AddRDF($this->QQuad($dosageform_id,  "rdfs:subClassOf", "ndc_vocabulary:Dosage-Form"));
				}
				$this->AddRDF($this->QQuad($ndc_product,  "ndc_vocabulary:dosage-form", $dosageform_id));
			}
			if($a[6]) { //  MV
				$b = explode("; ",$a[6]);
				foreach($b AS $c) {
					$route = strtolower(trim($c));
					$route_id = "ndc_vocabulary:".md5($route);
					if(!isset($list[$route_id])) {
						$list[$route_id] = '';
						$this->AddRDF($this->QQuadL($route_id,  "rdfs:label", $route." [$route_id]"));
						$this->AddRDF($this->QQuad($route_id,  "rdfs:subClassOf", "ndc_vocabulary:Route"));
					}
					$this->AddRDF($this->QQuad($ndc_product,  "ndc_vocabulary:route", $route_id));
				}
			}
			if($a[7])  $this->AddRDF($this->QQuadL($ndc_product,  "ndc_vocabulary:start-marketing-date", $a[7]));
			if($a[8])  $this->AddRDF($this->QQuadL($ndc_product,  "ndc_vocabulary:end-marketing-date", $a[8]));
			if($a[9])  $this->AddRDF($this->QQuadL($ndc_product,  "ndc_vocabulary:marketing-category", $a[9]));
			if($a[10]) $this->AddRDF($this->QQuadL($ndc_product,  "ndc_vocabulary:application-number", $a[10]));
			
			// create a labeller node
			if($a[11]) {
				$labeller_id = "ndc_resource:".md5($a[11]);
				if(!isset($list[$labeller_id])) {
					$list[$labeller_id] = '';
					$this->AddRDF($this->QQuadL($labeller_id,  "rdfs:label", addslashes($a[11])));
					$this->AddRDF($this->QQuad ($labeller_id,  "rdf:type", "ndc_vocabulary:Labeller"));
				}
				$this->AddRDF($this->QQuad($ndc_product,  "ndc_vocabulary:labeller", $labeller_id));
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
					
					$ingredient_id = "ndc_resource:".md5($ingredient_label);
					if(!isset($list[$ingredient_id])) {
						$list[$ingredient_id] = '';
						$this->AddRDF($this->QQuadL($ingredient_id, "rdfs:label", $ingredient_label." [$ingredient_id]"));
						$this->AddRDF($this->QQuad($ingredient_id, "rdf:type", "ndc_vocabulary:Ingredient"));
					}
					$this->AddRDF($this->QQuad($ndc_product,  "ndc_vocabulary:ingredient", $ingredient_id));
					
					// describe the substance composition
					$substance_label = "$strength $unit $ingredient_label";
					
					$substance_id = "ndc_resource:".md5($substance_label);
					if(!isset($list[$substance_id])) {
						$list[$substance_id] = '';
						$this->AddRDF($this->QQuadL($substance_id, "rdfs:label", $substance_label." [$substance_id]"));
						$this->AddRDF($this->QQuad($substance_id, "rdf:type", "ndc_vocabulary:Substance"));
						$this->AddRDF($this->QQuadL($substance_id, "ndc_vocabulary:amount", $strength));
						
						$unit_id = "ndc_vocabulary:".md5($unit);
						if(!isset($list[$unit_id])) {
							$list[$unit_id] = '';
							$this->AddRDF($this->QQuadL($unit_id, "rdfs:label", $unit." [$unit_id]"));
							$this->AddRDF($this->QQuad($unit_id, "rdfs:subClassOf", "ndc_vocabulary:Unit"));
						}
						$this->AddRDF($this->QQuad($substance_id, "ndc_vocabulary:amount_unit", $unit_id));
					}
					$this->AddRDF($this->QQuad($ndc_product,  "ndc_vocabulary:has-part", $substance_id));
					
					$l .= $substance_label.", ";
				}
				$label .= " (".substr($l,0,-2).")";
			}	
			
			if($a[15]) { // MV
				$b = explode(", ",$a[15]);
				foreach($b AS $c) {
					$cat_id = 'ndc_vocabulary:'.md5($c);
					if(!isset($list[$cat_id])) {
						$list[$cat_id] = '';
						$this->AddRDF($this->QQuadL($cat_id, "rdfs:label", $c." [$unit_id]"));
						$this->AddRDF($this->QQuad($cat_id, "rdfs:subClassOf", "ndc_vocabulary:Pharmacological-Class"));
					}
					$this->AddRDF($this->QQuad($ndc_product,  "ndc_vocabulary:pharmagocological-class", $cat_id));
				}
			}	
			$this->AddRDF($this->QQuadL($ndc_product, "rdfs:label", $label." [$ndc_product]"));
			
			//echo $this->GetRDF();exit;
		}
	}
}

set_error_handler('error_handler');
$parser = new NDCParser($argv);
$parser->Run();

?>
