'''
Created 03/20/2014

@authors: Yifan Ning

@summary: parse the xml that download from UMLS
          output: setid, unii, nui, displayname (name and role)
'''

import xml.etree.ElementTree as ET
import os, sys

XML_DIR = "../Data_Source/dailymed/pharmacologic_class_indexing_spl_files/"

XML_SAMPLE = "0a1e7ef2-7bdd-4a6e-b725-5112ec46d65a.xml"
NS = "{urn:hl7-org:v3}" 

if len(sys.argv) > 1:
    XML_DIR = str(sys.argv[1])
else:
    print "Usage: parseEPCfromXMLs.py <the path to pharmacologic_class_indexing_spl_files>"
    sys.exit(1)


#get setId
def getSetId(root):
    setId = root.find(NS + "setId")
    if setId.get("root") is not None:
        return setId.attrib["root"]
    else:
        return None

#get UNII\


def getUNII(root):
    for childIdSub in root.iter(tag=NS + "identifiedSubstance"):
        subId = childIdSub.find(NS + "id")
        if subId == None:
            continue
        else:
            return subId.attrib["extension"]
    return None

#get multiple nui and NameAndRole
#then output with setid and unii

def printEPC(root):
    for childGM in root.iter(tag=NS + "generalizedMaterialKind"):
        subcode = childGM.find(NS + "code")
        if subcode == None:
            continue
        else:
            setid = getSetId(root)
            unii = getUNII(root)
            out = setid +"\t"+ unii +"\t"+ subcode.attrib["code"] +"\t"+ subcode.attrib["displayName"]
            print unicode(out)


for f in os.listdir(XML_DIR):
    if f.endswith("xml"):
        tree = ET.parse(XML_DIR+f)
        root = tree.getroot()
        printEPC(root)


