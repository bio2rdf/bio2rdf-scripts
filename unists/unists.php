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
 * UniSTS RDFizer
 * @version 2.0
 * @author Jose Cruz-Toledo
 * @description ftp://ftp.ncbi.nih.gov/repository/UniSTS/README
*/
class UniSTSParser extends Bio2RDFizer{
	private $bio2rdf_base = "http://bio2rdf.org/";
	private $unists_vocab = "unists_vocabulary:";
	private $unists_resource = "unists_resource:";
	private $version = 0.1;

	private static $packageMap = array(
		"markers" =>  "UniSTS.sts",
		"aliases" => "UniSTS.aliases",
		"map_reports" => "ftp.ncbi.nih.gov/repository/UniSTS/UniSTS_MapReports/",
		//"pcr_reports" => "ftp.ncbi.nih.gov/repository/UniSTS/UniSTS_ePCR.Reports/"
	);

	function __construct($argv) {
		parent::__construct($argv, "unists");
		parent::AddParameter('files', true, 'all', 'all', 'files to process');
		parent::AddParameter('download_url', false, null, 'ftp://ftp.ncbi.nih.gov/repository/UniSTS/');
		parent::initialize();
	}//constructor

	function run(){
		$dataset_description = '';
		$ldir = parent::GetParameterValue('indir');
		$odir = parent::GetParameterValue('outdir');
		//download
		if($this->GetParameterValue('download') == true){
			$list = $this->getFtpFileList('ftp.ncbi.nih.gov');
			$total = count($list);
			$counter = 1;
			foreach($list as $f){
				echo "downloading file $counter out of $total :".parent::getParameterValue('download_url').$f."... ".PHP_EOL;
				file_put_contents($ldir.$f, file_get_contents(parent::GetParameterValue('download_url').$f));
				$counter ++;
			}
		}//if download
		//iterate over the files
		$paths = $this->getFilePaths($ldir, 'gz');
		$lfile = null;
		foreach ($paths as $aPath) {
			$lfile = $aPath;
			$ofile = $odir.basename($aPath,".gz").".".parent::getParameterValue('output_format');
			$gz = false;
			if(strstr(parent::getParameterValue('output_format'), "gz")){$gz = true;}
			parent::setWriteFile($ofile, $gz);
			parent::setReadFile($ldir.$lfile, true);
			$source_file = (new DataResource($this))
				->setURI(parent::getParameterValue('download_url').basename($aPath))
				->setTitle('NCBI UniSTS filename: '.basename($aPath))
				->setRetrievedDate(date("Y-m-d\TG:i:s\Z", filemtime($ldir.$lfile)))
				->setFormat('xml/unists-format')
				->setFormat('application/zip')
				->setPublisher('https://www.ncbi.nlm.nih.gov')
				->setHomepage('https://www.ncbi.nlm.nih.gov/unists')
				->setRights('use')
				->setRights('attribution')
				->setLicense('https://www.nlm.nih.gov/copyright.html')
				->setDataset(parent::getDatasetURI());
				$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/unists/unists.php")
				->setCreateDate($date)
				->setHomepage("http://download.bio2rdf.org/release/$bVersion/$prefix/$prefix.html")
				->setPublisher("http://bio2rdf.org")
				->setRights("use-share-modify")
				->setRights("restricted-by-source-license")
				->setLicense("http://creativecommons/licenses/by/3.0/")
				->setDataset(parent::getDatasetURI());
			$dataset_description .= $output_file->toRDF().$source_file->toRDF();
			echo "processing $aPath ...";
			$this->process();
			echo "done!".PHP_EOL;
			$this->setWriteFile($odir.$this->getBio2RDFReleaseFile());
			$this->getWriteFile()->write($dataset_description);
			$this->getWriteFile()->close();
		}//foreach
	}//run

