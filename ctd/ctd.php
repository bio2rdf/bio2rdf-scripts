<?php
/**
Copyright (C) 2013 Michel Dumontier, Alison Callahan

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

require('/home/alison/git/php-lib/bio2rdfapi.php');

/** 
 * Parser for CTD: The Comparative Toxicogenomics Database (http://ctd.mdibl.org/)
 * documentation: http://ctdbase.org/downloads
 * download pattern: http://ctd.mdibl.org/reports/XXXX.tsv.gz
*/
class CTDParser extends Bio2RDFizer 
{
	private $version = null;

	function __construct($argv) {

		parent::__construct($argv,"ctd");
		parent::addParameter('files',true,'all|chem_gene_ixns|chem_gene_ixn_types|chemicals_diseases|chem_go_enriched|chem_pathways_enriched|genes_diseases|genes_pathways|diseases_pathways|chemicals|diseases|genes|pathways','all','all or comma-separated list of files to process');
		parent::addParameter('download_url',false,null,'http://ctdbase.org/reports/');
		parent::initialize();

		return TRUE;
	}
	
	function Run()
	{

		// get the file list
		if(parent::getParameterValue('files') == 'all') {
			$files = explode("|",parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",parent::getParameterValue('files'));
		}

		//set directory values
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$rdir = parent::getParameterValue('download_url');
		
		//make sure directories end with slash
		if(substr($ldir, -1) !== "/"){
			$ldir = $ldir."/";
		}
		
		if(substr($odir, -1) !== "/"){
			$odir = $odir."/";
		}

		$gz_suffix = ".gz";		

		foreach($files AS $file) {

			$lfile = $ldir.$file.$gz_suffix;
			$ofile = $odir."ctd_".$file.".nt";
			$gz = false;

			if($this->GetParameterValue('graph_uri')) {
				$ofile = $odir."ctd_".$file.'.nq';
			}

			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$ofile .= '.gz';
				$gz = true;
			}			
			
			if(!file_exists($lfile)) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
			}
			
			if(parent::getParameterValue('download') == true) {
				if($file == 'chem_gene_ixn_types') $suffix = '.tsv';
				else if($file == 'exposure_ontology') $suffix = '.obo';
				else $suffix = ".tsv.gz";
				
				$rfile = $rdir.'CTD_'.$file.$suffix;
				if($suffix == ".tsv.gz") {
					Utils::DownloadSingle ($rfile, $lfile);
				} else {
					Utils::DownloadSingle ($rfile, "compress.zlib://".$lfile);
				}
			}

			echo "Processing ".$file." ...";
			parent::setWriteFile($ofile, $gz);

			//set read file
			parent::setReadFile($lfile, TRUE);
	
			$fnx = "CTD_".$file;
			if($this->$fnx() === FALSE) {
				trigger_error("Error in $fnx");
				exit;
			}
			
			//write RDF to file
			parent::writeRDFBufferToWriteFile();

			//close write file
			parent::getWriteFile()->close();
			echo "Done!".PHP_EOL;

		}//foreach

