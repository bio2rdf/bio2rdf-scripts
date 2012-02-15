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

class Cis 
    
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
            @cis          = RDF::Vocabulary.new("http://bio2rdf.org/cis:")
            @cis_resource = RDF::Vocabulary.new("http://bio2rdf.org/cis_resource:")
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
           begin 
            if(node.name.include?("cis")&& node.node_type == 1)
                node.read
                node.read
            end
            
            if(node.name.include?("DOC") && node.node_type ==1 )
                @doc = @cis["cis_#{Digest::MD5.hexdigest(node.inner_xml)}"]
                @graph << [@doc, RDF.type, @cis_resource.DOC]
            end
            
            if(node.name == "DOCNO" && node.node_type == 1)
                
                node.read
                
                docno = @cis["cis_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                @graph << [docno,RDF.type,@cis_resource.DOCNO]
                @graph << [docno,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasDOCNO,docno]
                
                elsif(node.name == "ArticleTitle" && node.node_type == 1)
                
                    node.read
                
                    r = @cis["cis_#{Digest::MD5.hexdigest("ArticleTitle" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@cis_resource.ArticleTitle]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@cis_resource.hasArticleTitle,r]
                
                elsif(node.name == "AbstractText" && node.node_type == 1)
    
                    node.read
                    r = @cis["cis_#{Digest::MD5.hexdigest("AbstractTitle" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@cis_resource.AbstractTitle]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@cis_resource.hasAbstractTitle,r]
                elsif(node.name == "Author" && node.node_type == 1)
                    node.read
                
                    r = @cis["cis_#{Digest::MD5.hexdigest("Author" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@cis_resource.ArticleTitle]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@cis_resource.hasAuthor,r]
                elsif(node.name == "CASRegistryNumber" && node.node_type == 1)
                    node.read
                
                    r = @cas[node.value]
                @graph << [r,RDF.type,@cis_resource.CASRegistryNumbe]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@cis_resource.hasCASRegistryNumber,r]
                
                elsif(node.name == "ClassificationCode" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("ClassificationCode" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.ClassificationCode]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasClassificationCode,r]
                
                elsif(node.name == "Coden" && node.node_type == 1)
                    node.read
                
                    r = @cis["cis_#{Digest::MD5.hexdigest("Coden" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@cis_resource.Coden]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@cis_resource.hasCoden,r]
                elsif(node.name == "CollectiveName" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("CollectiveName" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.CollectiveName]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasCollectiveName,r]
                elsif(node.name == "CountryOrState" && node.node_type == 1)
                node.read
                
                    r = @cis["cis_#{Digest::MD5.hexdigest("CountryOrState" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@cis_resource.CountryOrState]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                    @graph << [@doc,@cis_resource.hasCountryOrState,r]
                elsif(node.name == "EntryMonth" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("EntryMonth" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.EntryMonth]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasEntryMonth,r]

                elsif(node.name == "ISSN" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("ISSN" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.ISSN]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasISSN,r]

                elsif(node.name == "Keyword" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("Keyword" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.Keyword]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasKeyword,r]

                elsif(node.name == "Language" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("Language" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.Language]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasLanguage,r]

                elsif(node.name == "PublicationType" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("PublicationType" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.PublicationType]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasPublicationType,r]

                elsif(node.name == "SecondarySourceID" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("SecondarySourceID" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.SecondarySourceID]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasSecondarySourceID,r]
                elsif(node.name == "Source" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("Source" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.Source]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasSource,r]

                elsif(node.name == "VernacularTitle" && node.node_type == 1)
                node.read
                
                r = @cis["cis_#{Digest::MD5.hexdigest("VernacularTitle" + node.value + @doc.to_s)}"]
                @graph << [r,RDF.type,@cis_resource.VernacularTitle]
                @graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                @graph << [@doc,@cis_resource.hasVernacularTitle,r]

                elsif(node.name == "i" && node.node_type == 1)


                elsif(node.name == "sub" && node.node_type == 1)
                                             
                elsif(node.name == "sup" && node.node_type == 1)
                
                elsif(node.name == "url" && node.node_type == 1)
                                
                elsif(node.name == "DOC" && node.node_type != 1)
                @graph.each_statement do |statement|
                    @output.puts statement.to_s
                end
                @graph.clear
            end
            
            rescue TypeError => e
              next
            end
          end
    end

end

Cis.new(ARGV).run




