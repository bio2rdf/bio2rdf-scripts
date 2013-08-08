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
    // Names of direct child XML elements that are literals
    private $directChildLiterals = array(
		"indication",
		'pharmacology',
		"mechanism-of-action",
		"toxicity",
		"biotransformation",
		"absorption",
		"volume-of-distribution",
		"clearance"
	);
    
    function __construct($argv) {
        parent::__construct($argv,"drugbank");
        parent::addParameter('files', true, 'all|drugbank|target_ids','all','Files to convert');
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
		
		$dataset_description = '';
		foreach($files AS $f) {
			if($f == 'drugbank') {
				$file = 'drugbank.xml.zip';
				$lname = 'drugbank_drugs';
			} else if($f == 'target_ids') {
				$file = 'all_target_ids_all.csv.zip';
				$lname = 'drugbank_target_ids';
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
				echo "processing $file ...";
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
			$date = date ("Y-m-d\TG:i:s\Z");
			// dataset description
			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("DrugBank ($file)")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($indir.$file)))
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
		parent::getWriteFile()->close();
    }
	
	function parse_target_ids($ldir,$infile)
	{
		/*
		[0] => ID
		[1] => Name
		[2] => Gene Name
		[3] => GenBank Protein ID
		[4] => GenBank Gene ID
		[5] => UniProt ID
		[6] => Uniprot Title
		[7] => PDB ID
		[8] => GeneCard ID
		[9] => GenAtlas ID
		[10] => HGNC ID
		[11] => HPRD ID
		[12] => Species Category
		[13] => Species
		[14] => Drug IDs
		*/
		$csv_file = basename($infile,".zip");
		$lfile = $ldir.$infile;
		$zin = new ZipArchive();
		if ($zin->open($lfile) === FALSE) {
			trigger_error("Unable to open $lfile");
			exit;
		}
		if(($fp = $zin->getStream($csv_file)) === FALSE) {
			trigger_error("Unable to get $csv_file in ziparchive $lfile");
			return FALSE;
		}
		parent::setReadFile($lfile);
		parent::getReadFile()->setFilePointer($fp);
		
		$header = explode(",",parent::getReadFile()->read());
		while($l = parent::getReadFile()->read(500000)) {		
			$a = str_getcsv($l);
			$tid = 'drugbank_target:'.$a[0];
			
			if($a[2] && $a[2] != '""') parent::addRDF(parent::triplifyString($tid,parent::getVoc().'gene-name',$a[2]));
			if($a[3] && $a[3] != '""') parent::addRDF(parent::triplify($tid,parent::getVoc().'x-gi',"gi:".$a[3]));
			if($a[4] && $a[4] != '""') parent::addRDF(parent::triplifyString($tid,parent::getVoc().'gene-locus',$a[4]));
			if($a[5] && $a[5] != '""') {
				parent::addRDF(parent::triplify($tid,parent::getVoc().'x-uniprot',"uniprot:".$a[5]));
				if($a[6] && $a[6] != '""') parent::addRDF(parent::triplifyString("uniprot:".$a[5],"rdfs:label",$a[6]));
			}
			if($a[7] && $a[7] != '""') parent::addRDF(parent::triplify($tid,parent::getVoc().'x-pdb',"pdb:".$a[7]));
			if($a[8] && $a[8] != '""') {
				$a[8] = str_replace(" ","_",$a[8]);
				parent::addRDF(parent::triplify($tid,parent::getVoc().'x-genecards',"genecards:".$a[8]));
			}
			if($a[9] && $a[9] != '""') parent::addRDF(parent::triplify($tid,parent::getVoc().'x-genatlas','genatlas:'.$a[9]));
			if($a[10] && $a[10] != '""') {
				if($a[10][0] == "G") $a[10] = "H".$a[10];
				if($a[10][0] != 'H') $a[10] = 'hgnc:'.$a[10];
				parent::addRDF(parent::triplify($tid,parent::getVoc().'x-hgnc',$a[10]));
			}
			if($a[11] && $a[11] != '""') parent::addRDF(parent::triplify($tid,parent::getVoc().'x-hprd','hprd:'.$a[11]));
		
			parent::writeRDFBufferToWriteFile();
		}
		parent::getReadFile()->close();
	}


    function parse_drugbank($ldir,$infile)
    {
        $i = 0;
		$xml = new CXML($ldir,$infile);
		while($xml->parse("drug") == TRUE) {
			$this->parseDrugEntry($xml);
		}
		unset($xml);
		
        $xml = new CXML($ldir,$infile);
        while($xml->parse("partner") == TRUE) {
            $this->parsePartnerEntry($xml);
        }
        unset($xml);
    }
    
    function parsePartnerEntry(&$xml)
    {
        $x                         = $xml->GetXMLRoot();
        $id                        = (string)$x->attributes()->id;
        $pid                       = "drugbank_target:".$id;
        $name                      = (string) $x->name;
        $this->named_entries[$pid] = $name;

        parent::addRDF(
            parent::describeIndividual($pid,$name,parent::getVoc()."Target",null,null)
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
                                parent::triplify($pid,parent::getVoc()."article","pubmed:".$pmid)
                            );  
                        }
                    }
                } else if($v != '') {
                    parent::addRDF(
                        parent::triplifyString($pid,parent::getVoc().$k,addslashes((string)$v))
                    );
                }
                
            } else {
                // work with nested elements
                
                // special cases
                if($k == "species") {
                    parent::addRDF(
                        parent::triplify($pid,parent::getVoc()."species","taxon:".$v->{'uniprot-taxon-id'})
                    );  
                
                } else {
                    
                    // default handling for collections
                    $found = false;
                    $list_name = $k;
                    $item_name = substr($k,0,-1);
                    foreach($v->children() AS $k2 => $v2) {
                        if(!$v2->children() && $k2 == $item_name) {
                            parent::addRDF(
                                parent::triplifyString($pid,parent::getVoc().$item_name,"".$v2)
                            );
                            $found = true;
                        } else {
                            // need special handling
                            if($k == 'external-identifiers') {
                                $ns = $this->NSMap($v2->resource);
                                if($ns == "genecards") $id = str_replace(array(" "),array("_"),$id);

                                parent::addRDF(
                                    parent::triplify($pid,parent::getVoc()."xref","$ns:$id")
                                );
                            } elseif($k == 'pfams') {
                                parent::addRDF(
                                    parent::triplify($pid,parent::getVoc()."xref","pfam:".$v2->identifier)
                                );
                            }   
                         } // special handlers
                    }
                 } // default handler
            }
         }
		 parent::writeRDFBufferToWriteFile();
    }

    /**
    * @description check if a type has already been defined and add appropriate RDF
    * NOTE:: Should be moved into bio2rdfapi.php
    */
    function typify($id,$tid,$subclass,$label){

        parent::addRDF(
            parent::triplify($id,$this->getVoc().strtolower($subclass),$tid)
        );

        if(!isset($defined[$tid])) {
             $defined[$tid] = '';
             parent::addRDF(
                 parent::describeClass($tid,$label,$this->getVoc().$subclass)
             );
        }
    }

    function parseDrugEntry(&$xml)
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
            parent::describeIndividual($did, $name, parent::getVoc()."Drug",$name, $description).
            parent::triplify($did,"owl:sameAs","http://identifiers.org/drugbank/".$dbid).
            parent::triplify($did,"rdfs:seeAlso","http://www.drugbank.ca/drugs/".$dbid). 
            parent::triplifyString($did,parent::getVoc()."category", ucfirst($x->attributes()->type[0]))
        );

		
        // TODO:: Replace the next two lines
        $this->AddText($x,$did,"groups","group",parent::getVoc()."category");
        $this->AddText($x,$did,"categories","category",parent::getVoc()."category");

		$this->addLinkedResource($x, $did, 'atc-codes','atc-code','atc');
		$this->addLinkedResource($x, $did, 'atc-ahfs','ahfs-code','ahfs');
        
        // taxonomy
        $this->AddText($x,$did,"taxonomy","kingdom",parent::getVoc()."kingdom");

        // substructures
        $this->AddText($x,$did,"taxonomy","substructures",parent::getVoc()."substructure", "substructure");
            
        // synonyms
        $this->AddText($x,$did,"synonyms","synonym",parent::getVoc()."synonym");

        // brand names
        $this->AddText($x,$did,"brands","brand",parent::getVoc()."brand");

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
                        parent::describeIndividual($mid,$o->name[0],parent::getVoc()."Mixture",null).
                        parent::triplifyString($mid,$this->getVoc()."ingredients","".$o->ingredients[0]) 
                    );
                 
                    $a = explode(" + ",$o->ingredients[0]);
                    foreach($a AS $b) {
                        $b = trim($b);
                        $iid = parent::getRes().str_replace(" ","-",$b);
                        parent::addRDF(
                            parent::triplifyString($iid,parent::getVoc()."ingredients",$b).
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
                                parent::describe($pid,"".$item->name[0],null,null)
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
             $uid = parent::getVoc()."".md5($product->unit);
               
                parent::addRDF(
                  parent::describeIndividual($pid,"".$product->description[0],parent::getVoc()."Pharmaceutical").
                  parent::triplify($did,parent::getVoc()."product",$pid).
                  parent::triplifyString($pid,parent::getVoc()."price","".$product->cost)
                );    

             // NOTE:: Should move the variable checking to describe and triplify?
             if(!isset($defined[$uid])) {
                 $defined[$uid] = '';
                    parent::addRDF(
                        parent::describeIndividual($uid,$product->unit,parent::getVoc()."Unit").
                        parent::triplify($pid,parent::getVoc()."form",$uid) 
                    );
             }
         }
     }           
        
     // dosages <dosages><dosage><form>Powder, for solution</form><route>Intravenous</route><strength></strength></dosage>
     if(isset($x->dosages->dosage)) {
         foreach($x->dosages->dosage AS $dosage) {
            $id = parent::getRes().md5($dosage->form.$dosage->route);

            parent::addRDF(
                parent::triplify($did,parent::getVoc()."dosage",$id).
                parent::describe($id,$dosage->form." by ".$dosage->route,parent::getVoc()."Dossage")
            );    

            $rid = "pharmgkb_vocabulary:".md5($dosage->route);
            $this->typify($id,$rid,"Route","".$dosage->route);

            $fid = "pharmgkb_vocabulary:".md5($dosage->form);
            $this->typify($id,$fid,"Form","".$dosage->form);
         }
     } 

     // experimental-properties
     if(isset($x->{"experimental-properties"})) {
         foreach($x->{"experimental-properties"} AS $properties) {
             foreach($properties AS $property) {
                 $type  = "".$property->kind;
                 $value = "".$property->value;
                
                 $id = "drugbank_resource:experimental_property_".$dbid."_".($counter++);
                 parent::addRDF(
                     parent::triplify($did,$this->getVoc()."experimental-property",$id).
                     parent::triplifyString($id,$this->getVoc()."value",$value).
                     parent::triplifyString($id,"rdfs:label",$property->kind.": $value".($property->source == ''?'':" from ".$property->source)." [$id]")
                 );

                 // Type
                 $tid = parent::getVoc()."".md5($type);
                 $this->typify($id,$tid,"Experimental-Property",$type);
                    
                 // Source
                 if(isset($property->source)) {
                     foreach($property->source AS $source) {
                         $sid = parent::getRes().md5($source);
                         $this->typify($id,$sid,"Source",$source);
                     }
                 }       
             }
         }
     } 
        
     // Calculated-properties
     if(isset($x->{"calculated-properties"})) {
         foreach($x->{"calculated-properties"} AS $properties) {
             foreach($properties AS $property) {
                 $type   = "".$property->kind;
                 $value  = addslashes($property->value);
                 $source = "".$property->source;            
                    
                 $id = "drugbank_resource:calculated_property_".$dbid."_".($counter++);
                 parent::addRDF(
                    parent::triplify($did,$this->getVoc()."calculated-property",$id).
                    parent::describe($id,$property->kind.": $value".($property->source == ''?'':" from ".$property->source)." [$id]",null,null)
                 );

                 // value
                 if($type == "InChIKey") {
                     $value = substr($value,strpos($value,"=")+1);
                 }
                 parent::addRDF(
                    parent::triplifyString($id,$this->getVoc()."value",$value)
                 );

                 // type
                 $tid = parent::getVoc()."".md5($type);
                 $this->typify($id,$tid,"Calculated-Property",$type);
                    
                 // source
                 if(isset($property->source)) {
                     foreach($property->source AS $source) {
                         $sid = parent::getRes().md5($source);
                         $this->typify($id,$sid,"Source",$source);
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
                parent::triplifyString($id,$this->getVoc()."approved","".$patent->approved).
                parent::triplifyString($id,$this->getVoc()."expires","".$patent->expires)
             );
                           
             $cid = parent::getRes().md5($patent->country);
             $this->typify($id,$cid,"Country","".$patent->country);
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
                    
                 $this->AddRDF($this->QQuad($did,parent::getVoc()."target",$tid));
                    
                 $dti = parent::getRes().$dbid."_".$pid;
                 parent::addRDF(
                     parent::describeIndividual($dti,"drug-target interaction $name and $partner_name",$this->getVoc()."Drug-Target-Interaction").
                     parent::triplify($dti,$this->getVoc()."drug",$did).
                     parent::triplify($dti,$this->getVoc()."target",$tid) 
                 );
                    
                 if(isset($target->actions)) {
                     foreach($target->actions AS $action) {
                         if(isset($action->action) && $action->action != '') {
                             parent::addRDF(
                                 parent::triplifyString($dti,$this->getVoc()."action","".$action->action)
                             );
                         }
                     }
                 }
                 if(isset($target->{"known-action"})) {
                     parent::addRDF(
                         parent::triplifyString($dti,$this->getVoc()."pharmacological-action","".$target->{'known-action'})
                     );

                 }               
                 if(isset($target->references)) {
                     $a = preg_match_all("/pubmed\/([0-9]+)/",$target->references,$m);
                     if(isset($m[1])) {
                         foreach($m[1] AS $pmid) {
                             parent::addRDF(
                                 parent::triplify($dti,$this->getVoc()."article","pubmed:$pmid")
                             );
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
                 $this->AddRDF($this->QQuad($did,parent::getVoc()."enzyme",$tid));
                    
                 $dti = parent::getRes().$dbid."_".$enzyme->attributes()->partner;
                 $partner_name = $tid;
                 if(isset($this->named_entries[$tid])) $partner_name = $this->named_entries[$tid];
                    parent::addRDF(
                         parent::describeIndividual($dti,"drug-enzyme interaction $name and $partner_name",$this->getVoc()."Drug-Enzyme-Interaction").
                         parent::triplify($dti,$this->getVoc()."drug",$did).
                         parent::triplify($dti,$this->getVoc()."enzyme",$tid)
                    );
                 
                 if(isset($enzyme->actions)) {
                     foreach($enzyme->actions AS $action) {
                         if(isset($action->action) && $action->action != '') {
                            parent::addRDF(
                               parent::triplifyString($dti,$this->getVoc()."action","".$enzyme->action)
                            );
                         }
                     }
                 }
                 if(isset($enzyme->{"known-action"})) {
                    parent::addRDF(
                               parent::triplifyString($dti,$this->getVoc()."known-action","".$enzyme->{'known-action'})
                    );
                 }               
                 if(isset($enzyme->references)) {
                     $a = preg_match_all("/pubmed\/([0-9]+)/",$enzyme->references,$m);
                     if(isset($m[1])) {
                         foreach($m[1] AS $pmid) {
                            parent::addRDF(
                               parent::triplify($dti,$this->getVoc()."article","pubmed:".$pmid)
                            );
                         }
                     }
                 }
                    
             }
         }
     }
    
//      // transporters
     if(isset($x->transporters)) {
         foreach($x->transporters AS $transporters) {
             foreach($transporters->transporter AS $transporter) {                   
                 $tid = "drugbank_target:".$transporter->attributes()->partner;
                 parent::addRDF(
                    parent::triplify($did,$this->getVoc()."transporter",$tid)
                 );
                    
                 $dti = parent::getRes().$dbid."_".$tid;
                 $partner_name = $tid;
                 if(isset($this->named_entries[$tid])) $partner_name = $this->named_entries[$tid];
                    parent::addRDF(
                        parent::describeIndividual($dti,"drug-transporter interaction $name and $partner_name [$dti]",$this->getVoc()."Drug-Transporter-Interaction").
                        parent::triplify($dti,$this->getVoc()."drug",$did).
                        parent::triplify($dti,$this->getVoc()."transporter",$tid)
                    );

                 if(isset($transporter->actions)) {
                     foreach($transporter->actions AS $action) {
                         if(isset($action->action) && $action->action != '') {
                            parent::addRDF(
                               parent::triplifyString($dti,$this->getVoc()."action","".$action->action)
                            );
                         }
                     }
                 }
                 if(isset($transporter->{"known-action"})) {

                    parent::addRDF(
                               parent::triplifyString($dti,$this->getVoc()."pharmacological-action","".$transporter->{"known-action"})
                    );
                 }               
                 if(isset($transporter->references)) {
                     $a = preg_match_all("/pubmed\/([0-9]+)/",$transporter->references,$m);
                     if(isset($m[1])) {
                         foreach($m[1] AS $pmid) {
                            parent::addRDF(
                               parent::triplify($dti,$this->getVoc()."article","pubmed:".$pmid)
                            );
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
                     $ddi_id = parent::getRes().$dbid."_".$ddi->drug;
                     parent::addRDF(
                        parent::triplify("drugbank:".$ddi->drug,parent::getVoc()."ddi-interactor-in","".$ddi_id).
                        parent::describeIndividual($ddi_id,"DDI between $name and ".$ddi->name." - ".trim($this->SafeLiteral($ddi->description)),parent::getVoc()."Drug-Drug-Interaction")
                     );
                 }
             }
         }
     }

     // food-interactions
     $this->AddText($x,$did,"food-interactions","food-interaction",parent::getVoc()."food-interaction");
     
     // affected-organisms
     $this->AddText($x,$did,"affected-organisms","affected-organism",parent::getVoc()."affected-organism");
        
     //  <external-identifiers>
     if(isset($x->{"external-identifiers"})) {
         foreach($x->{"external-identifiers"} AS $objs) {
             foreach($objs AS $obj) {
                 $ns = $this->NSMap($obj->resource);
                 $id = $obj->identifier;
                 if($ns == "genecards") $id = str_replace(array(" "),array("_"),$id);

                 parent::addRDF(
                    parent::triplify($did,parent::getVoc()."xref","$ns:$id")
                 );
             }
         }
     }
     // <external-links>
     if(isset($x->{"external-links"})) {
         foreach($x->{"external-links"} AS $objs) {
             foreach($objs AS $obj) {
                    if(strpos($obj->url,'http') !== false){

                        parent::addRDF(
                            parent::triplify($did,"rdfs:seeAlso","".$obj->url)
                        );
                    }
                }
         }
     }
        
		parent::writeRDFBufferToWriteFile();
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
                    $this->AddRDF($this->triplify($id,parent::getVoc()."x-$ns",trim($l)));
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
                            $this->AddRDF($this->triplifyString($id,$predicate,addslashes(ucfirst($k))));
                        }
                    } else {
                        $this->AddRDF($this->triplifyString($id,$predicate,addslashes(ucfirst($l))));
                    }
                }
            }
        }
    }

} // end class

?>
