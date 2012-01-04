# chebi2n3.pl

# perl chebi2n3.pl ../data/hgnc/hgnc.txt

# download input file with chebi-wget.sh

$path = shift;
$path = "/media/twotb/bio2rdf/data/chebi";

$max = 1000;
$max = 10000000;

Compound();
Name();
Chemical();

exit;

sub Compound {
# insert into compounds (id, name, source, parent_id, chebi_accession, status, definition, modified_on) values ('574','','KEGG COMPOUND','18381','CHEBI:574','C','','');    

	open(ENTREE, "< $path/compounds.sql") || die "Fichier $fichier introuvable:$!\n";


	$nombre = 0;
	$ligne = <ENTREE>;
	while ($ligne = <ENTREE> and $nombre < $max) {
        	$nombre = $nombre + 1;

        	$ligne =~ s/>/&gt;/g;
        	$ligne =~ s/</&lt;/g;
    		$ligne =~ s/'//g;
    		$ligne =~ s/"//g;
	        $ligne =~ s/null//g;

       		#print $ligne;

        	$ligne =~ / values \((.*?)\);/;
        	#print "#$1#\n";
		@fields = split(/,/,$1);

        	$nsid = "chebi:@fields[0]";
        	$bmuri = "http://bio2rdf.org/$nsid";
		$source = @fields[2];
        	$source =~ s/ /_/g;

print <<EOF;
<$bmuri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/chebi#Compound> .
<$bmuri> <http://purl.org/dc/elements/1.1/identifier> "$nsid" .
<$bmuri> <http://purl.org/dc/elements/1.1/title> "@fields[1]" .
<$bmuri> <http://www.w3.org/2000/01/rdf-schema#label> "@fields[1] [$nsid]" .
<$bmuri> <http://bio2rdf.org/ns/chebi#xSource> <http://bio2rdf.org/chebi:source-$source> .
<$bmuri> <http://bio2rdf.org/ns/chebi#xParent> <http://bio2rdf.org/chebi:@fields[3]> .
<$bmuri> <http://bio2rdf.org/ns/chebi#status> <http://bio2rdf.org/chebi:status-@fields[5]> .
<$bmuri> <http://www.w3.org/2000/01/rdf-schema#comment> "@fields[6]" .
<$bmuri> <http://purl.org/dc/elements/1.1/modified> "@fields[7]" .

EOF

	}
	close(ENTREE);
}

sub Name {
#insert into names (id, compound_id, name, type, source, adapted, language) values ('813','490','3,4-Dihydro-7-hydroxy-1-[(3-hydroxy-4-methoxyphenyl)methyl]-6-methoxy-2-(methyl-14C)-isoquinolinium','SYNONYM','KEGG COMPOUND','F','English');  

	open(ENTREE, "< $path/names.sql") || die "Fichier $fichier introuvable:$!\n";

	$nombre = 0;
	$ligne = <ENTREE>;
	while ($ligne = <ENTREE> and $nombre < $max) {
        	$nombre = $nombre + 1;

        	$ligne =~ s/>/&gt;/g;
        	$ligne =~ s/</&lt;/g;
    		$ligne =~ s/'//g;
	        $ligne =~ s/null//g;

       		#print $ligne;

        	$ligne =~ / values \((.*?)\);/;
        	#print "#$1#\n";
		@fields = split(/,/,$1);

        	$nsid = "chebi:@fields[1]";
        	$bmuri = "http://bio2rdf.org/$nsid";

	if (@fields[3] eq "NAME") {
		print "<$bmuri> <http://purl.org/dc/elements/1.1/title> \"@fields[2]\" .\n";
 	} else {
		print "<$bmuri> <http://bio2rdf.org/ns/bio2rdf#synonym> \"@fields[2]\" .\n";
	} 

	}
	close(ENTREE);
}


sub Chemical {
#insert into chemical_data (id, compound_id, chemical_data, source, type) values ('71605','51976','C39H44N2O6S','ChEBI','FORMULA');                           
	open(ENTREE, "< $path/chemical_data.sql") || die "Fichier $fichier introuvable:$!\n";

	$nombre = 0;
	$ligne = <ENTREE>;
	while ($ligne = <ENTREE> and $nombre < $max) {
        	$nombre = $nombre + 1;

        	$ligne =~ s/>/&gt;/g;
        	$ligne =~ s/</&lt;/g;
    		$ligne =~ s/'//g;
	        $ligne =~ s/null//g;

       		#print $ligne;

        	$ligne =~ / values \((.*?)\);/;
        	#print "#$1#\n";
		@fields = split(/,/,$1);

        	$nsid = "chebi:@fields[1]";
        	$bmuri = "http://bio2rdf.org/$nsid";
        	$predicate = "http://bio2rdf.org/ns/chebi#@fields[4]";

		print "<$bmuri> <$predicate> \"@fields[2]\" .\n";
	}
	close(ENTREE);
}


