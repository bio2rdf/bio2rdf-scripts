from lxml import etree
import os
import MySQLdb as mysql
import pdb

from dailymed_rss import unzip

NS = "{urn:hl7-org:v3}"  #namespace for dailymed spls

def check_loinc_table(loincs):
    excluded = set()
    conn = mysql.connect(user="root", passwd="5bboys", db="linkedSPLs")
    cursor = conn.cursor()
    for loinc in loincs:
        cursor.execute("SELECT * FROM loinc WHERE loinc='{0}'".format(loinc))
        results = cursor.fetchall()
        if not results:
            excluded.add(loinc)
    conn.close()
    return excluded

def get_sections(xmlRoot):
    sections = {}
    for sectionTag in xmlRoot.getiterator(tag=NS+"section"):
        for codeTag in sectionTag.getiterator(tag=NS+"code"):
            code = codeTag.get("code")
            name = codeTag.get("displayName")
            if code and name:
                if is_loinc(code):
                    sections[code] = name
    return sections

def is_loinc(code):
    if len(code) == 7:
        for i in xrange(7):
            if i == 5 and code[i] != "-":
                return False
            if i!=5 and not code[i].isdigit():
                    return False
        return True
    return False

def print_spl_sections(spls):
    allSections = {}
    for spl in spls:
        parser = etree.XMLParser(huge_tree=True)
        tree = etree.parse(spl, parser=parser)
        document = tree.getroot()
        sections = get_sections(document)
        allSections.update(sections)
    excluded = check_loinc_table(allSections)
    print "Unique LOINC Sections:\n\n"
    for loinc, name in allSections.items():
        print "{0}\t{1}".format(loinc, name)
    print "\n\nUnrecognized LOINC Sections:\n\n"
    for loinc in excluded:
        print "{0}\t{1}".format(loinc, allSections[loinc])

if __name__ == "__main__":
    splDir = "/home/PITT/gag30/spls"
    splFiles = [os.path.join(splDir, f) for f in os.listdir(splDir)]
    print_spl_sections(splFiles)

