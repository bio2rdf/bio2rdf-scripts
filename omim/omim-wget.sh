# omim-wget.sh
# ftp://ftp.ncbi.nih.gov/repository/OMIM/
set -e

DOWNLOAD_DIR=$1

cd $DOWNLOAD_DIR
rm -rf $DOWNLOAD_DIR/*

wget ftp://ftp.ncbi.nih.gov/repository/OMIM/* --recursive --output-file=omim-wget.log
