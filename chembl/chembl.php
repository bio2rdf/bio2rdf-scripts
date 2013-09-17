<?php
	/*
		Need to develop procedure to download and load a mysql database.
		requires that you have 'mysql' installed and have root access

		source_url: ftp://ftp.ebi.ac.uk/pub/databases/chembl/ChEMBLdb/latest/chembl_14_mysql.tar.gz
	*/


/**
	Copyright (C) 2012 Dana Klassen

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
 * Chembl Parser
 * @version: 0.1
 * @author: Dana Klassen
 * descreiption:
*/
require('../../php-lib/rdfapi.php');

class ChemblParser extends RDFFactory {

	private $version = null ;

	function __construct($argv) {
		parent::__construct();
			$this->SetDefaultNamespace("chembl");
			
			// set and print application parameters
			$this->AddParameter('files',true,'all|compounds|targets|assays|references|properties','','files to process');
			$this->AddParameter('indir',false,null,'/data/download/gene/','directory to download into and parse from');
			$this->AddParameter('outdir',false,null,'/data/rdf/gene/','directory to place rdfized files');
			$this->AddParameter('graph_uri',false,null,null,'provide the graph uri to generate n-quads instead of n-triples');
			$this->AddParameter('gzip',false,'true|false','true','gzip the output');
			$this->AddParameter('download',false,'true|false','false','set true to download files');
			$this->AddParameter('user',false,false,'dba','set the user to access the mysql chembl database');
			$this->AddParameter('pass',false,false,'dba','set the password of the user to access the mysql chembl database');
			$this->AddParameter('db_name',false,null,'chembl_14','set the database table to configure/access the mysql database');
			$this->AddParameter('download_url',false,null,'ftp://ftp.ebi.ac.uk/pub/databases/chembl/ChEMBLdb/latest/');
			
			if($this->SetParameters($argv) == FALSE) {
				$this->PrintParameters($argv);
				exit;
			}
			if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
			if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
			if($this->GetParameterValue('graph_uri')) $this->SetGraphURI($this->GetParameterValue('graph_uri'));
			
		return TRUE;
	}

	function Run(){

		$this->connect_to_db();

		switch($this->GetParameterValue('files')) {
			case "compounds" :
				$this->process_compounds();
				break;
			case "targets":
				$this->process_targets();
				break;
			case "assays":
				$this->process_assays();
				break;
			case "references":
				$this->process_references();
				break;
			case "all":
				$this->all();
				break;
		}
	}

	/*
	*	download the mysql database dump from chembl
	*/
	function download(){
		//where to download the files
		$local_file = $this->GetParameterValue('outdir')."chembl_14_mysql.tar.gz";
		$remote_file = "chembl_14_mysql.tar.gz";

		$connection  = ftp_connect($this->GetParameterValue('download_uri'));
		$login_result = ftp_login($connection,"","");

		if(ftp_get($connection,$local_file,$remote_file)) {
			echo "successfully downloaded file to $local_file\n";
		} else {
			echo "There was a problem downloading the chembl mysql database file. \n";
		}

		ftp_close($connection);
	}

	/*
	*	Configure and load the mysql database
	*/
	function load_chembl_mysql() {
		//mysql -u username -p < dump/file/path/filename.sql
		$local_file = $this->GetParameterValue("outdir")."chembl_14_mysql.tar.gz";
		$dump_cmd = "mysql -u ".$this->GetParameterValue("user")." -p ".$this->GetParameterValue("pass")." ".$this->GetParameterValue("db_name")." < ".$local_file;
		
		if(shell_exec($dump_cmd)){
			echo "Successfully loaded the chembl mysql database.\n";
		} else {
			echo "There was a problem loading the chembl database into mysql.\n";
		}
	}

	function process_all(){
		$this->process_compounds();
		$this->process_targets();
		$this->process_assays();
		$this->process_references();
		$this->process_properties();
	}

