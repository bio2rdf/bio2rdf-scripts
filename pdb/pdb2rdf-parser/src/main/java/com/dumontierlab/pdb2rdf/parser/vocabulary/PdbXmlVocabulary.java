/**
 * Copyright (c) 2009 Dumontierlab
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
package com.dumontierlab.pdb2rdf.parser.vocabulary;

/**
 * @author Alexander De Leon
 */
public class PdbXmlVocabulary {

	public static final String DATABLOCK = "datablock";
	public static final String ENTITY_CATEGORY = "entityCategory";
	public static final String ENTITY = "entity";
	public static final String TYPE = "type";
	public static final String DETAILS = "details";
	public static final String DESCRIPTION = "pdbx_description";
	public static final String FORMULA_WEIGHT = "formula_weight";
	public static final String EXPERIMENTAL_FORMULA_WEIGHT = "pdbx_formula_weight_exptl";
	public static final String EXPERIMENTAL_FORMULA_WEIGHT_METHOD = "pdbx_formula_weight_exptl_method";
	public static final String MODIFICATION = "pdbx_modification";
	public static final String MUTATION = "pdbx_mutation";
	public static final String CHEMICAL_AMOUNT = "pdbx_number_of_molecules";
	public static final String SOURCE_METHOD = "src_method";
	public static final String CITATION_CATEGORY = "citationCategory";
	public static final String CITATION = "citation";
	public static final String ABSTRACT = "abstract";
	public static final String BOOK_ISBN = "book_id_ISBN";
	public static final String BOOK_PUBLISHER = "book_publisher";
	public static final String BOOK_TITLE = "book_title";
	public static final String COUNTRY = "country";
	public static final String CSD_ID = "database_id_CSD";
	public static final String MEDLINE_ID = "database_id_Medline";
	public static final String JOURNAL_ABBREVIATION = "journal_abbrev";
	public static final String JOURNAL_FULL = "journal_full";
	public static final String JOURNAL_CSD = "journal_id_CSD";
	public static final String JOURNAL_ISSN = "journal_id_ISSN";
	public static final String JOURNAL_VOLUME = "journal_volume";
	public static final String LANGUAGE = "language";
	public static final String FIRST_PAGE = "page_first";
	public static final String LAST_PAGE = "page_last";
	public static final String DOI_ID = "pdbx_database_id_DOI";
	public static final String PUBMED_ID = "pdbx_database_id_PubMed";
	public static final String TITLE = "title";
	public static final String YEAR = "year";
	public static final String CITATION_AUTHOR = "citation_author";
	public static final String CITATION_AUTHOR_CATEGORY = "citation_authorCategory";
	public static final String ENTITY_POLY_CATEGORY = "entity_polyCategory";
	public static final String ENTITY_POLY = "entity_poly";
	public static final String NUMBER_OF_MONOMERS = "number_of_monomers";
	public static final String ONE_LETTER_SEQUENCE = "pdbx_seq_one_letter_code";
	public static final String ONE_LETTER_SEQUENCE_CANONICAL = "pdbx_seq_one_letter_code_can";
	public static final String TARGET_IDENTIFIER = "pdbx_target_identifier";
	public static final String ENTITY_SOURCE_NATURAL_CATEGORY = "entity_src_natCategory";
	public static final String ENTITY_SOURCE_NATURAL = "entity_src_nat";
	public static final String CELL_TYPE = "pdbx_cell";
	public static final String CELL_LINE = "pdbx_cell_line";
	public static final String NCBI_TAXONOMY_ID = "pdbx_ncbi_taxonomy_id";
	public static final String ORGAN = "pdbx_organ";
	public static final String ORGANELLE = "pdbx_organelle";
	public static final String ORGANISM_SCIENTIFIC_NAME = "pdbx_organism_scientific";
	public static final String PDBX_PLASMID_DETAILS = "pdbx_plasmid_details";
	public static final String PDBX_PLASMID_NAME = "pdbx_plasmid_name";
	public static final String SECRETION = "pdbx_secretion";
	public static final String TISSUE = "tissue";
	public static final String TISSUE_FRACTION = "tissue_fraction";
	public static final String ATOM_SITE_CATEGORY = "atom_siteCategory";
	public static final String ATOM_SITE = "atom_site";
	public static final String AUTH_ASYM_ID = "auth_asym_id";
	public static final String AUTH_ATOM_ID = "auth_atom_id";
	public static final String AUTH_COMP_ID = "auth_comp_id";
	public static final String AUTH_SEQ_ID = "auth_seq_id";
	public static final String MODEL_NUMBER = "pdbx_PDB_model_num";
	public static final String B_EQUIVALENT_GEOMETRIC_MEAN = "B_equiv_geom_mean";
	public static final String B_EQUIVALENT_GEOMETRIC_MEAN_ESD = "B_equiv_geom_mean_esd";
	public static final String B_ISO_OR_EQUIVALENT = "B_iso_or_equiv";
	public static final String B_ISO_OR_EQUIVALENT_ESD = "B_iso_or_equiv_esd";
	public static final String CARTN_X = "Cartn_x";
	public static final String CARTN_X_ESD = "Cartn_x_esd";
	public static final String CARTN_Y = "Cartn_y";
	public static final String CARTN_Y_ESD = "Cartn_y_esd";
	public static final String CARTN_Z = "Cartn_z";
	public static final String CARTN_Z_ESD = "Cartn_z_esd";
	public static final String U_EQUIVALENT_GEOMETRIC_MEAN = "U_equiv_geom_mean";
	public static final String U_EQUIVALENT_GEOMETRIC_MEAN_ESD = "U_equiv_geom_mean_esd";
	public static final String U_ISO_OR_EQUIVALENT = "U_iso_or_equiv";
	public static final String U_ISO_OR_EQUIVALENT_ESD = "U_iso_or_equiv_esd";
	public static final String WYCKOFF_SYMBOL = "Wyckoff_symbol";
	public static final String ANISO_B11 = "aniso_B11";
	public static final String ANISO_B11_ESD = "aniso_B11_esd";
	public static final String ANISO_B12 = "aniso_B12";
	public static final String ANISO_B12_ESD = "aniso_B12_esd";
	public static final String ANISO_B13 = "aniso_B13";
	public static final String ANISO_B13_ESD = "aniso_B13_esd";
	public static final String ANISO_B22 = "aniso_B22";
	public static final String ANISO_B22_ESD = "aniso_B22_esd";
	public static final String ANISO_B23 = "aniso_B23";
	public static final String ANISO_B23_ESD = "aniso_B23_esd";
	public static final String ANISO_B33 = "aniso_B33";
	public static final String ANISO_B33_ESD = "aniso_B33_esd";
	public static final String ANISO_U11 = "aniso_U11";
	public static final String ANISO_U11_ESD = "aniso_U11_esd";
	public static final String ANISO_U12 = "aniso_U12";
	public static final String ANISO_U12_ESD = "aniso_U12_esd";
	public static final String ANISO_U13 = "aniso_U13";
	public static final String ANISO_U13_ESD = "aniso_U13_esd";
	public static final String ANISO_U22 = "aniso_U22";
	public static final String ANISO_U22_ESD = "aniso_U22";
	public static final String ANISO_U23 = "aniso_U23";
	public static final String ANISO_U23_ESD = "aniso_U23";
	public static final String ANISO_U33 = "aniso_U33";
	public static final String ANISO_U33_ESD = "aniso_U33";
	public static final String ANISO_RATIO = "aniso_ratio";
	public static final String ATTACHED_HYDROGENS = "attached_hydrogens";
	public static final String CALC_ATTACHED_ATOM = "calc_attached_atom";
	public static final String CHEMICAL_CONN_NUMBER = "chemical_conn_number";
	public static final String CONSTRAINTS = "constraints";
	public static final String DISORDER_ASSEMBLY = "disorder_assembly";
	public static final String DISORDER_GROUP = "disorder_group";
	public static final String FOOTNOTE_ID = "footnote_id";
	public static final String FRACTION_X = "fract_x";
	public static final String FRACTION_X_ESD = "fract_x_esd";
	public static final String FRACTION_Y = "fract_y";
	public static final String FRACTION_Y_ESD = "fract_y_esd";
	public static final String FRACTION_Z = "fract_z";
	public static final String FRACTION_Z_ESD = "fract_z_esd";
	public static final String GROUP_PDB = "group_PDB";
	public static final String OCCUPANCY = "occupancy";
	public static final String OCCUPANCY_ESD = "occupancy_esd";
	public static final String PDBX_FORMAL_CHARGE = "pdbx_formal_charge";
	public static final String PDBX_NCS_DOM_ID = "pdbx_ncs_dom_id";
	public static final String PDBX_TLS_GROUP_ID = "pdbx_tls_group_id";
	public static final String RESTRAINTS = "restraints";
	public static final String SYMMETRY_MULTIPLICITY = "symmetry_multiplicity";
	public static final String TYPE_SYMBOL = "type_symbol";
	public static final String CHEMICAL_COMPONENT_CATEGORY = "chem_compCategory";
	public static final String CHEMICAL_COMPONENT = "chem_comp";
	public static final String NAME = "name";
	public static final String NUMBER_OF_ATOMS_ALL = "number_atoms_all";
	public static final String NUMBER_OF_NON_HYDROGEN_ATOMS = "number_atoms_nh";
	public static final String FORMULA = "formula";
	public static final String LABEL_COMP_ID = "label_comp_id";
	public static final String LABEL_ENTITY_ID = "label_entity_id";
	public static final String STRUCT_CONF = "struct_conf";
	public static final String BEG_AUTH_ASYM_ID = "beg_auth_asym_id";
	public static final String BEG_AUTH_COMP_ID = "beg_auth_comp_id";
	public static final String BEG_AUTH_SEQ_ID = "beg_auth_seq_id";
	public static final String CONF_TYPE_ID = "conf_type_id";
	public static final String END_AUTH_ASYM_ID = "end_auth_asym_id";
	public static final String END_AUTH_COMP_ID = "end_auth_comp_id";
	public static final String END_AUTH_SEQ_ID = "end_auth_seq_id";
	public static final String PDBX_PDB_HELIX_LENGTH = "pdbx_PDB_helix_length";
	public static final String STRUCT_CONFIG_CATEGORY = "struct_confCategory";
	public static final String ANGLE_ALPHA = "angle_alpha";
	public static final String ANGLE_ALPHA_ESD = "angle_alpha_esd";
	public static final String ANGLE_BETA = "angle_beta";
	public static final String ANGLE_BETA_ESD = "angle_beta_esd";
	public static final String ANGLE_GAMMA = "angle_gamma";
	public static final String ANGLE_GAMMA_ESD = "angle_gamma_esd";
	public static final String LENGTH_A = "length_a";
	public static final String LENGTH_A_ESD = "length_a_esd";
	public static final String LENGTH_B = "length_b";
	public static final String LENGTH_B_ESD = "length_b_esd";
	public static final String LENGTH_C = "length_c";
	public static final String LENGTH_C_ESD = "length_c_esd";
	public static final String RECIPROCAL_ANGLE_ALPHA = "reciprocal_angle_alpha";
	public static final String RECIPROCAL_ANGLE_ALPHA_ESD = "reciprocal_angle_alpha_esd";
	public static final String RECIPROCAL_ANGLE_BETA = "reciprocal_angle_beta";
	public static final String RECIPROCAL_ANGLE_BETA_ESD = "reciprocal_angle_beta_esd";
	public static final String RECIPROCAL_ANGLE_GAMMA = "reciprocal_angle_gamma";
	public static final String RECIPROCAL_ANGLE_GAMMA_ESD = "reciprocal_angle_gamma_esd";
	public static final String RECIPROCAL_LENGTH_A = "reciprocal_length_a";
	public static final String RECIPROCAL_LENGTH_A_ESD = "reciprocal_length_a_esd";
	public static final String RECIPROCAL_LENGTH_B = "reciprocal_length_b";
	public static final String RECIPROCAL_LENGTH_B_ESD = "reciprocal_length_b_esd";
	public static final String RECIPROCAL_LENGTH_C = "reciprocal_length_c";
	public static final String RECIPROCAL_LENGTH_C_ESD = "reciprocal_length_c_esd";
	public static final String VOLUME = "volume";
	public static final String VOLUME_ESD = "volume_esd";
	public static final String CELL_CATEGORY = "cellCategory";
	public static final String EXPTL_CATEGORY = "exptlCategory";
	public static final String EXPTL = "exptl";
	public static final String ABSORT_COEFFICIENT_MU = "absorpt_coefficient_mu";
	public static final String ABSORT_CORRECTION_T_MAX = "absorpt_correction_T_max";
	public static final String ABSORT_CORRECTION_T_MIN = "absorpt_correction_T_min";
	public static final String ABSORT_CORRECTION_TYPE = "absorpt_correction_type";
	public static final String ABSORT_PROCESS_DETAILS = "absorpt_process_details";
	public static final String STRUCT_CATEGORY = "structCategory";
	public static final String STRUCT = "struct";

