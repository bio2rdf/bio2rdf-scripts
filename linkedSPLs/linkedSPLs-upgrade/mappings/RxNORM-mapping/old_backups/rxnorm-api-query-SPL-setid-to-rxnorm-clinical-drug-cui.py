""" Script to query the rxnorm api for spl to branded and clinical
    drug product mappings for linkedSPLs

    Author: Richard D Boyce - University of Pittsburgh
"""

import re
import urllib
import urllib2

## import Sparql-related
from SPARQLWrapper import SPARQLWrapper, JSON

import sys
sys.path = sys.path + ['.']
import logansJSON 

RXNORM_BASE="http://rxnav.nlm.nih.gov/REST/"
HEADERS = { 'Accept' : 'application/json'}

#splSparql = SPARQLWrapper("http://dbmi-icode-01.dbmi.pitt.edu/linkedSPLs/sparql")
splSparql = SPARQLWrapper("http://130.49.206.86:25000/sparql")

def getSPLSetIds(sparql):
    qry = '''
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX diseasome: <http://www4.wiwiss.fu-berlin.de/diseasome/resource/diseasome/>
PREFIX db: <http://dbmi-icode-01.dbmi.pitt.edu/linkedSPLs/resource/>
PREFIX drugbank: <http://www4.wiwiss.fu-berlin.de/drugbank/resource/drugbank/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX map: <file:/home/rdb20/swat-4-med-safety/med-info-corpora/phase-I-package-inserts/dailymed_d2r_map_config.n3#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX dailymed: <http://dbmi-icode-01.dbmi.pitt.edu/linkedSPLs/vocab/resource/>

SELECT DISTINCT ?setId WHERE {
  ?splId dailymed:setId ?setId.
}
'''
    print "QUERY: %s" % qry

    sparql.setQuery(qry)
    sparql.setReturnFormat(JSON)
    results = sparql.query().convert()

    if len(results["results"]["bindings"]) == 0:
        print "ERROR: no results from query"
        return {}

    setIds = []
    for elt in results["results"]["bindings"]:
        setIds.append(elt["setId"]["value"])

    return setIds

def getSPLRxCUIs(sparql):
    qry = '''
PREFIX dc: <http://purl.org/dc/elements/1.1/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX diseasome: <http://www4.wiwiss.fu-berlin.de/diseasome/resource/diseasome/>
PREFIX db: <http://dbmi-icode-01.dbmi.pitt.edu/linkedSPLs/resource/>
PREFIX drugbank: <http://www4.wiwiss.fu-berlin.de/drugbank/resource/drugbank/>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX map: <file:/home/rdb20/swat-4-med-safety/med-info-corpora/phase-I-package-inserts/dailymed_d2r_map_config.n3#>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX dailymed: <http://dbmi-icode-01.dbmi.pitt.edu/linkedSPLs/vocab/resource/>

SELECT DISTINCT ?rxnorm WHERE {
  ?splId dailymed:activeMoietyRxCUI ?rxnorm.
}
'''
    print "QUERY: %s" % qry

    sparql.setQuery(qry)
    sparql.setReturnFormat(JSON)
    results = sparql.query().convert()

    if len(results["results"]["bindings"]) == 0:
        print "ERROR: no results from query"
        return {}

    rxcuis = []
    for elt in results["results"]["bindings"]:
        rxcuis.append(elt["rxnorm"]["value"].replace("http://purl.bioontology.org/ontology/RXNORM/",""))

    return rxcuis

def getSetIdToRXCuiMappings(rxcui, rxform, setIdCache):
        
    print "Requesting related branded and clinical products for rxcui '%s'" % cui
    req = urllib2.Request(RXNORM_BASE + "rxcui/" + cui + "/related/?tty=" + rxform.upper(), None, HEADERS)

    print "Request: %s" % req.get_full_url()

    response = urllib2.urlopen(req)
    result = logansJSON.read(response.read())

    if not result.has_key("relatedGroup"):
        print "ERROR: no result for rxcui: %s" % cui
        return setIdCache
    
    if not result["relatedGroup"].has_key("conceptGroup"):
        print "ERROR: no conceptGroup result for rxcui: %s" % cui
        return setIdCache

    if not result["relatedGroup"]["conceptGroup"][0].has_key("conceptProperties"):
        print "ERROR: no conceptProperties result for rxcui: %s" % cui
        return setIdCache

    propsL = result["relatedGroup"]["conceptGroup"][0]["conceptProperties"]
    for prop in propsL:
        sbdRxcui = prop["rxcui"]
        setidReq = urllib2.Request(RXNORM_BASE + "rxcui/" + sbdRxcui + "/splsetid", None, HEADERS)
        print "Set ID Request: %s" % setidReq.get_full_url()
    
        setidResponse = urllib2.urlopen(setidReq)
        setidResult = logansJSON.read(setidResponse.read())
    
        print "%s" % setidResult
        if not setidResult["splSetIdGroup"].has_key("splSetId"):
            print "INFO: no result for branded drug rxcui: %s, %s" % (sbdRxcui, prop["name"])
            continue
    
        setidL = setidResult["splSetIdGroup"]["splSetId"]
        print "INFO: %d results for branded drug rxcui: %s, %s" % (len(setidL), sbdRxcui, prop["name"])
        for setid in setidL:
            k = setid + sbdRxcui
            if setIdCache.has_key(k):
                print "INFO: skipping setid-sbdrxcui match: %s" % k
                continue

            setIdCache[k] = setid
            print "RESULT	%s	%s	%s" % (setid, sbdRxcui, prop["name"])
            
    return setIdCache

# Get an exact SPL to RxCUI mapping for branded drug products
setIds = getSPLSetIds(splSparql)
rxcuis = getSPLRxCUIs(splSparql)

setIdCache = {}
for cui in rxcuis:
    setIdCache = getSetIdToRXCuiMappings(cui, "SCD", setIdCache)
    setIdCache = getSetIdToRXCuiMappings(cui, "SBD", setIdCache)

print "SUMMARY: %d setid/rxcui mappings; %.2f percent of %d total setids in linkedSPLs" % (len(setIdCache.values()), float(len(setIdCache.values()))/float(len(setIds)) * 100.0, len(setIds))
