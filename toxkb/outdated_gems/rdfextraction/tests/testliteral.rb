require File.join(File.expand_path(File.dirname(__FILE__)),"..","lib","literal")
require 'rubygems'
require 'test/unit'


class TestLiteral < Test::Unit::TestCase

def test_syntax()
	assert_nothing_raised do
			test = Literal.new("qname1","subject1","qname2","predicate1","literal1")
	end
	puts "Checking to see that SyntaxError is raised when qname  a number"
	assert_raises(SyntaxError) {Literal.new("1","subject1","qname2","predicate1","literal1")}
	puts "passed....."
	puts "Checking to see an error is raised when uri is just a number...." 
	assert_raises(SyntaxError) { Literal.new("qname1","1","qname2","predicate1","literal1")}
	puts "passed...."
	puts "checking to see if _1 will be recognised as improper qname"
	assert_raises(SyntaxError) {Literal.new("_1","subject1","qname2","predicate1","literal1")}
	puts "passed....."
end

def test_literal_output()
  puts "testing that literals have escaped sequences removed......"
	test = Literal.new("w","subject1","qname2","predicate1","test\n\t\a\r")
	assert_equal("test",test.object,"test was supposed to include an n if failed")
	puts "passed...."
end

end

