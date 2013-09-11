<?php

/**
	Copyright (C) 2013 Dana Klassen, Alison Callahan

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
 * @version 0.1
 * @author Dana Klassen
 * @author Alison Callahan
 * @description Bio2RDF parser for ChEMBL SQL dump loaded into a MySQL database
*/

class ChemblParser extends Bio2RDFizer {

	private $version = null ;

	function __construct($argv) {
		parent::__construct($argv, "chembl");
		
		parent::addParameter('files',true,'all|activities|assays|components|compounds|documents|domains|protein_families|targets','all','all or comma-separated list of files to process');
		parent::addParameter('mysqluser',true,null,'dba','set the user to access the mysql chembl database');
		parent::addParameter('mysqlpass',true,null,'dba','set the password of the user to access the mysql chembl database');
		parent::addParameter('db_name',true,null,'chembl_16','the database to access');
		parent::addParameter('db_ip', true, null, 'localhost', 'the IP of the MySQL server hosting the database to access');
		parent::initialize();			
	}

	function run(){

		$this->connect_to_db();

		if(parent::getParameterValue('process') === true) 
		{
			$this->process();
		}
	}

	/*
	* connect to the chembl database
	*/
	function connect_to_db(){

		$ip = parent::getParameterValue("db_ip");
		$user = parent::getParameterValue("mysqluser");
		$pwd = parent::getParameterValue("mysqlpass");
		$db = parent::getParameterValue("db_name");

		$connection = new mysqli($ip, $user, $pwd, $db);

		if ($connection->connect_errno) {
	    	printf("Connection failed: %s\n", $connection->connect_error);
	   		exit();
		}
		return $connection;
	}

	/*
	* process all files
	*/
	function process(){

		echo "Connecting to database ...";

		$connection = $this->connect_to_db();

		$odir = parent::getParameterValue('outdir');

		// get the files to process
		if(parent::getParameterValue('files') == 'all') {
			$files = explode("|",$this->GetParameterList('files'));
			array_shift($files);
		} else {
			$files = explode(",",$this->GetParameterValue('files'));
		}

		//set graph URI to be dataset URI
		$graph_uri = parent::getGraphURI();
		if(parent::getParameterValue('dataset_graph') == true) parent::setGraphURI(parent::getDatasetURI());

		//start generating dataset description file
		$dataset_description = '';
		$source_file = (new DataResource($this))
				->setURI("ftp://ftp.ebi.ac.uk/pub/databases/chembl/ChEMBLdb/latest")
				->setTitle("EBI ChEMBL database")
				->setRetrievedDate( date ("Y-m-d\TG:i:s\Z", time()))
				->setFormat("SQL")
				->setPublisher("http://www.ebi.ac.uk")
				->setHomepage("http://www.ebi.ac.uk/chembl/")
				->setRights("use-share-modify")
				->setRights("by-attribution")
				->setLicense("ftp://ftp.ebi.ac.uk/pub/databases/chembl/ChEMBLdb/latest/LICENSE")
				->setDataset("http://identifiers.org/chembl/");

		$dataset_description .= $source_file->toRDF();

		// now go through each data table and process
		foreach($files AS $file) {
			echo "Processing $file... ";

			// set the write file
			$suffix = parent::getParameterValue('output_format');
			$outfile = $file.'.'.$suffix; 
			$gz = false;
			if(strstr(parent::getParameterValue('output_format'), "gz")) {
				$gz = true;
			}

			parent::setWriteFile($odir.$outfile, $gz);
			
			// process
			$fnx = $file;
			$this->$fnx($connection);
			
			// write to file
			parent::writeRDFBufferToWriteFile();
			parent::getWriteFile()->close();
			
			echo "done!".PHP_EOL;

			echo "Generating dataset description for $outfile... ";
			$prefix = parent::getPrefix();
			$bVersion = parent::getParameterValue('bio2rdf_release');
			$date = date ("Y-m-d\TG:i:s\Z");
			$output_file = (new DataResource($this))
				->setURI("http://download.bio2rdf.org/release/$bVersion/$prefix/$outfile")
				->setTitle("Bio2RDF v$bVersion RDF version of $prefix $file data (generated at $date)")
				->setSource($source_file->getURI())
				->setCreator("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/chembl/chembl.php")
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
			
			$dataset_description .= $output_file->toRDF();
			echo "done!".PHP_EOL;
		}
		
		//set graph URI back to default value
		parent::setGraphURI($graph_uri);

		//write dataset description to file
		parent::setWriteFile($odir.parent::getBio2RDFReleaseFile());
		parent::getWriteFile()->write($dataset_description);
		parent::getWriteFile()->close();
		
	}

