#!/usr/bin/perl
=comment
Copyright (C) 2012 Jose Cruz-Toledo

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
=cut

use Bio::SeqIO;
use Encode;
use Digest::MD5 qw(md5_base64);


$bio2rdf_base = "http://bio2rdf.org/";
$genbank_base = "http://bio2rdf.org/genbank:";
$genbank_vocabulary = "http://bio2rdf.org/genbank_vocabulary:";
$genbank_resource = "http://bio2rdf.org/genbank_resource:";
$rdf = "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
$rdfs = "http://www.w3.org/2000/01/rdf-schema#";
$owl = "http://www.w3.org/2002/07/owl#";

my $stream = Bio::SeqIO->new(-format => 'genbank', -file => '/home/jose/tmp/genbank/tmp.gb');

while(my $seq = $stream->next_seq){
	
	#printRecordFeatures($seq);
	#printReferences($seq);
	#printComments($seq);
	#printDBLinks($seq);
	printSeqFeatures($seq);

}


sub printSeqFeatures{
	$seq = $_[0];
	$accession = $seq->accession_number;
	@feat_array = $seq->get_all_SeqFeatures;
	$num_feat = $seq->feature_count;
	$counter = 1;
	foreach $feat (@feat_array){
		$display_name = $feat->display_name;
		$primary_tag = $feat->primary_tag;
		$source_tag = $feat->source_tag;
		$feature_id = $feat->seq_id;
		$start = $feat->start;
		$end = $feat->end;
		$strand = $feat->strand;
		$primary_id = $feat->primary_id;
		#create resource
		$fr = $genbank_resource;
		$fr .= md5_base64($accession."-".$counter);
		foreach $tag ($feat->get_all_tags){
			foreach $val ($feat->get_tag_values($tag)) {
				print "tag:\t".$tag;
				print "\tval:\t".$val."\n";
			}
		}
		print "primary tag:".$primary_tag."*****\n";
		print "source tag:".$source_tag."*****\n";
		print "feature id:".$feature_id.."*****\n";
		print "start:".$start."*****\n";
		print "end:".$end."*****\n";
		print "strand:".$strand."*****\n";
		print "gff:".$feat->gff_string."*****\n";
		print "has_tag:".$feat->has_tag."*****\n";
		print "primary_id:".$primary_id."*****\n";
		print "**************************\n";
		#print "<".$genbank_base.$accession."> <".$genbank_vocabulary."_feature> <".$fr."> .\n";
=comment
		print "<".$fr."> <".$rdf."type> <http://bio2rdf.org/ontology/ncbi:$primary_tag> .\n";
		if($display_name =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:displayName> ",'"',$display_name,'"'," .\n";}
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:primaryTag> ",'"',$primary_tag,'"'," .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:sourceTag> ",'"',$source_tag,'"'," .\n";
		if($primary_id =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:primaryID> ",'"',$primary_id,'"'," .\n";}
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:featureID> ",'"',$feature_id,'"'," .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xAccession> <http://bio2rdf.org/ncbi:$feature_id> .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:featureStart> ",'"',$start,'"'," .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:featureEnd> ",'"',$end,'"'," .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:featureStrand> ",'"',$strand,'"'," .\n";
		foreach $tag ( $feat->get_all_tags() ) {
			foreach $val ( $feat->get_tag_values($tag)){
				if($val =~ m/GI:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGI> <http://bio2rdf.org/gi:$1> .\n";
				}
				if($val =~ m/GeneID:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGeneid> <http://bio2rdf.org/geneid:$1> .\n";
				}
				if($val =~ m/taxon:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xTaxonomy> <http://bio2rdf.org/taxonomy:$1> .\n";
				}
				if($val =~ m/UniSTS:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xUniSTS> <http://bio2rdf.org/unists:$1> .\n";
				}
				if($val =~ m/UniProtKB.+?:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xUniprot> <http://bio2rdf.org/uniprot:$1> .\n";
				}
				if($val =~ m/dbSNP:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xDbSNP> <http://bio2rdf.org/dbsnp:$1> .\n";
				}
				if($val =~ m/TAIR:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xTAIR> <http://bio2rdf.org/tair:$1> .\n";
				}
				if($val =~ m/HGNC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xHGNC> <http://bio2rdf.org/hgnc:$1> .\n";
				}
				if($val =~ m/MIM:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xOMIM> <http://bio2rdf.org/omim:$1> .\n";
				}
				if($val =~ m/ZFIN:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xZFIN> <http://bio2rdf.org/zfin:$1> .\n";
				}
				if($val =~ m/miRBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xMiRBase> <http://bio2rdf.org/mirbase:$1> .\n";
				}
				if($val =~ m/Xenbase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xXenbase> <http://bio2rdf.org/xenbase:$1> .\n";
				}
				if($val =~ m/CGNC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xCGNC> <http://bio2rdf.org/cgnc:$1> .\n";
				}
				if($val =~ m/CCDS:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xCCDS> <http://bio2rdf.org/ccds:$1> .\n";
				}
				if($val =~ m/HPRD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xHPRD> <http://bio2rdf.org/hprd:$1> .\n";
				}
				if($val =~ m/RFAM:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRFAM> <http://bio2rdf.org/rfam:$1> .\n";
				}
				if($val =~ m/IMGT\/GENE-DB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xIMGT> <http://bio2rdf.org/imgt:$1> .\n";
				}
				if($val =~ m/PSEUDO:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPseudo> <http://bio2rdf.org/pseudo:$1> .\n";
				}
				if($val =~ m/ATCC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xATCC> <http://bio2rdf.org/atcc:$1> .\n";
				}
				if($val =~ m/GeneDB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGeneDB> <http://bio2rdf.org/genedb:$1> .\n";
				}
				if($val =~ m/HSSP:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xHSSP> <http://bio2rdf.org/hssp:$1> .\n";
				}
				if($val =~ m/RGD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRGD> <http://bio2rdf.org/rgd:$1> .\n";
				}
				if($val =~ m/RATMAP:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRatmap> <http://bio2rdf.org/ratmap:$1> .\n";
				}
				if($val =~ m/WormBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xWormBase> <http://bio2rdf.org/wormbase:$1> .\n";
				}
				if($val =~ m/PBR:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPBR> <http://bio2rdf.org/pbr:$1> .\n";
				}
				if($val =~ m/VBRC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xVBRC> <http://bio2rdf.org/vbrc:$1> .\n";
				}
				if($val =~ m/REBASE:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRebase> <http://bio2rdf.org/rebase:$1> .\n";
				}
				if($val =~ m/FLYBASE:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xFlybase> <http://bio2rdf.org/flybase:$1> .\n";
				}
				if($val =~ m/ISFinder:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xISFinder> <http://bio2rdf.org/isfinder:$1> .\n";
				}
				if($val =~ m/VectorBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xVectorBase> <http://bio2rdf.org/vectorbase:$1> .\n";
				}
				if($val =~ m/Pathema:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPathema> <http://bio2rdf.org/pathema:$1> .\n";
				}
				if($val =~ m/PseudoCap:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPseudoCap> <http://bio2rdf.org/pseudocap:$1> .\n";
				}
				if($val =~ m/NBRC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xNBRC> <http://bio2rdf.org/nbrc:$1> .\n";
				}
				if($val =~ m/ERIC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xERIC> <http://bio2rdf.org/eric:$1> .\n";
				}
				if($val =~ m/PIR:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPIR> <http://bio2rdf.org/pir:$1> .\n";
				}
				if($val =~ m/ASAP:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xASAP> <http://bio2rdf.org/asap:$1> .\n";
				}
				if($val =~ m/dictyBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xDictyBase> <http://bio2rdf.org/dictybase:$1> .\n";
				}
				if($val =~ m/ApiDB_CryptoDB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xEupathdb> <http://bio2rdf.org/eupathdb:$1> .\n";
				}
				if($val =~ m/ECOCYC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xEcocyc> <http://bio2rdf.org/ecocyc:$1> .\n";
				}
				if($val =~ m/EcoGene:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xEcogene> <http://bio2rdf.org/ecogene:$1> .\n";
				}
				if($val =~ m/JCM:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xJCM> <http://bio2rdf.org/jcm:$1> .\n";
				}
				if($val =~ m/AFTOL:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xAFTOL> <http://bio2rdf.org/aftol:$1> .\n";
				}
				if($val =~ m/GDB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGDB> <http://bio2rdf.org/gdb:$1> .\n";
				}
				if($val =~ m/niaEST:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xNiaEST> <http://bio2rdf.org/niaest:$1> .\n";
				}
				if($val =~ m/dbEST:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xDbEST> <http://bio2rdf.org/dbest:$1> .\n";
				}
				if($val =~ m/RiceGenes:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRiceGenes> <http://bio2rdf.org/ricegenes:$1> .\n";
				}
				if($val =~ m/GABI:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGABI> <http://bio2rdf.org/gabi:$1> .\n";
				}
				if($val =~ m/UNILIB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xUNILIB> <http://bio2rdf.org/unilib:$1> .\n";
				}
				if($val =~ m/RZPD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRZPD> <http://bio2rdf.org/rzpd:$1> .\n";
				}
				if($val =~ m/PGN:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPGN> <http://bio2rdf.org/pgn:$1> .\n";
				}
				if($val =~ m/BDGP_EST:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xBDGPEST> <http://bio2rdf.org/bdgpest:$1> .\n";
				}
				if($val =~ m/GRIN:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGRIN> <http://bio2rdf.org/grin:$1> .\n";
				}
				if($val =~ m/Axeldb:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xAxeldb> <http://bio2rdf.org/axeldb:$1> .\n";
				}
				if($val =~ m/NRESTdb:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xNRESTdb> <http://bio2rdf.org/nrestdb:$1> .\n";
				}
				if($val =~ m/BDGP_INS:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xBDGPINS> <http://bio2rdf.org/bdgpins:$1> .\n";
				}
				if($val =~ m/SoyBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xSoyBase> <http://bio2rdf.org/soybase:$1> .\n";
				}
				if($val =~ m/PBmice:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPBmice> <http://bio2rdf.org/pbmice:$1> .\n";
				}
				if($val =~ m/FANTOM_DB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xFANTOMDB> <http://bio2rdf.org/fantomdb:$1> .\n";
				}
				if($val =~ m/BOLD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xBOLD> <http://bio2rdf.org/bold:$1> .\n";
				}
				if($val =~ m/MaizeGDB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xMaizeGDB> <http://bio2rdf.org/maizegdb:$1> .\n";
				}
				if($val =~ m/dbSTS:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xDbSTS> <http://bio2rdf.org/dbsts:$1> .\n";
				}
				if($val =~ m/BioHealthBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xBioHealthBase> <http://bio2rdf.org/biohealthbase:$1> .\n";
				}
				if($val =~ m/COG:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xCOG> <http://bio2rdf.org/cog:$1> .\n";
				}
				if($val =~ m/GOA:(\S+?)$/){ # GOA use the same ID that uniprot use. So I'm also creating a Uniprot URI
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGOA> <http://bio2rdf.org/goa:$1> .\n";
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xUniprot> <http://bio2rdf.org/uniprot:$1> .\n";
			}
			if($val =~ m/SGD:(\S+?)$/){
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xSGD> <http://bio2rdf.org/sgd:$1> .\n";
			}
			if($val =~ m/PDB:(\S+?)$/){
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPDB> <http://bio2rdf.org/pdb:$1> .\n";
			}
			if($val =~ m/PFAM:(\S+?)$/){
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPfam> <http://bio2rdf.org/pfam:$1> .\n";
			}
			if($val =~ m/GO:(\S+?)\s.*$/){
				$goLength = length($1);
				$prefix = "";
				for($i=$goLength;$i<7;$i++){$prefix = $prefix . "0";}					
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGO> <http://bio2rdf.org/go:$prefix$1> .\n";
			}
			if($val =~ m/InterPro:(\S+?)$/){
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xInterpro> <http://bio2rdf.org/interpro:$1> .\n";
			}
			if($val =~ m/CDD:(\S+?)$/){
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xCDD> <http://bio2rdf.org/cdd:$1> .\n";
			}
			if($tag =~ m/protein_id/){
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xAccession> <http://bio2rdf.org/ncbi:$val> .\n";
			}
			else{
				print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:$tag> ".'"',$val,'"'," .\n";
			}
		}
	}
=cut
	$counter++;
}

}


sub printDBLinks{
	$seq = $_[0];
	$ac = $seq->annotation;
	$accession = $seq->accession_number;
	$counter = 1;
	foreach $ann ($ac->get_Annotations('dblink')){
		#create a resource
		$dr = $genbank_resource;
		$dr .= md5_base64($accession."-".$counter);
		if($ann->database == "GenBank"){
			print "<".$dr."> <".$rdfs."seeAlso> <".$bio2rdf_base."genbank:".$ann->primary_id."> .\n";
		}
		$counter++;
	}
}

sub printComments{
	$seq = $_[0];
	$ac = $seq->annotation;
	$accession = $seq->accession_number;
	$counter = 1;
	foreach $ann ($ac->get_Annotations('comment')){
		$txt = Encode::encode("UTF-8", $ann->display_text);
		#create a resource for each commenbt
		$cr = $genbank_resource;
		$cr .= md5_base64($accession."-".$counter);
		print "<".$cr."> <".$genbank_vocabulary."_comment> <".$genbank_resource.$accession."> .\n";
		if($txt =~ m/.+/){print "<".$cr."> <".$genbank_vocabulary."_comment> \"".$txt."\" . \n";}
		$counter++;
	}
}


sub printReferences{
	$seq = $_[0];
	$ac = $seq->annotation;
	$accession = $seq->accession_number;
	$counter = 1;
	foreach $ann ($ac->get_Annotations('reference')){
		$authors = Encode::encode("UTF-8",$ann->authors);
		$authors =~ s/"/'/g;
		$pubmed = $ann->pubmed;
		$title = Encode::encode("UTF-8",$ann->title);
		$title =~ s/"/'/g;
		$source_db = $ann->database;
		$consortium = $ann->consortium;
		$doi = $ann->doi;
		$primary_id = $ann->primary_id;
		$reference = $ann->location;
		$rr = $genbank_resource;
		#create a resource for each reference
		$rr .= md5_base64($accession."-".$counter);
		print "<".$rr."> <".$genbank_vocabulary."_reference> <".$genbank_resource.$accession."> .\n";
		if($pubmed =~ m/.+/){print "<".$rr."> <".$genbank_vocabulary."x_pubmed> <".$bio2rdf_base."pubmed:".$pubmed."> . \n";}
		if($authors =~ m/.+/){print "<".$rr."> <".$genbank_vocabulary."_authors> \"".$authors."\" . \n";}
		if($source_db =~ m/.+/){print "<".$rr."> <".$genbank_vocabulary."_source_db> \"".$source_db."\" . \n";}
		if($consortium =~ m/.+/){print "<".$rr."> <".$genbank_vocabulary."_consortium> \"".$consortium."\" . \n";}
		if($doi =~ m/.+/){print "<".$rr."> <".$genbank_vocabulary."_doi> \"".$doi."\" .\n";}
		if($reference =~ m/.+/){print "<".$rr."> <".$genbank_vocabulary."_reference> \"".$reference."\" .\n";}
		if($primary_id =~ m/.+/){print "<".$rr."> <".$genbank_vocabulary."_primary_id> \"".$primary_id."\" .\n";}
		$counter++;
	}#foreach
}#printAnnotations

sub printRecordFeatures{
	$seq = $_[0];
	$sequence_length = $seq->length;
	$accession = $seq->accession_number;
	$definition = Encode::encode("UTF-8", $seq->desc);
	$definition =~ s/"/'/g;
	$gi = $seq->primary_id;
	$version = $seq->seq_version;
	$alphabet = $seq->alphabet;
	$division = $seq->division;
	$molecule_type = $seq->molecule;

	print "<".$genbank_resource.$accession."> <".$rdf."type> <".$genbank_vocabulary."genbank_record> .\n"; 
	print "<".$genbank_resource.$accession."> <".$rdfs."label> \"".$definition." [genbank:".$accession."]\" .\n";
	if($molecule_type =~ m/.+/){print "<".$genbank_resource.$accession."> <".$genbank_vocabulary."_molecule_type> \"".$molecule_type."\" . \n";}
	if($definition =~ m/.+/){print "<".$genbank_resource.$accession."> <".$genbank_vocabulary."_definition> \"".$definition."\".\n";} 
	if($alphabet =~ m/.+/){print "<".$genbank_resource.$accession."> <".$genbank_vocabulary."_alphabet> \"".$alphabet."\" .\n";}
	if($sequence_length =~ m/.+/){print "<".$genbank_resource.$accession."> <".$genbank_vocabulary."_sequence_length> \"".$sequence_length."\" .\n";}
	if($version != ""){
		print "<".$genbank_resource.$accession.".".$version."> <".$genbank_vocabulary."_version_of> <".$bio2rdf_base."genbank:".$accession."> .\n";
		print "<".$bio2rdf_base."genbank:".$accession."> <".$genbank_vocabulary."_version> <".$genbank_resource.$accession.".".$version."> .\n";
		print "<".$genbank_resource.$accession.".".$version."> <".$owl."sameAs> <".$bio2rdf_base."gi:".$gi."> .\n";
	}
}#printRecordFeatures

=pod
while ( my $seq = $stream->next_seq() ) {

	$sequence = $seq->seq;
	$seq_length = $seq->length;
	$display_id = $seq->display_id;
	$acc_number = $seq->accession_number;
	$description = $seq->desc;
	$description =~ s/"/'/g;
	$primary_id = $seq->primary_id;
	$object_id = $seq->object_id;
	$version = $seq->seq_version;
	$authority  = $seq->authority;
	$namespace = $seq->namespace;
	$alphabet = $seq->alphabet;


	print "<http://bio2rdf.org/ncbi:$acc_number> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ontology/ncbi:Record> .\n";
	print "<http://bio2rdf.org/ncbi:$acc_number> <http://purl.org/dc/elements/1.1/identifier> ",'"genbank:',$acc_number,'"'," .\n";
	print "<http://bio2rdf.org/ncbi:$acc_number> <http://www.w3.org/2000/01/rdf-schema#label> ",'"',$description," [ncbi:$acc_number]",'"'," .\n";
	if($description =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number> <http://www.w3.org/2000/01/rdf-schema#comment> ",'"',$description,'"'," .\n";}
	print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:version> ",'"',$version,'"'," .\n";
	if($primary_id =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/bio2rdf:xGI> <http://bio2rdf.org/gi:$primary_id> .\n";}
	if($authority =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:authority> ",'"',$authority,'"'," .\n";}
	if($namespace =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:namespace> ",'"',$namespace,'"'," .\n";}
	if($display_id =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:displayID> ",'"',$display_id,'"'," .\n";}
	if($object_id =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:objectID> ",'"',$object_id,'"'," .\n";}
	print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:sequenceLength> ",'"',$seq_length,'"'," .\n";
	print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:sequenceAlphabet> ",'"',$alphabet,'"'," .\n";
	print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:sequence> ",'"',$sequence,'"'," .\n";
	print "<http://bio2rdf.org/ncbi:$acc_number.$version> <http://bio2rdf.org/ontology/ncbi:is_version_of> <http://bio2rdf.org/ncbi:$acc_number> .\n";

	$ac = $seq->annotation;
	$num_annotations =$ac->get_num_of_annotations;
	$counter = 1;
	foreach $key ( $ac->get_all_annotation_keys) {
		@values = $ac->get_Annotations($key);
		foreach $value ( @values ) {
			if($value->display_text =~ m/.+/){
				$text = $value->display_text;
				$text =~ s/"/'/g;
				$tag = $value->tagname;
				print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:annotation> <http://bio2rdf.org/ncbi:$acc_number","_","A$counter> .\n";
				print "<http://bio2rdf.org/ncbi:$acc_number","_","A$counter> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ontology/ncbi:$tag> .\n";
				print "<http://bio2rdf.org/ncbi:$acc_number","_","A$counter> <http://www.w3.org/2000/01/rdf-schema#label> ",'"',$text," [ncbi:$acc_number","_","A$counter]",'"'," .\n";
				print "<http://bio2rdf.org/ncbi:$acc_number","_","A$counter> <http://bio2rdf.org/ontology/ncbi:$tag> ",'"',$text,'"'," .\n";
				$counter++;
			}
		}
	}
	foreach $ann ($ac->get_Annotations('reference')){
		$authors = $ann->authors;
		$authors =~ s/"/'/g;
		$pubmed = $ann->pubmed;
		$title = $ann->title;
		$title =~ s/"/'/g;
		print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:annotation> <http://bio2rdf.org/ncbi:$acc_number","_","A$counter> .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","A$counter> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ontology/ncbi:reference> .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","A$counter> <http://bio2rdf.org/ontology/ncbi:authors> ",'"',$authors,'"'," .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","A$counter> <http://bio2rdf.org/ontology/ncbi:title> ",'"',$title,'"'," .\n";
		if($pubmed =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number","_","A$counter> <http://bio2rdf.org/ontology/bio2rdf:xPubmed> <http://bio2rdf.org/pubmed:$pubmed> .\n";}
		$counter++;
	}
	@feat_array = $seq->get_all_SeqFeatures;
	$num_feat = $seq->feature_count;
	$counter = 1;
	foreach $feat (@feat_array){
		$display_name = $feat->display_name;
	     	$primary_tag = $feat->primary_tag;
		$source_tag = $feat->source_tag;
		$feature_id = $feat->seq_id;
		$start = $feat->start;
		$end = $feat->end;
		$strand = $feat->strand;
		$primary_id = $feat->primary_id;
		print "<http://bio2rdf.org/ncbi:$acc_number> <http://bio2rdf.org/ontology/ncbi:feature> <http://bio2rdf.org/ncbi:$acc_number","_","F$counter> .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ontology/ncbi:$primary_tag> .\n";
		if($display_name =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:displayName> ",'"',$display_name,'"'," .\n";}
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:primaryTag> ",'"',$primary_tag,'"'," .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:sourceTag> ",'"',$source_tag,'"'," .\n";
		if($primary_id =~ m/.+/){print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:primaryID> ",'"',$primary_id,'"'," .\n";}
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:featureID> ",'"',$feature_id,'"'," .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xAccession> <http://bio2rdf.org/ncbi:$feature_id> .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:featureStart> ",'"',$start,'"'," .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:featureEnd> ",'"',$end,'"'," .\n";
		print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:featureStrand> ",'"',$strand,'"'," .\n";
		foreach $tag ( $feat->get_all_tags() ) {
			foreach $val ( $feat->get_tag_values($tag)){
				if($val =~ m/GI:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGI> <http://bio2rdf.org/gi:$1> .\n";
				}
				if($val =~ m/GeneID:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGeneid> <http://bio2rdf.org/geneid:$1> .\n";
				}
				if($val =~ m/taxon:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xTaxonomy> <http://bio2rdf.org/taxonomy:$1> .\n";
				}
				if($val =~ m/UniSTS:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xUniSTS> <http://bio2rdf.org/unists:$1> .\n";
				}
				if($val =~ m/UniProtKB.+?:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xUniprot> <http://bio2rdf.org/uniprot:$1> .\n";
				}
				if($val =~ m/dbSNP:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xDbSNP> <http://bio2rdf.org/dbsnp:$1> .\n";
				}
				if($val =~ m/TAIR:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xTAIR> <http://bio2rdf.org/tair:$1> .\n";
				}
				if($val =~ m/HGNC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xHGNC> <http://bio2rdf.org/hgnc:$1> .\n";
				}
				if($val =~ m/MIM:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xOMIM> <http://bio2rdf.org/omim:$1> .\n";
				}
				if($val =~ m/ZFIN:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xZFIN> <http://bio2rdf.org/zfin:$1> .\n";
				}
				if($val =~ m/miRBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xMiRBase> <http://bio2rdf.org/mirbase:$1> .\n";
				}
				if($val =~ m/Xenbase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xXenbase> <http://bio2rdf.org/xenbase:$1> .\n";
				}
				if($val =~ m/CGNC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xCGNC> <http://bio2rdf.org/cgnc:$1> .\n";
				}
				if($val =~ m/CCDS:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xCCDS> <http://bio2rdf.org/ccds:$1> .\n";
				}
				if($val =~ m/HPRD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xHPRD> <http://bio2rdf.org/hprd:$1> .\n";
				}
				if($val =~ m/RFAM:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRFAM> <http://bio2rdf.org/rfam:$1> .\n";
				}
				if($val =~ m/IMGT\/GENE-DB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xIMGT> <http://bio2rdf.org/imgt:$1> .\n";
				}
				if($val =~ m/PSEUDO:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPseudo> <http://bio2rdf.org/pseudo:$1> .\n";
				}
				if($val =~ m/ATCC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xATCC> <http://bio2rdf.org/atcc:$1> .\n";
				}
				if($val =~ m/GeneDB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGeneDB> <http://bio2rdf.org/genedb:$1> .\n";
				}
				if($val =~ m/HSSP:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xHSSP> <http://bio2rdf.org/hssp:$1> .\n";
				}
				if($val =~ m/RGD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRGD> <http://bio2rdf.org/rgd:$1> .\n";
				}
				if($val =~ m/RATMAP:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRatmap> <http://bio2rdf.org/ratmap:$1> .\n";
				}
				if($val =~ m/WormBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xWormBase> <http://bio2rdf.org/wormbase:$1> .\n";
				}
				if($val =~ m/PBR:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPBR> <http://bio2rdf.org/pbr:$1> .\n";
				}
				if($val =~ m/VBRC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xVBRC> <http://bio2rdf.org/vbrc:$1> .\n";
				}
				if($val =~ m/REBASE:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRebase> <http://bio2rdf.org/rebase:$1> .\n";
				}
				if($val =~ m/FLYBASE:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xFlybase> <http://bio2rdf.org/flybase:$1> .\n";
				}
				if($val =~ m/ISFinder:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xISFinder> <http://bio2rdf.org/isfinder:$1> .\n";
				}
				if($val =~ m/VectorBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xVectorBase> <http://bio2rdf.org/vectorbase:$1> .\n";
				}
				if($val =~ m/Pathema:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPathema> <http://bio2rdf.org/pathema:$1> .\n";
				}
				if($val =~ m/PseudoCap:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPseudoCap> <http://bio2rdf.org/pseudocap:$1> .\n";
				}
				if($val =~ m/NBRC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xNBRC> <http://bio2rdf.org/nbrc:$1> .\n";
				}
				if($val =~ m/ERIC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xERIC> <http://bio2rdf.org/eric:$1> .\n";
				}
				if($val =~ m/PIR:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPIR> <http://bio2rdf.org/pir:$1> .\n";
				}
				if($val =~ m/ASAP:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xASAP> <http://bio2rdf.org/asap:$1> .\n";
				}
				if($val =~ m/dictyBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xDictyBase> <http://bio2rdf.org/dictybase:$1> .\n";
				}
				if($val =~ m/ApiDB_CryptoDB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xEupathdb> <http://bio2rdf.org/eupathdb:$1> .\n";
				}
				if($val =~ m/ECOCYC:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xEcocyc> <http://bio2rdf.org/ecocyc:$1> .\n";
				}
				if($val =~ m/EcoGene:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xEcogene> <http://bio2rdf.org/ecogene:$1> .\n";
				}
				if($val =~ m/JCM:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xJCM> <http://bio2rdf.org/jcm:$1> .\n";
				}
				if($val =~ m/AFTOL:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xAFTOL> <http://bio2rdf.org/aftol:$1> .\n";
				}
				if($val =~ m/GDB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGDB> <http://bio2rdf.org/gdb:$1> .\n";
				}
				if($val =~ m/niaEST:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xNiaEST> <http://bio2rdf.org/niaest:$1> .\n";
				}
				if($val =~ m/dbEST:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xDbEST> <http://bio2rdf.org/dbest:$1> .\n";
				}
				if($val =~ m/RiceGenes:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRiceGenes> <http://bio2rdf.org/ricegenes:$1> .\n";
				}
				if($val =~ m/GABI:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGABI> <http://bio2rdf.org/gabi:$1> .\n";
				}
				if($val =~ m/UNILIB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xUNILIB> <http://bio2rdf.org/unilib:$1> .\n";
				}
				if($val =~ m/RZPD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xRZPD> <http://bio2rdf.org/rzpd:$1> .\n";
				}
				if($val =~ m/PGN:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPGN> <http://bio2rdf.org/pgn:$1> .\n";
				}
				if($val =~ m/BDGP_EST:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xBDGPEST> <http://bio2rdf.org/bdgpest:$1> .\n";
				}
				if($val =~ m/GRIN:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGRIN> <http://bio2rdf.org/grin:$1> .\n";
				}
				if($val =~ m/Axeldb:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xAxeldb> <http://bio2rdf.org/axeldb:$1> .\n";
				}
				if($val =~ m/NRESTdb:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xNRESTdb> <http://bio2rdf.org/nrestdb:$1> .\n";
				}
				if($val =~ m/BDGP_INS:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xBDGPINS> <http://bio2rdf.org/bdgpins:$1> .\n";
				}
				if($val =~ m/SoyBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xSoyBase> <http://bio2rdf.org/soybase:$1> .\n";
				}
				if($val =~ m/PBmice:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPBmice> <http://bio2rdf.org/pbmice:$1> .\n";
				}
				if($val =~ m/FANTOM_DB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xFANTOMDB> <http://bio2rdf.org/fantomdb:$1> .\n";
				}
				if($val =~ m/BOLD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xBOLD> <http://bio2rdf.org/bold:$1> .\n";
				}
				if($val =~ m/MaizeGDB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xMaizeGDB> <http://bio2rdf.org/maizegdb:$1> .\n";
				}
				if($val =~ m/dbSTS:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xDbSTS> <http://bio2rdf.org/dbsts:$1> .\n";
				}
				if($val =~ m/BioHealthBase:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xBioHealthBase> <http://bio2rdf.org/biohealthbase:$1> .\n";
				}
				if($val =~ m/COG:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xCOG> <http://bio2rdf.org/cog:$1> .\n";
				}
				if($val =~ m/GOA:(\S+?)$/){ # GOA use the same ID that uniprot use. So I'm also creating a Uniprot URI
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGOA> <http://bio2rdf.org/goa:$1> .\n";
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xUniprot> <http://bio2rdf.org/uniprot:$1> .\n";
				}
				if($val =~ m/SGD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xSGD> <http://bio2rdf.org/sgd:$1> .\n";
				}
				if($val =~ m/PDB:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPDB> <http://bio2rdf.org/pdb:$1> .\n";
				}
				if($val =~ m/PFAM:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xPfam> <http://bio2rdf.org/pfam:$1> .\n";
				}
				if($val =~ m/GO:(\S+?)\s.*$/){
					$goLength = length($1);
					$prefix = "";
					for($i=$goLength;$i<7;$i++){$prefix = $prefix . "0";}					
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xGO> <http://bio2rdf.org/go:$prefix$1> .\n";
				}
				if($val =~ m/InterPro:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xInterpro> <http://bio2rdf.org/interpro:$1> .\n";
				}
				if($val =~ m/CDD:(\S+?)$/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xCDD> <http://bio2rdf.org/cdd:$1> .\n";
				}
				if($tag =~ m/protein_id/){
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/bio2rdf:xAccession> <http://bio2rdf.org/ncbi:$val> .\n";
				}
				else{
					print "<http://bio2rdf.org/ncbi:$acc_number","_","F$counter> <http://bio2rdf.org/ontology/ncbi:$tag> ".'"',$val,'"'," .\n";
				}
			}
		}
		$counter++;
	}

}=cut