	public static final String ENTITY_SOURCE_GEN_CATEGORY = "entity_src_genCategory";
	public static final String ENTITY_SOURCE_GEN = "entity_src_gen";
	public static final String GENE_SRC_DETAILS = "gene_src_details";
	public static final String GENE_SRC_DEVELOMENT_STAGE = "gene_src_dev_stage";
	public static final String GENE_SRC_TISSUE = "gene_src_tissue";
	public static final String GENE_SRC_TISSUE_FRACTION = "gene_src_tissue_fraction";
	public static final String HOST_ORGANISM_DETAILS = "host_org_details";
	public static final String GENE_SOURCE_ATCC = "pdbx_gene_src_atcc";
	public static final String ATCC = "pdbx_atcc";
	public static final String GENE_SOURCE_CELL = "pdbx_gene_src_cell";
	public static final String GENE_SOURCE_CELL_LINE = "pdbx_gene_src_cell_line";
	public static final String GENE_SOURCE_CELLULAR_LOCATION = "pdbx_gene_src_cellular_location";
	public static final String GENE_SOURCE_GENE = "pdbx_gene_src_gene";
	public static final String GENE_SOURCE_ORGANISM_TAXONOMY = "pdbx_gene_src_ncbi_taxonomy_id";
	public static final String GENE_SOURCE_ORGAN = "pdbx_gene_src_organ";
	public static final String GENE_SOURCE_ORGANELLE = "pdbx_gene_src_organelle";
	public static final String GENE_SOURCE_PLASMID = "pdbx_gene_src_plasmid";
	public static final String GENE_SOURCE_PLASMID_NAME = "pdbx_gene_src_plasmid_name";
	public static final String GENE_SOURCE_ORGANISM_SCIENTIFIC_NAME = "pdbx_gene_src_scientific_name";
	public static final String HOST_CELL = "pdbx_host_org_cell";
	public static final String HOST_CELL_LINE = "pdbx_host_org_cell_line";
	public static final String HOST_CELLULAR_LOCATION = "pdbx_host_org_cellular_location";
	public static final String HOST_GENE = "pdbx_host_org_gene";
	public static final String HOST_ORGANISM_TAXONOMY = "pdbx_host_org_ncbi_taxonomy_id";
	public static final String HOST_ORGAN = "pdbx_host_org_organ";
	public static final String HOST_ORGANELLE = "pdbx_host_org_organelle";
	public static final String HOST_SCIENTIFIC_NAME = "pdbx_host_org_scientific_name";
	public static final String HOST_TISSUE = "pdbx_host_org_tissue";
	public static final String HOST_TISSUE_FRACTION = "pdbx_host_org_tissue_fraction";
	public static final String HOST_VECTOR_TYPE = "pdbx_host_org_vector_type";
	public static final String PLASMID_DETAILS = "plasmid_details";
	public static final String PLASMID_NAME = "plasmid_name";
	public static final String REFINE_CATEGORY = "refineCategory";
	public static final String REFINE = "refine";
	public static final String PDBX_NMR_ENSEMBLECATEGORY = "pdbx_nmr_ensembleCategory";
	public static final String PDBX_NMR_ENSEMBLE = "pdbx_nmr_ensemble";
	public static final String PDBX_NMR_REFINECATEGORY = "pdbx_nmr_refineCategory";
	public static final String PDBX_NMR_REFINE = "pdbx_nmr_refine";
	public static final String B_ISO_MEAN = "B_iso_mean";
	public static final String B_ISO_MAX = "B_iso_max";
	public static final String B_ISO_MIN = "B_iso_min";
	//Aniso_B11 already exists for each atom, the following are for the overall structure
	public static final String Aniso_B11 = "aniso_B11"; 
	public static final String Aniso_B12 = "aniso_B12";
	public static final String Aniso_B13 = "aniso_B13";
	public static final String Aniso_B22 = "aniso_B22";
	public static final String Aniso_B23 = "aniso_B23";
	public static final String Aniso_B33 = "aniso_B33";
	public static final String CORRELATION_COEFF_FO_TO_FC = "correlation_coeff_Fo_to_Fc";
	public static final String CORRELATION_COEFF_FO_TO_FC_FREE = "correlation_coeff_Fo_to_Fc_free";
	public static final String LS_R_FACTOR_R_FREE = "ls_R_factor_R_free";
	public static final String LS_R_FACTOR_R_FREE_ERROR = "ls_R_factor_R_free_error";
	public static final String LS_R_FACTOR_R_FREE_ERROR_DETAILS = "ls_R_factor_R_free_details";
	public static final String LS_R_FACTOR_R_WORK = "ls_R_factor_work";
	public static final String LS_R_FACTOR_ALL = "ls_R_factor_all";
	public static final String LS_R_FACTOR_OBS= "ls_R_factor_obs";
	public static final String LS_D_RES_HIGH= "ls_d_res_high";
	public static final String LS_D_RES_LOW= "ls_d_res_low";
	public static final String LS_NUMBER_PARAMETERS= "ls_number_parameters";
	public static final String LS_NUMBER_REFLNS_R_FREE= "ls_number_reflns_R_free";
	public static final String LS_NUMBER_REFLNS_ALL= "ls_number_reflns_all";
	public static final String LS_NUMBER_REFLNS_OBS= "ls_number_reflns_obs";
	public static final String LS_NUMBER_RESTRAINTS= "ls_number_restraints";
	public static final String LS_PERCENT_REFLNS_R_FREE= "ls_percent_reflns_R_free";
	public static final String LS_PERCENT_REFLNS_OBS= "ls_percent_reflns_obs";
	public static final String LS_REDUNDANCY_REFLNS_OBS= "ls_redundancy_reflns_obs";
	public static final String LS_WR_FACTOR_R_FREE= "ls_wR_factor_R_free";
	public static final String LS_WR_FACTOR_R_WORK= "ls_wR_factor_R_work";
	public static final String OCCUPANCY_MAX= "occupancy_max";
	public static final String OCCUPANCY_MIN= "occupancy_min";
	public static final String OVERALL_FOM_FREE_R_SET= "overall_FOM_free_R_set";
	public static final String OVERALL_FOM_WORK_R_SET= "overall_FOM_work_R_set";
	public static final String OVERALL_SU_B = "overall_SU_B";
	public static final String OVERALL_SU_ML = "overall_SU_ML";
	public static final String OVERALL_SU_R_CRUICKSHANK_DPI = "overall_SU_R_Cruickshank_DPI";
	public static final String OVERALL_SU_R_FREE = "overall_SU_R_free";
	public static final String PDBX_R_FREE_SELECTION_DETAILS= "pdbx_R_Free_selection_details";
	public static final String PDBX_DATA_CUTOFF_HIGH_ABSF = "pdbx_data_cutoff_high_absF";
	public static final String PDBX_DATA_CUTOFF_HIGH_RMS_ABSF = "pdbx_data_cutoff_high_rms_absF";
	public static final String PDBX_DATA_CUTOFF_LOW_ABSF = "pdbx_data_cutoff_low_absF";
	public static final String PDBX_ISOTROPIC_THERMAL_MODEL = "pdbx_isotropic_thermal_model";
	public static final String PDBX_LS_CROSS_VALID_METHOD = "pdbx_ls_cross_valid_method";
	public static final String PDBX_LS_SIGMA_F = "pdbx_ls_sigma_F";
	public static final String PDBX_LS_SIGMA_I = "pdbx_ls_sigma_I";
	public static final String PDBX_METHOD_TO_DETERMINE_STRUCT = "pdbx_method_to_determine_struct";
	public static final String PDBX_OVERALL_ESU_R = "pdbx_overall_ESU_R";
	public static final String PDBX_OVERALL_ESU_R_FREE= "pdbx_overall_ESU_R_Free";
	public static final String PDBX_OVERALL_PHASE_ERROR = "pdbx_overall_phase_error";
	public static final String PDBX_SOLVENT_ION_PROBE_RADII = "pdbx_solvent_ion_probe_radii";
	public static final String PDBX_SOLVENT_SHRINKAGE_RADII = "pdbx_solvent_shrinkage_radii";
	public static final String PDBX_SOLVENT_VDW_PROBE_RADII = "pdbx_solvent_vdw_probe_radii";
	public static final String PDBX_STARTING_MODEL = "pdbx_starting_model";
	public static final String PDBX_STEREOCHEM_TARGET_VAL_SPEC_CASE = "pdbx_stereochem_target_val_spec_case";
	public static final String PDBX_STEREOCHEMISTRY_TARGET_VALUES = "pdbx_stereochemistry_target_values";
	public static final String SOLVENT_MODEL_DETAILS = "solvent_model_details";
	public static final String SOLVENT_MODEL_PARAM_BSOL = "solvent_model_param_bsol";
	public static final String SOLVENT_MODEL_PARAM_KSOL = "solvent_model_param_ksol";
	public static final String METHOD = "method";
	public static final String CONFORMER_SELECTION_CRITERIA = "conformer_selection_criteria";
	public static final String AVERAGE_CONSTRAINT_VIOLATIONS_PER_RESIDUE = "average_constraint_violations_per_residue";
	public static final String AVERAGE_CONSTRAINTS_PER_RESIDUE = "average_constraints_per_residue";
	public static final String AVERAGE_DISTANCE_CONSTRAINT_VIOLATION = "average_distance_constraint_violation";
	