	/*
	*	process activities
	*/
	function process_activities(){

		$this->set_write_file("activities");

		$allIDs = mysql_query("SELECT DISTINCT * FROM activities" . $limit);

		while ($row = mysql_fetch_assoc($allIDs)) {
			$activity = "chembl:activity_".$row['activity_id'];
			$this->AddRDF($this->QQuad($activity,"rdf:type","chembl_vocabulary:Activity"));

		if ($row['doc_id'] != '-1') {
			$reference = "chembl:reference_".$row["doc_id"];
			$this->AddRDF($this->QQuad($activity,"chembl_vocabulary:citesAsDataSource",$reference));
		}
			$assay = "chembl:assay_".$row['assay_id'];
			$molecule = "chembl:compound_".$row['molregno'];

			$this->AddRDF($this->QQuad($activity,"chembl_vocabulary:onAssay",$assay));
			$this->AddRDF($this->QQuad($activity,"forMolecule",$molecule));

		if ($row['relation']) {
			$this->AddRDF($this->QQuadl($activity, "chembl_vocabulary:relation",  $row['relation'] ));
		}
		if ($row['standard_value']) {
			$this->AddRDF($this->QQuadl($activity,"chembl_vocabulary:standardValue",$row['standard_value']));
			$this->AddRDF($this->QQuadl($activity,"chembl_vocabulary:standardUnits",$row['standard_units']));
			$this->AddRDF($this->QQuadl($activity,"chembl_vocabulary:standardValue",$row['standard_type']));
		}

		}
	}
	/*
	*	process the compounds table into RDF
	*/
	function process_compounds() {
		$this->set_write_file("compounds");

		$allIDs = mysql_query("SELECT DISTINCT molregno FROM molecule_dictionary");

		$num = mysql_numrows($allIDs);

		while ($row = mysql_fetch_assoc($allIDs)) {
		$molregno = $row['molregno'];
		$molecule = "chembl:compound_".$molregno;

		# get the literature references
		$refs = mysql_query("SELECT DISTINCT doc_id FROM compound_records WHERE molregno = $molregno");
		while ($refRow = mysql_fetch_assoc($refs)) {
			if ($refRow['doc_id'])
				$this->AddRDF($this->QQuad($molecule,"chembl_vocabulary:citesAsDataSource","chembl:reference_".$refRow['doc_id']));
			}

			# get the compound type, ChEBI, and ChEMBL identifiers
			$chebi = mysql_query("SELECT DISTINCT * FROM molecule_dictionary WHERE molregno = $molregno");
			if ($chebiRow = mysql_fetch_assoc($chebi)) {
			
			if ($chebiRow['molecule_type']) {
			  if ($chebiRow['molecule_type'] == "Small molecule") {
			  		$this->AddRDF($this->QQuad($molecule,"rdfs:subClassOf","chembl_vocabulary:SmallMolecule"));
			  } else if ($chebiRow['molecule_type'] == "Protein") {
			   		$this->AddRDF($this->QQuad($molecule,"rdfs:subClassOf","chembl_vocabulary:Protein"));
			  } else if ($chebiRow['molecule_type'] == "Cell") {
			  		$this->AddRDF($this->QQuad($molecule,"rdfs:subClassOf","chembl_vocabulary:Cell"));
			  } else if ($chebiRow['molecule_type'] == "Oligosaccharide") {
			   		$this->AddRDF($this->QQuad($molecule,"rdfs:subClassOf","chembl_vocabulary:Oligosaccharide"));
			  } else if ($chebiRow['molecule_type'] == "Oligonucleotide") {
			   		$this->AddRDF($this->QQuad($molecule,"rdfs:subClassOf","chembl_vocabulary:Oligonucleotide"));
			  } else if ($chebiRow['molecule_type'] == "Antibody") {
			  		$this->AddRDF($this->QQuad($molecule,"rdfs:subClassOf","chembl_vocabulary:Antibody"));
			  }
			}
			if ($chebiRow['max_phase'] == "4") {
				$this->AddRDF($this->QQuad($molecule,"chembl_vocabulary:hasRole","chembl_vocabulary:Drug"));
			}

			$chebi = "chebi:".$chebiRow['chebi_id'];
			$chembl = "chembl:".$chebiRow['chembl_id'];
			$this->AddRDF($this->QQuad($molecule,"owl:equivalentClass",$chebi));
			$this->AddRDF($this->QQuad($chebi,"owl:equivalentClass",$molecule));
			$this->AddRDF($this->QQuad($molecule,"owl:equivalentClass",$chembl));
			$this->AddRDF($this->QQuad($chembl,"owl:equivalentClass",$molecule));
			$this->AddRDF($this->QQuad($chebi,"owl:equivalentClass",$chembl));

			// add some human readable labels and bio2rdf requirements
			$this->AddRDF($this->QQuadl($chebi,"dc:identifier",$chebiRow['chebi_id']));
			$this->AddRDF($this->QQuadl($chembl,"dc:identifier",$chebiRow['chembl_id']));
		}

		# get the structure information
		$structs = mysql_query("SELECT DISTINCT * FROM compound_structures WHERE molregno = $molregno");
		while ($struct = mysql_fetch_assoc($structs)) {
			if ($struct['canonical_smiles']) {
			  $smiles = $struct['canonical_smiles'];
			  $smiles = str_replace("\\", "\\\\", $smiles);
			  $smiles = str_replace("\n", "", $smiles);
			 
			  $this->AddRDF($this->QQuadl($molecule,"chembl_vocabulary:smiles",$smiles));

			}
			if ($struct['standard_inchi']) {
				$this->AddRDF($this->QQuadl($molecule,"chembl_vocabulary:standardInchi",$struct['standard_inchi']));
			}

			if ($struct['standard_inchi_key']) {
				$this->AddRDF($this->QQuadl($molecule,"chembl_vocabulary:standardInchiKey",$struct['standard_inchi_key']));
			}

			$this->WriteRDFBufferToWriteFile();
		}

		# get parent/child information
		$hierarchies = mysql_query("SELECT DISTINCT * FROM molecule_hierarchy WHERE molregno = $molregno");
			while ($hierarchy = mysql_fetch_assoc($hierarchies)) {
				if ($hierarchy['parent_molregno'] != $molregno) {
				  $parent = "chembl:".$hierarchy['parent_molregno'];
				  $this->AddRDF($this->QQuad($molecule,"chembl_vocabulary:hasParent",$parent));
				}
				if ($hierarchy['active_molregno'] != $molregno) {
				  $child = "chembl:".$hierarchy['active_molregno'];
				  $this->AddRDF($this->QQuad($molecule,"chembl_vocabulary:activeCompound",$child));
				}

				$this->WriteRDFBufferToWriteFile();
			}

		$this->WriteRDFBufferToWriteFile();

		}

		$this->GetWriteFile()->Close();
	}


