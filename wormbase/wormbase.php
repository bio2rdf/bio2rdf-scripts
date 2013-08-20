<?php
/*
Copyright (C) 2013 Alison Callahan, Juan Jose Cifuentes

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
 * WormBase RDFizer
 * @version 2.0
 * @author Juan Jose Cifuentes
 * @author Alison Callahan
 * @description http://www.wormbase.org/about/userguide
*/

class WormbaseParser extends Bio2RDFizer {

	function __construct($argv) {
		parent::__construct($argv, "wormbase");
		parent::addParameter('files', true, 'all|geneIDs|functional_descriptions|gene_associations|gene_interactions|phenotype_associations','all','files to process');
		parent::addParameter('release', true, null, 'WS235', 'Release version of WormBase');
		parent::addParameter('download_url', false, null,'ftp://ftp.wormbase.org/pub/wormbase/');
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
			"geneIDs" => "species/c_elegans/annotation/geneIDs/c_elegans.".parent::getParameterValue('release').".geneIDs.txt.gz",
			"functional_descriptions" => "species/c_elegans/annotation/functional_descriptions/c_elegans.".parent::getParameterValue('release').".functional_descriptions.txt.gz",
			"gene_associations" => "releases/".parent::getParameterValue('release')."/ONTOLOGY/gene_association.".parent::getParameterValue('release').".wb.ce",
			"gene_interactions" => "species/c_elegans/annotation/gene_interactions/c_elegans.".parent::getParameterValue('release').".gene_interactions.txt.gz",
			"phenotype_associations" => "releases/".parent::getParameterValue('release')."/ONTOLOGY/phenotype_association.".parent::getParameterValue('release').".wb"
		);

		$local_files = array(
			"geneIDs" => "c_elegans.".parent::getParameterValue('release').".geneIDs.txt.gz",
			"functional_descriptions" => parent::getParameterValue('release').".functional_descriptions.txt.gz",
			"gene_associations" => "gene_association.".parent::getParameterValue('release').".wb.ce",
			"gene_interactions" => "c_elegans.".parent::getParameterValue('release').".gene_interactions.txt.gz",
			"phenotype_associations" => "phenotype_association.".parent::getParameterValue('release').".wb"
		);

		//set directory values
		$ldir = parent::getParameterValue('indir');
		$rdir = parent::getParameterValue('download_url');

		foreach($files as $file){
			$rfile = $rdir.$remote_files[$file];
			$lfile = $ldir.$local_files[$file];
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
			"geneIDs" => "species/c_elegans/annotation/geneIDs/c_elegans.".parent::getParameterValue('release').".geneIDs.txt.gz",
			"functional_descriptions" => "species/c_elegans/annotation/functional_descriptions/c_elegans.".parent::getParameterValue('release').".functional_descriptions.txt.gz",
			"gene_associations" => "releases/".parent::getParameterValue('release')."/ONTOLOGY/gene_association.".parent::getParameterValue('release').".wb.ce",
			"gene_interactions" => "species/c_elegans/annotation/gene_interactions/c_elegans.".parent::getParameterValue('release').".gene_interactions.txt.gz",
			"phenotype_associations" => "releases/".parent::getParameterValue('release')."/ONTOLOGY/phenotype_association.".parent::getParameterValue('release').".wb"
		);

		$local_files = array(
			"geneIDs" => "c_elegans.".parent::getParameterValue('release').".geneIDs.txt.gz",
			"functional_descriptions" => parent::getParameterValue('release').".functional_descriptions.txt.gz",
			"gene_associations" => "gene_association.".parent::getParameterValue('release').".wb.ce",
			"gene_interactions" => "c_elegans.".parent::getParameterValue('release').".gene_interactions.txt.gz",
			"phenotype_associations" => "phenotype_association.".parent::getParameterValue('release').".wb"
		);

		$idir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$rdir = parent::getParameterValue('download_url');

		$dataset_description = '';

		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		foreach($files as $file){
			$lfile = $idir.$local_files[$file];
			$rfile = $rdir.$remote_files[$file];

			if(!file_exists($lfile)) {
				trigger_error($lfile." not found. Will attempt to download.".PHP_EOL, E_USER_WARNING);
				echo "Downloading $rfile... ";
				Utils::DownloadSingle($rfile, $lfile);
				echo "done!".PHP_EOL;
			}

			if(strstr($lfile, "gz")){
				parent::setReadFile($lfile, TRUE);
			} else {
				parent::setReadFile($lfile, FALSE);
			}

			$suffix = parent::getParameterValue('output_format');
			$ofile = "wormbase_celegans_".parent::getParameterValue('release')."_".$file.".".$suffix;

			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}

			parent::setWriteFile($odir.$ofile, $gz);

