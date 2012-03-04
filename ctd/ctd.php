<?php
/**
Copyright (C) 2011 Michel Dumontier

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


require (dirname(__FILE__).'/../common/php/libphp.php');

/** Show/Set command line parameters **/
$options = null;
AddOption($options, 'indir', null, '/data/download/ctd/', false);
AddOption($options, 'outdir',null, '/data/rdf/ctd/', false);
AddOption($options, 'download','true|false', 'false', false);
AddOption($options, 'remote_base_url',null,'http://ctd.mdibl.org/reports/', false);
// exposure_ontology 
AddOption($options, 'files','all|chem_gene_ixns|chemicals_diseases|genes_diseases|chem_pathways_enriched|diseases_pathways|genes_pathways|chem_go_enriched|chemicals|diseases|genes|pathways|chem_gene_ixn_types','all',true);
AddOption($options, CONF_FILE_PATH, null,'/bio2rdf-scripts/common/bio2rdf_conf.rdf', false);
AddOption($options, USE_CONF_FILE,'true|false','false', false);

if(SetCMDlineOptions($argv, $options) == FALSE) {
	PrintCMDlineOptions($argv, $options);
	exit;
}

@mkdir($options['indir']['value'],null,true);
@mkdir($options['outdir']['value'],null,true);
$infile_suffix = ".tsv.gz";
$outfile_suffix = ".n3.gz";

if($options['files']['value'] == 'all') {
	$files = explode("|",$options['files']['list']);
	array_shift($files);
} else {
	$files = explode("|",$options['files']['value']);
}

if($options['download']['value'] == 'true') {
 foreach($files AS $file) {
	if($file == 'chem_gene_ixn_types') $suffix = '.tsv';
	else if($file == 'exposure_ontology') $suffix = '.obo';
	else $suffix = $infile_suffix;
	
	$f = $options['remote_base_url']['value'].'CTD_'.$file.$suffix;
	$l = $options['indir']['value'].$file.$suffix;
	echo "Downloading $f to $l\n";
	copy($f, $l);
 }
}

foreach($files AS $file) {
	if($file == 'chem_gene_ixn_types') $suffix = '.tsv';
	else if($file == 'exposure_ontology') $suffix = '.obo';
	else $suffix = $infile_suffix;
	
	$infile = $options['indir']['value'].$file.$suffix;
	$outfile = $options['outdir']['value'].$file.$outfile_suffix;
	
	echo "Processing ".$infile." ...";
	
	$infp = gzopen($infile,"r");
	if(!$infp) {trigger_error("Unable to open $infile");exit;}
	$outfp = gzopen($outfile,"w");
	if(!$outfp) {trigger_error("Unable to open $outfile");exit;}
	
	
	$fnx = "CTD_".$file;
	if(isset($fnx)) {
		if($fnx($infp,$outfp)) {
		 	trigger_error("Error in $fnx");
			exit;
        }
	}
	echo "Converted to $outfile\n";
	
	gzclose($infp);
	gzclose($outfp);
}


/*
Fields:
x 0 ChemicalName
x 1 ChemicalID (MeSH accession identifier)
x 2 CasRN
  3 ParentIDs (accession identifiers of the parent terms; '|'-delimited list)
  4 ChemicalTreeNumbers (unique identifiers of the chemical's nodes; '|'-delimited list)
  5 ParentTreeNumbers (unique identifiers of the parent nodes; '|'-delimited list)
  6 Synonyms ('|'-delimited list)
*/
function CTD_chemicals($infp, $outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();

	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		list($name,$mid,$casrn) = explode("\t",trim($l));
		$m=explode(":",$mid);
		$mid = $m[1];
		$name = addslashes($name);

		$buf .= QQuadL("mesh:$mid","dc:identifier", "mesh:$mid");
		$buf .= QQuadL("mesh:$mid","dc:title",$name);
		$buf .= QQuadL("mesh:$mid","rdfs:label","$name [mesh:$mid]");
		
		$buf .= QQuad("mesh:$mid", "rdf:type", "ctd_vocabulary:Chemical");
		if($casrn) $buf .= QQuad("mesh:$mid","owl:equivalentClass","cas:$casrn");
		$buf .= QQuad("mesh:$mid","ctd_vocabulary:in","registry_dataset:ctd");
	}
	gzwrite($outfp,$buf);
	return 0;
}


