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
		parent::addParameter('files',true,'all|10090|10116|4932|559292|562|6239|7227|9606|A','all','all or comma-separated list of files to process');
		parent::addParameter('version',false,'07042015|08122013|03022013|10182011','07042015','dated version of files to download');
		parent::addParameter('download_url',false,null,'http://irefindex.org/download/irefindex/data/current/psi_mitab/MITAB2.6/');
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
		$dataset_description = '';		

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

			$rfile = $rdir.$zip_file;
			if($download == true) {
				echo "downloading $rfile".PHP_EOL;
				if(FALSE === Utils::DownloadSingle($rfile,$lfile)) {
					trigger_error("Error in Download");
					return FALSE;
				}
			}
			
			$zin = new ZipArchive();
			if ($zin->open($lfile) === FALSE) {
				trigger_error("Unable to open $lfile",E_USER_ERROR);
				exit;
			}
			$ifile = $file.".mitab.04072015.txt"; // introduced because of file name change in this release
			if(($fp = $zin->getStream($ifile)) === FALSE) {
					trigger_error("Unable to get $ifile in ziparchive $lfile",E_USER_ERROR);
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
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix - $file")
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
			trigger_erorr("Expecting 54 columns, found $c!",E_USER_ERROR);
			return FALSE;
		}
		// check # of columns
		while($l = parent::getReadFile()->read(500000)) {
			$a = explode("\t",trim($l));
			// irefindex identifiers
			$rigid  = "irefindex.".$a[34];     # checksum for interaction
			$rogida = "irefindex.".$a[32];     # checksum for A
			$rogidb = "irefindex.".$a[33];     # checksum for B
			$irigid   = "irefindex.irigid:".$a[44];   # integer id for interaction
			$irogida  = "irefindex.irogid:".$a[42];   # integer id for A 
			$irogidb  = "irefindex.irogid:".$a[43];   # integer id for B
			$crigid   = "irefindex.crigid:".$a[47];   # checksum for canonical interaction
			$icrigid  = "irefindex.icrigid:".$a[50];  # integer id for canonical interaction
			$crogida  = "irefindex.crogid:".$a[45];   # checksum for A's canonical group
			$crogidb  = "irefindex.crogid:".$a[46];  # checksum for B's canonical group
			$icrogida = "irefindex.icrogid:".$a[48]; # integer for A's canonical group
			$icrogidb = "irefindex.icrogid:".$a[49];  # integer for B's canonical group


			// 13 contains the original identifier, the rigid, and the edgetype
			$ids = explode("|",$a[13]);
			if(count($ids) != 3) {
				trigger_error("Expecting 3 entries in column 14");
				print_r($ids);
				exit;
			}
			parent::getRegistry()->parseQName($ids[0],$ns,$id);
			if($id == '-') {
				// this happens with hprd
				$iid = "hprd:".substr($ids[1],6);
			} else if($ns=="pubmed") {
				$data = $this->ParseStringArray($a[12]);
				$ns = $data['label'];
				continue;
			} else {
				$iid = $ns.":".$id;
			}

			// get the type
			if($a[52] == "X") {
				$label = "$a[0] - $a[1] Interaction";
				$type = "Pairwise-Interaction";
			} else if($a[52] == "C") {
				$label = $a[53]." component complex"; #num of participants
				$type = "Multimeric-Complex";
			} else if($a[52] == "Y") {
				$label = "$a[0] homomeric complex";  
				$type = "Homopolymeric-Complex";
			}
			parent::addRDF(
				parent::describeIndividual($iid, $label, parent::getVoc().$type).
				parent::describeClass(parent::getVoc().$type, str_replace("-"," ",$type))
			);

			// interaction type[52] by method[6]
			unset($method);
			if($a[6] != '-') {
				$data = $this->ParseStringArray($a[6]);
				$method = trim($data["label"]);
				$qname = trim($data["ns"]).":".trim($data["id"]);
				if($qname) {
					parent::addRDF(
						parent::triplify($iid,parent::getVoc()."method",$qname).
						parent::describeClass($qname,$data['label'])
					);
				}
			}

			parent::addRDF(
				parent::triplify($iid,"rdfs:seeAlso","http://wodaklab.org/iRefWeb/interaction/show/".$a[50])
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
							parent::triplify($iid,parent::getVoc()."interactor_$p"."_biological_role",$qname).
							parent::describeClass($qname,$data['label'])
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
							parent::triplify($iid,parent::getVoc()."interactor_$p"."_experimental_role",$qname).
							parent::describeClass($qname,$data['label'])
						);
					}
				}
				// interactor type
				$type = $a[20+$i];
				if($type != '-') {
					$data = $this->ParseStringArray($type);
					$qname = trim($data["ns"]).":".trim($data["id"]);
					parent::addRDF(
						parent::triplify($interactor,"rdf:type",$qname).
						parent::describeClass($qname,$data['label'])
					);
				}
			}

			// add the alternatives through the taxon + seq redundant group
			for($i=2;$i<=3;$i++) {
				$taxid = '';
				$rogid = "irefindex.".$a[32+($i-2)];
				parent::addRDF(
					parent::describeIndividual($rogid,"",parent::getVoc()."Taxon-Sequence-Identical-Group").
					parent::describeClass(parent::getVoc()."Taxon-Sequence-Identical-Group","Taxon + Sequence Identical Group")
				);
				$tax = $a[9+($i-2)];
				if($tax && $tax != '-' && $tax != '-1') {
					$data = $this->ParseStringArray($tax);
					$taxid = trim($data["ns"]).":".trim($data["id"]);
					parent::addRDF(
						parent::triplify($rogid, parent::getVoc()."x-taxonomy", $taxid)
					);
				}

				$list = explode("|",$a[3]);
				foreach($list AS $item) {
					$data = $this->ParseStringArray($item);
					$ns = trim($data["ns"]);
					$id = trim($data["id"]);
					$qname = $ns.":".$id;
					if($ns && $ns != 'rogid' && $ns != 'irogid' and $id != '-') {
						parent::addRDF(
							parent::triplify($rogid,parent::getVoc()."has-member",$qname)
						);
						if($taxid && $taxid != '-' && $taxid != '-1') parent::addRDF(
							parent::triplify($qname,parent::getVoc()."x-taxonomy",$taxid)
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
						parent::triplifyString($qname,"rdfs:label",$data['label'])
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
				$id = parent::getRes().md5($a[15]);
				parent::addRDF(
					parent::describeIndividual($id, $a[15], parent::getVoc()."Expansion-Method").
					parent::describeClass(parent::getVoc()."Expansion-Method","Expansion Method").
					parent::triplify($iid,parent::getVoc()."expansion-method",$id)
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
				parent::triplify($iid,     parent::getVoc()."taxon-sequence-identical-interaction", $rigid).
				parent::triplify($rigid,   "rdf:type", parent::getVoc()."Taxon-Sequence-Identical-Interaction").
				parent::describeClass(parent::getVoc()."Taxon-Sequence-Identical-Interaction","Taxon + Sequence Identical Interaction").
				parent::triplify($rigid,   parent::getVoc()."irigid", $irigid).
				parent::triplify($rigid,   parent::getVoc()."interactor-a", $rogida).
				parent::triplify($rogida,  parent::getVoc()."irogid", $irogida).
				parent::triplify($rigid,   parent::getVoc()."interactor-b", $rogidb).
				parent::triplify($rogidb,  parent::getVoc()."irogid", $irogidb).
				parent::triplify($rogida,  parent::getVoc()."canonical-group", $crogida).
				parent::triplify($rogidb,  parent::getVoc()."canonical-group", $crogidb).

				parent::triplify($rigid,   parent::getVoc()."taxon-sequence-similar-interaction", $crigid).
				parent::triplify($crigid,   "rdf:type", parent::getVoc()."Taxon-Sequence-Canonical-Interaction").
				parent::describeClass(parent::getVoc()."Taxon-Sequence-Canonical-Interaction","Taxon + Sequence Canonical Interaction").
				parent::triplify($crigid,  parent::getVoc()."icrigid", $icrigid).

				parent::triplify($crigid,  parent::getVoc()."interactor-a-canonical-group", $crogida).
				parent::triplify($crogida, "rdf:type", parent::getVoc()."Taxon-Sequence-Similar-Group").
				parent::triplify($crogida, parent::getVoc()."icrogid", $icrogida).

				parent::triplify($crigid,  parent::getVoc()."interactor-b-canonical-group", $crogidb).
				parent::triplify($crogidb, "rdf:type", parent::getVoc()."Taxon-Sequence-Similar-Group").
				parent::triplify($crogidb, parent::getVoc()."icrogid", $icrogidb).
				parent::describeClass(parent::getVoc()."Taxon-Sequence-Similar-Group","Taxon + Sequence Similar Group")

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
