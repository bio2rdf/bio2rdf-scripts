<?php
/**
Copyright (C) 2013 Jose Cruz-Toledo, Alison Callahan

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
 * Entrez Gene RDFizer
 * @version 0.2.2
 * @author Jose Cruz-Toledo
 * @author Alison Callahan
 * @contributor Michel Dumontier
 * @description ftp://ftp.ncbi.nih.gov/gene/DATA/
*/
require_once(__DIR__.'../../php-lib/bio2rdfapi.php');

class EntrezGeneParser extends Bio2RDFizer{

		private $version = null;
		
		private static $packageMap = array(
			"geneinfo" => "GENE_INFO/All_Data.gene_info.gz",
			"gene2accession" => "gene2accession.gz",
			"gene2ensembl" => "gene2ensembl.gz",
			"gene2go" => "gene2go.gz",
			"gene2pubmed" => "gene2pubmed.gz",
			"gene2refseq" => "gene2refseq.gz",
			"gene2sts" => "gene2sts",
			"gene2unigene" => "gene2unigene",
			"gene2vega" => "gene2vega.gz",					
		);
		
		function __construct($argv) {
			parent::__construct($argv,"ncbigene");
			
			// set and print application parameters
			parent::addParameter('files',true,'all|geneinfo|gene2accession|gene2ensembl|gene2go|gene2pubmed|gene2refseq|gene2sts|gene2unigene|gene2vega','','files to process');
			parent::addParameter('download_url',false,null,'ftp://ftp.ncbi.nih.gov/gene/DATA/');
			parent::initialize();
			
	  }//constructor
	  
	 function Run(){
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

		//which files are to be converted?
		$selectedPackage = trim($this->GetParameterValue('files'));		 
		if($selectedPackage == 'all') {
			$files = $this->getPackageMap();
		} else {
			$sel_arr = explode(",",$selectedPackage);
			$pm = $this->getPackageMap();
			$files = array();
			foreach($sel_arr as $a){
				if(array_key_exists($a, $pm)){
					$files[$a] = $pm[$a];
				}
			}	
		}
		//now iterate over the files array
		foreach ($files as $id => $file){
			echo "Processing $id ... ";	

			$lfile = $ldir.$id.".gz";

			// download
			if(!file_exists($lfile) || parent::getParameterValue('download') == true) { 				
				//don't use subdirectory GENE_INFO for saving local version of All_data.gene_info.gz
				if($id == "gene2sts" || $id == "gene2unigene") {
					$rfile = "compress.zlib://".$rdir.$file;
				} else {
					$rfile = $rdir.$file;
				}
				Utils::DownloadSingle($rfile, $lfile);
			}
			
			$ofile = $odir.$id.".nt"; 
			$gz = false;
			if(parent::getParameterValue('graph_uri')) {
				$ofile .= ".nq";
			}
			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$ofile .= '.gz';
				$gz = true;
			}

			parent::setReadFile($lfile, true);
			parent::setWriteFile($ofile, $gz);
			$fnx = $id;
			echo ' parsing ...';
			$this->$fnx();
			echo 'done!'.PHP_EOL;
			parent::getReadFile()->Close();
			parent::getWriteFile()->Close();
			
		}//foreach
		
		// generate the dataset release file
		echo "generating dataset release file... ";
		$desc = parent::getBio2RDFDatasetDescription(
			$this->getPrefix(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/gene/entrez_gene.php", 
			$this->getBio2RDFDownloadURL($this->getNamespace()),
			"http://yeastgenome.org",
			array("use-share-modify"),
			"http://www.ncbi.nlm.nih.gov/About/disclaimer.html",
			parent::getParameterValue('download_url'),
			parent::getDatasetVersion()
		);
		$this->setWriteFile($odir.$this->getBio2RDFReleaseFile($this->getNamespace()));
		$this->getWriteFile()->write($desc);
		$this->getWriteFile()->close();
		echo "done!".PHP_EOL;
		
	}//run

	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2vega(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 7){
					$taxid = trim($splitLine[0]);
					$aGeneId = trim($splitLine[1]);
					$vegaGeneId = trim($splitLine[2]);
					$rnaNucleotideAccession = trim($splitLine[3]);
					$vegaRnaIdentifier = trim($splitLine[4]);
					$proteinAccession = trim($splitLine[5]);
					$vegaProteinId = trim($splitLine[6]);
					//taxid
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_taxid", "taxon:".$taxid).
						parent::describeProperty($this->getVoc()."has_taxid", "Relationship between a gene and a taxonomic identifier")
					);

