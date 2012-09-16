@ECHO OFF

setlocal enabledelayedexpansion

set CLASSPATH=
for /f %%i in ( 'dir /b lib\*.jar' ) do set CLASSPATH=!CLASSPATH!lib\%%i;




REM Set the maximum RAM used by this program

set MEMORY=2g

REM set OPTS=-ea -Dcom.sun.management.jmxremote.ssl=false -Dcom.sun.management.jmxremote.authenticate=false -Dcom.sun.management.jmxremote.port=8010
set OPTS=-ea -Dlog4j.configuration=file:log4j.properties 


java -Xmx%MEMORY% -cp %CLASSPATH% %OPTS% com.dumontierlab.pdb2rdf.Pdb2Rdf %*