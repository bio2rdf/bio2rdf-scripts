###############################################################################
#Copyright (C) Marc-Alexandre Nolin
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

# dbpedia2id.sh

cd /media/twotb/bio2rdf/data/dbpedia

cat infobox_en.nt | grep -e "property/meshnumberProperty>" > meshnumberProperty.n3
#exit
cat infobox_en.nt | grep -e "property/meshid>" > meshid.n3
cat infobox_en.nt | grep -e "property/meshname>" > meshname.n3
cat infobox_en.nt | grep -e "property/meshnumber>" > meshnumber.n3
cat infobox_en.nt | grep -e "property/iupacname>" > iupacname.n3
cat infobox_en.nt | grep -e "property/iupacName>" > iupacName.n3
cat infobox_en.nt | grep -e "property/mgiid>" > mgi.n3
cat infobox_en.nt | grep -e "property/symbol>" > symbol.n3
cat infobox_en.nt | grep -e "property/scop>" > scop.n3
cat infobox_en.nt | grep -e "property/interpro>" > interpro.n3
cat infobox_en.nt | grep -e "property/hgncid>" > hgnc.n3
cat infobox_en.nt | grep -e "property/kegg>" > kegg.n3
cat infobox_en.nt | grep -e "property/pdb>" > pdb.n3
cat infobox_en.nt | grep -e "property/pfam>" > pfam.n3
cat infobox_en.nt | grep -e "property/prosite>" > prosite.n3
#exit
cat infobox_en.nt | grep -e "property/inchi>" > inchi.n3
cat infobox_en.nt | grep -e "property/smiles>" > smiles.n3
#exit
cat infobox_en.nt | grep -e "property/casNumber>" > cas.n3
cat infobox_en.nt | grep -e "property/chebi>" > chebi.n3
cat infobox_en.nt | grep -e "property/ecnumber>" > ec.n3
cat infobox_en.nt | grep -e "property/entrezgene>" > entrezgene.n3
cat infobox_en.nt | grep -e "property/omim>" > omim.n3
cat infobox_en.nt | grep -e "property/pubchem>" > pubchem.n3
cat infobox_en.nt | grep -e "property/refseq>" > refseq.n3
cat infobox_en.nt | grep -e "property/uniprot>" > uniprot.n3

cat infobox_en.nt | grep -e "property/drugbank>" > drugbank.n3

cat *.n3 > dbpedia.n3
