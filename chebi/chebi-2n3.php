<?php
###############################################################################
#Copyright (C) 2012 Jose Cruz-Toledo, Alison Callahan, Marc-Alexandre Nolin
#
#Permission is hereby granted, free of charge, to any person obtaining a copy of
#this software and associated documentation files (the "Software"), to deal in
#the Software without restriction, including without limitation the rights to
#use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
#of the Software, and to permit persons to whom the Software is furnished to do
#so, subject to the following conditions:
#
#The above copyright notice and this permission notice shall be included in all
#copies or substantial portions of the Software.
#
#THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
#SOFTWARE.
###############################################################################

require_once('php-sql-parser.php');


#Set the paths to all the SQL files that will be parsed
$compound_path = "/home/jose/tmp/chebi/compounds.sql";
$chemical_path = "/home/jose/tmp/chebi/chemical_data.sql";
$names_path = "/home/jose/tmp/chebi/names.sql";
$relations_path = "/home/jose/tmp/chebi/relation.sql";



#Initialize the SQL parser
$parser = new PHPSQLParser();


/********************/
/** Funciton Calls **/
/********************/

/*parse_name_sql_file($names_path);
parse_chemical_data_sql_file($chemical_path);
parse_compound_sql_file($compound_path);*/
parse_relations_sql_file($relations_path);

/***************/
/** Functions **/
/***************/

