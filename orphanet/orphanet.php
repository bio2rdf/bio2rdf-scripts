<?php
/**
Copyright (C) 2013 Michel Dumontier

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
 * An RDF generator for orphanet
 * documentation: http://www.orphadata.org/
 * @version 1.0
 * @author Michel Dumontier
*/

require_once(__DIR__.'/../../php-lib/bio2rdfapi.php');

class ORPHANETParser extends Bio2RDFizer 
{
	private $filemap = array(
		'disease' => 'en_product1.xml',
		'epi'     => 'en_product9_prev.xml',
		# 'd2s'     => 'en_product4.xml',
		# 'signs'   => 'en_product5.xml',
		'genes'   => 'en_product6.xml'
	);
	function __construct($argv) {
		parent::__construct($argv, "orphanet");
		parent::addParameter('files',true,'all|disease|genes','all','all or comma-separated list of ontology short names to process');
		parent::addParameter('download_url',false,null,'http://www.orphadata.org/data/xml/');
		parent::initialize();
	}

	function run() 
	{
		$ldir = parent::getParameterValue('indir');
		$odir = parent::getParameterValue('outdir');
		$dd = '';
		
		$files = parent::getParameterValue('files');
		if($files == 'all') {
			$files = explode('|', parent::getParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(',', parent::getParameterValue('files'));
		}
		
		foreach($files AS $file) {
			echo "processing $file ...";
			$lfile = $ldir.$this->filemap[$file];
			$rfile = parent::getParameterValue('download_url').$this->filemap[$file];
			if(!file_exists($lfile) || parent::getParameterValue('download') == 'true') {
				$ret = utils::downloadSingle($rfile,$lfile);
				if($ret === false) {
					echo "unable to download $file ... skipping".PHP_EOL;
					continue;
				}
			}
			
			parent::setReadFile($lfile,true);	
			
			$suffix = parent::getParameterValue('output_format');
			$ofile = "orphanet-".$file.'.'.$suffix; 
			$gz = strstr(parent::getParameterValue('output_format'), "gz")?($gz=true):($gz=false);
			
			parent::setWriteFile($odir.$ofile, $gz);
			$this->$file($lfile);
			parent::getWriteFile()->close();
			parent::getReadFile()->close();
			parent::clear();
			echo "done!".PHP_EOL;

			// dataset description
			$source_file = (new DataResource($this))
				->setURI($rfile)
				->setTitle("Orphanet: $file")
				->setRetrievedDate(parent::getDate(filemtime($lfile)))
				->setFormat("application/xml")
				->setPublisher("http://www.orpha.net")
				->setHomepage("http://www.orpha.net/")
				->setRights("use")
				->setRights("sharing-modified-version-needs-permission")
				->setLicense("http://creativecommons.org/licenses/by-nd/3.0/")
				->setDataset("http://identifiers.org/orphanet/");

			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = parent::getDate(filemtime($odir.$ofile));

			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$ofile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/orphanet/orphanet.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")
				->setRights("use-share-modify")
				->setRights("by-attribution")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons.org/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());

			$gz = (strstr(parent::getParameterValue('output_format'),".gz") === FALSE)?false:true;
			if($gz) $output_file->setFormat("application/gzip");
			if(strstr(parent::getParameterValue('output_format'),"nt")) $output_file->setFormat("application/n-triples");
			else $output_file->setFormat("application/n-quads");

			$dd .= $source_file->toRDF().$output_file->toRDF();

		}//foreach
		parent::writeToReleaseFile($dd);
	}

