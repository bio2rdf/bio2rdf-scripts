'''
Created 08/15/2014

@authors: Yifan Ning

@summary: merge rxnorm URI, OMOP id, DrOn id for clinical drug.

'''

import os, sys
import pandas as pd
from StringIO import StringIO
import numpy as np
#from numpy import nan

CHEBI_BASE_URI = "http://purl.obolibrary.org/obo/"
RXNORM_BASE_URI = "http://purl.bioontology.org/ontology/RXNORM/"

## Define data inputs
DRON_RXCUI = "../LinkedSPLs-update/mappings/DrOn-to-RxNorm/cleaned-dron-to-rxcui-drug.txt"
SETID_RXCUI = "mappings/setid_rxcui.txt"
FULLNAME_SETID = "mappings/setid_fullname.txt"

#OMOP_RXCUI = "mappings/imeds_drugids_to_rxcuis.csv"
OMOP_RXCUI = "mappings/clinical-drug-omopid-rxcui.dsv"



## read mappings of dron and rxcui

dron_rxcui_cols = ['dron', 'chebi', 'rxcui']
dron_rxcui_DF = pd.DataFrame({'dron': ['string'],'chebi': ['string'],'rxcui': ['string']})
dron_rxcui_DF = pd.read_csv(DRON_RXCUI, sep='|', names=dron_rxcui_cols, usecols=["dron", "rxcui"],)


## read mappings of omopid and rxcui

omop_rxcui_cols = ['omop','rxcui']
omop_rxcui_DF = pd.DataFrame({'omop': ['string'],'rxcui': ['string']})
omop_rxcui_DF = pd.read_csv(OMOP_RXCUI, sep='|', names=omop_rxcui_cols, skiprows=[0])


## merge dron, omop, rxcui

dron_omop_rxcui_DF = pd.DataFrame({'dron': ['string'],'rxcui': ['string'],'omop': ['string']})
dron_omop_rxcui_DF = dron_rxcui_DF.merge(omop_rxcui_DF, on=['rxcui'], how='inner')

print dron_omop_rxcui_DF.info()


## read mappings of setid and rxcui
setid_rxcui_cols = ['setid','rxcui']
setid_rxcui_DF = pd.DataFrame({'setid': ['string'],'rxcui': ['string']})
setid_rxcui_DF = pd.read_csv(SETID_RXCUI, sep='|', names=setid_rxcui_cols)


## read mappings of setid and fullname
fullname_setid_cols = ['setid','fullname']
fullname_setid_DF = pd.DataFrame({'setid': ['string'],'fullname': ['string']})
fullname_setid_DF = pd.read_csv(FULLNAME_SETID, sep=',', names=fullname_setid_cols)


## merge fullname, setid, rxcui
fullname_rxcui_setid_DF = pd.DataFrame({'rxcui': ['string'],'setid': ['string'],'fullname': ['string']})
fullname_rxcui_setid_DF = setid_rxcui_DF.merge(fullname_setid_DF, on=['setid'], how='right')
print fullname_rxcui_setid_DF.info()



## merge fullname, rxcui, dron, omopid

output_DF = fullname_rxcui_setid_DF.merge(dron_omop_rxcui_DF, on=['rxcui'], how='left')

print output_DF.info()
#print output_DF

output_DF.to_csv('mergedClinicalDrug.tsv', sep='\t', index=False)
