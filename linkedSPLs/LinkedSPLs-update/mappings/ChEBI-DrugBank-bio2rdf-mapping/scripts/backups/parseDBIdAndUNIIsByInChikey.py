'''
Created 03/20/2014

@authors: Yifan Ning

@summary: parse inchikey and drugbank_id from drugbank.xml then parse
          inchikey, FDA Preferred Term and UNII from UNIIs records
          match the results from drugbank and results of parse UNIIs
          records output terms: FDA Preferred Term, UNII, Drugbank URI
          output file: PT-UNIIs-Drugbank-03202014.txt
'''

from lxml import etree
from lxml.etree import XMLParser, parse
import os, sys

DRUGBANK_XML = "drugbank.xml"
UNIIS_RECORDS = "UNIIs 27Jun2014 Records.txt"
NS = "{http://www.drugbank.ca}" 

DRUGBANK_BIO2RDF = "http://bio2rdf.org/drugbank:"
DRUGBANK_CA = "http://www.drugbank.ca/drugs/"
dict_ickey_dbid = {}

'''
data structure of drugbank.xml
	
</drug><drug type="small molecule" created="2005-06-13 07:24:05 -0600"
updated="2013-09-16 17:11:29 -0600" version="4.0">
  <drugbank-id>DB00641</drugbank-id>
  <name>Simvastatin</name>
  <calculated-properties>
  <property>
      <kind>InChIKey</kind>
      <value>InChIKey=RYMZZMVNJRMUDD-HGQWONQESA-N</value>
      <source>ChemAxon</source>
    </property>
'''


#get mappings of inchikey and drugbank id
def parseDbIdAndInChiKey(root):
    for childDrug in root.iter(tag=NS + "drug"):
        subId = childDrug.find(NS + "drugbank-id")
        
        if subId == None:
            continue
        else:
            drugbankid = subId.text
            drugbankName = unicode(childDrug.find(NS + "name").text)   

            for subProp in childDrug.iter(NS + "property"):
                subKind = subProp.find(NS + "kind")
                if subKind == None:
                    continue
                elif subKind.text == "InChIKey":
                    subValue = subProp.find(NS + "value")
                    if subValue == None:
                        continue
                    else:
                        #print drugbankid + '\t' + subValue.text[9:]
                        ikey = subValue.text[9:]
                        dict_ickey_dbid [ikey] = (drugbankid,drugbankName)

p = XMLParser(huge_tree=True)
tree = parse(DRUGBANK_XML,parser=p)
root = tree.getroot()

parseDbIdAndInChiKey(root)

#print dict_ickey_dbid

#read mapping file that contains UNII PT INCHIKEY

for line in open(UNIIS_RECORDS,'r').readlines():
    row = line.split('\t')
    inchikey = row[4]
    if len(inchikey) == 0:
        continue
    #print "mapping inchikey:" + inchikey

    if dict_ickey_dbid.has_key(inchikey):
        drugbankid = dict_ickey_dbid[inchikey][0]
        drugbankName = dict_ickey_dbid[inchikey][1]
        output = row[1] +'\t'+ row[0] +'\t'+ drugbankName +'\t'+  DRUGBANK_CA + drugbankid +'\t'+ DRUGBANK_BIO2RDF + drugbankid
        print output.encode('utf-8').strip()
        



