""" Simple Python script to query "http://alphasparql.bioontology.org/sparql/"
    No extra libraries required.

    Modified to map drug names in text to other drug ontologies
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

    #To get your API key register at http://bioportal.bioontology.org/accounts/new
    api_key = "74028721-e60e-4ece-989b-1d2c17d14e9c"

    f = open("active_moieties.txt", "r")
    dl = [x.strip("\n").strip() for x in f.readlines()]
    f.close()

    for d in dl:

        query_string = """ 
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT DISTINCT *
FROM <http://bioportal.bioontology.org/ontologies/CHEBI> 
WHERE 
{
   ?x <http://www.geneontology.org/formats/oboInOWL#hasRelatedSynonym> ?label .
   FILTER (UCASE(str(?label)) = "%s")
} 
""" % d.upper()
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
