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
        @toxcast             = RDF::Vocabulary.new("http://bio2rdf.org/toxcast:")
        @toxcast_resource    = RDF::Vocabulary.new("http://bio2rdf.org/toxcast_resource:")
        @toxcast_dictionary  = RDF::Vocabulary.new("http://bio2rdf.org/toxcast_dictionary:")
        @cebs                = RDF::Vocabulary.new("http://bio2rdf.org/cas:")
	end
    
	#run the application.
	def run
        @log = Logger.new(STDOUT)
        @log.info "Running Program."
		if (process_arguments && arguments_valid?)
            SimpleLogger.instance.log_setup(STDOUT)
            process_file(@options.file)
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
       
      RDF::Writer.open(@options.output) do |writer|
        
        dictionary = @toxcast_dictionary["toxcast_#{Digest::MD5.hexdigest(File.open(file,"r").read)}"]
        
        writer << [dictionary,RDF.type, @toxcast_dictionary.Dictionary]
        
  			assay_file.each_line do |line|
            
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
            opts.on('-h','--help') 	   {puts "The commands are: \n-v=run verbosely.\n-h=output this message, congrats on figuring it out \n-d=set the log file level to debug\n-f=the path of the file you want to be parsed\n-o=where do you want to place the output?\nl=if you want to change the debug to include errors and warnings."; exit}
            opts.on('-f','--file FILE'){|f| @options.file=File.expand_path(f)}
            opts.on('-o','--output PATH'){|path| @options.output=File.expand_path(path)}
    end
        opts_parse.parse!(@arguments) rescue return false
        true
	end
    
    
	#check if the arguments are valid.
	#not doing much good at the moment
	def arguments_valid?
        #things to do check file, add further
        begin
            raise LoadError,"The file you specified doesn't exist: #{@options.file}" if (File.exist?(@options.file) == false)
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
