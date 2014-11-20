'''
Created 07/28/2014

@authors: Yifan Ning

@summary: merge UNIIs name to UNNIs record

'''

import os, sys
import csv

DIR_NAME = "backups/UNIIs 27Jun2014 Names.txt"
DIR_RECORD = "backups/UNIIs 27Jun2014 Records.txt"


# parse file have all names of UNNIs
# return a dict of names for specified type: SY, SN, CD

def parseTypeByUNII(DIR_NAME, UNII):
    dict_type = {}
    for name in open(DIR_NAME,'r').readlines():
        nameArr = name.split('\t')
        if nameArr[2] == UNII:
            if nameArr[1] == "SY":
                if "SY" in dict_type:
                    dict_type["SY"] = dict_type["SY"] + "|" + nameArr[0]
                else:
                    dict_type["SY"] = nameArr[0]
            if nameArr[1] == "SN":
                if "SN" in dict_type:
                    dict_type["SN"] = dict_type["SN"] + "|" + nameArr[0]
                else:
                    dict_type["SN"] = nameArr[0]
            if nameArr[1] == "CD":
   
                if "CD" in dict_type:
                    dict_type["CD"] = dict_type["CD"] + "|" + nameArr[0]
                else:
                    dict_type["CD"] = nameArr[0]

    return dict_type
    


def mergeNameForRecords(DIR_NAME, DIR_RECORD):

    index = 0
    for record in open(DIR_RECORD,'r').readlines():

        mergedline = record.strip('\n').strip('\r')

        #print mergedline

        if index == 0:
            print mergedline + "\t" + "SY" + "\t" + "SN" + "\t" + "CD"
        else:
            recordArr = record.split('\t')
            UNII = recordArr[0]

            #print "parsing name for " + UNII + " ********************************************"

            dict_type = parseTypeByUNII(DIR_NAME, UNII)

            mergedline = mergedline + "\t"

            if "SY" in dict_type:
                mergedline = mergedline + dict_type["SY"]

            mergedline = mergedline + "\t"

            if "SN" in dict_type:
                mergedline = mergedline + dict_type["SN"]

            mergedline = mergedline + "\t"

            if "CD" in dict_type:
                mergedline = mergedline + dict_type["CD"]

            print mergedline


        index = index +1



def merge():
    mergeNameForRecords(DIR_NAME, DIR_RECORD)


if __name__=="__main__":
    merge()
