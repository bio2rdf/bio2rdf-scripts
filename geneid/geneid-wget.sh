# geneid-wget.sh
# ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/

cd /media/2tbdisk/bio2rdf/data/geneid

rm /media/2tbdisk/bio2rdf/data/geneid/*

wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/All_Data.ags.gz --output-file=All_Data.log
wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/Organelles.ags.gz --output-file=Organelles.log
wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/Plasmids.ags.gz --output-file=Plasmids.log

wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/Archaea_Bacteria/All_Archaea_Bacteria.ags.gz --output-file=All_Archaea_Bacteria.log
wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/Fungi/All_Fungi.ags.gz --output-file=All_Fungi.log
wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/Invertebrates/All_Invertebrates.ags.gz --output-file=All_Invertebrates.log
wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/Mammalia/All_Mammalia.ags.gz --output-file=All_Mammalia.log
wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/Non-mammalian_vertebrates/All_Non-mammalian_vertebrates.ags.gz --output-file=All_Non-mammalian_vertebrates.log
wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/Plants/All_Plants.ags.gz --output-file=All_Plants.log
wget ftp://ftp.ncbi.nih.gov/gene/DATA/ASN_BINARY/Viruses/All_Viruses.ags.gz --output-file=All_Viruses.log
