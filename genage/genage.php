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

class GenageParser extends Bio2RDFizer {
	function __construct($argv) {
		parent::__construct($argv, "genage");
		parent::addParameter('files', true, 'all|human|models','all','files to process');
		parent::addParameter('download_url', false, null,'http://genomics.senescence.info/genes/');
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
			"human" => "human_genes.zip",
			"models" => "models_genes.zip",
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
			"human" => "human_genes.zip",
			"models" => "models_genes.zip",
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

			$suffix = parent::getParameterValue('output_format');
			$ofile = "genage_".$file.'.'.$suffix; 

			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}

			$zin = new ZipArchive();
			if ($zin->open($lfile) === FALSE) {
				trigger_error("Unable to open $lfile");
				exit;
			}

			if($file == "human"){
				$zipentry = "genage_human.csv";
			} else if($file == "models"){
				$zipentry = "genage_models.csv";
			}
			
			if(($fp = $zin->getStream($zipentry)) === FALSE) {
				trigger_error("Unable to get $zipentry in ziparchive $lfile");
				return FALSE;
			}

			parent::SetReadFile($lfile);
			parent::GetReadFile()->SetFilePointer($fp);

			// set the write file, parse, write and close
			$suffix = parent::getParameterValue('output_format');
			$outfile = "genage_".$file.'.'.$suffix; 
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
				->setTitle("Human Ageing Genomic Resources GenAge database (".$remote_files[$file].")")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
				->setFormat("text/comma-separated-value")
				->setFormat("application/gzip")	
				->setPublisher("http://genomics.senescence.info/")
				->setHomepage("http://genomics.senescence.info/genes/")
				->setRights("use")
				->setLicense("http://genomics.senescence.info/legal.html")
				->setDataset("http://identifiers.org/genage/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/genage/genage.php")
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

