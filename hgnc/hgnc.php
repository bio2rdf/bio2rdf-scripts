<?php

/**
Copyright (C) 2012 Alison Callahan

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
 * @version 1.0
 * @author Alison Callahan
*/

require('../../php-lib/rdfapi.php');

class HGNCParser extends RDFFactory {
	private $version = null;

	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("hgnc");
		
		// set and print application parameters
		$this->AddParameter('indir',false,null,'/data/download/hgnc/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/hgnc/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download file');
		$this->AddParameter('download_url',false,null,'http://www.genenames.org/cgi-bin/hgnc_downloads?title=HGNC+output+data&hgnc_dbtag=on&preset=all&status=Approved&status=Entry+Withdrawn&status_opt=2&level=pri&=on&where=&order_by=gd_app_sym_sort&limit=&format=text&submit=submit&.cgifields=&.cgifields=level&.cgifields=chr&.cgifields=status&.cgifields=hgnc_dbtag');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
		
		return TRUE;
	}//construct

	function Run(){

		$file = "hgnc.tab";

		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rdir = $this->GetParameterValue('download_url');
		
		//make sure directories end with slash
		if(substr($ldir, -1) !== "/"){
			$ldir = $ldir."/";
		}
		
		if(substr($odir, -1) !== "/"){
			$odir = $odir."/";
		}

		$lfile = $ldir.$file;

		if(!file_exists($lfile) && $this->GetParameterValue('download') == false) {
			trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
			$this->SetParameterValue('download',true);
		}
		
		//download all files [except mapping file]
		if($this->GetParameterValue('download') == true) {
			$rfile = $rdir;
			echo "downloading $file ... ";
			file_put_contents($lfile,file_get_contents($rfile));
		}

		$ofile = $odir.$file.'.nt'; 
		$gz=false;
		
		if($this->GetParameterValue('gzip')) {
			$ofile .= '.gz';
			$gz = true;
		}
		
		$this->SetWriteFile($ofile, $gz);

		$this->SetReadFile($lfile);
		
		echo "processing $file... ";
		$this->process();
		echo "done!";

		//close write file
		$this->GetWriteFile()->Close();
		echo PHP_EOL;
		
		// generate the dataset release file
		echo "generating dataset release file... ";
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/hgnc/hgnc.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()),
			"http://www.genenames.org",
			array("use"),
			"http://www.genenames.org/about/overview",
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		echo "done!".PHP_EOL;
		
	}//Run

	function process(){
		$this->GetReadFile()->Read(4096);
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
			$vega_ids = $fields[23];
			$locus_specific_databases = $fields[31];
			$gdb_id_mappeddata = $fields[32];
			$entrez_gene_id_mappeddatasuppliedbyNCBI = $fields[33];
			$omim_id_mappeddatasuppliedbyNCBI = $fields[34];
			$refseq_mappeddatasuppliedbyNCBI = $fields[35];
			$uniprot_id_mappeddatasuppliedbyUniProt = $fields[36];
			$ensembl_id_mappeddatasuppliedbyEnsembl = $fields[37];
			$ucsc_id_mappeddatasuppliedbyUCSC = $fields[38];
			$mouse_genome_database_id_mappeddatasuppliedbyMGI = $fields[39];
			$rat_genome_database_id_mappeddatasuppliedbyRGD = $fields[40];

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
				$this->AddRDF($this->QQuadL($id, "hgnc_vocabulary:locus_specific_databases", "$locus_specific_databases"));
			}

			/*if(!empty($gdb_id_mappeddata)){
				$gdb_id_mappeddata = explode(", ", $gdb_id_mappeddata);
				foreach ($gdb_id_mappeddata as $gdb_id) {
					$this->AddRDF($this->QQuad($id, "hgnc_vocabulary:x-gdb", "gdb:".$gdb_id));
				}
			}*/

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
	}//process
}//HGNCParser

set_error_handler('error_handler');
$parser = new HGNCParser($argv);
$parser->Run();

?>
