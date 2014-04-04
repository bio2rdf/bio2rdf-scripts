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
 * An RDF generator for MGI
 * documentation: ftp://ftp.informatics.jax.org/pub/reports/index.html
 * @version 2.0
 * @author Michel Dumontier
 * @author Jose Cruz-Toledo
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
class MGIParser extends Bio2RDFizer 
{
        function __construct($argv) {
                parent::__construct($argv, "mgi");
                parent::addParameter('files',true,'all|MGI_Strain|MGI_PhenotypicAllele|MGI_PhenoGenoMP|MRK_Sequence','all','all or comma-separated list to process');
                parent::addParameter('download_url', false, null,'ftp://ftp.informatics.jax.org/pub/reports/' );
                parent::initialize();
        }
        
        function Run()
        {
                $idir = parent::getParameterValue('indir');
                $odir = parent::getParameterValue('outdir');
                $files = parent::getParameterValue('files');
                
                if($files == 'all') {
                        $list = explode('|',parent::getParameterList('files'));
                        array_shift($list);
                } else {
                        $list = explode(',',parent::getParameterValue('files'));
                }
		$dataset_description = '';                
                foreach($list AS $item) {
                        $lfile = $idir.$item.'.rpt';
                        $rfile = parent::getParameterValue('download_url').$item.'.rpt';
                        if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
                                echo "downloading $item...";
                                Utils::DownloadSingle ($rfile, $lfile);
                        }
                        parent::setReadFile($lfile,true);
                        
                        echo "Processing $item...";
                        $ofile = $odir.$item.'.'.parent::getParameterValue('output_format'); 
                        $gz= strstr(parent::getParameterValue('output_format'), "gz")?true:false;

                        parent::setWriteFile($ofile, $gz);
                        $this->$item();
                        parent::GetWriteFile()->Close();
                        parent::GetReadFile()->Close();
                        echo "Done".PHP_EOL;
			parent::clear();

                        $source_file = (new DataResource($this))
                                ->setURI($rfile)
                                ->setTitle("MGI $item")
                                ->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
                                ->setFormat("text")
                                ->setPublisher("http://www.informatics.jax.org")
                                ->setHomepage("http://www.informatics.jax.org")
                                ->setRights("use")
                                ->setLicense("http://www.informatics.jax.org/mgihome/other/copyright.shtml")
                                ->setDataset("http://identifiers.org/mgi/");

                        $prefix = parent::getPrefix();
                        $bVersion = parent::getParameterValue('bio2rdf_release');
                        $date = date ("Y-m-d\TG:i:s\Z");
                        $output_file = (new DataResource($this))
                                ->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
                                ->setTitle("Bio2RDF v$bVersion RDF version of $item in $prefix")
                                ->setSource($source_file->getURI())
                                ->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/mgi/mgi.php")
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
			

                }//foreach

