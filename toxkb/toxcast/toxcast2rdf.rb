#! /usr/bin/ruby
require 'rubygems'
require 'ostruct'
require 'optparse'
require 'logger'
require 'singleton'
require 'rdf'
require 'rdf/ntriples'
require 'digest/md5'
include RDF

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
        @arguments = args
        @options = OpenStruct.new(:file=>"",:output=>"")
        @log     = Logger.new(@options.logfile)
        
        @toxcast = RDF::Vocabulary.new("http://bio2rdf.org/toxcast:")
        @toxcast_resource = RDF::Vocabulary.new("http://bio2rdf.org/toxcast_resource:")
        @cas             = RDF::Vocabulary.new("http://bio2rdf.org/cas:")
        @dsstox           = RDF::Vocabulary.new("http://bio2rdf.org/dsstox:")
        
	end
    
	#run the application.
	def run
        @log = Logger.new(STDOUT)
        @log.info "Running Program."
		if (process_arguments && arguments_valid?)
            SimpleLogger.instance.log_setup(STDOUT)
             
            if(File.exists?(@options.file))
              process_file(@options.file)
             elsif(!File.exists?(@options.file) && File.directory?(@options.dir))
             
              @log.info("Reading directory:#{@options.dir}/")
              
              Dir["#{File.expand_path(@options.dir)}/*.txt"].each do |file|
                @log.info("\tparsing file:#{file}")
                
                  @log.info "Saving to: " + @options.output
                  process_file(file)
                end
            end
            
        else
            STDERR.puts "Unable to run program:"
		end
	end
  
  def process_file(file)
  
			# set up the output stream.
			assay_file = File.open(file,"r")
			header = false

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
                index= index+3
                x = @toxcast["toxcast_#{Digest::MD5.hexdigest(line.to_s+line[index] +header[index])}"]
                writer << [data_row,@toxcast_resource.hasEntity,x]
                writer << [x,RDF.type,@toxcast_resource[header[index]]]
                writer << [x,RDF.value,line[index]]
            end
          end
        end
  end
  
	def process_arguments()
		opts_parse = OptionParser.new do |opts|
            opts.on('-v', '--verbose') {@options.verbose=true}
            opts.on('-h','--help') 	   
            opts.on('-f','--file FILE',"parse an individual file"){|f| @options.file=File.expand_path(f)}
            opts.on('-o','--output PATH',"set the output PATH file names will be determined from source file"){|path| @options.output=File.expand_path(path)}
            opts.on('-d','--dir DIR',"parse and entire directory"){|dir| @options.dir = File.expand_path(dir)}
            opts.on('-h','--help') do 
              puts opts
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
        begin
            raise LoadError,"The file you specified doesn't exist: #{@options.file}" if (File.exist?(@options.file) == false && @options.dir == nil)
            #raise DirectoryError,"The directory you specified as the outpath doesn't exist: #{@options.outpath}" if File.directory?(@options.outpath) == false
            rescue LoadError => bam
            puts bam
            exit
            resuce DirectoryError => boom
            puts boom
            # puts "creating directory and giving it another try (okay not yet implemented"
            exit
        end
        
        return true
    end
end

class DirectoryError < LoadError; end
application = AppCommand.new(ARGV)
application.run
