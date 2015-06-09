<?php
/**
Copyright (C) 2011-2013 Michel Dumontier

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
 * An RDF generator for the life science registry
 * @version 1.0
 * @author Michel Dumontier
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class LSRParser extends Bio2RDFizer
{
	function __construct($argv) {
		parent::__construct($argv, "lsr");
		parent::addParameter('files',true,'all','all','all or comma-separated list of ontology short names to process');
		parent::addParameter('download_url',false,null,'http://tinyurl.com/lsregistry');
		parent::initialize();
	}

	function run()
	{
		// setup the write file
		$rfile = parent::getParameterValue("download_url");
		$idir  = parent::getParameterValue("indir");
		$ifile = "registry.csv";
		$lfile = $idir.$ifile;
		if(!file_exists($lfile) or parent::getParameterValue("download") == "true") {
			echo "Downloading registry";
			utils::downloadSingle($rfile,$lfile);
			echo "done".PHP_EOL;
		}

		echo "Processing registry ...";
		$odir = parent::getParameterValue("outdir");
		$ofile = "lsr.". parent::getParameterValue('output_format');
		$gz = false;
		if(strstr(parent::getParameterValue('output_format'), "gz")) {$gz = true;}
		parent::setWriteFile($odir.$ofile, $gz);
		$this->parse();
		parent::getWriteFile()->close();

		// dataset description
		$source_file = (new DataResource($this))
                        ->setURI("http://tinyurl.com/lsregistry")
                        ->setTitle("Life Science Registry")
                        ->setRetrievedDate( parent::getDate(filemtime($lfile)))
                        ->setFormat("text/tab-separated-value")
                        ->setPublisher("http://bio2rdf.org")
                        ->setHomepage("http://tinyurl.com/lsregistry")
                        ->setRights("use-share-modify")
                        ->setLicense("http://creativecommons.org/licenses/by/3.0/")
                        ->setDataset("http://identifiers.org/lsr/");

		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = parent::getDate(filemtime($odir.$ofile));

		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix")
			->setSource($source_file->getURI())
			->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/lsr/lsr.php")
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
                $this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
                $this->getWriteFile()->write($dataset_description);
                $this->getWriteFile()->close();
		echo "Done".PHP_EOL;
	}

	function parse() 
	{
		$registry = $this->getRegistry()->getRegistry() ;
		foreach($registry AS $i => $r) {
			if(!isset($r['preferredPrefix'])) continue;
			$dataset = $r['preferredPrefix'];
			$id = $this->getNamespace().$r['preferredPrefix'];

			parent::addRDF( 
				parent::describeIndividual($id,$r['title'],"lsr_vocabulary:Dataset",$r['title'],$r['description']).
				parent::describeClass("lsr_vocabulary:Dataset","LSR Dataset").
				parent::triplify($id,"rdf:type","dctypes:Dataset")
			);
			parent::addRDF(
				parent::triplifyString($id,"idot:preferredPrefix",$r['preferredPrefix'])
			);
			
			if($r['alternatePrefix']) {
				foreach( explode(",",$r['alternatePrefix']) AS $syn) {
					if(trim($syn) == '') continue;
					$syn = $this->getRegistry()->normalizePrefix(preg_replace("/\([^\)]+/","",$syn));
					parent::addRDF(
						parent::triplifyString($id,"idot:alternativePrefix",$syn)
					);
				}
			}
			if($r['providerURI']) {
				parent::triplifyString($id,"void:uriRegexPattern",$r['providerURI'], "xsd:anyUri");
			}
			if($r['alternateURI']) {
				foreach( explode(",",$r['alternateURI']) AS $alt_uri) {
					if(trim($alt_uri) != '') {
						parent::addRDF(
							parent::triplifyString($id,"void:uriRegexPattern",$alt_uri, "xsd:anyUri")
						);
					}
				}
			}
			if($r['miriam']) {
				foreach(explode(",",$r['miriam']) AS $miriam) {
					$miriam_id = str_replace("MIR:","",$miriam);
					parent::addRDF(
						parent::triplify($id,$this->getVoc()."x-miriam","miriam:$miriam_id").
						parent::triplify("miriam:$miriam_id","bio2rdf_vocabulary:url",'http://identifiers.org/'.$dataset)
					);
				}
			}
			if($r['biodbcore']) {
				foreach(explode(",",$r['biodbcore']) AS $biodbcore_id) {
					$biodbcore = "biodbcore:$biodbcore_id";
					parent::addRDF(
						parent::triplify($id,$this->getVoc()."x-biodbcore",$biodbcore).
						parent::triplify($biodbcore,"bio2rdf_vocabulary:url","http://www.biosharing.org/$biodbcore_id")
					);
				}
			}
			if($r['bioportal']) {
				foreach(explode(",",$r['bioportal']) AS $bioportal) {
					parent::addRDF(
						parent::triplify($id,$this->getVoc()."x-bioportal","bioportal:".$bioportal)
					);
				}
			}
			if($r['datahub']) {
				parent::addRDF(
					parent::triplify($id,$this->getVoc()."x-datahub","datahub:".$r['datahub'])
				);
			}
			if($r['pubmed']) {
				foreach(explode(",",$r['pubmed']) AS $pubmed) {
					parent::addRDF(
						parent::triplify($id,"cito:citesAsAuthority","pubmed:".$pubmed)
//						parent::triplify("pubmed:".$pubmed, "rdf:type", "pubmed_vocabulary:Resource")
					);
				}
			}
			if($r['abbreviation']) {
				parent::addRDF(
					parent::triplifyString($id,"dc:alternative",$r['abbreviation'])
				);
			}
			if($r['organization']) {
				$pid = parent::getRes().md5($r['organization']);
				parent::addRDF(
					parent::triplify($id,"dc:publisher", $pid).
					parent::triplifyString($pid, "dc:title", $r['organization'])
				);
			}
			if($r['type']) {
				parent::addRDF(
					parent::triplifyString($id,$this->getVoc()."type",$r['type'])
				);
			}
			foreach( explode(",",$r['keywords']) AS $keyword) {
				if($keyword) {
					parent::addRDF(
						parent::triplifyString($id,"dcat:keyword",$keyword)
					);
				}
			}
			if($r['homepage'] 
				&& $r['homepage'] !== 'dead'
				&& $r['homepage'] !== 'unavailable') {
				parent::addRDF(
					parent::triplify($id,"foaf:page",$r['homepage'])
				);
			}
			if($r['license']) {
				parent::addRDF(
					parent::triplify($id,"dc:license",$r['license'])
				);
			}
			if($r['licenseText']) {
				parent::addRDF(
					parent::triplifyString($id,$this->getVoc()."license-text",$r['licenseText'])
				);
			}
			foreach(explode(",",$r['rights']) AS $right) {
				if($right) {
					parent::addRDF(
						parent::triplifyString($id,"dc:rights",$right)
					);
				}
			}
			parent::addRDF(
				parent::triplifyString($id,"idot:identifierPattern",$r['id_regex'])
			);
			parent::addRDF(
				parent::triplifyString($id,"idot:exampleIdentifier",$r['example_id'])
			);
			if($r['html_template']
				&& $r['html_template'] !== 'unavailable'
				&& $r['html_template'] !== 'N/A') {

				parent::addRDF(
					parent::triplifyString($id,"idot:accessPattern",$r['html_template'])
				);
			}

			// add the resource statement
			$resource = $dataset.'_vocabulary:Resource';
			parent::addRDF(
				parent::QQuad($resource,"rdf:type","owl:Class").
				parent::QQuad($resource,"sio:is-member-of",$id).
				parent::QQuad($id,"sio:has-member",$resource)
			);

			$this->setCheckpoint();
		}
	}
}

?>
