$:.unshift(File.dirname(__FILE__))

require 'rubygems'
require 'rdf'
require 'rdf/nquads'
require 'namespaces'
require 'logger'

include RDF

module Sider2Rdf
  
  ##
  # Class to convert the SIDER database file medra_adverse_effects to RDF
  # @author:: Dana Klassen
  # @contact: dana.klassen @ deri.org
  class MeddraAdverseEffects
    include NameSpaces
    
    @@medra_vocab = {"LLT" => "Lowest_Level_Term", "PT" =>"Preferred_term"}
    
    def initialize(output_dir,file=false,debug=true)
      @umls = RDF::Vocabulary.new("http://bio2rdf.org/umls:")
      @file = file
      @outfile = File.join(File.expand_path(output_dir),"meddra_adverse_effects.nq")
      @log = Logger.new(STDOUT) if debug
      load_namespaces
    end

    ## 
    # Download the database file meddra_adverse_effects
    # uncompress the file once downloaded
    # @param [String] the path of the download folder
    # @return [String] location of the downloaded file
    def download(download_dir)
      @downloaded_file = File.join(download_dir,"meddra_adverse_effects.tsv.gz")
      
      @log.info "Downloading from SIDER to #{@downloaded_file}" if @log
      system("curl -o #{@downloaded_file} -i  ftp://sideeffects.embl.de/SIDER/latest/meddra_adverse_effects.tsv.gz")
      system("gunzip #{@downloaded_file}")
      
      @file = File.join(download_dir,"meddra_adverse_effects.tsv")
    end
    
    ##
    # process the database file into RDF
    def process 
      return false if @file == false
		
		time = File.mtime(@file)

		RDF::NQuads::Writer.open(@outfile) do |writer|
		
		record = RDF::URI.new("http://bio2rdf.org/sider")

        File.open(@file,"r").each do |line|
          row = line.strip.chomp.split("\t")
          
          # convert the STICH id to pubchem (see NOTES)
          pubchem = @pubchem_compound[row[1].to_i.abs.to_s]
          writer << [pubchem,RDF.type,@sider_vocabulary['Drug'],record]
          writer << [pubchem,DC.title,row[3],record]
          writer << [pubchem,DC.identifier,"pubchem:#{row[1].to_i.abs.to_s}",record]
          
          # these identifiers should in the future be linked 
          # with proper ontology URIS retrieved from NCBO bioportal.
          side_effect = @umls[row[2]]
          writer << [side_effect,RDF.type,@sider_vocabulary['Side_Effect'],record]
          writer << [side_effect,DC.identifier,"ulms:#{row[2]}",record]
          writer << [side_effect,DC.title,row[4],record]
          writer << [pubchem,@sider_vocabulary['side_effect'],side_effect,record]
        end
      end
    end
  end
end
