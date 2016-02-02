<?php
/**
Copyright (C) 2013 Alison Callahan, Jose Cruz-Toledo, Michel Dumontier

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
 * @version 3.0
 * @author Alison Callahan
 * @author Jose Cruz-Toledo
 * @author MIchel Dumontier
*/

class IProClassParser extends Bio2RDFizer
{
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

		$lfile = $ldir.$file;
		if(!file_exists($lfile)) {
			trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
			parent::setParameterValue('download',true);
		}

		//download all files 
		$rfile = $rdir.$file;
		if($this->GetParameterValue('download') == true) {
			echo "downloading $file... ";
			utils::DownloadSingle($rfile,$lfile);
//			$cmd = "gzip -c $lfile | split -d -l 1000000 --filter='gzip > $FILE.gz' - iproclass-"
		}
		$ofile = "iproclass.nq";
		$gz = true;

		parent::setReadFile($lfile, true);
		echo "processing $file... ";
		$this->process();
		echo "done!".PHP_EOL;
		parent::getWriteFile()->close();

		echo "generating dataset release file... ";
		$source_file = (new DataResource($this))
                                ->setURI($rfile)
                                ->setTitle("iProClass")
                                ->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
                                ->setFormat("text/tab-separated-value")
                                ->setFormat("application/gzip")
                                ->setPublisher("http://pir.georgetown.edu")
                                ->setHomepage("http://pir.georgetown.edu/iproclass")
                                ->setRights("use-share-modify")
                                ->setLicense("http://pir.georgetown.edu/pirwww/about/linkpir.shtml")
                                ->setDataset("http://identifiers.org/iproclass/");

                        $prefix = parent::getPrefix();
                        $bVersion = parent::getParameterValue('bio2rdf_release');
                        $date = date ("Y-m-d\TG:i:s\Z");
                        $output_file = (new DataResource($this))
                                ->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
                                ->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
                                ->setSource($source_file->getURI())
                                ->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/iproclass/iproclass.php")
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
		$dataset_description = $source_file->toRDF().$output_file->toRDF();
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();

		echo "done!".PHP_EOL;

	}//Run

	private function process()
	{
		$z = 0;$y = 1;
		while($l = $this->getReadFile()->Read(200000)) {
			if($z++ % 1000000 == 0) {
				echo $z.PHP_EOL;
				$odir = parent::getParameterValue('outdir');
				$ofile = 'iproclass.'.($y++).".".parent::getParameterValue('output_format'); 
				$gz = (strstr(parent::getParameterValue('output_format'), "gz"))?true:false;

				if(parent::getWriteFile() != null) {
					parent::getWriteFile()->close();
					parent::clear();
				}
				// generate a new file
				parent::setWriteFile($odir.$ofile, $gz);
			}

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

			$id = $uniprot_acc;
			$id_res = $this->getNamespace().$id;
			$id_label = "iproclass entry for uniprot:$uniprot_acc";
			parent::addRDF(parent::triplify($id_res, $this->getVoc()."x-uniprot", "uniprot:".$uniprot_acc));

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
						parent::QQuadO_URL($id_res, "rdfs:seeAlso", "http://uniprot.org/uniref/".$uniref_100_id)
					);
				}
			}

			if(!empty($uniref_90)){
				$uniref_90_ids = explode("; ", $uniref_90);
				foreach ($uniref_90_ids as $uniref_90_id) {
					parent::AddRDF(
						parent::QQuadO_URL($id_res, "rdfs:seeAlso", "http://uniprot.org/uniref/".$uniref_90_id)
					);
				}
			}

			if(!empty($uniref_50)){
				$uniref_50_ids = explode("; ", $uniref_50);
				foreach ($uniref_50_ids as $uniref_50_id) {
					parent::AddRDF(
						parent::QQuadO_URL($id_res, "rdfs:seeAlso", "http://uniprot.org/uniref/".$uniref_50_id)
					);
				}
			}

			if(!empty($uniparc)){
				$uniparc_ids = explode("; ", $uniparc);
				foreach ($uniparc_ids as $uniparc_id) {
					parent::AddRDF(
						parent::triplify($id_res, $this->getVoc()."x-uniparc", "uniparc:".$uniparc_id).
						parent::QQuadO_URL($id_res, "rdfs:seeAlso", "http://uniprot.org/uniparc/".$uniparc_id)
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

?>
