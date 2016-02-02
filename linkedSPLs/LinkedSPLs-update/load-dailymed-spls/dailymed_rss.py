'''
Created 03/15/2012

@auther: gag30

@summary: Download and extract all of the xml files for drugs updated on Dailymed within the past 7 days.
          Extracted xml files are saved to ./spls and ./spls/updates, both of which are created if they 
          don't exist.
'''


import feedparser
from lxml.html import fromstring
import os
import shutil
import string
import sys
import time
import urllib
import urllib2
from zipfile import ZipFile, is_zipfile
import pdb

## Remove the zip files that were downloaded from Dailymed/any 
## contents of tmpdir
def clean(tmpdir):
    files = [tmpdir + f for f in os.listdir(tmpdir)]
    for f in files:
        try:
            os.remove(f)
        except OSError, err:
            print "Couldn't delete " + f + ": " + err 
    try:
        os.rmdir(tmpdir)
    except OSError, err:
        print "Couldn't delete " + tmpdir + ": " + err 

## After the zip files have been downloaded and extracted to 
## the temp folder, copy them to ./updateDir
## (./spls/updates by default)
def copy_xml(tmpdir, spldir):
    tmpFiles = [tmpdir + f for f in os.listdir(tmpdir)]
    updateDir = os.path.join(spldir, "updates")
    for f in os.listdir(updateDir):
        os.remove(os.path.join(updateDir, f))
    for tmpFile in tmpFiles:
        if tmpFile.endswith(".xml"):
            shutil.copy(tmpFile, updateDir)

## Display download progress, updated on a single line
def dl_progress(current, total):
    percent = int(float(current) / float(total) * 100)
    message = " ".join([str(percent) + "%", "downloaded (" + str(current), "of", str(total) + ")"])
    if percent == 100:
        message += "...done\n"
    sys.stdout.write("\r\x1b[K" + message)
    sys.stdout.flush()

## No longer used
def get_download_name(title):
    name = ""
    for char in title:
        if char in string.uppercase:
            name += char
        elif char == " ":
            name += "%20"
        else:
            return name.strip("%20")

## Parse html for a link to xml file for a single drug
def get_xml_url(url):
    usock = urllib2.urlopen(url)
    html = usock.read()
    usock.close()
    
    baseurl = "http://dailymed.nlm.nih.gov"
    root = fromstring(html)
    for div in root.iter("div"):
        if div.get("id") == "options":
            for link in div.iter("a"):
                href = link.get("href")
                if "getFile.cfm?id" in href and "type=zip" in href:
                    return baseurl + href
    return None

##Try to get the xml file url from url num
##times before failing
def get_xml_url_retry(url, num):
    cnt = 0
    while cnt < num:
        cnt += 1
        xmlUrl = get_xml_url(url)
        if xmlUrl:
            return xmlUrl
        time.sleep(1)
    return None

## Create a directory if one doesn't exist, else continue
def make_dir(name):
    try:
        os.mkdir(name)
    except OSError:
        pass

## Extract xml files from downloaded zip files
## to a temp dir
def unzip(tmpdir):
    files = [tmpdir + f for f in os.listdir(tmpdir)]
    for f in files:
        try:
            zipfile = ZipFile(f)
            contents = zipfile.infolist()
            for c in contents:
                if c.filename[-4:] == ".xml":
                    zipfile.extract(c, tmpdir)
        except:
            print "Downloaded file {0} does not appear to be a zip file!".format(f)
            sys.exit(1)

## Get the Dailymed rss feed for drugs updated within the past 7 days.
## Download the files for each, extract the spl and copy it to a
## master directory of spls and directory for spls contained in the 
## current update.  Delete downloaded files when finished.
def run():
    TMPDIR = "tmp_spls/"
    SPLDIR = "spls/"
    rssparser = feedparser.parse('http://dailymed.nlm.nih.gov/dailymed/rss.cfm')
    make_dir(TMPDIR)
    make_dir(SPLDIR)
    make_dir(os.path.join(SPLDIR, "updates"))
    for ctr, entry in enumerate(rssparser['entries']):
        #downloadURL = get_xml_url_retry(entry['link'], 3)
        #downloadURL = downloadURL[:downloadURL.index("name=")]
        setid = entry['id'].split('setid=')[1]
        downloadURL = "http://dailymed.nlm.nih.gov/dailymed/downloadzipfile.cfm?setId={0}".format(setid)
        #dailymedid = downloadURL.split("id=")[1].split("&")[0]
        filename = os.path.join(TMPDIR, setid + ".zip")
        urllib.urlretrieve(downloadURL, filename)
        dl_progress(ctr+1, len(rssparser['entries']))
    unzip(TMPDIR)
    copy_xml(TMPDIR, SPLDIR)
    clean(TMPDIR)
    
if __name__=="__main__":
    run()

