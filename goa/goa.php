<?php

/**
Copyright (C) 2012 Alison Callahan, Jose Cruz-Toledo, Michel Dumontier

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
 * An RDF generator for GOA (http://www.ebi.ac.uk/GOA/)
 * @version 2.0
 * @author Alison Callahan
 * @author Jose Cruz-Toledo
 * @author Michel Dumontier
*/

require_once(__DIR__.'/../../php-lib/rdfapi.php');

class GOAParser extends Bio2RDFizer 
{
	function __construct($argv) {
		parent::__construct($argv,"goa");
//		parent::addParameter('files',true,'all|arabidopsis|chicken|cow|dicty|dog|fly|human|mouse|pdb|pig|rat|uniprot|worm|yeast|zebrafish','all','all or comma-separated list of files to process');
		parent::addParameter('files',true,'all|arabidopsis|chicken|cow|dicty|dog|fly|human|mouse|pig|rat|worm|yeast|zebrafish','all','all or comma-separated list of files to process');
		parent::addParameter('download_url',false,null,'ftp://ftp.ebi.ac.uk/pub/databases/GO/goa/');		
		parent::initialize();
	}

	function run(){
		if(parent::getParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",parent::getParameterValue('files'));
		}		
		
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$rdir = parent::getParameterValue('download_url');
		$dataset_description = '';
		
		foreach($files as $file){
			$download = parent::getParameterValue('download');
			$basefile = "goa_".$file.".gaf";
			$lfile = $ldir."/".$basefile.".gz";
			if(!file_exists($lfile) && $download == false) {
				trigger_error($lfile." not found. Will attempt to download.", E_USER_NOTICE);
				$download = true;
			}

			//download file
			$rfile = $rdir.strtoupper($file)."/goa_".$file.".gaf.gz";
			if($download == true) {
				echo "downloading $file ... ";
				utils::DownloadSingle($rfile,$lfile);
			}

			$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
			$ofile = "bio2rdf-goa-".$file.".".parent::getParameterValue('output_format'); 	
		
			parent::setReadFile($lfile, TRUE);
			parent::setWriteFile($odir.$ofile, $gz);
			
			echo "processing $file ... ";
			$this->process($file);
			echo "done!";
			parent::clear();

			//close write file
			parent::getWriteFile()->close();
			echo PHP_EOL;
			
			// dataset description
			$graph_uri = parent::getGraphURI();
			if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());
			
			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("Gene Ontology Annotation file $file ($rfile")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
				->setFormat("text/tab-separated-value")
				->setFormat("application/gzip")	
				->setPublisher("http://www.ebi.ac.uk/")
				->setHomepage("http://www.ebi.ac.uk/GOA/")
				->setRights("use")
				->setLicense("http://www.ebi.ac.uk/GOA/goaHelp.html")
				->setDataset("http://identifiers.org/goa/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/irefindex/irefindex.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")			
				->setRights("use-share-modify")
				->setRights("by-attribution")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons.org/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());

			if($gz) $output_file->setFormat("application/gzip");
			if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
			else $output_file->setFormat("application/n-quads");
			
