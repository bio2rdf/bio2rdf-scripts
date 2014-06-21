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
		
		// set and print application parameters
		parent::addParameter('files',true,'all','all','all or comma-separated list of ontology short names to process');
		parent::addParameter('download_url',false,null,'http://tinyurl.com/lsregistry');
		
		parent::initialize();
	}

	function run() 
	{
		// setup the write file
		$odir = parent::getParameterValue("outdir");
		$ofile = "lsr.". parent::getParameterValue('output_format');
		$gz = false;
		if(strstr(parent::getParameterValue('output_format'), "gz")) {$gz = true;}
		parent::setWriteFile($odir.$ofile, $gz);
		$this->parse();
		parent::getWriteFile()->close();
	
		// write the metdata
		
	}
	
	function parse() 
	{
		$registry = $this->getRegistry()->getRegistry() ;
		foreach($registry AS $i => $r) {
			if(!isset($r['preferredPrefix'])) continue;
			$dataset = $r['preferredPrefix'];
			$id = $this->getNamespace().$r['preferredPrefix'];
			
			parent::addRDF( 
				parent::QQuad($id,"rdf:type","dcat:Dataset").
				parent::QQuadL($id,"dc:title",$r['title']).
				parent::QQuadL($id,"dc:description",$r['description']).
				parent::QQuadL($id,"rdfs:label", $r['title']." [".$id."]").
				parent::QQuadL($id,"dc:identifier",$id).
				parent::QQuadL($id,"bio2rdf:identifier", $r['preferredPrefix']).
				parent::QQuadL($id,"bio2rdf:namespace", "lsr")
//				parent::describeIndividual($id,$r['title'],"dcat:Dataset",$r['title'],$r['description'])
			);
			parent::addRDF(
				parent::triplifyString($id,$this->getVoc()."preferred-prefix",$r['preferredPrefix'])
			);
			
			if($r['alternatePrefix']) {
				foreach( explode(",",$r['alternatePrefix']) AS $syn) {
					if(trim($syn) == '') continue;
					$syn = $this->getRegistry()->normalizePrefix(preg_replace("/\([^\)]+/","",$syn));
					parent::addRDF(
						parent::QQuadL($id,$this->getVoc()."alternative-prefix",$syn)
					);
				}
			}
			if($r['providerURI']) {
				parent::QQuad($id,$this->getVoc()."preferred-base-uri",$r['providerURI']);
			}
			if($r['alternateURI']) {
				foreach( explode(",",$r['alternateURI']) AS $alt_uri) {
					parent::addRDF(
						parent::QQuad($id,$this->getVoc()."alternative-base-uri",$alt_uri)
					);
				}
			}
			if($r['miriam']) {
				foreach(explode(",",$r['miriam']) AS $miriam) {
					$miriam_id = str_replace("MIR:","",$miriam);
					parent::addRDF(
						parent::QQuad($id,$this->getVoc()."x-miriam","miriam:$miriam_id").
						parent::triplify("miriam:$miriam_id","bio2rdf_vocabulary:url",'http://identifiers.org/'.$dataset)
					);
				}
			}
			if($r['biodbcore']) {
				foreach(explode(",",$r['biodbcore']) AS $biodbcore_id) {
					$biodbcore = "biodbcore:$biodbcore_id";
					parent::addRDF(
						parent::QQuad($id,$this->getVoc()."x-biodbcore",$biodbcore).
						parent::triplify($biodbcore,"bio2rdf_vocabulary:url","http://www.biosharing.org/$biodbcore_id")
					);
				}
			}
			if($r['bioportal']) {
				foreach(explode(",",$r['bioportal']) AS $bioportal) {
					parent::addRDF(
						parent::QQuad($id,$this->getVoc()."x-bioportal","bioportal:".$bioportal)
					);
				}
			}
			if($r['datahub']) {
				parent::addRDF(
					parent::QQuad($id,$this->getVoc()."x-datahub","datahub:".$r['datahub'])
				);
			}
			if($r['pubmed']) {
				foreach(explode(",",$r['pubmed']) AS $pubmed) {
					parent::addRDF(
						parent::QQuad($id,$this->getVoc()."x-pubmed","pubmed:".$pubmed)
					);
				}
			}
			if($r['abbreviation']) {
				parent::addRDF(
					parent::QQuadL($id,$this->getVoc()."abbreviation",$r['abbreviation'])
				);
			}
			if($r['organization']) {
				parent::addRDF(
					parent::QQuadL($id,$this->getVoc()."organization",$r['organization'])
				);
			}
			if($r['type']) {
				parent::addRDF(
					parent::QQuadL($id,$this->getVoc()."type",$r['type'])
				);
			}
			foreach( explode(",",$r['keywords']) AS $keyword) {
				if($keyword) {
					parent::addRDF(
						parent::QQuadL($id,$this->getVoc()."keyword",$keyword)
					);
				}
			}
			if($r['homepage'] 
				&& $r['homepage'] !== 'dead'
				&& $r['homepage'] !== 'unavailable') {
				parent::addRDF(
					parent::QQuad($id,"foaf:homepage",$r['homepage'])
				);
			}
			if($r['license']) {
				parent::addRDF(
					parent::QQuad($id,$this->getVoc()."license",$r['license'])
				);
			}
			if($r['licenseText']) {
				parent::addRDF(
					parent::QQuadL($id,$this->getVoc()."license-text",$r['licenseText'])
				);
			}
			foreach(explode(",",$r['rights']) AS $right) {
				if($right) {
					parent::addRDF(
						parent::QQuadL($id,$this->getVoc()."right",$right)
					);
				}
			}
			parent::addRDF(
				parent::QQuadL($id,$this->getVoc()."id-regex",$r['id_regex'])
			);
			parent::addRDF(
				parent::QQuadL($id,$this->getVoc()."example-id",$r['example_id'])
			);
			if($r['html_template']
				&& $r['html_template'] !== 'unavailable'
				&& $r['html_template'] !== 'N/A') {

				parent::addRDF(
					parent::QQuadL($id,$this->getVoc()."html-template",$r['html_template'])
				);
			}

			// add the resource statement
			$resource = $dataset.'_vocabulary:Resource';
			parent::addRDF(
				parent::QQuad($resource,"sio:is-member-of",$id).
				parent::QQuad($id,"sio:has-member",$resource)
			);

			$this->setCheckpoint();
		}
	}
}

?>
