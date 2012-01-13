###############################################################################
#Copyright (C) 2011 Alison Callahan, Marc-Alexandre Nolin, Francois Belleau
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

# dc:title      ipi2n3.pl
# dc:creator    francoisbelleau at yahoo.ca
# dc:modified   2009-04-05
# dc:description Convert ftp://ftp.ebi.ac.uk/pub/databases/IPI/current/*.xrefs.gz to N3
#		http://www.ebi.ac.uk/IPI/xrefs.html
# perl ipi2n3.pl /bio2rdf/data/ipi/*.xref > /bio2rdf/download/download/n3/ipi.n3 2> temp/ipi2n3.log &
 
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

# # Database from which master entry of this IPI entry has been taken.
# One of either SP (UniProtKB/Swiss-Prot), TR (UniProtKB/TrEMBL), ENSEMBL (Ensembl), ENSEMBL_HAVANA (Ensembl Havana subset), REFSEQ_STATUS (where STATUS corresponds to the RefSeq entry revision status), VEGA (Vega), TAIR (TAIR Protein data set) or HINV (H-Invitational Database).
# # UniProtKB accession number or Vega ID or Ensembl ID or RefSeq ID or TAIR Protein ID or H-InvDB ID.
# # International Protein Index identifier.
# # Supplementary UniProtKB/Swiss-Prot entries associated with this IPI entry.
# # Supplementary UniProtKB/TrEMBL entries associated with this IPI entry.
# # Supplementary Ensembl entries associated with this IPI entry. Havana curated transcripts preceeded by the key HAVANA: (e.g. HAVANA:ENSP00000237305;ENSP00000356824;).
# # Supplementary list of RefSeq STATUS:ID couples (separated by a semi-colon ';') associated with this IPI entry (RefSeq entry revision status details).
# # Supplementary TAIR Protein entries associated with this IPI entry.
# # Supplementary H-Inv Protein entries associated with this IPI entry.
# # Protein identifiers (cross reference to EMBL/Genbank/DDBJ nucleotide databases).
# # List of HGNC number, HGNC official gene symbol couples (separated by by a semi-colon ';') associated with this IPI entry.
# # List of NCBI Entrez Gene gene number, Entrez Gene Default Gene Symbol couples (separated by a semi-colon ';') associated with this IPI entry.
# # UNIPARC identifier associated with the sequence of this IPI entry.
# # UniGene identifiers associated with this IPI entry.
# # CCDS identifiers associated with this IPI entry.
# # RefSeq GI protein identifiers associated with this IPI entry.
# # Supplementary Vega entries associated with this IPI entry. 


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
	@namespaces = (
	"source",
	"source_acc",
	"ipi",
	"uniprot_sp",
	"uniprot_tr",
	"ensembl",
	"refseq",
	"tair",
	"hinv",
	"xref",
	"hgnc",
	"geneid",
	"uniparc",
	"unigene",
	"ccds",
	"gi",
	"vega"
	);

	chop $line;	
	@fields = split(/\t/, $line);
	$ctr = 0;
	foreach $field (@fields) {
		@items = split(/; /, $field);
		foreach $item (@items) {
			
			$ns = $namespaces[$ctr];
			$ns2 = ucfirst($ns);
			if($ns eq "ipi"){ 
				$id = $item;
				print "\n<http://bio2rdf.org/ipi:$id> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/ipi_vocabulary:InternationalProteinIdentifier> .\n";
				print "<http://bio2rdf.org/ipi:$id> <http://purl.org/dc/elements/1.1/identifier> \"ipi:$id\" .\n";
				print "<http://bio2rdf.org/ipi:$id> <http://www.w3.org/2000/01/rdf-schema#label> \"[ipi:$id]\" .\n";
				print "<http://bio2rdf.org/ipi:$id> <http://bio2rdf.org/ipi_vocabulary:url> <http://srs.ebi.ac.uk/srsbin/cgi-bin/wgetz?-e+[IPI:%27$id%27]> .\n";
				last; 
			};

			if($ns eq "go"){ $item =~ s/^GO://g; };
			if($ns eq "pdb"){ $item =~ s/:(.*?)$//g; };

			print "<http://bio2rdf.org/ipi:$id> <http://bio2rdf.org/ipi_vocabulary:x$ns2> <http://bio2rdf.org/$ns:$item> .\n";
			#print $namespaces[$ctr]."=\t$item\n";
		}
		$ctr++;
		warn $linesLength/$fileLength."\n"
	}
}

