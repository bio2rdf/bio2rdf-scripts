'''
Created 1/24/2012

@authors: Richard Boyce, Greg Gardner

@summary: Iterate over a bunch of SPLs and load the text into a SQL database (schema created by db_import_dailymed.php)

        Script run from
        med-info-corpora/phase-I-package-inserts/lodd-dailymed-subset.
        Requires a comma delimited file with SPL file names

Mapping of sections to tables is done using the following section heading map from http://www.fda.gov/ForIndustry/DataStandards/StructuredProductLabeling/ucm162057.htm:

LOINC OID: 2.16.840.1.113883.6.1

LOINC Code
	
LOINC Name
34086-9 	ABUSE SECTION
60555-0 	ACCESSORIES
34084-4 	ADVERSE REACTIONS SECTION
34091-9 	ANIMAL PHARMACOLOGY & OR TOXICOLOGY SECTION
60556-8 	ASSEMBLY OR INSTALLATION INSTRUCTIONS
34066-1 	BOXED WARNING SECTION
60557-6 	CALIBRATION INSTRUCTIONS
34083-6 	CARCINOGENESIS & MUTAGENESIS & IMPAIRMENT OF FERTILITY SECTION
34090-1 	CLINICAL PHARMACOLOGY SECTION
60558-4 	CLEANING, DISINFECTING, AND STERILIZATION INSTRUCTIONS
34092-7 	CLINICAL STUDIES SECTION
60559-2 	COMPONENTS
34070-3 	CONTRAINDICATIONS SECTION
34085-1 	CONTROLLED SUBSTANCE SECTION
34087-7 	DEPENDENCE SECTION
34089-3 	DESCRIPTION SECTION
34068-7 	DOSAGE & ADMINISTRATION SECTION
43678-2 	DOSAGE FORMS & STRENGTHS SECTION
34074-5 	DRUG & OR LABORATORY TEST INTERACTIONS SECTION
42227-9 	DRUG ABUSE AND DEPENDENCE SECTION
34073-7 	DRUG INTERACTIONS SECTION
50742-6 	ENVIRONMENTAL WARNING SECTION
50743-4 	FOOD SAFETY WARNING SECTION
34072-9 	GENERAL PRECAUTIONS SECTION
34082-8 	GERIATRIC USE SECTION
50740-0 	GUARANTEED ANALYSIS OF FEED SECTION
69719-3 	HEALTH CLAIM SECTION
34069-5 	HOW SUPPLIED SECTION
51727-6 	INACTIVE INGREDIENT SECTION
34067-9 	INDICATIONS & USAGE SECTION
50744-2 	INFORMATION FOR OWNERS/CAREGIVERS SECTION
34076-0 	INFORMATION FOR PATIENTS SECTION
59845-8 	INSTRUCTIONS FOR USE SECTION
60560-0 	INTENDED USE OF THE DEVICE
34079-4 	LABOR & DELIVERY SECTION
34075-2 	LABORATORY TESTS SECTION
43679-0 	MECHANISM OF ACTION SECTION
49489-8 	MICROBIOLOGY SECTION
43680-8 	NONCLINICAL TOXICOLOGY SECTION
34078-6 	NONTERATOGENIC EFFECTS SECTION
34080-2 	NURSING MOTHERS SECTION
60561-8 	OTHER SAFETY INFORMATION
34088-5 	OVERDOSAGE SECTION
55106-9 	OTC - ACTIVE INGREDIENT SECTION
50569-3 	OTC - ASK DOCTOR SECTION
50568-5 	OTC - ASK DOCTOR/PHARMACIST SECTION
50570-1 	OTC - DO NOT USE SECTION
50565-1 	OTC - KEEP OUT OF REACH OF CHILDREN SECTION
53414-9 	OTC - PREGNANCY OR BREAST FEEDING SECTION
55105-1 	OTC - PURPOSE SECTION
53413-1 	OTC - QUESTIONS SECTION
50566-9 	OTC - STOP USE SECTION
50567-7 	OTC - WHEN USING SECTION
51945-4 	PACKAGE LABEL.PRINCIPAL DISPLAY PANEL
68498-5 	PATIENT MEDICATION INFORMATION SECTION
34081-0 	PEDIATRIC USE SECTION
43681-6 	PHARMACODYNAMICS SECTION
66106-6 	PHARMACOGENOMICS SECTION
43682-4 	PHARMACOKINETICS SECTION
42232-9 	PRECAUTIONS SECTION
42228-7 	PREGNANCY SECTION
43683-2 	RECENT MAJOR CHANGES SECTION
34093-5 	REFERENCES SECTION
53412-3 	RESIDUE WARNING SECTION
60562-6 	ROUTE, METHOD AND FREQUENCY OF ADMINISTRATION
50741-8 	SAFE HANDLING WARNING SECTION
48779-3 	SPL INDEXING DATA ELEMENTS SECTION
48780-1 	SPL PRODUCT DATA ELEMENTS SECTION
42231-1 	SPL MEDGUIDE SECTION
42230-3 	SPL PATIENT PACKAGE INSERT SECTION
42229-5 	SPL UNCLASSIFIED SECTION
69718-5 	STATEMENT OF IDENTITY SECTION
44425-7 	STORAGE AND HANDLING SECTION
60563-4 	SUMMARY OF SAFETY AND EFFECTIVENESS
34077-8 	TERATOGENIC EFFECTS SECTION
43684-0 	USE IN SPECIFIC POPULATIONS SECTION
54433-8 	USER SAFETY WARNINGS SECTION
50745-9 	VETERINARY INDICATIONS SECTION
43685-7 	WARNINGS AND PRECAUTIONS SECTION
34071-1 	WARNINGS SECTION
71744-7	        HEALTH CARE PROVIDER LETTER SECTION
38056-8	        SUPPLEMENTAL PATIENT MATERIAL SECTION
69763-1	        DISPOSAL AND WASTE HANDLING

'''
import logging
import os, sys
from lxml import etree
from lxml.etree import XMLParser, parse
import MySQLdb as mdb
import shutil
from rxnorm import setid_in_rxnorm
from get_spl_sections import get_sections