/* CURATED chemical-gene interactions
  0 ChemicalName
X 1 ChemicalID (MeSH accession identifier)
  2 CasRN
  3 GeneSymbol
X 4 GeneID (NCBI Gene or CTD accession identifier)
  5 Organism (scientific name)
X 6 OrganismID (NCBI Taxonomy accession identifier)
x 7 Interaction
  8 InteractionActions ('|'-delimited list)
X 9 PubmedIDs ('|'-delimited list) */ 

function CTD_chem_gene_ixns($infp, $outfp) {
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		$mid = $a[1];
		$gene_id = $a[4];
		$taxon_id = $a[6];
		$interaction_text = $a[7];
		$interaction_action = $a[8];
		$pubmed_ids = explode("|",trim($a[9]));
		foreach($pubmed_ids AS $i=> $pmid) {
			if(!is_int($pmid)) unset($pubmed_ids[$i]);
		}
		
		$uri  = "ctd_resource:$mid$gene_id";  // should taxon be part of the ID?
		
		$buf .= QQuadL($uri,"dc:identifier","ctd_resource:$mid$gene_id");
		$buf .= QQuadL($uri,"rdfs:label","interaction between $a[3] (geneid:$gene_id) and $a[0] (mesh:$mid) [ctd:$mid$gene_id]");
		$buf .= QQuad($uri,"rdf:type","ctd_vocabulary:ChemicalGeneInteraction");
		
		$buf .= QQuadL($uri,"rdfs:comment","$a[7]");
		$buf .= QQuad($uri,"ctd_vocabulary:gene","geneid:$gene_id");
		$buf .= QQuad($uri,"ctd_vocabulary:chemical","mesh:$mid");
		if($taxon_id) $buf .= QQuad($uri,"ctd_vocabulary:organism","taxon:$taxon_id");
		if($pubmed_ids) foreach($pubmed_ids AS $pubmed_id) $buf .= QQuad($uri,"ctd_vocabulary:article","pubmed:$pubmed_id");
		if($interaction_action) $buf .= QQuadL($uri,"ctd_vocabulary:action","$interaction_action");
		//break;
	
//		echo $buf;exit;
	}
	gzwrite($outfp,$buf);
	return 0;
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
function CTD_chemicals_diseases($infp, $outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
		$chemical_name = $a[0];
		$chemical_id = $a[1];
		$disease_name = $a[3];
		$disease = explode(":",$a[4]);
		$disease_ns = strtolower($disease[0]);
		$disease_id = $disease[1];
				
		$uid = "$chemical_id$disease_id";
		$uri = "ctd_resource:$uid";
		
		$buf .= QQuadL($uri, 'dc:identifier', "ctd_resource:$uid");
		$buf .= QQuadL($uri, 'rdfs:label',"interaction between $chemical_name ($chemical_id) and disease $disease_name ($disease_ns:$disease_id) [$uri]").PHP_EOL;
		$buf .= QQuad($uri, 'rdf:type', 'ctd_vocabulary:ChemicalDiseaseInteraction');
		$buf .= QQuad($uri, 'ctd_vocabulary:chemical', "mesh:$chemical_id");
		$buf .= QQuad($uri, 'ctd_vocabulary:disease', "$disease_ns:$disease_id");
		
		if($a[8])  {
			$omim_ids = explode("|",strtolower($a[8]));
			foreach($omim_ids AS $omim_id)     $buf .= QQuad($uri, 'ctd_vocabulary:disease', "omim:$omim_id");
		}
		if(isset($a[9])) {
			$pubmed_ids = explode("|",$a[9]);
			foreach($pubmed_ids AS $pubmed_id) {
				if($pubmed_id) $buf .= QQuad($uri,'ctd_vocabulary:article', "pubmed:$pubmed_id");
			}
		}
		gzwrite($outfp,$buf);
		$buf = '';
	}
	
	return 0;
}

/*
  0 ChemicalName
X 1 ChemicalID (MeSH accession identifier)
  2 CasRN
  3 PathwayName
X 4 PathwayID (KEGG or REACTOME  accession identifier)
  5 EnrichmentScore
  6 TargetMatchQty
  7 TargetTotalQty
  8 BackgroundMatchQty
  9 BackgroundTotalQty
*/
function CTD_chem_pathways_enriched($infp, $outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
		$chemical_id = $a[1];
		ParseQNAME($a[4],$pathway_ns,$pathway_id);
		if($pathway_ns == "react") $pathway_ns = "reactome";
		
		$buf .= QQuad("mesh:$chemical_id","ctd_vocabulary:pathway","$pathway_ns:$pathway_id");
	}
	
	gzwrite($outfp,$buf);
	return 0;
}

