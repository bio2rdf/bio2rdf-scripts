'''
Created 09/04/2014

@authors: Yifan Ning

@summary: parse drugbank id and chebi id from drugbank xml
 
'''

from lxml import etree
from lxml.etree import XMLParser, parse
import os, sys
from sets import Set


#DRUGBANK_XML = "../drugbank.xml"
NS = "{http://www.drugbank.ca}" 

CHEBI_OBO = "http://purl.obolibrary.org/obo/CHEBI_"
CHEBI_BIO2RDF = "http://bio2rdf.org/chebi:"
DRUGBANK_CA = "http://www.drugbank.ca/drugs/"
DRUGBANK_BIO2RDF = "http://bio2rdf.org/drugbank:"

'''
data structure of drugbank.xml                                                                                                                                                            
</drug><drug type="small molecule" created="2005-06-13 07:24:05 -0600"                                                                                          
updated="2013-09-16 17:11:29 -0600" version="4.0">                                                                                                              
  <drugbank-id>DB00007</drugbank-id>                                                                                                                            
  <name>Simvastatin</name>                                                                                                                                      
  <property>                                                                                                                                                   
      <kind>InChIKey</kind>                                                                                                         
      <value>InChIKey=RYMZZMVNJRMUDD-HGQWONQESA-N</value>                                                                                                      
      <source>ChemAxon</source>                                                                                                                                
    </property>    

  <synonymns>
   <synonymn>...</synonymn>
   </synonyms>

   <external-identifiers>
    <external-identifier>
    <resource>ChEBI</resource>
    <identifier>6427</identifier>
    </external-identifier>
    </external-identifiers>
'''


if len(sys.argv) > 1:
    DRUGBANK_XML = str(sys.argv[1])
else:
    print "Usage: parseDBIdAndChEBI.py <drugbank.xml>"
    sys.exit(1)


## get dict of mappings of drugbank id, name, inchikeys and synonmymns

def parseDbIdAndChEBI(root):
    dbidchebiD = {}

    for childDrug in root.iter(tag=NS + "drug"):
        subId = childDrug.find(NS + "drugbank-id")
        
        if subId == None:
            continue
        else:
            drugbankid = subId.text

            externalIds = childDrug.find(NS + "external-identifiers")
            if externalIds is not None:
                #print "[INFO] external-identifiers:"
                for subProp in externalIds.iter(NS + "external-identifier"):
                    resource = subProp.find(NS + "resource").text
                    #print "resource: " + resource
                    if "ChEBI" == resource:
                        ChEBIId = subProp.find(NS + "identifier").text
                        #print "[INFO] drugbankId: %s - chebiId: %s" % (drugbankid, ChEBIId)
                        print "%s\t%s\t%s\t%s" % (CHEBI_OBO + ChEBIId, CHEBI_BIO2RDF + ChEBIId, DRUGBANK_CA + drugbankid, DRUGBANK_BIO2RDF + drugbankid)
                        
def main():
   
    p = XMLParser(huge_tree=True)
    tree = parse(DRUGBANK_XML,parser=p)
    root = tree.getroot()
    
    ## mappings of drugbank Id and ChEBI id from drugbank.xml
    parseDbIdAndChEBI(root)    
    

if __name__ == "__main__":
    main()        



