<?php
/**
Copyright (C) 2011-2012 Michel Dumontier

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
 * Parser for CTD: The Comparative Toxicogenomics Database (http://ctd.mdibl.org/)
 * documentation: http://ctdbase.org/downloads
 * download pattern: http://ctd.mdibl.org/reports/XXXX.tsv.gz
*/


require('../../php-lib/rdfapi.php');
class CTDParser extends RDFFactory 
{
	function __construct($argv) { //
		parent::__construct();
		// set and print application parameters
		$this->AddParameter('files',true,'all|chem_gene_ixns|chem_gene_ixn_types|chemicals_diseases|chem_go_enriched|chem_pathways_enriched|genes_diseases|genes_pathways|diseases_pathways|chemicals|diseases|genes|pathways','all','all or comma-separated list of files to process');
		$this->AddParameter('indir',false,null,'/data/download/ctd/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/ctd/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://ctd.mdibl.org/reports/');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		$this->SetReleaseFileURI("ctd");
		
		return TRUE;
	}
	
	function Run()
	{
		// get the file list
		if($this->GetParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",$this->GetParameterValue('files'));
		}

		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rdir = $this->GetParameterValue('download_url');
		$gz_suffix = ".gz";

		foreach($files AS $file) {
			$lfile = $ldir.$file.$gz_suffix;
			$outfile = $odir.$file.".ttl.gz";
			if(!file_exists($lfile)) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
			}
			
			if($this->GetParameterValue('download') == true) {
				if($file == 'chem_gene_ixn_types') $suffix = '.tsv';
				else if($file == 'exposure_ontology') $suffix = '.obo';
				else $suffix = ".tsv.gz";
				
				$rfile = $rdir.'CTD_'.$file.$suffix;
				if($suffix == ".tsv.gz") copy($rfile,$lfile);
				else {
					$buf = file_get_contents($rfile);
					file_put_contents("compress.zlib://".$lfile, $buf);
				}
			}
			
			echo "Processing ".$file." ...";
			$this->SetReadFile($lfile, true);
			$this->SetWriteFile($outfile, true);
	
			$fnx = "CTD_".$file;
			if($this->$fnx() === FALSE) {
				trigger_error("Error in $fnx");
				exit;
			}
			
			$this->WriteRDFBufferToWriteFile();
			$this->GetWriteFile()->Close();
			echo "Done!".PHP_EOL;
		}
	}


/*
Fields:
x 0 ChemicalName
x 1 ChemicalID (MeSH accession identifier)
x 2 CasRN
  3 Definition
  4 ParentIDs (accession identifiers of the parent terms; '|'-delimited list)
  5 ChemicalTreeNumbers (unique identifiers of the chemical's nodes; '|'-delimited list)
  6 ParentTreeNumbers (unique identifiers of the parent nodes; '|'-delimited list)
  7 Synonyms ('|'-delimited list)
*/
function CTD_chemicals()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		if($first) {
			if(($c = count($a) != 8)) {
				trigger_error("Expecting 8 fields, found $c!");return FALSE;
			}
			$first = false;
		}
	
		$this->GetNS()->ParsePrefixedName($a[1],$ns,$id);
		$mesh_id = "mesh:$id";
		$this->AddRDF($this->QQuadL($mesh_id,"rdfs:label", "$a[0] [$mesh_id]"));
		$this->AddRDF($this->QQuad($mesh_id, "rdf:type", "ctd_vocabulary:Chemical"));
		if($a[2]) $this->AddRDF($this->QQuad($mesh_id,"owl:equivalentClass","cas:$a[2]"));
	}
	return TRUE;
}


/* CURATED chemical-gene interactions
  0 ChemicalName
X 1 ChemicalID (MeSH accession identifier)
  2 CasRN
  3 GeneSymbol
X 4 GeneID (NCBI Gene or CTD accession identifier)
  5 GeneForms ('|' delimited)
  6 Organism (scientific name)
X 7 OrganismID (NCBI Taxonomy accession identifier)
x 8 Interaction
  9 InteractionActions ('|'-delimited list)
X 10 PubmedIDs ('|'-delimited list) */ 

