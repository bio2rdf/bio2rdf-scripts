require '/Users/dana/svn/trunk/rdfizers/RDFExtractionToolKit/lib/namespace'
require '/Users/dana/svn/trunk/rdfizers/RDFExtractionToolKit/lib/trigfile'
require 'test/unit'

class TestTrigFile < Test::Unit::TestCase
  
  def test_openfile
     assert_nothing_raised do
       file = TrigFile.new("/Users/dana/Desktop/test.txt")
      end
   end
end