                // generate the dataset release file
 		$this->setWriteFile($odir.parent::getBio2RDFReleaseFile());
                $this->getWriteFile()->write($dataset_description);
                $this->getWriteFile()->close();
                echo "done!".PHP_EOL;
        }
        
        /*
        0 MGI Allele Accession ID       
        1 Allele Symbol 
        2 Allele Name   
        3 Allele Type   
        4 PubMed ID for original reference      
        5 MGI Marker Accession ID       
        6 Marker Symbol 
        7 Marker RefSeq ID      
        8 Marker Ensembl ID     
        9 High-level Mammalian Phenotype ID (comma-delimited)   
        10 Synonyms (|-delimited)
        */
        function MGI_PhenotypicAllele($qtl = false)
        {
                $line = 0;
                while($l = $this->GetReadFile()->Read(200000)) {
                        $a = explode("\t",$l);
                        $line++;
                        
                        $id = strtolower($a[0]);
                        if($a[0][0] == "#") continue;
                        
                        if(count($a) != 11) {
                                echo "Expecting 11 columns, but found ".count($a)." at line $line. skipping!".PHP_EOL;
                                continue;
                        }

                        $id_label = $a[1].", ".$a[2];
                        parent::AddRDF(
                                parent::describeIndividual($id, $id_label, $this->getVoc()."Allele").
                                parent::describeClass($this->getVoc()."Allele", "MGI Allele")
                        );

                        if(trim($a[1])) {
                                parent::AddRDF(
                                        parent::triplifyString($id, $this->getVoc()."allele-symbol", trim($a[1]))
                                );
                        }
                        if(trim($a[2])) {
                                parent::AddRDF(
                                        parent::triplifyString($id, $this->getVoc()."allele-name", trim($a[2]))
                                );
                        }
                        if(trim($a[3])) {
                                parent::AddRDF(
                                        parent::triplifyString($id, $this->getVoc()."allele-type", trim($a[3]))
                                );
                        }
                        if(trim($a[4])) {
                                parent::AddRDF(
                                        parent::triplify($id, $this->getVoc()."x-pubmed", "pubmed:".trim($a[4]))
                                );
                        }
                        if(trim($a[5])) {
                                $marker_id = strtolower($a[5]);
                                parent::AddRDF(
                                        parent::triplify($id, $this->getVoc()."genetic-marker", $marker_id).
                                        parent::triplify($marker_id, "rdf:type", $this->getVoc()."MGI-Marker").
					parent::describeClass($this->getVoc()."MGI-Marker","MGI Marker")
                                );              
                                if(trim($a[6])) {
                                        parent::AddRDF(
                                                parent::triplifyString($marker_id, $this->getVoc()."marker-symbol", trim(strtolower($a[6])))
                                        );
                                }
                                if(trim($a[7])) {
                                        parent::AddRDF(
                                                parent::triplify($marker_id, $this->getVoc()."x-refseq", "refseq:".trim($a[7]))
                                        );
                                }               
                                if(trim($a[8])) {
                                        parent::AddRDF(
                                                parent::triplify($marker_id, $this->getVoc()."x-ensembl", "ensembl:".trim($a[8]))
                                        );
                                }
                        }

                        if(trim($a[9])) {
                                $b = explode(",",$a[9]);
                                foreach($b AS $mp) {
                                        parent::AddRDF(
                                                parent::QQuadO_URL($id, $this->getVoc()."phenotype", str_replace("MP:","http://purl.obolibrary.org/obo/MP_",$mp))
                                        );
                                }
                        }
                }
                $this->WriteRDFBufferToWriteFile();     
        } //closes function




