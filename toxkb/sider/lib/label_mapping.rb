$:.unshift(File.dirname(__FILE__))

require 'rubygems'
require 'rdf'
require 'rdf/ntriples'
require 'namespaces'
require 'logger'
require 'cgi'

include RDF

module Sider2Rdf
  
  ##
  # Class to convert the SIDER database file medra_adverse_effects to RDF
  # @author:: Dana Klassen
  # @contact: dana.klassen @ deri.org
  class LabelMapping
    include NameSpaces
        
    def initialize(output_dir,file=false,debug=true)
      @file = file
      @outfile = File.join(output_dir,"label_mapping.nq")
      @log = Logger.new(STDOUT) if debug
      load_namespaces
    end
    
    ## 
    # Download the database file meddra_adverse_effects
    # uncompress the file once downloaded
    # @param [String] the path of the download folder
    # @return [String] location of the downloaded file
    def download(download_dir)
      @downloaded_file = File.join(download_dir,"label_mapping.tsv.gz")
      
      @log.info "Downloading from SIDER to #{@downloaded_file}" if @log
      system("curl -o #{@downloaded_file} -i  ftp://sideeffects.embl.de/SIDER/latest/label_mapping.tsv.gz")
      system("gunzip #{@downloaded_file}")
      
      @file = File.join(download_dir,"label_mapping.tsv")
    end
    
    ##
    # process the database file into RDF
    def process
      @log.info "processing label mapping to #{@outfile}" if @log
      time = File.mtime(@file)
     begin 
      RDF::Writer.open(@outfile) do |writer|
    	
    	record = RDF::URI.new("http://bio2rdf.org/sider")
        
        File.open(@file,"r").each do |line|
          row = line.strip.chomp.split("\t") rescue next
          label = @sider["label_"+Digest::MD5.hexdigest(row[-1])] 
          
          writer << [label,DC.identifier,row[-1],record]
          writer << [label,RDF.type,@sider_vocabulary['Drug_Information_Label'],record]
          
          case row[1]
          when ""
            drug = @pubchem[row[3].to_i.abs.to_s]
            writer << [label,RDFS.comment,"Label mapping for: #{CGI.escape(row[0])}",record]
            writer << [label,@sider_vocabulary['involves_drug'],drug,record]
            writer << [drug,RDF.type,@sider_vocabulary['Drug'],record]
            writer << [drug,DC.identifier,"cid:"+row[3].to_i.abs.to_s,record]
            writer << [drug,RDFS.label,CGI.escape(row[0]),record]
          when "combination"
            writer << [label,RDFS.label,"Label mapping for combination: #{row[0]}",record]
            writer << [label,@sider_vocabulary['marker'],row[1],record]
            #writer << [label,RDF.type,@sider_vocabulary['Combination'],record]
           row[0].split(";").each {|drug| writer << [label,@sider_vocabulary['drug'],drug,record]}
          when "not found"
            # not implemented
          when "mapping conflict"
            # not implemented
          when "template"
            # not implemented
          end
        end
      end
     rescue ArgumentError => e
        @log.error e.backtrace.join("\n")
     end
    end
  end
end
