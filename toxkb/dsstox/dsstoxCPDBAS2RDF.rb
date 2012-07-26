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
# Title :: DSSTox Carcinogenicity Potency Database Summary Tables - All Species (CPDBAS)
# Author :: Dana Klassen
# Description :: Parser for DSSTOX files that link to the Carcinogenicity Potency Database
# =============================================================================================================
class DssToxCarcPotencyDatabaseSD
 RECORD_END  = "$$$$"
 MOL_END     = "M  END"
 DATA_END    = "\n"
 DATA_HEADER = ">"
 
  def initialize(args)
    @arguments = args
    @options   = OpenStruct.new()
    @log = Logger.new(STDOUT)
    @source = "ftp://ftp.epa.gov/dsstoxftp/DSSTox_CurrentFiles/CPDBAS_DownloadFiles/CPDBAS_v5d_1547_20Nov2008.zip"
    @dsstox = RDF::Vocabulary.new("http://bio2rdf.org/dsstox:")
    @dsstox_resource = RDF::Vocabulary.new("http://bio2rdf.org/dsstox_resource:")
    @cas    = RDF::Vocabulary.new("http://bio2rdf.org/cas:")
  end
  
  # run the application
  def run
    begin
      process_arguments 
      check_arguments
      process_file()
    rescue SystemExit  => bam
      @log.error bam.backtrace.join("\n")
      exit!
    #rescue StandardError => bam
    #  puts "d"
    #  puts bam.backtrace.join("\n")
    end

  end
  
  # process each file recursively
  def process_file
    # write everything to the file 
    RDF::Writer.for(:ntriples).open(@options.output) do |writer|
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
                  elsif(data_header.include?("CASRN"))
                      subject = @cas[data]
                      graph << [subject,RDF.type,@dsstox_resource[data_header]]
                      graph << [subject,RDF.value,RDF::Literal.new("\"#{data}\"")]
                      graph << [record_uri,@dsstox_resource["has#{data_header}"],subject]
                  # split the endpoints into individual RDF triples.
                  elsif(data_header.include?("Endpoint") || data_header.include?("TargetSites"))
                    if(!data.include?("blank"))
                        data.split(";").each do |endpoint|
                          endpoint = endpoint.strip.chomp
                          subject = @dsstox[Digest::MD5.hexdigest(record_uri.to_s + data_header + endpoint)]
                          graph << [subject,RDF.type,@dsstox_resource[data_header]]
                          graph << [subject,RDFS.label,RDF::Literal.new("#{data_header} [source: #{@source}]") ]
                          graph << [subject,RDF.value,RDF::Literal.new("#{endpoint}")]
                          graph << [record_uri,@dsstox_resource["has#{data_header}"],subject]
                        end
                    end
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
        opts.on('-o','--output FILE','store the output in the following file.') {|f| @options.output = f }
  
        
        opts.on('-d','--download','download the files') {@options.download = true}
        # prints the help          
        opts.on('-h','--help') do 
             puts opts
             exit 
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
        @log.error "The file doesn't exist (download first)" if (!File.exists?(@options.file))
        #raise FormatError, "This file is not in the proper SDF format" if(!File.basename(@options.file).include?("sdf"))
        elsif(!@options.file && @options.download)
        @log.info "Downloading file from #{@source}"
          system("curl -o ~/Downloads/dsstox_cpdbas.zip #{@source} > /dev/null")
          system("mkdir ~/Downloads/dsstox_cpdbas/")
          system("unzip ~/Downloads/dsstox_cpdbas.zip -d ~/Downloads/dsstox_cpdbas/")

          @options.file =  Dir.glob(File.join(File.expand_path("~/Downloads/dsstox_cpdbas"),"*.sdf")).first
          @log.info "Processing :" + @options.file
      end
    
    rescue Exception => bam
      puts bam.backtrace.join("\n")
    end
    
    return true
  end

    
end

DssToxCarcPotencyDatabaseSD.new(ARGV).run
