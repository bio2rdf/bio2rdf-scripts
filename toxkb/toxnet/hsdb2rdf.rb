  require 'rubygems'
  require 'digest/md5'
  require 'rdfextraction'
  require 'cgi'

#################################################################################################################
#HSDB :: Hazardous Substance Data Bank
#Author :: Dana Klassen
#Description :: This script takes the HSDB.xml file downloaded from the NLM toxnet archives and coverts it to RDF.
# The script relies on several gems that are located in the repository this script is stored. These Gems must be
# installed prior to use
#################################################################################################################
  settings = {'file' => File.expand_path(File.join(File.dirname(__FILE__),"..","/localfiles/databases/hsdb.xml")),
                'outpath'  => File.expand_path(File.join(File.dirname(__FILE__),"..","/localfiles/triples/hsdb/")),
                  'default' => "true"}

  #-------------------------------------------------------------------------------------------------
  #check everthing is alright to get the party started.          
                  begin
                  	raise ArgumentError if InitParam.check(settings,ARGV) == false
                  	#validate the presnce of the xml file.
                  	begin
                  		@parser = XmlAdaptor.new(settings.fetch('file'))
                  	rescue IOError => e
                    		puts e.backtrace.join("\n")
                    		exit!
                  	end
                  	#validate that a file path was passed in a file can be created.
                  	begin
                  	  file      = settings.fetch('outpath') + "/hsdb.n3"
                  		@outfile  = File.new(file,"w+")
                  	rescue Errno::ENOENT => e
                  			if (File.directory?(settings.fetch('outpath')) == false && File.exists?(settings.fetch('outpath')) == false)
                  				Dir.mkdir(settings.fetch('outpath'))
                  				retry
                  			end	
                  			  puts "The path you provided for the outpath was invalid"
                  			  puts settings.fetch('outpath')
                  			  puts e.backtrace.join("\n")
                  			  Kernel.exit
                  	end
                  rescue ArgumentError => e
                  	Kernel.exit
                  end
  puts "Program Running:"
  puts "--->infile: " + settings.fetch('file')
  puts "--->outfile: " + @outfile.path
  #-----------------------------------------------------------------------------------------------------
  #okay lets add the prefix information to the outfile and start RDFIzing
  nslist = NameSpaceList.new()
  nslist.add_prefix("hsdb","http://bio2rdf.org/hsdb:")
  nslist.add_prefix("hsdb_resource","http://bio2rdf.org/hsdb_resource:")
  @outfile << nslist.output
#Start parsing the XML file.

