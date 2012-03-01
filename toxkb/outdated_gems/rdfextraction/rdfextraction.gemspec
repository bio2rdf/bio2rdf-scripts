lib = File.expand_path('../lib/', __FILE__)
$:.unshift lib unless $:.include?(lib)
 
 require 'libxml'

  Gem::Specification.new do |s|
    s.name        = "rdfextraction"
      s.version     = "0.1"
        s.platform    = Gem::Platform::RUBY
          s.authors     = ["Dana Klassen"]
            s.email       = ["dklassen@connect.carleton.ca"]
                s.summary     = "Legacy tools for coverting xml databases to RDF"
                  s.description = ""
                             s.files        = Dir.glob("{lib}/**/*")
                                 s.require_path = 'lib'
                                 end
