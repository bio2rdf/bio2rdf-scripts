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
		parent::addParameter('files',true,'all|label_mapping|adverse_effects_raw|indications_raw|meddra_freq_parsed','all','all or comma-separated list of ontology short names to process');
		parent::addParameter('download_url',false,null,'http://sideeffects.embl.de/media/download/');
		
		parent::initialize();
	}

	function run() {

		if(parent::getParameterValue('download') === true) 
		{
			$this->download();
		}
		if(parent::getParameterValue('process') === true) 
		{
			$this->process();
		}
		
	}
		
	function download(){
		$idir = parent::getParameterValue('indir');
		$files = parent::getParameterValue('files');

		if($files == 'all') {
			$files = explode('|', parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(',', parent::getParameterValue('files'));
		}
		
		foreach($files AS $file) {
			$lfile = $idir.$file.'.tsv.gz';
			$rfile = parent::getParameterValue('download_url').$file.'.tsv.gz';
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
		}//foreach
	}

	function process(){

		$idir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$files = parent::getParameterValue('files');
		
		if($files == 'all') {
			$files = explode('|', parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(',', parent::getParameterValue('files'));
		}
		
		parent::setCheckpoint('dataset');

		$dataset_description = '';

		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		foreach($files AS $file) {
			$lfile = $idir.$file.'.tsv.gz';
			$rfile = parent::getParameterValue('download_url').$file.'.tsv.gz';

			echo "Processing $file... ";
			parent::setReadFile($lfile,true);	
			
			$suffix = parent::getParameterValue('output_format');
			$ofile = "sider-".$file.'.'.$suffix; 
			$gz = false;
			
			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}

			parent::setWriteFile($odir.$ofile, $gz);
			$this->$file();
			parent::getWriteFile()->Close();
			parent::getReadFile()->Close();
			echo "done!".PHP_EOL;

			echo "Generating dataset description... ";

			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("SIDER Side Effect resource ($file.tsv.gz")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", filemtime($lfile)))
				->setFormat("text/tab-separated-value")
				->setFormat("application/gzip")	
				->setPublisher("http://sideeffects.embl.de/")
				->setHomepage("http://sideeffects.embl.de/")
				->setRights("use-share-modify")
				->setLicense("http://creativecommons.org/licenses/by-nc-sa/3.0/")
				->setDataset("http://identifiers.org/sider.effect/");

			if($file == "label_mapping") $source_file->setLicense("http://creativecommons.org/publicdomain/zero/1.0/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
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

		//reset graph URI to default value
		parent::setGraphURI($graph_uri);

	}
/*
1 & 2: generic and brand names

3: a marker if the drug could be successfully mapped to STITCH. Possible values:
 - [empty field]: success
 - combination: two or more drugs were combined
 - not found: could not find the name in the database
 - mapping conflict: the available names point to two different compounds
 - template: a package insert that contains information for a group of related drugs

4 & 5: STITCH compound ids, based on PubChem. Salt forms and stereo-isomers have been merged.
   Column 4: "flat compound", i.e. stereo-isomers have been merged into one compound
	 Column 5: stereo-specific compound id
	
	 To get the PubChem Compound Ids: take absolute value, for flat compounds ids: subtract 100000000
	 E.g. aspirin: -100002244 --> 2244

6: URL of the downloaded PDF. This column is empty for FDA SPL labels, which are available in XML.
	 Unfortunately, many links have become stale since the labels were downloaded in 2009. 

7: label identifier
*/
	function label_mapping()
	{
		parent::setCheckpoint('file');

		$declared = null;
		while($l = parent::getReadFile()->Read()) {
			parent::setCheckpoint('record');

			$a = explode("\t",$l);
			$id = parent::getNamespace().urlencode(trim($a[6]));

			$label = $a[1];
			$names = explode(";",strtolower(trim($a[1])));
			array_unique($names);
			asort($names);
			if($a[2] == "combination") {
				$label = implode(";",$names);
			}

			parent::addRDF(
				parent::describeIndividual($id, $label, parent::getVoc()."Drug", $a[6]).
				parent::describeClass(parent::getVoc()."Drug","SIDER Drug")
			);
		
			if(trim($a[0])) {
				$brand_label = strtolower(trim($a[0]));
				$brand_qname = parent::getRes().md5($brand_label);
				parent::addRDF(
					parent::describeIndividual($brand_qname, $brand_label, parent::getVoc()."Brand-Drug").
					parent::describeClass(parent::getVoc()."Brand-Drug","Brand Drug")
				);

				parent::addRDF(
					parent::triplify($id, parent::getVoc()."brand-name", $brand_qname)
				);
			}
			if(trim($a[1])) {
				foreach($names AS $generic_name) {
					$generic_label = trim($generic_name);
					$generic_qname = parent::getRes().md5($generic_label);
					parent::addRDF(
						parent::describeIndividual($generic_qname, $generic_label, parent::getVoc()."Generic-Drug").
						parent::describeClass(parent::getVoc()."Generic-Drug","Generic Drug")
					);

					parent::addRDF(
						parent::triplify($id, parent::getVoc()."generic-name", $generic_qname)
					);
				}
			}
			
			if($a[2]){
				$mapping_result = str_replace(" ","-",$a[2]);
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."mapping-result", parent::getVoc().$mapping_result)
				);
			}

			if($a[3]){
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."stitch-flat-compound-id", "stitch:".$a[3])
				);

				$pubchemcompound = $this->GetPCFromFlat($a[3]);
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."pubchem-flat-compound-id", "pubchemcompound:".$pubchemcompound)
				);
			}

			if($a[4]){
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."stitch-stereo-compound-id", "stitch:".$a[4])
				);
				$pubchemcompound = $this->GetPCFromStereo($a[4]);
				parent::addRDF(
					parent::triplify($id, parent::getVoc()."pubchem-stereo-compound-id", "pubchemcompound:".$pubchemcompound)
				);
			}

			if($a[5]){
				$url = str_replace(" ","+",$a[5]);
				parent::addRDF(
					parent::QQuadO_URL($id, parent::getVoc()."pdf-url", $url)
				);
			}
			parent::setCheckpoint('record');

		}
		parent::setCheckpoint('file');
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

	function adverse_effects_raw()
	{
		$declared = null;

		parent::setCheckpoint('file');

		while($l = $this->GetReadFile()->Read()) {
			$a = explode("\t",$l);
			$id = "sider:".urlencode($a[0]);
			$cui = "umls:".$a[1];
			$cui_label= strtolower(trim($a[2]));
			parent::addRDF(
				parent::describeIndividual($cui, $cui_label, null)
			);
			parent::addRDF(
				parent::triplify($id, parent::getVoc()."side-effect", $cui)
			);
		}
		parent::setCheckpoint('file');
	}

	function indications_raw()
	{
		$declared = null;

		parent::setCheckpoint('file');

		while($l = $this->GetReadFile()->Read()) {
			parent::setCheckpoint('record');

			$a = explode("\t",$l);
			$id = "sider:".urlencode($a[0]);
			$cui = "umls:".$a[1];
			$cui_label = strtolower(trim($a[2]));

			parent::addRDF(
				parent::describeIndividual($cui, $cui_label, null)
			);

			parent::addRDF(
				parent::triplify($id, parent::getVoc()."indication", $cui)
			);
			parent::setCheckpoint('record');

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
	function meddra_freq_parsed()
	{
		$cols = 12;
		$i = 1;
		parent::setCheckpoint('file');
		while($l = parent::getReadFile()->read()) {
			parent::setCheckpoint('record');

			$a = explode("\t",str_replace("%","",$l));
			if(count($a) != $cols) {
				trigger_error("Expecting $cols, but found ".count($a)." instead... skipping file!");
				return false; 
			}
			$label = $a[2];
			$label_id = parent::getNamespace().urlencode($label);
			$effect_id = "umls:".$a[3];
			
			$id = parent::getRes().md5($a[2].$a[3].$a[6]);
			$label = "$a[4] in $label $a[2]";
			parent::addRDF(
				parent::describeIndividual($id, $label, parent::getVoc()."Drug-Effect").
				parent::describeClass(parent::getVoc()."Drug-Effect","SIDER Drug-Effect")
			);

			parent::addRDF(
				parent::describeIndividual($effect_id, $a[4], parent::getVoc()."Effect")
			);

			parent::addRDF(
				parent::triplify($id, parent::getVoc()."drug", $label_id)
			);

			parent::addRDF(
				parent::triplify($id, parent::getVoc()."effect", $effect_id)
			);

			if($a[5]){
				parent::addRDF(
					parent::triplifyString($id, parent::getVoc()."placebo", "true", "xsd:boolean")
				);
			}

				$fid = $id.md5($a[5].$a[6].$a[7].$a[8]);
//				$fid = $id.($i++);
				$flabel = $a[6];
				$ftype  = parent::getVoc().$a[6]."-Frequency";
				$number = false;
				if(is_numeric($a[6])) {
					$flabel = $a[6]."%";
					$ftype  = parent::getVoc()."Specified-Frequency";
					$number = true;
				}
				if($a[7] != $a[8]) {
					$flabel .= "($a[7]-$a[8])";
					$ftype = parent::getVoc()."Range-Frequency";
				}

				parent::addRDF(
					parent::triplify($id,parent::getVoc()."reported-frequency",$fid).
					parent::describeIndividual($fid,$flabel,parent::getVoc().$ftype)
				);
		
				if($number == true) {
					parent::addRDF(
						parent::triplifyString($fid, parent::getVoc()."frequency", $a[6]/100)
					);
				} else {
					parent::addRDF(
						parent::triplifyString($fid, parent::getVoc()."frequency", $a[6])
					);
				}
			//	if($a[7] != $a[8]){
					parent::addRDF(
						parent::triplifyString($fid, parent::getVoc()."lower-frequency", $a[7]).
						parent::triplifyString($fid, parent::getVoc()."upper-frequency", $a[8])
					);
			//	}

				$meddra_id = "umls:$a[10]";
				$label = "";
				if(trim($a[11])) $label = strtolower(trim($a[11]));
				$rel = "preferred-term";
				if($a[9] != "LLT") $rel = "lower-level-term";	
			
				parent::addRDF(
					parent::triplify($fid, parent::getVoc().$rel, $meddra_id).
					parent::describeClass($meddra_id,$label)				
				);

print_r($a).PHP_EOL;
echo parent::getRDF();
parent::deleteRDF();
			parent::setCheckpoint('record');
		}
		parent::setCheckpoint('file');

	}
	
/*
meddra_adverse_effects.tsv.gz
-----------------------------

1 & 2: STITCH compound ids (flat/stereo, see above)
3: UMLS concept id as it was found on the label
4: drug name
5: side effect name
6: MedDRA concept type (LLT = lowest level term, PT = preferred term)
7: UMLS concept id for MedDRA term
8: MedDRA side effect	name

All side effects found on the labels are given as LLT. Additionally, the PT is shown. There is at least one
PT for every side effect, but sometimes the PT is the same as the LLT. 
*/
// @TODO
	function meddra_adverse_effects()
	{
		
	}
}
?>