function parse_relations_sql_file($path){
	$fh = fopen($path, 'r') or die("Cannot open $path !\n");
	if($fh){
		while (($buffer = fgets($fh, 4096)) !== false) {
			$pattern = '/insert.*/';
			preg_match($pattern, $buffer, $matches);
			if(count($matches)){
				//go parse the insert line
				$parsed_insert = parse_relations_data_line($matches[0]);
				$initial_id = "";
				$final_id = "";
				$relation = "";
				$status = "";
				
				if(isset($parsed_insert[2])){
					$initial_id = $parsed_insert[2];
				}
				if(isset($parsed_insert[3])){
					$final_id = $parsed_insert[3];
				}
				if(isset($parsed_insert[1])){
					$relation = $parsed_insert[1];
				}
				if(isset($parsed_insert[4])){
					$status = $parsed_insert[4];
				}
				
				$nsid = "chebi:".$initial_id;
				$bmuri = "http://bio2rdf.org/$nsid";
				$final_uri = "http://bio2rdf.org/chebi:$final_id";
				$rand = rand(1,7999999999999999);
				$buf = "";
				$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:has_relation> <http://bio2rdf.org/chebi_resource:$bmuri$final_uri$rand> . \n";
				$buf .= "<$bmuri> <http://bio2rdf.org/chebi_resource:$bmuri$final_uri$rand> <$final_uri>.\n";
				$buf .= "<$bmuri$final_uri$rand> <http://bio2rdf.org/chebi_vocabulary:has_value>  \"$relation\".\n";
				$buf .= "<$bmuri$final_uri$rand> <http://bio2rdf.org/chebi_vocabulary:has_status>  \"$status\".\n";
				echo $buf."\n\n";
				
				
			}
		}
	}
	if(!feof($fh)){
		echo "Error: unexpected fgets() fail\n";
	}
	fclose($fh);
}
function parse_name_sql_file($path){
	$fh = fopen($path, 'r') or die("Cannot open $path !\n");
	$skip_these_chebi_ids = array("33863","33894","33895","27502","50162","52371","52372","61225");
	if($fh){
		while (($buffer = fgets($fh, 4096)) !== false) {
			$pattern = '/insert.*/';
			preg_match($pattern, $buffer, $matches);
			if(count($matches)){
				//go parse the insert line
				$parsed_insert = parse_name_data_line($matches[0]);
				$id = "";
				$name = "";
				$type ="";
				$source ="";
				$adapted = "";
				$lang = "";
				
				if(isset($parsed_insert[1])){
					$id = $parsed_insert[1];
				}
				if(isset($parsed_insert[2])){
					$name = urlencode($parsed_insert[2]);
				}
				if(isset($parsed_insert[3])){
					$type = strtolower($parsed_insert[3]);
				}
				if(isset($parsed_insert[4])){
					$source = $parsed_insert[4];
				}
				if(isset($parsed_insert[5])){
					$adapted = urlencode($parsed_insert[5]);
				}
				if(isset($parsed_insert[6])){
					$lang = $parsed_insert[6];
				}
				
				$nsid = "chebi:".$id;
				$bmuri = "http://bio2rdf.org/$nsid";
				
				if(!in_array($id, $skip_these_chebi_ids)){
					$buf = "";
					$buf .= "<$bmuri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/chebi_vocabulary:$type>.\n";
					$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:has_source> \"$source\".\n";
					$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:has_language> \"$lang\".\n";
					$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:has_adapted> \"$adapted\".\n";
					$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:has_name> \"$name\".\n";
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

function parse_chemical_data_sql_file($path){
	$fh = fopen($path, 'r') or die("Cannot open $path !\n");
	if($fh){
		while (($buffer = fgets($fh, 4096)) !== false) {
			$pattern = '/insert.*/';
			preg_match($pattern, $buffer, $matches);
			if(count($matches)){
				//go parse the insert line
				$parsed_insert = parse_chemical_data_line($matches[0]);
				$id = "";//chebi id
				$chemical_data = "";//formula
				$source = "";
				$type = "";
				
				if(isset($parsed_insert[1])){
					$id = $parsed_insert[1];
				}
				if(isset($parsed_insert[2])){
					$chemical_data = $parsed_insert[2];
				}
				if(isset($parsed_insert[3])){
					$source = $parsed_insert[3];
				}
				if(isset($parsed_insert[4])){
					$type = strtolower($parsed_insert[4]);
				}
				
				$nsid = "chebi:".$id;
				$bmuri = "http://bio2rdf.org/$nsid";
				
				$buf = "";
				$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:has_$type> \"$chemical_data\".\n";
				$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:has_source> \"$source\".\n";
				$buf .= "<$bmuri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/chebi_vocabulary:$type>.\n";
				echo $buf."\n\n";		
			}
		}
	}
	if(!feof($fh)){
		echo "Error: unexpected fgets() fail\n";
	}
	fclose($fh);
}

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
						$buf .= "<$bmuri> <http://bio2rdf.org/chebi_vocabulary:has_source> \"$source\" .\n";
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

function parse_relations_data_line($anInsertLine){
	global $parser;
	$returnMe = array();
	$parsed = $parser->parse($anInsertLine);
	$p2 = $parsed["VALUES"][0];
	//remove the first and last round brackets
	$p2 = substr($p2, 1,-1);
	$p2 .= ",";
	$re1='.*?';	# Non-greedy match on filler
	$re2='(\\d+)';	# Integer Number 1
	$re3='.*?';	# Non-greedy match on filler
	$re4='((?:[a-z][a-z0-9_]*))';	# Variable Name 1
	$re5='.*?';	# Non-greedy match on filler
	$re6='(\\d+)';	# Integer Number 2
	$re7='.*?';	# Non-greedy match on filler
	$re8='(\\d+)';	# Integer Number 3
	$re9='.*?';	# Non-greedy match on filler
	$re10='([a-z])';	# Any Single Word Character (Not Whitespace) 1

  if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4.$re5.$re6.$re7.$re8.$re9.$re10."/is", $p2, $matches))
  {
      $int1=$matches[1][0];
      $var1=$matches[2][0];
      $int2=$matches[3][0];
      $int3=$matches[4][0];
      $w1=$matches[5][0];
      $returnMe[] = $int1;	
      $returnMe[] = $var1;
      $returnMe[] = $int2;
      $returnMe[] = $int3;
	
  }

	return $returnMe;
	
}

function parse_name_data_line($anInsertLine){
	global $parser;
	$returnMe = array();
	$parsed = $parser->parse($anInsertLine);
	$p2 = $parsed["VALUES"][0];
	//remove the first and last round brackets
	$p2 = substr($p2, 1,-1);
	$p2 .= ",";
	
	$re1='(\\\'.*?\\\')';	# Single Quote String 1
	$re2='.*?';	# Non-greedy match on filler
	$re3='(\\\'.*?\\\')';	# Single Quote String 2
	$re4='.*?';	# Non-greedy match on filler
	$re5='(\\\'.*?\\\')';	# Single Quote String 3
	$re6='.*?';	# Non-greedy match on filler
	$re7='(\\\'.*?\\\')';	# Single Quote String 4
	$re8='.*?';	# Non-greedy match on filler
	$re9='(\\\'.*?\\\')';	# Single Quote String 5
	$re10='.*?';	# Non-greedy match on filler
	$re11='(\\\'.*?\\\')';	# Single Quote String 6
	$re12='.*?';	# Non-greedy match on filler
	$re13='(\\\'.*?\\\')';	# Single Quote String 7
	
	

	if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4.$re5.$re6.$re7.$re8.$re9.$re10.$re11.$re12.$re13."/is", $p2, $matches)){
	  $strng1=$matches[1][0];
	  $strng2=$matches[2][0];
	  $strng3=$matches[3][0];
	  $strng4=$matches[4][0];
	  $strng5=$matches[5][0];
	  $strng6=$matches[6][0];
	  $strng7=$matches[7][0];
	  $returnMe[] = htmlentities(str_replace("'","",$strng1));
      $returnMe[] = htmlentities(str_replace("'","",$strng2));
      $returnMe[] = htmlentities(str_replace("'","",$strng3));
      $returnMe[] = htmlentities(str_replace("'","",$strng4));
      $returnMe[] = htmlentities(str_replace("'","",$strng5)); 
      $returnMe[] = htmlentities(str_replace("'","",$strng6));
      $returnMe[] = htmlentities(str_replace("'","",$strng7));
      return $returnMe;
	}
	$returnMe = array();
	
	
	
}

function parse_chemical_data_line($anInsertLine){
	global $parser;
	$returnMe = array();
	$parsed = $parser->parse($anInsertLine);
	$p2 = $parsed["VALUES"][0];
	//remove the first and last round brackets
	$p2 = substr($p2, 1,-1);
	$p2 .= ",";
	
	$re1='(\\\'.*?\\\')';	# Single Quote String 1
	$re2='.*?';	# Non-greedy match on filler
	$re3='(\\\'.*?\\\')';	# Single Quote String 2
	$re4='.*?';	# Non-greedy match on filler
	$re5='(\\\'.*?\\\')';	# Single Quote String 3
	$re6='.*?';	# Non-greedy match on filler
	$re7='(\\\'.*?\\\')';	# Single Quote String 4
	$re8='.*?';	# Non-greedy match on filler
	$re9='(\\\'.*?\\\')';	# Single Quote String 5

  if ($c=preg_match_all ("/".$re1.$re2.$re3.$re4.$re5.$re6.$re7.$re8.$re9."/is", $p2, $matches)){
      $strng1=$matches[1][0];
      $strng2=$matches[2][0];
      $strng3=$matches[3][0];
      $strng4=$matches[4][0];
      $strng5=$matches[5][0];
      
      $returnMe[] = htmlentities(str_replace("'","",$strng1));
      $returnMe[] = htmlentities(str_replace("'","",$strng2));
      $returnMe[] = htmlentities(str_replace("'","",$strng3));
      $returnMe[] = htmlentities(str_replace("'","",$strng4));
      $returnMe[] = htmlentities(str_replace("'","",$strng5)); 
  }
	return $returnMe;
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
