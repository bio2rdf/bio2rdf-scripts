'''
Created 08/18/2014

@authors: Yifan Ning

@summary: Read csv file
Inputs: mappings of dron, rxcui, omop concept id
Outputs: RDF/XML graph for clinical drug

'''

import sys
import time
sys.path = sys.path + ['.']

import re, codecs, uuid, datetime
import urllib2
import urllib
import traceback
import csv
import difflib
from rdflib import Graph, BNode, Literal, Namespace, URIRef, RDF, RDFS
from sets import Set

OUT_FILE = "clinicalDrugSub-in-rdf.xml"
CLINICALDRUG_BASE = "http://bio2rdf.org/linkedspls:"
RXNORM_BASE = "http://purl.bioontology.org/ontology/RXNORM/"
OHDSI_BASE = "http://purl.org/net/ohdsi#"
DRON_BASE = "http://purl.obolibrary.org/obo/"

class DictItem:

   def __init__(self, dron, rxcui, omop):
      self.dron = str(dron)
      self.rxcui = str(rxcui)
      self.omop = str(omop)


data_set = csv.DictReader(open("mergedClinicalDrug.csv","rb"), delimiter='\t')
drugsL = []

## convert data from csv to dict 

for item in data_set:
    if item["rxcui"] and item["dron"] and item["omop"]:
        drugRow = DictItem(item["dron"], item["rxcui"], item["omop"])
        drugsL.append(drugRow)

## set up RDF graph
# identify namespaces for other ontologies to be used                                                                                    
dcterms = Namespace("http://purl.org/dc/terms/")
pav = Namespace("http://purl.org/pav")
dctypes = Namespace("http://purl.org/dc/dcmitype/")
linkedspls_vocabulary = Namespace('http://bio2rdf.org/linkedspls_vocabulary:')
sio = Namespace('http://semanticscience.org/resource/')
oa = Namespace('http://www.w3.org/ns/oa#')
cnt = Namespace('http://www.w3.org/2011/content#')
gcds = Namespace('http://www.genomic-cds.org/ont/genomic-cds.owl#')

siocns = Namespace('http://rdfs.org/sioc/ns#')
swande = Namespace('http://purl.org/swan/1.2/discourse-elements#')
dikbD2R = Namespace('http://dbmi-icode-01.dbmi.pitt.edu/dikb/vocab/resource/')
linkedspls = Namespace('file:///home/rdb20/Downloads/d2rq-0.8.1/linkedspls-dump.nt#structuredProductLabelMetadata/')
ncbit = Namespace('http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#')
dikbEvidence = Namespace('http://dbmi-icode-01.dbmi.pitt.edu/dikb-evidence/DIKB_evidence_ontology_v1.3.owl#')
ndfrt = Namespace('http://purl.bioontology.org/ontology/NDFRT/')
activemoiety = Namespace('http://purl.org/net/nlprepository/spl-active-moiety')

graph = Graph()

graph.namespace_manager.reset()
graph.namespace_manager.bind("dcterms", "http://purl.org/dc/terms/")
graph.namespace_manager.bind("pav",  "http://purl.org/pav");
graph.namespace_manager.bind("dctypes", "http://purl.org/dc/dcmitype/")

graph.namespace_manager.bind('linkedspls_vocabulary', 'http://bio2rdf.org/linkedspls_vocabulary:')

graph.namespace_manager.bind('sio', 'http://semanticscience.org/resource/')
graph.namespace_manager.bind('oa', 'http://www.w3.org/ns/oa#')
graph.namespace_manager.bind('cnt', 'http://www.w3.org/2011/content#')
graph.namespace_manager.bind('gcds','http://www.genomic-cds.org/ont/genomic-cds.owl#')

graph.namespace_manager.bind('siocns','http://rdfs.org/sioc/ns#')
graph.namespace_manager.bind('swande','http://purl.org/swan/1.2/discourse-elements#')
graph.namespace_manager.bind('dikbD2R','http://dbmi-icode-01.dbmi.pitt.edu/dikb/vocab/resource/')

graph.namespace_manager.bind('linkedspls','file:///home/rdb20/Downloads/d2rq-0.8.1/linkedspls-dump.nt#structuredProductLabelMetadata/')
graph.namespace_manager.bind('ncbit','http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#')
graph.namespace_manager.bind('dikbEvidence','http://dbmi-icode-01.dbmi.pitt.edu/dikb-evidence/DIKB_evidence_ontology_v1.3.owl#')
graph.namespace_manager.bind('ndfrt','http://purl.bioontology.org/ontology/NDFRT/')

graph.namespace_manager.bind('activemoiety','http://purl.org/net/nlprepository/spl-active-moiety')

## metadata

graph.add((URIRef(activemoiety), pav["createdBy"], Literal('Richard D. Boyce, PhD')))
graph.add((URIRef(activemoiety), pav["contributedBy"], Literal('Yifan Ning, MS')))
graph.add((URIRef(activemoiety), pav["createdOn"], Literal(time.strftime("%m/%d/%Y-%H:%M"))))
graph.add((URIRef(activemoiety), dcterms['publisher'], Literal("Department of Biomedical Informatics, University of Pittsburgh")))
graph.add((URIRef(activemoiety), dcterms['license'], URIRef("http://www.opendatacommons.org/licenses/by/1.0")))

index =1

for drug in drugsL:

   clinicalDrug = CLINICALDRUG_BASE + drug.rxcui

   graph.add((URIRef(clinicalDrug), linkedspls_vocabulary["RxCUI"], URIRef(RXNORM_BASE + str(int(float(drug.rxcui))))))

   if drug.omop:
      graph.add((URIRef(clinicalDrug), linkedspls_vocabulary["OMOPConceptId"], URIRef((OHDSI_BASE + str(int(float(drug.omop)))))))

   if drug.dron:
      graph.add((URIRef(clinicalDrug), linkedspls_vocabulary["DrOnId"], URIRef(DRON_BASE + drug.dron)))

      
##display the graph
f = codecs.open(OUT_FILE,"w","utf8")
s = graph.serialize(format="xml",encoding="utf8")
f.write(unicode(s,errors='replace'))
#print graph.serialize(format="xml")
f.close
graph.close()