	function disease($file)
	{
		$xml = new CXML($file);
		while($xml->parse("DisorderList") == TRUE) {
			$x = $xml->GetXMLRoot();
			$version = $x->attributes()->version;
			
			foreach($x->Disorder AS $d) {
				// var_dump($d);exit;

				$internal_id = (string) $d->attributes()->id;
				$orphanet_id = parent::getNamespace().((string)$d->OrphaNumber);
				$name = (string) $d->Name;
				$expert_link = (string) $d->ExpertLink;
				
				parent::addRDF(
					parent::describeIndividual($orphanet_id,$name,parent::getVoc()."Disorder").
					parent::describeClass(parent::getVoc()."Disorder","Disorder").
					parent::triplifyString($orphanet_id, parent::getVoc()."internal-id", $internal_id).
					parent::triplify($orphanet_id, parent::getVoc()."expert-link-url", $expert_link)
				);
				// get the synonyms
				foreach($d->SynonymList AS $s) {
					$synonym = str_replace('"','', (string) $s->Synonym);
					parent::addRDF(
						parent::triplifyString($orphanet_id, parent::getVoc()."synonym", $synonym)
					);
				}
				//DisorderFlagList
				foreach($d->DisorderFlagList AS $dfl) {
					$df = $dfl->DisorderFlag;
					if($df) {
						parent::addRDF(
							parent::triplifyString($orphanet_id, parent::getVoc()."disorder-flag", (string) $df->attributes()->id)
						);
					}
				}
				// get external references
				foreach($d->ExternalReferenceList AS $erl) {
					foreach($erl->ExternalReference AS $er) {						
						$source = (string) $er->Source;
						$db = parent::getRegistry()->getPreferredPrefix($source);
						$id = (string) $er->Reference;
						parent::addRDF(
							parent::triplify($orphanet_id, parent::getVoc()."x-$db", "$db:$id")
						);
					}
				}
				// get the definition
				foreach($d->TextualInformationList AS $til) {
					foreach($til->TextualInformation As $ti) {
						foreach($ti->TextSectionList AS $tsl) {
							foreach($tsl->TextSection AS $ts) {
								if(((string) $ts->TextSectionType->Name) == "Definition") {
									parent::addRDF(
										parent::triplifyString($orphanet_id, parent::getVoc()."definition", addslashes((string) $ts->Contents))
									);
								};								
							}
						}
					}
				}
				parent::writeRDFBufferToWriteFile();
			}
		}
		unset($xml);	
	}
	
	function epi ($file) 
	{
		$seen = '';
		$xml = new CXML($file);
		while($xml->parse("DisorderList") == TRUE) {
			$x = $xml->GetXMLRoot();
			foreach($x->Disorder AS $d) {
				// var_dump($d);exit;
				$orphanet_id = parent::getNamespace().((string)$d->OrphaNumber);
				if(isset($d->ClassOfPrevalence)) {
					$id = parent::getNamespace().((string) $d->ClassOfPrevalence->attributes()->id);
					$name = (string) $d->ClassOfPrevalence->Name;
					if($name != '' && $name != 'Unknown' && $name != 'No data available') {
						if(!isset($seen[$name])) {
							$seen[$name] = true;
							$a = explode (" / ", $name);
							$size = str_replace(" ","",$a[1]);
							$upper_bound = $lower_bound = '';
							if($a[0][0] == '<') {
								$upper_bound = substr($a[0],1) / $size;
							} else if($a[0][0] == '>') {
								$lower_bound = substr($a[0],1) / $size;
							} else {
								$b = explode("-",$a[0]);
								$lower_bound = $b[0] / $size;
								$upper_bound = $b[1] / $size;
							}
							if($upper_bound) {
								parent::addRDF(
									parent::triplifyString($id,parent::getVoc()."upper-bound",$upper_bound, "xsd:float")
								);
							}
							if($lower_bound) {
								parent::addRDF(
									parent::triplifyString($id,parent::getVoc()."lower-bound",$lower_bound, "xsd:float")
								);
							}
						}
						parent::addRDF(
							parent::triplify($orphanet_id, parent::getVoc()."prevalence", $id).
							parent::describeClass($id,$name,parent::getVoc()."Prevalence")
						);

						//echo parent::getRDF();exit;
					}
				}
				if(isset($d->AverageAgeofOnset)) {
					$id = parent::getNamespace().((string) $d->AverageAgeOfOnset->attributes()->id);
					$name = (string) $d->AverageAgeOfOnset->Name;
					parent::addRDF(
						parent::triplify($orphanet_id, parent::getVoc()."average-age-of-onset", $id).
						parent::describeClass($id,$name,parent::getVoc()."Average-Age-Of-Onset")
					);
				}
				if(isset($d->AverageAgeofDeath)) {
					$id = parent::getNamespace().((string) $d->AverageAgeofDeath->attributes()->id);
					$name = (string) $d->AverageAgeOfDeath->Name;
					parent::addRDF(
						parent::triplify($orphanet_id, parent::getVoc()."average-age-of-death", $id).
						parent::describeClass($id,$name,parent::getVoc()."Average-Age-Of-Death")
					);
				}
				if(isset($d->TypeOfInheritanceList)) {
					if($d->TypeOfInheritanceList->attributes()) {
						$n = $d->TypeOfInheritanceList->attributes()->count;
						if($n > 0) {
							foreach($d->TypeOfInheritanceList AS $o) {
								//echo $orphanet_id.PHP_EOL;
								$toi = $o->TypeOfInheritance;
								$id = parent::getNamespace().((string) $toi->attributes()->id);
								$name = (string) $toi->Name;
								parent::addRDF(
									parent::triplify($orphanet_id, parent::getVoc()."type-of-inheritance", $id)
									.parent::describeClass($id,$name,parent::getVoc()."Inheritance")
								);
							}
						}
					}
				}
//				echo $this->getRDF();exit;
				parent::writeRDFBufferToWriteFile();
			}
		}
		unset($xml);	
	}
	
