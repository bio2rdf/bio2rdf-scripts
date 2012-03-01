#Sift format file parser to create first phase linked data RDF.
#Author:: Dana Klassen
#Created :: May 05, 2010
require 'rubygems'
require 'rdfextraction'
require 'digest/md5'
require 'set'

@settings = {'file' => "full path of file",
              'outpath' => File.expand_path(File.join(File.dirname(__FILE__),"..",'/localfiles/triples/cebs/')),
                'vocab' => File.expand_path(File.join(File.dirname(__FILE__),"..","/localfiles/databases/cebs/cebs_dictionary.n3")),
                }         
                #==================================================================================================================
                #Check the settings make sure files are where the should be and other files can be created.
                begin
                	raise ArgumentError if InitParam.check(@settings,ARGV) == false
                	#validate that a file path was passed in a file can be created.
                	begin
                	  file      = @settings.fetch('outpath') + "\/"+ File.basename(@settings.fetch('file'),".sift") + ".n3"
                	  puts file
                	  @infile = File.open(@settings.fetch('file'))
                		@outfile  = File.new(file,"w+")
                	rescue Errno::ENOENT => e
                			if (File.directory?(@settings.fetch('outpath')) == false && File.exists?(@settings.fetch('outpath')) == false)
                				Dir.mkdir(settings.fetch('outpath'))
                				retry
                			end	
                			  puts "The path you provided for the outpath was invalid"
                			  puts @settings.fetch('outpath')
                			  puts e.backtrace.join("\n")
                			  Kernel.exit
                	end
                rescue ArgumentError => e
                	Kernel.exit
                end
puts "Program Running:"
#--------------------------------------------------------------------------------
#ugly section to extract vocabulary from CEBS definition file.

