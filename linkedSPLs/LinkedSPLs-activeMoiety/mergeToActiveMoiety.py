'''
Created 08/15/2014

@authors: Yifan Ning

@summary: merge preferred term, UNII, NUI, preferredNameAndRole, Drug bank URI, ChEBI URI, rxnorm URI, OMOP id, DrOn id for active moiety together.

'''

import os, sys
import pandas as pd

## Define data inputs

DRON_CHEBI = "dronid_chebi_rxcui-07232014.txt"
OMOP_RXCUI = "omopid_rxcui.csv"
PT_CHEBI = "pt_chebi-03132014.txt"
PT_DRUGBANK = "pt_drugbank-04082014.txt"
PT_RXCUI = "pt_rxcui-03132014.txt"
PT_UNII = "pt_unii-03202014.csv"
UNII_NUI_PREFERRED_NAME_ROLE = "unii_nui_preferrednamerole-05202014.txt"

CHEBI_BASE_URI = "http://purl.obolibrary.org/obo/"



## read mappings of pt and unii

pt_unii_cols = ['id','pt','unii']
pt_unii_DF = pd.read_csv(PT_UNII, sep='|', names=pt_unii_cols)
#print pt_unii_DF
#pt_unii_DF.to_csv('test.csv', sep='|')


## read mappings of pt and drugbank uri

pt_drugbank_cols = ['pt','db_uri1','db_uri2']
pt_drugbank_DF = pd.read_csv(PT_DRUGBANK, sep='\t', names=pt_drugbank_cols)


## read mappings of pt and rxcui

pt_rxcui_cols = ['pt','rxcui']
pt_rxcui_DF = pd.read_csv(PT_RXCUI, sep='\t', names=pt_rxcui_cols)


## read mappings of omop id and rxcui
omop_rxcui_cols = ['omopid','rxcui']
omop_rxcui_DF = pd.read_csv(OMOP_RXCUI, sep='|', names=omop_rxcui_cols)
#print omop_rxcui_DF.head()


## read mappings of pt and chebi

chebi_rxcui_cols = ['pt','chebi']
omop_rxcui_DF = pd.read_csv(PT_CHEBI, sep='\t', names=omop_rxcui_cols)
print omop_rxcui_DF.head()



## merge pt, unii and drugbank uri

pt_unii_db_DF = pt_unii_DF.merge(pt_drugbank_DF, on=['pt'], how='left')
print pt_unii_db_DF.info()


## merge pt, unii, drugbank uri, rxcui together

pt_unii_db_rxcui_DF = pt_unii_db_DF.merge(pt_rxcui_DF, on=['pt'], how='left')
print pt_unii_db_rxcui_DF
#print pt_unii_db_rxcui_DF.to_string()
#print pt_unii_db_rxcui_DF.head()
#pt_unii_db_rxcui_DF.to_csv('test.csv', sep='|')


## merge omop to pt_unii_db_rxcui_DF

pt_unii_db_rxcui_omop_DF = pt_unii_db_rxcui_DF.merge(omop_rxcui_DF, on=['rxcui'], how='left')
print pt_unii_db_rxcui_omop_DF.info()
