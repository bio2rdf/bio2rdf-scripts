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
DRON_RXCUI = "mappings/cleaned-dron-to-rxcui-drug-06222015.txt"
OMOP_RXCUI = "mappings/imeds_drugids_to_rxcuis.csv"
PT_RXCUI = "mappings/converted_rxnorm_mappings_06262015.txt"

## read mappings of dron and rxcui

dron_rxcui_cols = ['dron', 'chebi', 'rxcui']
dron_rxcui_DF = pd.DataFrame({'dron': ['string'],'chebi': ['string'],'rxcui': ['string']})
dron_rxcui_DF = pd.read_csv(DRON_RXCUI, sep='|', names=dron_rxcui_cols, usecols=["dron", "rxcui"],)
#print dron_rxcui_DF

## read mappings of omopid and rxcui

omop_rxcui_cols = ['omop','rxcui']
omop_rxcui_DF = pd.DataFrame({'omop': ['string'],'rxcui': ['string']})
omop_rxcui_DF = pd.read_csv(OMOP_RXCUI, sep='|', names=omop_rxcui_cols, skiprows=[0])
#print omop_rxcui_DF

## read mappings of preferred term and rxcui
pt_rxcui_cols = ['setid','rxcui','pt']
pt_rxcui_DF = pd.DataFrame({'setid': ['string'],'rxcui': ['string'],'pt': ['string']})
pt_rxcui_DF = pd.read_csv(PT_RXCUI, sep='|', names=pt_rxcui_cols, usecols = ['rxcui','pt'])
#print pt_rxcui_DF


## merge dron, omop, rxcui

dron_omop_rxcui_DF = pd.DataFrame({'dron': ['string'],'rxcui': ['string'],'omop': ['string']})
dron_omop_rxcui_DF = dron_rxcui_DF.merge(omop_rxcui_DF, on=['rxcui'], how='inner')
#print dron_omop_rxcui_DF

## merge preferred term
pt_dron_omop_rxcui_DF = dron_omop_rxcui_DF.merge(pt_rxcui_DF, on=['rxcui'], how='inner')

pt_dron_omop_rxcui_DF.to_csv('mergedClinicalDrug.csv', sep='\t', index=False)
