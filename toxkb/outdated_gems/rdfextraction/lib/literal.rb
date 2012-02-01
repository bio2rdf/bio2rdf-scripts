#Class to represent triple literal values
#created by passing in string values for Subject, predicate, and object
#format outlined in in RDF syntax.
#
#Author:: Dana Klassen
#Created::March 24, 2010.
require 'cgi'
require 'iconv'

class Literal
  
  attr_reader:subject_prefix,:subject,:pred_prefix,:pred,:object
    
  def initialize(sp,s,pp,p,o)
    @subject_prefix = self.properUriSyntax?(sp)
    @subject = self.properUriSyntax?(s)
    @pred_prefix = self.properUriSyntax?(pp)
    @pred = self.properUriSyntax?(p)
    @object = self.cleanLiteral(o)
  end #initialize

  #removes any characters from a string literal portion of a triple
  #that would result in possible errors.
  #*1.remove newline characters and bad escape sequences not permitted in RDF.
  #*2.escape double quoted characters.
  #*3.Escape any HTML present in literal.
  def cleanLiteral(string)
	ic = Iconv.new('UTF-8//IGNORE', 'UTF-8')
	valid_string = ic.iconv(string + ' ')[0..-2]
	
    valid_string = valid_string.gsub(/[\t\n\b\a]+/,"")
    valid_string = valid_string.gsub(/['"]/,'\1')
    return CGI.escapeHTML(valid_string) 
  end
  
	#lets look at the syntax of the of the qname and make sure that we were passed
	#a string that conforms to proper syntax.
	#raises error in the event that a string does not conform to standards.
	#*1.contains only numbers.
	#*2.contains non-uri safe characters.
  def properUriSyntax?(string)
    #raise SyntaxError.new("Improper qname syntax: contains only numbers in " + string) if /[^0-9]/.match(string) != nil
	  #raise SyntaxError.new("Improper qname syntax: contains non-uri encoded characters in " + string) if /[[:punct:]]/.match(string) !=nil
    return string
  end
  
  #Format the outgoing object string as a triple.
  def to_s
    @subject_prefix + ":" + @subject + " " + @pred_prefix + ":" + @pred + " " + "\"" + @object + "\""
  end
end #end class