SPL_DIR = "spls/"
UPDATE_DIR = "spls/updates/"

NS = "{urn:hl7-org:v3}"  #namespace for dailymed spls

## database connection
con = None
try:
    con = mdb.connect('localhost', 'root', '5bboys', 'linkedSPLs')     
    con.set_character_set('utf8')
    cursor = con.cursor()
except mdb.Error, e:  
    print "Error %d: %s" % (e.args[0], e.args[1])
    sys.exit(1)

# several to add from the above list
tableToSectionMap = [ 
                     ("34070-3", "contraindications"), 
                     ("34084-4", "adverse_reactions"), 
                     ("34088-5", "overdosage"), 
                     ("51727-6", "inactiveIngredient"),
                     ("34068-7", "dosage_and_administration"), 
                     ("34067-9", "indications_and_usage"), 
                     ("42232-9", "precautions"), 
                     ("34069-5", "how_supplied"), 
                     ("34089-3", "description"), 
                     ("34090-1", "clinical_pharmacology"), 
                     ("34066-1", "boxed_warning"),
                     ("34073-7", "drug_interactions"),
                     ("43684-0", "specific_populations"),
                     ("34092-7", "clinical_studies")
                     ]

##gag30: Tables that the script currently inserts into
ENABLED_TABLES = ["spl_has_active_moiety", "structuredProductLabelMetadata", "active_moiety"]

