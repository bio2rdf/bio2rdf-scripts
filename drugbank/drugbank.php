#! /usr/bin/php
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

/**
 * DrugBank RDFizer
 * @version 2.0
 * @author Michel Dumontier
 * @author Dana Klassen
 * @description 
*/
require('../../php-lib/bio2rdfapi.php');
require('../../php-lib/xmlapi.php');

class DrugBankParser extends Bio2RDFizer 
{
	
	function __construct($argv) {
		parent::__construct($argv,"drugbank");
        parent::addParameter('files', true, 'all|drugbank.xml.zip','all','Files to convert');
        parent::addParameter('download_url',false,null,'http://www.drugbank.ca/system/downloads/current/');
        parent::initialize();
	}
	
	function Run()
    {
        $indir        = parent::getParameterValue('indir');
        $outdir       = parent::getParameterValue('outdir');
        $download_url = parent::getParameterValue('download_url');

        parent::downloadSources();
        parent::setupSingleOutFile($outdir.'drugbank.nt.gz');

        // TODO:: Get the filename and set a variable
        // right now its a bit awkward asking for the file list again
        $this->Parse($indir,"drugbank.xml.zip");
        parent::GetWriteFile()->Close();
    

        exit;
        
		// generate the release file
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/drugbank/drugbank.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()).$ofile,
			"http://drugbank.ca", 
			array("use","no-commercial"), 
			"http://www.drugbank.ca/about",
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		
        parent::clear();
	}


	function Parse($ldir,$infile)
	{
		$i = 0;
		$xml = new CXML($ldir,$infile);
		while($xml->Parse("partner") == TRUE) {
			$this->ParsePartnerEntry($xml);
			$this->WriteRDFBufferToWriteFile();

			//if($i++ == 10) break;
		}
		unset($xml);
		
		$xml = new CXML($ldir,$infile);
		while($xml->Parse("drug") == TRUE) {
			$this->ParseDrugEntry($xml);
			$this->WriteRDFBufferToWriteFile();

			//if($i++ == 10) break;
		}
		unset($xml);
	}
	
	function ParsePartnerEntry(&$xml)
	{
		$x                         = $xml->GetXMLRoot();
		$id                        = (string)$x->attributes()->id;
		$pid                       = "drugbank_target:".$id;
		$name                      = (string) $x->name;
		$this->named_entries[$pid] = $name;

        parent::addRDF(
            parent::describeIndividual($pid,$name,"drugbank_vocabulary:Target",null,null).
            parent::triplify($pid,"void:inDataset",parent::GetDatasetURI()) 
        );
	
		// iterate over all the child nodes
		foreach($x->children() AS $k => $v) {
			
			// get the direct values
			if(!$v->children()) {
				
				// special cases
				if($k == "references") {
					$a = preg_match_all("/pubmed\/([0-9]+)/",$v,$m);
					if(isset($m[1])) {
						foreach($m[1] AS $pmid) {
                            parent::addRDF(
                                parent::triplify($pid,"drugbank_vocabulary:article","pubmed:".$pmid)
                            );	
						}
					}
				} else if($v != '') {
					// default
                    parent::addRDF(
                        parent::triplifyString($pid,"drugbank_vocabulary:$k",$v)
                    );
				}
				
			} else {
				// work with nested elements
				
				// special cases
				if($k == "species") {
                    parent::addRDF(
                        parent::triplify($pid,"drugbank_vocabulary:species","taxon:".$v->{'uniprot-taxon-id'})
                    );	
				
				} else {
                    
                    /*
                    // default handling for collections
					$found = false;
					$list_name = $k;
					$item_name = substr($k,0,-1);
					foreach($v->children() AS $k2 => $v2) {
						if(!$v2->children() && $k2 == $item_name) {
							$this->AddRDF($this->QQuadL($pid,"drugbank_vocabulary:".$item_name,$this->SafeLiteral($v2)));
							$found = true;
						} else {
							// need special handling
							if($k == 'external-identifiers') {
								$this->GetNS()->ParsePrefixedName($v2->identifier,$ns,$id);
								$ns = $this->NSMap($v2->resource);
								if($ns == "genecards") $id = str_replace(array(" "),array("_"),$id);
								$this->AddRDF($this->QQuad($pid,"drugbank_vocabulary:xref","$ns:$id"));
							} elseif($k == 'pfams') {
								$this->AddRDF($this->QQuad($pid,"drugbank_vocabulary:xref","pfam:".$v2->identifier));
							}	
						} // special handlers
                    }

                     */
				} // default handler
				
			}
		}
	}

	function ParseDrugEntry(&$xml)
	{	
		$declared    = null; // a list of all the entities declared
		$counter     = 1;
		$x           = $xml->GetXMLRoot();
		$dbid        = $x->{"drugbank-id"};
		$did         = "drugbank:".$dbid;
        $name        = (string)$x->name;
        $description = null;

		if(isset($x->description) && $x->description != '') {
			$description = addslashes(trim((string)$x->description));
		}		
	    
        parent::addRDF(
            parent::describeIndividual($did, $label, "drugbank_vocabulary:Drug",null, $description). 
            parent::triplify($did,"void:inDataset",parent::GetDatasetURI()).
            parent::triplify($did,"owl:sameAs","http://indentifiers.org/drugbank/".$dbid).
            parent::triplify($did,"rdfs:seeAlso","http://www.drugbank.ca/drugs/".$dbid). 
            parent::triplifyString($did,"drugbank_vocabulary:category", ucfirst($x->attributes()->type);
        );    

		$this->AddText($x,$did,"groups","group","drugbank_vocabulary:category");
		$this->AddText($x,$did,"categories","category","drugbank_vocabulary:category");
		
		$this->AddLinkedResource($x,$did,"atc-codes","atc-code","atc");
		$this->AddLinkedResource($x,$did,"ahfs-codes","ahfs-code","ahfs");
		$this->AddLinkedResource($x,$did,"pdb-entries","pdb-entry","pdb");
		
		// taxonomy
		$this->AddText($x,$did,"taxonomy","kingdom","drugbank_vocabulary:kingdom");

		// substructures
		$this->AddText($x,$did,"taxonomy","substructures","drugbank_vocabulary:substructure", "substructure");
		
		// synonyms
		$this->AddText($x,$did,"synonyms","synonym","drugbank_vocabulary:synonym");

		// brand names
		$this->AddText($x,$did,"brands","brand","drugbank_vocabulary:brand");

		// mixtures
		// <mixtures><mixture><name>Cauterex</name><ingredients>dornase alfa + fibrinolysin + gentamicin sulfate</ingredients></mixture>
		if(isset($x->mixtures)) {
			$id = 0;
			foreach($x->mixtures->mixture AS $item) {
				if(isset($item)) {
					$o = $item;
                    $mid = "drugbank_resource:".str_replace(" ","-",$o->name);

                    parent::addRDF(
                        parent::triplify($did,"drugbank_vocabulary:mixture",$mid).
                        parent::describeIndividual($mid,$o->name,"drugbank_vocabulary:Mixture",null).
                        parent::triplify($mid,"drubank_vocabulary:ingredients",$o->ingredients) 
                    );
                    
                    $a = explode(" + ",$o->ingredients);
					foreach($a AS $b) {
						$b = trim($b);
						$iid = "drugbank_resource:".str_replace(" ","-",$b);
                        parent::addRDF(
                            parent::triplifyString($iid,"drugbank_vocabulary:ingredients",$b).
                            parent::triplify($mid,"drugbank_vocabulary:ingredient",$iid)
                        );
					}
				}
			}
		}
		
		// packagers
		// <packagers><packager><name>Cardinal Health</name><url>http://www.cardinal.com</url></packager>
		if(isset($x->packagers)) {
			foreach($x->packagers AS $items) {
				if(isset($items->packager)) {
					foreach($items->packager AS $item) {
						$pid = "drugbank_resource:".md5($item->name);
		                
                        parent::addRDF(
                            parent::triplify($did,"drugbank_vocabulary:packager",$pid)
                        );                
						if(!isset($defined[$pid])) {
							$defined[$pid] = '';
                            parent::addRDF(
                                parent::describe($pid,$item->name,null,null);
                            );

                            if(strstr($item->url,"http://") && $item->url != "http://BASF Corp."){
                                // TODO:: Needs to be updated QQuad0_URL?
                                parent::addRDF(
                                    $this->QQuadO_URL($pid,"rdfs:seeAlso",$item->url)
                                );
                            }    
                        }
					}
				}
			}
		}	  
		// manufacturers
		$this->AddText($x,$did,"manufacturers","manufacturer","drugbank_vocabulary:manufacturer"); // @TODO RESOURCE
		
		// prices
		if(isset($x->prices->price)) {
			foreach($x->prices->price AS $product) {
				$pid = "drugbank_resource:".md5($product->description);
				
				$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:product",$pid));
				$this->AddRDF($this->QQuadL($pid,"rdfs:label",$product->description." [$pid]"));
				$this->AddRDF($this->QQuad($pid,"rdf:type","drugbank_vocabulary:Pharmaceutical"));
				$this->AddRDF($this->QQuadL($pid,"drugbank_vocabulary:price", $product->cost));
				
				$uid = "drugbank_vocabulary:".md5($product->unit);
				$this->AddRDF($this->QQuad($pid,"drugbank_vocabulary:form", $uid));
				
				if(!isset($defined[$uid])) {
					$defined[$uid] = '';
					$this->AddRDF($this->QQuadL($uid,"rdfs:label",$product->unit." [$uid]"));
					$this->AddRDF($this->QQuad($uid,"rdf:type","drugbank_vocabulary:Unit"));
				}
			}
		}			
		
		// dosages <dosages><dosage><form>Powder, for solution</form><route>Intravenous</route><strength></strength></dosage>
		if(isset($x->dosages->dosage)) {
			foreach($x->dosages->dosage AS $dosage) {
				$id = "drugbank_resource:".md5($dosage->form.$dosage->route);
				
				$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:dosage",$id));
				$this->AddRDF($this->QQuadL($id,"rdfs:label",$dosage->form." by ".$dosage->route." route [$id]"));
				$this->AddRDF($this->QQuad($id,"rdf:type","drugbank_vocabulary:Dosage"));
				
				$rid = "pharmgkb_vocabulary:".md5($dosage->route);
				$this->AddRDF($this->QQuad($id,"drugbank_vocabulary:route", $rid));
				if(!isset($defined[$rid])) {
					$defined[$rid] = '';
					$this->AddRDF($this->QQuadL($rid,"rdfs:label",$dosage->route." [$rid]"));
					$this->AddRDF($this->QQuad($rid,"rdf:type","drugbank_vocabulary:Route"));
				}
				$fid = "pharmgkb_vocabulary:".md5($dosage->form);
				$this->AddRDF($this->QQuad($id,"drugbank_vocabulary:form", $fid));
				if(!isset($defined[$fid])) {
					$defined[$fid] = '';
					$this->AddRDF($this->QQuadL($fid,"rdfs:label",$dosage->form." [$fid]"));
					$this->AddRDF($this->QQuad($fid,"rdf:type","drugbank_vocabulary:Form"));						
				}
				
				// strength
			}
		}	
		
		//indication
		if(isset($x->indication) && $x->indication != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:indication",$this->SafeLiteral(trim(stripslashes($x->indication)))));
		}
		
		//pharmacology
		if(isset($x->pharmacology) && $x->pharmacology != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:pharmacology",$this->SafeLiteral($x->pharmacology)));
		}
		//mechanism-of-action
		if(isset($x->{"mechanism-of-action"})  && $x->{'mechanism-of-action'} != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:mechanism-of-action",$this->SafeLiteral($x->{"mechanism-of-action"})));
		}
		//toxicity
		if(isset($x->toxicity) && $x->toxicity != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:toxicity",$this->SafeLiteral($x->toxicity)));
		}
		// biotransformation
		if(isset($x->biotransformation) && $x->biotransformation != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:biotransformation",$this->SafeLiteral($x->biotransformation)));
		}
		// absorption
		if(isset($x->absorption) && $x->absorption != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:absorption",$this->SafeLiteral($x->absorption)));
		}		
		// half-life
		if(isset($x->{"half-life"}) && $x->{"half-life"} != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:half-life",$this->SafeLiteral($x->{"half-life"})));
		}		
		// protein-binding
		if(isset($x->{"protein-binding"}) && $x->{"protein-binding"} != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:protein-binding",$this->SafeLiteral($x->{"protein-binding"})));
		}		
		// route-of-elimination		
 		if(isset($x->{"route-of-elimination"}) && $x->{"route-of-elimination"} != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:route-of-elimination",$this->SafeLiteral($x->{"route-of-elimination"})));
		}
		// volume-of-distribution	
 		if(isset($x->{"volume-of-distribution"}) && $x->{"volume-of-distribution"} != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:volume-of-distribution",$this->SafeLiteral($x->{"volume-of-distribution"})));
		}	
		// clearance
		if(isset($x->clearance) && $x->clearance != '') {
			$this->AddRDF($this->QQuadL($did,"drugbank_vocabulary:clearance",$this->SafeLiteral($x->clearance)));
		}	

		// experimental-properties
		if(isset($x->{"experimental-properties"})) {
			foreach($x->{"experimental-properties"} AS $properties) {
				foreach($properties AS $property) {
					$type = $property->kind;
					$value = $property->value;
				
					$id = "drugbank_resource:experimental_property_".$dbid."_".($counter++);
					$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:experimental-property",$id));
					$this->AddRDF($this->QQuadL($id,"rdfs:label",$property->kind.": $value".($property->source == ''?'':" from ".$property->source)." [$id]"));

					// value
					$this->AddRDF($this->QQuadL($id,"drugbank_vocabulary:value",$value));					

					// type
					$tid = "drugbank_vocabulary:".md5($type);
					$this->AddRDF($this->QQuad($id,"rdf:type",$tid));
					if(!isset($defined[$tid])) {
						$defined[$tid] = '';
						$this->AddRDF($this->QQuadL($tid,"rdfs:label","$type [$tid]"));
						$this->AddRDF($this->QQuad($tid,"rdfs:subClassOf","drugbank_vocabulary:Experimental-Property"));
					}
					
					// source
					if(isset($property->source)) {
						foreach($property->source AS $source) {
							$sid = "drugbank_resource:".md5($source);
							$this->AddRDF($this->QQuad($id,"drugbank_vocabulary:source",$sid));
							if(!isset($defined[$sid])) {
								$defined[$sid] = '';
								$this->AddRDF($this->QQuadL($sid,"rdfs:label",$source." [$sid]"));
								$this->AddRDF($this->QQuad($sid,"rdf:type","drugbank_vocabulary:Source"));
							}
						}
					} // source				
				}
			}
		} 
		
		// calculated-properties
		if(isset($x->{"calculated-properties"})) {
			foreach($x->{"calculated-properties"} AS $properties) {
				foreach($properties AS $property) {
					$type = $property->kind;
					$value = addslashes($property->value);
					$source = $property->source;			
					
					$id = "drugbank_resource:calculated_property_".$dbid."_".($counter++);
					$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:calculated-property",$id));
					$this->AddRDF($this->QQuadL($id,"rdfs:label",$property->kind.": $value".($property->source == ''?'':" from ".$property->source)." [$id]"));

					// value
					if($type == "InChIKey") {
						$value = substr($value,strpos($value,"=")+1);
					}
					$this->AddRDF($this->QQuadL($id,"drugbank_vocabulary:value",$value));					

					// type
					$tid = "drugbank_vocabulary:".md5($type);
					$this->AddRDF($this->QQuad($id,"rdf:type",$tid));
					if(!isset($defined[$tid])) {
						$defined[$tid] = '';
						$this->AddRDF($this->QQuadL($tid,"rdfs:label","$type [$tid]"));
						$this->AddRDF($this->QQuad($tid,"rdfs:subClassOf","drugbank_vocabulary:Calculated-Property"));
					}
					
					// source
					if(isset($property->source)) {
						foreach($property->source AS $source) {
							$sid = "drugbank_resource:".md5($source);
							$this->AddRDF($this->QQuad($id,"drugbank_vocabulary:source",$sid));
							if(!isset($defined[$sid])) {
								$defined[$sid] = '';
								$this->AddRDF($this->QQuadL($sid,"rdfs:label",$source." [$sid]"));
								$this->AddRDF($this->QQuad($sid,"rdf:type","drugbank_vocabulary:Source"));
							}
						}
					} // source
					
				}
			}
		}
		
		
		// identifiers
		
		// <patents><patent><number>RE40183</number><country>United States</country><approved>1996-04-09</approved>        <expires>2016-04-09</expires>
		if(isset($x->patents->patent)) {
			foreach($x->patents->patent AS $patent) {
				$id = "uspatent:".$patent->number;
				$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:patent",$id));
				
				$this->AddRDF($this->QQuadL($id,"rdfs:label",$patent->country." patent ".$patent->number." [$id]"));
				$this->AddRDF($this->QQuad($id,"rdf:type","drugbank_vocabulary:Patent"));
				$this->AddRDF($this->QQuadL($id,"drugbank_vocabulary:approved",$patent->approved));
				$this->AddRDF($this->QQuadL($id,"drugbank_vocabulary:expires",$patent->expires));					
				
				$cid = "drugbank_resource:".md5($patent->country);
				$this->AddRDF($this->QQuad($id,"drugbank_vocabulary:country",$cid));
				if(!isset($defined[$cid])) {
					$defined[$cid] = '';
					$this->AddRDF($this->QQuadL($cid,"rdfs:label",$patent->country." [$cid]"));
					$this->AddRDF($this->QQuad($cid,"rdf:type","drugbank_vocabulary:Country"));						
				}
			}
		}
		
		// targets
		if(isset($x->targets)) {
			foreach($x->targets AS $targets) {
				foreach($targets->target AS $target) {	
					$pid = $target->attributes()->partner;
					$tid = "drugbank_target:".$pid;
					
					if(isset($this->named_entries[$tid])) $partner_name = $this->named_entries[$tid];
					else $partner_name = $tid;
					
					$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:target",$tid));
					
					$dti = "drugbank_resource:".$dbid."_".$pid;
					$this->AddRDF($this->QQuadL($dti,"rdfs:label","drug-target interaction $name and $partner_name [$dti]" ));
					$this->AddRDF($this->QQuad($dti,"rdf:type","drugbank_vocabulary:Drug-Target-Interaction"));
					$this->AddRDF($this->QQuad($dti,"drugbank_vocabulary:drug",$did));
					$this->AddRDF($this->QQuad($dti,"drugbank_vocabulary:target",$tid));
					
					if(isset($target->actions)) {
						foreach($target->actions AS $action) {
							if(isset($action->action) && $action->action != '') {
								$this->AddRDF($this->QQuadL($dti,"drugbank_vocabulary:action",$action->action));	
							}
						}
					}
					if(isset($target->{"known-action"})) {
						$this->AddRDF($this->QQuadL($dti,"drugbank_vocabulary:pharmacological-action",$target->{"known-action"}));
					}				
					if(isset($target->references)) {
						$a = preg_match_all("/pubmed\/([0-9]+)/",$target->references,$m);
						if(isset($m[1])) {
							foreach($m[1] AS $pmid) {
								$this->AddRDF($this->QQuad($dti,"drugbank_vocabulary:article","pubmed:".$pmid));	
							}
						}
					}
					
				}
			}
		}
		
		// enzymes
		if(isset($x->enzymes)) {
			foreach($x->enzymes AS $enzymes) {
				foreach($enzymes->enzyme AS $enzyme) {					
					$tid = "drugbank_target:".$enzyme->attributes()->partner;
					$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:enzyme",$tid));
					
					$dti = "drugbank_resource:".$dbid."_".$enzyme->attributes()->partner;
					$partner_name = $tid;
					if(isset($this->named_entries[$tid])) $partner_name = $this->named_entries[$tid];
					
					$this->AddRDF($this->QQuadL($dti,"rdfs:label","drug-enzyme interaction $name and $partner_name [$dti]" ));
					$this->AddRDF($this->QQuad($dti,"rdf:type","drugbank_vocabulary:Drug-Enzyme-Interaction"));
					$this->AddRDF($this->QQuad($dti,"drugbank_vocabulary:drug",$did));
					$this->AddRDF($this->QQuad($dti,"drugbank_vocabulary:enzyme",$tid));
					if(isset($enzyme->actions)) {
						foreach($enzyme->actions AS $action) {
							if(isset($action->action) && $action->action != '') {
								$this->AddRDF($this->QQuadL($dti,"drugbank_vocabulary:action",$action->action));	
							}
						}
					}
					if(isset($enzyme->{"known-action"})) {
						$this->AddRDF($this->QQuadL($dti,"drugbank_vocabulary:pharmacological-action",$enzyme->{"known-action"}));
					}				
					if(isset($enzyme->references)) {
						$a = preg_match_all("/pubmed\/([0-9]+)/",$enzyme->references,$m);
						if(isset($m[1])) {
							foreach($m[1] AS $pmid) {
								$this->AddRDF($this->QQuad($dti,"drugbank_vocabulary:article","pubmed:".$pmid));	
							}
						}
					}
					
				}
			}
		}
	
		// transporters
		if(isset($x->transporters)) {
			foreach($x->transporters AS $transporters) {
				foreach($transporters->transporter AS $transporter) {					
					$tid = "drugbank_target:".$transporter->attributes()->partner;
					$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:transporter",$tid));
					
					$dti = "drugbank_resource:".$dbid."_".$tid;
					$partner_name = $tid;
					if(isset($this->named_entries[$tid])) $partner_name = $this->named_entries[$tid];
					
					$this->AddRDF($this->QQuadL($dti,"rdfs:label","drug-transporter interaction $name and $partner_name [$dti]" ));
					$this->AddRDF($this->QQuad($dti,"rdf:type","drugbank_vocabulary:Drug-Transporter-Interaction"));
					$this->AddRDF($this->QQuad($dti,"drugbank_vocabulary:drug",$did));
					$this->AddRDF($this->QQuad($dti,"drugbank_vocabulary:transporter",$tid));
					if(isset($transporter->actions)) {
						foreach($transporter->actions AS $action) {
							if(isset($action->action) && $action->action != '') {
								$this->AddRDF($this->QQuadL($dti,"drugbank_vocabulary:action",$action->action));	
							}
						}
					}
					if(isset($transporter->{"known-action"})) {
						$this->AddRDF($this->QQuadL($dti,"drugbank_vocabulary:pharmacological-action",$transporter->{"known-action"}));
					}				
					if(isset($transporter->references)) {
						$a = preg_match_all("/pubmed\/([0-9]+)/",$transporter->references,$m);
						if(isset($m[1])) {
							foreach($m[1] AS $pmid) {
								$this->AddRDF($this->QQuad($dti,"drugbank_vocabulary:article","pubmed:".$pmid));	
							}
						}
					}
					
				}
			}
		}
		
		
		// drug-interactions
		$y = (int) substr($dbid,2);
		if(isset($x->{"drug-interactions"})) {
			foreach($x->{"drug-interactions"} AS $ddis) {
				foreach($ddis->{"drug-interaction"} AS $ddi) {
					$z = (int) substr($ddi->drug,2);

					if($y < $z) { // don't repeat
						$ddi_id = "drugbank_resource:".$dbid."_".$ddi->drug;
						$this->AddRDF($this->QQuad("drugbank:".$ddi->drug,"drugbank_vocabulary:ddi-interactor-in",$ddi_id));
						$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:ddi-interactor-in",$ddi_id));
						$this->AddRDF($this->QQuadL($ddi_id,"rdfs:label","DDI between $name and ".$ddi->name." - ".trim($this->SafeLiteral($ddi->description))." [$ddi_id]"));
						$this->AddRDF($this->QQuad($ddi_id,"rdf:type","drugbank_vocabulary:Drug-Drug-Interaction"));
					}
				}
			}
		}
		// food-interactions
		$this->AddText($x,$did,"food-interactions","food-interaction","drugbank_vocabulary:food-interaction");
		// affected-organisms
		$this->AddText($x,$did,"affected-organisms","affected-organism","drugbank_vocabulary:affected-organism");
		
		// cas
		if(isset($x->{'cas-number'}) && $x->{'cas-number'} != '') {
			$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:xref","cas:".$x->{'cas-number'}));
		}
		
		//	<external-identifiers>
		if(isset($x->{"external-identifiers"})) {
			foreach($x->{"external-identifiers"} AS $objs) {
				foreach($objs AS $obj) {
					$ns = $this->NSMap($obj->resource);
					$id = $obj->identifier;
					if($ns == "genecards") $id = str_replace(array(" "),array("_"),$id);
					$this->AddRDF($this->QQuad($did,"drugbank_vocabulary:xref","$ns:$id"));
				}
			}
		}
		// <external-links>
		if(isset($x->{"external-links"})) {
			foreach($x->{"external-links"} AS $objs) {
				foreach($objs AS $obj) {
                    if(strpos($obj->url,'http') !== false){
                         $this->AddRDF($this->QQuadO_URL($did,"rdfs:seeAlso",$obj->url));
                    }
                }
			}
		}
		
  
	}
	
	function NSMap($source)
	{
		$source = strtolower($source);
		switch($source) {
			case 'uniprotkb':
				return 'uniprot';
			case "pubchem compound":
				return 'pubchemcompound';
			case 'pubchem substance':
				return 'pubchemsubstance';
			case 'drugs product database (dpd)':
				return 'dpd';
			case 'kegg compound':
			case 'kegg drug':
				return 'kegg';
			case 'national drug code directory':
				return 'ndc';		
			case 'guide to pharmacology':
				return 'gtp';
			case 'human protein reference database (hprd)':
				return 'hprd';
			case 'genbank gene database':
			case 'genbank protein database':
				return 'genbank';
			case 'hugo gene nomenclature committee (hgnc)':
				return 'hgnc';
				
			default:
				return strtolower($source);
		}
	}
	
	function AddLinkedResource(&$x, $id, $list_name,$item_name,$ns)
	{
		if(isset($x->$list_name)) {
			foreach($x->$list_name AS $item) {
				if(isset($item->$item_name) && ($item->$item_name != '')) {
					$l = $ns.":".$item->$item_name;
					$this->AddRDF($this->QQuad($id,"drugbank_vocabulary:xref",trim($l)));
				}
			}
		}
	}

	function AddText(&$x, $id, $list_name,$item_name,$predicate, $list_item_name = null)
	{
		if(isset($x->$list_name)) {
			foreach($x->$list_name AS $item) {
				if(isset($item->$item_name) && ($item->$item_name != '')) {	
					$l = $item->$item_name;
					if(isset($l->$list_item_name)) {
						foreach($l->$list_item_name AS $k) {
							$this->AddRDF($this->QQuadL($id,$predicate,$this->SafeLiteral(addslashes(ucfirst($k)))));
						}
					} else {
						$this->AddRDF($this->QQuadL($id,$predicate,$this->SafeLiteral(addslashes(ucfirst($l)))));
					}
				}
			}
		}
	}

} // end class


set_error_handler('error_handler');
$dbparser = new DrugBankParser($argv);
$dbparser->Run();
?>
