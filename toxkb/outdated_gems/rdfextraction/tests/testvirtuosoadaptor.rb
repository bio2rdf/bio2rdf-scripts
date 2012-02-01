
require File.join(File.expand_path(File.dirname(__FILE__)),"..","lib","virtuosoadaptor")
require 'test/unit'


class TestVirtuosoAdaptor < Test::Unit::TestCase

  def setup()
    @virt = VirtuosoAdaptor.new("port"=>"1111")
  end
=begin	
  def test_setnamespace()
      @virt = VirtuosoAdaptor.new("format"=>"n3")
    assert_nothing_raised do
     @virt.load_default_namespaces() 
    end
  end
=end
  
  def test_online()
  	assert_nothing_raised do
  		puts @virt.online?
  	end
  end
  
  def test_loadfile()
    path = File.join(File.expand_path(File.dirname(__FILE__)),"..","..","localfiles/triples/hsdb/hsdb.trig")
    @file = File.open(path,"r+")
    assert_nothing_raised do
      @virt.load_file(@file)
    end
  end
  
  
end
