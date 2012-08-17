$:.unshift(File.dirname(__FILE__))
$:.unshift(File.join(File.dirname(__FILE__),"../../../lib/"))
require 'rubygems'
require 'rdf'
require 'rdf/ntriples'
require 'digest/md5'
require 'cgi'
require 'namespaces'

##
# convert the activities table of the experimental table section of Chembl to RDF
# @author:: Dana Klassen
# @contact:: dana . klassen AT deri.org
module Chembl2Rdf
  class References
    include NameSpaces
    
    def initialize(output,debug=false)
      @output = output
      @log = Logger.new(STDOUT) if :debug
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
    # query for the entire table of activities
    def query_all_ids
     @dbh.query("SELECT DISTINCT * FROM docs WHERE doc_id > 0 ")
    end

    
    def process
      @log.info "Generating assay rdf" if @debug
      RDF::NQuads::Writer.open(File.join(@output,"chembl_references.nq")) do |writer|
           
        record = RDF::URI.new("http://bio2rdf.org/chembl")

        query_all_ids.each_hash do |row|
            reference = @chembl["reference_"+row['doc_id']]

            if(row['journal']  != nil)
              journal = @chembl['journal_'+Digest::MD5.hexdigest(row['journal'])]
              writer << [reference,@chembl_vocabulary['journal'],journal,record]
              writer << [journal,RDF.type,@chembl_vocabulary['Journal'],record]
              writer << [journal,RDFS.label,CGI.escape(row['journal']),record]
            end
            
            writer << [reference,RDF.type,@chembl_vocabulary['Article'],record]
            writer << [reference,RDFS.seeAlso,RDF::URI.new("http://www.ncbi.nlm.nih.gov/pubmed/#{row['pubmed_id']}"),record] unless row['pubmed_id'] == nil
            writer << [reference,@chembl_vocabulary['date'],row['date'],record] unless row['date'] == nil
            writer << [reference,@chembl_vocabulary['issue'],row['issue'],record] unless row['issue'] == nil
        end.free
      end
    end
  end
end
