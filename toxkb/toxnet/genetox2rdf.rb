require 'rubygems'
require 'rdf'
require 'rdf/ntriples'
require 'nokogiri'
require 'digest/md5'
require 'cgi'
require 'ostruct'
require 'optparse'
require 'logger'

include RDF

class GeneToxDatabase 
    
    def initialize(args)
        @arguments = args
        @options = OpenStruct.new()
        @log  = Logger.new(STDOUT)
        
    end
    
    def run
        @log.info "Running program"
            
        if(process_arguments && valid_arguments?)
           @log.info "Processing: \n\t#{@options.file}"
           @log.info "Output to : \n \t\t #{@options.output}"
           @reader           = Nokogiri::XML::Reader(File.open(@options.file))
           @output           = File.new(@options.output,"w+")
           @genetox          = RDF::Vocabulary.new("http://bio2rdf.org/genetox:")
           @genetox_resource = RDF::Vocabulary.new("http://bio2rdf.org/genetox_resource:")
            @cas             = RDF::Vocabulary.new("http://bio2rdf.org/cas:")
           parse()
           
        end
    end
           
    # Process the arguments on the command line
    def process_arguments
        opts_parse = OptionParser.new do |opts|
           opts.on('-f','--file FILE')  {|f|@options.file   = File.expand_path(f)}
           opts.on('-o','--output FILE'){|f|@options.output = File.expand_path(f)}
        end
           
        opts_parse.parse!(@arguments) rescue return false
           
        return true
    end
    
    # check the arguments to make sure they are valid.
    def valid_arguments?
        begin
           if(@options.file)
               raise LoadError,"The file you specified doesn't exist: #{@options.file}" if File.exist?(@options.file) == false
           else
            @log.error "Select a file using -f or --file FILE"
           end
           
           if(@options.output)
               # not going to worry about this one.
           else
            @log.error "No output was specified select using -o or --output"
           end
        rescue LoadError => bam
           @log.error bam
           exit
        end
           
        return true
    end
    
    def parse()
        
        @graph = RDF::Graph.new()
        
        @reader.each do |node|
            
            
            if(node.name.include?("genetox")&& node.node_type == 1)
                node.read
                node.read
            end
            
            if(node.name.include?("DOC") && node.node_type ==1 )
                @doc = @genetox["genetox_#{Digest::MD5.hexdigest(node.inner_xml)}"]
                @graph << [@doc, RDF.type, @genetox_resource.DOC]
            end
            
            if(node.name.include?("DOCNO") && node.node_type == 1)
                node.read
                
                docno = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                @graph << [docno,RDF.type,@genetox_resource.DOCNO]
                @graph << [docno,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                @graph << [@doc,@genetox_resource.hasDOCNO,docno]
                
                elsif(node.name == "date" && node.node_type == 1)
                node.read
                
                date = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                @graph << [date,RDF.type,@genetox_resource.Date]
                @graph << [date,RDF.value,RDF::Literal.new("\"#{node.value}\"",:datatype => RDF::XSD.date)]
                @graph << [@doc,@genetox_resource.hasDate,date]
                
                elsif(node.name == "DateRevised" && node.node_type == 1)
                node.read
                
                date_revised = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                @graph << [@doc,@genetox_resource.hasDateRevised,date_revised]
                @graph << [date_revised,RDF.type,@genetox_resource.DateRevised]
                @graph << [date_revised,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                
                elsif(node.name == "NameOfSubstance" && node.node_type == 1)
                node.read
                
                substance_name = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                @graph << [substance_name,RDF.type,@genetox_resource.NameOfSubstance]
                @graph << [substance_name,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                @graph << [@doc,@genetox_resource.hasNameOfSubstance,substance_name]
                
                elsif(node.name == "CASRegistryNumber" && node.node_type == 1)
                
                node.read
                
                # because we know the CAS identifier has its own namespace we created the URI for it.
                if(!node.value.include?("NO CAS RN"))
                    cas     = @cas[node.value]
                    @graph << [cas,RDF.type,@genetox_resource.CASRegistryNumber]
                    @graph << [@doc,@genetox_resource.hasCASRegistryNumber,cas]
                end
                
                elsif(node.name == "ccat" && node.node_type == 1)
                
                node.read
                
                ccat    = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                @graph << [ccat,RDF.type,@genetox_resource.Ccat]
                @graph << [ccat,RDF.value,"\"#{CGI.escape(node.value)}\""]
                @graph << [@doc,@genetox_resource.hasCcat,ccat]
                
                
                elsif(node.name == "tax" && node.node_type == 1)
                node.read
                tax = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                @graph << [tax,RDF.type,@genetox_resource.Tax]
                @graph << [tax,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@genetox_resource.hasTax,tax]
                
                elsif(node.name.include?("gen")  && node.node_type == 1)
                
                gen = @genetox["genetox_#{Digest::MD5.hexdigest(node.inner_xml + @doc.to_s)}"]
                @graph << [gen,RDF.type,@genetox_resource.Gen]
                @graph << [@doc,@genetox_resource.hasGen,gen]
                
                # handle specta
                until(node.name.include?("gen") && node.node_type != 1) 
                    
                    node.read
                    
                    if(node.name.include?("spct") && node.node_type == 1)
                        node.read
                        
                        spct = @genetox["species_#{Digest::MD5.hexdigest(node.value + gen.to_s)}"]
                        @graph << [spct,RDF.type,@genetox_resource['Spct']]
                        @graph << [spct,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        @graph << [gen,@genetox_resource['hasSpecies'],spct]
                        
                        elsif(node.name.include?("ast") && node.node_type == 1)
                        
                        node.read
                        ast = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + gen.to_s)}"]
                        
                        @graph << [ast,RDF.type,@genetox_resource['Ast']]
                        @graph << [ast,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        @graph << [gen,@genetox_resource['hasAst'],ast]
                        
                        elsif(node.name.include?("asc") && node.node_type == 1)
                        
                        node.read
                        asc = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + gen.to_s)}"]
                        
                        @graph << [asc,RDF.type,@genetox_resource['Asc']]
                        @graph << [asc,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        @graph << [gen,@genetox_resource['hasAsc'],asc]
                        
                        elsif(node.name.include?("rpt") && node.node_type == 1)
                        
                        node.read
                        rpt= @genetox["genetox_#{Digest::MD5.hexdigest(node.value + gen.to_s)}"]
                        
                        @graph << [rpt,RDF.type,@genetox_resource['Rpt']]
                        @graph << [rpt,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        @graph << [gen,@genetox_resource['hasRpt'],rpt]                            
                        elsif(node.name.include?("ref") && node.node_type == 1)
                        
                        node.read
                        ref = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + gen.to_s)}"]
                        
                        @graph << [ref,RDF.type,@genetox_resource['Ref']]
                        @graph << [ref,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        @graph << [gen,@genetox_resource['hasRef'],ref]
                        
                        elsif(node.name.include?("res") && node.node_type == 1)
                        
                        node.read
                        res = @genetox["genetox_#{Digest::MD5.hexdigest(node.value + gen.to_s)}"]
                        @graph << [res,RDF.type,@genetox_resource['Res']]
                        @graph << [res,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        @graph << [gen,@genetox_resource['hasRes'],res]
                    end
                    
                end
                
                elsif(node.name == "DOC" && node.node_type != 1)
                    @graph.each_statement do |statement|
                        @output.puts statement.to_s
                    end
                @graph.clear
            end
            
        end
    end
end

GeneToxDatabase.new(ARGV).run