	function set_write_file($name){
		 $write_file = $this->GetParameterValue("outdir").$name.".ttl";
		 echo $write_file."----> processing\n";
		 // set the compression
		 $gz=false;
		 if( $this->GetParameterValue('gzip')){
		 	$write_file.= ".gz";
		 	$gz=true;
		 }

		 $this->SetWriteFile($write_file,$gz);
	}

	/*
	* connect to the chembl database
	*/
	function connect_to_db(){
		$user = $this->GetParameterValue("user");
		$pwd = $this->GetParameterValue("pass");
		$db = $this->GetParameterValue('db_name');
		mysql_connect("127.0.0.1",$user,$pwd) or die(mysql_error()) ;
		mysql_select_db($db) or die(mysql_error());
	}

	function process_targets() {

		$this->set_write_file("targets");

		$allIDs = mysql_query("SELECT DISTINCT * FROM target_dictionary");

		$num = mysql_numrows($allIDs);

		while ($row = mysql_fetch_assoc($allIDs)) {

			$target = "chembl:target_". $row['tid'];
			$this->AddRDF($this->QQuad($target,"rdf:type","chembl_vocabulary:Target"));

			if ($row['target_type'] == 'PROTEIN') {
				$this->AddRDF($this->QQuad($target,"rdfs:subClassOf","chembl_vocabulary:Protein"));
			} else {
				$this->AddRDF($this->QQuad($target,"chembl_vocabulary:hasTargetType","chembl:".$row['target_type']));
			}

			$chembl = "chembl:". $row['chembl_id'];

			$this->AddRDF($this->QQuad($chembl,"owl:equivalentClass",$target));
			$this->AddRDF($this->QQuad($target,"owl:equivalentClass",$chembl));
			$this->AddRDF($this->QQuad($target,"dc:identifier",$row['chembl_id']));

			if ($row['organism']){
				$this->AddRDF($this->QQuadl($target,"chembl_vocabulary:organism",$row['organism']));
			}
			if ($row['description']){
				$this->AddRDF($this->QQuadl($target,"chembl_vocabulary:hasDescription",str_replace("\"", "\\\"", $row['description']) ));
			}
			if ($row['synonyms']) {
				$synonyms = preg_split("/[;]+/", $row['synonyms']);
				foreach ($synonyms as $i => $synonym) {
					$this->AddRDF($this->QQuadl($target,"chembl_vocabulary:hasSynonym",str_replace("\"", "\\\"", trim($synonym))));
				}
			}
			if ($row['keywords']) {
				$keywords = preg_split("/[;]+/", $row['keywords']);
				foreach ($keywords as $i => $keyword) {
					$this->AddRDF($this->QQuadl($target,"chembl_vocabulary:hasKeyword",str_replace("\"", "\\\"", trim($keyword)) ));
				}
			}
			if ($row['protein_sequence']){
				$this->AddRDF($this->QQuadl($target,"chembl_vocabulary:hasSequence",$row['protein_sequence']));
			}
			if ($row['ec_number']) {
				$this->AddRDF($this->QQuadl($target,"dc:identifier",$row['ec_number']));
			}
			if ($row['protein_accession']) {
				$this->AddRDF($this->QQuad($target,"owl:equivalentClass","uniprot:". $row['protein_accession']));
			}
			if ($row['tax_id']){
				$this->AddRDF($this->QQuad($target, "owl:equivalentClass","taxon:".$row['tax_id']));
			}

			# classifications
			$class = mysql_query("SELECT DISTINCT * FROM target_class WHERE tid = \"" . $row['tid'] . "\"");
			if ($classRow = mysql_fetch_assoc($class)) {
				if ($classRow['l1']) $this->AddRDF($this->QQuadl( $target,"chembl_vocabulary:classL1", str_replace("\"", "\\\"", $classRow['l1'])));
				if ($classRow['l2']) $this->AddRDF($this->QQuadl( $target,"chembl_vocabulary:classL2", str_replace("\"", "\\\"", $classRow['l2'])));
				if ($classRow['l3']) $this->AddRDF($this->QQuadl( $target,"chembl_vocabulary:classL3", str_replace("\"", "\\\"", $classRow['l3'])));
				if ($classRow['l4']) $this->AddRDF($this->QQuadl( $target,"chembl_vocabulary:classL4", str_replace("\"", "\\\"", $classRow['l4'])));
				if ($classRow['l5']) $this->AddRDF($this->QQuadl( $target,"chembl_vocabulary:classL5", str_replace("\"", "\\\"", $classRow['l5'])));
				if ($classRow['l6']) $this->AddRDF($this->QQuadl( $target,"chembl_vocabulary:classL6", str_replace("\"", "\\\"", $classRow['l6'])));
				if ($classRow['l7']) $this->AddRDF($this->QQuadl( $target,"chembl_vocabulary:classL7", str_replace("\"", "\\\"", $classRow['l7'])));
				if ($classRow['l8']) $this->AddRDF($this->QQuadl( $target,"chembl_vocabulary:classL8", str_replace("\"", "\\\"", $classRow['l8'])));
			}

			if ($row['pref_name'])
				$this->AddRDF($this->QQuadl($target,"dc:title",$row['pref_name']));
			}
	}

