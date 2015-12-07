<?php
/**
Copyright (C) 2012-2013 Michel Dumontier

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
require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../../php-lib/xmlapi.php');
/**
 * InterPro RDFizer
 * @version 2.0 
 * @author Michel Dumontier
 * @description http://www.ebi.ac.uk/interpro/
*/
class InterproParser extends Bio2RDFizer 
{	
	private $version = null;

	function __construct($argv) {
		parent::__construct($argv,"interpro");
		parent::addParameter('files',true,'all','all','');
		parent::addParameter('download_url',false,null,'ftp://ftp.ebi.ac.uk/pub/databases/interpro/interpro.xml.gz','');
		parent::initialize();
	}
	
	function Run()
	{	
		// directory shortcuts
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		
		// get the listings page
		$rfile = trim(parent::getParameterValue('download_url'));
		$file = "interpro.xml.gz";
		$lfile = $ldir.$file;
		if(!file_exists($lfile) || parent::getParameterValue("download") == "true") {
			echo "Downloading $lfile".PHP_EOL;
			$ret = file_get_contents($rfile);
			if($ret === FALSE) {
				trigger_error("unable to download $rfile");
				exit;
			}
			file_put_contents($lfile,$ret);
		}
		echo "Loading XML file...";
		$cxml = new CXML($lfile);
		$cxml->Parse();
		$xml = $cxml->GetXMLRoot();	
		echo "Done".PHP_EOL;
		
		// set the write file
		$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
		$outfile = "interpro.".parent::getParameterValue('output_format'); 	
		parent::setWriteFile($odir.$outfile, $gz);
		
		echo "Parsing interpro xml file".PHP_EOL;
		$this->parse($xml);		
		parent::writeRDFBufferToWriteFile();
		parent::getWriteFile()->close();	
		echo "Done!".PHP_EOL;


		// let's make an nq file
		parent::setGraphURI(parent::getDatasetURI());
		
		// dataset description
		$source_version = parent::getDatasetVersion();
		$source_file = (new DataResource($this))
		->setURI($rfile)
		->setTitle("InterPro v$source_version")
		->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
		->setFormat("application/xml")
		->setFormat("application/g-zip")
		->setPublisher("http://www.ebi.ac.uk/")
		->setHomepage("http://www.ebi.ac.uk/interpro/")
		->setRights("InterPro - Integrated Resource Of Protein Domains And Functional Sites. Copyright (C) 2001 The InterPro Consortium")
		->setLicense("http://www.ebi.ac.uk/interpro/faqs.html")
		->setDataset("http://identifiers.org/interpro/");
	
		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");
		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$outfile")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix v$source_version")
			->setSource($source_file->getURI())
			->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/interpro/interpro.php")
			->setCreateDate($date)
			->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
			->setPublisher("http://bio2rdf.org")			
			->setRights("use-share-modify")
			->setRights("by-attribution")
			->setRights("restricted-by-source-license")
			->setLicense("http://creativecommons.org/licenses/by/3.0/")
			->setDataset(parent::getDatasetURI());

		if($gz) $output_file->setFormat("application/gzip");
		if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
		else $output_file->setFormat("application/n-quads");
		
