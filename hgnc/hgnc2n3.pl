###############################################################################
#Copyright (C) 2011 Alison Callahan, Marc-Alexandre Nolin, Francois Belleau
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

#!/usr/bin/perl

use strict;
use Digest::MD5;
use utf8;
# dc:title      hgnc2n3v2.pl
#
# You can contact the Bio2RDF team at bio2rdf@gmail.com
# Visit our blog at http://bio2rdf.blogspot.com/
# Visit the main application at http://bio2rdf.org
# This open source project is hosted at http://sourceforge.net/projects/bio2rdf/
# -------------------------------------------------------------------------------

#  1 HGNC ID
#  2 Approved Symbol
#  3 Approved Name
#  4 Status
#  5 Locus Type
#  6 Locus Group
#  7 Previous Symbols
#  8 Previous Names
#  9 Aliases
# 10 Name Aliases
# 11 Chromosome
# 12 Date Approved
# 13 Date Modified
# 14 Date Symbol Changed
# 15 Date Name Changed
# 16 Accession Numbers
# 17 Enzyme IDs
# 18 Entrez Gene ID
# 19 Ensembl Gene ID
# 20 Mouse Genome Database ID
# 21 Specialist Database Links
# 22 Specialist Database IDs
# 23 Pubmed IDs
# 24 RefSeq IDs
# 25 Gene Family Tag
# 26 Record Type
# 27 Primary IDs
# 28 Secondary IDs
# 29 CCDS IDs
# 30 VEGA IDs
# 31 Locus Specific Databases
# 32 GDB ID (mapped data)
# 33 Entrez Gene ID (mapped data supplied by NCBI)
# 34 OMIM ID (mapped data supplied by NCBI)
# 35 RefSeq (mapped data supplied by NCBI)
# 36 UniProt ID (mapped data supplied by UniProt)
# 37 Ensembl ID (mapped data supplied by Ensembl)
# 38 UCSC ID (mapped data supplied by UCSC)
# 39 Mouse Genome Database ID (mapped data supplied by MGI)
# 40 Rat Genome Database ID (mapped data supplied by RGD)

my $file = shift;
our $graph = "";
our $identifier = "";
our $ApprovedSymbol = "";
our $ApprovedName = "";
our $lcHGNCID = "";
our $base = "http://bio2rdf.org/hgnc";
our $vocabulary = "http://bio2rdf.org/hgnc_vocabulary";
our $bio2rdf = "http://bio2rdf.org";
our $bio2rdf_res = "http://bio2rdf.org/bio2rdf_resource";
our $resource = "http://bio2rdf.org/hgnc_resource";
our $rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns";
our $rdfs = "http://www.w3.org/2000/01/rdf-schema";
our $dc = "http://purl.org/dc/terms";
our $foaf = "http://xmlns.com/foaf/0.1";
our $owl = "http://www.w3.org/2002/07/owl";

my $uniqueID = "";

open(INPUT, "<$file") || die "File $file not found:$!\n";

# remove header line
my $line = <INPUT>;

