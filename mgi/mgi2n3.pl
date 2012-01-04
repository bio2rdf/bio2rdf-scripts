# mgi2n3.pl
# dc:title      mgi2n3.pl
# dc:creator    francoisbelleau at yahoo.ca
# dc:modified   2009-03-26
# dc:description  convert a tabulated file in rdf
# perl mgi2n3.pl > mgi.n3
 
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

$path= "/media/2tbdisk/bio2rdf/data/mgi";

$numberMax = 10;
$numberMax = 100000000;


ReadFile("$path/MGI_Coordinate.rpt");
ReadFile("$path/MRK_Dump2.rpt");
ReadFile("$path/HMD_HGNC_Accession.rpt");
ReadFile("$path/MRK_InterPro.rpt");
ReadFile("$path/MRK_Synonym.rpt");
ReadFile("$path/MRK_SwissProt.rpt");
ReadFile("$path/gene_association.mgi");

exit;

sub ReadFile {
	my $path = shift;

	$path =~ /(.*)\/(.*)\.(.*)/;
	$file = "$2.$3";
	$LINK = "$2";
	$file =~ /(.*?)\.(.*)/;
	$proc = "$1";
	warn  "$path\t$file\t$proc\tfile\n";
	#return;
	
	
	open(ENTREE, "<$path") || die "Fichier $path introuvable:$!\n";
	
	$fileLength = -s $path;
	
	# read the title line
	if ($file eq "MGI_Coordinate.rpt") {
		$line= <ENTREE> ;
	}
	if ($file eq "gene_association.mgi") {
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
	}
	
	if ($file eq "MRK_Synonym.rpt") {
		$line= <ENTREE> ;
	}
	
	if ($file eq "HMD_HGNC_Accession.rpt") {
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
		$line= <ENTREE> ;
	}
	
	$linesLength = 0;
	$number = 0;
	
	while ($line= <ENTREE> and $number < $numberMax) {
		$number ++;
		$linesLength = $linesLength + length($line);
		last if ($number > $numberMax);
		#print  "$line";
		
		$line =~ s/'/&#39;/g;
		$line =~ s/&/&amp;/g;
		$line =~ s/>/&gt;/g;
		$line =~ s/</&lt;/g;

		chop $line;
		@fields = split(/\t/, $line);
		#print  "@fields\n";
		$fields = join("~", @fields);
		#print  "$fields\n";
		
		$LSID = lc("@fields[0]");
		$LSID2 = lc("$LSID-$LINK");

		#$TEXT = $line;
		
		&$proc;
		print $TEXT;

		#warn  "###".localtime(time)."\tmgi2mysql.pl\t$table\t$number\t".$linesLength/$fileLength."\t$LSID2\t$LINK\n";
	}
	
	close(ENTREE);

}


sub MRK_Dump2 {

$chromosomePosition = $fields[3];
$chromosomePosition =~ /^\s*(.*)/;
$chromosomePosition = $1;

$symbol = "";
$symbol = "$fields[1], " if ($fields[1] ne "");
$symbol2 = lc($fields[1]);
$lsid = lc($fields[0]);
$uri = "http://bio2rdf.org/$lsid";

$TEXT = <<EOF;
<$uri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/mgi#Marker> .
<$uri> <http://www.w3.org/2000/01/rdf-schema#label> "$symbol$fields[2] [$lsid]" .
<$uri> <http://purl.org/dc/elements/1.1/identifier> "$lsid" .
<$uri> <http://purl.org/dc/elements/1.1/title> "$symbol$fields[2]" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#url> "http://www.informatics.jax.org/searches/accession_report.cgi?id=$lsid" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#symbol> "$fields[1]" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#xChromosome> <http://bio2rdf.org/chr:10090-chr$fields[4]> .
<$uri> <http://bio2rdf.org/ns/bio2rdf#chromosomePosition> "$chromosomePosition" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#xLocus> <http://bio2rdf.org/locus:10090-chr$fields[4]-$chromosomePosition> .
<$uri> <http://bio2rdf.org/ns/bio2rdf#subType> "$fields[5]" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#url> "http://www.informatics.jax.org/searches/accession_report.cgi?id=$lsid" .

<http://bio2rdf.org/symbol:$symbol2> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/bio2rdf#Symbol> .
<http://bio2rdf.org/symbol:$symbol2> <http://www.w3.org/2002/07/owl#sameAs> <$uri> .

EOF
}

