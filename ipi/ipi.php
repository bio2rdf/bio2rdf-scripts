<?php
/**
Copyright (C) 2013 Jose Cruz-Toledo and Alison Callahan

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
 * International Protein Index parser
 * @version 2.0
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ebi.ac.uk/pub/databases/IPI/last_release/current/Final%20Release%20of%20IPI
*/
class IPI extends Bio2RDFizer{
	private static $packageMap = array(
		"species_xrefs" =>array(
			"ipi.ARATH.xrefs.gz",
			"ipi.CHICK.xrefs.gz",
			"ipi.BOVIN.xrefs.gz", 
			"ipi.DANRE.xrefs.gz", 
			"ipi.HUMAN.xrefs.gz",
			"ipi.MOUSE.xrefs.gz",
			"ipi.RAT.xrefs.gz"
		),
		"gene_xrefs" => array(
			"ipi.genes.ARATH.xrefs.gz",
			"ipi.genes.BOVIN.xrefs.gz",
			"ipi.genes.CHICK.xrefs.gz",
			"ipi.genes.DANRE.xrefs.gz"
		),
		"gi2ipi" => array(
			"gi2ipi.xrefs.gz"
		)
	);

	function __construct($argv){

	}
	public function Run(){

	}

	public function getPackageMap(){
		return self::$packageMap;
	}//getpackagemap
}



?>