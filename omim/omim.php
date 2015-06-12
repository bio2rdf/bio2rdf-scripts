<?php
/**
Copyright (C) 2012-2013 Michel Dumontier

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

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
/**
 * OMIM RDFizer (API version)
 * @version 2.0
 * @author Michel Dumontier
 * @description http://www.omim.org/help/api
*/
class OMIMParser extends Bio2RDFizer
{
	function __construct($argv) {
		parent::__construct($argv, 'omim');
		parent::addParameter('files',true,null,'all|omim#','entries to process: comma-separated list or hyphen-separated range');
		parent::addParameter('omim_api_url',false,null,'http://api.omim.org/api/entry?include=all&format=json');
		parent::addParameter('omim_api_key',false,null);
		parent::addParameter('omim_api_key_file',false,null,'omim.key','A file containing your omim KEY');
		parent::initialize();
	}
	
	function Run()
	{	
		// directory shortcuts
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		if(parent::getParameterValue('omim_api_key') == '') {
			$key_file = parent::getParameterValue('omim_api_key_file');
			if(file_exists($key_file)) {
				$key = file_get_contents($key_file);
				if($key) {
					parent::setParameterValue('omim_api_key', $key);
				} else {
					trigger_error("No API key found in the specified omim key file $key_file",E_USER_WARNING);						
				}
			} else {	
				trigger_error("No OMIM key has been provided either by commmand line or in the expected omim key file $key_file",E_USER_WARNING);	
			}
		}

		// get the list of mim2gene entries
		$entries = $this->GetListOfEntries($ldir);
		
		// get the work specified
		$list = trim(parent::getParameterValue('files'));		
		if($list != 'all') {
			// check if a hyphenated list was provided
			if(($pos = strpos($list,"-")) !== FALSE) {
				$start_range = substr($list,0,$pos);
				$end_range = substr($list,$pos+1);
				
				// get the whole list
				$full_list = $this->GetListOfEntries($ldir);
				// now intersect
				foreach($full_list AS $e => $type) {
					if($e >= $start_range && $e <= $end_range) {
						$myentries[$e] = $type;
					}
				}
				$entries = $myentries;
			} else {
				// for comma separated list
				$b = explode(",",parent::getParameterValue('files'));
				foreach($b AS $e) {
					$myentries[$e] = '';
				}
				$entries = array_intersect_key ($entries,$myentries);
			}		
		}
		
		// set the write file
		$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
		$outfile = 'omim.'.parent::getParameterValue('output_format');
		
		parent::setWriteFile($odir.$outfile, $gz);
		
		// declare the mapping method types
		$this->get_method_type(null,true);
		
		// iterate over the entries
		$i = 0;
		$total = count($entries);
		foreach($entries AS $omim_id => $type) {
			echo "processing ".(++$i)." of $total - omim# ";
			$download_file = $ldir.$omim_id.".json.gz";
			$gzfile = "compress.zlib://$download_file";
			// download if the file doesn't exist or we are told to
			if(!file_exists($download_file) || parent::getParameterValue('download') == true) {
				// download using the api
				$url = parent::getParameterValue('omim_api_url').'&apiKey='.parent::getParameterValue('omim_api_key').'&mimNumber='.$omim_id;
				$buf = file_get_contents($url);
				if(strlen($buf) != 0)  {
					file_put_contents($download_file, $buf);
					usleep(500000); // limit of 4 requests per second
				}
			}
			
			// load entry, parse and write to file
			$entry = json_decode(file_get_contents($gzfile), true);
			$omim_id = trim((string)$entry["omim"]["entryList"][0]["entry"]['mimNumber']);
			echo $omim_id;
			$this->ParseEntry($entry,$type);
			parent::writeRDFBufferToWriteFile();
			echo PHP_EOL;
		}
		parent::writeRDFBufferToWriteFile();
		parent::getWriteFile()->close();
		
		// generate the dataset description file
		$source_file = (new DataResource($this))
		->setURI(parent::getParameterValue('omim_api_url'))
		->setTitle("OMIM ".parent::getDatasetVersion())
		->setRetrievedDate( date ("Y-m-d\TG:i:s\Z"))
		->setFormat("application/json")
		->setPublisher("http://omim.org")
		->setHomepage("http://omim.org")
		->setRights("use")
		->setRights("no-commercial")
		->setRights("registration-required")
		->setLicense("http://www.omim.org/help/agreement")
		->setDataset("http://identifiers.org/omim/");
		
		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");
		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$outfile")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
			->setSource($source_file->getURI())
			->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/omim/omim.php")
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
		
		$dataset_description = $source_file->toRDF().$output_file->toRDF();
			
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		
		return TRUE;
	}
	