#holds value key pairs of element tags and RDF property values.
stack = Hash.new()
					while record = 	@parser.parse_xml("DOC") 
							#------------------------------------------------
							#administrative information
							#------------------------------------------------
							entry_number = record.find_first('DOCNO')
						  entry_date = record.find_first("date")
							entry_dates_modified = record.find("DateRevised").to_a
							entry_comments = record.find("no")
							related_records = record.find("relt")
							
							database_record = Concept.new("hsdb",Digest::MD5.hexdigest(entry_number.content))
							database_record.add_statement("rdf","type","hsdb_resource","Database_Record")
							database_record.add_literal("rdfs","label",entry_number.content)
							
							related_records.each do |node|
							    tmp = node.content.match(/[0-9]+/)
							  if(tmp !=nil)
							      rr_concept = Concept.new("hsdb",Digest::MD5.hexdigest(tmp.to_s))
							      rr_concept.add_statement("rdf","type","hsdb_resource","Database_Record")
						        database_record.add_relationship("hsdb_resource","hasRelatedDatabaseRecord",rr_concept)
						        @outfile << rr_concept.output
						    end
						  end
							entry_dates_modified.each do |node|
							  edm_concept = Concept.new("hsdb",Digest::MD5.hexdigest(node.content))
							  edm_concept.add_statement("rdf","type","hsdb_resource","Entry_Date_Modified")
							  edm_concept.add_literal("rdfs","label",node.content)
							  database_record.add_relationship("hsdb_resource","hasEntryDateModified",edm_concept)
							  @outfile << edm_concept.output
						  end
						  entry_comments.each do |node|
						    comment_concept = Concept.new("hsdb",Digest::MD5.hexdigest(node.content))
						    comment_concept.add_statement("rdf","type","hsdb_resource","Comment")
						    comment_concept.add_literal("rdfs","label",node.content)
						    database_record.add_relationship("hsdb_resource","hasComment",comment_concept)
						    @outfile << comment_concept.output
					    end

              #-------------------------------------------------------------------------------
              #Chemical information to created chemical class
              #-----------------------------------------------------------------------------------
							substance_name = record.find_first("NameOfSubstance")
							cas_number = record.find_first("CASRegistryNumber")
							substance = Concept.new("hsdb",Digest::MD5.hexdigest(substance_name.content + entry_number.content))
							substance.add_statement("rdf","type","hsdb_resource","Substance")
							substance.add_literal("rdfs","label",substance_name.content)
							substance.add_statement("hsdb_resource","hasCasRegistryNumber","cas",cas_number.content) if(cas_number.content.include?("null") == false && cas_number.content.include?("NO CAS RN") == false)
							database_record.add_relationship("hsdb_resource","hasSubstance",substance)
							
							#everythi ;askflkgng else will be assigned on the basis of what was found in the HSDB schema.
							stack.store("RTECSNumber","sy")
							stack.store("Synonyms","mf")
							stack.store("MolecularFormula","rtec")
							stack.store("ShippingName","shpn")
							stack.store("STCCNumber","stcc")
							stack.store("EPAHazardousWasteNumber","hazn")
							stack.store("AssociatedChemicals","asch")
							
							#manufacturing/Use Information
							stack.store("MethodsOfManufacturing","mmfa")
							stack.store("Impurities","imp")
							stack.store("FormulationsPreparations","form")
							stack.store("Manufacturers","mfs")
							stack.store("OtherManufacturingInformation","omin")
							stack.store("MajorUses","use")
							stack.store("ConsumptionPatterns","cpat")
							stack.store("USProduction","prod")
							stack.store("USImports","impt")
							stack.store("USExports","expt")
							
							#chemical and physical properties
							stack.store("ColorForm","cofo")
							stack.store("Odor","odor")
							stack.store("Taste","tast")
							stack.store("BoilingPoint","bp")
							stack.store("MeltingPoint","mp")
							stack.store("MolecularWeight","mw")
							stack.store("Corrosivity","corr")
							stack.store("CriticalTemperaturePressure","ctp")
							stack.store("DensitySpecificGravity","den")
							stack.store("DissociationConstants","dsc")
							stack.store("HeatOfCombustion","htc")
							stack.store("HeatOfVaporization","htv")
							stack.store("OctanolWaterPartitionCoefficient","owpc")
							stack.store("PH","ph")
							stack.store("Solubilities","sol")
							stack.store("SpectralProperties","spec")
							stack.store("SurfaceTension","surf")
							stack.store("VaporDensity","vapd")
							stack.store("VaporPressure","vap")
							stack.store("RelativeEvaporationRate","evap")
							stack.store("Vicosity","visc")
							stack.store("OtherChemicalPhysicalProperties","ocpp")
							
							#safety and handling
							stack.store("HazardsSummary","hszs")
							stack.store("DotEmergencyGuidelines","dot")
							stack.store("FirePotential","fpot")
							stack.store("NfpaHazardClassification","nfpa")
							stack.store("FlammableLimits","flmt")
							stack.store("FlashPoint","flpt")
							stack.store("AutoignitionTemperature","auto")
							stack.store("FireFightingProcedures","firp")
							stack.store("ToxicCombustionProducts","toxc")
							stack.store("OtherFirefightingHazards","ofhz")
							stack.store("ExplosiveLimitsAndPotential","expl")
							stack.store("ReactivitiesAndIncompatibilities","reac")
							stack.store("Decomposition","dcmp")
							stack.store("Polymerization","poly")
							stack.store("OtherHazardousReactions","ohaz")
							stack.store("OdorTheshold","odrt")
							stack.store("SkinEyeRespiratoryIrritations","seri")
							stack.store("ProtectieEquipmentAndClothing","equp")
							stack.store("OtherPrevntativeMeasures","oprm")
							stack.store("StabilitiesShelfLife","ssl")
							stack.store("ShipmentMethodsRegulations","ship")
							stack.store("StorageConditions","strg")
							stack.store("CleanupMethods","clup")
							stack.store("DisposalMethods","disp")
							stack.store("RadiationLimitsandPotential","radl")
							
							#toxicity and biomedical effects
							stack.store("ToxicitySummary","toxs")
							stack.store("EvidenceforCarcinogenicity","care")
							stack.store("AntidoteandEmrgencyTreatment","antr")
							stack.store("MedicalSurveillance","meds")
							stack.store("HumanToxicityExcerpts","htox")
							stack.store("NonHumanToxicityExcerpts","ntox")
							stack.store("HumanToxicityValues","htxv")
							stack.store("NonHumanToxicity","ntxv")
							stack.store("EcotoxicityValues","etv")
							stack.store("NationalToxicologyProgramReports","ntp")
							stack.store("TSCATestSubmissions","tcat")
							stack.store("PopulationsAtRisk","popl")
							stack.store("ADMET","ade")
							stack.store("MetabolismMetabolites","metb")
							stack.store("MechanismOfAction","actn")
							stack.store("Interactions","intc")
							
							#pharmacology","")
							stack.store("Bionecessity","bion")
							stack.store("TherapticUses","ther")
							stack.store("MinimumPotentialFateHumanDose","minf")
							stack.store("DrugWarning","warn")
							stack.store("DrugTolerance","idio")
							stack.store("MaximumDrugDose","mxdo")
							
							#environmental fate/exposure potential
							stack.store("EnvironmentalFateSummary","envs")
							stack.store("NaturalOccuringSources","nats")
							stack.store("ArtificialSources","arts")
							stack.store("EnvironmentalFate","fate")
							stack.store("Biodegredation","biod")
							stack.store("AbioticDegredation","abio")
							stack.store("Bioconcentration","bioc")
							stack.store("SoilAdsorptionMobility","koc")
							stack.store("VolatilizationFromWaterSoil","vws")
							stack.store("WaterConcentrations","watc")
							stack.store("EffluentsConcentrations","effl")
							stack.store("SedimentSoilConcentrations","seds")
							stack.store("AtmosphericConcentrations","atmc")
							stack.store("FoodSurveyValues","food")
							stack.store("PlantConcetrations","pint")
							stack.store("FishSeafoodConcentrations","fish")
							stack.store("AnimalConcentrations","anml")
							stack.store("MilkConcentrations","milk")
							stack.store("OtherEnvironmentalConcentrations","oevc")
							stack.store("ProbableRoutesOfHumanExposure","rtex")
							stack.store("AverageDailyIntake","avdi")
							stack.store("BodyBurdens","body")
							
							#exposure standards and Regulations
							stack.store("ImmediatelyDangerous","idlh")
							stack.store("AcecptableDailyIntakes","adi")
							stack.store("AllowableTolerances","atol")
							stack.store("OSHAStandards","osha")
							stack.store("NIOSHRecommendations","nrec")
							stack.store("ThesholdLimitValues","tly")
							stack.store("OtherOccupationalPermissibleLevels","oopl")
							stack.store("AtmosphericStandards","astd")
							stack.store("SoilStandards","sstd")
							stack.store("FederalDrinkingWaterStandards","fdws")
							stack.store("FederalDrinkingWaterGuidelines","fdwg")
							stack.store("StateDrinkingWaterStandards","sdws")
							stack.store("StateDrinkingWaterGuidlines","sdwg")
							stack.store("CleanWaterActRequirements","cwa")
							stack.store("CerclaReportableQuantities","cerc")
							stack.store("TSCARequirements","tsca")
							stack.store("RCRARequirements","rcra")
							stack.store("FIFRARequirements","fifr")
							stack.store("FDARequirements","fda")
							
							#monitoring and analysis methods
							stack.store("SamplingProcedures","samp")
							stack.store("AnalyticLaboratoryMethods","alab")
							stack.store("ClinicalLaboratoryMethods","clab")
							
							#additional references
							stack.store("SpecialReports","rpts")
							stack.store("TestStatus","test")
							stack.store("PriorHistoryOfAccidents","hist")
							
							#now enumerate through hash find all labels with value and convert to literal assinged to chemical
							stack.each do |key,value|
							   record.find(value).to_a.each do |node|
							     if(node.content.include?("null") == false)
							        temp_concept = Concept.new("hsdb",Digest::MD5.hexdigest(key +  node.content))
							          temp_concept.add_statement("rdf","type","hsdb_resource",key)
							          temp_concept.add_literal("rdfs","label","#{CGI.escape(node.content)}")
							          substance.add_relationship("hsdb_resource","has#{key}",temp_concept)
							          @outfile << temp_concept.output
						      end
						    end
						  end #enumerate
						  
						  #add all concepts to defaultGraph
						  
						  @outfile << substance.output
              @outfile << database_record.output
					end #end while
					
			#sort the file and delete duplicate lines
      puts "--->sorting file and removing duplicate lines."
      %x[sort --unique #{@outfile.path}]