	function human(){

		$inclusion_criteria = array(
			"human_link" => array("HumanDirectLink", "Evidence directly linking the gene product to ageing in humans"),
			"human" => array("HumanDirectLink", "Evidence directly linking the gene product to ageing in humans"),
			"mammal" => array("MammalianModelDirectLink", "Evidence directly linking the gene product to ageing in a mammalian model organism"),
			"model" => array("NonMammalianModelDirectLink", "Evidence directly linking the gene product to ageing in a non-mammalian model organism"),
			"cell" => array("CellularModelDirectLink", "Evidence directly linking the gene product to ageing in a cellular model system"),
			"upstream" => array("GeneticRegulationDirectLink", "Evidence directly linking the gene product to the regulation or control of genes previously linked to ageing"),
			"functional" => array("FunctionalLink", "Evidence linking the gene product to a pathway or mechanism linked to ageing"),
			"downstream" => array("DownstreamLink", "Evidence showing the gene product to act downstream of a pathway, mechanism, or other gene product linked to ageing"),
			"putative" => array("PutativeLink", "Indirect or inconclusive evidence linking the gene product to ageing")
		);

		foreach($inclusion_criteria as $type => $description){
			parent::addRDF(
				parent::describeClass(parent::getVoc().$description[0], parent::getVoc().$description[1], parent::getVoc()."InclusionCriteria")
			);
		}

		$h = explode(",", parent::getReadFile()->read());
		$expected_columns = 16;
		if(($n = count($h)) != $expected_columns) {
			trigger_error("Found $n columns in gene file - expecting $expected_columns!", E_USER_WARNING);
			return false;			
		}

		while($l = parent::getReadFile()->read(200000)) {
			$data = str_getcsv($l);
			$hagr = str_pad($data[0], 4, "0", STR_PAD_LEFT);
			$aliases = $data[1];
			$hgnc_symbol = $data[2];
			$common_name = $data[3];
			$ncbi_gene_id = $data[4];
			$reasons = $data[5];
			$band = $data[6];
			$location_start = $data[7];
			$location_end = $data[8];
			$orientation = $data[9];
			$unigene_id = $data[10];
			$swissprot = $data[11];
			$acc_promoter = $data[12];
			$acc_orf = $data[13];
			$acc_cds = $data[14];
			$references = $data[15];
		//	$ppis = $data[16];
		//	$notes = $data[17];

			$hagr_id = "hagr:".$hagr;
			parent::addRDF(
				parent::describeIndividual($hagr_id, $data[3], parent::getVoc()."Human-Aging-Related-Gene").
				parent::describeClass(parent::getVoc()."Human-Aging-Related-Gene","Human Aging Related Gene")
			);
			
			if($aliases !== ""){
				$split_aliases = explode(" ", $aliases);
				foreach ($split_aliases as $alias){
					parent::addRDF(
						parent::triplifyString($hagr_id, parent::getVoc()."alias", parent::safeLiteral($alias))
					);
				}
			}
			
			parent::addRDF(
				parent::triplifyString($hagr_id, parent::getVoc()."hgnc-symbol", parent::safeLiteral($hgnc_symbol))
			);

			parent::addRDF(
				parent::triplify($hagr_id, parent::getVoc()."x-ncbigene", "ncbigene:".$ncbi_gene_id)
			);

			if($reasons !== ""){
				$reasons_split = explode(",", $reasons);
				foreach($reasons_split as $reason){

					parent::addRDF(
						parent::triplify($hagr_id, parent::getVoc()."inclusion-criteria", parent::getVoc().$inclusion_criteria[$reason][0])
					);
				}
			}
			
			if($band !== ""){
				parent::addRDF(
					parent::triplifyString($hagr_id, parent::getVoc()."cytogenetic-band", parent::safeLiteral($band))
				);
			}
			if($location_start !== ""){
				parent::addRDF(
					parent::triplifyString($hagr_id, parent::getVoc()."gene-start-position", $location_start)
				);
			}

			if($location_end !== ""){
				parent::addRDF(
					parent::triplifyString($hagr_id, parent::getVoc()."gene-end-position", $location_end)
				);
			}

			if($orientation !== ""){
				parent::addRDF(
					parent::triplifyString($hagr_id, parent::getVoc()."strand-orientation", parent::safeLiteral($orientation))
				);
			}

			if($unigene_id !== ""){
				parent::addRDF(
					parent::triplify($hagr_id, parent::getVoc()."x-unigene", "unigene:".$unigene_id)
				);
			}

			if($swissprot !== ""){
				if(strstr($swissprot, "_")){
					parent::addRDF(
						parent::triplifyString($hagr_id, parent::getVoc()."uniprot-old-mnemonic", parent::safeLiteral($swissprot))
					);
				} else {
					parent::addRDF(
						parent::triplify($hagr_id, parent::getVoc()."x-uniprot", "uniprot:".$swissprot)
					);
				}
			}

			if($acc_promoter !== ""){
				parent::addRDF(
					parent::triplifyString($hagr_id, parent::getVoc()."promoter-accession", parent::safeLiteral($acc_promoter))
				);
			}

			if($acc_cds !== ""){
				parent::addRDF(
					parent::triplifyString($hagr_id, parent::getVoc()."cds-accession", parent::safeLiteral($acc_cds))
				);
			}

			if($acc_orf !== ""){
				parent::addRDF(
					parent::triplifyString($hagr_id, parent::getVoc()."orf-accession", parent::safeLiteral($acc_orf))
				);
			}

			if($references !== ""){
				$split_refs = explode(",", $references);
				foreach($split_refs as $ref){
					parent::addRDF(
						parent::triplify($hagr_id, parent::getVoc()."article", "pubmed:".$ref)
					);
				}
			}
/*			if($ppis !== ""){
				$split_ppis = explode(",", $ppis);
				foreach($split_ppis as $ppi){
					$proteins = explode(";", $ppi);
					$other_hagr_id = "hagr:".$proteins[0];
					if($hagr == $proteins[0]){
						$other_hagr_id = "hagr:".$proteins[1];
					}
					parent::addRDF(
						parent::triplify($hagr_id, parent::getVoc()."interacts-with", $other_hagr_id)
					);
				}
			}
*/
			parent::WriteRDFBufferToWriteFile();
		}

	}