/*
  0 DiseaseName
X 1 DiseaseID (MeSH or OMIM accession identifier)
  2 AltDiseaseIDs
  3 ParentIDs
  4 DiseaseTreeNumbers
  5 ParentTreeNumbers
  6 Synonyms
*/
function CTD_diseases($infp, $outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
		ParseQNAME($a[1],$disease_ns,$disease_id);

		$uid = "$disease_ns:$disease_id";
		$buf .= QQuadL($uid,"rdfs:label","$a[0] [$uid]");
		$buf .= QQuad($uid,"rdf:type", "ctd_vocabulary:Disease");
		
		//echo $buf;exit;
	}
	
	gzwrite($outfp,$buf);
	return 0;
}


/*
  DiseaseName
X DiseaseID (MeSH or OMIM accession identifier)
  PathwayName
X PathwayID (KEGG accession identifier)
  InferenceGeneSymbol
*/
function CTD_diseases_pathways($infp, $outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
		ParseQNAME($a[1],$disease_ns,$disease_id);
		ParseQNAME($a[3],$pathway_ns,$pathway_id);
		if($pathway_ns == 'react') $pathway_ns = 'reactome';

		$buf .= QQuad("$disease_ns:$disease_id","ctd_vocabulary:pathway","$pathway_ns:$pathway_id");
		
		// extra
		$buf .= QQuadL("$disease_ns:$disease_id","dc:identifer","$disease_ns:$disease_id");
		$buf .= QQuadL("$disease_ns:$disease_id","rdfs:label","$a[0] [$disease_ns:$disease_id]");
		$buf .= QQuadL("$pathway_ns:$pathway_id","dc:identifer","$pathway_ns:$pathway_id");
		$buf .= QQuadL("$pathway_ns:$pathway_id","rdfs:label","$a[2] [$pathway_ns:$pathway_id]");
	}
	
	gzwrite($outfp,$buf);
	return 0;
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
function CTD_genes_diseases($infp, $outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
		$gene_name = $a[0];
		$gene_ns = 'geneid';
		$gene_id = $a[1];
		$disease_name = $a[2];
		ParseQNAME($a[3],$disease_ns,$disease_id);
		
		$uri = "ctd_resource:$gene_id$disease_id";
		
		$buf .= QQuad($uri,"rdf:type","ctd_vocabulary:GeneDiseaseInteraction");
		$buf .= QQuadL($uri,"dc:identifier","$uri");
		$buf .= QQuadL($uri,"rdfs:label","Gene Disease interaction between $gene_name ($gene_ns:$gene_id) and $disease_name ($disease_ns:$disease_id) [$uri]");
		$buf .= QQuad($uri,"ctd_vocabulary:gene","$gene_ns:$gene_id");
		$buf .= QQuad($uri,"ctd_vocabulary:disease","$disease_ns:$disease_id");
		if($a[7]) {
			$omim_ids = explode("|",$a[7]);			
			foreach($omim_ids AS $omim_id)    $buf .= QQuad($uri,"ctd_vocabulary:disease","omim:$omim_id");
		}
		if(isset($a[8])) {
			$pubmed_ids = explode("|",$a[8]);
			foreach($pubmed_ids AS $pubmed_id) {
				if(!is_numeric($pubmed_id)) continue;
				$buf .= QQuad($uri,"ctd_vocabulary:article","pubmed:$pubmed_id");
			}
		}
		
		gzwrite($outfp,$buf);
		$buf = '';
	}
	
	gzwrite($outfp,$buf);
	return 0;
}

