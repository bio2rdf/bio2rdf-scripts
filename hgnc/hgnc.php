<?php

/**
Copyright (C) 2013 Alison Callahan, Jose Cruz-Toledo and Michel Dumontier

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
 * An RDF generator for HGNC (http://www.genenames.org/)
 * @version 2.0
 * @author Alison Callahan
 * @author Jose Cruz-Toledo
*/

require(__DIR__.'/../../php-lib/bio2rdfapi.php');

class HGNCParser extends Bio2RDFizer {
	private $version = 2.0;
	function __construct($argv){
		parent::__construct($argv, "hgnc");
		parent::addParameter('files', true, 'hgnc_complete_set', 'hgnc_complete_set', 'The filename of the complete HGNC dataset');
		parent::addParameter('download_url',false,null,'ftp://ftp.ebi.ac.uk/pub/databases/genenames/hgnc_complete_set.txt.gz');
		parent::initialize();
	}//constructor

	function Run(){
		$file = "hgnc_complete_set.txt.gz";
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$rdir = parent::getParameterValue('download_url');
		//make sure directories end with slash
		if(substr($ldir, -1) !== "/"){
			$ldir = $ldir."/";
		}		
		if(substr($odir, -1) !== "/"){
			$odir = $odir."/";
		}
		$lfile = $ldir.$file;
		if(!file_exists($lfile) && parent::getParameterValue('download') == false) {
			trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
			parent::setParameterValue('download',true);
		}		
		//download the hgnc file
		if(parent::getParameterValue('download') == true) {
			$rfile = $rdir;
			echo "downloading $file ... ";
			Utils::DownloadSingle($rfile, $lfile);
		}

		$ofile = $odir.$file.'.nt'; 
		$gz=false;
		if(strstr(parent::getParameterValue('output_format'), "gz")){			
			$ofile .= '.gz';
			$gz = true;
		}
		
		parent::setWriteFile($ofile, $gz);
		parent::setReadFile($lfile, true);
		echo "processing $file... ";
		$this->process();
		echo "done!".PHP_EOL;
		//close write file
		parent::getWriteFile()->close();
		echo PHP_EOL;
		// generate the dataset release file
		echo "generating dataset release file... ";
		$desc = parent::getBio2RDFDatasetDescription(
			$this->getPrefix(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/hgnc/hgnc.php", 
			$this->getBio2RDFDownloadURL($this->getNamespace()),
			"http://www.genenames.org",
			array("use"),
			"http://www.genenames.org/about/overview",
			"ftp://ftp.ebi.ac.uk/pub/databases/genenames/hgnc_complete_set.txt.gz",
			parent::getDatasetVersion()
		);
		parent::setWriteFile($odir.$this->getBio2RDFReleaseFile($this->getNamespace()));
		parent::getWriteFile()->write($desc);
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;
		
	}//Run

	function process(){
		echo "poto\n";
		/*$header = $this->GetReadFile()->Read(4096);
		$expected = "HGNC ID	Approved Symbol	Approved Name	Status	Locus Type	Locus Group	Previous Symbols	Previous Names	Synonyms	Name Synonyms	Chromosome	Date Approved	Date Modified	Date Symbol Changed	Date Name Changed	Accession Numbers	Enzyme IDs	Entrez Gene ID	Ensembl Gene ID	Mouse Genome Database ID	Specialist Database Links	Specialist Database IDs	Pubmed IDs	RefSeq IDs	Gene Family Tag	Gene family description	Record Type	Primary IDs	Secondary IDs	CCDS IDs	VEGA IDs	Locus Specific Databases	Entrez Gene ID(supplied by NCBI)	OMIM ID(supplied by NCBI)	RefSeq(supplied by NCBI)	UniProt ID(supplied by UniProt)	Ensembl ID(supplied by Ensembl)	UCSC ID(supplied by UCSC)	Mouse Genome Database ID(supplied by MGI)	Rat Genome Database ID(supplied by RGD)\n";
		if ($header != $expected)
		{
			echo PHP_EOL;
			echo "FOUND :".$header.PHP_EOL;
			echo "EXPCTD:".$expected.PHP_EOL;
			trigger_error ("Header format is different than expected, please update the script");
			exit;
		}

		while($l = $this->GetReadFile()->Read(4096)) {
			$fields = explode("\t", $l);
			$id = strtolower($fields[0]);
			$approved_symbol = $fields[1];
			$approved_name = $fields[2];
			$status = $fields[3];
			$locus_type = $fields[4];
			$locus_group = $fields[5];
			$previous_symbols = $fields[6];
			$previous_names = $fields[7];
			$synonyms = $fields[8];
			$name_synonyms = $fields[9];
			$chromosome = $fields[10];
			$date_approved = $fields[11];
			$date_modified = $fields[12];
			$date_symbol_changed = $fields[13];
			$date_name_changed = $fields[14];
			$accession_numbers = $fields[15];
			$enzyme_ids = $fields[16];
			$entrez_gene_id = $fields[17];
			$ensembl_gene_id = $fields[18];
			$mouse_genome_database_id = $fields[19];
			$specialist_database_links = $fields[20];
			$specialist_database_ids = $fields[21];
			$pubmed_ids = $fields[22];
			$refseq_ids = $fields[23];
			$gene_family_tag = $fields[24];
			$gene_family_description = $fields[25];
			$record_type = $fields[26];
			$primary_ids = $fields[27];
			$secondary_ids = $fields [28];
			$ccd_ids = $fields[29];
			$vega_ids = $fields[30];
			$locus_specific_databases = $fields[31];
			$entrez_gene_id_mappeddatasuppliedbyNCBI = $fields[32];
			$omim_id_mappeddatasuppliedbyNCBI = $fields[33];
			$refseq_mappeddatasuppliedbyNCBI = $fields[34];
			$uniprot_id_mappeddatasuppliedbyUniProt = $fields[35];
			$ensembl_id_mappeddatasuppliedbyEnsembl = $fields[36];
			$ucsc_id_mappeddatasuppliedbyUCSC = $fields[37];
			$mouse_genome_database_id_mappeddatasuppliedbyMGI = $fields[38];
			$rat_genome_database_id_mappeddatasuppliedbyRGD = $fields[39];

			$this->AddRDF($this->QQuad($id, "rdf:type", "hgnc_vocabulary:GeneSymbol"));
			$this->AddRDF($this->QQuad($id, "void:inDataset", $this->GetDatasetURI()));
			$this->AddRDF($this->QQuadL($id, "dc:identifier", "$id"));

			if(!empty($approved_symbol)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:approved_symbol", $approved_symbol));
			}

			if(!empty($approved_name)){
				$this->AddRDF($this->QQuadL($id, "rdfs:label", "$approved_name [$id]"));
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:approved_name", $approved_name));
			}
			
			if(!empty($status)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:status", $status));
			}
			
			if(!empty($locus_id)){
				$locus_id = "hgnc:".$hgnc_id."_LOCUS";
				$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:locus", $locus_id));
				$this->AddRDF($this->QQuadL($locus_id, "hgnc_vocabulary:locus_type", $locus_type));
				$this->AddRDF($this->QQuadL($locus_id, "hgnc_vocabulary:locus_group", $locus_group));
			}
			
			if(!empty($previous_symbols)){
				$previous_symbols = explode(", ", $previous_symbols);
				foreach($previous_symbols as $previous_symbol){
					$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:previous_symbol", $previous_symbol));
				}
			}

			if(!empty($previous_names)){
				$previous_names = explode(", ", $previous_names);
				foreach($previous_names as $previous_name){
					$previous_name = str_replace("\"", "", $previous_name);
					$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:previous_name", $this->SafeLiteral($previous_name)));
				}
			}

			if(!empty($synonyms)){
				$synonyms = explode(", ", $synonyms);
				foreach ($synonyms as $synonym) {
					$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:synonym", $synonym));
				}
			}

			if(!empty($name_synonyms)){
				$name_synonyms = explode(", ", $name_synonyms);
				foreach ($name_synonyms as $name_synonym) {
					$name_synonym = str_replace("\"", "", $name_synonym);
					$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:name_synonym", $this->SafeLiteral($name_synonym)));
				}
			}

			if(!empty($chromosome)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:chromosome", "$chromosome"));
			}

			if(!empty($date_approved)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:date_approved", "$date_approved", null, "xsd:date"));
			}

			if(!empty($date_modified)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:date_modified", "$date_modified", null, "xsd:date"));
			}

			if(!empty($date_symbol_changed)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:date_symbol_changed", "$date_symbol_changed", null, "xsd:date"));
			}

			if(!empty($date_name_changed)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:date_name_changed", "$date_name_changed", null, "xsd:date"));
			}

			if(!empty($accession_numbers)){
				$accession_numbers = explode(", ", $accession_numbers);
				foreach ($accession_numbers as $accession_number) {
					$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:accession_number", "$accession_number"));
				}
			}

			if(!empty($enzyme_ids)){
				$enzyme_ids = explode(", ", $enzyme_ids);
				foreach ($enzyme_ids as $enzyme_id) {
					$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:enzyme_id", "$enzyme_id"));
				}
			}

			if(!empty($entrez_gene_id)){
				$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-geneid", "geneid:$entrez_gene_id"));
			}

			if(!empty($ensembl_gene_id)){
				$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-ensembl", "ensembl:$ensembl_gene_id"));
			}

			if(!empty($mouse_genome_database_id)){
				if(strpos($mouse_genome_database_id, "MGI:") !== FALSE){
					$mouse_genome_database_id = substr($mouse_genome_database_id, 4);
				}
				$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-mgi", "mgi:$mouse_genome_database_id"));
			}

			if(!empty($specialist_database_links)){
				$specialist_database_links = explode(", ", $specialist_database_links);
				foreach ($specialist_database_links as $specialist_database_link) {
					preg_match('/href="(\S+)"/', $specialist_database_link, $matches);
					if(!empty($matches[1])){
						$this->AddRDF($this->QQuadO_URL($id, "hgnc_vocabulary:specialist_database_link", $matches[1]));
					}
				}
			}

			if(!empty($pubmed_ids)){
				$pubmed_ids = explode(", ", $pubmed_ids);
				foreach ($pubmed_ids as $pubmed_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-pubmed", "pubmed:".trim($pubmed_id)));
				}
			}

			if(!empty($refseq_ids)){
				$refseq_ids = explode(", ", $refseq_ids);
				foreach ($refseq_ids as $refseq_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-refseq", "refseq:".trim($refseq_id)));
				}
			}

			if(!empty($gene_family_tag)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:gene_family_tag", "$gene_family_tag"));
			}

			if(!empty($gene_family_description)){
				$gene_family_description = str_replace("\"", "", $gene_family_description);
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:gene_family_description", "$gene_family_description"));
			}

			if(!empty($record_type)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:record_type", $this->SafeLiteral($record_type)));
			}

			if(!empty($primary_ids)){
				$primary_ids = explode(", ", $primary_ids);
				foreach ($primary_ids as $primary_id) {
					$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:primary_id", "$primary_id"));
				}
			}

			if(!empty($secondary_ids)){
				$secondary_ids = explode(", ", $secondary_ids);
				foreach ($secondary_ids as $secondary_id) {
					$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:secondary_id", "$secondary_id"));
				}
			}

			if(!empty($ccd_ids)){
				$ccd_ids = explode(", ", $ccd_ids);
				foreach ($ccd_ids as $ccd_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-ccd", "ccd:".$ccd_id));
				}
			}

			if(!empty($vega_ids)){
				$vega_ids = explode(", ", $vega_ids);
				foreach ($vega_ids as $vega_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-vega", "vega:".$vega_id));
				}
			}

			if(!empty($locus_specific_databases)){
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:locus_specific_databases", $this->SafeLiteral($locus_specific_databases)));
			}

		

			if(!empty($entrez_gene_id_mappeddatasuppliedbyNCBI)){
				$entrez_gene_id_mappeddatasuppliedbyNCBI = explode(", ", $entrez_gene_id_mappeddatasuppliedbyNCBI);
				foreach ($entrez_gene_id_mappeddatasuppliedbyNCBI as $gene_id) {
					if(strstr($gene_id, ":") !== FALSE){
						$gene_id = explode(":", $gene_id);
						$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-geneid", "geneid:".$gene_id[1]));
					} else {
						$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-geneid", "geneid:".$gene_id));
					}
					
				}
			}

			if(!empty($omim_id_mappeddatasuppliedbyNCBI)){
				$omim_id_mappeddatasuppliedbyNCBI = explode(", ", $omim_id_mappeddatasuppliedbyNCBI);
				foreach ($omim_id_mappeddatasuppliedbyNCBI as $omim_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-omim", "omim:".$omim_id));
				}
			}

			if(!empty($refseq_mappeddatasuppliedbyNCBI)){
				$refseq_mappeddatasuppliedbyNCBI = explode(", ", $refseq_mappeddatasuppliedbyNCBI);
				foreach ($refseq_mappeddatasuppliedbyNCBI as $refseq_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-refseq", "refseq:".$refseq_id));
				}
			}

			if(!empty($uniprot_id_mappeddatasuppliedbyUniProt)){
				$uniprot_id_mappeddatasuppliedbyUniProt = explode(", ", $uniprot_id_mappeddatasuppliedbyUniProt);
				foreach ($uniprot_id_mappeddatasuppliedbyUniProt as $uniprot_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-uniprot", "uniprot:".$uniprot_id));
				}
			}

			if(!empty($ensembl_id_mappeddatasuppliedbyEnsembl)){
				$ensembl_id_mappeddatasuppliedbyEnsembl = explode(", ", $ensembl_id_mappeddatasuppliedbyEnsembl);
				foreach ($ensembl_id_mappeddatasuppliedbyEnsembl as $ensembl_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-ensembl", "ensembl:".$ensembl_id));
				}
			}

			if(!empty($ucsc_id_mappeddatasuppliedbyUCSC)){
				$ucsc_id_mappeddatasuppliedbyUCSC = explode(", ", $ucsc_id_mappeddatasuppliedbyUCSC);
				foreach ($ucsc_id_mappeddatasuppliedbyUCSC as $ucsc_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-ucsc", "ucsc:".$ucsc_id));
				}
			}

			if(!empty($mouse_genome_database_id_mappeddatasuppliedbyMGI)){
				$mouse_genome_database_id_mappeddatasuppliedbyMGI = explode(", ", $mouse_genome_database_id_mappeddatasuppliedbyMGI);
				foreach ($mouse_genome_database_id_mappeddatasuppliedbyMGI as $mgi_id) {
					if(strpos($mgi_id, "MGI:") !== FALSE){
						$mgi_id = substr($mgi_id, 4);
					}
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-mgi", "mgi:".$mgi_id));
				}
			}

			if(!empty($rat_genome_database_id_mappeddatasuppliedbyRGD)){
				$rat_genome_database_id_mappeddatasuppliedbyRGD = explode(", ", trim($rat_genome_database_id_mappeddatasuppliedbyRGD));
				foreach ($rat_genome_database_id_mappeddatasuppliedbyRGD as $rgd_id) {
					$rgd_id = trim($rgd_id);
					if(!empty($rgd_id)){
						$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-rgd", strtolower($rgd_id)));
					}
				}
			}
			//write RDF to file
			$this->WriteRDFBufferToWriteFile();
		}//while
		*/
	}//process
}//HGNCParser
$start = microtime(true);

set_error_handler('error_handler');
$parser = new HGNCParser($argv);
$parser->Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"

?>
