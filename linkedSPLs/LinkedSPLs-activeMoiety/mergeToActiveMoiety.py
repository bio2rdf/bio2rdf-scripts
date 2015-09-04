'''
Created 08/15/2014

@authors: Yifan Ning

@summary: merge preferred term, UNII, NUI, preferredNameAndRole, Drug bank URI, ChEBI URI, rxnorm URI, OMOP id, DrOn id for active moiety together.

'''

import os, sys
import pandas as pd
from StringIO import StringIO
import numpy as np

## Define namespaces
CHEBI_BASE_URI = "http://purl.obolibrary.org/obo/"
RXNORM_BASE_URI = "http://purl.bioontology.org/ontology/RXNORM/"

## Define data inputs
PT_UNII = "../LinkedSPLs-update/data/FDA/FDAPreferredSubstanceToUNII.txt"
UNII_RXCUI = "../LinkedSPLs-update/data/UMLS/UNIIs-Rxcuis-from-UMLS.txt"

PT_CHEBI = "mappings/UNIIToChEBI-06102015.txt"
PT_DRUGBANK = "mappings/fda-substance-preferred-name-to-drugbank-06102015.txt"

UNII_NUI_PREFERRED_NAME_ROLE = "mappings/EPC_extraction_most_recent_06102015.txt"
DRON_CHEBI_RXCUI = "mappings/cleaned-dron-chebi-rxcui-ingredient-06222015.txt"
OMOP_RXCUI = "mappings/active-ingredient-omopid-rxcui-09042015.dsv"

## Get UNII - PT - RXCUI
unii_pt_cols = ['unii','pt']
unii_pt_DF = pd.read_csv(PT_UNII, sep='\t', names=unii_pt_cols)

rxcui_unii_cols = ['rxcui','unii']
rxcui_unii_DF = pd.read_csv(UNII_RXCUI, sep='|', names=rxcui_unii_cols)
rxcui_unii_DF['rxcui'] = rxcui_unii_DF['rxcui'].astype('str')

unii_pt_rxcui_DF = unii_pt_DF.merge(rxcui_unii_DF, on=['unii'], how='left')
print unii_pt_rxcui_DF.info()

## read mappings of pt and drugbank uri

pt_drugbank_cols = ['pt','db_uri1','db_uri2']
pt_drugbank_DF = pd.read_csv(PT_DRUGBANK, sep='\t', names=pt_drugbank_cols)

## read mappings of pt and chebi

pt_chebi_cols = ['pt','chebi']
pt_chebi_DF = pd.read_csv(PT_CHEBI, sep='\t', names=pt_chebi_cols)

## read mappings of dron and rxcui
dron_chebi_rxcui_cols = ['dron','chebi','rxcui']
dron_chebi_rxcui_DF = pd.read_csv(DRON_CHEBI_RXCUI, sep='|', names=dron_chebi_rxcui_cols, usecols=[0,2])
dron_chebi_rxcui_DF['rxcui'] = dron_chebi_rxcui_DF['rxcui'].astype('str')

## read mappings of unii, nui and preferredNameAndRole
unii_nui_namerole_cols = ['setid', 'unii','nui','nameAndRole']
unii_nui_namerole_DF = pd.read_csv(UNII_NUI_PREFERRED_NAME_ROLE, sep='\t', names=unii_nui_namerole_cols)[['unii','nui','nameAndRole']]


## read mappings of omopid and rxcui
omop_rxcui_cols = ['omopid','rxcui']
omop_rxcui_DF = pd.read_csv(OMOP_RXCUI, sep='|', names=omop_rxcui_cols)
omop_rxcui_DF['rxcui'] = omop_rxcui_DF['rxcui'].astype('str')

## merge pt, unii, rxcui and drugbank uri
unii_pt_rxcui_db_DF = unii_pt_rxcui_DF.merge(pt_drugbank_DF, on=['pt'], how='left')

unii_pt_rxcui_db_DF.to_csv('PT-RXCUI-UNII-DB.csv', sep='\t', index=False)

## merge chebi 
merged_chebi_DF = unii_pt_rxcui_db_DF.merge(pt_chebi_DF, on=['pt'], how='left')

## merge dron id
merged_dron_DF = merged_chebi_DF.merge(dron_chebi_rxcui_DF, on=['rxcui'], how = 'left')

## merge omop id
merged_omop_DF = merged_dron_DF.merge(omop_rxcui_DF, on=['rxcui'], how = 'left')

## merge <nui> and <preferred name and role>
merged_epc_DF = merged_omop_DF.merge(unii_nui_namerole_DF, on=['unii'], how='left')

print merged_epc_DF.info()

merged_epc_DF.to_csv('mergedActiveMoiety.csv', sep='\t', index=False)

