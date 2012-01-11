# dc:title      uniprot-n32bio2rdf.pl
# dc:creator    francoisbelleau at yahoo.ca
# dc:modified   2009-05-05
# dc:description Transform uniprot N3 dump to bio2rdf N3
 
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

# perl uniprot-n32bio2rdf.pl Protein /bio2rdf/data/uniprot/uniprot.n3  > ../n3/uniprot.uniprot.n3  2> log/uniprot2n3.log &
# perl uniprot-n32bio2rdf.pl Sequence /bio2rdf/data/uniprot/uniparc.n3 > ../n3/uniprot.uniparc.n3  2> log/uniparc2n3.log &
# perl uniprot-n32bio2rdf.pl Cluster /bio2rdf/data/uniprot/uniref.n3   > ../n3/uniprot.uniref.n3   2> log/uniref2n3.log &

$type = shift;
$file = shift;
$end = shift;
$end = 1000000000;
$trace = shift;
$trace = off;

our $vocabulary = "http://bio2rdf.org/uniprot_vocabulary";

open(INPUT, "<$file");
$fileSize = -s $file;

$rows = 0;
$lineLength = 0;
$lines = "";
$nbLines = 0;
$step = 1000;

LOOP: while ($line = <INPUT> and $rows < $end) {
        $rows ++;	
	$lineLength = $lineLength + length($line);
	print "\n0###$line" if ($trace eq "on" );

	# replace
	#$line =~ s|http://purl.uniprot.org|http://bio2rdf.org|;
	
	print "1###$line" if ($trace eq "on" );

	$lit1 = "NULL";
	$lit2 = "NULL";
	$bnode = "NULL";

	if ($line =~ /^<(.*?)> <(.*?)> \"(.*)\" \.$/) {
		$subj1 = $1;
		$subj2 = $1;
		$pred1 = $2;
		$pred2 = $2;
		$lit1 = $3;
		$lit2 = $3;

		$subj1 =~ s|http://purl.uniprot.org/(.*?)/(.*?)|http://bio2rdf.org/$1:$2|;

		$subj1 =~ /http:\/\/bio2rdf.org\/(.*?):(.*)/;
		$ns = $1;
		$id = $2;
		
		# to remove bnode
		if ($subj1 =~ /#_(.*)/) {
			$bnode = $1;
			$subj1 = "$subjType"."_"."$bnode";
			#print "$subj1\n";
		}
		
		
		if ($pred1 eq "http://purl.uniprot.org/core/mnemonic" and $label eq "") {
			print "<$subj1> <http://www.w3.org/2000/01/rdf-schema#label> \"$lit1 [$ns:$id]\" .\n";
			print "<$subj1> <http://purl.org/dc/elements/1.1/title> \"$lit1\" .\n";
			$label = $lit1;
		}
	} elsif ($line =~ /^<(.*?)> <(.*?)> <(.*)> \.$/) {
		$subj1 = $1;
		$subj2 = $1;
		$pred1 = $2;
		$pred2 = $2;
		$obj1 = $3;
		$obj2 = $3;

		$subj1 =~ s|http://purl.uniprot.org/(.*?)/(.*?)|http://bio2rdf.org/$1:$2|;
		$obj1 =~ s|http://purl.uniprot.org/(.*?)/(.*?)|http://bio2rdf.org/$1:$2|;

		$subj1 =~ /http:\/\/bio2rdf.org\/(.*?):(.*)/;
		$ns = $1;
		$id = $2;

		# to remove bnode
		if ($subj1 =~ /#_(.*)/) {
			$bnode = $1;
			$subj1 = "$subjType"."_"."$bnode";
			#print "$subj1\n";
		}
		if ($obj1 =~ /#_(.*)/) {
			$bnode = $1;
			$obj1 = "$subjType"."_"."$bnode";
			#print "$obj1\n";
		}

		if ($obj1 eq "http://bio2rdf.org/core:$type" ) {
			print "<$subj1> <http://www.w3.org/2002/07/owl#sameAs> <http://purl.uniprot.org/$ns/$id> .\n";
			print "<$subj1> <$vocabulary:html> <http://www.uniprot.org/$ns/$id> .\n";
			print "<$subj1> <http://purl.org/dc/elements/1.1/identifier> \"$ns:$id\" .\n";
			$label = "";
			$subjType = $subj1;
		}
	} else {
		warn "ERROR# $rows#$line#";
		goto LOOP;
	}; 

	print "2###$line" if ($trace eq "on" );

	if ($lit1 ne "NULL") {
		$line = "<$subj1> <$pred1> \"$lit1\" .\n";
	} else {
		$line = "<$subj1> <$pred1> <$obj1> .\n";
	}
	print $line;

	$nbLines ++;
	if ($nbLines == $step) {
		#print "$rows\t$uri\n";
		warn localtime(time)."\t".substr($lineLength/$fileSize,0,7)."\t$i\t$rows\t$subj1\n";
		$nbLines = 0;
	}
}

close(INPUT);
exit;

sub CorrectURI {
	my $uri = shift;

	$uri =~ s|http://bio2rdf.org/(.*?)/(.*)|http://bio2rdf.org/$1:$2|;
}
