###############################################################################
#Copyright (C) Marc-Alexandre Nolin, Jose Cruz-Toledo
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


#specify the location of the downloaded files
DOWNLOAD_DIR=/media/twotb/bio2rdf/data/dbpedia
#specify the location of the output
OUTPUT_DIR=/media/twotb/bio2rdf/n3/dbpedia


cd $DOWNLOAD_DIR


#uncompress the file
bunzip2 infobox_properties_en.nt.bz2

cat infobox_properties_en.nt | grep -e "property/meshnumberProperty>" > meshnumberProperty.nt
cat infobox_properties_en.nt | grep -e "property/meshid>" > meshid.nt
cat infobox_properties_en.nt | grep -e "property/meshname>" > meshname.nt
cat infobox_properties_en.nt | grep -e "property/meshnumber>" > meshnumber.nt
cat infobox_properties_en.nt | grep -e "property/iupacname>" > iupacname.nt
cat infobox_properties_en.nt | grep -e "property/iupacName>" > iupacName.nt
cat infobox_properties_en.nt | grep -e "property/mgiid>" > mgi.nt
cat infobox_properties_en.nt | grep -e "property/symbol>" > symbol.nt
cat infobox_properties_en.nt | grep -e "property/scop>" > scop.nt
cat infobox_properties_en.nt | grep -e "property/interpro>" > interpro.nt
cat infobox_properties_en.nt | grep -e "property/hgncid>" > hgnc.nt
cat infobox_properties_en.nt | grep -e "property/kegg>" > kegg.nt
cat infobox_properties_en.nt | grep -e "property/pdb>" > pdb.nt
cat infobox_properties_en.nt | grep -e "property/pfam>" > pfam.nt
cat infobox_properties_en.nt | grep -e "property/prosite>" > prosite.nt
cat infobox_properties_en.nt | grep -e "property/inchi>" > inchi.nt
cat infobox_properties_en.nt | grep -e "property/smiles>" > smiles.nt
cat infobox_properties_en.nt | grep -e "property/casNumber>" > cas.nt
cat infobox_properties_en.nt | grep -e "property/chebi>" > chebi.nt
cat infobox_properties_en.nt | grep -e "property/ecnumber>" > ec.nt
cat infobox_properties_en.nt | grep -e "property/entrezgene>" > entrezgene.nt
cat infobox_properties_en.nt | grep -e "property/omim>" > omim.nt
cat infobox_properties_en.nt | grep -e "property/pubchem>" > pubchem.nt
cat infobox_properties_en.nt | grep -e "property/refseq>" > refseq.nt
cat infobox_properties_en.nt | grep -e "property/uniprot>" > uniprot.nt
cat infobox_properties_en.nt | grep -e "property/drugbank>" > drugbank.nt
cat *.nt > dbpedia_out.nt
#gzip the file
gzip -c dbpedia_out.nt > dbpedia_out.nt.gz
#remove the .nt files
rm *.nt
#move the output file
mv dbpedia_out.nt.gz $OUTPUT_DIR
