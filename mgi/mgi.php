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
                parent::addParameter('files',true,'all|MGI_Strain|MGI_PhenotypicAllele|HMD_HGNC_Accession|MGI_PhenoGenoMP','all','all or comma-separated list to process');
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
                        $list = explode('|',parent::getParameterValue('files'));
                }
                
                foreach($list AS $item) {
                        $lfile = $idir.$item.'.rpt';
                        $rfile = parent::getParameterValue('download_url').$item.'.rpt';
                        if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
                                echo "downloading $item...";
                                Utils::DownloadSingle ($rfile, $lfile);
                        }
                        parent::setReadFile($lfile,true);
                        
                        echo "Processing $item...";
                        $ofile = $odir."mgi-".$item.'.nt'; 
                        $gz=false;
                        if(strstr(parent::getParameterValue('output_format'), "gz")) {
                                $ofile .= '.gz';
                                $gz = true;
                        }
                        
                        parent::setWriteFile($ofile, $gz);
                        $this->$item();
                        parent::GetWriteFile()->Close();
                        parent::GetReadFile()->Close();
                        echo "Done".PHP_EOL;
                }//foreach

                // generate the dataset release file
                echo "generating dataset release file... ";
                $desc = parent::getBio2RDFDatasetDescription(
                        $this->getPrefix(),
                        "https://github.com/bio2rdf/bio2rdf-scripts/blob/master/mgi/mgi.php", 
                        $this->getBio2RDFDownloadURL($this->getNamespace()),
                        "http://www.informatics.jax.org/",
                        array("use"),
                        "http://www.informatics.jax.org/",
                        parent::getParameterValue('download_url'),
                        parent::getDatasetVersion()
                );
                $this->setWriteFile($odir.$this->getBio2RDFReleaseFile($this->getNamespace()));
                $this->getWriteFile()->write($desc);
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
                                echo "skipping badly formed line $line++".PHP_EOL;
                                continue;
                        }

                        $id_label = "mgi id";
                        $id_label_class = "Allele for ".$id;
                        parent::AddRDF(
                                parent::describeIndividual($id, $id_label, $this->getVoc()."Allele").
                                parent::describeClass($this->getVoc()."Allele", $id_label_class)
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
                                        parent::triplify($id, $this->getVoc()."Genetic-Marker", $marker_id).
                                        parent::triplify($marker_id, "rdf:type", $this->getVoc()."Mouse-Marker")
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
                                                parent::QQuaadO_URL($id, $this->getVoc()."phenotype", str_replace("MP:","http://purl.obolibrary.org/obo/MP_",$mp))
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
                        else{
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
                                        parent::triplify($id, $this->getVoc()."model-id", $mp)
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
                                parent::triplify($id, $this->getVoc()."pubmed-id", trim($a[4]))
                                );
                        }

                $this->WriteRDFBufferToWriteFile();
                }
                
        }//closes function
        


        function HMD_HGNC_Accession()
        {
                /*
                MGI Marker Accession ID 
                Mouse Marker Symbol     
                Mouse Marker Name       
                Mouse Entrez Gene ID    
                HGNC ID 
                HGNC Human Marker Symbol        
                Human Entrez Gene ID
                */      
                $line = 0;
                while($l = $this->GetReadFile()->Read(50000)) {
                        $a = explode("\t",$l);
                        $line ++;
                        if(count($a) != 7) {
                                echo "incorrect number of columns at line $line!".PHP_EOL;
                                continue;
                        }
                        
                        $id = "mgi_resource:".$line;
                        $mgi_id  = strtolower($a[0]);
                        $ncbigene_id = "geneid:".trim($a[6]);
                        
                        $id_label = "mgi id";
                        $id_label_class = "Orthologous-Relationship for ".$id;
                        parent::AddRDF(
                                parent::describeIndividual($id, $id_label, $this->getVoc()."Orthologous-Relationship").
                                parent::describeClass($this->getVoc()."Orthologous-Relationship", $id_label_class).
                                parent::triplify($id, $this->getVoc()."x-mgi", "mgi:".$mgi_id).
                                parent::triplify($mgi_id, $this->getVoc()."x-mgi", "mgi:".$mgi_id).
                                parent::describeIndividual($mgi_id, null, null).
                                parent::triplify($id, $this->getVoc()."x-ncbigene", $ncbigene_id)
                        );
                        if($a[4]){
                                parent::AddRDF(
                                        parent::triplify($ncbigene_id, "mgi_vocabulary:x-hgnc", strtolower($a[4]))
                                );
                        }
                }
                $this->WriteRDFBufferToWriteFile();     
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
                                if($error++ == 10) {echo "found 10 errors. quiting!"; return;}
                                continue;
                        }
                        $id = strtolower($a[0]);
                        $id_label = $a[1];
                        parent::AddRDF(
                                parent::describeIndividual($id, $id_label, $this->getVoc()."Strain").
                                parent::triplify($id, $this->getVoc()."strain-type", "mgi_vocabulary:".str_replace(" ","-",strtolower($a[2])))
                        );
                }
                
                $this->WriteRDFBufferToWriteFile();     
        
        } //closes function

} 

?>      
                
