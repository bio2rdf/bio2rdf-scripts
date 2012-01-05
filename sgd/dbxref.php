<?php

class SGD_DBXREF {

	function __construct($infile, $outfile)
	{
		$this->_in = fopen($infile,"r");
		if(!isset($this->_in)) {
			trigger_error("Unable to open $infile");
			return 1;
		}
		$this->_out = fopen($outfile,"w");
		if(!isset($this->_out)) {
			trigger_error("Unable to open $outfile");
			return 1;
		}
		
	}
	function __destruct()
	{
		fclose($this->_in);
		fclose($this->_out);
	}
/*
1) DBXREF ID
2) DBXREF ID source
3) DBXREF ID type
4) S. cerevisiae feature name
5) SGDID

counts:
BioGRID	Gene ID	6194
CGD	Gene ID	3594
DIP	Gene ID	6130
EBI	UniParc ID	5880
EBI	UniProt/Swiss-Prot ID	5890
EBI	UniProt/TrEMBL ID	16
EUROSCARF	Gene ID	5909
GenBank/EMBL/DDBJ	DNA accession ID	1789
GenBank/EMBL/DDBJ	DNA version ID	47
GenBank/EMBL/DDBJ	Protein version ID	13803
GermOnline	Gene ID	684
IUBMB	EC number	1800
MetaCyc	Pathway ID	1047
NCBI	DNA accession ID	142
NCBI	Gene ID	5880
NCBI	NCBI protein GI	5880
NCBI	RefSeq Accession	18
NCBI	RefSeq protein version ID	5880
TCDB	TC number
*/
	function Convert2RDF()
	{
		$buf = N3NSHeader();
		
		while($l = fgets($this->_in,2048)) {
			list($id,$ns, $type, $name, $sgdid) = explode("\t",trim($l));;
					
			$sameas = 'owl:sameAs';
			$seealso = 'rdfs:seeAlso';
			$suf = $rel = "";
			switch($ns) {
				case "BioGRID":
					$ns  = 'biogrid';$rel = $sameas;break;
				case "CGD":
					$ns = 'candida'; $rel = $sameas;break;
				case "DIP":
					$ns = 'dip'; $rel = $sameas;$suf='gp';break;
				case "EBI":
					if($type == "UniParc ID") {$ns='uniparc'; $rel = $sameas;$suf='gp';break;}
					if($type == "UniProt/Swiss-Prot ID") {$ns='swissprot';$rel=$sameas;$suf='gp';break;}
					if($type == "UniProt/TrEMBL ID") {$ns='trembl';$rel=$sameas;$suf='gp';break;}
					break;
				case "EUROSCARF":
					$ns = 'euroscarf';$rel=$sameas;break;
				case "GenBank/EMBL/DDBJ":
					$ns = 'ncbi';$rel=$sameas;break;
				case "GermOnline":
					$ns = 'germonline';$rel=$sameas;break;
				case "IUBMB":
					$ns = 'ec';$rel=$seealso;
					break;
				case "MetaCyc":
					$ns = 'metacyc';$rel=$seealso;
					break;
				case "NCBI":
					if($type == "DNA accession ID") {$ns='ncbi'; $rel=$sameas;  break;}
					if($type == "Gene ID") {$ns='entrez_gene';$rel=$sameas;break;}
					if($type == "NCBI protein GI") {$ns='ncbi';$rel=$sameas;$suf='gp';break;}
					if($type == "RefSeq Accession") {$ns='refseq';$rel=$sameas;$suf='gp';break;}
					if($type == "RefSeq protein version ID") {$ns='refseq';$rel=$sameas;$suf='gp';break;}
				case "TCDB":
					$ns = 'tcdb';$rel=$seealso;break;
				default:
					echo "unable to map $ns : $id to $sgdid";
			}
			
			if($rel) {
				if($suf == 'gp'){
					//if the entity is not an sgd entity but a bio2rdf sgd entity, use the sgd_resource namespace
					$buf .= "sgd_resource:$sgdid$suf $rel $ns:$id .".PHP_EOL;
				} else {
					//otherwise use the sgd namespace
					$buf .= "sgd:$sgdid$suf $rel $ns:$id .".PHP_EOL;
				}
			}
			//echo $buf;exit;
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}
};

?>
