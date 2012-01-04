# homologene-wget.sh 

cd /media/twotb/bio2rdf/data/homologene

rm -rf /media/twotb/bio2rdf/data/homologene/*

wget ftp://ftp.ncbi.nih.gov/pub/HomoloGene/current/* --output-file=HomoloGene.log
