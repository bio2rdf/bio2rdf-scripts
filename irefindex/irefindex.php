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
 * An RDF generator for iRefIndex (http://irefindex.uio.no)
 * documentation: http://irefindex.uio.no/wiki/README_MITAB2.6_for_iRefIndex_9.0
 * @version 1.0
 * @author Michel Dumontier
*/

require('../../php-lib/rdfapi.php');
class iREFINDEXParser extends RDFFactory 
{
	function __construct($argv) { //
		parent::__construct();
		// set and print application parameters
		$this->AddParameter('files',true,'all|10090|10116|4932|559292|562|6239|7227|9606|other','all','all or comma-separated list of files to process');
		$this->AddParameter('indir',false,null,'/data/download/irefindex/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/irefindex/','directory to place rdfized files');
		$this->AddParameter('version',false,null,'10182011','dated version of files to download');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'ftp://ftp.no.embnet.org/irefindex/data/current/psimi_tab/MITAB2.6/');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		$this->SetReleaseFileURI("irefindex");
		
		return TRUE;
	}
	
	function Run()
	{
		// get the file list
		if($this->GetParameterValue('files') == 'all') {
			$files = array('all');
		} else {
			$files = explode(",",$this->GetParameterValue('files'));
		}

		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$rdir = $this->GetParameterValue('download_url');
		
		foreach($files AS $file) {
			$base_file = ucfirst($file).".mitab.".$this->GetParameterValue("version").".txt";
			$zip_file  = $base_file.".zip";
			$lfile = $ldir.$zip_file;
			$outfile = $odir.$file.".ttl.gz";
			if(!file_exists($lfile)) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$this->SetParameterValue('download',true);
			}
			
			if($this->GetParameterValue('download') == true) {
				if(FALSE === Utils::Download("ftp://ftp.no.embnet.org",array("/irefindex/data/current/psimi_tab/MITAB2.6/".$zip_file),$ldir)) {
					trigger_error("Error in Download");
					return FALSE;
				}
			}
			
			$zin = new ZipArchive();
			if ($zin->open($lfile) === FALSE) {
				trigger_error("Unable to open $lfile");
				exit;
			}
			if(($fp = $zin->getStream($base_file)) === FALSE) {
					trigger_error("Unable to get $base_file in ziparchive $lfile");
					return FALSE;
			}
			$this->SetReadFile($lfile);
			$this->GetReadFile()->SetFilePointer($fp);
				
			
			echo "Processing ".$file." ...";
			$this->SetWriteFile($outfile, true);
	
			if($this->Parse() === FALSE) {
				trigger_error("Parsing Error");
				exit;
			}
			
			$this->WriteRDFBufferToWriteFile();
			$this->GetWriteFile()->Close();
			$zin->close();
			echo "Done!".PHP_EOL;
		}
	}

	function Parse()
	{
		$l = $this->GetReadFile()->Read(100000);
		$header = explode("\t",trim(substr($l,1)));
		if(($c = count($header)) != 54) {
			trigger_erorr("Expecting 54 columns, found $c!");
			return FALSE;
		}

		$describe = array(
		  'irogida' => array('label' => 'taxon-seq-identical-entity', 'description' => 'SHA-1 digest of aa seq + NCBI taxonomy'),
		  'irogidb' => array('label' => 'taxon-seq-identical-entity', 'description' => 'SHA-1 digest of aa seq + NCBI taxonomy'),
		  'irigid' => array('label' => 'taxon-seq-identical-interaction', 'description' => 'interaction involving same interactors (seq+taxon)'),
		  'icrogida' =>array('label' => 'taxon-seq-similar-entity','description'=>'canonical grouping of similar sequences from the same taxon'),
		  'icrogidb' =>array('label' => 'taxon-seq-similar-entity','description'=>'canonical grouping of similar sequences from the same taxon'),
		  'icrigid' =>array('label' => 'taxon-seq-similar-interaction','description' => 'interactions involving similar interactors')
		);
		// check # of columns
		while($l = $this->GetReadFile()->Read(100000)) {
			$a = explode("\t",$l);

			// 13 is the original identifier
			$ids = explode("|",$a[13],2);
			$this->GetNS()->ParsePrefixedName($ids[0],$ns,$str);
			$this->Parse4IDLabel($str,$id,$label);
			$iid = $this->GetNSMap(strtolower($ns)).":".$id;
			
			// get the type
			if($a[52] == "X") {
				$label = "Pairwise interaction between $a[0] and $a[1]";
				$type = "Pairwise-Interaction";
			} else if($a[52] == "C") {
				$label = $a[53]." component complex";
				$type = "Multimeric-Complex";
			} else if($a[52] == "Y") {
				$label = "homomeric complex composed of $a[0]";  
				$type = "Homopolymeric-Complex";
			}
			$this->AddRDF($this->QQuad($iid,"rdf:type","irefindex_vocabulary:$type"));
			
			// generate the label
			// interaction type[52] by method[6]
			$this->GetNS()->ParsePrefixedName($a[6],$ns,$str);
			$this->Parse4IDLabel($str,$id,$method);

			$this->AddRDF($this->QQuadL($iid,"rdfs:label","$label identified by $method [$iid]"));
			$this->AddRDF($this->QQuadO_URL($iid,"rdfs:seeAlso","http://wodaklab.org/iRefWeb/interaction/show/".$a[50]));
			
			foreach($a AS $k => $v) {
				$list = explode("|",trim($v));
				if($list[0][0] == "-") continue;
				foreach($list AS $item) {

					// we're going to ignore the hash entries and edgetype
					if(in_array($header[$k], array("crogida","crogidb","crigid","edgetype"))) continue;

					$this->GetNS()->ParsePrefixedName($item,$ns,$str);
					$this->Parse4IDLabel($str,$id,$label);
					$ns = trim($ns);
					$id = trim($id);
					
					if($ns) {
						$ns = $this->getNSMap(strtolower($ns));
						if($ns == "edgetype") continue;					
						if($ns == "lpr" || $ns == "hpr" || $ns == "np") {
							$this->AddRDF($this->QQuadL($iid, "irefindex_vocabulary:".$ns, $id));
							continue;
						}
						$id = str_replace(" ","-",$id);
						if($ns) $this->AddRDF($this->QQuad($iid, "irefindex_vocabulary:".$header[$k], $ns.":".$id));
					} else {
						$this->AddRDF($this->QQuadL($iid, "irefindex_vocabulary:".$header[$k], $id));
					}
				}
			}
//echo $this->GetRDF();
			$this->WriteRDFBufferToWriteFile();
		}
	}

	function Parse4IDLabel($str,&$id,&$label)
	{
		$id='';$label='';
		preg_match("/(.*)\((.*)\)/",$str,$m);
		if(isset($m[1])) {
			$id = $m[1];
			$label = $m[2];
		} else {
			$id = $str;
		}
	}
	
	function getNSMap($ns)
	{
		$nsmap = array(
			'emb' => 'embl',
			'gb' => 'genbank',
			'genbank_protein_gi' => 'gi',
			'taxid' => 'taxon',
			'uniprotkb' => 'uniprot',
			'uniprotkb/trembl' => 'uniprot',
			'entrezgene/locuslink' => 'geneid',
			'dbj' => 'ddbj',
			'kegg:ecj' => 'kegg',
			'mppi' => 'mips',
			'swiss-prot' => 'uniprot',
			'ddbj-embl-genbank' => 'genbank',
			'ddbj/embl/genbank' => 'genbank',
			'complex' => 'irefindex',
			'bind_translation' => 'bind',
			'genbank' => 'genbank',
			'rcsb pdb' => 'pdb',
			'sp' => 'swissprot',
			'genbank indentifier' => 'ncbi',
			'entrez gene/locuslink' => 'geneid',
			'gi'=> 'ncbi',
			'uniprot knowledge base' => 'uniprot',
			'mpilit' => 'mpi',
			'mpiimex' => 'mpi',
			'grid' =>  'biogrid',
			
			'rogid'    => 'irefindex_rogid',
			'irogid'   => 'irefindex_irogid',
			'rigid'    => 'irefindex_rigid',
			'irigid'   => 'irefindex_irigid',
			'irogida'  => 'irefindex_irogid',
			'irogidb'  => 'irefindex_irogid',
			'icrigid'  => 'irefindex_icrigid',
			'icrogid'  => 'irefindex_icrogid',
			'icrogida' => 'irefindex_icrogid',
			'icrogidb' => 'irefindex_icrogid',
			'crigid'   => 'irefindex_crigid',
			'crogid'   => 'irefindex_crogid',
			'crogida'  => 'irefindex_crogid',
			'crogidb'  => 'irefindex_crogid',
			'other' => '',					
			'xx' => '',
		);
		if(isset($nsmap[$ns])) return $nsmap[$ns];
		return $ns;
	}
}

set_error_handler('error_handler');
$parser = new iREFINDEXParser($argv);
$parser->Run();

?>
