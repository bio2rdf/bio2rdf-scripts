# unists-wget.sh 

cd /media/twotb/bio2rdf/data/unists

rm -rf /media/twotb/bio2rdf/data/unists/*

#wget ftp://ftp.ncbi.nih.gov/repository/UniSTS/UniSTS_MapReports/Homo_sapiens/* --output-file=Homo_sapiens.log
#wget ftp://ftp.ncbi.nih.gov/repository/UniSTS/UniSTS_MapReports/Mus_musculus/* --output-file=Mus_musculus.log
wget -r ftp://ftp.ncbi.nih.gov/repository/UniSTS/UniSTS_MapReports/* --output-file=unists.log