	/*
	*	parse the assays tables
	*/
	function process_assays() {

		$this->set_write_file("assays");

		$allIDs = mysql_query(
		    "SELECT DISTINCT * FROM assays, assay_type " .
		    "WHERE assays.assay_type = assay_type.assay_type"
		);

		$num = mysql_numrows($allIDs);

		while ($row = mysql_fetch_assoc($allIDs)) {

		  $assay = "chembl:assay_".$row['assay_id'];
		  $this->AddRDF($this->QQuad($assay,"rdf:type","chembl_vocabulary:Assay"));

		  //chembl assay id
		  $chembl = "chembl:". $row['chembl_id'];
		  $this->AddRDF($this->QQuadl($assay,"dc:identifier",$row['chembl_id']));
		  $this->AddRDF($this->QQuad($assay,"owl:equivalentClass",$chembl));
		  $this->AddRDF($this->QQuad($chembl,"owl:equivalentClass",$assay));
		  $this->WriteRDFBufferToWriteFile();

		  if ($row['description']) {
		    # clean up description
		    $description = $row['description'];
		    $description = str_replace("\\", "\\\\", $description);
		    $description = str_replace("\"", "\\\"", $description);
		    $this->AddRDF($this->QQuadl($assay,"chembl_vocabulary:hasDescription",$description));
		  }

		  if ($row['doc_id']){
		  	$this->AddRDF($this->QQuad($assay,"chembl_vocabulary:citesAsDataSource","chembl:reference_".$row['doc_id']));
		  }

		  $props = mysql_query("SELECT DISTINCT * FROM assay2target WHERE assay_id = " . $row['assay_id']);
		  
		  while ($prop = mysql_fetch_assoc($props)) {		  	  
		    if ($prop['tid']) {
		      $target = "chembl:target_".$prop['tid'];
		      $this->AddRDF($this->QQuad($assay,"chembl_vocabulary:hasTarget",$target));

		      if ($prop['confidence_score']) {
		        $targetScore = "chembl:tscore_".md5($assay.$prop['tid']);
		        $this->AddRDF($this->QQuad($assay,"chembl_vocabulary:hasTargetScore",$targetScore));
		        $this->AddRDF($this->QQuad($targetScore,"chembl_vocabulary:forTarget",$target));
		        $this->AddRDF($this->QQuadl($targetScore,"rdf:value",$prop['confidence_score']));
		      }
		    }

		     $this->WriteRDFBufferToWriteFile();

		  }

		  $this->AddRDF($this->QQuad($assay,"chembl_vocabulary:hasAssayType","chembl_vocabulary:".$row['assay_desc']));
		  $this->WriteRDFBufferToWriteFile();
		}
	}

