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
 * SABIORK RDFizer
 * @version 2.0
 * @author Juan Jose Cifuentes
 * @author Alison Callahan
 * @description http://www.wormbase.org/about/userguide
*/

class WormbaseParser extends Bio2RDFizer {

	function __construct($argv, $path) {
		parent::__construct($argv, "wormbase");
		parent::addParameter('files', true, null, 'all|geneIDs|functional_description|gene_association|gene_interactions|phenotype_association','all','files to process');
		parent::addParameter('release', true, null, 'WS235')
		parent::addParameter('download_url', false, null 'ftp://ftp.wormbase.org/pub/wormbase/')
		parent::initialize();
	}//constructor
	
	public function Run(){

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
			"geneIDs" => "species/c_elegans/annotation/geneIDs/c_elegans.".parent::parameterValue('release').".geneIDs.txt.gz",
			"functional_description" => "species/c_elegans/annotation/functional_descriptions/c_elegans.".parent::getParameterValue('release').".functional_descriptions.txt.gz",
			"gene_association" => "releases/".parent::getParameterValue('release')."/ONTOLOGY/gene_association.".parent::getParameterValue('release').".wb.ce",
			"gene_interactions" => "species/c_elegans/annotation/gene_interactions/c_elegans.".parent::parameterValue('release').".gene_interactions.txt.gz",
			"phenotype_association" => "releases/".parent::getParameterValue('release')."/ONTOLOGY/phenotype_association.".parent::getParameterValue('release').".wb"
		);

		$local_files = array(
			"geneIDs" => "c_elegans.".parent::parameterValue('release').".geneIDs.txt.gz",
			"functional_description" => parent::getParameterValue('release').".functional_descriptions.txt.gz",
			"gene_association" => "gene_association.".parent::getParameterValue('release').".wb.ce",
			"gene_interactions" => "c_elegans.".parent::parameterValue('release').".gene_interactions.txt.gz",
			"phenotype_association" => "phenotype_association.".parent::getParameterValue('release').".wb"
		);

		//set directory values
		$ldir = parent::getParameterValue('indir');
		$rdir = parent::getParameterValue('download_url');

