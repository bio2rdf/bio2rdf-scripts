Toxicology Knowledge Base
=========================

Introduction
------------

The Toxicology Knowledge Base (ToxKB) is an integrated resource of toxicology
information. The scripts listed here convert toxicology resources into Linked
Data to incorporation into the Bio2RDF project.

Instructions
------------

All scripts were written using the Ruby scripting language and make use of
external Ruby libraries. These libraries are listed in the gemfile. Installation
of the gemfile is accomplished using bundle in the project directory:

> bundle install

Additional outdated gems may be required to support older scripts. These gems
are located in the outdated_gems directory. They can be installed using the
rubygems package manager.

Linked Data
------------
The ruby scripts in scripts/ are used to convert their respective resources into
Linked Data. A description of each resource is below:

Comparative Toxicogenomics Database(CTD)
Data source: http://ctdbase.org/ 
Accession date: 2010-09-10

The comparative toxicogenomics database houses curated information related to
cross species chemical-gene/protein interactions and chemical- and gene-disease
associations. The goal to help uncover the etiology of environmental disease.
The data curation process is well documented-{Wiegers 2009}- and leverages
unique identifiers (MeSh, CAS, Geneid, PubMed, and OMIM) to represent
participants in an association.

U.S Environmental Protection Agency Computational Toxicology Research Program
(U.S EPA ToxCast)
Data Source: http://www.epa.gov/ncct/toxcast/
Accession Date: 2011-11-10

THE U.S EPA ToxCast program was created to develop to develop strategies to
prioritize chemical testing-{Dix 2007}-. The goal to support in vitro/in vivo
testing by evaluating novel in vitro assays for predictive ability and
developing chemical toxicity predictors. The program has augmented an initial
resource of 320 well characterized chemicals  with test data from over 650 high
throughput assays. All generated data is verified and assessed by the U.S EPA.

National Library of Medicine TOXNET Archives (NLM TOXNET)
Data Source:http://toxnet.nlm.nih.gov/
Accession Date: 2010-09-10

The NLM TOXNET archives is a collection of data files related to toxicological
and environmental health information-{Fonger 2000}-. Contained in these
datafiles are the Genetic Toxicology Datafile (GENETOX)-{Waters 1981}-, Chemical
Carcinogen-sis Research Information System (CCRIS),  Hazardous Substance
Database (HSDB),  and Toxicology LIterature Online (TOXLINE).

Distributed Structure-Searchable Toxicity Database-Network (DSSTOX)
Data Source:http://www.epa.gov/ncct/dsstox/
Accession Date: 2011-11-10

DSSTOX is an effort by the U.S EPA to develop a public foundation for structure
searchable toxicology information. DSSTOX provides chemcical structure
information and links to toxicity data sets of environmental relevance. This
resources includes links to toxicogenomics information from Array Express and
chemical carcinogenicity data from the Carcinogenic Potency Database(CPDB)

Chemical Effects in Biological Systems(CEBS)
Data Source:http://www.niehs.nih.gov/research/resources/databases/cebs/index.cfm
Accession:2010-10-30

CEBS is a unique resource dedicated to providing data in the context of biology
and study design. This resource contains meta information related to study
design from sources such as Array Express and GEO.

The linked data produced from these scripts is avaiable to query
from the sparql endpoint: 
  
  >toxkb.bio2rdf.org:1250/sparql
  >s4.semanticscience.org:1250/sparql

Ontology
--------
The Linked Data created by the conversion has a back up ontology to integrate
data improving querying of data. The ontology utlizes the Semanticscience
Integrated Ontology (SIO).
