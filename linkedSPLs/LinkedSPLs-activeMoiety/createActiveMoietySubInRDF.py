'''
Created 08/18/2014

@authors: Yifan Ning

@summary: Read csv file, which contains preferred term, UNII, NUI, preferredNameAndRole, Drug bank URI, ChEBI URI, rxnorm URI, OMOP id, DrOn id. To create rdf graph to represents active moieties.

'''

import sys
sys.path = sys.path + ['.']

import re, codecs, uuid, datetime
import urllib2
import urllib
import traceback
import csv
import difflib
from rdflib import Graph, BNode, Literal, Namespace, URIRef, RDF, RDFS
from sets import Set

OUT_FILE = "activeMoietySub-in-rdf.xml"
ACTIVEMOIETY_BASE = "http://bio2rdf.org/linkedspls:"

CHEBI_BASE = "http://purl.obolibrary.org/obo/"
RXNORM_BASE = "http://purl.bioontology.org/ontology/RXNORM/"
DRUGBANK_CA = "http://www.drugbank.ca/drugs/"
DRUGBANK_BIO2RDF = "http://bio2rdf.org/drugbank:"
DRON_BASE = "http://purl.obolibrary.org/obo/"
NDFRT_BASE = "http://purl.bioontology.org/ontology/NDFRT/"
OHDSI_BASE = "http://purl.org/net/ohdsi#"

class DictItem:

   nuis = Set()
   nameAndRoles = Set()

   def __init__(self, pt, db_uri1, db_uri2, rxcui, omopid, chebi, dron, nui, nameAndRole):

      self.pt = str(pt)
      self.db_uri1 = str(db_uri1)
      self.db_uri2 = str(db_uri2)
      self.rxcui = str(rxcui)
      self.omopid = str(omopid)
      self.chebi = str(chebi)
      self.dron = str(dron)

      #pts = Set(str(pt))
      self.nuis.add(str(nui))
      self.nameAndRoles.add(str(nameAndRole))
      
   def addNUI(nui):
      self.nuis.add(str(nui))
   def addNameAndRole(nameAndRole):
      self.nameAndRoles.add(str(nameAndRole))



data_set = csv.DictReader(open("mergedActiveMoiety.csv","rb"), delimiter='\t')
dict_moieties = {}

## convert data from csv to dict (unii, items-object)

for item in data_set:

   if item["unii"] not in dict_moieties:
      moiety = DictItem(item["pt"], item["db_uri1"], item["db_uri2"], item["rxcui"], item["omopid"], item["chebi"], item["dron"], item["nui"], item["nameAndRole"])
      dict_moieties[item["unii"]]=moiety
   else:
      if not dict_moieties[item["unii"]].pt:
         dict_moieties[item["unii"]].pt = item["pt"]  
      if not dict_moieties[item["unii"]].db_uri1:
         dict_moieties[item["unii"]].db_uri1 = item["db_uri1"]      
      if not dict_moieties[item["unii"]].db_uri2:
         dict_moieties[item["unii"]].db_uri2 = item["db_uri2"] 
      if not dict_moieties[item["unii"]].rxcui:
         dict_moieties[item["unii"]].rxcui = item["rxcui"] 
      if not dict_moieties[item["unii"]].omopid:
         dict_moieties[item["unii"]].omopid = item["omopid"] 
      if not dict_moieties[item["unii"]].chebi:
         dict_moieties[item["unii"]].chebi = item["chebi"] 
      if not dict_moieties[item["unii"]].dron:
         dict_moieties[item["unii"]].dron = item["dron"] 

      #print '|'+item["pt"] + '|'
      #preferredterm = item["pt"] 
      #dict_moieties[item["unii"]].addPT(preferredterm) 

      dict_moieties[item["unii"]].nuis.add(item['nui'])
      dict_moieties[item["unii"]].nameAndRoles.add(item["nameAndRole"])