		foreach($files as $file){
			$rfile = $rdir.$remote_files[$file];
			$lfile = $ldir.$local_files[$file];
			parent::downloadSingle($rfile, $lfile);
		}
		
	}

	function process(){
		if(parent::getParameterValue('files') == 'all') {
			$files = explode("|",parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",parent::getParameterValue('files'));
		}

		$local_files = array(
			"geneIDs" => "c_elegans.".parent::parameterValue('release').".geneIDs.txt.gz",
			"functional_description" => parent::getParameterValue('release').".functional_descriptions.txt.gz",
			"gene_association" => "gene_association.".parent::getParameterValue('release').".wb.ce",
			"gene_interactions" => "c_elegans.".parent::parameterValue('release').".gene_interactions.txt.gz",
			"phenotype_association" => "phenotype_association.".parent::getParameterValue('release').".wb"
		);

		$idir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');

		foreach($files as $file){
			$lfile = $idir.$local_files[$file];
			if(strstr($lfile, "gz")){
				parent::setReadFile($lfile, TRUE);
			} else {
				parent::setReadFile($lfile, FALSE);
			}

			$suffix = parent::getParameterValue('output_format');
			$ofile = $file.".".$suffix;

			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}

			$this->SetWriteFile($odir.$file, $gz);

			echo "Processing $file... "
			$fnx = $file;
			$this-> $fnx();
			echo "done!";
		}
	}

	function geneIDs(){
		$first = true;
		while($l = $this->GetReadFile()->Read()){
			if($l[0] == '#') continue;
			
			$data = explode("\t",trim($l));

			if($first) {
				if(($c = count($data) != 3)) {
					trigger_error("WormBase function expects 3 fields, found $c!".PHP_EOL, E_USER_WARNING);
				}
				$first = false;
			}
			//add the rdf:type

			$id = parent::getNamespace().$gene_IDs[0];
			$gene_label = "WormBase gene ".$gene_IDs[1]." with cosmid name ".$gene_IDs[2];

			parent::addRDF(
				parent::describeIndividual($id, $gene_label, parent::getVoc()."Gene")
			);
			
			//add gene approved name
			if ($gene_IDs[1] != '') {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."has_approved_gene_name", $gene_IDs[1])
				);
			}
			#Add cosmid name 
			if ($gene_IDs[2] != '') {
				$cosmid_id = parent::getRes().$gene_IDs[2];
				parent::addRDF(
					parent::describeIndividual($cosmid_id, "Gene/cosmid name for ".$gene_IDs[0], parent::getVoc()."Cosmid_gene")
					parent::triplify($id, parent::getVoc()."has_sequence/cosmid_name", $cosmid_id)
				);
			}				
			parent::WriteRDFBufferToWriteFile();
		}//while
	}# Funcion Gene_IDs
			
	function functional_description(){
		
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
					parent::triplifyString(parent::getNamespace().$WBGene, parent::getVoc()."gene_description", $current_description)
				);
				$current_description='';
			}
				
			if ($collect ==  true){
				$current_description = $current_description.rtrim($l);
			}
		}
		parent::WriteRDFBufferToWriteFile();
	}#function functional_descri
			
	private function gene_association(){

		while($l = parent::getReadFile->Read()){

			$data = explode("\t", $l);
			$gene = $data[1];
			$go = $data[3];
			$papers = $data[4];
			$evidence_type = $data[5];
			$taxon = $data[9];

			$go_evidence_type = array(
				'IC'=>'eco:0000001', 
				'IDA'=>'eco:0000314', 
				'IEA'=>'eco:0000203', 
				'IEP'=>'eco:0000008', 
				'IGI'=>'eco:0000316',
				'IMP'=>'eco:0000315',
				'IPI'=>'eco:0000021',
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
				parent::triplify($association_id, parent::getVoc()."evidence_type", $go_evidence_type[$evidence_type]).
				parent::triplify($association_id, parent::getVoc()."gene", parent::getNamespace().$gene).
				parent::triplify($association_id, parent::getVoc()."go_term", $go).
				parent::triplify($association_id, parent::getVoc()."taxon", $taxon)
			);

			$split_papers = explode("|", $papers);
			foreach($split_papers as $paper){
				$paper_id = null;
				$split_paper = explode(":", $paper);
				if($paper[0] == "PMID"){
					$paper_id = "pubmed:".$paper[1];
				} elseif($paper[0] == "WB_REF"){
					$paper_id = parent::getNamespace().$paper[1];
					$paper_label = "Wormbase paper ".$paper[1];
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
 	function phenotype_association(){

 		while($l = parent::getReadFile()->Read()){
 			$data = explode("\t", $l);

 			$gene = $data[1];
 			$not = $data[3];
 			$phenotype = $data[4];
 			$paper = $data[5];
 			$var_rnai = $data[7];

 			if($not == "NOT"){

 				$pa_id = parent::getRes().md5($gene.$not.$phenotype.$paper.$variant);
 				$pa_label = "Gene-phenotype non-association between ".$gene." and ".$phenotype." under condition ".$var_rnai;

 				$npa_id = parent::getRes().md5($gene.$not.$phenotype.$paper.$variant."negative property assertion");
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
	 				parent::addRDF(
	 					parent::describeIndividual(parent::getNamespace().$var_rnai, "RNAi knockdown experiment targeting gene ".$gene." that does NOT result in phenotype ".$phenotype, parent::getVoc()."RNAi-Knockdown-Experiment").
	 					parent::triplify($pa_id, parent::getVoc()."associated-rnai-knockdown-experiment", parent::getNamespace().$var_rnai)
	 				);
	 			}
 				
 				parent::addRDF(
 					parent::describeIndividual($npa_id, $npa_label, "owl:NegativeObjectPropertyAssertion")
 					parent::triplify($npa_id, "owl:sourceIndividual", parent::getNamespace().$gene).
 					parent::triplify($npa_id, "owl:assertionProperty", parent::getVoc()."has-associated-phenotype").
 					parent::triplify($npa_id, "owl:targetIndividual", parent::getNamespace().$phenotype)
 				);

 				

 			} else {
 				$pa_id = parent::getRes().md5($gene.$phenotype.$paper.$variant);
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
	 				parent::addRDF(
	 					parent::describeIndividual(parent::getNamespace().$var_rnai, "RNAi knockdown experiment targeting gene ".$gene." resulting in phenotype ".$phenotype, parent::getVoc()."RNAi-Knockdown-Experiment").
	 					parent::triplify($pa_id, parent::getVoc()."associated-rnai-knockdown-experiment", parent::getNamespace().$var_rnai)
	 				);
	 			}
 			}

 			
 		}//while
		parent::WriteRDFBufferToWriteFile();
	} ##phenotype_association
	
	private function gene_interactions(){
		#1 Regular expression to cath the data
		while($l = parent::getReadFile()->Read()){

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
				$npa_label = "Negative property assertion stating that ".$gene1." and ".$gene2."do not have a ".$interaction_type." interaction";
				
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
				$interaction_label = $int_additional_info." ".strtolower($interaction_type). "interaction between ".$gene1." and ".$gene2;
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

