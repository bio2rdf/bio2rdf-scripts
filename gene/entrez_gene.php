<?php
/**
Copyright (C) 2012 Jose Cruz-Toledo

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
 * @version 0.2
 * @author Jose Cruz-Toledo
 * @contributor Michel Dumontier
 * @description ftp://ftp.ncbi.nih.gov/gene/DATA/
*/
require('../../php-lib/rdfapi.php');

class EntrezGeneParser extends RDFFactory{

		private $ns = null;
		private $named_entries = array();
		
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
			parent::__construct();
			// set and print application parameters
			$this->AddParameter('files',true,'all|geneinfo|gene2accession|gene2ensembl|gene2go|gene2pubmed|gene2refseq|gene2sts|gene2unigene|gene2vega','','files to process');
			$this->AddParameter('indir',false,null,'/data/download/gene/','directory to download into and parse from');
			$this->AddParameter('outdir',false,null,'/data/rdf/gene/','directory to place rdfized files');
			$this->AddParameter('gzip',false,'true|false','true','gzip the output');
			$this->AddParameter('download',false,'true|false','false','set true to download files');
			$this->AddParameter('download_url',false,null,'ftp://ftp.ncbi.nih.gov/gene/DATA/');
			if($this->SetParameters($argv) == FALSE) {
				$this->PrintParameters($argv);
				exit;
			}
			if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
			if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
			$this->SetReleaseFileURI("gene");
		return TRUE;
	  }//constructor
	  
	 function Run(){
		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rdir = $this->GetParameterValue('download_url');

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
			echo "Processing $id ...";	

			$lfile = $ldir.$id.".gz";

			// download
			if(!file_exists($lfile) || $this->GetParameterValue('download') == true) { 
				echo "downloading ... ";
				
				//don't use subdirectory GENE_INFO for saving local version of All_data.gene_info.gz
				if($id == "gene2sts" || $id == "gene2unigene") {
					$rfile = "compress.zlib://".$rdir.$file;
				} else {
					$rfile = $rdir.$file;
				}
				file_put_contents($lfile,file_get_contents($rfile));
			}
			
			$writefile = $odir.$id.".ttl";
			$gz=false;
			if($this->GetParameterValue('gzip')){
				$writefile .= '.gz';
				$gz = true;
			}
			$this->SetReadFile($lfile, true);
			$this->SetWriteFile($writefile, $gz);
			echo 'parsing ...';
			$this->$id();
			echo 'done.'.PHP_EOL;
			$this->GetReadFile()->Close();
			$this->GetWriteFile()->Close();
		}//foreach
		return TRUE;
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
					$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_taxid",
							"taxon:".$taxid));
					//vega gene identifier
					$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_vega_gene",
							"vega:".$vegaGeneId));
					//rna nucleotide accession
					if($rnaNucleotideAccession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_rna_nucleotide_accession",
							"refseq:".$rnaNucleotideAccession));
					}
					//vega rna id
					if($vegaRnaIdentifier != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_vega_rna_id",
							"vega:".$vegaRnaIdentifier));
					}
					//protein accession
					if($proteinAccession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_protein_accession",
							"refseq:".$proteinAccession));
					}
					//vega protein
					if($vegaProteinId != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_vega_protein_id",
							"vega:".$vegaProteinId));
					}
				}
				$this->WriteRDFBufferToWriteFile();	
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
				$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_unists_id",
							"unists:".$uniStsId));
			}//if
			$this->WriteRDFBufferToWriteFile();	
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
					$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_unigene_cluster",
							"unigene:".$unigene_cluster));
				}//if
				$this->WriteRDFBufferToWriteFile();	
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
					//taxid
					$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_taxid",
							"taxon:".$taxid));
					//taxid
					$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_pubmed_id",
							"pubmed:".$pubmedId));
				}//if
			$this->WriteRDFBufferToWriteFile();	
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
					$genomicNucleotideAcession = trim($splitLine[7]);
					$genomicNucleotideGi = trim($splitLine[8]);
					$startPositionOnGenomicAccession = trim($splitLine[9]);
					$endPositionOnGenomicAccession = trim($splitLine[10]);
					$orientation = trim($splitLine[11]);
					$assembly = trim($splitLine[12]);
					//taxid
					$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_taxid",
							"taxon:".$taxid));
					//status
					$this->AddRDF($this->QQuadL("geneid:".$aGeneId,
							"geneid_vocabulary:has_status",
							$status));
					//RNA nucleotide accession
					if($rnaNucleotideAccession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_rna_nucleotide_accession",
							"refseq:".$rnaNucleotideAccession));
					}
					//RNA nucleotide gi
					if($rnaNucleotideGi != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_rna_nucleotide_gi",
							"refseq:".$rnaNucleotideGi));
					}
					//protein accession
					if($proteinAccession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_protein_accession",
							"refseq:".$proteinAccession));
					}
					//protein gi
					if($proteinGi != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_protein_accession",
							"refseq:".$proteinGi));
					}				
					// genomic nucleotide accession
					if($genomicNucleotideAcession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_genomic_nucleotide_accession",
							"refseq:".$genomicNucleotideAcession));
					}
					//genomic nucleotide gi
					if($genomicNucleotideGi != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_genomic_nucleotide_gi",
							"gi:".$genomicNucleotideGi));
					}
					//start position on the genomic accession
					if(($startPositionOnGenomicAccession != "-") && ($genomicNucleotideAcession != "-")){
						$this->AddRDF($this->QQuadL("refseq:".$genomicNucleotideAcession,
							"geneid_vocabulary:has_start_position",
							$startPositionOnGenomicAccession));
					}
					//end position on the genomic accession
					if(($endPositionOnGenomicAccession != "-") && ($genomicNucleotideAcession != "-")){
						$this->AddRDF($this->QQuadL("refseq:".$genomicNucleotideAcession,
							"geneid_vocabulary:has_end_position",
							$endPositionOnGenomicAccession));
					}
					//orientation
					if($orientation != "?"){
						$this->AddRDF($this->QQuadL("geneid:".$aGeneId,
							"geneid_vocabulary:has_orientation",
							$orientation));
					}
					//assembly
					if($assembly != "-"){
						$this->AddRDF($this->QQuadL("geneid:".$aGeneId,
							"geneid_vocabulary:has_assembly",
							$assembly));
					}
				} //if
			$this->WriteRDFBufferToWriteFile();		
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
					$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_taxid",
							"taxon:".$taxid));
					//ensembl_gene_identifier
					$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_ensembl_gene_identifier",
							"ensembl:".$ensemblGeneIdentifier));
					//ensemblRnaIdentifier
					if($rnaNucleotideAccession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_rna_ensemble_identifier",
							"ensembl:".$ensemblRnaIdentifier));
					}
					//proteinAccession
					if($proteinAccession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_protein_accession",
							"genbank:".$proteinAccession));
					}
					//ensemblProtein identifier
					if($ensemblProteinIdentifier != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_ensembl_protein_identifier",
							"ensembl:".$ensemblProteinIdentifier));
					}
				} //if
			$this->WriteRDFBufferToWriteFile();		
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
					$genomicNucleotideAcession = trim($splitLine[7]);
					$genomicNucleotideGi = trim($splitLine[8]);
					$startPositionOnGenomicAccession = trim($splitLine[9]);
					$endPositionOnGenomicAccession = trim($splitLine[10]);
					$orientation = trim($splitLine[11]);
					$assembly = trim($splitLine[12]);
					//taxid
					$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_taxid",
							"taxon:".$taxid));
					//status
					if($status != "-"){
						$this->AddRDF($this->QQuadL("geneid:".$aGeneId,
							"geneid_vocabulary:has_status",
							$status));
					}
					//rna nucleotide accession version
					if($rnaNucleotideAccession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_rna_nucleotide_genbank_accession",
							"genbank:".$rnaNucleotideAccession));
					}
					//rna nucleotide gi
					if($rnaNucleotideGi != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_rna_gi",
							"gi:".$rnaNucleotideGi));
					}
					//protein accession
					if($proteinAccession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_protein_accession",
							"genbank:".$proteinAccession));
					}
					//protein gi
					if($proteinGi != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_protein_gi",
							"gi:".$proteinGi));
					}
					//genomic nucleotide accession
					if($genomicNucleotideAcession != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_genomic_nucleotide_accession",
							"refseq:".$genomicNucleotideAcession));
					}
					//genomic nucleotide gi
					if($genomicNucleotideGi != "-"){
						$this->AddRDF($this->QQuad("geneid:".$aGeneId,
							"geneid_vocabulary:has_genomic_nucleotide_gi",
							"gi:".$genomicNucleotideGi));
					}
					//start position on the genomic accession
					if(($startPositionOnGenomicAccession != "-")&&($genomicNucleotideAcession != "-")){
						$this->AddRDF($this->QQuadL("refseq:".$genomicNucleotideAcession,
							"geneid_vocabulary:has_start_position",
							$startPositionOnGenomicAccession));
					}
					//end position on the genomic accession
					if(($endPositionOnGenomicAccession != "-")&&($genomicNucleotideAcession != "-")){
						$this->AddRDF($this->QQuadL("refseq:".$genomicNucleotideAcession,
							"geneid_vocabulary:has_end_position",
							$endPositionOnGenomicAccession));
					}
					//orientation
					if($orientation != "?"){
						$this->AddRDF($this->QQuadL("geneid:".$aGeneId,
							"geneid_vocabulary:has_orientation",
							$orientation));
					}
					//assembly
					if($assembly != "-"){
						$this->AddRDF($this->QQuadL("geneid:".$aGeneId,
							"geneid_vocabulary:has_assembly_name",
							$assembly));
					}
				} //if
			$this->WriteRDFBufferToWriteFile();		
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
					$goCategory = trim($splitLine[7]);
					
					$geneid = "geneid:$aGeneId";
					// $this->AddRDF($this->QQuad($geneid,"geneid_vocabulary:has_taxid",$taxid));
					$this->AddRDF($this->QQuad($geneid,"geneid_vocabulary:".strtolower($goCategory),$goid));

					//evidence
					if($evidenceCode != "-"){
						// create an evidence object
						$eid = "geneid_resource:".$aGeneId."_".($id++);
						$this->AddRDF($this->QQuad($geneid,"geneid_vocabulary:gene-go-association",$eid));

						$this->AddRDF($this->QQuadL($eid,"rdfs:label", "$geneid-$goid association [$eid]"));
						$this->AddRDF($this->QQuad($eid,"rdf:type", "geneid_vocabulary:Gene-GO-Association"));
						$this->AddRDF($this->QQuad($eid,"geneid_vocabulary:evidence","eco:$evidenceCode"));

						foreach ($pmids as $pmid){
							if($pmid != '-') $this->AddRDF($this->QQuad($eid,"geneid_vocabulary:publication","pubmed:$pmid"));
						}
					} 
				} //if
				$this->WriteRDFBufferToWriteFile();
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
				$geneid = "geneid:".trim($splitLine[1]);
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
					$this->AddRDF($this->QQuadL($geneid, "rdfs:label", "$description ($symbolid,$taxid) [$geneid]"));
					$this->AddRDF($this->QQuad($geneid, "rdf:type", "geneid:vocabulary:Gene"));
					if($type_of_gene != '-') {
						$this->AddRDF($this->QQuad($geneid, "rdf:type", "geneid:vocabulary:".$type_of_gene."-gene"));
					} 
					//taxid
					$this->AddRDF($this->QQuad($geneid, "geneid_vocabulary:has_taxid", $taxid ));
					//symbol - these are not official hgnc symbols as they contain spaces
					$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:has_symbol", $symbol));
					//locustag
					$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:has_locus_tag", addslashes(stripslashes($locusTag))));
					//symbol synonyms
					foreach($symbols_arr as $s){
						if($s != "-"){
							$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:has_symbol_synonym", addslashes(stripslashes($s))));	
						}
					}				
					//dbxrefs
					foreach($dbxrefs_arr as $dbx){
						if($dbx != "-"){
//							$this->ParsePrefixedName($dbx,$ns,$id);
							$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:has_dbxref", $dbx));
						}
					}
					//chromosome
					if($chromosome != "-"){
						$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:has_chromosome", $chromosome));
					}
					//map location
					if($map_location != "-"){
						$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:has_map_location", $map_location));
					}
					//description
					if($description != "-"){
						$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:has_description", $description));
					}
					//nomenclature authority
					if($symbol_authority != "-"){
						$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:has_nomenclature_authority", $symbol_authority));

						if($symbol_auth_full_name != "-"){
							$this->AddRDF($this->QQuadL($geneid, 
								"geneid_vocabulary:has_nomenclature_authority_fullname", 
								$symbol_auth_full_name));
						}
					}
					//nomenclature status
					if($nomenclature_status != "-"){
						$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
								"geneid_vocabulary:has_nomenclature_status", 
								$nomenclature_status));
					}
					//other designations
					if($other_designations != "-"){
						foreach(explode("|",$other_designations) AS $d) {
							$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:other_designation", $d));
						}
					}				
					//modification date
					if($mod_date != "-"){
						$this->AddRDF($this->QQuadL($geneid, "geneid_vocabulary:modification_date", 
							$mod_date["month"]."-".$mod_date["day"]."-".$mod_date["year"]));
					}
				}
			}
			$this->WriteRDFBufferToWriteFile();
		} // while
	}
	
	public function getPackageMap(){
		return self::$packageMap;
	}	
}

set_error_handler('error_handler');
$parser = new EntrezGeneParser($argv);
$parser-> Run();

?>