	/*
	*	process activities
	*/
	function activities($connection){

		$sql = 'SELECT DISTINCT * FROM activities';

		if($result = $connection->query($sql)){
			while($row = $result->fetch_assoc()){

				$activity = $row['activity_id'];
				$doc_id = $row['doc_id'];
				$assay_id = $row['assay_id'];
				$record_id = $row['record_id'];
				$molecule = $row['molregno'];

				$standard_relation = $row['standard_relation'];
				$standard_value = $row['standard_value'];
				$standard_units = $row['standard_units'];
				$standard_flag = $row['standard_flag'];
				$standard_type = $row['standard_type'];

				$published_value = $row['published_value'];
				$published_units = $row['published_units'];
				$published_type = $row['published_type'];
				$published_relation = $row['published_relation'];

				$activity_comment = $row['activity_comment'];
				$data_validity_comment = $row['data_validity_comment'];
				$potential_duplicate = $row['potential_duplicate'];
				$pchembl_value = $row['pchembl_value'];

				$chembl_compound_sql = 'SELECT `chembl_id` FROM `molecule_dictionary` WHERE `molregno`="'.$molecule.'"';
				$chembl_compound_result = $connection->query($chembl_compound_sql);
				$chembl_compound_row = $chembl_compound_result->fetch_array();
				$chembl_compound_id = $chembl_compound_row[0];

				$chembl_assay_sql = 'SELECT `chembl_id` FROM `assays` WHERE `assay_id`="'.$assay_id.'"';
				$chembl_assay_result = $connection->query($chembl_assay_sql);
				$chembl_assay_row = $chembl_assay_result->fetch_array();
				$chembl_assay_id = $chembl_assay_row[0];

				$activity_id = parent::getRes()."ACTIVITY_".$activity;
				$activity_label = "Activity ".$standard_relation." ".$standard_value." ".$standard_units." for compound ".$chembl_compound_id;
				if($standard_type !== "" && $standard_type !== NULL){
					$activity_label .= " (".$standard_type.")";
				}

				parent::addRDF(
					parent::describeIndividual($activity_id, $activity_label, parent::getVoc()."ActivityAssayResult")
				);

				parent::addRDF(
					parent::triplify($activity_id, parent::getVoc()."compound", parent::getNamespace().$chembl_compound_id)					
				);

				parent::addRDF(
					parent::triplify($activity_id, parent::getVoc()."assay", parent::getNamespace().$chembl_assay_id)
				);

				if($doc_id !== NULL){
					$chembl_doc_sql = 'SELECT `chembl_id` FROM `docs` WHERE `doc_id`="'.$doc_id.'"';
					$chembl_doc_result = $connection->query($chembl_doc_sql);
					$chembl_doc_row = $chembl_doc_result->fetch_array();
					$chembl_doc_id = $chembl_doc_row[0];

					parent::addRDF(
						parent::triplify($activity_id, parent::getVoc()."document", parent::getNamespace().$chembl_doc_id)
					);
				}

				if($standard_flag == "1"){
					if($standard_value !== '' && $standard_value !== NULL){
						$sa_id = parent::getRes()."STANDARD_ACTIVITY_".$activity;
						$sa_label = "Standard activity ".$standard_relation." ".$standard_value." ".$standard_units." for compound ".$chembl_compound_id;
						if($standard_type !== "" && $standard_type !== NULL){
							$sa_label .= " (".$standard_type.")";
						}
						parent::addRDF(
							parent::describeIndividual($sa_id, $sa_label, parent::getVoc()."StandardActivity").
							parent::triplify($activity_id, parent::getVoc()."standard-activity", $sa_id)
						);

						parent::addRDF(
							parent::triplifyString($sa_id, "rdf:value", $standard_value)
						);

						if($standard_relation !== '' && $standard_relation !== NULL){
							parent::addRDF(
								parent::triplifyString($sa_id, parent::getVoc()."standard-relation", $standard_relation)
							);
						}

						if($standard_units !== '' && $standard_units !== NULL){
							parent::addRDF(
								parent::triplifyString($sa_id, parent::getVoc()."standard-units", $standard_units)
							);
						}
						
						if($standard_type !== '' && $standard_type !== NULL){
							parent::addRDF(
								parent::triplifyString($sa_id, parent::getVoc()."standard-type", $standard_type)
							);
						}
					}
				}
				
				if($published_value !== '' && $published_value !== NULL){

					$pa_id = parent::getRes()."PUBLISHED_ACTIVITY_".$activity;
					$pa_label = "Published activity ".$published_relation." ".$published_value." ".$published_units." for compound ".$chembl_compound_id;
					if($published_type !== "" && $published_type !== NULL){
						$pa_label .= " (".$published_type.")";
					}

					parent::addRDF(
						parent::describeIndividual($pa_id, $pa_label, parent::getVoc()."PublishedActivity").
						parent::triplify($activity_id, parent::getVoc()."published-activity", $pa_id)
					);

					parent::addRDF(
						parent::triplifyString($pa_id, "rdf:value", $published_value)
					);

					if($published_relation !== '' && $published_relation !== NULL){
						parent::addRDF(
							parent::triplifyString($pa_id, parent::getVoc()."published-relation", $published_relation)
						);
					}

					if($published_units !== '' && $published_units !== NULL){
						parent::addRDF(
							parent::triplifyString($pa_id, parent::getVoc()."published-units", $published_units)
						);
					}
					
					if($published_type !== '' && $published_type !== NULL){
						parent::addRDF(
							parent::triplifyString($pa_id, parent::getVoc()."published-type", $published_type)
						);
					}
				}

				if($activity_comment !== '' && $activity_comment !== NULL){
					parent::addRDF(
						parent::triplifyString($activity_id, parent::getVoc()."activity-comment", $activity_comment)
					);
				}

				if($data_validity_comment !== '' && $data_validity_comment !== NULL){
					parent::addRDF(
						parent::triplifyString($activity_id, parent::getVoc()."data-validity-comment", $data_validity_comment)
					);
				}

				if($potential_duplicate !== '' && $potential_duplicate !== NULL){
					$boolean = "false";
					
					if($potential_duplicate == "0"){
						$boolean = "true";
					}

					parent::addRDF(	
						parent::triplifyString($activity_id, parent::getVoc()."potential-duplicate", $boolean)
					);
				}

				if($pchembl_value !== '' && $pchembl_value !== NULL){
					parent::addRDF(
						parent::triplifyString($activity_id, parent::getVoc()."pchembl-value", $pchembl_value)
					);
				}
				parent::writeRDFBufferToWriteFile();
			}
			/* free result set */
    		$result->free();
		}
	}

