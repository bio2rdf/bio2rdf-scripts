<?php
/**
Copyright (C) 2014 Michel Dumontier

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
 * An RDF generator for KEGG
 * documentation: 
 * @version 1.0
 * @author Michel Dumontier
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class KEGGParser extends Bio2RDFizer 
{
	function __construct($argv) {
		parent::__construct($argv, "kegg");
		parent::addParameter('files',true,'all|pathway|disease|drug|compound|genome|genes|enzyme|reaction|ko|module|environ|glycan|rpair|rclass','all','all or comma-separated list of kegg databases'); // brite|
		parent::addParameter('download_url',false,null,'http://rest.kegg.jp/','The KEGG REST API');
		parent::initialize();
	}

	function run() 
	{
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$dataset_description = '';
		
		$files = parent::getParameterValue('files');
		if($files == 'all') {
			$files = explode('|', parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(',', parent::getParameterValue('files'));
		}
		if(parent::getParameterValue('id_list') != '') {
			$this->idlist = explode(",",parent::getParameterValue("id_list"));
		}

		// handle genes separately
		if(in_array("genes",$files)) {	
			$orgs = array("hsa"); //,"mmu","eco","dre","dme","ath","sce","ddi");
			echo "processing genes".PHP_EOL;
			
			$ofile = "kegg-genes.".parent::getParameterValue('output_format'); 
			$gz = strstr(parent::getParameterValue('output_format'),"gz")?true:false;
			parent::setWriteFile($odir.$ofile, $gz);	

			// get the list of genomes
			$lfile = $ldir."genome.txt";
			$rfile = parent::getParameterValue("download_url")."list/genome";
			if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
				$ret = utils::downloadSingle($rfile,$lfile);
			}
			$fp = fopen($lfile,"r");
			while($l = fgets($fp)) {
				$a = explode("\t",$l);
				$b = explode(", ",$a[1]);
				$org = $b[0];
				
				if(!in_array($org,$orgs)) continue;
				
				// get the list of genes for this organims
				echo "processing $org".PHP_EOL;
				$this->org = $org; // local variable
				
				$lfile = $ldir.$org.".txt";
				$rfile = parent::getParameterValue("download_url")."list/$org";
				if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
					$ret = utils::downloadSingle($rfile,$lfile);
				}
				parent::setReadFile($lfile,false);
				$this->process("gene");
				parent::getReadFile()->close();
				parent::clear();
				$this->org = null;
				
				// add dataset description
				$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("KEGG: Gene")
				->setRetrievedDate( parent::getDate(filemtime($lfile)))
				->setFormat("text/plain")
				->setPublisher("http://www.kegg.jp/")
				->setHomepage("http://www.kegg.jp/")
				->setRights("use")
				->setRights("no-commercial")
				->setLicense("http://www.kegg.jp/kegg/legal.html")
				->setDataset("http://identifiers.org/kegg/");

				$dataset_description .= $source_file->toRDF();
			}
			fclose($fp);
			
			parent::getWriteFile()->close();
			echo "done".PHP_EOL;	

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = parent::getDate(filemtime($odir.$ofile));

			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix - Gene ")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/kegg/kegg.php")
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
		}
		
		// all other files
		foreach($files AS $db) {
			if($db == "genes") continue;
			echo "processing $db".PHP_EOL;			
			$lfile = $ldir.$db.".txt";
			$rfile = parent::getParameterValue("download_url")."list/$db";
			if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
				echo "Downloading $rfile ";
				$ret = utils::downloadSingle($rfile,$lfile);
				if($ret === false) {
					echo "unable to download $file ... skipping".PHP_EOL;
					continue;
				}
				echo "done.".PHP_EOL;
			}
			
			// now for each list, get the individual entries	
			$ofile = "kegg-$db.".parent::getParameterValue('output_format'); 
			$gz = strstr(parent::getParameterValue('output_format'),"gz")?true:false;
			
			parent::setReadFile($lfile,false);	
			parent::setWriteFile($odir.$ofile, $gz);
			$this->process($db);
			parent::getWriteFile()->close();
			parent::getReadFile()->close();
			parent::clear();
			echo "done!".PHP_EOL;
			
			// add dataset description
			$source_file = (new DataResource($this))
			->setURI($rfile)
			->setTitle("KEGG: $db")
			->setRetrievedDate( parent::getDate(filemtime($lfile)))
			->setFormat("text/plain")
			->setPublisher("http://www.kegg.jp/")
			->setHomepage("http://www.kegg.jp/")
			->setRights("use")
			->setRights("no-commercial")
			->setLicense("http://www.kegg.jp/kegg/legal.html")
			->setDataset("http://identifiers.org/kegg/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = parent::getDate(filemtime($odir.$ofile));

			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix - $db ")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/kegg/kegg.php")
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
		// write the dataset description
		$this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
		$this->getWriteFile()->write($dataset_description);
		$this->getWriteFile()->close();
	}
	
	function process($db)
	{
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		
		while($l = parent::getReadFile()->read()) {
			list($nsid,$name) = explode("\t",$l);
			list($ns,$id) = explode(":",$nsid);
			
			if(isset($this->idlist) and !in_array($id,$this->idlist)) continue;
			
			if(isset($this->org)) {
				$id = $ns."_".$id;
			}
			$uri = $this->getNamespace().$id;
			parent::addRDF(
				parent::describeIndividual($uri,$name,parent::getVoc().ucfirst($db)).
				parent::describeClass(parent::getVoc().ucfirst($db),"KEGG $db").
				parent::triplifyString($uri,parent::getVoc()."internal-id",$nsid)
			);

			// now get the entries for each
			$lfile = $ldir.$id.".txt";
			$rfile = parent::getParameterValue("download_url")."get/$nsid";
			if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
				echo "downloading $nsid ";
				$ret = utils::downloadSingle($rfile,$lfile);
				if($ret === false) {
					echo "unable to download ".$nsid." ... skipping".PHP_EOL;
					continue;
				}
				echo "done. ";
			}
			
			echo "parsing $nsid ... ";
			$this->parseEntry($lfile);
			parent::writeRDFBufferToWriteFile();
			
			if($db === "pathway") {
					$ko = str_replace("map","ko",$id);
					$lfile = $ldir.$id.".kgml";
					$rfile = "http://www.kegg.jp/kegg-bin/download?entry=".$ko."&format=kgml";
					if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
						echo "downloading KGML for $nsid ";
						$ret = utils::downloadSingle($rfile,$lfile);
						if($ret === false) {
							echo "unable to download ".$nsid." ... skipping".PHP_EOL;
							continue;
						}
						echo "done. ";
					}
					$this->parseKGML($lfile);
					parent::writeRDFBufferToWriteFile();
			}
			echo "done!".PHP_EOL;
		}
	}
		
	function parseEntry($lfile)
	{
		$fp = fopen($lfile,"r");
		while($l = fgets($fp,100000)) {
			$k_t = trim(substr($l,0,12));			
			$v = trim(substr($l,12));
			if(!$k_t and $v == '') continue;
			
			// set the key to the current key if not empty, else keep using what was there before
			if(!isset($k)) $k = $k_t;
			else if(!empty($k_t)) $k = $k_t;
			if($k == "///" or $k == "ENTRY1") break;
			
			if($k == "ENTRY") {
				$a = explode("  ",$v,2);
				$e['id'] = str_replace(array("EC "," "),"",$a[0]);
				if(isset($this->org)) $e['id'] = ($this->org)."_".$e['id'];
				
				$e['type'] = trim(str_replace(array("Complete ","Pathway   Module"),array("","Pathway Module"),$a[1]));
				$e['type_label'] = str_replace(" ","-",$e['type']);
				$uri = parent::getNamespace().$e['id'];
				continue;
			}
			// key with value
			if(in_array($k, array("NAME","DESCRIPTION","DEFINITION","EQUATION","COMMENT"))) {
				if($k == "NAME") {
					parent::addRDF(
						parent::describeIndividual($uri,$v,parent::getVoc().$e['type']).
						parent::describeClass(parent::getVoc().$e['type'],$e['type_label']).
						parent::triplify($uri, "rdfs:seeAlso", "http://www.kegg.jp/dbget-bin/www_bget?".$e['id'])
					);

					if($e['type'] == 'Genome') {
						$a = explode(",",$v);
						parent::addRDF(
							parent::triplify($uri,"owl:sameAs","kegg:".$a[0])
						);
					} 
				} else if($k == "DESCRIPTION") {
					parent::addRDF(
						parent::triplifyString($uri,"dc:description",$v)
					);
				} else if($k == "DEFINITION" and $e['type'] == "KO") {
						preg_match("/\[([^\]]+)\]/",$v,$m);
						if(isset($m[1])) {
							parent::addRDF(
								parent::triplify($uri,parent::getVoc()."x-ec",$m[1])
							);
						}
				} else if($k == "COMMENT") {
					preg_match("/ICD-O: ([^,]+),/",$v,$m);
					if(isset($m[1])) {
						parent::addRDF(
							parent::triplify($uri,parent::getVoc()."x-icdo","icdo:".$m[1])
						);
						continue;
					}
				} else {
					parent::addRDF(
						parent::triplifyString($uri,parent::getVoc().strtolower($k),$v)
					);			
				}
				continue;
			}
			
			if($k == "RPAIR" and $e['type'] == "Reaction") {
				$list = explode(" ",$v);
				$id = parent::getRes().$e['id'].".".$list[2].".".$list[3];
				$rc = '';
				if(isset($list[4])) $rc = "kegg:".substr($list[4],4,-1);
				parent::addRDF(
					parent::describeIndividual($id, $e['id']." ".$v, parent::getVoc()."RPair-Role").
					parent::describeClass(parent::getVoc()."RPair-Role","RPair Role").
					parent::triplify($id, parent::getVoc()."rpair", "kegg:".$list[0]).
					parent::triplifyString($id, parent::getVoc()."role", $list[3]).
					($rc!=''? parent::triplify($id, parent::getVoc()."reaction-center", $rc):'').
					parent::triplify($uri, parent::getVoc()."rpair-role", $id)
				);
				continue;
			}

			// list of entries
			if(in_array($k, array("ENZYME","RPAIR","RELATEDPAIR"))
			   or (in_array($e['type'],array("Compound","RClass","RPair")) and $k == "REACTION") ) {
				$list = explode(" ",$v);
				foreach($list AS $id) {
					if(!$id) continue;
					parent::addRDF(
						parent::triplify($uri,parent::getVoc().strtolower($k),"kegg:$id")
					);
				}
				continue;
			}
			
			// key with semi-colon separated values
			if(in_array($k, array("CLASS","CATEGORY","KEYWORDS","CHROMOSOME","ANNOTATION","ACTIVITY","TYPE"))) {  
				$a = explode(";",$v);
				foreach($a AS $c) {
					parent::addRDF(
						parent::triplifyString($uri,parent::getVoc().strtolower($k),trim($c))
					);	
				}
				continue;
			}
			
			// kegg seems to make a prefix mistake with the pathway identifiers...
			if($k == "PATHWAY") {
				$a = explode("  ",$v,2);
				preg_match("/[a-z]+([0-9]{5})/",$a[0],$m);
				if(isset($m[1])) {
					parent::addRDF(
						parent::triplify($uri,parent::getVoc().strtolower($k),"kegg:map".$m[1])
					);
				} else {
					echo "pathway problem: ".$v.PHP_EOL;
				}
				continue;
			}
			
			// multi-line header with key-value pair
			if(in_array($k, array("PATHWAY_MAP","STR_MAP","MODULE","DISEASE","KO_PATHWAY","COMPOUND"))) {
				// PATHWAY_MAP map00010  Glycolysis / Gluconeogenesis
				$a = explode("  ",$v,2);
				$mid = $a[0];
				if(strpos($a[0],'(') !== FALSE) {
					$mid = substr($a[0],0,strpos($a[0],'('));
				}
				if(isset($this->org) and $k == "MODULE") {
					$mid = substr($mid,strpos($v,"_")+1);
				}
				parent::addRDF(
					parent::triplify($uri,parent::getVoc().strtolower($k),"kegg:".$mid)
				);
				continue;
			}
			
			// REACTION parsing
			if(preg_match("/\[RN:([^\]]+)]/",$v,$m) != FALSE) {
				$list = explode(" ",$m[1]);
				foreach($list AS $item) {
					parent::addRDF(
						parent::triplify($uri,parent::getVoc().strtolower($k),"kegg:".$item)
					);
				}
				continue;
			}
			
			if($k == "DRUG") {
				preg_match("/\[DR:([^\]]+)]/",$v,$m);
				if(isset($m[1])) {
					$list = explode(" ",$m[1]);
					foreach($list AS $item) {
						parent::addRDF(
							parent::triplify($uri,parent::getVoc().strtolower($k),"kegg:".$item)
						);
					}
					continue;
				}
			}
			if($k == "TAXONOMY") {
				parent::addRDF(
					parent::triplify($uri,parent::getVoc().strtolower($k),"kegg:".str_replace("TAX","taxonomy",$v))
				);	
				continue;
			} 
			
			// a list of objects to parse out that are defined within square brackets
			if(in_array($k, array("SOURCE","COMPONENT"))) {
				preg_match_all("/\[([^\]]+)\]/",$v,$m);
				if(isset($m[1])) {
					foreach($m[1] AS $id) {
						$myid = str_replace(array("TAX","CPD","DR"), array("taxonomy","kegg","kegg"),$id);
						parent::addRDF(
							parent::triplify($uri,parent::getVoc().strtolower($k),$myid)
						);
					}
					continue;
				}
			}

			// multi-line header with multi-key single value pair
			if(in_array($k,array("ORTHOLOGY","REACTION"))) {
				// K00844,K12407,K00845  hexokinase/glucokinase [EC:2.7.1.1 2.7.1.2] [RN:R01786]
				// R01786,R02189,R09085  C00267 -> C00668

				$a = explode(" ",$v,2);
				$ids = explode(",",$a[0]);
				if($k == "REACTION" and $ids[0][0] != "R")  {echo "unable to parse $k".PHP_EOL;continue;}
				if(!isset($a[1])) {
					if($e['type'] == "Reaction") {
						parent::addRDF(
							parent::triplify($uri, parent::getVoc()."orthology","kegg:".trim($a[0]))
						);
						continue;
					}
					echo "parse error: ".$k." ".$v.PHP_EOL;continue;
				}
				$str = $a[1];
				
				foreach($ids AS $id) {
					$o = '';
					$o['id'] = $id;
					$o['label'] = $str;
					$o['type'] = strtolower($k);
					parent::addRDF(
						parent::triplify($uri,parent::getVoc().strtolower($k),"kegg:$id")
					);
				}
				continue;
			}
			if($k == "DBLINKS") {
			    // DBLINKS     GO: 0006096 0006094
				$a = explode(": ",$v,2);
				$ns = str_replace( 
					array("ncbi-geneid","ncbi-gi","rn", "pubchem", "pdb-ccd","icd-10","um-bbd",
					"iubmb enzyme nomenclature","explorenz - the enzyme database","expasy - enzyme nomenclature database","umbbd (biocatalysis/biodegradation database)","brenda, the enzyme database"),
					array("ncbigene","gi","kegg","pubchem.compound","ccd","icd10","umbbd",
					"ec","ec","ec","ec","ec"),
					strtolower($a[0])
					);
				$ids = explode(" ",$a[1]);
				foreach($ids AS $id) {
					if(!$id)continue;
					parent::addRDF(
						parent::triplify($uri,parent::getVoc()."x-$ns","$ns:$id")
					);
				}
				continue;
			}
			if($k == "REMARK") {
				preg_match("/Same as: ([A-Z0-9]+)/",$v,$m);
				if(isset($m[1])) {
					parent::addRDF(
						parent::triplify($uri,parent::getVoc()."same-as","kegg:".$m[1])
					);
					continue;
				}
				preg_match("/ATC code: (.*)/",$v,$m);
				if(isset($m[1])) {
					$list = explode(" ",$m[1]);
					foreach($list AS $item) {
						parent::addRDF(
							parent::triplify($uri,parent::getVoc()."x-atc","atc:".$item)
						);
					}
					continue;
				}
				preg_match("/Therapeutic category: (.*)/",$v,$m);
				if(isset($m[1])) {
					$list = explode(" ",$m[1]);
					foreach($list AS $item) {
						parent::addRDF(
							parent::triplifyString($uri,parent::getVoc()."therapeutic-category",$item)
						);
					}
					continue;
				}	
				preg_match("/Drug group: (.*)/",$v,$m);
				if(isset($m[1])) {
					$list = explode(" ",$m[1]);
					foreach($list AS $item) {
						parent::addRDF(
							parent::triplify($uri,parent::getVoc()."drug-group","kegg:".$item)
						);
					}
					continue;
				}
			}
			if($k == "PRODUCT" or $k == "SUBSTRATE") {
				preg_match("/([a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12})/",$v,$m);
				if(isset($m[1])) {
					parent::addRDF(
						parent::triplify($uri,parent::getVoc()."x-dailymed","dailymed:".$m[1]).
						parent::triplifyString("dailymed:".$m[1],"rdfs:label",$v)
					);
					continue;
				}
				preg_match("/\[CPD:([^\]]+)\]/",$v,$m);
				if(isset($m[1])) {
					parent::addRDF(
						parent::triplify($uri,parent::getVoc().strtolower($k),"kegg:".$m[1])
					);
					continue;
				}			
			}
			if($k == "STATISTICS") {
				$a = explode(": ",$v);
				parent::addRDF(
					parent::triplifyString($uri,parent::getVoc().str_replace(" ","-",strtolower($a[0])),$a[1])
				);
				continue;
			}
			if($k == "ORGANISM") {
				$a = explode(" ",$v);
				parent::addRDF(
					parent::triplify($uri,parent::getVoc()."organism","kegg:".$a[0])
				);
				continue;
			}
			
			if($k == "REFERENCE") {
				
				if(!isset($ref)) $ref = 1;
				else {
					if(!isset($e['reference'][$ref]['title'])) continue; // this is a bug where the reference declaration is split onto two lines
					$ref++;
				}
				if(strstr($v,"PMID")) {
					// PMID:11529849 (marker)
					preg_match("/(PMID:[0-9]+) /",$v,$m);
					if(isset($m[1])) {
						$e['reference'][$ref]['pubmed'] = $m[1];
					}
				}
				continue;
			}
			if($k == "AUTHORS") {
				$e['reference'][$ref]['authors'] = $v;
				continue;
			}
			if($k == "TITLE") {
				$e['reference'][$ref]['title'] = $v;
				continue;
			}
			if($k == "JOURNAL") {
				$e['reference'][$ref]['journal'] = $v;
				continue;
			}
			
			if($e['type'] == "Disease" and ($k == "GENE" or $k == "MARKER")) {
				// BCR-ABL (translocation) [HSA:613 25] [KO:K08878 K06619]
				preg_match_all("/ \[([^\]]+)\]/",$v,$m);
				if(isset($m[1])) {
					foreach($m[1] AS $idlist) {
						$a = explode(":",$idlist);
						$ns = $a[0];
						$b = explode(" ",$a[1]);
						foreach($b AS $id) {
							if($ns == "KO") {$rel = "ko-".strtolower($k);$gene = $id;}
							else {$rel = strtolower($k); $gene = $ns."_".$id;}
							parent::addRDF(
								parent::triplify($uri,parent::getVoc().$rel,"kegg:$gene")
							);
						}
						
					}
				} else {
					echo $v;
				}
				continue;
			}
			
			if($k == "GENES") {
				// ATH: AT1G32780 AT1G64710 AT1G77120(ADH1) AT5G24760
				$a = explode(": ",$v);
				$org = $a[0];
				$b = explode(" ",$a[1]);
				foreach($b AS $id) {
					$c = explode("(",$id);
					$gene = parent::getNamespace().$org."_".$c[0];
					parent::addRDF(
						parent::triplify($uri,parent::getVoc()."gene",$gene)
					);
				}
				//echo parent::getRDF();exit;
				continue;			
			}
			if($k == "DRUG_TARGET") {
				// Afatinib: D09724 D09733
				$s = substr($v,strpos($v,":")+2);
				$list = explode(" ",$s);
				foreach($list AS $item) {
					parent::addRDF(
						parent::triplify($uri,parent::getVoc()."drug-target","kegg:$item")
					);				
				}
				continue;
			}
			if($k == "STRUCTURE") {
				$list = explode(" ",$v);
				foreach($list AS $item) {
					if(trim($item) == '') continue;
					parent::addRDF(
						parent::triplify($uri,parent::getVoc()."x-pdb","pdb:$item")
					);					
				}
				continue;
			}
			if($k == "MOTIF") {
				$list = explode(" ",$v);
				foreach($list AS $item) {
					parent::addRDF(
						parent::triplify($uri,parent::getVoc()."x-pfam","pfam:$item")
					);				
				}
				continue;
			}
			if(in_array($k, array("INTERACTION","METABOLISM","TARGET"))) {
				// dopamine D2-receptor antagonist [HSA:1813] [KO:K04145]
				$id = parent::getRes().md5($uri.$v);
				$type = ucfirst(strtolower($k));
				if(in_array($k, array("INTERACTION","METABOLISM"))) {
					$a = explode(":",$v,2);
					$modifier = $a[0];
				} else {
					$modifier = '';
					$s = substr($v,0,strpos($v,"[")+1);
					// dopamine D2-receptor antagonist [
					preg_match("/ ([a-z]+) \[/",$s,$m);
					if(isset($m[1])) $modifier = $m[1];
				}
				parent::addRDF(
					parent::describeIndividual($id,$v,parent::getVoc().$type).
					parent::describeClass(parent::getVoc().$type, $type).
					parent::triplifyString($id,parent::getVoc()."modifier",$modifier).
					parent::triplify($uri,parent::getVoc().strtolower($k),$id)
				);
				preg_match_all("/ \[([^\]]+)\]/",$v,$m);
				if(isset($m[1])) {
					foreach($m[1] AS $item) {
						if(!strstr($item,"KO")) $item = "kegg:".str_replace(":","_",$item);
						else $item = str_replace("KO:","kegg:",$item);
						parent::addRDF(
							parent::triplify($id,parent::getVoc()."link",$item)
						);
					}
				}
				continue;
			}
			
			// skip these
			if(in_array($k, array( "ATOM","BOND","BRITE","AASEQ","NTSEQ","SEQUENCE"))) {
				continue;
			}
			// simple strings to keep as is
			if(in_array($k, array("EXACT_MASS","FORMULA","MOL_WEIGHT","LINEAGE","LENGTH","MASS","COMPOSITION","NODE","EDGE","POSITION"))) {
				parent::addRDF(
					parent::triplifyString($uri,parent::getVoc().strtolower($k),$v)
				);
				continue;
			}
			
			// default catchall
			parent::addRDF(
				parent::triplifyString($uri,parent::getVoc().strtolower($k),$v." [script:default]")
			);
		}
		if(isset($e['reference'])){
			foreach($e['reference'] AS $i => $r) {
				$ref = parent::getRes().$e['id'].".ref.$i";
				parent::addRDF(
					parent::describeIndividual($ref, $r['title'], parent::getVoc()."Reference").
					parent::describeClass(parent::getVoc()."Reference","Reference").
					parent::triplifyString($ref,parent::getVoc()."authors",$r['authors']).
					parent::triplifyString($ref,parent::getVoc()."journal",$r['journal']).
					parent::triplify($uri,parent::getVoc()."reference",$ref)
				);
				if(isset($r['pubmed'])) {
					parent::addRDF(
						parent::triplify($ref,parent::getVoc()."x-pubmed",$r['pubmed'])
					);
				}
			}
		}
		fclose($fp);
	}
	
	// php runparser.php parser=kegg files=pathway
	function parseKGML($lfile)
	{
		// read XML
		
		// iterate over relations
		
		
			// write rdf 
		
	}
}


	