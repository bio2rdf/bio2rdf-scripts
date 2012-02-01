
=begin rdoc
  EMIC(1991-present) and EMICBACK(1950-1990) are bibliographic databases on chemical, biological and physical agents that have been tested for genotoxic activity. 
  Records are either derived from MEDLINE or created especially for this database. 
  Many records contain abstracts and MeSH terms and all records contain EMIC specialized indexing keywords and the names and CAS Registry Numbers of all chemicals tested.
  The databases are produced by the Oak Ridge National Laboratory in Oak Ridge, Tennessee, and are funded by the Environmental Protection Agency (EPA) and The National Institutes of Environmental Health Sciences(NIEHS). 
  To reach the databases contact:

National Library of Medicine(NLM)
Specialized Information Services
8600 Rockville Pike
Bethesda, MD 20894
Phone: (800) 638-8480
=end
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

class Emic 
    
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
            @emic             = RDF::Vocabulary.new("http://bio2rdf.org/emic:")
            @emic_resource    = RDF::Vocabulary.new("http://bio2rdf.org/emic_resource:")
            @cas              = RDF::Vocabulary.new("http://bio2rdf.org/cas:")
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
            
            if(node.name.include?("emic")&& node.node_type == 1)
                node.read
                node.read
            end
            
            if(node.name.include?("DOC") && node.node_type ==1 )
                @doc = @emic["emic_#{Digest::MD5.hexdigest(node.inner_xml)}"]
                @graph << [@doc, RDF.type, @emic_resource.DOC]
            end
            
            if(node.name == "DOCNO" && node.node_type == 1)
                  node.read
                  r = @emic["emic_#{Digest::MD5.hexdigest("docno" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.DOCNO]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasDOCNO,r]
                elsif(node.name == "ArticleTitle" && node.node_type == 1)
                  node.read
                  
                  r = @emic["emic_#{Digest::MD5.hexdigest("ArticleTitle" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.ArticleTitle]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasArticleTitle,r]
                elsif(node.name == "AbstractText" && node.node_type == 1)
                  node.read
                 
                  r = @emic["emic_#{Digest::MD5.hexdigest("AbstractTitle" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.AbstracTitle]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasAbstractTitle,r]
                elsif(node.name == "Affiliation" && node.node_type == 1)
                  node.read
                  
                  r = @emic["emic_#{Digest::MD5.hexdigest("Affiliation" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.Affiliation]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasAffiliation,r]
                elsif(node.name == "Author" && node.node_type == 1)
                  node.read
                  
                  r = @emic["emic_#{Digest::MD5.hexdigest("Author" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.Author]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasAuthor,r]
                elsif(node.name == "CASRegistryNumber" && node.node_type == 1)
                  node.read
                  
                  r = @cas[node.value]
                  @graph << [r,RDF.type,@emic_resource.CASRegistryNumber]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasCASRegistryNumber,r]
                elsif(node.name == "CASRegistryNumberName" && node.node_type == 1)
                  node.read
                  
                  r = @emic["emic_#{Digest::MD5.hexdigest("CASRegistryNumberName" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.CASRegistryNumberName]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasCASRegistryNumberName,r]
                elsif(node.name == "CellsObserved" && node.node_type == 1)
                  node.read
                  
                  r = @emic["emic_#{Digest::MD5.hexdigest("CellsObserved" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.CellsObserved]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasCellsObservered,r]
                elsif(node.name == "CellsTreated" && node.node_type == 1)
                  node.read
                  
                  r = @emic["emic_#{Digest::MD5.hexdigest("CellsTreated" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.CellsTreated]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasCellsTreated,r]
                elsif(node.name == "Comments" && node.node_type == 1)
                  node.read
                  
                  r = @emic["emic_#{Digest::MD5.hexdigest("Comments" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.Comments]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasComments,r]
                elsif(node.name == "Control" && node.node_type == 1)
                  node.read
                  
                  r = @emic["emic_#{Digest::MD5.hexdigest("Control" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.Control]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasControl,r]
                elsif(node.name == "DateRevised" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("DateRevised" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.DateRevised]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasDateRevised,r]
                elsif(node.name == "ECSubstance" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("ECSubstance" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.ECSubstance]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasECSubstance,r]
                elsif(node.name == "EnglishAbstractIndicator" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("EnglishAbstractIdicator" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.EnblishAbstractIndicator]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasEnglishAbstractIndicator,r]
                elsif(node.name == "EntryMonth" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("EntryMonth" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.EntryMonth]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasEntryMonth,r]
                elsif(node.name == "ExperimentalConditions" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("ExperimentalConditions" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.ExperimentalConditions]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasExperimentalConditions,r]
                elsif(node.name == "GrantID" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("GrantID" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.GrantID]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasGrantID,r]
                elsif(node.name == "ISBN" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("ISBN" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.ISBN]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasISBN,r]
                elsif(node.name == "ISSN" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("ISSN" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.ISSN]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasISSN,r]
                
                elsif(node.name == "Inducer" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("Inducer" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.Inducer]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasInducer,r]
                elsif(node.name == "InducerCASRegistryNumber" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("InducerCASRegistryNumber" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.InducerCASRegistryNumber]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasInducerCASRegistryNumber,r]
                elsif(node.name == "Language" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("Language" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.Language]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasLanguage,r]
                elsif(node.name == "MatedTo" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("MatedTo" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.MatedTo]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasMatedTo,r]
                elsif(node.name == "MedlineCode" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("MedlineCode" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.MedlineCode]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasMedlineCode,r]
                elsif(node.name == "MedlineTA" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("MedlineTA" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.MedlineTA]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasMedlineTA,r]
                elsif(node.name == "MeshHeading" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("MeshHeading" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.MeshHeading]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasMeshHeading,r]
                elsif(node.name == "NameOfAgent" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("NameofAgent" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.NameOfAgent]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasNameOfAgent,r]
                elsif(node.name == "NameOfAgentCASRegistryNumber" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("NameOfAgentCASRegistyNumber" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.NameOfAgentCASRegistryNumber]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasDOCNO,r]
                elsif(node.name == "NumberOfReferences" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("NumberOfReference" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.NumberOfReferences]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasNumberOfReferences,r]
                elsif(node.name == "PersonalNameSubject" && node.node_type == 1)
                  node.read
                  next if node.value == nil
                    r = @emic["emic_#{Digest::MD5.hexdigest("PersonalNameSubject" + node.value + @doc.to_s)}"]
                    @graph << [r,RDF.type,@emic_resource.PersonalNameSubject]
                    @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                    @graph << [@doc,@emic_resource.hasPersonalNameSubject,r]
                elsif(node.name == "PublicationType" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("PublicationType" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.PublicationType]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasPublicationType,r]
                elsif(node.name == "SecondarySourceID" && node.node_type == 1)
                  node.read

                  r = @emic["emic_#{Digest::MD5.hexdigest("SecondarySourceID" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.SecondarySourceID]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasSecondarySourceID,r]
                elsif(node.name == "SexTreated" && node.node_type == 1)
                  node.read

                       r = @emic["emic_#{Digest::MD5.hexdigest("SexTreated" + node.value + @doc.to_s)}"]
                      @graph << [r,RDF.type,@emic_resource.SexTreated]
                      @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                      @graph << [@doc,@emic_resource.hasSexTreated,r]
                elsif(node.name == "Source" && node.node_type == 1)
                  node.read

                   r = @emic["emic_#{Digest::MD5.hexdigest("Source" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.Source]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasSource,r]
                elsif(node.name == "SpecificTestEndpoint" && node.node_type == 1)
                  node.read

                   r = @emic["emic_#{Digest::MD5.hexdigest("SpecificTestEndpoint" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.SpecificTestEndpoint]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasSpecificTestEndpoint,r]
                elsif(node.name == "TaxonomicName" && node.node_type == 1)
                  node.read

                   r = @emic["emic_#{Digest::MD5.hexdigest("TaxonomicName" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.TaxonomicName]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasTaxonomicName,r]
                elsif(node.name == "TestCategory" && node.node_type == 1)
                  node.read

                   r = @emic["emic_#{Digest::MD5.hexdigest("TestCategory" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.TestCategory]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasTestCategory,r]
                elsif(node.name == "TestObject" && node.node_type == 1)
                  node.read

                   r = @emic["emic_#{Digest::MD5.hexdigest("TestObject" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.TestObject]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasTestObject,r]
                elsif(node.name == "TissueCultured" && node.node_type == 1)
                  node.read


                   r = @emic["emic_#{Digest::MD5.hexdigest("TissueCultured" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.TissueCultured]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasTissueCultured,r]
                elsif(node.name == "VernacularTitle" && node.node_type == 1)
                  node.read

                   r = @emic["emic_#{Digest::MD5.hexdigest("VernacularTitle" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.VernacularTitle]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasVernacularTitle,r]
                elsif(node.name == "Year" && node.node_type == 1)
                  node.read

                   r = @emic["emic_#{Digest::MD5.hexdigest("Year" + node.value + @doc.to_s)}"]
                  @graph << [r,RDF.type,@emic_resource.Year]
                  @graph << [r,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                  @graph << [@doc,@emic_resource.hasYear,r]

                elsif(node.name == "DOC" && node.node_type != 1)
                @graph.each_statement do |statement|
                    @output.puts statement.to_s
                end
                @graph.clear
            end
        end
    end
    
end

Emic.new(ARGV).run




