# unists-wget.sh 

mkdir -p /Users/gabivulcu/Downloads/bio2-rdf-data/unists/in
mkdir -p /Users/gabivulcu/Downloads/bio2-rdf-data/unists/out

cd /Users/gabivulcu/Downloads/bio2-rdf-data/unists/in
rm -rf /Users/gabivulcu/Downloads/bio2-rdf-data/unists/in/*

#wget ftp://ftp.ncbi.nih.gov/repository/UniSTS/UniSTS_MapReports/Homo_sapiens/* --output-file=Homo_sapiens.log
#wget ftp://ftp.ncbi.nih.gov/repository/UniSTS/UniSTS_MapReports/Mus_musculus/* --output-file=Mus_musculus.log
wget -r ftp://ftp.ncbi.nih.gov/repository/UniSTS/UniSTS_MapReports/* --output-file=unists.log


