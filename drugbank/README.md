#DrugBank Database Parser

##Introduction

The Drugbank Database is a resource that describes and links drug information with drug target information.

##Requirements

* php5
* wget
* Bio2rdf API

##Description

* files - drugbank has two sets of files one for drugs and one for targets. This option specifies the file to process
* indir - the directory to download and parse from
* outdir - the directory to put result RDF
* download - sets the flag to download or not. Default value is false
* download_url - sets where to download the files from. Default is set to the currect download link