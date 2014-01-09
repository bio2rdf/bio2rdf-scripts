<?php
/**
Copyright (C) 2013 Jose Cruz-Toledo

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

/**
* NCBI RefSeq Parser
* @version 1.0
* @author Jose Cruz-Toledo
* @description 
*/

class RefSeqParser extends Bio2RDFizer{
	function __construct($argv){
		parent::__construct($argv, "refseq");
		parent::addParameter('files', true, 'all', 'all', 'files to process');
		parent::addParameter('download_url',false,null,'ftp://ftp.ncbi.nlm.nih.gov/refseq/release/complete/');
		parent::initialize();
	}//construct

	function Run(){
		$dataset_description = '';
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');

		//download
		echo "Getting FTP file list".PHP_EOL;
		$list = $this->getFtpFileList('ftp.ncbi.nlm.nih.gov', '/refseq/release/complete/','/(complete\.[0-9]+\.protein\.gpff\.gz)/');
		asort($list);
		$counter = 1;
		$total = count($list);
		foreach($list as $f){
			$lfile = $ldir.$f;
			if(!file_exists($lfile) || $this->getParameterValue('download') == true){
				$rfile = parent::getParameterValue('download_url').$f;
				echo "Downloading file ".($counter++)." out of $total : $rfile ... ".PHP_EOL;
				utils::DownloadSingle($rfile,$lfile);
			}
		}//if download
		//iterate over the files
		$files = $this->getFilePaths($ldir, 'gz');
		asort($files);
		foreach($files as $f){
			$lfile = $ldir.$f;
			$ofile = $odir.basename($f,".gz").".".parent::getParameterValue('output_format');
			$gz = (strstr(parent::getParameterValue('output_format'), "gz"))?true:false;
			parent::setWriteFile($ofile, $gz);
			parent::setReadFile($lfile, true);

			echo "processing $f ...";
			$this->process();
			echo "done!".PHP_EOL;

			$this->getReadFile()->close();
			$this->getWriteFile()->close();

			$source_file = (new DataResource($this))
				->setURI(parent::getParameterValue('download_url').$lfile)
				->setTitle("NCBI RefSeq - $f")
				->setRetrievedDate(date("Y-m-d\TG:i:s\Z", filemtime($lfile)))
				->setFormat('text/refseq-format')
				->setFormat('application/zip')
				->setPublisher('http://www.ncbi.nlm.nih.gov')
				->setHomepage('http://www.ncbi.nlm.nih.gov/refseq')
				->setRights('use')
				->setRights('attribution')
				->setLicense('http://www.nlm.nih.gov/copyright.html')
				->setDataset(parent::getDatasetURI());
			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix - $f")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/refseq/refseq.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")
				->setRights("use-share-modify")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());
			$dataset_description .= $output_file->toRDF().$source_file->toRDF();
		}//for
		$this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
		$this->getWriteFile()->write($dataset_description);
		$this->getWriteFile()->close();
	}//run


	function process(){
		$refseq_record_str = "";
		while($aLine = $this->getReadFile()->Read(4096)){
			 preg_match("/^\/\/$/", $aLine, $matches);
		    if(count($matches)){
		    	//now remove the header if it is there
		    	
		    	$refseq_record_str = $this->removeHeader($refseq_record_str);
		    	$sectionsRaw = $this->parseGenbankRaw($refseq_record_str);
		    	/**
		    	* SECTIONS being parsed:
		    	* locus, definition, accession, version, keywords, source
		    	* features
		    	**/
		    	//get the locus section
		    	$locus = $this->retrieveSections("LOCUS", $sectionsRaw);
		    	$parsed_locus_arr = $this->parseLocus($locus);
		    	//get the definition
		    	$definition = $this->retrieveSections("DEFINITION", $sectionsRaw);
		    	$parsed_definition_arr = $this->parseDefinition($definition);
		    	//get the accession
		    	$accessions = $this->retrieveSections("ACCESSION", $sectionsRaw);
		    	$parsed_accession_arr = $this->parseAccession($accessions);
		    	//get the version
		    	$versions = $this->retrieveSections("VERSION", $sectionsRaw);
		    	$parsed_version_arr = $this->parseVersion($versions);
		    	//get the keywords
		    	$keywords = $this->retrieveSections("KEYWORDS", $sectionsRaw);
		    	$parsed_keyword_arr = $this->parseKeywords($keywords);
		    	//get the reference section
		    	$references = $this->retrieveSections("REFERENCE", $sectionsRaw);
		    	$parsed_refs_arr = $this->parseReferences($references);
		    	//get the source section
		    	$source = $this->retrieveSections("SOURCE", $sectionsRaw);
		    	$parsed_source_arr = $this->parseSource($source);
		    	//get the features
		    	$features = $this->retrieveSections("FEATURES", $sectionsRaw);
		   		$parsed_features_arr = $this->parseFeatures($features);

		   		//lets make some rdf		   		
		    	$refseq_res = $this->getNamespace().$parsed_version_arr['versioned_accession'];
		    	$refseq_label = utf8_encode(htmlspecialchars($parsed_definition_arr[0]));

		    	parent::AddRDF(
		    		parent::describeIndividual($refseq_res, $refseq_label, $this->getVoc().'refseq-record').
		    		parent::triplifyString($refseq_res, $this->getVoc().'sequence-length', $parsed_locus_arr[0]['sequence_length']).
		    		parent::triplifyString($refseq_res, $this->getVoc().'chromosome-shape', $parsed_locus_arr[0]['chromosome_shape']).
		    		parent::triplifyString($refseq_res, $this->getVoc().'date-of-entry', $parsed_locus_arr[0]['date']).
					parent::triplifyString($refseq_res, $this->getVoc().'source', utf8_encode($parsed_source_arr[0])).
					parent::QQuadO_URL($refseq_res, $this->getVoc().'fasta-seq', 'https://www.ncbi.nlm.nih.gov/sviewer/viewer.cgi?sendto=on&db=nucest&dopt=fasta&val='.$parsed_version_arr['gi']).
					parent::QQuadO_URL('https://www.ncbi.nlm.nih.gov/sviewer/viewer.cgi?sendto=on&db=nucest&dopt=fasta&val='.$parsed_version_arr['gi'], "rdf:type", $this->getVoc().'fasta-sequence')
		    	);
		    	//add the features to the rdf
		    	foreach ($parsed_features_arr as $aFeature) {
					$type = $aFeature['type'];
					$feat_desc = $this->getFeatures($type);
					$label = null;
					if(isset($feat_desc['definition'])){
						$label =  preg_replace('/\s\s*/', ' ', $feat_desc['definition']);
					}
					$comment = null;
					$value = $aFeature['value'];
					$value_arr = explode("/", $value);
					$location = preg_replace('/\n/', '',$value_arr[0]);
					$class_id = parent::getVoc().md5($type);
					$feat_res = parent::getRes().md5($type.$location.$refseq_res);
					$feat_label = utf8_encode($type." ".$location." for ".$refseq_res);
					if(isset($feat_desc['comment'])){
						$comment = $feat_desc['comment'];
						$comment = preg_replace('/\s\s*/', ' ', $comment);
						$label .= " ".$comment;
					}
					parent::AddRDF(
						parent::describeClass($class_id, $label, parent::getVoc()."Feature").
						parent::describeIndividual($feat_res, $feat_label,  $class_id).
						parent::triplify($refseq_res, $this->getVoc()."has-feature", $feat_res)
					);
					foreach($value_arr as $aL){
						//check if aL has an equals in it
						$p = "/(\S+)\=(.*)/";
						preg_match($p, $aL, $m);
						if(count($m)){
							if($m[1] == "db_xref"){
								parent::AddRDF(
									parent::triplify($feat_res, "rdfs:seeAlso", str_replace("\"", "", $m[2]))
								);
							}else{
								parent::AddRDF(
									parent::triplifyString($feat_res, $this->getVoc().$m[1], utf8_encode(str_replace("\"", "", $m[2])))
								);
							}
						}
					}					
				}
				//add the accession
				foreach($parsed_accession_arr[0] as $acc ){
					parent::AddRDF(
						parent::triplifyString($refseq_res, $this->getVoc()."accession", $acc)
					);
				}
				//versioned accession
				if(isset($parsed_version_arr['versioned_accession'])){
					parent::AddRDF(
						parent::triplifyString($refseq_res, $this->getVoc()."versioned-accession", $parsed_version_arr['versioned_accession'])
					);
				}
				//keywords
				foreach($parsed_keyword_arr as $akw){
					parent::AddRDF(
						parent::triplifyString($refseq_res, $this->getVoc()."keyword", $akw)
					);
				}
				//references
				foreach($parsed_refs_arr as $aRef){
					$r = rand();
					$ref_res = $this->getRes().md5($r);
					$ref_label = "reference for ".$refseq_res;
					if(isset($aRef['TITLE'])){
						parent::AddRDF(
							parent::describeIndividual($ref_res, $ref_label, $this->getVoc()."reference").
							parent::triplifyString($ref_res, $this->getVoc()."title", $aRef['TITLE'])
						);
					}
					if(isset($aRef['PUBMED'])){
						parent::AddRDF(
							parent::triplify($ref_res, $this->getVoc()."x-pubmed", 'pubmed:'.$aRef['PUBMED'])
						);
					}
					if(isset($aRef['AUTHORS'])){
						parent::AddRDF(
							parent::triplifyString($ref_res, $this->getVoc()."authors", $aRef['AUTHORS'])
						);
					}
					if(isset($aRef['COORDINATES'])){
						parent::AddRDF(
							parent::triplify($refseq_res, $this->getVoc()."reference", $ref_res).
							parent::triplifyString($ref_res, $this->getVoc()."coordinates", $aRef['COORDINATES']).
							parent::triplifyString($ref_res, $this->getVoc()."citation", $aRef['JOURNAL'])
						);
					}else{
						parent::AddRDF(
							parent::triplify($refseq_res, $this->getVoc()."reference", $ref_res).
							parent::triplifyString($ref_res, $this->getVoc()."citation", $aRef['JOURNAL'])
						);
					}
					
				}
		    	$refseq_record_str = "";
		    	$this->WriteRDFBufferToWriteFile();
		    	continue;
		   	}
		   	preg_match("/^\n$/", $aLine, $matches);
		    if(count($matches) == 0){
		    	$refseq_record_str .= $aLine;
		    }
		}//while
	}//process


	/**
	* Get a  feature map with definition and 
	* comments (when available) for a given key. See http://www.insdc.org/documents/feature-table 
	* for reference 
	*/
	function getFeatures($aKey){
		$features = array(
			'assembly_gap' => array(
				'definition' => 'gap between two components of a CON record that is part of a genome assembly',
				'comment' => "the location span of the assembly_gap feature for an 
		      unknown gap is 100 bp, with the 100 bp indicated as
		      100 n's in sequence"
				),
			'attenuator' => array(
				'definition' => '1) region of DNA at which regulation of termination of
                         transcription occurs, which controls the expression
                         of some bacterial operons;
                      2) sequence segment located between the promoter and the
                         first structural gene that causes partial termination
                         of transcription',
				),
			'C_region' => array(
				'definition' => 'constant region of immunoglobulin light and heavy 
                      chains, and T-cell receptor alpha, beta, and gamma 
                      chains; includes one or more exons depending on the 
                      particular chain',
				),
			'CAAT_signal' => array(
				'definition' => ' CAAT box; part of a conserved sequence located about 75
                      bp up-stream of the start point of eukaryotic
                      transcription units which may be involved in RNA
                      polymerase binding; consensus=GG(C or T)CAATCT [1,2].',
				),
			'CDS' => array(
				'definition' => 'coding sequence; sequence of nucleotides that
                      corresponds with the sequence of amino acids in a
                      protein (location includes stop codon); 
                      feature includes amino acid conceptual translation.',
                'comment' => 'codon_start has valid value of 1 or 2 or 3, indicating
                      the offset at which the first complete codon of a coding
                      feature can be found, relative to the first base of
                      that feature;
                      /transl_table defines the genetic code table used if
                      other than the universal genetic code table;
                      genetic code exceptions outside the range of the specified
                      tables is reported in /transl_except qualifier;
                      /protein_id consists of a stable ID portion (3+5 format
                      with 3 position letters and 5 numbers) plus a version 
                      number after the decimal point; when the protein 
                      sequence encoded by the CDS changes, only the version 
                      number of the /protein_id value is incremented; the
                      stable part of the /protein_id remains unchanged and as 
                      a result will permanently be associated with a given 
                      protein;'
				),
			'centromere' => array(
				'definition' => 'region of biological interest identified as a centromere and
                      which has been experimentally characterized;',
                'comment' => 'the centromere feature describes the interval of DNA 
                      that corresponds to a region where chromatids are held 
                      and a kinetochore is formed'
				),
			'D-loop' => array(
				'definition' => 'displacement loop; a region within mitochondrial DNA in
                      which a short stretch of RNA is paired with one strand
                      of DNA, displacing the original partner DNA strand in
                      this region; also used to describe the displacement of a
                      region of one strand of duplex DNA by a single stranded
                      invader in the reaction catalyzed by RecA protein'
				),
			'D_segment' => array(
				'definition' => 'Diversity segment of immunoglobulin heavy chain, and 
                      T-cell receptor beta chain;'
				),
			'enhancer' => array(
				'definition' => ' a cis-acting sequence that increases the utilization of
                      (some)  eukaryotic promoters, and can function in either
                      orientation and in any location (upstream or downstream)
                      relative to the promoter;'
				),
			'exon' => array(
				'definition' => "region of genome that codes for portion of spliced mRNA, 
                      rRNA and tRNA; may contain 5'UTR, all CDSs and 3' UTR; "
				),
			'gap' => array(
				'definition' => 'gap in the sequence',
				'comment' => "the location span of the gap feature for an unknown 
                      gap is 100 bp, with the 100 bp indicated as 100 n's in 
                      the sequence.  Where estimated length is indicated by 
                      an integer, this is indicated by the same number of 
                      n's in the sequence. 
                      No upper or lower limit is set on the size of the gap."
				),
			'GC_signal' => array(
				'definition' => "GC box; a conserved GC-rich region located upstream of
                      the start point of eukaryotic transcription units which
                      may occur in multiple copies or in either orientation;
                      consensus=GGGCGG;"
				),
			'gene' => array(
				'definition' => 'region of biological interest identified as a gene 
                      and for which a name has been assigned;',
                'comment' => "the gene feature describes the interval of DNA that 
                      corresponds to a genetic trait or phenotype; the feature is,
                      by definition, not strictly bound to it's positions at the 
                      ends;  it is meant to represent a region where the gene is 
                      located."
				),
			'iDNA' => array(
				'definition' => "intervening DNA; DNA which is eliminated through any of
                      several kinds of recombination;",
                'comment' => 'e.g., in the somatic processing of immunoglobulin genes.'
				),
			'intron' => array(
				'definition' => "a segment of DNA that is transcribed, but removed from
                      within the transcript by splicing together the sequences
                      (exons) on either side of it;"
				),
			'J_segment' => array(
				'definition' => "joining segment of immunoglobulin light and heavy 
                      chains, and T-cell receptor alpha, beta, and gamma 
                      chains;"
				),
			'LTR' => array(
				'definition' => "long terminal repeat, a sequence directly repeated at
                      both ends of a defined sequence, of the sort typically
                      found in retroviruses;"
				),
			'mat_peptide' => array(
				'definition' => " mature peptide or protein coding sequence; coding
                      sequence for the mature or final peptide or protein
                      product following post-translational modification; the
                      location does not include the stop codon (unlike the
                      corresponding CDS);"
				),
			'misc_binding' => array(
				'definition' => "site in nucleic acid which covalently or non-covalently
                      binds another moiety that cannot be described by any
                      other binding key (primer_bind or protein_bind);",
                'comment' => 'note that the key RBS is used for ribosome binding sites'
				),
			'misc_difference' => array(
				'definition' => "feature sequence is different from that presented 
                      in the entry and cannot be described by any other 
                      Difference key (unsure, old_sequence, 
                      variation, or modified_base);",
				'comment' => "the misc_difference feature key should be used to 
                      describe variability that arises as a result of 
                      genetic manipulation (e.g. site directed mutagenesis);
                      use /replace=\"\" to annotate deletion, e.g. 
                      misc_difference 412..433
                                      /replace=\"\""
				),
			'misc_feature' => array(
				'definition' => "region of biological interest which cannot be described
                      by any other feature key; a new or rare feature;",
                'comment' => "this key should not be used when the need is merely to 
                      mark a region in order to comment on it or to use it in 
                      another feature's location"
				),
			'-35_signal' => array(
				'definition' => "a conserved hexamer about 35 bp upstream of the start
                      point of bacterial transcription units; consensus=TTGACa
                      or TGTTGACA;"
				),
			'-10_signal' => array(
				'definition' => "Pribnow box; a conserved region about 10 bp upstream of
                      the start point of bacterial transcription units which
                      may be involved in binding RNA polymerase;
                      consensus=TAtAaT [1,2,3,4];"
				),
			"5'UTR" => array(
				'definition' => "region at the 5' end of a mature transcript (preceding 
                      the initiation codon) that is not translated into a 
                      protein;"
				),
			"3'UTR" => array(
				'definition' => "region at the 3' end of a mature transcript (following 
                      the stop codon) that is not translated into a protein;"
				),
			'V_segment' => array(
				'definition' => "variable segment of immunoglobulin light and heavy
                      chains, and T-cell receptor alpha, beta, and gamma
                      chains; codes for most of the variable region (V_region)
                      and the last few amino acids of the leader peptide;",
				),
			'variation' => array(
				'definition' => "a related strain contains stable mutations from the same
                      gene (e.g., RFLPs, polymorphisms, etc.) which differ
                      from the presented sequence at this location (and
                      possibly others);",
				'comment' => "used to describe alleles, RFLP's,and other naturally occurring 
                      mutations and  polymorphisms; variability arising as a result 
                      of genetic manipulation (e.g. site directed mutagenesis) should 
                      be described with the misc_difference feature;
                      use /replace=\"\" to annotate deletion, e.g. 
                      variation   4..5
                                  /replace=\"\"  "
				),
			'V_region' => array(
				'definition' => "variable region of immunoglobulin light and heavy
                      chains, and T-cell receptor alpha, beta, and gamma
                      chains;  codes for the variable amino terminal portion;
                      can be composed of V_segments, D_segments, N_regions,
                      and J_segments;"
				),
			'unsure' => array(
				'definition' => "author is unsure of exact sequence in this region;",
				'comment' => " use /replace=\"\" to annotate deletion, e.g. 
                      Unsure      11..15
                                  /replace=\"\""
				),
			'tRNA' => array(
				'definition' => " mature transfer RNA, a small RNA molecule (75-85 bases
                      long) that mediates the translation of a nucleic acid
                      sequence into an amino acid sequence;"
				),
			'transit_peptide' => array(
				'definition' => "transit peptide coding sequence; coding sequence for an
                      N-terminal domain of a nuclear-encoded organellar
                      protein; this domain is involved in post-translational
                      import of the protein into the organelle;"
				),
			'tmRNA' => array(
				'definition' => "transfer messenger RNA; tmRNA acts as a tRNA first,
                      and then as an mRNA that encodes a peptide tag; the
                      ribosome translates this mRNA region of tmRNA and attaches
                      the encoded peptide tag to the C-terminus of the
                      unfinished protein; this attached tag targets the protein for
                      destruction or proteolysis;"
				),
			'telomere' => array(
				'definition' => "region of biological interest identified as a telomere 
                      and which has been experimentally characterized;",
                'comment' => 'the telomere feature describes the interval of DNA 
                      that corresponds to a specific structure at the end of   
                      the linear eukaryotic chromosome which is required for                
		      the integrity and maintenance of the end; this region
                      is unique compared to the rest of the chromosome and 
                      represent the physical end of the chromosome;'
				),
			'terminator' => array(
				'definition' => "sequence of DNA located either at the end of the
                      transcript that causes RNA polymerase to terminate 
                      transcription;"
				),
			'TATA_signal' => array(
				'definition' => "TATA box; Goldberg-Hogness box; a conserved AT-rich
                      septamer found about 25 bp before the start point of
                      each eukaryotic RNA polymerase II transcript unit which
                      may be involved in positioning the enzyme  for correct 
                      initiation; consensus=TATA(A or T)A(A or T) [1,2];"
				),
			'STS' => array(
				'definition' => "sequence tagged site; short, single-copy DNA sequence
                      that characterizes a mapping landmark on the genome and
                      can be detected by PCR; a region of the genome can be
                      mapped by determining the order of a series of STSs;",
                'comment' => "STS location to include primer(s) in primer_bind key or
                      primers."
				),
			'stem_loop' => array(
				'definition' => "hairpin; a double-helical region formed by base-pairing
                      between adjacent (inverted) complementary sequences in a
                      single strand of RNA or DNA."
				),
			'source' => array(
				'definition' => "identifies the biological source of the specified span of
                      the sequence; this key is mandatory; more than one source
                      key per sequence is allowed; every entry/record will have, as a
                      minimum, either a single source key spanning the entire
                      sequence or multiple source keys, which together, span the
                      entire sequence.",
                'comment' => "transgenic sequences must have at least two source feature
                      keys; in a transgenic sequence the source feature key
                      describing the organism that is the recipient of the DNA
                      must span the entire sequence;
                      see Appendix IV /organelle for a list of <organelle_value>"
				),
			'S_region' => array(
				'definition' => "switch region of immunoglobulin heavy chains;  
                      involved in the rearrangement of heavy chain DNA leading 
                      to the expression of a different immunoglobulin class 
                      from the same B-cell;"
				),
			'sig_peptide' => array(
				'definition' => "signal peptide coding sequence; coding sequence for an
                      N-terminal domain of a secreted protein; this domain is
                      involved in attaching nascent polypeptide to the
                      membrane leader sequence;"
				),
			'rRNA' => array(
				'definition' => "mature ribosomal RNA; RNA component of the
                      ribonucleoprotein particle (ribosome) which assembles
                      amino acids into proteins.",
                'comment' => "rRNA sizes should be annotated with the /product
                      Qualifier"
				),
			'rep_origin' => array(
				'definition' => "origin of replication; starting site for duplication of
                      nucleic acid to give two identical copies; ",
                'comment' => "/direction has valid values: RIGHT, LEFT, or BOTH."
				),
			'repeat_region' => array(
				'definition' => "region of genome containing repeating units;"
				),
			'RBS' => array(
				'definition' => " ribosome binding site;",
				'comment' => 'in prokaryotes, known as the Shine-Dalgarno sequence: is
                      located 5 to 9 bases upstream of the initiation codon;
                      consensus GGAGGT [1,2].'
				),
			'protein_bind' => array(
				'definition' => "non-covalent protein binding site on nucleic acid;",
				'comment' => "note that RBS is used for ribosome binding sites."
				),
			'promoter' => array(
				'definition' => "region on a DNA molecule involved in RNA polymerase
                      binding to initiate transcription;"
				),
			'primer_bind' => array(
				'definition' => "non-covalent primer binding site for initiation of
                      replication, transcription, or reverse transcription;
                      includes site(s) for synthetic e.g., PCR primer elements;",
                'comment' => " used to annotate the site on a given sequence to which a primer 
                      molecule binds - not intended to represent the sequence of the
                      primer molecule itself; PCR components and reaction times may 
                      be stored under the \"\/PCR_conditions\" qualifier; 
                      since PCR reactions most often involve pairs of primers,
                      a single primer_bind key may use the order() operator
                      with two locations, or a pair of primer_bind keys may be
                      used."
				),
			'prim_transcript' => array(
				'definition' => "primary (initial, unprocessed) transcript;  includes 5'
                      untranslated region (5'UTR), coding sequences
                      (CDS, exon), intervening sequences (intron) and 3'
                      untranslated region (3'UTR);"
				),
			'precursor_RNA' => array(
				'definition' => "any RNA species that is not yet the mature RNA product;
                      may include 5' untranslated region (5'UTR), coding
                      sequences (CDS, exon), intervening sequences (intron)
                      and 3' untranslated region (3'UTR);",
                'comment' => 'used for RNA which may be the result of 
                      post-transcriptional processing;  if the RNA in question 
                      is known not to have been processed, use the 
                      prim_transcript key.'
				),
			'polyA_site' => array(
				'definition' => "site on an RNA transcript to which will be added adenine
                      residues by post-transcriptional polyadenylation;"
				),
			'misc_recomb' => array(
				'definition' => "site of any generalized, site-specific or replicative
                      recombination event where there is a breakage and
                      reunion of duplex DNA that cannot be described by other
                      recombination keys or qualifiers of source key 
                      (/proviral);"
				),
			'misc_RNA' => array(
				'definition' => "any transcript or RNA product that cannot be defined by
                      other RNA keys (prim_transcript, precursor_RNA, mRNA,
                      5'UTR, 3'UTR, exon, CDS, sig_peptide, transit_peptide,
                      mat_peptide, intron, polyA_site, ncRNA, rRNA and tRNA);"
				),
			'misc_signal' => array(
				'definition' => "any region containing a signal controlling or altering
                      gene function or expression that cannot be described by
                      other signal keys (promoter, CAAT_signal, TATA_signal,
                      -35_signal, -10_signal, GC_signal, RBS, polyA_signal,
                      enhancer, attenuator, terminator, and rep_origin)."
				),
			'misc_structure' => array(
				'definition' => "any secondary or tertiary nucleotide structure or 
                      conformation that cannot be described by other Structure
                      keys (stem_loop and D-loop);"
				),
			'mobile_element' => array(
				'definition' => "region of genome containing mobile elements;"
				),
			'modified_base' => array(
				'definition' => "the indicated nucleotide is a modified nucleotide and
                      should be substituted for by the indicated molecule
                      (given in the mod_base qualifier value)",
                'comment' => 'value is limited to the restricted vocabulary for 
                      modified base abbreviations;'
				),
			'mRNA' => array(
				'definition' => "messenger RNA; includes 5'untranslated region (5'UTR),
                      coding sequences (CDS, exon) and 3'untranslated region
                      (3'UTR);"
				),
			'ncRNA' => array(
				'definition' => "a non-protein-coding gene, other than ribosomal RNA and
                      transfer RNA, the functional molecule of which is the RNA
                      transcript;",
                'comment' => 'the ncRNA feature is not used for ribosomal and transfer
                      RNA annotation, for which the rRNA and tRNA feature keys
                      should be used, respectively;'
				),
			'N_region' => array(
				'definition' => "extra nucleotides inserted between rearranged 
                      immunoglobulin segments."
				),
			'old_sequence' => array(
				'definition' => "the presented sequence revises a previous version of the
                      sequence at this location;",
                'comment' => "/replace=\"\" is used to annotate deletion, e.g. 
                      old_sequence 12..15
                      /replace=\"\" 
                      NOTE: This feature key is not valid in entries/records
                      created from 15-Oct-2007."
				),
			'polyA_signal' => array(
				'definition' => "recognition region necessary for endonuclease cleavage
                      of an RNA transcript that is followed by polyadenylation;
                      consensus=AATAAA [1];"
				),
			'operon' => array(
				'definition' => "region containing polycistronic transcript including a cluster of
                      genes that are under the control of the same regulatory sequences/promotor
                      and in the same biological pathway"
				),
			'oriT' => array(
				'definition' => "rigin of transfer; region of a DNA molecule where transfer is
                      initiated during the process of conjugation or mobilization",
                'comment' => "rep_origin should be used for origins of replication; 
                      /direction has legal values RIGHT, LEFT and BOTH, however only                
                      RIGHT and LEFT are valid when used in conjunction with the oriT  
                      feature;
                      origins of transfer can be present in the chromosome; 
                      plasmids can contain multiple origins of transfer"
				),
		);
		if(strlen($aKey)){
			if(array_key_exists($aKey, $features)){
				return $features[$aKey];
			}else{
				trigger_error("Could not find key: ".$aKey."\n", E_USER_NOTICE);
			}
		}else{
			trigger_error("Invalid key: ".$key."\n", E_USER_ERROR);
			exit;
		}
		return $features;
	}

	/**
	* Parse the features section of genbank documents according to:
	* http://www.insdc.org/documents/feature-table
	*/
	function parseFeatures($feature_arr){

		$out = array();
		//get a copy of the features array 
		foreach($feature_arr as $feat){
			$feature_raw = utf8_encode(trim($feat['value']));

			$arr = explode("\n", $feature_raw);
			$count = 0;
			foreach($arr as $aLine){
				$p1 = "/^\s{5}(\S+)\s+(.*)/";
				$p2 = "/^\s{20,}(.*)/";
				preg_match($p1, $aLine, $m1);
				preg_match($p2, $aLine, $m2);
				if(count($m1)){
					$out[$count] = array('type'=> $m1[1], 'value'=>$m1[2]);
					$count ++;
					continue;
				}
				if(count($m2)){
					$value = $out[$count-1]['value'];
					$out[$count-1]['value'] = $value.PHP_EOL.$m2[1];
				}
			}
		}
		return $out;
	}
	/**
	* Parse the reference section according to section 3.4.11 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseReferences($ref_arr){
		$rm = array();
		$reference_fields = array("AUTHORS", "TITLE", "JOURNAL", "MEDLINE", "PUBMED", "REMARK");
		foreach($ref_arr as $reference){
			$ref_raw = utf8_encode(trim($reference['value']));
			if(strlen($ref_raw)){

				$ref_raw = utf8_encode(trim(preg_replace('/\s\s+/', ' ', $ref_raw)));
				$regex_string = "(.*)";
				$regex_groups = array("COORDINATES");
				//construct regular expression based on the fields in this reference
				foreach($reference_fields as $field){
					if(strpos($ref_raw, $field)){
						$regex_string .= "\s+".$field."\s+(.*)";
						$regex_groups[] = $field;
					}
				}
				$regex = "/".$regex_string."/";
				//search with constructed regex
				preg_match($regex, $ref_raw, $matches);
				$tmp_ref = array();
				//get output of preg_match search
				if(count($matches)){
					foreach($regex_groups as $i => $field){
						if($field == "COORDINATES"){
							$tmp_coord = $matches[$i+1];
							preg_match('/.*\((.*)\)/', $tmp_coord, $matchesc);
							if(count($matchesc) && isset($matchesc[1])){							
								$tmp_ref[$field] = $matchesc[1];
							}
						} else {
							$tmp_ref[$field] = $matches[$i+1];
						}
					}
					$rm[] = $tmp_ref;
				}
			} else {
				trigger_error("Empty reference line!", E_USER_ERROR);
				exit;
			}
		}

		return $rm;
	}

	/**
	* Parse the source section according to section 3.4.10 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseSource($source_arr){
		$rm = array();
		foreach($source_arr as $source){
			$source_raw = utf8_encode(trim($source['value']));
			if(strlen($source_raw)){
				$s_arr = preg_split('/\s+ORGANISM/', $source_raw);
				if(strlen($s_arr[0])){
					$rm[] = $s_arr[0];
				}
			}else{
				trigger_error("Empty source line!", E_USER_ERROR);
				exit;
			}
		}
		return $rm;
	}

	/**
	* Parse the Keyword section according to section 3.4.8 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/

	function parseKeywords($keywords_arr){
		$rm = array();
		foreach($keywords_arr as $keywords){
			$keywords_raw = utf8_encode(trim($keywords['value']));
			if(strlen($keywords_raw)){
				//remove the periods
				$kw_no_dots = str_replace('.', '', $keywords_raw);
				$kw_arr = explode(';',$kw_no_dots);
				$tmp_keywords = array();
				foreach ($kw_arr as $aKw) {
					$tmp_keywords[] = trim($aKw);
				}
				$rm = $tmp_keywords;
			}else{
				trigger_error("Empty keywords line!", E_USER_ERROR);
				exit;
			}
		}
		return $rm;
	}

	/**
	* Parse the Version section according to section 3.4.7 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseVersion($version_arr){
		$rm = array();

		foreach($version_arr as $version){
			$version_raw = utf8_encode(trim($version['value']));
			if(strlen($version_raw)){
				$version_split = preg_split('/\s+/', $version_raw);
				$tmp_version = array();
				if(count($version_split)){
					$tmp_version['versioned_accession'] = $version_split[0];
					$tmp_version['gi'] = substr($version_split[1], 3);
				}
				$rm = $tmp_version;
			}else{
				trigger_error("Empty version line!", E_USER_ERROR);
				exit;
			}
		}
		
		return $rm;
	}	

	/**
	* This method parses the definition line according to section 3.4.6 of
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseAccession($accessions){
		$rm = array();
		foreach($accessions as $accession){
			$acc_raw = trim($accession['value']);
			if(strlen($acc_raw)){
				$acc_raw = utf8_encode(trim(preg_replace('/\s\s+/', ' ', $acc_raw)));
				//now split by spaces
				$acc_arr = explode(' ', $acc_raw);
				$tmp_accs = array();
				foreach($acc_arr as $anAcc){
					$tmp_accs[] = $anAcc;
				}
				$rm[] = $tmp_accs;
			}else{
				trigger_error("Empty acccession line!", E_USER_ERROR);
				exit;
			}
		}
		
		return $rm;
	}

	/**
	* Parse the definition line
	*/
	function parseDefinition($definition_array){
		$rm = array();
		foreach($definition_array as $definition){
			$def_raw = trim($definition['value']);
			if(strlen($def_raw)){
				$rm[] = utf8_encode(trim(preg_replace('/\s\s+/', ' ', $def_raw)));
			}else{
				trigger_error("Empty definition line !", E_USER_ERROR);
				exit;
			}
		}
		
		return $rm;
	}
	/**
	* This method parses the locus line 
	*/
	function parseLocus($locus_array){
		$rm = array();
		foreach($locus_array as $locus){
			$intervals = array(16,1,11,1,2,1,3,6,2,8,1,3,1,11);
			$locus_raw = trim($locus['value']);
			if(strlen($locus_raw)){
				$start = 0;
				$parts = array();
				foreach($intervals as $i){
					$parts[] = mb_substr($locus_raw,$start, $i);
					$start += $i;
				}
				$d = date_parse(trim($parts[13]));
				$locus_details = array();
				$locus_details['locus_name'] = trim($parts[0]);
				$locus_details['sequence_length'] = trim($parts[2]);
				$locus_details['chromosome_shape'] = trim($parts[9]);
				$locus_details['date'] = $d['year'].'-'.$d['month'].'-'.$d['day'];

				$rm[] = $locus_details;
			}else{
				trigger_error("Empty locus line !", E_USER_ERROR);
				exit;
			}
		}
		return $rm;
	}
	/**
	* Parse $gb_record_sections and return an array containing
	* the sections of type $aSectionType
	*/
	function retrieveSections($aSectionType, $gb_record_sections){
		$rm = array();
		if(strlen($aSectionType)){
			foreach ($gb_record_sections as $section) {
				if($section['type'] == $aSectionType){
					$rm[] = $section;
				}
			}
		} else {
			trigger_error("Section type not provided!", E_USER_ERROR);
			exit;
		}
		
		return $rm;
	}

	/**
	* Pass in a text file containing multiple GB records
	* returns an array with one genbank record per elment
	* it removes the header at the top of the file
	*/
	function removeHeader($aGbRecord){
		$gb_arr = split("\n", $aGbRecord);
		for($i=0;$i<count($gb_arr);$i++){
			preg_match("/^LOCUS/", $gb_arr[$i], $matches);
			if(count($matches)){
				if($i == 0){
					//locus is the first line everything is ok
					return $aGbRecord;
				}else{
					$arr = array_slice($gb_arr, $i);
					return implode("\n", $arr);
				}
			}
		}
	}

	/**
	* This function separates the genbank record into its sections.
	* ftp://ftp.ncbi.nlm.nih.gov/genbank/gbrel.txt
	*/
	function parseGenbankRaw($gb_record){
		$sections = array();
		$gb_arr = split("\n", $gb_record);
		$aSection = "";
		$section_name = "";
		$record_counter = 0;
		for ($i=0; $i < count($gb_arr); $i++) { 
			if(preg_match('/^(\w+)(.*)/', $gb_arr[$i], $matches) == 1){
				if(count($matches)){
					$type = $matches[1];
					$value = $matches[2];
					$sections[$record_counter]['type'] = $type;
					$sections[$record_counter]['value'] = $value.PHP_EOL;
				}
				$record_counter++;
			} else {
				preg_match('/^(\s+)(.*)/', $gb_arr[$i], $matches);
				if(count($matches)){
					if(array_key_exists($record_counter-1, $sections)){
						$sections[$record_counter-1]['value'] .= $matches[0].PHP_EOL;	
					}
				}
			}
		}//for 
		return $sections;
	}


	/**
	* return an array of paths to the files with extension $ext found in $dir
	*/
	function getFilePaths($dir, $ext){
		$rm = array();
		if($h = opendir($dir)){
			while(false !== ($file = readdir($h))){
				if($file != '.' && $file != '..' && strtolower(substr($file, strrpos($file, '.')+1)) == $ext){
					$rm [] = $file;
				}
			}
		}else{
			trigger_error("Could not open directory ".$dir);
			exit;
		}
		return $rm;
	}

	/**
	* Given an FTP uri get a non recursive list of all files of a given extension
	* located inside a given path
	*/
	function getFtpFileList($ftp_uri, $path, $regex){
		$rm = array();
		// set up basic connection
		$conn_id = ftp_connect($ftp_uri);
		$ftp_user = 'anonymous';
		if (@ftp_login($conn_id, $ftp_user, '')) {
		} else {
		    echo "Couldn't connect as $ftp_user\n";
		    exit;
		}
	 
		// get contents of the current directory
		$contents = ftp_nlist($conn_id, $path);
		foreach($contents as $aFile){
//			$reg_exp = "/.*\/(.*".$extension.")/";
			preg_match($regex, $aFile, $matches);
			if(count($matches)){
				$rm[] = $matches[1];
			}
		}
		return $rm;
	}	
}//class


?>
