<?php
/**
Copyright (C) 2013 Jose Cruz-Toledo, Alison Callahan

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
 * NCBI Gene RDFizer
 * @version 2.0
 * @author Jose Cruz-Toledo
 * @author Alison Callahan
 * @author Michel Dumontier
 * @description ftp://ftp.ncbi.nih.gov/gene/DATA/
*/
require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class NCBIGeneParser extends Bio2RDFizer
{
	private static $packageMap = array(
		"geneinfo" => "GENE_INFO/All_Data.gene_info.gz",
		"gene2accession" => "gene2accession.gz",
		"gene2ensembl" => "gene2ensembl.gz",
		"gene2go" => "gene2go.gz",
		"gene2pubmed" => "gene2pubmed.gz",
		"gene2refseq" => "gene2refseq.gz",
		"gene2sts" => "gene2sts",
		"gene2unigene" => "gene2unigene",
		"gene2vega" => "gene2vega.gz",					
	);
	private $taxids = null;
	private $default_taxids = array(
		"9606" => "Homo Sapiens",
		"44689" => "Dictyostelium Discoideum",
		"4896" => "Schizosaccharomyces pombe",
		"4932" => "Saccharomyces cerevisiae",
		"6239" => "Caenorhabditis elegans",
		"7227" => "Drosophila melanogaster",
		"8355" => "Xenopus laevis",
		"9913" => "Bos taurus",
		"10116" => "Rattus norvegicus",
		"10090" => "Mus musculus",
		"7955" => "Danio rerio",
		"3702" => "Arabidopsis thaliana",
		"562" => "Escherichia coli"
	);
	function __construct($argv) {
		parent::__construct($argv,"ncbigene");

		// set and print application parameters
		parent::addParameter('files',true,'all|geneinfo|gene2accession|gene2ensembl|gene2go|gene2pubmed|gene2refseq|gene2sts|gene2unigene|gene2vega','','files to process');
		parent::addParameter('download_url',false,null,'ftp://ftp.ncbi.nih.gov/gene/DATA/');
		parent::addParameter('limit_organisms',false,'true|false','false','flag to use specified organisms');
		parent::addParameter('organisms',false,null,implode(",",array_keys($this->default_taxids)),'taxonomy ids for organisms to process');
		parent::initialize();
	}//constructor

	function Run()
	{
		$this->process();
	}//run


	function process()
	{
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$rdir = parent::getParameterValue('download_url');

		//which files are to be converted?
		$files = trim($this->GetParameterValue('files'));
		if($files == 'all') {
			$files = $this->getPackageMap();
		} else {
			$sel_arr = explode(",",$files);
			$pm = $this->getPackageMap();
			$files = array();
			foreach($sel_arr as $a){
				if(array_key_exists($a, $pm)){
					$files[$a] = $pm[$a];
				}
			}
		}
		if($this->getParameterValue('limit_organisms') == true) {
			$this->taxids = array_flip(explode(",",$this->getParameterValue('organisms')));
		}
		//set dataset graph to be dataset URI
		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		$dataset_description = '';

		//now iterate over the files array
		foreach ($files as $module => $rfilename){
			$file = $module.".gz";
			$lfile = $ldir.$file;
			$rfile = $rdir.$rfilename;

			// download
			if(!file_exists($lfile) || parent::getParameterValue('download') == true) {
				trigger_error("$lfile not found. Will attempt to download.", E_USER_NOTICE);		
				$myfile = $lfile;
				if($module == "gene2sts" || $module == "gene2unigene") {
					$myfile = "compress.zlib://".$lfile;
				}
				echo "downloading $module ...";
				utils::DownloadSingle($rfile, $myfile);
				echo "done".PHP_EOL;
			}
		}

		foreach($files AS $module => $rfilename) {
			$file = $module.".gz";
			$lfile = $ldir.$file;
			$rfile = $rdir.$rfilename;
			$ofile = $module.".".parent::getParameterValue('output_format');

			$gz = false;
			if(strstr(parent::getParameterValue('output_format'), "gz")) $gz = true;

			echo "Processing $module ... ";	
			parent::setReadFile($lfile, true);
			parent::setWriteFile($odir.$ofile, $gz);
			$fnx = $module;
			if($module == 'gene2refseq') $fnx = 'gene2accession';
			$this->$fnx();
			parent::clear();

			echo 'done!'.PHP_EOL;
			parent::getReadFile()->close();
			parent::getWriteFile()->close();

			// generate the dataset release file
			// dataset description
			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("NCBI Gene ($module)")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
				->setFormat("text/tab-separated-value")
				->setFormat("application/gzip")	
				->setPublisher("http://www.ncbi.nlm.nih.gov")
				->setHomepage("http://www.ncbi.nlm.nih.gov/gene")
				->setRights("use-share-modify")
				->setLicense("http://www.ncbi.nlm.nih.gov/About/disclaimer.html")
				->setDataset("http://identifiers.org/ncbigene/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/ncbigene/ncbigene.php")
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
			
			$dataset_description .= $source_file->toRDF().$output_file->toRDF();
			
		}//foreach
		
		//set graph URI back to default value
		parent::setGraphURI($graph_uri);
		//write dataset description to file
		echo "Generating dataset description... ";
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;
		
	}

	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2vega(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 7){
					$taxid = trim($splitLine[0]);
					if(isset($this->taxids) and !isset($this->taxids[$taxid])) {continue;}
					$aGeneId = trim($splitLine[1]);
					$vegaGeneId = trim($splitLine[2]);
					$rnaNucleotideAccession = trim($splitLine[3]);
					$vegaRnaIdentifier = trim($splitLine[4]);
					$proteinAccession = trim($splitLine[5]);
					$vegaProteinId = trim($splitLine[6]);
					//taxid
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."x-taxonomy", "taxonomy:".$taxid)
					);

					//vega gene identifier
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."x-vega.gene", "vega:".$vegaGeneId)
					);

					//vega rna id
					if($vegaRnaIdentifier != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."x-vega.rna", "vega:".$vegaRnaIdentifier)
						);
					}
					//vega protein
					if($vegaProteinId != "-"){
						$this->AddRDF(
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."x-vega.protein", "vega:".$vegaProteinId)
						);
					}
				}
				parent::writeRDFBufferToWriteFile();
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2sts(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
			$splitLine = explode("\t",$aLine);
			if(count($splitLine) == 2){
				$aGeneId = trim($splitLine[0]);
				$uniStsId = trim($splitLine[1]);
				$this->AddRDF(
					parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."x-unists", "unists:".$uniStsId)
				);
			}//if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2unigene(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 2){
					$aGeneId = trim($splitLine[0]);
					$unigene_cluster = trim($splitLine[1]);
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."x-unigene", "unigene:".$unigene_cluster)
					);
				}//if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2pubmed(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 3){
					$taxid = trim($splitLine[0]);
					if(isset($this->taxids) and !isset($this->taxids[$taxid])) {continue;}
					$aGeneId = trim($splitLine[1]);
					$pubmedId = trim($splitLine[2]);
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."x-taxonomy", "taxon:".$taxid).
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."x-pubmed", "pubmed:".$pubmedId)
					);
				}//if
			parent::writeRDFBufferToWriteFile();
		}//while
	}

	// see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2ensembl()
	{
		$h = $this->getReadFile()->read(200000);
		//tax_id GeneID Ensembl_gene_identifier RNA_nucleotide_accession.version Ensembl_rna_identifier protein_accession.version Ensembl_protein_identifier
		$header = array(
			"0" => array ('rel' => "x-taxonomy", 'ns' => 'taxonomy'),
			"1" => array ('rel' => "ncbigene", 'ns' => 'ncbigene'),
			"2" => array ('rel' => "x-ensembl.gene", 'ns' => 'ensembl'),
			"3" => array ('rel' => "rna-accession.version", 'ns' => 'genbank'),
			"4" => array ('rel' => "x-ensembl.rna", 'ns' => 'ensembl'),
			"5" => array ('rel' => "protein-accession", 'ns' => 'genbank'),
			"6" => array ('rel' => "x-ensembl.protein", 'ns' => 'ensembl')
		);
		while($l = $this->getReadFile()->read(200000)){
			$a = explode("\t",rtrim($l));
			if(count($a) != 7) { trigger_error("gene2ensembl: expecting 7 columns, found ".count($a)." instead", E_USER_ERROR);}
			$id = parent::getNamespace().$a[1];

			$taxid = $a[0];
			if(isset($this->taxids) and !isset($this->taxids[$taxid])) {continue;}

			foreach($header AS $i => $v) {
				if($a[$i] == "-" or $i == "1") continue;
				$this->addRDF(
					parent::triplify($id, $this->getVoc().$v['rel'], $v['ns'].':'.$a[$i])
				);
			}
			parent::writeRDFBufferToWriteFile();
		}//while
	}

	// see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2accession()
	{
		$this->getReadFile()->read(200000);
		$header = array(
			0 => array('rel'=>"x-taxonomy",'ns'=>"taxonomy"),
			1 => array('rel'=>"ncbigene",'ns'=>"ncbigene"),
			2 => array('rel'=>"status"),
			3 => array('rel'=>"rna-nucleotide-accession.version",'ns'=>"genbank"),
			4 => array('rel'=>"rna-nucleotide-gi",'ns'=>"gi"),
			5 => array('rel'=>"protein-accession.version",'ns'=>"genbank"),
			6 => array('rel'=>"protein-gi",'ns'=>"gi"),
			7 => array('rel'=>"genomic-nucleotide-accession.version",'ns'=>"genbank"),
			8 => array('rel'=>"genomic-nucleotide-gi",'ns'=>"gi"),
			9 => array('rel'=>"genomic-start-position"),
			10 => array('rel'=>"genomic-end-position"),
			11 => array('rel'=>"orientation"),
			12 => array('rel'=>"assembly"),
			13 => array('rel'=>"mature-peptide-accession.version",'ns'=>"genbank"),
			14 => array('rel'=>"mature-peptide-gi",'ns'=>"gi"),
			15 => array('rel'=>"symbol")
		);
		//(tab is used as a separator, pound sign - start of a comment) */
		$z = 1;
		while($l = $this->getReadFile()->read(200000)){
			if($l[0] == "#") continue;
			if(($z++) % 10000 == 0) {echo $z.PHP_EOL;parent::clear();}
			$a = explode("\t",rtrim($l));
			if(count($a) != 16) { trigger_error("gene2accession: expecting 16 columns, found ".count($a)." instead", E_USER_ERROR);}
			$taxid = $a[0];
			if(isset($this->taxids) and !isset($this->taxids[$taxid])) {continue;}

			$id = parent::getNamespace().$a[1];
			$refseq = false;
			if($a[2] != '-') $refseq = true;
			if($a[9] != '-' and $a[10] != '-') {
				$region = parent::getRes().$a[7]."/".$a[9]."-".$a[10];
				$start_pos = parent::getRes().$a[7]."/".$a[9];
				$stop_pos = parent::getRes().$a[7]."/".$a[10];
				if($a[11] == "+") $orientation = "faldo:ForwardStrandPosition";
				else if($a[11] == "-") $orientation = "faldo:ReverseStrandPosition";
				else $orientation = "faldo:StrandedPosition";

				parent::addRDF(
					parent::describeIndividual($region,"location of ncbigene:".$a[1]." on ".$a[7],"faldo:Region").
					parent::describeIndividual($start_pos,"start of ncbigene:".$a[1]." on ".$a[7],"faldo:ExactPosition").
					parent::describeIndividual($stop_pos,"stop position of ncbigene:".$a[1]." on ".$a[7],"faldo:ExactPosition").
					parent::triplify($id,"faldo:location",$region).
					parent::triplify($region,"faldo:begin",$start_pos).
					parent::triplify($start_pos,"rdf:type",$orientation).
					parent::triplifyString($start_pos,"faldo:position",$a[9],"xsd:integer").
					parent::triplify($start_pos,"faldo:reference","refseq:".$a[7]).
					parent::triplify($region,"faldo:end",$stop_pos).
					parent::triplify($stop_pos,"rdf:type",$orientation).
					parent::triplifyString($stop_pos,"faldo:position",$a[10],"xsd:integer").
					parent::triplify($stop_pos,"faldo:reference","refseq:".$a[7])
				);
			}

			foreach($header AS $i => $v) {
				if($a[$i] == "-") continue;
				if($i == 1 or $i == 9 or $i == 10 or $i == 11) continue; /// ncbigene

				if(isset($v['ns'])) {
					$ns = $v['ns'];
					if($ns == 'genbank' and $refseq == true) $ns = 'refseq';
					parent::addRDF(
						parent::triplify($id, parent::getVoc().$v['rel'], "$ns:".$a[$i])
					);
				} else {
					parent::addRDF(
						parent::triplifyString($id, parent::getVoc().$v['rel'], $a[$i])
					);
				}
			}
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	
	// #see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function gene2go(){
		$this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
				$id = 1;
				$splitLine = explode("\t",$aLine);
				if(count($splitLine) == 8){
					$taxid = "taxon:".trim($splitLine[0]);

					if(isset($this->taxids) and !isset($this->taxids[ trim($splitLine[0]) ])) {continue;}

					$aGeneId = trim($splitLine[1]);
					$goid = strtolower(trim($splitLine[2]));
					$evidenceCode = trim($splitLine[3]);
					$qualifier = trim($splitLine[4]);
					$golabel = trim($splitLine[5]);
					$pmids = explode("|", $splitLine[6]);
					$goCategory = strtolower(trim($splitLine[7]));
					
					// $this->AddRDF($this->QQuad($geneid,"geneid_vocabulary:has_taxid",$taxid));
					$this->AddRDF(
						parent::triplify($this->getNamespace().$aGeneId, $this->getVoc().$goCategory, $goid).
						parent::describeProperty($this->getVoc().$goCategory, "Relationship between a gene and a GO $goCategory")
					);

					$i = substr($goid,3);

					//evidence
					if($evidenceCode != "-"){
						// create an evidence object
						$eid = $this->getRes().$aGeneId."_".$i;
						$this->AddRDF(
							parent::describeIndividual($eid, $this->getNamespace().$aGeneId."-$goid association", $this->getVoc()."Gene-$goCategory-Association").
							parent::triplify($this->getNamespace().$aGeneId, $this->getVoc()."gene-".$goCategory."-association", $eid).
							parent::triplify($eid, $this->getVoc()."evidence", "eco:$evidenceCode").
							parent::triplify($eid, $this->getVoc()."gene", $this->getNamespace().$aGeneId).
							parent::triplifyString($eid, $this->getVoc()."go-category", $goCategory).
							parent::triplify($eid, $this->getVoc()."go-term", $goid).
							parent::describeProperty($this->getVoc()."gene-".$goCategory."-association", "Relationship between a gene and a gene-$goCategory-association")

						);
						foreach ($pmids as $pmid){
							if($pmid != '-'){
								$this->AddRDF(
									parent::triplify($eid, $this->getVoc()."publication", "pubmed:".$pmid)
								);
							}
						}
					} 
				} //if
			parent::writeRDFBufferToWriteFile();
		}//while
	}
	
	
	#see: ftp://ftp.ncbi.nlm.nih.gov/gene/DATA/README
	private function geneinfo(){
		$i = 1;
		$header = $this->GetReadFile()->Read(200000);
		while($aLine = $this->GetReadFile()->Read(200000)){
			if(($i++) % 1000 == 0) parent::clear();
			$a = $splitLine = explode("\t", $aLine);
			if(count($splitLine) == 15){
				$taxid = "taxon:".trim($splitLine[0]);
				if(isset($this->taxids) and !isset($this->taxids[ trim($splitLine[0]) ])) {continue;}
				$aGeneId = trim($splitLine[1]);
				$geneid = "ncbigene:".trim($splitLine[1]);
				$symbol = addslashes(stripslashes(trim($splitLine[2])));
				$symbolid =  "symbol:$symbol";
				$locusTag = trim($splitLine[3]);
				$symbols_arr = explode("|",$splitLine[4]);
				$dbxrefs_arr = explode("|",$splitLine[5]);
				$chromosome = trim($splitLine[6]);
				$map_location = trim($splitLine[7]);
				$description = addslashes(stripslashes(trim($splitLine[8])));
				$type_of_gene = trim($splitLine[9]);
				$symbol_authority = addslashes(stripslashes(trim($splitLine[10])));
				$symbol_auth_full_name = addslashes(stripslashes(trim($splitLine[11])));
				$nomenclature_status = addslashes(stripslashes(trim($splitLine[12])));
				$other_designations = addslashes(stripslashes(trim($splitLine[13])));
				$mod_date = date_parse(trim($splitLine[14]));
				//check for a valid symbol
				if($symbol != "NEWENTRY"){

					$this->AddRDF(
						parent::describeIndividual($geneid, "$description ($symbolid, $taxid)", $this->getVoc()."Gene").
						parent::triplify($geneid, $this->getVoc()."x-taxonomy", $taxid).
						parent::triplifyString($geneid, $this->getVoc()."symbol", $symbol).
						parent::triplifyString($geneid, $this->getVoc()."locus", addslashes(stripslashes($locusTag))).
						parent::describeClass($this->getVoc()."Gene", "NCBI Gene gene")
					);

			
					if($type_of_gene != '-') {
						$this->AddRDF(
							parent::triplify($geneid, "rdf:type", $this->getVoc().ucfirst($type_of_gene)."-Gene").
							parent::describeClass($this->getVoc().ucfirst($type_of_gene)."-Gene", ucfirst($type_of_gene)." Gene")
						);
					} 
					
					//symbol synonyms
					foreach($symbols_arr as $s){
						if($s != "-"){
							$this->AddRDF(
								parent::triplifyString($geneid, $this->getVoc()."symbol-synonym", addslashes(stripslashes($s)))
							);
						}
					}				
					//dbxrefs
					foreach($dbxrefs_arr as $dbx){
						if($dbx != "-"){
							$this->AddRDF(
								parent::triplifyString($geneid, $this->getVoc()."dbxref", $dbx)
							);
						}
					}
					//chromosome
					if($chromosome != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."chromosome", $chromosome)
						);
					}
					//map location
					if($map_location != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."map-location", $map_location)
						);
					}
					//description
					if($description != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, "dc:description", $description)
						);
					}
					//nomenclature authority
					if($symbol_authority != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."nomenclature-authority", $symbol_authority)
						);

						if($symbol_auth_full_name != "-"){
							$this->AddRDF(
								parent::triplifyString($geneid, $this->getVoc()."nomenclature-authority-fullname", $symbol_auth_full_name)
							);
						}
					}
					//nomenclature status
					if($nomenclature_status != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."nomenclature-status", $nomenclature_status)
						);
					}
					//other designations
					if($other_designations != "-"){
						foreach(explode("|",$other_designations) AS $d) {
							$this->AddRDF(
								parent::triplifyString($geneid, $this->getVoc()."other-designation", $d)
							);
						}
					}				
					//modification date
					if($mod_date != "-"){
						$this->AddRDF(
							parent::triplifyString($geneid, $this->getVoc()."modification-date", $mod_date["year"]."-".$mod_date["month"]."-".$mod_date["day"])
						);
					}
				}
			}
			parent::writeRDFBufferToWriteFile();
		} // while
	}
	
	public function getPackageMap(){
		return self::$packageMap;
	}	
}
?>
