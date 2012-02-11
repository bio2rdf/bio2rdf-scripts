<?php
// Turn off all error reporting
error_reporting(0);
require_once('php-sql-parser.php');

$parser = new PHPSQLParser();
$compound_path = "/home/jose/tmp/chebi/compounds.sql";

parse_compound_sql_file($compound_path);


function parse_compound_sql_file($path){
	$fh = fopen($path, 'r') or die("Cannot open $path !\n");
	if($fh){
		while (($buffer = fgets($fh, 4096)) !== false) {
			$pattern ='/insert.*/';
			preg_match($pattern, $buffer, $matches);
			if(count($matches)){
				//go parse the insert line
				$parsed_insert = parse_compound_line($matches[0]);
				if(count($parsed_insert)){
					
					$id = "";
					$name = "";
					$name_safe ="";
					$source = "";
					$source_safe = "";
					$modified_on_date = "";
					$status = "";
					$comment = "";
					$parent_id = "";
					$creator = "";
					$chebi_accession = "";
					$definition ="";
					$stars = "";
					
					
					
					if(isset($parsed_insert[0])){
						$id = $parsed_insert[0];
					}
					
					if(isset($parsed_insert[1])){
						$name = $parsed_insert[1];
						$name_safe = urlencode($name);
					}
					if(isset($parsed_insert[2])){
						$source = $parsed_insert[2];
						$source_safe = urlencode($parsed_insert[2]);
					}
					if(isset($parsed_insert[3])){
						$parent_id = $parsed_insert[3];
					}
					if(isset($parsed_insert[4])){
						$chebi_accession = $parsed_insert[4];
					}
					if(isset($parsed_insert[5])){
						$status = $parsed_insert[5];
					}
					if(isset($parsed_insert[6])){
						$definition = urlencode($parsed_insert[6]);
					}
					if(isset($parsed_insert[7])){
						$modified_on_date = $parsed_insert[7];
					}
					if(isset($parsed_insert[8])){
						$creator = $parsed_insert[8];
					}
					if(isset($parsed_insert[9])){
						$stars = $parsed_insert[9];
					}
					
					$nsid = "chebi:".$id;
					$bmuri = "http://bio2rdf.org/$nsid";
					
					if($id !="3721"){
						$buf = "";
						$buf .= "<$bmuri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/chebi_vocabulary:compound> .\n";
						$buf .= "<$bmuri> <http://purl.org/dc/elements/1.1/identifier> \"$nsid\" .\n";
						$buf .= "<$bmuri> <http://purl.org/dc/elements/1.1/title> \"$name_safe [$nsid]\" .\n";
						$buf .= "<$bmuri> <http://www.w3.org/2000/01/rdf-schema#label> \"$name_safe [$nsid]\" .\n";
						$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:source> \"$source\" .\n";
						if($parent_id != "null"){
							$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:parent_id> <http://bio2rdf.org/chebi:$parent_id> .\n";
						}
						$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:status> \"$status\" .\n";
						$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:definition> \"$definition\" .\n";
						$buf .= "<$bmuri> <http://purl.org/dc/elements/1.1/modified> \"$modified_on_date\" .\n";
						$buf .=	"<$bmuri> <http://purl.org/dc/elements/1.1/creator> \"$creator\" .\n";
						$buf .="<$bmuri> <http://bio2rdf.org/chebi_vocabulary:stars> \"$stars\" .\n";
					}
					echo $buf."\n\n";
				}
				
			}
		}
    }
	
	if(!feof($fh)){
		echo "Error: unexpected fgets() fail\n";
	}
	
	fclose($fh);

}



/**
 * This function reutrns an array where
 * every element corresponds to a value 
 * in the VALUES clause of an insert query
 **/
function parse_compound_line($anInsertLine){
	global $parser;
	$returnMe = array();
	$parsed = $parser->parse($anInsertLine);
	$p2 = $parsed["VALUES"][0];
	//remove the first and last round brackets
	$p2 = substr($p2, 1,-1);
	$p2 .= ",";
	
	//echo $p2;
	
	$re1='(\\\'.*?\\\')';	# Single Quote String 1
  $re2='.*?';	# Non-greedy match on filler
  $re3='(\\\'.*?\\\')';	# Single Quote String 2
  $re4='.*?';	# Non-greedy match on filler
  $re5='(\\\'.*?\\\')';	# Single Quote String 3
  $re6='.*?';	# Non-greedy match on filler
  $re7='(\\\'.*?\\\'|(?:[a-z][a-z]+))';	# Single Quote String 4
  $re8='.*?';	# Non-greedy match on filler
  $re9='(\\\'.*?\\\')';	# Single Quote String 5
  $re10='.*?';	# Non-greedy match on filler
  $re11='(\\\'.*?\\\')';	# Single Quote String 6
  $re12='.*?';	# Non-greedy match on filler
  $re13='(\\\'.*?\\\')';	# Single Quote String 7
  $re14='.*?';	# Non-greedy match on filler
  $re15='(\\\'.*?\\\')';	# Single Quote String 8
  $re16='.*?';	# Non-greedy match on filler
  $re17='(\\\'.*?\\\')';	# Single Quote String 9
  $re18='.*?';	# Non-greedy match on filler
  $re19='(\\\'.*?\\\')';	# Single Quote String 10

  if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4.$re5.$re6.$re7.$re8.$re9.$re10.$re11.$re12.$re13.$re14.$re15.$re16.$re17.$re18.$re19."/is", $p2, $matches))
  {
      $strng1=$matches[1][0];
      $strng2=$matches[2][0];
      $strng3=$matches[3][0];
      $strng4=$matches[4][0];
      $strng5=$matches[5][0];
      $strng6=$matches[6][0];
      $strng7=$matches[7][0];
      $strng8=$matches[8][0];
      $strng9=$matches[9][0];
      $strng10=$matches[10][0];
      $returnMe[] = htmlentities(str_replace("'","",$strng1));
      $returnMe[] = htmlentities(str_replace("'","",$strng2));
      $returnMe[] = htmlentities(str_replace("'","",$strng3));
      $returnMe[] = htmlentities(str_replace("'","",$strng4));
      $returnMe[] = htmlentities(str_replace("'","",$strng5));
      $returnMe[] = htmlentities(str_replace("'","",$strng6));
      $returnMe[] = htmlentities(str_replace("'","",$strng7));
      $returnMe[] = htmlentities(str_replace("'","",$strng8));
      $returnMe[] = htmlentities(str_replace("'","",$strng9));
      $returnMe[] = htmlentities(str_replace("'","",$strng10));
      
      return $returnMe;
  }

  
	
}
?>
