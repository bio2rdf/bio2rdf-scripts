#Riskline toxicology literature database parser script.
#Author:: Dana Klassen
#Purpose:: Create linked data from riskline.xml file.

require 'rubygems'
require 'rdfextraction'
require 'digest/md5'

settings = {'file' => File.expand_path(File.join(File.dirname(__FILE__),"..","/localfiles/databases/riskline.xml")),
              'outpath'  => File.expand_path(File.join(File.dirname(__FILE__),"..","/localfiles/triples/riskline/")),
                'default' => "true"}
                
#-------------------------------------------------------------------------------------------------
#check everthing is alright to get the party started.          
                begin
                	raise ArgumentError if InitParam.check(settings,ARGV) == false
                	#validate the presnce of the xml file.
                	begin
                		@parser = XmlAdaptor.new(settings.fetch('file'))
                	rescue IOError => e
                  		puts e.backtrace.join("\n")
                  		kernel.exit
                	end
                	#validate that a file path was passed in a file can be created.
                	begin
                	  file      = settings.fetch('outpath') + "/riskline.n3"
                		@outfile  = File.new(file,"w+")
                	rescue Errno::ENOENT => e
                			if (File.directory?(settings.fetch('outpath')) == false && File.exists?(settings.fetch('outpath')) == false)
                				Dir.mkdir(settings.fetch('outpath'))
                				retry
                			end	
                			  puts "The path you provided for the outpath was invalid"
                			  puts settings.fetch('outpath')
                			  puts e.backtrace.join("\n")
                			  Kernel.exit
                	end
                rescue ArgumentError => e
                	Kernel.exit
                end
puts "Program Running:"
puts "--->infile: " + settings.fetch('file')
puts "--->outfile: " + @outfile.path
#-----------------------------------------------------------------------------------------------------
#okay lets add the prefix information to the outfile and start RDFIzing
nslist = NameSpaceList.new()
nslist.add_prefix("riskline","http://bio2rdf.org/riskline:")
nslist.add_prefix("riskline_resource","http://bio2rdf.org/riskline_resource:")
@outfile << nslist.output

#------------------------------------------------------------------------------------------------------
#parse the xml file and convert to RDF
puts "--->Convert XML to RDF."
while record = @parser.parse_xml("DOC")
  
  
  #collect elements
  entry_id          = record.find_first("DOCNO")
  article_title     = record.find("ArticleTitle").to_a
  abstract_text     = record.find("AbstractText").to_a
  author            = record.find("Author").to_a
  casregistrynumber = record.find("CASRegistryNumber").to_a
  entrymonth        = record.find("EntryMonth").to_a
  keyword          = record.find("Keyword").to_a
  secondarysourceid = record.find("Language").to_a
  source            = record.find("SecondarySourceID").to_a
  year              = record.find("Year").to_a
  
  #ROOT CONCEPT - database_record
  database_record = Concept.new("riskline",Digest::MD5.hexdigest("databaseRecord" + entry_id.content))
  database_record.add_statement("rdf","type","riskline_resource","Database_Record")
  database_record.add_literal("rdfs","label",entry_id.content)
  #secondary Source ID CONCEPT
  secondarysourceid.each do |node|
    ssid_concept = Concept.new("riskline",Digest::MD5.hexdigest("secondarySourceID"+node.content))
    ssid_concept.add_statement("rdf","type","riskline_resource","Secondary_Source_ID")
    ssid_concept.add_literal("rdfs","label",node.content)
    database_record.add_relationship("riskline_resource","hasSecondaryId",ssid_concept)
    @outfile << ssid_concept.output
  end
  #ENTRYMONTH CONCEPT
  entrymonth.each do |node|
    entry_month_concept = Concept.new("riskline",Digest::MD5.hexdigest("entryMonth" + node.content))
    entry_month_concept.add_statement("rdf","type","riskline_resource","Entry_Month")
    entry_month_concept.add_literal("rdfs","label",node.content)
    database_record.add_relationship("riskline_resource","hasEntryMonth",entry_month_concept)
    @outfile << entry_month_concept.output
  end
  #ARTICLE ROOT CONCEPT - linkes to database_record
  article = Concept.new("riskline",Digest::MD5.hexdigest(entry_id.content + article_title.to_s + abstract_text.to_s + casregistrynumber.to_s))
  article.add_statement("rdf","type","riskline_resource","Article")
  database_record.add_relationship("riskline_resource","hasArticle",article)
  
  article_title.each do |node|
      article_title_concept = Concept.new("riskline",Digest::MD5.hexdigest("articleTitle" + node.content))
      article_title_concept.add_statement("rdf","type","riskline_resource","ArticleTitle")
      article_title_concept.add_literal("rdfs","label",node.content)
      article.add_relationship("riskline_resource","hasArticleTitle",article_title_concept)
      @outfile << article_title_concept.output
  end
  abstract_text.each do |node|
    abstract_text_concept = Concept.new("riskline",Digest::MD5.hexdigest("abstactText"+node.content))
    abstract_text_concept.add_statement("rdf","type","riskline_resource","AbstractText")
    abstract_text_concept.add_literal("rdfs","label",node.content)
    article.add_relationship("riskline_resource","hasAbstractTet",abstract_text_concept)
    @outfile << abstract_text_concept.output
  end
  author.each do |node|
    author_concept = Concept.new("riskline",Digest::MD5.hexdigest("author"+node.content))
    author_concept.add_statement("rdf","type","riskline_resource","Author")
    author_concept.add_literal("rdfs","label",node.content)
    article.add_relationship("riskline_resource","hasAuthor",author_concept)
    @outfile << author_concept.output
  end
  casregistrynumber.each do |node|
    chemical = Concept.new("riskline",Digest::MD5.hexdigest("chemical"+node.content))
    chemical.add_statement("rdf","type","riskline_resource","Chemical")
    chemical.add_statement("riskline_resource","hasCasRegistryNumber","cas",node.content)
    article.add_relationship("riskline_resource","hasChemical",chemical)
    @outfile << chemical.output
  end
  keyword.each do |node|
    keyword_concept = Concept.new("riskline",Digest::MD5.hexdigest("keyword"+node.content))
    keyword_concept.add_statement("rdf","type","riskline_resource","Keyword")
    keyword_concept.add_literal("rdfs","label",node.content)
    article.add_relationship("riskline_resource","hasKeyword",keyword_concept)
    @outfile << keyword_concept.output
  end
  source.each do |node|
    source_concept = Concept.new("riskline",Digest::MD5.hexdigest("source"+node.content))
    source_concept.add_statement("rdf","type","riskline_resource","Source")
    source_concept.add_literal("rdfs","label",node.content)
    article.add_relationship("riskline_resource","hasSource",source_concept)
    @outfile << source_concept.output
  end
  year.each do |node|
    year_concept = Concept.new("riskline",Digest::MD5.hexdigest("year"+node.content))
    year_concept.add_statement("rdf","type","riskline_resource","Year")
    year_concept.add_literal("rdfs","label",node.content)
    article.add_relationship("riskline_resource","year",year_concept)
    @outfile << year_concept.output
  end
  
  @outfile << article.output
  @outfile << database_record.output
  
end

puts "--->Sorting file and removing duplicate lines."
%x[sort --unique #{@outfile.path}]
