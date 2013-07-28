<?php
/**
Copyright (C) 2013 Alison Callahan and Jose Cruz-Toledo

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
 * @version 2.0
 * @author Alison Callahan
 * @author Jose Cruz-Toledo
*/

require(__DIR__.'/../../php-lib/bio2rdfapi.php');

class IProClassParser extends Bio2RDFizer{

	private $version = 2.0;

	public function __construct($argv) {
		parent::__construct($argv, "iproclass");
		parent::addParameter('files',true,'all','all','files to process');
		parent::addParameter('download',false,'true|false','false','set true to download files');
		parent::addParameter('download_url', false, null,'ftp://ftp.pir.georgetown.edu/databases/iproclass/');
		parent::initialize();
	}

	public function Run(){
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
			trigger_error($file." not found. Will attempt to download.", E_USER_NOTICE);
			parent::setParameterValue('download',true);
		}
		
		//download all files 
		if($this->GetParameterValue('download') == true) {
			$rfile = $rdir.$file;
			echo "downloading $file... ";
			file_put_contents($lfile,file_get_contents($rfile));
		}

		$ofile = $odir.'iproclass.nt'; 
		$gz = false;
		if(strstr(parent::getParameterValue('output_format'), "gz")) {
			$ofile .= '.gz';
			$gz = true;
		}
		parent::setReadFile($lfile);
		parent::setWriteFile($ofile, $gz);
		echo "processing $file... ";
		$this->process();
		echo "done!".PHP_EOL;
		//close write file
		parent::getWriteFile()->close();
		
		echo "generating dataset release file... ";
		$desc = parent::getBio2RDFDatasetDescription(
			$this->getPrefix(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/iproclass/iproclass.php", 
			$this->getBio2RDFDownloadURL($this->getNamespace()),
			"http://pir.georgetown.edu/iproclass",
			array("restricted-by-source-license"),
			"http://pir.georgetown.edu/pirwww/about/linkpir.shtml",
			parent::getParameterValue('download_url'),
			parent::getDatasetVersion()
		);
		parent::setWriteFile($odir.$this->getBio2RDFReleaseFile($this->GetNamespace()));
		parent::getWriteFile()->write($desc);
		parent::getWriteFile()->close();

		echo "done!".PHP_EOL;

	}//Run

	private function process(){
		while($l = $this->GetReadFile()->Read(200000)) {
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
			$id_res = $this->getNamespace().$id;
			$id_label = "uniprot accession";

			if(!empty($uniprot)){
				$uniprot_ids = explode("; ", $uniprot);
				foreach($uniprot_ids as $uniprot_id){
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-uniprot", "uniprot:".$uniprot_id)
					);
				}
			}
			
			if(!empty($gene)){
				$gene_ids = explode("; ", $gene);
				foreach($gene_ids as $gene_id){
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-ncbigene", "geneid:".$gene_id)
					);
				}	
			}
			
			if(!empty($refseq)){
				$refseq_ids = explode("; ", $refseq);
				foreach ($refseq_ids as $refseq_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-refseq", "refseq:".$refseq_id)
					);
				}	
			}			

			if(!empty($gi)){
				$gi_ids = explode("; ", $gi);
				foreach ($gi_ids as $gi_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-gi", "gi:".$gi_id)
					);
				}	
			}
			
			if(!empty($pdb)){
				$pdb_ids = explode("; ", $pdb);
				foreach ($pdb_ids as $pdb_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-pdb", "pdb:".$pdb_id)
					);
				}
			}

			if(!empty($pfam)){
				$pfam_ids = explode("; ", $pfam);
				foreach ($pfam_ids as $pfam_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-pfam", "pfam:".$pfam_id)
					);
				}
			}

			if(!empty($go)){
				$go_ids = explode("; ", $go);
				foreach ($go_ids as $go_id) {
					$go_id = substr($go_id, 3);
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-go", "go:".$go_id)
					);
				}
			}

			if(!empty($pirsf)){
				$pirsf_ids = explode("; ", $pirsf);
				foreach ($pirsf_ids as $pirsf_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-pirsf", "pirsf:".$pirsf_id)
					);
				}
			}

			if(!empty($ipi)){
				$ipi_ids = explode("; ", $ipi);
				foreach ($ipi_ids as $ipi_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-ipi", "ipi:".$ipi_id)
					);
				}
			}

			if(!empty($uniref_100)){
				$uniref_100_ids = explode("; ", $uniref_100);
				foreach ($uniref_100_ids as $uniref_100_id) {
					parent::AddRDF(
						parent::QQuaadO_URL($id_res, "rdfs:seeAlso", "http://uniprot.org/uniref/".$uniref_100_id)
					);
				}
			}

			if(!empty($uniref_90)){
				$uniref_90_ids = explode("; ", $uniref_90);
				foreach ($uniref_90_ids as $uniref_90_id) {
					parent::AddRDF(
						parent::QQuaadO_URL($id_res, "rdfs:seeAlso", "http://uniprot.org/uniref/".$uniref_90_id)
					);
				}
			}

			if(!empty($uniref_50)){
				$uniref_50_ids = explode("; ", $uniref_50);
				foreach ($uniref_50_ids as $uniref_50_id) {
					parent::AddRDF(
						parent::QQuaadO_URL($id_res, "rdfs:seeAlso", "http://uniprot.org/uniref/".$uniref_50_id)
					);
				}
			}

			if(!empty($uniparc)){
				$uniparc_ids = explode("; ", $uniparc);
				foreach ($uniparc_ids as $uniparc_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-uniparc", "uniparc:".$uniparc_id).
						parent::QQuaadO_URL($id_res, "rdfs:seeAlso", "http://uniprot.org/uniparc/".$uniparc_id)
					);
				}
			}

			if(!empty($ncbi_taxonomy)){
				$taxonomy_ids = explode("; ", $ncbi_taxonomy);
				foreach ($taxonomy_ids as $taxonomy_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-taxon", "taxon:".$taxonomy_id)
					);
				}
			}

			if(!empty($mim)){
				$mim_ids = explode("; ", $mim);
				foreach($mim_ids as $mim_id){
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-omim", "omim:".$mim_id)
					);
				}
			}

			if(!empty($unigene)){
				$unigene_ids = explode("; ", $unigene);
				foreach ($unigene_ids as $unigene_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-unigene", "unigene:".$unigene_id)
					);
				}
			}

			if(!empty($ensembl)){
				$ensembl_ids = explode("; ", $ensembl);
				foreach ($ensembl_ids as $ensembl_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-ensembl", "ensembl:".$ensembl_id)
					);
				}
			}

			if(!empty($pubmed)){
				$pubmed_ids = explode("; ", $pubmed);
				foreach ($pubmed_ids as $pubmed_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-pubmed", "pubmed:".$pubmed_id)
					);
				}
			}

			if(!empty($embl_genbank_ddbj)){
				$genbank_ids = explode("; ", $embl_genbank_ddbj);
				foreach ($genbank_ids as $genbank_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-genbank", "genbank:".$genbank_id)
					);
				}
			}

			if(!empty($embl_protein)){
				$embl_protein_ids = explode(";", $embl_protein);
				foreach ($embl_protein_ids as $embl_protein_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-genbank", "genbank:".$embl_protein_id)
					);
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
