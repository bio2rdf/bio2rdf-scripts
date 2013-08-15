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
		// set and print application parameters
		$this->AddParameter('files',true,null,'|all|geneIDs|functional_description|gene_association|gene_interactions|phenotype_association','','files to process'); #The files subject to RDF from wormbase
		parent::initialize();
	}//constructor
	
	public function Run(){
		//input files
		$ldir = $this->GetParameterValue('indir');
		//output dir
		$odir = $this->GetParameterValue('outdir');
		if(substr($ldir, -1) == "/"):
		else: 
			$ldir = $ldir."/";
		endif;
		if(substr($odir, -1) == "/"):
		else: 
			$odir = $odir."/";
		endif;
		$selectedPackage = trim($this->GetParameterValue('files'));	#File to rdfsize
		$convertTheseFiles = array();
		if($selectedPackage == 'all'):
			$convertTheseFiles=array('geneIDs','functional_description','gene_association','gene_interactions','phenotype_association');
		else:
			$sel_arr = explode(",",$selectedPackage);
			$convertTheseFiles = $sel_arr;
		endif;	 
		foreach($convertTheseFiles as $k => $v){
			##llamar una funcion para geneIds
			if($v == "geneIDs"):
				//seteamos el archivo que se parsearea
				$lfile = "c_elegans.WS235.geneIDs.txt";
				//create a file pointer
				$fp = fopen($ldir.$lfile, "r") or die("Could not open file !\n");
				$fout = "Gene_IDs.rdf";
				$this->SetReadFile($ldir.$lfile);
				$this->GetReadFile()->SetFilePointer($fp);
				$this->SetWriteFile($odir.$fout, false);
				echo "starting with ".$lfile."\n";
				$this->geneIDs(); #Entra a la funcion geneIDs
			endif;
			## llama a la funcion para functional descriptions
			if($v == "functional_description"):
				//seteamos el archivo que se parsearea
				$lfile = "c_elegans.WS235.functional_descriptions.txt";
				//create a file pointer
				$fp = fopen($ldir.$lfile, "r") or die("Could not open file !\n");
				$fout = "Genes_functional_descriptions.rdf";
				$this->SetReadFile($ldir.$lfile);
				$this->GetReadFile()->SetFilePointer($fp);
				$this->SetWriteFile($odir.$fout, false);
				echo "starting with ".$lfile."\n";
				$this->functional_descri();
			endif;
			##call the function for gene_association
			if($v == "gene_association"):
				//seteamos el archivo que se parsearea
				$lfile = "gene_association.WS235.wb"; # real file  gene_association.WS235.wb
				//create a file pointer
				$fp = fopen($ldir.$lfile, "r") or die("Could not open file !\n");
				$fout = "gene_association.rdf";
				$this->SetReadFile($ldir.$lfile);
				$this->GetReadFile()->SetFilePointer($fp);
				$this->SetWriteFile($odir.$fout, false);
				echo "starting with ".$lfile."\n";
				$this->gene_association_F();
			endif;	
			##call the function for phenotype_association
			if($v == "phenotype_association"):
				//seteamos el archivo que se parsearea
				$lfile = "phenotype_association.WS235.wb"; # phenotype_association.WS235.wb
				//create a file pointer
				$fp = fopen($ldir.$lfile, "r") or die("Could not open file !\n");
				$fout = "phenotype_association.rdf";
				$this->SetReadFile($ldir.$lfile);
				$this->GetReadFile()->SetFilePointer($fp);
				$this->SetWriteFile($odir.$fout, false);
				echo "starting with ".$lfile."\n";
				$this->phenotype_association_F();
			endif;	
			if($v == "gene_interactions"):
				//seteamos el archivo que se parsearea
				$lfile = "c_elegans.WS235.gene_interactions.txt"; # real file c_elegans.WS235.gene_interactions.txt
				//create a file pointer
				$fp = fopen($ldir.$lfile, "r") or die("Could not open file !\n");
				$fout = "gene_interactions.rdf";
				$this->SetReadFile($ldir.$lfile);
				$this->GetReadFile()->SetFilePointer($fp);
				$this->SetWriteFile($odir.$fout, false);
				echo "starting with ".$lfile."\n";
				$this->gene_interactions_F();					
			endif;
			$this->GetWriteFile()->Close();
		}
	} #Run

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
			$this->WriteRDFBufferToWriteFile();
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
				
			$this->WriteRDFBufferToWriteFile();
		}
		
	}#function functional_descri
			
	private function gene_association_F(){

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
	} //gene_association_F function
	
	//phenotype association 
 	function phenotype_association_F(){

 		while($l = parent::getReadFile()->Read()){
 			$data = explode("\t", $l);

 			$gene = $data[1];
 			$not = $data[3];
 			$phenotype = $data[4];
 			$paper = $data[5];
 			$var_rnai = $data[7];


 			parent::addRDF(
 				parent::describeIndividual($pa_id, $pa_label, parent::getVoc()."Gene-Phenotype-Association").
 				parent::triplify($pa_id, parent::getVoc()."gene", parent::getNamespace().$gene).
 			);

 			if($not == "NOT"){
 				$npa_id = parent::getRes().md5($gene.$phenotype.$paper.$variant."negative property assertion");
 				$npa_label = "Negative property assertion stating that gene ".$gene. "is not associated with phenotype ".$phenotype;
 				parent::addRDF(
 					parent::describeIndividual($npa_id, $npa_label, "owl:NegativePropertyAssertion")
 					parent::triplify($npa_id, "owl:sourceIndividual", parent::getNamespace().$gene).
 					parent::triplify($npa_id, "owl:assertionProperty", parent::getVoc()."phenotype").
 					parent::triplify($npa_id, "owl:targetIndividual", parent::getNamespace().$phenotype)
 				);
 			} else {
 				$pa_id = parent::getRes().md5($gene.$phenotype.$paper.$variant);
 				$pa_label = "Gene-phenotype association between ".$gene." and ".$phenotype." under condition ".$var_rnai;
 				parent::addRDF(
 					parent::triplify($pa_id, parent::getVoc()."phenotype", parent::getNamespace().$phenotype)
 				);
 			}

 			if(strstr($var_rnai, "WBVar")){
 				parent::addRDF(
 					parent::describeIndividual(parent::getNamespace().$var_rnai, "Variant of ".$gene, parent::getVoc()."Gene-Variant").
 					parent::triplify($pa_id, parent::getVoc()."associated-gene-variant", parent::getNamespace().$var_rnai)
 				);
 			} elseif(strstr($var_rnai, "WBRNAi")){
 				parent::addRDF(
 					parent::describeIndividual(parent::getNamespace().$var_rnai, "RNAi knockdown experiment", parent::getVoc()."RNAi-Knockdown-Experiment").
 					parent::triplify($pa_id, parent::getVoc()."associated-rnai-knockdown-experiment", parent::getNamespace().$var_rnai)
 				);
 			}
 		}//while

		parent::WriteRDFBufferToWriteFile();
	} ##phenotype_association
	
	private function gene_interactions_F(){
		#1 Regular expression to cath the data
		$wbgi= '/(^WBInteraction[0-9]+)/';
		$itype='/(Genetic|Physical|Predicted|Regulatory)/';
		$ad_info='/(Enhancement|Epistasis|Genetic_interaction|Mutual_enhancement|Mutual_suppression|N\/A|No_interaction|Suppression|Synthetic)/';
		$wbgs='/^.+\s(WBGene[0-9]+)\s.+\s(WBGene[0-9]+)/';
		$Dicto=array('WBI'=>'','I_type'=>'','Ad_info'=>'','WBG1'=>'','WBG2'=>'');
		while($aLine = $this->GetReadFile()->Read(200000)):
				if (preg_match($wbgi,$aLine,$matches)==1):
					$Dicto['WBI']=$matches[1];
				endif;	
				if (preg_match($itype,$aLine,$matches)==1):
					$Dicto['I_type']=$matches[1];
				endif;	
				if (preg_match($ad_info,$aLine,$matches)==1):
					$Dicto['Ad_info']=$matches[1];
				endif;	
				if (preg_match($wbgs,$aLine,$matches)==1):
					$Dicto['WBG1']=$matches[1];
					$Dicto['WBG2']=$matches[2];
				endif;		
				$this->Resource_relations($Dicto,"Inter");
				$Dicto=array('WBI'=>'','I_type'=>'','Ad_info'=>'','WBG1'=>'','WBG2'=>'');
		endwhile;
		echo "Done! gene_interactions.rdf wrote \n";
	} //enf gene interaction F
	
	
	##common function for Dict objects to RDFsize
	private function Resource_relations($Gene_dicto,$resource){ 
		if ($resource=='Gene'): //$Dicto=array('WBG'=>'','GO'=>array(),'Pubmed'=>array(),'Tax'=>array(),'Go_Evi'=>array());
		##GO evidence
		$GO_Evidence= array('IC'=>'Inferred_by_Curator', 'IDA'=>'Inferred_from_Direct_Assay', 'IEA'=>'Inferred_from_Electronic_Annotation', 'IEP'=>'Inferred_from_Expression_Pattern', 'IGI'=>'Inferred_from_Genetic_Interaction','IMP'=>'Inferred_from_Mutant_Phenotype','IPI'=>'Inferred_from_Physical_Interaction','ISS'=>'Inferred_from_Sequence_(or_Structural)_Similarity','NAS'=>'Non-traceable_Author_Statement','ND'=>'No_Biological_Data_available','RCA'=>'Inferred_from_Reviewed_Computational_Analysis','TAS'=>'Traceable_Author_Statement');
		$n=0;
		foreach ($Gene_dicto['Go_Evi'] as $current_GO_evi) {
		  $evi_type=$GO_Evidence[$current_GO_evi];
		  $current_GO=$Gene_dicto['GO'][$n];
		  ##Ad evidence resource type
		  $this->AddRDF(
					$this->QQuad(
						"wormbase_resource:".$Gene_dicto['WBG'].$current_GO_evi.$current_GO,
						"rdf:type",
						"wormbase_vocabulary:".$evi_type
					)
				);
			##Ad the relation beetween gene and direct relation object			  
				$this->AddRDF(
				$this->QQuad(
					"wormbase_resource:".$Gene_dicto['WBG'].$current_GO_evi.$Gene_dicto['GO'][$n],
					"wormbase_vocabulary:sourceIndividual",
					"wormbase_resource:".$Gene_dicto['WBG']
					)
				);
			##Ad the relation beetween direct relation object and GO			  
				$this->AddRDF(
				$this->QQuad(
					"wormbase_resource:".$Gene_dicto['WBG'].$current_GO_evi.$Gene_dicto['GO'][$n],
					"wormbase_vocabulary:targetIndividual",
					"go:".$Gene_dicto['GO'][$n]
					)
				);	
				$n=$n+1;
			} //foreach GOevidence	
			##TaxID
			foreach ($Gene_dicto['Tax'] as $current_Tax) {
				$this->AddRDF(
				$this->QQuad(
					"wormbase_resource:".$Gene_dicto['WBG'],
					"wormbase_vocabulary:is_associated_with",
					"taxon:".$current_Tax
					)
				);
			} //foreach taxonomy	
			##Pubmed
			foreach ($Gene_dicto['Pubmed'] as $current_Pub) {
				$this->AddRDF(
				$this->QQuad(
					"wormbase_resource:".$Gene_dicto['WBG'],
					"wormbase_vocabulary:is_associated_with",
					"pubmed:".$current_Pub 
					)
				);
			} //foreach pubmed	
		$this->WriteRDFBufferToWriteFile();	 
		elseif ($resource=='Phenotype'): //$Dicto=array('WBG'=>'','fenotipo'=>array( [NOT] WBPhenotype:0001413|RNAiWBRNAi00090769),'variante'=>array(),'ARNi'=>array()); 
	//		$variantes_list=array(); #to be shure of not overwrite the same resource two times or more.
		//	$rnai_list=array(); #to be shure of not overwrite the same resource two times or more.
			## write RDF for variants
			foreach ($Gene_dicto['variante'] as $vari):  
				##add rdf type for variants	
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:".$vari,
						"rdf:type",
						"wormbase_vocabulary:Gene_variant"
					)
				);
				//add the rdfs:label
				$this->AddRDF(
					$this->QQuadL(
						"wormbase_resource:".$vari,
						"rdfs:label",
						"Wormbase Gene variant of ".$Gene_dicto['WBG'].",["."wormbase:".$vari."]"
					)
				);	
				##add relation of gene->variant
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:".$Gene_dicto['WBG'],
						"wormbase_vocabulary:has_variant",
						"wormbase_resource:".$vari
					)
				);
			endforeach;
			foreach ($Gene_dicto['ARNi'] as $rnai):
				##add rdf type for rnai	
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:".$rnai,
						"rdf:type",
						"wormbase_vocabulary:knockdown(RNAi)"
					)
				);
				//add the rdfs:label
				$this->AddRDF(
					$this->QQuadL(
						"wormbase_resource:".$rnai,
						"rdfs:label",
						"Wormbase ".$Gene_dicto['WBG']." knockdown RNAi experiment ,["."wormbase:".$rnai."]"
					)
				);	
				##add relation of gene->rnai
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:".$Gene_dicto['WBG'],
						"wormbase_vocabulary:is_target_in",
						"wormbase_resource:".$rnai
					)
				);
			endforeach;
			foreach($Gene_dicto['fenotipo'] as $gen_phen):
	//		echo $gen_phen."\n"; 
			$components=explode("|",$gen_phen);
	//		var_dump($components);
				if (preg_match('/NOT\s(.+)/',$components[0],$matches)==1):
					##add rdf type for owl:NegativePropertyAssertion	
					$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$matches[1]."|".$components[1], ## How to generate the uniq id here.
							"rdf:type",
							"owl:NegativePropertyAssertion" ##has to also included inside of wormbase vocabulary?
						)
					);
					##add the indivisual source for owl:NegativePropertyAssertion
					$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$matches[1]."|".$components[1], ## How to generate the uniq id here.
							"owl:sourceIndividual",
							"wormbase_resource:".$components[1]
						)
					);
					##add the indivisual source for owl:NegativePropertyAssertion
					$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$matches[1]."|".$components[1], ## How to generate the uniq id here.
							"owl:assertionProperty",
							"wormbase_vocabulary:has_phenotype"
						)
					);
					##add the individual target for owl:NegativePropertyAssertion
					$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$matches[1]."-".$components[1], ## How to generate the uniq id here.
							"owl:targetIndividual",
							"wormbase_resource:".$matches[1]
						)
					);					
				else:
				##add the positive relation between variant|RNA with phenotype
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:".$components[1],
						"wormbase_vocabulary:has_phenotype",
						"wormbase_resource:".$components[0]
					)
				);
				endif;	
			endforeach;
			$this->WriteRDFBufferToWriteFile();	 
			
		elseif ($resource=='Inter'): //$Dicto=array('WBI'=>'','I_type'=>'','Ad_info'=>'','WBG1'=>'','WBG2'=>'');
			##add the RDF relations
			#1 WBI resource,type 4 tipos dependindo
			//add the rdf:type for WBI
				if ($Gene_dicto['I_type']=='Genetic'): //Genetic|Physical|Predicted|Regulatory
					$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$Gene_dicto['WBI'],
							"rdf:type",
							"wormbase_vocabulary:Genetic_Interaction"
						)
					);
				elseif ($Gene_dicto['I_type']=='Physical'): 
					$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$Gene_dicto['WBI'],
							"rdf:type",
							"wormbase_vocabulary:Physical_Interaction"
						)
					);
				elseif ($Gene_dicto['I_type']=='Predicted'): 
					$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$Gene_dicto['WBI'],
							"rdf:type",
							"wormbase_vocabulary:Predicted_Interaction"
						)
					);
				elseif ($Gene_dicto['I_type']=='Regulatory'): 
					$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$Gene_dicto['WBI'],
							"rdf:type",
							"wormbase_vocabulary:Regulatory_Interaction"
						)
					);
				endif;
				#2 WBI label texto indica genA y B
				$this->AddRDF(
					$this->QQuadL(
						"wormbase_resource:".$Gene_dicto['WBI'],
						"rdfs:label",
						"Wormbase ".$Gene_dicto['I_type']." interaction between ".$Gene_dicto['WBG1']." and ".$Gene_dicto['WBG2']
					)
				);					
			
			# 4 WBI invole resourse gene A y B
				$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$Gene_dicto['WBI'],
							"wormbase_vocabulary:involves",
							"wormbase_resource:".$Gene_dicto['WBG1']
						)
					);
				$this->AddRDF(
						$this->QQuad(
							"wormbase_resource:".$Gene_dicto['WBI'],
							"wormbase_vocabulary:involves",
							"wormbase_resource:".$Gene_dicto['WBG2']
						)
					);
			#3 WBI has details Ad info (one of this is a negative relation) Enhancement|Epistasis|Genetic_interaction|Mutual_enhancement|Mutual_suppression|N\/A|No_interaction|Suppression|Synthetic
			if ($Gene_dicto['Ad_info']=='No_interaction'):
				##add rdf type for owl:NegativePropertyAssertion	
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:Not".$Gene_dicto['WBI'], 
						"rdf:type",
						"owl:NegativePropertyAssertion" ##
					)
				);
				##add the indivisual source for owl:NegativePropertyAssertion
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:Not".$Gene_dicto['WBI'],  ## How to generate the uniq id here.
						"owl:sourceIndividual",
						"wormbase_resource:".$Gene_dicto['WBI'] ##it comes from WBGI#
					)
				);
				##add the propertyefor owl:NegativePropertyAssertion
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:Not".$Gene_dicto['WBI'], ## How to generate the uniq id here.
						"owl:assertionProperty",
						"wormbase_vocabulary:involves"
					)
				);
				##add the individual target for owl:NegativePropertyAssertion
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:Not".$Gene_dicto['WBI'], ## How to generate the uniq id here.
						"owl:targetIndividual",
						"wormbase_resource:".$Gene_dicto['WBG1']
					)
				);	
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:Not".$Gene_dicto['WBI'], ## How to generate the uniq id here.
						"owl:targetIndividual",
						"wormbase_resource:".$Gene_dicto['WBG2']
					)
				);
			else:
				$this->AddRDF(
						$this->QQuadL(
							"wormbase_resource:".$Gene_dicto['WBI'],
							"wormbase_vocabulary:Aditional_information",
							"Aditional interaction information: ".$Gene_dicto['Ad_info']
						)
					);
			endif;			
		$this->WriteRDFBufferToWriteFile();	
		endif;
	} //Dict_relations
	
	
} ## LA clase
$pwd = `pwd`;
if (count($argv) == 1 or $argv[1]=="-h|help"):
 echo "\n";
 echo ' no arguments supply runing with default parametes '."\n"; 
 echo "\n";
 echo " php wormbase_parser.php file=all indir=$pwd outdir=$pwd \n";	
 echo " Other usage look like this: \n";
 echo " php wormbase_parser.php file=gene_association intdir=/my/specific/folder \n";
 echo " It possible to supply more than one file separate by commas. Accepted files are the following : ";
 echo " geneIDs,functional_description, gene_association, gene_interactions phenotype_association or all \n";
 echo " indir=directory to download into and parse from \n";
 echo " outdir=directory to place rdfized files \n";

 $argv[1]='files=all';
endif; 
$p = new WormbaseParser($argv, $pwd);
$p->Run();
?> 

