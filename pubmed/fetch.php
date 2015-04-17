<?php
$base_dir = '/data/download/pubmed'; // EDIT THIS to point to an existing, empty directory
$input_dir = "$base_dir";
$output_dir = "$base_dir/pmid";
@mkdir($base_dir);
@mkdir($input_dir);
@mkdir($output_dir);

system("cd $input_dir; /usr/bin/lftp -e 'o ftp://ftp.nlm.nih.gov/nlmdata/.medleasebaseline/gz && mirror --verbose && quit'; /usr/bin/lftp -e 'o ftp://ftp.nlm.nih.gov/nlmdata/.medlease/gz && mirror --verbose && quit'");
exit;


$item_start = '<MedlineCitation '; // defines the start of each item
$id_regexp = '/<PMID Version=\"\d\">(\d+)<\/PMID>/'; // unique identifier used for the filename
// fetch MEDLINE (needs NLM license)

// process all files ending in .xml.gz
foreach (glob("$input_dir/*.xml.gz") as $file){ 
  // not-particularly-strict stream parsing of large xml files 
  $handle = gzopen($file, 'r');
  print "Processing $file\n";
  while (!feof($handle)) {
    $line = fgets($handle);
    if (ereg($item_start, $line)) {
      if (isset($id)){
        $i = ceil($id/100000);
	@mkdir("$output_dir/$i");
        $output_file = "$output_dir/$i/$id.xml.gz";
        // save the individual article data, gzipped
        //file_put_contents("compress.zlib://$output_file", json_encode(simplexml_load_string(implode('', $output))));
        file_put_contents("compress.zlib://$output_file", implode('', $output));
      }
      $output = array();
      unset($id);
    }
    if (preg_match($id_regexp, $line, $matches)){
      $id = $matches[1];
    }
    $output[] = $line;
  }
}
