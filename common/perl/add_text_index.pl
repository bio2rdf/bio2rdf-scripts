#!/usr/bin/perl
# dc:title       add_text_index.pl
# dc:creator     francoisbelleau at yahoo.ca and manolin at gmail.com
# dc:modified    2010-07-28
# dc:description Create the free text index on a specified endpoint
 
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

# perl add_text_index.pl <port> <password> 

$port = shift;
$password = shift;

print system("isql $port -P $password verbose=on banner=off prompt=off echo=ON errors=stdout exec=\"DB.DBA.RDF_OBJ_FT_RULE_ADD(null, null, 'All'); DB.DBA.vt_inc_index_db_dba_rdf_obj(); checkpoint; \""); 
