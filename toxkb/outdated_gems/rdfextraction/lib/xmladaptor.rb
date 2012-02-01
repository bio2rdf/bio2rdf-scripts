require 'rubygems'
require 'xml/libxml'

=begin rdoc
XmlAdaptor class facilates parsing large xml documents by using a streamreader to scan the file for a supplied element
then converting and returning that element as DOM object.
=end
class XmlAdaptor
  
#accessor list
attr_accessor:databasename,:file

=begin rdoc
initialize with the full file path of the document to be parsed.

=end
   
def initialize(file_path)
  #where the file or database is stored
	@file_path = file_path  	
				
  				#create directory if out path does not already exist.					
  				begin	
  				    #first check if file exists and set off an exception
  				    raise IOError, "File Doesn't Exist", caller[1..-1] if File.exist?(@file_path) == false
  						#sets up xml reader with specific encoding (not sure how this should properly be done)
  						@reader = XML::Reader.file(@file_path,:encoding=>XML::Encoding::ISO_8859_1 ,:options=>XML::Parser::Options::NOENT)  						
  				rescue LibXML::XML::Error =>e
  				  puts "------------------------------------------------\n"
  				  puts "Here is the back trace of the error:"
  				  puts e.backtrace.join("\n")
  					exit
  				rescue IOError => e
  				  puts "The file you specified does not exist:"
  				  puts @file_path
  				  exit
  				end
end #initialize 

=begin rdoc
parse_xml(node->String) accepts a string parameter that matches the xml element to be convert to DOM object. 
Returns DOM object
=end
  def parse_xml(node)
  
  #begin error checking, looking for libxml errors generated.
      
    while @reader.read 
        
      if(@reader.name() == node && @reader.node_type() != XML::Reader::TYPE_END_ELEMENT)
     
        begin
           
           #gets individual record as string 
           node = @reader.read_outer_xml() 
			     #create reader using document, make sure string is safe to pass into 
   		     parser = XML::Parser.string(node)
   		     return parser.parse
   		      
   		   rescue LibXML::XML::Error => e
   		      puts "Produced a LibXML::XML error:"
            puts e.backtrace.join("\n")
            exit
          rescue TypeError => e
            puts "Produced a TypeError:"
            puts e.backtrace.join("\n")
            exit
          rescue FatalError => e
            puts "Produced a FatalError:"
            puts e.backtrace.join("\n")
            exit
          end #begin rescue 
   		   
       end #if
    end #while 
  end #parse_xml
  
              #check to see if node contains subnodes (other then text nodes)
              def subtree?
                
                return true
                
              end
              
              #Handle subtree RDF conversion
              def subtree
                
              end
end #class

