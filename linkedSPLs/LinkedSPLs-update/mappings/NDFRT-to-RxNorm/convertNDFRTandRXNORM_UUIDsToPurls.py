'''
Created 03/20/2014

@authors: Yifan Ning

@summary: add rxnorm and NDFRT base URI to rxcui from UMLS RXNCONSO.rrf

'''


import sys

RXNOEM_NDFRT = "rxnorm-to-ndfrt-chemical-ingredient-mapping-10292014.txt"

if len(sys.argv) > 1:
    RXNOEM_NDFRT = str(sys.argv[1])
else:
    print "Usage: convertNDFRTandRXNORM_UUIDsToPurls.py <rxnorm-to-ndfrt-chemical-ingredient-mapping.txt>"
    sys.exit(1)


f = open(RXNOEM_NDFRT,"r")
buf = f.read()
f.close()

l = buf.split("\n")
for elt in l[1:]:
    if elt == "":
        break 
    (rxcui,ncui,label) = elt.split("|")
    print "http://purl.bioontology.org/ontology/RXNORM/%s	http://purl.bioontology.org/ontology/NDFRT/%s	%s" % (rxcui,ncui,label)