while ($line = <INPUT>) {

	my @lines = split(/\t/,$line);

	my $HGNCID = $lines[0];
	$lcHGNCID = lc($HGNCID);
	(my $temp, $identifier) = split(/:/,$HGNCID);
	$graph = "$bio2rdf/hgnc_record:$identifier";
	$ApprovedSymbol = $lines[1];
	$ApprovedSymbol =~ s/~withdrawn//g;
	$ApprovedName = $lines[2];
	my $Status = $lines[3];
	my $LocusType = $lines[4];
	my $LocusGroup = $lines[5];
	my $PreviousSymbols = $lines[6];
	my $PreviousNames = $lines[7];
	my $Aliases = $lines[8];
	my $NameAliases = $lines[9];
	my $Chromosome = $lines[10];
	my $DateApproved = $lines[11];
	my $DateModified = $lines[12];
	my $DateSymbolChanged = $lines[13];
	my $DateNameChanged = $lines[14];
	my $AccessionNumbers = $lines[15];
	my $EnzymeIDs = $lines[16];
	my $EntrezGeneID = $lines[17];
	my $EnsemblGeneID = $lines[18];
	my $MouseGenomeDatabaseID = $lines[19];
	my $SpecialistDatabaseLinks = $lines[20];
	my $SpecialistDatabaseIDs = $lines[21];
	my $PubmedIDs = $lines[22];
	my $RefSeqIDs = $lines[23];
	my $GeneFamilyTag = $lines[24];
	my $RecordType = $lines[25];
	my $PrimaryIDs = $lines[26];
	my $SecondaryIDs = $lines [27];
	my $CCDSIDs = $lines[28];
	my $VEGAIDs = $lines[29];
	my $LocusSpecificDatabases = $lines[30];
	my $GDBID_mappeddata = $lines[31];
	my $EntrezGeneID_mappeddatasuppliedbyNCBI = $lines[32];
	my $OMIMID_mappeddatasuppliedbyNCBI = $lines[33];
	my $RefSeq_mappeddatasuppliedbyNCBI = $lines[34];
	my $UniProtID_mappeddatasuppliedbyUniProt = $lines[35];
	my $EnsemblID_mappeddatasuppliedbyEnsembl = $lines[36];
	my $UCSCID_mappeddatasuppliedbyUCSC = $lines[37];
	my $MouseGenomeDatabaseID_mappeddatasuppliedbyMGI = $lines[38];
	my $RatGenomeDatabaseID_mappeddatasuppliedbyRGD = $lines[39];


	printN3("$bio2rdf/symbol:$ApprovedSymbol", "$rdf#type", "$bio2rdf/symbol_vocabulary:Symbol", 0, 0);
	printN3("$bio2rdf/symbol:$ApprovedSymbol", "$dc/identifier", "symbol:$ApprovedSymbol", 1, "^^xsd:string");
	printN3("$bio2rdf/symbol:$ApprovedSymbol", "$rdfs#label", "$ApprovedName [symbol:$ApprovedSymbol]", 1, "^^xsd:string");
	printN3("$bio2rdf/symbol:$ApprovedSymbol", "$bio2rdf/symbol_vocabulary:approvedSymbol", $ApprovedSymbol, 1, "^^xsd:string");
	printN3("$bio2rdf/symbol:$ApprovedSymbol", "$owl#sameAs", "$base:$identifier", 0, 0);

	printN3("$bio2rdf/hugo:$ApprovedSymbol", "$rdf#type", "$bio2rdf/hugo_vocabulary:Symbol", 0, 0);
	printN3("$bio2rdf/hugo:$ApprovedSymbol", "$dc/identifier", "hugo:$ApprovedSymbol", 1, "^^xsd:string");
	printN3("$bio2rdf/hugo:$ApprovedSymbol", "$rdfs#label", "$ApprovedName [hugo:$ApprovedSymbol]", 1, "^^xsd:string");
	printN3("$bio2rdf/hugo:$ApprovedSymbol", "$bio2rdf/hugo_vocabulary:approvedSymbol", $ApprovedSymbol, 1, "^^xsd:string");
	printN3("$bio2rdf/hugo:$ApprovedSymbol", "$owl#sameAs", "$base:$identifier", 0, 0);

	printN3("$base:$identifier", "$rdf#type", "$vocabulary:Gene", 0, 0);
	printN3("$base:$identifier", "$dc/identifier", $lcHGNCID, 1, "^^xsd:string");
	printN3("$base:$identifier", "$dc/title", "$ApprovedName ($ApprovedSymbol)", 1, "^^xsd:string");
	printN3("$base:$identifier", "$rdfs#label", "$ApprovedName ($ApprovedSymbol) [$lcHGNCID]", 1, "^^xsd:string");
	printN3("$base:$identifier", "$vocabulary:url", "http://www.genenames.org/data/hgnc_data.php?hgnc_id=$identifier", 1, "^^xsd:string");
	printN3("$base:$identifier", "$vocabulary:approvedSymbol", $ApprovedSymbol, 1, "^^xsd:string");

	if($Status !~ /^$/){printN3("$base:$identifier", "$vocabulary:status", $Status, 1, "^^xsd:string");}
	if($LocusType !~ /^$/){printN3("$base:$identifier", "$vocabulary:locusType", $LocusType, 1, "^^xsd:string");}
	
	if($PreviousSymbols !~ /^$/){
		$uniqueID = generateUniqueURI($PreviousSymbols);
		printN3("$base:$identifier", "$vocabulary:previousSymbol", "$resource:$identifier-$uniqueID", 0, 0);
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:PreviousSymbol", 0, 0);
		PreviousSymbols("$resource:$identifier-$uniqueID", $PreviousSymbols);
	}

	if($PreviousNames !~ /^$/){
		$uniqueID = generateUniqueURI($PreviousNames);
		printN3("$base:$identifier", "$vocabulary:previousName", "$resource:$identifier-$uniqueID", 0, 0);
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:PreviousName", 0, 0);
		PreviousNames("$resource:$identifier-$uniqueID", $PreviousNames);
	}

	if($Aliases !~ /^$/){
		$uniqueID = generateUniqueURI($Aliases);
		printN3("$base:$identifier", "$vocabulary:alias", "$resource:$identifier-$uniqueID", 0, 0);
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:Alias", 0, 0);
		Aliases("$resource:$identifier-$uniqueID", $Aliases);
	}

	if($NameAliases !~ /^$/){
		$uniqueID = generateUniqueURI($NameAliases);
		printN3("$base:$identifier", "$vocabulary:nameAlias", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:NameAlias", 0, 0);
		NameAliases("$resource:$identifier-$uniqueID", $NameAliases);
	}

	if($Chromosome !~ /^$/){printN3("$base:$identifier", "$vocabulary:chromosome", $Chromosome, 1, "^^xsd:string");}
	if($DateApproved !~ /^$/){printN3("$base:$identifier", "$dc/dateAccepted", $DateApproved, 1, "^^xsd:date");}
	if($DateModified !~ /^$/){printN3("$base:$identifier", "$dc/modified", $DateModified, 1, "^^xsd:date");}
	if($DateSymbolChanged !~ /^$/){printN3("$base:$identifier", "$vocabulary:dateSymbolChanged", $DateSymbolChanged, 1, "^^xsd:date");}
	if($DateNameChanged !~ /^$/){printN3("$base:$identifier", "$vocabulary:dateNameChanged", $DateNameChanged, 1, "^^xsd:date");}

	if($AccessionNumbers !~ /^$/){
		$uniqueID = generateUniqueURI($AccessionNumbers);
		printN3("$base:$identifier", "$vocabulary:accessionNumber", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:AccessionNumber", 0, 0);
		AccessionNumbers("$resource:$identifier-$uniqueID", $AccessionNumbers);
	}

	if($EnzymeIDs !~ /^$/){
		$uniqueID = generateUniqueURI($EnzymeIDs);
		printN3("$base:$identifier", "$vocabulary:enzymeID", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:EnzymeID", 0, 0);
		EnzymeIDs("$resource:$identifier-$uniqueID", $EnzymeIDs);
	}

	if($EntrezGeneID !~ /^$/){
		printN3("$base:$identifier", "$vocabulary:entrezGeneID", $EntrezGeneID, 1, "^^xsd:integer" );
		printN3("$base:$identifier", "$vocabulary:xEntrezGene", "$bio2rdf/geneid:$EntrezGeneID", 0, 0 );
		xRef("geneid:$EntrezGeneID");
	}

	if($EnsemblGeneID !~ /^$/){
		printN3("$base:$identifier", "$vocabulary:ensemblGeneID", $EnsemblGeneID, 1, "^^xsd:string" );
		printN3("$base:$identifier", "$vocabulary:xEnsemblGene", "$bio2rdf/ensembl:$EnsemblGeneID", 0, 0 );
		xRef("ensemble:$EnsemblGeneID");
	}

	if($MouseGenomeDatabaseID !~ /^$/){
		(my $mgi_temp, my $mgi) = split(/:/, $MouseGenomeDatabaseID);
		printN3("$base:$identifier", "$vocabulary:mouseGenomeDatabaseID", $mgi, 1, "^^xsd:integer" );
		printN3("$base:$identifier", "$vocabulary:xMouseGenomeDatabase", "$bio2rdf/mgi:$mgi", 0, 0 );
		xRef("mgi:$mgi");
	}

	if($SpecialistDatabaseLinks =~ /http/){printN3("$base:$identifier", "$vocabulary:specialistDatabaseLinks", $SpecialistDatabaseLinks, 1, "^^xsd:string" );}

	if($SpecialistDatabaseIDs !~ /^$/){
		$uniqueID = generateUniqueURI($SpecialistDatabaseIDs);
		printN3("$base:$identifier", "$vocabulary:specialistDatabaseID", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:SpecialistDatabaseID", 0, 0);
		SpecialistDatabaseIDs("$resource:$identifier-$uniqueID", $SpecialistDatabaseIDs);
	}

	if($PubmedIDs !~ /^$/){
		$uniqueID = generateUniqueURI($PubmedIDs);
		printN3("$base:$identifier", "$vocabulary:pubmedID", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:PubmedID", 0, 0);
		PubmedIDs("$resource:$identifier-$uniqueID", $PubmedIDs);
	}

	if($RefSeqIDs !~ /^$/){
		$uniqueID = generateUniqueURI($RefSeqIDs);
		printN3("$base:$identifier", "$vocabulary:refSeqID", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:RefSeqID", 0, 0);
		RefSeqIDs("$resource:$identifier-$uniqueID", $RefSeqIDs);
	}

	if($GeneFamilyTag !~ /^$/){
		$uniqueID = generateUniqueURI($GeneFamilyTag);
		printN3("$base:$identifier", "$vocabulary:geneFamilyTag", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:GeneFamilyTag", 0, 0);
		GeneFamilyTag("$resource:$identifier-$uniqueID", $GeneFamilyTag);
	}

	if($RecordType !~ /^$/){printN3("$base:$identifier", "$vocabulary:recordType", $RecordType, 1, "^^xsd:string" );}
	if($PrimaryIDs !~ /^$/ && $PrimaryIDs !~ /^-$/){
		printN3("$base:$identifier", "$vocabulary:primaryIDs", $PrimaryIDs, 1, "^^xsd:string" );
		my @PI = split(/, /, $PrimaryIDs);
		foreach(@PI){
			printN3("$base:$identifier", "$vocabulary:xPrimaryID", "$base:$_", 0, 0 );
			xRef("hgnc:$_");
		}
	}
	if($SecondaryIDs !~ /^$/ && $SecondaryIDs !~ /^-$/){
		printN3("$base:$identifier", "$vocabulary:secondaryIDs", $SecondaryIDs, 1, "^^xsd:string" );
		my @PI = split(/, /, $SecondaryIDs);
		foreach(@PI){
			printN3("$base:$identifier", "$vocabulary:xSecondaryID", "$base:$_", 0, 0 );
			xRef("hgnc:$_");
		}
	}

	if($CCDSIDs !~ /^$/){
		$uniqueID = generateUniqueURI($CCDSIDs);
		printN3("$base:$identifier", "$vocabulary:CCDSID", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:CCDSID", 0, 0);
		CCDSIDs("$resource:$identifier-$uniqueID", $CCDSIDs);
	}

	if($VEGAIDs !~ /^$/){
		$uniqueID = generateUniqueURI($VEGAIDs);
		printN3("$base:$identifier", "$vocabulary:VEGAID", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:VEGAID", 0, 0);
		VEGAIDs("$resource:$identifier-$uniqueID", $VEGAIDs);
	}

	if($LocusSpecificDatabases !~ /^$/ && $LocusSpecificDatabases !~ /<strong><\/strong>/){
		printN3("$base:$identifier", "$vocabulary:LocusSpecificDatabases", $LocusSpecificDatabases, 1, "^^xsd:string" );
	}

#	if($GDBID_mappeddata !~ /^$/){
#		$uniqueID = generateUniqueURI($GDBID_mappeddata);
#		printN3("$resource:$identifier", "$vocabulary:GDBID_mappeddata", "$resource:$identifier-$uniqueID", 0, 0 );
#		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:GDBID_mappeddata", 0, 0);
#		GDBID_mappeddata("$base:$identifier-$uniqueID", $GDBID_mappeddata);
#	}

	if($EntrezGeneID_mappeddatasuppliedbyNCBI !~ /^$/){
		$uniqueID = generateUniqueURI($EntrezGeneID_mappeddatasuppliedbyNCBI);
		printN3("$base:$identifier", "$vocabulary:EntrezGeneID_mappeddatasuppliedbyNCBI", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:EntrezGeneID_mappeddatasuppliedbyNCBI", 0, 0);
		EntrezGeneID_mappeddatasuppliedbyNCBI("$resource:$identifier-$uniqueID", $EntrezGeneID_mappeddatasuppliedbyNCBI);
	}
	if($OMIMID_mappeddatasuppliedbyNCBI !~ /^$/){
		$uniqueID = generateUniqueURI($OMIMID_mappeddatasuppliedbyNCBI);
		printN3("$base:$identifier", "$vocabulary:OMIMID_mappeddatasuppliedbyNCBI", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:OMIMID_mappeddatasuppliedbyNCBI", 0, 0);
		OMIMID_mappeddatasuppliedbyNCBI("$resource:$identifier-$uniqueID", $OMIMID_mappeddatasuppliedbyNCBI);
	}
	if($RefSeq_mappeddatasuppliedbyNCBI !~ /^$/){
		$uniqueID = generateUniqueURI($RefSeq_mappeddatasuppliedbyNCBI);
		printN3("$base:$identifier", "$vocabulary:RefSeq_mappeddatasuppliedbyNCBI", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:RefSeq_mappeddatasuppliedbyNCBI", 0, 0);
		RefSeq_mappeddatasuppliedbyNCBI("$resource:$identifier-$uniqueID", $RefSeq_mappeddatasuppliedbyNCBI);
	}
	if($UniProtID_mappeddatasuppliedbyUniProt !~ /^$/){
		$uniqueID = generateUniqueURI($UniProtID_mappeddatasuppliedbyUniProt);
		printN3("$base:$identifier", "$vocabulary:UniProtID_mappeddatasuppliedbyUniProt", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:UniProtID_mappeddatasuppliedbyUniProt", 0, 0);
		UniProtID_mappeddatasuppliedbyUniProt("$resource:$identifier-$uniqueID", $VEGAIDs);
	}
	if($EnsemblID_mappeddatasuppliedbyEnsembl !~ /^$/){
		$uniqueID = generateUniqueURI($EnsemblID_mappeddatasuppliedbyEnsembl);
		printN3("$base:$identifier", "$vocabulary:EnsemblID_mappeddatasuppliedbyEnsembl", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:EnsemblID_mappeddatasuppliedbyEnsembl", 0, 0);
		EnsemblID_mappeddatasuppliedbyEnsembl("$resource:$identifier-$uniqueID", $EnsemblID_mappeddatasuppliedbyEnsembl);
	}
	if($UCSCID_mappeddatasuppliedbyUCSC !~ /^$/){
		$uniqueID = generateUniqueURI($UCSCID_mappeddatasuppliedbyUCSC);
		printN3("$base:$identifier", "$vocabulary:UCSCID_mappeddatasuppliedbyUCSC", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:UCSCID_mappeddatasuppliedbyUCSC", 0, 0);
		UCSCID_mappeddatasuppliedbyUCSC("$resource:$identifier-$uniqueID", $UCSCID_mappeddatasuppliedbyUCSC);
	}
	if($MouseGenomeDatabaseID_mappeddatasuppliedbyMGI !~ /^$/){
		$uniqueID = generateUniqueURI($MouseGenomeDatabaseID_mappeddatasuppliedbyMGI);
		printN3("$base:$identifier", "$vocabulary:MouseGenomeDatabaseID_mappeddatasuppliedbyMGI", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:MouseGenomeDatabaseID_mappeddatasuppliedbyMGI", 0, 0);
		MouseGenomeDatabaseID_mappeddatasuppliedbyMGI("$resource:$identifier-$uniqueID", $MouseGenomeDatabaseID_mappeddatasuppliedbyMGI);
	}
	if($RatGenomeDatabaseID_mappeddatasuppliedbyRGD !~ /^$/){
		$uniqueID = generateUniqueURI($RatGenomeDatabaseID_mappeddatasuppliedbyRGD);
		printN3("$base:$identifier", "$vocabulary:RatGenomeDatabaseID_mappeddatasuppliedbyRGD", "$resource:$identifier-$uniqueID", 0, 0 );
		printN3("$resource:$identifier-$uniqueID", "$rdf#type", "$vocabulary:RatGenomeDatabaseID_mappeddatasuppliedbyRGD", 0, 0);
		RatGenomeDatabaseID_mappeddatasuppliedbyRGD("$resource:$identifier-$uniqueID", $RatGenomeDatabaseID_mappeddatasuppliedbyRGD);
	}



}

close(ENTREE);

exit;

sub PreviousSymbols{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:symbol", $_, 1, "^^xsd:string");

			printN3("$bio2rdf/symbol:$_", "$rdf#type", "$bio2rdf/symbol_vocabulary:Symbol", 0, 0);
			printN3("$bio2rdf/symbol:$_", "$dc/identifier", "symbol:$_", 1, "^^xsd:string");
			printN3("$bio2rdf/symbol:$_", "$rdfs#label", "Previous symbol for $ApprovedSymbol [symbol:$_]", 1, "^^xsd:string");
			printN3("$bio2rdf/symbol:$_", "$owl#sameAs", "$base:$identifier", 0, 0);
			xRef("symbol:$_");

			printN3("$bio2rdf/hugo:$_", "$rdf#type", "$bio2rdf/hugo_vocabulary:Symbol", 0, 0);
			printN3("$bio2rdf/hugo:$_", "$dc/identifier", "hugo:$content", 1, "^^xsd:string");
			printN3("$bio2rdf/hugo:$_", "$rdfs#label", "Previous symbol for $ApprovedSymbol [hugo:$_]", 1, "^^xsd:string");
			printN3("$bio2rdf/hugo:$_", "$owl#sameAs", "$base:$identifier", 0, 0);
			xRef("hugo:$_");
		}
	}
	else{
		printN3("$subject", "$vocabulary:symbol", $content, 1, "^^xsd:string");

		printN3("$bio2rdf/symbol:$content", "$rdf#type", "$bio2rdf/symbol_vocabulary:Symbol", 0, 0);
		printN3("$bio2rdf/symbol:$content", "$dc/identifier", "symbol:$content", 1, "^^xsd:string");
		printN3("$bio2rdf/symbol:$content", "$rdfs#label", "Previous symbol for $ApprovedSymbol [symbol:$content]", 1, "^^xsd:string");
		printN3("$bio2rdf/symbol:$content", "$owl#sameAs", "$base:$identifier", 0, 0);
		xRef("symbol:$content");

		printN3("$bio2rdf/hugo:$content", "$rdf#type", "$bio2rdf/hugo_vocabulary:Symbol", 0, 0);
		printN3("$bio2rdf/hugo:$content", "$dc/identifier", "hugo:$content", 1, "^^xsd:string");
		printN3("$bio2rdf/hugo:$content", "$rdfs#label", "Previous symbol for $ApprovedSymbol [hugo:$content]", 1, "^^xsd:string");
		printN3("$bio2rdf/hugo:$content", "$owl#sameAs", "$base:$identifier", 0, 0);
		xRef("hugo:$content");
	}
}

sub PreviousNames{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:previousName", $_, 1, "^^xsd:string");
		}
	}
	else{
			printN3("$subject", "$vocabulary:previousName", $content, 1, "^^xsd:string");
	}
}

sub Aliases{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:alias", $_, 1, "^^xsd:string");

			printN3("$bio2rdf/symbol:$_", "$rdf#type", "$bio2rdf/symbol_vocabulary:Symbol", 0, 0);
			printN3("$bio2rdf/symbol:$_", "$dc/identifier", "symbol:$_", 1, "^^xsd:string");
			printN3("$bio2rdf/symbol:$_", "$rdfs#label", "$_ is an alias symbol for $ApprovedSymbol [symbol:$_]", 1, "^^xsd:string");
			printN3("$bio2rdf/symbol:$_", "$owl#sameAs", "$base:$identifier", 0, 0);
			xRef("symbol:$_");

			printN3("$bio2rdf/hugo:$_", "$rdf#type", "$bio2rdf/hugo_vocabulary:Symbol", 0, 0);
			printN3("$bio2rdf/hugo:$_", "$dc/identifier", "hugo:$content", 1, "^^xsd:string");
			printN3("$bio2rdf/hugo:$_", "$rdfs#label", "$_ is an alias symbol for $ApprovedSymbol [hugo:$_]", 1, "^^xsd:string");
			printN3("$bio2rdf/hugo:$_", "$owl#sameAs", "$base:$identifier", 0, 0);
			xRef("hugo:$_");
		}
	}
	else{
		printN3("$subject", "$vocabulary:alias", $content, 1, "^^xsd:string");

		printN3("$bio2rdf/symbol:$content", "$rdf#type", "$bio2rdf/symbol_vocabulary:Symbol", 0, 0);
		printN3("$bio2rdf/symbol:$content", "$dc/identifier", "symbol:$content", 1, "^^xsd:string");
		printN3("$bio2rdf/symbol:$content", "$rdfs#label", "$content is an alias symbol for $ApprovedSymbol [symbol:$content]", 1, "^^xsd:string");
		printN3("$bio2rdf/symbol:$content", "$owl#sameAs", "$base:$identifier", 0, 0);
		xRef("symbol:$content");

		printN3("$bio2rdf/hugo:$content", "$rdf#type", "$bio2rdf/hugo_vocabulary:Symbol", 0, 0);
		printN3("$bio2rdf/hugo:$content", "$dc/identifier", "hugo:$content", 1, "^^xsd:string");
		printN3("$bio2rdf/hugo:$content", "$rdfs#label", "$content is an alias symbol for $ApprovedSymbol [hugo:$content]", 1, "^^xsd:string");
		printN3("$bio2rdf/hugo:$content", "$owl#sameAs", "$base:$identifier", 0, 0);
		xRef("hugo:$content");
	}
}

sub NameAliases{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:nameAlias", $_, 1, "^^xsd:string");
		}
	}
	else{
			printN3("$subject", "$vocabulary:nameAlias", $content, 1, "^^xsd:string");
	}
}

sub AccessionNumbers{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:accessionNumber", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xAccession", "$bio2rdf/ncbi:$_", 0, 0);
			xRef("ncbi:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:accessionNumber", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xAccession", "$bio2rdf/ncbi:$content", 0, 0);
			xRef("ncbi:$content");
	}
}

sub EnzymeIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:enzymeID", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xEC", "$bio2rdf/ec:$_", 0, 0);
			xRef("ec:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:enzymeID", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xEC", "$bio2rdf/ec:$content", 0, 0);
			xRef("ec:$content");
	}
}

sub SpecialistDatabaseIDs{
	my $subject = shift;
	my $content = shift;

	my @list = split(/, /,$content);
	if($list[0] !~ /^$/){
		printN3("$subject", "$vocabulary:mirbase", "mirbase:".$list[0], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xMirbase", "$bio2rdf/mirbase:".$list[0], 0, 0);
		xRef("mirbase:".$list[0]);
	}
	if($list[1] !~ /^$/){
		printN3("$subject", "$vocabulary:HORDE", "horde:".$list[1], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xHORDE", "$bio2rdf/horde:".$list[1], 0, 0);
		xRef("horde:".$list[1]);
	}
	if($list[2] !~ /^$/){
		printN3("$subject", "$vocabulary:CD", "cd:".$list[2], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xCD", "$bio2rdf/cd:".$list[2], 0, 0);
		xRef("cd:".$list[2]);
	}
	if($list[3] !~ /^$/){
		printN3("$subject", "$vocabulary:Rfam", "rfam:".$list[3], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xRfam", "$bio2rdf/rfam:".$list[3], 0, 0);
		xRef("rfam:".$list[3]);
	}
	if($list[4] !~ /^$/){
		printN3("$subject", "$vocabulary:snornabase", "snornabase:".$list[4], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xSnornabase", "$bio2rdf/snornabase:".$list[4], 0, 0);
		xRef("snornabase:".$list[4]);
	}
	if($list[5] !~ /^$/){
		printN3("$subject", "$vocabulary:KZNF", "kznf:".$list[5], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xKZNF", "$bio2rdf/kznf:".$list[5], 0, 0);
		xRef("kznf:".$list[5]);
	}
	if($list[6] !~ /^$/){
		printN3("$subject", "$vocabulary:IFDB", "ifdb:".$list[6], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xIFDB", "$bio2rdf/ifdb:".$list[6], 0, 0);
		xRef("ifdb:".$list[6]);
	}
	if($list[7] !~ /^$/){
		$list[7] =~ s/objectId://;
		printN3("$subject", "$vocabulary:IUPHAR", "iuphar:".$list[7], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xIUPHAR", "$bio2rdf/iuphar:".$list[7], 0, 0);
		xRef("iuphar:".$list[7]);
	}
	if($list[8] !~ /^$/){
		printN3("$subject", "$vocabulary:IMGT", "imgt:".$list[8], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xIMGT", "$bio2rdf/imgt:".$list[8], 0, 0);
		xRef("imgt:".$list[8]);
	}
	if($list[9] !~ /^$/){
		printN3("$subject", "$vocabulary:MEROPS", "merops:".$list[9], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xMEROPS", "$bio2rdf/merops:".$list[9], 0, 0);
		xRef("merops:".$list[9]);
	}
	if($list[10] !~ /^$/){
		printN3("$subject", "$vocabulary:COSMIC", "cosmic:".$list[10], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xCOSMIC", "$bio2rdf/cosmic:".$list[10], 0, 0);
		xRef("cosmic:".$list[10]);
	}
	if($list[11] !~ /^$/){
		printN3("$subject", "$vocabulary:Orphanet", "orphanet:".$list[11], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xOrphanet", "$bio2rdf/orphanet:".$list[11], 0, 0);
		xRef("orphanet:".$list[11]);
	}
	if($list[12] !~ /^$/){
		printN3("$subject", "$vocabulary:Pseudogene", "pseudogene:".$list[12], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xPseudogene", "$bio2rdf/pseudogene:".$list[12], 0, 0);
		xRef("pseudogene:".$list[12]);
	}
	if($list[13] !~ /^$/ && $list[13] !~ /^-$/){
		printN3("$subject", "$vocabulary:pirnabank", "pirnabank:".$list[13], 1, "^^xsd:string");
		printN3("$subject", "$vocabulary:xPirnabank", "$bio2rdf/pirnabank:".$list[13], 0, 0);
		xRef("pirnabank:".$list[13]);
	}
}

sub PubmedIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:pubmedID", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xPubmed", "$bio2rdf/pubmed:$_", 0, 0);
			xRef("pubmed:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:pubmedID", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xPubmed", "$bio2rdf/pubmed:$content", 0, 0);
			xRef("pubmed:$content");
	}
}

sub RefSeqIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:refSeqID", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xRefSeq", "$bio2rdf/ncbi:$_", 0, 0);
			xRef("ncbi:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:refSeqID", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xRefSeq", "$bio2rdf/ncbi:$content", 0, 0);
			xRef("ncbi:$content");
	}
}

sub GeneFamilyTag{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:geneFamilyTag", $_, 1, "^^xsd:string");
		}
	}
	else{
			printN3("$subject", "$vocabulary:geneFamilyTag", $content, 1, "^^xsd:string");
	}
}

sub CCDSIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:CCDSID", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xCCDSID", "$bio2rdf/ccds:$_", 0, 0);
			xRef("ccds:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:CCDSID", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xCCDSID", "$bio2rdf/ccds:$content", 0, 0);
			xRef("ccds:$content");
	}
}

sub VEGAIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:VEGAID", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xVEGAID", "$bio2rdf/vega:$_", 0, 0);
			xRef("vega:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:VEGAID", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xVEGAID", "$bio2rdf/vega:$content", 0, 0);
			xRef("vega:$content");
	}
}

sub GDBID_mappeddata{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:GDBID", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xGDB", "$bio2rdf/gdb:$_", 0, 0);
			xRef("gdb:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:GDBID", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xGDB", "$bio2rdf/gdb:$content", 0, 0);
			xRef("gdb:$content");
	}
}

sub EntrezGeneID_mappeddatasuppliedbyNCBI{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:entrezgeneID", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xEntrezGene", "$bio2rdf/geneid:$_", 0, 0);
			xRef("geneid:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:entrezgeneID", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xEntrezGene", "$bio2rdf/geneid:$content", 0, 0);
			xRef("geneid:$content");
	}
}

sub OMIMID_mappeddatasuppliedbyNCBI{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:OMIM", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xOMIM", "$bio2rdf/omim:$_", 0, 0);
			xRef("omim:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:OMIM", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xOMIM", "$bio2rdf/omim:$content", 0, 0);
			xRef("omim:$content");
	}
}

sub RefSeq_mappeddatasuppliedbyNCBI{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:RefSeq", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xRefSeq", "$bio2rdf/ncbi:$_", 0, 0);
			xRef("ncbi:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:RefSeq", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xRefSeq", "$bio2rdf/ncbi:$content", 0, 0);
			xRef("ncbi:$content");
	}
}

sub UniProtID_mappeddatasuppliedbyUniProt{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:uniprot", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xUniprot", "$bio2rdf/uniprot:$_", 0, 0);
			xRef("uniprot:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:uniprot", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xUniprot", "$bio2rdf/uniprot:$content", 0, 0);
			xRef("uniprot:$content");
	}
}

sub EnsemblID_mappeddatasuppliedbyEnsembl{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:ensembl", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xEnsembl", "$bio2rdf/ensembl:$_", 0, 0);
			xRef("ensembl:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:ensembl", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xEnsembl", "$bio2rdf/ensembl:$content", 0, 0);
			xRef("ensembl:$content");
	}
}

sub UCSCID_mappeddatasuppliedbyUCSC{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:UCSCID", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xUCSCID", "$bio2rdf/ucsc:$_", 0, 0);
			xRef("ucsc:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:UCSCID", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xUCSCID", "$bio2rdf/uscs:$content", 0, 0);
			xRef("ucsc:$content");
	}
}

sub MouseGenomeDatabaseID_mappeddatasuppliedbyMGI{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:MGI", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xMGI", "$bio2rdf/mgi:$_", 0, 0);
			xRef("mgi:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:MGI", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xMGI", "$bio2rdf/mgi:$content", 0, 0);
			xRef("mgi:$content");
	}
}