	public static final String DATABLOCK_NAME_ATT = "datablockName";
	public static final String NIL_ATT = "xsi:nil";
	public static final String ID_ATT = "id";
	public static final String CITATION_ID_ATT = "citation_id";
	public static final String NAME_ATT = "name";
	public static final String ORDINAL_ATT = "ordinal";
	public static final String ENTITY_ID_ATT = "entity_id";
	public static final String MODEL_NUMBER_ATT = "model_number";
	public static final String METHOD_ATT = "method";
	public static final String UNITS_ATT = "units";

	public static final String ENTITY_TYPE_POLYMER_VALUE = "polymer";
	public static final String ENTITY_TYPE_NON_POLYMER_VALUE = "non-polymer";
	public static final String ENTITY_TYPE_WATER_VALUE = "water";
	public static final String ENTITY_TYPE_MACROLIDE_VALUE = "macrolide";

	public static final String ENTITY_SOURCE_METHOD_NAT_VALUE = "nat";
	public static final String ENTITY_SOURCE_METHOD_MAN_VALUE = "man";
	public static final String ENTITY_SOURCE_METHOD_SYN_VALUE = "syn";

	public static final String POLYMER_TYPE_POLYPEPTIDE_D_VALUE = "polypeptide(D)";
	public static final String POLYMER_TYPE_POLYPEPTIDE_L_VALUE = "polypeptide(L)";
	public static final String POLYMER_TYPE_POLYDEOXYRIBONUCLEOTIDE_VALUE = "polydeoxyribonucleotide";
	public static final String POLYMER_TYPE_POLYRIBONUCLEOTIDE_VALUE = "polyribonucleotide";
	public static final String POLYMER_TYPE_POLYSACCHARIDE_D_VALUE = "polysaccharide(D)";
	public static final String POLYMER_TYPE_POLYSACCHARIDE_L_VALUE = "polysaccharide(L)";
	public static final String POLYMER_TYPE_HYBRID_L_VALUE = "polydeoxyribonucleotide/polyribonucleotide hybrid";
	public static final String POLYMER_TYPE_CYCLIC_PSEUDO_PEPTIDE_L_VALUE = "cyclic-pseudo-peptide";

