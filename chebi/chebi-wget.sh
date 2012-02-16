# chebi-wget.sh
set -e

DOWNLOAD_DIR=$1

cd $DOWNLOAD_DIR

rm -rf /bio2rdf/data/chebi/*

wget ftp://ftp.ebi.ac.uk/pub/databases/chebi/generic_dumps/* --output-file=chebi.log

unzip *.zip
gunzip *.gz