	/*
	* process the assays table
	*/
	function assays($connection) {

		$sql = 'SELECT DISTINCT * FROM assays, assay_type WHERE assays.assay_type = assay_type.assay_type';
		if($result = $connection->query($sql)){
			while($row = $result->fetch_assoc()){
				
				$doc_id = $row['doc_id'];
				$description = $row['description'];
				$assay_test_type = $row['assay_test_type'];
				$assay_category = $row['assay_category'];
				$assay_organism = $row['assay_organism'];
				$assay_tax_id = $row['assay_tax_id'];
				$assay_strain = $row['assay_strain'];
				$assay_tissue = $row['assay_tissue'];
				$assay_cell_type = $row['assay_cell_type'];
				$assay_subcellular_fraction = $row['assay_subcellular_fraction'];
				$target_id = $row['tid'];
				$relationship_type = $row['relationship_type'];
				$confidence_score = $row['confidence_score'];
				$curated_by = $row['curated_by'];
				$src_id = $row['src_id'];
				$src_assay_id = $row['src_assay_id'];
				$chembl_id = $row['chembl_id'];
				$assay_desc = $row['assay_desc'];

				$chembl_doc_sql = 'SELECT `chembl_id` FROM `docs` WHERE `doc_id`="'.$doc_id.'"';
				$chembl_doc_result = $connection->query($chembl_doc_sql);
				$chembl_doc_row = $chembl_doc_result->fetch_array();
				$chembl_doc_id = $chembl_doc_row[0];
				$chembl_doc_result->free();

				$chembl_target_sql = 'SELECT `chembl_id`, `pref_name` FROM `target_dictionary` WHERE `tid`="'.$target_id.'"';
				$chembl_target_result = $connection->query($chembl_target_sql);
				$chembl_target_row = $chembl_target_result->fetch_assoc();
				$chembl_target_result->free();

				$target_chembl_id = $chembl_target_row['chembl_id'];
				$target_pref_name = $chembl_target_row['pref_name'];

				$relationship_type_sql = 'SELECT `relationship_desc` FROM `relationship_type` WHERE `relationship_type`="'.$relationship_type.'"';
				$relationship_type_result = $connection->query($relationship_type_sql);
				$relationship_type_row = $relationship_type_result->fetch_array();
				$relationship_type_result->free();
				$relationship_type_desc = $relationship_type_row[0];

				$src_id_sql = 'SELECT `src_description` FROM `source` WHERE `src_id`="'.$src_id.'"';
				$src_id_result = $connection->query($src_id_sql);
				$src_id_row = $src_id_result->fetch_array();
				$src_id_result->free();
				$source_desc = $src_id_row[0];

				$assay_id = parent::getNamespace().$chembl_id;

				if($target_pref_name !== "Unchecked"){
					$assay_label = $assay_desc." assay targeting ".$target_pref_name." in ".$assay_organism;
				} else {
					$assay_label = $assay_desc." assay in ".$assay_organism;
				}

				parent::addRDF(
					parent::describeIndividual($assay_id, $assay_label, parent::getVoc()."Assay", null, $description)
				);

				parent::addRDF(
					parent::triplify($assay_id, parent::getVoc()."target", parent::getNamespace().$target_chembl_id)
				);

				parent::addRDF(
					parent::triplify($assay_id, parent::getVoc()."document", parent::getNamespace().$chembl_doc_id)
				);
				
				parent::addRDF(
					parent::triplify($assay_id, parent::getVoc()."organism", "ncbitaxon:".$assay_tax_id)
				);

				if(isset($assay_test_type) && !empty($assay_test_type)){
					parent::addRDF(
						parent::triplifyString($assay_id, parent::getVoc()."assay-test-type", $assay_test_type)
					);
				}

				if(isset($assay_category) && !empty($assay_category)){
					parent::addRDF(
						parent::triplifyString($assay_id, parent::getVoc()."assay-category", $assay_category)
					);
				}

				if($assay_strain !== '' && $assay_strain !== NULL){
					parent::addRDF(
						parent::triplifyString($assay_id, parent::getVoc()."strain", $assay_strain)
					);
				}

				if($assay_tissue !== '' && $assay_tissue !== NULL){
					parent::addRDF(
						parent::triplifyString($assay_id, parent::getVoc()."tissue", $assay_tissue)
					);
				}

				if($assay_cell_type !== '' && $assay_cell_type !== NULL){
					parent::addRDF(
						parent::triplifyString($assay_id, parent::getVoc()."cell-type", $assay_cell_type)
					);
				}

				if($assay_subcellular_fraction !== '' && $assay_subcellular_fraction !== NULL){
					parent::addRDF(
						parent::triplifyString($assay_id, parent::getVoc()."subcellular-fraction", $assay_subcellular_fraction)
					);
				}

				parent::addRDF(
					parent::triplifyString($assay_id, parent::getVoc()."relationship-type", $relationship_type_desc)
				);

				if($confidence_score !== '' && $confidence_score !== NULL){
					parent::addRDF(
						parent::triplifyString($assay_id, parent::getVoc()."confidence-score", $confidence_score)
					);
				}

				if(isset($curated_by) && !empty($curated_by)){
					parent::addRDF(
						parent::triplifyString($assay_id, parent::getVoc()."curated-by", $curated_by)
					);
				}

				parent::addRDF(
					parent::triplifyString($assay_id, parent::getVoc()."source", $source_desc)
				);

				if(isset($src_assay_id) && !empty($src_assay_id)){
					parent::addRDF(
						parent::triplifyString($assay_id, parent::getVoc()."source-assay-identifier", $src_assay_id)
					);
				}
				parent::writeRDFBufferToWriteFile();
			}//while
			$result->free();
		}//if
	}

