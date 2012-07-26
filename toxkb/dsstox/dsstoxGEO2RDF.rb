require 'rubygems'
require 'rdf'
require 'optparse'
require 'ostruct'
require 'optparse'
require 'rdf/ntriples'
require 'digest/md5'
require 'logger'
require 'cgi'

include RDF
# =============================================================================================================
# Title :: DSSTox GEO accession Files
# Author :: Dana Klassen
# Description :: Parser for DSSTOX files that link to Gene Expression Omnibus 
# =============================================================================================================
class DssToxCarcPotencyDatabaseSD
 RECORD_END  = "$$$$"
 MOL_END     = "M  END"
 DATA_END    = "\n"
 DATA_HEADER = ">"
 
  def initialize(args)
    @arguments = args
    @options   = OpenStruct.new()
    @log       = Logger.new(STDOUT)
    @source    = "ftp://ftp.epa.gov/dsstoxftp/DSSTox_CurrentFiles/ARYEXP_DownloadFiles/ARYEXP_v2a_958_06Mar2009.zip"
    @dsstox    = RDF::Vocabulary.new("http://bio2rdf.org/dsstox_geo:")
    @dsstox_resource = RDF::Vocabulary.new("http://bio2rdf.org/dsstox_geo_resource:")
    @cas             = RDF::Vocabulary.new("http://bio2rdf.org/cas:")
  end
  
  # run the application
  def run
    begin
      process_arguments 
      check_arguments
      process_file()
      clean_up if @options.download
    rescue SystemExit  => bam
      puts bam.backtrace.join("\n")
    end

  end
  
  # process each file recursively
  def process_file
    # write everything to the file 
    RDF::Writer.open(@options.output) do |writer|
              record = []
              
              # shoudl make graph root be the dataset name with description of dataset 
              File.open(@options.file,"r").each_line do |line|
      
               record << line
               if(line.include?(RECORD_END))
                 graph       =  RDF::Graph.new()
                 record_uri  =  @dsstox["#{Digest::MD5.hexdigest(record.to_s)}"]
                 mol_end     =  record.index{ |i| i.include?(MOL_END)}
                 mol_file    =  record.take_while{|i| !i.include?(MOL_END)}
                 data_file   =  record.slice(mol_end+1..record.index{ |i| i.include?(RECORD_END)}-1)
      
                #  build rdf graph for the record
                mol = @dsstox[Digest::MD5.hexdigest(mol_file.to_s)]
      
                graph << [mol,RDF.type,@dsstox_resource.MolFile]
                graph << [mol,RDF.value,RDF::Literal.new("\"#{CGI.escape(mol_file.to_s)}\"")]
                graph << [record_uri,@dsstox_resource.hasMolFile,mol]
                graph << [record_uri,RDF.type,@dsstox_resource['DataRecord']]
       
                data_file.each_slice(3) do |entry|
      
                  data_header = entry[0].gsub(/[<>\s]/,"")
                  data = entry[1].strip.chomp
        
                  # Make identifier specific URI's for CAS and DSSTOX identifiers
                  if(data_header.include?("DSSTox_CID") || data_header.include?("DSSTox_Generic_SID"))
                      subject = @dsstox[data]
                      graph << [subject,RDF.type,@dsstox_resource[data_header]]
                      graph << [subject,RDF.value,RDF::Literal.new("#{data}")]
                      graph << [record_uri,@dsstox_resource["has#{data_header}"],subject]

                  # build URI's for chemicals identified using CAS registry numbers
                  elsif(data_header.include?("CASRN") && !data.include?("NOCAS"))
                      subject = @cas[data]
                      graph << [subject,RDF.type,@dsstox_resource[data_header]]
                      graph << [subject,RDF.value,RDF::Literal.new("\"#{data}\"")]
                      graph << [record_uri,@dsstox_resource["has#{data_header}"],subject]
                 
                  # prepare unique uri's for each experimental accession number to GEO
                  elsif(data_header.include?("Experiment_Accession"))
                    data.split(";").each do |geo_accession|
                      subject = RDF::URI.new("http://bio2rdf.org/geo:#{geo_accession.strip.chomp}")
                      graph << [subject, RDF.type, @dsstox_resource[data_header]]
                      graph << [subject,RDF.value,RDF::Literal.new("\"#{data}\"")]
                      graph << [record_uri,@dsstox_resource["has#{data_header}"], subject]
                    end
                  # experimental_url's to geo database

                  elsif(data_header.include?("Experiment_URL") && !data.include?("blank"))
                    data.split(";").each do |geo_url|
                      subject = RDF::URI.new(geo_url.to_s)
                      graph << [subject, RDF.type, @dsstox_resource[data_header]]
                      graph << [record_uri,@dsstox_resource["has#{data_header}"],subject]
                    end

                  # foreach generic data field do the following.
                  else
                    if(!data.include?("blank"))
                      subject = @dsstox[Digest::MD5.hexdigest(record_uri.to_s + data_header + data )]
                      graph << [subject,RDF.type,@dsstox_resource[data_header]]
                      graph << [subject,RDF.value,RDF::Literal.new("#{data}")]
                      graph << [record_uri,@dsstox_resource["has#{data_header}"],subject]
                    end
                  end
    
                 
                end
               
                  graph.each_statement do |statement|
                         writer << statement
                  end    
                  
                  record.clear
               end
            
          end #file open do line        
      end # writer end line
  end # process_file end
  
  
  # process the incoming arguments using default RUBY library
  def process_arguments()
    opts_parse = OptionParser.new do |opts|
        
        # sets the local file to be used. we will only be parsing the SDF files.
        opts.on('-f','--file FILE','use the following local file') {|f| @options.file = f}
        
        # sets the output file of the parser. default is same directory
        opts.on('-o','--output FILE','store the output in the following file.') do |f|
              @options.output = f 
        end 
        
        # sets the download flag to true if the flag was set
        opts.on('-d','--download','download the file from the dsstox servers') do
          @options.download = true
        end
       
        # prints the help          
        opts.on('-h','--help') do 
             puts opts
             exit! 
        end

    end

    opts_parse.parse!(@arguments) rescue return false
    true
  end

  # check the arguments 
  def check_arguments
  
    begin
      
      if(@options.output)
        # check the output can specify a file.
        @log.info "Output the file to: #{@options.output}"
       # @options.output = File.new(@options.output,"w+")
      else
        @log.error "Did not specify output file using --output"
        exit!
      end
      
      if(@options.file)
        @log.info "Reading: #{@options.file}"
        raise LoadError,"The file doesn't exist (download first)" if (!File.exists?(@options.file))
      elsif(!@options.file && @options.download)
        @log.info "Downloading file from #{@source}"
        system("curl -o ~/Downloads/dsstox_geo.zip #{@source}")
        system("mkdir ~/Downloads/dsstox_geo/")
        system("unzip ~/Downloads/dsstox_geo.zip -d ~/Downloads/dsstox_geo/")
        @options.file = File.expand_path("~/Downloads/dsstox_geo/ARYEXP_v2a_958_06Mar2009.sdf")
      end
      
    rescue Exception => bam
      puts bam
      exit!
    end
    
    return true
  end

  # remove downloaded files
  def clean_up
    system("rm -rf ~/Downloads/dsstox_geo ~/Downloads/dsstox_geo.zip")
  end
    
end

DssToxCarcPotencyDatabaseSD.new(ARGV).run
