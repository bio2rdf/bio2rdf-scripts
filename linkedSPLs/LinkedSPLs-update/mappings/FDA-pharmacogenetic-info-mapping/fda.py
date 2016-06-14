##########------------------------------------------------------------------------------------------##########
########## Short script to grab FDA PGx Table and convert to pipe delimited csv with utf-8 encoding ##########
##########------------------------------------------------------------------------------------------##########

## Author: Solomon M. Adams ##
## University of Pittsburgh ##


import urllib.request
import csv
import re
from bs4 import BeautifulSoup
import codecs

def gettable(): ## Pulls and cleans the raw FDA table ##
    fdapgx = "http://www.fda.gov/drugs/scienceresearch/researchareas/pharmacogenetics/ucm083378.htm"
    rawtable = urllib.request.urlopen(fdapgx).read()
    soup = BeautifulSoup(rawtable, "lxml")
    table = soup.find("table", attrs={"class":"table table-striped tablesorter table-bordered"})
    rows = table.findAll("tr")
    cols = []
    raw = []
    for tr in rows:
        col = tr.findAll("td")
        col = [ele.text.strip() for ele in col]
        raw.append([ele for ele in col if ele])
    raw.remove(raw[0])
    for item in raw:
        item = str(item)
        cols.append(item.replace("\\r\\n", ""))
    writetable(cols)


def writetable(cols): ## Writes the table to CSV as pip delimited utf-8 ##
    file = codecs.open("FDA_PGx_Table.csv", "w", "utf-8-sig")
    out = csv.writer(file, delimiter = "|", quotechar = '"', quoting = csv.QUOTE_ALL)
    for item in cols:
        itemlist = []
        writelist = item.split("',")
        for line in writelist: ## These look messy, I'm sure there is a better way to get rid of the extra characters ##
            line = line.strip("['")
            line = line.strip("'")
            line = line.strip(" '")
            line = line.strip("' ")
            line = line.strip("']")
            line = line.replace(" (1)", "")
            line = line.replace(" (2)", "")
            line = line.replace(" (3)", "")
            line = line.replace(" (4)", "")
            itemlist.append(line)
        labellist = itemlist[4:len(itemlist)]
        out.writerow([itemlist[0], itemlist[1], itemlist[2], itemlist[3],(", ".join([str(x) for x in labellist]))])

if __name__ == "__main__":
    gettable()