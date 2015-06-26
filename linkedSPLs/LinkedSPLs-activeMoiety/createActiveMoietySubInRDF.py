'''
Created 08/18/2014

@authors: Yifan Ning

@summary: Read csv file, which contains preferred term, UNII, NUI, preferredNameAndRole, Drug bank URI, ChEBI URI, rxnorm URI, OMOP id, DrOn id. To create rdf graph to represents active moieties.

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

OUT_FILE = "activeMoietySub-in-rdf.xml"
ACTIVEMOIETY_BASE = "http://bio2rdf.org/linkedspls:"

CHEBI_BASE = "http://purl.obolibrary.org/obo/"
RXNORM_BASE = "http://purl.bioontology.org/ontology/RXNORM/"
DRUGBANK_CA = "http://www.drugbank.ca/drugs/"
DRUGBANK_BIO2RDF = "http://bio2rdf.org/drugbank:"
NDFRT_BASE = "http://purl.bioontology.org/ontology/NDFRT/"
DRON_BASE = "http://purl.obolibrary.org/obo/"
#OHDSI_BASE = "http://purl.org/net/ohdsi#"


class DictItem:

    def __init__(self, pt, db_uri1, db_uri2, rxcui, chebi, nui, dron, nameAndRole):
        self.pt = str(pt)
        self.db_uri1 = str(db_uri1)
        self.db_uri2 = str(db_uri2)
        self.rxcui = str(rxcui)
        self.chebi = str(chebi)
        self.dron = str(dron)

        if nui and nameAndRole:
            self.drugClass = Set([str(nui)+'|'+str(nameAndRole)])
        else:
            self.drugClass = Set()


data_set = csv.DictReader(open("mergedActiveMoiety.csv","rb"), delimiter='\t')
dict_moieties = {}

## convert data from csv to dict (unii, items-object)

for item in data_set:

    if item["unii"] not in dict_moieties:
        moiety = DictItem(item["pt"], item["db_uri1"], item["db_uri2"], item["rxcui"], item["chebi"], item["nui"], item["dron"] ,item["nameAndRole"])
        dict_moieties[item["unii"]]=moiety

    else:
        if not dict_moieties[item["unii"]].pt and item["pt"]:
            dict_moieties[item["unii"]].pt = item["pt"]  
        if not dict_moieties[item["unii"]].db_uri1 and item["db_uri1"]:
            dict_moieties[item["unii"]].db_uri1 = item["db_uri1"]      
        if not dict_moieties[item["unii"]].db_uri2 and item["db_uri2"]:
            dict_moieties[item["unii"]].db_uri2 = item["db_uri2"] 
        if not dict_moieties[item["unii"]].rxcui:
            dict_moieties[item["unii"]].rxcui = item["rxcui"] 
        if not dict_moieties[item["unii"]].dron:
            dict_moieties[item["unii"]].dron = item["dron"] 
        if not dict_moieties[item["unii"]].chebi:
            dict_moieties[item["unii"]].chebi = item["chebi"]  
        #print item['nui']+'|'+item['nameAndRole']

        if item['nui'] and item['nameAndRole']:
            if dict_moieties[item["unii"]].drugClass:
                dict_moieties[item["unii"]].drugClass.add(item['nui']+'|'+item['nameAndRole'])
            else:
                dict_moieties[item["unii"]].drugClass = Set(item['nui']+'|'+item['nameAndRole'])
      



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

for k,v in dict_moieties.items():

    # pt, unii, db_uri1, db_uri2, rxcui, omopid, chebi, dron, nui, nameAndRole

    graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["UNII"], Literal(k)))
    graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), RDFS.label, Literal(v.pt.strip())))
    graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), RDF.type, linkedspls_vocabulary["ActiveMoietyUNII"]))
    if v.rxcui:
        graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["RxCUI"], URIRef(RXNORM_BASE + str(int(float(v.rxcui))))))

    if v.chebi:
        graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["ChEBI"], URIRef(v.chebi)))

    if v.db_uri1:
        graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["x-drugbank"], URIRef(v.db_uri1)))
        graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["x-drugbank"], URIRef(v.db_uri2)))

    if v.dron:
        graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["DrOnId"], URIRef(DRON_BASE + v.dron)))
        
   # if v.omopid:
   #    graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), linkedspls_vocabulary["OMOPConceptId"], Literal(OHDSI_BASE + str(int(float(v.omopid))))))



    if v.drugClass:

        for dc in v.drugClass:
            idx = dc.find('|')
            nui = dc[0:idx]
            dcStr = dc[idx+1:]

            dcGroup = None

            if '[PE]' in dcStr:
                dcGroup = "N0000009802"
            elif '[MoA]' in dcStr:
                dcGroup = "N0000000223"
            elif '[Chemical/Ingredient]' in dcStr:
                dcGroup = "N0000000002"
            elif '[EPC]' in dcStr:
                dcGroup = "N0000182631"

            if dcGroup:
                graph.add((URIRef(ACTIVEMOIETY_BASE + str(k)), ndfrt[dcGroup], ndfrt[nui]))

##display the graph
f = codecs.open(OUT_FILE,"w","utf8")
s = graph.serialize(format="xml",encoding="utf8")
f.write(unicode(s,errors='replace'))
#print graph.serialize(format="xml")
f.close
graph.close()

