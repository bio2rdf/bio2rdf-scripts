# dc:title      homologene2n3.pl
# dc:creator    francoisbelleau at yahoo.ca
# dc:modified   2009-03-31
# dc:description Convert homologene tabulated file to N3 format
# perl homologene2n3.pl /bio2rdf/data/homologene/homologene.data > homologene.rdf    
 
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
 

$fichier = shift;
$max = 100;
$max = 100000000;
$nombre = 0;

#homologene.data is a tab delimited file containing the following columns:

#1) HID (HomoloGene group id)
#2) Taxonomy ID
#3) Gene ID
#4) Gene Symbol
#5) Protein gi
#6) Protein accession

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

	$ligne =~ /(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)$/;
	$v1 = $1;
	$v2 = $2;
	$v3 = $3;
	$v4 = $4;
	$v41 = lc($4);
	$v5 = $5;
	$v6 = $6;

print <<EOF;
<http://bio2rdf.org/homologene:$v1> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/homologene#Cluster> .
<http://bio2rdf.org/homologene:$v1> <ttp://purl.org/dc/elements/1.1/iidentifier> "homologene:$v1" .
<http://bio2rdf.org/homologene:$v1> <http://www.w3.org/2000/01/rdf-schema#label> "[homologene:$v1]" .
<http://bio2rdf.org/homologene:$v1> <http://bio2rdf.org/bio2rdf#url> "http://www.ncbi.nlm.nih.gov/homologene/$v1" .

<http://bio2rdf.org/geneid:$v3> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ncbi#Gene> .
<http://bio2rdf.org/geneid:$v3> <http://purl.org/dc/elements/1.1/identifier> "geneid:$v3" .
<http://bio2rdf.org/geneid:$v3> <http://bio2rdf.org/bio2rdf#xTaxon> <http://bio2rdf.org/taxon:$v2> .
<http://bio2rdf.org/geneid:$v3> <http://bio2rdf.org/bio2rdf#url> "http://www.ncbi.nlm.nih.gov/gene/$v3" .

<http://bio2rdf.org/taxon:$v2> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ncbi#Taxonomy> .
<http://bio2rdf.org/taxon:$v2> <http://purl.org/dc/elements/1.1/identifier> "taxon:$v2" .
<http://bio2rdf.org/taxon:$v2> <http://bio2rdf.org/bio2rdf#url> "http://www.ncbi.nlm.nih.gov/taxonomy/$v2" .

<http://bio2rdf.org/homologene:$v2-$v3> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/homologene#Taxon-Gene> .
<http://bio2rdf.org/homologene:$v2-$v3> <http://purl.org/dc/elements/1.1/identifier> "homologene:$v2-$v3" .
<http://bio2rdf.org/homologene:$v2-$v3> <http://purl.org/dc/elements/1.1/title> "$v4" .
<http://bio2rdf.org/homologene:$v2-$v3> <http://www.w3.org/2000/01/rdf-schema#label> "$v4 [homologene:$v2-$v3]" .
<http://bio2rdf.org/homologene:$v2-$v3> <http://bio2rdf.org/bio2rdf#partOf> <http://bio2rdf.org/homologene:$v1> .
<http://bio2rdf.org/homologene:$v2-$v3> <http://bio2rdf.org/bio2rdf#xTaxon> <http://bio2rdf.org/taxon:$v2> .
<http://bio2rdf.org/homologene:$v2-$v3> <http://bio2rdf.org/bio2rdf#xGeneID> <http://bio2rdf.org/geneid:$v3> .
<http://bio2rdf.org/homologene:$v2-$v3> <http://bio2rdf.org/bio2rdf#xSymbol> <http://bio2rdf.org/symbol:$v4> .
<http://bio2rdf.org/homologene:$v2-$v3> <http://bio2rdf.org/bio2rdf#xGene> <http://bio2rdf.org/gene:$v2-$v41> .
<http://bio2rdf.org/homologene:$v2-$v3> <http://bio2rdf.org/bio2rdf#xGI> <http://bio2rdf.org/gi:$v5> .
<http://bio2rdf.org/homologene:$v2-$v3> <http://bio2rdf.org/bio2rdf#xProteinID> <http://bio2rdf.org/proteinid:$v6> .


EOF

}

close(ENTREE);

exit;