		$dataset_description = $source_file->toRDF().$output_file->toRDF();

		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		
		return true;
	}	
	
	function Parse($xml)
	{
		// state the dataset info
		foreach($xml->release->dbinfo AS $o) {
			$db = $o->attributes()->dbname." v".$o->attributes()->version." (".$o->attributes()->entry_count." entries) [".$o->attributes()->file_date."]";
			parent::addRDF(
				parent::triplifyString(parent::getDatasetURI(), parent::getVoc()."contains", $db)
			);
			if(((string)$o->attributes()->dbname) === "INTERPRO") {
				parent::setDatasetVersion($o->attributes()->version);
			}
		}
		// get a potential id list
		if(parent::getParameterValue("id_list") != '') $id_list = explode(",",parent::getParameterValue("id_list"));
		
		// now interate over the entries
		foreach($xml->interpro AS $o) {
			parent::writeRDFBufferToWriteFile();

			$interpro_id = $o->attributes()->id;
			if(isset($id_list) && !in_array($interpro_id,$id_list)) {
				continue;
			}
			echo "Processing $interpro_id".PHP_EOL;
			
			$name = $o->name;
			$short_name = $o->attributes()->short_name;
			$type = $o->attributes()->type;
			$s = parent::getNamespace().$interpro_id;
			
			//echo "Adding... $s rdfs:label $name ($short_name) $type [$s]".PHP_EOL;
			parent::addRDF(
				parent::describeIndividual($s,"$name ($short_name) $type", parent::getVoc().$type)
			);
			
			// get the pubs
			unset($pubs);
			foreach($o->pub_list->publication AS $p) {
				$pid = (string) $p->attributes()->id;
				if(isset($p->db_xref)) {
					if($p->db_xref->attributes()->db == "PUBMED") {
						$pmid = (string) $p->db_xref->attributes()->dbkey;
						$pubs['pid'][] = '<cite idref="'.$pid.'"/>';
						$pubs['pmid'][] = '<a href="http://www.ncbi.nlm.nih.gov/pubmed/'.$pmid.'">pubmed:'.$pmid.'</a>';
						parent::addRDF(
							parent::triplify($s,parent::getVoc()."x-pubmed","pubmed:$pmid")
						);
					}
				}
			}
			$abstract = (string) $o->abstract->p->asXML();
			if(isset($pubs)) {
				$abstract = str_replace($pubs['pid'],$pubs['pmid'],$abstract);
			}
			
			parent::addRDF(
				parent::triplifyString($s,"dc:description",$abstract)
			);
			
			if(isset($o->example_list)) {
				foreach($o->example_list->example AS $example) {
					$db = (string) $example->db_xref->attributes()->db;
					$id = (string) $example->db_xref->attributes()->dbkey;
					parent::addRDF(
						parent::triplify($s,parent::getVoc()."example-entry", "$db:$id")
					);
				}
			}
			if(isset($o->parent_list->rel_ref)) {
				foreach($o->parent_list->rel_ref AS $parent) {
					$id = (string) $parent->attributes()->ipr_ref;
					parent::addRDF(
						parent::triplify($s,parent::getVoc()."parent", "interpro:$id")
					);
				}
			}
			if(isset($o->child->rel_ref)) {
				foreach($o->child->rel_ref AS $child) {
					$id = (string) $child->attributes()->ipr_ref;
					parent::addRDF(
						parent::triplify($s,parent::getVoc()."child", "interpro:$id")
					);
				}
			}
			if(isset($o->contains->rel_ref)) {
				foreach($o->contains->rel_ref AS $contains) {
					$id = (string) $contains->attributes()->ipr_ref;
					parent::addRDF(
						parent::triplify($s,parent::getVoc()."contains", "interpro:$id")
					);
				}
			}
			if(isset($o->found_in->rel_ref)) {
				foreach($o->found_in->rel_ref AS $f) {
					$id = (string) $f->attributes()->ipr_ref;
					parent::addRDF(
						parent::triplify($s,parent::getVoc()."found-in", "interpro:$id")
					);
				}
			}
			if(isset($o->sec_list->sec_ac)) {
				foreach($o->sec_ac AS $s) {
					$id = (string) $s->attributes()->acc;
					parent::addRDF(
						parent::triplify($s,parent::getVoc()."secondary-accession", "interpro:$id")
					);
				}
			}
			
			
			// xrefs
			if(isset($o->member_list->dbxref)) {
				foreach($o->member_list->db_xref AS $dbxref) {
					$db = (string) $dbxref->attributes()->db;
					$id = (string) $dbxref->attributes()->dbkey;
					parent::addRDF(
						parent::triplify($s,parent::getVoc()."x-".strtolower($db), "$db:$id")
					);
				}
			}
			if(isset($o->external_doc_list)) {
				foreach($o->external_doc_list->db_xref AS $dbxref) {
					$db = (string) $dbxref->attributes()->db;
					$id = (string) $dbxref->attributes()->dbkey;
					parent::addRDF(
						parent::triplify($s,parent::getVoc()."x-".strtolower($db), "$db:$id")
					);
				}
			}
			if(isset($o->structure_db_links->db_xref)) {
				foreach($o->structure_db_links->db_xref AS $dbxref) {
					$db = (string) $dbxref->attributes()->db;
					$id = (string) $dbxref->attributes()->dbkey;
					parent::addRDF(
						parent::triplify($s,parent::getVoc()."x-".strtolower($db), "$db:$id")
					);
				}
			}
			
			// taxon distribution
			foreach($o->taxonomy_distribution->taxon_data AS $t) {
				$organism = (string) $t->attributes()->name;
				$number = (string) $t->attributes()->proteins_count;
				parent::addRDF(
					parent::triplifyString($s,parent::getVoc()."taxon-distribution", "$organism ($number)")
				);
			}
		}
	}
}
?>
