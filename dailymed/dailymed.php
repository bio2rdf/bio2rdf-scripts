<?php
/**
Copyright (C) 2013 Michel Dumontier

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
 * An RDF generator for DailyMed
 * documentation: https://dailymed.nlm.nih.gov/
 * @version 1.0
 * @author Michel Dumontier
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class DailymedParser extends Bio2RDFizer 
{
	function __construct($argv) {
		parent::__construct($argv, "dailymed");
		parent::addParameter('files',true,'all|prescription|otc','all','all or comma-separated list of short names to process');
		parent::addParameter('download_url',false,null,'ftp://public.nlm.nih.gov/nlmdata/.dailymed/');
		parent::initialize();
	}

	var $filemap = array(
        "prescription" => array(
            "dm_spl_release_human_rx_part1.zip",
            "dm_spl_release_human_rx_part2.zip",
            "dm_spl_release_human_rx_part3.zip",
            "dm_spl_release_human_rx_part4.zip"),
        "otc" => array(
            "dm_spl_release_human_otc_part1.zip",
            "dm_spl_release_human_otc_part2.zip",
            "dm_spl_release_human_otc_part3.zip",
            "dm_spl_release_human_otc_part4.zip",
            "dm_spl_release_human_otc_part5.zip",
            "dm_spl_release_human_otc_part6.zip",
            "dm_spl_release_human_otc_part7.zip"
        )
        
    );
        
	function run() 
	{
        $dd = '';
        $ldir = parent::getParameterValue('indir');
        $odir = parent::getParameterValue('outdir');
        $tdir = $ldir."tmp/";
        @mkdir ($tdir, 0777);
		
		$files = parent::getParameterValue('files');
		if($files == 'all') {
			$files = explode('|', parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(',', parent::getParameterValue('files'));
		}
        
        
		foreach($files AS $filetype) {
            echo "processing $filetype ...";
            
            // download files
			$files_ = $this->filemap[$filetype];
			foreach($files_ AS $file) {	
                $lfile = $ldir.$file;
				$rfile = parent::getParameterValue('download_url').$file;
				if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
					$ret = utils::downloadSingle($rfile,$lfile);
					if($ret === false) {
						echo "unable to download $file ... skipping".PHP_EOL;
						continue;
					}
				}
            }
/*
            $xmlfile = "c:/data/download/dailymed/tmp/8ae4a0c1-1424-47a9-9a59-7fe38bedc0c7.xml";
            $this->setReadFile($xmlfile);
            $this->$filetype($xmlfile);
            exit;
*/
            // process files
            $z= 0;
            foreach($files_ AS $file) {	
                $lfile = $ldir.$file;
                $zin1 = new ZipArchive();
                if ($zin1->open($lfile) === FALSE) {
                    trigger_error("Unable to open $lfile");
                    exit;
                }
                
                $suffix = parent::getParameterValue('output_format');
                $ofile = "dailymed-".substr($file,0,-4).'.'.$suffix; 
                $gz = strstr(parent::getParameterValue('output_format'), "gz")?($gz=true):($gz=false);
                parent::setWriteFile($odir.$ofile, $gz);

                for($i = 0; $i < $zin1->count(); $i++) {
                    //if(++$z == 20) break;
                    $entry = $zin1->getNameIndex($i);
                    echo "processing $entry".PHP_EOL;

                    // extract the dailymed entry (zip file) as a temporary file
                    $fileinfo = pathinfo($entry);
                    $tfile = $tdir.$fileinfo['basename'];
                    if(!file_exists($tfile)) {
                        //break;
                        copy("zip://".$lfile."#".$entry, $tfile);
                    }

                    // read the dailmed entry zip file
                    $zin2 = new ZipArchive();
                    if ($zin2->open($tfile) !== TRUE) {
                        trigger_error("Unable to open $lfile2",E_USER_ERROR);
                        exit;
                    }
                    
                    // now find, extract, and process the xml file
                    for($j = 0; $j < $zin2->count(); $j++) {
                        $f = $zin2->getNameIndex($j);
                        if(!strstr($f,".xml")) continue;                  
 
                        $fileinfo = pathinfo($f);
                        $xmlfile = $tdir.$fileinfo['basename'];
                        $gzxml = $xmlfile.".gz";
                        if(!file_exists($gzxml)) {
                            copy("zip://".$tfile."#".$f, "compress.zlib://".$gzxml);
                        }

                        $this->setReadFile($gzxml);
                        $this->$filetype($gzxml);
                        $this->getReadFile()->close();
                        $this->clear();
                        //unlink($xmlfile);
                    }
                    $zin2->close();
                    //unlink($tfile);                    
                }
                $zin1->close();
                parent::getWriteFile()->close();
            }

			// dataset description
            $source_file = (new DataResource($this))
                ->setURI($rfile)
                ->setTitle("Dailymed: $file")
                ->setRetrievedDate(parent::getDate(filemtime($lfile)))
                ->setFormat("application/xml")
                ->setPublisher("https://dailymed.nlm.nih.gov")
                ->setHomepage("https://dailymed.nlm.nih.gov")
                ->setRights("use")
                ->setLicense("http://creativecommons.org/licenses/by-nd/3.0/")
                ->setDataset("http://identifiers.org/dailmed/");

            $prefix = parent::getPrefix();
            $bVersion = parent::getParameterValue('bio2rdf_release');
            $date = parent::getDate(filemtime($odir.$ofile));

            $output_file = (new DataResource($this))
                ->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
                ->setTitle("Bio2RDF v$bVersion RDF version of $prefix")
                ->setSource($source_file->getURI())
                ->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/dailymed/dailymed.php")
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

			$dd .= $source_file->toRDF().$output_file->toRDF();

		}//foreach
		parent::writeToReleaseFile($dd);
	}

    function otc($file)
    {
        return $this->prescription($file);    
    }
    
	function prescription($file)
	{
        $xml = new CXML($file);
        parent::addRDF(
            parent::describeClass(parent::getVoc()."Indication-Section", "FDA product label indication section")
        );

		while($xml->parse("document") == TRUE) {
            $x = $xml->getXMLRoot();
            $setid = $x->setId->attributes()->root;
            $id = parent::getNamespace().$setid;
            $title = addslashes(str_replace( array("\t", "\r","\n", '"'), array(" ","","",""), (string) $x->title));
            #@todo look elsewhere if empty

            $type_id = "loinc:".$x->code->attributes()->code;
            $type_label = $x->code->attributes()->displayName;

            parent::addRDF(
                parent::describeIndividual($id, $title, $type_id).
                parent::describeClass($type_id, $type_label)
            );

            $z = 1;
			foreach($x->component->structuredBody->component AS $c) {
                $section = $c->section;
                $code = (string) @$section->code->attributes()->code;
                if($code != "34067-9" and $code != "42229-5") continue; // indications
                $type_id = "loinc:$code";

                if(isset($section->text)) {
                    $sid = parent::getRes().md5($section->text->asXML());
                    $x = (string) $section->text->asXML();
                    
                    $x = preg_replace('/(?i)<[^>]*>/', ' ', $x);
                    setlocale(LC_ALL, 'en_GB');
                    $x = @iconv('UTF-8', 'ASCII//IGNORE', $x);
                    $x = str_replace(array('"',"'",'\\','�'),'', $x);                    
                    $x = trim(preg_replace("/\s+/",' ',$x));
                    $x = addslashes($x);

                    parent::addRDF(
                        parent::describeIndividual($sid, "indication section", $type_id).
                        parent::triplifyString($sid, "rdf:value", $x).
                        parent::triplifyString($sid, parent::getVoc()."strlen", strlen($x)).
                        parent::triplify($id, parent::getVoc()."indicationSection", $sid)
                    );   
                }
/*
                if(isset($section->component->section)) {
                    foreach($section->component as $component) {
                        $component_code = (string) @$component->section->code->attributes()->code;
                        $component_type_id = "loinc:$component_code";
                        $component_label = (string) $component->title;

                        $sid = parent::getRes().md5($id.$component->section->text->asXML());
                        $content = addslashes(trim((string) $component->section->text->asXML()));
                        if($content != "") {
                            parent::addRDF(
                                parent::describeIndividual($sid, "$component_label section $z for $id", $component_type_id).
                                parent::triplifyString($sid, "rdf:value", $content).
                                parent::triplifyString($sid, parent::getVoc()."order", $z++).
                                parent::triplify($id, parent::getVoc()."indicationSection", $sid)
                            );   
                        }
                    }
                    
                } else 
                */

                /* for processing individual paragraphs
                $n = 0;
                foreach($section->text->paragraph AS $paragraph) {
                    $pid = parent::getRes().md5($paragraph->asXML());
                    $content = trim((string) $paragraph);
                    if($content == "") continue;

                    parent::addRDF(
                        parent::describeIndividual($pid, "indication section ".++$n." for $id", $type_id).
                        parent::triplifyString($pid, "rdf:value", $content).
                        parent::triplifyString($pid, parent::getVoc()."order", $n).
                        parent::triplify($id, parent::getVoc()."indicationSection", $pid)
                    );   
                }
                */
            }
            
		}
        unset($xml);	
        parent::writeRDFBufferToWriteFile();
    }
}
?>
