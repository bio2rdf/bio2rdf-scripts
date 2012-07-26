#! /usr/bin/ruby
require 'rubygems'
require 'ostruct'
require 'optparse'
require 'logger'
require 'singleton'
require 'rdf'
require 'rdf/ntriples'
require 'digest/md5'
require 'cgi'
require 'iconv'
include RDF

#=====================================================================================================
# Title :: Toxcast2RDFD
# Author :: Dana Klassen
# Description :: Parser to convert the Toxcast database(http://www.epa.gov/ncct/toxcast/)
#=====================================================================================================

class SimpleLogger
    include Singleton
    
    attr_accessor:log
    
    ERROR = 1
    WARNING = 2
    INFO = 3
    
    def log_setup(file = "log.txt",level = SimpleLogger::INFO)
        @log = Logger.new(file)
        
        case level
            when SimpleLogger::ERROR
            @log.info "setting log level to error."
            @log.level = Logger::ERROR
            when SimpleLogger::INFO
            @log.info "setting log level to info."
            @log.level = Logger::INFO
            when SimpleLogger::WARNING
            @log.info "setting log level to warning."
            @log.level = Logger::WARNING
        end
    end
    
    def error(msg)
        @log.error msg
    end
    
    def info(msg)
        @log.info msg
    end
    
    def warning(msg)
        @log.warning(msg)
    end
    
end
#Description:: Program to convert RDF to various formats.

