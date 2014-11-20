'''
Created 03/20/2014

@authors: Yifan Ning

@summary: using restfull api, get rxcui by preferred substance
          output: preferred substance, rxnorm uri
'''

# TODO: document like the other scripts with author and purpose of the script
# TODO: document the expected result of the RxNorm API call (i.e., substance or active ingredient RxCui returned because we expect an exact string match with the FDA UNII preferred name). Note this as documentation:
# - Method: an exact string match (case insensitive) to the RxNorm ingredient string from the official FDA substance list (the same methods used by RxNorm folks, see
# <http://www.nlm.nih.gov/research/umls/rxnorm/docs/2012/rxnorm_doco_full_2012-3.html#s8_0>)


import urllib2
from xml.dom.minidom import parseString
import os, sys


RXNORM_BASE="http://rxnav.nlm.nih.gov/REST/"
HEADERS = { 'Accept' : 'application/json'}

# manually load 
input = "FDAPreferredSubstance_03132014.txt"
#input = "data/subset_00"
#input = "data/subset_01"
#input = "data/subset_02"
#input = "data/subset_03"
#input = "data/subset_04"


if len(sys.argv) > 1:
    input = str(sys.argv[1])
else:
    print "Usage: getPreferredSubstanceRxNORM_Mapping.py <FDAPreferredSubstance.txt>"
    sys.exit(1)


for line in open(input,'r').readlines():
    
    #unicodeps = urllib2.quote(line)
    
    rxcuiURL = RXNORM_BASE+'rxcui?name='+line.replace(' ','%20')

    file = urllib2.urlopen(rxcuiURL)
    
    data = file.read()

    file.close()
    dom = parseString(data)

    
    rxnormid = dom.getElementsByTagName('rxnormId')

    if rxnormid:
        xmlTag = rxnormid[0].toxml()
    
        xmlData=xmlTag.replace('<rxnormId>','').replace('</rxnormId>','')

        output = line + "|http://purl.bioontology.org/ontology/RXNORM/" +xmlData
        print unicode(output.replace('\n',''))

#    else:
#        print unicode('not rxnormID for ' + line)



