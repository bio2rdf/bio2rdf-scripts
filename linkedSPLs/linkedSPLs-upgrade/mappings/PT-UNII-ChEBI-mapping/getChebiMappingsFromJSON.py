""" Python script to parse the results of the following query to bioportal for all ChEBI lables

    The results, which wer stored in a json file, are parsed and
    searched for an exact string match (case insensitive) between the
    ChEBI label and the preferred ingredient string from the official
    FDA substance list.

    Author: Richard D Boyce, Yifan Ning - University of Pittsburgh
"""

import json as simplejson
from SPARQLWrapper import SPARQLWrapper, JSON
import json,urllib2,urllib,traceback, sys

FDAPreferredSubstanceToUNII = ""

if len(sys.argv) > 1:
    FDAPreferredSubstanceToUNII = str(sys.argv[1])
else:
    print "Usage: getChebiMappingsFromJSON.py <path to FDAPreferredSubstanceToUNII.txt>"
    sys.exit(1)

# add manually query into script
# f = open("bioportal_sparql_results_10202014.json","r")
# labelsD = simplejson.load(f)

def query(q,apikey,epr,f='application/json'):
    """Function that uses urllib/urllib2 to issue a SPARQL query.
       By default it requests json as data format for the SPARQL resultset"""

    try:
        params = {'query': q, 'apikey': apikey}
        params = urllib.urlencode(params)
        opener = urllib2.build_opener(urllib2.HTTPHandler)
        request = urllib2.Request(epr+'?'+params)
        request.add_header('Accept', f)
        request.get_method = lambda: 'GET'
        url = opener.open(request)
        return url.read()
    except Exception, e:
        traceback.print_exc(file=sys.stdout)
        raise e

qry = """

PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT DISTINCT *
FROM <http://bioportal.bioontology.org/ontologies/CHEBI> 
WHERE 
{
  ?x <http://www.geneontology.org/formats/oboInOWL#hasRelatedSynonym> ?label.     
}

"""

sparql_service = "http://sparql.bioontology.org/sparql/"

#To get your API key register at http://bioportal.bioontology.org/accounts/new
api_key = "74028721-e60e-4ece-989b-1d2c17d14e9c"

#print "query_string: %s" % qry
json_string = query(qry, api_key, sparql_service)
resultset=json.loads(json_string)

if len(resultset["results"]["bindings"]) == 0:
    print "INFO: No result for %s" % d
else:
    labToUriD = {}
    for bnd in resultset["results"]["bindings"]:

        uri = bnd["x"]["value"]
        label = bnd["label"]["value"]
        labToUriD[label] = uri

        f = open(FDAPreferredSubstanceToUNII, "r")

    dl = [x.split("\t")[0] for x in f.readlines()]
    f.close()

    uniiToChebiD = {}
    for d in dl:
        if labToUriD.get(d.upper()):
            uniiToChebiD[d] = labToUriD.get(d.upper())
            continue
        elif labToUriD.get(d.lower()):
            uniiToChebiD[d] = labToUriD.get(d.lower())
            continue
        elif labToUriD.get(d.lower().capitalize()):
            uniiToChebiD[d] = labToUriD.get(d.lower().capitalize())
            continue
    #else:
        #print "$"
        #print "ERROR: No match for %s found in ChEBI synonyms" % d

#print "\n\nRESULTS:"

    for k,v in uniiToChebiD.iteritems():
        print "%s	%s" % (k,v)

