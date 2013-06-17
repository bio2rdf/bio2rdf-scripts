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
 * An RDF generator for iProClass (http://pir.georgetown.edu/iproclass/)
 * @version 1.0
 * @author Alison Callahan
*/

require('../../php-lib/rdfapi.php');

class IProClassParser extends RDFFactory{

	private $version = null;

	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("iproclass");
		
		// set and print application parameters
		$this->AddParameter('indir',false,null,'/data/download/iproclass/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/iproclass/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'ftp://ftp.pir.georgetown.edu/databases/iproclass/');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
		
		return TRUE;
	}

	function Run(){

		$file = "iproclass.tb.gz";

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
			$rfile = $rdir.$file;
			echo "downloading $file... ";
			file_put_contents($lfile,file_get_contents($rfile));
		}

		$ofile = $odir.'iproclass.nt'; 
		$gz = false;
		if($this->GetParameterValue('graph_uri')){$ofile = $odir.'iproclass.nq'; }
		if($this->GetParameterValue('gzip')) {
			$ofile .= '.gz';
			$gz = true;
		}
		
		$this->SetReadFile($lfile, true);
		$this->SetWriteFile($ofile, $gz);

		echo "processing $file... ";
		$this->process();

		//close write file
		$this->GetWriteFile()->Close();
		
		echo "generating dataset release file... ";
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/iproclass/iproclass.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()),
			"http://pir.georgetown.edu/iproclass/",
			array("restricted-by-source-license"),
			"http://pir.georgetown.edu/pirwww/about/linkpir.shtml",
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		

		echo "done!".PHP_EOL;

	}//Run

	function process(){

		while($l = $this->GetReadFile()->Read(4096)) {
			$fields = explode("\t", $l);
			@$uniprot_acc = $fields[0];
			@$uniprot = $fields[1];
			@$gene = $fields[2];
			@$refseq= $fields[3];
			@$gi = $fields[4];
			@$pdb = $fields[5];
			@$pfam = $fields[6];
			@$go = $fields[7];
			@$pirsf = $fields[8];
			@$ipi = $fields[9];
			@$uniref_100 = $fields[10];
			@$uniref_90 = $fields[11];
			@$uniref_50 = $fields[12];
			@$uniparc = $fields[13];
			//skipping pir-psd because db no longer maintained
			@$ncbi_taxonomy = $fields[15];
			@$mim = $fields[16];
			@$unigene = $fields[17];
			@$ensembl = $fields[18];
			@$pubmed = $fields[19];
			@$embl_genbank_ddbj = $fields[20];
			@$embl_protein = trim($fields[21]);

			$id = "uniprot:".$uniprot_acc;

			if(!empty($uniprot)){
				$uniprot_ids = explode("; ", $uniprot);
				foreach($uniprot_ids as $uniprot_id){
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-uniprot", "uniprot:".$uniprot_id));
				}
			}
			
			if(!empty($gene)){
				$gene_ids = explode("; ", $gene);
				foreach($gene_ids as $gene_id){
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-geneid", "geneid:".$gene_id));
				}	
			}
			
			if(!empty($refseq)){
				$refseq_ids = explode("; ", $refseq);
				foreach ($refseq_ids as $refseq_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-refseq", "refseq:".$refseq_id));
				}	
			}			

			if(!empty($gi)){
				$gi_ids = explode("; ", $gi);
				foreach ($gi_ids as $gi_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-gi", "gi:".$gi_id));
				}	
			}
			
			if(!empty($pdb)){
				$pdb_ids = explode("; ", $pdb);
				foreach ($pdb_ids as $pdb_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-pdb", "pdb:".$pdb_id));
				}
			}

			if(!empty($pfam)){
				$pfam_ids = explode("; ", $pfam);
				foreach ($pfam_ids as $pfam_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-pfam", "pfam:".$pfam_id));
				}
			}

			if(!empty($go)){
				$go_ids = explode("; ", $go);
				foreach ($go_ids as $go_id) {
					$go_id = substr($go_id, 3);
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-go", "go:".$go_id));
				}
			}

			if(!empty($pirsf)){
				$pirsf_ids = explode("; ", $pirsf);
				foreach ($pirsf_ids as $pirsf_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-pirsf", "pirsf:".$pirsf_id));
				}
			}

			if(!empty($ipi)){
				$ipi_ids = explode("; ", $ipi);
				foreach ($ipi_ids as $ipi_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-ipi", "ipi:".$ipi_id));
				}
			}

			if(!empty($uniref_100)){
				$uniref_100_ids = explode("; ", $uniref_100);
				foreach ($uniref_100_ids as $uniref_100_id) {
					$this->AddRDF($this->QQuadO_URL($id, "rdfs:seeAlso", "http://uniprot.org/uniref/".$uniref_100_id));
				}
			}

			if(!empty($uniref_90)){
				$uniref_90_ids = explode("; ", $uniref_90);
				foreach ($uniref_90_ids as $uniref_90_id) {
					$this->AddRDF($this->QQuadO_URL($id, "rdfs:seeAlso", "http://uniprot.org/uniref/".$uniref_90_id));
				}
			}

			if(!empty($uniref_50)){
				$uniref_50_ids = explode("; ", $uniref_50);
				foreach ($uniref_50_ids as $uniref_50_id) {
					$this->AddRDF($this->QQuadO_URL($id, "rdfs:seeAlso", "http://uniprot.org/uniref/".$uniref_50_id));
				}
			}

			if(!empty($uniparc)){
				$uniparc_ids = explode("; ", $uniparc);
				foreach ($uniparc_ids as $uniparc_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-uniparc", "uniparc:".$uniparc_id));
					$this->AddRDF($this->QQuadO_URL($id, "rdfs:seeAlso", "http://uniprot.org/uniparc/".$uniparc_id));
				}
			}

			if(!empty($ncbi_taxonomy)){
				$taxonomy_ids = explode("; ", $ncbi_taxonomy);
				foreach ($taxonomy_ids as $taxonomy_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-taxon", "taxon:".$taxonomy_id));
				}
			}

			if(!empty($mim)){
				$mim_ids = explode("; ", $mim);
				foreach($mim_ids as $mim_id){
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-omim", "omim:".$mim_id));
				}
			}

			if(!empty($unigene)){
				$unigene_ids = explode("; ", $unigene);
				foreach ($unigene_ids as $unigene_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-unigene", "unigene:".$unigene_id));
				}
			}

			if(!empty($ensembl)){
				$ensembl_ids = explode("; ", $ensembl);
				foreach ($ensembl_ids as $ensembl_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-ensembl", "ensembl:".$ensembl_id));
				}
			}

			if(!empty($pubmed)){
				$pubmed_ids = explode("; ", $pubmed);
				foreach ($pubmed_ids as $pubmed_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-pubmed", "pubmed:".$pubmed_id));
				}
			}

			if(!empty($embl_genbank_ddbj)){
				$genbank_ids = explode("; ", $embl_genbank_ddbj);
				foreach ($genbank_ids as $genbank_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-genbank", "genbank:".$genbank_id));
				}
			}

			if(!empty($embl_protein)){
				$embl_protein_ids = explode(";", $embl_protein);
				foreach ($embl_protein_ids as $embl_protein_id) {
					$this->AddRDF($this->QQuad($id, "iproclass_vocabulary:x-genbank", "genbank:".$embl_protein_id));//not an error! these are in fact genbank identifiers
				}
			}

			//write rdf to file
			$this->WriteRDFBufferToWriteFile();

		}//while
	}
}
$start = microtime(true);

set_error_handler('error_handler');
$parser = new IProClassParser($argv);
$parser->Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"
?>
