#!/bin/sh
CLASSPATH=""
for i in $( ls lib/*.jar );
do 
CLASSPATH="$CLASSPATH$i:" 
done

#Set the maximum RAM used by this program
MEMORY=2g

#OPTS="-ea -Dcom.sun.management.jmxremote.ssl=false -Dcom.sun.management.jmxremote.authenticate=false -Dcom.sun.management.jmxremote.port=8010"
OPTS="-ea"

java -Xmx$MEMORY -cp $CLASSPATH $OPTS com.dumontierlab.pdb2rdf.cluster.ClusterServer $@