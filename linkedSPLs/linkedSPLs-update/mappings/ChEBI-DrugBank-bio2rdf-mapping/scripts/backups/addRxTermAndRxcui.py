'''
Created 10/27/2014

@authors: Yifan Ning

@summary: using restfull api, get rxnorm term by rxcui and merge term and rxcui to preferred substance and drugbank URI

input: PS-to-RxNorm-mapping.txt and INCHI-OR-Syns-OR-Name.txt
output: preferred substance, UNII, drugbank name,  drug bank id, rxnorm uri and rxnorm term

'''

import urllib2
from xml.dom.minidom import parseString
import os, sys
import codecs

RXNORM_BASE="http://rxnav.nlm.nih.gov/REST/"
HEADERS = { 'Accept' : 'application/json'}

PS_DRUGBANK = "../UNII-data/INCHI-OR-Syns-OR-Name-09162014.txt"
PS_DRUGBANK_AND = "../UNII-data/INCHI-AND-Syns-OR-Name-09162014.txt"
PS_RxCUI = "../../RxNORM-mapping/PS-to-Rxcui-mapping-10272014.txt"

OUTPUTS = "merge-error.log"
OUTPUTS_OR = "../UNII-data/PT-UNII-Name-DrugBank-Rxnorm-OR-mappings-10272014.tsv"
OUTPUTS_AND = "../UNII-data/PT-UNII-Name-DrugBank-Rxnorm-AND-mappings-10272014.tsv"

dict_RxCUIs = {}


if len(sys.argv) > 1:
    mapping_mode = str(sys.argv[1])
else:
    print "Usage: addRxTermAndRxcui.py <merge mode>(0: merge inchi-name-synomyns or mappings with rxcui and rxnorm type), 1: merge Inchi-name-synomyns and mappings with rxcui and rxnorm type))"
    sys.exit(1)


if mapping_mode == "0":
    OUTPUTS = OUTPUTS_OR
elif mapping_mode == "1":
    PS_DRUGBANK = PS_DRUGBANK_AND
    OUTPUTS = OUTPUTS_AND
else:
    print "unknown mapping mode - please check if the mapping mode is 0 or 1"


for line in codecs.open(PS_RxCUI, 'r', encoding='utf-8').readlines():
    row = line.split('|')
    if len(row) == 2:
        dict_RxCUIs[row[0]] = row[1]


# add header

f = codecs.open(OUTPUTS, 'w', encoding='utf-8')

header =  "FDA preferred term" + '\t' + "UNII" + '\t' + "Drugbank Name" + '\t' + "Drugbank Id" + '\t' + "Rxcui"  + '\t' +  "RxTerm" + '\n'
f.write(header)

for line in codecs.open(PS_DRUGBANK, 'r', encoding='utf-8').readlines():

    rxcui = ""
    RxTerm = ""    
    row = line.split('\t')

    # match by FDA preferred term to get Rxcui
    # request Rxterm by rxcui
    if len(row)==4 and dict_RxCUIs.has_key(row[0]):
        rxcui = dict_RxCUIs[row[0]].strip('http://purl.bioontology.org/ontology/RXNORM/')
        
        requestURL = RXNORM_BASE+'rxcui/'+ rxcui.strip('\n') + '/properties'

        file = urllib2.urlopen(requestURL)
        data = file.read()
        file.close()
        dom = parseString(data)

        termXmlTag = dom.getElementsByTagName('tty')

        if termXmlTag:
            RxTerm = unicode(termXmlTag[0].toxml().strip('<tty>').strip('</tty>'))
    output = line.strip('\n') + '\t' + rxcui.strip('\n') + '\t' + RxTerm + '\n'

    f.write(output) 
 
    
