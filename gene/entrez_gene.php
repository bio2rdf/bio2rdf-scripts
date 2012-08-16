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
 * @version 0.1
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ncbi.nih.gov/gene/DATA/
*/
require('../../php-lib/rdfapi.php');

class EntrezGeneParser extends RDFFactory{

		private $ns = null;
		private $named_entries = array();
		
		private static $packageMap = array(
			"gene_info_all" => "GENE_INFO/All_Data.gene_info.gz",
			"gene2accession" => "gene2accession.gz",
			"gene2ensembl" => "gene2ensembl.gz",
			"gene2go" => "gene2go.gz",
			"gene2pubmed" => "gene2pubmed.gz",
			"gene2refseq" => "gene2refseq.gz",
			"gene2sts" => "gene2sts",
			"gene2unigene" => "gene2unigene",
			"gene2vega" => "gene2vega.gz",					
		);
		private  $bio2rdf_base = "http://bio2rdf.org/";
		private  $gene_vocab ="entrezgene_vocabulary:";
		private  $gene_resource = "entrezgene_resource:";	
		
		function __construct($argv) {
			parent::__construct();
			// set and print application parameters
			$this->AddParameter('files',true,'all|gene_info_all|gene2accession|gene2ensembl|gene2go|gene2pubmed|gene2refseq|gene2sts|gene2unigene|gene2vega','','files to process');
			$this->AddParameter('indir',false,null,'/media/twotb/bio2rdf/data/gene/','directory to download into and parse from');
			$this->AddParameter('outdir',false,null,'/media/twotb/bio2rdf/n3/gene/','directory to place rdfized files');
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

		//which files are to be converted?
		$selectedPackage = trim($this->GetParameterValue('files'));		 
		if($selectedPackage == 'all') {
			$files = $this->getPackageMap();
		}else {
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
		foreach ($files as $k => $aFile){
			//create a file pointer
			//inputfilename
			$inputFilename = $ldir.$aFile;
			$fp = gzopen($inputFilename, "r") or die("Could not open file ".$aFile."!\n");
			//make the output file
			$gzoutfile = $odir.$k.".ttl";
			$gz=false;
			if($this->GetParameterValue('gzip')){
				$gzoutfile .= '.gz';
				$gz = true;
			}
			$this->SetReadFile($inputFilename);
			
			$this->GetReadFile()->SetFilePointer($fp);
			$this->SetWriteFile($gzoutfile, $gz);
			
			
			//first check if the file is there
			if(!file_exists($gzoutfile)){
				if (($gzout = gzopen($gzoutfile,"a"))=== FALSE) {
					trigger_error("Unable to open $odir.$gzoutfile");
					exit;
				}
				echo "processing $aFile...";
				
				$this->$k();
				
				echo "\n";
			}else{
				echo "file $gzoutfile already there!\nPlease remove file and try again\n";
				exit;
			}
		}//foreach
		$this->GetWriteFile()->Close();		
		return TRUE;
	}//run
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2vega(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^#.*/", $aLine, $matches);
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 7){
				$taxid = $splitLine[0];
				$aGeneId = $splitLine[1];
				$vegaGeneId = $splitLine[2];
				$rnaNucleotideAccession = $splitLine[3];
				$vegaRnaIdentifier = $splitLine[4];
				$proteinAccession = $splitLine[5];
				$vegaProteinId = $splitLine[6];
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
			}//if
			$this->WriteRDFBufferToWriteFile();	
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2sts(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^#.*/", $aLine, $matches);
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 2){
				$aGeneId = $splitLine[0];
				$uniStsId = $splitLine[1];
				$this->AddRDF($this->QQuad("geneid:".$aGeneId,
						"geneid_vocabulary:has_unists_id",
						"unists:".$uniStsId));
			}//if
			$this->WriteRDFBufferToWriteFile();	
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2unigene(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^#.*/", $aLine, $matches);
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 2){
				$aGeneId = $splitLine[0];
				$unigene_cluster = $splitLine[1];
				$this->AddRDF($this->QQuad("geneid:".$aGeneId,
						"geneid_vocabulary:has_unigene_cluster",
						"unigene:".$unigene_cluster));
			}//if
			$this->WriteRDFBufferToWriteFile();	
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2pubmed(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^#.*/", $aLine, $matches);
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 3){
				$taxid = $splitLine[0];
				$aGeneId = $splitLine[1];
				$pubmedId = $splitLine[2];
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
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^#.*/", $aLine, $matches);
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 13){
				$taxid = $splitLine[0];
				$aGeneId = $splitLine[1];
				$status = $splitLine[2];
				$rnaNucleotideAccession = $splitLine[3];
				$rnaNucleotideGi = $splitLine[4];
				$proteinAccession = $splitLine[5];
				$proteinGi = $splitLine[6];
				$genomicNucleotideAcession = $splitLine[7];
				$genomicNucleotideGi = $splitLine[8];
				$startPositionOnGenomicAccession = $splitLine[9];
				$endPositionOnGenomicAccession = $splitLine[10];
				$orientation = $splitLine[11];
				$assembly = $splitLine[12];
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
			}//if count
			$this->WriteRDFBufferToWriteFile();		
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2ensembl(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^#.*/", $aLine, $matches);
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 7){
				$taxid = $splitLine[0];
				$aGeneId = $splitLine[1];
				$ensemblGeneIdentifier = $splitLine[2];
				$rnaNucleotideAccession = $splitLine[3];
				$ensemblRnaIdentifier = $splitLine[4];
				$proteinAccession = $splitLine[5];
				$ensemblProteinIdentifier = $splitLine[6];
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
			}//if
			$this->WriteRDFBufferToWriteFile();		
		}//while
	}
	
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2accession(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^#.*/", $aLine, $matches);
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 13){
				$taxid =  $splitLine[0];
				$aGeneId = $splitLine[1];
				$status = $splitLine[2];
				$rnaNucleotideAccession = $splitLine[3];
				$rnaNucleotideGi = $splitLine[4];
				$proteinAccession = $splitLine[5];
				$proteinGi = $splitLine[6];
				$genomicNucleotideAcession = $splitLine[7];
				$genomicNucleotideGi = $splitLine[8];
				$startPositionOnGenomicAccession = $splitLine[9];
				$endPositionOnGenomicAccession = $splitLine[10];
				$orientation = $splitLine[11];
				$assembly = $splitLine[12];
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
			}//if	
			$this->WriteRDFBufferToWriteFile();		
		}//while
	}
	
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2go(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^#.*/", $aLine, $matches);
			if(count($matches)){
				continue;
			}
			
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 8){
				$taxid = $splitLine[0];
				$aGeneId = $splitLine[1];
				$goid = $splitLine[2];
				$evidenceCode = $splitLine[3];
				$qualifier = $splitLine[4];
				$golabel = $splitLine[5];
				$pmid_arr = explode("|", $splitLine[6]);
				$goCategory = $splitLine[7];
				
				//taxid
				$this->AddRDF($this->QQuad("geneid:".$aGeneId,
						"geneid_vocabulary:has_taxid",
						"taxon:".$taxid));
				//goid
				$this->AddRDF($this->QQuad("geneid:".$aGeneId,
						"geneid_vocabulary:has_goid",
						"go:".$goid));
				//go label
				if($golabel != "-"){
					$this->AddRDF($this->QQuadL("go:".$goid,
						"rdfs:label",
						$golabel));
				}
				//evidence code
				if($evidenceCode != "-"){
					$this->AddRDF($this->QQuadL("go:".$goid,
						"geneid_vocabulary:has_go_evidence_code",
						$evidenceCode));
				}
				//go category 
				if($goCategory != "-"){
					$this->AddRDF($this->QQuadL("go:".$goid,
						"geneid_vocabulary:has_go_category",
						$goCategory));
				}
				if(count($pmid_arr)){
					foreach ($pmid_arr as $aP){
						$this->AddRDF($this->QQuad("go:".$goid,
						"geneid_vocabulary:has_evidence",	"pubmed:".$aP));
					}
				}
			}
			$this->WriteRDFBufferToWriteFile();
		}//while
	}
	
	
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene_info_all(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			preg_match("/^#.*/", $aLine, $matches);
			if(count($matches)){
				continue;
			}
			$splitLine = explode("\t", $aLine);
			//echo "**\ncount:".count($splitLine)."\t".$aLine."\n";
			if(count($splitLine) == 15){
			
			$taxid = $splitLine[0];
			$aGeneId = $splitLine[1];
			$symbol =  $splitLine[2];
			$locusTag = $splitLine[3];
			$symbols_arr = explode("|",$splitLine[4]);
			$dbxrefs_arr = explode("|",$splitLine[5]);
			$chromosome = $splitLine[6];
			$map_location = $splitLine[7];
			$description = $splitLine[8];
			$type_of_gene = $splitLine[9];
			$symbol_authority = $splitLine[10];
			$symbol_auth_full_name = $splitLine[11];
			$nomenclature_status = $splitLine[12];
			$other_designations = $splitLine[13];
			$mod_date = date_parse($splitLine[14]);
			//check for a valid symbol
			if($symbol != "NEWENTRY"){
				//taxid
				$this->AddRDF($this->QQuad("geneid:".$aGeneId, 
							"geneid_vocabulary:has_taxid", 
							"taxon:".$taxid ));
				//symbol
				$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
							"geneid_vocabulary:has_symbol", 
							$symbol));
				//locustag
				$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
						"geneid_vocabulary:has_locus_tag", 
						$symbol));
				//synonyms
				if(count($symbols_arr)){
					foreach($symbols_arr as $aSymb){
						if($aSymb != "-"){
							$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
									"geneid_vocabulary:has_synonym", 
									$symbol));	
						}
					}	
				}				
				//dbxrefs
				if(count($dbxrefs_arr)){
					foreach($dbxrefs_arr as $dbx){
						if($dbx != "-"){
							$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
									"geneid_vocabulary:has_dbxref", 
									$dbx));
						}
					}
				}
				//chromosome
				if($chromosome != "-"){
					$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
								"geneid_vocabulary:has_chromosome", 
								$chromosome));
				}
				//map location
				if($map_location != "-"){
					$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
								"geneid_vocabulary:has_map_location", 
								$map_location));
				}
				//description
				if($description != "-"){
					$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
								"geneid_vocabulary:has_description", 
								$description));
					$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
								"rdfs:label", 
								$description));
				}
				//gene type
				if($type_of_gene != "-"){
					$this->AddRDF($this->QQuad("geneid:".$aGeneId, 
							"geneid_vocabulary:has_gene_type", 
							"geneid_vocabulary:".$type_of_gene ));
					$this->AddRDF($this->QQuadL("geneid_vocabulary:".$type_of_gene, 
							"rdfs:label", 
							$type_of_gene ));
				}
				//nomenclature authority
				if($symbol_authority != "-"){
					$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
							"geneid_vocabulary:has_nomenclature_authority", 
							$symbol_authority));
					if($symbol_auth_full_name != "-"){
						$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
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
					$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
						"geneid_vocabulary:other_designations", 
						$other_designations));
				}				
				//modification date
				if($mod_date != "-"){
					$this->AddRDF($this->QQuadL("geneid:".$aGeneId, 
								"geneid_vocabulary:modification_date", 
								$mod_date["month"]."-".$mod_date["day"]."-".$mod_date["year"]));
				}
			}
			}
			$this->WriteRDFBufferToWriteFile();
		}//while
	}
	
	public function getPackageMap(){
		return self::$packageMap;
	}	
}



$parser = new EntrezGeneParser($argv);
$parser-> Run();

?>
