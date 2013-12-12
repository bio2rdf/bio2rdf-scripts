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
		parent::addParameter('files',true,null,'all','all|clinical|omim|snp#,snp#');
		parent::addParameter('download_url',false,null,'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=snp&retmode=xml&id=','the download url for individual snps');
		parent::initialize();
	}
	
	function run()
	{
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		
		// get the snps from pharmgkb
		$snps = explode(",",parent::getParameterValue('files'));
		if($snps == 'all') $snps = 'clinical'; // for now.

		if($snps[0] == 'clinical') {
			$snps = $this->getSNPs();
		} else if($snps[0] == 'omim') {
			$lfile = $ldir.'snp_omimvar.txt';
			if(!file_exists($lfile) || (parent::getParameterValue('download') == true)) {
				$ret = utils::DownloadSingle('ftp://ftp.ncbi.nlm.nih.gov/snp/Entrez/snp_omimvar.txt',$lfile);
			}
			$snps = $this->processOMIMVar($lfile);
		} else if($snp[0] == 'pharmgkb') {
			// @todo get the pharmgkb variants
			
		} else if($snps[0] == 'all') {
			// @todo get the big list
			
		}

		$outfile = $odir."dbsnp.".parent::getParameterValue('output_format');
		$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
		parent::setWriteFile($outfile, $gz);
		$nsnps = count($snps);

		foreach($snps AS $i => $snp) {
			$file = $snp.'.xml';
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
				$ret = utils::downloadSingle($rfile,$infile,true);
				if($ret === false) continue;
			}
			
			// process
			echo "Processing $snp ($i/$n)".PHP_EOL;
			$this->parse($infile);
			parent::writeRDFBufferToWriteFile();

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


	function getSNPs()
	{
	
		$lfile = $this->getParameterValue("indir")."ncbi-snps.xml";
		if(!file_exists($lfile) || $this->getParameterValue('download') == true) {
			$retmax = 100000;
			$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=snp&retmax=$retmax&term=%22pathogenic%22[Clinical%20Significance]%20or%20%22drug-response%22[Clinical%20Significance]%20or%20%22probable%20pathogenic%22[Clinical%20Significance]%20OR%20%22other%22[Clinical%20Significance]";
			$ret = file_get_contents($url);
			if($ret === FALSE) {
				trigger_error("Unable to get snps from ncbi eutils",E_USER_ERROR);
				return FALSE;
			}
			$ret = file_put_contents($lfile, $ret);
			if($ret === FALSE) {
				trigger_error("Unable to save snps into $lfile",E_USER_ERROR);
				return FALSE;
			}
		}
		// load the file
		$xml = simplexml_load_file($lfile);
		$json = json_encode($xml);
		$a = json_decode($json,TRUE);
		return $a['IdList']['Id'];
	}


	function parse($file)
	{	
		$xml = new CXML($file);		
		$xml->parse();
		$entry = $xml->getXMLRoot();
	//	if(!isset($entry->children())) return false;
		
		foreach($entry->children() AS $o) {
			$rsid = "rs".$o->attributes()->rsId;
			$id = parent::getNamespace().$rsid;
			
			// attributes
			parent::addRDF(
				parent::describeIndividual($id,$rsid,parent::getVoc().((string) str_replace(" ","-",(string) $o->attributes()->snpClass))).
				parent::triplifyString($id,parent::getVoc()."snp-class",(string) $o->attributes()->snpClass).
				parent::triplifyString($id,parent::getVoc()."snp-type",(string) $o->attributes()->snpType).
				parent::triplifyString($id,parent::getVoc()."mol-type",(string) $o->attributes()->molType).
				parent::triplifyString($id,parent::getVoc()."genotype",(string) $o->attributes()->genotype).
				parent::triplify($id,parent::getVoc()."taxid","ncbitaxon:".(string) $o->attributes()->taxId)
			);
			
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
			$assembly= $o->Assembly;
			if($assembly->attributes()->reference == "true") {
				parent::addRDF(
					parent::triplifyString($id,parent::getVoc()."dbsnp-build",(string) $assembly->attributes()->dbSnpBuild).
					parent::triplifyString($id,parent::getVoc()."genome-build",(string) $assembly->attributes()->genomeBuild)
				);
				
				$component = $assembly->Component;
				parent::addRDF(
					parent::triplify($id,parent::getVoc()."contig-accession","genbank:".((string) $component->attributes()->accession)).
					parent::triplify($id,parent::getVoc()."contig-gi","gi:".((string) $component->attributes()->gi)).
					parent::triplifyString($id,parent::getVoc()."chromosome",((string) $component->attributes()->chromosome))
				);
				$maploc = $component->MapLoc;
				
				foreach($maploc->children() AS $fxnset) {
					$fxnset_id = parent::getRes().md5($fxnset->asXML());
					
					parent::addRDF(
						parent::triplify($id,parent::getVoc()."maps-to",$fxnset_id).
						parent::triplify($fxnset_id,"rdf:type",parent::getVoc()."fxnset")
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
}

?>
