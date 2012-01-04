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
