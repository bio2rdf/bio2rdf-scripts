# ipi-wget.sh

# Put your own locatio nwhere you wnat the data to be downloaded
mkdir -p /Users/gabivulcu/Downloads/bio2-rdf-data/ipi/in
cd /Users/gabivulcu/Downloads/bio2-rdf-data/ipi/in
rm -rf /Users/gabivulcu/Downloads/bio2-rdf-data/ipi/in/*

wget ftp://ftp.ebi.ac.uk/pub/databases/IPI/last_release/current/*.xrefs.gz --output-file=ipi.log
# gunzip *.gz