##gag: At some point it might be nice to set up a nicer create database script
##      using sqlalchemy...for now, let's just empty the tables we're using
##      rather than deleting them, avoiding the need to recreate them.
def clear_tables():
    cursor.execute("SELECT * FROM loinc")
    tables = cursor.fetchall()
    for table in tables:
        cursor.execute("DELETE FROM `{0}`".format(table[2]))
        cursor.execute("ALTER TABLE `{0}` AUTO_INCREMENT=1".format(table[2]))
    for table in ENABLED_TABLES:
        cursor.execute("DELETE FROM `{0}`".format(table))
        cursor.execute("ALTER TABLE `{0}` AUTO_INCREMENT=1".format(table))

##Copy a spl to the master spl directory, making sure
##to delete the previous file with setid if it exists
def copy_to_master_dir(splFile, setid=None):
    if setid:
        cursor.execute("SELECT filename FROM structuredProductLabelMetadata WHERE setId=%s",setid)
        oldFilename = cursor.fetchone()[0]
        try:
            os.remove(os.path.join(SPL_DIR, oldFilename))
        except OSError as e:
            print "\nWARNING: Attempt to remove %s yielded OSError %s, SPL file does not exist?" % (oldFilename, e.strerror)
    shutil.copy(splFile, SPL_DIR)

##Get section with code = code
def get_section(root, code):
    for secTag in root.getiterator(tag=NS + "section"):
        secIter = secTag.getiterator(tag=NS + "code")
        for childElt in secIter:
            codeTagCode = childElt.get("code")
            if codeTagCode == code:
            #     print "codeTagCode: %s" % codeTagCode

                # section are sometimes embedded so, if necessary,
                # find the section that is the parent of this tag
                subSections = [el for el in secTag.iter(tag=NS+"section")]
                if not subSections:
                    return (secTag, childElt)

                for childSec in secTag.iter(tag=NS + "section"):
                    subcode = childSec.find(NS + "code")
                    if subcode == None:
                        continue
                    if subcode.get("code") == codeTagCode:
                        return (childSec, childElt)
    return (None, None)

##gag: Let's generalize getting a value for a tag
##so we don't load up on various get_tag functions.
##This covers most of the tags we get.
def get_tag_value(root, tag, value, attr=None):
    for tag in root.getiterator(tag=NS + tag):
        if tag.find(NS + value) is not None:
            if attr:
                return tag.find(NS + value).get(attr)
            else:
                return tag.find(NS + value).text
    return None

##Get the values for all occurrences of tag
def get_tag_values(root, tag, value, attr=None):
    values = []
    for tag in root.iter(tag=NS + tag):
        subTag = tag.find(NS+value)
        if subTag is None:
            continue
        if attr:
            values.append(subTag.get(attr))
        else:
            values.append(subTag.text)
    return values

##gag: This will cover some more
def get_tag_attr(root, tag, attr):
    for tag in root.getiterator(tag=NS + tag):
        if tag.get(attr) is not None:
            return tag.get(attr)
    return None

##Get the tags for the structuredProductLabelMetaData table
def get_tags(root, splF, logger):
    tags = {}
    tags['setId'] = get_tag_attr(root, "setId", "root")
    check_var(tags['setId'], "setId", splF, logger)

    tags['versionNumber'] = get_tag_attr(root, "versionNumber", "value") 
    check_var(tags['versionNumber'], "versionNumber", splF, logger)

    tags['activeMoieties'] = get_tag_values(root, "activeMoiety", "name")
    tags['activeMoieties'] = [x.upper() for x in tags['activeMoieties']] # upper case to simplify ontology and linked data mappings 
    check_var(tags['activeMoieties'], "activeMoiety", splF, logger)
    
    # The UNIIs for each active moiety should be retrieved in the same
    # order as the active moieties themselves
    tags['activeMoietyUNIIs'] = get_tag_values(root, "activeMoiety", "code", "code")
    check_var(tags['activeMoietyUNIIs'], "activeMoietyUNIIs", splF, logger)

    # if there is not the same number of moiety names and UNIIs then
    # something is wrong so return an empty dictionary
    if len(tags['activeMoieties']) != len(tags['activeMoietyUNIIs']):
        return {}

    tags['fullName'] = get_tag_value(root, "manufacturedProduct", "name")
    if not tags['fullName']:
        tags['fullName'] = get_tag_value(root, "manufacturedMedicine", "name")
        check_var(tags['fullName'], "fullName", splF, logger)

    tags['routeOfAdministration'] = get_tag_value(root, "substanceAdministration", "routeCode", attr="displayName")
    check_var(tags['routeOfAdministration'], "substanceAdministration", splF, logger)

    tags['genericMedicine'] = get_tag_value(root, "genericMedicine", "name")
    check_var(tags['genericMedicine'], "genericMedicine", splF, logger)

    tags['representedOrganization'] = get_tag_value(root, "representedOrganization", "name")
    check_var(tags['representedOrganization'], "representedOrganization", splF, logger)
    
    ##gag: Get insert date
    tags['effectiveTime'] = get_effective_time(root)
    check_var(tags['effectiveTime'], "effectiveTime", splF, logger)

    tags['filename'] = splF.split("/")[-1]
    
    return tags

