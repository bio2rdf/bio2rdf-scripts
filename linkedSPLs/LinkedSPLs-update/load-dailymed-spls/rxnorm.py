import urllib2
import json
import os

from lxml import etree

RXNORM_BASE="http://rxnav.nlm.nih.gov/REST/"
HEADERS = { 'Accept' : 'application/json'}

def setid_in_rxnorm(setId):
    req = urllib2.Request(RXNORM_BASE + "rxcui?idtype=SPL_SET_ID&id=" + setId, None, HEADERS)
    #print "Request: %s" % req.get_full_url()

    response = urllib2.urlopen(req)
    result = json.loads(response.read())
    if "idGroup" in result:
        if "rxnormId" in result["idGroup"]:
            return True
    return False

def test_spls(splDir):
    ns = "{urn:hl7-org:v3}"  #namespace for dailymed spls
    spls = [os.path.join(splDir, f) for f in os.listdir(splDir)]
    for spl in spls:
        parser = etree.XMLParser(huge_tree=True)
        tree = etree.parse(spl, parser=parser)
        document = tree.getroot()
        for idTag in document.getiterator(tag=ns+"id"):
            setid = idTag.get("root")
        if setid_in_rxnorm(setid):
            print "{0} IN RxNORM".format(setid)
        else:
            print "{0} NOT IN RxNORM".format(setid)

if __name__ == "__main__":
#    setId = "1C5BC1DD-E9EC-44C1-9281-67AD482315D9"
#    print setid_in_rxnorm(setId)
    test_spls("/home/PITT/gag30/spls/")
