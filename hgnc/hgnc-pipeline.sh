#!/bin/bash

# ISQL must be in the path
# export PATH=$PATH:/opt/virtuoso6/bin

pdir=0
ddir=0
vdir=0

if [ -n "$1" ]
then
	pdir=$1
else
	echo "You must specified the root directory of the pipeline."
	exit
fi

if [ -n "$2" ]
then
	ddir=$2
else
	echo "You must specified the location of the download directory on your server."
	exit
fi

if [ -n "$3" ]
then
	vdir=$3
else
	echo "You must specified the location of HGNC Virtuoso directory on your server."
	exit
fi

echo "--> Changing to data directory $pdir/data. <--"
cd $pdir/data
echo ""

echo "--> Removing old HGNC files. <--"
rm -f hgnc.txt
rm -f hgnc.nq.gz
echo ""

echo "--> Downloading new HGNC information from http://www.genenames.org/ <--"
wget "http://www.genenames.org/cgi-bin/hgnc_downloads.cgi?title=HGNC+output+data&hgnc_dbtag=on&preset=all&status=Approved&status=Entry+Withdrawn&status_opt=2&level=pri&=on&where=&order_by=gd_app_sym_sort&limit=&format=text&submit=submit&.cgifields=&.cgifields=level&.cgifields=chr&.cgifields=status&.cgifields=hgnc_dbtag" -O hgnc.txt
echo ""

echo "--> Converting to nQuads. <--"
perl $pdir/script/hgnc2nq.pl hgnc.txt | gzip > hgnc.nq.gz
echo ""

echo "--> Generating statistical information about this update. <--"
$pdir/../shared/complete_stats.sh $pdir/data/hgnc.nq.gz > $pdir/stats/hgnc.nq.gz.stats
echo""

echo "--> Generating the release information. <--"
date=$(date +'%Y-%m-%d')
echo '@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .' > hgnc-release.nq
echo '@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .' >> hgnc-release.nq
echo '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .' >> hgnc-release.nq
echo '@prefix owl: <http://www.w3.org/2002/07/owl#> .' >> hgnc-release.nq
echo '@prefix dcterms: <http://purl.org/dc/terms/> .' >> hgnc-release.nq
echo '@prefix bio2rdf: <http://bio2rdf.org/bio2rdf_resource:> .' >> hgnc-release.nq
echo '@prefix release: <http://bio2rdf.org/release_resource:> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> rdf:type release:release <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> dcterms:date "'$date'"^^xsd:date <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> dcterms:description "This SPARQL endpoint have been construct from the information contain in the full download of HGNC information located at http://www.genenames.org on the date '$date'." <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> rdfs:label "HGNC Sparql Endpoint [release:hgnc]" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> dcterms:format "RDF" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> dcterms:creator "HGNC" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> dcterms:publisher "Bio2RDF.org" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> dcterms:rights "http://www.genenames.org" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> dcterms:title "HGNC Sparql Endpoint" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> release:rdfizer "http://download.bio2rdf.org/script/hgnc2nq.pl.gz" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> release:releaseMetaData "http://download.bio2rdf.org/data/hgnc/hgnc-release.nq.gz" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> release:releaseData "http://download.bio2rdf.org/data/hgnc/hgnc.nq.gz" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> release:virtuoso_db "http://download.bio2rdf.org/virtuoso_db/hgnc.virtuoso.db.bz2" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> release:statistic "http://download.bio2rdf.org/stats/hgnc.nq.gz.stats" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
echo '<http://bio2rdf.org/release:hgnc> dcterms:hasVersion "'$date'" <http://bio2rdf.org/release_record:hgnc> .' >> hgnc-release.nq
gzip -f hgnc-release.nq
echo ""

echo "--> Remove old triples from the endpoint <--"
perl $pdir/../shared/remove_all_information.pl 13012 $HGNC_endpoint_password
echo ""

echo "--> Add new triples to the endpoint <--"
perl $pdir/../shared/nq2virtuoso.pl hgnc 13012 $HGNC_endpoint_password 689 2 $pdir/data/hgnc.nq.gz > $pdir/data/load-hgnc.log
perl $pdir/../shared/checkpoint.pl 13012 $HGNC_endpoint_password
echo ""

echo "--> Updating release endpoint information <--"
perl $pdir/../shared/remove_information_in_release_endpoint.pl hgnc
perl $pdir/../shared/nq2virtuoso.pl hgnc 13019 $release_endpoint_password 689 2 $pdir/data/hgnc-release.nq.gz> $pdir/data/load-release.log
perl $pdir/../shared/checkpoint.pl 13019 $release_endpoint_password
echo ""

echo "--> Copying generated files in the download directory <--"
cp -f $vdir/virtuoso.db $ddir/virtuoso_db/version-6.1.0
gzip -f $ddir/virtuoso_db/version-6.1.0/virtuoso.db
mv -f $ddir/virtuoso_db/version-6.1.0/virtuoso.db.gz $ddir/virtuoso_db/version-6.1.0/hgnc.virtuoso.db.gz
cp -f $pdir/data/hgnc-release.nq.gz $ddir/data/hgnc
cp -f $pdir/data/hgnc.nq.gz $ddir/data/hgnc
cp -f $pdir/stats/hgnc.nq.gz.stats $ddir/stats
cp -f $pdir/script/hgnc2nq.pl $ddir/script
gzip -f $ddir/script/hgnc2nq.pl