##gag: Help clean up the main code a little
def check_var(var, name, filename, logger):
    if not var:
        logger.warning(name + " not found for " + filename)

##gag: Get the effective time of the insert itself, which is 
##       under <document> <effectiveTime value=yyyymmdd>
def get_effective_time(root):
    if root.find(NS + "effectiveTime") is not None:
        return root.find(NS + "effectiveTime").get("value")
    return None

##gag: We can solve the text/tail problem using recursion (lxml.etree actually has a 
##     recursive function for this purpose, but since we want a mix of text and html
##     it works out well that I recreated it anyway).
##     This function traverses the xml tree in a depth first fashion (i.e. we're 
##     still using Element.getiterator()).  However, the Element.text is added
##     when the element is reached, but Element.tail is only added after all of the
##     text and tail attributes of the element's children.
def get_section_text(section, sectionText):
    for elem in section.getiterator():
        if elem.tag.lower() == NS + "table":
            return "".join([sectionText, "\n\n", etree.tostring(elem, pretty_print=True),"\n\n"])
        elif has_parent(elem, "table"):
            return sectionText
        if elem.text:
            sectionText += elem.text
        if elem.tag.lower() == NS + "br":
            sectionText += "\n"            
        children = list(elem)
        for child in children:
            sectionText = get_section_text(child, sectionText)
        if elem.tail:
            return sectionText + elem.tail
    return sectionText

##Determine if a section table contains an entry
##for the spl being updated
def has_entry(table, key, rowid):
    cursor.execute("SELECT * FROM `{0}` WHERE `{1}`={2}".format(table, key, rowid))
    row = cursor.fetchone()
    if row:
        return True
    return False

##Determine if <lxml.etree.Element elem> has a parent element with tag=tag.
def has_parent(elem, tag):
    tableAncestors = [el for el in elem.iterancestors(tag = NS+tag)]
    if tableAncestors:
        return True
    return False

##Try to insert an active moiety and its UNII into the active_moiety table
def insert_active_moieties(activeMoieties, activeMoietyUNIIs):
    #print "%s\n%s" % (activeMoieties, activeMoietyUNIIs)
    for i,v in enumerate(activeMoieties):
        query = "INSERT INTO active_moiety (name, UNII) VALUES (%s, %s) ON DUPLICATE KEY UPDATE id=id"
        values = [v, activeMoietyUNIIs[i]]
        cursor.execute(query, values)

##Insert a new section entry if an updated spl contains 
##a new section
def insert_section_entry(table, splid, fieldText):
    query = "INSERT INTO `{0}` (`splId`,`field`) VALUES (%s, %s)".format(table)
    values = [splid, fieldText]
    cursor.execute(query, values)