class AppCommand
		
	def initialize(args)
        @arguments        = args
        @options          = OpenStruct.new(:file=>"",:output=>"")
        @log              = Logger.new(@options.logfile)
        @source           = "http://www.epa.gov/ncct/toxcast/files/ToxCast_20110110.zip"
        
        # core namespaces for toxcast
        @toxcast          = RDF::Vocabulary.new("http://bio2rdf.org/toxcast:")
        @toxcast_resource = RDF::Vocabulary.new("http://bio2rdf.org/toxcast_resource:")
        @toxcast_dictionary  = RDF::Vocabulary.new("http://bio2rdf.org/toxcast_vocabulary:")

        # additional vocabularies to be used
        @cas              = RDF::Vocabulary.new("http://bio2rdf.org/cas:")
        @dsstox           = RDF::Vocabulary.new("http://bio2rdf.org/dsstox:")

        # defaults for options
        @options.output   = false
        @options.file     = false
        @options.download = false
        @options.dir      = false
        
	end
    
	#run the application.
	def run
        @log = Logger.new(STDOUT)
        @log.info "Running Program."
		  if (process_arguments && arguments_valid?)
            SimpleLogger.instance.log_setup(STDOUT)
             
            if(@options.file && File.exists?(@options.file))
              process_file(@options.file)
             elsif(!@options.file && File.directory?(@options.dir))
             
              @log.info("Reading directory:#{@options.dir}/")
              
              Dir["#{File.expand_path(@options.dir)}/*.txt"].each do |file|
                if file.include? "ToxCast_Phase_1_Assays_20110110.txt"
                  @log.info "processing dictionary file"
                  process_dictionary(file)
                else
                  @log.info("parsing file:#{file}")
                  process_file(file)
                end
               end
            end
        else
            @log.error "Unable to run program:"
            exit!
		end
	end
  
  def process_file(file)
  
			# set up the output stream.
      assay_file = File.open(file,"r")
      header     = false

			# skip the comments section and grab the header
			while(!header)
  				line   = assay_file.readline
  				header = line.strip.chomp.split("\t") if !line.match(/^#/)
			end
       
      RDF::Writer.open("#{@options.output}\/"+ File.basename(file,".txt") + ".nt") do |writer|
  			data_file  = @toxcast["toxcast_#{Digest::MD5.hexdigest(File.open(file,"r").read)}"]
  		
  			# generate unique URI for the document itself. 
        writer << [data_file,RDF.type, @toxcast_resource.DataDocument]
        
  			assay_file.each_line do |line|        

   	  	  line = line.strip.chomp.split("\t")
    	    data_row   = @toxcast["toxcast_#{Digest::MD5.hexdigest(data_file.to_s + line.to_s)}"]
    	      
   	      writer << [data_row, RDF.type, @toxcast_resource.DataRow]
          writer << [data_file,@toxcast_resource.hasDataRow,data_row]
          
            # Unique chemical identifier use for referencing chemicals in DSSTOX  datasets
    	      source_name_sid = @dsstox[line[0]]
    	      # Unique chemical identifier used for references chemicals in the Chemical Abstract Service (CAS) database
   	        cas             = @cas[line[1]]
   	        # none unique label for chemcial
   	        name            = @toxcast["toxcast_#{Digest::MD5.hexdigest(line[2])}"]
   	        
   	        writer << [source_name_sid, RDF.type,@toxcast_resource['SOURCE_NAME_SID']]
            writer << [data_row,@toxcast_resource.hasEntity,source_name_sid]
    
   	        writer << [cas,RDF.type,@toxcast_resource.CASRegistryNumber]
            writer << [data_row,@toxcast_resource.hasEntity,cas]
    
   		      writer << [name,RDF.type,@toxcast_resource.ChemicalName]
   		      writer << [data_row,@toxcast_resource.hasEntity,name]
            writer << [name,RDFS.label,line[2]]
    
            line[3..line.length].each_index do |index|
                index = index+3
                x     = @toxcast["toxcast_#{Digest::MD5.hexdigest(line.to_s+line[index] +header[index])}"]
                writer << [data_row,@toxcast_resource.hasEntity,x]
                writer << [x,RDF.type,@toxcast_resource[header[index]]]
                writer << [x,RDF.value,line[index]]
            end
          end
        end
  end
  
   def process_dictionary(file)
  
      # set up the output stream.
      assay_file = File.open(file,"r:ascii-8bit")
      header = false

      # skip the comments section and grab the header
      while(!header)
          line   = assay_file.readline
          header = line.strip.chomp.split("\t") if !line.match(/^#/)
      end
       
      RDF::Writer.open("#{@options.output}\/"+ File.basename(file,".txt") + ".nt") do |writer|
        
        dictionary = @toxcast_dictionary["toxcast_#{Digest::MD5.hexdigest(File.open(file,"r").read)}"]
        
        writer << [dictionary,RDF.type, @toxcast_dictionary.Dictionary]
        
        assay_file.each_line do |line|
          ic = Iconv.new("UTF-8//IGNORE","UTF-8")
          line = ic.iconv(line)
           line = line.strip.chomp.split("\t")
            

            # everything is in the file is a description of the assay component
            ac = @toxcast_resource[line[0]]
            writer << [ac,RDF.type,@toxcast_dictionary.ASSAY_COMPONENT]
            writer << [ac,RDFS.label,RDF::Literal.new("#{line[0]}")]
            writer << [dictionary,@toxcast_resource.hasTerm,@toxcast_dictionary.ASSAY_COMPONENT]
            
         line[1..line.length].each_index do |index|
            index= index+1
            attribute = @toxcast["toxcast_#{Digest::MD5.hexdigest( ac.to_s + line[index])}"]
            writer << [ac,@toxcast_resource["has#{header[index]}"],attribute]
            writer << [attribute,RDF.type,@toxcast_dictionary[header[index]]]
            writer << [attribute,RDFS.label,RDF::Literal.new("#{CGI.escape(line[index])}")]
         end   
              
        end # assay_file each line end
      end # RDF writer end
  end

	def process_arguments()
		opts_parse = OptionParser.new do |opts|
            opts.on('-v', '--verbose') {@options.verbose=true}
            opts.on('-h','--help') 	   
            opts.on('-f','--file FILE',"parse an individual file") {|f| @options.file=File.expand_path(f)}
            opts.on('-o','--output PATH',"set the output PATH file names will be determined from source file"){|path| @options.output = File.expand_path(path)}
            opts.on('-l','--dir DIR',"parse and entire directory") {|dir| @options.dir = File.expand_path(dir)}
            opts.on('-d','--download',"download the files from the remote server"){ @options.download = true }
            opts.on('-h','--help') do 
              @log.info opts
              exit!
            end   
    end
        opts_parse.parse!(@arguments) rescue return false
        true
	end   
    
	#check if the arguments are valid.
	#not doing much good at the moment
	def arguments_valid?
        #things to do check file, add further
        if(!@options.file && !@options.download)
          @log.error "you need to specify either a file using -f or to download the files using -d"
          exit!
        end

        if(@options.file && !@options.dir && !@options.download)
          @log.error "You need to specific the directory to parse -l or to download the files -d"
          exit!
        elsif(@options.file && File.exist?(@options.file) == false && !@options.download && !@options.dir)
          @log.error "The file you specified doesn't exist: #{@options.file}" 
          exit!
        end

        if(!@options.output)
          @log.error "You need to specify an output directory using -o PATH"
          exit!
        end
        
        if(!File.exists?(@options.output))
          @log.info "No directory found generating directory: #{@options.output}"
          Dir.mkdir(File.expand_path(@options.output))
        end

        if(@options.download)
          @log.info "Downloading files from #{@source} to ~/Downloads"
          system("curl -o ~/Downloads/toxcast.zip #{@source} > /dev/null")
          system("mkdir ~/Downloads/toxcast")
          system("unzip ~/Downloads/toxcast.zip -d ~/Downloads/toxcast/")
          @options.dir = File.expand_path("~/Downloads/toxcast")
        end   
        
        return true
    end
end

class DirectoryError < LoadError; end
application = AppCommand.new(ARGV)
application.run