	function d2s($file)
	{
	/*
	   <DisorderSignList count="18">
        <DisorderSign>
          <ClinicalSign id="2040">
            <Name lang="en">Macrocephaly/macrocrania/megalocephaly/megacephaly</Name>
          </ClinicalSign>
          <SignFreq id="640">
            <Name lang="en">Very frequent</Name>
          </SignFreq>
        </DisorderSign>
	*/
		$xml = new CXML($file);
		while($xml->parse("DisorderList") == TRUE) {
			$x = $xml->GetXMLRoot();
			foreach($x->Disorder AS $d) {
				$orphanet_id = parent::getNamespace().((string)$d->OrphaNumber);
				foreach($d->DisorderSignList->DisorderSign AS $ds) {
					$sfid = parent::getRes().md5($ds->asXML());
					if($ds->ClinicalSign) {
						$sid = parent::getVoc().((string)$ds->ClinicalSign->attributes()->id);
						$s = (string) $ds->ClinicalSign->Name;
						$fid = parent::getRes().((string) $ds->SignFreq->attributes()->id);
						$f = (string) $ds->SignFreq->Name;
						parent::addRDF(
							parent::describeIndividual($sfid, "$f $s",parent::getVoc()."Clinical-Sign-And-Frequency").
							parent::describeClass(parent::getVoc()."Clinical-Sign-And-Frequency","Clinical Sign and Frequency").
							parent::triplify($orphanet_id, parent::getVoc()."sign-freq", $sfid).
							parent::triplify($sfid,parent::getVoc()."sign", $sid).
							parent::describeClass($sid,$s,parent::getVoc()."Clinical-Sign").
							parent::triplify($sfid,parent::getVoc()."frequency",$fid).
							parent::describeClass($fid,$f,parent::getVoc()."Frequency")
						);
					}
				}
				parent::writeRDFBufferToWriteFile();
			}
		}
		unset($xml);
	}
	
	function signs($file) 
	{
	/*
	<ClinicalSign id="49580">
      <Name lang="en">Oligoelements metabolism anomalies</Name>
      <ClinicalSignChildList count="0">
      </ClinicalSignChildList>
    </ClinicalSign>
    <ClinicalSign id="25300">
      <Name lang="en">Abnormal toenails</Name>
      <ClinicalSignChildList count="4">
        <ClinicalSign id="25350">
          <Name lang="en">Absent/small toenails/anonychia of feet</Name>
          <ClinicalSignChildList count="0">
          </ClinicalSignChildList>
        </ClinicalSign>
*/
		$xml = new CXML($file);
		while($xml->parse("ClinicalSignList") == TRUE) {
			$x = $xml->GetXMLRoot();
			foreach($x->ClinicalSign AS $cs) {
				$this->traverseCS($cs);
				parent::writeRDFBufferToWriteFile();
			}
		}
		unset($xml);
	}