	/*
	* process references and information sources about assays
	*/
	function process_references(){

		$this->set_write_file("references");

		$allIDs = mysql_query("SELECT DISTINCT journal FROM docs WHERE doc_id > 0 " . $limit);

		$num = mysql_numrows($allIDs);

		while ($row = mysql_fetch_assoc($allIDs)) {
		if (strlen($row['journal']) > 0) {
		//echo triple($JRN . "j" . md5($row['journal']), $RDF . "type", $BIBO . "Journal");
		//echo data_triple($JRN . "j" . md5($row['journal']), $DC . "title", $row['journal']);
		}
		}
		

		$allIDs = mysql_query("SELECT DISTINCT * FROM docs WHERE doc_id > 0 " . $limit);

		$num = mysql_numrows($allIDs);

		while ($row = mysql_fetch_assoc($allIDs)) {

			$reference = "chembl:reference_". $row['doc_id'];
			$this->AddRDF($this->QQuad($reference,"rdf:type","chembl_vocabulary:Article"));
			if ($row['doi']) {
				$this->AddRDF($this->QQuadl($reference,"chembl_vocabulary:hasDoi",$row['doi']));
			}

			if ($row['pubmed_id']) {
				$this->AddRDF($this->QQuadl($reference,"owl:equivalentClass","pmid:".$row['pubmed_id']));
			}

			$this->AddRDF($this->QQuadl($reference,"dc:date",$row['year'] ));
			$this->AddRDF($this->QQuadl($reference,"chembl_vocabulary:hasVolume",$row['volume'] ));
			$this->AddRDF($this->QQuadl($reference,"chembl_vocabulary:hasIssue",$row['issue'] ));
			$this->AddRDF($this->QQuadl($reference,"chembl_vocabulary:hasFirstPage",$row['first_page'] ));
			$this->AddRDF($this->QQuadl($reference,"chembl_vocabulary:hasLastPage",$row['last_page'] ));
			$this->AddRDF($this->QQuadl($reference,"chembl_vocabulary:hasJournal",$row['journal'] ));

			$this->WriteRDFBufferToWriteFile();
		}
	}

