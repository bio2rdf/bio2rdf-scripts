'''
Created 09/04/2014

@authors: Yifan Ning

@summary: add drugbank base uri to mappings of preferred term and drugbank uri
'''

import os, sys


DRUGBANK_BIO2RDF = "http://bio2rdf.org/drugbank:"
DRUGBANK_CA = "http://www.drugbank.ca/drugs/"

PT_DRUGBANK = "../UNII-data/INCHI-OR-Syns-OR-Name.txt"

if len(sys.argv) > 1:
   PT_DRUGBANK = str(sys.argv[1])
else:
    print "Usage: addBio2rdf_UNII_to_DrugBank.py <UNII-data/INCHI-OR-Syns-OR-Name.txt>"
    sys.exit(1)


for line in open(PT_DRUGBANK,'r').readlines():

   columns = line.split('\t')
   
   if len(columns):
      PT = columns[0]
      DBid = columns[3]
      
      DBbio2rdf = DRUGBANK_BIO2RDF + DBid
      DBca = DRUGBANK_CA + DBid
      
      print (PT + '\t' + DBca + '\t' + DBbio2rdf).replace("\n","").replace("\r","")
      #out =  (line+bio2rdf).replace("\r\n","")
      #print out.replace("|","\t")
	
