require File.join(File.dirname(__FILE__),'concept') #ugly way of including relative search path\
require 'set'

#Container Class to hold concept objects.
#Concept objects associated with the graph will be outputted
#in trig syntax.
#Author:: Dana Klassen

class Graph < Concept
      
  def initialize(ns,subject)
    #creates new array to hold triples added to the graph
    super(ns,subject)
    @graph = Set.new()
  end
  
=begin rdoc
add Concept object to Set.
=end 
  def add_concept(concept)
    @graph << concept
  end
  
=begin rdoc
Output iterates through concepts associated with the graph and outputs triples of those concepts.
=end
  def to_named_graph
    stack = @ns + ":" + @subject + "{\n"
    	for concept in @graph
      	concept.each {|triple| stack <<  triple.to_s + " .\n"}
   	  end
   	  stack << "}\n"
    @graph.clear
    return stack
  end
  
=begin rdoc
Output_triples outputs triples 'about' the graph object
=end
 def graph_description()
 	 	stack = ""
  		@triples.each{|triple| stack << triple.to_s + "\n"}
  return stack
end

=begin rdoc
add Concept object to Set.
=end
  def <<(concept)
    @graph << concept
  end
 
  
end #end graph
