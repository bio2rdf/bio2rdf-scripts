require 'rubygems'
require 'rdf'

## 
# Modules holds the vocabulary instances of namespaces
# required to make and link triples in the 
# Lundbeck Knowledge Base

module NameSpaces
    include RDF
    
  # all the namespaces necessary to process datafiles
  def load_namespaces
    host = "http://bio2rdf.org"
    @ctd             = RDF::Vocabulary.new("#{host}/ctd:")
    @ctd_vocabulary  = RDF::Vocabulary.new("#{host}/ctd_vocabulary:")
    @pubmed          = RDF::Vocabulary.new("http://www.ncbi.nlm.nih.gov/pubmed/")
    @pubchem         = RDF::Vocabulary.new("#{host}/pubchem:")
    @pubchem_compound  = RDF::Vocabulary.new("#{host}/pubchem_compound:")
    @geneid          = RDF::Vocabulary.new("#{host}/geneid:")
    @mesh            = RDF::Vocabulary.new("#{host}/mesh:")
    @taxon           = RDF::Vocabulary.new("#{host}/taxon:")
    @cas             = RDF::Vocabulary.new("#{host}/cas:")
    @goa             = RDF::Vocabulary.new("#{host}/goa:")
    @goa_vocabulary  = RDF::Vocabulary.new("#{host}/goa_vocabulary:")
    @go_ref          = RDF::Vocabulary.new("#{host}/go_ref:")
    @uniprot         = RDF::Vocabulary.new("#{host}/uniprot:")
    @pdb             = RDF::Vocabulary.new("#{host}/pdb:")
    @ipi             = RDF::Vocabulary.new("#{host}/ipi:")
    @go              = RDF::Vocabulary.new("http://purl.org/obo/owl/GO#")
    @omim            = RDF::Vocabulary.new("#{host}/omim:")
    @omim_vocabulary = RDF::Vocabulary.new("#{host}/omim_vocabulary:")
    @hgnc            = RDF::Vocabulary.new("#{host}/hgnc:")
    @chebi           = RDF::Vocabulary.new("#{host}/chebi:")
    @chebi_vocabulary= RDF::Vocabulary.new("#{host}/chebi_vocabulary:")
    @chembl          = RDF::Vocabulary.new("#{host}/chembl:")
    @chembl_vocabulary = RDF::Vocabulary.new("#{host}/chembl_vocabulary:")
    @sio             = RDF::Vocabulary.new("http://semanticscience.org/ontologies/sio:")
    @kegg            = RDF::Vocabulary.new("#{host}/kegg:")
    @reactome        = RDF::Vocabulary.new("#{host}/react:")
    @sider           = RDF::Vocabulary.new("#{host}/sider:")
    @sider_vocabulary= RDF::Vocabulary.new("#{host}/sider_vocabulary:")
    @meddra           = RDF::Vocabulary.new("#{host}/meddra:")
  end
end
