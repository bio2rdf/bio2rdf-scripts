#!/usr/bin/perl
# dc:title       remove_all_information.pl
# dc:creator     manolin at gmail.com
# dc:modified    2010-07-28
# dc:description Remove all triple in the Virtuoso Endpoint at the given port
 
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

# perl remove_all_information.pl <port> <password> 

$port = shift;
$password = shift;

print system("isql $port -P $password verbose=on banner=off prompt=off echo=ON errors=stdout exec=\"rdf_global_reset(); \""); 