	function traverseCS($cs)
	{
		if($cs->ClinicalSignChildList->attributes()->count > 0) {
			$cs_id = parent::getVoc().((string)$cs->attributes()->id);
			$cs_label = (string)$cs->Name;
			parent::addRDF(
				parent::describeClass($cs_id,$cs_label,parent::getVoc()."Clinical-Sign")
			);

			foreach($cs->ClinicalSignChildList->ClinicalSign AS $cl) {
				$child_id = parent::getVoc().((string)$cl->attributes()->id);
				$child_label = (string) $cl->Name;
				parent::addRDF(
					parent::describeClass($child_id,$child_label,$cs_id)
				);
				if(isset($cl->ClinicalSignChildList)) $this->traverseCS($cl);
			}
		}
	}

	function genes($file)
	{
		$xml = new CXML($file);
		while($xml->parse("DisorderList") == TRUE) {
			$x = $xml->GetXMLRoot();
			foreach($x->Disorder AS $d) {
				$orphanet_id = parent::getNamespace().((string)$d->OrphaNumber);
				$disorder_name = (string) $d->Name;

				foreach($d->DisorderGeneAssociationList->DisorderGeneAssociation AS $dga) {
					// gene
					$gene = $dga->Gene;
					$gid = ((string) $gene->attributes()->id);		
					$gene_id = parent::getNamespace().$gid;
					$gene_label = (string) $gene->Name;
					$gene_symbol = (string) $gene->Symbol;
					parent::addRDF(
						parent::describeIndividual($gene_id,$gene_label,parent::getVoc()."Gene").
						parent::describeClass(parent::getVoc()."Gene","Orphanet Gene").
						parent::triplifyString($gene_id,parent::getVoc()."symbol",$gene_symbol)
					);

					foreach($gene->SynonymList AS $s) {
						$synonym = (string) $s->Synonym;
						parent::addRDF(
							parent::triplifyString($gene_id,parent::getVoc()."synonym",$synonym)
						);
					}
					foreach($gene->ExternalReferenceList AS $erl) {
						$er = $erl->ExternalReference;
						$db = (string) $er->Source;
						$db = parent::getRegistry()->getPreferredPrefix($db);
						$id = (string) $er->Reference;
						$xref = "$db:$id";
						parent::addRDF(
							parent::triplify($gene_id, parent::getVoc()."x-$db", $xref)
						);
					}

					$dga_id = parent::getRes().((string)$d->OrphaNumber)."_".md5($dga->asXML());
					$ga = $dga->DisorderGeneAssociationType;
					$ga_id    = parent::getNamespace().((string) $ga->attributes()->id);
					$ga_label = (string) $ga->Name;

					$s = $dga->DisorderGeneAssociationStatus;
					$s_id    = parent::getNamespace().((string) $s->attributes()->id);
					$s_label = (string) $s->Name;

					parent::addRDF(
						parent::describeIndividual($dga_id,"$ga_label $gene_label in $disorder_name ($s_label)",$ga_id).
						parent::describeClass($ga_id,$ga_label,parent::getVoc()."Disorder-Gene-Association").
						parent::triplify($dga_id,parent::getVoc()."status", $s_id).
						parent::describeClass($s_id,$s_label,parent::getVoc()."Disorder-Gene-Association-Status").
						parent::triplify($dga_id,parent::getVoc()."disorder",$orphanet_id).
						parent::describeIndividual($orphanet_id,$disorder_name,parent::getVoc()."Disorder").
						parent::triplify($dga_id,parent::getVoc()."gene",$gene_id)
					);
				}

				parent::writeRDFBufferToWriteFile();
			}
		}
		unset($xml);
	}
}
?>
