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
 * An RDF generator for SGD (http://www.yeastgenome.org/)
 * @version 1.0
 * @author Michel Dumontier
*/


require_once(dirname(__FILE__).'/../common/php/libphp.php');

$options = null;
AddOption($options, 'indir', null, '/data/download/sgd/', false);
AddOption($options, 'outdir',null, '/data/rdf/sgd/', false);
AddOption($options, 'files','all|dbxref|features|domains|protein|goa|goslim|complex|interaction|phenotype|pathways','',true);
AddOption($options, 'remote_base_url',null,'http://downloads.yeastgenome.org/', false);
AddOption($options, 'ncbo_api_key',null,'24e19c82-54e0-11e0-9d7b-005056aa3316', false);
AddOption($options, 'download','true|false','false', false);
AddOption($options, CONF_FILE_PATH, null,'/bio2rdf-scripts/common/bio2rdf_conf.rdf', false);
AddOption($options, USE_CONF_FILE,'true|false','false', false);

if(SetCMDlineOptions($argv, $options) == FALSE) {
	PrintCMDlineOptions($argv, $options);
	exit;
}

$date = date("d-m-y"); 
$releasefile_uri = "sgd-$date.ttl";
$releasefile_uri = "http://download.bio2rdf.org/sgd/".$releasefile_uri;

@mkdir($options['indir']['value'],null,true);
@mkdir($options['outdir']['value'],null,true);
if($options['files']['value'] == 'all') {
	$files = explode("|",$options['files']['list']);
	array_shift($files);
} else {
	$files = explode("|",$options['files']['value']);
}

$remote_files = array(
 "dbxref"      => "curation/chromosomal_feature/dbxref.tab",
 "features"    => "curation/chromosomal_feature/SGD_features.tab",
 "domains"     => "curation/calculated_protein_info/domains/domains.tab",
 "protein"     => "curation/calculated_protein_info/protein_properties.tab",
 "goa"         => "curation/literature/gene_association.sgd.gz",
 "goslim"      => "curation/literature/go_slim_mapping.tab",
 "complex"     => "curation/literature/go_protein_complex_slim.tab",
 "interaction" => "curation/literature/interaction_data.tab",
 "phenotype"   => "curation/literature/phenotype_data.tab",
 "pathways"    => "curation/literature/biochemical_pathways.tab",
// "psiblast"    => "genomics/homology/psi_blast/psi_blast.tab.gz",

 );

// download the files
if($options['download']['value'] == 'true') {
  foreach($files AS $file) {
	$myfiles[] = $remote_files[$file];
  }
  DownloadFiles($options['remote_base_url']['value'],$myfiles,$options['indir']['value']);
}

foreach($files AS $file) {	
   	echo "Parsing $file...";
	require($file.".php");
	BreakPath($remote_files[$file],$dir,$myfile);
	
	$fnx = "SGD_".strtoupper($file);
	$n = new $fnx($options['indir']['value'].$myfile,$options['outdir']['value'].$file.".ttl");
	$n->Convert2RDF();
	unset($n);
	echo "done!\n";
}

?>
