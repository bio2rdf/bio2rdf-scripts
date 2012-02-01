require File.join(File.expand_path(File.dirname(__FILE__)),"..","lib","sparqladaptor")

query =  Query.new()

query.select("?d ?f ?g")
query.where("?d ?f ?g.")

sparql = SparqlAdaptor.new(:uri=>"http://bio2rdf.semanticscience.org:8026/sparql?")
sparql.prefix(:cebs=>"http://bio2rdf.org/cebs:")
sparql.query(query)

