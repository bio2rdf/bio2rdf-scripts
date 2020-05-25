<?php

/**
Copyright (C) 2013 Alison Callahan, Jose Cruz-Toledo and Michel Dumontier

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
 * An RDF generator for HGNC (http://www.genenames.org/)
 * @version 2.0
 * @author Alison Callahan
 * @author Jose Cruz-Toledo
 * @author Michel Dumontier
*/
require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class HGNCParser extends Bio2RDFizer {
	private $version = 2.0;
	function __construct($argv){
		parent::__construct($argv, "hgnc");
		parent::addParameter('files',true,'all','all','files to process');
		parent::addParameter('download_url',false,null,'ftp://ftp.ebi.ac.uk/pub/databases/genenames/hgnc/tsv/hgnc_complete_set.txt');
		parent::initialize();
	}//constructor

	function Run(){
		$file = "hgnc_complete_set.txt";
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$rdir = parent::getParameterValue('download_url');
		$lfile = $ldir.$file;
		if(!file_exists($lfile) && parent::getParameterValue('download') == false) {
			trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
			parent::setParameterValue('download',true);
		}
		//download the hgnc file
		$rfile = null;
		if(parent::getParameterValue('download') == true) {
			$rfile = $rdir;
			echo "downloading $file ... ";
			Utils::DownloadSingle($rfile, $lfile);
		}

		$ofile = $odir."hgnc.".parent::getParameterValue('output_format');
		$gz=false;
		if(strstr(parent::getParameterValue('output_format'), "gz")){$gz = true;}

		parent::setWriteFile($ofile, $gz);
		parent::setReadFile($lfile, true);
		echo "processing $file... ";
		$this->process();
		echo "done!".PHP_EOL;
		//close write file
		parent::getWriteFile()->close();
		echo PHP_EOL;
		// generate the dataset release file
		echo "generating dataset release file... ";
		$dataset_description = '';
		$source_file = (new DataResource($this))
			->setURI($rdir)
			->setTitle('HUGO Gene Nomenclature Committee (HGNC)')
			->setRetrievedDate(date("Y-m-d\TG:i:s\Z", filemtime($lfile)))
			->setFormat('text/tab-separated-value')
			->setFormat('application/zip')
			->setPublisher('http://www.genenames.org/')
			->setHomepage('https://www.genenames.org/help/statistics-and-downloads/')
			->setRights('use')
			->setRights('attribution')
			->setLicense('http://www.genenames.org/about/overview')
			->setDataset(parent::getDatasetURI());
		
		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date("Y-m-d\TG:i:s\Z");
		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
			->setSource($source_file->getURI())
			->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/hgnc/hgnc.php")
			->setCreateDate($date)
			->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
			->setPublisher("http://bio2rdf.org")
			->setRights("use-share-modify")
			->setRights("restricted-by-source-license")
			->setLicense("http://creativecommons/licenses/by/3.0/")
			->setDataset(parent::getDatasetURI());
		
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;
		if($gz) $output_file->setFormat("application/gzip");
		if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
		else $output_file->setFormat("application/n-quads");
		$dataset_description .= $source_file->toRDF().$output_file->toRDF();
		$this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
		$this->getWriteFile()->write($dataset_description);
		$this->getWriteFile()->close();
		
	}//Run

	function process(){
		$header = $this->getReadFile()->read(200000);
		$header_arr = explode("\t", $header);
		$h = array_flip($header_arr);

		$c = count($h);
		$n = 52;
		if ($c != $n)
		{
			echo PHP_EOL;
			print_r($header_arr);
			trigger_error ("Expected $n columns, found $c . some fields may not be properly processed. update the script",E_USER_ERROR);
		}
		$this->getReadFile()->read(200000); // skip a line

		while($l = $this->getReadFile()->read(4096)) {
			$l = str_replace('"','',$l);
			$r = explode("\t", $l);
			
			$id = strtolower($r[$h['hgnc_id']]);
			$uid = str_replace(":","_",$id);
			$symbol = $r[$h['symbol']];

			parent::addRDF(
				parent::triplify($id, "rdf:type", $this->getVoc()."Gene-Symbol").
				parent::describeIndividual($id, "Gene symbol for ".$symbol, $this->getVoc()."Gene-Symbol").
				parent::describeClass($this->getVoc()."Gene-Symbol", "HGNC Official Gene Symbol")
			);

			if(!empty($symbol)){
				$s = "hgnc.symbol:".$symbol;
				parent::addRDF(
					parent::triplifyString($id, $this->getVoc()."approved-symbol",utf8_encode(htmlspecialchars($symbol))).
					parent::describeProperty($this->getVoc()."approved-symbol", "HGNC approved gene symbol","The official gene symbol that has been approved by the HGNC and is publicly available. Symbols are approved based on specific HGNC nomenclature guidelines. In the HTML results page this ID links to the HGNC Symbol Report for that gene").
					parent::describeIndividual($s, $symbol, parent::getVoc()."Approved-Gene-Symbol").
					parent::describeClass(parent::getVoc()."Approved-Gene-Symbol","Approved Gene Symbol").
					parent::triplify($id, parent::getVoc()."has-approved-symbol", $s).
					parent::triplify($s, parent::getVoc()."is-approved-symbol-of", $id)
				);
			}
			if(!empty($r[$h['name']])){
				parent::addRDF(
					parent::triplifyString($id, $this->getVoc()."approved-name",utf8_encode(htmlspecialchars($r[$h['name']]))).
					parent::describeProperty($this->getVoc()."approved-name","HGNC approved name", "The official gene name that has been approved by the HGNC and is publicly available. Names are approved based on specific HGNC nomenclature guidelines.")
				);
			}			
			if(!empty($r[$h['status']])){
				$s = $this->getVoc().str_replace(" ","-",$r[$h['status']]);
				parent::addRDF(
					parent::triplify($id, $this->getVoc()."status",$s).
					parent::describeProperty($this->getVoc()."status","HGNC status", "Indicates whether the gene is classified as: Approved - these genes have HGNC-approved gene symbols. Entry withdrawn - these previously approved genes are no longer thought to exist. Symbol withdrawn - a previously approved record that has since been merged into a another record.").
					parent::describeClass($s,$r[$h['status']],$this->getVoc()."Status")
				);
			}			
			if(!empty($r[$h['locus_group']])){
				$locus_res = $this->getRes().$uid."_locus";
				parent::addRDF(
					parent::triplify($id, $this->getVoc()."locus", $locus_res).
					parent::triplifyString($locus_res, $this->getVoc()."locus-type",utf8_encode(htmlspecialchars($r[$h['locus_type']]))).
					parent::triplifyString($locus_res, $this->getVoc()."locus-group", utf8_encode(htmlspecialchars($r[$h['locus_group']]))).
					parent::describeProperty($this->getVoc()."locus-type", "locus type","Specifies the type of locus described by the given entry").
					parent::describeProperty($this->getVoc()."locus-group", "locus group", "Groups locus types together into related sets. Below is a list of groups and the locus types within the group")
				);
			}
			if(!empty($r[$h['prev_symbol']])){
				$s = $r[$h['prev_symbol']];
				$previous_symbols = explode("|", $s);
				foreach($previous_symbols as $previous_symbol){
					$previous_symbol_uri = "hgnc.symbol:".$previous_symbol;
					parent::addRDF(
						parent::describeIndividual($previous_symbol_uri, $previous_symbol, parent::getVoc()."Previous-Symbol").
						parent::describeClass(parent::getVoc()."Previous-Symbol","Previous Symbol").
						parent::triplify($id, $this->getVoc()."previous-symbol", $previous_symbol_uri).
						parent::describeProperty($this->getVoc()."previous-symbol", "HGNC previous symbol","Symbols previously approved by the HGNC for this gene")
					);
				}
			}
			if(!empty($r[$h['prev_name']])){
				$s = $r[$h['prev_name']];
				$previous_names = explode("|", $s);
				foreach($previous_names as $previous_name){
					parent::addRDF(
						parent::triplifyString($id, $this->getVoc()."previous-name",  utf8_encode(htmlspecialchars($previous_name))).
						parent::describeProperty($this->getVoc()."previous-name", "HGNC previous name","Gene names previously approved by the HGNC for this gene")
					);
				}
			}
			if(!empty($r[$h['prev_symbol']])){
				$s = $r[$h['prev_symbol']];
				$prev_symbols = explode('|',$s);
				foreach ($prev_symbols as $prev_symbol) {
					parent::addRDF(
						parent::triplifyString($id, $this->getVoc()."prev-symbol",  utf8_encode(htmlspecialchars($prev_symbol))).
						parent::describeProperty($this->getVoc()."prev-symbol", "previous symbol","Previously used symbols used to refer to this gene")
					);
				}
			}
			if(!empty($r[$h['alias_name']])){
				$s = $r[$h['alias_name']];
				$alias_names = explode("|", $s);
				foreach ($alias_names as $alias_name) {
					parent::addRDF(
						parent::triplifyString($id, $this->getVoc()."alias-name",  utf8_encode(htmlspecialchars($alias_name))).
						parent::describeProperty($this->getVoc()."alias-name", "alias name","Other names used to refer to this gene")
					);
				}
			}
			if(!empty($r[$h['alias_symbol']])){
				$s = $r[$h['alias_symbol']];
				$alias_symbols = explode("|", $s);
				foreach ($alias_symbols as $alias_symbol) {
					parent::addRDF(
						parent::triplifyString($id, $this->getVoc()."alias-symbol",  utf8_encode(htmlspecialchars($alias_symbol))).
						parent::describeProperty($this->getVoc()."alias-symbol", "alias symbol","Other symbols used to refer to this gene")
					);
				}
			}	
			if(!empty($r[$h['location']])){
				$s = $r[$h['location']];
				parent::addRDF(
					parent::triplifyString($id, $this->getVoc()."location",  utf8_encode(htmlspecialchars($s))).
					parent::describeProperty($this->getVoc()."location", "location", "Indicates the location of the gene or region on the chromosome")
				);
			}
			if(!empty($r[$h['date_approved_reserved']])){
				$s = $r[$h['date_approved_reserved']];
				parent::addRDF(
					parent::triplifyString($id, $this->getVoc()."date-approved", $s, "xsd:date").
					parent::describeProperty($this->getVoc()."date-approved", "date approved","Date the gene symbol and name were approved by the HGNC")
				);
			}
			if(!empty($r[$h['date_modified']])){
				$s = $r[$h['date_modified']];
				parent::AddRDF(
					parent::triplifyString($id, $this->getVoc()."date-modified", $s, "xsd:date").
					parent::describeProperty($this->getVoc()."date-modified", "date modified", "the date the entry was modified by the HGNC")
				);
			}
			if(!empty($r[$h['date_symbol_changed']])){
				$s = $r[$h['date_symbol_changed']];
				parent::AddRDF(
					parent::triplifyString($id, $this->getVoc()."date-symbol-changed", $s, "xsd:date").
					parent::describeProperty($this->getVoc()."date-symbol-changed", "date symbol changed","The date the gene symbol was last changed by the HGNC from a previously approved symbol. Many genes receive approved symbols and names which are viewed as temporary (eg C2orf#) or are non-ideal when considered in the light of subsequent information. In the case of individual genes a change to the name (and subsequently the symbol) is only made if the original name is seriously misleading")
				);
			}
			if(!empty($r[$h['date_name_changed']])){
				$s = $r[$h['date_name_changed']];
				parent::addRDF(
					parent::triplifyString($id, $this->getVoc()."date-name-changed", $s, "xsd:date").
					parent::describeProperty($this->getVoc()."date-name-changed", "date name changed", "The date the gene name was last changed by the HGNC from a previously approved name")
				);
			}

			$idmap = array(
				"entrez_id" => "ncbigene",
				"ensembl_gene_id" => "ensembl",
				"vega_id" => "vega",
				"ucsc_id" => "ucsc",
				"ena" => "ena",
				"refseq_accession" => "refseq",
				"ccds_id" => "ccds",
				"uniprot_ids" => "uniprot",
				"pubmed_id" => "pubmed",
				"mgd_id" => "mgd",
				"rgd_id" => "rgd",
				// "lsdb" => "lsdb", # special structure
				"cosmic" => "cosmic",
				"omim_id" => "omim",
				"mirbase" => "mirbase",
				"homeodb" => "homeodb",
				"snornabase" => "snornabase",
				"bioparadigms_slc" => "bioparadigms_slc",
				"orphanet" =>"orphanet",
				"pseudogene.org" => "pseudogene",
				"horde_id" => "horde",
				"merops" => "merops",
				"imgt" => "imgt",
				"iuphar" => "iuphar",
				"kznf_gene_catalog" => "kznf",
				"mamit-trnadb" => "mamit",
				"cd" => "hcdm",
				"lncrnadb" => "lncrnadb",
				"enzyme_id" => "ec",
				"intermediate_filament_db" => "intermediate_filament_db",
				"rna_central_ids" => "rna_central_ids",
				"lncipedia" => "lncipedia",
				"gtrnadb" => "gtrnadb",
				// "agr" => "agr" #uses hgnc?
			);
			foreach($idmap AS $fieldname => $prefix) {
				if(!empty($r[$h[$fieldname]])){
					$s = $r[$h[$fieldname]];
					$identifiers = explode("|", $s);
					foreach ($identifiers as $identifier) {
						// some identifiers are prefixed...
						$pos = strpos($identifier,":");
						if($pos !== FALSE) {
							$identifier = substr($identifier, strpos($identifier, ":")+1);
						}

						parent::addRDF(
							parent::triplify($id, $this->getVoc()."x-".$prefix, $prefix.":".$identifier).
							parent::describeProperty($this->getVoc()."x-".$prefix, $prefix, "reference to an entry in the $prefix database")
						);
					}
				}
			}
			if(!empty($r[$h['gene_family_id']])){
				$s = $r[$h['gene_family_id']];
				parent::AddRDF(
					parent::triplifyString($id_res, $this->getVoc()."gene-family-tag",  utf8_encode(htmlspecialchars($s))).
					parent::describeProperty($this->getVoc()."gene-family-tag", "Gene Family Tag","Tag used to designate a gene family or group the gene has been assigned to, according to either sequence similarity or information from publications, specialist advisors for that family or other databases. Families/groups may be either structural or functional, therefore a gene may belong to more than one family/group. These tags are used to generate gene family or grouping specific pages at genenames.org and do not necessarily reflect an official nomenclature. Each gene family has an associated gene family tag and gene family description. If a particular gene is a member of more than one gene family, the tags and the descriptions will be shown in the same order.")
				);
			}

			if(!empty($r[$h['gene_family']])){
				$s = $r[$h['gene_family']];
				parent::AddRDF(
					parent::triplifyString($id_res, $this->getVoc()."gene-family-description",  utf8_encode(htmlspecialchars($s))).
					parent::describeProperty($this->getVoc()."gene-family-description", "gene family name","Name given to a particular gene family. The gene family description has an associated gene family tag. Gene families are used to group genes according to either sequence similarity or information from publications, specialist advisors for that family or other databases. Families/groups may be either structural or functional, therefore a gene may belong to more than one family/group.")
				);
			}
			//write RDF to file
			$this->WriteRDFBufferToWriteFile();
		}//while
		
	}//process

}//HGNCParser

?>
