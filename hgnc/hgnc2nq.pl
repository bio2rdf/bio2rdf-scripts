#!/usr/bin/perl

use strict;
use Digest::MD5;

# dc:title      hgnc2n3.pl
# dc:creator    francoisbelleau at yahoo.ca
# dc:modified   2009-03-25
# dc:description rdfise HGNC tabulated file
# 
# Modified by Marc-Alexandre Nolin
# email: manolin at gmail.com
# date: 2010-07-12
# Nature of the change: Adapted to generate nquads instead.
 
# -------------------------------------------------------------------------------
# Bio2RDF is a creation Francois Belleau, Marc-Alexandre Nolin and the Bio2RDF community.
# The SPARQL end points are hosted by the Centre de Recherche du CHUL de Quebec.
# This program is release under the GPL v2 licence. The term of this licence are #specified at http://www.gnu.org/copyleft/gpl.html.
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
	print "--------------------\n";

	printQuad("$bio2rdf/symbol:$ApprovedSymbol", "$rdf#type", "$bio2rdf/symbol_resource:Symbol", 0, 0);
	printQuad("$bio2rdf/symbol:$ApprovedSymbol", "$dc/identifier", "symbol:$ApprovedSymbol", 1, "^^xsd:string");
	printQuad("$bio2rdf/symbol:$ApprovedSymbol", "$rdfs#label", "$ApprovedName [symbol:$ApprovedSymbol]", 1, "^^xsd:string");
	printQuad("$bio2rdf/symbol:$ApprovedSymbol", "$bio2rdf/symbol_resource:approvedSymbol", $ApprovedSymbol, 1, "^^xsd:string");
	printQuad("$bio2rdf/symbol:$ApprovedSymbol", "$owl#sameAs", "$base:$identifier", 0, 0);

	printQuad("$bio2rdf/hugo:$ApprovedSymbol", "$rdf#type", "$bio2rdf/hugo_resource:Symbol", 0, 0);
	printQuad("$bio2rdf/hugo:$ApprovedSymbol", "$dc/identifier", "hugo:$ApprovedSymbol", 1, "^^xsd:string");
	printQuad("$bio2rdf/hugo:$ApprovedSymbol", "$rdfs#label", "$ApprovedName [hugo:$ApprovedSymbol]", 1, "^^xsd:string");
	printQuad("$bio2rdf/hugo:$ApprovedSymbol", "$bio2rdf/hugo_resource:approvedSymbol", $ApprovedSymbol, 1, "^^xsd:string");
	printQuad("$bio2rdf/hugo:$ApprovedSymbol", "$owl#sameAs", "$base:$identifier", 0, 0);

	printQuad("$base:$identifier", "$rdf#type", "$resource:Gene", 0, 0);
	printQuad("$base:$identifier", "$dc/identifier", $lcHGNCID, 1, "^^xsd:string");
	printQuad("$base:$identifier", "$dc/title", "$ApprovedName ($ApprovedSymbol)", 1, "^^xsd:string");
	printQuad("$base:$identifier", "$rdfs#label", "$ApprovedName ($ApprovedSymbol) [$lcHGNCID]", 1, "^^xsd:string");
	printQuad("$base:$identifier", "$bio2rdf_res:url", "http://www.genenames.org/data/hgnc_data.php?hgnc_id=$identifier", 1, "^^xsd:string");
	printQuad("$base:$identifier", "$resource:approvedSymbol", $ApprovedSymbol, 1, "^^xsd:string");

	if($Status !~ /^$/){printQuad("$base:$identifier", "$resource:status", $Status, 1, "^^xsd:string");}
	if($LocusType !~ /^$/){printQuad("$base:$identifier", "$resource:locusType", $LocusType, 1, "^^xsd:string");}
	
	if($PreviousSymbols !~ /^$/){
		$uniqueID = generateUniqueURI($PreviousSymbols);
		printQuad("$base:$identifier", "$resource:previousSymbol", "$resource:$identifier-$uniqueID", 0, 0);
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:PreviousSymbol", 0, 0);
		PreviousSymbols("$resource:$identifier-$uniqueID", $PreviousSymbols);
	}

	if($PreviousNames !~ /^$/){
		$uniqueID = generateUniqueURI($PreviousNames);
		printQuad("$base:$identifier", "$resource:previousName", "$resource:$identifier-$uniqueID", 0, 0);
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:PreviousName", 0, 0);
		PreviousNames("$resource:$identifier-$uniqueID", $PreviousNames);
	}

	if($Aliases !~ /^$/){
		$uniqueID = generateUniqueURI($Aliases);
		printQuad("$base:$identifier", "$resource:alias", "$resource:$identifier-$uniqueID", 0, 0);
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:Alias", 0, 0);
		Aliases("$resource:$identifier-$uniqueID", $Aliases);
	}

	if($NameAliases !~ /^$/){
		$uniqueID = generateUniqueURI($NameAliases);
		printQuad("$base:$identifier", "$resource:nameAlias", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:NameAlias", 0, 0);
		NameAliases("$resource:$identifier-$uniqueID", $NameAliases);
	}

	if($Chromosome !~ /^$/){printQuad("$base:$identifier", "$resource:chromosome", $Chromosome, 1, "^^xsd:string");}
	if($DateApproved !~ /^$/){printQuad("$base:$identifier", "$dc/dateAccepted", $DateApproved, 1, "^^xsd:date");}
	if($DateModified !~ /^$/){printQuad("$base:$identifier", "$dc/modified", $DateModified, 1, "^^xsd:date");}
	if($DateSymbolChanged !~ /^$/){printQuad("$base:$identifier", "$resource:dateSymbolChanged", $DateSymbolChanged, 1, "^^xsd:date");}
	if($DateNameChanged !~ /^$/){printQuad("$base:$identifier", "$resource:dateNameChanged", $DateNameChanged, 1, "^^xsd:date");}

	if($AccessionNumbers !~ /^$/){
		$uniqueID = generateUniqueURI($AccessionNumbers);
		printQuad("$base:$identifier", "$resource:accessionNumber", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:AccessionNumber", 0, 0);
		AccessionNumbers("$resource:$identifier-$uniqueID", $AccessionNumbers);
	}

	if($EnzymeIDs !~ /^$/){
		$uniqueID = generateUniqueURI($EnzymeIDs);
		printQuad("$base:$identifier", "$resource:enzymeID", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:EnzymeID", 0, 0);
		EnzymeIDs("$resource:$identifier-$uniqueID", $EnzymeIDs);
	}

	if($EntrezGeneID !~ /^$/){
		printQuad("$base:$identifier", "$resource:entrezGeneID", $EntrezGeneID, 1, "^^xsd:integer" );
		printQuad("$base:$identifier", "$resource:xEntrezGene", "$bio2rdf/geneid:$EntrezGeneID", 0, 0 );
		xRef("geneid:$EntrezGeneID");
	}

	if($EnsemblGeneID !~ /^$/){
		printQuad("$base:$identifier", "$resource:ensemblGeneID", $EnsemblGeneID, 1, "^^xsd:string" );
		printQuad("$base:$identifier", "$resource:xEnsemblGene", "$bio2rdf/ensembl:$EnsemblGeneID", 0, 0 );
		xRef("ensemble:$EnsemblGeneID");
	}

	if($MouseGenomeDatabaseID !~ /^$/){
		(my $mgi_temp, my $mgi) = split(/:/, $MouseGenomeDatabaseID);
		printQuad("$base:$identifier", "$resource:mouseGenomeDatabaseID", $mgi, 1, "^^xsd:integer" );
		printQuad("$base:$identifier", "$resource:xMouseGenomeDatabase", "$bio2rdf/mgi:$mgi", 0, 0 );
		xRef("mgi:$mgi");
	}

	if($SpecialistDatabaseLinks =~ /http/){printQuad("$base:$identifier", "$resource:specialistDatabaseLinks", $SpecialistDatabaseLinks, 1, "^^xsd:string" );}

	if($SpecialistDatabaseIDs !~ /^$/){
		$uniqueID = generateUniqueURI($SpecialistDatabaseIDs);
		printQuad("$base:$identifier", "$resource:specialistDatabaseID", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:SpecialistDatabaseID", 0, 0);
		SpecialistDatabaseIDs("$resource:$identifier-$uniqueID", $SpecialistDatabaseIDs);
	}

	if($PubmedIDs !~ /^$/){
		$uniqueID = generateUniqueURI($PubmedIDs);
		printQuad("$base:$identifier", "$resource:pubmedID", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:PubmedID", 0, 0);
		PubmedIDs("$resource:$identifier-$uniqueID", $PubmedIDs);
	}

	if($RefSeqIDs !~ /^$/){
		$uniqueID = generateUniqueURI($RefSeqIDs);
		printQuad("$base:$identifier", "$resource:refSeqID", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:RefSeqID", 0, 0);
		RefSeqIDs("$resource:$identifier-$uniqueID", $RefSeqIDs);
	}

	if($GeneFamilyTag !~ /^$/){
		$uniqueID = generateUniqueURI($GeneFamilyTag);
		printQuad("$base:$identifier", "$resource:geneFamilyTag", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:GeneFamilyTag", 0, 0);
		GeneFamilyTag("$resource:$identifier-$uniqueID", $GeneFamilyTag);
	}

	if($RecordType !~ /^$/){printQuad("$base:$identifier", "$resource:recordType", $RecordType, 1, "^^xsd:string" );}
	if($PrimaryIDs !~ /^$/ && $PrimaryIDs !~ /^-$/){
		printQuad("$base:$identifier", "$resource:primaryIDs", $PrimaryIDs, 1, "^^xsd:string" );
		my @PI = split(/, /, $PrimaryIDs);
		foreach(@PI){
			printQuad("$base:$identifier", "$resource:xPrimaryID", "$base:$_", 0, 0 );
			xRef("hgnc:$_");
		}
	}
	if($SecondaryIDs !~ /^$/ && $SecondaryIDs !~ /^-$/){
		printQuad("$base:$identifier", "$resource:secondaryIDs", $SecondaryIDs, 1, "^^xsd:string" );
		my @PI = split(/, /, $SecondaryIDs);
		foreach(@PI){
			printQuad("$base:$identifier", "$resource:xSecondaryID", "$base:$_", 0, 0 );
			xRef("hgnc:$_");
		}
	}

	if($CCDSIDs !~ /^$/){
		$uniqueID = generateUniqueURI($CCDSIDs);
		printQuad("$base:$identifier", "$resource:CCDSID", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:CCDSID", 0, 0);
		CCDSIDs("$resource:$identifier-$uniqueID", $CCDSIDs);
	}

	if($VEGAIDs !~ /^$/){
		$uniqueID = generateUniqueURI($VEGAIDs);
		printQuad("$base:$identifier", "$resource:VEGAID", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:VEGAID", 0, 0);
		VEGAIDs("$resource:$identifier-$uniqueID", $VEGAIDs);
	}

	if($LocusSpecificDatabases !~ /^$/ && $LocusSpecificDatabases !~ /<strong><\/strong>/){
		printQuad("$base:$identifier", "$resource:LocusSpecificDatabases", $LocusSpecificDatabases, 1, "^^xsd:string" );
	}

	if($GDBID_mappeddata !~ /^$/){
		$uniqueID = generateUniqueURI($GDBID_mappeddata);
		printQuad("$resource:$identifier", "$resource:GDBID_mappeddata", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:GDBID_mappeddata", 0, 0);
		GDBID_mappeddata("$base:$identifier-$uniqueID", $GDBID_mappeddata);
	}

	if($EntrezGeneID_mappeddatasuppliedbyNCBI !~ /^$/){
		$uniqueID = generateUniqueURI($EntrezGeneID_mappeddatasuppliedbyNCBI);
		printQuad("$base:$identifier", "$resource:EntrezGeneID_mappeddatasuppliedbyNCBI", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:EntrezGeneID_mappeddatasuppliedbyNCBI", 0, 0);
		EntrezGeneID_mappeddatasuppliedbyNCBI("$resource:$identifier-$uniqueID", $EntrezGeneID_mappeddatasuppliedbyNCBI);
	}
	if($OMIMID_mappeddatasuppliedbyNCBI !~ /^$/){
		$uniqueID = generateUniqueURI($OMIMID_mappeddatasuppliedbyNCBI);
		printQuad("$base:$identifier", "$resource:OMIMID_mappeddatasuppliedbyNCBI", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:OMIMID_mappeddatasuppliedbyNCBI", 0, 0);
		OMIMID_mappeddatasuppliedbyNCBI("$resource:$identifier-$uniqueID", $OMIMID_mappeddatasuppliedbyNCBI);
	}
	if($RefSeq_mappeddatasuppliedbyNCBI !~ /^$/){
		$uniqueID = generateUniqueURI($RefSeq_mappeddatasuppliedbyNCBI);
		printQuad("$base:$identifier", "$resource:RefSeq_mappeddatasuppliedbyNCBI", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:RefSeq_mappeddatasuppliedbyNCBI", 0, 0);
		RefSeq_mappeddatasuppliedbyNCBI("$resource:$identifier-$uniqueID", $RefSeq_mappeddatasuppliedbyNCBI);
	}
	if($UniProtID_mappeddatasuppliedbyUniProt !~ /^$/){
		$uniqueID = generateUniqueURI($UniProtID_mappeddatasuppliedbyUniProt);
		printQuad("$base:$identifier", "$resource:UniProtID_mappeddatasuppliedbyUniProt", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:UniProtID_mappeddatasuppliedbyUniProt", 0, 0);
		UniProtID_mappeddatasuppliedbyUniProt("$resource:$identifier-$uniqueID", $VEGAIDs);
	}
	if($EnsemblID_mappeddatasuppliedbyEnsembl !~ /^$/){
		$uniqueID = generateUniqueURI($EnsemblID_mappeddatasuppliedbyEnsembl);
		printQuad("$base:$identifier", "$resource:EnsemblID_mappeddatasuppliedbyEnsembl", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:EnsemblID_mappeddatasuppliedbyEnsembl", 0, 0);
		EnsemblID_mappeddatasuppliedbyEnsembl("$resource:$identifier-$uniqueID", $EnsemblID_mappeddatasuppliedbyEnsembl);
	}
	if($UCSCID_mappeddatasuppliedbyUCSC !~ /^$/){
		$uniqueID = generateUniqueURI($UCSCID_mappeddatasuppliedbyUCSC);
		printQuad("$base:$identifier", "$resource:UCSCID_mappeddatasuppliedbyUCSC", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:UCSCID_mappeddatasuppliedbyUCSC", 0, 0);
		UCSCID_mappeddatasuppliedbyUCSC("$resource:$identifier-$uniqueID", $UCSCID_mappeddatasuppliedbyUCSC);
	}
	if($MouseGenomeDatabaseID_mappeddatasuppliedbyMGI !~ /^$/){
		$uniqueID = generateUniqueURI($MouseGenomeDatabaseID_mappeddatasuppliedbyMGI);
		printQuad("$base:$identifier", "$resource:MouseGenomeDatabaseID_mappeddatasuppliedbyMGI", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:MouseGenomeDatabaseID_mappeddatasuppliedbyMGI", 0, 0);
		MouseGenomeDatabaseID_mappeddatasuppliedbyMGI("$resource:$identifier-$uniqueID", $MouseGenomeDatabaseID_mappeddatasuppliedbyMGI);
	}
	if($RatGenomeDatabaseID_mappeddatasuppliedbyRGD !~ /^$/){
		$uniqueID = generateUniqueURI($RatGenomeDatabaseID_mappeddatasuppliedbyRGD);
		printQuad("$base:$identifier", "$resource:RatGenomeDatabaseID_mappeddatasuppliedbyRGD", "$resource:$identifier-$uniqueID", 0, 0 );
		printQuad("$resource:$identifier-$uniqueID", "$rdf#type", "$resource:RatGenomeDatabaseID_mappeddatasuppliedbyRGD", 0, 0);
		RatGenomeDatabaseID_mappeddatasuppliedbyRGD("$resource:$identifier-$uniqueID", $RatGenomeDatabaseID_mappeddatasuppliedbyRGD);
	}

	print "--------------------\n";

}

close(ENTREE);

exit;

sub PreviousSymbols{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:symbol", $_, 1, "^^xsd:string");

			printQuad("$bio2rdf/symbol:$_", "$rdf#type", "$bio2rdf/symbol_resource:Symbol", 0, 0);
			printQuad("$bio2rdf/symbol:$_", "$dc/identifier", "symbol:$_", 1, "^^xsd:string");
			printQuad("$bio2rdf/symbol:$_", "$rdfs#label", "Previous symbol for $ApprovedSymbol [symbol:$_]", 1, "^^xsd:string");
			printQuad("$bio2rdf/symbol:$_", "$owl#sameAs", "$base:$identifier", 0, 0);
			xRef("symbol:$_");

			printQuad("$bio2rdf/hugo:$_", "$rdf#type", "$bio2rdf/hugo_resource:Symbol", 0, 0);
			printQuad("$bio2rdf/hugo:$_", "$dc/identifier", "hugo:$content", 1, "^^xsd:string");
			printQuad("$bio2rdf/hugo:$_", "$rdfs#label", "Previous symbol for $ApprovedSymbol [hugo:$_]", 1, "^^xsd:string");
			printQuad("$bio2rdf/hugo:$_", "$owl#sameAs", "$base:$identifier", 0, 0);
			xRef("hugo:$_");
		}
	}
	else{
		printQuad("$subject", "$resource:symbol", $content, 1, "^^xsd:string");

		printQuad("$bio2rdf/symbol:$content", "$rdf#type", "$bio2rdf/symbol_resource:Symbol", 0, 0);
		printQuad("$bio2rdf/symbol:$content", "$dc/identifier", "symbol:$content", 1, "^^xsd:string");
		printQuad("$bio2rdf/symbol:$content", "$rdfs#label", "Previous symbol for $ApprovedSymbol [symbol:$content]", 1, "^^xsd:string");
		printQuad("$bio2rdf/symbol:$content", "$owl#sameAs", "$base:$identifier", 0, 0);
		xRef("symbol:$content");

		printQuad("$bio2rdf/hugo:$content", "$rdf#type", "$bio2rdf/hugo_resource:Symbol", 0, 0);
		printQuad("$bio2rdf/hugo:$content", "$dc/identifier", "hugo:$content", 1, "^^xsd:string");
		printQuad("$bio2rdf/hugo:$content", "$rdfs#label", "Previous symbol for $ApprovedSymbol [hugo:$content]", 1, "^^xsd:string");
		printQuad("$bio2rdf/hugo:$content", "$owl#sameAs", "$base:$identifier", 0, 0);
		xRef("hugo:$content");
	}
}

sub PreviousNames{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:previousName", $_, 1, "^^xsd:string");
		}
	}
	else{
			printQuad("$subject", "$resource:previousName", $content, 1, "^^xsd:string");
	}
}

sub Aliases{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:alias", $_, 1, "^^xsd:string");

			printQuad("$bio2rdf/symbol:$_", "$rdf#type", "$bio2rdf/symbol_resource:Symbol", 0, 0);
			printQuad("$bio2rdf/symbol:$_", "$dc/identifier", "symbol:$_", 1, "^^xsd:string");
			printQuad("$bio2rdf/symbol:$_", "$rdfs#label", "$_ is an alias symbol for $ApprovedSymbol [symbol:$_]", 1, "^^xsd:string");
			printQuad("$bio2rdf/symbol:$_", "$owl#sameAs", "$base:$identifier", 0, 0);
			xRef("symbol:$_");

			printQuad("$bio2rdf/hugo:$_", "$rdf#type", "$bio2rdf/hugo_resource:Symbol", 0, 0);
			printQuad("$bio2rdf/hugo:$_", "$dc/identifier", "hugo:$content", 1, "^^xsd:string");
			printQuad("$bio2rdf/hugo:$_", "$rdfs#label", "$_ is an alias symbol for $ApprovedSymbol [hugo:$_]", 1, "^^xsd:string");
			printQuad("$bio2rdf/hugo:$_", "$owl#sameAs", "$base:$identifier", 0, 0);
			xRef("hugo:$_");
		}
	}
	else{
		printQuad("$subject", "$resource:alias", $content, 1, "^^xsd:string");

		printQuad("$bio2rdf/symbol:$content", "$rdf#type", "$bio2rdf/symbol_resource:Symbol", 0, 0);
		printQuad("$bio2rdf/symbol:$content", "$dc/identifier", "symbol:$content", 1, "^^xsd:string");
		printQuad("$bio2rdf/symbol:$content", "$rdfs#label", "$content is an alias symbol for $ApprovedSymbol [symbol:$content]", 1, "^^xsd:string");
		printQuad("$bio2rdf/symbol:$content", "$owl#sameAs", "$base:$identifier", 0, 0);
		xRef("symbol:$content");

		printQuad("$bio2rdf/hugo:$content", "$rdf#type", "$bio2rdf/hugo_resource:Symbol", 0, 0);
		printQuad("$bio2rdf/hugo:$content", "$dc/identifier", "hugo:$content", 1, "^^xsd:string");
		printQuad("$bio2rdf/hugo:$content", "$rdfs#label", "$content is an alias symbol for $ApprovedSymbol [hugo:$content]", 1, "^^xsd:string");
		printQuad("$bio2rdf/hugo:$content", "$owl#sameAs", "$base:$identifier", 0, 0);
		xRef("hugo:$content");
	}
}

sub NameAliases{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:nameAlias", $_, 1, "^^xsd:string");
		}
	}
	else{
			printQuad("$subject", "$resource:nameAlias", $content, 1, "^^xsd:string");
	}
}

sub AccessionNumbers{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:accessionNumber", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xAccession", "$bio2rdf/ncbi:$_", 0, 0);
			xRef("ncbi:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:accessionNumber", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xAccession", "$bio2rdf/ncbi:$content", 0, 0);
			xRef("ncbi:$content");
	}
}

sub EnzymeIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:enzymeID", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xEC", "$bio2rdf/ec:$_", 0, 0);
			xRef("ec:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:enzymeID", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xEC", "$bio2rdf/ec:$content", 0, 0);
			xRef("ec:$content");
	}
}

sub SpecialistDatabaseIDs{
	my $subject = shift;
	my $content = shift;

	my @list = split(/, /,$content);
	if($list[0] !~ /^$/){
		printQuad("$subject", "$resource:mirbase", "mirbase:".$list[0], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xMirbase", "$bio2rdf/mirbase:".$list[0], 0, 0);
		xRef("mirbase:".$list[0]);
	}
	if($list[1] !~ /^$/){
		printQuad("$subject", "$resource:HORDE", "horde:".$list[1], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xHORDE", "$bio2rdf/horde:".$list[1], 0, 0);
		xRef("horde:".$list[1]);
	}
	if($list[2] !~ /^$/){
		printQuad("$subject", "$resource:CD", "cd:".$list[2], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xCD", "$bio2rdf/cd:".$list[2], 0, 0);
		xRef("cd:".$list[2]);
	}
	if($list[3] !~ /^$/){
		printQuad("$subject", "$resource:Rfam", "rfam:".$list[3], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xRfam", "$bio2rdf/rfam:".$list[3], 0, 0);
		xRef("rfam:".$list[3]);
	}
	if($list[4] !~ /^$/){
		printQuad("$subject", "$resource:snornabase", "snornabase:".$list[4], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xSnornabase", "$bio2rdf/snornabase:".$list[4], 0, 0);
		xRef("snornabase:".$list[4]);
	}
	if($list[5] !~ /^$/){
		printQuad("$subject", "$resource:KZNF", "kznf:".$list[5], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xKZNF", "$bio2rdf/kznf:".$list[5], 0, 0);
		xRef("kznf:".$list[5]);
	}
	if($list[6] !~ /^$/){
		printQuad("$subject", "$resource:IFDB", "ifdb:".$list[6], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xIFDB", "$bio2rdf/ifdb:".$list[6], 0, 0);
		xRef("ifdb:".$list[6]);
	}
	if($list[7] !~ /^$/){
		$list[7] =~ s/objectId://;
		printQuad("$subject", "$resource:IUPHAR", "iuphar:".$list[7], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xIUPHAR", "$bio2rdf/iuphar:".$list[7], 0, 0);
		xRef("iuphar:".$list[7]);
	}
	if($list[8] !~ /^$/){
		printQuad("$subject", "$resource:IMGT", "imgt:".$list[8], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xIMGT", "$bio2rdf/imgt:".$list[8], 0, 0);
		xRef("imgt:".$list[8]);
	}
	if($list[9] !~ /^$/){
		printQuad("$subject", "$resource:MEROPS", "merops:".$list[9], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xMEROPS", "$bio2rdf/merops:".$list[9], 0, 0);
		xRef("merops:".$list[9]);
	}
	if($list[10] !~ /^$/){
		printQuad("$subject", "$resource:COSMIC", "cosmic:".$list[10], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xCOSMIC", "$bio2rdf/cosmic:".$list[10], 0, 0);
		xRef("cosmic:".$list[10]);
	}
	if($list[11] !~ /^$/){
		printQuad("$subject", "$resource:Orphanet", "orphanet:".$list[11], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xOrphanet", "$bio2rdf/orphanet:".$list[11], 0, 0);
		xRef("orphanet:".$list[11]);
	}
	if($list[12] !~ /^$/){
		printQuad("$subject", "$resource:Pseudogene", "pseudogene:".$list[12], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xPseudogene", "$bio2rdf/pseudogene:".$list[12], 0, 0);
		xRef("pseudogene:".$list[12]);
	}
	if($list[13] !~ /^$/ && $list[13] !~ /^-$/){
		printQuad("$subject", "$resource:pirnabank", "pirnabank:".$list[13], 1, "^^xsd:string");
		printQuad("$subject", "$resource:xPirnabank", "$bio2rdf/pirnabank:".$list[13], 0, 0);
		xRef("pirnabank:".$list[13]);
	}
}

sub PubmedIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:pubmedID", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xPubmed", "$bio2rdf/pubmed:$_", 0, 0);
			xRef("pubmed:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:pubmedID", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xPubmed", "$bio2rdf/pubmed:$content", 0, 0);
			xRef("pubmed:$content");
	}
}

sub RefSeqIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:refSeqID", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xRefSeq", "$bio2rdf/ncbi:$_", 0, 0);
			xRef("ncbi:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:refSeqID", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xRefSeq", "$bio2rdf/ncbi:$content", 0, 0);
			xRef("ncbi:$content");
	}
}

sub GeneFamilyTag{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:geneFamilyTag", $_, 1, "^^xsd:string");
		}
	}
	else{
			printQuad("$subject", "$resource:geneFamilyTag", $content, 1, "^^xsd:string");
	}
}

sub CCDSIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:CCDSID", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xCCDSID", "$bio2rdf/ccds:$_", 0, 0);
			xRef("ccds:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:CCDSID", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xCCDSID", "$bio2rdf/ccds:$content", 0, 0);
			xRef("ccds:$content");
	}
}

sub VEGAIDs{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:VEGAID", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xVEGAID", "$bio2rdf/vega:$_", 0, 0);
			xRef("vega:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:VEGAID", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xVEGAID", "$bio2rdf/vega:$content", 0, 0);
			xRef("vega:$content");
	}
}

sub GDBID_mappeddata{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:GDBID", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xGDB", "$bio2rdf/gdb:$_", 0, 0);
			xRef("gdb:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:GDBID", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xGDB", "$bio2rdf/gdb:$content", 0, 0);
			xRef("gdb:$content");
	}
}

sub EntrezGeneID_mappeddatasuppliedbyNCBI{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:entrezgeneID", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xEntrezGene", "$bio2rdf/geneid:$_", 0, 0);
			xRef("geneid:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:entrezgeneID", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xEntrezGene", "$bio2rdf/geneid:$content", 0, 0);
			xRef("geneid:$content");
	}
}

sub OMIMID_mappeddatasuppliedbyNCBI{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:OMIM", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xOMIM", "$bio2rdf/omim:$_", 0, 0);
			xRef("omim:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:OMIM", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xOMIM", "$bio2rdf/omim:$content", 0, 0);
			xRef("omim:$content");
	}
}

sub RefSeq_mappeddatasuppliedbyNCBI{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:RefSeq", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xRefSeq", "$bio2rdf/ncbi:$_", 0, 0);
			xRef("ncbi:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:RefSeq", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xRefSeq", "$bio2rdf/ncbi:$content", 0, 0);
			xRef("ncbi:$content");
	}
}

sub UniProtID_mappeddatasuppliedbyUniProt{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:uniprot", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xUniprot", "$bio2rdf/uniprot:$_", 0, 0);
			xRef("uniprot:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:uniprot", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xUniprot", "$bio2rdf/uniprot:$content", 0, 0);
			xRef("uniprot:$content");
	}
}

sub EnsemblID_mappeddatasuppliedbyEnsembl{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:ensembl", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xEnsembl", "$bio2rdf/ensembl:$_", 0, 0);
			xRef("ensembl:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:ensembl", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xEnsembl", "$bio2rdf/ensembl:$content", 0, 0);
			xRef("ensembl:$content");
	}
}

sub UCSCID_mappeddatasuppliedbyUCSC{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:UCSCID", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xUCSCID", "$bio2rdf/ucsc:$_", 0, 0);
			xRef("ucsc:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:UCSCID", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xUCSCID", "$bio2rdf/uscs:$content", 0, 0);
			xRef("ucsc:$content");
	}
}

sub MouseGenomeDatabaseID_mappeddatasuppliedbyMGI{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:MGI", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xMGI", "$bio2rdf/mgi:$_", 0, 0);
			xRef("mgi:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:MGI", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xMGI", "$bio2rdf/mgi:$content", 0, 0);
			xRef("mgi:$content");
	}
}

sub RatGenomeDatabaseID_mappeddatasuppliedbyRGD{
	my $subject = shift;
	my $content = shift;

	if($content =~ /, /){
		my @list = split(/, /,$content);
		foreach(@list){
			printQuad("$subject", "$resource:RGD", $_, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xRGD", "$bio2rdf/rgd:$_", 0, 0);
			xRef("rgd:$_");
		}
	}
	else{
			printQuad("$subject", "$resource:RGD", $content, 1, "^^xsd:string");
			printQuad("$subject", "$resource:xRGD", "$bio2rdf/rgd:$content", 0, 0);
			xRef("rgd:$content");
	}
}

sub xRef{
	my $smallURI = shift;

	printQuad($graph, "$resource:xRef", "$bio2rdf/$smallURI", 0, 0);
}

sub generateUniqueURI{
	my $content  = shift;

	my $ctx = Digest::MD5->new;
	$ctx->add($content);

	return $ctx->hexdigest;
}

sub printQuad{
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
			print "<$subject> <$predicate> ".'"'.$object.'"'."$type <$graph> .\n";
		}
		else{
			print "<$subject> <$predicate> <$object> <$graph> .\n";
		}
	}
}