sub RatGenomeDatabaseID_mappeddatasuppliedbyRGD{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printN3("$subject", "$vocabulary:RGD", $_, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xRGD", "$bio2rdf/rgd:$_", 0, 0);
			xRef("rgd:$_");
		}
	}
	else{
			printN3("$subject", "$vocabulary:RGD", $content, 1, "^^xsd:string");
			printN3("$subject", "$vocabulary:xRGD", "$bio2rdf/rgd:$content", 0, 0);
			xRef("rgd:$content");
	}
}

sub xRef{
	my $smallURI = shift;

	printN3($graph, "$vocabulary:xRef", "$bio2rdf/$smallURI", 0, 0);
}

sub generateUniqueURI{
	my $content  = shift;

	my $ctx = Digest::MD5->new;
	$ctx->add($content);

	return $ctx->hexdigest;
}

sub printN3{
	my $subject = shift;
	my $predicate = shift;
	my $object = shift;
	my $literal = shift;
	my $type = shift;

#        $line =~ s/&/&amp;/g;

	if($object !~ /^$/){
		if($literal){
			$object =~ s/^"//g;
			$object =~ s/"$//g;
		        $line =~ s/>/&gt;/g;
		        $line =~ s/</&lt;/g;
			$object =~ s/\\/\\u005c/g;
			$object =~ s/"/\\u0022/g;
			$object =~ s/'/\\u0027/g;
			print "<$subject> <$predicate> ".'"'.utf8::encode($object).'"'." .\n";
		}
		else{
			print "<$subject> <$predicate> <$object> .\n";
		}
	}
}
