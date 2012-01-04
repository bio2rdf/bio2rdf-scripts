# mgi-wget.sh

cd /bio2rdf/data/mgi

rm /bio2rdf/data/mgi/*

wget ftp://ftp.informatics.jax.org/pub/reports/* --output-file=mgi.log