	//reference: ftp://ftp.ncbi.nih.gov/repository/UniSTS/unists.dtd
	private function process(){
		//read the file into a string
		$xml_str = '';
		while($l = $this->getReadFile()->Read(4096)){
			$xml_str .= $l;
		}
		$xml = new SimpleXMLElement($xml_str);
		foreach($xml->{'sts'} as $sts){
			$uid = (string)$sts->uid;
			$uid_res = $this->getNamespace().$uid;
			$name = (string)$sts->name;
			$uid_label = $name;
			parent::AddRDF(
				parent::describeIndividual($uid_res, $uid_label, $this->getVoc()."sequence_tagged_site")
			);

			//split by semicolon
			$genbank_accession_arr = explode(";", (string)$sts->gbacc);
			foreach($genbank_accession_arr as $gb){
				parent::AddRDF(
					parent::triplify($uid_res, $this->getVoc().'x-genbank', 'genbank:'.$gb)
				);
			}

			//split by semicolon
			$gi_arr = explode(';',(string)$sts->gi);
			foreach($gi_arr as $gi){
				parent::AddRDF(
					parent::triplify($uid_res, $this->getVoc().'x-gi', 'gi:'.$gi)
				);
			}
			$links = (string)$sts->links;
			if(isset($links)){
				parent::AddRDF(
					parent::triplifyString($uid_res, $this->getVoc().'links', utf8_encode($links))
				);
			}
			$forward_primer = (string)$sts->pcrfor;
			if(isset($forward_primer)){
				parent::AddRDF(
					parent::triplifyString($uid_res, $this->getVoc().'forward-primer', $forward_primer)
				);
			}
			$reverse_primer = (string)$sts->pcrrev;
			if(isset($reverse_primer)){
				parent::AddRDF(
					parent::triplifyString($uid_res, $this->getVoc().'reverse-primer', $reverse_primer)
				);
			}
			$epcr_summary = (string)$sts->EPCR_Summary;
			if(isset($epcr_summary)){
				parent::AddRDF(
					parent::triplifyString($uid_res, $this->getVoc().'epcr-summary', $epcr_summary)
				);
			}
			$dbsts = (string) $sts->dbsts;
			if(isset($dbsts)){
				parent::AddRDF(
					parent::triplifyString($uid_res, $this->getVoc().'dbsts', $dbsts)
				);
			}
			
			foreach($sts->{'Map_Gene_Summary_List'} as $gsl){
				foreach($gsl->{'Map_Gene_Summary'} as $mgs){
					$r = rand();
					$org = (string)$mgs->Org;
					$taxid = (string)$mgs->taxid;
					$chromosome = (string)$mgs->Chr;
					$locus = (string)$mgs->Locus;
					$polymorphic = (string)$mgs->Polymorphic;
					$lbl = "map gene summary for :".$org." ".$taxid;
					$u_res = $this->getRes().md5($r.$org);
					parent::AddRDF(
						parent::describeIndividual($u_res,$lbl, $this->getVoc()."map-gene-summary" ).
						parent::triplify($uid_res, $this->getVoc()."has-map-gene-summary", $u_res)
					);
					if(isset($polymorphic)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc()."polymorphic", $polymorphic)
						);
					}
					if(isset($locus)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc()."locus", $locus)
						);
					}
					if(isset($chromosome)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc()."chromosome", $chromosome)
						);
					}
					if(isset($taxid)){
						parent::AddRDF(
							parent::triplify($u_res, $this->getVoc()."x-taxonomy", "taxon:".$taxid)
						);
					}
					if(isset($org)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc()."org-name", $org)
						);
					}
				}
			}

			foreach($sts->{'org'} as $anOrg){
				$r = rand();
				$taxname = (string) $anOrg->taxname;
				$taxid = (string) $anOrg->taxid;
				$name = (string) $anOrg->name;
				$keyword = (string) $anOrg->keyword;
				$alias = (string) $anOrg->alias;
				$polymorphic = (string) $anOrg->polymorphic;
				$dseg = (string) $anOrg->dseg;
				$gdb = (string) $anOrg->gdb;
				$rgd = (string) $anOrg->rgd;
				$mgd = (string) $anOrg->mgd;
				$zfin = (string) $anOrg->zfin;
				$pcrsize = (string) $anOrg->pcrsize;
				$dbsnp = (string) $anOrg->dbsnp;
				$warn = (string) $anOrg->warn;
				$wloc = (string) $anOrg->wloc;
				$wmap = (string) $anOrg->wmap;
				$wctg = (string) $anOrg->wctg;
				$wdrift = (string) $anOrg->wdrift;
				$mapview = (string) $anOrg->mapview;
				$u_res = $this->getRes().md5($r.$taxname.$taxid);
				$u_lbl = "org entry for ".$taxname." ".$taxid;

				parent::AddRDF(
					parent::describeIndividual($u_res, $u_lbl, $this->getVoc()."organism" ).
					parent::triplify($uid_res, $this->getVoc()."has-organism", $u_res)
				);
				if(isset($mapview)&& strlen($mapview)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."mapview", $mapview)
					);
				}
				if(isset($wdrift)&& strlen($wdrift)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."wdrift", $wdrift)
					);
				}
				if(isset($wctg)&& strlen($wctg)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."wctg", $wctg)
					);
				}
				if(isset($wmap)&& strlen($wmap)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."wmap", $wmap)
					);
				}
				if(isset($wloc)&& strlen($wloc)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."wloc", $wloc)
					);
				}
				if(isset($warn)&& strlen($warn)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."warn", $warn)
					);
				}
				if(isset($taxname)&& strlen($taxname)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."taxname", $taxname)
					);
				}
				if(isset($name)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."name", $name)
					);
				}
				if(isset($keyword)&& strlen($keyword)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."keyword", $keyword)
					);
				}
				if(isset($alias)&& strlen($alias)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."alias", $alias)
					);
				}
				if(isset($polymorphic)&& strlen($polymorphic)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."polymorphic", $polymorphic)
					);
				}
				if(isset($dseg)&& strlen($dseg)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."dseg", $dseg)
					);
				}
				if(isset($gdb)&& strlen($gdb)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."gdb", $gdb)
					);
				}
				if(isset($rgd)&& strlen($rgd)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."rgd", $rgd)
					);
				}
				if(isset($mgd)&& strlen($mgd)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."mgd", $mgd)
					);
				}
				if(isset($zfin)&& strlen($zfin)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."zfin", $zfin)
					);
				}
				if(isset($pcrsize)&& strlen($pcrsize)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."pcrsize", $pcrsize)
					);
				}
				if(isset($dbsnp) && strlen($dbsnp)){
					parent::AddRDF(
						parent::triplifyString($u_res, $this->getVoc()."dbsnp", $dbsnp)
					);
				}


				foreach($anOrg->{'rhdb'} as $rh){
					$id = (string)$rh->id;
					$panel = (string) $rh->panel;
					$r = rand();
					$u_res = $this->getRes().md5($r.$id.$panel);
					$u_label = "rhdb for ".$id." ".$panel;
					parent::AddRDF(
						parent::describeIndividual($u_res, $u_label, $this->getVoc()."rhdb").
						parent::triplify($uid_res, $this->getVoc().'has-rhdb', $u_res).
						parent::triplifyString($u_res, $this->getVoc().'id', $id).
						parent::triplifyString($u_res, $this->getVoc().'panel', $panel)
					);
				}

				foreach($anOrg->{'unigene'} as $u){
					$ugid = (string)$u->unigene;
					$ugname = (string)$u->ugname;
					$r = rand();
					$u_res = $this->getRes().md5($r.$ugid.$ugname);
					$u_label = "unigene for ".$ugid." ".$ugname;
					if(strlen($ugid) > 0 && strlen($ugname)>0)
					parent::AddRDF(
						parent::describeIndividual($u_res, $u_label, $this->getVoc()."unigene").
						parent::triplify($uid_res, $this->getVoc().'has-unigene', $u_res).
						parent::triplify($u_res, $this->getVoc().'x-unigene', $ugid).
						parent::triplifyString($u_res, $this->getVoc().'ugname', $ugname)
					);

				}

				foreach($anOrg->{'locus'} as $aLocus){
					$lid = (string) $aLocus->lid;
					$lsymbol = (string) $aLocus->lsymbol;
					$lname = (string) $aLocus->lname;
					$lcyto = (string) $aLocus->lcyto;
					$ltype = (string) $aLocus->ltype;
					$r = rand();
					$u_res = $this->getRes().md5($r.$lid);
					$u_label = "locus for ".$lid." ".$lname." ".$lsymbol;
					parent::AddRDF(
						parent::describeIndividual($u_res, $u_label, $this->getVoc()."locus").
						parent::triplify($uid_res, $this->getVoc().'has-locus', $u_res)
					);
					if(isset($lcyto)&& strlen($lcyto)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'cyto', $lcyto)
						);
					}
					if(isset($ltype)&& strlen($ltype)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'ltype', $ltype)
						);
					}

					if(isset($lname)&& strlen($lname)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'name', $lname)
						);
					}

				}

				foreach ($anOrg->{'mappos'} as $aMapPos ) {
					$map = (string) $aMapPos->map;
					$marker = (string) $aMapPos->marker;
					$chr = (string) $aMapPos->chr;
					$chrpos = (string) $aMapPos->chrpos;
					$ctg = (string) $aMapPos->ctg;
					$ctgpos = (string) $aMapPos->ctgpos;
					$coord = (string) $aMapPos->coord;
					$lod = (string) $aMapPos->lod;
					$bin = (string) $aMapPos->bin;
					$binpos = (string)$aMapPos->binpos;
					$het = (string) $aMapPos->het;
					$lab = (string) $aMapPos->lab;
					$unit = (string) $aMapPos->unit;
					$r = rand();
					$u_res = $this->getRes().md5($r.$map);
					$u_label = "locus for ".$map." ".$marker." ".$chr;
					parent::AddRDF(
						parent::describeIndividual($u_res, $u_label, $this->getVoc()."mappos").
						parent::triplify($uid_res, $this->getVoc().'has-mappos', $u_res)
					);

					if(isset($unit)&& strlen($unit)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'unit', $unit)
						);
					}
					if(isset($lab)&& strlen($lab)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'lab', $lab)
						);
					}
					if(isset($het)&& strlen($het)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'het', $het)
						);
					}
					if(isset($binpos)&& strlen($binpos)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'binpos', $binpos)
						);
					}
					if(isset($bin)&& strlen($bin)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'bin', $bin)
						);
					}
					if(isset($lod)&& strlen($lod)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'lod', $lod)
						);
					}
					if(isset($coord)&& strlen($coord)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'coord', $coord)
						);
					}
					if(isset($ctgpos)&& strlen($ctgpos)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'ctgpos', $ctgpos)
						);
					}
					if(isset($ctg)&& strlen($ctg)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'ctg', $ctg)
						);
					}
					if(isset($chrpos)&& strlen($chrpos)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'chrpos', $chrpos)
						);
					}
					if(isset($chr)&& strlen($chr)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'chr', $chr)
						);
					}
					if(isset($marker)&& strlen($marker)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'marker', $marker)
						);
					}
				}

				foreach($anOrg->{'epcr'} as $aEpcr){
					$seqType = (string) $aEpcr->seqtype;
					$acc = (string) $aEpcr->acc;
					$gi = (string) $aEpcr->gi;
					$pos1 = (string) $aEpcr->pos1;
					$pos2 = (string) $aEpcr->pos2;
					$epcr_size = (string) $aEpcr->epcrsize;
					$r = rand();
					$u_res = $this->getRes().md5($r.$gi.$pos1);
					$u_label = "epcr for ".$pos1." ".$pos2;
					parent::AddRDF(
						parent::describeIndividual($u_res, $u_label, $this->getVoc()."epcr").
						parent::triplify($uid_res, $this->getVoc().'has-epcr', $u_res)
					);
					if(isset($seqtype) && strlen($seqtype)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'seqtype', $seqtype)
						);
					}
					if(isset($acc) && strlen($acc)){
						parent::AddRDF(
							parent::triplify($u_res, $this->getVoc().'x-genbank', 'genbank:'.$acc)
						);
					}
					if(isset($gi) && strlen($gi)){
						parent::AddRDF(
							parent::triplify($u_res, $this->getVoc().'x-gi', 'gi:'.$gi)
						);
					}
					if(isset($pos1) && strlen($pos1)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'pos1', $pos1)
						);
					}
					if(isset($pos2) && strlen($pos2)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'pos2', $pos2)
						);
					}
					if(isset($epcr_size)&& strlen($epcr_size)){
						parent::AddRDF(
							parent::triplifyString($u_res, $this->getVoc().'epcr_size', $epcr_size)
						);
					}
				}
			}
			$this->WriteRDFBufferToWriteFile();
		}

	}
	
