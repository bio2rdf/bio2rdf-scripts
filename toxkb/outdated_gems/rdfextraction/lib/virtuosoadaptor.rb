require File.join(File.dirname(__FILE__),"initparam.rb")
require File.join(File.dirname(__FILE__),"namespace.rb")
require File.join(File.dirname(__FILE__),"namespacelist.rb")
require 'tempfile'

=begin rdoc
VirtuosoAdaptor Class to faciliate loading of n3 and nqauad triples into virtuoso database. Loading can be done either on the fly or can parse a file.
=end
class VirtuosoAdaptor

#sets new contructor private
  
  def initialize(incoming_settings)  
    #instance of the virtuosoadaptor
    @command = ""
    @default ={'isql'=>"/usr/local/virtuoso-opensource/bin/isql",
                "pass"=>"dba",
                "user"=>"dba",
                "port"=>"1111",
                "ignoreerror"=>"true",
                "graphname"=>"false",
                "flags"=>"401",
                "threads"=>"4"}
  
    init_settings(incoming_settings)
    @default_namespace = NameSpace.new("bio2rdf","http://bio2rdf.org/graph/")
    @initial_command = @default.fetch("isql") + " -S " + @default.fetch("port") + " -U " + @default.fetch("user") + " -P " + @default.fetch("pass") + " verbose=on banner=off prompt=off echo=ON errors=stdout exec=\""
    @finalize_command ="\""
  end
  
def init_settings(settings)
    settings.each do |key,value|
      @default[key] = value if @default.has_key?("#{key}") == true
      raise ArgumentError, "Incorrect Settings supplied #{key}", caller[1..-1] if @default.has_key?("#{key}") == false
    end
end

=begin rdoc
@method:load_default_namespaces
@parameter: none
@purpose: load default namespacelist from file ns.txt located in the /lib of rdfextration gem.
=end
def load_default_namespaces()
  default_namespace = NameSpaceList.new
  default_namespace.each do |namespace|
    @command << "DB.DBA.XML_SET_NS_DECL(" + namespace.qname + "," + namespace.uri + ",2);"
  end
  #execute command
  puts Kernel.system(@initial_command + @command + @finalize_command)
end

#loads the file into the virtuoso database
#accepts a file object for now in the future will accept a file path as well. 
def load_file(file)
  
  #is graph name set? set from file name
  @default["graphname"] = "http://bio2rdf.org/"+File.basename(file.path,".trig") + ":" if @default.fetch("graphname") == "false"
  
	#DB.DBA.TTLP_MT function name to load triples, quads, and n3
	@program = "DB.DBA.TTLP_MT(file_to_string_output(\'" + file.path + "\'),\'\', \'" + @default.fetch("graphname")+ "\'," + @default.fetch("flags") + ", " + @default.fetch("threads") + "); checkpoint;"
  load = @initial_command + @program + @finalize_command
  
 	Kernel.system(load)
 	 
 	
 	#error to give you information which line to remove
  #	*** Error 37000: [Virtuoso Driver][Virtuoso Server]SP029: TriG RDF loader, line 41945:

    
end

=begin rdoc
@method: delete_graph
@parameters: graph_name -> string to be deleted
@purpose: Delete specified graph and triples associated with the graph from the triple store.
=end
def delete_graph(graph_name)
	@command = "Sparql clear graph <" + graph_name + ">"
 	#execute command
 	puts "Deleting graph:  "  + graph_name
 	puts Kernel.system(@intial_command + @command + @finalize_command)
end

=begin rdoc
@method: update_facet()
@purpose: create an index of triples to allow facet browsing
=end
def update_facet()
	@command = "RDF_OBJ_FT_RULE_ADD (null, null, 'All');VT_INC_INDEX_DB_DBA_RDF_OBJ ();urilbl_ac_init_db();s_rank();"
	puts "Updating Facet ...."
	Kernel.system(@intial_command + @command + @finalize_command)
end

=begin rdoc "RDF_OBJ_FT_RULE_ADD (null, null, 'All');VT_INC_INDEX_DB_DBA_RDF_OBJ ();urilbl_ac_init_db();s_rank();"c
@method: online?
@paramters:none
@purpose: Checks to see that there is an instance of Virtuoso Triple Store is running. Returns true if running, false otherwise (running or not installed)
=end
def online?
 	return 	Kernel.system("ps -e | grep virtuoso")
end
  
end #end class
