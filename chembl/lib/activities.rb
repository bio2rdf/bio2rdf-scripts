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
  class Activities
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
     @dbh.query("SELECT DISTINCT * FROM activities")
    end

    
    def process
      
      type = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> "
      label= "<http://www.w3.org/2000/01/rdf-schema#label> "
      dcid = "<http://purl.org/dc/elements/1.1/identifier> "
      title = "<http://purl.org/dc/elements/1.1/title> "
      comment = "<http://www.w3.org/2000/01/rdf-schema#comment> "
      value = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#value> "
      chembl = "http://hcls.sindice.com/chembl:"
      chembl_v = "http://hcls.sindice.com/chembl_vocabulary:"
      named_graph = "<http://hcls.sindice.com/chembl> "
      eol = ".\n"

      @log.info "Generating assay rdf" if @debug
      File.open(File.join(@output,"chembl_activities.nq"),"w+") do |writer|
		 
        query_all_ids.each_hash do |id|
          
          buf=""
          
          record = "<http://hcls.sindice.com/chembl> "
          activity = "<#{chembl}activity_#{id['activity_id']}> "
          assay    = "<#{chembl}assay_#{id['assay_id']}> "
          compound = "<#{chembl}chembl_compound_#{id['molregno']}> "
          
          buf << activity + type + "<#{chembl_v}Activity> " + record + eol
          buf << assay    + "<#{chembl_v}activity> " + activity + record + eol
          buf << activity + "<#{chembl_v}compound> " + compound + record + eol
          buf << activity + comment + "\"#{id['activity_comment']}\" " + record + eol unless id['activity_comment'] == nil
          
          # relation
          buf << activity + "<#{chembl_v}relation> \"#{id['relation']}\" " + record + eol unless id['relation'] == nil
          buf << activity + "<#{chembl_v}standard_value> \"#{id['standard_value']}\"^^<http://www.w3.org/2001/XMLSchema#double> " + record + eol
        
          # standard units
          if(id['standard_units'] !=nil)
            su = "<#{chembl}su_#{Digest::MD5.hexdigest(id.values.join)}> "
            buf << su + type + "<#{chembl_v}Standard_Units> " + record + eol
            buf << su + value + "\"#{id['standard_units']}\" " + record + eol
            buf << activity + "<#{chembl_v}standard_units> " + su + record + eol
          end
          
          # standard type
          if(id['standard_type'] !=nil)
            st = "<#{chembl}st_#{Digest::MD5.hexdigest(id.values.join)}> "
            buf << st + type + "<#{chembl_v}Standard_Type> " + record + eol
            buf << st + value + "\"#{id['standard_type']}\" " + record + eol
            buf << activity + "<#{chembl_v}standard_type> "+ st + record + eol
          end 
  
          writer << buf
          
        end
      end
    end
  end
end
