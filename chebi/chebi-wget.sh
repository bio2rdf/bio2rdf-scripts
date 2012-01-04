# chebi-wget.sh

cd /bio2rdf/data/chebi

rm -rf /bio2rdf/data/chebi/*

wget ftp://ftp.ebi.ac.uk/pub/databases/chebi/generic_dumps/* --output-file=chebi.log

unzip *.zip