	function models(){
		$tax_ids = array(
			"Caenorhabditis elegans" => "6239", 
			"Mus musculus" => "10090",
			"Saccharomyces cerevisiae" => "4932",
			"Drosophila melanogaster" => "7227",
			"Podospora anserina" => "5145",
			"Mesocricetus auratus" => "10036",
			"Schizosaccharomyces pombe" => "4896",
			"Danio rerio" => "7955",
		);

		$h = explode(",", parent::getReadFile()->read());
		$expected_columns = 10;
		if(($n = count($h)) != $expected_columns) {
			trigger_error("Found $n columns in gene file - expecting $expected_columns!", E_USER_WARNING);
			return false;			
		}

		while($l = parent::getReadFile()->read(200000)) {
			$data = str_getcsv($l);
			
			$genage = str_pad($data[0], 4, "0", STR_PAD_LEFT);
			$name = $data[1];
			$gene_symbol = $data[2];
			$organism = $data[3];
			$function = $data[4];
			$ncbi_gene_id = $data[5];
//			$ensembl_id = $data[6];
//			$uniprot_id = $data[7];
//			$unigene_id = $data[8];
			$max_percent_obsv_avg_lifespan_change = $data[6];
			$lifespan_effect = $data[7];
			$longevity_influence = $data[8];
			$observations = $data[9];

			$genage_id = parent::getNamespace().$genage;


			parent::addRDF(
				parent::describeIndividual($genage_id, $name, parent::getVoc()."Aging-Related-Gene").
				parent::describeClass(parent::getVoc()."Aging-Related-Gene","Aging Related Gene")
			);

			parent::addRDF(
				parent::triplifyString($genage_id, parent::getVoc()."gene-symbol", parent::safeLiteral($gene_symbol))
			);

			parent::addRDF(
				parent::triplify($genage_id, parent::getVoc()."taxon", "ncbitaxon:".$tax_ids[$organism])
			);

			if($function !== ""){
				parent::addRDF(
					parent::triplifyString($genage_id, parent::getVoc()."function", parent::safeLiteral($function))
				);
			}

			if($ncbi_gene_id !== ""){
				parent::addRDF(
					parent::triplify($genage_id, parent::getVoc()."x-ncbigene", "ncbigene:".$ncbi_gene_id)
				);
			}
/*

			if($ensembl_id !== ""){
				parent::addRDF(
					parent::triplify($genage_id, parent::getVoc()."x-ensembl", "ensembl:".$ensembl_id)
				);
			}
			if($uniprot_id !== ""){
				if(strstr($uniprot_id, "_")){
					parent::addRDF(
						parent::triplifyString($genage_id, parent::getVoc()."uniprot-entry", parent::safeLiteral($uniprot_id))
					);
				} else {
					parent::addRDF(
						parent::triplify($genage_id, parent::getVoc()."x-uniprot", "uniprot:".$uniprot_id)
					);
				}
			}

			if($unigene_id !== ""){
				parent::addRDF(
					parent::triplify($genage_id, parent::getVoc()."x-unigene", "unigene:".$unigene_id)
				);
			}
*/
			if($max_percent_obsv_avg_lifespan_change !== ""){
				parent::addRDF(
					parent::triplifyString($genage_id, parent::getVoc()."maximum-percent-observed-average-lifespan-change", parent::safeLiteral($max_percent_obsv_avg_lifespan_change))
				);
			}

			if($lifespan_effect == "Increase and Decrease"){
				parent::addRDF(
					parent::triplifyString($genage_id, parent::getVoc()."lifespan-effect", "increase").
					parent::triplifyString($genage_id, parent::getVoc()."lifespan-effect", "decrease")
				);
			} else {
				parent::addRDF(
					parent::triplifyString($genage_id, parent::getVoc()."lifespan-effect", strtolower($lifespan_effect))
				);
			}

			parent::addRDF(
				parent::triplifyString($genage_id, parent::getVoc()."longevity-influence", strtolower($longevity_influence))
			);

			parent::WriteRDFBufferToWriteFile();
		}
	}

}
?>