//get all files from directory recursively
	public function getFileR($directory, $recursive=true) {
		$array_items = array();
		if ($handle = opendir($directory)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					if (is_dir($directory. "/" . $file)) {
						if($recursive) {
								$array_items = array_merge($array_items, $this->getFileR($directory. "/" . $file, $recursive));
						}//if
						$file = $directory . "/" . $file;
						if(is_file($file)){
								$array_items[] = preg_replace("/\/\//si", "/", $file);
						}
					} else {
						$file = $directory . "/" . $file;
						if(is_file($file)){
							$array_items[] = preg_replace("/\/\//si", "/", $file);
						}
					}//else
				}//if
			}//while
			closedir($handle);
		}//if
		return $array_items;
	}//getFileR

	public function filterByExtension($anArrOfFilenames, $anExtension){
		$r = array();
		foreach ($anArrOfFilenames as $afn) {
			$p = pathinfo($afn);
			if(isset($p["extension"])){
				if($p["extension"] == $anExtension){
					$r[] =$afn;
				}
			}
		}
		return $r;
	}



	//makes an assoc array from the metadata of each map record
	private function parseMapRecordMetadata($record_metadata){
		$rm = array();
		$a = explode("\n", $record_metadata);
		foreach ($a as $key => $value) {
			$b = explode(": ", $value);
			if(isset($b[0]) && isset($b[1])){
				$rm[str_replace(" ", "_", trim($b[0]))] = utf8_encode(trim($b[1]));
			}
		}
		return $rm;
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
	* Given an FTP uri get a non recursive list of all files
	*/
	function getFtpFileList($ftp_uri){
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
		$contents = ftp_nlist($conn_id, "/repository/UniSTS");
		foreach($contents as $aFile){
			preg_match("/\/repository\/UniSTS\/(.*.gz)/", $aFile, $matches);
			if(count($matches)){
				$rm[] = $matches[1];
			}
		}
		return $rm;
	}
}//class

?>