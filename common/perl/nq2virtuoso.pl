#!/usr/bin/perl
# dc:title       nq2virtuoso.pl
# dc:creator     francoisbelleau at yahoo.ca and manolin at gmail.com
# dc:modified    2010-07-28
# dc:description Load a compressed nQuad file into a Virtuoso database using an isql command
 
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

# perl nq2virtuoso.pl <$graph> <$port> <$password> <$flag> <$thread> <$file>
# Usage example:
# ls /bio2rdf2/data/entrezgene/All_Mammalia_part_*.nq.gz | /opt/Bio2rdf/perl/parargs.py -n12 /opt/Bio2rdf/perl/nq2virtuoso.pl geneid 11111 dbabio2rdf 945 2 > load-geneid-1.log & 2>&1

$graph = shift;
$port = shift;
$password = shift;
$flag = shift;
$thread = shift;
$file = shift;

print system("isql $port -P $password verbose=on banner=off prompt=off echo=ON errors=stdout exec=\"log_enable(2); ttlp_mt (gz_file_open('$file'), '', 'http://bio2rdf.org/graph/$graph',$flag,0,$thread); \""); 

open(DONEFILES, ">>/tmp/done_file_list");
print DONEFILES "$file\n";
close DONEFILES;