	function components($connection){
		$sql = 'SELECT * FROM `component_sequences`';
		if($result = $connection->query($sql)){
			while($row = $result->fetch_assoc()){
				$component_id = $row['component_id'];
				$component_type = $row['component_type'];
				$accession = $row['accession'];
				$sequence = $row['sequence'];
				$sequence_md5sum = $row['sequence_md5sum'];
				$description = $row['description'];
				$tax_id = $row['tax_id'];
				$db_source = $row['db_source'];
				$db_version = $row['db_version'];

				$cid = parent::getRes()."COMPONENT_".$component_id;

				parent::addRDF(
					parent::describeIndividual($cid, $description, parent::getVoc()."Component")
				);

				parent::addRDF(
					parent::triplifyString($cid, parent::getVoc()."component-type", $component_type)
				);

				parent::addRDF(
					parent::triplifyString($cid, parent::getVoc()."accession", $accession).
					parent::triplify($cid, parent::getVoc()."x-uniprot", "uniprot:".$accession)
				);

				parent::addRDF(
					parent::triplifyString($cid, parent::getVoc()."sequence", $sequence)
				);

				parent::addRDF(
					parent::triplifyString($cid, parent::getVoc()."sequence-md5sum", $sequence_md5sum)
				);

				parent::addRDF(
					parent::triplify($cid, parent::getVoc()."taxon", "taxonomy:".$tax_id)
				);

				parent::addRDF(
					parent::triplifyString($cid, parent::getVoc()."source-database", $db_source)
				);

				parent::addRDF(
					parent::triplifyString($cid, parent::getVoc()."source-db-version", $db_version)
				);

				$component_synonym_sql = 'SELECT * FROM `component_synonyms` WHERE `component_id`="'.$component_id.'"';
				$component_synonym_result = $connection->query($component_synonym_sql);
				while($component_synonym_row = $component_synonym_result->fetch_assoc()){
					$compsyn_id = $component_synonym_row['compsyn_id'];
					$synonym = $component_synonym_row['component_synonym'];
					$syn_type = $component_synonym_row['syn_type'];

					$synonym_id = parent::getRes()."SYNONYM_".$compsyn_id;

					parent::addRDF(
						parent::describeIndividual($synonym_id, $synonym, parent::getVoc()."Synonym").
						parent::triplifyString($synonym_id, parent::getVoc()."synonym-source", $syn_type).
						parent::triplifyString($synonym_id, "rdf:value", $synonym).
						parent::triplify($cid, parent::getVoc()."synonym", $synonym_id)
					);
				}
				$component_synonym_result->free();

				$component_domains_sql = 'SELECT * FROM `component_domains` WHERE `component_id`="'.$component_id.'"';
				$component_domains_result = $connection->query($component_domains_sql);
				while($component_domains_row = $component_domains_result->fetch_assoc()){
					$compd_id = $component_domains_row['compd_id'];
					$domain_id = $component_domains_row['domain_id'];
					$start_pos = $component_domains_row['start_position'];
					$end_pos = $component_domains_row['end_position'];

					$cd_id = parent::getRes()."COMPONENT_DOMAIN_".$compd_id;
					$cd_label = "Domain of COMPONENT_".$component_id." starting at $start_pos and ending at $end_pos";
					parent::addRDF(
						parent::describeIndividual($cd_id, $cd_label, parent::getVoc()."ComponentDomain").
						parent::triplify($cd_id, parent::getVoc()."domain", parent::getRes()."DOMAIN_".$domain_id).
						parent::triplifyString($cd_id, parent::getVoc()."start-position", $start_pos).
						parent::triplifyString($cd_id, parent::getVoc()."end-position", $end_pos).
						parent::triplify($cid, parent::getVoc()."component-domain", $cd_id)
					);
				}
				$component_domains_result->free();

				$component_classes_sql = 'SELECT * FROM `component_class` WHERE `component_id`="'.$component_id.'"';
				$component_classes_result = $connection->query($component_classes_sql);
				while($component_classes_row = $component_classes_row->fetch_assoc()){
					$protein_class_id = $component_classes_row['protein_class_id'];
					parent::addRDF(
						parent::triplify($cid, parent::getVoc()."protein-family", parent::getRes()."PROTEIN_FAMILY_".$protein_class_id)
					);
				}
				parent::writeRDFBufferToWriteFile();
			}
			$result->free();
		}
	}