	function getListOfEntries($ldir)
	{
		// get the master list of entries
		$file = "mim2gene.txt";
		if(!file_exists($ldir.$file)) {
			trigger_error($ldir.$file." not found. Will attempt to download. ", E_USER_NOTICE);
			$this->SetParameterValue('download',true);
		}		
		
		if(parent::getParameterValue('download')==true) {
			// connect
			if(!isset($ftp)) {
				$host = 'ftp.omim.org';
				echo "connecting to $host ...";
				$ftp = ftp_connect($host);
				if(!$ftp) {
					echo "Unable to connect to $host".PHP_EOL;
					die;
				}
				ftp_pasv ($ftp, true) ;				
				$login = ftp_login($ftp, 'anonymous', 'bio2rdf@gmail.com');
				if ((!$ftp) || (!$login)) { 
					echo "FTP-connect failed!"; die; 
				} else {
					echo "Connected".PHP_EOL;
				}
			}
				
			// download
			echo "Downloading $file ...";
			if(ftp_get($ftp, $ldir.$file, 'omim/'.$file, FTP_BINARY) === FALSE) {
				trigger_error("Error in downloading $file");
				continue;
			}
			if(isset($ftp)) ftp_close($ftp);
			echo "success!".PHP_EOL;
		}

		// parse the mim2gene file for the entries
		// # Mim Number    Type    Gene IDs        Approved Gene Symbols
		$fp = fopen($ldir.$file,"r");
		fgets($fp);
		while($l = fgets($fp)) {
			$a = explode("\t",$l);
			if($a[1] != "moved/removed")
				$list[$a[0]] = $a[1];
		}
		fclose($fp);
		return $list;
	}
	
	
	function get_phenotype_mapping_method_type($id = null, $generate_declaration = false)
	{
		$pmm = array(
			"1" => array("name"=>"mapping-by-association",
					"description" => "the disorder is placed on the map based on its association with a gene"),
			"2" => array("name" => "mapping-by-linkage",
					"description" => "the disorder is placed on the map by linkage"),
			"3" => array("name" => "mapping-by-mutation",
					"description" => "the disorder is placed on the map and a mutation has been found in the gene"),
			"4" => array("name" => "mapping-by-copy-number-variation",
					"description" => "the disorder is caused by one or more genes deleted or duplicated")
		);
		
		if($generate_declaration == true) {
			foreach($pmm AS $i => $o) {
				$pmm_uri = parent::getVoc().ucfirst($pmm[$i]['name']);
				parent::addRDF(
					parent::describeClass($pmm_uri, $pmm[$id]['name'], parent::getVoc().'Mapping-Method', $pmm[$id]['description'])
				);
			}
		}
			
		if(isset($id)) {
			if(isset($pmm[$id])) return parent::getVoc().ucfirst($pmm[$id]['name']);
			else return false;
		}
		return true;
	}
	