/*
  0 GeneSymbol
X 1 GeneID (NCBI Gene or CTD accession identifier)
x 2 PathwayName
X 3 PathwayID (KEGG accession identifier)
*/
function CTD_genes_pathways($infp, $outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
		$gene_ns = 'geneid';
		$gene_id = $a[1];
		ParseQNAME($a[3],$pathway_ns,$pathway_id);
		$kegg_id = strtolower($a[3]);
		if($pathway_ns == "react") $pathway_ns = "reactome";

		$buf .= QQuad("$gene_ns:$gene_id","ctd_vocabulary:pathway","$pathway_ns:$pathway_id");
		
		// extra
		$buf .= QQuadL("$pathway_ns:$pathway_id","dc:identifer","$pathway_ns:$pathway_id");
		$buf .= QQuadL("$pathway_ns:$pathway_id","rdfs:label","$a[2] [$pathway_ns:$pathway_id]");
		$buf .= QQuadL("$gene_ns:$gene_id","dc:identifer","$gene_ns:$gene_id");
		$buf .= QQuadL("$gene_ns:$gene_id","rdfs:label","gene ".str_replace(array("\/", "'"), array("/", "\\\'"), ($a[0]))." [$gene_ns:$gene_id]");

//echo $buf;exit;
	}
	
	gzwrite($outfp,$buf);
	return 0;
}

/*
PathwayName
PathwayID (KEGG or REACTOME accession identifier)
*/
function CTD_Pathways($infp, $outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
		
		ParseQNAME($a[1],$pathway_ns,$pathway_id);	
		if($pathway_ns == "react") $pathway_ns = "reactome";		
		
		$buf .= QQuadL("$pathway_ns:$pathway_id","dc:identifer","$pathway_ns:$pathway_id");
		$buf .= QQuadL("$pathway_ns:$pathway_id","rdfs:label","$a[0] [$pathway_ns:$pathway_id]");

//echo $buf;exit;
	}	
	gzwrite($outfp,$buf);
	return 0;
}

/*
0 GeneSymbol
1 GeneName
2 GeneID (primary NCBI Gene accession identifier)
3 AltGeneIDs (alternative NCBI Gene accession identifiers; '|'-delimited list)
4 Synonyms ('|'-delimited list)
*/
function CTD_Genes($infp, $outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",$l);
		
		$symbol = str_replace("\\/",'|',$a[0]);
		$label = str_replace("\\+/",'+',$a[1]);
		$geneid = $a[2];
		
		$buf .= QQuadL("geneid:$geneid","dc:identifer","geneid:$geneid");
		$buf .= QQuadL("geneid:$geneid","rdfs:label","$label [geneid:$geneid]");
		$buf .= QQuadL("geneid:$geneid","ctd_vocabulary:symbol",$symbol);

//echo $buf;exit;
	}	
	gzwrite($outfp,$buf);
	return 0;
}

/*
  0 ChemicalName
* 1 ChemicalID (MeSH accession identifier)
  2 CasRN
  3 Ontology
  4 GOTermName
* 5 GOTermID
  6 HighestGOLevel
  7 EnrichmentScore
  8 TargetMatchQty
  9 TargetTotalQty
  10 BackgroundMatchQty
  11 BackgroundTotalQty
*/

function CTD_chem_go_enriched($infp,$outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
				
		ParseQNAME($a[5],$go_ns,$go_id);
		$rel = "involved-in";
		if($a[3] == "Biological Process") $rel = "is-participant-in";
		elseif($a[3] == "Molecular Function") $rel = "has-function";
		elseif($a[3] == "Cellular Component") $rel = "is-located-in";

		$buf .= QQuad("mesh:$a[1]","ctd_vocabulary:$rel","$go_ns:$go_id");

//echo $buf;exit;
	}	
	gzwrite($outfp,$buf);
	return 0;
}

/*
TypeName
Code
Description
ParentCode
*/
function CTD_chem_gene_ixn_types($infp,$outfp)
{
	require_once (dirname(__FILE__).'/../common/php/libphp.php');
	$buf = N3NSHeader();
	
	gzgets($infp);
	while($l = gzgets($infp)) {
		if($l[0] == '#') continue;
		$a = explode("\t",trim($l));
				
		$buf .= QQuadL("ctd_vocabulary:$a[1]","rdfs:label",$a[0]);
		$buf .= QQuadL("ctd_vocabulary:$a[1]","dc:identifier","ctd_vocabulary:".$a[1]);
		$buf .= QQuadL("ctd_vocabulary:$a[1]","dc:description",$a[2]);
		if(isset($a[4]))
			$buf .= QQuad("ctd_vocabulary:$a[1]","rdfs:subClassOf","ctd_vocabulary:$a[4]");
//echo $buf;exit;
	}	
	gzwrite($outfp,$buf);
	return 0;
}




?>