function CTD_chem_gene_ixns() 
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		if($first) {
			if(($c = count($a)) != 11) {
				trigger_error("Expecting 11 fields, found $c!");return FALSE;
			}
			$first = false;
		}

		$mesh_id = $a[1];
		$gene_id = $a[4];
		$taxon_id = $a[7];
		$interaction_text = $a[8];
		$interaction_action = $a[9];
		$pubmed_ids = explode("|",trim($a[10]));
		foreach($pubmed_ids AS $i=> $pmid) {
			if(!is_int($pmid)) unset($pubmed_ids[$i]);
		}
		
		$uri  = "ctd_resource:$mesh_id$gene_id";  // should taxon be part of the ID?
		
		$this->AddRDF($this->QQuadL($uri,"rdfs:label","interaction between $a[3] (geneid:$gene_id) and $a[0] (mesh:$mesh_id) [$uri]"));
		$this->AddRDF($this->QQuad($uri,"rdf:type","ctd_vocabulary:Chemical-Gene-Association"));
		
		$this->AddRDF($this->QQuadL($uri,"rdfs:comment","$a[7]"));
		$this->AddRDF($this->QQuad($uri,"ctd_vocabulary:gene","geneid:$gene_id"));
		$this->AddRDF($this->QQuad($uri,"ctd_vocabulary:chemical","mesh:$mesh_id"));
		if($taxon_id) $this->AddRDF($this->QQuad($uri,"ctd_vocabulary:organism","taxon:$taxon_id"));
		if($pubmed_ids) foreach($pubmed_ids AS $pubmed_id) $this->AddRDF($this->QQuad($uri,"ctd_vocabulary:article","pubmed:$pubmed_id"));
		if($interaction_action) $this->AddRDF($this->QQuadL($uri,"ctd_vocabulary:action","$interaction_action"));
	}
	return TRUE;
}



/*
X 0 ChemicalName
X 1 ChemicalID (MeSH accession identifier)
  2 CasRN
X 3 DiseaseName
X 4 DiseaseID (MeSH or OMIM accession identifier)
  5 DirectEvidence
  6 InferenceGeneSymbol
x 7 InferenceScore
X 8 OmimIDs ('|'-delimited list)
X 9 PubmedIDs ('|'-delimited list)
*/
function CTD_chemicals_diseases()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
		
		if($first) {
			if(($c = count($a)) != 10) {
				trigger_error("Expecting 10 fields, found $c!");return FALSE;
			}
			$first = false;
		}
		
		$chemical_name = $a[0];
		$chemical_id = $a[1];
		$disease_name = $a[3];
		$disease = explode(":",$a[4]);
		$disease_ns = strtolower($disease[0]);
		$disease_id = $disease[1];
				
		$uid = "$chemical_id$disease_id";
		$uri = "ctd_resource:$uid";
		
		$this->AddRDF($this->QQuadL($uri, 'rdfs:label',"interaction between $chemical_name ($chemical_id) and disease $disease_name ($disease_ns:$disease_id) [$uri]"));
		$this->AddRDF($this->QQuad($uri, 'rdf:type', 'ctd_vocabulary:Chemical-Disease-Association'));
		$this->AddRDF($this->QQuad($uri, 'ctd_vocabulary:chemical', "mesh:$chemical_id"));
		$this->AddRDF($this->QQuad($uri, 'ctd_vocabulary:disease', "$disease_ns:$disease_id"));
		
		if($a[8])  {
			$omim_ids = explode("|",strtolower($a[8]));
			foreach($omim_ids AS $omim_id)     $this->AddRDF($this->QQuad($uri, 'ctd_vocabulary:disease', "omim:$omim_id"));
		}
		if(isset($a[9])) {
			$pubmed_ids = explode("|",trim($a[9]));
			foreach($pubmed_ids AS $pubmed_id) {
				if($pubmed_id) $this->AddRDF($this->QQuad($uri,'ctd_vocabulary:article', "pubmed:$pubmed_id"));
			}
		}
	}
	
	return TRUE;
}

/*
  0 ChemicalName
X 1 ChemicalID (MeSH accession identifier)
  2 CasRN
  3 PathwayName
X 4 PathwayID (KEGG or REACTOME  accession identifier)
  5 PValue
  6 CorrectedPValue
  7 TargetMatchQty
  8 TargetTotalQty
  9 BackgroundMatchQty
  10 BackgroundTotalQty
*/
function CTD_chem_pathways_enriched()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		
		// check number of columns
		$a = explode("\t",trim($l));
		if($first) {
			if(($c = count(explode("\t",$l))) != 11) {
				trigger_error("Expecting 11 fields, found $c!");
				return FALSE;
			}
			$first = false;
		}
		
		$chemical_id = $a[1];
		$this->GetNS()->ParsePrefixedName($a[4],$pathway_ns,$pathway_id);
		if($pathway_ns == "react") $pathway_ns = "reactome";
		
		$this->AddRDF($this->QQuad("mesh:$chemical_id","ctd_vocabulary:pathway","$pathway_ns:$pathway_id"));
	}
	
	return TRUE;
}

