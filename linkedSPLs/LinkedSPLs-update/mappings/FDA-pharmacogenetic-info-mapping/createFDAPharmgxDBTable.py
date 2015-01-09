# createFDAPharmgxDBTable.py
#
# load data from a cleaned up version ofthe FDA's pharmacogenomic
# biomarker table
# <http://www.fda.gov/Drugs/ScienceResearch/ResearchAreas/Pharmacogenetics/ucm083378.htm>
# into a table format that can be used to link the data and SPL
# section date within linkedSPLs

import sys
sys.path = sys.path + ['.']

import re
import string
import pprint
import pickle 

## import Sparql-related
from SPARQLWrapper import SPARQLWrapper, JSON
import simplejson as json

reload(sys)
sys.setdefaultencoding("utf-8")

RAW_DATA_FILE = "genetic-biomarker-table-raw-import.csv"
OUTFILE = "FDAPharmgxTable.csv"
PT_RXCUI = "../RxNORM-mapping/PreferredTermRxcui-mapping.txt"

#LINKED_SPL_SPARQL = SPARQLWrapper("http://dbmi-icode-01.dbmi.pitt.edu:8080/sparql")
#LINKED_SPL_SPARQL.addDefaultGraph("http://dbmi-icode-01.dbmi.pitt.edu/linkedSPLs/")
LINKED_SPL_SPARQL = SPARQLWrapper("http://130.49.206.86:8890/sparql")

RXNORM_BASE = "http://purl.bioontology.org/ontology/RXNORM/"

########################################################################################################################

if len(sys.argv) > 3:
    RAW_DATA_FILE = str(sys.argv[1])
    PT_RXCUI = str(sys.argv[2])
    OUTFILE = str(sys.argv[3])
else:
    print "Usage: createFDAPharmgxDBTable.py <path to raw data file> <PreferredTermRxcui-mapping.txt> <path to output file>"
    sys.exit(1)



'''
SELECT DISTINCT ?setId WHERE {
  ?splId dailymed:activeMoietyRxCUI <%s>.
  ?splId dailymed:setId ?setId.
}

'''


def getSPLSetIdsForMoiety(sparql, rxcuiMoiety):
    qry = '''
PREFIX linkedspls_vocabulary: <http://bio2rdf.org/linkedspls_vocabulary:>

SELECT DISTINCT ?setId WHERE {
?s rdf:type linkedspls_vocabulary:structuredProductLabelMetadata;
    linkedspls_vocabulary:setId ?setId;

    linkedspls_vocabulary:activeMoiety ?activeMoiety.
    ?activeMoiety linkedspls_vocabulary:RxCUI <%s>.

}

''' % rxcuiMoiety
    #print "QUERY: %s" % qry

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




"""
    qry = '''
PREFIX dailymed: <http://dbmi-icode-01.dbmi.pitt.edu/linkedSPLs/vocab/resource/>

SELECT DISTINCT ?setId WHERE {
  %s
  ?splId dailymed:setId ?setId.
}
''' % "\n".join(["?splId dailymed:activeMoietyRxCUI <%s>." % x for x in rxcuiMoietyL])
    print "QUERY: %s" % qry

"""


def getSPLSetIdsForMultipleMoieties(sparql, rxcuiMoietyL):
    qry = '''
PREFIX linkedspls_vocabulary: <http://bio2rdf.org/linkedspls_vocabulary:>
SELECT DISTINCT ?setId WHERE {
  %s
  ?setId linkedspls_vocabulary:setId ?setId.
}
''' % "\n".join(["?s rdf:type linkedspls_vocabulary:structuredProductLabelMetadata; linkedspls_vocabulary:setId ?setId; linkedspls_vocabulary:activeMoiety ?activeMoiety. ?activeMoiety linkedspls_vocabulary:RxCUI <%s>." % x for x in rxcuiMoietyL]
)
    #print "QUERY: %s" % qry

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


##########################################################################################

# load genetic biomarker data from FDA
f = open(RAW_DATA_FILE,"r")
lines = f.readlines()
f.close()

# get RXCUI to active moiety mappings
rxcuis = []
f = open(PT_RXCUI, "r")
rxcuiLines = f.readlines()
f.close()

for l in rxcuiLines:
    if not l:
        break

    l = l.strip()
    (activeIngred, rxcui) = l.split("\t")

    rxcuis.append((activeIngred, RXNORM_BASE + rxcui))

#print rxcuis

newLines = []
ingredRxcuiToAdd = ""


idx = 1

for l in lines:

    # if idx >5:
    #     break
    # idx = idx + 1

    l = l.strip()
    elts = l.split("|")

    #print "***" + str(elts)

    rxcuiL = filter(lambda x: x[0].upper() == elts[0].strip('"').upper(), rxcuis)
    if len(rxcuiL) == 0:
        print "ERROR: no active moiety match found for %s; testing if this is a multiple active moiety case " % elts[0].strip('"').upper()
        sL = elts[0].strip('"').upper().split(" AND ")
        mL = []
        for ingr in sL:
            rxcuiL2 = filter(lambda x: x[0].upper() == ingr, rxcuis)
            if len(rxcuiL2) == 0:
                print "ERROR: no active moiety match found for %s; skipping case %s " % (ingr, elts[0].strip('"').upper())
                break
            elif len(rxcuiL2) > 1:
                print "WARNING: more than one active moiety/rxcui match %s:%s" % (ingr, rxcuiL2)
            mL += rxcuiL2
        if len(sL) != len(mL):
            print "ERROR: possible multiple active moiety case but could not acquire at least one rxcui per moiety"
        else:
            rxcuiL.append(mL)
            
    elif len(rxcuiL) > 1:
        print "WARNING: more than one active moiety/rxcui match (single active moiety case) %s:%s" % (elts[0].strip('"').upper(), rxcuiL)
    
    for rxcui in rxcuiL:
        sects = elts[-1].strip('"').split(",")

        # get all setids for spls containing the active moieties
        setids = None
        if type(rxcui[0]) == type(()): # multiple active moieties
            print "multi %s" % rxcui
            ingredRxcuiToAdd += "\n" +"\n".join([elts[0].strip('"').upper() + "\t" + x[1] for x in rxcui])
            setids = getSPLSetIdsForMultipleMoieties(LINKED_SPL_SPARQL, [x[1] for x in rxcui])
            for setid in setids:
                for sect in sects:
                    newLines.append("%s	%s	%s	%s	%s" % (elts[0].strip('"').upper(), elts[1].strip('"'), elts[2].strip('"'), setid, sect.strip()))

        else: # single active moiety
            print "single"
            setids = getSPLSetIdsForMoiety(LINKED_SPL_SPARQL, rxcui[1])
            for setid in setids:
                for sect in sects:
                    newLines.append("%s	%s	%s	%s	%s" % (elts[0].strip('"').upper(), elts[1].strip('"'), elts[2].strip('"'), setid, sect.strip()))
    
print "TODO: ADD THE FOLLOWING LINES TO THE FILE THAT MAPS FDA ACTIVE INGREDIENTS TO RXCUIS"
print ingredRxcuiToAdd

f = open(OUTFILE,"w")
for ln in newLines:
    f.write(ln + "\n")
f.close()

    
