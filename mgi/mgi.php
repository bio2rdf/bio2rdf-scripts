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
                parent::addParameter('files',true,'all|MGI_Strain|MGI_PhenotypicAllele|MGI_GenePheno|MRK_Sequence|MGI_Geno_Disease|MGI_Geno_NotDisease','all','all or comma-separated list to process');
                parent::addParameter('download_url', false, null,'http://www.informatics.jax.org/downloads/reports/' );
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
                                $ret = Utils::DownloadSingle ($rfile, $lfile);
								if($ret != true) {
									continue;
								}
                        }
                        parent::setReadFile($lfile,true);
                        
                        echo "Processing $item...";
                        $ofile = $odir."bio2rdf-".$item.'.'.parent::getParameterValue('output_format'); 
                        $gz= strstr(parent::getParameterValue('output_format'), "gz")?true:false;

                        parent::setWriteFile($ofile, $gz);
                        $this->$item();
                        parent::getWriteFile()->close();
                        parent::getReadFile()->close();
                        echo "Done".PHP_EOL;
						parent::clear();
                        $source_file = (new DataResource($this))
                                ->setURI($rfile)
                                ->setTitle("MGI $item")
                                ->setRetrievedDate( date ("Y-m-d\TH:i:s", filemtime($lfile)))
                                ->setFormat("text")
                                ->setPublisher("http://www.informatics.jax.org")
                                ->setHomepage("http://www.informatics.jax.org")
                                ->setRights("use")
                                ->setLicense("http://www.informatics.jax.org/mgihome/other/copyright.shtml")
                                ->setDataset("http://identifiers.org/mgi/");


                        $prefix = parent::getPrefix();
                        $bVersion = parent::getParameterValue('bio2rdf_release');
                        $date = date ("Y-m-d\TH:i:s");

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
	4 Allele Attribute
        5 PubMed ID for original reference      
        6 MGI Marker Accession ID       
        7 Marker Symbol 
        8 Marker RefSeq ID      
        9 Marker Ensembl ID     
        10 High-level Mammalian Phenotype ID (comma-delimited)   
        11 Synonyms (|-delimited) 
        */
        function MGI_PhenotypicAllele($qtl = false)
        {
		$line = 0; $errors = 0;
		while($l = $this->GetReadFile()->Read(200000)) {
			$a = explode("\t",$l);
			$line++;
			if($a[0][0] == "#") continue;
			$expected_columns = 13;
			if(count($a) != $expected_columns) {
				echo "Expecting $expected_columns columns, but found ".count($a)." at line $line. skipping!".PHP_EOL;
				if($errors++ == 25) {echo 'stopping'.PHP_EOL;break;}
				continue;
			}
			$id = strtolower($a[0]);

			$id_label = $a[1].", ".$a[2];
			parent::addRDF(
				parent::describeIndividual($id, $id_label, $this->getVoc()."Allele").
				parent::describeClass($this->getVoc()."Allele", "MGI Allele")
			);

			if(trim($a[1])) {
				parent::addRDF(
					parent::triplifyString($id, $this->getVoc()."allele-symbol", trim($a[1]))
				);
			}
			if(trim($a[2])) {
				parent::addRDF(
					parent::triplifyString($id, $this->getVoc()."allele-name", trim($a[2]))
				);
			}
			if(trim($a[3])) {
				parent::addRDF(
					parent::triplifyString($id, $this->getVoc()."allele-type", trim($a[3]))
				);
			}
			if(trim($a[4])) {
				$list = explode("|",$a[4]);
				foreach($list AS $item) {
					parent::addRDF(
						parent::triplifyString($id, $this->getVoc()."allele-attribute", trim($item))
					);
				}
			}
			if(trim($a[5])) {
				parent::addRDF(
					parent::triplify($id, $this->getVoc()."x-pubmed", "pubmed:".trim($a[5]))
				);
			}
/*			if(trim($a[6])) {
				$marker_id = $a[6];
				parent::addRDF(
					parent::triplify($id, $this->getVoc()."marker", $marker_id).
					parent::triplify($marker_id, "rdf:type", $this->getVoc()."MGI-Marker").
					parent::describeClass($this->getVoc()."MGI-Marker","MGI Marker")
				);

				if(trim($a[7])) {
					parent::addRDF(
						parent::triplifyString($marker_id, $this->getVoc()."marker-symbol", trim(strtolower($a[7])))
					);
				}
				if(trim($a[8])) {
					parent::addRDF(
						parent::triplify($marker_id, $this->getVoc()."x-refseq", "refseq:".trim($a[8]))
					);
				}
				if(trim($a[9])) {
 					parent::addRDF(
						parent::triplify($marker_id, $this->getVoc()."x-ensembl", "ensembl:".trim($a[8]))
					);
				}
			}

			if(trim($a[9])) {
				$b = explode(",",$a[9]);
				foreach($b AS $mp) {
					//$mp_uri = str_replace("MP:","http://purl.obolibrary.org/obo/MP_",$mp);
					parent::addRDF(
						parent::triplify($id, $this->getVoc()."high-level-phenotype", $mp)
					);
				}
			}
*/
			$this->writeRDFBufferToWriteFile(); 
		}
        } //closes function


	/*
	Gene-Allele-Background-Phenotype-Literature

	0 Allelic Composition	 - Rbpj<tm1Kyo>/Rbpj<tm1Kyo>
	1 Allele Symbol(s)	 - Rbpj<tm1Kyo>
	2 Allele ID(s)	     - MGI:1857411
	3 Genetic Background	 - involves: 129S2/SvPas * C57BL/6
	4 Mammalian Phenotype ID	- MP:0000364
	5 PubMed ID	         - 15466160
	6 MGI Marker Accession ID (comma-delimited) - MGI:96522
	7 MGI Genotype ID (comma-delimted) 
	*/
	function MGI_GenePheno()
	{
		$line = 1;
		while($l = $this->getReadFile()->read(248000)) {
			$a = explode("\t",$l);
			$exp = 8;
			if(count($a) != $exp) {
				trigger_error("Incorrect number of columns: Found ".count($a)." and was expecting $exp",E_USER_WARNING);
				exit();
			}
			$id = trim($a[7]);

			$label = $a[0]." ".$a[3];
			parent::addRDF(
				parent::describeIndividual($id, $label, $this->getVoc()."Genotype").
				parent::describeClass($this->getVoc()."Genotype","MGI Genotype").
				parent::triplifyString($id,$this->getVoc()."genotype",$a[0]).
				parent::triplifyString($id,$this->getVoc()."background",$a[3]).
				parent::triplify($id,$this->getVoc()."phenotype",$a[4])
			);
			if($a[2]) {
				//parent::triplifyString($id,$this->getVoc()."allele-symbol",$a[1]).
				$alleles = explode("|",$a[2]);
				foreach($alleles AS $allele) {
					parent::addRDF(
						parent::triplify($id,$this->getVoc()."allele",$allele)
					);
				}
			}

			if($a[5]) {
				$pmids = explode("|",$a[5]);
				foreach($pmids AS $pmid) {
					parent::addRDF(		
						parent::triplify($id,$this->getVoc()."x-pubmed","pubmed:".$pmid)	
					);
				}
			}
			$b = explode("|",$a[6]);
			foreach($b AS $marker) {
				parent::addRDF(
					parent::triplify($id,$this->getVoc()."marker",$marker).
					parent::triplify($marker, "rdf:type", parent::getVoc()."Marker").
					parent::describeClass(parent::getVoc()."Marker","MGI Marker")
				);
			}
			$this->writeRDFBufferToWriteFile();
		}
	}


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

        function MRK_Sequence()
        {
		$cols = 19;
		$line = 0;
		$h = $this->getReadFile()->read(500000);
		$o = $this->getReadFile()->read(500000); // extra feature header on a separate line...if you can imagine
		//print_r(explode("\t",$h));exit;
		while($l = $this->getReadFile()->Read(500000)) {
			$a = explode("\t",$l);
			$line ++;
			if(count($a) != $cols) {
				echo "Expecting $cols columns, but found ".count($a)." at line $line. skipping!".PHP_EOL;
				print_r($a);
				continue;
			}
			$id  = strtolower($a[0]);
			$type =  $this->getVoc().str_replace(" ","-",$a[3]);
			parent::addRDF(
				parent::describeIndividual($id, $a[1], $type).
				parent::describeClass($type,"MGI ".$a[3]).
				parent::triplifyString($id, parent::getVoc()."symbol", $a[1]).
				parent::triplifyString($id, parent::getVoc()."status", $a[2]).
				parent::triplifyString($id, parent::getVoc()."name", $a[4]).
				parent::triplifyString($id, parent::getVoc()."cm-position", $a[5], "xsd:string").
				parent::triplifyString($id, parent::getVoc()."chromosome", $a[6], "xsd:string").
				parent::triplifyString($id, parent::getVoc()."genome-start", $a[7], "xsd:string").
				parent::triplifyString($id, parent::getVoc()."genome-end", $a[8], "xsd:string").
				parent::triplifyString($id, parent::getVoc()."strand", $a[7], "xsd:string")
			);
			$start_pos = 10;
			$list = array("genbank","refseq-transcript","ensembl-transcript","uniprot","trembl","ensembl-protein","refseq-protein","unigene");
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


	/*
	MGI Strain ID	Strain Name	Strain Type
	*/
        function MGI_Strain()
        {
                $line = 0;
                $errors = 0;
                while($l = $this->getReadFile()->read(50000)) {
                        $a = explode("\t",trim($l));
                        $line ++;
                        if(count($a) != 3) {
                                echo "Expecting 3 columns, but found ".count($a)." at line $line. skipping!".PHP_EOL;
                                if($errors++ == 10) {echo "found 10 errors. quitting!"; return;}
                                continue;
                        }
                        $id = strtolower($a[0]);
                        $id_label = $a[1];
                        parent::addRDF(
                                parent::describeIndividual($id, $id_label, $this->getVoc()."Strain").
				parent::describeClass($this->getVoc()."Strain", "MGI Strain").
                                parent::triplify($id, $this->getVoc()."strain-type", "mgi_vocabulary:".str_replace(" ","-",strtolower($a[2])))
                        );
		                $this->writeRDFBufferToWriteFile();     
                }
        } //closes function

	/*
		0 Allelic Composition	
		1 Allele Symbol(s)
		2 Allele ID(s)	
		3 Genetic Background	
		4 Mammalian Phenotype ID	
		5 PubMed ID	
		6 MGI Marker Accession ID (comma-delimited)	
		7 OMIM ID (comma-delimited)
	*/
	function MGI_Geno_Disease()
	{
		$line = 1;
		while($l = $this->getReadFile()->read(248000)) {
			$a = explode("\t",$l);
			if(count($a) != 8) {
				trigger_error("Incorrect number of columns",E_USER_WARNING);
				continue;
			}
			
			$allele = strtolower($a[2]);
			if(!$allele) {echo "ignoring ".$a[0].PHP_EOL;continue;}

			$alleles = explode("|",strtolower($a[2]));
			$genotype = $a[0];
			$diseases = explode(",",$a[7]);
			foreach($diseases AS $d) {
				$disease = "$d";
				foreach($alleles AS $allele) {
					$id = parent::getRes().md5($allele.$disease); 
					$label = "$allele $disease association";
					parent::addRDF(
						parent::describeIndividual($id, $label, $this->getVoc()."Allele-Disease-Association").
						parent::describeClass($this->getVoc()."Allele-Disease-Association","MGI Allele-Disease Association").
						parent::triplifyString($id,$this->getVoc()."genotype-string",$genotype).
						parent::triplify($id,$this->getVoc()."allele",$allele).
						parent::triplify($id,$this->getVoc()."disease",$disease)
					);
					if($a[5]) {
						$pmids = explode(",",$a[5]);
						foreach($pmids AS $pmid) {
							parent::addRDF(		
								parent::triplify($id,$this->getVoc()."x-pubmed","pubmed:".$pmid)	
							);
						}
					}
				}
			}
			$this->writeRDFBufferToWriteFile();
		}
	}
	
		/*
		0 Allelic Composition	
		1 Allele Symbol(s)
		2 Allele ID(s)	
		3 Genetic Background	
		4 Mammalian Phenotype ID	
		5 PubMed ID	
		6 MGI Marker Accession ID (comma-delimited)	
		7 OMIM ID (comma-delimited)
	*/
	function MGI_Geno_NotDisease()
	{
		$line = 1;
		while($l = $this->getReadFile()->read(248000)) {
			$a = explode("\t",$l);
			if(count($a) != 8) {
				trigger_error("Incorrect number of columns",E_USER_WARNING);
				continue;
			}
			
			$genotype = $a[0];
			$alleles = explode("|",strtolower($a[2]));
			$diseases = explode(",",$a[7]);
			foreach($diseases AS $d) {
				$disease = "$d";

				foreach($alleles AS $allele) {
					$id = parent::getRes().md5($allele.$disease); 
					$label = "$allele $disease absent association";
					parent::addRDF(
						parent::describeIndividual($id, $label, $this->getVoc()."Allele-Disease-Non-Association").
						parent::describeClass($this->getVoc()."Allele-Disease-Non-Association","MGI Allele-Disease Non-Association").
						parent::triplify($id,$this->getVoc()."allele",$allele).
						parent::triplifyString($id,$this->getVoc()."genotype-string",$genotype).
						parent::triplify($id,$this->getVoc()."disease",$disease).
						parent::triplifyString($id,$this->getVoc()."is-negated","true")
					);

					if($a[5]) {
						$pmids = explode(",",$a[5]);
						foreach($pmids AS $pmid) {
							parent::addRDF(		
								parent::triplify($id,$this->getVoc()."x-pubmed","pubmed:".$pmid)	
							);
						}
					}
				}
			}
			$this->writeRDFBufferToWriteFile();
		}
	}
	
}

?>
