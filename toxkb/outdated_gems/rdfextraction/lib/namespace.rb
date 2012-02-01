=begin rdoc
NameSpace is used to represent information related to the 
=end
class NameSpace
  
  attr_reader:qname,:uri
  
  def initialize(qname,uri)
    @qname = qname
    @uri = uri
  end
  
  def to_s
    "@prefix " + @qname + ":<" + @uri + ">."
  end
end #class end