/*
  0 DiseaseName
X 1 DiseaseID (MeSH or OMIM accession identifier)
  2 Definition
  3 AltDiseaseIDs
  4 ParentIDs
  5 TreeNumbers
  6 ParentTreeNumbers
  7 Synonyms
*/
function CTD_diseases()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		// check number of columns
		if($first) {
			if(($c = count(explode("\t",$l))) != 8) {
				trigger_error("Expecting 8 fields, found $c!");
				return FALSE;
			}
			$first = false;
		}
		
		$this->GetNS()->ParsePrefixedName($a[1],$disease_ns,$disease_id);

		$uid = "$disease_ns:$disease_id";
		$this->AddRDF($this->QQuadL($uid,"rdfs:label","$a[0] [$uid]"));
		$this->AddRDF($this->QQuad($uid,"rdf:type", "ctd_vocabulary:Disease"));
		$this->AddRDF($this->QQuadL($uid,"dc:description","$a[2]"));
	}
	return TRUE;
}


/*
  0 DiseaseName
X 1 DiseaseID (MeSH or OMIM accession identifier)
  2 PathwayName
X 3 PathwayID (KEGG accession identifier)
  4 InferenceGeneSymbol
*/
function CTD_diseases_pathways()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		// check number of columns
		if($first) {
			if(($c = count(explode("\t",$l))) != 5) {
				trigger_error("Expecting 5 fields, found $c!");
				return FALSE;
			}
			$first = false;
		}
		
		$this->GetNS()->ParsePrefixedName($a[1],$disease_ns,$disease_id);
		$this->GetNS()->ParsePrefixedName($a[3],$pathway_ns,$pathway_id);
		if($pathway_ns == 'react') $pathway_ns = 'reactome';

		$this->AddRDF($this->QQuad("$disease_ns:$disease_id","ctd_vocabulary:pathway","$pathway_ns:$pathway_id"));
		
		// extra
		$this->AddRDF($this->QQuadL("$disease_ns:$disease_id","rdfs:label","$a[0] [$disease_ns:$disease_id]"));
		$this->AddRDF($this->QQuadL("$pathway_ns:$pathway_id","rdfs:label","$a[2] [$pathway_ns:$pathway_id]"));
	}
	return TRUE;
}


/*
  0 GeneSymbol
X 1 GeneID (NCBI Gene or CTD accession identifier)
  2 DiseaseName
X 3 DiseaseID (MeSH or OMIM accession identifier)
  4 DirectEvidence
  5 InferenceChemicalName
  6 InferenceScore
X 7 OmimIDs ('|'-delimited list)
X 8 PubmedIDs ('|'-delimited list)
*/
function CTD_genes_diseases()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		// check number of columns
		if($first) {
			if(($c = count(explode("\t",$l))) != 9) {
				trigger_error("Expecting 9 fields, found $c!");
				return FALSE;
			}
			$first = false;
		}
		
		$gene_name = $a[0];
		$gene_id = $a[1];
		$disease_name = $a[2];
		$this->GetNS()->ParsePrefixedName($a[3],$disease_ns,$disease_id);
		
		$uri = "ctd_resource:$gene_id$disease_id";
		
		$this->AddRDF($this->QQuadL($uri,"rdfs:label","$gene_name (geneid:$gene_id) - $disease_name ($disease_ns:$disease_id) association [$uri]"));
		$this->AddRDF($this->QQuad($uri,"rdf:type","ctd_vocabulary:Gene-Disease-Association"));
		
		$this->AddRDF($this->QQuad($uri,"ctd_vocabulary:gene","geneid:$gene_id"));
		$this->AddRDF($this->QQuad($uri,"ctd_vocabulary:disease","$disease_ns:$disease_id"));
		if($a[7]) {
			$omim_ids = explode("|",$a[7]);			
			foreach($omim_ids AS $omim_id) $this->AddRDF($this->QQuad($uri,"ctd_vocabulary:disease","omim:$omim_id"));
		}
		if(isset($a[8])) {
			$pubmed_ids = explode("|",trim($a[8]));
			foreach($pubmed_ids AS $pubmed_id) {
				if(!is_numeric($pubmed_id)) continue;
				$this->AddRDF($this->QQuad($uri,"ctd_vocabulary:article","pubmed:$pubmed_id"));
			}
		}
		$this->WriteRDFBufferToWriteFile();
	}
	return TRUE;
}

