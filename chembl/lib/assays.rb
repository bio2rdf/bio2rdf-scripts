$:.unshift(File.dirname(__FILE__))
$:.unshift(File.join(File.dirname(__FILE__),"../../../lib/"))
require 'rubygems'
require 'rdf'
require 'rdf/ntriples'
require 'digest/md5'
require 'cgi'

module Chembl2Rdf
  class Assays
    
    def initialize(output,debug=false)
      @output = output
      @log = Logger.new(STDOUT) if :debug
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
    
    def query_all_ids
     @dbh.query("SELECT DISTINCT * FROM assays, assay_type WHERE assays.assay_type = assay_type.assay_type")
    end
    
    def query_assay2target(assay_id)
     @dbh.query("SELECT DISTINCT * FROM assay2target WHERE assay_id = #{assay_id}")
    end
    
    def process

      type     = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> "
      label    = "<http://www.w3.org/2000/01/rdf-schema#label> "
      dcid     = "<http://purl.org/dc/elements/1.1/identifier> "
      title    = "<http://purl.org/dc/elements/1.1/title> "
      comment  = "<http://www.w3.org/2000/01/rdf-schema#comment> "
      value    = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#value> "
      chembl   = "http://bio2rdf.org/chembl:"
      chembl_v = "http://bio2rdf.org/chebl_vocabulary:"
      equiv    = "<http://www.w3.org/2002/07/owl#equivalentClass> " 
      sameas    = "<http://www.w3.org/2002/07/owl#sameAs> " 
      named_graph = "http://bio2rdf.org/chembl> "
      eol = ".\n"

      @log.info "Generating assay rdf" if @debug

      File.open(File.join(@output,"chembl_assays.nq"),"w+") do |writer|
      
		 
        query_all_ids.each_hash do |row|
          
          buf = ""
          record = "<http://bio2rdf.org/chembl> "
          assay = "<#{chembl}assay_" + row['assay_id']+"> "
          cid = "<#{chembl}"+row['chembl_id']+"> "
                    
          buf << assay + type + "<#{chembl_v}Assay> " + record + eol
          buf << assay + dcid + "\"chembl:#{row['chembl_id']}\" " + record + eol
          buf << cid + equiv + assay + record + eol
          buf << assay + equiv + cid + record + eol
          buf << cid + dcid + "\"chembl:#{row['chembl_id']}\" " + record + eol
          buf << assay + "<#{chembl_v}assay_type> <#{chembl}#{row['assay_desc']}> " + record + eol
          buf << assay + comment + "\"#{CGI.escape(row['description'])}\" " + record + eol unless row['description'] == nil
          buf << assay + "<#{chembl_v}cites_as_data_source> <#{chembl}reference_#{row['doc_id']}> " + record + eol unless row['doc_id'] == nil
          buf <<assay + "<#{chembl_v}assay_tax_id> "http://bio2rdf.org/taxon:#{row['assay_tax_id']}> " + record + eol unless row['assay_tax_id'] == nil
            
          # section to link up targets and assays
          query_assay2target(row['assay_id']).each_hash do |a2t|
            # assign target identifiers
            conscore = "<#{chembl}confidence_score_#{Digest::MD5.hexdigest(row['chembl_id']+a2t['confidence_score'])}> "

            buf << assay + "<#{chembl_v}target> <#{chembl}target_#{a2t['tid']}> " + record + eol unless a2t['tid'] == nil
            buf << assay + "<#{chembl_v}confidence_score> "+conscore+" " + record + eol
            buf << conscore + type + "<#{chembl_v}Confidence_score> " + record + eol
            buf << conscore + value + "\"#{a2t['confidence_score']}\" " + record + eol
          end.free
         
          writer << buf
          
        end.free
      end
    end
  end
end
