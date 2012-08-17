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
  class Properties
    
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
    
    ##
    # query for the entire table of activities
    def query_all_ids
     @dbh.query("SELECT * FROM compound_properties ")
    end
    
    
    ##
    # Process the molecular properties from chembl
    def process
      props = {"alogp" => ["AlogP","double"],
               "hba"=> ["Hydrogen_Bond_Acceptors","integer"],
               "hbd"=> ["Hydrogen_Bond_Donors","integer"],
               "psa"=> ["Polar_Surface_Area","double"],
               "rtb"=> ["Rotatable_Bonds","integer"],
               "num_ro5_violations" => ["Rule_Of_5_Violations","integer"],
               "ro3_pass"=>["Ro2_Pass","string"],
               "acd_most_apka"=> ["Acd_most_apka","double"],
               "acd_most_bpka"=> ["Acd_most_bpka","double"],
               "acd_logp" => ["Acd_Logp","double"],
               "acd_logd"=> ["Acd_Logd","double"],
               "full_mwt"=> ["Full_Molecular_Weight","double"]}
               
               type = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> "
               label= "<http://www.w3.org/2000/01/rdf-schema#label> "
               dcid = "<http://purl.org/dc/elements/1.1/identifier> "
               title = "<http://purl.org/dc/elements/1.1/title> "
               comment = "<http://www.w3.org/2000/01/rdf-schema#comment> "
               value = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#value> "
               named_graph = "<http://bio2rdf.org/chembl> "
                
      		eol = ".\n"
               
      @log.info "Generating assay rdf" if @debug
      File.open(File.join(@output,"chembl_properties.nq"),"w+") do |writer|
		 
        query_all_ids.each_hash do |row|
            record = "<http://bio2rdf.org/chembl> "
            compound = "<http://bio2rdf.org/chembl:chembl_compound_#{row['molregno']}> "
            
            props.each_pair do |k,v|
              buf=""
              if(row[k])
                 
                p = "<http://bio2rdf.org/chembl:#{k.to_s}_#{row['molregno']}> "
                buf << compound + "<http://bio2rdf.org/chembl_vocabulary:#{v[0].downcase}> " + p + record + eol
                buf << p + type + "<http://bio2rdf.org/chembl_vocabulary:#{v[0]}> " + record + eol
                buf << p + value + "\"#{row[k]}\"^^<http://www.w3.org/2001/XMLSchema##{v[1]}>  " + record + eol
              end
              
              writer << buf
            end
          
        end.free
      end
    end
  end
end