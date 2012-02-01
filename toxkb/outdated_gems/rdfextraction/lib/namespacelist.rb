require File.join(File.dirname(__FILE__),'namespace.rb')
require 'set'
=begin rdoc
class to read the contents of a text file containing default namespace information to create prefix list at the top of N3 document.
=end
class NameSpaceList
  
  def initialize()
    @list = Set.new()
    self.load()
  end
  
  def load()
      #find namespace file
      file = File.open(File.join(File.dirname(__FILE__),'ns.txt'))
      
      file.each do |line|
        #namespaces are saved in the format - prefix|http://.com
        line = line.chomp.split("|")
        @list << NameSpace.new(line[0],line[1])
      end
  end #load
  
=begin rdoc
  returns string represntation of all individual namespaces associated with list.
=end
  def output
    stack = ""
    @list.each do |namespace|
      stack << namespace.to_s + "\n"
    end
    return stack
  end
  
  #lets add a prefix to the namespace list 
  #check namespace doesn't already exist
  #then add to set.
  def add_prefix(qname,uri)
  			newNs = NameSpace.new(qname,uri)
  			@list << newNs if @list.include?(newNs) == false
 	end
  
  def add_namespace(namespace)
    @list << namespace
  end
  
  def each()
    @list.each {|namespaceobject| yield namespaceobject}
  end
  
end #end class