#print dict_moieties

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
poc = Namespace('http://purl.org/net/nlprepository/spl-ddi-annotation-poc#')
ncbit = Namespace('http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#')
dikbEvidence = Namespace('http://dbmi-icode-01.dbmi.pitt.edu/dikb-evidence/DIKB_evidence_ontology_v1.3.owl#')
mp = Namespace('http://purl.org/mp/') # namespace for micropublication

ndfrt = Namespace('http://purl.bioontology.org/ontology/NDFRT/')

graph = Graph()

graph.namespace_manager.reset()
graph.namespace_manager.bind("dcterms", "http://purl.org/dc/terms/")
graph.namespace_manager.bind("pav", "http://purl.org/pav");
graph.namespace_manager.bind("dctypes", "http://purl.org/dc/dcmitype/")

#graph.namespace_manager.bind('dailymed','http://linkedspls.bio2rdf.org/dailymed#')
graph.namespace_manager.bind('linkedspls_vocabulary', 'http://bio2rdf.org/linkedspls_vocabulary:')

graph.namespace_manager.bind('sio', 'http://semanticscience.org/resource/')
graph.namespace_manager.bind('oa', 'http://www.w3.org/ns/oa#')
graph.namespace_manager.bind('cnt', 'http://www.w3.org/2011/content#')
graph.namespace_manager.bind('gcds','http://www.genomic-cds.org/ont/genomic-cds.owl#')

graph.namespace_manager.bind('siocns','http://rdfs.org/sioc/ns#')
graph.namespace_manager.bind('swande','http://purl.org/swan/1.2/discourse-elements#')
graph.namespace_manager.bind('dikbD2R','http://dbmi-icode-01.dbmi.pitt.edu/dikb/vocab/resource/')

graph.namespace_manager.bind('linkedspls','file:///home/rdb20/Downloads/d2rq-0.8.1/linkedspls-dump.nt#structuredProductLabelMetadata/')
graph.namespace_manager.bind('poc','http://purl.org/net/nlprepository/spl-ddi-annotation-poc#')
graph.namespace_manager.bind('ncbit','http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#')
graph.namespace_manager.bind('dikbEvidence','http://dbmi-icode-01.dbmi.pitt.edu/dikb-evidence/DIKB_evidence_ontology_v1.3.owl#')
graph.namespace_manager.bind('mp','http://purl.org/mp/')
graph.namespace_manager.bind('ndfrt','http://purl.bioontology.org/ontology/NDFRT/')


index =1

for k,v in dict_moieties.items():

   # pt, unii, db_uri1, db_uri2, rxcui, omopid, chebi, dron, nui, nameAndRole

   graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["UNII"], Literal(k)))
   graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), RDFS.label, Literal(v.pt.strip())))
   graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), RDF.type, linkedspls_vocabulary["ActiveMoietyUNII"]))
   if v.rxcui:
      graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["RxCUI"], URIRef(RXNORM_BASE + str(int(float(v.rxcui))))))

   if v.chebi:
      graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["ChEBI"], URIRef(CHEBI_BASE + v.chebi)))

   if v.db_uri1:
      graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["subjectXref"], URIRef(v.db_uri1)))
      graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["subjectXref"], URIRef(v.db_uri2)))

   if v.omopid:
      graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["OMOPConceptId"], Literal(OHDSI_BASE + str(int(float(v.omopid))))))

   if v.dron:
      graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["DrOnId"], URIRef(DRON_BASE + v.dron)))



   ## TODO: add nuis and name and roles into active moiety sub graph

   # for (nui, role) in v.nuis, v.nameAndRoles:
   #     graph.add((URIRef(ACTIVEMOIETY_BASE + str(v.unii)), ndfrt[nui], Literal(role)))



##display the graph
f = codecs.open(OUT_FILE,"w","utf8")
s = graph.serialize(format="xml",encoding="utf8")
f.write(unicode(s,errors='replace'))
print graph.serialize(format="xml")
f.close
graph.close()