/*
  0 GeneSymbol
X 1 GeneID (NCBI Gene or CTD accession identifier)
x 2 PathwayName
X 3 PathwayID (KEGG accession identifier)
*/
function CTD_genes_pathways()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		// check number of columns
		if($first) {
			if(($c = count(explode("\t",$l))) != 4) {
				trigger_error("Expecting 4 fields, found $c!");
				return FALSE;
			}
			$first = false;
		}
		
		$gene_ns = 'geneid';
		$gene_id = $a[1];
		$this->GetNS()->ParsePrefixedName($a[3],$pathway_ns,$pathway_id);
		$pathway_id = trim($pathway_id);
		if($pathway_ns == "react") $pathway_ns = "reactome";

		$this->AddRDF($this->QQuad("$gene_ns:$gene_id","ctd_vocabulary:pathway","$pathway_ns:$pathway_id"));
		
		// extra
		$this->AddRDF($this->QQuadL("$pathway_ns:$pathway_id","rdfs:label","$a[2] [$pathway_ns:$pathway_id]"));
		$this->AddRDF($this->QQuadL("$gene_ns:$gene_id","rdfs:label","gene ".str_replace(array("\/", "'"), array("/", "\\\'"), ($a[0]))." [$gene_ns:$gene_id]"));
	}
	return TRUE;
}

/*
PathwayName
PathwayID (KEGG or REACTOME accession identifier)
*/
function CTD_Pathways()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		// check number of columns
		if($first) {
			if(($c = count(explode("\t",$l))) != 2) {
				trigger_error("Expecting 2 fields, found $c!");
				return FALSE;
			}
			$first = false;
		}
		
		$this->GetNS()->ParsePrefixedName(trim($a[1]),$pathway_ns,$pathway_id);	
		if($pathway_ns == "react") $pathway_ns = "reactome";		
		
		$this->AddRDF($this->QQuadL("$pathway_ns:$pathway_id","rdfs:label","$a[0] [$pathway_ns:$pathway_id]"));
		$this->AddRDF($this->QQuadL("$pathway_ns:$pathway_id","rdf:type","ctd_vocabulary:Pathway"));
	}	
	return TRUE;
}

/*
0 GeneSymbol
1 GeneName
2 GeneID (primary NCBI Gene accession identifier)
3 AltGeneIDs (alternative NCBI Gene accession identifiers; '|'-delimited list)
4 Synonyms ('|'-delimited list)
*/
function CTD_Genes()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		// check number of columns
		if($first) {
			if(($c = count(explode("\t",$l))) != 5) {
				trigger_error("Expecting 5 fields, found $c!");
				return FALSE;
			}
			$first = false;
		}
		
		$symbol = str_replace(array("\\/"),array('|'),$a[0]);
		$label = str_replace("\\+/",'+',$a[1]);
		$geneid = $a[2];
		
		$this->AddRDF($this->QQuadL("geneid:$geneid","rdfs:label","$label [geneid:$geneid]"));
		$this->AddRDF($this->QQuad("geneid:$geneid","rdf:type","ctd_vocabulary:Gene"));
		$this->AddRDF($this->QQuadL("geneid:$geneid","ctd_vocabulary:gene-symbol",$symbol));
	}	
	return TRUE;
}

/*
  0 ChemicalName
* 1 ChemicalID (MeSH accession identifier)
  2 CasRN
  3 Ontology
  4 GOTermName
* 5 GOTermID
  6 HighestGOLevel
  7 PValue
  8 CorrectedPValue
  9 TargetMatchQty
  10 TargetTotalQty
  11 BackgroundMatchQty
  12 BackgroundTotalQty
*/

function CTD_chem_go_enriched()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);

		// check number of columns
		if($first) {
			if(($c = count(explode("\t",$l))) != 13) {
				trigger_error("Expecting 13 fields, found $c!");
				return FALSE;
			}
			$first = false;
		}		
		
		$this->GetNS()->ParsePrefixedName($a[5],$go_ns,$go_id);
		$rel = "involved-in";
		if($a[3] == "Biological Process") $rel = "is-participant-in";
		elseif($a[3] == "Molecular Function") $rel = "has-function";
		elseif($a[3] == "Cellular Component") $rel = "is-located-in";

		$this->AddRDF($this->QQuad("mesh:$a[1]","ctd_vocabulary:$rel","$go_ns:$go_id"));
	}	
	return TRUE;
}

/*
0 TypeName
1 Code
2 Description
3 ParentCode
*/
function CTD_chem_gene_ixn_types()
{
	$first = true;
	while($l = $this->GetReadFile()->Read()) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);

		// check number of columns
		if($first) {
			if(($c = count(explode("\t",$l))) != 4) {
				trigger_error("Expecting 4 fields, found $c!");
				return FALSE;
			}
			$first = false;
		}
		$id = "ctd_vocabulary:$a[1]";
		$this->AddRDF($this->QQuadL($id,"rdfs:label",$a[0]." [$id]"));
		$this->AddRDF($this->QQuadL($id,"dc:description",$a[2]));
		if(isset($a[4]))
			$this->AddRDF($this->QQuad($id,"rdfs:subClassOf","ctd_vocabulary:$a[4]"));

	}	
	return TRUE;
}

} // end class


set_error_handler('error_handler');
$parser = new CTDParser($argv);
$parser->Run();

?>
