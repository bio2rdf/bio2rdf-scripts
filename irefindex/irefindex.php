<?php
/**
Copyright (C) 2011-2013 Michel Dumontier, Alison Callahan

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
 * An RDF generator for iRefIndex (http://irefindex.uio.no)
 * documentation: http://irefindex.uio.no/wiki/README_MITAB2.6_for_iRefIndex_9.0
 * @version 2.0
 * @author Michel Dumontier
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
class irefindexParser extends Bio2RDFizer 
{
	function __construct($argv) { //
		parent::__construct($argv,"irefindex");
		parent::addParameter('files',true,'all|10090|10116|4932|559292|562|6239|7227|9606|other','all','all or comma-separated list of files to process');
		parent::addParameter('version',false,'08122013|03022013|10182011','08122013','dated version of files to download');
		parent::addParameter('download_url',false,null,'ftp://ftp.no.embnet.org/irefindex/data/current/psi_mitab/MITAB2.6/');
		parent::initialize();
	}
	
	function Run()
	{
		// get the file list
		if(parent::getParameterValue('files') == 'all') {
			$files = array('all');
		} else {
			$files = explode(",",parent::getParameterValue('files'));
		}

		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$rdir = parent::getParameterValue('download_url');
			
		
		foreach($files AS $file) {
			$download = parent::getParameterValue('download');
			$base_file = ucfirst($file).".mitab.".parent::getParameterValue("version").".txt";
			$zip_file  = $base_file.".zip";
			$lfile = $ldir.$zip_file;
			
			$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
			$ofile = "irefindex-".$file.".".parent::getParameterValue('output_format');
			
			if(!file_exists($lfile)) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$download = true;
			}
			
			$rfile = "ftp://ftp.no.embnet.org/irefindex/data/current/psi_mitab/MITAB2.6/$zip_file";
			if($download == true) {
				echo "downloading $rfile".PHP_EOL;
				if(FALSE === Utils::Download("ftp://ftp.no.embnet.org",array("/irefindex/data/current/psi_mitab/MITAB2.6/".$zip_file),$ldir)) {
					trigger_error("Error in Download");
					return FALSE;
				}
			}
			
			$zin = new ZipArchive();
			if ($zin->open($lfile) === FALSE) {
				trigger_error("Unable to open $lfile");
				exit;
			}
			if(($fp = $zin->getStream($base_file)) === FALSE) {
					trigger_error("Unable to get $base_file in ziparchive $lfile");
					return FALSE;
			}
			parent::setReadFile($lfile);
			parent::getReadFile()->setFilePointer($fp);
				
			echo "Processing ".$file." ...";
			parent::setWriteFile($odir.$ofile, true);
	
			if($this->Parse() === FALSE) {
				trigger_error("Parsing Error");
				exit;
			}
			
			parent::writeRDFBufferToWriteFile();
			parent::getWriteFile()->close();
			$zin->close();
			echo "Done!".PHP_EOL;
		
			$graph_uri = parent::getGraphURI();
			if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());
			
			// dataset description
			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("iRefIndex ($zip_file")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
				->setFormat("text/tab-separated-value")
				->setFormat("application/zip")	
				->setPublisher("http://irefindex.uio.no")
				->setHomepage("http://irefindex.uio.no")
				->setRights("use")
				->setRights("by-attribution")
				->setRights("no-commercial")
				->setLicense("http://irefindex.uio.no/wiki/README_MITAB2.6_for_iRefIndex#License")
				->setDataset("http://identifiers.org/irefindex/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/irefindex/irefindex.php")
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
			parent::setGraphURI($graph_uri);
		}
		
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		
		return TRUE;
	}

	function Parse()
	{
		$l = parent::getReadFile()->read(100000);
		$header = explode("\t",trim(substr($l,1)));
		if(($c = count($header)) != 54) {
			trigger_erorr("Expecting 54 columns, found $c!");
			return FALSE;
		}

		// check # of columns
		while($l = parent::getReadFile()->read(100000)) {
			$a = explode("\t",trim($l));

			// 13 is the original identifier
			$ids = explode("|",$a[13],2);
			parent::getRegistry()->parseQName($ids[0],$ns,$str);
			
			$data = $this->ParseIDLabelArray($str);
			$id = str_replace('"','', trim($data["id"]));
			$label = trim($data["label"]);
			$iid = $ns.":".$id;

			// get the type
			if($a[52] == "X") {
				$label = "Pairwise interaction between $a[0] and $a[1]";
				$type = "Pairwise-Interaction";
			} else if($a[52] == "C") {
				$label = $a[53]." component complex";
				$type = "Multimeric-Complex";
			} else if($a[52] == "Y") {
				$label = "homomeric complex composed of $a[0]";  
				$type = "Homopolymeric-Complex";
			}

			// generate the label
			// interaction type[52] by method[6]
			unset($method);
			if($a[6] != '-') {
				$data = $this->ParseStringArray($a[6]);
				$method = trim($data["label"]);
				$qname = trim($data["ns"]).":".trim($data["id"]);
				if($qname) {
					parent::addRDF(parent::triplify($iid,parent::getVoc()."method",$qname));
				} 
			}

			$method_label = '';
			if(isset($method)) $method_label = " identified by $method ";
			parent::addRDF(
				parent::describeIndividual($iid,$label.$method_label,parent::getVoc().$type)
			);
			
			parent::addRDF(
				parent::QQuadO_URL($iid,"rdfs:seeAlso","http://wodaklab.org/iRefWeb/interaction/show/".$a[50])
			);

			// set the interactors
			for($i=0;$i<=1;$i++) {
				$p = 'a';
				if($i == 1) $p = 'b';

				$data = $this->ParseStringArray($a[$i]);
				$interactor = trim($data["ns"]).":".trim($data["id"]);
				parent::addRDF(
					parent::triplify($iid,parent::getVoc()."interactor_$p",$interactor)
				);

				// biological role
				$role = $a[16+$i];
				if($role != '-') {
					$data = $this->ParseStringArray($role);
					$qname = trim($data["ns"]).":".trim($data["id"]);
					if($qname != "mi:0000") {
						parent::addRDF(
							parent::triplify($iid,parent::getVoc()."interactor_$p"."_biological_role",$qname)
						);
					}
				}
				// experimental role
				$role = $a[18+$i];
				if($role != '-') {
					$data = $this->ParseStringArray($role);
					$qname = trim($data["ns"]).":".trim($data["id"]);
					if($qname != "mi:0000") {
						parent::addRDF(
							parent::triplify($iid,parent::getVoc()."interactor_$p"."_experimental_role",$qname)
						);
					}
				}
				// interactor type
				$type = $a[20+$i];
				if($type != '-') {
					$data = $this->ParseStringArray($type);
					$qname = trim($data["ns"]).":".trim($data["id"]);
					parent::addRDF(
						parent::triplify($interactor,"rdf:type",$qname)
					);
				}
			}

			// add the alternatives through the taxon + seq redundant group
			for($i=2;$i<=3;$i++) {
				$taxid = '';
				$irogid = "irefindex_irogid:".$a[42+($i-2)];
				if(!isset($defined[$irogid])) {
					$defined[$irogid] = '';
					parent::addRDF(
						parent::describeIndividual($irogid,"",parent::getVoc()."Taxon-Sequence-Identical-Group")
					);
					$tax = $a[9+($i-2)];
					if($tax && $tax != '-' && $tax != '-1') {
						$data = $this->ParseStringArray($tax);
						$taxid = trim($data["ns"]).":".trim($data["id"]);
						parent::addRDF(
							parent::triplify($irogid,parent::getVoc()."x-taxonomy",$taxid)
						);
					}
				}

				$list = explode("|",$a[3]);
				foreach($list AS $item) {
					$data = $this->ParseStringArray($item);
					$ns = trim($data["ns"]);
					$qname = $ns.":".trim($data["id"]);
					if($ns && $ns != 'irefindex_rogid' && $ns != 'irefindex_irogid') {
						parent::addRDF(
							parent::triplify($qname,parent::getVoc()."taxon-sequence-identical-group",$irogid)
						);	
						if($taxid && $taxid != '-' && $taxid != '-1') parent::addRDF(
							parent::triplify($qname,parent::getVoc()."x-taxonomy",$taxid)
						);
					}
				}
			}	
			// add the aliases through the canonical group
			for($i=4;$i<=5;$i++) {
				$icrogid = "irefindex_icrogid:".$a[49+($i-4)];
				if(!isset($defined[$icrogid])) {
					$defined[$icrogid] = '';
					parent::addRDF(
						parent::describeIndividual($icrogid, "",parent::getVoc()."Taxon-Sequence-Similar-Group")
					);
				}

				$list = explode("|",$a[3]);
				foreach($list AS $item) {
					$data = $this->ParseStringArray($item);
					$ns = trim($data["ns"]);
					$qname = $ns.":".trim($data["id"]);
					if($ns && $ns != 'crogid' && $ns != 'icrogid') {
						parent::addRDF(
							parent::triplify($qname,parent::getVoc()."taxon-sequence-similar-group",$icrogid)
						);	
					}
				}
			}

			// publications
			$list = explode("|",$a[8]);
			foreach($list AS $item) {
				if($item == '-' && $item != 'pubmed:0') continue;
				$data = $this->ParseStringArray($item);
				$qname = trim($data["ns"]).":".trim($data["id"]);
				parent::addRDF(
					parent::triplify($iid,parent::getVoc()."article",$qname)
				);
			}
			
			// MI interaction type
			if($a[11] != '-' && $a[11] != 'NA') {
				$data = $this->ParseStringArray($a[11]);
				$qname = trim($data["ns"]).":".trim($data["id"]);
				parent::addRDF(parent::triplify($iid,"rdf:type",$qname));
				if(!isset($defined[$qname])) {
					$defined[$qname] = '';
					parent::addRDF(
						parent::triplifyString($qname,"rdfs:label",$label)
					);
				}
			}
			
			// source
			if($a[12] != '-') {
				$data = $this->ParseStringArray($a[12]);
				$qname = trim($data["ns"]).":".trim($data["id"]);
				parent::addRDF(
					parent::triplify($iid,parent::getVoc()."source",$qname)
				);
			}
		
			// confidence
			$list = explode("|",$a[14]);
			foreach($list AS $item) {
				$data = $this->ParseStringArray($item);
				$ns = trim($data["ns"]);
				$id = trim($data["id"]);
				if($ns == 'lpr') {
					//  lowest number of distinct interactions that any one article reported
					parent::addRDF(
						parent::triplifyString($iid,parent::getVoc()."minimum-number-interactions-reported",$id)
					);
				} else if($ns == "hpr") {
					//  higher number of distinct interactions that any one article reports
					parent::addRDF(
						parent::triplifyString($iid,parent::getVoc()."maximum-number-interactions-reported",$id)
					);
				} else if($ns = 'hp') {
					//  total number of unique PMIDs used to support the interaction 
					parent::addRDF(
						parent::triplifyString($iid,parent::getVoc()."number-supporting-articles",$id)
					);				
				}
			}

			// expansion method
			if($a[15]) {
				parent::addRDF(
					parent::triplifyString($iid,parent::getVoc()."expansion-method",$a[15])
				);
			}

			// host organism
			if($a[28] != '-') {
				$data = $this->ParseStringArray($a[28]);
				$qname = trim($data["ns"]).":".trim($data["id"]);
				parent::addRDF(
					parent::triplify($iid,parent::getVoc()."host-organism",$qname)
				);
			}

			// @todo add to record
			// created 2010/05/18
			$date = str_replace("/","-",$a[30])."T00:00:00Z";
			parent::addRDF(
				parent::triplifyString($iid,"dc:created", $date,"xsd:dateTime")
			);

			// taxon-sequence identical interaction group
			parent::addRDF(
				parent::triplify($iid,parent::getVoc()."taxon-sequence-identical-interaction-group", "irefindex_irigid:".$a[44])
			);

			// taxon-sequence similar interaction group
			parent::addRDF(
				parent::triplify($iid,parent::getVoc()."taxon-sequence-similar-interaction-group", "irefindex_crigid:".$a[50])
			);

			parent::writeRDFBufferToWriteFile();
		}
	}

	function ParseStringArray($string){
		parent::getRegistry()->parseQName($string,$ns,$str);
		
		$rm = $this->ParseIDLabelArray($str);
		
		if($rm !== null){
			$id = trim($rm["id"]);
			$label = trim($rm["label"]);
			if($ns == 'other' || $ns == 'xx') $ns = '';
			if($ns == 'complex') $ns = 'rogid';

			$returnMe = array();
			$returnMe["label"] = $label;
			$returnMe["id"] = $id;
			$returnMe["ns"] = $ns;
			return $returnMe;
		} else {
			return null;
		}
	}

	function ParseIDLabelArray($string){
		preg_match("/([^()\s]+)(\((.*)\)|(\s*.*))/", $string, $m);
		if(isset($m[1])){
			$id = $m[1];
			$label = '';
			if(isset($m[3]) && !empty($m[3])){
				$label = $m[3];
			} elseif(isset($m[4]) && !empty($m[4])){
				$label = $m[4];
			}
			$first = substr($label, 0, 1);
			if($first == "("){
				$label = trim($label, "()");
				if(strpos($label, "(")){
					$label .= ")";
				}
			}
			$returnMe = array();
			$returnMe["id"] = $id;
			$returnMe["label"] = $label;
			return $returnMe;
		} else {
			return null;
		}
	}
	
}


?>