	function get_method_type($id = null, $generate_declaration = false)
	{
		$methods = array(
			"A" => "in situ DNA-RNA or DNA-DNA annealing (hybridization)",
			"AAS" => "inferred from the amino acid sequence",
			"C" => "chromosome mediated gene transfer",
			"Ch" => "chromosomal change associated with phenotype and not linkage (Fc), deletion (D), or virus effect (V)",
			"D" => "deletion or dosage mapping, trisomy mapping, or gene dosage effects",
			"EM" => "exclusion mapping",
			"F" => "linkage study in families",
			"Fc" => "linkage study - chromosomal heteromorphism or rearrangement is one trait",
			"Fd" => "linkage study - one or both of the linked loci are identified by a DNA polymorphism",
			"H" =>  "based on presumed homology",
			"HS" => "DNA/cDNA molecular hybridization in solution (Cot analysis)",
			"L" => "lyonization",
			"LD" => "linkage disequilibrium",
			"M" => "Microcell mediated gene transfer",
			"OT" => "ovarian teratoma (centromere mapping)",
			"Pcm" => "PCR of microdissected chromosome segments (see REl)",
			"Psh" => "PCR of somatic cell hybrid DNA",
			"R" => "irradiation of cells followed by rescue through fusion with nonirradiated (nonhuman) cells (Goss-Harris method of radiation-induced gene segregation)",
			"RE" => "Restriction endonuclease techniques",
			"REa" => "combined with somatic cell hybridization",
			"REb" => "combined with chromosome sorting",
			"REc" => "hybridization of cDNA to genomic fragment (by YAC, PFGE, microdissection, etc.)",
			"REf" => "isolation of gene from genomic DNA; includes exon trapping",
			"REl" => "isolation of gene from chromosome-specific genomic library (see Pcm)",
			"REn" => "neighbor analysis in restriction fragments",
			"S" => "segregation (cosegregation) of human cellular traits and human chromosomes (or segments of chromosomes) in particular clones from interspecies somatic cell hybrids",
			"T" => "TACT telomere-associated chromosome fragmentation",
			"V" => "induction of microscopically evident chromosomal change by a virus",
			"X/A" => "X-autosome translocation in female with X-linked recessive disorder"
		);
		if($generate_declaration == true) {
			foreach($methods AS $k => $v) {
				$method_uri = parent::getNamespace().$k;
				parent::addRDF(parent::describe($method_uri, $methods[$k]));
			}
		}
		
		if(isset($id)) {
			if(isset($methods[$id])) return parent::getVoc().$id;
			else return false;
		}
		return true;
	}	
	
	
	function ParseEntry($obj, $type)
	{
		$o = $obj["omim"]["entryList"][0]["entry"];
		$omim_id = $o['mimNumber'];
		$omim_uri = parent::getNamespace().$o['mimNumber'];
		if(isset($o['version'])) parent::setDatasetVersion($o['version']);

		// add the links
		parent::addRDF($this->QQuadO_URL($omim_uri, "rdfs:seeAlso", "http://omim.org/entry/".$omim_id));
		parent::addRDF($this->QQuadO_URL($omim_uri, "owl:sameAs",   "http://identifiers.org/omim/".$omim_id));

		// parse titles
		$titles = $o['titles'];
		parent::addRDF(
			parent::describeIndividual($omim_uri, $titles['preferredTitle'], parent::getVoc().str_replace(array(" ","/"),"-", ucfirst($type))).
			parent::describeClass(parent::getVoc().str_replace(array(" ","/"),"-", ucfirst($type)),$type)
		);
		if(isset($titles['preferredTitle'])) {
			parent::addRDF(parent::triplifyString($omim_uri, parent::getVoc()."preferred-title", $titles['preferredTitle']));
		}
		if(isset($titles['alternativeTitles'])) {
			$b = explode(";;",$titles['alternativeTitles']);
			foreach($b AS $title) {
				parent::addRDF(parent::triplifyString($omim_uri, parent::getVoc()."alternative-title", trim($title)));
			}
		}

		// parse text sections
		if(isset($o['textSectionList'])) {
			foreach($o['textSectionList'] AS $i => $section) {
			
				if($section['textSection']['textSectionTitle'] == "Description") {
					parent::addRDF(parent::triplifyString($omim_uri, "dc:description", $section['textSection']['textSectionContent']));	
				} else {
					$p = str_replace(" ","-", strtolower($section['textSection']['textSectionTitle']));
					parent::addRDF(parent::triplifyString($omim_uri, parent::getVoc()."$p", $section['textSection']['textSectionContent']));	
				}
				
				// parse the omim references
				preg_match_all("/\{([0-9]{6})\}/",$section['textSection']['textSectionContent'],$m);
				if(isset($m[1][0])) {
					foreach($m[1] AS $oid) {
						parent::addRDF(parent::triplify($omim_uri, parent::getVoc()."refers-to", "omim:$oid"));
					}
				}
			}
		}
		
		// allelic variants
		if(isset($o['allelicVariantList'])) {
			foreach($o['allelicVariantList'] AS $i => $v) {
				$v = $v['allelicVariant'];
			
				$uri = parent::getRes()."$omim_id"."_allele_".$i;
				$label = str_replace("\n"," ",$v['name']);
				
				parent::addRDF(
					parent::describeIndividual($uri, $label, parent::getVoc()."Allelic-Variant").
					parent::describeClass(parent::getVoc()."Allelic-Variant","Allelic Variant")
				);

				if(isset($v['alternativeNames'])) {
					$names = explode(";;",$v['alternativeNames']);
					foreach($names AS $name) {
						$name = str_replace("\n"," ",$name);
						parent::addRDF(parent::triplifyString($uri,parent::getVoc()."alternative-names",$name));				
					}
				}
				if(isset($v['text'])) parent::addRDF(parent::triplifyString($uri,"dc:description",$v['text']));
				if(isset($v['mutations'])) parent::addRDF(parent::triplifyString($uri,parent::getVoc()."mutation",$v['mutations']));				
				if(isset($v['dbSnps'])) {
					$snps = explode(",",$v['dbSnps']);
					foreach($snps AS $snp) {
						parent::addRDF(parent::triplify($uri, parent::getVoc()."x-dbsnp", "dbsnp:".$snp));
					}
				}
				parent::addRDF(parent::triplify($omim_uri, parent::getVoc()."variant", $uri));
			}
		}
		
		// clinical synopsis
		if(isset($o['clinicalSynopsis'])) {
			$cs = $o['clinicalSynopsis'];
			$cs_uri = parent::getRes()."".$omim_id."_cs";
			parent::addRDF(
				parent::describeIndividual($cs_uri, "Clinical synopsis for omim $omim_id", parent::getVoc()."Clinical-Synopsis").
				parent::describeClass(parent::getVoc()."Clinical-Synopsis","Clinical Synopsis").
				parent::triplify($omim_uri, parent::getVoc()."clinical-synopsis", $cs_uri)
			);

			foreach($cs AS $k => $v) {
				if(!strstr($k,"Exists")) { // ignore the boolean assertion.
					
					// @todo ignore provenance for now
					if(in_array($k, array('contributors','creationDate','editHistory','epochCreated','dateCreated','epochUpdated','dateUpdated'))) continue;
					
					if(!is_array($v)) $v = array($k=>$v);
					foreach($v AS $k1 => $v1) {
						$phenotypes = explode(";",$v1);
						foreach($phenotypes AS $coded_phenotype) {
							// parse out the codes
							$coded_phenotype = trim($coded_phenotype);
							if(!$coded_phenotype) continue;
							$phenotype = preg_replace("/\{.*\}/","",$coded_phenotype);
							$phenotype_id = parent::getRes()."".md5(strtolower($phenotype));
							$entity_id = parent::getRes()."".$k1;

							parent::addRDF(
								parent::describeIndividual($phenotype_id, $phenotype, parent::getVoc().'Characteristic').
								parent::describeClass(parent::getVoc().'Characteristic','Characteristic').
								parent::triplify($cs_uri, parent::getVoc()."feature", $phenotype_id).
								parent::describeIndividual($entity_id, $k1, parent::getVoc()."Entity").
								parent::describeClass(parent::getVoc()."Entity","Entity").
								parent::triplify($phenotype_id, parent::getVoc()."characteristic-of", $entity_id)
							);

							// parse out the vocab references
							preg_match_all("/\{([0-9A-Za-z \:\-\.]+)\}|;/",$coded_phenotype,$codes);
							//preg_match_all("/((UMLS|HPO HP|SNOMEDCT|ICD10CM|ICD9CM|EOM ID)\:[A-Z0-9]+)/",$coded_phenotype,$m);
							if(isset($codes[1][0])) {
								foreach($codes[1] AS $entry) {
									$entries = explode(" ",trim($entry));
									foreach($entries AS $e) {
										if($e == "HPO" || $e == "EOM") continue;
										$this->getRegistry()->parseQName($e,$ns,$id);
										if(!isset($ns) || $ns == '') {
											$b = explode(".",$id);
											$ns = "omim"; 
											$id = $b[0];
										} else {
											$ns = str_replace(array("hpo","id","icd10cm","icd9cm","snomedct"), array("hp","eom","icd10","icd9","snomed"), $ns);
										}
										parent::addRDF(
											parent::triplify($phenotype_id, parent::getVoc()."x-$ns", "$ns:$id")
										);
									} // foreach
								} // foreach
							} // codes
						} //foreach
					} // foreach
				} // exists
			}
		} // clinical synopsis
		
		// genemap
		if(isset($o['geneMap'])) {
			$map = $o['geneMap'];
			if(isset($map['chromosome'])) {
				parent::addRDF(parent::triplifyString($omim_uri, parent::getVoc()."chromosome", (string) $map['chromosome']));
			}
			if(isset($map['cytoLocation'])) {
				parent::addRDF(parent::triplifyString($omim_uri, parent::getVoc()."cytolocation", (string)  $map['cytoLocation']));
			}
			if(isset($map['geneSymbols'])) {
				$b = preg_split("/[,;\. ]+/",$map['geneSymbols']);
				foreach($b AS $symbol) {
					parent::addRDF(parent::triplify($omim_uri, parent::getVoc()."gene-symbol", "symbol:".trim($symbol)));
				}
			}

			if(isset($map['geneName'])) {
				$b = explode(",",$map['geneName']);
				foreach($b AS $name) {
					parent::addRDF(parent::triplifyString($omim_uri, parent::getVoc()."gene-name", trim($name)));
				}
			}
			if(isset($map['mappingMethod'])) {
				$b = explode(",",$map['mappingMethod']);
				foreach($b AS $c) {
					$mapping_method = trim($c);
					$method_uri = $this->get_method_type($mapping_method);
					if($method_uri !== false)
						parent::addRDF(parent::triplify($omim_uri, parent::getVoc()."mapping-method", $method_uri));
				}
			}

			if(isset($map['mouseGeneSymbol'])) {
				$b = explode(",",$map['mouseGeneSymbol']);
				foreach($b AS $c) {
					parent::addRDF(parent::triplify($omim_uri, parent::getVoc()."mouse-gene-symbol", "symbol:".strtoupper($c)));
				}
			}
			if(isset($map['mouseMgiID'])) {
				$b = explode(",",$map['mouseMgiID']);
				foreach($b AS $c) {
					parent::addRDF(parent::triplify($omim_uri, parent::getVoc()."x-mgi", $c));
				}
			}
			if(isset($map['geneInheritance']) && $map['geneInheritance'] != '') {
				parent::addRDF(parent::triplifyString($omim_uri, parent::getVoc()."gene-inheritance", $map['geneInheritance']));
			}	
		}
		if(isset($o['phenotypeMapList'])) {	
			foreach($o['phenotypeMapList'] AS $i => $phenotypeMap) {
				$phenotypeMap = $phenotypeMap['phenotypeMap'];
				$pm_uri = parent::getRes().$omim_id."_pm_".($i+1);
				parent::addRDF(
					parent::describeIndividual($pm_uri,"phenotype mapping for $omim_id", parent::getVoc()."Phenotype-Map").
					parent::describeClass(parent::getVoc()."Phenotype-Map","OMIM Phenotype-Map").
					parent::triplify($omim_uri, parent::getVoc()."phenotype-map", $pm_uri)
				);
				
				foreach(array_keys($phenotypeMap) AS $k) {
					if(in_array($k, array("mimNumber","phenotypeMimNumber","phenotypicSeriesMimNumber"))) {
						parent::addRDF(parent::triplify($pm_uri, parent::getVoc().$k, "omim:".$phenotypeMap[$k]));
					} else if($k == "geneSymbols") {
						$l = explode(", ",$phenotypeMap[$k]);
						foreach($l AS $gene) {
							parent::addRDF(parent::triplify($pm_uri, parent::getVoc()."gene-symbol", "hgnc.symbol:".$gene));
						}
					} else if ($k == "phenotypeMappingKey") {
						$l = $this->get_phenotype_mapping_method_type($phenotypeMap[$k]);
						parent::addRDF(parent::triplify($pm_uri, parent::getVoc()."mapping-method", $l));
					} else {
						parent::addRDF(parent::triplifyString($pm_uri, parent::getVoc().$k, $phenotypeMap[$k]));
					}
				}
			}
		}
		
		
		// references
		if(isset($o['referenceList'])) {
			foreach($o['referenceList'] AS $i => $r) {
				$r = $r['reference'];
				if(isset($r['pubmedID'])) {
					$pubmed_uri = "pubmed:".$r['pubmedID'];
					parent::addRDF(parent::triplify($omim_uri, parent::getVoc()."article", $pubmed_uri));
					$title = 'article';
					if(isset($r['title']))  $title = $r['title'];
					parent::addRDF(parent::describe($pubmed_uri, addslashes($r['title'])));
					if(isset($r['articleUrl'])) parent::addRDF($this->QQuadO_URL($pubmed_uri, "rdfs:seeAlso", htmlentities($r['articleUrl']))); 
				}
			}
		}
	
		// external ids
		if(isset($o['externalLinks'])) {		
			foreach($o['externalLinks'] AS $k => $id) {
				if($id === false) continue;
				
				$ns = '';
				switch($k) {
					case 'approvedGeneSymbols':        $ns = 'symbol';break;
					case 'geneIDs':                    $ns = 'ncbigene';break;
					case 'ncbiReferenceSequences':     $ns = 'gi';break;
					case 'genbankNucleotideSequences': $ns = 'gi';break;
					case 'proteinSequences':           $ns = 'gi';break;
					case 'uniGenes':                   $ns = 'unigene';break;
					case 'ensemblIDs':                 $ns = 'ensembl';break;
					case 'swissProtIDs':               $ns = 'uniprot';break;
					case 'mgiIDs':                     $ns = 'mgi';$b = explode(":",$id);$id=$b[1];break;
					
					case 'flybaseIDs':                 $ns = 'flybase';break;
					case 'zfinIDs':                    $ns = 'zfin';break;
					case 'hprdIDs':                    $ns = 'hprd';break;
					case 'orphanetDiseases':           $ns = 'orphanet';break;
					case 'refSeqAccessionIDs':         $ns = 'refseq';break;
					case 'ordrDiseases':               $ns = 'ordr';$b=explode(";;",$id);$id=$b[0];break;
					
					case 'snomedctIDs':                $ns = 'snomed';break;
					case 'icd10cmIDs':                 $ns = 'icd10';break;
					case 'icd9cmIDs':                  $ns = 'icd9';break;
					case 'umlsIDs':                    $ns = 'umls';break;
					case 'wormbaseIDs':                $ns = 'wormbase';break;
					
					case 'diseaseOntologyIDs':	   		$ns = 'do';break;
					
					// specifically ignorning
					case 'geneTests':
					case 'cmgGene':
					case 'geneticAllianceIDs':  // #
					case 'nextGxDx':
					case 'nbkIDs': // NBK1207;;Alport Syndrome and Thin Basement Membrane Nephropathy
					case 'newbornScreeningUrls':  
					case 'decipherUrls':
					case 'geneReviewShortNames':      
					case 'locusSpecificDBs':
					case 'geneticsHomeReferenceIDs':   					
					case 'omiaIDs':                   
					case 'coriellDiseases': 
					case 'clinicalDiseaseIDs':        
					case 'possumSyndromes':
					case 'keggPathways':
					case 'gtr':
					case 'gwasCatalog':
					case 'mgiHumanDisease':
					case 'wormbaseDO':
					case 'dermAtlas':                  // true/false
						break;
					
					default:
						echo "unhandled external link $k $id".PHP_EOL;
				}

				$ids = explode(",",$id);
				foreach($ids AS $id) {
					if($ns) {
						if(strstr($id,";;") === FALSE) {
							parent::addRDF(parent::triplify($omim_uri, parent::getVoc()."x-$ns", $ns.':'.$id)); 
						} else {
							$b = explode(";;",$id); // multiple ids//names
							foreach($b AS $c) {
								preg_match("/([a-z])/",$c,$m);
								if(!isset($m[1])) {
									parent::addRDF(parent::triplify($omim_uri, parent::getVoc()."x-$ns", $ns.':'.$c)); 
								}
							}
						}
					}
				}
			}
		} //external links

	} // end parse
} 

?>
