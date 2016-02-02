#!/bin/bash
rdfproc -c dron-drug query sparql - '       
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> PREFIX owl: <http://www.w3.org/2002/07/owl#> PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> PREFIX dron: <http://purl.obolibrary.org/obo/dron#>   PREFIX obo: <http://purl.obolibrary.org/obo/>

SELECT * WHERE {   ?dron obo:DRON_00010000 ?rxcui. }' > dron-rxcui-drug.txt
