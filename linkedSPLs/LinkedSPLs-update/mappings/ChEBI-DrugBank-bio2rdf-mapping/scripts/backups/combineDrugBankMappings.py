# combineDrugBankMappings.py
# 
# Combine FDA UNII to DrugBank mappings created bu INCHI key and synonymn matching
#
# Author: Richard Boyce
#

FDA_DRUGBANK_BY_INCHI = "PT-UNIIs-Drugbank-09042014.txt"
FDA_DRUGBANK_BY_SYN = "SYNONYMNS-UNIIs-Drugbank-09042014.txt"
FDA_DRUGBANK_COMBINED = "FDA_DRUGBANK_INCHI_AND_SYNONYMNS_09042014.tsv"

f = open(FDA_DRUGBANK_BY_INCHI, 'r')
buf = f.read()
f.close()
ptDic = {}
for ln in buf.split("\n"):
    ln = ln.strip()
    if ln == "":
        break

    (uniiPT, unii, drugbankPT, drugbankID) = ln.split("\t")[0:4]
    drugbankID = drugbankID.replace("http://www.drugbank.ca/drugs/","")
    ptDic[uniiPT] = [uniiPT, unii, drugbankPT, drugbankID]


newMappings = {}
f = open(FDA_DRUGBANK_BY_SYN, 'r')
buf = f.read()
f.close()
l = buf.split("\n")
for ln in l[1:]:
    ln = ln.strip()
    if ln == "":
        break

    (FDA_Preferred_Term, FDA_synonymn, UNII, Drugbank_drug, drugbank_id) = ln.split("\t")

    if not ptDic.has_key(FDA_Preferred_Term):
        if newMappings.get(FDA_Preferred_Term):
            newMappings[FDA_Preferred_Term].append((FDA_synonymn, UNII, Drugbank_drug, drugbank_id))
        else:
            newMappings[FDA_Preferred_Term] = [(FDA_synonymn, UNII, Drugbank_drug, drugbank_id)]

f = open(FDA_DRUGBANK_COMBINED, 'w')
f.write("\t".join(["FDA_Preferred_Term","UNII","Drugbank_drug","drugbank_id","mapping_type","synonymns_used"]) + "\n")
for k,v in ptDic.iteritems():
    f.write("\t".join(v + ["INCHI","N/A"]) + "\n")

(FDA_SYNONYMN, UNII, DRUGBANK_DRUG, DRUGBANK_ID) = range(0,4)
for k,v in newMappings.iteritems():
    f.write("\t".join([k, v[0][UNII], v[0][DRUGBANK_DRUG], v[0][DRUGBANK_ID]] + ["SYN","|".join([x[FDA_SYNONYMN] for x in v])]) + "\n")

f.close()