sub MGI_Coordinate {

$symbol = "";
$symbol = "$fields[2], " if ($fields[2] ne "");
$symbol2 = lc($fields[2]);
$lsid = lc($fields[0]);
$uri = "http://bio2rdf.org/$lsid";

$TEXT = <<EOF;
<$uri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/mgi#Marker> .
<$uri> <http://www.w3.org/2000/01/rdf-schema#label> "$fields[3] ($symbol) [$lsid]" .
<$uri> <http://purl.org/dc/elements/1.1/identifier> "$lsid" .
<$uri> <http://purl.org/dc/elements/1.1/title> "$fields[3]" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#url> "http://www.informatics.jax.org/searches/accession_report.cgi?id=$lsid" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#image> "http://gbrowse.informatics.jax.org/cgi-bin/gbrowse_img/thumbs_build_34?options=Everything;width=400;name=chr$fields[4]:$fields[5]..$fields[6]" .

<$uri> <http://bio2rdf.org/ns/bio2rdf#subType> "$fields[1]" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#symbol> "$fields[2]" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#xChromosome> <http://bio2rdf.org/chromosome:10090-chr$fields[4]> .
<$uri> <http://bio2rdf.org/ns/mgi#genomeStart> "$fields[5]" .
<$uri> <http://bio2rdf.org/ns/mgi#genomeEnd> "$fields[6]" .
<$uri> <http://bio2rdf.org/ns/mgi#genomeStrand> "$fields[7]" .
<$uri> <http://bio2rdf.org/ns/bio2rdf#xGeneID> <http://bio2rdf.org/geneid:$fields[10]> .
<$uri> <http://bio2rdf.org/ns/bio2rdf#xENSEMBL> <http://bio2rdf.org/ensembl:$fields[15]> .

<$uri> <http://bio2rdf.org/ns/bio2rdf#xGene> <http://bio2rdf.org/gene:10090-$symbol2> .
<$uri> <http://bio2rdf.org/ns/bio2rdf#xSymbol> <http://bio2rdf.org/symbol:$fields[2]> .

<http://bio2rdf.org/symbol:$fields[2]> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/bio2rdf#Symbol> .
<http://bio2rdf.org/symbol:$fields[2]> <http://www.w3.org/2002/07/owl#sameAs> <http://bio2rdf.org/$lsid> .

EOF
}

sub MRK_Synonym {

$line =~ /^(.*)?\t(.*)?\t(.*)?\t(.*)?\t(.*)$/ ;
$LSID = $1;
$synonym = $5;
$LSID =~ /(.*?)\s/;
$LSID = lc($1);
$LSID2 = "$LSID-$file-$synonym";
	
$TEXT = <<EOF;
<http://bio2rdf.org/$LSID> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/mgi#Marker> .
<http://bio2rdf.org/$LSID> <http://bio2rdf.org/ns/bio2rdf#synonym> "$synonym" .
EOF
}

sub MRK_SwissProt {
	
$lsid = lc($fields[0]);

$TEXT = <<EOF;
<http://bio2rdf.org/$lsid> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/mgi#Marker> .
<http://bio2rdf.org/$lsid> <http://bio2rdf.org/ns/bio2rdf#xUniProt> <http://bio2rdf.org/uniprot:$fields[6]> .
EOF
}

sub MRK_InterPro {
$lsid = lc($fields[0]);
$TEXT = "<http://bio2rdf.org/$lsid> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/mgi#Marker> .\n";
	
	@elements = split(/ /, $fields[2]);
	foreach $element (@elements) {
		$TEXT = $TEXT."<http://bio2rdf.org/$lsid> <http://bio2rdf.org/ns/bio2rdf#xInterPro> <http://bio2rdf.org/interpro:$element> .\n";
	}

}

sub HMD_HGNC_Accession {
$lsid = lc($fields[0]);
$hgnc = lc($fields[4]);
	
$TEXT = <<EOF;
<http://bio2rdf.org/$lsid> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/mgi#Marker> .
<http://bio2rdf.org/$lsid> <http://bio2rdf.org/ns/bio2rdf#xHGNC> <http://bio2rdf.org/$hgnc> .
<http://bio2rdf.org/$lsid> <http://bio2rdf.org/ns/bio2rdf#xGeneID> <http://bio2rdf.org/geneid:$fields[6]> .
EOF
}

sub gene_association {

$LSID = "@fields[1]";
$LSID2 = lc("$LSID-$file-@fields[4]");
$lsid = lc($fields[1]);
$go = lc($fields[4]);

$TEXT = <<EOF;
<http://bio2rdf.org/$lsid> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/mgi#Marker> .
<http://bio2rdf.org/$lsid> <http://bio2rdf.org/ns/bio2rdf#xGO> <http://bio2rdf.org/$go> .
EOF
}