##Insert values for the many to many relationship
##between structuredProductLabelMetaData and active_moiety
##into the join table spl_has_active_moiety
def link_spl_to_active_moieties(setId, activeMoieties):
    cursor.execute("SELECT id FROM structuredProductLabelMetadata WHERE setId=%s",[setId])
    splid = cursor.fetchone()[0]
    for am in activeMoieties:
        cursor.execute("SELECT id FROM active_moiety WHERE name=%s",[am])
        try:
            amid = cursor.fetchone()[0]
        except:
            pdb.set_trace()
        cursor.execute("INSERT IGNORE INTO spl_has_active_moiety VALUES (%s,%s)",(splid, amid))
        
##Display db loading progress
def print_progress(current, total, filename):
    percent = int(float(current) / float(total) * 100)
    message = " ".join([str(percent) + "%", "loaded (" + str(current), "of", str(total) + ")", filename])
    if percent == 100:
        message += "...done\n"
    sys.stdout.write("\r\x1b[K" + message)
    sys.stdout.flush()
    

##Insert new spls into database
def run(logger, spls, limit=None):        
    count = 0
    
    for splF in spls:
        print "\n Start parsing: {0}".format(splF)
        #tree = etree.ElementTree(file=splF)
        p = XMLParser(huge_tree=True)
        tree = parse(splF, parser=p)
        root = tree.getroot()        
        tags = get_tags(root, splF, logger)

        #print "[DEBUG] tags: " + str(tags)
        
        if len(tags.keys()) == 0:
            print "\nERROR: get_tags failed, most likely because a UNII could not be retrieved for all active moities. Please check the following spl: %s" % splF
            continue
        if not setid_in_rxnorm(tags['setId']):
            logger.info("SetId {0} from file {1} not found in rxnorm".format(tags['setId'],splF))
            continue

        ## check if there are deplicated setId
        cursor.execute("SELECT id FROM structuredProductLabelMetadata WHERE setId=%s",[tags['setId']])
        idExists = cursor.fetchall()
        if idExists:
            print "\n duplicated setId %s in file %s" % (tags['setId'], splF)
            continue
        
        try:
            
            insert_active_moieties(tags['activeMoieties'], tags['activeMoietyUNIIs'])
            insertQuery = "INSERT INTO structuredProductLabelMetadata(setId, versionNumber, fullName, routeOfAdministration, genericMedicine, representedOrganization, effectiveTime, filename) VALUES(%s,%s,%s,%s,%s,%s,%s,%s)"
            values = (tags['setId'], tags['versionNumber'], tags['fullName'], tags['routeOfAdministration'], tags['genericMedicine'], tags['representedOrganization'], tags['effectiveTime'], tags['filename'])
            cursor.execute(insertQuery, values)
            link_spl_to_active_moieties(tags['setId'], tags['activeMoieties'])
            cursor.execute("SELECT id FROM structuredProductLabelMetadata WHERE setId=%s",[tags['setId']])
            splId = cursor.fetchone()[0]

            #print "[DEBUG] insert active moieties - done"
        
            splSections = get_sections(root)

            #print "[DEBUG] splSections: " + str(splSections)
            
            for code in splSections:

                cursor.execute("SELECT table_name FROM loinc WHERE loinc='{0}'".format(code))
                res = cursor.fetchone
                if res:
                    table = cursor.fetchone()[0]
                else:
                    logger.info("Filename: {0}\tSetId: {1}\t no table name from loinc".format(tags['filename'],tags['setId']))
                    continue

                (sectElt, codeElt) = get_section(root, code)

                #gag: Recursive function to retrieve text from a section
                allText = get_section_text(sectElt, "")

                cursor.execute("INSERT INTO `{0}`(splId, field) VALUES({1}, '{2}')".format(table, splId, allText.encode('utf8').replace("'","\\'")))


            logger.info("Filename: {0}\tSetId: {1}\tadded".format(tags['filename'],tags['setId']))
            count +=1
            if limit is not None and count == limit:
                break
            if len(spls) > 1:
                print_progress(spls.index(splF)+1, len(spls), splF)
            
            con.commit()

        except mdb.Error, e:  
            print "Error %d: %s" % (e.args[0], e.args[1])
            con.rollback()
            con.commit()
            os.rename ("spls/{0}".format(splF),"problematic-spls/{0}".format(splF))
            continue
        except:
            print "Unexpected error:", sys.exc_info()[0]
            con.rollback()
            con.commit()
            continue


