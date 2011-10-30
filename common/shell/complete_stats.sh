#!/bin/bash
echo -ne "FFFF\t" $1 "\n"
perl /bio2rdf/perl/pipeline/shared/generate_namespace_association.pl $1 | sort | uniq -c
echo -ne "TTTT\t"; zcat $1 | sed '/^\s*$/d' | wc -l
