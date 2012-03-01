require 'rubygems'
require 'rdf'
require 'rdf/ntriples'
require 'nokogiri'
require 'digest/md5'
require 'cgi'
require 'ostruct'
require 'optparse'
require 'logger'

include RDF

class CcrisDatabase 
    
    def initialize(args)
        @arguments = args
        @options = OpenStruct.new()
        @log  = Logger.new(STDOUT)
        
    end
    
    def run
        @log.info "Running program"
            
        if(process_arguments && valid_arguments?)
           @log.info "Processing: \n\t#{@options.file}"
           @log.info "Output to : \n \t\t #{@options.output}"
           @reader           = Nokogiri::XML::Reader(File.open(@options.file))
           @output           = File.new(@options.output,"w+")
           @ccris                = RDF::Vocabulary.new("http://bio2rdf.org/ccris:")
           @ccris_resource       = RDF::Vocabulary.new("http://bio2rdf.org/ccris_resource:")
           @cas             = RDF::Vocabulary.new("http://bio2rdf.org/cas:")
           parse()
           
        end
    end
           
    # Process the arguments on the command line
    def process_arguments
        opts_parse = OptionParser.new do |opts|
           opts.on('-f','--file FILE')  {|f|@options.file   = File.expand_path(f)}
           opts.on('-o','--output FILE'){|f|@options.output = File.expand_path(f)}
        end
           
        opts_parse.parse!(@arguments) rescue return false
           
        return true
    end
    
    # check the arguments to make sure they are valid.
    def valid_arguments?
        begin
           if(@options.file)
               raise LoadError,"The file you specified doesn't exist: #{@options.file}" if File.exist?(@options.file) == false
           else
            @log.error "Select a file using -f or --file FILE"
            exit!
           end
           
           if(@options.output)
               # not going to worry about this one.
           else
            @log.error "No output was specified select using -o or --output"
            exit!
           end
        rescue LoadError => bam
           @log.error bam
           exit!
        end
           
        return true
    end
    
    def parse()
            
        graph = RDF::Graph.new()
        
        @reader.each do |node|
            
            if(node.name.include?("ccris")&& node.node_type == 1)
                node.read
                node.read
            end
            
            if(node.name.include?("DOC") && node.node_type ==1 )
                @doc = @ccris["ccris#{Digest::MD5.hexdigest(node.inner_xml)}"]
                graph << [@doc, RDF.type, @ccris_resource.DOC]
            end
            
            if(node.name == "DOCNO" && node.node_type == 1)
                node.read
                @record = @ccris["ccris_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                graph << [@record,RDF.type,@ccris_resource['DOCNO']]
                graph << [@record,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                graph << [@doc,@ccris_resource.hasDOCNO,@record]
                
                elsif(node.name == "date" && node.node_type == 1)
                node.read
                date = @ccris["ccris_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                
                graph << [date,RDF.type,@ccris_resource.Date]
                graph << [date,RDF.value,"\"#{node.value}\""]
                graph << [@doc,@ccris_resource.hasDate,date]
                
                elsif(node.name == "rln" && node.node_type == 1)
                node.read
                
                rln = @ccris["ccris_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                graph << [rln,RDF.type,@ccris_resource.RLN]
                graph << [rln,RDF.value,"\"#{node.value}\""]
                graph << [@doc,@ccris_resource.hasRLN,rln]
                
                elsif(node.name == "DateRevised" && node.node_type == 1)
                node.read
                
                date_revised = @ccris["ccris_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                graph << [date_revised,RDF.type,@ccris_resource.DateRevised]
                graph << [date_revised,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                graph << [@doc,@ccris_resource.hasDateRevised,date_revised]
                
                elsif(node.name == "NameOfSubstance" && node.node_type == 1)
                node.read
                
                substance_name = @ccris["ccris_#{Digest::MD5.hexdigest(node.value + @doc.to_s)}"]
                graph << [substance_name,RDF.type,@ccris_resource.NameOfSubstance]
                graph << [substance_name,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                graph << [@doc,@ccris_resource.hasNameOfSubstance,substance_name]
                
                # scan for the cas registry number
                node.read until (node.name == "CASRegistryNumber" && node.node_type == 1)
                node.read # get the inside text
                
                if(!node.value.include?("NO CAS RN"))
                    cas = @cas[node.value.strip.chomp]
                    graph << [cas,RDF.type,@ccris_resource.CASRegistryNumber]
                    graph << [cas,RDF.value,RDF::Literal.new("\"#{node.value}\"")]
                    graph << [@doc,@ccris_resource.hasCASRegistryNumber,cas]
                end
                
                
                elsif(node.name == "cstu" && node.node_type == 1)
                
                #handle cstu experiment
                exp = @ccris["ccris_#{Digest::MD5.hexdigest(node.inner_xml + @doc)}"]
                graph << [exp,RDF.type,@ccris_resource.Cstu]
                graph << [@doc,@ccris_resource.hasCstu,exp]
                
                until(node.name == "cstu" && node.node_type !=1)
                    node.read
                    
                    if(node.name == "specc" && node.node_type == 1)
                        node.read
                        
                        specc = @ccris["ccris_#{Digest::MD5.hexdigest(node.value + exp.to_s)}"]
                        
                        graph << [specc,RDF.type,@ccris_resource.Specc]
                        graph << [specc,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasSpecc,specc]
                        
                        elsif(node.name == "ref" && node.node_type == 1)
                        node.read
                        
                        ref = @ccris["ccris_#{Digest::MD5.hexdigest("ref" + node.value + exp.to_s)}"]
                        
                        graph << [ref,RDF.type,@ccris_resource.Ref]
                        graph << [ref,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasReference,ref]
                        
                        
                        # Strain and/or Sex
                        elsif(node.name == "stsxc" && node.node_type == 1)
                        
                        node.read
                        
                        stsxc = @ccris["ccris_#{Digest::MD5.hexdigest("stsxc" + node.value + exp.to_s)}"]
                        
                        graph << [stsxc,RDF.type,@ccris_resource.Stsxc]
                        graph << [stsxc,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasStrainSex,stsxc]
                        
                        
                        # Route carcinogen
                        elsif(node.name == "routc" && node.node_type == 1)
                        
                        node.read
                        
                        routc = @ccris["ccris_#{Digest::MD5.hexdigest("routc" + node.value + exp.to_s)}"]
                        
                        graph << [routc,RDF.type,@ccris_resource.Routc]
                        graph << [routc,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasRoutc,routc]
                        
                        # dose carcinogen
                        elsif(node.name == "dosec" && node.node_type == 1)
                        node.read
                        
                        dose = @ccris["ccris_#{Digest::MD5.hexdigest("dosec" + node.value + exp.to_s)}"]
                        
                        graph << [routc,RDF.type,@ccris_resource.Dosec]
                        graph << [dose,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasDosec,dose]
                        
                        # tumor site type of leasion
                        elsif(node.name == "tstlc" && node.node_type == 1)
                        node.read
                        
                        tstlc = @ccris["ccris_#{Digest::MD5.hexdigest("tstlc" + node.value + exp.to_s)}"]
                        
                        graph << [tstlc,RDF.type,@ccris_resource.Tstlc]
                        graph << [tstlc,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasTstlc,tstlc]
                        
                        # result carcinogenc
                        elsif(node.name == "rsltc" && node.node_type == 1)
                        node.read
                        
                        result = @ccris["ccris_#{Digest::MD5.hexdigest("rsltc" + node.value + exp.to_s)}"]
                        
                        graph << [result,RDF.type,@ccris_resource.Resultc]
                        graph << [result,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasResultc,result]
                    end
                end
                elsif(node.name == "istu" && node.node_type == 1)
                
                #handle istu experiment
                exp = @ccris["ccris_#{Digest::MD5.hexdigest(node.inner_xml + @doc)}"]
                graph << [exp,RDF.type,@ccris_resource.Istu]       
                graph << [@doc,@ccris_resource.hasIstu,exp]
                
                #handle istu experment
                until(node.name == "istu" && node.node_type !=1)
                    node.read
                    
                    # species (inhibitor)
                    if(node.name == "speci" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("speci"+node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Speci]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasSpeci,r]
                        
                    # Reference   
                    elsif(node.name == "ref" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("ref" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Ref]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasRef,r]
                        
                    # Number of animals tested
                    elsif(node.name == "noat" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("noat" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Noat]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasNoat,r]
                    
                    # Strain/Sex
                    elsif(node.name == "stsxi" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("stsxi" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Stsxi]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasStsxi,r]
                        
                    # route inhibitor
                    elsif(node.name == "rtini" && node.node_type == 1)
                        
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("rtini" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Rtini]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasSpecc,r]
                        
                        # Dose [inhibitor] 
                    elsif(node.name == "doini" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("doini" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Doini]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasDoini,r]
                        
                    # carcinogen (cas number)
                    elsif(node.name == "crcni" && node.node_type == 1)
                        node.read
                        
                        
                        if(!node.value.include?("NONE USED") && /\d{3,5}-\d{2}-\d{1}/.match(node.value))
                          
                          r = @ccris[/\d{3,5}-\d{2}-\d{1}/.match(node.value)]
                          graph << [r,RDF.type,@ccris_resource.Crcni]
                          graph << [r,RDF.value,RDF::Literal.new("\"#{/\d{3,5}-\d{2}-\d{1}/.match(node.value)}\"")]
                          graph << [exp,@ccris_resource.hasCrcni,r]
                        end
                    #Route [carcinogen]
                    elsif(node.name == "rtcai" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("rtcai" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Rtcai]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasRtcai,r]
                    
                    # dose [carcinogen]
                    elsif(node.name == "docai" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("docai" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Docai]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasDocai,r]
                    
                    # Promotor
                    elsif(node.name == "prmti" && node.node_type == 1)
                        node.read
                        
                      
                       if(!node.value.include?("NONE USED") && /\d{3,5}-\d{2}-\d{1}/.match(node.value))
                         
                         r = @ccris[/\d{3,5}-\d{2}-\d{1}/.match(node.value)]
                         graph << [r,RDF.type,@ccris_resource.Crcni]
                         graph << [r,RDF.value,RDF::Literal.new("\"#{/\d{3,5}-\d{2}-\d{1}/.match(node.value)}\"")]
                         graph << [exp,@ccris_resource.hasCrcni,r]
                        end
                        # route [promotor]
                    elsif(node.name == "rtpri" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("rtpri" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Rtpri]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasRtpri,r]
                        
                        # dose [promotor]
                    elsif(node.name == "dopri" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("dopri" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Dopri]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasDopri,r]
                        
                        #target tissue:Type of lesion
                    elsif(node.name == "tttli" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("tttli" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Tttli]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasTtli,r]
                        
                        #Endpoint[incidence]
                    elsif(node.name == "endii" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("endii" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Endii]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasEndii,r]
                        
                        #Endpoint [multiplicity]
                    elsif(node.name == "endmi" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("endmi" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Endmi]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasEndmi,r]
                        
                        #Endpoint [Latency]
                    elsif(node.name == "endli" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("endli" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Endli]
                        graph << [r,RDF.value,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasEndli,r]
                        
                        #comments
                    elsif(node.name == "commi" && node.node_type == 1)
                        node.read
                        
                        r = @ccris["ccris_#{Digest::MD5.hexdigest("commi" + node.value + exp.to_s)}"]
                        
                        graph << [r,RDF.type,@ccris_resource.Commi]
                        graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                        graph << [exp,@ccris_resource.hasCommi,r]
                    end
                end
                
                elsif(node.name == "mstu" && node.node_type ==1)
                    #handle mstu experiment
                    exp = @ccris["ccris_#{Digest::MD5.hexdigest(node.inner_xml + @doc)}"]
                    graph << [exp,RDF.type,@ccris_resource.Mstu]
                    graph << [@doc,@ccris_resource.hasMstu,exp]
                
                    until(node.name == "mstu" && node.node_type != 1)
                        node.read 
                        # end point
                            if(node.name == "endpm" && node.node_type == 1)
                                
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("endpm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Endpm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasEndpm,r]
                                    # test system
                                elsif(node.name == "tsstm" && node.node_type == 1)
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("tsstm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Endpm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasEndpm,r]
                                    #strain/indicator
                                elsif(node.name == "indcm" && node.node_type == 1)
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("indcm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Indcm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasIndcm,r]
                                    # species
                                elsif(node.name == "specm" && node.node_type == 1)
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("specm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Specm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasSpecm,r]
                                    # strain/sex
                                elsif(node.name == "stsxm" && node.node_type == 1)
                                    node.read
                                
                                    r  = @ccris["ccris_#{Digest::MD5.hexdigest("stsxm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Stsxm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasStsxm,r]
                                    # Route
                                elsif(node.name == "routm" && node.node_type == 1)
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("routm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Routm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasRoutm,r]
                                    # metabolic activation
                                elsif(node.name == "matvm" && node.node_type == 1)
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("matvm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Matvm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasMatvm,r]
                                    # method
                                elsif(node.name == "methm" && node.node_type == 1)
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("methm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Methm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasMethm,r]
                                    # dose range
                                elsif(node.name == "dosem" && node.node_type == 1)
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("dosem" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Dosem]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasDosem,r]
                                    # dose regimen
                                elsif(node.name == "dosrm" && node.node_type == 1)
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("dosrm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Dosrm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasDosrm,r]
                                    # results
                                elsif(node.name == "rsltm" && node.node_type == 1)
                                    node.read
                                
                                    r = @ccris["ccris_#{Digest::MD5.hexdigest("rsltm" + node.value + exp.to_s)}"]
                                
                                    graph << [r,RDF.type,@ccris_resource.Rsltm]
                                    graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                    graph << [exp,@ccris_resource.hasRsltm,r]
                                # reference
                                elsif(node.name == "ref" && node.node_type == 1)

                                node.read
                                
                                r = @ccris["ccris_#{Digest::MD5.hexdigest("ref" + node.value + exp.to_s)}"]
                                
                                graph << [r,RDF.type,@ccris_resource.Ref]
                                graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                                graph << [exp,@ccris_resource.hasRef,r]


                                end
                    end
                
                elsif(node.name == "tstu" && node.node_type == 1)
                
                    #handle tstu experiment
                    exp = @ccris["ccris_#{Digest::MD5.hexdigest(node.inner_xml + @doc)}"]
                    graph << [exp,RDF.type,@ccris_resource.Tstu]
                    graph << [@doc,@ccris_resource.hasTstu,exp]

                    until(node.name == "tstu" && node.node_type != 1)
                        node.read    
                        
                        # species
                         if(node.name == "spect" && node.node_type == 1)
                             node.read
                             
                             r = @ccris["ccris_#{Digest::MD5.hexdigest("spect" + node.value + exp.to_s)}"]
                             
                             graph << [r,RDF.type,@ccris_resource.Spect]
                             graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                             graph << [exp,@ccris_resource.hasSpect,r]
                             
                             # reference
                         elsif(node.name == "ref" && node.node_type == 1)
                             node.read
                             
                             r = @ccris["ccris_#{Digest::MD5.hexdigest("ref" + node.value + exp.to_s)}"]
                             
                             graph << [r,RDF.type,@ccris_resource.Ref]
                             graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                             graph << [exp,@ccris_resource.hasRef,r]
                             
                             #strain/sex
                         elsif(node.name == "stsxt" && node.node_type == 1)
                             node.read
                             
                             r = @ccris["ccris_#{Digest::MD5.hexdigest("stsxt" + node.value + exp.to_s)}"]
                             
                             graph << [r,RDF.type,@ccris_resource.Stsxt]
                             graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                             graph << [exp,@ccris_resource.hasStsxt,r]
                             
                             #route [promotor]
                         elsif(node.name == "rtprt" && node.node_type == 1)
                             node.read
                             
                             r = @ccris["ccris_#{Digest::MD5.hexdigest("rtprt" + node.value + exp.to_s)}"]
                             
                             graph << [r,RDF.type,@ccris_resource.Rtprt]
                             graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                             graph << [exp,@ccris_resource.hasRtprt,r]
                             
                             #dose [promotor]
                         elsif(node.name == "doprt" && node.node_type == 1)
                             node.read
                             
                             r = @ccris["ccris_#{Digest::MD5.hexdigest("doprt" + node.value + exp.to_s)}"]
                             
                             graph << [r,RDF.type,@ccris_resource.Doprt]
                             graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                             graph << [exp,@ccris_resource.hasDoprt,r]
                             
                             #carcinogen
                         elsif(node.name == "crct" && node.node_type == 1)
                             node.read
                             
                             r = @cas[node.value]
                             
                             graph << [r,RDF.type,@ccris_resource.Crct]
                             graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                             graph << [exp,@ccris_resource.hasCrct,r]
                             
                             #route carcinogen
                         elsif(node.name == "rtcat" && node.node_type == 1)
                             node.read
                             
                             r = @ccris["ccris_#{Digest::MD5.hexdigest("rtcat" + node.value + exp.to_s)}"]
                             
                             graph << [r,RDF.type,@ccris_resource.Rtcat]
                             graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                             graph << [exp,@ccris_resource.hasRtcat,r]
                             
                             #dose carcinogen
                         elsif(node.name == "docat" && node.node_type == 1)
                             node.read
                             
                             r = @ccris["ccris_#{Digest::MD5.hexdigest("docat" + node.value + exp.to_s)}"]
                             
                             graph << [r,RDF.type,@ccris_resource.Docat]
                             graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                             graph << [exp,@ccris_resource.hasDocat,r]
                             
                             #target tissue:type of Lesion
                         elsif(node.name == "tttlt" && node.node_type == 1)
                             node.read
                             
                             r = @ccris["ccris_#{Digest::MD5.hexdigest("tttlt" + node.value + exp.to_s)}"]
                             
                             graph << [r,RDF.type,@ccris_resource.Tttlt]
                             graph << [r,RDFS.label,RDF::Literal.new("\"#{CGI.escape(node.value)}\"")]
                             graph << [exp,@ccris_resource.hasTttlt,r]
                        end
                    end
                
                elsif(node.name == "DOC" && node.node_type != 1)
                    graph.each_statement do |statement|
                        @output.puts statement.to_s
                    end
                    graph.clear
                end
        end
    end
end

CcrisDatabase.new(ARGV).run
