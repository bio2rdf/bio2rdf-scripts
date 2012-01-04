# omim-wget.sh
# ftp://ftp.ncbi.nih.gov/repository/OMIM/

cd /bio2rdf/data/omim
rm -rf /bio2rdf/data/omim/*

wget ftp://ftp.ncbi.nih.gov/repository/OMIM/*  --output-file=omim-wget.log

gunzip omim.txt.Z
