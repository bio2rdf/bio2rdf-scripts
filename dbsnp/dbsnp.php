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
 * An RDF generator for dbSNP (http://www.ncbi.nlm.nih.gov/projects/SNP/)
 * @version 1.0
 * @author Michel Dumontier
*/


require_once(__DIR__.'/../../php-lib/xmlapi.php');
require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class dbSNPParser extends Bio2RDFizer 
{
	private $ns = null;
	private $named_entries = array();
	
	function __construct($argv) {
		parent::__construct($argv,'dbsnp');	
		// set and print application parameters
		parent::addParameter('files',true,null,'all','all|clinical|omim|pharmgkb|snp#,snp#');
		parent::addParameter('download_url',false,null,'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=snp&retmode=xml&id=','the download url for individual snps');
		parent::initialize();
	}
	
	function run()
	{
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');

		// get the snps from pharmgkb
		$snps = explode(",",parent::getParameterValue('files'));
		if($snps[0] == 'all') {
			$snps =  $this->getSNPs();
		} else if($snps[0] == 'clinical') {
			$snps = $this->getSNPs(true);
		} else if($snps[0] == 'omim') {
			$lfile = $ldir.'snp_omimvar.txt';
			if(!file_exists($lfile) || (parent::getParameterValue('download') == true)) {
				$ret = utils::DownloadSingle('ftp://ftp.ncbi.nlm.nih.gov/snp/Entrez/snp_omimvar.txt',$lfile);
			}
			$snps = $this->processOMIMVar($lfile);
		} else if($snps[0] == 'pharmgkb') {
			$lfile = $ldir.'pharmgkb.snp.zip';
			if(!file_exists($lfile) || (parent::getParameterValue('download') == true)) {
				$ret = utils::DownloadSingle('http://www.pharmgkb.org/download.do?objId=rsid.zip&dlCls=common',$lfile);
			}
			$snps = $this->processPharmGKBSnps($lfile);
		}

		$outfile = $odir."dbsnp.".parent::getParameterValue('output_format');
		$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
		parent::setWriteFile($outfile, $gz);
		$n = count($snps);

		$z = 0;
		foreach($snps AS $i => $snp) {
			$file = $snp.'.xml.gz';
			$infile = $ldir.$file;

			$rfile = parent::getParameterValue('download_url').$snp;
			//$outfile = $odir.$snp.".".parent::getParameterValue('output_format');

			// check if exists
			$download = false;
			if(!file_exists($infile)) {
				//trigger_error($lfile." not found. Will attempt to download. ", E_USER_NOTICE);
				parent::setParameterValue('download',true);
			}

			// download
			if(parent::getParameterValue('download') == true) {
				trigger_error("Downloading $file",E_USER_NOTICE);
				$ret = utils::downloadSingle($rfile,"compress.zlib://".$infile,true);
				if($ret === false) continue;
			}

			// process
			echo "Processing $snp (".($i+1)."/$n)".PHP_EOL;
			$this->parse($infile);
			parent::writeRDFBufferToWriteFile();
			if($z++ % 10000 == 0) parent::clear();
		}
		parent::getWriteFile()->close();

		// generate the dataset description file
		$source_file = (new DataResource($this))
			->setURI($rfile)
			->setTitle("dbSNP ".parent::getDatasetVersion())
			->setRetrievedDate( date ("Y-m-d\TG:i:s\Z"))
			->setFormat("application/xml")
			->setPublisher("http://www.ncbi.nlm.nih.gov")
			->setHomepage("http://www.ncbi.nlm.nih.gov/SNP/")
			->setRights("use-share-modify")
			->setLicense("http://www.ncbi.nlm.nih.gov/About/disclaimer.html")
			->setDataset("http://identifiers.org/dbsnp/");
			
		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");
		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$outfile")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
			->setSource($source_file->getURI())
			->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/dbsnp/dbsnp.php")
			->setCreateDate($date)
			->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
			->setPublisher("http://bio2rdf.org")			
			->setRights("use-share-modify")
			->setRights("by-attribution")
			->setRights("restricted-by-source-license")
			->setLicense("http://creativecommons.org/licenses/by/3.0/")
			->setDataset(parent::getDatasetURI());

		if($gz) $output_file->setFormat("application/gzip");
		if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
		else $output_file->setFormat("application/n-quads");

		$dataset_description = $source_file->toRDF().$output_file->toRDF();

		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
	}


	function getSNPs($clinical_flag = false)
	{
		$all= array("unknown","untested","non-pathogenic","probable-non-pathogenic","probable-pathogenic","pathogenic","drug-response","histocompatibility","other");
		$clinical = array("pathogenic","probable-pathogenic","drug-response","other");
		if($clinical_flag == true) {
			$term = implode("[Clinical Significance] or ",$all);
			$term = '"'.substr($term,0)."\"[Clinical Significance]";
		} else {
			$term = "snp";
		}
		$lfile = $this->getParameterValue("indir")."snps.json";
		if(!file_exists($lfile) || $this->getParameterValue('download') == true) {
			echo "Downloading snp list ";
			$xmlfile = $this->getParameterValue('indir').'snp.list.xml';
			$retmax = 10000000;
//			$retmax = 10;
			$start = 0;
			$mylist = array();
			do {
				echo count($mylist).PHP_EOL;
				$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=snp&retmax=$retmax&term=".urlencode($term)."&retstart=$start";

				$c = file_get_contents($url);
				preg_match_all("/<Id>([^\<]+)<\/Id>/",$c,$m);
				if(!isset($m[1])) break;
				$mylist = array_merge($mylist,$m[1]);
				$start += $retmax;
			} while(true);
			file_put_contents($lfile,json_encode($mylist));
			echo PHP_EOL;
		} else {
			$mylist = json_decode( file_get_contents($lfile));
		}
		// load the file
		return $mylist;
	}


	function parse($file)
	{
		$xml = new CXML($file);		
		$xml->parse();
		$entry = $xml->getXMLRoot();
		if(!isset($entry) or !$entry) return false;

		foreach($entry->children() AS $o) {
			$rsid = "rs".$o->attributes()->rsId;
			$id = parent::getNamespace().$rsid;
			$type = parent::getVoc().ucfirst(str_replace(" ","-", (string) $o->attributes()->snpClass));

			$snpclass = parent::getVoc().((string)$o->attributes()->snpClass);
			$moltype  = parent::getVoc().((string)$o->attributes()->molType);
			// attributes
			parent::addRDF(
				parent::describeIndividual($id,$rsid,$type).
				parent::describeClass($type, ucfirst("".$o->attributes()->snpClass)).
				parent::triplify($id,parent::getVoc()."mol-type",$moltype).
				parent::describeClass($moltype,(string)$o->attributes()->molType, parent::getVoc()."Moltype").
				parent::describeClass(parent::getVoc()."Moltype","Moltype").
				parent::triplify($id,parent::getVoc()."taxid","taxonomy:".(string) $o->attributes()->taxId)
			);
			$genotype = (string)$o->attributes()->genoType;
			if($genotype) {
				parent::addRDF(
					parent::triplifyString($id,parent::getVoc()."genotype",parent::getVoc().$genotype, "xsd:bool")
				);
			}

			// frequency
			
			// create/update
/*			if(!isset($o->Update)) $a = $o->Create;
			else $a = $o->Update;
			parent::addRDF(parent::triplifyString($id,parent::getVoc()."build",(string) $a->attributes()->build));
*/			
			//validation
			$a = $o->Validation;
			parent::addRDF(
				parent::triplifyString($id,parent::getVoc()."validation-by-cluster",(string) $a->attributes()->byCluster).
				parent::triplifyString($id,parent::getVoc()."validation-by-frequency",(string) $a->attributes()->byFrequency).
				parent::triplifyString($id,parent::getVoc()."validation-by-2hit2allele",(string) $a->attributes()->by2Hit2Allele).
				parent::triplifyString($id,parent::getVoc()."validation-by-1000G",(string) $a->attributes()->by1000G)
			);
			
			//hgvs names
			foreach($o->hgvs AS $name) {
				parent::addRDF(
					parent::triplifyString($id,parent::getVoc()."hgvs-name",(string)$name)
				);
			}
			
			
			// assembly
			$assembly = $o->Assembly;
			if($assembly and $assembly->attributes()->reference == "true") {
				parent::addRDF(
					parent::triplifyString($id,parent::getVoc()."dbsnp-build",(string) $assembly->attributes()->dbSnpBuild).
					parent::triplifyString($id,parent::getVoc()."genome-build",(string) $assembly->attributes()->genomeBuild)
				);
				
				$component = $assembly->Component;
				if($component) {
					parent::addRDF(
						parent::triplify($id,parent::getVoc()."contig-accession","genbank:".((string) $component->attributes()->accession)).
						parent::triplify($id,parent::getVoc()."contig-gi","gi:".((string) $component->attributes()->gi)).
						parent::triplifyString($id,parent::getVoc()."chromosome",((string) $component->attributes()->chromosome))
					);
					$maploc = $component->MapLoc;
					if($maploc) {
						foreach($maploc->children() AS $fxnset) {
							$fxnset_id = parent::getRes().md5($fxnset->asXML());
							parent::addRDF(
								parent::triplify($id,parent::getVoc()."maps-to",$fxnset_id).
								parent::triplify($fxnset_id,"rdf:type",parent::getVoc()."Fxnset").
								parent::describeClass(parent::getVoc()."Fxnset","Fxnset")
							);
							if(isset($fxnset->attributes()->geneId)) 
								parent::addRDF(parent::triplify($fxnset_id,parent::getVoc()."gene","ncbigene:".((string) $fxnset->attributes()->geneId)));
							if(isset($fxnset->attributes()->symbol)) 
								parent::addRDF(parent::triplifyString($fxnset_id,parent::getVoc()."gene-symbol", ((string) $fxnset->attributes()->symbol)));
							if(isset($fxnset->attributes()->mrnaAcc)) 
								parent::addRDF(parent::triplify($fxnset_id,parent::getVoc()."mrna","refseq:".((string) $fxnset->attributes()->mrnaAcc)));
							if(isset($fxnset->attributes()->protAcc))
								parent::addRDF(parent::triplify($fxnset_id,parent::getVoc()."protein","refseq:".((string) $fxnset->attributes()->protAcc)));
							if(isset($fxnset->attributes()->fxnClass)) 
								parent::addRDF(parent::triplifyString($fxnset_id,parent::getVoc()."fxn-class",((string) $fxnset->attributes()->fxnClass)));
							if(isset($fxnset->attributes()->allele)) 
								parent::addRDF(parent::triplifyString($fxnset_id,parent::getVoc()."allele",((string) $fxnset->attributes()->allele)));
							if(isset($fxnset->attributes()->residue)) 
								parent::addRDF(parent::triplifyString($fxnset_id,parent::getVoc()."residue",((string) $fxnset->attributes()->residue)));
							if(isset($fxnset->attributes()->readingFrame)) 
								parent::addRDF(parent::triplifyString($fxnset_id,parent::getVoc()."reading-frame",((string) $fxnset->attributes()->readingFrame)));
							if(isset($fxnset->attributes()->aaPosition)) 
								parent::addRDF(parent::triplifyString($fxnset_id,parent::getVoc()."position",((string) $fxnset->attributes()->aaPosition)));
						}
					}
				}
			}
		}
		unset($xml);
	}
	
	/*
	1. identifiers in dbSNP (rs#, as snp_id)
    2. MIM number of the record that has links to dbSNP
    3. Allelic variant number of the variant that corresponds to the record in dbSNP.  (If this value is 0000, the link is NOT based on an allelic
variant).
	*/
	public function processOMIMVar($lfile)
	{
		$fp = fopen($lfile,"r");
		while($l = fgets($fp)) {
			$a = explode("\t",$l);
			if($a[0]) $snps[] = "rs".$a[0];
		}
		$snps = array_unique($snps);
		fclose($fp);
		return $snps;
	}
	public function processPharmGKBSnps($lfile)
	{
		$zin = new ZipArchive();
		if($zin->open($lfile) === FALSE) {
			trigger_error("Unable to open $lfile");
			exit;
		}

		$f = "rsid.tsv";
		$fp = $zin->getStream($f);
		if(!$fp) {
			trigger_error("Unable to get pointer to $f in $lfile", E_USER_ERROR);
			return FALSE;
		}
		fgets($fp);
		fgets($fp);
		while($a = fgetcsv($fp,1000,"\t")) {
			$snps[] = $a[0];
		}
		$zin->close();

		return $snps;
	}
}

?>
