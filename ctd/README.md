#Comparative Toxicogenomics Database Parser

##Database Description

The Comparative Toxicogenomics Database(CTD) is a database of curated interactions between genes, chemicals, and diseases. The goal to facilitate the discovery of environmental chemicals that affect human health. If you wish to learn more about the CTD you can visit their website http://ctdbase.org .

##Requirements

Below are the requirements to run the script:

*php5
*wget

##Description

The following options are available:

* indir - specifies the directory path to download files to or parse already downloaded files
* outdir - specifies the directory path to put RDF into
* download - can either be TRUE or FALSE and sets the flag to download files from database.
* files - tag to specify a specific file to download/parse  from the CTD. THe list of options can be viewed by typing php ctd.php and enter.
* conf-file-path - configuration file written in RDF to configure the parser. Usually you won't need to touch this and the default will be used.
* use-conf-file - boolean flag (TRUE or FALSE) to use a configuration file the default is false.

