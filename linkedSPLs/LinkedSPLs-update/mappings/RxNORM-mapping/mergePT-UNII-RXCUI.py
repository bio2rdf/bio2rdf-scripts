'''
Created 11/25/2014

@authors: Yifan Ning

@summary: merge preferred term, UNII, rxcui by join mappings of UNII from UMLS and rxcui with mappings of FDA Preferredterm and UNII.

'''

import os, sys
import pandas as pd
from StringIO import StringIO
import numpy as np

PT_UNII = "../../data/FDA/FDAPreferredSubstanceToUNII.txt"
UNII_RXCUI = "../../data/UMLS/UNIIs-Rxcuis-from-UMLS.txt"
PT_UNII_RXCUI_OUT = "pt_unii_rxcui_test.txt"
PT_RXCUI_OUT = "pt_rxcui_test.txt"

if len(sys.argv) > 3:
    PT_UNII = str(sys.argv[1])
    UNII_RXCUI = str(sys.argv[2])
    PT_UNII_RXCUI_OUT = str(sys.argv[3])
    PT_RXCUI_OUT = str(sys.argv[4])
else:
    print "Usage: mergePT-UNII-RXCUI.py <mapppings of UNII and PT> <mappings of UNII and RXCUI> <OUTPUT PT-UNII-RXCUI> <OUTPUT PT-RXCUI>"
    sys.exit(1)

unii_pt_cols = ['unii','pt']
unii_pt_DF = pd.read_csv(PT_UNII, sep='\t', names=unii_pt_cols)

rxcui_unii_cols = ['rxcui','unii']
rxcui_unii_DF = pd.read_csv(UNII_RXCUI, sep='|', names=rxcui_unii_cols)


pt_unii_rxcui_DF = unii_pt_DF.merge(rxcui_unii_DF, on=['unii'], how='left')
#print pt_unii_rxcui_DF.info()

pt_rxcui_DF = unii_pt_DF.merge(rxcui_unii_DF, on=['unii'], how='right')

pt_rxcui_DF[pt_rxcui_DF.pt.notnull()]
#pt_rxcui_DF.dropna(how="all", inplace=True)
#print pt_rxcui_DF.info()

pt_unii_rxcui_DF.to_csv(PT_UNII_RXCUI_OUT, sep='\t',index=False)
pt_rxcui_DF[pt_rxcui_DF.pt.notnull()].ix[:,[1,2]].to_csv(PT_RXCUI_OUT, sep='\t', index=False)
