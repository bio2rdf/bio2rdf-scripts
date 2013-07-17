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

require('../../php-lib/biopax2bio2rdf.php');

/**
 * Pathwaycommons RDFizer 
 * @version 1.0
 * @author Michel Dumontier
 * @description http://www.pathwaycommons.org
*/
class PathwaycommonsParser extends RDFFactory 
{		
	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("pathwaycommons");
		
		// set and print application parameters
		$this->AddParameter('files',true,'all|biogrid|cell-map|hprd|humancyc|imid|intact|mint|nci-nature|reactome','all','biopax OWL files to process');
		$this->AddParameter('indir',false,null,'/data/download/'.$this->GetNamespace().'/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/'.$this->GetNamespace().'/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://www.pathwaycommons.org/pc-snapshot/current-release/biopax/by_source/');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));		
		
		return TRUE;
	}
	
	function Run()
	{			
		// get the work
		if($this->GetParameterValue('files') == 'all') {
			$sources = explode("|",$this->GetParameterList('files'));
			array_shift($sources);
		} else {
			// comma separated list
			$sources = explode(",",$this->GetParameterValue('files'));
		}					

		// iterate over the requested data
		foreach($sources AS $source) {
			echo "processing $source...";
			
			// set the remote and input files
			$file  = $source.".owl";
			$zfile = $source.".owl.zip";
			$rfile = $this->GetParameterValue('download_url').$zfile;
			$lfile = $this->GetParameterValue('indir').$zfile;

			// download if if the file doesn't exist locally or we are told to
			if(!file_exists($lfile) || $this->GetParameterValue('download') == 'true') {
				// download 
				echo "downloading..";
				file_put_contents($lfile, file_get_contents($rfile));
			}
			
			// extract the file out of the ziparchive
			// and load into a buffer
			echo 'extracting...';
			$zin = new ZipArchive();
			if ($zin->open($lfile) === FALSE) {
				trigger_error("Unable to open $lfile");
				exit;
			}
			$data = '';
			$fpin = $zin->getStream($file);
			while($l = fgets($fpin)) $data .= $l;
			fclose($fpin);

			// set the output file
			$outfile = $this->GetParameterValue('outdir').$source.'nt';
			$gz = false;
			if($this->GetParameterValue('graph_uri')) {$outfile = $this->GetParameterValue('outdir').$source.'nq';}
			if($this->GetParameterValue('gzip') == 'true') {
				$outfile .= '.gz';
				$gz = true;
			}
			$this->SetWriteFile($outfile, $gz);
			
			// parse
			$this->Parse($data);
			
			// write to output
			$this->WriteRDFBufferToWriteFile();
			$this->GetWriteFile()->Close();
			
			echo PHP_EOL;
		}
		return TRUE;
	}
	
	

	function Parse($data)
	{
		$endpoint = "http://s4.semanticscience.org:8010/sparql";
		// query the endpoint
		$sparql = 'SELECT *
WHERE {
 ?x <http://www.biopax.org/release/biopax-level2.owl#xref> ?xref .
 ?xref <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> ?type .
 ?xref <http://www.biopax.org/release/biopax-level2.owl#db> ?db .
 ?xref <http://www.biopax.org/release/biopax-level2.owl#id> ?id .
}
LIMIT 1
';
	$a = json_decode(file_get_contents($endpoint.'?query='.urlencode($sparql).'&format=json'));
	foreach($a->results->bindings AS $r) {
		print_r($r);exit;
	}
	
	
	
	
	echo 'parsing...';
		$parser = ARC2::getRDFParser();
		$parser->parse('http://pathwaycommons.org', $data);
	echo 'building index...';
		$triples = $parser->getTriples();
		foreach($triples AS $i => $a) {
			$o['value'] = $a['o'];
			$o['type'] = $a['o_type'];
			$o['datatype'] = $a['o_datatype'];
			$index[$a['s']][$a['p']][] = $o;
		}

		$biopax = 'http://www.biopax.org/release/biopax-level2.owl#';
		$cpath  = 'http://cbio.mskcc.org/cpath#';

		$nso = $this->GetNS();
	echo 'processing...';
		$total = count($index);
		$interval = (int) (.25*$total);
		$z = 0;
		foreach($index AS $s => $p_list) {
			if($z++ % $interval == 0) {
				echo "$z of $total".PHP_EOL;
				$this->WriteRDFBufferToWriteFile();
			}
			$s_uri = str_replace(
				array($biopax,$cpath),
				array("http://bio2rdf.org/biopaxl2:","http://bio2rdf.org/cpath:"),
				$s);
			
			// make the original uri the same as the bio2rdf uri
			$this->AddRDF($this->Quad($s_uri,$nso->GetFQURI("owl:sameAs"),$s));
	

			// handle the unification/relationship xrefs here
			if( isset($p_list['http://www.biopax.org/release/biopax-level2.owl#DB'])
			 && isset($p_list['http://www.biopax.org/release/biopax-level2.owl#ID'])) {

				$db = $p_list['http://www.biopax.org/release/biopax-level2.owl#DB'][0]['value'];
				$id = $p_list['http://www.biopax.org/release/biopax-level2.owl#ID'][0]['value'];
				
				if(!$db || !$id) continue;
				// sometimes we see stupid stuff like go:XXXXXX in the id
				$this->GetNS()->ParsePrefixedName($id,$ns2,$id2);
				if($ns2) $id = $id2;
				
				$qname = $this->MapDB($db).":".$id;
				$o_uri = $this->GetNS()->getFQURI($qname);
				$this->AddRDF($this->QuadL($s_uri,$nso->GetFQURI("rdfs:label"), $qname));
				$type = $p_list['http://www.w3.org/1999/02/22-rdf-syntax-ns#type'][0]['value'];
				if($type == 'http://www.biopax.org/release/biopax-level2.owl#unificationXref') {
					$this->AddRDF($this->Quad($s_uri,$nso->GetFQURI("owl:sameAs"),$o_uri));
				} elseif($type == 'http://www.biopax.org/release/biopax-level2.owl#relationshipXref') {
					$this->AddRDF($this->Quad($s_uri,$nso->GetFQURI("biopaxl2:relationshipXref"),$o_uri));
				}
				continue;
			}
				
			// now process each relation
			foreach($p_list AS $p => $o_list) {
				$p_uri = str_replace(
					array("http://www.biopax.org/release/biopax-level2.owl#","http://cbio.mskcc.org/cpath#"),
					array("http://bio2rdf.org/biopaxv2:","http://bio2rdf.org/cpath:"),
					$p);
			
				// now process each object of the relation
				foreach($o_list AS $o) {
					if($o['type'] == 'uri') {
						$o_uri = str_replace(
							array("http://www.biopax.org/release/biopax-level2.owl#","http://cbio.mskcc.org/cpath#"),
							array("http://bio2rdf.org/biopaxv2:","http://bio2rdf.org/cpath:"),
							$o['value']);						
						$this->AddRDF($this->Quad($s_uri,$p_uri,$o_uri));
					} else {
						// literal
						$literal = $this->SafeLiteral($o['value']);
						$datatype = null;
						if(isset($o['datatype'])) {
							if(strstr($o['datatype'],"http://")) {
								$datatype = $o['datatype'];
							} else {
								$datatype = $nso->GetFQURI($o['datatype']);
							}
						}
						$this->AddRDF($this->QuadL($s_uri,$p_uri,$literal,null,$datatype));
					}
				}
			}
			
		}
	
	echo 'done!'.PHP_EOL;
	} // end parse
	
	function MapDB($db)
	{
		switch($db) {
			case "ARACYC": return "aracyc";
			case "BRENDA": return "brenda";
			case "CAS": return "cas";
			case "CHEMICALABSTRACTS": return "cas";
			case "ChEBI": return "chebi";
			case "CYGD": return 'cygd';
			case "DDBJ/EMBL/GENBANK": return "genbank";
			case "ECOCYC": return 'ecocyc';
			case "EMBL": return 'embl';
			case "ENSEMBL":
			case "ENSEMBLGENOMES":
				return "ensembl";
			case "ENTREZ":
			case "ENTREZ_GENE":
			case "ENTREZGENE/LOCUSLINK": 
				return "geneid";
			case "ENZYMECONSORTIUM": return "ec";
			case "EVIDENCE CODES ONTOLOGY": return "eco";
			case "GENBANK":
				return 'genbank';
			case "GENBANK_NUCL_GI":
			case "GENBANK_PROTEIN_GI":
				return "gi";
			case "GENE_ONTOLOGY": return "go";
			case "GENE_SYMBOL": return "symbol";
			case "GRID": return 'biogrid';
						
			case "HPRD": return 'hprd';
			case "HUMANCYC": return 'humancyc';
			case "INTACT": return 'intact';
			
			case "COMPOUND": 
			case "KEGG-LEGACY":
			case "KEGG":
				return "kegg";
			case "IPI": return 'ipi';
			case "INTERPRO": return 'interpro';
			case "KNAPSACK": return "knapsack";
			case "METACYC":  return "metacyc";
			case "MINT": return "mint";
			case "NCBI TAXONOMY": return "taxon";
			case "NCBI_TAXONOMY": return "taxon";
			case "NCI": return "pid";
			case "NEWT": return "newt";
			case 'PDB': return 'pdb';
			case 'PDBE': return 'pdb';
			case 'PRIDE': return 'pride';
			case 'PSI-MI': return 'psi-mi';
			case 'PSI-MOD': return 'psi-mod';
			case 'PUBCHEM': return 'pubchemcompound';
			case 'RCSB PDB': return 'pdb';
			case 'REACTOME': return 'reactome';
			case 'REACTOME DATABASE ID': return 'reactome';
			case 'REF_SEQ': return 'refseq';
			case 'RESID': return 'resid';
			case 'SGD': return 'sgd';
			case 'TAXON': return 'taxon';
			case 'TAXONOMY': return 'taxon';
			case 'UMBBD-COMPOUNDS': return 'umbbd';
			case 'UNIPARC': return 'uniparc';
			case 'UNIPROT': return 'uniprot';
			case 'WORMBASE': return 'wormbase';
			case 'WWPDB': return 'pdb';

			// what?
			case "CABRI":
			case "CPATH":
			case "IOB":  
			case 'WIKIPEDIA': 
			
			default:
				return strtolower($db);
		}
	}
} 
$start = microtime(true);

set_error_handler('error_handler');
$parser = new PathwaycommonsParser($argv);
$parser->Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"

?>