			echo "Processing $file... ";
			$fnx = $file;
			$this-> $fnx();
			echo "done!".PHP_EOL;

			parent::getWriteFile()->close();

			// generate the dataset release file
			echo "Generating dataset description for $ofile... ";
			// dataset description
			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("WormBase C. elegans Release ".parent::getParameterValue('release')." subset ($file)")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
				->setFormat("text/tab-separated-value")
				->setFormat("application/gzip")	
				->setPublisher("http://wormbase.org/")
				->setHomepage("http://wormbase.org/")
				->setRights("use")
				->setRights("restricted-by-source-license")
				->setLicense("http://www.wormbase.org/about/policies")
				->setDataset("http://identifiers.org/wormbase/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/wormbase/wormbase.php")
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

	function geneIDs(){
		$first = true;
		while($l = $this->GetReadFile()->Read()){
			if($l[0] == '#') continue;
			
			$data = explode(",",trim($l));

			if($first) {
				if(($c = count($data) != 3)) {
					trigger_error("WormBase function expects 3 fields, found $c!".PHP_EOL, E_USER_WARNING);
				}
				$first = false;
			}
			//add the rdf:type

			$id = parent::getNamespace().$data[0];
			$gene_label = "WormBase gene ".$data[1]." with cosmid name ".$data[2];

			parent::addRDF(
				parent::describeIndividual($id, $gene_label, parent::getVoc()."Gene")
			);
			
			//add gene approved name
			if ($data[1] != '') {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."has_approved_gene_name", $data[1])
				);
			}
			#Add cosmid name 
			if ($data[2] != '') {
				$cosmid_id = parent::getRes().$data[2];
				parent::addRDF(
					parent::describeIndividual($cosmid_id, "Gene/cosmid name for ".$data[0], parent::getVoc()."Cosmid_gene").
					parent::triplify($id, parent::getVoc()."has_sequence/cosmid_name", $cosmid_id)
				);
			}				
			parent::WriteRDFBufferToWriteFile();
		}//while
	}# Funcion Gene_IDs
			
	function functional_descriptions(){
		
		$start = '/(^WBGene[0-9]+)\s/';
		$end = '/^=\n/';
		$current_description = '';
		$collect = false;

		while($l = $this->GetReadFile()->Read(200000)){

			if (preg_match($start, $l, $matches) == 1 ){
				$collect = true;
				$WBGene = $matches[1];
				continue;
			}
			
			if (preg_match($end,$l)== 1 ){
				$collect = false;
				parent::addRDF(
					parent::triplifyString(parent::getNamespace().$WBGene, parent::getVoc()."gene_description", trim($current_description))
				);
				$current_description='';
			}
				
			if ($collect ==  true){
				$current_description = $current_description." ".rtrim($l);
			}
		}
		parent::WriteRDFBufferToWriteFile();
	}
			
	function gene_associations(){

		while($l = parent::getReadFile()->Read()){
			if($l[0] == '#') continue;

			$data = explode("\t", $l);
			$gene = $data[1];
			$go = $data[4];
			$papers = $data[5];
			$evidence_type = $data[6];
			$taxon = $data[12];

			$go_evidence_type = array(
				'IC'=>'eco:0000001', 
				'IDA'=>'eco:0000314', 
				'IEA'=>'eco:0000203', 
				'IEP'=>'eco:0000008', 
				'IGI'=>'eco:0000316',
				'IMP'=>'eco:0000315',
				'IPI'=>'eco:0000021',
				'ISM' => 'eco:0000202',
				'ISO' => 'eco:0000201',
				'ISS'=>'eco:0000044',
				'NAS'=>'eco:0000034',
				'ND'=>'eco:0000035',
				'RCA'=>'eco:0000245',
				'TAS'=>'eco:0000033'
			);

			$association_id = parent::getRes().md5($gene.$go.$evidence_type);
			$association_label = $gene." ".$go." association";
			parent::addRDF(
				parent::describeIndividual($association_id, $association_label, parent::getVoc()."Gene-GO-Association").
				parent::triplify($association_id, parent::getVoc()."gene", parent::getNamespace().$gene).
				parent::triplify($association_id, parent::getVoc()."go_term", $go).
				parent::triplify($association_id, parent::getVoc()."taxon", $taxon)
			);

			if($evidence_type !== ''){
				parent::addRDF(				
					parent::triplify($association_id, parent::getVoc()."evidence_type", $go_evidence_type[$evidence_type])
				);
			}

			$split_papers = explode("|", $papers);
			foreach($split_papers as $paper){
				$paper_id = null;
				$split_paper = explode(":", $paper);
				if($split_paper[0] == "PMID"){
					$paper_id = "pubmed:".$split_paper[1];
				} elseif($split_paper[0] == "WB_REF"){
					$paper_id = parent::getNamespace().$split_paper[1];
					$paper_label = "Wormbase paper ".$split_paper[1];
					parent::addRDF(
						parent::describeIndividual($paper_id, $paper_label, parent::getVoc()."Publication") 
					);
				}
				parent::addRDF(
					parent::triplify($association_id, parent::getVoc()."Publication", $paper_id)
				);
			}//foreach
		}//while
		parent::WriteRDFBufferToWriteFile();
	}
	
	//phenotype association 
 	function phenotype_associations(){

 		while($l = parent::getReadFile()->Read()){
 			if($l[0] == '#') continue;

 			$data = explode("\t", $l);

 			$gene = $data[1];
 			$not = $data[3];
 			$phenotype = $data[4];
 			$paper = $data[5];
 			$var_rnai = $data[7];



 			if($not == "NOT"){

 				$pa_id = parent::getRes().md5($gene.$not.$phenotype.$paper.$var_rnai);
 				$pa_label = "Gene-phenotype non-association between ".$gene." and ".$phenotype." under condition ".$var_rnai;

 				$npa_id = parent::getRes().md5($gene.$not.$phenotype.$paper.$var_rnai."negative property assertion");
 				$npa_label = "Negative property assertion stating that gene ".$gene. "is not associated with phenotype ".$phenotype;

 				parent::addRDF(
	 				parent::describeIndividual($pa_id, $pa_label, parent::getVoc()."Gene-Phenotype-Non-Association").
	 				parent::triplify($pa_id, parent::getVoc()."gene", parent::getNamespace().$gene).
	 				parent::triplify($pa_id, parent::getVoc()."phenotype", parent::getNamespace().$phenotype)
 				);

 				if(strstr($var_rnai, "WBVar")){
	 				parent::addRDF(
	 					parent::describeIndividual(parent::getNamespace().$var_rnai, "Variant of ".$gene, parent::getVoc()."Gene-Variant").
	 					parent::triplify($pa_id, parent::getVoc()."associated-gene-variant", parent::getNamespace().$var_rnai)
	 				);
	 			} elseif(strstr($var_rnai, "WBRNAi")){
	 				$var_rnai_id = parent::getNamespace().$var_rnai;
		 			$var_rnai_label = "RNAi ".$var_rnai;
		 			$rnai_exp_id = parent::getRes().$var_rnai."_".$gene."_".$phenotype;
	 				parent::addRDF(
	 					parent::describeIndividual($var_rnai_id, $var_rnai_label, parent::getVoc()."RNAi").
	 					parent::describeIndividual($rnai_exp_id, "RNAi knockdown experiment targeting gene ".$gene." that does NOT result in phenotype ".$phenotype, parent::getVoc()."RNAi-Knockdown-Experiment").
	 					parent::triplify($rnai_exp_id, parent::getVoc()."target-gene", parent::getNamespace().$gene).
	 					parent::triplify($rnai_exp_id, parent::getVoc()."rnai", $var_rnai_id).
	 					parent::triplify($pa_id, parent::getVoc()."associated-rnai-knockdown-experiment", $rnai_exp_id)
	 				);
	 			}
 				
 				parent::addRDF(
 					parent::describeIndividual($npa_id, $npa_label, "owl:NegativeObjectPropertyAssertion").
 					parent::triplify($npa_id, "owl:sourceIndividual", parent::getNamespace().$gene).
 					parent::triplify($npa_id, "owl:assertionProperty", parent::getVoc()."has-associated-phenotype").
 					parent::triplify($npa_id, "owl:targetIndividual", parent::getNamespace().$phenotype)
 				);

 			} else {
 				$pa_id = parent::getRes().md5($gene.$phenotype.$paper.$var_rnai);
 				$pa_label = "Gene-phenotype association between ".$gene." and ".$phenotype." under condition ".$var_rnai;
 				parent::addRDF(
 					parent::describeIndividual($pa_id, $pa_label, parent::getVoc()."Gene-Phenotype-Association").
 					parent::triplify($pa_id, parent::getVoc()."gene", parent::getNamespace().$gene).
 					parent::triplify($pa_id, parent::getVoc()."phenotype", parent::getNamespace().$phenotype).
 					parent::triplify(parent::getNamespace().$gene, parent::getVoc()."has-associated-phenotype", parent::getNamespace().$phenotype)
 				);

 				if(strstr($var_rnai, "WBVar")){
	 				parent::addRDF(
	 					parent::describeIndividual(parent::getNamespace().$var_rnai, "Variant of ".$gene, parent::getVoc()."Gene-Variant").
	 					parent::triplify($pa_id, parent::getVoc()."associated-gene-variant", parent::getNamespace().$var_rnai)
	 				);
	 			} elseif(strstr($var_rnai, "WBRNAi")){
	 				$var_rnai_id = parent::getNamespace().$var_rnai;
		 			$var_rnai_label = "RNAi ".$var_rnai;
	 				$rnai_exp_id = parent::getRes().$var_rnai."_".$gene."_".$phenotype;
	 				parent::addRDF(
	 					parent::describeIndividual($var_rnai_id, $var_rnai_label, parent::getVoc()."RNAi").
	 					parent::describeIndividual($rnai_exp_id, "RNAi knockdown experiment targeting gene ".$gene." resulting in phenotype ".$phenotype, parent::getVoc()."RNAi-Knockdown-Experiment").
	 					parent::triplify($rnai_exp_id, parent::getVoc()."target-gene", parent::getNamespace().$gene).
	 					parent::triplify($rnai_exp_id, parent::getVoc()."resulting-phenotype", parent::getNamespace().$phenotype).
	 					parent::triplify($rnai_exp_id, parent::getVoc()."rnai", $var_rnai_id).
	 					parent::triplify($pa_id, parent::getVoc()."associated-rnai-knockdown-experiment", $rnai_exp_id)
	 				);
	 			}
 			}
 			parent::WriteRDFBufferToWriteFile();
 		}//while
	}
	
	function gene_interactions(){
		while($l = parent::getReadFile()->Read()){
			if($l[0] == '#') continue;

			$data = explode("\t", $l);
			$interaction = $data[0];
			$interaction_type = $data[1];
			$int_additional_info = $data[2];
			$gene1 = $data[3];
			$gene2 = $data[6];

			$interaction_id = parent::getNamespace().$interaction;

			if($interaction_type == "Genetic"){
				$int_pred = parent::getVoc()."genetically-interacts-with";
			} elseif($interaction_type == "Physical"){
				$int_pred = parent::getVoc()."physically-interacts-with";
			} elseif($interaction_type == "Predicted"){
				$int_pred = parent::getVoc()."predicted-to-interact-with";
			} elseif($interaction_type == "Regulatory"){
				$int_pred = parent::getVoc()."regulates";
			}//elseif

			if($int_additional_info == "No_interaction"){
				$interaction_label = "No ".strtolower($interaction_type)." interaction between ".$gene1." and ".$gene2;

				parent::addRDF(
					parent::describeIndividual($interaction_id, $interaction_label, parent::getVoc().$interaction_type."-Non-Interaction").
					parent::triplify($interaction_id, parent::getVoc()."involves", parent::getNamespace().$gene1).
					parent::triplify($interaction_id, parent::getVoc()."involves", parent::getNamespace().$gene2)
				);

				$npa_id = parent::getRes().md5($interaction_id."negative property assertion");
				$npa_label = "Negative property assertion stating that ".$gene1." and ".$gene2." do not have a ".$interaction_type." interaction";
				
				parent::addRDF(
					parent::describeIndividual($npa_id, $npa_label, "owl:NegativeObjectPropertyAssertion").
					parent::triplify($npa_id, "owl:sourceIndividual", parent::getNamespace().$gene1).
					parent::triplify($npa_id, "owl:targetIndividual", parent::getNamespace().$gene2).
					parent::triplify($npa_id, "owl:assertionProperty", $int_pred)
				);

			} elseif($int_additional_info == "N/A" || $int_additional_info == "Genetic_interaction") {
				$interaction_label = $interaction_type." interaction between ".$gene1." and ".$gene2;
				parent::addRDF(
					parent::describeIndividual($interaction_id, $interaction_label, parent::getVoc().$interaction_type."-Interaction").
					parent::triplify($interaction_id, parent::getVoc()."involves", parent::getNamespace().$gene1).
					parent::triplify($interaction_id, parent::getVoc()."involves", parent::getNamespace().$gene2).
					parent::triplify(parent::getNamespace().$gene1, $int_pred, parent::getNamespace().$gene2)
				);
			} else {
				$interaction_label = $int_additional_info." ".strtolower($interaction_type). " interaction between ".$gene1." and ".$gene2;
				parent::addRDF(
					parent::describeIndividual($interaction_id, $interaction_label, parent::getVoc().$int_additional_info."-".$interaction_type."-Interaction").
					parent::describeClass(parent::getVoc().$int_additional_info."-".$interaction_type."-Interaction", $int_additional_info." ".$interaction_type." Interaction", parent::getVoc().$interaction_type."-Interaction").
					parent::triplify($interaction_id, parent::getVoc()."involves", parent::getNamespace().$gene1).
					parent::triplify($interaction_id, parent::getVoc()."involves", parent::getNamespace().$gene2).
					parent::triplify(parent::getNamespace().$gene1, $int_pred, parent::getNamespace().$gene2)
				);
			}//else
		}//while
		parent::WriteRDFBufferToWriteFile();
	}
}

?> 

