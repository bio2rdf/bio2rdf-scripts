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


# unists2rdf.pl
# dc:title      unists2n3.pl
# dc:creator    francoisbelleau at yahoo.ca
# dc:modified   2009-03-31
# dc:description Convert UniSTS tabulated file in taxon directory to N3 format
# perl unists2rdf.pl ./unists/*.txt > unists.rdf    
 
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
 

$max = 100;
$max = 10000;
$max = 100000000;

$path = "/media/twotb/bio2rdf/data/unists";

	opendir( DIR, $path ) or die "Can't open $path: $!";
        @dir1s = readdir( DIR );
        closedir( DIR );

	foreach $dir (@dir1s) {
		#print "$dir\n";
		if ($dir ne "." and $dir ne ".." and ! ($dir =~ /READ.*/)) {
			opendir( DIR, "$path/$dir" ) or die "Can't open $path: $!";
        		@dir2s = readdir( DIR );
        		closedir( DIR );
			foreach $fichier (@dir2s) {
				if ($fichier ne "." and $fichier ne ".." and ! ($fichier =~ /READ.*/)) {
					#print "$fichier\n";
					$fichier =~ /(.*?)\.(.*?)\.txt/;
					$taxon = $1;
					$ns = lc($2);
					#print "$fichier\t$taxon\t$ns\n";
					ReadFile("$path/$dir/$fichier", $taxon, $ns);
				}	
			}	
		}
	}

exit;


sub ReadFile {
$fichier = shift;
$taxon = shift;
$ns = shift;
$nombre = 0;

#return;
$uri = "http://bio2rdf.org/unists:group-$ns";

print <<EOF;
<$uri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/unists_vocabulary:MarkerGroup> .
<$uri> <http://purl.org/dc/elements/1.1/identifier> "unists_resouce:group-$ns" .
<$uri> <http://www.w3.org/2000/01/rdf-schema#label> "$ns [unists_resource:group-$ns]" .
<$uri> <http://bio2rdf.org/unists_vocabulary:xTaxon> <http://bio2rdf.org/taxon:$taxon> .

EOF

open(ENTREE, "<$fichier") || die "Fichier $fichier introuvable:$!\n";

while ($ligne = <ENTREE> and $nombre < $max) {
	$nombre = $nombre + 1;
	
	$ligne =~ s/&/&amp;/g;
	$ligne =~ s/>/&gt;/g;
	$ligne =~ s/</&lt;/g;
        $ligne =~ s/://g;
        $ligne =~ s/#//g;
        $ligne =~ s/\///g;
        $ligne =~ s/\\//g;

	#print $ligne;

	$ligne =~ /(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)$/;
	$v1 = $1;
	$v2 = $2;
	$v3 = $3;
	$v4 = $4;
	$v5 = $5;
	$v6 = $6;
	$v7 = $7;
   
        $v1 =~ s/ /_/g;
        $v2 =~ s/ /_/g;
        $v2 =~ s/>/_/g;
        $v2 =~ s/</_/g;
        $v2 =~ s/"/_/g;
        $v2 =~ s/'/_/g;
    	
	$uri_unists = "http://bio2rdf.org/unists:$v1";
        $uri_marker = "http://bio2rdf.org/marker:$v2";
#generate triples about markers only if they have a UniSTS identifier
if ($v1 ne "") {
print <<EOF;

<$uri_unists> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/unists_vocabulary:Marker> .
<$uri_unists> <http://purl.org/dc/elements/1.1/identifier> "unists:$v1" .
<$uri_unists> <http://www.w3.org/2000/01/rdf-schema#label> "[unists:$v1]" .
<$uri_unists> <http://bio2rdf.org/unists_vocabulary:url> "http://www.ncbi.nlm.nih.gov/genome/sts/sts.cgi?uid=$v1" .
<$uri_unists> <http://bio2rdf.org/unists_vocabulary:xTaxon> <http://bio2rdf.org/taxon:$taxon> .
<$uri_unists> <http://bio2rdf.org/unists_vocabulary:xChromosome> <http://bio2rdf.org/chromosome_resource:$taxon-$v3> .

<$uri_marker> <http://bio2rdf.org/marker_vocabulary:xUniSTS> <http://bio2rdf.org/unists:$v1> .
<$uri_marker> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/unists_vocabulary:MarkerName> .
<$uri_marker> <http://purl.org/dc/elements/1.1/title> "$v2" .
<$uri_marker> <http://purl.org/dc/elements/1.1/identifier> "marker:$v2" .
<$uri_marker> <http://www.w3.org/2000/01/rdf-schema#label> "[marker:$v2]" .
<$uri_marker> <http://bio2rdf.org/unists_vocabulary:xTaxon> <http://bio2rdf.org/taxon:$taxon> .
<$uri_marker> <http://bio2rdf.org/unists_vocabulary:xMarkerGroup> <http://bio2rdf.org/marker_resource:group-$ns> .
<$uri_marker> <http://bio2rdf.org/unists_vocabulary:xChromosome> <http://bio2rdf.org/chromosome_resource:$taxon-$v3> .
<$uri_marker> <http://bio2rdf.org/unists_vocabulary:xLocus> <http://bio2rdf.org/locus_resource:$taxon-$v3-$v4> .
<$uri_marker> <http://bio2rdf.org/unists_vocabulary:chromosome> "$v3" .
<$uri_marker> <http://bio2rdf.org/unists_vocabulary:position> "$v4" .

EOF
}

}

close(ENTREE);


}


