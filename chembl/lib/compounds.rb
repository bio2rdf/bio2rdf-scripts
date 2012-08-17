$:.unshift(File.dirname(__FILE__))
require 'rubygems'
require 'rdf'
require 'rdf/ntriples'
require 'mysql'
require 'digest/md5'
require 'namespaces'
require 'cgi'

##
# module with the necessary methods for parsing the chembl database
#
module Chembl2Rdf
  class Compounds
    
    def initialize(output,debug=false)
      @log = Logger.new(STDOUT) if(debug)
      @output = output
      @limit
    end

    ##
    # query the mysql database containing the Chembl database
    # @params [] mysql connection to database
    # @returns [] results of the query
    def query_compound_ids()
        @dbh.query("SELECT DISTINCT molregno FROM compound_records")
    end
    
    ##
    # query the compound names based on the molregno identifier
    # @ params [String] molregno of the compound we want information for.
    def query_compound_names(molregno)
      @dbh.query("SELECT DISTINCT compound_name FROM compound_records WHERE molregno = #{molregno}")
    end
    
    def query_compound_literature_references(molregno)
      @dbh.query("SELECT DISTINCT doc_id FROM compound_records WHERE molregno = #{molregno}")
    end
    
    def query_identifiers(molregno)
      @dbh.query("SELECT DISTINCT * from molecule_dictionary where molregno = #{molregno}")
    end
    
    def query_compound_structure_info(molregno)
      @dbh.query("SELECT DISTINCT * FROM compound_structures WHERE molregno = #{molregno}")
    end
    
    def query_synonyms(molregno)
        @dbh.query("SELECT DISTINCT * FROM molecule_synonyms WHERE molregno = #{molregno}")
    end
    
    def query_compound_hierarchy(molregno)
      @dbh.query("SELECT DISTINCT * FROM molecule_hierarchy WHERE molregno = #{molregno}")
    end
    
    ##
    # process the database to RDF
    def process
      
      @log.info "Generating RDF for compounds" if @log
      
      type = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> "
      label= "<http://www.w3.org/2000/01/rdf-schema#label> "
      dcid = "<http://purl.org/dc/elements/1.1/identifier> "
      title = "<http://purl.org/dc/elements/1.1/title> "
      comment = "<http://www.w3.org/2000/01/rdf-schema#comment> "
      value = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#value> "
      chembl = "http://bio2rdf.org/chembl:"
      chembl_v = "http://bio2rdf.org/chembl_vocabulary:"
      named_graph = "<http://bio2rdf.org/chembl> "
      eol = ".\n"
      
     File.open(File.join(@output,"chembl_compounds.nq"),"w+") do |writer|
		
		 
        query_compound_ids.each_hash do |row|   
        
          buf=""
          
          
          #the molecule
          molregno = row["molregno"]
          
          record = "<http://bio2rdf.org/chembl> "
          
          # create uri that we are going to reference
          compound = "<#{chembl}chembl_compound_#{molregno}> "
          buf << compound + type + "<#{chembl_v}Compound> " + record + eol
          buf << compound + dcid + "\"#{molregno}\" " +  record + eol
          
          # lets name our compound
          query_compound_names(molregno).each_hash do |row|
            buf << compound + title + "\"#{CGI.escape(row['compound_name'])}\" " + record + eol unless row['compound_name'] == nil
          end.free
        
          # assign literature references
          query_compound_literature_references(molregno).each_hash do |row|
            buf << compound + "<#{chembl_v}cites_as_reference> "+"<#{chembl}reference_#{row['doc_id']}> " + record + eol
          end.free
        
          # assign the compound types and identifiers
          query_identifiers(molregno).each_hash do |row|
            if(row['molecule_type'])
           
              case row['molecule_type']
              when "Small Molecule"
                buf << compound + type + "<#{chembl_v}Small_Molecules> " + record + eol
              when "Cell"
                buf << compound + type + "<#{chembl_v}Cell> " + record + eol
              when "Protein"
                buf << compound + type + "<#{chembl_v}Protein> " + record + eol
              when "Oligosaccharide"
                buf << compound + type + "<#{chembl_v}Oligosaccharide> " + record + eol
              when "Antibody"
                buf << compound + type + "<#{chembl_v}Antibody> " + record + eol
              when "Drug"
                buf << compound + type + "<#{chembl_v}Drug> " + record + eol
              end
            end
              buf << compound + "<http://www.w3.org/2002/07/owl#equivalentClass> "+"<#{chembl}#{row['chembl_id']}> " + record + eol unless row['chembl_id'] == nil || row['chembl_id'] == ""
              buf << compound + "<http://www.w3.org/2002/07/owl#equivalentClass> "+"<http://bio2rdf.org/chebi:#{row['chebi_id']}> " + record + eol unless row['chebi_id'] == nil || row['chebi_id'] == ""
          end.free
        
        # assign the structure information
        query_compound_structure_info(molregno).each_hash do |row|
          
            if(row['canonical_smiles'] != nil )
              smiles = "<#{chembl}smiles_#{Digest::MD5.hexdigest(row['canonical_smiles'])}> "
              buf << smiles + type + "<#{chembl_v}Canonical_Smiles> " + record + eol
              buf << smiles + value + "\"#{CGI.escape(row['canonical_smiles'])}\" " + record + eol
              buf << compound + "<#{chembl_v}smiles> " + smiles + record + eol
            end
          
            if(row['standard_inchi'] != nil)
              standard_inchi = "<#{chembl}standard_inchi_#{Digest::MD5.hexdigest(row['standard_inchi'])}> "
              buf << standard_inchi + type + "<#{chembl_v}Standard_Inchi> " + record + eol
              buf << standard_inchi + value + "\"#{CGI.escape(row['standard_inchi'])}\" " + record + eol
              buf << compound + "<#{chembl_v}standard_inchi> " + standard_inchi + record + eol
            end
          
            if(row['standard_inchi_key'] != nil)
              standard_inchi_key = "<#{chembl}#{row['standard_inchi_key']}> "
              buf << standard_inchi_key + type + "<#{chembl_v}Standard_Inchi_Key> " + record + eol
              buf << standard_inchi_key + value + "\"#{CGI.escape(row['standard_inchi_key'])}\" " + record + eol
              buf << compound + "<#{chembl_v}standard_inchi_key> " + standard_inchi_key + record + eol
            end
          
          end.free
        
          # assign the synonyms
          query_synonyms(molregno).each_hash do |row|
            if(row['synonyms'])
              buf << compound+"<#{chembl_v}synonym> \"#{CGI.escape(row['synonyms'])}\" " + record + eol
            end
          end.free
        
          # get the compound hierarchy information
          query_compound_hierarchy(molregno).each_hash do |row|
            if(row['parent_molregno'] != molregno)
               pcomp = "<#{chembl}chembl_compound_#{row['parent_molregno']}> "
                buf << compound+"<#{chembl_v}parent_compound> " + pcomp + record + eol
            end
          
            if(row['active_molregno'] != molregno)
               ac = "<#{chembl}chembl_compound_#{row['active_molregno']}> "
               buf << compound + "<#{chembl_v}active_compound> " + ac + record + eol
            end
          end.free  
          
          writer << buf
        end
      end
      self.disconnect
    end
    
    ##
    # connect to mysql database
    # @params [String] user of the database with read privs
    # @params [String] pass of the user
    # @params [String] the database containing Chembl
    def connect(user,pass,database)
      begin
        @log.info "Conecting to database using user: #{user} and table: #{database}" if @log
        @dbh = Mysql.real_connect("localhost",user,pass,database)
      rescue Mysql::Error => e
        @log.error "Error code: #{e.errno}" if @log
        @log.error "Error message: #{e.error}" if @log
        @log.error "Error SQLSTATE: #{e.sqlstate}" if e.respond_to?("sqlstate") if @log
      end
    end
    
    ##
    # disconnect from the server
    def disconnect
      @dbh.close if @dbh
    end
  
  end
end
