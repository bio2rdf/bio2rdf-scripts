# combineMappings.py
# 
# Combine RxNorm, FDA, DrugBank, and ChEBI identifiers into one file
#
# Author: Richard Boyce
#

FDA_DRUGBANK = "FDA_UNII_to_DrugBank_03132014.txt"
FDA_CHEBI = "FDA_UNII_to_ChEBI_03132014.txt"
FDA_RXNORM = "PreferredSubstance-to-Rxcui-mapping_03132014.txt"
FDA_UNII = "FDAPreferredSubstanceToUNII_03132014.txt"
FDA_RXNORM_DRUGBANK_CHEBI = "FDA_RXNORM_DRUGBANK_CHEBI_COMBINED_MAPPING_04042014_TRIADS.tsv"

f = open(FDA_RXNORM,'r')
buf = f.read()
f.close()
l = buf.split("\n")
fdaD = {}
for elt in l:
    if not elt.strip():
        break
    
    (pt,rxpurl) = elt.split("\t")
    fdaD[pt] = ["None",rxpurl.replace("http://purl.bioontology.org/ontology/RXNORM/","").strip(),"None","None","None"]

db = f = open(FDA_UNII,'r')
buf = f.read()
f.close()
l = buf.split("\n")
for elt in l:
    if not elt.strip():
        break
    
    (pt,unii) = elt.split("\t")
    if fdaD.get(pt):
        fdaD[pt][0] = unii.strip()


db = f = open(FDA_DRUGBANK,'r')
buf = f.read()
f.close()
l = buf.split("\n")
for elt in l:
    if not elt.strip():
        break
    
    (pt,dbname,dbpurl) = elt.split("|")
    if fdaD.get(pt):
        fdaD[pt][2] = dbname
        fdaD[pt][3] = dbpurl.replace("http://www.drugbank.ca/drugs/","").strip()
    

db = f = open(FDA_CHEBI,'r')
buf = f.read()
f.close()
l = buf.split("\n")
for elt in l:
    if not elt.strip():
        break
    
    (pt,dbname,chebipurl) = elt.split("|")
    if fdaD.get(pt):
        fdaD[pt][4] = chebipurl.replace("http://purl.obolibrary.org/obo/","").strip()

f = open(FDA_RXNORM_DRUGBANK_CHEBI,'w')
f.write("FDA_PreferredTerm\tFDA_UNII\tRxNorm_CUI\tDrugBank_Name\tDrugBank_CUI\tChEBI_CUI\n")
for k,v in fdaD.iteritems():
    ln = "%s\t%s\n" % (k ,"\t".join(v))
    f.write(ln)
f.close()
