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
require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../../php-lib/xmlapi.php');

class DrugBankParser extends Bio2RDFizer 
{    
    function __construct($argv) {
        parent::__construct($argv,"drugbank");
        parent::addParameter('files', true, 'all|drugbank','all','Files to convert');
        parent::addParameter('download_url',false,null,'http://www.drugbank.ca/system/downloads/current/');
        parent::initialize();
    }
    
    function Run()
    {
        $indir        = parent::getParameterValue('indir');
        $outdir       = parent::getParameterValue('outdir');
        $download_url = parent::getParameterValue('download_url');

		if(parent::getParameterValue('files') == 'all') {
			$files = explode("|",parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode("|",parent::getParameterValue('files'));
		}
		
		if(parent::getParameterValue("id_list")) {
			$this->id_list = array_flip(explode(",",parent::getParameterValue('id_list')));
		}
		
		$dataset_description = '';
		foreach($files AS $f) {
			if($f == 'drugbank') {
				$file = 'drugbank.xml.zip';
				$lname = 'drugbank';
			}
			$fnx = 'parse_'.$f;
			
			$rfile = parent::getParameterValue('download_url').$file;
			$lfile = parent::getParameterValue('indir').$file;
			$cfile = $lname.".".parent::getParameterValue('output_format');

			// download
			if(!file_exists($lfile) || parent::getParameterValue('download') == true) {
				utils::downloadSingle($rfile,$lfile);
			}
			
			// setup the write
			$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
			parent::setWriteFile($outdir.$cfile, $gz);
			if(file_exists($indir.$file)) {
				// call the parser
				echo "processing $file ...".PHP_EOL;
				$this->$fnx($indir,$file);
				echo "done".PHP_EOL;
				parent::clear();
			}
			parent::getWriteFile()->close();
			
			// dataset description
			$ouri = parent::getGraphURI();
			parent::setGraphURI(parent::getDatasetURI());
			
			$source_version = parent::getDatasetVersion();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$prefix = parent::getPrefix();
			$date = date ("Y-m-d\TH:i:sP");
			// dataset description
			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("DrugBank ($file)")
				->setRetrievedDate( date ("Y-m-d\TH:i:sP", filemtime($indir.$file)))
				->setFormat("application/xml")
				->setFormat("application/zip")
				->setPublisher("http://drugbank.ca")
				->setHomepage("http://drugbank.ca")
				->setRights("use")
				->setRights("by-attribution")
				->setRights("no-commercial")
				->setLicense("http://www.drugbank.ca/about")
				->setDataset("http://identifiers.org/drugbank/");
			
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$cfile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix v$source_version")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/drugbank/drugbank.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")			
				->setRights("use-share-modify")
				->setRights("by-attribution")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons.org/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());

			$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
			if($gz) $output_file->setFormat("application/gzip");
			if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
			else $output_file->setFormat("application/n-quads");
		
			parent::writeToReleaseFile($source_file->toRDF().$output_file->toRDF());
			parent::setGraphURI($ouri);
		}
        parent::closeReleaseFile();
    }


    function parse_drugbank($ldir,$infile)
    {		
		$xml = new CXML($ldir,$infile);
		while($xml->parse("drug") == TRUE) {
			if(isset($this->id_list) and count($this->id_list) == 0) break;
			$this->parseDrugEntry($xml);
		}
		unset($xml);
    }
	
	    
    function NSMap($source)
    {
        $source = strtolower($source);
        switch($source) {
            case 'uniprotkb':
            case 'uniprot accession':
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
				return 'genbank';
            case 'genbank protein database':
                return 'gi';
            case 'hugo gene nomenclature committee (hgnc)':
                return 'hgnc';
                
            default:
                return strtolower($source);
        }
    }
    
    function parsePartnerEntry($did, &$x,$type)
    {
        $id = (string)$x->id;
        $pid = "drugbank:".$id;
	$lid = parent::getRes().substr($did,strpos($did,":")+1)."_".$id; // local pivot to keep the action between the drug and target
        $name = (string) $x->name;
	$parent = parent::getVoc().ucfirst(str_replace(" ","-",$type));
        parent::addRDF(
		parent::describeIndividual($pid,$name,$parent).
		parent::describeClass($parent,ucfirst($type)).
		parent::triplify($did,parent::getVoc().$type,$pid).
		parent::describeIndividual($lid,"$did to $pid relation",parent::getVoc().ucfirst("$type-Relation")).
		parent::describeClass(parent::getVoc().ucfirst("$type-Relation"), ucfirst("$type Relation")).
		parent::triplify($lid,parent::getVoc()."drug",$did).
		parent::triplify($lid,parent::getVoc().$type,$pid)
        );

        // iterate over all the child nodes
        foreach($x->children() AS $k => $v) {
            // get the direct values
             if(!$v->children()) {
                // special cases
                if($k == "references") { // for local pivot
                    $a = preg_match_all("/pubmed\/([0-9]+)/",$v,$m);
                    if(isset($m[1])) {
                        foreach($m[1] AS $pmid) {
                            parent::addRDF(
                                parent::triplify($lid,parent::getVoc()."reference","pubmed:".$pmid)
                            );
                        }
                    }
                } else if($v != '') {
			if($k == 'known-action') {  // for local pivot
				parent::addRDF(
					parent::triplifyString($lid,parent::getVoc().$k,(string)$v)
				);
			} else {
				parent::addRDF(
					parent::triplifyString($pid,parent::getVoc().$k,(string)$v)
				);
			}
                }

            } else {
                // work with nested elements
		$found = false;
		$list_name = $k;
		$item_name = substr($k,0,-1);
		foreach($v->children() AS $k2 => $v2) {
			if($k2 == "organism") {
				// extract the ncbi id
				$taxid = $v2->attributes()->{'ncbi-taxonomy-id'};
				$label = $v2;
				parent::addRDF(
					parent::triplify($pid, parent::getVoc()."x-taxonomy", "taxonomy:$taxid").
					parent::triplifyString("taxonomy:$taxid","rdfs:label",$label)
				);
				continue;
			}

			if(!$v2->children()) {
				// echo "not nested $k2".PHP_EOL;
				if($k2 == "action") {
					$aid = str_replace(array(" ","/"),"-",$v2);
					parent::addRDF(
						parent::describeIndividual($lid,$v2,parent::getVoc()."Action").
						parent::describeClass(parent::getVoc()."Action","Action").
						parent::triplify($lid,parent::getVoc()."action",parent::getVoc().$aid)
					);
				} else {
					// default handler
					parent::addRDF(
						parent::triplifyString($pid, parent::getVoc().$k2, "".$v2)
					);
				}
			} else { // nested elements
				// echo "nested $k2".PHP_EOL;
				foreach($v2->children() AS $k3 => $v3) {
					// echo " ".$k3.PHP_EOL;
					if(!$v3->children()) {
						parent::addRDF(
							parent::triplifyString($pid,parent::getVoc().$k3, "".$v3)
						);
					} else {
						foreach($v3 AS $k4 => $v4) {
							if($k3 == 'external-identifier') {
								$ns = $this->NSMap($v3->resource);
								$id = (string) $v3->identifier;
								$id = str_replace(array("GNC:","HGNC:"),"",$id);
								parent::addRDF(
									parent::triplify($pid, parent::getVoc()."x-$ns","$ns:$id")
								);
							} else if($k3 == 'pfam') {
								parent::addRDF(
									parent::triplify($pid, parent::getVoc()."x-pfam","pfam:"."".$v3->identifier)
								);
							} else if($k3 == "go-classifier") {
								parent::addRDF(
									parent::triplifyString($pid, parent::getVoc()."go-".$v3->category, $v3->description)
								);
							} else {
								trigger_error("no handler for $k3",E_USER_WARNING);
/*								parent::addRDF(
									parent::triplifyString($pid, parent::getVoc().$k3, $v4)
								);
*/
							}
						 }
					}
				}
			}
		} // foreach
            }
         }
    }

    /**
    * @description check if a type has already been defined and add appropriate RDF
    * NOTE:: Should be moved into bio2rdfapi.php
    */
    function typify($id,$tid,$subclass,$label)
	{
        if(!isset($this->defined[$tid])) {
             $this->defined[$tid] = '';
             parent::addRDF(
                 parent::describeClass($tid,$label,$this->getVoc().$subclass)
             );
        }
        parent::addRDF(
            parent::triplify($id,$this->getVoc().strtolower($subclass),$tid)
        );
    }

    function parseDrugEntry(&$xml)
    {   
        $declared    = null; // a list of all the entities declared
        $counter     = 1;
        $x           = $xml->GetXMLRoot();
        $dbid        = (string) $x->{"drugbank-id"};
        $did         = "drugbank:".$dbid;
        $name        = (string)$x->name;
	$type        = ucfirst((string)str_replace(" ","-",$x->attributes()->type));
	$type_label  = ucfirst($x->attributes()->type);
        $description = null;

	if(isset($this->id_list)) {
		if(!isset($this->id_list[$dbid])) return;
		unset($this->id_list[$dbid]);
	}

	echo $dbid.PHP_EOL;
	if(isset($x->description) && $x->description != '') {
		$description = trim((string)$x->description);
        }

        parent::addRDF(
		parent::describeIndividual($did, $name, parent::getVoc()."Drug",$name, $description).
		parent::describeClass(parent::getVoc()."Drug","Drug").
		parent::triplify($did,"owl:sameAs","http://identifiers.org/drugbank/".$dbid).
		parent::triplify($did,"rdfs:seeAlso","http://www.drugbank.ca/drugs/".$dbid). 
		parent::triplify($did,"rdf:type", parent::getVoc().$type).
		parent::describeClass(parent::getVoc().$type, $type_label)
        );

	foreach($x->{'drugbank-id'} AS $id) {
		parent::addRDF(
			parent::triplifyString($did, parent::getVoc()."drugbank-id", $id)
		);
	}
	if(isset($x->{'cas-number'})) {
		parent::addRDF(
			parent::triplify($did, parent::getVoc()."x-cas", "cas:".$x->{'cas-number'})
		);
	}
 
	$literals = array(
		"indication",
		"pharmacology",
		"mechanism-of-action",
		"toxicity",
		"biotransformation",
		"absorption",
		"half-life",
		"protein-binding",
		"route-of-elimination",
		"volume-of-distribution",
		"clearance"
	);

	foreach($literals AS $l) {
		if(isset($x->$l) and $x->$l != '') {
			$lid = parent::getRes().md5($l.$x->$l);
			parent::addRDF(
				parent::describeIndividual($lid,"$l for $did",parent::getVoc().ucfirst($l), "$l for $did",$x->$l).
				parent::describeClass(parent::getVoc().ucfirst($l),ucfirst(str_replace("-"," ",$l))).
				parent::triplify($did,parent::getVoc().$l,$lid)
			);
		}
	}

        // TODO:: Replace the next two lines
	$this->AddList($x,$did,"groups","group",parent::getVoc()."group");
	$this->AddList($x,$did,"categories","category",parent::getVoc()."category");

	if(isset($x->classification)) {
		foreach($x->classification->children() AS $k => $v) {
			$cid = parent::getRes().md5($v);
			parent::addRDF(
				parent::describeIndividual($cid, $v, parent::getVoc()."Drug-Classification-Category").
				parent::describeClass(parent::getVoc()."Drug-Classification-Category","Drug Classification Category").
				parent::triplify($did, parent::getVoc()."drug-classification-category", $cid)
			);
		}
	}

	$this->addLinkedResource($x, $did, 'atc-codes','atc-code','atc');
	$this->addLinkedResource($x, $did, 'ahfs-codes','ahfs-code','ahfs');

        // taxonomy
        $this->AddText($x,$did,"taxonomy","kingdom",parent::getVoc()."kingdom");

        // substructures
        $this->AddText($x,$did,"taxonomy","substructures",parent::getVoc()."substructure", "substructure");

        // synonyms
        $this->AddCategory($x,$did,"synonyms","synonym",parent::getVoc()."synonym");

        // brand names
        $this->AddCategory($x,$did,"brands","brand",parent::getVoc()."brand");

	// salt
        $this->AddText($x,$did,"salts","salt",parent::getVoc()."salt");

        // mixtures
        // <mixtures><mixture><name>Cauterex</name><ingredients>dornase alfa + fibrinolysin + gentamicin sulfate</ingredients></mixture>
        if(isset($x->mixtures)) {
            $id = 0;
            foreach($x->mixtures->mixture AS $item) {
                if(isset($item)) {
                    $o = $item;
                    $mid = parent::getRes().str_replace(" ","-",$o->name[0]);

                    parent::addRDF(
				parent::triplify($did,parent::getVoc()."mixture",$mid).
				parent::describeIndividual($mid,$o->name[0],parent::getVoc()."Mixture").
				parent::describeClass(parent::getVoc()."Mixture","mixture").
				parent::triplifyString($mid,$this->getVoc()."ingredients","".$o->ingredients[0]) 
                    );

                    $a = explode(" + ",$o->ingredients[0]);
                    foreach($a AS $b) {
                        $b = trim($b);
                        $iid = parent::getRes().str_replace(" ","-",$b);
                        parent::addRDF(
				parent::describeClass($iid,$b, parent::getVoc()."Ingredient").
				parent::describeClass(parent::getVoc()."Ingredient","Ingredient").
				parent::triplify($mid,parent::getVoc()."ingredient",$iid)
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
			$pid = parent::getRes().md5($item->name);

			parent::addRDF(
				parent::triplify($did,parent::getVoc()."packager",$pid)
			);
			if(!isset($defined[$pid])) {
				$defined[$pid] = '';
				parent::addRDF(
					parent::describe($pid,"".$item->name[0])
				);

				if(strstr($item->url,"http://") && $item->url != "http://BASF Corp."){
					parent::addRDF(
						$this->triplify($pid,"rdfs:seeAlso","".$item->url[0])
					);
				}
                    }
                 }
             }
         }
     }     

//      // manufacturers
     $this->AddText($x,$did,"manufacturers","manufacturer",parent::getVoc()."manufacturer"); // @TODO RESOURCE
        
     // prices
	if(isset($x->prices->price)) {
		foreach($x->prices->price AS $product) {
			$pid = parent::getRes().md5($product->description);
			parent::addRDF(
				parent::describeIndividual($pid,$product->description,parent::getVoc()."Pharmaceutical",$product->description).
				parent::describeClass(parent::getVoc()."Pharmaceutical","pharmaceutical").
				parent::triplifyString($pid,parent::getVoc()."price","".$product->cost,"xsd:float").
				parent::triplify($did, parent::getVoc()."product", $pid)
			);

			$uid = parent::getVoc().md5($product->unit);
			parent::addRDF(
				parent::describeIndividual($uid,$product->unit,parent::getVoc()."Unit",$product->unit).
				parent::describeClass(parent::getVoc()."Unit","unit").
				parent::triplify($pid,parent::getVoc()."form",$uid)
			);
		}
	}

     // dosages <dosages><dosage><form>Powder, for solution</form><route>Intravenous</route><strength></strength></dosage>
     if(isset($x->dosages->dosage)) {
         foreach($x->dosages->dosage AS $dosage) {
		$id = parent::getRes().md5($dosage->strength.$dosage->form.$dosage->route);
		$label = (($dosage->strength != '')?$dosage->strength." ":"").$dosage->form." form with ".$dosage->route. " route";

		parent::addRDF(
			parent::describeIndividual($id,$label,parent::getVoc()."Dosage").
			parent::describeClass(parent::getVoc()."Dosage","Dosage").
			parent::triplify($did, parent::getVoc()."dosage", $id)
		);

		$rid = parent::getVoc().md5($dosage->route);
		$this->typify($id,$rid,"Route","".$dosage->route);

		$fid =  parent::getVoc().md5($dosage->form);
		$this->typify($id,$fid,"Form","".$dosage->form);

		if($dosage->strength != '') {
			parent::addRDF(
				parent::triplifyString($id, parent::getVoc()."strength", $dosage->strength)
			);
		}
         }
     }

    // experimental-properties
	$props = array("experimental-properties","calculated-properties");
	foreach($props AS $prop) {
		$subtype = substr($prop,0,strpos("-",$prop));
		if(isset($x->{$prop})) {
			foreach($x->{$prop} AS $properties) {
				foreach($properties AS $property) {
					$type  = (string) $property->kind;
					$value = (string) $property->value;
					$type_uri = parent::getVoc().ucfirst(str_replace(" ","-",$type));
					
					$id = parent::getRes().$prop."-".$dbid."-".($counter++);
					$label = $property->kind.": $value".($property->source == ''?'':" from ".$property->source);
					parent::addRDF(
						parent::describeIndividual($id,$label,$type_uri).
						parent::describeClass($type_uri,$type,parent::getVoc().ucfirst($prop)).
						parent::describeClass(parent::getVoc().ucfirst($prop),str_replace("-"," ",$prop)).
						parent::triplifyString($id,$this->getVoc()."value",$value).
						parent::triplify($did,$this->getVoc().$prop,$id)
					);

					// Source
					if(isset($property->source)) {
						 foreach($property->source AS $source) {
							$s = (string) $source;
							if($s == '') continue;
							$sid = parent::getRes().md5($s);
							 parent::addRDF(
								parent::describeIndividual($sid,$s,parent::getVoc()."Source").
								parent::describeClass(parent::getVoc()."Source","Source").
								parent::triplify($id,parent::getVoc()."source",$sid)
							);
						}
					 }       
				 }
			 }
		}
     } 
 
    
     // identifiers 
     // <patents><patent><number>RE40183</number><country>United States</country><approved>1996-04-09</approved>        <expires>2016-04-09</expires>
     if(isset($x->patents->patent)) {
         foreach($x->patents->patent AS $patent) {
             $id = "uspto:".$patent->number;

             parent::addRDF(
                parent::triplify($did,$this->getVoc()."patent",$id).
                parent::describeIndividual($id,$patent->country." patent ".$patent->number,$this->getVoc()."Patent").
		parent::describeClass(parent::getVoc()."Patent","patent").
                parent::triplifyString($id,$this->getVoc()."approved","".$patent->approved).
                parent::triplifyString($id,$this->getVoc()."expires","".$patent->expires)
             );

             $cid = parent::getRes().md5($patent->country);
             $this->typify($id,$cid,"Country","".$patent->country);
         }
     }
	
	// partners
	$partners = array('target','enzyme','transporter','carrier');
    foreach($partners AS $partner) {
		$plural = $partner.'s';
		if(isset($x->$plural)) {
			foreach($x->$plural AS $list) {
				foreach($list->$partner AS $item) {  
					$this->parsePartnerEntry($did,$item,$partner);
					parent::writeRDFBufferToWriteFile();
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
                     $ddi_id = parent::getRes().$dbid."_".$ddi->drug;
                     parent::addRDF(
                        parent::triplify("drugbank:".$ddi->drug,parent::getVoc()."ddi-interactor-in","".$ddi_id).
                        parent::triplify("drugbank:".$dbid,parent::getVoc()."ddi-interactor-in","".$ddi_id).
                        parent::describeIndividual($ddi_id,"DDI between $name and ".$ddi->name." - ".$ddi->description,parent::getVoc()."Drug-Drug-Interaction").
						parent::describeClass(parent::getVoc()."Drug-Drug-Interaction","drug-drug interaction")
                     );
                 }
             }
         }
     }

     // food-interactions
     $this->AddText($x,$did,"food-interactions","food-interaction",parent::getVoc()."food-interaction");

     // affected-organisms
     $this->AddCategory($x,$did,"affected-organisms","affected-organism",parent::getVoc()."affected-organism");

     //  <external-identifiers>
     if(isset($x->{"external-identifiers"})) {
         foreach($x->{"external-identifiers"} AS $objs) {
             foreach($objs AS $obj) {
                 $ns = $this->NSMap($obj->resource);
                 $id = $obj->identifier;
                 if($ns == "genecards") $id = str_replace(array(" "),array("_"),$id);

                 parent::addRDF(
                    parent::triplify($did,parent::getVoc()."x-$ns","$ns:$id")
                 );
             }
         }
     }
     // <external-links>
     if(isset($x->{"external-links"})) {
         foreach($x->{"external-links"}->{'external-link'} AS $el) {
            if(strpos($el->url,'http') !== false) {
               parent::addRDF(
                  parent::triplify($did,"rdfs:seeAlso","".$el->url)
               );
            }
         }
     }
        
	parent::writeRDFBufferToWriteFile();
    }

    
    function AddLinkedResource(&$x, $id, $list_name,$item_name,$ns)
    {
        if(isset($x->$list_name)) {
            foreach($x->$list_name AS $item) {
		
                if(isset($item->$item_name) && ($item->$item_name != '')) {
					if($item_name == "atc-code") {
						$l = $ns.":".$item->$item_name->attributes()->code;
					} else {
						$l = $ns.":".$item->$item_name;
					}
                    $this->addRDF($this->triplify($id,parent::getVoc()."x-$ns",trim($l)));
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
				$kid = parent::getRes().md5($k);
				$this->addRDF(
					$this->describeIndividual($kid,"$item_name for $id",parent::getVoc().ucfirst($item_name)).
					$this->describeClass(parent::getVoc().ucfirst($item_name),$item_name).
					$this->triplifyString($kid,"rdf:value",$k).
					$this->triplify($id,$predicate,$kid)
				);
                        }
                    } else {
						$kid = parent::getRes().md5($l);
                        $this->addRDF(
							$this->describeIndividual($kid,"$item_name for $id",parent::getVoc().ucfirst($item_name)).
							$this->describeClass(parent::getVoc().ucfirst($item_name),$item_name).
							$this->triplifyString($kid,"rdf:value",$l).
							$this->triplify($id,$predicate,$kid)
						);
                    }
                }
            }
        }
    }
	
	function AddCategory(&$x, $id, $list_name, $item_name, $predicate, $list_item_name = null) 
	{
		if(isset($x->$list_name)) {
			foreach($x->$list_name AS $item) {
				if(isset($item->$item_name) && ($item->$item_name != '')) { 
					$l = $item->$item_name;
					if(isset($l->$list_item_name)) {
						foreach($l->$list_item_name AS $k) {
							$kid = parent::getVoc().ucfirst(str_replace(" ","-",$k));
							$this->addRDF(
								$this->describeIndividual($kid,ucfirst($k),parent::getVoc().ucfirst($item_name)).
								$this->describeClass(parent::getVoc().ucfirst($item_name),ucfirst($item_name)).
								$this->triplify($id,$predicate,$kid)
							);
						}
					} else {
						if($item_name == "synonym") {
							$kid = parent::getvoc().md5($l);
						} else {
							$kid = parent::getVoc().ucfirst(str_replace(" ","-",$l));
						}
						$this->addRDF(
							$this->describeIndividual($kid,ucfirst($l),parent::getVoc().ucfirst($item_name)).
							$this->describeClass(parent::getVoc().ucfirst($item_name),ucfirst($item_name)).
							$this->triplify($id,$predicate,$kid)
						);
						foreach($l->attributes() AS $ka => $va) {
							parent::addRDF(
								$this->triplifyString($kid, parent::getVoc().$ka, $va)
							);
						}
					}
				}
			}
		}
	}
	
	function AddList(&$x, $id, $list_name, $item_name, $predicate, $list_item_name = null)
    {
        if(isset($x->$list_name)) {
            foreach($x->$list_name->$item_name AS $item) {
                if(isset($item->$item_name) && ($item->$item_name != '')) { 
			$l = $item->$item_name;
			foreach($l AS $k) {
				if(!trim($k)) continue;
				$kid = parent::getVoc().ucfirst(str_replace(" ","-",$k)); // generate a new identifier for the list item
				$this->addRDF(
					$this->describeIndividual($kid,$k,parent::getVoc().ucfirst($item_name)).
					$this->describeClass(parent::getVoc().ucfirst($item_name),ucfirst($item_name)).
					$this->triplify($id,$predicate,$kid)
				);
			}
		}
	     }
	  }
	}

} // end class

?>

