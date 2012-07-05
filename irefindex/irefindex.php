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
 * An RDF generator for iRefIndex (http://irefindex.uio.no)
 * documentation: http://irefindex.uio.no/wiki/README_MITAB2.6_for_iRefIndex_9.0
 * @version 1.0
 * @author Michel Dumontier
*/

require_once (dirname(__FILE__).'/../common/php/libphp.php');

$options = null;
AddOption($options, 'name', null, 'irefindex', false);
AddOption($options, 'indir', null, '/data/download/irefindex/', false);
AddOption($options, 'outdir',null, '/data/rdf/irefindex/', false);
AddOption($options, 'files',null,'All.mitab.10182011.txt.zip',true);
AddOption($options, 'remote_base_url',null,'ftp://ftp.no.embnet.org/irefindex/data/archive/release_9.0/psimi_tab/MITAB2.6/', false);
AddOption($options, 'download','true|false','false', false);
AddOption($options, CONF_FILE_PATH, null,'/bio2rdf-scripts/common/bio2rdf_conf.rdf', false);
AddOption($options, USE_CONF_FILE,'true|false','false', false);
if(SetCMDlineOptions($argv, $options) == FALSE) {
	PrintCMDlineOptions($argv, $options);exit;
}

@mkdir($options['indir']['value'],null,true);
@mkdir($options['outdir']['value'],null,true);
if($options['files']['value'] == 'all') {
	$files = explode("|",$options['files']['list']);
	array_shift($files);
} else {
	$files = explode("|",$options['files']['value']);
}

$date = date("d-m-y"); 
$releasefile_uri = $options['name']['value']."-$date.ttl";
$releasefile_uri = "http://download.bio2rdf.org/".$options['name']['value']."/".$releasefile_uri;

$header = N3NSHeader();
$header .= "<$releasefile_uri> a sio:Document .".PHP_EOL;
$header .= "<$releasefile_uri> rdfs:label \"Bio2RDF iRefIndex release in RDF/N3 [bio2rdf_file:irefindex.tgz]\".".PHP_EOL;
$header .= "<$releasefile_uri> rdfs:comment \"RDFized from iRefIndex psi-mi tab data files\".".PHP_EOL;
$header .= "<$releasefile_uri> dc:date \"".date("D M j G:i:s T Y")."\".".PHP_EOL;
file_put_contents($options['outdir']['value']."irefindex-$date.ttl",$header);

foreach($files AS $file) {
	$indir = $options['indir']['value'];
	$outdir = $options['outdir']['value'];
	
	$infile = $file;
	$outfile = $infile.".ttl";
	
	$zipfile = false;
	if(($pos = strpos($file,".zip")) !== FALSE) {
		$infile = substr($file,0,$pos);
		$zipfile = true;
	}

	// download the zip file if it doesn't exist, or have been mandated to do so
	if((!file_exists($indir.$file) && $zipfile) || $options['download']['value'] == 'true') {
		DownloadFiles($options['remote_base_url']['value'],array($file),$options['indir']['value']);
		if(!file_exists($indir.$file)) {
			trigger_error("error in downloading file");
			exit;
		}
	}
	// expand the zip file
	if($zipfile) {
		$zip = zip_open($indir.$file);
		if (is_resource($zip)) {
			while ($zip_entry = zip_read($zip)) {
				if (zip_entry_open($zip, $zip_entry, "r")) {
					echo 'expanding '.zip_entry_name($zip_entry).PHP_EOL;
					$fp = fopen($indir.zip_entry_name($zip_entry),"w");
					$total_size = zip_entry_filesize($zip_entry);
					$max_read_size = "100000000"; // 100MB
					do {
						
					} while (fwrite($fp, zip_entry_read($zip_entry, $read_size)) != 0);
					fclose($fp);
					zip_entry_close($zip_entry);
				}
			}
			zip_close($zip);
		}
	}


	$in = fopen($indir.$infile,"r");
	if($in === FALSE) {
		trigger_error("Unable to open ".$indir.$infile." for reading.");
		exit;
	}
	$out = fopen($outdir.$outfile,"w");
	if($out === FALSE) {
		trigger_error("Unable to open ".$outdir.$outfile." for writing.");
		exit;
	}
	$head = N3NSHeader();
	fwrite($out,$head);
	
	iRefIndex2RDF($in,$out);
	
	fclose($in);		
	fclose($out);
	
	unlink($indir.$infile);
	echo "done!".PHP_EOL;
}


function iRefIndex2RDF(&$in, &$out)
{
	global $releasefile_uri;
	$header = explode("\t", trim(substr(fgets($in,100000),1)));
	
	$buf = '';
	fgets($in);
	while($l = fgets($in,100000)) {
		$a = explode("\t",$l);
		
		$iid = "irefindex:".$a[50]; //icrigid This identifier serves to group together evidence for interactions that involve the same set (or a related set) of proteins.
		
		$buf .= Quad  ($releasefile_uri, GetFQURI("dc:subject"), GetFQURI($iid));
		
		// get the type
		if($a[52] == "C") $type = "Complex";
		else $type = "Interaction";
		$buf .= QQuad ($iid,"rdf:type","irefindex_vocabulary:$type");
		
		// generate the label
		// interaction type[52] by method[6]
		ParseQNAME($a[6],$ns,$str);
		Parse4IDLabel($str,$id,$method);
		$label = "$type by $method [$iid]";
		$buf .= QQuadL($iid,"rdfs:label",$label);
		
		foreach($a AS $k => $v) {
			$list = explode("|",trim($v));
			if($list[0][0] == "-") continue;
			foreach($list AS $item) {
				ParseQNAME($item,$ns,$str);
				Parse4IDLabel($str,$id,$label);
				
				$id = trim($id);
				
				if($header[$k] == "irogida")  $ns = "irogid";
				if($header[$k] == "irogidb")  $ns = "irogid";
				if($header[$k] == "rigid")    $ns = "rigid";
				if($header[$k] == "irigid")   $ns = "irigid";
				if($header[$k] == "crogida")  $ns = "crogid";
				if($header[$k] == "crogidb")  $ns = "crogid";
				if($header[$k] == "icrogida") $ns = "icrogid";
				if($header[$k] == "icrogidb") $ns = "icrogid";
				if($header[$k] == "crigid")   $ns = "crigid";
				if($header[$k] == "icrigid")  $ns = "icrigid";
				
				if($ns) {
					$ns = getNSMap(strtolower($ns));
				}
				if($ns) {
					if($ns == "edgetype") continue;
					if($ns == "lpr" || $ns == "hpr" || $ns == "np") {
						$buf .= QQuadL($iid, "irefindex_vocabulary:".$ns, $id);
						continue;
					}
					if($ns=="geneid" && ($header[$k] == "aliasA" || $header[$k] == "aliasB")) {
						$ns = "symbol";
					}
					$id = str_replace(" ","-",$id);
					$buf .= QQuad($iid, "irefindex_vocabulary:".strtolower($header[$k]), $ns.":".$id);
				} else {
					$buf .= QQuadL($iid, "irefindex_vocabulary:".strtolower($header[$k]), $id);
				}
			}
		}
		fwrite($out,$buf);
		fflush($out);
		$buf = '';
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
		'gb' => 'ncbi',
		'genbank_protein_gi' => 'ncbi',
		'taxid' => 'taxon',
		'uniprotkb' => 'uniprot',
		'uniprotkb/trembl' => 'uniprot',
		'entrezgene/locuslink' => 'geneid',
		'dbj' => 'ddbj',
		'kegg:ecj' => 'kegg',
		'mppi' => 'mips',
		'swiss-prot' => 'swissprot',
		'ddbj-embl-genbank' => 'ncbi',
		'ddbj/embl/genbank' => 'ncbi',
		'complex' => 'irefindex',
		'bind_translation' => 'bind',
		'genbank' => 'ncbi',
		'rcsb pdb' => 'pdb',
		'sp' => 'swissprot',
		'genbank indentifier' => 'ncbi',
		'entrez gene/locuslink' => 'geneid',
		'gi'=> 'ncbi',
		'uniprot knowledge base' => 'uniprot',
		'mpilit' => 'mpi',
		'mpiimex' => 'mpi',
		'xx' => '',
	);
	if(isset($nsmap[$ns])) return $nsmap[$ns];
	return $ns;
}

?>
