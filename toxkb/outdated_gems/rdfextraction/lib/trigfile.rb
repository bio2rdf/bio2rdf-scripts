
=begin rdoc
Class to handle the manipulation of RDF statements and literals and save them in the TRIG syntax format to file.
The format is  _:a _:b _:c n_:namedgraph. Files are created using the qname of the supplied namespace argument
and saved with the ending .trig
=end
class TrigFile < File
    
  def initialize(file_path)
    super(file_path,"w+")
    default_prefixlist()
  end

def default_prefixlist()
  #find namespace file
  nslist = File.open(File.join(File.dirname(__FILE__),'ns.txt'))
  
  nslist.each do |line|
    #namespaces are saved in the format - prefix|http://.com
    line = line.chomp.split("|")
    puts("@prefix " + line[0] + ":<" + line[1] + ">.")
  end
end

def add_prefix(prefix,uri)
    puts( "@prefix " + prefix + ":<" + uri + ">.")
end

end #class end

