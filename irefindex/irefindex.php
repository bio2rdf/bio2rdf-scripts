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
		$this->SetDefaultNamespace("irefindex");
		
		// set and print application parameters
		$this->AddParameter('files',true,'all|10090|10116|4932|559292|562|6239|7227|9606|other','all','all or comma-separated list of files to process');
		$this->AddParameter('indir',false,null,'/data/download/irefindex/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/irefindex/','directory to place rdfized files');
		$this->AddParameter('version',false,null,'10182011','dated version of files to download');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'ftp://ftp.no.embnet.org/irefindex/data/current/psimi_tab/MITAB2.6/');
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
			
			$ofile = "irefindex-".$file.".nt";
			$gz = false;
			if($this->GetParameterValue("gzip") == "true") {
				$gz = true;
				$ofile .= ".gz";
			}
			$download_files[] = $ofile;
			
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
			$this->SetWriteFile($odir.$ofile, true);
	
			if($this->Parse() === FALSE) {
				trigger_error("Parsing Error");
				exit;
			}
			
			$this->WriteRDFBufferToWriteFile();
			$this->GetWriteFile()->Close();
			$zin->close();
			echo "Done!".PHP_EOL;
		}
		
		// generate the release file
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/irefindex/irefindex.php", 
			$download_files,
			"http://irefindex.uio.no", 
			array("use","by-attribution","no-commercial"), 
			"http://irefindex.uio.no/wiki/README_MITAB2.6_for_iRefIndex#License",
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		
		return TRUE;
	}

	function Parse()
	{
		$l = $this->GetReadFile()->Read(100000);
		$header = explode("\t",trim(substr($l,1)));
		if(($c = count($header)) != 54) {
			trigger_erorr("Expecting 54 columns, found $c!");
			return FALSE;
		}

		// check # of columns
		while($l = $this->GetReadFile()->Read(100000)) {
			$a = explode("\t",trim($l));

			// 13 is the original identifier
			$ids = explode("|",$a[13],2);
			$this->GetNS()->ParsePrefixedName($ids[0],$ns,$str);
			$this->Parse4IDLabel($str,$id,$label);
			$id = str_replace('"','',$id);
			$iid = $this->GetNSMap(strtolower($ns)).":$id";

			$this->AddRDF($this->QQuad($iid,"void:inDataset",$this->GetDatasetURI()));

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
			$this->ParseString($a[6],$ns,$id,$method);
			if($ns) $this->AddRDF($this->QQuad($iid,"irefindex_vocabulary:method","$ns:$id"));

			$method_label = '';
			if($method != 'NA' && $method != '-1') $method_label = " identified by $method ";
			$this->AddRDF($this->QQuadL($iid,"rdfs:label","$label".$method_label."[$iid]"));
			$this->AddRDF($this->QQuadO_URL($iid,"rdfs:seeAlso","http://wodaklab.org/iRefWeb/interaction/show/".$a[50]));

			// set the interators
			for($i=0;$i<=1;$i++) {
				$p = 'a';
				if($i == 1) $p = 'b';

				$this->ParseString($a[$i],$ns,$id,$label);
				$interactor = "$ns:$id";
				$this->AddRDF($this->QQuad($iid,"irefindex_vocabulary:interactor_$p",$interactor));

				// biological role
				$role = $a[16+$i];
				if($role != '-') {
					$this->ParseString($role,$ns,$id,$label);
					if("$ns:$id" != "mi:0000") $this->AddRDF($this->QQuad($iid,"irefindex_vocabulary:interactor_$p"."_biological_role","$ns:$id"));
				}
				// experimental role
				$role = $a[18+$i];
				if($role != '-') {
					$this->ParseString($role,$ns,$id,$label);
					if("$ns:$id" != "mi:0000") $this->AddRDF($this->QQuad($iid,"irefindex_vocabulary:interactor_$p"."_experimental_role","$ns:$id"));
				}
				// interactor type
				$type = $a[20+$i];
				if($type != '-') {
					$this->ParseString($type,$ns,$id,$label);
					$this->AddRDF($this->QQuad($interactor,"rdf:type","$ns:$id"));
				}
			}

			// add the alternatives through the taxon + seq redundant group
			for($i=2;$i<=3;$i++) {
				$taxid = '';
				$irogid = "irefindex_irogid:".$a[42+($i-2)];
				if(!isset($defined[$irogid])) {
					$defined[$irogid] = '';
					$this->AddRDF($this->QQuadL($irogid,"rdfs:label","[$irogid]"));			
					$this->AddRDF($this->QQuad($irogid,"rdf:type","irefindex_vocabulary:Taxon-Sequence-Identical-Group"));
					$tax = $a[9+($i-2)];
					if($tax != '-') {
						$this->ParseString($tax,$ns,$id,$label);
						$taxid = "$ns:$id";
						$this->AddRDF($this->QQuad($irogid,"irefindex_vocabulary:taxon",$taxid));
					}
				}

				$list = explode("|",$a[3]);
				foreach($list AS $item) {
					$this->ParseString($item,$ns,$id,$label);
					if($ns != 'irefindex_rogid' && $ns != 'irefindex_irogid') {
						if($ns) $this->AddRDF($this->QQuad("$ns:$id","irefindex_vocabulary:taxon-sequence-identical-group",$irogid));	
						if($taxid) $this->AddRDF($this->QQuad("$ns:$id","irefindex_vocabulary:taxon",$taxid));
					}
				}
			}	
			// add the aliases through the canonical group
			for($i=4;$i<=5;$i++) {
				$icrogid = "irefindex_icrogid:".$a[49+($i-4)];
				if(!isset($defined[$icrogid])) {
					$defined[$icrogid] = '';
					$this->AddRDF($this->QQuadL($icrogid,"rdfs:label","[$icrogid]"));			
					$this->AddRDF($this->QQuad($icrogid,"rdf:type","irefindex_vocabulary:Taxon-Sequence-Similar-Group"));			
				}

				$list = explode("|",$a[3]);
				foreach($list AS $item) {
					$this->ParseString($item,$ns,$id,$label);
					if($ns != 'crogid' && $ns != 'icrogid') {
						if($ns) $this->AddRDF($this->QQuad($ns.':'.$id,"irefindex_vocabulary:taxon-sequence-similar-group",$icrogid));	
					}
				}
			}

			// publications
			$list = explode("|",$a[8]);
			foreach($list AS $item) {
				if($item == '-') continue;
				$this->ParseString($item,$ns,$id,$label);
				$this->AddRDF($this->QQuad($iid,"irefindex_vocabulary:article","$ns:$id"));
			}
			
			// MI interaction type
			if($a[11] != '-' && $a[11] != 'NA') {
				$this->ParseString($a[11],$ns,$id,$label);
				$this->AddRDF($this->QQuad($iid,"rdf:type","$ns:$id"));
				if(!isset($defined["$ns:$id"])) {
					$defined["$ns:$id"] = '';
					$this->AddRDF($this->QQuadL("$ns:$id","rdfs:label","$label [$ns:$id]"));
				}
			}
			
			// source
			if($a[12] != '-') {
				$this->ParseString($a[12],$ns,$id,$label);
				$this->AddRDF($this->QQuad($iid,"irefindex_vocabulary:source","$ns:$id"));
			}
		
			// confidence
			$list = explode("|",$a[14]);
			foreach($list AS $item) {
				$this->ParseString($item,$ns,$id,$label);
				if($ns == 'lpr') {
					//  lowest number of distinct interactions that any one article reported
					$this->AddRDF($this->QQuadL($iid,"irefindex_vocabulary:minimum-number-interactions-reported",$id));
				} else if($ns == "hpr") {
					//  higher number of distinct interactions that any one article reports
					$this->AddRDF($this->QQuadL($iid,"irefindex_vocabulary:maximum-number-interactions-reported",$id));
				} else if($ns = 'hp') {
					//  total number of unique PMIDs used to support the interaction 
					$this->AddRDF($this->QQuadL($iid,"irefindex_vocabulary:number-supporting-articles",$id));				
				}
			}

			// expansion method
			if($a[15]) {
				$this->AddRDF($this->QQuadL($iid,"irefindex_vocabulary:expansion-method",$a[15]));
			}

			// host organism
			if($a[28] != '-') {
				$this->ParseString($a[28],$ns,$id,$label);
				$this->AddRDF($this->QQuad($iid,"irefindex_vocabulary:host-organism","$ns:$id"));
			}

			// created
			$this->AddRDF($this->QQuadL($iid,"dc:created", $a[30]));

			// taxon-sequence identical interaction group
			$this->AddRDF($this->QQuad($iid,"irefindex_vocabulary:taxon-sequence-identical-interaction-group", "irefindex_irigid:".$a[44]));

			// taxon-sequence similar interaction group
			$this->AddRDF($this->QQuad($iid,"irefindex_vocabulary:taxon-sequence-similar-interaction-group", "irefindex_crigid:".$a[50]));

			$this->WriteRDFBufferToWriteFile();
		}
	}

	function ParseString($string,&$ns,&$id,&$label)
	{
		$this->GetNS()->ParsePrefixedName($string,$ns,$str);
		$this->Parse4IDLabel($str,$id,$label);
		$ns = $this->getNSMap(strtolower(trim($ns)));
		$id = trim($id);
		$label = trim($label);
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
