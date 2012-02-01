require 'rubygems'
require 'net/http'
require 'uri'
require 'cgi'

#///////////////////////////////////////////////////////////////////////////////////
#
#
#///////////////////////////////////////////////////////////////////////////////////
class Query
  attr_reader:limit,:distinct,:select,:select_clauses,:where_clauses
  #---------------------
  def initialize()
    @limit = nil
    @distinct = false
    @select_clauses = []
    @where_clauses = []
  end
  #--------------------
  def select s
    s.each do |e|
      @select_clauses << e
    end
    @select_clauses.uniq!
  end
  #----------------------
  def where w
      w.each do |e|
        @where_clauses << e
      end
  end
end
#//////////////////////////////////////////////////////////////////////////////////
#
#
#
#//////////////////////////////////////////////////////////////////////////////////
class SparqlAdaptor
  
  #parameters required to connect to a sparql address
  #
  def initialize(params)
    @uri  = params[:uri]
  #  @port = params[:port]
    @prefix_statements = Hash.new
  end
  
  def prefix(p)
      @prefix_statements.merge!(p)
  end
  #-------------------------------------------------------
  #construct uri query for posting to sparql endpoint.
  #will make additional changes to query before further processing.
  def construct_query(query)
    qs = translate2sparql(query)
    return qs
  end
  #-------------------------------------------------------------
  #query the sparql endpoint and get result
  def query(query,params={})
    
    #set defaults
    {:graph=>"",:format=>"xml"}.merge!(params)
    
    puts construct_query(query)
    url = URI.parse(@uri)
    req = Net::HTTP::Post.new(url.path)
    req.set_form_data(':default-graph-uri'=>params[:graph],:query=>construct_query(query))
    response  = Net::HTTP.new(url.host,url.port).start {|http| http.request(req)}
    
    case response
	    when Net::HTTPSuccess, Net::HTTPRedirection
	      #id = parse_response(results)
	      results =  case params[:format]
          when "xml"
            parse_xml(response)
          else
            puts "sorry we don't deal with this format type yet"
            puts results.error!
        end
      else
        puts "you got an error"
        puts results.error!
    end
    return results
  end
  #------------------------------------------------------------------
  #parse the xml results
  #------------------------------------------------------------------
  #translate query object into string representation for sparql query.
  def translate2sparql(query)
    str = ""
        #str << self.declare_prefix() if @prefix_statements.empty? == false
        str << "SELECT #{query.select_clauses.join(' ')} "
        str << "WHERE { #{query.where_clauses.join(' ')}}"
    return str
  end
  #------------------------------------------------------------------------------------
  #set up the prefix declarations in the from the query. should be moved to declaring 
  def declare_prefix()
    tmp = ""
      @prefix_statements.each do |key,value|
        tmp << "prefix+#{key}:<#{value}>"
      end
    return tmp
  end
  
end