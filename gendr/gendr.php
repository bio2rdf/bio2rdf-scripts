<?php
/*
Copyright (C) 2013 Alison Callahan

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

class GendrParser extends Bio2RDFizer {
	function __construct($argv) {
		parent::__construct($argv, "gendr");
		parent::addParameter('files', true, 'all|gene_manipulations|gene_expression','all','files to process');
		parent::addParameter('download_url', false, null,'http://genomics.senescence.info/diet/');
		parent::initialize();
	}//constructor

	public function run(){

		if(parent::getParameterValue('download') === true) 
		{
			$this->download();
		}
		if(parent::getParameterValue('process') === true) 
		{
			$this->process();
		}
	}

	function download(){
		// get the file list
		if(parent::getParameterValue('files') == 'all') {
			$files = explode("|",parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",parent::getParameterValue('files'));
		}

		$remote_files = array(
			"gene_manipulations" => "dataset.zip",
			"gene_expression" => "TableS2.xls",
		);

		$ldir = parent::getParameterValue('indir');
		$rdir = parent::getParameterValue('download_url');

		foreach($files as $file){
			$rfile = $rdir.$remote_files[$file];
			$lfile = $ldir.$remote_files[$file];
			echo "Downloading ".$rfile."... ";
			Utils::DownloadSingle($rfile, $lfile);
			echo "done!".PHP_EOL;
		}
	}
	function process(){
		if(parent::getParameterValue('files') == 'all') {
			$files = explode("|",parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",parent::getParameterValue('files'));
		}

		$remote_files = array(
			"gene_manipulations" => "dataset.zip",
			"gene_expression" => "TableS2.xls"
		);

		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$rdir = parent::getParameterValue('download_url');

		$dataset_description = '';

		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		foreach($files as $file){
			$lfile = $ldir.$remote_files[$file];
			$rfile = $rdir.$remote_files[$file];

			if(!file_exists($lfile)) {
				trigger_error($lfile." not found. Will attempt to download.".PHP_EOL, E_USER_WARNING);
				echo "Downloading $rfile... ";
				Utils::DownloadSingle($rfile, $lfile);
				echo "done!".PHP_EOL;
			}

			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}

			$zin = new ZipArchive();
			if($file == "gene_manipulations"){
				if ($zin->open($lfile) === FALSE) {
					trigger_error("Unable to open $lfile");
					exit;
				}
				$zipentry = "gendr_manipulations.csv";
				if(($fp = $zin->getStream($zipentry)) === FALSE) {
					trigger_error("Unable to get $zipentry in ziparchive $lfile");
					return FALSE;
				}
				parent::SetReadFile($lfile);
				parent::GetReadFile()->SetFilePointer($fp);
			} else if($file == "gene_expression"){
				$lfile = $ldir."gendr_genes_expression.csv";
				parent::SetReadFile($lfile);
			}

			// set the write file, parse, write and close
			$suffix = parent::getParameterValue('output_format');
			$ofile = "gendr_".$file.'.'.$suffix; 
			$gz=false;

			if(strstr($suffix, "gz")) {
				$gz = true;
			}

			parent::setWriteFile($odir.$ofile, $gz);

			echo "Processing $lfile... ";
			$fnx = $file;
			$this-> $fnx();
			echo "done!".PHP_EOL;

			parent::getWriteFile()->close();

			// generate the dataset release file
			echo "Generating dataset description for $ofile... ";
			// dataset description
			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("Human Ageing Genomic Resources GenDR database (".$remote_files[$file].")")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
				->setFormat("text/comma-separated-value")
				->setFormat("application/gzip")	
				->setPublisher("http://genomics.senescence.info/")
				->setHomepage("http://genomics.senescence.info/diet/")
				->setRights("use")
				->setLicense("http://genomics.senescence.info/legal.html")
				->setDataset("http://identifiers.org/gendr/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/gendr/gendr.php")
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
			echo "done!".PHP_EOL;
		}
		parent::setGraphURI($graph_uri);
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
	}

	function gene_manipulations(){
		$h = explode(",", parent::getReadFile()->read());
		$expected_columns = 6;
		if(($n = count($h)) != $expected_columns) {
			trigger_error("Found $n columns in gene file - expecting $expected_columns!", E_USER_WARNING);
			return false;			
		}

		while($l = parent::getReadFile()->read(200000)) {
			$data = str_getcsv($l);
			$gendr = $data[0];
			$gene_symbol = $data[1];
			$species_name = $data[2];
			$geneid = $data[3];
			$gene_name = $data[4];
			$references = $data[5];

			$gendr_id = parent::getNamespace().$gendr;
			$gendr_label = $gene_name." (".$gene_symbol.")";

			$association_id = parent::getRes().md5($gendr.$geneid."_association");
			$association_label = "Association between ".$gene_symbol." and variation in life span extension induced by dietary restriction";

			parent::addRDF(
				parent::describeIndividual($gendr_id, $gendr_label, parent::getVoc()."DietaryRestrictionLifeExtensionRelatedGene").
				parent::triplify($gendr_id, parent::getVoc()."x-ncbigene", "ncbigene:".$geneid).
				parent::triplifyString($gendr_id, parent::getVoc()."gene-name", $gene_name).
				parent::triplifyString($gendr_id, parent::getVoc()."gene-symbol", $gene_symbol).
				parent::describeIndividual($association_id, $association_label, parent::getVoc()."Gene-Phenotype-Association").
				parent::triplify($association_id, parent::getVoc()."gene", $gendr_id).
				parent::triplify($association_id, parent::getVoc()."phenotype", parent::getVoc()."Diet-Induced-Life-Span-Variant")
			);

			if($species_name == "Caenorhabditis elegans"){
				parent::addRDF(
					parent::triplify($association_id, parent::getVoc()."phenotype", "wormbase:WBPhenotype:0001837").
					parent::triplify($association_id, parent::getVoc()."taxon", "taxon:6239")
				);
			} else if($species_name == "Saccharomyces cerevisiae"){
				parent::addRDF(
					parent::triplify($association_id, parent::getVoc()."taxon", "taxon:4932")
				);
			} else if($species_name == "Schizosaccharomyces pombe"){
				parent::addRDF(
					parent::triplify($association_id, parent::getVoc()."taxon", "taxon:9896")
				);
			} else if($species_name == "Drosophila melanogaster"){
				parent::addRDF(
					parent::triplify($association_id, parent::getVoc()."taxon", "taxon:7227")
				);
			} else if($species_name == "Mus musculus"){
				parent::addRDF(
					parent::triplify($association_id, parent::getVoc()."taxon", "taxon:10090")
				);
			} 

			if(!empty($references)){
				$split_refs = explode(",", $references);
				foreach($split_refs as $ref){
					parent::addRDF(
						parent::triplify($gendr_id, parent::getVoc()."article", "pmid:".$ref).
						parent::triplify($association_id, parent::getVoc()."article", "pmid:".$ref)
					);
				}
			}
			parent::writeRDFBufferToWriteFile();
		}//while		
	}

	function gene_expression(){
		$h = explode(",", parent::getReadFile()->read());
		$expected_columns = 8;
		if(($n = count($h)) != $expected_columns) {
			trigger_error("Found $n columns in gene file - expecting $expected_columns!", E_USER_WARNING);
			return false;			
		}

		while($l = parent::getReadFile()->read(200000)) {
			$data = str_getcsv($l);
			$mgi_symbol = $data[0];
			$mgi_description = $data[1];
			$geneid = $data[2];
			$total_datasets = $data[3];
			$total_ovexp = $data[4];
			$total_underexp = $data[5];
			$p_value = $data[6];
			$expression = $data[7];

			$id = parent::getRes().md5($gene_id.$total_datasets.$total_ovexp.$total_underexp.$p_value.$expression);
			$evidence_id = parent::getRes().md5($gene_id.$total_datasets.$total_ovexp.$total_underexp.$p_value.$expression."_evidence");
			$label = "Dietary restriction induced ".$expression."-expression of ".$mgi_symbol." based on microarray results from ".$total_datasets." datasets, with p-value ".$p_value;

			parent::addRDF(
				parent::describeIndividual($id, $label, parent::getVoc()."Gene-".ucfirst($expression)."-Expression").
				parent::triplify($id, parent::getVoc()."gene", "ncbigene:".$geneid).
				parent::triplifyString("ncbigene:".$geneid, parent::getVoc()."mgi-gene-symbol", $mgi_symbol).
				parent::triplifyString("ncbigene:".$geneid, parent::getVoc()."mgi-gene-description", $mgi_description).
				parent::triplify($id, parent::getVoc()."evidence", $evidence_id).
				parent::triplifyString($id, parent::getVoc()."perturbation-context", "dietary restriction").
				parent::triplifyString($evidence_id, parent::getVoc()."total-number-datasets", $total_datasets).
				parent::triplifyString($evidence_id, parent::getVoc()."total-number-datasets-overexpressed", $total_ovexp).
				parent::triplifyString($evidence_id, parent::getVoc()."total-number-datasets-underexpressed", $total_underexp).
				parent::triplifyString($evidence_id, parent::getVoc()."p-value", $p_value)
			);

			parent::writeRDFBufferToWriteFile();
		}//while
	}
}

?>