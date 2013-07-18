<?php

/**
Copyright (C) 2013 Michel Dumontier

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

require_once(__DIR__.'/../php-lib/bio2rdfapi.php');
require_once(__DIR__.'/../php-lib/rdfapi.php');
require_once(__DIR__.'/../php-lib/registry.php');
require_once(__DIR__.'/../php-lib/application.php');

class Bio2RDFApp extends Application
{
	public function __construct($argv)
	{
		parent::__construct();
	
		// get the parsers;
		$parsers = $this->getParsers();
		parent::addParameter('parser',true,implode("|",$parsers),null,'bio2rdf parser to run');
		if(parent::setParameters($argv,true) == false) {
			parent::printParameters($argv);
			exit;
		}
		
		// now get the file and run it
		$parser_name = parent::getParameterValue('parser');
		$file = $parser_name.'/'.$parser_name.'.php';
		require($file);
		$parser_class = $parser_name."Parser";	
		$parser = new $parser_class($argv);
		
		$start = microtime(true);
		$parser->Run();
		
		$end = microtime(true);
		$time_taken =  $end - $start;
		print "Start: ".date("l jS F \@ g:i:s a", $start)."\n";
		print "End:   ".date("l jS F \@ g:i:s a", $end)."\n";
		print "Time:  ".sprintf("%.2f",$time_taken)." seconds\n";
	}
	
	/** looks for dir/dir.php, as an initial list of parsers */
	function getParsers()
	{
		$dh = opendir('./');
		while (($file = readdir($dh)) !== false) {
			if($file[0] == '.') continue;
			if(is_dir($file)) {
				$parser_dir = $file;
				$dh2 = opendir($parser_dir);
				while($file = readdir($dh2)) {
					if($file[0]=='.')continue;
					preg_match("/^($parser_dir)\.php$/",$file,$m);
					if(isset($m[1])) $parsers[] = $m[1];
				}
			}
		}
		return $parsers;
	}
}

set_error_handler('error_handler');
$a = new Bio2RDFApp($argv);
?>

