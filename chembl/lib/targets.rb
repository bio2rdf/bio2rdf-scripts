$:.unshift(File.dirname(__FILE__))
$:.unshift(File.join(File.dirname(__FILE__),"../../../lib/"))
require 'rubygems'
require 'rdf'
require 'rdf/ntriples'
require 'digest/md5'
require 'cgi'
require 'namespaces'

module Chembl2Rdf
  class Targets
    include NameSpaces
    
    def initialize(output,debug=false)
      @output = output
      @log = Logger.new(STDOUT) if debug
      load_namespaces
    end
    
    ##
    # connect to mysql database
    # @params [String] user of the database with read privs
    # @params [String] pass of the user
    # @params [String] the database containing Chembl
    def connect(user,pass,database)
      begin
        @dbh = Mysql.real_connect("localhost",user,pass,database)
      rescue Mysql::Error => e
        STDERR.puts "Error code: #{e.errno}"
        STDERR.puts "Error message: #{e.error}"
        STDERR.puts "Error SQLSTATE: #{e.sqlstate}" if e.respond_to?("sqlstate")
        exit!
      end
    end
    
    ##
    # disconnect from the server
    def disconnect
      @dbh.close if @dbh
    end
    
    ##
    # query for all unique ids of targets
    def query_all_ids
     @dbh.query("SELECT DISTINCT * FROM target_dictionary")
    end
    
    def query_target_classification(tid)
      @dbh.query("SELECT * FROM target_class WHERE tid = \"#{tid}\"")
    end
    
    def process
      @log.info "Generating assay rdf" if @debug
      RDF::NQuads::Writer.open(File.join(@output,"chembl_targets.nq")) do |writer|
     
     	record =  RDF::URI.new("http://bio2rdf.org/chembl")

        query_all_ids.each_hash do |row|
          target  = @chembl["target_"+row['tid']]
          
          writer << [target,RDF.type,@chembl_vocabulary['Target'],record] 
          writer << [target,@chembl_vocabulary['pref_name'],row['pref_name'],record] unless row['pref_name'] == nil
          writer << [target,RDFS.comment,CGI.escape(row['description']),record] unless row['description'] == nil
          writer << [target,OWL.equivalentClass,@chembl[row['chembl_id']],record]
          
          if(row['tissue']!=nil)
            tissue = @chembl["tissue_"+Digest::MD5.hexdigest(row['tissue'])]
            writer << [target,@chembl_vocabulary['tissue'],tissue,record]
            writer << [tissue, RDFS.label, row['tissue'],record]
          end
          
          if(row['keywords'] !=nil)
            row['keywords'].split(";").each do |keywrd|
              writer << [target,@chembl_vocabulary['keyword'],keywrd.strip.chomp,record]
            end
          end
          
          case row['target_type']

                      when "PROTEIN"
                        writer << [target,RDF.type,@chembl_vocabulary['Protein'],record]

                        row['gene_names'].split(";").each do |gene_name|
                          gene = @hgnc[gene_name.strip.chomp.upcase]
                          writer << [target,@chembl_vocabulary['gene'],gene,record]
                          writer << [target,RDFS.label,gene_name.strip.chomp,record]
                        end   unless row['gene_names'] == nil
                        
                        row['synonyms'].split(";").each do |syn|
                          writer << [target,@chembl_vocabulary['synonym'], syn.strip.chomp,record]
                        end unless row['synonyms'] == nil
                                                
                        writer << [target,@chembl_vocabulary['source'],row['source'],record] unless row['source'] == nil
                        writer << [target,@chembl_vocabulary['ec_number'],row['ec_number'].gsub(".","\."),record] unless row['ec_number'] == nil
                        writer << [target,OWL.equivalentClass,RDF::URI.new("http://www.uniprot.org/uniprot/#{row['protein_accession']}"),record] unless row['protein_accession'] == nil
                        writer << [RDF::URI.new("http://www.uniprot.org/uniprot/#{row['protein_accession']}"),DC.identifier,"uniprot:#{row['protein_accession']}",record] unless row['protein_accession'] == nil
                        writer << [target,@chembl_vocabulary['protein_sequence'],row['protein_sequence'],record] unless row['protein_sequence'] == nil
                        
                        if(row['tax_id'] != nil)
                          taxon = RDF::URI.new("http://bio2rdf.org/taxon:#{row['tax_id']}") 
                       		writer << [taxon,RDF.type ,@chembl_vocabulary['Organism']]
							            writer << [target,@chembl_vocabulary['organism'], taxon, record] 
                       		writer << [taxon,DC.title, row['organism'],record ] unless row['organism'] == nil
                        end
                        
                      when "ADMET"
                        writer << [target,RDF.type,@chembl_vocabulary['Admet'],record]
                        writer << [target,RDFS.comment,row['description'],record]
                        writer << [target,RDFS.label,row['pref_name'],record] unless row['pref_name'] == nil
                      when "CELL-LINE"
                        writer << [target,RDF.type,@chembl_vocabulary['Cell_Line'],record]
                        writer << [target,RDFS.label,row['pref_name'],record] unless row['pref_name'] == nil
                        writer << [target,@chembl_vocabulary['cell_line'],row['cell_line'],record] unless row['cell_line'] == nil
                        
                        if(row['tax_id'] != nil)
                       		taxon = RDF::URI.new("http://bio2rdf.org/taxon:#{row['tax_id']}") 
                          puts taxon
							            writer << [target,@chembl_vocabulary['organism'], taxon, record] 
                       		writer << [taxon,DC.title, row['organism'],record ] unless row['organism'] == nil
                        end
                      when "NUCLEIC-ACID"
                        writer << [target,RDF.type,@chembl_vocabulary['Nucleic_Acid'],record]
                        
                        if(row['tax_id'] != nil)
                       		taxon = RDF::URI.new("http://bio2rdf.org/taxon:#{row['tax_id']}") 
							            writer << [target,@chembl_vocabulary['organism'], taxon, record] 
                       		writer << [taxon,DC.title, row['organism'],record ] unless row['organism'] == nil
                        end
                      when "ORGANISM"
                        writer << [target,RDF.type,@chembl_vocabulary['Organism'],record]
                        writer << [target,OWL.equivalentClass,@taxon[row['tax_id']],record] unless row['tax_id'] == nil
                        writer << [target,DC.title,row['organism'],record] unless row['organism'] == nil
                      when "SUBCELLULAR"
                        writer << [target,RDF.type,@chembl_vocabulary['Subcellular'],record]
                        
                        if(row['tax_id'] != nil)
                       		taxon = RDF::URI.new("http://bio2rdf.org/taxon:#{row['tax_id']}") 
							            writer << [target,@chembl_vocabulary['organism'],taxon,record] 
                       		writer << [taxon,DC.title, row['organism'],record ] unless row['organism'] == nil
                        end
                      when "TISSUE"
                        writer << [target,RDF.type,@chembl_vocabulary['Tissue'],record]
                        
                        if(row['tax_id'] != nil)
                       		taxon = RDF::URI.new("http://bio2rdf.org/taxon:#{row['tax_id']}") 
							            writer << [target,@chembl_vocabulary['organism'], taxon, record] 
                       		writer << [taxon,DC.title, row['organism'],record ] unless row['organism'] == nil
                        end
                      when "UNCHECKED"
                        writer << [target,RDF.type,@chembl_vocabulary['Unchecked'],record]
                      when "UNKNOWN"
                        writer << [target,RDF.type,@chembl_vocabulary['Unknown'],record]
            end
                      
            query_target_classification(row['tid']).each_hash do |cls|
              writer << [target,@chembl_vocabulary['classL1'],cls['l1'],record] unless cls['l1'] == nil
              writer << [target,@chembl_vocabulary['classL2'],cls['l2'],record] unless cls['l2'] == nil
              writer << [target,@chembl_vocabulary['classL3'],cls['l3'],record] unless cls['l3'] == nil
              writer << [target,@chembl_vocabulary['classL4'],cls['l4'],record] unless cls['l4'] == nil
              writer << [target,@chembl_vocabulary['classL5'],cls['l5'],record] unless cls['l5'] == nil
              writer << [target,@chembl_vocabulary['classL6'],cls['l6'],record] unless cls['l6'] == nil
              writer << [target,@chembl_vocabulary['classL7'],cls['l7'],record] unless cls['l7'] == nil
              writer << [target,@chembl_vocabulary['classL8'],cls['l8'],record] unless cls['l8'] == nil
            end.free
        end.free
    end
  end
  end
  end
