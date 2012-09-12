<?php
/**
Copyright (C) 2012 Jose Cruz-Toledo

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
 * Bio2RDF DBpedia RDFizer
 * @version 0.1
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ncbi.nih.gov/gene/DATA/
 * http://wiki.dbpedia.org/Downloads38
*/

require('../../php-lib/rdfapi.php');
class DBpedia extends RDFFactory{
	private  $bio2rdf_base = "http://bio2rdf.org/";
	private  $dbpedia_vocab ="dbpedia_vocab:";
	private  $dbpedia_resource = "dbpedia_resource:";
	private $version = 3.8;

	function __construct($argv) {
			parent::__construct();
			$this->SetDefaultNamespace("dbpedia");
			// set and print application parameters
			$this->AddParameter('files',true,null,'all|descriptor_records|qualifier_records|supplementary_records','','files to process');
			$this->AddParameter('indir',false,null,'/home/jose/tmp/mesh/','directory to download into and parse from');
			$this->AddParameter('outdir',false,null,'/home/jose/tmp/mesh/n3','directory to place rdfized files');
			$this->AddParameter('gzip',false,'true|false','true','gzip the output');
			$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
			$this->AddParameter('download',false,'true|false','false','set true to download files');
			$this->AddParameter('download_url',false,null,'http://www.nlm.nih.gov/cgi/request.meshdata');
			if($this->SetParameters($argv) == FALSE) {
				$this->PrintParameters($argv);
				exit;
			}
			if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
			if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
			if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
		return TRUE;
	}//constructor

}

?>