		// generate the dataset release file
		echo "generating dataset release file... ";
		$desc = parent::getBio2RDFDatasetDescription(
			$this->getPrefix(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/sgd/sgd.php", 
			$this->getBio2RDFDownloadURL($this->getNamespace()),
			"http://ctdbase.org", 
			array("use", "no-commercial"),
			"http://ctdbase.org/about/legal.jsp",
			parent::getParameterValue('download_url'),
			parent::getDatasetVersion()
		);
		$this->setWriteFile($odir.$this->getBio2RDFReleaseFile($this->getNamespace()));
		$this->getWriteFile()->write($desc);
		$this->getWriteFile()->close();
		echo "done!".PHP_EOL;
		
		return TRUE;
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
	
		$this->getRegistry()->parseQName($a[1],$ns,$id);
		
		$mesh_id = "mesh:".$id;

		$this->AddRDF(
			parent::describeIndividual($mesh_id, $a[0], $this->getVoc()."Chemical").
			parent::describeClass($this->getVoc()."Chemical", "CTD Chemical")
		);

		if($a[2]){
			$this->AddRDF(
				parent::triplify($mesh_id, "owl:equivalentClass", "cas:".$a[2])
			);
		}
		parent::WriteRDFBufferToWriteFile();
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
		
		$uri  = $this->getRes().$mesh_id.$gene_id;  // should taxon be part of the ID?
		
		$this->AddRDF(
			parent::describeIndividual($uri, "association between ".$a[3]." (geneid:$gene_id) and ".$a[0]." (mesh:$mesh_id)", $this->getVoc()."Chemical-Gene-Association").
			parent::triplifyString($uri, "rdfs:comment", $a[7]).
			parent::triplify($uri, $this->getVoc()."gene", "geneid:".$gene_id).
			parent::triplify($uri, $this->getVoc()."chemical", "mesh:".$mesh_id).
			parent::describeProperty($this->getVoc()."gene", "Relation bteween a CTD entity and a gene").
			parent::describeProperty($this->getVoc()."chemical", "Relation between a CTD entity and a chemical").
			parent::describeClass($this->getVoc()."Chemical-Gene-Association", "A CTD association between a chemical and a gene")
		);

		if($taxon_id){
			$this->AddRDF(
				parent::triplify($uri, $this->getVoc()."organism", "taxon:".$taxon_id).
				parent::describeProperty($this->getVoc()."organism", "Relation between a CTD entity and the organism it was observed in")
			);
		}

		if($pubmed_ids){
			foreach ($pubmed_ids as $pubmed_id) {
				$this->AddRDF(
					parent::triplify($uri, $this->getVoc()."article", "pubmed".$pubmed_id).
					parent::describeProperty($this->getVoc()."article", "Relation between a CTD entity and a published article")
				);
			}
		}

		if($interaction_action){
			$this->AddRDF(
				parent::triplifyString($uri, $this->getVoc()."action", $interaction_action).
				parent::describeProperty($this->getVoc()."action", "Relation between a CTD entity and its resulting action")
			);
		}
		parent::WriteRDFBufferToWriteFile();
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
		$this->WriteRDFBufferToWriteFile();

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
		$uri = $this->getRes().$uid;
		
		$this->AddRDF(
			parent::describeIndividual($uri, "association between $chemical_name ($chemical_id) and disease $disease_name ($disease_ns:$disease_id)", $this->getVoc()."Chemical-Disease-Association").
			parent::triplify($uri, $this->getVoc()."chemical", "mesh:".$chemical_id).
			parent::triplify($uri, $this->getVoc()."disease", $disease_ns.":".$disease_id).
			parent::describeClass($this->getVoc()."Chemical-Disease-Association", "A CTD association between a chemical and a disease").
			parent::describeProperty($this->getVoc()."chemical", "Relation between a CTD entity and a chemical").
			parent::describeProperty($this->getVoc()."disease", "Relation between a CTD entity and a disease")
		);

		if($a[8])  {
			$omim_ids = explode("|",strtolower($a[8]));
			foreach($omim_ids as $omim_id){
				$this->AddRDF(
					parent::triplify($uri, $this->getVoc()."disease", "omim:".$omim_id)
				);
			}
		}

		if(isset($a[9])) {
			$pubmed_ids = explode("|",trim($a[9]));
			foreach($pubmed_ids as $pubmed_id){
				$this->AddRDF(
					parent::triplify($uri, $this->getVoc()."article", "pubmed:".$pubmed_id)
				);
			}
		}
		parent::WriteRDFBufferToWriteFile();
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

		$this->getRegistry()->parseQName($a[4], $pathway_ns, $pathway_id);
		if($pathway_ns == "react") $pathway_ns = "reactome";
		
		$this->AddRDF(
			parent::triplify("mesh:".$chemical_id, $this->getVoc()."pathway", $pathway_ns.":".$pathway_id).
			parent::describeProperty($this->getVoc()."pathway", "Relation between a CTD entity and a pathway it is associated with")
		);
		parent::WriteRDFBufferToWriteFile();
	}
	
	return TRUE;
}

