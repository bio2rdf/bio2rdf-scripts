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

class Archival 
    
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
            @archival          = RDF::Vocabulary.new("http://bio2rdf.org/archival:")
            @archival_resource = RDF::Vocabulary.new("http://bio2rdf.org/archival_resource:")
            @cas               = RDF::Vocabulary.new("http://bio2rdf.org/cas:")
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
            
            if(node.name.include?("archival")&& node.node_type == 1)
                node.read
                node.read
            end
            
            if(node.name.include?("DOC") && node.node_type ==1 )
                @doc = @archival["archival_#{Digest::MD5.hexdigest(node.inner_xml)}"]
                @graph << [@doc, RDF.type, @archival_resource.DOC]
            end
            
            if(node.name == "DOCNO" && node.node_type == 1)
                
                elsif(node.name == "ArticleTitle" && node.node_type == 1)
                        node.read
                        
                        r = @archival["archival_#{Digest::MD5.hexdigest("ArticleTitle" + node.value + @doc.to_s)}"]
                        @graph << [r,RDF.type,@archival_resource.ArticleTitle]
                        @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        @graph << [@doc,@archival_resource.hasArticleTitle,r]
                
                elsif(node.name == "AbstractTitle" && node.node_type == 1)
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("AbstractTitle" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.AbstractTitle]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasAbstractTitle,r]
                
                elsif(node.name == "Affiliation" && node.node_type == 1)
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("Affiliation" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.Affiliation]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasAffiliation,r]
                
                elsif(node.name == "Author" && node.node_type == 1)
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("Author" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.Author]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasAuthor,r]
                
                elsif(node.name == "CASRegistryNumber" && node.node_type == 1)
                
                    node.read
                    
                    cas  = /\d{3,5}-\d{2,4}-\d{1,2}/.match(node.value)
                    
                    if(cas != nil)
                      r = @cas[cas]
                      @graph << [r,RDF.type,@archival_resource.CASRegistryNumber]
                      @graph << [r,RDF.value,RDF::Literal.new("\"#{cas}\"")]
                      @graph << [@doc,@archival_resource.hasCASRegistryNumber,r]
                    end
                
                elsif(node.name == "Coden" && node.node_type == 1)
                
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("Coden" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.Coden]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasCoden,r]
                
                elsif(node.name == "Keyword" && node.node_type == 1)
                
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("Keyword" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.Keyword]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasKeyword,r]
                
                elsif(node.name == "Language" && node.node_type == 1)
                    
                    node.read
                    if(node.value !=nil)
                      r = @archival["archival_#{Digest::MD5.hexdigest("Language" + node.value + @doc.to_s)}"]
                      @graph << [r,RDF.type,@archival_resource.Language]
                      @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                      @graph << [@doc,@archival_resource.hasLanguage,r]
                    end
                elsif(node.name == "PestabPubCode" && node.node_type == 1)
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("PestabPubCode" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.PestabPubCode]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasPestabPubCode,r]
                
                elsif(node.name == "PublicationType" && node.node_type == 1)
                
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("PublicationType" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.PublicationType]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasPublicationType,r]
                
                elsif(node.name == "SecondarySourceID" && node.node_type == 1)
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("SeconardarySourceID" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.SecondarySourceID]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasSecondarySourceID,r]
                
                elsif(node.name == "Source" && node.node_type == 1)
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("Source" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.Source]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasSource,r]
                
                elsif(node.name == "SponsoringAgency" && node.node_type == 1)
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("SponsoringAgency" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.SponsoringAgency]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasSponsoringAgency,r]
                
                elsif(node.name == "EntryMonth" && node.node_type == 1)
                    node.read
                    
                    r = @archival["archival_#{Digest::MD5.hexdigest("EntryMonth" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.EntryMonth]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasEntryMonth,r]
                
                elsif(node.name == "Year" && node.node_type == 1)
                    node.read
                
                    r = @archival["archival_#{Digest::MD5.hexdigest("Year" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@archival_resource.Year]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@archival_resource.hasYear,r]
              
            elsif(node.name == "DOC" && node.node_type != 1)
                @graph.each_statement do |statement|
                    @output.puts statement.to_s
            end
                @graph.clear
            end
        end
    end

end

Archival.new(ARGV).run




