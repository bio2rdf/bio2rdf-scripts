#Represet RDF triple statements
#Author:: Dana Klassen
require File.join(File.dirname(__FILE__),"literal")

class Statement < Literal
  
  attr_reader:subject_prefix,:subject,:pred_prefix,:pred,:object_prefix,:object
  
  def initialize(sp,s,pp,p,op,o)
    @subject_prefix = sp 
    @subject = s				
    @pred_prefix = pp
    @pred = p
    @object_prefix = op
    @object = o 
  end #initialize

  def to_s
    @subject_prefix + ":" + @subject + " " + @pred_prefix + ":" +  @pred + " " + @object_prefix + ":" + @object
  end
end #end class