##Create custom logger
def get_logger(filename, loggername, level):
    logging.basicConfig(
                        filename=filename,
                        format="%(asctime)-6s: %(levelname)s - %(message)s")
    logger = logging.getLogger(loggername)
    logger.setLevel(level)
    return logger

##Update the database with spls contained in UPDATE_DIR.  
##Will insert new values into database if spl setid is not found in the database.
def update(logger):
    spls = [os.path.join(UPDATE_DIR, f) for f in os.listdir(UPDATE_DIR) if f.endswith(".xml")]
    for cnt, spl in enumerate(spls):
        p = XMLParser(huge_tree=True)
        tree = parse(spl, parser=p)
        root = tree.getroot()
        tags = get_tags(root, spl, logger)
        if len(tags.keys()) == 0:
            print "\nERROR: get_tags failed, most likely because a UNII could not be retrieved for all active moities. Please check the following spl: %s" % spl
            continue
        if not setid_in_rxnorm(tags['setId']):
            logger.info("SetId {0} from file {1} not found in rxnorm".format(tags['setId'],spl))
            continue
        cursor.execute("SELECT id FROM structuredProductLabelMetadata WHERE setId=%s",tags['setId'])
        rowid = cursor.fetchone()
        if rowid:
            rowid = rowid[0]
            copy_to_master_dir(spl, setid=tags['setId'])
            for name, value in tags.items():
                if name == 'activeMoieties':
                    insert_active_moieties(value, tags["activeMoietyUNIIs"])
                    link_spl_to_active_moieties(tags['setId'], value)
                elif name == "activeMoietyUNIIs":
                    continue
                else:
                    update_db("structuredProductLabelMetadata", name, value, "id", rowid)
            splSections = get_sections(root)
            for code in splSections:
                try:
                    cursor.execute("SELECT table_name FROM loinc WHERE loinc={0}".format(code))
                    table = cursor.fetchone()[0]
                except TypeError:
                    logger.debug("LOINC code not for SPL section found in the database: %s. This section will not be loaded for spl. Try updating the LOINC codes and re-loading this SPL.")
                    continue
            #for sM in tableToSectionMap:
            #    (code, table) = (sM[0], sM[1])
                (sectElt, codeElt) = get_section(root, code)
    
            #    if sectElt is None:
            #        logger.info("No section: %s, %s" % sM)
            #        continue
    
                ##gag: Recursive function to retrieve text from a section
                allText = get_section_text(sectElt, "")

                ##If the section already existed in the spl, update it's entry,
                ##else add a new entry for the section for that spl
                if has_entry(table, "splId", rowid):
                    update_db(table, "field", allText, "splId", rowid)
                else:
                    insert_section_entry(table, rowid, allText)
            logger.info(tags['setId'] + " updated")
        else:
            copy_to_master_dir(spl)
            run(logger, [spl])
        print_progress(cnt+1, len(spls), spl)
    con.commit()
    con.close()

##Wrapper around the UPDATE SQL syntax.
def update_db(tablename, field, value, key, rowid):
    names = [tablename, field, key]
    values = [value, rowid]
    query = "UPDATE `{0}` SET `{1}`= %s  WHERE `{2}` = %s".format(*names)
    cursor.execute(query, values)
    
if __name__ == "__main__":
    ##If run as its own script, clear and repopulate the database tables with the 
    ##spls in SPL_DIR
    logger = get_logger('loadDailymedToSql.log', 'main', logging.INFO)
    spls = [SPL_DIR + f for f in os.listdir(SPL_DIR) if f.endswith(".xml")]
    #clear_tables()
    #run(logger, spls, limit=100)
    run(logger, spls)

    if con:
        con.commit()
        con.close()