/*
        0 Allelic composition   
        1 Allele Symbol 
        2 Genetic Background    
        3 Mammalian Phenotype ID        
        4 PubMed ID for original reference      
        5 MGI Marker Accession ID       
        
        */
        function MGI_PhenoGenoMP(){
                $line = 1;
                while($l = $this->GetReadFile()->Read(248000)) {
                    $a = explode("\t",$l);
            
                    $line++;
                    if($a[0][0] == "#") continue;

                    if(count($a) == 6) {
                        //make identifier for row
                        $id = $this->getRes().md5($a[0].$a[1].$a[2].$a[3].$a[4].$a[5]);
                        //echo $id;
                    }
                    else {
                        //echo "skipping badly formed line $line".PHP_EOL;
                        continue;
                    }                       
                    
                    
                    
                    //describe this individual
                    $id_label = "Genotype-phenotype association between ".$a[1]." and ".$a[3]." for model(s) ".trim($a[5]);
                    $class_label = "Genotype-phenotype association";
                    parent::AddRDF(
                        parent::describeIndividual($id, $id_label, $this->getVoc()."Genotype-phenotype-association").
                        parent::describeClass($this->getVoc()."Genotype-phenotype-association", $class_label)
                    );

                    // model id's seperated by commas -> break into strings, and set as ID
                    $b = explode(",",$a[5]);
                    foreach($b AS $mp) {
                        $mp = strtolower(trim($mp));
                        parent::AddRDF(
                                parent::triplify($id, $this->getVoc()."mouse-marker", $mp)
                        );

                    }

                    //get allelic composition
                    if(trim($a[0])) {
                    parent::AddRDF(
                            parent::triplifyString($id, $this->getVoc()."allele-composition", trim($a[0]))
                        );
                    }

                    //get the allele symbol for this association
                    if(trim($a[1])) {
                    parent::AddRDF(
                            parent::triplifyString($id, $this->getVoc()."allele-symbol", trim($a[1]))
                        );
                    }

                    //get genetic composition
                    if(trim($a[2])) {
                    parent::AddRDF(
                            parent::triplifyString($id, $this->getVoc()."genetic-background", trim($a[2]))
                        );
                    }

                    //get mammalian phenotype ID
                    if(trim($a[3])) {
                    parent::AddRDF(
                            parent::triplify($id, $this->getVoc()."mammalian-phenotype-id", trim($a[3]))
                        );
                    }

                    //get pubmed ID
                    if(trim($a[4])) {
                    parent::AddRDF(
                            parent::triplify($id, $this->getVoc()."pubmed-id", "pubmed:".trim($a[4]))
                        );
                    }

                    $this->WriteRDFBufferToWriteFile();
                }
                
        }//closes function
        


        function MRK_Sequence()
        {
                
/*
0-MGI Marker Accession ID	
1-Marker Symbol	
2-Status	
3-Marker Type	
4-Marker Name	
5-cM Position	
6-Chromosome	
7-Genome Coordinate Start
8-Genome Coordinate End	
9-Strand	
10-GenBank Accession IDs (pipe-delimited)	
11-RefSeq Transcript ID (if any)	
12-VEGA Transcript ID (if any)	
13-Ensembl Transcript ID (if any)	
14-UniProt ID (if any)	
15-TrEMBL ID (if any)	
16-VEGA protein ID (if any)	
17-Ensembl protein ID (if any)	
18-RefSeq protein ID (if any)	
19-Unigene ID (if any)
*/
		$cols = 20;
                $line = 0;
		$h = $this->getReadFile()->read(50000);
                while($l = $this->GetReadFile()->Read(500000)) {
                        $a = explode("\t",$l);
                        $line ++;
                        if(count($a) != $cols) {
                                echo "Expecting $cols columns, but found ".count($a)." at line $line. skipping!".PHP_EOL;
                                continue;
                        }
                        
                        $id  = strtolower($a[0]);
                        parent::AddRDF(
                                parent::describeIndividual($id, $a[1], $this->getVoc()."MGI-Marker").
                                parent::describeClass($this->getVoc()."MGI-Marker", "MGI Marker").
                                parent::triplifyString($id, parent::getVoc()."symbol", $a[1]).
                                parent::triplifyString($id, parent::getVoc()."status", $a[2]).
                                parent::triplify($id, "rdf:type", $this->getRes().str_replace(" ","-",$a[3])).
                                parent::triplifyString($id, parent::getVoc()."name", $a[4]).
                                parent::triplifyString($id, parent::getVoc()."cm-position", $a[5]).
                                parent::triplifyString($id, parent::getVoc()."chromosome", $a[6])
                        );
			$start_pos = 10;
			$list = array("genbank","refseq-transcript","vega-transcript","ensembl-transcript","uniprot","trembl","vega-protein","ensembl-protein","refseq-protein","unigene");
			$list_len = count($list);
			for($i=0;$i<$list_len;$i++) {
				$value = trim($a[$i+$start_pos]);
				if($value) {
					$rel = $list[$i];
					$b = explode("-",$list[$i]);
					$ns = $b[0];
					
					$ids = explode("|",$value);
					foreach($ids AS $mid) {
						parent::addRDF(
	        		                        parent::triplify($id, $this->getVoc()."x-$rel", "$ns:$mid")
						);
					}
				}
			}
	                $this->writeRDFBufferToWriteFile();
                }
        } //closes function
        
        function MGI_Strain()
        {
                $line = 0;
                $errors = 0;
                while($l = $this->GetReadFile()->Read(50000)) {
                        $a = explode("\t",trim($l));
                        $line ++;
                        if(count($a) != 3) {
                                echo "Expecting 3 columns, but found ".count($a)." at line $line. skipping!".PHP_EOL;
                                if($errors++ == 10) {echo "found 10 errors. quitting!"; return;}
                                continue;
                        }
                        $id = strtolower($a[0]);
                        $id_label = $a[1];
                        parent::AddRDF(
                                parent::describeIndividual($id, $id_label, $this->getVoc()."Strain").
				parent::describeClass($this->getVoc()."Strain", "MGI Strain").
                                parent::triplify($id, $this->getVoc()."strain-type", "mgi_vocabulary:".str_replace(" ","-",strtolower($a[2])))
                        );
                }
                
                $this->WriteRDFBufferToWriteFile();     
        
        } //closes function

} 

?>      
                
