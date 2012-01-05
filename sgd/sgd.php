<?php
require_once(dirname(__FILE__).'/../common/php/libphp.php');
$ncbo_apikey = '24e19c82-54e0-11e0-9d7b-005056aa3316';
$ncbo_dl_dir = '/data/ncbo/download/';

$data = array(
 "dbxref"      => array("infile" => "curation/chromosomal_feature/dbxref.tab"),
 "features"    => array("infile" => "curation/chromosomal_feature/SGD_features.tab"),
 "domains"     => array("infile" => "curation/calculated_protein_info/domains/domains.tab"),
 "protein"     => array("infile" => "curation/calculated_protein_info/protein_properties.tab"),
 "goa"         => array("infile" => "curation/literature/gene_association.sgd.gz"),
 "goslim"      => array("infile" => "curation/literature/go_slim_mapping.tab"),
 "complex"     => array("infile" => "curation/literature/go_protein_complex_slim.tab"),
 "interaction" => array("infile" => "curation/literature/interaction_data.tab"),
 "phenotype"   => array("infile" => "curation/literature/phenotype_data.tab"),
 "pathways"    => array("infile" => "curation/literature/biochemical_pathways.tab"),
// "psiblast"    => array ("infile" => "genomics/homology/psi_blast/psi_blast.tab.gz"),
// not availaible? "expression" => array("infile" => "/systematic_results/expression_data/expression_connection_data/*"),
 );


$list = '[all|'.implode('|',array_keys($data)).']';

$options = array(
 "p" => $list,
 "tabdir" => "/data/sgd/tab/",
 "n3dir" => "/data/sgd/n3/",
 "dl" => "false",
 "ftp" => "http://downloads.yeastgenome.org/"
);

// show options
if($argc == 1) {
 echo "Usage: php $argv[0] ".PHP_EOL;
 echo " Default values as follows, * mandatory".PHP_EOL;
 foreach($options AS $key => $value) {
  if($key == "p") echo "*$key=$value";
  else echo " $key=$value ";
  echo PHP_EOL;
 }
}

// set options from user input
foreach($argv AS $i=> $arg) {
 if($i==0) continue;
 $b = explode("=",$arg);
 if(isset($options[$b[0]])) $options[$b[0]] = $b[1];
 else {echo "unknown key $b[0]";exit;}
}

if($options['p'] == $list) {
 exit;
}

@mkdir($options['tabdir'],null,true);
@mkdir($options['n3dir'],null,true);

//download
if($options['dl'] == "true") {
 $a = '';
 if($options['p'] == "all") $a = $data;
 else $a[$options['p']] = $data[$options['p']];

 foreach($data AS $a) {
   $files[] = $a['infile'];
 }
 DownloadFiles($options['ftp'], $files, $options['tabdir']);
}

if($options['p'] == 'all') $do = $data;
else if(isset($data[$options['p']])) $do[$options['p']] = $data[$options['p']];
else {echo "Invalid choice -> $list";exit;}

foreach($do AS $script => $args) {
   	echo "Running $script...";
	require($script.".php");
	BreakPath($args['infile'],$option['tabdir'],$file);

	$fnx = "SGD_".strtoupper($script);
	$n = new $fnx($options['tabdir'].$file,$options['n3dir'].$script.".n3");
	$n->Convert2RDF();
	unset($n);
	echo "done!\n";
}

?>
