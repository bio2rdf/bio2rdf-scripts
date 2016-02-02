'''
Created 03/20/2014

@authors: Yifan Ning

@summary: parse Molecular Formula and drugbank_id from drugbank.xml
          then parse MF(Molecular Formula), FDA Preferred Term and UNNI from UNNIs records
          match the results from drugbank and results of parse UNNIs records
          output terms: FDA Preferred Term, UNII, Drugbank URI
          output file: PT-UNIIs-Drugbank-byMF-03202014.txt
'''

import xml.etree.ElementTree as ET
import os, sys

DRUGBANK_XML = "drugbank.xml"
UNIIS_RECORDS = "UNIIs 25Jan2014 Records.txt"
NS = "{http://drugbank.ca}" 

DRUGBANK_BIO2RDF = "http://bio2rdf.org/drugbank:"
DRUGBANK_CA = "http://www.drugbank.ca/drugs/"
dict_ickey_dbid = {}

'''
<property>
      <kind>Molecular Formula</kind>
      <value>C4H6N4O3S2</value>
      <source>ChemAxon</source>
    </property>

'''


def parseDbIdAndMF(root):
    for drug in root.iter(tag=NS + "drug"):
        dbid = drug.find(NS + "drugbank-id")
        
        if dbid == None:
            continue
        else:
            drugbankid = dbid.text
            for subProp in drug.iter(NS + "property"):
                msKind = subProp.find(NS + "kind")
                if msKind == None:
                    continue
                elif msKind.text == "Molecular Formula":
                    msValue = subProp.find(NS + "value")
                    if msValue == None:
                        continue
                    else:
                        #print drugbankid + '\t' + subValue.text[9:]
                        ms = msValue.text
                        dict_ickey_dbid [ms] = drugbankid

tree = ET.parse(DRUGBANK_XML)
root = tree.getroot()
parseDbIdAndMF(root)

#read mapping file that contains UNII PT MF

for line in open(UNIIS_RECORDS,'r').readlines():
    row = line.split('\t')
    mf = row[2]
    if len(mf) == 0:
        continue

    if dict_ickey_dbid.has_key(mf):
        drugbankid = dict_ickey_dbid[mf]
        output = row[1] +'\t'+ row[0] +'\t'+ DRUGBANK_CA + drugbankid +'\t'+ DRUGBANK_BIO2RDF + drugbankid
        print output.encode('utf-8').strip()
        


