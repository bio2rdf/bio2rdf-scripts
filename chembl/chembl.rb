#!/usr/bin/env ruby

###############################################################################
#Copyright (C) 2012 Dana Klassen
#
#Permission is hereby granted, free of charge, to any person obtaining a copy of
#this software and associated documentation files (the "Software"), to deal in
#the Software without restriction, including without limitation the rights to
#use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
#of the Software, and to permit persons to whom the Software is furnished to do
#so, subject to the following conditions:
#
#The above copyright notice and this permission notice shall be included in all
#copies or substantial portions of the Software.
#
#THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
#SOFTWARE.
###############################################################################


$:.unshift(File.join(File.dirname(__FILE__), "lib/"))

require 'rubygems'
require 'optparse'
require 'ostruct'
require 'logger'
require 'fileutils'

# classes and modules specific to the Chembl2Rdf project
require 'compounds'
require 'targets'
require 'assays'
require 'activities'
require 'properties'
require 'references'

##
# Commandline application for the Chembl2Rdf project 
# 
module Chembl2Rdf
    class CommandLine
    
        def initialize(args)
            @arguments = args
            @options = OpenStruct.new()
            @options.compress = false
            @options.section  = false
            @options.download = false
            @options.pass     = false
            @options.user     = false
            @options.database = "chembl_13"
            @options.output_dir = false 
            @log = Logger.new(STDOUT)
            @sections = ["compounds","activities","references","assays","all","properties","targets","references"]
        end
        
        def run
          if(process_arguments && validate_arguments)
            if(@options.section == "compounds")
              @log.info "Processing compounds:"
              @compounds = Chembl2Rdf::Compounds.new(@options.output_dir,false)
              @compounds.connect(@options.user,@options.pass,@options.database)
              @compounds.process if(@compounds)
            elsif(@options.section == "assays")
              @log.info "Processing Assays:"
              @assays = Chembl2Rdf::Assays.new(@options.output_dir,false)
              @assays.connect(@options.user,@options.pass,@options.database)
              @assays.process if @assays
            elsif(@options.section == "activities")
              @log.info "processing activities:"
              @activities = Chembl2Rdf::Activities.new(@options.output_dir,false)
              @activities.connect(@options.user,@options.pass,@options.database)
              @activities.process if @activities
            elsif(@options.section == "targets")
              @log.info "processing targets:"
              @activities = Chembl2Rdf::Targets.new(@options.output_dir,false)
              @activities.connect(@options.user,@options.pass,@options.database)
              @activities.process if @activities
            elsif(@options.section == "references")
              @log.info "processing references:"
              @references = Chembl2Rdf::References.new(@options.output_dir,false)
              @references.connect(@options.user,@options.pass,@options.database)
              @references.process if @references
            elsif(@options.section == "properties")
              @log.info "processing properties:"
              @properties = Chembl2Rdf::Properties.new(@options.output_dir,false)
              @properties.connect(@options.user,@options.pass,@options.database)
              @properties.process if @properties
            elsif(@options.section == "all")
              @log.info "processing all of chembl:"
              @log.info "--->processing compounds."
              @compounds = Chembl2Rdf::Compounds.new(@options.output_dir,false)
              @compounds.connect(@options.user,@options.pass,@options.database)
              @compounds.process if(@compounds)

              @log.info "--->processing assays:"
              @assays = Chembl2Rdf::Assays.new(@options.output_dir,false)
              @assays.connect(@options.user,@options.pass,@options.database)
              @assays.process if @assays

              @log.info "--->processing activities:"
              @activities = Chembl2Rdf::Activities.new(@options.output_dir,false)
              @activities.connect(@options.user,@options.pass,@options.database)
              @activities.process if @activities

              @log.info "--->processing activities:"
              @activities = Chembl2Rdf::Targets.new(@options.output_dir,false)
              @activities.connect(@options.user,@options.pass,@options.database)
              @activities.process if @activities 
              
              @log.info "--->processing references:"
              @references = Chembl2Rdf::References.new(@options.output_dir,false)
              @references.connect(@options.user,@options.pass,@options.database)
              @references.process if @references
              
              @log.info "--->processing properties:"
              @properties = Chembl2Rdf::Properties.new(@options.output_dir,false)
              @properties.connect(@options.user,@options.pass,@options.database)
              @properties.process if @properties
            end
          end
        end
        
        def process_arguments 
          @arguments << "-h" if(@arguments.length < 1)

          opts_parse = OptionParser.new do |opts|
            opts.on('-c',"compress the generated rdf file") {@options.compress = true}
            opts.on('-s','--section SECTION',"generate RDF for the specific section of Chembl: #{@sections.join("|")}") {|section| @options.section = section}
            opts.on('-u','--user USER',"username to access the chembl database") {|user|@options.user = user}
            opts.on('-p','--pass PASS',"Password to access the chembl database") {|pass|@options.pass = pass}
            opts.on('-d','--database TABLE',"The database where the ") {|table| @options.database = table}            
            opts.on('-o','--output DIR','set the output directory where all files created will be placed'){|dir|@options.output_dir = dir}
            opts.on('-h','--help') {puts opts;exit1! }
          end

            opts_parse.parse!(@arguments) rescue false
        end
        
        def validate_arguments
          if(@options.section && !@sections.include?(@options.section))
            @log.error "The section you specified could not be found: #{@options.section}"
            @log.error "Choose one of: #{@sections.join("|")}"
            exit!
          end

          if(!@options.user || !@options.pass)
            @log.error "Must set the user and pass see -h for details"
            exit!
          end

          if(@options.database == "chembl_13")
            @log.info "Using default chemb_13 datatable as source"
          else
            @log.info "using non-default #{@options.database} as source"
          end

          if(!@options.output_dir)
            @log.error "need to set output directory with -o"
            exit!
          else
            @log.info "setting output directory to: #{@options.output_dir}"
            FileUtils.mkdir_p(@options.output_dir) if !File.exists?(@options.output_dir)
          end
          
          return true
        end
    end
end

Chembl2Rdf::CommandLine.new(ARGV).run
