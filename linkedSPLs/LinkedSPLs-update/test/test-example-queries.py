import urllib2

qryUrlsL = ["https://goo.gl/Z3CAuz","https://goo.gl/dYHEQe","https://goo.gl/UsnWRI","https://goo.gl/z7HJdM","https://goo.gl/YHjIcB ","https://goo.gl/0Ogeka","https://goo.gl/64JQlU","https://goo.gl/XmphPo","https://goo.gl/gOZbHF","https://goo.gl/VgSfw6","https://goo.gl/kS8agv","https://goo.gl/Q4RISO","https://goo.gl/ujJL5s","https://goo.gl/eY2ljk","http://tinyurl.com/zauxa8b","https://goo.gl/AsbpBQ","https://goo.gl/al1r0y","https://goo.gl/fp3syi","https://goo.gl/eQi5pf","https://goo.gl/nocS8m","http://tinyurl.com/z3wrh3j","http://tinyurl.com/z3wrh3j","http://tinyurl.com/hx32ty3","http://tinyurl.com/jcty9qn","http://tinyurl.com/gpxglqf","http://tinyurl.com/hrulbmn","https://goo.gl/WYIzYz","https://goo.gl/Hr1HMX","https://goo.gl/WhpOFO","https://goo.gl/jZ0jpi","https://goo.gl/bH6gA1","https://goo.gl/fr1WoQ","https://goo.gl/fFFLwN","https://goo.gl/YyIbec","https://goo.gl/F9DF3N","https://goo.gl/hoKbw6","https://goo.gl/yIzjo1","https://goo.gl/nMjTjE"]

for qryUrlStr in qryUrlsL:

    response = urllib2.urlopen(qryUrlStr)
    html = response.read()

    if html:
        print "[TESTING] query %s is OK" % (qryUrlStr)
    else:
        print "[WARNING] query %s not return any results" % (qryUrlStr)



