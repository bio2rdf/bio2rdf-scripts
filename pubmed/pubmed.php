<?php
/**
Copyright (C) 2011 Alison Callahan

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
 * @version 1.0
 * @author Alison Callahan
*/

require('../../php-lib/rdfapi.php');

class PubmedParser extends RDFFactory
{

	private $version = null;

	function __construct($argv) {
			parent::__construct();

			$this->SetDefaultNamespace("pubmed");

			// set and print application parameters
			$this->AddParameter('indir',true,null,'/path/to/indir/','directory to download into and parse from');
			$this->AddParameter('outdir',true,null,'/path/to/outdir/','directory to place rdfized files');
			$this->AddParameter('gzip',false,'true|false','true','gzip the output');
			$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
			$this->AddParameter('download_url',false,null,null);

			if($this->SetParameters($argv) == FALSE) {
				$this->PrintParameters($argv);
				exit;
			}
			if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
			if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
			if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));

			return TRUE;
	  }//constructor
	
	function Run() {
		$ldir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		
		//make sure directories end with slash
		if(substr($ldir, -1) !== "/"){
			$ldir = $ldir."/";
		}
		
		if(substr($odir, -1) !== "/"){
			$odir = $odir."/";
		}
		
		// generate the dataset release file
		$this->DeleteBio2RDFReleaseFiles($odir);
		$desc = $this->GetBio2RDFDatasetDescription(
			$this->GetNamespace(),
			"https://github.com/bio2rdf/bio2rdf-scripts/blob/master/pubmed/pubmed.php", 
			$this->GetBio2RDFDownloadURL($this->GetNamespace()),
			"http://www.ncbi.nlm.nih.gov/pubmed", 
			array("use"), 
			"http://www.nlm.nih.gov/databases/download.html",
			$this->GetParameterValue("download_url"),
			$this->version);

		$this->SetWriteFile($odir.$this->GetBio2RDFReleaseFile($this->GetNamespace()));
		$this->GetWriteFile()->Write($desc);
		$this->GetWriteFile()->Close();

		if ($lhandle = opendir($ldir)) {
			while (($lfilename = readdir($lhandle)) !== FALSE) {
				if ($lfilename != "." && $lfilename != "..") {

					$lfile = $ldir.$lfilename;
					$ofile = $odir.$lfilename.".ttl";
				
					if($this->GetParameterValue('gzip')) {
						$ofile .= '.gz';
						$gz = true;
					}
					
					$fp = gzopen($lfile, "r") or die("Could not open file ".$lfilename."!\n");
					
					$this->SetReadFile($lfile);
					$this->GetReadFile()->SetFilePointer($fp);
					$this->SetWriteFile($ofile, $gz);
					
					echo "processing $lfilename... ";
				
					$this->pubmed();
					$this->WriteRDFBufferToWriteFile();
					
					echo "done!\n";
					$this->GetWriteFile()->Close();
				}
				
			}
			closedir($lhandle);
		}
	}
	
	function pubmed(){
		$this->version = "2012";

		$citations = null;
		$ext = substr(strrchr($this->GetReadFile()->GetFileName(), '.'), 1);

		if($ext = "gz"){
			$citations = new SimpleXMLElement("compress.zlib://".$this->GetReadFile()->GetFileName(), NULL, TRUE);
		} elseif($ext="xml"){
			$citations = new SimpleXMLElement($this->GetReadFile()->GetFileName(), NULL, TRUE);
		}
		
		foreach($citations->MedlineCitation as $citation){
			
			$pmid = $citation->PMID;
			$dateCreated = trim($citation->DateCreated);
			$dateCompleted = trim($citation->DateCompleted);
			$dateRevised = trim($citation->DateRevised);
			$chemicals = $citation->ChemicalList;//optional
			$supplMeshList = $citation->SupplMeshList;//optional
			$commentsCorrectionsList = $citation->CommentCorrectionsList;//optional
			$geneSymbolList = $citation->GeneSymbolList; //optional; if present, children are <GeneSymbol>
			$meshHeadingList = $citation->MeshHeadingList; //optional
			$numberOfReferences = $citation->NumberOfReferences; //optional
			$personalNameSubjectList = $citation->PersonalNameSubjectList;//optional
			$keywordList = $citation->KeywordList;//optional
			$generalNote = $citation->GeneralNote;//optional
			$investigatorList = $citation->InvestigatorList;//optional

			$citationOwner = $citation['Owner'];
			$citationStatus = $citation['Status'];
			$citationVersionID = $citation['VersionID'];
			$citationVersionDate = $citation['VersionDate'];

			$publicationTypeList = $citation->Article->PublicationTypeList; //children are <PublicationType>
			$articleTitle = $citation->Article->ArticleTitle;
			$abstract = $citation->Article->Abstract;//optional
			$dataBankList = $citation->Article->DataBankList;//optional
			$grantList = $citation->Article->GrantList;//optional
			
			$affiliation = $citation->Article->Affiliation;//optional

			$vernacularTitle = $citation->Article->VernacularTitle; //optional
			$copyright = $citation->Article->Abstract->CopyrightInformation; //optional		
			$articleDate = $citation->Article->ArticleDate;//optional
			$authorList = $citation->Article->AuthorList;//optional
			
			$journal = $citation->Article->Journal;
			$pagination = trim($citation->Article->Pagination);
			$pubmodel = $citation->Article['PubModel'];

			$id = "pubmed:".$pmid;
			$this->AddRDF($this->QQuadL($id, "rdfs:label", "$articleTitle [pubmed:$id]"));
			$this->AddRDF($this->QQuad($id, "rdf:type", "pubmed_vocabulary:PubMedRecord"));
			$this->AddRDF($this->QQuad($id, "dc:identifier", "pubmed:$pmid"));
			$this->AddRDF($this->QQuad($id,"void:inDataset",$this->GetDatasetURI()));

			if(!empty($citationOwner)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:owner", "$citationOwner"));

			}

			if(!empty($citationStatus)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:status", "$citationStatus"));
			}

			if(!empty($citationVersionID)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:version_id", "$citationVersionID"));
			}

			if(!empty($citationVersionDate)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:version_date", "$citationVersionDate"));
			}

			$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:publication_model", "$pubmodel"));

			foreach($citation->OtherID as $otherID){
				if($otherID !== NULL && $otherID !== ""){
					$this->AddRDF($this->QQuadL($id,"pubmed_vocabulary:other_id","$otherID"));
					$this->AddRDF($this->QQuadL($id,"pubmed_vocabulary:other_id_source", $otherID['Source']));
				}
			}

			if(!empty($dateCreated)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:date_created", "$dateCreated"));
			}

			if(!empty($dateCompleted)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:date_completed", "$dateCompleted"));
			}

			if(!empty($dateRevised)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:date_revised", "$dateRevised"));
			}

			foreach($publicationTypeList->PublicationType as $publicationType){
				$publicationType = str_replace(" ", "", $publicationType);
				$this->AddRDF($this->QQuadL($id,"pubmed_vocabulary:publication_type","$publicationType"));
			}

			$this->AddRDF($this->QQuadL($id, "dc:title", "$articleTitle"));
			
			if(!empty($abstract)){
				$abstractIdentifier = "pubmed_resource:".$pmid."_ABSTRACT";
				$this->AddRDF($this->QQuad($id, "dc:abstract", $abstractIdentifier));
				$this->AddRDF($this->QQuad($abstractIdentifier, "rdf:type", "pubmed_vocabulary:ArticleAbstract"));
				$abstractText = "";
				foreach($abstract->AbstractText as $text){
					$abstractText .= " ".$text;
					if(!empty($text['Label']) && $text['Label'] !== "UNLABELLED"){
						$this->AddRDF($this->QQuadL($abstractIdentifier, "pubmed_vocabulary:abstract_".strtolower($text['NlmCategory']), "$text"));
					}
				}
				$this->AddRDF($this->QQuadL($abstractIdentifier, "pubmed_vocabulary:abstract_text", $abstractText));
			}

			$otherAbstractNumber = 0;
			foreach($citation->OtherAbstract as $otherAbstract){
				$otherAbstractNumber++;
				if(!empty($otherAbstract)){
					$otherAbstractIdentifier = "pubmed_resource:".$pmid."_OTHER_ABSTRACT_".$otherAbstractNumber;
					$this->AddRDF($this->QQuad($id, "dc:abstract", $otherAbstractIdentifier));
					$this->AddRDF($this->QQuad($id, "rdf:type", "pubmed_vocabulary:ArticleAbstract"));
					$otherAbstractText = "";
					foreach($otherAbstract->AbstractText as $otherText){
						$otherAbstractText .= " ".$otherText;
						if(!empty($otherText['Label']) && $otherText['Label'] !== "UNLABELLED"){
							$this->AddRDF($this->QQuadL($otherAbstractIdentifier, "pubmed_vocabulary:abstract_".strtolower($otherText['Category']), "$otherText"));
						}
					}
					$this->AddRDF($this->QQuadL($otherAbstractIdentifier, "pubmed_vocabulary:abstract_text", $otherAbstractText));
				}
			}
			
			foreach($citation->Article->Language as $language){
				if(!empty($language)){
					$this->AddRDF($this->QQuadL($id, "dc:language", "$language"));
				}
			}

			if(!empty($keywordList)){
				foreach($keywordList->Keyword as $keyword){
					$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:keyword", "$keyword"));
				}
			}

			if(!empty($geneSymbolList)){
				foreach($geneSymbolList->GeneSymbol as $geneSymbol){
					$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:gene_symbol", "hgnc:".$geneSymbol));
				}
			}

			if(!empty($dataBankList)){
				foreach($dataBankList->DataBank as $dataBank){
					$accessionNumberList = $dataBank->AccessionNumberList;
					$dataBankName = $dataBank->DataBankName;
					$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:databank", "$dataBankName"));
					if($accessionNumberList !== NULL){
						foreach($accessionNumberList->AccessionNumber as $acc){
							$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:x-".strtolower($dataBankName), "$acc"));
						}
					}
				}
			}

			if(!empty($grantList)){
				$grantNumber = 0;
				foreach($grantList->Grant as $grant){
					$grantNumber++;
					$grantIdentifier = "pubmed_resource:".$pmid."_GRANT_".$grantNumber;
					$grantId = $grant->GrantID;//optional
					$grantAcronym = $grant->Acronym;//optional
					$grantAgency = $grant->Agency;
					$grantCountry = $grant->Country;

					$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:grant", $grantIdentifier));
					$this->AddRDF($this->QQuad($grantIdentifier, "rdf:type", "pubmed_vocabulary:Grant"));
					
					if(!empty($grantId)){
						$this->AddRDF($this->QQuadL($grantIdentifier, "pubmed_vocabulary:grant_identifier", "$grantId"));
					}
					
					if(!empty($grantAcronym)){
						$this->AddRDF($this->QQuadL($grantIdentifier, "pubmed_vocabulary:grant_acronym", "$grantAcronym"));
					}

					$this->AddRDF($this->QQuadL($grantIdentifier, "pubmed_vocabulary:grant_agency", "$grantAgency"));
					$this->AddRDF($this->QQuadL($grantIdentifier, "pubmed_vocabulary:grant_country", "$grantCountry"));
				}
			}

			if(!empty($affiliation)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:affiliation", "$affiliation"));
			}

			if(!empty($numberOfReferences)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:number_of_references", "$numberOfReferences"));
			}

			if(!empty($vernacularTitle)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:vernacular_title", "$vernacularTitle"));
			}

			if(!empty($copyright)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:copyright_information", "$copyright"));
			}

			if(!empty($meshHeadingList)){
				$meshHeadingNumber = 0;
				foreach($meshHeadingList->MeshHeading as $meshHeading){
					$meshHeadingNumber++;
					$meshHeadingIdentifier = "pubmed_resource:".$pmid."_MESH_HEADING_".$meshHeadingNumber;
					$descriptorName = $meshHeading->DescriptorName;
					$qualifierName = $meshHeading->QualifierName;
					$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:mesh_heading", $meshHeadingIdentifier));
					$this->AddRDF($this->QQuad($meshHeadingIdentifier, "rdf:type", "pubmed_vocabulary:MeshHeading"));
					$this->AddRDF($this->QQuadL($meshHeadingIdentifier, "pubmed_vocabulary:mesh_descriptor_name", "$descriptorName"));
					$this->AddRDF($this->QQuadL($meshHeadingIdentifier, "rdfs:label", "$descriptorName"));

					if(!empty($qualifierName)){
						$this->AddRDF($this->QQuadL($meshHeadingIdentifier, "pubmed_vocabulary:mesh_qualifier_name", "$qualifierName"));
					}
				}
			}

			if(!empty($chemicals)){
				$chemicalNumber = 0;
				foreach($chemicals->Chemical as $chemical){
					$registryNumber = $chemical->RegistryNumber;
					$chemicalName = $chemical->NameOfSubstance;
					$chemicalNumber++;
					$chemicalIdentifier = "pubmed_resource:".$pmid."_CHEMICAL_".$chemicalNumber;
					
					$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:chemical", $chemicalIdentifier));
					$this->AddRDF($this->QQuad($chemicalIdentifier, "rdf:type", "pubmed_vocabulary:Chemical"));
					$this->AddRDF($this->QQuadL($chemicalIdentifier, "rdfs:label", "$chemicalName"));

					if($registryNumber !== "0"){
						$this->AddRDF($this->QQuadL($chemicalIdentifier,"pubmed_vocabulary:cas_registry_number", "$registryNumber"));
					}
					
				}
			}

			if(!empty($supplMeshList)){
				$supplMeshNumber = 0;
				foreach($supplMeshList->SupplMeshName as $supplMeshName){
					$supplMeshNumber++;
					$supplMeshIdentifier = "pubmed_resource:".$pmid."SUPPL_MESH_HEADING_".$supplMeshNumber;
					$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:suppl_mesh_heading", $supplMeshIdentifier));
					$this->AddRDF($this->QQuad($supplMeshIdentifier, "rdf:type", "pubmed_vocabulary:MeshHeading"));
					$this->AddRDF($this->QQuadL($supplMeshIdentifier, "pubmed_vocabulary:mesh_descriptor_name", "$supplMeshName"));
					$this->AddRDF($this->QQuadL($supplMeshIdentifier, "rdfs:label", "$supplMeshName"));
				}
			}

			foreach($citation->CitationSubset as $citationSubset){
				if(!empty($citationSubset)){
					$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:citation_subset", "$citationSubset"));
				}
			}

			if(!empty($commentsCorrectionsList)){
				$ccNumber = 0;
				foreach($commentsCorrectionsList->CommentsCorrections as $commentCorrection){
					$ccNumber++;
					$ccRefType = $commentCorrection['RefType'];
					$ccRefSource = $commentCorrection->RefSource;
					$ccPmid = $commentCorrection->PMID;//optional
					$ccNote = $commentCorrection->Note;//optional

					$ccIdentifier = "pubmed_resource:".$pmid."_COMMENT_CORRECTION_".$ccNumber;

					$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:comment_correction", $ccIdentifier));
					$this->AddRDF($this->QQuad($ccIdentifier, "rdf:type", "pubmed_vocabulary:".$ccRefType));
					$this->AddRDF($this->QQuad($ccIdentifier, "rdf:type", "pubmed_vocabulary:CommentCorrection"));
					$this->AddRDF($this->QQuadL($ccIdentifier, "pubmed_vocabulary:ref_source", "$ccRefSource"));
					if(!empty($ccPmid)){
						$this->AddRDF($this->QQuad($ccIdentifier, "pubmed_vocabulary:pmid", "pubmed:".$pmid));
					}

					if(!empty($ccNote)){
						$this->AddRDF($this->QQuadL($ccIdentifier, "pubmed_vocabulary:note", "$ccNote"));
					}	
				}
			}

			if(!empty($generalNote)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:general_note", "$generalNote"));
			}

			if(!empty($articleDate)){
				$year = $articleDate->Year;
				$month = $articleDate->Month;
				$day = $articleDate->Day;
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:article_date", "$year-$month-$day", null, "xsd:date"));
			}

			if(!empty($authorList)){
				$authorNumber = 0;
				foreach($authorList->Author as $author){
					$authorNumber++;
					$authorLastName = $author->LastName;
					$authorForeName = $author->ForeName;//optional
					$authorInitials = $author->Initials;//optional
					$authorCollectiveName = $author->CollectiveName;//optional

					$authorIdentifier = "pubmed_resource:".$pmid."_AUTHOR_".$authorNumber;
					$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:author", $authorIdentifier));
					$this->AddRDF($this->QQuad($authorIdentifier, "rdf:type", "pubmed_vocabulary:Author"));
					$this->AddRDF($this->QQuadL($authorIdentifier, "pubmed_vocabulary:last_name", "$authorLastName"));
					if(!empty($authorForeName)){
						$this->AddRDF($this->QQuadL($authorIdentifier, "pubmed_vocabulary:fore_name", "$authorForeName"));
					}

					if(!empty($authorInitials)){
						$this->AddRDF($this->QQuadL($authorIdentifier, "pubmed_vocabulary:initials", "$authorInitials"));
					}

					if(!empty($authorCollectiveName)){
						$this->AddRDF($this->QQuadL($authorIdentifier, "pubmed_vocabulary:collective_name", "$authorCollectiveName"));
					}

					foreach($author->NameID as $authorNameId){
						if(!empty($authorNameId)){
							$this->AddRDF($this->QQuadL($authorIdentifier, "pubmed_vocabulary:name_id", "$authorNameId"));
						}
					}
				}
			}

			foreach($citation->SpaceFlightMission as $spaceFlightMission){
				if(!empty($spaceFlightMission)){
					$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:space_flight_mission", "$spaceFlightMission"));
				}
			}

			if(!empty($investigatorList)){
				$investigatorNumber = 0;
				foreach($investigatorList->Investigator as $investigator){
					$investigatorNumber++;
					$iLastName = $investigator->LastName;
					$iForeName = $investigator->ForeName;//optional
					$iInitials = $investigator->Initials;//optional
					$iAffiliation = $investigator->Affiliation;//optional

					$iIdentifier = "pubmed_resource:".$pmid."_INVESTIGATOR_".$investigatorNumber;

					$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:investigator", $iIdentifier));
					$this->AddRDF($this->QQuad($iIdentifier, "rdf:type", "pubmed_vocabulary:Investigator"));
					$this->AddRDF($this->QQuadL($iIdentifier, "pubmed_vocabulary:last_name", "$iLastName"));
					
					if(!empty($iForeName)){
						$this->AddRDF($this->QQuadL($iIdentifier, "pubmed_vocabulary:fore_name", "$iForeName"));
					}

					if(!empty($iInitials)){
						$this->AddRDF($this->QQuadL($iIdentifier, "pubmed_vocabulary:affiliation", "$iAffiliation"));
					}

					foreach($investigator->NameID as $iNameId){
						if(!empty($iNameId)){
							$this->AddRDF($this->QQuadL($iIdentifier, "pubmed_vocabulary:name_id", "$iNameId"));
						}	
					}
				}
			}

			if(!empty($personalNameSubjectList)){
				$pnsNumber = 0;
				foreach($personalNameSubjectList->PersonalNameSubject as $personalNameSubject){
					$pnsNumber++;
					$pnsIdentifier = "pubmed_resource:".$pmid."_PERSONAL_NAME_SUBJECT_".$pnsNumber;

					$pnsLastName = $personalNameSubject->LastName;
					$pnsForeName = $personalNameSubject->ForeName;//optional
					$pnsInitials = $personalNameSubject->Initials;//optional
					$pnsSuffix = $personalNameSubject->Suffix;//optional

					$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:personal_name_subject", $pnsIdentifier));
					$this->AddRDF($this->QQuadL($pnsIdentifier, "pubmed_vocabulary:last_name", "$pnsLastName"));
					
					if(!empty($pnsForeName)){
						$this->AddRDF($this->QQuadL($pnsIdentifier, "pubmed_vocabulary:fore_name", "$pnsForeName"));
					}

					if(!empty($pnsInitials)){
						$this->AddRDF($this->QQuadL($pnsIdentifier, "pubmed_vocabulary:initials", "$pnsInitials"));
					}

					if(!empty($pnsSuffix)){
						$this->AddRDF($this->QQuadL($pnsIdentifier, "pubmed_vocabulary:suffix", "$pnsSuffix"));
					}

				}
			}

			$journalISSN = $journal->ISSN;//optional
			$journalIssue = $journal->JournalIssue;
			$journalTitle = $journal->Title;//optional
			$journalAbbrev = $journal->ISOAbbreviation;//optional
			$journalVolume = $journalIssue->Volume;//optional
			$journalIssueIssue = $journalIssue->Issue;//optional
			$journalPubDate = trim($journalIssue->PubDate);
			$journalNlmID = $citation->MedLineJournalInfo->NlmUniqueID;//optional

			$journalId = "pubmed_resource:".$pmid."_JOURNAL";
			$this->AddRDF($this->QQuad($id, "pubmed_vocabulary:journal", $journalId));
			$this->AddRDF($this->QQuad($journalId, "rdf:type", "pubmed_vocabulary:Journal"));
			if(!empty($journalNlmID)){
				$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:journal_nlm_identifier", "$journalNlmID"));
			}

			if(!empty($journalPubDate)){
				$journalYear = $journalPubDate->Year;
				$journalMonth = $journalPubDate->Month;//optional
				$journalDay = $journalPubDate->Day;//optional
				if(!empty($journalYear)){
					if(!empty($journalMonth)){
						if(!empty($journalDay)){
							$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:publication_date", "$journalYear-$journalMonth-$journalDay", null, "xsd:date"));
						} else {
							$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:publication_year", "$journalYear"));
							$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:publication_month", "$journalMonth"));
						}
					} else {
						$journalSeason = $journalPubDate->Season;
						if(!empty($journalSeason)){
							$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:publication_season", "$journalSeason"));
						}
						
					}
				} else {
					$journalMedlineDate = $journalPubDate->MedlineDate;
					if(!empty($journalMedlineDate)){
						$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:publication_date", "$journalMedlineDate"));
					}
				}
				
			}

			if(!empty($journalTitle)){
				$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:journal_title", "$journalTitle"));
			}

			if(!empty($journalAbbrev)){
				$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:journal_abbreviation", "$journalAbbrev"));
			}

			if(!empty($journalVolume)){
				$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:journal_volume", "$journalVolume"));
			}

			if(!empty($journalIssueIssue)){
				$this->AddRDF($this->QQuadL($journalId, "pubmed_vocabulary:journal_issue", "$journalIssueIssue"));
			}

			if(!empty($pagination)){
				$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:pagination", "$pagination"));
			}

			foreach($citation->Article->ELocation as $eLocation){
				if(!empty($eLocation)){
					$this->AddRDF($this->QQuadL($id, "pubmed_vocabulary:elocation", "$eLocation"));
				}
			}
		}
	}
}

$parser = new PubmedParser($argv);
$parser->Run();


?>
