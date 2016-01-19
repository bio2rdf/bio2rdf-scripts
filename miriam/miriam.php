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

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../../php-lib/xmlapi.php');

/**
 * MIRIAM DB RDFizer
 * @version 1.0 
 * @author Michel Dumontier
 * @description http://www.ebi.ac.uk/miriam/
*/
class MIRIAMParser extends Bio2RDFizer 
{	
	private $version = null;
	
	function __construct($argv) {
		parent::__construct($argv,"miriam");
		parent::addParameter('files',true,'all','all','files to process');
		parent::addParameter('download_url',false,null,'http://www.ebi.ac.uk/miriam/main/export/xml/');
		parent::addParameter('overwrite',false,'true|false','false','overwrite existing files with download option');
		parent::initialize();
	}
	
	function Run()
	{	
		echo "processing miriam database";
	
		// directory shortcuts
		$ldir = $this->getParameterValue('indir');
		$odir = $this->getParameterValue('outdir');
		
		// download and set the read file
		$file = 'miriam.xml';
		$rfile = $this->getParameterValue("download_url");
		$lfile = $ldir.$file;
		if(!file_exists($lfile) || $this->getParameterValue("download") == "true") {
			utils::downloadSingle($rfile,$lfile);
		}
		
		parent::setReadFile($lfile);

		// set the write file
		$outfile = "miriam.".parent::getParameterValue('output_format');
		$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
		parent::setWriteFile(parent::getParameterValue("outdir").$outfile,$gz);		
		$this->parse();
		parent::WriteRDFBufferToWriteFile();
		$this->getWriteFile()->Close();
				
		return true;
	}	
	
	function parse()
	{
		// convert into json
		$lfile = parent::getReadFile()->getFileName();
		$xml = simplexml_load_file($lfile);
		$json = json_encode($xml);
		$db = json_decode($json,TRUE);
		
		// miriam metadata
		// $attributes = $db['@attributes'];
		foreach($db['datatype'] AS $item) {
			$this->parseItem($item);
		}
	}
	
