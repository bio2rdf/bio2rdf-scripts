<?php

function OBOParser($in)
{
	if($in === NULL) return NULL;
	
	while($l = fgets($in)) {
		if(strlen(trim($l)) == 0) continue;
		
		if(strstr($l,"[Term]")) {
			if(isset($term)) {
				$terms[$term['id'][0]] = $term;	
			}
			$term = array();
		} else if(strstr($l,"[Typedef]")) {
			if(isset($term)) {
				$terms[$term['id'][0]] = $term;			
				unset($term);
			}
			$typedef = '';
		} else {
			if(isset($term)) {
				$a = explode(": ",trim($l));
				$a[1] = str_replace('"','',$a[1]); // remove brackets
				
				preg_match("/(.*) \! (.*)/",$a[1],$m); // parse out the label that may come with an id
				if(count($m)) {
					$a[1] = $m[1];
				}
				$term[$a[0]][] = $a[1];
			
			} else if(isset($typedef))  {
				
			} else {
				// in the header
				//format-version: 1.0
				$a = explode(": ",trim($l));
				$terms['ontology'][$a[0]][] = $a[1];
			} 
		}
	}
	if(isset($term)) $terms[$term['id'][0]] = $term;

	return $terms;
}

function BuildNamespaceSearchList($terms, &$out)
{
	foreach($terms AS $term) {
		if(isset($term['namespace'][0]) && isset( $term['id'][0] ) && isset( $term['name'][0])) 
			$out[$term['namespace'][0]][$term['id'][0]] = $term['name'][0];
	}
}
?>