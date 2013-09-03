#PDB Bio2RDF parser
This is the Bio2RDF parser for the PDB dataset. The files used for the conversion are the PDBML files.

##Requirements
	1. Sun Java JRE 1.6 or above+
	2. Apache Maven 2.2.0 or above+

##Building
	1. Simply run: mvn clean install

##Contents

 1. **pdb2rdf-cli**:
   The command line interface for this parser. Once you have installed the software go to pdb2rdf-cli/target and extract `pdb2rdf-cli-2.0.0-bin.zip`. This file contains `pdb2rdf.sh` an executable shell script that can be used to execute this rdfizer. Here are some example execution types:
     1. Print the help:
       `./pdb2rdf.sh -help`
     2. Convert one PDB file given its id and store the output in /tmp/output/:
       `./pdb2rdf.sh -out /tmp/output`
     3. Convert all PDB entries found in a given directory:
       `./pdb2rdf.sh -dir`


 2. **pdb2rdf-parser**:
   orem ipsum dolor sit amet, consectetur adipiscing elit. Donec turpis augue, tincidunt nec dictum sed, ornare in enim. In fringilla ornare urna sit amet vestibulum. Donec ultricies purus metus, id bibendum tortor posuere nec. Phasellus quis felis ut risus ultricies tristique ut eu dui. Nullam fermentum sem in massa convallis, sed hendrerit erat elementum. Donec ut sapien eu augue sollicitudin pulvinar. In felis purus, suscipit at dolor non, consequat pharetra odio. Vivamus tempor sagittis pellentesque. Curabitur eleife

 3. **pdb2rdf-cluster**:
   orem ipsum dolor sit amet, consectetur adipiscing elit. Donec turpis augue, tincidunt nec dictum sed, ornare in enim. In fringilla ornare urna sit amet vestibulum. Donec ultricies purus metus, id bibendum tortor posuere nec. Phasellus quis felis ut risus ultricies tristique ut eu dui. Nullam fermentum sem in massa convallis, sed hendrerit erat elementum. Donec ut sapien eu augue sollicitudin pulvinar. In felis purus, suscipit at dolor non, consequat pharetra odio. Vivamus tempor sagittis pellentesque. Curabitur eleife
	