	public static final String VECTOR_TYPE_PLASMID_VALUE = "plasmid";
	public static final String VECTOR_TYPE_VIRUS_VALUE = "virus";
	public static final String VECTOR_TYPE_COSMID_VALUE = "cosmid";
	public static final String VECTOR_TYPE_MACROPHAGE_VALUE = "macrophage";
	public static final String VECTOR_TYPE_BACULOVIRUS_VALUE = "baculovirus";
	public static final String VECTOR_TYPE_BACTERIAL_VALUE = "bacterial";
	public static final String VECTOR_TYPE_BACTERIUM_VALUE = "bacterium";
	public static final Object PDB_GROUP_ATOM_VALUE = "ATOM";

	// Nucleic acid
	public static final String NDB_STRUCT_NUCLIC_ACID_BASE_PAIR_CATEGORY = "ndb_struct_na_base_pairCategory";
	public static final String NDB_STRUCT_NUCLIC_ACID_BASE_PAIR = "ndb_struct_na_base_pair";
	public static final String J_AUTH_ASYM_ID = "j_auth_asym_id";
	public static final String J_AUTH_SEQ_ID = "j_auth_seq_id";
	public static final String I_AUTH_ASYM_ID = "i_auth_asym_id";
	public static final String I_AUTH_SEQ_ID = "i_auth_seq_id";
	public static final String OPENING = "opening";
	public static final String PROPELLER = "propeller";
	public static final String SHEAR = "shear";
	public static final String STAGGER = "stagger";
	public static final String STRETCH = "stretch";
	public static final String BUCKLE = "buckle";
	public static final String HBOND_TYPE_12 = "hbond_type_12";
	public static final String HBOND_TYPE_28 = "hbond_type_28";

}
