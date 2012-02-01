require 'rubygems'
require 'mysql'

=begin rdoc
author: Dana Klassen
Description: Class was created to be an adaptor to a mysql database and deal with error handling.
=end
class MysqlAdaptor
attr_accessor:dbh
private_class_method:new

@@instance = nil
def MysqlAdaptor.instance()
   return @@instance
end
#connect to mysql database
#created a new constructor that intializes and connects to a database
#in one step
def MysqlAdaptor.connect(host,user,password,database)
  @@instance = new unless @@instance
  begin
      #connect to database
      @@dbh = Mysql.real_connect(host,user,password,database)
      puts "Connection Status \n -------------------------------- \n Connected to: #{@@dbh.get_server_info}"      
  rescue Mysql::Error => e
      puts "Error code: #{e.errno}"
      puts "Error message: #{e.error}"
      puts "Error SQLSTATE: #{e.sqlstate}" if e.respond_to?("sqlstate")
  end
  
  return @@instance
  
end #end connect to database

#query the attached database
#get the results and
#return them to the calling object
def adaptor_query(query)
  
  begin
      result = @@dbh.query(query)
      return result
  rescue Mysql::Error => e
    	puts "Error code: #{e.errno}"
    	puts "Error message: #{e.error}"
  end #error checking
end #end query
  
end #end class