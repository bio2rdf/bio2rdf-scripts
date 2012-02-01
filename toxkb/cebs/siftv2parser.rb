#sift parser v.1.q.
require 'rubygems'
require 'rdfextraction'
require 'digest/md5'
require File.join(File.dirname(__FILE__),'dev.rb')

#------------------------------------------------------------------------------------------
#Functions:

                  #==================================
                  #load the vocab from sparql endpoint
                  #==================================
                  def sparql_vocab()
                        q = Dev::Query.new()
                        q.select("?term ?id")
                        q.where("?id skos:prefLabel ?term")
                        
                        sparql = Dev::SparqlQuery.new(:uri=>"http://bio2rdf.semanticscience.org:8026/sparql?")
                        sparql.prefix(:skos=>"http://www.w3.org/2004/02/skos/core#")
                        return sparql.query(q,:graph=>"cebs",:format=>"xml")

                  end
                  #====================================================
                  #entries2rdf - add the information for table entries
                  #to the supplied object. Then output information
                  #to file.
                  #=====================================================
                  def entries2rdf(pos,object)
                    tmp = File.open(@settings.fetch('file'),"r")
                    tmp.seek(pos,IO::SEEK_SET)
                    begin
                      
                      tmp.each_line do |line|
                        line = line.strip.chomp       #clean line up before you do anything else.
                        
                            break if study_part? line  #its all over if you encounter another study
                            @header = line.split("\t") if(header? line)#set header param as you encounter it.
                            #if line is an entry parsr it using
                            #current header. assumption always encounter header
                            #before entry!
                            if(entry? line)
                                entry_concept = Concept.new("cebs",get_hash(clean(line)))
                                entry_concept.add_statement("rdf","type","cebs_resource","Entry")
                                object.add_relationship("cebs_resource","hasEntry",entry_concept)
                                entry = line.split("\t")
                                                                
                                  entry.each_index do |index|    
                                    #checking to make sure @header[index] is in the vocab list.  
                                      case @stack[clean(@header[index])]  
                            
                                        when nil
                                          #create type to assign attribute to.
                                          header_concept = Concept.new("cebs",get_hash(@header[index]))
                                          header_concept.add_statement("rdf","type","cebs_resource","AttributeType")
                                          header_concept.add_literal("rdfs","label",clean(@header[index]))
                                          #create attribute and assing to header_concept
                                          attribute = Concept.new("cebs",get_hash(entry[index] + @header[index]))
                                          attribute.add_relationship("rdf","type",header_concept)
                                          attribute.add_literal("cebs_resource","hasValue",entry[index])
                                          entry_concept.add_relationship("cebs_resource","hasAttribute",attribute)
                                          @outfile<<header_concept.output
                                          @outfile<<attribute.output
                                        else
                                          if(entry[index] !="")                                            
                                                attribute = Concept.new("cebs",get_hash(entry[index] + @stack[clean(@header[index])]))
                                                attribute.add_statement("rdf","type","cebs_dictionary",@stack[clean(@header[index])])
                                                attribute.add_literal("cebs_resource","hasValue",clean(entry[index]))
                                                entry_concept.add_relationship("cebs_resource","hasAttribute",attribute)
                                                @outfile << attribute.output
                                          end
                                        end
                                end
                                @outfile<<entry_concept.output
                            end
                          end
                      
                    rescue TypeError => e
                      puts "Probably a term that wasn't in the dictionary." 
                      puts e.backtrace.join("\n")
                    end
                  
                  end
                 
                  #====================================================
                  #determines if line is an entry
                  #====================================================
                  def entry?(line)
                    if(/[\!\#\$]/.match(line) == nil && line !="")
                      return true
                    else
                      return false
                    end
                  end
                  #====================================================
                  #clean up the string and remove unwated characters
                  #====================================================
                  def clean(str)
                  	return str.strip.chomp.gsub(/[\^\t\#\$!]+/,'')
                  end
                  #=====================================================
                  #generate and return hash of string.
                  #=====================================================
                  def get_hash(str)
                    return Digest::MD5.hexdigest(str)
                  end
                  #=====================================================
                  #determine if the line is the start of thet study
                  #=====================================================
                  def study?(str)
                     if(str.include?("^STUDY"))
                        return true
                     else
                        return false
                     end
                  end
                  
                  #=========================================================
                  #determine if the line is a header
                  #=========================================================
                  def header?(str)
                  	if(str.include?("$"))
                  		return true
                  	else
                  		return false
                  	end
                  end
                  #=========================================================
                  #gets meta data for a given section block
                  #=========================================================
                  def metadata2rdf(pos,object)
                    meta_data = get_metadata(@infile.pos)
                    return false if meta_data == false
                    
                  	  		meta_data.each_pair do |key,value|
                  	  			meta_concept = Concept.new("cebs", get_hash(key + value))
                  	  			meta_concept.add_statement("rdf","type","cebs_dictionary",@stack[key])
                  	  			meta_concept.add_literal("cebs_resource","hasValue",value)
                  	  			object.add_relationship("cebs_resource","hasMetaData",meta_concept)
                  	  			@outfile << meta_concept.output
                  	  		end
                  end
                  #=========================================================
                  #gets the meta_data from a particular block of information
                  #=========================================================
                  def get_metadata(pos)
                    mtmp = Hash.new()
                    #retrieve meta data.
                    tmp = File.open(@settings.fetch('file'),"r")
                    tmp.seek(pos,IO::SEEK_SET)

                    tmp.each_line do |line|
                      
                      if line.include? "!"
                        line = line.strip.chomp.gsub(/[!]/,'').split("=")
                        mtmp[line[0]] = line[1]
                      else
                        return mtmp if mtmp.length !=0
                        break
                      end
                    end
                    
                    #return false if no meta data was found.
                    return false
                  end
                  #=====================================================
                  #determine if the line is the start of a section block
                  #======================================================
                  def study_part?(str)
                      if(str.include?("^") && study?(str) == false)
                        return true
                      else
                        return false
                      end  
                  end
                  #======================================================
                  #load the vocab from file or from sparql endpoint
                  #======================================================
                  def load_vocab()
                    #regular expression
                    concept_statement = Regexp.new(/cebs_dictionary:[a-zA-Z0-9_]+\sa\sskos:Concept/)
                    subject = Regexp.new(/cebs_dictionary:[a-zA-Z0-9_]+/)
                    skos_label = Regexp.new(/skos:prefLabel\s\"[\w0-9\s_()]+\"/)
                    identifier = Regexp.new(/cebs_dictionary:[\w0-9\_]+/)
                    #instance variables
                    stack = Hash.new()

                    if(@settings.fetch('sparql') == "false")
                        f = File.open(@settings.fetch('dictionary'),"r")
                        puts "---------------------->using dictionary created: " + f.mtime.to_s
                        
                          f.each do |line|
                              if(line.match(concept_statement) !=nil || line.match(/cebs_dictionary:[a-zA-Z0-9_]+\sa\sskos:Collection/))
                                  #extract identifier and store in array
                                  @tmp = line.scan(/CEBSD_\d+/)
                              elsif(line.match(skos_label) != nil && @tmp !=nil)
                                  match_data = line.match(skos_label)
                                  literal = match_data.to_s.match(/"[a-zA-Z0-9_ ()]+"/)
                                  literal = literal.to_s.chomp.strip.gsub("\"","")
                                  stack[literal] = @tmp[0]
                              end
                            end
                            
                            return stack
                      else
                        puts "---------------------->loading dictionary from sparql endpoint:http://bio2rdf.semanticscience.org:8026"
                        
                        results = sparql_vocab()
                        @reader = XML::Reader.string(results.body)
                        
                         while @reader.read 

                            if(@reader.name() == "result" && @reader.node_type() != XML::Reader::TYPE_END_ELEMENT)

                                 #gets individual record as string 
                                 node = @reader.read_outer_xml() 
                      			     #create reader using document, make sure string is safe to pass into 
                         		     parser = XML::Parser.string(node).parse
                         		      results = parser.find('result:binding','result:http://www.w3.org/2005/sparql-results#').to_a
                         		     
                         		     id = results[1].content.scan(/CEBSD_\d+/)
                                 stack.store(results[0].content,id[0])

                             end #if
                          end #while
                      end
                                return stack
                  end

#------------------------------------------------------------------------------------------
@settings = {'file' => "~/svn/trunk/rdfizers/localfiles/databases/cebs/iconix1.sift",
              'outpath' => File.expand_path(File.join(File.dirname(__FILE__),"..",'/localfiles/triples/cebs/')),
                'dictionary' => File.expand_path(File.join(File.dirname(__FILE__),"..","/localfiles/databases/cebs/cebs_dictionary.n3")),
                  'sparql' =>"false"}
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
                				Dir.mkdir(@settings.fetch('outpath'))
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
@stack = load_vocab
#---------------------------------------------------------------------------------

#instance variables
doc_id = Digest::MD5.hexdigest(File.read(@infile.path))

doc = Concept.new("cebs",doc_id)
doc.add_statement("rdf","type","cebs_resource","SiftFile")

#==================================
#prepare triple file header
#add the prefix header to file.
#==================================
@prefix_list = NameSpaceList.new()
@prefix_list.add_prefix("cebs","http://bio2rdf.org/cebs:")								#hold concepts belonging to cebs
@prefix_list.add_prefix("cebs_resource","http://bio2rdf.org/cebs_resource:")	 	#hold relations and properties developed outside of cebs.
@prefix_list.add_prefix("cebs_dictionary","http://bio2rdf.org/cebs_dictionary:")	#holds relations to types extracted from the cebs dictionary.
@outfile <<  @prefix_list.output

@infile.each_line do |line|
  
  line = line.strip.chomp
  
  #=================================================================
  #deals with creation of study  and attaching meta data to the study concept
  #======================================================================
  if(study? line)
      @study = Concept.new("cebs",get_hash(clean(line) + doc_id))   #uri from line "STUDY" and hash of entire document.
      @study.add_statement("rdf","type","cebs_dictionary",@stack[clean(line)])
      metadata2rdf(@infile.pos,@study)
      entries2rdf(@infile.pos,@study)
      
  #=====================================================================================  	  		
  #deals with the creation of study parts and attaching the entry and data point values
  #======================================================================================
  elsif(study_part? line)
  		
  		if line.include? "="
  		  tmp = line.split("=")
  		  @subject = clean(tmp[1])
  		else
  		    @subject = clean(line)
		  end
		  
		  @study_part = Concept.new("cebs",get_hash(@subject + doc_id))
		  @study_part.add_statement("rdf","type","cebs_dictionary",@stack[@subject])
		  @study.add_relationship("cebs_resource","hasStudyPart",@study_part)
		  metadata2rdf(@infile.pos,@study_part)
		  entries2rdf(@infile.pos,@study_part)
		  @outfile << @study_part.output
  end

end
  @outfile << @study.output
    #sort the file and delete duplicate lines
  puts "--->sorting file and removing duplicate lines."
  %x[sort --unique #{@outfile.path}]

