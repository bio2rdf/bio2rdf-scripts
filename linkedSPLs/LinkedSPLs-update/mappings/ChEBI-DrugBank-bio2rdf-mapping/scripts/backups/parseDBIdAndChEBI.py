'''
Created 03/20/2014

@authors: Yifan Ning

@summary: parse chebi and drugbank_id from drugbank.xml
          output terms: FDA Preferred Term, ChEBI URI, Drugbank URI
          output file: PT-ChEBI-Drugbank-03202014.txt
'''

import xml.etree.ElementTree as ET
import os, sys

DRUGBANK_XML = "drugbank.xml"
UNIIS_RECORDS = "FDA_UNII_to_ChEBI_09042014.txt"

CHEBI_URL = "http://purl.obolibrary.org/obo/CHEBI_"
DRUGBANK_BIO2RDF = "http://bio2rdf.org/drugbank:"
DRUGBANK_CA = "http://www.drugbank.ca/drugs/"

NS = "{http://www.drugbank.ca}" 

dict_chebi_dbid = {}

'''
data structure of drugbank.xml
	
<drug>...
<drugbank-id>...
<name>Simvastatin</name>
<external-identifiers>
    <external-identifier>
      <resource>ChEBI</resource>
      <identifier>6427</identifier>
    </external-identifier>
'''

#print out mappings of chebi and drugbank id
def parseDbIdAndChebi(root):
    for drug in root.iter(tag=NS + "drug"):
        subId = drug.find(NS + "drugbank-id")
        
        if subId == None:
            continue
        else:
            drugbankid = unicode(subId.text)
            drugbankName = unicode(drug.find(NS + "name").text)   

            for exIdens in drug.iter(NS + "external-identifiers"):
                for exIden in exIdens.iter(NS + "external-identifier"):

                    resource = exIden.find(NS + "resource")
                    if resource == None:
                        continue
                    elif resource.text == "ChEBI":
                        childIdenti = exIden.find(NS + "identifier") 
                        if childIdenti == None:
                            continue
                        else:
                            chebiId = unicode(CHEBI_URL + childIdenti.text)
                            dict_chebi_dbid[chebiId] = (drugbankid,drugbankName)
                           
tree = ET.parse(DRUGBANK_XML)
root = tree.getroot()
parseDbIdAndChebi(root)

for line in open(UNIIS_RECORDS,'r').readlines():
    row = line.split('\t')
    chebi = row[1].strip()

    if dict_chebi_dbid.has_key(chebi):
        drugbankid = dict_chebi_dbid[chebi][0]
        drugbankName = dict_chebi_dbid[chebi][1]
        output = row[0] +'\t'+ drugbankName +'\t'+ chebi +'\t'+  DRUGBANK_CA+drugbankid +'\t'+ DRUGBANK_BIO2RDF+drugbankid
        print output.encode('utf-8').strip()

