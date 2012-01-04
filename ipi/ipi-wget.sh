# ipi-wget.sh

cd /media/twotb/bio2rdf/data/ipi
rm -rf /media/twotb/bio2rdf/data/ipi/*

wget ftp://ftp.ebi.ac.uk/pub/databases/IPI/current/*.xrefs.gz --output-file=ipi.log
gunzip *.gz
