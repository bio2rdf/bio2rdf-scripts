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
			"gene_group" => "gene_group.gz",
			"gene2vega" => "gene2vega.gz",
			"gene_info" => "gene_info.gz",
			"gene_refseq_uniprotkb_collab" => "gene_refseq_uniprotkb_collab.gz",
			"go_process" => "go_process.xml"			
		);
		private  $bio2rdf_base = "http://bio2rdf.org/";
		private  $gene_vocab ="entrezgene_vocabulary:";
		private  $gene_resource = "entrezgene_resource:";
		private  $geneid = "http://bio2rdf.org/gene:";
	
		
		function __construct($argv) {
			parent::__construct();
			// set and print application parameters
			$this->AddParameter('files',true,'all|gene_info_all|gene2accession|gene2ensembl|gene2go|gene2pubmed|gene2refseq|gene2sts|gene2unigene|gene2vega|gene_group|gene_refseq_uniprotkb_collab|go_process','','files to process');
			$this->AddParameter('indir',false,null,'/tmp/download/gene/','directory to download into and parse from');
			$this->AddParameter('outdir',false,null,'/tmp/rdf/gene/','directory to place rdfized files');
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

		 //what files are to be converted?
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
			$fp = gzopen($ldir.$aFile, "r") or die("Could not open file ".$aFile."!\n");
			//make the output file
			$gzoutfile = $odir.$k.".ttl";
			$gz=false;
			if($this->GetParameterValue('gzip')){
				$outfile .= '.gz';
				$gz = true;
			}
			$this->SetWriteFile($gzoutfile, $gz);
			$this->SetReadFile($
			//first check if the file is there
			if(!file_exists($gzoutfile)){
				if (($gzout = gzopen($gzoutfile,"a"))=== FALSE) {
					trigger_error("Unable to open $odir.$gzoutfile");
					exit;
				}
				$this->$k($fp, $gzout);
				gzclose($fp);
				gzclose($gzout);
				echo "done processing $aFile!\n";
			}else{
				echo "file $gzoutfile already there!\nPlease remove file and try again\n";
				exit;
			}
		}		
	}//run
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2go($aFp,$outputPointer){
		while(!gzeof($aFp)){
			$aLine = gzgets($aFp, 4096);
			preg_match("/^#.*/", $aLine, $matches);
			if(count($matches)){
				continue;
			}
			$splitLine = explode("\t",$aLine);
			$taxid = $splitLine[0];
			$aGeneId = $splitLine[1];
			$goid = $splitline[2];
			$evidenceCode = $splitLine[3];
			$qualifier = $splitLine[4];
			$golabel = $splitLine[5];
			$pmid_arr = explode("|", $splitline[6]);
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
			gzwrite($ouputPointer, $this->GetRDF());
			$this->DeleteRDF();			
		}//while
	}
	
	
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene_info_all($aFp, $ouputPointer){
		while(!gzeof($aFp)){
			$aLine = gzgets($aFp, 4096);
			preg_match("/^#.*/", $aLine, $matches);
			if(count($matches)){
				continue;
			}
			$splitLine = explode("\t", $aLine);
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
				gzwrite($ouputPointer, $this->GetRDF());
				$this->DeleteRDF();
			}			
		}
	}
	
	public function getPackageMap(){
		return self::$packageMap;
	}	
}



$parser = new EntrezGeneParser($argv);
$parser-> Run();

?>
