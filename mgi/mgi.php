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
 * An RDF generator for MGI
 * documentation: ftp://ftp.informatics.jax.org/pub/reports/index.html
 * @version 1.0
 * @author Michel Dumontier
*/

require('../../php-lib/rdfapi.php');
class MGIParser extends RDFFactory 
{
	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("sider");
		
		// set and print application parameters
		$this->AddParameter('files',true,'all|MGI_PhenotypicAllele','all','all or comma-separated list to process');
		$this->AddParameter('indir',false,null,'/data/download/mgi/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/mgi/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('graph_uri',false,null,null,'specify a graph uri to generate nquads');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'ftp://ftp.informatics.jax.org/pub/reports/');

		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) {
			$this->SetGraphURI($this->GetParameterValue('graph_uri'));
		}
				
		return TRUE;
	}
	
	function Run()
	{
		$idir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$files = $this->GetParameterValue('files');
		
		if($files == 'all') {
			$list = explode('|',$this->GetParameterList('files'));
			array_shift($list);
		} else {
			$list = explode('|',$this->GetParameterValue('files'));
		}
		
		foreach($list AS $item) {
		
			$lfile = $idir.$item.'.rpt';
			$rfile = $this->GetParameterValue('download_url').$item.'.rpt';
			if(!file_exists($lfile) || $this->GetParameterValue('download') == 'true') {
				echo "downloading $item...";
				$ret = file_get_contents($rfile);
				if($ret === FALSE) {
					trigger_error("Unable to get $rfile",E_USER_WARNING);
					continue;
				}
				$ret = file_put_contents($lfile,$ret);
				if($ret === FALSE) {
					trigger_error("Unable to write $lfile",E_USER_ERROR);
					exit;
				}		
				echo "done!".PHP_EOL;
			}
			$this->SetReadFile($lfile,true);
			
			$ofile = $odir."mgi-".$item.'.rdf.gz';
			$this->SetWriteFile($ofile,true);
			$this->$item();
			$this->GetWriteFile()->Close();
			$this->GetReadFile()->Close();
		}
	}
	
	/*
	0 MGI Allele Accession ID	
	1 Allele Symbol	
	2 Allele Name	
	3 Allele Type	
	4 PubMed ID for original reference	
	5 MGI Marker Accession ID	
	6 Marker Symbol	
	7 Marker RefSeq ID	
	8 Marker Ensembl ID	
	9 High-level Mammalian Phenotype ID (comma-delimited)	
	10 Synonyms (|-delimited)
	*/
	function MGI_PhenotypicAllele($qtl = false)
	{
		while($l = $this->GetReadFile()->Read(50000)) {
			$a = explode("\t",$l);
			$id = strtolower($a[0]);
			if($a[0][0] == "#") continue;
			//print_r($a);
			
			$this->AddRDF($this->QQuadL($id,"dc:identifier",$a[0]));
			$this->AddRDF($this->QQuad($id,"rdf:type","mgi_vocabulary:allele"));
			//$this->AddRDF($this->QQuadL($id,"rdfs:label",$a[2]." [$id]"));
			if(trim($a[1])) {
				$this->AddRDF($this->QQuadL($id,"mgi_vocabulary:allele-symbol",trim($a[1])));
			}
			if(trim($a[2])) {
				$this->AddRDF($this->QQuadL($id,"mgi_vocabulary:allele-name",trim($a[2])));
			}
			if(trim($a[3])) {
				$this->AddRDF($this->QQuadL($id,"mgi_vocabulary:allele-type",trim($a[3])));
			}
			if(trim($a[4])) {
				$this->AddRDF($this->QQuad($id,"mgi_vocabulary:pubmed-id","pubmed:".$a[4]));
			}
			if(trim($a[5])) {
				$marker_id = strtolower($a[5]);
				$this->AddRDF($this->QQuad($id,"mgi_vocabulary:marker",$marker_id));
				$this->AddRDF($this->QQuad($marker_id,"rdf:type","mgi_vocabulary:marker"));
		
				if(trim($a[6])) {
					$this->AddRDF($this->QQuadL($marker_id,"mgi_vocabulary:marker-symbol",strtolower($a[6])));
				}
				if(trim($a[7])) {
					$this->AddRDF($this->QQuad($marker_id,"mgi_vocabulary:refseq","refseq:".$a[7]));
				}		
				if(trim($a[8])) {
					$this->AddRDF($this->QQuad($marker_id,"mgi_vocabulary:ensembl","ensembl:".$a[8]));
				}
			}

			if(trim($a[9])) {
				$b = explode(",",$a[9]);
				foreach($b AS $mp) {
					$this->AddRDF($this->QQuadO_URL($id,"mgi_vocabulary:phenotype",str_replace("MP:","http://purl.obolibrary.org/obo/MP_",$mp)));
				}
			}
		}
		$this->WriteRDFBufferToWriteFile();	
	}
	
	function HMD_HGNC_Accession()
	{
		/*
		MGI Marker Accession ID	
		Mouse Marker Symbol	
		Mouse Marker Name	
		Mouse Entrez Gene ID	
		HGNC ID	
		HGNC Human Marker Symbol	
		Human Entrez Gene ID
		*/
		while($l = $this->GetReadFile()->Read(50000)) {
			$a = explode("\t",$l);

			$id = "hgnc:".$a[4];
			
			$this->AddRDF($this->QQuadL($id,"dc:identifier",$id));
			$this->AddRDF($this->QQuad($id,"rdf:type","hgnc:gene-map"));
			
			$this->AddRDF($this->QQuad($id,"hgnc_vocabulary:mouse-marker",strtolower($a[0])));
			$this->AddRDF($this->QQuad($id,"hgnc_vocabulary:mouse-ncgigene-id",$a[0])));
			
			if(trim($a[2])) {
				$this->AddRDF($this->QQuadL($id,"mgi_vocabulary:allele-name",trim($a[2])));
			}
			if(trim($a[3])) {
				$this->AddRDF($this->QQuadL($id,"mgi_vocabulary:allele-type",trim($a[3])));
			}
			if(trim($a[4])) {
				$this->AddRDF($this->QQuad($id,"mgi_vocabulary:pubmed-id","pubmed:".$a[4]));
			}
			if(trim($a[5])) {
				$marker_id = strtolower($a[5]);
				$this->AddRDF($this->QQuad($id,"mgi_vocabulary:marker",$marker_id));
				$this->AddRDF($this->QQuad($marker_id,"rdf:type","mgi_vocabulary:marker"));
		
				if(trim($a[6])) {
					$this->AddRDF($this->QQuadL($marker_id,"mgi_vocabulary:marker-symbol",strtolower($a[6])));
				}
				if(trim($a[7])) {
					$this->AddRDF($this->QQuad($marker_id,"mgi_vocabulary:refseq","refseq:".$a[7]));
				}		
				if(trim($a[8])) {
					$this->AddRDF($this->QQuad($marker_id,"mgi_vocabulary:ensembl","ensembl:".$a[8]));
				}
			}

			if(trim($a[9])) {
				$b = explode(",",$a[9]);
				foreach($b AS $mp) {
					$this->AddRDF($this->QQuadO_URL($id,"mgi_vocabulary:phenotype",str_replace("MP:","http://purl.obolibrary.org/obo/MP_",$mp)));
				}
			}
		}
		$this->WriteRDFBufferToWriteFile();	
	}
	
}

set_error_handler('error_handler');
$parser = new MGIParser($argv);
$parser->Run();
?>	
		