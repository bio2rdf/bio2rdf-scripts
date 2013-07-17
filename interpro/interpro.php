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
require('../../php-lib/rdfapi.php');
require('../../php-lib/xmlapi.php');
/**
 * InterPro RDFizer
 * @version 1.0 
 * @author Michel Dumontier
 * @description http://www.ebi.ac.uk/interpro/
*/
class AffymetrixParser extends RDFFactory 
{	
	private $version = null;
	
	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("interpro");
		
		// set and print application parameters
		$this->AddParameter('files',true,'all','all','');
		$this->AddParameter('indir',false,null,'/data/download/'.$this->GetNamespace().'/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/'.$this->GetNamespace().'/','directory to place rdfized files');
		$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'ftp://ftp.ebi.ac.uk/pub/databases/interpro/interpro.xml.gz','');

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
		// directory shortcuts
		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		
		
		// get the listings page
		$rfile = trim($this->GetParameterValue('download_url'));
		$file = "interpro.xml.gz";
		$lfile = $ldir.$file;
		if(!file_exists($lfile) || $this->GetParameterValue("download") == "true") {
			echo "Downloading $lfile".PHP_EOL;
			$ret = file_get_contents($rfile);
			if($ret === FALSE) {
				trigger_error("unable to download $rfile");
				exit;
			}
			file_put_contents($lfile,$ret);
		}
		$cxml = new CXML($ldir,$file);
		$cxml->Parse();
		$xml = $cxml->GetXMLRoot();
		
		
		// set the write file
		$outfile = 'interpro.nt'; $gz=false;
		if($this->GetParameterValue('graph_uri')) {$outfile = 'interpro.nq';}
		if($this->GetParameterValue('gzip')) {
			$outfile .= '.gz';
			$gz = true;
		}
		$this->SetWriteFile($odir.$outfile, $gz);
		
		echo "Parsing interpro xml file".PHP_EOL;
		$this->Parse($xml);		
		$this->WriteRDFBufferToWriteFile();
		$this->GetWriteFile()->Close();	
		echo "Done!".PHP_EOL;
	
		// generate the release file
		$this->DeleteBio2RDFReleaseFiles($odir);
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/interpro/intepro.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()).$outfile,
			"http://www.ebi.ac.uk/interpro/",
			array("use-share-modify"),
			null, // license
			$this->GetParameterValue('download_url'),
			$this->version
		);
		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();
		
		return true;
	}	
	
	function Parse($xml)
	{
		// state the dataset info
		foreach($xml->release->dbinfo AS $o) {
			$db = $o->attributes()->dbname." v".$o->attributes()->version." (".$o->attributes()->entry_count." entries) [".$o->attributes()->file_date."]";
			$this->AddRDF($this->QQuadL($this->GetDatasetURI(), "interpro_vocabulary:contains", $db));
		}
		// now interate over the entries
		foreach($xml->interpro AS $o) {
			$this->WriteRDFBufferToWriteFile();

			$interpro_id = $o->attributes()->id;
			echo "Processing id... $interpro_id".PHP_EOL;
			
			$name = $o->name;
			$short_name = $o->attributes()->short_name;
			$type = $o->attributes()->type;
			$s = "interpro:$interpro_id";
			
			echo "Adding... $s rdfs:label $name ($short_name) $type [$s]".PHP_EOL;
			$this->AddRDF($this->QQuadL($s,"rdfs:label","$name ($short_name) $type [$s]"));
			$this->AddRDF($this->QQuad($s,"rdf:type","interpro_vocabulary:$type"));
			$this->AddRDF($this->QQuad($s,"void:inDataset",$this->GetDatasetURI()));
			
			// get the pubs
			unset($pubs);
			foreach($o->pub_list->publication AS $p) {
				$pid = (string) $p->attributes()->id;
				if(isset($p->db_xref)) {
					if($p->db_xref->attributes()->db == "PUBMED") {
						$pmid = (string) $p->db_xref->attributes()->dbkey;
						$pubs['pid'][] = '<cite idref="'.$pid.'"/>';
						$pubs['pmid'][] = '<a href="http://www.ncbi.nlm.nih.gov/pubmed/'.$pmid.'">pubmed:'.$pmid.'</a>';
						$this->AddRDF($this->QQuad($s,"interpro_vocabulary:x-pubmed","pubmed:$pmid"));
					}
				}
			}
			$abstract = (string) $o->abstract->p->asXML();
			if(isset($pubs)) {
				$abstract = str_replace($pubs['pid'],$pubs['pmid'],$abstract);
			}
			
			$this->AddRDF($this->QQuadL($s,"dc:description",$this->SafeLiteral($abstract)));
			
			foreach($o->example_list->example AS $example) {
				$db = (string) $example->db_xref->attributes()->db;
				$id = (string) $example->db_xref->attributes()->dbkey;
				$this->AddRDF($this->QQuad($s,"interpro_vocabulary:example-entry", $this->GetNS()->MapQName("$db:$id")));
			}
			
			if(isset($o->parent_list->rel_ref)) {
				foreach($o->parent_list->rel_ref AS $parent) {
					$id = (string) $parent->attributes()->ipr_ref;
					$this->AddRDF($this->QQuad($s,"interpro_vocabulary:parent", "interpro:$id"));
				}
			}
			if(isset($o->child->rel_ref)) {
				foreach($o->child->rel_ref AS $child) {
					$id = (string) $child->attributes()->ipr_ref;
					$this->AddRDF($this->QQuad($s,"interpro_vocabulary:child", "interpro:$id"));
				}
			}
			if(isset($o->contains->rel_ref)) {
				foreach($o->contains->rel_ref AS $contains) {
					$id = (string) $contains->attributes()->ipr_ref;
					$this->AddRDF($this->QQuad($s,"interpro_vocabulary:contains", "interpro:$id"));
				}
			}
			if(isset($o->found_in->rel_ref)) {
				foreach($o->found_in->rel_ref AS $f) {
					$id = (string) $f->attributes()->ipr_ref;
					$this->AddRDF($this->QQuad($s,"interpro_vocabulary:found-in", "interpro:$id"));
				}
			}
			if(isset($o->sec_list->sec_ac)) {
				foreach($o->sec_ac AS $s) {
					$id = (string) $s->attributes()->acc;
					$this->AddRDF($this->QQuad($s,"interpro_vocabulary:secondary-accession", "interpro:$id"));
				}
			}
			
			
			// xrefs
			if(isset($o->member_list->dbxref)) {
				foreach($o->member_list->db_xref AS $dbxref) {
					$db = (string) $dbxref->attributes()->db;
					$id = (string) $dbxref->attributes()->dbkey;
					$this->AddRDF($this->QQuad($s,"interpro_vocabulary:x-".strtolower($db), "$db:$id"));
				}
			}
			if(isset($o->external_doc_list)) {
				foreach($o->external_doc_list->db_xref AS $dbxref) {
					$db = (string) $dbxref->attributes()->db;
					$id = (string) $dbxref->attributes()->dbkey;
					$this->AddRDF($this->QQuad($s,"interpro_vocabulary:x-".strtolower($db), "$db:$id"));
				}
			}
			if(isset($o->structure_db_links->db_xref)) {
				foreach($o->structure_db_links->db_xref AS $dbxref) {
					$db = (string) $dbxref->attributes()->db;
					$id = (string) $dbxref->attributes()->dbkey;
					$this->AddRDF($this->QQuad($s,"interpro_vocabulary:x-".strtolower($db), "$db:$id"));
				}
			}
			
			// taxon distribution
			foreach($o->taxonomy_distribution->taxon_data AS $t) {
				$organism = (string) $t->attributes()->name;
				$number = (string) $t->attributes()->proteins_count;
				$this->AddRDF($this->QQuadL($s,"interpro_vocabulary:taxon-distribution", "$organism ($number)"));
			}
		}
	}

}
$start = microtime(true);

set_error_handler('error_handler');
$parser = new AffymetrixParser($argv);
$parser->Run();

$end = microtime(true);
$time_taken =  $end - $start;
print "Started: ".date("l jS F \@ g:i:s a", $start)."\n";
print "Finished: ".date("l jS F \@ g:i:s a", $end)."\n";
print "Took: ".$time_taken." seconds\n"
?>


