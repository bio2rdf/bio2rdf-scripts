<?php
/**
Copyright (C) 2012 Michel Dumontier

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
 * BioPAX RDFizer
 * @version 1.0
 * @author Michel Dumontier
 * @description 
*/
require_once(__DIR__.'/../../php-lib/rdfapi.php');
class BioPAXParser extends RDFFactory 
{		
	function __construct($argv) {
		parent::__construct();
		
		// set and print application parameters
		$this->AddParameter('files',true,null,'all','entries to process: comma-separated list or hyphen-separated range');
		$this->AddParameter('indir',false,null,'/data/download/biopax/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/biopax/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		
		return TRUE;
	}
	
	function Run()
	{	
		$endpoint = "http://bio2rdf.semanticscience.org:8010/sparql";
		$data = "cpath";
		$bio2rdf_uri = "http://bio2rdf.org/$data:";
		if($data == "cpath") {
			$base_uri = "http://cbio.mskcc.org/cpath#";
			$level = 2;
		} else if($data == "biomodels") {
			$base_uri = "http://bio2rdf.org/";
			$level = 3;
		}
		$bio2rdf_biopax_uri = "http://bio2rdf.org/biopaxl".$level.":";
		
		
		// query the endpoint
		if($level == 2) {
			$biopax_uri = 'http://www.biopax.org/release/biopax-level2.owl#';
			$where = '
	 ?x <http://www.biopax.org/release/biopax-level2.owl#XREF> ?xref .
	 ?xref <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?type .
	 ?xref <http://www.biopax.org/release/biopax-level2.owl#DB> ?db .
	 ?xref <http://www.biopax.org/release/biopax-level2.owl#ID> ?id .';
		} else if($level == 3) {
			$biopax_uri = 'http://www.biopax.org/release/biopax-level3.owl#';
			$where = '
	 ?x <http://www.biopax.org/release/biopax-level3.owl#xref> ?xref .
	 ?xref <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?type .
	 ?xref <http://www.biopax.org/release/biopax-level3.owl#db> ?db .
	 ?xref <http://www.biopax.org/release/biopax-level3.owl#id> ?id .';
		}
	 
		// get the counts
	$sparql = "SELECT COUNT(distinct ?x) AS ?count WHERE {".$where."}";
		$a = json_decode(file_get_contents($endpoint.'?query='.urlencode($sparql).'&format=json'));
		$counts = $a->results->bindings[0]->count->value;

//		$counts = 25111803;
//		$counts = 20;
		$interval = 1000;
		$offset = 0;
		do {
			echo "$offset ...";
			$sparql = "SELECT distinct ?x ?xref ?type ?db ?id WHERE {".$where."} LIMIT $interval OFFSET $offset";
			$a = json_decode(file_get_contents($endpoint.'?query='.urlencode($sparql).'&format=json'));
	
			foreach($a->results->bindings AS $r) {
				$subject_uri = str_replace($base_uri, $bio2rdf_uri, $r->x->value);
				$xref_uri = str_replace($base_uri, $bio2rdf_uri, $r->xref->value);
				$type_uri = str_replace($base_uri, $bio2rdf_uri, $r->type->value);
				$id = $r->id->value;
				
				// generate the sameas
				$this->AddRDF($this->Quad($r->x->value, $this->GetNS()->getNSURI("owl")."sameAs", $subject_uri));
				$this->AddRDF($this->Quad($r->xref->value, $this->GetNS()->getNSURI("owl")."sameAs", $xref_uri));
				
				// generate the new relationship
				if(strstr($type_uri,"unification")) $rel = "identical-to";
				else if(strstr($type_uri,"relationship")) $rel = "related-to";
				else if(strstr($type_uri,"publication")) $rel = "publication";
				$this->AddRDF($this->Quad($r->x->value, substr($bio2rdf_biopax_uri,0,-3)."_vocabulary:".$rel, $r->xref->value));
				
				// now generate the bio2rdf xrefs by mapping
				$db = $this->Map($r->db->value,"identifiers.org");
				if($db) {
					$uri = 'http://identifiers.org/'.$db.'/'.$id;
					$this->AddRDF($this->Quad($r->xref->value,  $this->GetNS()->getNSURI("owl")."sameAs", $uri));
				}
				$db = $this->Map($r->db->value,"bio2rdf.org");
				if($db) {
					$uri = 'http://bio2rdf.org/'.$db.':'.$id;
					$this->AddRDF($this->Quad($r->xref->value,  $this->GetNS()->getNSURI("owl")."sameAs", $uri));
				}
			}
		
			$offset += $interval;
			
			$this->WriteRDFBufferToWriteFile();
		} while($offset < $counts);
		
		echo $this->GetRDF();
	
	}
	
	function Map($db, $type) 
	{
		$ns_map = array(
		"aracyc" => array('bio2rdf.org'=>'aracyc'),
		"biocyc" => array('bio2rdf.org'=>'biocyc'),
		"ecocyc" => array('bio2rdf.org'=>'ecocyc'),
		"metacyc" => array('bio2rdf.org'=>'metacyc'),
		"biomodels database"=> array(
			'identifiers.org'=>'biomodels.db',
			'bio2rdf.org'=>'biomodels'),
		"brenda"=> array('identifiers.org'=>'brenda','bio2rdf.org'=>'brenda'),
		"brenda tissue ontology"=> array('identifiers.org'=>'obo.bto','bio2rdf.org'=>'bto'),
		"cas" => array('identifiers.org'=>'cas','bio2rdf.org'=>'cas'),
		"chemicalabstracts" => array('identifiers.org'=>'cas','bio2rdf.org'=>'cas'),
		
		"cell type ontology" => array('identifiers.org'=>'obo.cto','bio2rdf.org'=>'cto'),
		"cell cycle ontology" => array('identifiers.org'=>'obo.cco','bio2rdf.org'=>'cco'),
		"chebi" => array('identifiers.org'=>'chebi','bio2rdf.org'=>'chebi'),
		"cygd" => array('identifiers.org'=>'cygd','bio2rdf.org'=>'cygd'),
		"ddbj/embl/genbank" => array('identifiers.org'=>'insdc','bio2rdf.org'=>'insdc'),		
		"doi"=>array('identifiers.org'=>'doi','bio2rdf.org'=>'doi'),
		"embl"=>array('identifiers.org'=>'ena','bio2rdf.org'=>'embl'),
		"ensembl"=> array('identifiers.org'=>'ensembl','bio2rdf.org'=>'ensembl'),
		"ensemblgenomes"=> array('identifiers.org'=>'ensembl','bio2rdf.org'=>'ensembl'),
		"entrez"=>array('identifiers.org'=>'ncbigene','bio2rdf.org'=>'geneid'),
		"entrez_gene"=>array('identifiers.org'=>'ncbigene','bio2rdf.org'=>'geneid'),
		"entrezgene/locuslink"=>array('identifiers.org'=>'ncbigene','bio2rdf.org'=>'geneid'),
		"enzymeconsortium"=> array('identifiers.org'=>'ec-code','bio2rdf.org'=>'ec'),
		"enzyme nomenclature" => array('identifiers.org'=>'ec-code','bio2rdf.org'=>'ec'),
		"evidence codes ontology" => array('identifiers.org'=>'obo.eco','bio2rdf.org'=>'eco'),
		"fma"=> array('identifiers.org'=>'obo.fma','bio2rdf.org'=>'fma'),
		"gene ontology"=> array('identifiers.org'=>'obo.go','bio2rdf.org'=>'go'),
		"genbank"=> array('identifiers.org'=>'genbank','bio2rdf.org'=>'genbank'),
		"genbank_nucl_gi" => array('identifiers.org'=>'ncbinuc','bio2rdf.org'=>'gi'),
		"genbank_protein_gi" =>  array('identifiers.org'=>'ncbiprot','bio2rdf.org'=>'gi'),
		"gene ontology"  =>  array('identifiers.org'=>'obo.go','bio2rdf.org'=>'go'),
		"gene_ontology"  =>  array('identifiers.org'=>'obo.go','bio2rdf.org'=>'go'),
		"gene_symbol" =>array('identifiers.org'=>'hgnc.symbol','bio2rdf.org'=>'symbol'),
		"grid" =>array('identifiers.org'=>'grid','bio2rdf.org'=>'biogrid'),
		"human disease ontology"=> array('identifiers.org'=>'obo.do','bio2rdf.org'=>'do'),
		"hprd" =>array('bio2rdf.org'=>'hprd'),
		"icd"=> array('identifiers.org'=>'icd','bio2rdf.org'=>'icd9'),
		"intact"=>array('identifiers.org'=>'intact','bio2rdf.org'=>'intact'),
		"interpro"=> array('identifiers.org'=>'interpro','bio2rdf.org'=>'interpro'),
		"ipi"=> array('identifiers.org'=>'ipi','bio2rdf.org'=>'ipi'),
		"compound"=> array('identifiers.org'=>'kegg.compound','bio2rdf.org'=>'kegg'),
		"kegg" => array('identifiers.org'=>'kegg','bio2rdf.org'=>'kegg'),
		"kegg-legacy" => array('identifiers.org'=>'kegg','bio2rdf.org'=>'kegg'),
		"kegg compound"=> array('identifiers.org'=>'kegg.compound','bio2rdf.org'=>'kegg'),
		"kegg pathway"=> array('identifiers.org'=>'kegg.pathway','bio2rdf.org'=>'kegg'),
		"kegg reaction"=> array('identifiers.org'=>'kegg.reaction','bio2rdf.org'=>'kegg'),		
		"knapsack" => array('identifiers.org'=>'knapsack','bio2rdf.org'=>'knapsack'),		
		"mint" => array('identifiers.org'=>'mint','bio2rdf.org'=>'mint'),		
		"narcis"=> array('identifiers.org'=>'narcis','bio2rdf.org'=>'narcis'),
		"nci" => array('identifiers.org'=>'pid.pathway','bio2rdf.org'=>'pid'),
		"omim" => array('identifiers.org'=>'omim','bio2rdf.org'=>'omim'),
		"pato" => array('identifiers.org'=>'obo.pato','bio2rdf.org'=>'pato'),
		"pdb" => array('identifiers.org'=>'pdb','bio2rdf.org'=>'pdb'),
		"wwpdb" => array('identifiers.org'=>'pdb','bio2rdf.org'=>'pdb'),
		"rcsb pdb" => array('identifiers.org'=>'pdb','bio2rdf.org'=>'pdb'),
		"pdbe" => array('identifiers.org'=>'pdb','bio2rdf.org'=>'pdb'),
		"pirsf"=> array('identifiers.org'=>'pirsf','bio2rdf.org'=>'pirsf'),
		"pride"=> array('identifiers.org'=>'pride','bio2rdf.org'=>'pride'),
		
		"psi-mi"=> array('bio2rdf.org'=>'psi-mi'),
		"psi-mod"=> array('identifiers.org'=>'obo.psi-mod','bio2rdf.org'=>'psi-mod'),
		"protein modification ontology"=> array('identifiers.org'=>'obo.psi-mod','bio2rdf.org'=>'psi-mod'),
		"pubmed"=> array('identifiers.org'=>'pubmed','bio2rdf.org'=>'pubmed'),
		"pubchem"=> array('identifiers.org'=>'pubchemcompound','bio2rdf.org'=>'pubchemcompound'),

		"reactome"=> array('identifiers.org'=>'reactome','bio2rdf.org'=>'reactome'),
		"reactome database identifier"=> array('identifiers.org'=>'reactome','bio2rdf.org'=>'reactome'),
		"ref_seq" => array('identifiers.org'=>'refseq','bio2rdf.org'=>'refseq'),
		"resid" => array('identifiers.org'=>'resid','bio2rdf.org'=>'resid'),
		"sgd" => array('identifiers.org'=>'sgd','bio2rdf.org'=>'sgd'),
		"taxon"=> array('identifiers.org'=>'taxonomy','bio2rdf.org'=>'taxon'),
		"ncbi taxonomy"=> array('identifiers.org'=>'taxonomy','bio2rdf.org'=>'taxon'),
		"ncbi_taxonomy"=> array('identifiers.org'=>'taxonomy','bio2rdf.org'=>'taxon'),
		"newt" => array('identifiers.org'=>'taxonomy','bio2rdf.org'=>'taxon'),
		"taxonomy" => array('identifiers.org'=>'taxonomy','bio2rdf.org'=>'taxon'),
		"umbbd-compounds" => array('identifiers.org'=>'umbbd.compound','bio2rdf.org'=>'umbbd'),
		"uniprot"=> array('identifiers.org'=>'uniprot','bio2rdf.org'=>'uniprot'),
		"uniparc"=> array('identifiers.org'=>'uniparc','bio2rdf.org'=>'uniparc'),
		"wormbase"=> array('identifiers.org'=>'wormbase','bio2rdf.org'=>'wormbase'),
	);
	$db = strtolower($db);
	if(isset($ns_map[$db][$type])) {
		return $ns_map[$db][$type];
	}
	
	}
}


set_error_handler('error_handler');
$parser = new BioPAXParser($argv);
$parser->Run();
	

	
