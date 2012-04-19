#!/bin/sh

zcat $1 | perl /media/2tbdisk/bio2rdf/scripts/medline/medline2n3.pl | gzip > /media/2tbdisk/bio2rdf/n3/medline/$1.n3.gz
