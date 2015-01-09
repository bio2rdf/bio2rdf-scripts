'''
Created 07/23/2014

@authors: Yifan Ning

@summary: convert mappings that mix with Dron to Rxcui and with ChEBI to Rxcui
          to three columnes (Dron - ChEBI - Rxcui) txt files 
'''


import os, sys, re


if len(sys.argv) > 1:
    INPUT = str(sys.argv[1])
else:
    print "Usage: cleanData.py <PATH TO dron-to-chebi-and-rxnorm.txt>"
    sys.exit(1)

f = open(INPUT,"r")
buf = f.read()
f.close()

for line in buf.split("\n"):
    
    if not line:
        continue

    if "CHEBI_" in line:
        chebi = re.findall(r'CHEBI_[0-9]+', line)[0]
    else:
        chebi = ""
    
    if "DRON_" in line:
        dron = re.findall(r'DRON_[0-9]+',line)[0]
    else:
        dron = ""

    if "rxcui=" in line:
        rxcui = re.findall(r'rxcui=\"[0-9]+',line)[0]
    else:
        rxcui = ""

    print str(dron) + "|" + str(chebi) + "|http://purl.bioontology.org/ontology/RXNORM/" + str(rxcui).replace("rxcui=\"","")
        

