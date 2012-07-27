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
 * Pathwaycommons RDFizer 
 * @version 1.0
 * @author Michel Dumontier
 * @description http://www.pathwaycommons.org
*/
require('../../php-lib/rdfapi.php');
include_once('../../arc2/ARC2.php'); // available on git @ https://github.com/semsol/arc2.git
class PathwaycommonsParser extends RDFFactory 
{
	private $ns = null;
	private $named_entries = array();
	private $file_open_func = null;
	private $file_close_func = null;
	private $file_write_func = null;
	private $out = null;
		
	function __construct($argv) {
		parent::__construct();
		// set and print application parameters
		$this->AddParameter('files',true,'all|biogrid|cell-map|hprd|humancyc|imid|intact|mint|nci-nature|reactome','all','biopax OWL files to process');
		$this->AddParameter('indir',false,null,'/data/download/pathwaycommons/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/pathwaycommons/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://www.pathwaycommons.org/pc-snapshot/current-release/biopax/by_source/');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		return TRUE;
	}
	
	function Run()
	{	// create directories
		$ldir = $this->GetParameterValue('indir');
		if(!is_dir($ldir)) {
			if(@mkdir($ldir,0777,true) === FALSE) {
				trigger_error("Unable to create $ldir");
				exit;
			}
		}
		$odir = $this->GetParameterValue('outdir');
		if(!is_dir($odir)) {
			if(@mkdir($odir,0777,true) === FALSE) {
				trigger_error("Unable to create $odir");
				exit;
			}
		}
		
		// now what did we want?
		if($this->GetParameterValue('files') == 'all') {
			$sources = explode("|",$this->GetParameterList('files'));
			array_shift($sources);
		} else {
			// comma separated list
			$sources = explode(",",$this->GetParameterValue('files'));
		}
		
		// prepare one of two output files based on whether gzipped or not
		if($this->GetParameterValue('gzip') == 'true') {
			$this->file_open_func = 'gzopen';
			$this->file_write_func = 'gzwrite';
			$this->file_close_func = 'gzclose';
		} else {
			$this->file_open_func = 'fopen';
			$this->file_write_func = 'fwrite';
			$this->file_close_func = 'fclose';
		}

		// iterate over the requested sources
		foreach($sources AS $source) {
			echo "processing $source...";
			
			$zfile = $source.".owl.zip";
			$rfile = $this->GetParameterValue('download_url').$zfile;
			$lfile = $ldir.$zfile;

			// download if the file doesn't exist or we are told to
			if(!file_exists($lfile) || $this->GetParameterValue('download') == 'true') {
				// download 
				echo "downloading..";
				file_put_contents($lfile, file_get_contents($rfile));
			}

			// open the output file
			$outfile = $odir.$source.'ttl';
			if($this->GetParameterValue('gzip') == 'true') $outfile .= '.gz';
			$fnx = $this->file_open_func;
			if (($this->out = $fnx($outfile,"w"))=== FALSE) {
				trigger_error("Unable to open $odir.$outfile");
				exit;
			}

			
			// load and parse
			$this->Parse($lfile,$source.".owl");
			$fnx = $this->file_write_func;
			$fnx($this->out,$this->GetRDF());
			$this->DeleteRDF(); // clear the buffer

			$fnx = $this->file_close_func;
			$fnx($this->out);
			echo "\n";
		}
		return true;
	}
	
	

	function Parse($zfile, $file)
	{
	echo 'extracting...';
		$zin = new ZipArchive();
		if ($zin->open($zfile) === FALSE) {
			trigger_error("Unable to open $zfile");
			exit;
		}
		$data = '';
		$fpin = $zin->getStream($file);
		while($l = fgets($fpin)) {
			$data .= $l;
		}
		fclose($fpin);
		
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
		$z = 0;
		foreach($index AS $s => $p_list) {
			if($z++ % 10000 == 0) {
				echo "$z of $total".PHP_EOL;
				$fnx = $this->file_write_func;
				$fnx($this->out,$this->GetRDF());
				$this->DeleteRDF();
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
			case "NCI": return "ncit";
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


set_error_handler('error_handler');
$parser = new PathwaycommonsParser($argv);
$parser->Run();
?>
