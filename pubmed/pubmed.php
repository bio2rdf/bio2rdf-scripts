<?php
/**
Copyright (C) 2013 Alison Callahan, Michel Dumontier

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
 * An RDF generator for PubMed (http://ncbi.nlm.nih.gov/pubmed)
 * @version 3
 * @author Alison Callahan
 * @author Michel Dumontier
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class PubmedParser extends Bio2RDFizer
{
	function __construct($argv) {
		parent::__construct($argv, "pubmed");
		parent::addParameter('files',false,null,null,'files to process');
		parent::addParameter('download_url',false,null,'ftp.ncbi.nlm.nih.gov','location of pubmed files');
		parent::initialize();
	}//constructor

	function run() {

		if(parent::getParameterValue('process') === true){
			$this->process_dir();
		}
	}//run

	function getFtpFileList($ftp_uri, $directory, $pattern){
		$rm = array();
		// set up basic connection
		$conn_id = ftp_connect($ftp_uri);
		$ftp_user = 'anonymous';
		if (FALSE === (@ftp_login($conn_id, $ftp_user, ''))) {
		    echo "Couldn't connect as $ftp_user\n";
		    exit;
		}

		// get contents of the current directory
		$contents = ftp_nlist($conn_id, $directory);
		if(FALSE === $contents) {
			trigger_error("Unable to obtain file list with directory $directory");
			exit;
		}
		foreach($contents as $aFile){
			preg_match($pattern, $aFile, $matches);
			if(count($matches)){
				print_r($matches);
				$rm[] = $matches[1];
			}
		}
		return $rm;
	}

	function downloadFiles($ldir, $rdir) {
		if($this->getParameterValue('download') == true){
			$host = parent::getParameterValue('download_url'); 
			$list = $this->getFtpFileList($host, $rdir, "/\/(.*xml\.gz)\$/");
			$total = count($list);
			$counter = 1;
			foreach($list as $f){
				$path_parts = pathinfo($f);
				$lfile = $ldir.$path_parts['basename'];
				$rfile = "http://".$host.$rdir.$path_parts['basename'];

				echo "downloading file $counter out of $total : $rfile ".PHP_EOL;
				file_put_contents($lfile,file_get_contents($rfile));
				$counter++;
			}
		}
	}
/*
	function extractCitationFile()
	{
		$item_start = '<MedlineCitation '; // defines the start of each item
		$id_regexp = '/<PMID Version=\"\d\">(\d+)<\/PMID>/'; // unique identifier used for the filename

		// process all files ending in .xml.gz
		foreach (glob("$input_dir/*.xml.gz") as $file){ 
			// not-particularly-strict stream parsing of large xml files 
			$handle = gzopen($file,'rb');
			print "Processing $file\n";
			while (!feof($handle)) {
				$line = fgets($handle);
				if (ereg($item_start, $line)) {
					if (isset($id)){
						$i = ceil($id/100000);
						@mkdir("$output_dir/$i");
						$output_file = "$output_dir/$i/$id.xml.gz";
						// save the individual article data, gzipped						
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
*/
	function process_dir(){
		$this->setCheckPoint('dataset');

		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$this->id_list = null;
		if(parent::getParameterValue('id_list') != '') {
			$this->id_list = array_flip(explode(",", trim(parent::getParameterValue("id_list"))));
		}

		$graph_uri = parent::getGraphURI();
		$dataset_description = '';
		$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;

		//set graph URI to dataset graph
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		if($this->getParameterValue('download') == true){
			$this->downloadFiles($ldir, "/pubmed/baseline/");
		}
		if($this->getParameterValue("files") != '') {
			$list = explode(",",$this->getParameterValue("files"));
			$files = array();
			foreach($list AS $item) {
				$files[] = $ldir.$item;
			}
		} else {
			$files = glob($ldir."*.xml.gz");
		}

		foreach($files AS $i => $file) {
			echo "Processing $file (".($i+1)."/".count($files).") ...";
			$this->process_file($file);
			parent::clear();
			echo "done!".PHP_EOL;
		}

		$source_file = (new DataResource($this))
			->setURI("http://www.ncbi.nlm.nih.gov/pubmed")
			->setTitle("NCBI PubMed")
			->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($ldir)))
			->setFormat("text/xml")
			->setPublisher("http://ncbi.nlm.nih.gov/")
			->setHomepage("http://www.ncbi.nlm.nih.gov/pubmed/")
			->setRights("use-share-modify")
			->setLicense("http://www.nlm.nih.gov/databases/license/license.html")
			->setDataset("http://identifiers.org/pubmed/");

		$prefix = parent::getPrefix();
		$bVersion = parent::getParameterValue('bio2rdf_release');
		$date = date ("Y-m-d\TG:i:s\Z");
		$output_file = (new DataResource($this))
			->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix")
			->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
			->setSource($source_file->getURI())
			->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/pubmed/pubmed.php")
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

		//set graph URI back to default
		parent::setGraphURI($graph_uri);

		// write the dataset description
		$this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
		$this->getWriteFile()->write($dataset_description);
		$this->getWriteFile()->close();
	}//process)dir

	function process_file($infile)
	{
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$suffix = parent::getParameterValue('output_format');
		$ofile = $odir.basename($infile, ".xml.gz").'.'.$suffix;
		$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
		$fp = gzopen($infile, "r") or die("Could not open file ".$infile."!\n");
		$this->setReadFile($infile);
		$this->getReadFile()->setFilePointer($fp);
		$this->setWriteFile($ofile, $gz);
		$this->setCheckPoint('file');

		
		$path = pathinfo($infile);
		#print_r($path);exit;
		#echo $ldir.$infile;exit;
		$insidezip_file = $path['filename'];
		$xml = new CXML($infile, $insidezip_file);
		while($xml->parse("PubmedArticle") == TRUE) {			
			#if(isset($this->id_list) and count($this->id_list) == 0) break;
			$this->parsePubmedArticle($xml);
		}
		unset($xml);

		
		$this->writeRDFBufferToWriteFile();
		$this->getWriteFile()->close();	
	}

	function getString($str) 
	{   // utf8_encode(str_replace(array("\\", "\"", "'"), array("/", "", ""),
		return (($str));
	}

	function parsePubmedArticle($xml)
	{
		$citations = null;
		#$ext = substr(strrchr($this->getReadFile()->getFileName(), '.'), 1);
/*
		if($ext = "gz"){
			$citations = new SimpleXMLElement("compress.zlib://".$this->getReadFile()->getFileName(), NULL, TRUE);
		} elseif($ext="xml"){
			$citations = new SimpleXMLElement($this->getReadFile()->getFileName(), NULL, TRUE);
		}
		*/
		$x = $xml->GetXMLRoot();
		$citation = $x->MedlineCitation;

		
		$this->setCheckPoint('record');	
		$pmid = "".$citation->PMID;
		if(isset($this->id_list)) {
			if(!isset($this->id_list[$pmid])) return;
			else echo "processing $pmid".PHP_EOL;
		}
		$pmid_uri = parent::getNamespace().$citation->PMID;
		$article = $citation->Article;

		parent::addRDF(
			parent::describeIndividual($pmid_uri, addslashes($this->getString($article->ArticleTitle)), parent::getVoc()."PubMedRecord").
			parent::describeClass(parent::getVoc()."PubMedRecord","PubMedRecord").
			parent::triplify($pmid_uri,"rdfs:seeAlso","http://www.ncbi.nlm.nih.gov/pubmed/$pmid")
		);
		
		// metadata about the record
		$owner = parent::getRes().md5($citation['Owner']);
		parent::addRDF(
			parent::describeIndividual($owner, $citation['Owner'], "foaf:Agent").
			parent::triplify($pmid_uri, parent::getVoc()."owner", $owner)
		);
		$status = parent::getRes().md5($citation['Status']);
		parent::addRDF(
			parent::describeIndividual($status, $citation['Status'], parent::getVoc()."Status").
			parent::describeClass(parent::getVoc()."Status", "Status").
			parent::triplify($pmid_uri, parent::getVoc()."status", $status).
			parent::triplifyString($pmid_uri, parent::getVoc()."version", $citation['VersionID'])
		);
		$this->addDate($pmid_uri,"version-date",$citation['VersionDate']);
		$this->addDate($pmid_uri,"date-created",$citation->DateCreated);
		$this->addDate($pmid_uri,"date-revised",$citation->DateRevised);
		$this->addDate($pmid_uri,"date-completed",$citation->DateCompleted);
		
		
		if(!empty($citation->MeshHeadingList)){
			$i = 0;
			foreach($citation->MeshHeadingList->MeshHeading AS $mh){
				$id = parent::getRes().$pmid."_mh_".++$i;
				$did = "mesh:".$mh->DescriptorName['UI'];
				parent::addRDF(
					parent::describeIndividual($id, $mh->DescriptorName, parent::getVoc()."MeshHeading").
					parent::triplify($id, parent::getVoc()."x-mesh", $did).
					parent::triplifyString($id, parent::getVoc()."descriptor-major-topic", "".$mh->DescriptorName['MajorTopicYN']).
					parent::describeClass(parent::getVoc()."MeshHeading","MeSH Heading").
					parent::triplify($pmid_uri, parent::getVoc()."mesh-heading", $id)
				);
				if(!empty($mh->QualifierName)){
					foreach($mh->QualifierName AS $qualifier_name) {
						$qid = "mesh:".$mh->QualifierName['UI'];
						parent::addRDF(
							parent::describeIndividual($qid, $qualifier_name, parent::getVoc()."Mesh-Qualifier").
							parent::triplify($id, parent::getVoc()."mesh-qualifier", $qid)
						);
					}
				}
			}
		}

		if(!empty($citation->ChemicalList)){
			$i = 0;
			foreach($citation->ChemicalList->Chemical as $chemical){
				$id = parent::getRes().$pmid."_ch_".++$i;
				$mesh_id = "mesh:".$chemical->NameOfSubstance['UI'];
				parent::addRDF(
					parent::describeIndividual($id, $chemical->NameOfSubstance, parent::getVoc()."Chemical").
					parent::triplify($id,parent::getVoc()."x-mesh",$mesh_id).
					parent::describeClass(parent::getVoc()."Chemical","Chemical").
					parent::triplify($pmid_uri, parent::getVoc()."chemical", $id)
				);
				if($chemical->RegistryNumber != "0"){
					// check if "EC"
					if(substr($chemical->RegistryNumber,0,2) == "EC") {
						$ec = substr($chemical->RegistryNumber,3);
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."x-ec", "ec:".$ec)
						);				
					} else {
						parent::addRDF(
							parent::triplify($id, parent::getVoc()."x-cas", "cas:".$chemical->RegistryNumber)
						);
					}
				}
			}
		}
		
		if(!empty($citation->GeneSymbolList)){
			foreach($citation->GeneSymbolList->GeneSymbol as $geneSymbol){
				parent::addRDF(
					parent::triplifyString($pmid_uri, parent::getVoc()."gene-symbol", $geneSymbol)
				);
			}
		}
		
		if(!empty($citation->SupplMeshList)){
			foreach($citation->SupplMeshList->SupplMeshName as $supplMeshName){
				$id = parent::getRes().md5($supplMeshName);
				parent::addRDF(
					parent::describeIndividual($id, $supplMeshName, parent::getVoc()."MeshHeading").
					parent::triplify($pmid_uri, parent::getVoc()."supplemental-mesh-heading", $id)
				);
			}
		}

		foreach($article->PublicationTypeList->PublicationType as $publicationType){
			$id = parent::getRes().md5($publicationType);
			$label = str_replace(" ","-",$publicationType);
			parent::addRDF(
				parent::triplify($pmid_uri, parent::getVoc()."publication-type", $id).
				parent::describeClass($id, $publicationType).
				parent::triplify($id,parent::getVoc()."x-mesh","mesh:".$publicationType['UI'])
			);
		}
		
		if(!empty($article->Abstract)){
			$id = parent::getRes().$pmid."_ABSTRACT";
			$label = "Abstract for PMID:$pmid";
			$abstract = $article->Abstract;
			parent::addRDF(
				parent::describeIndividual($id, $label, parent::getVoc()."Article-Abstract").
				parent::describeClass(parent::getVoc()."Article-Abstract","Article Abstract").
				parent::triplify($pmid_uri, "dc:abstract", $id).
				parent::triplifyString($id, parent::getVoc()."copyright", addslashes($abstract->CopyrightInformation))
			);

			$section = 0;
			$abstractText = "";
			foreach($abstract->AbstractText as $text){
				$abstractText .= " ".$text;
				if(!empty($text['Label']) && $text['Label'] !== "UNLABELLED"){
					$section_id = parent::getRes().$pmid."_ABSTRACT_SECTION_".++$section;
					parent::addRDF(
						parent::triplify($id, parent::getVoc()."section", $section_id).
						parent::triplifyString($section_id, parent::getVoc()."order", $section).
						parent::triplifyString($section_id, parent::getVoc()."nlm-section-type", $text['NlmCategory']).
						parent::triplifyString($section_id, parent::getVoc()."label", addslashes($text['Label'])).
						parent::triplifyString($section_id, parent::getVoc()."text", addslashes($text))
					);
				}
			}
			parent::addRDF(
				parent::triplifyString($id, parent::getVoc()."abstract-text", addslashes($abstractText))
			);
		}
		

		if(!empty($citation->OtherAbstract)){
			$i = 0;
			foreach($citation->OtherAbstract as $ab){
				$id = parent::getRes().$pmid."_oa_".++$i;

				parent::addRDF(
					parent::describeIndividual($id, "", parent::getVoc()."Article-Abstract").
					parent::describeClass(parent::getVoc()."Article-Abstract","Article Abstract").
					parent::triplify($pmid_uri, "dc:abstract", $id)
				);

				$abstractText = "";
				foreach($ab->AbstractText as $text){
					$abstractText .= " ".$text;
					if(!empty($text['Label']) && $text['Label'] !== "UNLABELLED"){
						parent::addRDF(
							parent::triplifyString($id, parent::getVoc()."abstract_".strtolower($text['Category']), $text)
						);
					}
				}
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."abstract-text", addslashes($abstractText))
				);
			}
		}

		$author_types = array("Investigator","Author","PersonalNameSubject");
		foreach($author_types AS $author_type) {
			$listname = $author_type."List";
			if(!empty($article->$listname->$author_type)){
				$i = 0;
				foreach($article->$listname->$author_type as $author){
					$id = parent::getRes().$pmid."_AUTHOR_".++$i;
					$author_label = $author->LastName.($author->Initials?", ".$author->Initials:"");

					parent::addRDF(
						parent::describeIndividual($id, $author_label, parent::getVoc().$author_type).
						parent::describeClass(parent::getVoc().$author_type,$author_type).
						parent::triplifyString($id, parent::getVoc()."list-position", $i).
						parent::triplify($pmid_uri, parent::getVoc().strtolower($author_type), $id).

						parent::triplifyString($id, parent::getVoc()."last-name", $author->LastName).
						parent::triplifyString($id, parent::getVoc()."fore-name", $author->ForeName).
						parent::triplifyString($id, parent::getVoc()."initials", $author->Initials).
						parent::triplifyString($id, parent::getVoc()."collective-name", $author->CollectiveName).
						parent::triplifyString($id, parent::getVoc()."suffix", $author->Suffix)
					);

					if($author->Affiliation) {
						$affilitation = parent::getRes().md5($author->Affilitation);
						parent::addRDF(
							parent::describeIndividual($affilitation, $author->Affilitation, parent::getVoc()."Organization").
							parent::describeClass(parent::getVoc()."Organization","Organization").
							parent::triplifyString($id, parent::getVoc()."affiliation", $affilitation)
						);
					}
					foreach($author->NameID as $authorNameId){
						if(!empty($authorNameId)){
							parent::addRDF(
								parent::triplifyString($id, parent::getVoc()."name-id", $author_name_id)
							);
						}
					}
				}
			}
		}

		if(!empty($article->ArticleDate)){
			$this->addDate($pmid_uri,"article-date",$article->ArticleDate);
		}
		
		foreach($article->Language as $language){
			parent::addRDF(
				parent::triplifyString($pmid_uri, "dc:language", $language)
			);
		}

		if(!empty($citation->KeywordList)){
			foreach($citation->KeywordList->Keyword as $keyword){
				parent::addRDF(
					parent::triplifyString($pmid_uri, parent::getVoc()."keyword", $keyword)
				);
			}
		}

		if(!empty($citation->otherID)) { // untested
			foreach($citation->OtherID as $otherID){
				if(!empty($otherID)){
					parent::addRDF(
						parent::triplifyString($pmid_uri, parent::getVoc()."other-id", $other_id).
						parent::triplifyString($pmid_uri, parent::getVoc()."other-id-source",$otherID['Source'])
					);
					if(strstr($other_id,"PMC")) {
						parent::addRDF(parent::triplify($pmid_uri,parent::getVoc()."x-pmc","pmc:".$other_id));
					}
				}
			}
		}

		if(!empty($article->DataBankList)){
			foreach($article->DataBankList->DataBank as $dataBank){
				parent::addRDF(
					parent::triplifyString($pmid_uri, parent::getVoc()."databank", $dataBank->DataBankName)
				);
				if($dataBank->AccessionNumberList !== NULL){
					foreach($dataBank->AccessionNumberList->AccessionNumber as $acc){
						parent::addRDF(
							parent::triplifyString($pmid_uri, parent::getVoc()."x-".strtolower($dataBank->dataBankName), $acc)
						);
					}
				}
			}
		}

		if(!empty($article->GrantList)){
			$i = 0;
			foreach($article->GrantList->Grant as $grant){
				$id = parent::getRes().$pmid."_GRANT_".++$i;
				$grant_label = "Grant ".$grant->GrantID." for ".parent::getNamespace().$pmid;
				parent::addRDF(
					parent::describeIndividual($id, $grant_label, parent::getVoc()."Grant").
					parent::describeClass(parent::getVoc()."Grant","Grant").
					parent::triplify($pmid_uri, parent::getVoc()."grant", $id).
					parent::triplifyString($id, parent::getVoc()."grant-identifier", $grant->GrantID).
					parent::triplifyString($id, parent::getVoc()."grant-acronym", $grant->Acronym).
					parent::triplifyString($id, parent::getVoc()."grant-agency", $grant->Agency).
					parent::triplifyString($id, parent::getVoc()."grant-country", $grant->Country)
				);
			}
		}

		if(!empty($citation->NumberOfReferences)){
			parent::addRDF(
				parent::triplifyString($pmid_uri, parent::getVoc()."number-of-references", $citation->NumberOfReferences)
			);
		}

		if(!empty($article->VernacularTitle)){
			parent::addRDF(
				parent::triplifyString($pmid_uri, parent::getVoc()."vernacular-title",addslashes($article->VernacularTitle))
			);
		}


		foreach($citation->CitationSubset as $citationSubset){
			if(!empty($citationSubset)){
				parent::addRDF(
					parent::triplifyString($pmid_uri, parent::getVoc()."citation-subset", $citationSubset)
				);
			}
		}

		if(!empty($citation->commentsCorrectionsList)){
			$i = 0;
			foreach($commentsCorrectionsList->CommentsCorrections as $commentCorrection){
				$id = parent::getRes().$pmid."_COMMENT_CORRECTION_".++$i;
				$ccRefType = $commentCorrection['RefType'];
				$ccPmid = $commentCorrection->PMID;//optional
				$ccNote = $commentCorrection->Note;//optional

				$cc_label = "Comment or correction .".$ccNumber." for ".parent::getNamespace().$pmid;

				parent::addRDF(
					parent::describeIndividual($id, $cc_label, parent::getVoc()."CommentCorrection").
					parent::describeClass(parent::getVoc()."CommentCorrection","CommentCorrection").
					parent::triplify($pmid_uri, parent::getVoc()."comment-correction",$id).
					parent::triplify($id, "rdf:type", parent::getVoc().$ccRefType).
					parent::triplifyString($id, parent::getVoc()."ref-source", $ref_source).
					parent::triplifyString($id, parent::getVoc()."note", $cc_note)
				);
			}
		}

		if(!empty($citation->generalNote)){
			parent::addRDF(
				parent::triplifyString($pmid_uri, parent::getVoc()."general-note", $general_note)
			);
		}

		$journal = $article->Journal;
		$journalId = parent::getRes().$pmid."_JOURNAL";
		$journal_label = "Journal for ".parent::getNamespace().$pmid;
		parent::addRDF(
			parent::describeIndividual($journalId, $journal_label, parent::getVoc()."Journal").
			parent::describeClass(parent::getVoc()."Journal","Journal").
			parent::triplify($pmid_uri, parent::getVoc()."journal", $journalId).
			parent::triplify($journalId, parent::getVoc()."x-issn","issn:".$journal->ISSN).
			parent::triplifyString($journalId, parent::getVoc()."journal-nlm-identifier", $citation->MedLineJournalInfo->NlmUniqueID).	
			parent::triplifyString($journalId, parent::getVoc()."journal-title", $journal->Title).
			parent::triplifyString($journalId, parent::getVoc()."journal-abbreviation", $journal->ISOAbbreviation).
			parent::triplifyString($journalId, parent::getVoc()."volume", $journal->JournalIssue->Volume).
			parent::triplifyString($journalId, parent::getVoc()."issue", $journal->JournalIssue->Issue).
			parent::triplifyString($journalId, parent::getVoc()."pages", "".$article->Pagination->MedlinePgn)
		);

		$journalPubDate = $journal->JournalIssue->PubDate;
		if(!empty($journalPubDate)){
			$journalYear = $journalPubDate->Year;
			$journalMonth = trim($journalPubDate->Month);//optional
			if($journalMonth and !is_numeric($journalMonth[0])) {
				$mo = array("jan","feb","mar","apr","may","jun","jul","aug","sep","oct","nov","dec");
				$journalMonth = str_pad(array_search(strtolower($journalMonth),$mo)+1, 2, "0",STR_PAD_LEFT);
			}
			$journalDay = trim($journalPubDate->Day);//optional
			if($journalDay) $journalDay = str_pad($journalDay,2,"0",STR_PAD_LEFT);
			parent::addRDF(
				parent::triplifyString($journalId, parent::getVoc()."publication-year", $journalYear).
				parent::triplifyString($journalId, parent::getVoc()."publication-month", $journalMonth).
				parent::triplifyString($journalId, parent::getVoc()."publication-day", $journalDay).
				parent::triplifyString($journalId, parent::getVoc()."publication-season",  $journalPubDate->Season).
				parent::triplifyString($journalId, parent::getVoc()."publication-date", $journalPubDate->MedlineDate)
			);

			if(!empty($journalYear) and !empty($journalMonth) and !empty($journalDay)){
				parent::addRDF(
					parent::triplifyString($journalId, parent::getVoc()."publication-date", "$journalYear-$journalMonth-$journalDay", "xsd:date")
				);
			}
		}
		
		foreach($citation->Article->ELocation as $eLocation){
			if(!empty($eLocation)){
				parent::addRDF(
					parent::triplifyString($pmid_uri, parent::getVoc()."elocation", $eLocation)
				);
			}
		}
		
		$this->writeRDFBufferToWriteFile();
	
	}
	function addDate($id,$field,$dateobj) 
	{
		if($dateobj == null) return FALSE;
		$year = $dateobj->Year;
		$month = $dateobj->Month;
		$day = $dateobj->Day;
		parent::addRDF(
			parent::triplifyString($id, parent::getVoc().$field, "$year-$month-$day", "xsd:date")
		);
	}

}
?>