	function process_properties(){

		$this->set_write_file("properties");

		$allIDs = mysql_query("SELECT * FROM compound_properties " . $limit);

		$num = mysql_numrows($allIDs);

		# CHEMINF mappings
		$descs = array(
			"alogp" => "ALogP",
			"hba" => "Hba",
			"hbd" => "CHEMINF_000310",
			"psa" => "CHEMINF_000308",
			"rtb" => "CHEMINF_000311",
			"acd_most_apka" => "CHEMINF_000324",
			"acd_most_bpka" => "CHEMINF_000325",
			"acd_logp" => "CHEMINF_000321",
			"acd_logd" => "CHEMINF_000323",
			"num_ro5_violations" => "CHEMINF_000314",
			"ro3_pass" => "CHEMINF_000317",
			"med_chem_friendly" => "CHEMINF_000319",
			"full_mwt" => "CHEMINF_000198",
		);
		$descTypes = array(
			"alogp" => "double",
			"hba" => "nonNegativeInteger",
			"hbd" => "nonNegativeInteger",
			"psa" => "double",
			"rtb" => "nonNegativeInteger",
			"acd_most_apka" => "double",
			"acd_most_bpka" => "double",
			"acd_logp" => "double",
			"acd_logd" => "double",
			"num_ro5_violations" => "nonNegativeInteger",
			"ro3_pass" => "string",
			"med_chem_friendly" => "string",
			"full_mwt" => "double",
		);

		while ($row = mysql_fetch_assoc($allIDs)) {
			$molregno = $row['molregno'];
			$molecule = "chembl:". $molregno;

			foreach ($descs as $value => $p) {
				if ($row[$value]) {
					$molprop = "chembl:molprop_".md5($molecule.$row[$value]);
					$this->AddRDF($this->QQuad($molecule,"chembl_vocabulary:hasProperty",$molprop));
					$this->AddRDF($this->QQuad($molprop,"rdf:type","chembl_vocabulary:".$p));
					$this->AddRDF($this->QQuadl($molprop,"rdf:value",$row[$value]));
					
					// still need to add datatype
					//echo typeddata_triple($molprop, $CHEMINF . "SIO_000300", $row[$value], $XSD . $descTypes[$value] );
				}
			}

			$this->WriteRDFBufferToWriteFile();

		}
	}
}
set_error_handler('error_handler');
$parser = new ChemblParser($argv);
$parser->Run();

?>
