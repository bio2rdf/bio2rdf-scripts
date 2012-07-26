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

$:.unshift(File.dirname(__FILE__))

require 'lib/meddra_freq_parsed'
require 'lib/meddra_adverse_effects'
require 'lib/label_mapping'
require 'rdf'
require 'rdf/ntriples'
require 'digest/md5'
require 'logger'
require 'ostruct'
require 'optparse'
require 'digest/md5'
require 'fileutils'

module Sider2Rdf
  class SiderCommand
  include NameSpaces
  
  @@sections = ["adverse","labels","frequency","all"]

  def initialize(args)
    @args = args
    @options = OpenStruct.new()
    @log = Logger.new(STDOUT)
  end
  
  def run
  
    if(process_arguments && validate_arguments)
       FileUtils.mkdir_p(@options.output) if !File.exists?(@options.output)
       @options.download_dir = FileUtils.mkdir_p(File.join(@options.output,"downloads")) if @options.download
       
      if(@options.parse == "adverse" && @options.download)
        adverse = Sider2Rdf::MeddraAdverseEffects.new(@options.output)
        adverse.download(@options.download_dir)
        adverse.process
      elsif(@options.parse == "adverse" && !@options.download && @options.file)
        @log.info "Parsing to: #{@options.output}"
        Sider2Rdf::MeddraAdverseEffects.new(@options.output,@options.file).process
      elsif(@options.parse == "labels" && @options.download)
        labels = Sider2Rdf::LabelMapping.new(@options.output)
        labels.download(@options.download_dir)
        labels.process
      elsif(@options.parse =="labels" && !@options.download && @options.file)
        Sider2Rdf::LabelMapping.new(@options.output,@options.file).process
      elsif(@options.parse == "frequency" && @options.download)
        freq = Sider2Rdf::MeddraFreqParsed.new(@options.output)
        freq.download(@options.download_dir)
        freq.process
      elsif(@options.parse == "frequency" && !@options.download && @options.file)
        Sider2Rdf::MeddraFreqParsed.new(@options.output,@options.file).process
      end
    else
      @log.error "something went pear shaped"
      exit!
    end
  end
    
  # set up the arguments we are going to need to parse files
  # and make this a usable script
  def process_arguments
      @args << "-h" if(@args.length < 1)
      
      opts_parse = OptionParser.new do |opts|
        opts.on('-f','--file FILE','use the following local file') {|file| @options.file = File.expand_path(file)}
        opts.on('-p','--parse PARSE',"sets which set of sider files to download #{@@sections.join("|")}") {|parse| @options.parse = parse}
        opts.on('-d','--download','download the file to be parsed') {@options.download = true}
        opts.on('-o','--output DIR','set the output directory') {|directory| @options.output = File.expand_path(directory)}
        opts.on('-h','--help',"prints the help"){puts opts; exit!}
      end
      
      opts_parse.parse!(@args) rescue raise "There was an error processing command line arguments use -h to see help"
  end
  
  # check all arguments are correct
  def validate_arguments
    if(!@options.parse || !@@sections.include?(@options.parse))
      @log.error "select one of the following to parse: #{@@sections.join("|")}"
      exit!
    end
    
    if(!@options.download && !@options.file)
      @log.error "Select either to download the file remotely or supply the given file"
      exit!
    end
    
    if(!@options.output)
      @log.error "supply an output directory with -o"
      exit!
    end
    
    return true
  end
 end
end

Sider2Rdf::SiderCommand.new(ARGV).run
