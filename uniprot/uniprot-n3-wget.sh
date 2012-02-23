# uniprot-n3-wget.sh

cd /media/2tbdisk/bio2rdf/data/uniprot

wget "http://www.uniprot.org/uniprot/?query=active:*&format=nt&compress=yes" --output-document=uniprot.n3.gz --output-file=uniprot-n3.log
wget "http://www.uniprot.org/uniparc/?query=*&format=nt&compress=yes" --output-document=uniparc.n3.gz --output-file=uniparc-n3.log
wget "http://www.uniprot.org/uniref/?query=*&format=nt&compress=yes" --output-document=uniref.n3.gz --output-file=uniref-n3.log
#wget "http://www.uniprot.org/taxonomy/?query=*&format=nt&compress=yes" --output-document=taxonomy.n3.gz --output-file=taxonomy-n3.log
#wget "http://www.uniprot.org/citations/?query=*&format=nt&compress=yes" --output-document=citations.n3.gz --output-file=citations-n3.log
#wget "http://www.uniprot.org/tissues/?query=*&format=nt&compress=yes" --output-document=tissues.n3.gz --output-file=tissues-n3.log
#wget "http://www.uniprot.org/keywords/?query=*&format=nt&compress=yes" --output-document=keywords.n3.gz --output-file=keywords-n3.log
#wget "http://www.uniprot.org/locations/?query=*&format=nt&compress=yes" --output-document=locations.n3.gz --output-file=locations-n3.log