puts "--->loading dictionary: "
f = File.open(@settings.fetch('vocab',"r"))
puts "---------------------->using dictionary created: " + f.mtime.to_s
#regular expression
concept_statement = Regexp.new(/cebs_dictionary:[a-zA-Z0-9_]+\sa\sskos:Concept/)
subject = Regexp.new(/cebs_dictionary:[a-zA-Z0-9_]+/)
skos_label = Regexp.new(/skos:prefLabel\s\"[\w0-9\s_()]+\"/)
identifier = Regexp.new(/cebs_dictionary:[\w0-9\_]+/)
#instance variables
@stack = Hash.new()

f.each do |line|
  if(line.match(concept_statement) !=nil || line.match(/cebs_dictionary:[a-zA-Z0-9_]+\sa\sskos:Collection/))
    #extract identifier and store in array
    match_data = line.match(identifier)
    @tmp = match_data.to_s.split(":")
  elsif(line.match(skos_label) != nil && @tmp !=nil)
    match_data = line.match(skos_label)
    literal = match_data.to_s.match(/"[a-zA-Z0-9_ ()]+"/)
    puts literal if literal.eql?("PRINCIPAL INVESTIGATOR")
    literal = literal.to_s.chomp.strip.gsub("\"","")
    @stack[literal] = @tmp[1]
  end
  
end
#------------------------------------------------------------------------------------

#regular expressions
study_header  = Regexp.new(/^[a-zA-Z0-9_=]+\n!/)
group_header = Regexp.new(/^[a-zA-Z0-9_=]+\n#/)
row_line = Regexp.new(/(([0-9A-Za-z():]+|\s)\t+)*/)

#instance variables of the types of lines in the document
@subject 	 = ""             #represents the subject of the current block.
@subject_id  = ""          #Hash derived from subject line and file name.
@meta_tag 	 = ""            #represets meta information denoted by !.
@meta_tag_id = 
@header 		 = Array.new()     #header of table information.
@row    		 = Array.new()     #single row of table.

#root concept is always going to be hash of file contents.
@doc_root_id = Digest::MD5.hexdigest(File.read(@infile.path))

#list of RDF Concepts used.
document = Concept.new("cebs",@doc_root_id)
document.add_literal("dc","title",File.basename(@infile.path))
document.add_literal("rdf","comment","CEBS database file converted to RDF:" + Time.now.to_s)
study      	  	= ""					#the top level of organization
study_part 		  = ""					#subsection of study
meta_concept    = ""					#meta data for the study object
data_point      = ""					#individual data point from subsection of study (a cell in a table)

#add the prefix header to file.
@prefix_list = NameSpaceList.new()
@prefix_list.add_prefix("cebs","http://bio2rdf.org/cebs:")								#hold concepts belonging to cebs
@prefix_list.add_prefix("cebs_resource","http://bio2rdf.org/cebs_resource:")	 	#hold relations and properties developed outside of cebs.
@prefix_list.add_prefix("cebs_dictionary","http://bio2rdf.org/cebs_dictionary:")	#holds relations to types extracted from the cebs dictionary.
@outfile <<  @prefix_list.output

#-------------------------------------------------------------------------------------
#START going through the file.
puts "--->parsing sift file: " + File.basename(@infile.path)

#begin looping through file
@infile.each_line do |line|
        #strip newline and return carriages from end of line 
        line = line.strip.chomp
 
  #decide which type of line we are getting.
  #^ means we are starting a new block.
  #dont' want to include information if it decribes the root "STUDY" header
  if(line.include?("^") == true && line.include?("STUDY") == true)
    
                #line may contain a more specified vocab to descibe section block - PREP_PROTOCOL = RNA_PREP. Only grab the last bit.
                if(line.include?("="))
                    args = line.split("=")
                    @subject = args[1].gsub(/[\^\t]+/,'')
                else
                    @subject = line.gsub(/[\^\t]+/,'')
                end
    
                    @subject_id= Digest::MD5.hexdigest("#{@subject}#{@doc_root}")								#subject_id hash the subject ex. STUDY with the document root URI.
   		              #format the RDF, make statements and such
   	                @study = Concept.new("cebs",@subject_id)
   	                document.add_relationship("cebs_resource","hasStudy",@study)
   	 
   	              if(@stack[@subject] !=nil)
   	                  @study.add_statement("rdf","type","cebs_dictionary",@stack[@subject])								#assigns the type based on the identifier found in the vocab lookup.
   	                  @study.add_literal("rdfs","comment","#{@subject}[cebs:#{@subject_id}]")
                   else
                      @study.add_statement("rdf","type","cebs_resource",Digest::MD5.hexdigest(@subject))
                      @study.add_literal("rdfs","comment",@subject)
                  end
  #Statement to find the start of section blocks other then "STUDY"
  #^ indicates starting new block
  elsif(line.include?("^") == true && line.include?("STUDY") == false)
  
 	      if(line.include?("="))
              args = line.split("=")
              @subject = args[1].gsub(/[\^\t]+/,'')
        else
              @subject = line.gsub(/[\^\t]+/,'')
        end
    
          @subject_id = Digest::MD5.hexdigest("#{@subject}#{@doc_root}")								#subject_id hash the subject ex. STUDY with the document hash.
          @study_part = Concept.new("cebs",@subject_id)
          @study_part.add_statement("rdf","type","cebs_dictionary",@stack[@subject.strip.chomp])
          @study_part.add_literal("rdfs","comment","#{@subject.strip.chomp}[cebs:#{@subject_id}]")
          @study.add_relationship("cebs_resource","hasStudyPart",@study_part)
          
  #!means it is meta data portion of record and should be assigned to subject.
  #warning: always added to STUDY object.
  elsif(line.include?("!") == true)
      @meta_tag = line.gsub(/[!\n\t]+/,'')
      @meta_tag_id = Digest::MD5.hexdigest(@meta_tag)
      
          if(line.include?("=")==true)
              @meta_tag = @meta_tag.split("=")
		          @meta_concept = Concept.new("cebs",@meta_tag_id)
		          #check if can find tag in vocab.
		          if(@stack[@meta_tag[0]] !=nil)                                                      
		              @meta_concept.add_statement("rdf","type","cebs_dictionary",@stack[@meta_tag[0]])
		              @meta_concept.add_literal("cebs_resource","hasValue",@meta_tag[1])
		          #    @meta_concept.add_literal("rdfs","comment","#{@meta_tag[1]}[cebs:#{@meta_tag_id}]")
		          #when can't find vocab create new reference.
	            else
	                @meta_concept.add_statement("rdf","type","cebs_resource",Digest::MD5.hexdigest(@meta_tag[1]))
	                #@meta_concept.add_literal("rdfs","comment","#{@meta_tag[1]}[cebs:#{@meta_tag_id}]")
	                @meta_concept.add_literal("cebs_resource","hasValue",@meta_tag[1])
              end
            @study.add_relationship("cebs_resource","hasMetaData",@meta_concept)
          end
    @outfile << @meta_concept.output
		
  #------------------------------------------------------------------------------------------------------------
  #$ means it is a table header identifier, split by tabs and store in array
  elsif(line.include?("$")==true)
          @header = line.split("\t")
          #clean up
            @header.each_index do |y|
                @header[y] = @header[y].gsub("$","")
            end
            
  #looking for table rows
  elsif(row_line.match(line) !=nil && line.include?("$")== false && line.include?("#") == false && line != "" && line.include?("^") == false)
   		 
   		 @row = line.split("\t")
   		 #create row_concept
   		 row_id   = Digest::MD5.hexdigest(line.strip.chomp)
   		 row_concept = Concept.new("cebs",row_id)
   		 row_concept.add_statement("rdf","type","cebs_resource","Entry")
   		 #row_concept.add_literal("rdfs","comment","Entry from #{@subject} section[cebs:#{row_id}]")
   		 @study_part.add_relationship("cebs_resource","hasEntry",row_concept)
   		 
    		 #loop through row index and match with header
    		@row.each_index do |index|
    		  #generate concept from row information
      		data_point = Concept.new("cebs",Digest::MD5.hexdigest(@row[index]+@header[index]))																	#data points URI are created from the value and type.
     					 #check to see if the header is in the controlled vocabulary list.
     					 if(@stack[@header[index]]!=nil)
    							    data_point.add_statement("rdf","type","cebs_dictionary",@stack[@header[index]])
    							    #data_point.add_literal("rdfs","comment","#{@header[index]}[cebs:#{Digest::MD5.hexdigest(@row[index])}] ")
    					 #ez
      				 else
      				        #stop gap measure, should find all the vocabulary!
      				         unident_vocab_id = Digest::MD5.hexdigest(@header[index])
      				         unident_vocab    = Concept.new("ceb_resource",unident_vocab_id)
      				         unident_vocab.add_literal("rdfs","comment","#{@header[index]}[cebs:#{unident_vocab_id}]")
      				         @outfile << unident_vocab.output
        							 data_point.add_relationship("rdf","type",unident_vocab)
        							 data_point.add_literal("rdfs","comment",@header[index])
      				 end
     					 #check to see if the row data is actually part of the controlled vocabulary.
     					 
    				    data_point.add_literal("cebs_resource","hasValue",@row[index])
    				    row_concept.add_relationship("cebs_resource","hasAttribute",data_point)
    			   	 @outfile << data_point.output
   		 end
   		 @outfile << @study_part.output
       @outfile << row_concept.output
  	end
end
  @outfile << @study.output
  @outfile << document.output

  #sort the file and delete duplicate lines
  puts "--->sorting file and removing duplicate lines."
  %x[sort --unique #{@outfile.path}]

