# omim-genemap2n3.pl
# dc:creator francoisbelleau at yahoo.ca

# data @ ftp://ftp.ncbi.nih.gov/repository/OMIM/genemap
# field descriptions @ ftp://ftp.ncbi.nih.gov/repository/OMIM/genemap.key

# -------------------------------------------------------------------------------
# Bio2RDF is a creation Francois Belleau, Marc-Alexandre Nolin and the Bio2RDF community.
# The SPARQL end points are hosted by the Centre de Recherche du CHUL de Quebec.
# This program is release under the GPL v2 licence. The term of this licence are #specified at http://www.gnu.org/copyleft/gpl.html.
#
# You can contact the Bio2RDF team at bio2rdf@gmail.com
# Visit our blog at http://bio2rdf.blogspot.com/
# Visit the main application at http://bio2rdf.org
# This open source project is hosted at https://sourceforge.net/projects/bio2rdf/
# -------------------------------------------------------------------------------
 
#1  - Numbering system, in the format  Chromosome.Map_Entry_Number
#2  - Month entered
#3  - Day     "
#4  - Year    "
#5  - Location
#6  - Gene Symbol(s)
#7  - Gene Status (see below for codes)
#8  - Title
#9  - 
#10 - MIM Number
#11 - Method (see below for codes)
#12 - Comments
#13 -
#14 - Disorders
#15 - Disorders, cont.
#16 - Disorders, cont
#17 - Mouse correlate
#18 - Reference

$nombreMax = 10000000;

open(INPUT, "</media/2tbdisk/bio2rdf/data/omim/genemap/genemap") || die "File $fichier unavailable:$!\n";
	
while ($line = <INPUT>) {
	$line =~ s/\\//g;
	$line =~ s/"/'/g;
	$line =~ s/</&lt/g;
	$line =~ s/>/&gt/g;
	@fields = split(/\|/, $line);
	#print "###$line\n";
	$id = "@fields[9]";
	$nsid = "omim:$id";
	$bmuri = "http://bio2rdf.org/$nsid";

print <<EOF;
<$bmuri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/omim#Gene> .
<$bmuri> <http://www.w3.org/2000/01/rdf-schema#label> "@fields[7] [$nsid]" .
<$bmuri> <http://purl.org/dc/elements/1.1/identifier> "$nsid" .
<$bmuri> <http://purl.org/dc/elements/1.1/title> "@fields[7]" .
<$bmuri> <http://purl.org/dc/elements/1.1/created> "@fields[3]-@fields[1]-@fields[2]" .
<$bmuri> <http://bio2rdf.org/bio2rdf#url> "http://www.ncbi.nlm.nih.gov/entrez/dispomim.cgi?id=$id" .
<$bmuri> <http://bio2rdf.org/bio2rdf#location> "@fields[4]" .
<$bmuri> <http://bio2rdf.org/omim#xGeneStatus> <http://bio2rdf.org/omim:status-@fields[6]> .

EOF

if (@fields[11] ne "") {
	$item = @fields[11];
	print "<$bmuri> <http://www.w3.org/2000/01/rdf-schema#comment> \"$item\" .\n";
}

if (@fields[13] ne "") {
	$item = @fields[13].@fields[14].@fields[15];
	##print "###<$bmuri> <http://www.w3.org/2000/01/rdf-schema#comment> \"$item\" .\n";
	foreach $item1 (split(";", $item)) {
		if ($item1 =~ / (.*)/) { $item1 = $1;}
		print "<$bmuri> <http://bio2rdf.org/omim#Disease> \"$item1\" .\n";
		if ($item1 =~ /, ([0-9]*) \(.\)/) {
			print "<$bmuri> <http://bio2rdf.org/omim#xDisease> <http://bio2rdf.org/omim:$1> .\n";
		}
	}
}

if (@fields[16] ne "") {
	$item = @fields[16];
	$item =~ /\((.*)\)/;
	$item1 = lc($1);
	foreach $item (split(", ", $item1)) {
		print "<$bmuri> <http://bio2rdf.org/bio2rdf#xMouseGene> <http://bio2rdf.org/gene:10090-$item> .\n";
	}
}

foreach $item (split(", ", @fields[5])) {
	$item1 = lc($item);
	print "<$bmuri> <http://bio2rdf.org/bio2rdf#xGene> <http://bio2rdf.org/gene:9606-$item1> .\n";
	print "<$bmuri> <http://bio2rdf.org/bio2rdf#symbol> \"$item\" .\n";
}

foreach $item (split(", ", @fields[11])) {
	print "<$bmuri> <http://bio2rdf.org/omim#xMethod> <http://bio2rdf.org/omim:method-$item> .\n";
}

print "\n\n";

}
close(INPUT);

exit;

