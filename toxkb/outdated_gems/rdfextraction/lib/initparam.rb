#Module to check the input parameters when a program is run
#default Hash value is checked against args Hash.
#keys passed in that are not in the default list result in error.
module InitParam
  
  def InitParam.check(defaults,args)
    
    #are there any parameters?
    if(args.length>0)
      
      #go through parameters one by one.
      for parameter in args
        
        arg = parameter.split("=")  #split by equals sign.
        #if setting is in the settings list set the appropriate value
        if(defaults.include?(arg[0]))
          defaults[arg[0]] = arg[1]
        else
          raise ArgumentError, "An improper key value was used: " + arg[0], caller[1..-1]
        end
      end
    else
      puts "Here are the available settings:"
      defaults.each_pair{|key,value| puts key + " ------> " + value}
      return false
    end
  end
  
end
