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
package com.dumontierlab.pdb2rdf.parser.vocabulary.uri;

/**
 * @author Alexander De Leon
 */
public enum Bio2RdfPdbUriPattern implements UriPattern, Bio2RdfPdbNamespace {
	// {0} -> pdbid
	PDB_RECORD(DEFAULT_NAMESPACE_RESOURCE + "{0}/record"),
	ANONYMOUS_NS(DEFAULT_NAMESPACE_RESOURCE + "{0}/anon/"),
	EXPERIMENT(DEFAULT_NAMESPACE + "{0}"),
	CHEMICAL_SUBSTANCE(DEFAULT_NAMESPACE_RESOURCE + "{0}/chemicalSubstance_{1}"),
	STRUCTURE_DETERMINATION(DEFAULT_NAMESPACE_RESOURCE + "{0}/structureDetermination"),
	CHEMICAL_SUBSTANCE_EXTRACTION(DEFAULT_NAMESPACE_RESOURCE + "{0}/chemicalSubstance_{1}/extraction"),
	THEORETICAL_FORMULA_WEIGHT(DEFAULT_NAMESPACE_RESOURCE + "{0}/chemicalSubstance_{1}/theoreticalFormulaWeight"),
	EXPERIMANTAL_FORMULA_WEIGHT(DEFAULT_NAMESPACE_RESOURCE + "{0}/chemicalSubstance_{1}/experimentalFormulaWeight"),
	CHEMICAL_AMOUNT(DEFAULT_NAMESPACE_RESOURCE + "{0}/chemicalSubstance_{1}/amount"),
	PUBLICATION(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}"),
	ABSTRACT(DEFAULT_NAMESPACE_RESOURCE + "{0}/abstract_{1}"),
	BOOK("urn:isbn:{0}"),
	BOOK_TITLE("urn:isbn:{0}/title"),
	PUBLISHER(DEFAULT_NAMESPACE_RESOURCE + "{0}"),
	PUBLICATION_TITLE(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}/title"),
	COUNTRY(DEFAULT_NAMESPACE_RESOURCE + "country_{0}"),
	COUNTRY_NAME(DEFAULT_NAMESPACE_RESOURCE + "country_{0}/name"),
	CSD("http://bio2rdf.org/csd:{0}"),
	MEDLINE("http://bio2rdf.org/medline:{0}"),
	MEDLINE_ID(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}/medlineId"),
	JOURNAL_ABBREVIATION(DEFAULT_NAMESPACE_RESOURCE + "journal_{0}/abbreviation"),
	JOURNAL_NAME(DEFAULT_NAMESPACE_RESOURCE + "journal_{0}/name"),
	JOURNAL(DEFAULT_NAMESPACE_RESOURCE + "journal_{0}"),
	ISSN("urn:issn:{0}"),
	JOURNAL_VOLUME(DEFAULT_NAMESPACE_RESOURCE + "journal_{0}/volume{1}"),
	VOLUME_NUMBER(DEFAULT_NAMESPACE_RESOURCE + "journal_{0}/volume{1}/volumeNumber"),
	ISBN_ID(DEFAULT_NAMESPACE_RESOURCE + "journal_{0}/issn"),
	LANGUAGE(DEFAULT_NAMESPACE_RESOURCE + "language_{0}"),
	LANGUAGE_NAME(DEFAULT_NAMESPACE_RESOURCE + "language_{0}/name"),
	FIRST_PAGE_NUMBER(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}/firstPageNumber"),
	LAST_PAGE_NUMBER(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}/lastPageNumber"),
	DOI("http://bio2rdf.org/doi:{0}"),
	DOI_NUMBER(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}/DOI"),
	PUBMED("http://bio2rdf.org/pubmed:{0}"),
	PUBMED_ID(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}/pubmedId"),
	PUBLICATION_YEAR(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}/year"),
	AUTHOR(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}/author_{2}"),
	AUTHOR_NAME(DEFAULT_NAMESPACE_RESOURCE + "{0}/publication_{1}/author_{2}/name"),
	FOAF_AUTHOR("http://bio2rdf.org/foaf:{0}"),
	NUMBER_OF_MONOMERS(DEFAULT_NAMESPACE_RESOURCE + "{0}/chemical_substance_{1}/numberOfMonomers"),
	POLYMER_SEQUENCE(DEFAULT_NAMESPACE_RESOURCE + "{0}/chemical_substance_{1}/sequence"),
	CANONICAL_POLYMER_SEQUENCE(DEFAULT_NAMESPACE_RESOURCE + "{0}/chemical_substance_{1}/canonicalSequence"),
	TARGET_DB("http://bio2rdf.org/targetdb:{0}"),
	CELL(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/cell"),
	CELL_TYPE(DEFAULT_NAMESPACE_RESOURCE + "{0}"),
	CELL_LINE(DEFAULT_NAMESPACE_RESOURCE + "{0}"),
	NCBI_TAXONOMY("http://bio2rdf.org/taxon:{0}"),
	CHEBI("http://bio2rdf.org/chebi:{0}"),
	ORGANISM(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/organism"),
	ORGAN(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/organ"),
	ORGAN_TYPE(DEFAULT_NAMESPACE_RESOURCE + "{0}"),
	ORGANELLE(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/organelle"),
	ORGANELLE_TYPE(DEFAULT_NAMESPACE_RESOURCE + "{0}"),
	ORGANISM_NAME(ORGANISM.pattern + "/name"),
	PLASMID(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/plasmid"),
	PLASMID_ID("http://bio2rdf.org/plasmid:{0}"),
	SECRETION(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/secretion"),
	SECRETION_TYPE(DEFAULT_NAMESPACE_RESOURCE + "{0}"),
	TISSUE(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/tissue"),
	TISSUE_TYPE(DEFAULT_NAMESPACE_RESOURCE + "Tissue_{0}"),
	TISSUE_FRACTION(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/tissueFraction"),
	TISSUE_FRACTION_TYPE(DEFAULT_NAMESPACE_RESOURCE + "TissueFraction_{0}"),
	ATOM(DEFAULT_NAMESPACE_RESOURCE + "{0}/atom_{1}{2}_{3}"), // {0}=>pdbId, {1}=>chain,
	// {2}=>chainPos,
	// {3}=>atomId
	MODEL(DEFAULT_NAMESPACE_RESOURCE + "{0}/model_{1}"),
	ATOM_SPATIAL_LOCATION(DEFAULT_NAMESPACE_RESOURCE + "{0}/atomSpatialLocation_{1}"),
	GENE_SOURCE(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/gene"),
	GENE_SOURCE_ORGANISM(GENE_SOURCE.pattern + "/organism"),
	B_ISO_OR_EQUIVALENT(ATOM_SPATIAL_LOCATION.pattern + "/isotropicAtomicDisplacementParameter"),
	CARTN_X(ATOM_SPATIAL_LOCATION.pattern + "/cartnX"),
	CARTN_Y(ATOM_SPATIAL_LOCATION.pattern + "/cartnY"),
	CARTN_Z(ATOM_SPATIAL_LOCATION.pattern + "/cartnZ"),
	OCCUPANCY(ATOM_SPATIAL_LOCATION.pattern + "/occupancy"),
	FORMAL_CHARGE(ATOM_SPATIAL_LOCATION.pattern + "/formalCharge"),
	VECTOR(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/sourceManipulation/vector"),
	GENE_SOURCE_TISSUE(GENE_SOURCE.pattern + "/source/tissue"),
	GENE_SOURCE_TISSUE_FRACTION(GENE_SOURCE.pattern + "/source/tissueFraction"),
	AMERICAN_TYPE_CULTURE_COLLECTION("http://bio2rdf.org/atcc_dna:{0}"),
	GENE_SOURCE_CELL(GENE_SOURCE.pattern + "/source/cell"),
	GENE_SOURCE_CELLULAR_LOCATION(GENE_SOURCE.pattern + "/source/cellularLocation"),
	CELLULAR_LOCATION_TYPE(DEFAULT_NAMESPACE_RESOURCE + "CellularLocation_{0}"),
	GENE_SOURCE_NAME(GENE_SOURCE.pattern + "/name"),
	GENE_SOURCE_ORGAN(GENE_SOURCE.pattern + "/source/organ"),
	GENE_SOURCE_ORGANELLE(GENE_SOURCE.pattern + "/source/organelle"),
	GENE_SOURCE_PLASMID(GENE_SOURCE.pattern + "/source/plasmid"),
	GENE_SOURCE_PLASMID_NAME(GENE_SOURCE_PLASMID.pattern + "/name"),
	GENE_SOURCE_ORGANISM_NAME(GENE_SOURCE_ORGANISM.pattern + "/name"),
	CELLULAR_LOCATION(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/cellularLocation"),
	HOST_GENE(CHEMICAL_SUBSTANCE_EXTRACTION.pattern + "/source/gene"),
	RESIDUE(DEFAULT_NAMESPACE_RESOURCE + "{0}/chemicalComponent_{1}{2}"),
	CHAIN(DEFAULT_NAMESPACE_RESOURCE + "{0}/chain_{1}"),
	CHAIN_POSITION(CHAIN.pattern + "/position_{2}"),
	PUBLISHER_NAME(PUBLISHER.pattern + "/name"),
	GENE_SOURCE_CELL_DEV_STAGE(GENE_SOURCE_CELL.pattern + "/developmentStage"),
	GENE_SOURCE_ORGANISM_DEV_STAGE(GENE_SOURCE_ORGANISM.pattern + "/developmentStage"),
	RESIDUE_FORMULA_WEIGH("{0}/formulaWeight", false), // {0} => Residue URI
	RESIDUE_TYPE(DEFAULT_NAMESPACE_RESOURCE + "{0}"), // {0} => residue type name
	RESIDUE_NUMBER_OF_ATOMS("{0}/numberOfAtoms", false), // {0} => Residue URI
	RESIDUE_NUMBER_OF_NON_HYDROGEN_ATOMS("{0}/numberOfNonHydrogenAtoms", false), // {0}
	// => Residue URI
	RESIDUE_FORMULA("{0}/formula", false), // {0} => Residue URI

	PDB_GRAPH("http://bio2rdf.org/graph/pdb:{0}"),
	SECONDARY_STRUCTURE(DEFAULT_NAMESPACE_RESOURCE + "{0}/secondaryStructure_{1}"), // {0}
	// =>
	// pdbId,
	// {1}
	// =>
	// structureId
	HELIX_LENGTH(SECONDARY_STRUCTURE.pattern + "/helixLength"),
	CRYSTAL(DEFAULT_NAMESPACE_RESOURCE + "{0}/crystal"),
	UNIT_CELL(CRYSTAL.pattern + "/unitCell"),
	ANGLE_ALPHA(UNIT_CELL.pattern + "/angleAlpha"),
	ANGLE_BETA(UNIT_CELL.pattern + "/angleBeta"),
	ANGLE_GAMMA(UNIT_CELL.pattern + "/angleGamma"),
	EDGE_A_LENGTH(UNIT_CELL.pattern + "/edgeALength"),
	EDGE_B_LENGTH(UNIT_CELL.pattern + "/edgeBLength"),
	EDGE_C_LENGTH(UNIT_CELL.pattern + "/edgeCLength"),
	RECIPROCAL_ANGLE_ALPHA(UNIT_CELL.pattern + "/reciprocalAngleAlpha"),
	RECIPROCAL_ANGLE_GAMMA(UNIT_CELL.pattern + "/reciprocalAngleGamma"),
	RECIPROCAL_ANGLE_BETA(UNIT_CELL.pattern + "/reciprocalAngleBeta"),
	RECIPROCAL_EDGE_A_LENGTH(UNIT_CELL.pattern + "/reciprocalEdgeALength"),
	RECIPROCAL_EDGE_B_LENGTH(UNIT_CELL.pattern + "/reciprocalEdgeBLength"),
	RECIPROCAL_EDGE_C_LENGTH(UNIT_CELL.pattern + "/reciprocalEdgeCLength"),
	UNIT_CELL_VOLUME(UNIT_CELL.pattern + "/volume"),
	ABSORPTION_CORRECTION(DEFAULT_NAMESPACE_RESOURCE + "{0}/absorptionCorrection"),
	ABSORT_COEFFICIENT_MU(ABSORPTION_CORRECTION.pattern + "/coefficientMu"),
	ABSORT_CORRECTION_T_MAX(ABSORPTION_CORRECTION.pattern + "/maximumTransmissionFactor"),
	ABSORT_CORRECTION_T_MIN(ABSORPTION_CORRECTION.pattern + "/minimumTransmissionFactor"),
	REFINEMENT(DEFAULT_NAMESPACE_RESOURCE + "{0}/refinement"),
	B_ISO_MEAN(REFINEMENT.pattern + "/bIsoMean"),
	B_ISO_MAX(REFINEMENT.pattern + "/bIsoMax"),
	B_ISO_MIN(REFINEMENT.pattern + "/bIsoMin"),
	ANISO_B11(REFINEMENT.pattern + "/anisoB11"),
	ANISO_B12(REFINEMENT.pattern + "/anisoB12"),
	ANISO_B13(REFINEMENT.pattern + "/anisoB13"),
	ANISO_B22(REFINEMENT.pattern + "/anisoB22"),
	ANISO_B23(REFINEMENT.pattern + "/anisoB23"),
	ANISO_B33(REFINEMENT.pattern + "/anisoB33"),
	CORRELATION_COEFFICIENT_FO_TO_FC(REFINEMENT.pattern + "/correlationCoefFoToFc"),
	CORRELATION_COEFFICIENT_FO_TO_FC_FREE(REFINEMENT.pattern + "/correlationCoefFoToFcFree"),
	REFINEMENT_DETAILS(REFINEMENT.pattern + "/details"),
	REFINEMENT_METHOD(REFINEMENT.pattern + "/method"),
	LS_R_FACTOR_R_FREE(REFINEMENT.pattern + "/lsRFactorRFree"),
	LS_R_FACTOR_R_FREE_ERROR(REFINEMENT.pattern + "/lsRFactorRFreeError"),
	LS_R_FACTOR_R_FREE_ERROR_DETAILS(REFINEMENT.pattern + "/lsRFactorRFreeErrorDetails"),
	LS_R_FACTOR_R_WORK(REFINEMENT.pattern + "/lsRFactorRWork"),
	LS_R_FACTOR_ALL(REFINEMENT.pattern + "/lsRFactorAll"),
	LS_R_FACTOR_OBS(REFINEMENT.pattern + "/lsRFactorObs"),
	LS_D_RES_HIGH(REFINEMENT.pattern + "/lsDResHigh"),
	LS_D_RES_LOW(REFINEMENT.pattern + "/lsDResLow"),
	LS_NUMBER_PARAMETERS(REFINEMENT.pattern + "/lsNumberParameters"),
	LS_NUMBER_REFLNS_R_FREE(REFINEMENT.pattern + "/lsNumberReflnsRFree"),
	LS_NUMBER_REFLNS_ALL(REFINEMENT.pattern + "/lsNumberReflnsAll"),
	LS_NUMBER_REFLNS_OBS(REFINEMENT.pattern + "/lsNumberReflnsObs"),
	LS_NUMBER_RESTRAINTS(REFINEMENT.pattern + "/lsNumberRestraints"),
	LS_PERCENT_REFLNS_R_FREE(REFINEMENT.pattern + "/lsPercentReflnsRFree"),
	LS_PERCENT_REFLNS_OBS(REFINEMENT.pattern + "/lsPercentReflnsObs"),
	LS_REDUNDANCY_REFLNS_OBS(REFINEMENT.pattern + "/lsRedundancyReflnsObs"),
	LS_WR_FACTOR_R_FREE(REFINEMENT.pattern + "/lsWrFactorRFree"),
	LS_WR_FACTOR_R_WORK(REFINEMENT.pattern + "/lsWrFactorWork"),
	OCCUPANCY_MAX(REFINEMENT.pattern + "/occupancyMax"),
	OCCUPANCY_MIN(REFINEMENT.pattern + "/occupancyMin"),
	OVERALL_FOM_FREE_R_SET(REFINEMENT.pattern + "/overallFomFreeRSet"),
	OVERALL_FOM_WORK_R_SET(REFINEMENT.pattern + "/overallFomWorkRSet"),
	OVERALL_SU_B(REFINEMENT.pattern + "/overallSUB"),
	OVERALL_SU_ML(REFINEMENT.pattern + "/overallSUML"),
	OVERALL_SU_R_CRUICKSHANK_DPI(REFINEMENT.pattern + "/overallSURCruishankDPI"),
	OVERALL_SU_R_FREE(REFINEMENT.pattern + "/overallSURFree"),
	R_FREE_SELECTION_DETAILS(REFINEMENT.pattern + "/rFreeSelectionDetails"),
	DATA_CUTOFF_HIGH_ABSF(REFINEMENT.pattern + "/dataCutoffHighAbsF"),
	DATA_CUTOFF_HIGH_RMS_ABSF(REFINEMENT.pattern + "/dataCutoffHighRmsAbsF"),
	DATA_CUTOFF_LOW_ABSF(REFINEMENT.pattern + "/dataCutoffLowAbsF"),
	ISOTROPIC_THERMAL_MODEL(REFINEMENT.pattern + "/isotropicThermalModel"),
	LS_CROSS_VALID_METHOD(REFINEMENT.pattern + "/lsCrossValidMethod"),
	LS_SIGMA_F(REFINEMENT.pattern + "/lsSigmaF"),
	LS_SIGMA_I(REFINEMENT.pattern + "/lsSigmaI"),
	METHOD_TO_DETERMINE_STRUCT(REFINEMENT.pattern + "/methodToDetermineStruct"),
	OVERALL_ESU_R(REFINEMENT.pattern + "/overallEsuR"),
	OVERALL_ESU_R_Free(REFINEMENT.pattern + "/overallEsuRFree"),
	OVERALL_PHASE_ERROR(REFINEMENT.pattern + "/phaseError"),
	SOLVENT_ION_PROBE_RADII(REFINEMENT.pattern + "/solventIonProbeRadii"),
	SOLVENT_SHRINKAGE_RADII(REFINEMENT.pattern + "/solventShrinkageRadii"),
	SOLVENT_VDW_PROBE_RADII(REFINEMENT.pattern + "/solventVdwProbeRadii"),
	STARTING_MODEL(REFINEMENT.pattern + "/startingModel"),
	STEREOCHEM_TARGET_VAL_SPEC_CASE(REFINEMENT.pattern + "/stereochemTargetValSpecCase"),
	STEREOCHEMISTRY_TARGET_VALUES(REFINEMENT.pattern + "/stereochemistryTargetValues"),
	SOLVENT_MODEL_DETAILS(REFINEMENT.pattern + "/solventModelDetails"),
	SOLVENT_MODEL_PARAM_BSOL(REFINEMENT.pattern + "/solventModelParamBsol"),
	SOLVENT_MODEL_PARAM_KSOL(REFINEMENT.pattern + "/solventModelParamKsol"),
	ENSEMBLE(DEFAULT_NAMESPACE_RESOURCE + "{0}/ensemble"),
	AVERAGE_CONSTRAINT_VIOLATIONS_PER_RESIDUE(ENSEMBLE.pattern + "/averageConstraintViolationsPerResidue"),
	AVERAGE_CONSTRAINTS_PER_RESIDUE(ENSEMBLE.pattern + "/averageConstraintsPerResidue"),
	AVERAGE_DISTANCE_CONSTRAINT_VIOLATION(ENSEMBLE.pattern + "/averageDistanceConstraintViolation"),
	AVERAGE_TORSION_ANGLE_CONSTRAINT_VIOLATION(ENSEMBLE.pattern + "/averageTorsionAngleConstraintViolation"),
	CONFORMER_SELECTION_CRITERIA(ENSEMBLE.pattern + "/conformerSelectionCriteria"),
	CONFORMERS_CALCULATED_TOTAL_NUMBER(ENSEMBLE.pattern + "/conformersCalculatedTotalNumber"),
	CONFORMERS_SUBMITTED_TOTAL_NUMBER(ENSEMBLE.pattern + "/conformersSubmittedTotalNumber"),
	DISTANCE_CONSTRAINT_VIOLATION_METHOD(ENSEMBLE.pattern + "/distanceConstraintViolationMethod"),
	MAXIMUM_DISTANCE_CONSTRAINT_VIOLATION(ENSEMBLE.pattern + "/maximumDistanceConstraintViolation"),
	MAXIMUM_LOWER_DISTANCE_CONSTRAINT_VIOLATION(ENSEMBLE.pattern + "/maximumLowerDistanceConstraintViolation"),
	MAXIUM_TORSION_ANGLE_CONSTRAINT_VIOLATION(ENSEMBLE.pattern + "/maximumTorsionAngleConstraintViolation"),
	MAXIMUM_UPPER_DISTANCE_CONSTRAINT_VIOLATION(ENSEMBLE.pattern + "/maximumUpperDistanceConstraintViolation"),
	REPRESENTATIVE_CONFORMER(ENSEMBLE.pattern + "/representativeConformer"),
	TORSION_ANGLE_CONSTRAINT_VIOLATION_METHOD(ENSEMBLE.pattern + "/torsionAngleConstraintViolationMethod"),
	COORDINATE(ATOM_SPATIAL_LOCATION.pattern + "/coordinate"),

	UNIPROT_CROSS_REFERENCE(DEFAULT_NAMESPACE_RESOURCE + "{0}/crossreference/uniprot/{1}"),
	UNIPROT("http://bio2rdf.org/uniprot:{0}"),
	GO_CROSS_REFERENCE(DEFAULT_NAMESPACE_RESOURCE + "{0}/crossreference/go/{1}"),
	GO("http://bio2rdf.org/go:{0}");

	private final String pattern;
	private boolean encode;

	private Bio2RdfPdbUriPattern(String pattern) {
		this(pattern, true);
	}
	private Bio2RdfPdbUriPattern(String pattern, boolean encode){
		this.pattern = pattern;
		this.encode = encode;
			
	}
	@Override
	public boolean requiresEncoding(){
		return this.encode;
	}
	@Override
	public String getPattern() {
		return pattern;
	}
}

interface Bio2RdfPdbNamespace {
	public static final String DEFAULT_NAMESPACE_RESOURCE = "http://bio2rdf.org/pdb_resource:";
	public static final String DEFAULT_NAMESPACE = "http://bio2rdf.org/pdb:";
}
