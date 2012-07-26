$:.unshift(File.dirname(__FILE__))

require 'rubygems'
require 'rdf'
require 'rdf/ntriples'
require 'namespaces'
require 'logger'

include RDF

module Sider2Rdf
  
  ##
  # Class to convert the SIDER database file meddra_freq_parsed to RDF
  # @author:: Dana Klassen
  # @contact: dana.klassen @ deri.org
  class MeddraFreqParsed
    include NameSpaces
    
    def initialize(output_dir,file=false,debug=true)
      @file = file
      @outfile = File.join(output_dir,"meddra_freq_parsed.nq")
      @log = Logger.new(STDOUT) if debug
      load_namespaces
    end

    ## 
    # Download the database file meddra_adverse_effects
    # uncompress the file once downloaded
    # @param [String] the path of the download folder
    # @return [String] location of the downloaded file
    def download(download_dir)
      @downloaded_file = File.join(download_dir,"meddra_freq_parsed.tsv.gz")
      
      @log.info "Downloading from SIDER to #{@downloaded_file}" if @log
      system("curl -o #{@downloaded_file} -i  ftp://sideeffects.embl.de/SIDER/latest/meddra_freq_parsed.tsv.gz")
      system("gunzip #{@downloaded_file}")
      
      @file = File.join(download_dir,"meddra_freq_parsed.tsv")
    end
    
    ##
    # process the database file into RDF
    def process
      
      host = "http://bio2rdf.org"
      sider = "http://hcls.sindice.com/sider:"
      sider_v = "http://hcls.sindice.com/sider_vocabulary:"
      
      type = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#type> "
      label= "<http://www.w3.org/2000/01/rdf-schema#label> "
      dcid = "<http://purl.org/dc/elements/1.1/identifier> "
      title = "<http://purl.org/dc/elements/1.1/title> "
      comment = "<http://www.w3.org/2000/01/rdf-schema#comment> "
      value = "<http://www.w3.org/1999/02/22-rdf-syntax-ns#value> "
      named_graph = "<http://hcls.sindice.com/sider> " 
      eol = ".\n"
      
      File.open(@outfile,"w+") do |writer|
	     record = "<http://bio2rdf.org/sider> "

        File.open(@file,"r").each do |line|
          row = line.strip.chomp.split("\t")
          
          buf =""
          
          l = "<#{sider}label_#{Digest::MD5.hexdigest(row[2])}> "
          drug  = "<#{host}/pubchem:#{row[1].to_i.abs.to_s}> "
          
          buf << l+type+"<#{sider_v}Drug_Information_Label> " + record + eol
          buf << l+"<#{sider_v}involves_drug> " + drug + record + eol
          
          # frequency
          freq = "<#{sider}freq_#{Digest::MD5.hexdigest(row.join)}> "
          ub = "<#{sider}ub_#{Digest::MD5.hexdigest(row.join)}> "
          lb = "<#{sider}lb_#{Digest::MD5.hexdigest(row.join)}> "
          
          buf << freq + type + "<#{sider_v}Frequency> " + record + eol
          buf << freq + label + "\"#{row[6]}\" " + record + eol
          buf << freq + comment + "\"#{row[5]}\" " + record + eol unless row[5] == ""
          buf << ub + type + "<#{sider_v}Upper_Bound> " + record + eol
          buf << ub + value + "\"#{row[8]}\" " + record + eol
          buf << freq + "<#{sider_v}upper_bound> " + ub + record + eol
          buf << lb + type + "<#{sider_v}Lower_Bound> " + record + eol
          buf << lb + value + "\"#{row[7]}\" " + record + eol
          buf << freq + "<#{sider_v}lower_bound> " + lb + record + eol
          buf << l + "<#{sider_v}frequency> " + freq + record + eol

          se = "<#{host}/umls:#{row[3]}> "
          buf << l+"<#{sider_v}identifies_side_effect> " + se + record + eol
          buf << se+type+"<#{sider_v}Side_Effect> " + record + eol
          
          writer << buf 
        end
      end
    end
  end
end
