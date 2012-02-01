require File.join(File.dirname(__FILE__),'statement') #ugly way of including relative search path\
require File.join(File.dirname(__FILE__),'literal') #ugly way of including relative search path\
require 'set'

=begin rdoc
Class Concept used to represent concepts in RDF N3 syntax. Concept objects store triple statements, relations, and literals associated with them in an array.
=end


class Concept
  include Enumerable

attr_reader:ns,:subject
  
      def initialize(ns,subject)
          @ns = ns
          @subject = subject
          @triples = Set.new()  
      end

      def add_statement(pp,p,op,o)
          @triples << Statement.new(@ns,@subject,pp,p,op,o)
      end

      def add_literal(pp,p,o)
          @triples << Literal.new(@ns,@subject,pp,p,o)
      end

      def add_relationship(pp,p,object)
          @triples << Statement.new(@ns,@subject,pp,p,object.ns,object.subject)
      end
      #===================================================================
      #output triples to string.
      #===================================================================
      def output()
          stack = ""
          @triples.each{|triple| stack << triple.to_s + " .\n"}
          return stack
      end
      #===================================================================
      #loop through triples.
      #===================================================================
      def each
          @triples.each do |triple|
            yield triple
          end
      end
      #===================================================================
      #clear the triples from the set.
      #===================================================================
      def clear
          @triples.clear()
      end

end #class end
