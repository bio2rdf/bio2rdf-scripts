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

OUT_FILE = "activeMoietySub-in-rdf.xml"

CHEBI_BASE = "http://purl.obolibrary.org/obo/"
RXNORM_BASE = "http://purl.bioontology.org/ontology/"
DRUGBANK_CA = "http://www.drugbank.ca/drugs/"
DRUGBANK_BIO2RDF = "http://bio2rdf.org/drugbank:"
DRON_BASE = "http://purl.obolibrary.org/obo/"
NDFRT_BASE = "http://purl.bioontology.org/ontology/NDFRT/"

class DictItem:
   def __init__(self, pt, unii, db_uri1, db_uri2, rxcui, omopid, chebi, dron, nui, nameAndRole):
      self.pt = str(pt)
      self.unii = str(unii)
      self.db_uri1 = str(db_uri1)
      self.db_uri2 = str(db_uri2)
      self.rxcui = str(rxcui)
      self.omopid = str(omopid)
      self.chebi = str(chebi)
      self.dron = str(dron)
      self.nui = str(nui)
      self.nameAndRole = str(nameAndRole)

data_set = csv.DictReader(open("mergedActiveMoiety.csv","rb"), delimiter='\t')
dict_moieties = {}

## convert data from csv to dict (unii, items-object)

for item in data_set:

   if item["unii"] not in dict_moieties:
      moiety = DictItem(item["pt"], item["unii"], item["db_uri1"], item["db_uri2"], item["rxcui"], item["omopid"], item["chebi"], item["dron"], item["nui"], item["nameAndRole"])
      dict_moieties[item["unii"]]=moiety
   else:
      if item["nui"].strip() is not None:
         dict_moieties[item["unii"]].nui += "|" + item["nui"]
          
      if item["nameAndRole"].strip() is not None:
         dict_moieties[item["unii"]].nameAndRole += "|" + item["nameAndRole"]


#print dict_moieties

## set up RDF graph
# identify namespaces for other ontologies to be used                                                                                    
dcterms = Namespace("http://purl.org/dc/terms/")
pav = Namespace("http://purl.org/pav")
dctypes = Namespace("http://purl.org/dc/dcmitype/")
dailymed = Namespace('http://dbmi-icode-01.dbmi.pitt.edu/linkedSPLs/vocab/resource/')
sio = Namespace('http://semanticscience.org/resource/')
oa = Namespace('http://www.w3.org/ns/oa#')
cnt = Namespace('http://www.w3.org/2011/content#')
gcds = Namespace('http://www.genomic-cds.org/ont/genomic-cds.owl#')

siocns = Namespace('http://rdfs.org/sioc/ns#')
swande = Namespace('http://purl.org/swan/1.2/discourse-elements#')
dikbD2R = Namespace('http://dbmi-icode-01.dbmi.pitt.edu/dikb/vocab/resource/')
linkedspls = Namespace('file:///home/rdb20/Downloads/d2rq-0.8.1/linkedSPLs-dump.nt#structuredProductLabelMetadata/')
poc = Namespace('http://purl.org/net/nlprepository/spl-ddi-annotation-poc#')
ncbit = Namespace('http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#')
dikbEvidence = Namespace('http://dbmi-icode-01.dbmi.pitt.edu/dikb-evidence/DIKB_evidence_ontology_v1.3.owl#')
mp = Namespace('http://purl.org/mp/') # namespace for micropublication



graph = Graph()

graph.namespace_manager.reset()
graph.namespace_manager.bind("dcterms", "http://purl.org/dc/terms/")
graph.namespace_manager.bind("pav", "http://purl.org/pav");
graph.namespace_manager.bind("dctypes", "http://purl.org/dc/dcmitype/")
graph.namespace_manager.bind('dailymed','http://dbmi-icode-01.dbmi.pitt.edu/linkedSPLs/vocab/resource/')
graph.namespace_manager.bind('sio', 'http://semanticscience.org/resource/')
graph.namespace_manager.bind('oa', 'http://www.w3.org/ns/oa#')
graph.namespace_manager.bind('cnt', 'http://www.w3.org/2011/content#')
graph.namespace_manager.bind('gcds','http://www.genomic-cds.org/ont/genomic-cds.owl#')

graph.namespace_manager.bind('siocns','http://rdfs.org/sioc/ns#')
graph.namespace_manager.bind('swande','http://purl.org/swan/1.2/discourse-elements#')
graph.namespace_manager.bind('dikbD2R','http://dbmi-icode-01.dbmi.pitt.edu/dikb/vocab/resource/')

graph.namespace_manager.bind('linkedspls','file:///home/rdb20/Downloads/d2rq-0.8.1/linkedSPLs-dump.nt#structuredProductLabelMetadata/')
graph.namespace_manager.bind('poc','http://purl.org/net/nlprepository/spl-ddi-annotation-poc#')
graph.namespace_manager.bind('ncbit','http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#')
graph.namespace_manager.bind('dikbEvidence','http://dbmi-icode-01.dbmi.pitt.edu/dikb-evidence/DIKB_evidence_ontology_v1.3.owl#')
graph.namespace_manager.bind('mp','http://purl.org/mp/')


currentAnnotSet = "active-moiety-sub-graph" 

index =1

for k,v in dict_moieties.items():

   currentAnnotItem = "ddi-spl-active-moiety-item-%s" % v.unii
   
   #print k
   # pt, unii, db_uri1, db_uri2, rxcui, omopid, chebi, dron, nui, nameAndRole

   graph.add((poc[currentAnnotSet], dailymed["activeMoietySub"], poc[currentAnnotItem]))

   graph.add((poc[currentAnnotItem], dailymed["activeMoietyUNII"], Literal(v.unii)))
   graph.add((poc[currentAnnotItem], RDFS.label, Literal(v.pt.strip())))
   graph.add((poc[currentAnnotItem], RDF.type, dailymed["ActiveMoietyUNII"]))
   if v.rxcui:
      graph.add((poc[currentAnnotItem], dailymed["activeMoietyRxCUI"], URIRef(RXNORM_BASE + str(int(float(v.rxcui))))))

   if v.chebi:
      graph.add((poc[currentAnnotItem], dailymed["activeMoietyChEBI"], URIRef(CHEBI_BASE + v.chebi)))

   if v.db_uri1:
      graph.add((poc[currentAnnotItem], dailymed["subjectXref"], URIRef(v.db_uri1)))
      graph.add((poc[currentAnnotItem], dailymed["subjectXref"], URIRef(v.db_uri2)))

   if v.omopid:
      graph.add((poc[currentAnnotItem], dailymed["OMOPConceptId"], Literal(int(float(v.omopid)))))

   if v.dron:
      graph.add((poc[currentAnnotItem], dailymed["DrOnId"], URIRef(DRON_BASE + v.db_uri2)))

   #print "****|" + v.nui + "|"

   if v.nui.strip() and v.nui.find("|") and v.nameAndRole.find("|") and  v.nameAndRole.strip():
      nuis = v.nui.split("|")
      nameAndRoles = v.nameAndRole.split("|")

      if nuis and nameAndRoles and len(nuis) == len(nameAndRoles):

         for index in range(len(nuis)):
         #print "***" + nuis[index] + "***" + nameAndRoles[index]
            graph.add((poc[currentAnnotItem], URIRef(NDFRT_BASE + str(nuis[index])), Literal(nameAndRoles[index])))


##display the graph
f = codecs.open(OUT_FILE,"w","utf8")
s = graph.serialize(format="xml",encoding="utf8")
f.write(unicode(s,errors='replace'))
print graph.serialize(format="xml")
f.close
graph.close()