	function parseItem($item)
	{
		$id = $item['@attributes']['id'];
		$label = $item['name'];
		
		parent::addRDF(
			parent::describeIndividual($id, $item['name'], parent::getVoc()."Entry").
			parent::describeClass(parent::getVoc()."Entry","MIRIAM database entry").
			parent::triplifyString($id, parent::getVoc()."namespace", $item['namespace'])
		);
		
		if(isset($item['@attributes'])) {
			foreach($item['@attributes'] AS $k => $v) {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc().$k, $v)
				);
			}
		}
		if(isset($item['comment'])) parent::addRDF(parent::triplifyString($id, parent::getVoc()."comment", $item['comment']));
		if(isset($item['definition'])) parent::addRDF(parent::triplifyString($id, parent::getVoc()."definition", $item['definition']));
		if(isset($item['synonyms'])) {
			$mylist = null;
			if(is_array($item['synonyms']['synonym'])) $mylist = $item['synonyms']['synonym'];
			else $mylist[] = $item['synonyms']['synonym'];
			foreach($mylist AS $myitem) {
				parent::addRDF(
					parent::triplifyString($id, "skos:altLabel", $myitem)
				);
			}
		}

		if(isset($item['uris'])) {
			foreach($item['uris']['uri'] AS $uri) {
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."uri", $uri)
				);
			}
		}
		if(isset($item['resources'])) {		
			$mylist = null;
			if(!isset($item['resources']['resource']['dataEntry'])) $mylist = $item['resources']['resource'];
			else $mylist[] = $item['resources']['resource'];
			foreach($mylist AS $myitem) {
				$rid = $myitem['@attributes']['id'];
				parent::addRDF(
					parent::describeIndividual($rid, $myitem['dataInfo'], parent::getVoc()."Resource").
					parent::describeClass(parent::getVoc()."Resource", "MIRIAM Resource").
					parent::triplify($rid, parent::getVoc()."url", $myitem['dataResource']).
					parent::triplifyString($rid, parent::getVoc()."urlTemplate", $myitem['dataEntry']).
					parent::triplifyString($rid, parent::getVoc()."organization", is_array($myitem['dataInstitution'])?"":$myitem['dataInstitution']).
					parent::triplifyString($rid, parent::getVoc()."location", is_array($myitem['dataLocation'])?"":$myitem['dataLocation']).
					parent::triplify($id, parent::getVoc()."resource", $rid)
				);
			}
		}
		if(isset($item['tags'])) {
			$i = $item['tags']['tag'];
			$mylist = null;
			if(!is_array($i)) $mylist[] = $i;
			else $mylist = $i;
			foreach($mylist AS $myitem) {
				parent::addRDF(
					parent::triplifyString($id, parent::getvoc()."tag", $myitem)
				);
		}}

		if(isset($item['documentations'])) {
			$i = $item['documentations']['documentation'];
			$mylist = null;
			if(!is_array($i)) $mylist[] = $i;
			else $mylist = $i;
			foreach($mylist AS $myitem) {
				if(strstr($myitem, "pubmed")) $uri = "pubmed:".substr($myitem, strrpos($myitem, ":")+1);
				else if(strstr($myitem, "doi")) $uri = "http://dx.doi.org/".substr($myitem, strpos($myitem, "doi:"));
				else $uri = $myitem;

				parent::addRDF(
					parent::triplify($id, parent::getvoc()."documentation", $uri)
				);
		}}
		
		if(isset($item['restrictions'])) {
			$mylist = null;
			if(!isset($item['restrictions']['restriction']['statement'])) $mylist = $item['restrictions']['restriction'];
			else $mylist[] = $item['restrictions']['restriction'];			
			foreach($mylist AS $i => $myitem) {
				$rid = parent::getRes().str_replace(":","",$id)."_".($i+1);
				$a = $myitem['@attributes'];
				$rid_type = parent::getVoc().'restriction_type_'.$a['type'];
				$page = '';
				if(isset($myitem['link']) and strstr($myitem['link'],"http") !== FALSE) $page = $myitem['link'];

				parent::addRDF(
					parent::describeIndividual($rid, $a['desc'], parent::getVoc()."Restriction").
					parent::describeClass(parent::getVoc()."Restriction", "Resource Restriction").
					parent::triplify($rid, "rdf:type", $rid_type).
					parent::describeClass($rid_type, $a['desc'], parent::getVoc()."Restriction").
					parent::triplifyString($rid, "dct:description", $myitem['statement']).
					parent::triplify($rid, "foaf:page", $page).
					parent::triplify($id, parent::getVoc()."restriction", $rid)
				);
		}}
		
		/*
		<annotation>
			<format name="SBML">
				<elements>
					<element>reaction</element>
					<element>event</element>
					<element>rule</element>
					<element>species</element>
				</elements>
			</format>
		*/
		if(isset($item['annotation'])) {
			$mylist = null;
			if(!isset($item['annotation']['format']['elements'])) $mylist = $item['annotation']['format'];
			else $mylist[] = $item['annotation']['format'];	
			foreach($mylist AS $i => $myitem) {
				$name = $myitem['@attributes']['name'];
				$myid = str_replace("MIR:",parent::getRes(), $id)."_annotation_".($i+1)."_".urlencode($name);
				parent::addRDF(
					parent::describeIndividual($myid, "$label used by $name", parent::getVoc()."ValueSet").
					parent::describeClass(parent::getVoc()."ValueSet", "MIRIAM Value Set").
					parent::triplifyString($myid, parent::getVoc()."used-in", $name).
					parent::triplify($myid, parent::getVoc()."uses", $id)
				);
				
				$b = $myitem['elements']['element'];
				$mylist2 = null;
				if(!is_array($b)) $mylist2[] = $b;
				else $mylist2 = $b;
				foreach($mylist2 AS $i => $e) {
					parent::addRDF(
						parent::triplifyString($myid, parent::getVoc()."used-for", $e)
					);
				}
			}
			
		}

	}

}

?>
