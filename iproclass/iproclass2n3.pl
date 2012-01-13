###############################################################################
#Copyright (C) 2011 Alison Callahan, Marc-Alexandre Nolini, Francois Belleau
#
#Permission is hereby granted, free of charge, to any person obtaining a copy of
#this software and associated documentation files (the "Software"), to deal in
#the Software without restriction, including without limitation the rights to
#use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
#of the Software, and to permit persons to whom the Software is furnished to do
#so, subject to the following conditions:
#
#The above copyright notice and this permission notice shall be included in all
#copies or substantial portions of the Software.
#
#THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
#SOFTWARE.
###############################################################################


# dc:title      iproclass2n3.pl
# dc:creator    francoisbelleau at yahoo.ca
# dc:modified   2009-04-04
# dc:description Convert iproclass.tb to N3
# perl iproclass2n3.pl /bio2rdf/data/pir/iproclass.tb > /bio2rdf/download/download/n3/iproclass.n3 2> temp/iproclass2n3.log &
 
# -------------------------------------------------------------------------------
# Bio2RDF is a creation Francois Belleau, Marc-Alexandre Nolin and the Bio2RDF community.
# The SPARQL end points are hosted by the Centre de Recherche du CHUL de Quebec.
# This program is release under the GPL v2 licence. The term of this licence are #specified at http://www.gnu.org/copyleft/gpl.html.
#
# You can contact the Bio2RDF team at bio2rdf@gmail.com
# Visit our blog at http://bio2rdf.blogspot.com/
# Visit the main application at http://bio2rdf.org
# This open source project is hosted at http://sourceforge.net/projects/bio2rdf/
# -------------------------------------------------------------------------------

#1. UniProtKB accession (UniProtKB accession with taxon_id)
#2. UniProtKB ID
#3. EntrezGene
#4. RefSeq
#5. NCBI GI number
#6. PDB
#7. Pfam
#8. GO
#9. PIRSF
#10. IPI
#11. UniRef100
#12. UniRef90
#13. UniRef50
#14. UniParc
#15. PIR-PSD accession
#16. NCBI taxonomy
#17. MIM
#18. UniGene
#19. Ensembl
#20. PubMed ID
#21. EMBL/GenBank/DDBJ
#22. EMBL protein_id

$path = shift; 
$numberMax = 100000000;
              
open(ENTREE, "<$path") || die "File $path not found:$!\n";
        
$fileLength = -s $path;

$number = 0;

while ($line = <ENTREE> and $number < $numberMax) {
	$number ++;
	$linesLength = $linesLength + length($line);
	last if ($number > $numberMax);
	#print  "$line";
	#$line =~ /(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)\t(.*?)/;	
	@namespaces = (
	"uniprot_acc",
	"uniprot",
	"geneid",
	"refseq",
	"gi",
	"pdb",
	"pfam",
	"go",
	"pirsf",
	"ipi",
	"uniref",
	"uniref",
	"uniref",
	"uniparc",
	"pir_psd",
	"taxonomy",
	"omim",
	"unigene",
	"ensembl",
	"pubmed",
	"genbank",
	"embl"
	);

	chop $line;	
	@fields = split(/\t/, $line);
	$ctr = 0;
	foreach $field (@fields) {
		@items = split(/; /, $field);
		foreach $item (@items) {
			
			$ns = $namespaces[$ctr];
			$ns2 = ucfirst($ns);
			if($ns eq "uniprot_acc"){ 
				$id = $item;
				print "\n<http://bio2rdf.org/iproclass:$id> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ns/iproclass#AnnotationClass> .\n";
				print "<http://bio2rdf.org/iproclass:$id> <http://bio2rdf.org/iproclass_vocabulary:xUniProt> <http://bio2rdf.org/uniprot:$id> .\n";
				print "<http://bio2rdf.org/iproclass:$id> <http://purl.org/dc/elements/1.1/identifier> \"iproclass:$id\" .\n";
				print "<http://bio2rdf.org/iproclass:$id> <http://www.w3.org/2000/01/rdf-schema#label> \"[iproclass:$id]\" .\n";
				print "<http://bio2rdf.org/iproclass:$id> <http://bio2rdf.org/iproclass_vocabulary:url> <http://pir.georgetown.edu/cgi-bin/ipcidmapping?id=$id> .\n";
				last; 
			};

			if($ns eq "go"){ $item =~ s/^GO://g; };
			if($ns eq "pdb"){ $item =~ s/:(.*?)$//g; };

			print "<http://bio2rdf.org/iproclass:$id> <http://bio2rdf.org/iproclass_vocabulary:x$ns2> <http://bio2rdf.org/$ns:$item> .\n";
			#print $namespaces[$ctr]."=\t$item\n";
		}
		$ctr++;
		warn $linesLength/$fileLength."\n"
	}
}


