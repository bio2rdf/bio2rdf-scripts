'''
Created 08/15/2014

@authors: Yifan Ning

@summary: merge preferred term, UNII, NUI, preferredNameAndRole, Drug bank URI, ChEBI URI, rxnorm URI, OMOP id, DrOn id for active moiety together.

'''

import os, sys
import pandas as pd
from StringIO import StringIO
import numpy as np
#from numpy import nan

## Define data inputs

DRON_CHEBI_RXCUI = "mappings/dronid_chebi_rxcui-07232014.txt"
OMOP_RXCUI = "mappings/omopid_rxcui.csv"
PT_CHEBI = "mappings/pt_chebi-03132014.txt"
PT_DRUGBANK = "mappings/pt_drugbank-04082014.txt"
PT_RXCUI = "mappings/pt_rxcui-03132014.txt"
PT_UNII = "mappings/pt_unii-03202014.csv"
UNII_NUI_PREFERRED_NAME_ROLE = "mappings/unii_nui_preferrednamerole-05202014.txt"

CHEBI_BASE_URI = "http://purl.obolibrary.org/obo/"



## read mappings of pt and unii

pt_unii_cols = ['id','pt','unii']
pt_unii_DF = pd.read_csv(PT_UNII, sep='|', names=pt_unii_cols)[['pt','unii']]
#print pt_unii_DF
#pt_unii_DF.to_csv('test.csv', sep='|')


## read mappings of pt and drugbank uri

pt_drugbank_cols = ['pt','db_uri1','db_uri2']
pt_drugbank_DF = pd.read_csv(PT_DRUGBANK, sep='\t', names=pt_drugbank_cols)


## read mappings of pt and rxcui

pt_rxcui_cols = ['pt','rxcui']
pt_rxcui_DF = pd.read_csv(PT_RXCUI, sep='\t', names=pt_rxcui_cols)
#pt_rxcui_DF["rxcui"].astype(str)
#print pt_rxcui_DF.head()


## read mappings of omop id and rxcui
omop_rxcui_cols = ['omopid','rxcui']
omop_rxcui_DF = pd.read_csv(OMOP_RXCUI, sep='|', names=omop_rxcui_cols)
#print omop_rxcui_DF.info()


## read mappings of pt and chebi

pt_chebi_cols = ['pt','chebi']
pt_chebi_DF = pd.read_csv(PT_CHEBI, sep='\t', names=pt_chebi_cols)
#print pt_chebi_DF.info()


## read mappings of dron and rxcui

dron_chebi_rxcui_cols = ['dron','chebi','rxcui']
dron_chebi_rxcui_DF = pd.read_csv(DRON_CHEBI_RXCUI, sep='|', names=dron_chebi_rxcui_cols)[['dron','rxcui']]
#print dron_chebi_rxcui_DF


## read mappings of unii, nui and preferredNameAndRole
unii_nui_namerole_cols = ['setid', 'unii','nui','nameAndRole']
unii_nui_namerole_DF = pd.read_csv(UNII_NUI_PREFERRED_NAME_ROLE, sep='\t', names=unii_nui_namerole_cols)[['unii','nui','nameAndRole']]
print unii_nui_namerole_DF.info()
#print unii_nui_namerole_DF.to_string()


## merge pt, unii and drugbank uri

pt_unii_db_DF = pt_unii_DF.merge(pt_drugbank_DF, on=['pt'], how='left')
#print pt_unii_db_DF.info()


## merge pt, unii, drugbank uri, rxcui together

pt_unii_db_rxcui_DF = pt_unii_db_DF.merge(pt_rxcui_DF, on=['pt'], how='left')
#print pt_unii_db_rxcui_DF


## merge omop to pt_unii_db_rxcui_DF

merged_omop_DF = pt_unii_db_rxcui_DF.merge(omop_rxcui_DF, on=['rxcui'], how='left')
#print merged_omop_DF.info()


## merge chebi to merged_omop_DF

merged_chebi_DF = merged_omop_DF.merge(pt_chebi_DF, on=['pt'], how='left')
#print merged_chebi_DF


## merge dronid to merged_chebi_DF
merged_dron_DF = merged_chebi_DF.merge(dron_chebi_rxcui_DF, on=['rxcui'], how='left')
print merged_dron_DF
#print merged_dron_DF.to_string()

## merge <nui> and <preferred name and role> to merged_dron_DF

merged_epc_DF = merged_dron_DF.merge(unii_nui_namerole_DF, on=['unii'], how='left')
print merged_epc_DF

#merged_epc_DF[['rxcui']] = merged_epc_DF[['rxcui']].astype(str)

#print merged_epc_DF

merged_epc_DF.to_csv('mergedActiveMoiety.csv', sep='\t')
