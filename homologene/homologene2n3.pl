###############################################################################
#Copyright (C) 2011 Alison Callahan, Francois Belleau
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
<http://bio2rdf.org/homologene:$v1> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/homologene_vocabulary:Cluster> .
<http://bio2rdf.org/homologene:$v1> <ttp://purl.org/dc/elements/1.1/iidentifier> "homologene:$v1" .
<http://bio2rdf.org/homologene:$v1> <http://www.w3.org/2000/01/rdf-schema#label> "[homologene:$v1]" .
<http://bio2rdf.org/homologene:$v1> <http://bio2rdf.org/homologene_vocabulary:url> "http://www.ncbi.nlm.nih.gov/homologene/$v1" .

<http://bio2rdf.org/geneid:$v3> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/homologene_vocabulary:Gene> .
<http://bio2rdf.org/geneid:$v3> <http://purl.org/dc/elements/1.1/identifier> "geneid:$v3" .
<http://bio2rdf.org/geneid:$v3> <http://bio2rdf.org/homologene_vocabulary:xTaxon> <http://bio2rdf.org/taxon:$v2> .
<http://bio2rdf.org/geneid:$v3> <http://bio2rdf.org/homologene_vocabulary:url> "http://www.ncbi.nlm.nih.gov/gene/$v3" .

<http://bio2rdf.org/taxon:$v2> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/homologene_vocabulary:Taxonomy> .
<http://bio2rdf.org/taxon:$v2> <http://purl.org/dc/elements/1.1/identifier> "taxon:$v2" .
<http://bio2rdf.org/taxon:$v2> <http://bio2rdf.org/homologene_vocabulary:url> "http://www.ncbi.nlm.nih.gov/taxonomy/$v2" .

<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/homologene_vocabulary:Taxon-Gene> .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://purl.org/dc/elements/1.1/identifier> "homologene_resource:$v2-$v3" .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://purl.org/dc/elements/1.1/title> "$v4" .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://www.w3.org/2000/01/rdf-schema#label> "$v4 [homologene_resource:$v2-$v3]" .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://bio2rdf.org/homologene_vocabulary:partOf> <http://bio2rdf.org/homologene:$v1> .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://bio2rdf.org/homologene_vocabulary:xTaxon> <http://bio2rdf.org/taxon:$v2> .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://bio2rdf.org/homologene_vocabulary:xGeneID> <http://bio2rdf.org/geneid:$v3> .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://bio2rdf.org/homologene_vocabulary:xSymbol> <http://bio2rdf.org/symbol:$v4> .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://bio2rdf.org/homologene_vocabulary:xGene> <http://bio2rdf.org/gene:$v2-$v41> .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://bio2rdf.org/homologene_vocabulary:xGI> <http://bio2rdf.org/gi:$v5> .
<http://bio2rdf.org/homologene_resource:$v2-$v3> <http://bio2rdf.org/homologene_vocabulary:xProteinID> <http://bio2rdf.org/proteinid:$v6> .


EOF

}

close(ENTREE);

exit;


