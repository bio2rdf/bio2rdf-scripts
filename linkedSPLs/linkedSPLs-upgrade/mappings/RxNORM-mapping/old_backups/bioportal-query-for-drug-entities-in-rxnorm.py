""" Python script to query "http://sparql.bioontology.org/sparql/ for RxNORM Purls"

    The association is made by an exact string match (case insensitive) to the RxNorm
    ingredient string from the official FDA substance list.

    http://www.nlm.nih.gov/research/umls/rxnorm/docs/2012/rxnorm_doco_full_2012-3.html#s8_0

    Author: Richard D Boyce - University of Pittsburgh
"""

import json
import urllib2
import urllib
import traceback
import sys 

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

if __name__ == "__main__":
    sparql_service = "http://sparql.bioontology.org/sparql/"

    print "SPARQL service initialized..."

    #To get your API key register at http://bioportal.bioontology.org/accounts/new
    api_key = "74028721-e60e-4ece-989b-1d2c17d14e9c"

    #f = open("active_moieties.txt", "r")
    #f = open("active_moieties-4K-20K.txt","r")
    #f = open("active_moieties-4K-20K-PART-1.txt","r")
    #f = open("active_moieties-4K-20K-PART-2.txt","r")
    #f = open("active_moieties-4K-20K-PART-3.txt","r")
    #f = open("active_moieties-4K-20K-PART-4.txt","r")
    #f = open("all-updated-active-moieties/needToGet.txt","r")
    f = open("FDAPreferredSubstance_03132014.txt","r")

    dl = [x.strip("\n").strip() for x in f.readlines()]
    f.close()


    for d in dl:
        query_string = """ 
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

SELECT DISTINCT *
FROM <http://bioportal.bioontology.org/ontologies/RXNORM> 
FROM <http://bioportal.bioontology.org/ontologies/globals> 
WHERE 
{
    {?x skos:prefLabel "%s"@EN.} UNION {?x skos:prefLabel "%s"@EN.} UNION {?x skos:prefLabel "%s"@EN.}
} 
""" % (d.lower().capitalize(), d.lower(), d.upper())
        print "query_string: %s" % query_string
        json_string = query(query_string, api_key, sparql_service)
        resultset=json.loads(json_string)

        if len(resultset["results"]["bindings"]) == 0:
            print "INFO: No result for %s" % d
        else:
            print "INFO: %d results for %s; showing PURL for each unique result" % (len(resultset["results"]["bindings"]), d)
            cache = []
            for i in range(0, len(resultset["results"]["bindings"])):
                if resultset["results"]["bindings"][i]["x"]["value"] in cache:
                    continue

                print "RESULT: %s	%s"  % (d, resultset["results"]["bindings"][i]["x"]["value"])
                cache.append(resultset["results"]["bindings"][i]["x"]["value"])
