
f = open("/tmp/t1.txt",'r')
buf = f.read()
f.close()

easyL = []
li = buf.split("\r\n")
for elt in li:
    if not elt:
       break 

    #print elt

    splIdx = elt.find("$")    
    spl = elt[0:splIdx]
    ss = elt.replace(spl + "$","")
    
    uniiIdx = ss.find("$N")    
    unii = ss[0:uniiIdx]
    sss = ss.replace(unii + "$","")

    #print spl
    #print unii

    clsL = sss.split("$")
    #print "%s" % clsL
    for cls in clsL:
        #print "%s" % cls

        (NUI,desc) = cls.split("|")
        easyL.append((spl,unii,NUI,desc))

for tpl in easyL:
    print "\t".join(tpl)