			$dataset_description .= $source_file->toRDF().$output_file->toRDF();
			parent::setGraphURI($graph_uri);
		}
		
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		
		echo "done!".PHP_EOL;
	}

	function process($file){
		$z = 1;
		while($l = parent::getReadFile()->read(100000)) {
			if($z % 100000 == 0) parent::clear();
			if($l[0] == "!") continue;
			$fields = explode("\t",$l);
			if(count($fields) != 17){
				trigger_error("Expected 17 columns, but found ".count($fields),E_USER_ERROR);
				return false;
			}
			#print_r($fields); exit;
			//get the Go id
			$db = $fields[0];
			$id = $fields[1];
			$symbol = $fields[2];
			$qualifier = $fields[3];
			$goid = substr($fields[4],3);
			$refs = $this->getDbReferences($fields[5]);
			$eco = $this->getEvidenceCodeLabelArr($fields[6]);
			if($eco === null) {
				print_r($fields[6]);exit;
			}
			$aspect = $this->getAspect($fields[8]);
			$label = $fields[9];
			$synonyms =  explode("|", $fields[10]);
			$taxid = $fields[12];
			$date = $this->parseDate($fields[13]);	
			$assignedBy = $fields[14];

			//entity id
			$eid = $this->getdbURI($db,$id);
			parent::addRDF(
				parent::describeIndividual($eid,$label,parent::getVoc()."GO-Annotation").
 				parent::describeClass(parent::getVoc()."GO-Annotation","GO Annotation").
				parent::triplifyString($eid,parent::getVoc()."symbol",$symbol)
			);
			parent::addRDF(
				parent::triplify($eid,parent::getVoc()."x-taxonomy",$taxid)
			);
			
			foreach($synonyms as $s){
				if(!empty($s)){
					parent::addRDF(
						parent::triplifyString($eid, parent::getVoc()."synonym", $s)
					);
				}
			}

			$rel = $aspect;
			if($qualifier == 'NOT') {
				if($aspect == 'process') $rel = 'not-in-process';	
				if($aspect == 'function') $rel = 'not-has-function';
				if($aspect == 'component') $rel = 'not-in-component';	
			}

			parent::addRDF(
				parent::describeObjectProperty(parent::getVoc().$rel,str_replace("-"," ",$rel)).
				parent::triplify($eid, parent::getVoc().$rel, "go:".$goid)					
			);

			$aid = parent::getRes().$file."_".($z++);
			parent::addRDF(
				parent::describeObjectProperty(parent::getVoc()."go-annotation","GO annotation").
				parent::triplify($eid, parent::getVoc()."go-annotation", $aid)
			);
				
			$cat = parent::getRes().md5($aspect);
			
			parent::addRDF(
				parent::describeIndividual($aid, "$id-go:$goid association", parent::getVoc()."GO-Annotation").
				parent::triplify($aid, parent::getVoc()."target", $eid).
				parent::triplify($aid, parent::getVoc()."go-term", "go:".$goid).
				parent::triplify($aid, parent::getVoc()."evidence", "eco:".$eco[1]).
				parent::triplify($aid, parent::getVoc()."go-category", $cat).
				parent::describeClass($cat,$aspect).
				parent::triplifyString($aid, parent::getVoc()."assigned-by", $assignedBy)
			);
			if($date != '') {
				parent::addRDF(
					parent::triplifyString($aid, parent::getVoc()."entry-date", $date."T00:00:00Z", "xsd:dateTime")
				);
			}
			foreach($refs as $ref){
				$b = explode(":",$ref);
				if($b[0] == 'PMID') {
					parent::addRDF(
						parent::triplify($aid, parent::getVoc()."article", "pubmed:".$b[1])
					);
				}
			}

			//write RDF to file
			parent::writeRDFBufferToWriteFile();
		}
	}


	function getAspect($anAspect){
		if($anAspect == "F"){
			return "function";
		}elseif($anAspect == "P"){
			return "process";
		}elseif($anAspect == "C"){
			return "component";
		}
	}

	function getDbReferences($aDbReference){
		$a = explode("|",$aDbReference);
		return $a;
	}

	/**
	 * This function returns the corresponding 
	 * bio2rdf URI for the GOA entry for the given
	 * db and db_id
	 **/
	function getdbURI($db_id, $db_object_id){
		$returnMe = "";
		if($db_id == "UniProtKB"){
			$returnMe = "uniprot:".$db_object_id;
		} else if ($db_id == "PDB"){
			$split_object = explode("_", $db_object_id);
			$returnMe = "pdb:".$split_object[0]."/chain_".$split_object[1];
		} else {
			$returnMe = $db_id.":".$db_object_id;
		}
		return $returnMe;
	}

	/**
 	* This function return an array that has as a key
	 * the name of the category to which the evidence code belongs
	 * to and as a value the label for the code.
	 * For example the evidence code "EXP" will return:
	 * ["Experimental Evidence Code" => ["Inferred from Experiment", "0000006"]]
	 * See: http://www.geneontology.org/GO.evidence.shtml
	 **/
	function getEvidenceCodeLabelArr($aec){
		//experimental code
		$ec = array(
			"EXP"=> array("Inferred from Experiment","0000006"),
			"IDA"=> array("Inferred from Direct Assay","0000314"),
			"IPI"=> array("Inferred from Physical Interaction","0000021"),
			"IMP"=> array("Inferred from Mutant Phenotype", "0000315"),
			"IGI"=> array("Inferred from Genetic Interaction","0000316"),
			"IEP"=> array("Inferred from Expression Pattern", "0000008"),
			"ISS"=> array("Inferred from Sequence or Structural Similarity","0000027"),
			"ISO"=> array("Inferred from Sequence Orthology", "0000201"),
			"ISA"=> array("Inferred from Sequence Alignment", "0000200"),
			"ISM"=> array("Inferred from Sequence Model", "0000202"),
			"IGC"=> array("Inferred from Genomic Context", "0000317"),
			"IBA"=> array("Inferred from Biological aspect of Ancestor","0000318"),
			"IBD"=> array("Inferred from Biological aspect of Desendant", "0000319"),
			"IKR"=> array("Inferred from Key Residues","0000320"),
			"IRD"=> array("Inferred from Rapid Divergence","0000321"),
			"RCA"=> array("Inferred from Reviewed Computational Analysis","0000245"),
			"TAS"=> array("Traceable Author Statement","0000304"),
			"NAS"=> array("Non-Traceable Author Statement","0000303"),
			"IC"=> array("Inferred by Curator","0000001"),
			"ND"=> array("No biological Data available","0000035"),
			"IEA"=>array("Inferred from Electronic Annotation", "0000203"),
			"HTP" => array("Inferred from High Throughput Experiment",""),
			"HDA" => array("Inferred from High Throughput Direct Assay",""),
			"HMP" => array("Inferred from Hight Throughput Mutant Phenotype",""),
			"HGI" => array("Inferred from High Throughput Genetic Interaction",""),
			"HEP" => array("Inferred from High Throughput Expression Pattern","")
		);			
		if(isset($ec[$aec])) return $ec[$aec];
		else {
			trigger_error("No code for $aec");
			return null;
		}
		
		return null;
	}

	function parseDate($str){
		$year = substr($str, 0, 4);
		$month = substr($str, 4, 2);
		$day = substr($str, 6, 2);
		return "$year-$month-$day";
	}
}

?>
