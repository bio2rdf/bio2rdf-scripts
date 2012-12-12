<?php
/**
Copyright (C) 2011-2012 Michel Dumontier

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
 * @version 1.0
 * @author Michel Dumontier
*/

require('../../php-lib/rdfapi.php');
class SIDERParser extends RDFFactory 
{
	function __construct($argv) {
		parent::__construct();
		$this->SetDefaultNamespace("sider");
		
		// set and print application parameters
		$this->AddParameter('files',true,'all|label_mapping|adverse_effects_raw|indications_raw|meddra_freq_parsed','all','all or comma-separated list of ontology short names to process');
		$this->AddParameter('indir',false,null,'/data/download/sider/','directory to download into and parse from');
		$this->AddParameter('outdir',false,null,'/data/rdf/sider/','directory to place rdfized files');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		$this->AddParameter('graph_uri',false,null,null,'specify a graph uri to generate nquads');
		$this->AddParameter('download',false,'true|false','false','set true to download files');
		$this->AddParameter('download_url',false,null,'http://sideeffects.embl.de/media/download/');

		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		if($this->GetParameterValue('graph_uri')) {
			$this->SetGraphURI($this->GetParameterValue('graph_uri'));
		}
				
		return TRUE;
	}
	
	function Run()
	{
		$idir = $this->GetParameterValue('indir');
		$odir = $this->GetParameterValue('outdir');
		$files = $this->GetParameterValue('files');
		
		if($files == 'all') {
			$files = explode('|',$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(',',$this->GetParameterValue('files'));
		}
		
		foreach($files AS $file) {
			$lfile = $idir.$file.'.tsv.gz';
			$rfile = $this->GetParameterValue('download_url').$file.'.tsv.gz';
			if(!file_exists($lfile) || $this->GetParameterValue('download') == 'true') {
				echo "downloading $file...";
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
			echo "processing $file";
			$this->SetReadFile($lfile,true);			
			$ofile = $odir."sider-".$file.'.rdf.gz';
			$this->SetWriteFile($ofile,true);
			$this->$file();
			$this->GetWriteFile()->Close();
			$this->GetReadFile()->Close();
			echo "done!".PHP_EOL;
		}
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
		$declared = null;
		while($l = $this->GetReadFile()->Read()) {
			$a = explode("\t",$l);
			$id = "sider:".urlencode(trim($a[6]));
			
			$this->AddRDF($this->QQuadL($id,"dc:identifier",trim($a[6])));
			if(trim($a[0])) {
				$brand = strtolower(trim($a[0]));
				$brand_qname = "sider_resource:".md5($brand);
				$this->AddRDF($this->QQuad($id,"sider_vocabulary:brand-name",$brand_qname));
				if(!isset($declared[$brand_qname])) {
					$declared[$brand_qname] = '';
						$this->AddRDF($this->QQuadL($brand_qname,"rdfs:label",$brand." [$brand_qname]"));
						$this->AddRDF($this->QQuadL($brand_qname,"dc:title",$brand));
						$this->AddRDF($this->QQuad($brand_qname,"rdf:type","sider_vocabulary:Brand-Drug"));	
				}
			}
			if(trim($a[1])) {
				$b = explode(";",strtolower(trim($a[1])));
				$b = array_unique($b);
				foreach($b AS $generic_name) {
					$generic = trim($generic_name);
					$generic_qname = "sider_resource:".md5($generic);
					$this->AddRDF($this->QQuad($id,"sider_vocabulary:generic-name",$generic_qname));
					if(!isset($declared[$generic_qname])) {
						$declared[$generic_qname] = '';
						$this->AddRDF($this->QQuadL($generic_qname,"rdfs:label",$generic." [$generic_qname]"));
						$this->AddRDF($this->QQuadL($generic_qname,"dc:title",$generic));
						$this->AddRDF($this->QQuad($generic_qname,"rdf:type","sider_vocabulary:Generic-Drug"));
					}
				}
			}
			if($a[2]) $this->AddRDF($this->QQuad($id,"sider_vocabulary:mapping-result","sider_vocabulary:".str_replace(" ","-",$a[2])));
			if($a[3]) $this->AddRDF($this->QQuad($id,"sider_vocabulary:stitch-flat-compound-id","stitch:".$a[3]));
			if($a[3]) $this->AddRDF($this->QQuad($id,"sider_vocabulary:pubchem-flat-compound-id","pubchemcompound:".$this->GetPCFromFlat($a[3])));
//			if($a[4]) $this->AddRDF($this->QQuad($id,"sider_vocabulary:stitch-stereo-compound-id","stitch:".abs($a[4])));
			if($a[4]) $this->AddRDF($this->QQuad($id,"sider_vocabulary:pubchem-stereo-compound-id","pubchemcompound:".$this->GetPCFromStereo($a[4])));
			if($a[5]) $this->AddRDF($this->QQuadO_URL($id,"sider_vocabulary:pdf-url",str_replace(" ","+",$a[5])));
			
		}
		$this->WriteRDFBufferToWriteFile();	
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
		while($l = $this->GetReadFile()->Read()) {
			$a = explode("\t",$l);
			$id = "sider:".urlencode($a[0]);
			$label= strtolower(trim($a[2]));
			$cui = "umls:".$a[1];

			$this->AddRDF($this->QQuad($id,"sider_vocabulary:side-effect",$cui));
			if(!isset($declared[$label])) {
				$declared[$label] = '';
				$this->AddRDF($this->QQuadL($cui,"rdfs:label",$label." [$cui]"));
				$this->AddRDF($this->QQuadL($cui,"dc:identifier",$cui));
			}
		}
		$this->WriteRDFBufferToWriteFile();	
	}
	function indications_raw()
	{
		$declared = null;
		while($l = $this->GetReadFile()->Read()) {
			$a = explode("\t",$l);
			$id = "sider:".urlencode($a[0]);
			$label= strtolower(trim($a[2]));
			$cui = "umls:".$a[1];
			
			$this->AddRDF($this->QQuad($id,"sider_vocabulary:indication",$cui));
			if(!isset($declared[$label])) {
				$declared[$label] = '';
				$this->AddRDF($this->QQuadL($cui,"rdfs:label",$label." [$cui]"));
				$this->AddRDF($this->QQuadL($cui,"dc:identifier",$cui));
			}
		}
		$this->WriteRDFBufferToWriteFile();	
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
		$i = 1;
		while($l = $this->GetReadFile()->Read()) {
			$a = explode("\t",$l);
			$label_id = "sider:".urlencode($a[2]);
			$effect_id = "umls:".$a[3];
			
			$id = "sider:F".$i++;
			$this->AddRDF($this->QQuad($id,"rdf:type","sider_vocabulary:Drug-Effect-Frequency"));
			$this->AddRDF($this->QQuad($id,"sider_vocabulary:drug",$label_id));
			$this->AddRDF($this->QQuad($id,"sider_vocabulary:effect",$effect_id));
			if($a[5]) $this->AddRDF($this->QQuadL($id,"sider_vocabulary:placebo","true"));
			if($a[6]) $this->AddRDF($this->QQuadL($id,"sider_vocabulary:frequency",$a[6]));
			if($a[7]) $this->AddRDF($this->QuadL($id,"sider_vocabulary:lower-frequency",$a[7]));
			if($a[8]) $this->AddRDF($this->QQuadL($id,"sider_vocabulary:upper-frequency",$a[8]));
			
/*			if($a[9]) $this->AddRDF($this->QQuadL($id,"sider_vocabulary:meddra-concept-level",$a[9]));
			if($a[10]) $this->AddRDF($this->QQuad($id,"sider_vocabulary:meddra-concept-id","umls:".$a[10]));
			if(trim($a[11])) $this->AddRDF($this->QQuadL("umls:".$a[10],"rdfs:label",strtolower(trim($a[11]))));
*/
			$this->WriteRDFBufferToWriteFile();	
		}
		
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
	function meddra_adverse_effects()
	{
/*
		while($l = $this->GetReadFile()->Read()) {
			$a = explode("\t",$l);
			
			
			print_r($a);exit;
			
		}
		$this->WriteRDFBufferToWriteFile();	
*/
	}
}

set_error_handler('error_handler');
$parser = new SIDERParser($argv);
$parser->Run();
?>