/*
  0 DiseaseName
X 1 DiseaseID (MeSH or OMIM accession identifier)
  2 AltDiseaseIDs
  3 Definition
  4 ParentIDs
  5 TreeNumbers
  6 ParentTreeNumbers
  7 Synonyms
  8 SlimMappings

*/
function CTD_diseases()
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
		
		$this->getRegistry()->parseQName($a[1],$disease_ns,$disease_id);
		$uid = "$disease_ns:$disease_id";

		$this->AddRDF(
			parent::describeIndividual($uid, $a[0], $this->getVoc()."Disease", null, $a[3]).
			parent::describeClass($this->getVoc()."Disease", "CTD Disease")
		);
		parent::WriteRDFBufferToWriteFile();
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
		
		$this->getRegistry()->parseQName($a[1],$disease_ns,$disease_id);
		$this->getRegistry()->parseQName($a[3],$pathway_ns,$pathway_id);
		if($pathway_ns == 'react') $pathway_ns = 'reactome';

		$this->AddRDF(
			parent::triplify($disease_ns.":".$disease_id, $this->getVoc()."pathway", $pathway_ns.":".$pathway_id).
			parent::triplifyString($disease_ns.":".$disease_id, "rdfs:label", $a[0]." [$disease_ns:$disease_id]").
			parent::triplifyString($pathway_ns.":".$pathway_id, "rdfs:label", $a[2]." [$pathway_ns:$pathway_id]")
		);
		parent::WriteRDFBufferToWriteFile();
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

		$this->getRegistry()->parseQName($a[3],$disease_ns,$disease_id);
		
		$uri = $this->getRes().$gene_id.$disease_id;

		$this->AddRDF(
			parent::describeIndividual($uri, "$gene_name (geneid:$gene_id) - $disease_name ($disease_ns:$disease_id) association", $this->getVoc()."Gene-Disease-Association").
			parent::triplify($uri, $this->getVoc()."gene", "geneid:".$gene_id).
			parent::triplify($uri, $this->getVoc()."disease", $disease_ns.":".$disease_id)
		);
		
		if($a[7]) {
			$omim_ids = explode("|",$a[7]);
			foreach($omim_ids as $omim_id){
				$this->AddRDF(
					parent::triplify($uri, $this->getVoc()."disease", "omim:".$omim_id)
				);
			}			
		}

		if(isset($a[8])) {
			$pubmed_ids = explode("|",trim($a[8]));
			foreach($pubmed_ids AS $pubmed_id) {
				if(!is_numeric($pubmed_id)) continue;
				$this->AddRDF(
					parent::triplify($uri, $this->getVoc()."article", "pubmed:".$pubmed_id)
				);
			}
		}
		parent::WriteRDFBufferToWriteFile();
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
		$this->getRegistry()->parseQName($a[3],$pathway_ns,$pathway_id);
		$pathway_id = trim($pathway_id);
		if($pathway_ns == "react") $pathway_ns = "reactome";

		$this->ADDRDF(
			parent::triplify($gene_ns.":".$gene_id, $this->getVoc()."pathway", $pathway_ns.":".$pathway_id).
			parent::triplifyString($gene_ns.":".$gene_id, "rdfs:label", "gene ".str_replace(array("\/", "'"), array("/", "\\\'"), ($a[0]))." [$gene_ns:$gene_id]").
			parent::triplifyString($pathway_ns.":".$pathway_id, "rdfs:label", $a[2]." [$pathway_ns:$pathway_id]")
		);
		parent::WriteRDFBufferToWriteFile();
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
		
		$this->getRegistry()->parseQName(trim($a[1]),$pathway_ns,$pathway_id);	
		if($pathway_ns == "react") $pathway_ns = "reactome";	

		$this->AddRDF(
			parent::describeIndividual($pathway_ns.":".$pathway_id, $a[0], $this->getVoc()."Pathway").
			parent::describeClass($this->getVoc()."Pathway", "CTD Pathway")	
		);
		parent::WriteRDFBufferToWriteFile();

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

		$this->AddRDF(
			parent::describeIndividual("geneid:".$geneid, $label, $this->getVoc()."Gene").
			parent::triplifyString("geneid:".$geneid, $this->getVoc()."gene-symbol", $symbol).
			parent::describeClass($this->getVoc()."Gene", "CTD Gene").
			parent::describeProperty($this->getVoc()."gene-symbol", "Relation between a gene and its symbol in CTD")
		);
		parent::WriteRDFBufferToWriteFile();
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
		
		$this->getRegistry()->parseQName($a[5], $go_ns, $go_id);
		$rel = "involved-in";
		if($a[3] == "Biological Process") $rel = "is-participant-in";
		elseif($a[3] == "Molecular Function") $rel = "has-function";
		elseif($a[3] == "Cellular Component") $rel = "is-located-in";

		$this->AddRDF(
			parent::triplify("mesh:".$a[1], $this->getVoc().$rel, $go_ns.":".$go_id).
			parent::describeProperty($this->getVoc().$rel, str_replace("-", " ", $rel))
		);
		parent::WriteRDFBufferToWriteFile();
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
		$id = $this->getVoc().$a[1];
		
		$parent = trim($a[3]);
		if(isset($parent) && !empty($parent)){
			$this->AddRDF(
				parent::describeClass($id, $a[0], $this->getVoc().$parent, null, $a[2])
			);
		} else {
			$this->AddRDF(
				parent::describeClass($id, $a[0], null, null, $a[2])
			);
		}
		parent::WriteRDFBufferToWriteFile();
	}	
	return TRUE;
}

} // end class

$start = microtime(true);

set_error_handler('error_handler');
$parser = new CTDParser($argv);
$parser->Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"


?>
