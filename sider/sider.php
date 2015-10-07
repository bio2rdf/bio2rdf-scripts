<?php
/**
Copyright (C) 2011-2013 Michel Dumontier

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
 * An RDF generator for SIDER
 * documentation: http://sideeffects.embl.de/media/download/README
 * @version 2.0
 * @author Michel Dumontier
 * @author Alison Callahan
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class SIDERParser extends Bio2RDFizer 
{

	function __construct($argv) {
		parent::__construct($argv, "sider");
		
		// set and print application parameters
		parent::addParameter('files',true,'all|indications|se|freq','all','all or comma-separated list of ontology short names to process');
		parent::addParameter('download_url',false,null,'http://sideeffects.embl.de/media/download/');
		
		parent::initialize();
	}

	function run() {
		$idir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$files = parent::getParameterValue('files');
		$dataset_description = '';

		if($files == 'all') {
			$files = explode('|', parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(',', parent::getParameterValue('files'));
		}
		
		foreach($files AS $file) {
			$f = $file;
			if($file != "freq") $f = "all_".$file; 
			$f = "meddra_".$f.".tsv.gz";
			$lfile = $idir.$f;
			$rfile = parent::getParameterValue('download_url').$f;
			if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
				echo "downloading $file... ";
				$ret = file_get_contents($rfile);
				if($ret === FALSE) {
					trigger_error("Unable to get $rfile",E_USER_WARNING);
					continue;
				}
				$ret = file_put_contents($lfile,$ret);
				if($ret === FALSE) {
					trigger_error("Unable to write $lfile",E_USER_ERROR);
					exit;
				}		
				echo "done!".PHP_EOL;
			}

			echo "Processing $f... ";
			parent::setReadFile($lfile,true);	
			
			$suffix = parent::getParameterValue('output_format');
			$ofile = "sider-".$file.'.'.$suffix; 
			$gz = false;
			
			if(strstr(parent::getParameterValue('output_format'), "gz")) $gz = true;

			parent::setWriteFile($odir.$ofile, $gz);
			$this->$file();
			parent::getWriteFile()->Close();
			parent::getReadFile()->Close();
			echo "done!".PHP_EOL;

			echo "Generating dataset description... ";

			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("SIDER Side Effect resource ($file.tsv.gz")
				->setRetrievedDate( parent::getDate(filemtime($lfile)))
				->setFormat("text/tab-separated-value")
				->setFormat("application/gzip")	
				->setPublisher("http://sideeffects.embl.de/")
				->setHomepage("http://sideeffects.embl.de/")
				->setRights("use-share-modify")
				->setLicense("http://creativecommons.org/licenses/by-nc-sa/3.0/")
				->setDataset("http://identifiers.org/sider.effect/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = parent::getDate(filemtime($odir.$ofile));
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2df.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix - $file")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/sider/sider.php")
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
		}//foreach

		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		echo "done!".PHP_EOL;
	}

	function GetPCFromFlat($id)
	{
		return ltrim(abs($id)-100000000, "0");
	}
	function GetPCFromStereo($id)
	{
		return ltrim(abs($id),"0");
	}
	
	/*
	Medical concepts are extracted both from the adverse effects and the indications sections of the drug labels.
	Terms that contain in the indication section are then removed from the adverse effect section. For example,
	the indications for an anti-depressant might contain "depression", but also the adverse effect section (e.g.
	"in clinical trials to treat depression ..."). 

	Format: label identifier, concept id, name of side effect (as found on the label)
	*/

	function se()
	{
		$declared = null;

		parent::setCheckpoint('file');
		while($l = $this->getReadFile()->Read()) {
			$a = explode("\t",$l);
			if(count($a) != 6) {
				trigger_error("Expecting 6 columns, found ".count($a)." instead.", E_USER_ERROR);
				exit;
			}
			$stitch_flat = "stitch:".$a[0];
			$stitch_stereo = "stitch:".$a[1];
			$cui = "umls:".$a[2];
			$term_type = $a[3];
			$term_type_cui = $a[4];
			$term_type_label = $a[5];

			if($term_type == 'LLT') continue;

			$id = "sider:".md5("se".$stitch_flat.$cui);

			$cui_label= strtolower(trim($term_type_label));
			if(!isset($declared[$cui])) {
				parent::addRDF(
					parent::describeClass($cui, $cui_label)
				);
				$declared[$cui] = '';
			}
			if(!isset($declared[$stitch_flat])) {
				$pubchem_id = "pubchem.compound:".ltrim( substr($a[0],4), "0");
				$stereo_id  = "pubchem.compound:".ltrim( substr($a[1],4), "0");
				parent::addRDF(
					parent::triplify($stitch_flat, "rdf:type", parent::getVoc()."Flat-Compound").
					parent::describeClass(parent::getVoc()."Flat-Compound", "Flat compound").
					parent::triplify($stitch_flat, parent::getVoc()."x-pubchem.compound", $pubchem_id).
					parent::triplify($stitch_flat, parent::getVoc()."stitch-stereo", $stitch_stereo)
				);
				$declared[$stitch_flat] = '';
			}
			if(!isset($declared[$stitch_stereo])) {
				$pubchem_id  = "pubchem.compound:".ltrim( substr($a[1],4), "0");
				parent::addRDF(
					parent::triplify($stitch_stereo, "rdf:type", parent::getVoc()."Stereo-Compound").
					parent::describeClass(parent::getVoc()."Stereo-Compound", "Stereo compound").
					parent::triplify($stitch_stereo, parent::getVoc()."x-pubchem.compound", $pubchem_id).
					parent::triplify($stitch_stereo, parent::getVoc()."stitch-flat", $stitch_flat)
				);
				$declared[$stitch_stereo] = '';
			}
			
			parent::addRDF(
				parent::describeIndividual($id, "$stitch_flat $cui_label effect", parent::getVoc()."Drug-Effect-Association").
				parent::triplify($id, parent::getVoc()."effect", $cui).
				parent::triplify($id, parent::getVoc()."drug", $stitch_flat)
			);
			parent::setCheckpoint('record');
		}

		parent::setCheckpoint('file');
	}

	function indications()
	{
		$declared = null;
		$list = null;
		parent::setCheckpoint('file');
		while($l = $this->getReadFile()->Read()) {
			parent::setCheckpoint('record');

			$a = explode("\t",$l);
			list($stitch_flat,$cui,$provenance,$cui_label,$term_type,$term_cui,$term_cui_label) = $a;
			$id = "sider:".md5("i".$stitch_flat.$cui);

			if($term_type == "LLT" or isset($list[$id])) continue;
			if(!isset($list[$id])) {
				$list[$id] = '';
			}

			$stitch_id = "stitch:$stitch_flat";
			$meddra_id = "meddra:$cui";

			if(!isset($declared[$cui])) {
				parent::addRDF(
					parent::describeClass($meddra_id, $cui_label)
				);
				$declared[$cui] = '';
			}
			if(!isset($declared[$stitch_flat])) {
				$pubchem_id = "pubchem.compound:".ltrim( substr($stitch_flat,4), "0");
				parent::addRDF(
					parent::triplify($stitch_id, "rdf:type", parent::getVoc()."Flat-Compound").
					parent::describeClass(parent::getVoc()."Flat-Compound", "STITCH Flat compound").
					parent::triplify($stitch_id, parent::getVoc()."x-pubchem.compound", $pubchem_id)
				);
				$declared[$stitch_flat] = '';
			}

			parent::addRDF(
				parent::describeIndividual($id, $stitch_id." - ".$meddra_id." indication ", parent::getVoc()."Drug-Indication-Association").
				parent::describeClass(parent::getVoc()."Drug-Indication-Association","Drug-Disease Association").
				parent::triplify($id, parent::getVoc()."drug", $stitch_id).
				parent::triplify($id, parent::getVoc()."indication", $meddra_id).
				parent::triplifyString($id, parent::getVoc()."provenance", $provenance)
			);

		}
		parent::setCheckpoint('file');
	}
	
	/*
meddra_freq_parsed.tsv.gz
-------------------------

This file contains the frequencies of side effects as extracted from the labels. Format:

1 & 2: STITCH compound ids (flat/stereo, see above)
3: the source label, if you don't use STITCH compound ids, you can use the label mapping file to 
   find out more about the label
4: UMLS concept id
5: concept name
6: "placebo" if the info comes from placebo administration, "" otherwise
7: a description of the frequency: either "postmarketing", "rare", "infrequent", "frequent", or an exact
   percentage
8: a lower bound on the frequency
9: an upper bound on the frequency
10-12: MedDRA information as for meddra_adverse_effects.tsv.gz

The bounds are ranges like 0.01 to 1 for "frequent". If the exact frequency is known, then the lower bound 
matches the upper bound. Due to the nature of the data, there can be more than one frequency for the same label,
e.g. from different clinical trials or for different levels of severeness.
*/
	function freq()
	{
		$cols = 10;
		$i = 1;
		parent::setCheckpoint('file');
		while($l = parent::getReadFile()->read()) {
			$a = explode("\t",str_replace("%","",$l));
			if(count($a) != $cols) {
				trigger_error("Expecting $cols, but found ".count($a)." instead... skipping file!", E_USER_ERROR);
				return false; 
			}
			list($stitch_flat, $stitch_stereo, $cui, $placebo, $freq, $freq_lower, $freq_upper, $concept_type, $meddra_concept_id, $meddra_concept_label) = $a;
			if($concept_type == "LLT") continue;
			$meddra_concept_label = trim($meddra_concept_label);
			
			$id = "stitch_resource:".md5("se_freq".$l);
			$stitch_flat = "stitch:$stitch_flat";
			$label = "$meddra_concept_label frequency for $stitch_flat";
			parent::addRDF(
				parent::describeIndividual($id, $label, parent::getVoc()."Drug-Effect-Frequency").
				parent::describeClass(parent::getVoc()."Drug-Effect-Frequency","SIDER Drug-Effect and Frequency").
				parent::triplify($id, parent::getVoc()."drug", $stitch_flat).
				parent::triplify($id, parent::getVoc()."effect", "meddra:".$meddra_concept_id)
			);

			if($placebo){
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."placebo", "true", "xsd:boolean")
				);
			}

			$number = false;
			if(is_numeric($freq)) {
				$flabel = $freq."%";
				$ftype_label = "Exact-Frequency";
				$ftype  = parent::getVoc().$ftype_label;
				$number = true;
			} else {
				$flabel = $freq;
				$ftype_label = "Qualitative-Frequency";
				$ftype = parent::getVoc()."$ftype_label";
			}
			if($freq_lower != $freq_upper) {
				$flabel .= "($freq_lower-$freq_upper)";
				$ftype_label = "Range-Frequency";
				$ftype = parent::getVoc().$ftype_label;
			} 

			$fid = $id.md5($a[5].$a[6].$a[8]);
			parent::addRDF(
				parent::triplify($id,parent::getVoc()."frequency",$fid).
				parent::describeIndividual($fid,$flabel,$ftype).
				parent::describeClass($ftype, $ftype_label)
			);
	
			if($number == true) {
				parent::addRDF(
					parent::triplifyString($fid, parent::getVoc()."frequency-value", $freq/100)
				);
			} else {
				parent::addRDF(
					parent::triplifyString($fid, parent::getVoc()."frequency-value", $freq)
				);
			}
			parent::addRDF(
				parent::triplifyString($fid, parent::getVoc()."lower-frequency", sprintf("%.3f",$freq_lower)).
				parent::triplifyString($fid, parent::getVoc()."upper-frequency", sprintf("%.3f",$freq_upper))
			);

			parent::setCheckpoint('record');
		}
		parent::setCheckpoint('file');

	}

}
?>