	/*
	*	process the compounds table
	*/
	function compounds($connection) {

		$sql = 'SELECT * FROM `molecule_dictionary`';

		if($result = $connection->query($sql)){
			while($row = $result->fetch_assoc()){

				$molregno = $row['molregno'];
				$pref_name = $row['pref_name'];
				$chembl_id = $row['chembl_id'];
				$max_phase = $row['max_phase'];
				$therapeutic_flag = $row['therapeutic_flag'];
				$dosed_ingredient = $row['dosed_ingredient'];
				$structure_type = $row['structure_type'];
				$chebi_id = $row['chebi_id'];
				$chebi_par_id = $row['chebi_par_id'];
				$molecule_type = $row['molecule_type'];
				$first_approval = $row['first_approval'];
				$oral = $row['oral'];
				$parenteral = $row['parenteral'];
				$topical = $row['topical'];
				$black_box_warning = $row['black_box_warning'];
				$natural_product = $row['natural_product'];
				$first_in_class = $row['first_in_class'];
				$chirality = $row['chirality'];
				$prodrug = $row['prodrug'];
				$inorganic_flag = $row['inorganic_flag'];
				$usan_year = $row['usan_year'];
				$availablity_type = $row['availability_type'];
				$usan_stem = $row['usan_stem'];

				$compound_records_sql = 'SELECT `doc_id`, `compound_key`, `compound_name`, `src_id`, `src_compound_id` FROM `compound_records` WHERE `molregno`="'.$molregno.'"';
				$compound_records_result = $connection->query($compound_records_sql);
				$compound_records_row = $compound_records_result->fetch_assoc();
				$compound_records_result->free();
				$doc_id = $compound_records_row['doc_id'];
				$compound_key = $compound_records_row['compound_key'];
				$compound_name = $compound_records_row['compound_name'];
				$src_id = $compound_records_row['src_id'];
				$src_compound_id = $compound_records_row['src_compound_id'];

				$chembl_doc_sql = 'SELECT `chembl_id` FROM `docs` WHERE `doc_id`="'.$doc_id.'"';
				$chembl_doc_result = $connection->query($chembl_doc_sql);
				$chembl_doc_row = $chembl_doc_result->fetch_array();
				$chembl_doc_id = $chembl_doc_row[0];
				$chembl_doc_result->free();

				$src_id_sql = 'SELECT `src_description` FROM `source` WHERE `src_id`="'.$src_id.'"';
				$src_id_result = $connection->query($src_id_sql);
				$src_id_row = $src_id_result->fetch_array();
				$src_id_result->free();
				$source_desc = $src_id_row[0];

				$compound_properties_sql = 'SELECT * FROM `compound_properties` WHERE `molregno`="'.$molregno.'"';
				$compound_properties_result = $connection->query($compound_properties_sql);
				$compound_properties_row = $compound_properties_result->fetch_assoc();
				$compound_properties_result->free();

				$mw_freebase = $compound_properties_row['mw_freebase'];
				$alogp = $compound_properties_row['alogp'];
				$hba = $compound_properties_row['hba'];
				$hbd = $compound_properties_row['hbd'];
				$psa = $compound_properties_row['psa'];
				$rtb = $compound_properties_row['rtb'];
				$ro3_pass = $compound_properties_row['ro3_pass'];
				$num_ro5_violations = $compound_properties_row['num_ro5_violations'];
				$med_chem_friendly = $compound_properties_row['med_chem_friendly'];
				$acd_most_apka = $compound_properties_row['acd_most_apka'];
				$acd_most_bpka = $compound_properties_row['acd_most_bpka'];
				$acd_logp = $compound_properties_row['acd_logp'];
				$acd_logd = $compound_properties_row['acd_logd'];
				$molecular_species = $compound_properties_row['molecular_species'];
				$full_mwt = $compound_properties_row['full_mwt'];
				$aromatic_rings = $compound_properties_row['aromatic_rings'];
				$heavy_atoms = $compound_properties_row['heavy_atoms'];
				$num_alerts = $compound_properties_row['num_alerts'];
				$qed_weighted = $compound_properties_row['qed_weighted'];
				$updated_on = $compound_properties_row['updated_on'];

				$compound_structures_sql = 'SELECT * FROM `compound_structures` WHERE `molregno`="'.$molregno.'"';
				$compound_structures_result = $connection->query($compound_structures_sql);
				$compound_structures_row = $compound_structures_result->fetch_assoc();
				$compound_structures_result->free();

				$molfile = $compound_structures_row['molfile'];
				$standard_inchi = $compound_structures_row['standard_inchi'];
				$standard_inchi_key = $compound_structures_row['standard_inchi_key'];
				$canonical_smiles = $compound_structures_row['canonical_smiles'];
				$mol_formula = $compound_structures_row['molformula'];

				$molecule_hierarchy_sql = 'SELECT * FROM `molecule_hierarchy` WHERE `molregno`="'.$molregno.'"';
				$molecule_hierarchy_result = $connection->query($molecule_hierarchy_sql);
				$molecule_hierarchy_row = $molecule_hierarchy_result->fetch_assoc();
				$molecule_hierarchy_result->free();

				$parent_molregno = $molecule_hierarchy_row['parent_molregno'];
				$active_molregno = $molecule_hierarchy_row['active_molregno'];

				$compound_id = parent::getNamespace().$chembl_id;

				parent::addRDF(
					parent::describeIndividual($compound_id, $compound_name, parent::getVoc()."Compound")
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."compound-key", $compound_key)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."source", $source_desc)
				);

				if(isset($src_compound_id) && !empty($src_compound_id)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."source-compound-identifier")
					);
				}

				parent::addRDF(
					parent::triplify($compound_id, parent::getVoc()."document", parent::getNamespace().$chembl_doc_id)
				);

				if(isset($pref_name) && !empty($pref_name)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."preferred-name", $pref_name)
					);
				}

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."maximum-development-phase", $max_phase)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."therapeutic-flag", $therapeutic_flag)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."dosed-ingredient", $dosed_ingredient)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."structure-type", $structure_type)
				);

				if(isset($chebi_id) && !empty($chebi_id)){
					parent::addRDF(
						parent::triplify($compound_id, parent::getVoc()."x-chebi", "chebi:".$chebi_id)
					);
				}

				if(isset($chebi_par_id) && !empty($chebi_par_id)){
					parent::addRDF(
						parent::triplify($compound_id, parent::getVoc()."chebi-parent-id", "chebi:".$chebi_par_id)
					);
				}

				if(isset($molecule_type) && !empty($molecule_type)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."molecule-type", $molecule_type)
					);
				}

				if(isset($first_approval) && !empty($first_approval)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."year-of-first-approval", $first_approval)
					);
				}

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."administered-orally", $oral)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."administered-parenterally", $parenteral)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."administered-topically", $topical)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."black-box-warning", $black_box_warning)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."natural-product", $natural_product)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."first-in-class", $first_in_class)
				);

				if($chirality == "0"){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."chirality", "racemic mixture")
					);
				} elseif($chirality == "1"){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."chirality", "single stereoisomer")
					);
				} elseif($chirality == "2") {
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."chirality", "achiral")
					);
				}

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."prodrug", $prodrug)
				);

				parent::addRDF(
					parent::triplifyString($compound_id, parent::getVoc()."inorganic", $inorganic_flag)
				);

				if(isset($usan_year) && !empty($usan_year)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."year-of-usan-application", $usan_year)
					);
				}

				if(isset($availablity_type) && !empty($availablity_type)){
					if($availablity_type == "0"){
						parent::addRDF(
							parent::triplifyString($compound_id, parent::getVoc()."availability-type", "discontinued")
						);
					} elseif($availablity_type == "1"){
						parent::addRDF(
							parent::triplifyString($compound_id, parent::getVoc()."availability-type", "prescription only")
						);
					} elseif($availablity_type == "2"){
						parent::addRDF(
							parent::triplifyString($compound_id, parent::getVoc()."availability-type", "over the counter")
						);
					}
				}

				if(isset($mw_freebase) && !empty($mw_freebase)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."molecular-weight-of-parent-compound", $mw_freebase)
					);
				}

				if(isset($alogp) && !empty($alogp)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."calculated-alogp", $alogp)
					);
				}

				if(isset($hba) && !empty($hba)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."number-of-hydrogen-bonds", $hba)
					);
				}

				if(isset($hbd) && !empty($hbd)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."number-of-hydrogen-bond-donors", $hbd)
					);
				}

				if(isset($psa) && !empty($psa)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."polar-surface-area", $psa)
					);
				}

				if(isset($rtb) && !empty($rtb)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."number-of-rotatable-bonds", $rtb)
					);
				}

				if(isset($ro3_pass) && !empty($ro3_pass)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."passes-rule-of-three", $ro3_pass)
					);
				}

				if(isset($num_ro5_violations) && !empty($num_ro5_violations)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."rule-of-five-violations", $num_ro5_violations)
					);
				}

				if(isset($med_chem_friendly) && !empty($med_chem_friendly)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."med-chem-friendly", $med_chem_friendly)
					);
				}

				if(isset($acd_most_apka) && !empty($acd_most_apka)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."most-acidic-pka", $acd_most_apka)
					);
				}

				if(isset($acd_most_bpka) && !empty($acd_most_bpka)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."most-basic-pka", $acd_most_bpka)
					);
				}

				if(isset($acd_logp) && !empty($acd_logp)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."octanol/water-partition-coefficient", $acd_logp)
					);
				}

				if(isset($acd_logd) && !empty($acd_logp)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."octanal/water-partition-cofficient-pH7.4", $acd_logd)
					);
				}

				if(isset($molecular_species) && !empty($molecular_species)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."molecular-species-type", $molecular_species)
					);
				}

				if(isset($full_mwt) && !empty($full_mwt)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."molecular-weight", $full_mwt)
					);
				}

				if(isset($aromatic_rings) && !empty($aromatic_rings)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."number-aromatic-rings", $aromatic_rings)
					);
				}

				if(isset($heavy_atoms) && !empty($heavy_atoms)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."number-heavy-atoms", $heavy_atoms)
					);
				}

				if(isset($num_alerts) && !empty($num_alerts)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."number-structural-alerts", $num_alerts)
					);
				}

				if(isset($qed_weighted) && !empty($qed_weighted)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."weighted-quantitative-estimate-drug-likeness", $qed_weighted)
					);
				}

				if(isset($updated_on) && !empty($updated_on)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."properties-update-date", $updated_on)
					);
				}

				if(isset($molfile) && !empty($molfile)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."mdl-molfile-format", parent::safeLiteral($molfile))
					);
				}

				if(isset($standard_inchi) && !empty($standard_inchi)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."standard-inchi", $standard_inchi)
					);
				}

				if(isset($standard_inchi_key) && !empty($standard_inchi_key)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."standard-inchi-key", $standard_inchi_key)
					);
				}

				if(isset($canonical_smiles) && !empty($canonical_smiles)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."canononical-smiles-string", $canonical_smiles)
					);
				}

				if(isset($mol_formula) && !empty($mol_formula)){
					parent::addRDF(
						parent::triplifyString($compound_id, parent::getVoc()."molecular-formula", $mol_formula)
					);
				}

				if(isset($parent_molregno) && !empty($parent_molregno)){
					$parent_molregno_sql = 'SELECT `chembl_id` FROM `molecule_dictionary` WHERE `molregno`="'.$parent_molregno.'"';
					$parent_molregno_result = $connection->query($parent_molregno_sql);
					$parent_molregno_row = $parent_molregno_result->fetch_array();
					$parent_molregno_result->free();
					$parent_chembl_id = $parent_molregno_row[0];

					parent::addRDF(
						parent::triplify($compound_id, parent::getRes()."parent-compound", parent::getNamespace().$parent_chembl_id)
					);
				}

				if(isset($active_molregno) && !empty($active_molregno) && $parent_molregno != $active_molregno){
					$active_molregno_sql = 'SELECT `chembl_id` FROM `molecule_dictionary` WHERE `molregno`="'.$active_molregno.'"';
					$active_molregno_result = $connection->query($active_molregno_sql);
					$active_molregno_row = $active_molregno_result->fetch_array();
					$active_molregno_result->free();
					$active_chembl_id = $active_molregno_row[0];

					parent::addRDF(
						parent::triplify($compound_id, parent::getRes()."active-metabolite-compound", parent::getNamespace().$active_chembl_id)
					);
				}
				parent::writeRDFBufferToWriteFile();
			}

			$result->free();
		}
	}

	function documents($connection){
		$sql = 'SELECT * FROM `docs`';

		if($result = $connection->query($sql)){
			while($row = $result->fetch_assoc()){
				$doc_chembl_id = $row['chembl_id'];
				$journal = $row['journal'];
				$year = $row['year'];
				$volume = $row['volume'];
				$issue = $row['issue'];
				$first_page = $row['first_page'];
				$last_page = $row['last_page'];
				$pubmed_id = $row['pubmed_id'];
				$doi = $row['doi'];
				$title = $row['title'];
				$doc_type = $row['doc_type'];
				$authors = $row['authors'];
				$abstract = $row['abstract'];

				$document_id = parent::getNamespace().$doc_chembl_id;

				parent::addRDF(
					parent::describeIndividual($document_id, $title, parent::getVoc()."Document")
				);

				if(isset($journal) && !empty($journal)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."journal-name", $journal)
					);
				}

				if(isset($year) && !empty($year)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."year", $year)
					);
				}

				if(isset($volume) && !empty($volume)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."volume", $volume)
					);
				}

				if(isset($issue) && !empty($issue)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."issue", $issue)
					);
				}

				if(isset($first_page) && !empty($first_page)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."first-page", $first_page)
					);
				}

				if(isset($last_page) && !empty($last_page)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."last-page", $last_page)
					);
				}

				if(isset($pubmed_id) && !empty($pubmed_id)){
					parent::addRDF(
						parent::triplify($document_id, parent::getNamespace()."x-pubmed", "pubmed:".$pubmed_id)
					);
				}

				if(isset($doi) && !empty($doi)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."doi", $doi)
					);
				}

				if(isset($title) && !empty($title)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."title", $title)
					);
				}

				parent::addRDF(
					parent::triplifyString($document_id, parent::getVoc()."document-type", $doc_type)
				);

				if(isset($authors) && !empty($authors)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."authors", $authors)
					);
				}

				if(isset($abstract) && !empty($abstract)){
					parent::addRDF(
						parent::triplifyString($document_id, parent::getVoc()."abstract", $abstract)
					);
				}
				parent::writeRDFBufferToWriteFile();
			}
			$result->free();
		}
	}

	function domains($connection){

		$sql = 'SELECT * FROM `domains`';

		if($result = $connection->query($sql)){
			while($row = $result->fetch_assoc()){
				$domain_id = $row['domain_id'];
				$domain_type = $row['domain_type'];
				$source_id = $row['source_domain_id'];
				$domain_name = $row['domain_name'];
				$domain_description = $row['domain_description'];

				$did = parent::getRes()."DOMAIN_".$domain_id;

				parent::addRDF(
					parent::describeIndividual($did, $domain_name, parent::getVoc()."Domain")
				);

				parent::addRDF(
					parent::triplifyString($did, parent::getVoc()."domain-type", $domain_type)
				);

				parent::addRDF(
					parent::triplifyString($did, parent::getVoc()."source-id", $source_id).
					parent::triplify($did, parent::getVoc()."x-pfam", "pfam:".$source_id)
				);

				parent::addRDF(
					parent::triplifyString($did, parent::getVoc()."domain-name", $domain_name)
				);

				if(isset($domain_description) && !empty($domain_description)){
					parent::addRDF(
						parent::triplifyString($did, parent::getVoc()."domain-description", $domain_description).
						parent::triplifyString($did, "dc:description", $domain_description)
					);
				}
				parent::writeRDFBufferToWriteFile();
			}
			$result->free();
		}
	}

	function targets($connection){

		$sql = 'SELECT * FROM `target_dictionary`';

		if($result = $connection->query($sql)){
			while($row = $result->fetch_assoc()){
				$tid = $row['tid'];
				$target_type = $row['target_type'];
				$pref_name = $row['pref_name'];
				$tax_id = $row['tax_id'];
				$organism = $row['organism'];
				$chembl_id = $row['chembl_id'];

				$target_id = parent::getNamespace().$chembl_id;

				parent::addRDF(
					parent::describeIndividual($target_id, $pref_name, parent::getVoc()."Target")
				);

				if(isset($target_type) && !empty($target_type)){
					parent::addRDF(
						parent::triplifyString($target_id, parent::getVoc()."target-type", $target_type)
					);
				}

				if(isset($tax_id)){
					parent::addRDF(
						parent::triplify($target_id, parent::getVoc()."taxon", "taxonomy:".$tax_id)
					);
				}

				$target_components_sql = 'SELECT * FROM `target_components` WHERE `tid`="'.$tid.'"';
				$target_components_result = $connection->query($target_components_sql);
				while($target_components_row = $target_components_result->fetch_assoc()){
					$component_id = $target_components_row['component_id'];
					parent::addRDF(
						parent::triplify($target_id, parent::getVoc()."target-component", parent::getRes()."COMPONENT_".$component_id)
					);
				}
				$target_components_result->free();

				parent::writeRDFBufferToWriteFile();
			}
			$result->free();
		}

	}

	function protein_families($connection){
		$sql = 'SELECT * FROM `protein_family_classification`';

		if($result = $connection->query($sql)){
			while($row = $result->fetch_assoc()){
				$protein_class_id = $row['protein_class_id'];
				$protein_class_desc = $row['protein_class_desc'];
				$l1 = $row['l1'];
				$l2 = $row['l2'];
				$l3 = $row['l3'];
				$l4 = $row['l4'];
				$l5 = $row['l5'];
				$l6 = $row['l6'];
				$l7 = $row['l7'];
				$l8 = $row['l8'];

				$family_id = parent::getRes()."PROTEIN_FAMILY_".$protein_class_id;

				parent::addRDF(
					parent::describeIndividual($family_id, $protein_class_desc, parent::getVoc()."ProteinFamily")
				);

				parent::addRDF(
					parent::triplifyString($family_id, parent::getVoc()."level-one-classification", $l1)
				);

				if(isset($l2) && !empty($l2)){
					parent::addRDF(
						parent::triplifyString($family_id, parent::getVoc()."level-two-classification", $l2)
					);
				}

				if(isset($l3) && !empty($l3)){
					parent::addRDF(
						parent::triplifyString($family_id, parent::getVoc()."level-three-classification", $l3)
					);
				}

				if(isset($l4) && !empty($l4)){
					parent::addRDF(
						parent::triplifyString($family_id, parent::getVoc()."level-four-classification", $l4)
					);
				}

				if(isset($l5) && !empty($l5)){
					parent::addRDF(
						parent::triplifyString($family_id, parent::getVoc()."level-five-classification", $l5)
					);
				}

				if(isset($l6) && !empty($l6)){
					parent::addRDF(
						parent::triplifyString($family_id, parent::getVoc()."level-six-classification", $l6)
					);
				}

				if(isset($l7) && !empty($l7)){
					parent::addRDF(
						parent::triplifyString($family_id, parent::getVoc()."level-seven-classification", $l7)
					);
				}

				if(isset($l8) && !empty($l8)){
					parent::addRDF(
						parent::triplifyString($family_id, parent::getVoc()."level-eight-classification", $l8)
					);
				}

				parent::writeRDFBufferToWriteFile();
			}
			$result->free();
		}
	}
}
?>