					//vega gene identifier
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_vega_gene", "vega:".$vegaGeneId).
						parent::describeProperty($this->getVoc()."has_vega_gene", "Relationship between a gene and a vega gene identifier")
					);

					//rna nucleotide accession
					if($rnaNucleotideAccession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_refseq_rna_nucleotide_accession", "refseq:".$rnaNucleotideAccession).
							parent::describeProperty($this->getVoc()."has_rna_nucleotide_accession", "Relationship between a gene and a RefSeq RNA accession")
						);
					}
					//vega rna id
					if($vegaRnaIdentifier != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_vega_rna_id", "vega:".$vegaRnaIdentifier).
							parent::describeProperty($this->getVoc()."has_vega_rna_id", "Relationship between a gene and a vega RNA identifier")
						);
					}
					//protein accession
					if($proteinAccession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_protein_accession", "refseq:".$proteinAccession).
							parent::describeProperty($this->getVoc()."has_protein_accession", "Relationship between a gene and a protein accession")
						);
					}
					//vega protein
					if($vegaProteinId != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_vega_protein_id", "vega:".$vegaProteinId).
							parent::describeProperty($this->getVoc()."has_vega_protein_id", "Relationship between a gene and a vega protein identifier")
						);
					}
				}
				parent::writeRDFBufferToWriteFile();
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2sts(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 2){
				$aGeneId = trim($splitLine[0]);
				$uniStsId = trim($splitLine[1]);
				$this->AddRDF(
					parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_unists_id", "unists:".$uniStsId).
					parent::describeProperty($this->getVoc()."has_unists_id", "Relationship between a gene and a UniSTS identifier")
				);
			}//if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2unigene(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 2){
					$aGeneId = trim($splitLine[0]);
					$unigene_cluster = trim($splitLine[1]);
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_unigene_cluster", "unigene:".$unigene_cluster).
						parent::describeProperty($this->getVoc()."has_unigene_cluster_id", "Relationship between a gene and a UniGene cluster identifier")
					);
				}//if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2pubmed(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 3){
					$taxid = trim($splitLine[0]);
					$aGeneId = trim($splitLine[1]);
					$pubmedId = trim($splitLine[2]);
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_taxid", "taxon:".$taxid).
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."publication", "pubmed:".$pubmedId).
						parent::describeProperty($this->getVoc()."has_pubmed_id", "Relationship between an NCBI entity and a publication")
					);
				}//if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2refseq(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 13){
					$taxid = trim($splitLine[0]);
					$aGeneId = trim($splitLine[1]);
					$status = trim($splitLine[2]);
					$rnaNucleotideAccession = trim($splitLine[3]);
					$rnaNucleotideGi = trim($splitLine[4]);
					$proteinAccession = trim($splitLine[5]);
					$proteinGi = trim($splitLine[6]);
					$genomicNucleotideAccession = trim($splitLine[7]);
					$genomicNucleotideGi = trim($splitLine[8]);
					$startPositionOnGenomicAccession = trim($splitLine[9]);
					$endPositionOnGenomicAccession = trim($splitLine[10]);
					$orientation = trim($splitLine[11]);
					$assembly = trim($splitLine[12]);

					//taxid
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_taxid", "taxon:".$taxid)
					);
					//status
					$this->AddRDF(
						parent::triplifyString($this->getNamespace().$aGeneId, $this->getVoc()."has_status", $status).
						parent::describeProperty($this->getVoc()."has_status", "Relationship between a gene and its Entrez Gene status")
					);
					//RNA nucleotide accession
					if($rnaNucleotideAccession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_rna_nucleotide_accession", "refseq:".$rnaNucleotideAccession).
							parent::describeProperty($this->getVoc()."has_rna_nucleotide_accession", "Relationship between a gene and its RNA nucleotide accession")
						);
					}
					//RNA nucleotide gi
					if($rnaNucleotideGi != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_rna_nucleotide_gi", "refseq:".$rnaNucleotideGi).
							parent::describeProperty($this->getVoc()."has_rna_nucleotide_gi", "Relationship between a gene and a RefSeq RNA nucleotide GI")
						);
					}
					//protein accession
					if($proteinAccession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_protein_accession", "refseq:".$proteinAccession)
						);
					}
					//protein gi
					if($proteinGi != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_protein_gi", "refseq:".$proteinGi).
							parent::describeProperty($this->getVoc()."has_protein_gi", "Relationship between a gene and a RefSeq protein GI")
						);
					}				
					// genomic nucleotide accession
					if($genomicNucleotideAccession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_genomic_nucleotide_accession", "refseq:".$genomicNucleotideAccession).
							parent::describeProperty($this->getVoc()."has_genomic_nucleotide_accession", "Relationship between a gene and a RefSeq genomic nucleotide accession")
						);
					}
					//genomic nucleotide gi
					if($genomicNucleotideGi != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_genomic_nucleotide_gi", "gi:".$genomicNucleotideGi).
							parent::describeProperty($this->getVoc()."has_genomic_nucleotide_gi", "Relationship between a gene and a genomic nucleotide GI")
						);
					}
					//start position on the genomic accession
					if(($startPositionOnGenomicAccession != "-") && ($genomicNucleotideAcession != "-")){
						$this->AddRDF(
							parent::triplifyString("refseq:".$genomicNucleotideAccession, $this->getVoc()."has_start_position", $startPositionOnGenomicAccession).
							parent::describeProperty($this->getVoc()."has_start_position", "Relationship between a nucleotide identifier and a start position")
						);
					}
					//end position on the genomic accession
					if(($endPositionOnGenomicAccession != "-") && ($genomicNucleotideAcession != "-")){
						$this->AddRDF(
							parent::triplifyString("refseq:".$genomicNucleotideAccession, $this->getVoc()."has_end_position", $endPositionOnGenomicAccession).
							parent::describeProperty($this->getVoc()."has_end_position", "Relationship between a nucleotide identifier and an end position")
						);
					}
					//orientation
					if($orientation != "?"){
						$this->AddRDF(
							parent::triplifyString($this->getNamespace().$aGeneId, $this->getVoc()."has_orientation", $orientation).
							parent::describeProperty($this->getVoc()."has_orientation", "Relationship between a gene and an orientation")
						);
					}
					//assembly
					if($assembly != "-"){
						$this->AddRDF(
							parent::triplifyString($this->getNamespace().$aGeneId, $this->getVoc()."has_assembly", $assembly).
							parent::describeProperty($this->getVoc()."has_assembly", "Relationship between a gene and an assembly")
						);
					}
				} //if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2ensembl(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 7){
					$taxid = trim($splitLine[0]);
					$aGeneId = trim($splitLine[1]);
					$ensemblGeneIdentifier = trim($splitLine[2]);
					$rnaNucleotideAccession = trim($splitLine[3]);
					$ensemblRnaIdentifier = trim($splitLine[4]);
					$proteinAccession = trim($splitLine[5]);
					$ensemblProteinIdentifier = trim($splitLine[6]);
					//taxid
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_taxid", "taxon:".$taxid)
					);
					//ensembl_gene_identifier
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_ensembl_gene_identifier", "ensembl:".$ensemblGeneIdentifier).
						parent::describeProperty($this->getVoc()."has_ensembl_gene_identifier", "Relationship between a gene and an Ensembl gene identifier")
					);
					//ensemblRnaIdentifier
					if($rnaNucleotideAccession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_ensembl_rna_identifier", "ensembl:".$ensemblRnaIdentifier).
							parent::describeProperty($this->getVoc()."has_ensembl_rna_identifier", "Relationship between a gene and an Ensembl RNA identifier")
						);
					}
					//proteinAccession
					if($proteinAccession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_protein_accession", "genbank:".$proteinAccession)
						);
					}
					//ensemblProtein identifier
					if($ensemblProteinIdentifier != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_ensembl_protein_identifier", "ensembl:".$ensemblProteinIdentifier).
							parent::describeProperty($this->getVoc()."has_ensembl_protein_identifier", "Relationship between a gene and an Ensembl protein identifier")
						);
					}
				} //if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2accession(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 13){
					$taxid =  trim($splitLine[0]);
					$aGeneId = trim($splitLine[1]);
					$status = trim($splitLine[2]);
					$rnaNucleotideAccession = trim($splitLine[3]);
					$rnaNucleotideGi = trim($splitLine[4]);
					$proteinAccession = trim($splitLine[5]);
					$proteinGi = trim($splitLine[6]);
					$genomicNucleotideAccession = trim($splitLine[7]);
					$genomicNucleotideGi = trim($splitLine[8]);
					$startPositionOnGenomicAccession = trim($splitLine[9]);
					$endPositionOnGenomicAccession = trim($splitLine[10]);
					$orientation = trim($splitLine[11]);
					$assembly = trim($splitLine[12]);
					//taxid
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_taxid", "taxon:".$taxid)
					);
					//status
					if($status != "-"){
						$this->AddRDF(
							parent::triplifyString($this->getNamespace().$aGeneId, $this->getVoc()."has_status", $status)
						);
					}
					//rna nucleotide accession version
					if($rnaNucleotideAccession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_genbank_rna_nucleotide_accession", "genbank:".$rnaNucleotideAccession).
							parent::describeProperty($this->getVoc()."has_genbank_rna_nucleotide_accession", "Relationship between a gene and a GenBank RNA nucleotide accession")
						);
					}
					//rna nucleotide gi
					if($rnaNucleotideGi != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_rna_gi",
							"gi:".$rnaNucleotideGi));
					}
					//protein accession
					if($proteinAccession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_protein_accession", "genbank:".$proteinAccession)
						);
					}
					//protein gi
					if($proteinGi != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_protein_gi", "gi:".$proteinGi)
						);
					}
					//genomic nucleotide accession
					if($genomicNucleotideAcession != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_genomic_nucleotide_accession", "refseq:".$genomicNucleotideAccession)
						);
					}
					//genomic nucleotide gi
					if($genomicNucleotideGi != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_genomic_nucleotide_gi", "gi:".$genomicNucleotideGi)
						);
					}
					//start position on the genomic accession
					if(($startPositionOnGenomicAccession != "-")&&($genomicNucleotideAcession != "-")){
						$this->AddRDF(
							parent::triplifyString("refseq:".$genomicNucleotideAccession, $this->getVoc()."has_start_position", $startPositionOnGenomicAccession)
						);
					}
					//end position on the genomic accession
					if(($endPositionOnGenomicAccession != "-")&&($genomicNucleotideAcession != "-")){
						$this->AddRDF(
							parent::triplifyString("refseq:".$genomicNucleotideAccession, $this->getVoc()."has_end_position", $endPositionOnGenomicAccession)
						);
					}
					//orientation
					if($orientation != "?"){
						$this->AddRDF(
							parent::triplifyString($this->getNamespace().$aGeneId, $this->getVoc()."has_orientation", $orientation)
						);
					}
					//assembly
					if($assembly != "-"){
						$this->AddRDF(
							parent::triplifyString($this->getNamespace().$aGeneId, $this->getVoc()."has_assembly", $assembly)
						);
					}
				} //if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2go(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$id = 1;
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 8){
					$taxid = "taxon:".trim($splitLine[0]);
					$aGeneId = trim($splitLine[1]);
					$goid = strtolower(trim($splitLine[2]));
					$evidenceCode = trim($splitLine[3]);
					$qualifier = trim($splitLine[4]);
					$golabel = trim($splitLine[5]);
					$pmids = explode("|", $splitLine[6]);
					$goCategory = strtolower(trim($splitLine[7]));
					
					// $this->AddRDF($this->QQuad($geneid,"geneid_vocabulary:has_taxid",$taxid));
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc().$goCategory, "go:".$goid).
						parent::describeProperty($this->getVoc().$goCategory, "Relationship between a gene and a GO $goCategory")
					);

					$i = substr($goid,3);

					//evidence
					if($evidenceCode != "-"){
						// create an evidence object
						$eid = $this->getRes().$aGeneId."_".$i;
						$this->AddRDF(
							parent::describeIndividual($eid, $this->getNamespace().$aGeneId."-$goid association", $this->getVoc()."Gene-$goCategory-Association").
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."has_gene-".$goCategory."_association", $eid).
							parent::triplify($eid, $this->getVoc()."evidence", "eco:$evidenceCode").
							parent::triplify($eid, $this->getVoc()."gene", $this->getNamespace().$aGeneId).
							parent::triplifyString($eid, $this->getVoc()."go_category", $goCategory).
							parent::triplify($eid, $this->getVoc()."go_term", "go:".$goid).
							parent::describeProperty($this->getVoc()."has_gene-".$goCategory."_association", "Relationship between a gene and a gene-$goCategory-association")

						);
						foreach ($pmids as $pmid){
							if($pmid != '-'){
								$this->AddRDF(
									parent::triplify($eid, $this->getVoc()."publication", "pubmed:".$pmid)
								);
							}
						}
					} 
				} //if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	
	
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function geneinfo(){
		$header = $this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
			$a = $splitLine = explode("\t", $aLine);
			if(count($splitLine) == 15){
				$taxid = "taxon:".trim($splitLine[0]);
				$aGeneId = trim($splitLine[1]);
				$geneid = "ncbigene:".trim($splitLine[1]);
				$symbol = addslashes(stripslashes(trim($splitLine[2])));
				$symbolid =  "symbol:$symbol";
				$locusTag = trim($splitLine[3]);
				$symbols_arr = explode("|",$splitLine[4]);
				$dbxrefs_arr = explode("|",$splitLine[5]);
				$chromosome = trim($splitLine[6]);
				$map_location = trim($splitLine[7]);
				$description = addslashes(stripslashes(trim($splitLine[8])));
				$type_of_gene = trim($splitLine[9]);
				$symbol_authority = addslashes(stripslashes(trim($splitLine[10])));
				$symbol_auth_full_name = addslashes(stripslashes(trim($splitLine[11])));
				$nomenclature_status = addslashes(stripslashes(trim($splitLine[12])));
				$other_designations = addslashes(stripslashes(trim($splitLine[13])));
				$mod_date = date_parse(trim($splitLine[14]));
				//check for a valid symbol
				if($symbol != "NEWENTRY"){

					$this->AddRDF(
						parent::describeIndividual($geneid, "$description ($symbolid, $taxid)", $this->getVoc()."Gene").
						parent::triplify($geneid, $this->getVoc()."has_taxid", $taxid).
						parent::triplifyString($geneid, $this->getVoc()."has_symbol", $symbol).
						parent::triplifyString($geneid, $this->getVoc()."has_locus_tag", addslashes(stripslashes($locusTag))).
						parent::describeClass($this->getVoc()."Gene", "An Entrez Gene gene").
						parent::describeProperty($this->getVoc()."has_locus_tag", "Relationship between a gene and a locus tag")
					);

			
					if($type_of_gene != '-') {
						$this->AddRDF(
							parent::triplify($geneid, "rdf:type", $this->getVoc().$type_of_gene."-gene").
							parent::describeClass($this->getVoc().$type_of_gene."-gene", "An Entrez Gene ".$type_of_gene."-gene")
						);
					} 
					
					//symbol synonyms
					foreach($symbols_arr as $s){
						if($s != "-"){
							$this->AddRDF(
								parent::triplifyString($geneid, $this->getVoc()."has_symbol_synonym", addslashes(stripslashes($s))).
								parent::describeProperty($this->getVoc()."has_symbol_synonym", "Relationship between a gene and a gene symbol synonym")
							);
						}
					}				
					//dbxrefs
					foreach($dbxrefs_arr as $dbx){
						if($dbx != "-"){
							$this->AddRDF(
								parent::triplifyString($geneid, $this->getVoc()."has_dbxref", $dbx).
								parent::describeProperty($this->getVoc()."has_dbxref", "Relationship between a gene and a database cross reference")
							);
						}
					}
					//chromosome
					if($chromosome != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."has_chromosome", $chromosome).
							parent::describeProperty($this->getVoc()."has_chromosome", "Relationship between a gene and a chromosome")
						);
					}
					//map location
					if($map_location != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."has_map_location", $map_location).
							parent::describeProperty($this->getVoc()."has_map_location", "Relationship between a gene and a map location")
						);
					}
					//description
					if($description != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, "dc:description", $description)
						);
					}
					//nomenclature authority
					if($symbol_authority != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."has_nomenclature_authority", $symbol_authority).
							parent::describeProperty($this->getVoc()."has_nomenclature_authority", "Relationship between a gene and its nomenclature authority")
						);

						if($symbol_auth_full_name != "-"){
							$this->AddRDF(
								parent::triplifyString($geneid, $this->getVoc()."has_nomenclature_authority_fullname", $symbol_auth_full_name).
								parent::describeProperty($this->getVoc()."has_nomenclature_authority_fullname", "Relationship between a gene and its nomenclature authority full name")
							);
						}
					}
					//nomenclature status
					if($nomenclature_status != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."has_nomenclature_status", $nomenclature_status).
							parent::describeProperty($this->getVoc()."has_nomenclature_status", "Relationship between a gene and its nomenclature status")
						);
					}
					//other designations
					if($other_designations != "-"){
						foreach(explode("|",$other_designations) AS $d) {
							$this->AddRDF(
								parent::triplifyString($geneid, $this->getVoc()."other_designation", $d).
								parent::describeProperty($this->getVoc()."other_designation", "Relationship between a gene an another designation")
							);
						}
					}				
					//modification date
					if($mod_date != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."modification_date", $mod_date["month"]."-".$mod_date["day"]."-".$mod_date["year"]).
							parent::describeProperty($this->getVoc()."modification_date", "Relationship between a gene and its date of modification")
						);
					}
				}
			}
			parent::writeRDFBufferToWriteFile();
		} // while
	}
	
	public function getPackageMap(){
		return self::$packageMap;
	}	
}

$start = microtime(true);

set_error_handler('error_handler');
$parser = new EntrezGeneParser($argv);
$parser-> Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"
?>
