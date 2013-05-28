<?php
/*
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


require("../../php-lib/rdfapi.php");
class WormbaseParser extends RDFFactory{
	private $bio2rdf_base = "http://bio2rdf.org/";
	private $wormbase_vocab = "wormbase_vocabulary:";
	private $wormbase_resource = "wormbase_resource:";
	private $version = null; // version of the release data
	
	function __construct($argv, $path) {
		parent::__construct();
		$this->SetDefaultNamespace("wormbase");
		// set and print application parameters
		$this->AddParameter('files',true,null,'|all|geneIDs|functional_description|gene_association|gene_interactions|phenotype_association','','files to process'); #The files subject to RDF from wormbase
		$this->AddParameter('indir',false,null,$path,'directory to download into and parse from');
		$this->AddParameter('outdir',false,null,$path,'directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
		return TRUE;
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
	
	
	private function geneIDs(){
		while($aLine = $this->GetReadFile()->Read(200000)){
			$gene_IDs=explode(",",trim($aLine));
			if(count($gene_IDs) == 3){
				//add the rdf:type
				$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:".$gene_IDs[0],
						"rdf:type",
						"wormbase_vocabulary:Gene"
					)
				);
				//add the rdfs:label
				$this->AddRDF(
					$this->QQuadL(
						"wormbase_resource:".$gene_IDs[0],
						"rdfs:label",
						"Wormbase Gene, also know as: ".$gene_IDs[1]." or based in their cosmid name is also known as ".$gene_IDs[2]." ["."wormbase:".$gene_IDs[0]."]"
					)
				);
				//add gene approved name
				if ($gene_IDs[1]!=''):
					$this->AddRDF(
					$this->QQuadL(
						"wormbase_resource:".$gene_IDs[0],
						"wormbase_vocabulary:has_approved_gene_name",
						"$gene_IDs[1]"
					)
				);
				endif;
				#Add cosmid name 
				if ($gene_IDs[2]!=''):
					$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:".$gene_IDs[0],
						"wormbase_vocabulary:has_sequence/cosmid_name",
						"wormbase_resource:".$gene_IDs[2]
					)
				);
					$this->AddRDF(
					$this->QQuad(
						"wormbase_resource:".$gene_IDs[2],
						"rdf:type",
						"wormbase_vocabulary:Cosmid_gene"
					)
				);
				
				endif;
				
			}#if	
			$this->WriteRDFBufferToWriteFile();
		}//while
		echo "Done! file Gene_IDs.rdf wrote \n";
		
			}# Funcion Gene_IDs
			
	private function functional_descri(){
		$start= '/(^WBGene[0-9]+)\s/';
		$end= '/^=\n/';
		$current_description='';
		$collect=false;
		while($aLine = $this->GetReadFile()->Read(200000)):
		if (preg_match($start,$aLine,$matches)==1):
			$collect=true;
			$WBGene=$matches[1];
			continue;
		endif;
		if (preg_match($end,$aLine)==1):
			$collect= false;
			$this->AddRDF(
					$this->QQuadL(
						"wormbase_resource:".$WBGene,
						"wormbase_vocabulary:gene_description",
						"$current_description"
					)
				);
			$current_description='';
		endif;
		if ($collect ==  true):
			$current_description=$current_description.rtrim($aLine);
		endif;
		$this->WriteRDFBufferToWriteFile();
		endwhile;
		echo "Done! file Genes_functional_descriptions.rdf wrote \n";
	}	#function functional_descri
			
	private function gene_association_F(){
		#1 Regular expression to cath the data
		$wbg= '/(WBGene[0-9]+)/';
		$goo='/GO:([0-9]+)/';
		$pubmed_ID='/PMID:([0-9]+)/';
		$taxa='/taxon:([0-9]+)/';
		/*
IC  Inferred by Curator
IDA Inferred from Direct Assay
IEA Inferred from Electronic Annotation
IEP Inferred from Expression Pattern
IGI Inferred from Genetic Interaction
IMP Inferred from Mutant Phenotype
IPI Inferred from Physical Interaction
ISS Inferred from Sequence (or Structural) Similarity
NAS Non-traceable Author Statement
ND  No Biological Data available
RCA Inferred from Reviewed Computational Analysis
TAS Traceable Author Statement
 */		$go_evidence='/(IC|IDA|IEA|IEP|IGI|IMP|IPI|ISS|NAS|ND|RCA|TAS)/';	
		$Dicto=array('WBG'=>'','GO'=>array(),'Pubmed'=>array(),'Tax'=>array(),'Go_Evi'=>array());
		$gen_actual='';
		//$bb=count($this->GetReadFile()->Read(200000)); ***revisar que esto no afecte
		while($aLine = $this->GetReadFile()->Read(200000)):
				if (preg_match($wbg,$aLine,$matches)==1):
				endif;	
				$line_components = preg_split("/[\s]+/", $aLine);
				##cada elemento en su lugar
				if ( "$matches[1]" == $gen_actual or $gen_actual == ''):					
					$gen_actual="$matches[1]";
				elseif("$matches[1]" != $gen_actual):
					##Escribir las tripletas##
					$this->Resource_relations($Dicto,"Gene");
					$Dicto=array('WBG'=>'','GO'=>array(),'Pubmed'=>array(),'Tax'=>array(),'Go_Evi'=>array());
					$gen_actual="$matches[1]";
				endif;
					foreach ($line_components as $component){
						$Dicto['WBG']="$matches[1]";
						if (preg_match($goo,$component,$go_matches)==1):
							$Dicto['GO'][]=$go_matches[1];
						elseif (preg_match($pubmed_ID,$component)==1):
							$pub_sep=explode("|",trim($component));
							foreach ($pub_sep as $single_pub){
								if (preg_match($pubmed_ID,$single_pub,$pb_matches)==1):
									if (in_array($pb_matches[1], $Dicto["Pubmed"])):
									else:	
										$Dicto['Pubmed'][]=$pb_matches[1];
									endif;
								endif;	
							}//end foreach	
						elseif (preg_match($taxa,$component,$tax_matches)==1):
							if (in_array($tax_matches[1], $Dicto["Tax"])):
							else:	
							$Dicto['Tax'][]=$tax_matches[1];
							endif;	
						elseif (preg_match($go_evidence,$component,$goev_matches)==1):
								$Dicto['Go_Evi'][]=$goev_matches[1];
						else :
						endif;
					} //end foreach	
		endwhile;
		$this->Resource_relations($Dicto,"Gene");
		echo "Done! file gene_association.rdf wrote \n";
	}
 # gene_association_F function
	
	##phenotype association 
 	private function phenotype_association_F()	{
		$wbg= '/(WBGene[0-9]+)/';
 		$phe='/(WBPhenotype:[0-9]+)/';
		$WBvar='/(WBVar[0-9]+)/';
		$rnai='/(WBRNAi[0-9]+)/';
		$Dicto=array('WBG'=>'','fenotipo'=>array(),'variante'=>array(),'ARNi'=>array()); //
		$gen_actual='primero';
		while($aLine = $this->GetReadFile()->Read(200000)):
			if (preg_match($wbg,$aLine,$matches)==1):
				if ($matches[1]==$Dicto['WBG'] or $gen_actual=='primero'):
					$Dicto['WBG']=$matches[1];
					$gen_actual='basura';
				else:
					$this->Resource_relations($Dicto,"Phenotype");
					$Dicto=array('WBG'=>'','fenotipo'=>array(),'variante'=>array(),'ARNi'=>array()); //new gen, we clean the array/
					$Dicto['WBG']=$matches[1];
				endif;	
			endif;
			if	(preg_match($phe,$aLine,$matches)==1): ##aqio
				if (preg_match($WBvar,$aLine,$matches_var)==1):
					if (preg_match('/\s+NOT\s+/',$aLine)==1):
						$Dicto['fenotipo'][]="NOT ".$matches[1]."|".$matches_var[1];
					else:
						$Dicto['fenotipo'][]=$matches[1]."|".$matches_var[1];
					endif;
     			elseif 	(preg_match($rnai,$aLine,$matches_rnai)==1):
					if (preg_match('/\s+NOT\s+/',$aLine)==1):
						$Dicto['fenotipo'][]="NOT ".$matches[1]."|".$matches_rnai[1];
					else:
						$Dicto['fenotipo'][]=$matches[1]."|".$matches_rnai[1];
					endif;
				endif;	
			endif;	
			if	(preg_match($WBvar,$aLine,$matches)==1):
				if (in_array($matches[1], $Dicto["variante"])): 
				else:
					$Dicto['variante'][]=$matches[1];
				endif;
			endif;
			if	(preg_match($rnai,$aLine,$matches)==1):
				if (in_array($matches[1], $Dicto["ARNi"])): 
				else:
					$Dicto['ARNi'][]=$matches[1];
				endif;
			endif;		
		endwhile;
	$this->Resource_relations($Dicto,"Phenotype");
	echo "Done! file phenotype_association.rdf wrote \n";
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

