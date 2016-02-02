package com.dumontierlab.pdb2rdf.parser;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.DCTerms;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;

public class RefineCategoryHandler extends ContentHandlerState {
	private final String pdbId;
	private Resource refinement;

	public RefineCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.REFINE)) {
			// read the contents of the element
			createRefinement();
		} else if (localName.equals(PdbXmlVocabulary.B_ISO_MAX) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.B_ISO_MIN) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.B_ISO_MEAN) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B11) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B12) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B13) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B22) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B23) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B33) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.CORRELATION_COEFF_FO_TO_FC) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.CORRELATION_COEFF_FO_TO_FC_FREE) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.DETAILS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.DETAILS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_R_FREE) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_R_FREE_ERROR) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_R_FREE_ERROR_DETAILS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_R_WORK) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_ALL) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_OBS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_D_RES_HIGH) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_D_RES_LOW) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_PARAMETERS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_REFLNS_R_FREE) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_REFLNS_ALL) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_REFLNS_OBS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_RESTRAINTS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_PERCENT_REFLNS_R_FREE) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_PERCENT_REFLNS_OBS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_REDUNDANCY_REFLNS_OBS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_WR_FACTOR_R_FREE) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_WR_FACTOR_R_WORK) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OCCUPANCY_MAX) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OCCUPANCY_MIN) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_FOM_FREE_R_SET) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_FOM_WORK_R_SET) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_SU_B) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_SU_ML) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_SU_R_CRUICKSHANK_DPI) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_SU_R_FREE) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_R_FREE_SELECTION_DETAILS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_DATA_CUTOFF_HIGH_ABSF) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_DATA_CUTOFF_HIGH_RMS_ABSF) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_DATA_CUTOFF_LOW_ABSF) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_ISOTROPIC_THERMAL_MODEL) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_LS_CROSS_VALID_METHOD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_LS_SIGMA_F) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_LS_SIGMA_I) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_METHOD_TO_DETERMINE_STRUCT) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_OVERALL_ESU_R) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_OVERALL_ESU_R_FREE) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_OVERALL_PHASE_ERROR) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_SOLVENT_ION_PROBE_RADII) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_SOLVENT_SHRINKAGE_RADII) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_SOLVENT_VDW_PROBE_RADII) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_STARTING_MODEL) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_STEREOCHEM_TARGET_VAL_SPEC_CASE) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_STEREOCHEMISTRY_TARGET_VALUES) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.SOLVENT_MODEL_DETAILS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.SOLVENT_MODEL_PARAM_BSOL) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.SOLVENT_MODEL_PARAM_KSOL) && !isNil(attributes)) {
			startBuffering();
		}

		super.startElement(uri, localName, name, attributes);
	}// startElement

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.B_ISO_MEAN) && isBuffering()) {
			createBIsoMean(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.B_ISO_MAX) && isBuffering()) {
			createBIsoMax(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.B_ISO_MIN) && isBuffering()) {
			createBIsoMin(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B11) && isBuffering()) {
			createAnisoB11(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B12) && isBuffering()) {
			createAnisoB12(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B13) && isBuffering()) {
			createAnisoB13(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B22) && isBuffering()) {
			createAnisoB22(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B23) && isBuffering()) {
			createAnisoB23(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.Aniso_B33) && isBuffering()) {
			createAnisoB33(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.CORRELATION_COEFF_FO_TO_FC) && isBuffering()) {
			createCorrelationCoeffFoToFc(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.CORRELATION_COEFF_FO_TO_FC_FREE) && isBuffering()) {
			createCorrelationCoeffFoToFcFree(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.DETAILS) && isBuffering()) {
			createDetails(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_R_FREE) && isBuffering()) {
			createLsRFactorRFree(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_R_FREE_ERROR) && isBuffering()) {
			createLsRFactorRFreeError(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_R_FREE_ERROR_DETAILS) && isBuffering()) {
			createLsRFactorRFreeErrorDetails(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_R_WORK) && isBuffering()) {
			createLsRFactorRWork(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_ALL) && isBuffering()) {
			createLsRFactorAll(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_R_FACTOR_OBS) && isBuffering()) {
			createLsRFactorObs(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_D_RES_HIGH) && isBuffering()) {
			createLsDResHigh(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_D_RES_LOW) && isBuffering()) {
			createLsDResLow(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_PARAMETERS) && isBuffering()) {
			createLsNumberParameters(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_REFLNS_R_FREE) && isBuffering()) {
			createLsNumberReflnsRFree(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_REFLNS_ALL) && isBuffering()) {
			createLsNumberReflnsAll(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_REFLNS_OBS) && isBuffering()) {
			createLsNumberReflnsObs(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_NUMBER_RESTRAINTS) && isBuffering()) {
			createLsNumberRestraints(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_PERCENT_REFLNS_R_FREE) && isBuffering()) {
			createLsPercentReflnsRFree(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_PERCENT_REFLNS_OBS) && isBuffering()) {
			createLsPercentReflnsObs(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_REDUNDANCY_REFLNS_OBS) && isBuffering()) {
			createLsRedundancy(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_WR_FACTOR_R_FREE) && isBuffering()) {
			createLsWrFactorRFree(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LS_WR_FACTOR_R_WORK) && isBuffering()) {
			createLsWrFactorRWork(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OCCUPANCY_MAX) && isBuffering()) {
			createOccupancyMax(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OCCUPANCY_MIN) && isBuffering()) {
			createOccupancyMin(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_FOM_FREE_R_SET) && isBuffering()) {
			createOverallFomFreeRSet(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_FOM_WORK_R_SET) && isBuffering()) {
			createOverallFomWorkRSet(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_SU_B) && isBuffering()) {
			createOverallSUB(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_SU_ML) && isBuffering()) {
			createOverallSUML(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_SU_R_CRUICKSHANK_DPI) && isBuffering()) {
			createOverallSURCruickshankDPI(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.OVERALL_SU_R_FREE) && isBuffering()) {
			createOverallSURFree(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_R_FREE_SELECTION_DETAILS) && isBuffering()) {
			createOverallPdbxRFreeSelectionDetails(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_DATA_CUTOFF_HIGH_ABSF) && isBuffering()) {
			createPdbxDataCutoffHighAbsF(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_DATA_CUTOFF_HIGH_RMS_ABSF) && isBuffering()) {
			createPdbxDataCutoffHighRmsAbsF(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_DATA_CUTOFF_LOW_ABSF) && isBuffering()) {
			createPdbxDataCutoffLowAbsF(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_ISOTROPIC_THERMAL_MODEL) && isBuffering()) {
			createPdbxIsotropicThermalModel(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_LS_CROSS_VALID_METHOD) && isBuffering()) {
			createPdbxLsCrossValidMethod(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_LS_SIGMA_F) && isBuffering()) {
			createPdbxLsSigmaF(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_LS_SIGMA_I) && isBuffering()) {
			createPdbxLsSigmaI(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_METHOD_TO_DETERMINE_STRUCT) && isBuffering()) {
			createPdbxMethodToDetermineStruct(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_OVERALL_ESU_R) && isBuffering()) {
			createPdbxOverallESUR(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_OVERALL_ESU_R_FREE) && isBuffering()) {
			createPdbxOverallESURFree(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_OVERALL_PHASE_ERROR) && isBuffering()) {
			createPdbxOverallPhaseError(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_SOLVENT_ION_PROBE_RADII) && isBuffering()) {
			createPdbxSolventIonProbeRadii(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_SOLVENT_SHRINKAGE_RADII) && isBuffering()) {
			createPdbxSolventShrinkageRadii(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_SOLVENT_VDW_PROBE_RADII) && isBuffering()) {
			createPdbxSolventVdwProbeRadii(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_STARTING_MODEL) && isBuffering()) {
			createPdbxStartingModel(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_STEREOCHEM_TARGET_VAL_SPEC_CASE) && isBuffering()) {
			createPdbxStereochemTargetValSpecCase(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.PDBX_STEREOCHEMISTRY_TARGET_VALUES) && isBuffering()) {
			createPdbxStereochemistryTargetValues(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.SOLVENT_MODEL_DETAILS) && isBuffering()) {
			createSolventModelDetails(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.SOLVENT_MODEL_PARAM_BSOL) && isBuffering()) {
			createSolventModelParamBsol(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.SOLVENT_MODEL_PARAM_KSOL) && isBuffering()) {
			createSolventModelParamKsol(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}// endElement

	private void createSolventModelParamKsol(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.SOLVENT_MODEL_PARAM_KSOL, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.SolventModelParamKsol.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The value of the KSOL solvent-model parameter describing the ratio of the electron density in the bulk solvent to the electron density in the molecular solute.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasSolventModelParamKsol.property(), x);
	}

	private void createSolventModelParamBsol(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.SOLVENT_MODEL_PARAM_BSOL, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.SolventModelParamBsol.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The value of the BSOL solvent-model parameter describing the average isotropic displacement parameter of disordered solvent atoms.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasSolventModelParamBsol.property(), x);
	}

	private void createSolventModelDetails(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.SOLVENT_MODEL_DETAILS, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.SolventModelDetails.resource());
		getRdfModel().add(x, RDFS.label, "Special aspects of the solvent model used during refinement.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasSolventModelDetails.property(), x);
	}

	private void createPdbxStereochemistryTargetValues(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.STEREOCHEMISTRY_TARGET_VALUES, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.StereochemistryTargetValues.resource());
		getRdfModel().add(x, RDFS.label, "Stereochemistry target values used in refinement.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasStereochemistryTargetValues.property(), x);
	}

	private void createPdbxStereochemTargetValSpecCase(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.STEREOCHEM_TARGET_VAL_SPEC_CASE, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.StereochemTargetValSpecCase.resource());
		getRdfModel().add(x, RDFS.label, "Special case of stereochemistry target values used in SHELXL refinement.  ",
				"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasStereochemTargetValSpecCase.property(), x);
	}

	private void createPdbxStartingModel(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.STARTING_MODEL, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.StartingModel.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"Starting model for refinement.  Starting model for molecular replacement should refer to a previous structure or experiment.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasStartingModel.property(), x);
	}

	private void createPdbxSolventVdwProbeRadii(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.SOLVENT_VDW_PROBE_RADII, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.SolventVdwProbeRadii.resource());
		getRdfModel().add(x, RDFS.label, "CCP4 solvent proble van der Waals radii.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasSolventVdwProbeRadii.property(), x);
	}

	private void createPdbxSolventShrinkageRadii(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.SOLVENT_SHRINKAGE_RADII, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.SolventShrinkageRadii.resource());
		getRdfModel().add(x, RDFS.label, "CCP4 solvent shrinkage radii.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasSolventShrinkageRadii.property(), x);
	}

	private void createPdbxSolventIonProbeRadii(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.SOLVENT_ION_PROBE_RADII, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.SolventIonProbeRadii.resource());
		getRdfModel().add(x, RDFS.label, "CCP4 solvent ion proble radii.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasSolventIonProbeRadii.property(), x);
	}

	private void createPdbxOverallPhaseError(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OVERALL_PHASE_ERROR, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OverallPhaseError.resource());
		getRdfModel().add(x, RDFS.label,
				"The overall phase error for all reflections after refinement using the current refinement target.",
				"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOverallPhaseError.property(), x);
	}

	private void createPdbxOverallESURFree(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OVERALL_ESU_R_Free, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OverallESURFree.resource());
		getRdfModel().add(x, RDFS.label,
				"Overall estimated standard uncertainties of positional parameters based on R value..", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOverallESURFree.property(), x);
	}

	private void createPdbxOverallESUR(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OVERALL_ESU_R, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OverallESUR.resource());
		getRdfModel().add(x, RDFS.label,
				"Overall estimated standard uncertainties of positional parameters based on R value.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOverallESUR.property(), x);
	}

	private void createPdbxMethodToDetermineStruct(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.METHOD_TO_DETERMINE_STRUCT, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.MethodToDetermineStruct.resource());
		getRdfModel().add(x, RDFS.label, "Method(s) used to determine the structure.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasMethodToDetermineStruct.property(), x);
	}

	private void createPdbxLsSigmaI(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_SIGMA_I, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsSigmaI.resource());
		getRdfModel().add(x, RDFS.label, "Data cutoff (SIGMA(I)).", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsSigmaI.property(), x);
	}

	private void createPdbxLsSigmaF(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_SIGMA_F, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsSigmaF.resource());
		getRdfModel().add(x, RDFS.label, "Data cutoff (SIGMA(F)).", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsSigmaF.property(), x);
	}

	private void createPdbxLsCrossValidMethod(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_CROSS_VALID_METHOD, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsCrossValidMethod.resource());
		getRdfModel().add(x, RDFS.label,
				"Whether the cross validataion method was used through out or only at the end.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsCrossValidMethod.property(), x);
	}

	private void createPdbxIsotropicThermalModel(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.ISOTROPIC_THERMAL_MODEL, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.IsotropicThermalModel.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"Whether the structure was refined with indvidual isotropic, anisotropic or overall temperature factor.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasIsotropicThermalModel.property(), x);
	}

	private void createPdbxDataCutoffLowAbsF(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.DATA_CUTOFF_LOW_ABSF, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.DataCutoffLowAbsF.resource());
		getRdfModel().add(x, RDFS.label, "Value of F at &quot;low end&quot; of data cutoff.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasDataCutoffLowAbsF.property(), x);
	}

	private void createPdbxDataCutoffHighRmsAbsF(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.DATA_CUTOFF_HIGH_RMS_ABSF, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.DataCutoffHighRmsAbsF.resource());
		getRdfModel().add(x, RDFS.label, "Value of RMS |F| used as high data cutoff. 205.1", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasDataCutoffHighRmsAbsF.property(), x);
	}

	private void createPdbxDataCutoffHighAbsF(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.DATA_CUTOFF_HIGH_ABSF, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.DataCutoffHighAbsF.resource());
		getRdfModel().add(x, RDFS.label, "Value of F at &quot;high end&quot; of data cutoff. 17600", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasDataCutoffHighAbsF.property(), x);
	}

	private void createOverallPdbxRFreeSelectionDetails(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.R_FREE_SELECTION_DETAILS, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.RFreeSelectionDetails.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"Details of the manner in which the cross validation reflections were selected. Random selection",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasRFreeSelectionDetails.property(), x);
	}

	private void createOverallSURFree(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OVERALL_SU_R_FREE, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OverallSURFree.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The overall standard uncertainty (estimated standard deviation) of the displacement parameters based on the free R value.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOverallSURFree.property(), x);
	}

	private void createOverallSURCruickshankDPI(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OVERALL_SU_R_CRUICKSHANK_DPI, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OverallSURCruishankDPI.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The overall standard uncertainty (estimated standard deviation) of the displacement parameters based on the crystallographic R value, expressed in a formalism known as the dispersion precision indicator (DPI).",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOverallSURCruickshankDPI.property(), x);
	}

	private void createOverallSUML(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OVERALL_SU_ML, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OverallSUML.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The overall standard uncertainty (estimated standard deviation)of the positional parameters based on a maximum likelihood residual.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOverallSUML.property(), x);
	}

	private void createOverallSUB(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OVERALL_SU_B, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OverallSUB.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The overall standard uncertainty (estimated standard deviation)of the displacement parameters based on a maximum-likelihood residual",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOverallSUB.property(), x);
	}

	private void createOverallFomWorkRSet(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OVERALL_FOM_WORK_R_SET, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OverallFOMWorkRSet.resource());
		getRdfModel().add(x, RDFS.label,
				"Average figure of merit of phases of reflections included in the refinement.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOverallFOMWorkRSet.property(), x);
	}

	private void createOverallFomFreeRSet(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OVERALL_FOM_FREE_R_SET, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OverallFOMFreeRSet.resource());
		getRdfModel().add(x, RDFS.label,
				"Average figure of merit of phases of reflections not included in the refinement.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOverallFOMFreeRSet.property(), x);
	}

	private void createOccupancyMin(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OCCUPANCY_MIN, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OccupancyMin.resource());
		getRdfModel().add(x, RDFS.label, "The minimum value for occupancy found in the coordinate set.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOccupancyMin.property(), x);
	}

	private void createOccupancyMax(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.OCCUPANCY_MAX, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.OccupancyMax.resource());
		getRdfModel().add(x, RDFS.label, "The maximum value for occupancy found in the coordinate set.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasOccupancyMax.property(), x);
	}

	private void createLsWrFactorRWork(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_WR_FACTOR_R_WORK, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsWRFactorRWork.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"Weighted residual factor wR for reflections that satisfy the resolution limits established by attribute ls_d_res_high in category refine and  attribute ls_d_res_low in category refine and the observation limit established by attribute observed_criterion in category reflns, and that were used as the working reflections (i.e. were included in the refinement) when the refinement included the calculation of a &apos;free&apos; R factor.Details of how reflections were assigned to the working andtest sets are given in attribute R_free_details.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsWRFactorRWork.property(), x);
	}

	private void createLsWrFactorRFree(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_WR_FACTOR_R_FREE, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsWRFactorRFree.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"Weighted residual factor wR for reflections that satisfy the resolution limits established by attribute ls_d_res_high in category refine and  attribute ls_d_res_low in category refine and the observation limit established by attribute observed_criterion in category reflns, and that were used as the test reflections (i.e. were excluded from the refinement) when the refinement included the calculation of a &apos;free&apos; R factor. Details of how reflections were assigned to the working and test sets are given in attribute R_free_details.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsWRFactorRFree.property(), x);
	}

	private void createLsRedundancy(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_REDUNDANCY_REFLNS_OBS, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsRedundancyReflnsObs.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The ratio of the total number of observations of the reflections that satisfy the resolution limits established by _refine.ls_d_res_high and _refine.ls_d_res_low and the observation limit established by attribute observed_criterion in category reflns to  the number of crystallographically unique reflections that satisfy the same limits.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsRedundancyReflnsObs.property(), x);
	}

	private void createLsPercentReflnsObs(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_PERCENT_REFLNS_OBS, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsPercentReflnsObs.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The number of reflections that satisfy the resolution limits established by _refine.ls_d_res_high and _refine.ls_d_res_low and the observation limit established by attribute observed_criterion in category reflns, expressed as a percentage of the  number of geometrically observable reflections that satisfy the resolution limits.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsPercentReflnsObs.property(), x);
	}

	private void createLsPercentReflnsRFree(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_PERCENT_REFLNS_R_FREE, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsPercentReflnsRFree.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The number of reflections that satisfy the resolution limits established by _refine.ls_d_res_high and _refine.ls_d_res_low and the observation limit established by attribute observed_criterion in category reflns, and that were used as the test reflections (i.e. were excluded from the refinement) when the refinement included the calculation of a &apos;free&apos; R factor, expressed as a percentage of the number of geometrically observable reflections that satisfy the resolution limits.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsPercentReflnsRFree.property(), x);
	}

	private void createLsNumberRestraints(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_NUMBER_RESTRAINTS, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsNumberRestraints.resource());
		getRdfModel().add(x, RDFS.label, "The number of restrained parameters. ", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsNumberRestraints.property(), x);
	}

	private void createLsNumberReflnsObs(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_NUMBER_REFLNS_OBS, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsNumberReflnsObs.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The number of reflections that satisfy the resolution limits established by _refine.ls_d_res_high and _refine.ls_d_res_low and the observation limit established by attribute observed_criterion in category reflns. ",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsNumberReflnsObs.property(), x);
	}

	private void createLsNumberReflnsAll(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_NUMBER_REFLNS_ALL, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsNumberReflnsAll.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The number of reflections that satisfy the resolution limits established by _refine.ls_d_res_high and _refine.ls_d_res_low.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsNumberReflnsAll.property(), x);
	}

	private void createLsNumberReflnsRFree(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_NUMBER_REFLNS_R_FREE, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsNumberReflnsRFree.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The number of reflections that satisfy the resolution limits established by _refine.ls_d_res_high and _refine.ls_d_res_low and the observation limit established by attribute observed_criterion in category reflns, and that were used as the test  reflections.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsNumberReflnsRFree.property(), x);
	}

	private void createLsNumberParameters(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_NUMBER_PARAMETERS, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsNumberParameters.resource());
		getRdfModel().add(x, RDFS.label, "The number of parameters refined in the least-squares process.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsNumberParameters.property(), x);
	}

	private void createLsDResLow(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_D_RES_LOW, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsDResLow.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The largest value for the interplanar spacings for the reflection data used in the refinement in angstroms. This is called the lowest resolution.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsDResLow.property(), x);
	}

	private void createLsDResHigh(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_D_RES_HIGH, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsDResHigh.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"The smallest value for the interplanar spacings for the reflection data used in the refinement in angstroms. This is called the highest resolution.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsDResHigh.property(), x);

	}

	private void createLsRFactorObs(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_R_FACTOR_OBS, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsRFactorObs.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"Residual factor R for reflections that satisfy the resolution limits established by attribute ls_d_res_high in category refine and attribute ls_d_res_low in category refine and the observation limit established by attribute observed_criterion.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsRFactorObs.property(), x);
	}

	private void createLsRFactorAll(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.LS_R_FACTOR_ALL, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.LsRFactorAll.resource());
		getRdfModel()
				.add(x,
						RDFS.label,
						"Residual factor R for all reflections that satisfy the resolution limits established by attribute ls_d_res_high in category refine and attribute ls_d_res_low.",
						"en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsRFactorAll.property(), x);
	}

	private void createLsRFactorRWork(String value) {
		Resource lsRFactorRWork = createResource(Bio2RdfPdbUriPattern.LS_R_FACTOR_R_WORK, pdbId);
		getRdfModel().add(lsRFactorRWork, RDF.type, PdbOwlVocabulary.Class.LsRFactorRWork.resource());
		getRdfModel()
				.add(lsRFactorRWork,
						RDFS.label,
						"Residual factor R for reflections that satisfy the resolution limits established by attribute ls_d_res_high in category refine and  attribute ls_d_res_low in category refine and the observation limit established by attribute observed_criterion in category reflns, and that were used as the working reflections (i.e. were included in the refinement)  when the refinement included the calculation of a R factor. ",
						"en");
		getRdfModel().add(lsRFactorRWork, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsRFactorRWork.property(), lsRFactorRWork);
	}

	private void createLsRFactorRFreeErrorDetails(String value) {
		Resource lsRFactorRFreeError = createResource(Bio2RdfPdbUriPattern.LS_R_FACTOR_R_FREE_ERROR, pdbId);
		getRdfModel().add(lsRFactorRFreeError, PdbOwlVocabulary.Annotation.details.property(), value, "en");
	}

	private void createLsRFactorRFreeError(String value) {
		Resource lsRFactorRFreeError = createResource(Bio2RdfPdbUriPattern.LS_R_FACTOR_R_FREE_ERROR, pdbId);
		getRdfModel().add(lsRFactorRFreeError, RDF.type, PdbOwlVocabulary.Class.LsRFactorRFreeError.resource());
		getRdfModel()
				.add(lsRFactorRFreeError,
						RDFS.label,
						"The estimated error in attribute ls_R_factor_R_free. in category refine The method used to estimate the error is described in the item attribute ls_R_factor_R_free_error_details in category refine. ",
						"en");
		getRdfModel().add(lsRFactorRFreeError, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsRFactorRFreeError.property(),
				lsRFactorRFreeError);
	}

	private void createLsRFactorRFree(String value) {
		Resource lsRFactorRFree = createResource(Bio2RdfPdbUriPattern.LS_R_FACTOR_R_FREE, pdbId);
		getRdfModel().add(lsRFactorRFree, RDF.type, PdbOwlVocabulary.Class.LsRFactorRFree.resource());
		getRdfModel()
				.add(lsRFactorRFree,
						RDFS.label,
						"Residual factor R for reflections that satisfy the resolution limits established by attribute ls_d_res_high in category refine and  attribute ls_d_res_low in category refine and the observation limit established by  attribute observed_criterion in category reflns, and that were used as the test  reflections (i.e. were excluded from the refinement) when the refinement included the calculation of a &apos;free&apos; R factor. Details of how reflections were assigned to the working and test sets are given in attribute R_free_details.",
						"en");
		getRdfModel().add(lsRFactorRFree, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasLsRFactorRFree.property(), lsRFactorRFree);
	}

	private void createDetails(String value) {
		// TODO: should this be models with details annotation?
		Resource details = createResource(Bio2RdfPdbUriPattern.REFINEMENT_DETAILS, pdbId);
		getRdfModel().add(details, RDF.type, PdbOwlVocabulary.Class.RefinementDetails.resource());
		getRdfModel().add(details, RDFS.label, "Description of special aspects of the refinement process", "en");
		getRdfModel().add(details, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasDetails.property(), details);
	}

	private void createCorrelationCoeffFoToFcFree(String value) {
		Resource ccFtoFcFree = createResource(Bio2RdfPdbUriPattern.CORRELATION_COEFFICIENT_FO_TO_FC_FREE, pdbId);
		getRdfModel().add(ccFtoFcFree, RDF.type, PdbOwlVocabulary.Class.CorrelationCoefficientFoToFcFree.resource());
		getRdfModel()
				.add(ccFtoFcFree,
						RDFS.label,
						"The correlation coefficient between the observed and calculated structure factors for reflections not included in the refinement (free reflections)",
						"en");
		getRdfModel().add(ccFtoFcFree, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasCorrelationCoefficientFoToFcFree.property(),
				ccFtoFcFree);
	}

	private void createCorrelationCoeffFoToFc(String value) {
		Resource ccFtoFc = createResource(Bio2RdfPdbUriPattern.CORRELATION_COEFFICIENT_FO_TO_FC, pdbId);
		getRdfModel().add(ccFtoFc, RDF.type, PdbOwlVocabulary.Class.CorrelationCoefficientFoToFc.resource());
		getRdfModel()
				.add(ccFtoFc,
						RDFS.label,
						"The correlation coefficient between the observed and calculated structure factors for reflections included in the refinement",
						"en");
		getRdfModel().add(ccFtoFc, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasCorrelationCoefficientFoToFc.property(),
				ccFtoFc);
	}

	private void createBIsoMin(String value) {
		Resource BIsoMin = createResource(Bio2RdfPdbUriPattern.B_ISO_MIN, pdbId);
		getRdfModel().add(BIsoMin, RDF.type, PdbOwlVocabulary.Class.BIsoMin.resource());
		getRdfModel().add(BIsoMin, RDFS.label,
				"The minimum isotropic displacement parameter (B value) found in the coordinate set", "en");
		getRdfModel().add(BIsoMin, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasBIsoMin.property(), BIsoMin);
	}

	private void createBIsoMax(String value) {
		Resource BIsoMax = createResource(Bio2RdfPdbUriPattern.B_ISO_MAX, pdbId);
		getRdfModel().add(BIsoMax, RDF.type, PdbOwlVocabulary.Class.BIsoMax.resource());
		getRdfModel().add(BIsoMax, RDFS.label,
				"The maximum isotropic displacement parameter (B value) found in the coordinate set", "en");
		getRdfModel().add(BIsoMax, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasBIsoMax.property(), BIsoMax);
	}

	private void createAnisoB33(String value) {
		Resource Aniso_B33 = createResource(Bio2RdfPdbUriPattern.ANISO_B33, pdbId);
		getRdfModel().add(Aniso_B33, RDF.type, PdbOwlVocabulary.Class.AnisoB33.resource());
		getRdfModel()
				.add(Aniso_B33,
						RDFS.label,
						"The [3][3] element of the matrix that defines the overall anisotropic displacement model if one was refined for this structure",
						"en");
		getRdfModel().add(Aniso_B33, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasAnisoB33.property(), Aniso_B33);
	}

	private void createAnisoB23(String value) {
		Resource Aniso_B23 = createResource(Bio2RdfPdbUriPattern.ANISO_B23, pdbId);
		getRdfModel().add(Aniso_B23, RDF.type, PdbOwlVocabulary.Class.AnisoB23.resource());
		getRdfModel()
				.add(Aniso_B23,
						RDFS.label,
						"The [2][3] element of the matrix that defines the overall anisotropic displacement model if one was refined for this structure",
						"en");
		getRdfModel().add(Aniso_B23, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasAnisoB23.property(), Aniso_B23);
	}

	private void createAnisoB22(String value) {
		Resource Aniso_B22 = createResource(Bio2RdfPdbUriPattern.ANISO_B22, pdbId);
		getRdfModel().add(Aniso_B22, RDF.type, PdbOwlVocabulary.Class.AnisoB22.resource());
		getRdfModel()
				.add(Aniso_B22,
						RDFS.label,
						"The [2][2] element of the matrix that defines the overall anisotropic displacement model if one was refined for this structure",
						"en");
		getRdfModel().add(Aniso_B22, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasAnisoB22.property(), Aniso_B22);
	}

	private void createAnisoB13(String value) {
		Resource Aniso_B13 = createResource(Bio2RdfPdbUriPattern.ANISO_B13, pdbId);
		getRdfModel().add(Aniso_B13, RDF.type, PdbOwlVocabulary.Class.AnisoB13.resource());
		getRdfModel()
				.add(Aniso_B13,
						RDFS.label,
						"The [1][3] element of the matrix that defines the overall anisotropic displacement model if one was refined for this structure",
						"en");
		getRdfModel().add(Aniso_B13, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasAnisoB13.property(), Aniso_B13);
	}

	private void createAnisoB12(String value) {
		Resource Aniso_B12 = createResource(Bio2RdfPdbUriPattern.ANISO_B12, pdbId);
		getRdfModel().add(Aniso_B12, RDF.type, PdbOwlVocabulary.Class.AnisoB12.resource());
		getRdfModel()
				.add(Aniso_B12,
						RDFS.label,
						"The [1][2] element of the matrix that defines the overall anisotropic displacement model if one was refined for this structure",
						"en");
		getRdfModel().add(Aniso_B12, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasAnisoB12.property(), Aniso_B12);
	}

	private void createAnisoB11(String value) {
		Resource Aniso_B11 = createResource(Bio2RdfPdbUriPattern.ANISO_B11, pdbId);
		getRdfModel().add(Aniso_B11, RDF.type, PdbOwlVocabulary.Class.AnisoB11.resource());
		getRdfModel()
				.add(Aniso_B11,
						RDFS.label,
						"The [1][1] element of the matrix that defines the overall anisotropic displacement model if one was refined for this structure",
						"en");
		getRdfModel().add(Aniso_B11, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasAnisoB11.property(), Aniso_B11);
	}

	private void createBIsoMean(String value) {
		// create the resource
		Resource BIsoMean = createResource(Bio2RdfPdbUriPattern.B_ISO_MEAN, pdbId);
		// type the resource
		getRdfModel().add(BIsoMean, RDF.type, PdbOwlVocabulary.Class.BIsoMean.resource());
		// add label
		getRdfModel().add(BIsoMean, RDFS.label,
				"Mean isotropic displacement parameter (B value) for the coordinate set", "en");
		// add value
		getRdfModel().add(BIsoMean, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		// link to refinement
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasBIsoMean.property(), BIsoMean);
	}

	private void createRefinement() {
		Resource structureDetermination = createResource(Bio2RdfPdbUriPattern.STRUCTURE_DETERMINATION, pdbId);
		refinement = createResource(Bio2RdfPdbUriPattern.REFINEMENT, pdbId);
		getRdfModel().add(refinement, RDF.type, PdbOwlVocabulary.Class.Refinement.resource());
		getRdfModel().add(refinement, RDFS.label, "Refinement of " + pdbId, "en");
		getRdfModel().add(structureDetermination, PdbOwlVocabulary.ObjectProperty.hasPart.property(), refinement);
	}
}
