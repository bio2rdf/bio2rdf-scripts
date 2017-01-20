import urllib2

qryUrlsL = ['https://dbmi-icode-01.dbmi.pitt.edu/sparql?default-graph-uri=&query=PREFIX+rdfs%3A+%3Chttp%3A%2F%2Fwww.w3.org%2F2000%2F01%2Frdf-schema%23%3E%0D%0APREFIX+foaf%3A+%3Chttp%3A%2F%2Fxmlns.com%2Ffoaf%2F0.1%2F%3E%0D%0APREFIX+linkedspls_vocabulary%3A+%3Chttp%3A%2F%2Fbio2rdf.org%2Flinkedspls_vocabulary%3A%3E%0D%0A%0D%0A%23%23+Get+metadata+for+the+SPLs+of+all+products+containing+a+drug+%23%23%0D%0ASELECT+%3Flabel+%3FsplId+%3Fversion+%3FsetId+%3Forg+%3Fdate+%3Fhomepage%0D%0AFROM+%3Chttp%3A%2F%2Fpurl.org%2Fnet%2Fnlprepository%2Fspl-core%3E%0D%0AWHERE+{%0D%0A%0D%0A%3FsplId+rdfs%3Alabel+%3Flabel.%0D%0A%3FsplId+dc%3Asubject+%3Chttp%3A%2F%2Fpurl.bioontology.org%2Fontology%2FRXNORM%2F1975177%3E.++%0D%0A%3FsplId+linkedspls_vocabulary%3AversionNumber+%3Fversion.%0D%0A%3FsplId+linkedspls_vocabulary%3AsetId+%3FsetId.%0D%0A%3FsplId+linkedspls_vocabulary%3ArepresentedOrganization+%3Forg.%0D%0A%3FsplId+linkedspls_vocabulary%3AeffectiveTime+%3Fdate.%0D%0A%3FsplId+foaf%3Ahomepage+%3Fhomepage.%0D%0A%0D%0A}&format=text%2Fhtml&timeout=0&debug=on','http://goo.gl/nU95fy','http://goo.gl/KfSGSR','http://goo.gl/9RFeL9']

for qryUrlStr in qryUrlsL:

    response = urllib2.urlopen(qryUrlStr)
    html = response.read()

    if html:
        print "[TESTING] query %s is OK" % (qryUrlStr)
    else:
        print "[WARNING] query %s not return any results" % (qryUrlStr)
