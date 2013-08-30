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

import com.hp.hpl.jena.ontology.AnnotationProperty;
import com.hp.hpl.jena.ontology.DatatypeProperty;
import com.hp.hpl.jena.ontology.OntClass;
import com.hp.hpl.jena.ontology.OntModel;
import com.hp.hpl.jena.ontology.OntModelSpec;
import com.hp.hpl.jena.rdf.model.ModelFactory;

/**
 * @author Alexander De Leon
 */
public class PdbOwlVocabulary {

	public static final String DEFAULT_NAMESPACE = "http://bio2rdf.org/pdb_vocabulary:";

	private static final OntModel model = ModelFactory.createOntologyModel(OntModelSpec.OWL_MEM);
	static {
		model.setNsPrefix("pdb", PdbOwlVocabulary.DEFAULT_NAMESPACE);
	}

	public static enum Class {
		PdbRecord(DEFAULT_NAMESPACE + "PdbRecord"),
		Experiment(DEFAULT_NAMESPACE + "Experiment"),
		ChemicalSubstance(DEFAULT_NAMESPACE + "ChemicalSubstance"),
		Polymer(DEFAULT_NAMESPACE + "Polymer"),
		NonPolymer(DEFAULT_NAMESPACE + "NonPolymer"),
		Water(DEFAULT_NAMESPACE + "Water"),
		Macrolide(DEFAULT_NAMESPACE + "Macrolide"),
		StructureDetermination(DEFAULT_NAMESPACE + "StructureDetermination"),
		ChemicalSubstanceExtraction(DEFAULT_NAMESPACE + "ChemicalSubstanceExtraction"),
		TheoreticalFormulaWeight(DEFAULT_NAMESPACE + "TheoreticalFormulaWeight"),
		ExperimentalFormulaWeight(DEFAULT_NAMESPACE + "ExperimentalFormulaWeight"),
		ChemicalSubstanceAmount(DEFAULT_NAMESPACE + "ChemicalSubstanceAmount"),
		NaturalChemicalSubstanceExtraction(DEFAULT_NAMESPACE + "NaturalChemicalSubstanceExtraction"),
		SynthecticChemicalSubstanceExtraction(DEFAULT_NAMESPACE + "SynthecticChemicalSubstanceExtraction"),
		GeneticallyManipulatedChemicalSubstanceExtraction(DEFAULT_NAMESPACE
				+ "GeneticallyManipulatedChemicalSubstanceExtraction"),
		Abstrat(DEFAULT_NAMESPACE + "Abstract"),
		Publication(DEFAULT_NAMESPACE + "Publication"),
		Book(DEFAULT_NAMESPACE + "Book"),
		Journal(DEFAULT_NAMESPACE + "Journal"),
		Publisher(DEFAULT_NAMESPACE + "Publisher"),
		Title(DEFAULT_NAMESPACE + "Title"),
		Country(DEFAULT_NAMESPACE + "Country"),
		MedlineId(DEFAULT_NAMESPACE + "MedlineId"),
		JournalAbbreviation(DEFAULT_NAMESPACE + "JournalAbbreviation"),
		Name(DEFAULT_NAMESPACE + "Name"),
		VolumeNumber(DEFAULT_NAMESPACE + "VolumeNumber"),
		ISBN(DEFAULT_NAMESPACE + "ISBN"),
		DocumentVolume(DEFAULT_NAMESPACE + "DocumentVolume"),
		ISSN(DEFAULT_NAMESPACE + "ISSN"),
		Language(DEFAULT_NAMESPACE + "Language"),
		PageNumber(DEFAULT_NAMESPACE + "PageNumber"),
		DOI(DEFAULT_NAMESPACE + "DOI"),
		PubmedId(DEFAULT_NAMESPACE + "PubmedId"),
		PublicationYear(DEFAULT_NAMESPACE + "PublicationYear"),
		NumberOfMonomers(DEFAULT_NAMESPACE + "NumberOfMonomers"),
		PolymerSequence(DEFAULT_NAMESPACE + "PolymerSequence"),
		CanonicalPolymerSequence(DEFAULT_NAMESPACE + "CanonicalPolymerSequence"),
		PolypeptideD(DEFAULT_NAMESPACE + "Polypeptide(D)"),
		PolypeptideL(DEFAULT_NAMESPACE + "Polypeptide(L)"),
		Polydeoxyribonucleotide(DEFAULT_NAMESPACE + "Polydeoxyribonucleotide"),
		Polyribonucleotide(DEFAULT_NAMESPACE + "Polyribonucleotide"),
		PolysaccharideD(DEFAULT_NAMESPACE + "Polysaccharide(D)"),
		PolysaccharideL(DEFAULT_NAMESPACE + "polysaccharide(L)"),
		PolydeoxyribonucleotidePolyribonucleotide(DEFAULT_NAMESPACE + "Polydeoxyribonucleotide-Polyribonucleotide"),
		CyclicPseudoPeptide(DEFAULT_NAMESPACE + "CyclicPseudoPeptide"),
		Cell(DEFAULT_NAMESPACE + "Cell"),
		Organ(DEFAULT_NAMESPACE + "Organ"),
		Organelle(DEFAULT_NAMESPACE + "Organelle"),
		Secretion(DEFAULT_NAMESPACE + "Secretion"),
		Tissue(DEFAULT_NAMESPACE + "Tissue"),
		TissueFraction(DEFAULT_NAMESPACE + "TissueFraction"),
		CellularLocation(DEFAULT_NAMESPACE + "CellularLocation"),
		IsotropicAtomicDisplacement(DEFAULT_NAMESPACE + "IsotropicAtomicDisplacement"),
		CartesianCoordinate(DEFAULT_NAMESPACE + "CartesianCoordinate"),
		XCartesianCoordinate(DEFAULT_NAMESPACE + "XCartesianCoordinate"),
		YCartesianCoordinate(DEFAULT_NAMESPACE + "YCartesianCoordinate"),
		ZCartesianCoordinate(DEFAULT_NAMESPACE + "ZCartesianCoordinate"),
		PartialCharge(DEFAULT_NAMESPACE + "PartialCharge"),
		Cosmid(DEFAULT_NAMESPACE + "Cosmid"),
		Plasmid(DEFAULT_NAMESPACE + "Plasmid"),
		Virus(DEFAULT_NAMESPACE + "Virus"),
		AtomOccupancy(DEFAULT_NAMESPACE + "AtomOccupancy"),
		HydrogenAtom(DEFAULT_NAMESPACE + "HydrogenAtom"),
		HeliumAtom(DEFAULT_NAMESPACE + "HeliumAtom"),
		LithiumAtom(DEFAULT_NAMESPACE + "LithiumAtom"),
		BerylliumAtom(DEFAULT_NAMESPACE + "BerylliumAtom"),
		BoronAtom(DEFAULT_NAMESPACE + "BoronAtom"),
		CarbonAtom(DEFAULT_NAMESPACE + "CarbonAtom"),
		NitrogenAtom(DEFAULT_NAMESPACE + "NitrogenAtom"),
		OxygenAtom(DEFAULT_NAMESPACE + "OxygenAtom"),
		FluorineAtom(DEFAULT_NAMESPACE + "FluorineAtom"),
		NeonAtom(DEFAULT_NAMESPACE + "NeonAtom"),
		SodiumAtom(DEFAULT_NAMESPACE + "SodiumAtom"),
		MagnesiumAtom(DEFAULT_NAMESPACE + "MagnesiumAtom"),
		AluminumAtom(DEFAULT_NAMESPACE + "AluminumAtom"),
		SiliconAtom(DEFAULT_NAMESPACE + "SiliconAtom"),
		PhosphorusAtom(DEFAULT_NAMESPACE + "PhosphorusAtom"),
		SulfurAtom(DEFAULT_NAMESPACE + "SulfurAtom"),
		ChlorineAtom(DEFAULT_NAMESPACE + "ChlorineAtom"),
		ArgonAtom(DEFAULT_NAMESPACE + "ArgonAtom"),
		PotassiumAtom(DEFAULT_NAMESPACE + "PotassiumAtom"),
		CalciumAtom(DEFAULT_NAMESPACE + "CalciumAtom"),
		ScandiumAtom(DEFAULT_NAMESPACE + "ScandiumAtom"),
		TitaniumAtom(DEFAULT_NAMESPACE + "TitaniumAtom"),
		VanadiumAtom(DEFAULT_NAMESPACE + "VanadiumAtom"),
		ChromiumAtom(DEFAULT_NAMESPACE + "ChromiumAtom"),
		ManganeseAtom(DEFAULT_NAMESPACE + "ManganeseAtom"),
		IronAtom(DEFAULT_NAMESPACE + "IronAtom"),
		CobaltAtom(DEFAULT_NAMESPACE + "CobaltAtom"),
		NickelAtom(DEFAULT_NAMESPACE + "NickelAtom"),
		CopperAtom(DEFAULT_NAMESPACE + "CopperAtom"),
		ZincAtom(DEFAULT_NAMESPACE + "ZincAtom"),
		GalliumAtom(DEFAULT_NAMESPACE + "GalliumAtom"),
		GermaniumAtom(DEFAULT_NAMESPACE + "GermaniumAtom"),
		ArsenicAtom(DEFAULT_NAMESPACE + "ArsenicAtom"),
		SeleniumAtom(DEFAULT_NAMESPACE + "SeleniumAtom"),
		BromineAtom(DEFAULT_NAMESPACE + "BromineAtom"),
		KryptonAtom(DEFAULT_NAMESPACE + "KryptonAtom"),
		RubidiumAtom(DEFAULT_NAMESPACE + "RubidiumAtom"),
		StrontiumAtom(DEFAULT_NAMESPACE + "StrontiumAtom"),
		YttriumAtom(DEFAULT_NAMESPACE + "YttriumAtom"),
		MolybdenumAtom(DEFAULT_NAMESPACE + "MolybdenumAtom"),
		RhodiumAtom(DEFAULT_NAMESPACE + "RhodiumAtom"),
		PalladiumAtom(DEFAULT_NAMESPACE + "PalladiumAtom"),
		SilverAtom(DEFAULT_NAMESPACE + "SilverAtom"),
		CadmiumAtom(DEFAULT_NAMESPACE + "CadmiumAtom"),
		IodineAtom(DEFAULT_NAMESPACE + "IodineAtom"),
		MercuryAtom(DEFAULT_NAMESPACE + "MercuryAtom"),
		LeadAtom(DEFAULT_NAMESPACE + "LeadAtom"),
		XenonAtom(DEFAULT_NAMESPACE + "XenonAtom"),
		AtomSpatialLocation(DEFAULT_NAMESPACE + "AtomSpatialLocation"),
		Model(DEFAULT_NAMESPACE + "Model"),
		Chain(DEFAULT_NAMESPACE + "Chain"),
		ChainPosition(DEFAULT_NAMESPACE + "ChainPosition"),
		DevelopmentStage(DEFAULT_NAMESPACE + "DevelopmentStage"),
		NumberOfAtoms(DEFAULT_NAMESPACE + "NumberOfAtoms"),
		NumberOfNonHydrogenAtoms(DEFAULT_NAMESPACE + "NumberOfNonHydrogenAtoms"),
		ChemicalFormula(DEFAULT_NAMESPACE + "ChemicalFormula"),
		Residue(DEFAULT_NAMESPACE + "Residue"),
		Helix(DEFAULT_NAMESPACE + "Helix"),
		RightHandedHelix(DEFAULT_NAMESPACE + "RightHandedHelix"),
		RightHandedAlphaHelix(DEFAULT_NAMESPACE + "RightHandedAlphaHelix"),
		RightHandedGammaHelix(DEFAULT_NAMESPACE + "RightHandedGammaHelix"),
		RightHandedOmegaHelix(DEFAULT_NAMESPACE + "RightHandedOmegaHelix"),
		RightHandedPiHelix(DEFAULT_NAMESPACE + "RightHandedPiHelix"),
		RightHanded22_7Helix(DEFAULT_NAMESPACE + "RightHanded2.2-7Helix"),
		RightHanded3_10Helix(DEFAULT_NAMESPACE + "RightHanded3-10Helix"),
		RightHandedPolyprolineHelix(DEFAULT_NAMESPACE + "RightHandPolyprolineHelix"),
		LeftHandedHelix(DEFAULT_NAMESPACE + "LeftHandedHelix"),
		LeftHandedAlphaHelix(DEFAULT_NAMESPACE + "LeftHandedAlphaHelix"),
		LeftHandedGammaHelix(DEFAULT_NAMESPACE + "LeftHandedGammaHelix"),
		LeftHandedOmegaHelix(DEFAULT_NAMESPACE + "LeftHandedOmegaHelix"),
		LeftHandedPiHelix(DEFAULT_NAMESPACE + "LeftHandedPiHelix"),
		LeftHanded22_7Helix(DEFAULT_NAMESPACE + "LeftHanded2.2-7Helix"),
		LeftHanded3_10Helix(DEFAULT_NAMESPACE + "LeftHanded3-10Helix"),
		LeftHandedPolyprolineHelix(DEFAULT_NAMESPACE + "LeftHandedPolyprolineHelix"),
		NonStandardHelix(DEFAULT_NAMESPACE + "NonStandardHelix"),
		NonStandardRightHandedHelix(DEFAULT_NAMESPACE + "NonStandardRightHandedHelix"),
		DoubleHelix(DEFAULT_NAMESPACE + "DoubleHelix"),
		NonStandardLeftHandedHelix(DEFAULT_NAMESPACE + "NonStandardLeftHandedHelix"),
		NonStandardDoubleHelix(DEFAULT_NAMESPACE + "NonStandardDoubleHelix"),
		RightHandedDoubleHelix(DEFAULT_NAMESPACE + "RightHandedDoubleHelix"),
		NonStandardRightHandedDoubleHelix(DEFAULT_NAMESPACE + "NonStandardRightHandedDoubleHelix"),
		RightHandedADoubleHelix(DEFAULT_NAMESPACE + "RightHandedADoubleHelix"),
		RightHandedBDoubleHelix(DEFAULT_NAMESPACE + "RightHandedBDoubleHelix"),
		RightHandedZDoubleHelix(DEFAULT_NAMESPACE + "RightHandedZDoubleHelix"),
		LeftHandedDoubleHelix(DEFAULT_NAMESPACE + "LeftHandedDoubleHelix"),
		NonStandardLeftHandedDoubleHelix(DEFAULT_NAMESPACE + "NonStandardLeftHandedDoubleHelix"),
		LeftHandedADoubleHelix(DEFAULT_NAMESPACE + "LeftHandedADoubleHelix"),
		LeftHandedBDoubleHelix(DEFAULT_NAMESPACE + "LeftHandedBDoubleHelix"),
		LeftHandedZDoubleHelix(DEFAULT_NAMESPACE + "LeftHandedZDoubleHelix"),
		Turn(DEFAULT_NAMESPACE + "Turn"),
		NonStandardTurn(DEFAULT_NAMESPACE + "NonStandardTurn"),
		TypeITurn(DEFAULT_NAMESPACE + "TypeITurn"),
		TypeIPrimeTurn(DEFAULT_NAMESPACE + "TypeIPrimeTurn"),
		TypeIITurn(DEFAULT_NAMESPACE + "TypeIITurn"),
		TypeIIPrimeTurn(DEFAULT_NAMESPACE + "TypeIIPrimeTurn"),
		TypeIIITurn(DEFAULT_NAMESPACE + "TypeIIITurn"),
		TypeIIIPrimeTurn(DEFAULT_NAMESPACE + "TypeIIIPrimeTurn"),
		BetaStrand(DEFAULT_NAMESPACE + "BetaStrand"),
		HelixLength(DEFAULT_NAMESPACE + "HelixLength"),
		AngleAlpha(DEFAULT_NAMESPACE + "AngleAlpha"),
		AngleBeta(DEFAULT_NAMESPACE + "AngleBeta"),
		AngleGamma(DEFAULT_NAMESPACE + "AngleGamma"),
		EdgeALength(DEFAULT_NAMESPACE + "EdgeALength"),
		EdgeBLength(DEFAULT_NAMESPACE + "EdgeBLength"),
		EdgeCLength(DEFAULT_NAMESPACE + "EdgeCLength"),
		ReciprocalAngleAlpha(DEFAULT_NAMESPACE + "ReciprocalAngleAlpha"),
		ReciprocalAngleGamma(DEFAULT_NAMESPACE + "ReciprocalAngleGamma"),
		ReciprocalAngleBeta(DEFAULT_NAMESPACE + "ReciprocalAngleBeta"),
		ReciprocalEdgeALength(DEFAULT_NAMESPACE + "ReciprocalEdgeALength"),
		ReciprocalEdgeBLength(DEFAULT_NAMESPACE + "ReciprocalEdgeBLength"),
		ReciprocalEdgeCLength(DEFAULT_NAMESPACE + "ReciprocalEdgeCLength"),
		Volume(DEFAULT_NAMESPACE + "Volume"),
		XRayDiffraction(DEFAULT_NAMESPACE + "XRayDiffraction"),
		NeutronDiffraction(DEFAULT_NAMESPACE + "NeutronDiffraction"),
		FiberDiffraction(DEFAULT_NAMESPACE + "FiberDiffraction"),
		ElectronCrystallography(DEFAULT_NAMESPACE + "ElectronCrystallography"),
		ElectronMicroscopy(DEFAULT_NAMESPACE + "ElectronMicroscopy"),
		SolutionNmr(DEFAULT_NAMESPACE + "SolutionNmr"),
		SolidStateNmr(DEFAULT_NAMESPACE + "SolidStateNmr"),
		SolutionScattering(DEFAULT_NAMESPACE + "SolutionScattering"),
		PowderDiffraction(DEFAULT_NAMESPACE + "PowderDiffraction"),
		InfraredSpectroscopy(DEFAULT_NAMESPACE + "InfraredSpectroscopy"),
		CoefficientMu(DEFAULT_NAMESPACE + "CoefficientMu"),
		MaximumTransmissionFactor(DEFAULT_NAMESPACE + "MaximumTransmissionFactor"),
		MinimumTransmissionFactor(DEFAULT_NAMESPACE + "MinimumTransmissionFactor"),
		AnalyticalAbsortCorrection(DEFAULT_NAMESPACE + "AnalyticalAbsortCorrection"),
		CylinderAbsortCorrection(DEFAULT_NAMESPACE + "CylinderAbsortCorrection"),
		EmpiricalAbsortCorrection(DEFAULT_NAMESPACE + "EmpiricalAbsortCorrection"),
		GaussianAbsortCorrection(DEFAULT_NAMESPACE + "GaussianAbsortCorrection"),
		IntegrationAbsortCorrection(DEFAULT_NAMESPACE + "IntegrationAbsortCorrection"),
		MultiScanAbsortCorrection(DEFAULT_NAMESPACE + "MultiScanAbsortCorrection"),
		NumericalAbsortCorrection(DEFAULT_NAMESPACE + "NumericalAbsortCorrection"),
		PsiScanAbsortCorrection(DEFAULT_NAMESPACE + "PsiScanAbsortCorrection"),
		RefdelfAbsortCorrection(DEFAULT_NAMESPACE + "RefdelfAbsortCorrection"),
		SphereAbsortCorrection(DEFAULT_NAMESPACE + "SphereAbsortCorrection"),
		Macrophage(DEFAULT_NAMESPACE + "Macrophage"),
		SamariumAtom(DEFAULT_NAMESPACE + "SamariumAtom"),
		Baculovirus(DEFAULT_NAMESPACE + "Baculovirus"),
		Bacteria(DEFAULT_NAMESPACE + "Bacteria"),
		Vector(DEFAULT_NAMESPACE + "Vector"),
		Atom(DEFAULT_NAMESPACE + "Atom"),
		TheoreticalModel(DEFAULT_NAMESPACE + "TheoreticalModel"),
		HybridExperimentalMethod(DEFAULT_NAMESPACE + "HybridExperimentalMethod"),
		ZirconiumAtom(DEFAULT_NAMESPACE + "ZirconiumAtom"),
		NiobiumAtom(DEFAULT_NAMESPACE + "NiobiumAtom"),
		TechnetiumAtom(DEFAULT_NAMESPACE + "TechnetiumAtom"),
		RutheniumAtom(DEFAULT_NAMESPACE + "RutheniumAtom"),
		IndiumAtom(DEFAULT_NAMESPACE + "IndiumAtom"),
		TinAtom(DEFAULT_NAMESPACE + "TinAtom"),
		AntimonyAtom(DEFAULT_NAMESPACE + "AntimonyAtom"),
		TellurimAtom(DEFAULT_NAMESPACE + "TellurimAtom"),
		CesiumAtom(DEFAULT_NAMESPACE + "CesiumAtom"),
		BariumAtom(DEFAULT_NAMESPACE + "BariumAtom"),
		LanthanumAtom(DEFAULT_NAMESPACE + "LanthanumAtom"),
		HafniumAtom(DEFAULT_NAMESPACE + "HafniumAtom"),
		TantalumAtom(DEFAULT_NAMESPACE + "TantalumAtom"),
		TungstenAtom(DEFAULT_NAMESPACE + "TungstenAtom"),
		RheniumAtom(DEFAULT_NAMESPACE + "RheniumAtom"),
		OsmiumAtom(DEFAULT_NAMESPACE + "OsmiumAtom"),
		IridiumAtom(DEFAULT_NAMESPACE + "IridiumAtom"),
		PlatinumAtom(DEFAULT_NAMESPACE + "PlatinumAtom"),
		GoldAtom(DEFAULT_NAMESPACE + "GoldAtom"),
		ThalliumAtom(DEFAULT_NAMESPACE + "ThalliumAtom"),
		BismuthAtom(DEFAULT_NAMESPACE + "BismuthAtom"),
		PoloniumAtom(DEFAULT_NAMESPACE + "PoloniumAtom"),
		AstatineAtom(DEFAULT_NAMESPACE + "AstatineAtom"),
		RadonAtom(DEFAULT_NAMESPACE + "RadonAtom"),
		FranciumAtom(DEFAULT_NAMESPACE + "FranciumAtom"),
		RadiumAtom(DEFAULT_NAMESPACE + "RadiumAtom"),
		ActiniumAtom(DEFAULT_NAMESPACE + "ActiniumAtom"),
		RuthefordiumAtom(DEFAULT_NAMESPACE + "RuthefordiumAtom"),
		DubniumAtom(DEFAULT_NAMESPACE + "DubniumAtom"),
		SeaborgiumAtom(DEFAULT_NAMESPACE + "SeaborgiumAtom"),
		BohriumAtom(DEFAULT_NAMESPACE + "BohriumAtom"),
		HassiumAtom(DEFAULT_NAMESPACE + "HassiumAtom"),
		MeitneriumAtom(DEFAULT_NAMESPACE + "MeitneriumAtom"),
		DarmstadtiumAtom(DEFAULT_NAMESPACE + "DarmstadtiumAtom"),
		RoentgeniumAtom(DEFAULT_NAMESPACE + "RoentgeniumAtom"),
		CeriumAtom(DEFAULT_NAMESPACE + "CeriumAtom"),
		PraseodymiumAtom(DEFAULT_NAMESPACE + "PraseodymiumAtom"),
		NeodymiumAtom(DEFAULT_NAMESPACE + "NeodymiumAtom"),
		PromethiumAtom(DEFAULT_NAMESPACE + "PromethiumAtom"),
		EuropiumAtom(DEFAULT_NAMESPACE + "EuropiumAtom"),
		GadoliniumAtom(DEFAULT_NAMESPACE + "GadoliniumAtom"),
		TerbiumAtom(DEFAULT_NAMESPACE + "TerbiumAtom"),
		DysprosiumAtom(DEFAULT_NAMESPACE + "DysprosiumAtom"),
		HolmiumAtom(DEFAULT_NAMESPACE + "HolmiumAtom"),
		ErbiumAtom(DEFAULT_NAMESPACE + "ErbiumAtom"),
		ThuliumAtom(DEFAULT_NAMESPACE + "ThuliumAtom"),
		YtterbiumAtom(DEFAULT_NAMESPACE + "YtterbiumAtom"),
		LutetiumAtom(DEFAULT_NAMESPACE + "LutetiumAtom"),
		ThoriumAtom(DEFAULT_NAMESPACE + "ThoriumAtom"),
		ProtactiniumAtom(DEFAULT_NAMESPACE + "ProtactiniumAtom"),
		UraniumAtom(DEFAULT_NAMESPACE + "UraniumAtom"),
		NeptuniumAtom(DEFAULT_NAMESPACE + "NeptuniumAtom"),
		PlutoniumAtom(DEFAULT_NAMESPACE + "PlutoniumAtom"),
		AmericiumAtom(DEFAULT_NAMESPACE + "AmericiumAtom"),
		CuriumAtom(DEFAULT_NAMESPACE + "CuriumAtom"),
		BerkeliumAtom(DEFAULT_NAMESPACE + "BerkeliumAtom"),
		CaliforniumAtom(DEFAULT_NAMESPACE + "CaliforniumAtom"),
		EinsteiniumAtom(DEFAULT_NAMESPACE + "EinsteiniumAtom"),
		FermiumAtom(DEFAULT_NAMESPACE + "FermiumAtom"),
		MendeleviumAtom(DEFAULT_NAMESPACE + "MendeleviumAtom"),
		NobeliumAtom(DEFAULT_NAMESPACE + "NobeliumAtom"),
		LawrenciumAtom(DEFAULT_NAMESPACE + "LawrenciumAtom"),
		FluorescenceTransfer(DEFAULT_NAMESPACE + "FluorescenceTransfer"),
		DeuteriumAtom(DEFAULT_NAMESPACE + "DeuteriumAtom"),
		BIsoMean(DEFAULT_NAMESPACE + "BIsoMean"),
		BIsoMax(DEFAULT_NAMESPACE + "BIsoMax"),
		BIsoMin(DEFAULT_NAMESPACE + "BIsoMin"),
		AnisoB11(DEFAULT_NAMESPACE + "Aniso_B11"),
		AnisoB12(DEFAULT_NAMESPACE + "Aniso_B12"),
		AnisoB13(DEFAULT_NAMESPACE + "Aniso_B13"),
		AnisoB22(DEFAULT_NAMESPACE + "Aniso_B22"),
		AnisoB23(DEFAULT_NAMESPACE + "Aniso_B23"),
		AnisoB33(DEFAULT_NAMESPACE + "Aniso_B33"),
		CorrelationCoefficientFoToFc(DEFAULT_NAMESPACE + "CorrelationCoefficientFoToFc"),
		CorrelationCoefficientFoToFcFree(DEFAULT_NAMESPACE + "CorrelationCoefficientFoToFcFree"),
		RefinementDetails(DEFAULT_NAMESPACE + "RefinementDetails"),
		RefinementMethod(DEFAULT_NAMESPACE + "RefinementMethod"),
		LsRFactorRFree(DEFAULT_NAMESPACE + "LsRFactorRFree"),
		LsRFactorRFreeError(DEFAULT_NAMESPACE + "LsRFactorRFreeError"),
		LsRFactorRFreeErrorDetails(DEFAULT_NAMESPACE + "LsRFactorRFreeErrorDetails"),
		LsRFactorRWork(DEFAULT_NAMESPACE + "LsRFactorRWork"),
		LsRFactorAll(DEFAULT_NAMESPACE + "LsRFactorAll"),
		LsRFactorObs(DEFAULT_NAMESPACE + "LsRFactorObs"),
		LsDResHigh(DEFAULT_NAMESPACE + "LsDResHigh"),
		LsDResLow(DEFAULT_NAMESPACE + "LsDResLow"),
		LsNumberParameters(DEFAULT_NAMESPACE + "LsNumberParameters"),
		LsNumberReflnsRFree(DEFAULT_NAMESPACE + "LsNumberReflnsRFree"),
		LsNumberReflnsAll(DEFAULT_NAMESPACE + "LsNumberReflnsAll"),
		LsNumberReflnsObs(DEFAULT_NAMESPACE + "LsNumberReflnsObs"),
		LsNumberRestraints(DEFAULT_NAMESPACE + "LsNumberRestraints"),
		LsPercentReflnsRFree(DEFAULT_NAMESPACE + "LsPercentReflnsRFree"),
		LsPercentReflnsObs(DEFAULT_NAMESPACE + "LsPercentReflnsObs"),
		LsRedundancyReflnsObs(DEFAULT_NAMESPACE + "LsRedundancyReflnsObs"),
		LsWRFactorRFree(DEFAULT_NAMESPACE + "LsWRFactorRFree"),
		LsWRFactorRWork(DEFAULT_NAMESPACE + "LsWRFactorRWork"),
		OccupancyMax(DEFAULT_NAMESPACE + "OccupancyMax"),
		OccupancyMin(DEFAULT_NAMESPACE + "OccupancyMin"),
		OverallFOMFreeRSet(DEFAULT_NAMESPACE + "OverallFOMFreeRSet"),
		OverallFOMWorkRSet(DEFAULT_NAMESPACE + "OverallFOMWorkRSet"),
		OverallSUB(DEFAULT_NAMESPACE + "OverallSUB"),
		OverallSUML(DEFAULT_NAMESPACE + "OverallSUML"),
		OverallSURCruishankDPI(DEFAULT_NAMESPACE + "OverallSURCruishankDPI"),
		OverallSURFree(DEFAULT_NAMESPACE + "OverallSURFree"),
		RFreeSelectionDetails(DEFAULT_NAMESPACE + "RFreeSelectionDetails"),
		DataCutoffHighAbsF(DEFAULT_NAMESPACE + "DataCutoffHighAbsF"),
		DataCutoffHighRmsAbsF(DEFAULT_NAMESPACE + "DataCutoffHighRmsAbsF"),
		DataCutoffLowAbsF(DEFAULT_NAMESPACE + "DataCutoffLowAbsF"),
		IsotropicThermalModel(DEFAULT_NAMESPACE + "IsotropicThermalModel"),
		LsCrossValidMethod(DEFAULT_NAMESPACE + "LsCrossValidMethod"),
		LsSigmaF(DEFAULT_NAMESPACE + "LsSigmaF"),
		LsSigmaI(DEFAULT_NAMESPACE + "LsSigmaI"),
		MethodToDetermineStruct(DEFAULT_NAMESPACE + "MethodToDetermineStruct"),
		OverallESUR(DEFAULT_NAMESPACE + "OverallESUR"),
		OverallESURFree(DEFAULT_NAMESPACE + "OverallESURFree"),
		OverallPhaseError(DEFAULT_NAMESPACE + "OverallPhaseError"),
		SolventIonProbeRadii(DEFAULT_NAMESPACE + "SolventIonProbeRadii"),
		SolventShrinkageRadii(DEFAULT_NAMESPACE + "SolventShrinkageRadii"),
		SolventVdwProbeRadii(DEFAULT_NAMESPACE + "SolventVdwProbeRadii"),
		StartingModel(DEFAULT_NAMESPACE + "StartingModel"),
		StereochemTargetValSpecCase(DEFAULT_NAMESPACE + "StereochemTargetValSpecCase"),
		StereochemistryTargetValues(DEFAULT_NAMESPACE + "StereochemistryTargetValues"),
		SolventModelDetails(DEFAULT_NAMESPACE + "SolventModelDetails"),
		SolventModelParamBsol(DEFAULT_NAMESPACE + "SolventModelParamBsol"),
		SolventModelParamKsol(DEFAULT_NAMESPACE + "SolventModelParamKsol"),
		ConformerSelectionCriteria(DEFAULT_NAMESPACE + "ConformerSelectionCriteria"),
		AverageConstraintViolationsPerResidue(DEFAULT_NAMESPACE + "AverageConstraintViolationsPerResidue"),
		Refinement(DEFAULT_NAMESPACE + "Refinement"),
		Alanine(DEFAULT_NAMESPACE + "Alanine"),
		Arginine(DEFAULT_NAMESPACE + "Arginine"),
		Asparagine(DEFAULT_NAMESPACE + "Asparagine"),
		AsparticAcid(DEFAULT_NAMESPACE + "AsparticAcid"),
		Cysteine(DEFAULT_NAMESPACE + "Cysteine"),
		GlutamicAcid(DEFAULT_NAMESPACE + "GlutamicAcid"),
		Glutamine(DEFAULT_NAMESPACE + "Glutamine"),
		Glycine(DEFAULT_NAMESPACE + "Glycine"),
		Histidine(DEFAULT_NAMESPACE + "Histidine"),
		Isoleucine(DEFAULT_NAMESPACE + "Isoleucine"),
		Leucine(DEFAULT_NAMESPACE + "Leucine"),
		Lysine(DEFAULT_NAMESPACE + "Lysine"),
		Methionine(DEFAULT_NAMESPACE + "Methionine"),
		Phenylalanine(DEFAULT_NAMESPACE + "Phenylalanine"),
		Proline(DEFAULT_NAMESPACE + "Proline"),
		Serine(DEFAULT_NAMESPACE + "Serine"),
		Threonine(DEFAULT_NAMESPACE + "Threonine"),
		Tryptophan(DEFAULT_NAMESPACE + "Tryptophan"),
		Tyrosine(DEFAULT_NAMESPACE + "Tyrosine"),
		Valine(DEFAULT_NAMESPACE + "Valine"),
		Selenocysteine(DEFAULT_NAMESPACE + "Selenocysteine"),
		Pyrrolysine(DEFAULT_NAMESPACE + "Pyrrolysine"),
		CytidineFivePrimeMonophosphate(DEFAULT_NAMESPACE + "Cytidine5PrimeMonophosphate"),
		GuanosineFivePrimeMonophosphate(DEFAULT_NAMESPACE + "Guanosine5PrimeMonophosphate"),
		UridineFivePrimeMonophosphate(DEFAULT_NAMESPACE + "Uridine5PrimeMonophosphate"),
		AdenosineFivePrimeMonophosphate(DEFAULT_NAMESPACE + "Adenosine5PrimeMonophosphate"),
		TwoPrimeDeoxyAdenosineFivePrimeMonophosphate(DEFAULT_NAMESPACE + "TwoPrimeDeoxyAdenosineMonophosphate"),
		TwoPrimeDeoxyCytidineFivePrimeMonophosphate(DEFAULT_NAMESPACE + "TwoPrimeDeoxyCitidineMonophosphate"),
		TwoPrimeDeoxyGuanosineFivePrimeMonophosphate(DEFAULT_NAMESPACE + "TwoPrimeDeoxyGuanosineFivePrimeMonophosphate"),
		ThymidineFivePrimeMonophosphate(DEFAULT_NAMESPACE + "Thymidine5PrimeMonophosphate"),
		PhosphoThreonine(DEFAULT_NAMESPACE + "PhosphoThreonine"),
		SOxyCysteine(DEFAULT_NAMESPACE + "SOxyCysteine"),
		Adenine(DEFAULT_NAMESPACE + "Adenine"),
		NOneNAcetamidylOneCyclohexymethylTwoHydroxyFourIsoPropylGlutaminylArginylAmide(DEFAULT_NAMESPACE
				+ "NOneNAcetamidylOneCyclohexymethylTwoHydroxyFourIsoPropylGlutaminylArginylAmide"),
		FivePrimeBromo2PrimeDeoxyCytidineFivePrimeMonophosphate(DEFAULT_NAMESPACE
				+ "FivePrimeBromo2PrimeDeoxyCytidineFivePrimeMonophosphate"),
		OneROneFourAnhydroTwoDeoxyOneThreeFluoroPhenylFiveOPhosphonoDErythroPentinol(DEFAULT_NAMESPACE
				+ "OneROneFourAnhydroTwoDeoxyOneThreeFluoroPhenylFiveOPhosphonoDErythroPentinol"),
		PotassiumIon(DEFAULT_NAMESPACE + "PotassiumIon"),
		ChlorideIon(DEFAULT_NAMESPACE + "ChlorideIon"),
		UnknownResidue(DEFAULT_NAMESPACE + "UnknownResidue"),
		TwoPrimeDeoxyinosine5PrimeMonophosphate(DEFAULT_NAMESPACE + "2PrimeDeoxyinosine5PrimeMonophosphate"),
		NapdNicotinamideAdenineDinucleotidePhosphate(DEFAULT_NAMESPACE + "NapdNicotinamideAdenineDinucleotidePhosphate"),
		FiveMethylTwoPrimeDeoxyCytidineFivePrimeMonophosphate(DEFAULT_NAMESPACE
				+ "FiveMethylTwoPrimeDeoxyCytidineFivePrimeMonophosphate"),
		Glycerol(DEFAULT_NAMESPACE + "Glycerol"),
		DGammaGlutamylLCysteinylGlycine(DEFAULT_NAMESPACE + "DGammaGlutamylLCysteinylGlycine"),
		GalactoseUridineFivePrimeDiphosphate(DEFAULT_NAMESPACE + "GalactoseUridineFivePrimeDiphosphate"),
		PhosphoSerine(DEFAULT_NAMESPACE + "PhosphoSerine"),
		MagnesiumIon(DEFAULT_NAMESPACE + "MagnesiumIon"),
		SelenoMethionine(DEFAULT_NAMESPACE + "SelenoMethionine"),
		NickelIIIon(DEFAULT_NAMESPACE + "NickelIIIon"),
		OleicAcid(DEFAULT_NAMESPACE + "OleicAcid"),
		CalciumIon(DEFAULT_NAMESPACE + "CalciumIon"),
		SulfateIon(DEFAULT_NAMESPACE + "SulfateIon"),
		IronSulfurCluster(DEFAULT_NAMESPACE + "IronSulfurCluster"),
		ManganeseIonII(DEFAULT_NAMESPACE + "ManganeseIonII"),
		AdenosineFivePrimeTriPhosphate(DEFAULT_NAMESPACE + "AdenosineFivePrimeTriPhosphate"),
		NicotinamideAdenineDinucleotide(DEFAULT_NAMESPACE + "NicotinamideAdenineDinucleotide"),
		TwoTwoSTwoMethylPyrrolidinTwoYLOneHBenzimiazoleSevenCarboxamide(DEFAULT_NAMESPACE
				+ "TwoTwoSTwoMethylPyrrolidinTwoYLOneHBenzimiazoleSevenCarboxamide"),
		FeIIIIon(DEFAULT_NAMESPACE + "FeIIIIon"),
		HypoPhosphite(DEFAULT_NAMESPACE + "HypoPhosphite"),
		TwoAminoTwoHydroxyMethylPropaneOneThreeDiol(DEFAULT_NAMESPACE + "TwoAminoTwoHydroxyMethylPropaneOneThreeDiol"),
		PyroglutamicAcid(DEFAULT_NAMESPACE + "PyroglutamicAcid"),
		TwoHydroxymethylSixOctylsulfanylTetrahydroPyranThreeFourFiveTriol(DEFAULT_NAMESPACE
				+ "TwoHydroxymethylSixOctylsulfanylTetrahydroPyranThreeFourFiveTriol"),
		ArachidonicAcid(DEFAULT_NAMESPACE + "ArachidonicAcid"),
		ProtoporphyrinIxContainingFe(DEFAULT_NAMESPACE + "ProtoporphyrinIxContainingFe"),
		NAcetylDGlucosamine(DEFAULT_NAMESPACE + "NAcetylDGlucosamine"),
		AminoGroup(DEFAULT_NAMESPACE + "AminoGroup"),
		FlavinMononucleotide(DEFAULT_NAMESPACE + "FlavinMononucleotide"),
		ZincIon(DEFAULT_NAMESPACE + "ZincIon"),
		GuanosineFivePrimeDiphosphate(DEFAULT_NAMESPACE + "GuanosineFivePrimeDiphosphate"),
		FourRFourAlphaThiazolyBenzamide(DEFAULT_NAMESPACE + "FourRFourAlphaThiazolyBenzamide"),
		ThreeCycloPentylNHydroxyPropanamide(DEFAULT_NAMESPACE + "ThreeCycloPentylNHydroxyPropanamide"),
		SodiumIon(DEFAULT_NAMESPACE + "SodiumIon"),
		UniprotCrossReference(DEFAULT_NAMESPACE + "UnitprotCrossReference"),
		GoCrossReference(DEFAULT_NAMESPACE + "GoCrossReference"), ;
		// HASH(DEFAULT_NAMESPACE + "IUPAC NAME")
		private final String uri;

		private Class(String uri) {
			this.uri = uri;
		}

		@Override
		public String toString() {
			return uri;
		}

		public String uri() {
			return uri;
		}

		public OntClass resource() {
			synchronized (model) {
				return model.createClass(uri);
			}
		}
	};

	public static enum ObjectProperty {
		isParticipantIn(DEFAULT_NAMESPACE + "isParticipantIn"),
		hasProduct(DEFAULT_NAMESPACE + "hasProduct"),
		hasPublication(DEFAULT_NAMESPACE + "hasPublication"),
		hasDocumentSection(DEFAULT_NAMESPACE + "hasDocumentSection"),
		hasPublisher(DEFAULT_NAMESPACE + "hasPublisher"),
		hasTitle(DEFAULT_NAMESPACE + "hasTitle"),
		hasCountryOfPublication(DEFAULT_NAMESPACE + "hasCountryOfPublication"),
		hasMedlineId(DEFAULT_NAMESPACE + "hasMedlineId"),
		hasJournalAbbreviation(DEFAULT_NAMESPACE + "hasJournalAbbreviation"),
		hasName(DEFAULT_NAMESPACE + "hasName"),
		isPublishedIn(DEFAULT_NAMESPACE + "isPublishedIn"),
		hasVolumeNumber(DEFAULT_NAMESPACE + "hasVolumeNumber"),
		hasISBN(DEFAULT_NAMESPACE + "hasISBN"),
		hasISSN(DEFAULT_NAMESPACE + "hasISSN"),
		hasLanguage(DEFAULT_NAMESPACE + "hasLanguage"),
		hasFirstPageNumber(DEFAULT_NAMESPACE + "hasFirstPageNumber"),
		hasLastPageNumber(DEFAULT_NAMESPACE + "hasLastPageNumber"),
		hasDOI(DEFAULT_NAMESPACE + "hasDOI"),
		hasPubmedId(DEFAULT_NAMESPACE + "hasPubmedId"),
		hasPublicationYear(DEFAULT_NAMESPACE + "hasPublicationYear"),
		hasAuthor(DEFAULT_NAMESPACE + "hasAuthor"),
		hasNumberOfMonomers(DEFAULT_NAMESPACE + "hasNumberOfMonomers"),
		hasTheoreticalFormulaWeight(DEFAULT_NAMESPACE + "hasTheoreticalFormulaWeight"),
		hasExperimentalFormulaWeight(DEFAULT_NAMESPACE + "hasExperimentalFormulaWeight"),
		hasChemicalSubstanceAmount(DEFAULT_NAMESPACE + "hasChemicalSubstanceAmount"),
		hasPolymerSequence(DEFAULT_NAMESPACE + "hasPolymerSequence"),
		hasSource(DEFAULT_NAMESPACE + "hasSource"),
		isProducedBy(DEFAULT_NAMESPACE + "isProducedBy"),
		isDerivedFrom(DEFAULT_NAMESPACE + "isDerivedFrom"),
		hasSpatialLocation(DEFAULT_NAMESPACE + "hasSpatialLocation"),
		hasIsotropicAtomicDisplacement(DEFAULT_NAMESPACE + "hasIsotropicAtomicDisplacement"),
		hasXCoordinate(DEFAULT_NAMESPACE + "hasXCoordinate"),
		hasYCoordinate(DEFAULT_NAMESPACE + "hasYCoordinate"),
		hasZCoordinate(DEFAULT_NAMESPACE + "hasZCoordinate"),
		hasOccupancy(DEFAULT_NAMESPACE + "hasOccupancy"),
		hasFormalCharge(DEFAULT_NAMESPACE + "hasFormalCharge"),
		isImmediatelyBefore(DEFAULT_NAMESPACE + "isImmediatelyBefore"),
		hasChainPosition(DEFAULT_NAMESPACE + "hasChainPosition"),
		hasDevelopmentStage(DEFAULT_NAMESPACE + "hasDevelopmentStage"),
		hasNumberOfAtoms(DEFAULT_NAMESPACE + "hasNumberOfAtoms"),
		hasNumberOfNonHydrogenAtoms(DEFAULT_NAMESPACE + "hasNumberOfNonHydrogenAtoms"),
		hasChemicalFormula(DEFAULT_NAMESPACE + "hasChemicalFormula"),
		beginsAt(DEFAULT_NAMESPACE + "beginsAt"),
		endsAt(DEFAULT_NAMESPACE + "endsAt"),
		hasHelixLength(DEFAULT_NAMESPACE + "hasHelixLegth"),
		hasUnitCell(DEFAULT_NAMESPACE + "hasUnitCell"),
		hasAngleAlpha(DEFAULT_NAMESPACE + "hasAngleAlpha"),
		hasParticipant(DEFAULT_NAMESPACE + "hasParticipant"),
		hasAngleBeta(DEFAULT_NAMESPACE + "hasAngleBeta"),
		hasAngleGamma(DEFAULT_NAMESPACE + "hasAngleGamma"),
		hasEdgeALength(DEFAULT_NAMESPACE + "hasEdgeALength"),
		hasEdgeBLength(DEFAULT_NAMESPACE + "hasEdgeBLength"),
		hasEdgeCLength(DEFAULT_NAMESPACE + "hasEdgeCLength"),
		hasReciprocalAngleAlpha(DEFAULT_NAMESPACE + "hasReciprocalAngleAlpha"),
		hasReciprocalAngleGamma(DEFAULT_NAMESPACE + "hasReciprocalAngleGamma"),
		hasReciprocalAngleBeta(DEFAULT_NAMESPACE + "hasReciprocalAngleBeta"),
		hasReciprocalEdgeALength(DEFAULT_NAMESPACE + "hasReciprocalEdgeALength"),
		hasReciprocalEdgeBLength(DEFAULT_NAMESPACE + "hasReciprocalEdgeBLength"),
		hasReciprocalEdgeCLength(DEFAULT_NAMESPACE + "hasReciprocalEdgeCLength"),
		hasVolume(DEFAULT_NAMESPACE + "hasVolume"),
		hasCoefficientMu(DEFAULT_NAMESPACE + "hasCoefficientMu"),
		hasMaximumTransmissionFactor(DEFAULT_NAMESPACE + "hasMaximumTransmissionFactor"),
		hasMinimumTransmissionFactor(DEFAULT_NAMESPACE + "hasMinimumTransmissionFactor"),
		hasBIsoMean(DEFAULT_NAMESPACE + "hasBIsoMean"),
		hasBIsoMax(DEFAULT_NAMESPACE + "hasBIsoMax"),
		hasBIsoMin(DEFAULT_NAMESPACE + "hasBIsoMin"),
		hasAnisoB11(DEFAULT_NAMESPACE + "hasAnisoB11"),
		hasAnisoB12(DEFAULT_NAMESPACE + "hasAnisoB12"),
		hasAnisoB13(DEFAULT_NAMESPACE + "hasAnisoB13"),
		hasAnisoB22(DEFAULT_NAMESPACE + "hasAnisoB22"),
		hasAnisoB23(DEFAULT_NAMESPACE + "hasAnisoB23"),
		hasAnisoB33(DEFAULT_NAMESPACE + "hasAnisoB33"),
		hasCorrelationCoefficientFoToFc(DEFAULT_NAMESPACE + "hasCorrelationCoefficientFoToFc"),
		hasCorrelationCoefficientFoToFcFree(DEFAULT_NAMESPACE + "hasCorrelationCoefficientFoToFcFree"),
		hasDetails(DEFAULT_NAMESPACE + "hasDetails"),
		hasLsRFactorRFree(DEFAULT_NAMESPACE + "hasLsRFactorRFree"),
		hasLsRFactorRFreeError(DEFAULT_NAMESPACE + "hasLsRFactorRFreeError"),
		hasLsRFactorRFreeErrorDetails(DEFAULT_NAMESPACE + "hasLsRFactorRFreeError"),
		hasLsRFactorRWork(DEFAULT_NAMESPACE + "hasLsRFactorRWork"),
		hasLsRFactorObs(DEFAULT_NAMESPACE + "hasLsRFactorObs"),
		hasLsDResHigh(DEFAULT_NAMESPACE + "hasLsDResHigh"),
		hasLsDResLow(DEFAULT_NAMESPACE + "hasLsDResLow"),
		hasLsNumberParameters(DEFAULT_NAMESPACE + "hasLsNumberParameters"),
		hasLsNumberReflnsRFree(DEFAULT_NAMESPACE + "hasLsNumberReflnsRFree"),
		hasLsNumberReflnsAll(DEFAULT_NAMESPACE + "hasLsNumberReflnsAll"),
		hasLsNumberReflnsObs(DEFAULT_NAMESPACE + "hasLsNumberReflnsObs"),
		hasLsNumberRestraints(DEFAULT_NAMESPACE + "hasLsNumberRestraints"),
		hasLsPercentReflnsRFree(DEFAULT_NAMESPACE + "hasLsPercentReflnsRFree"),
		hasLsPercentReflnsObs(DEFAULT_NAMESPACE + "hasLsPercentReflnsObs"),
		hasLsRedundancyReflnsObs(DEFAULT_NAMESPACE + "hasLsRedundancyReflnsObs"),
		hasLsWRFactorRFree(DEFAULT_NAMESPACE + "hasLsWRFactorRFree"),
		hasLsWRFactorRWork(DEFAULT_NAMESPACE + "hasLsWRFactorRWork"),
		hasLsRFactorAll(DEFAULT_NAMESPACE + "hasLsRFactorAll"),
		hasOccupancyMax(DEFAULT_NAMESPACE + "hasOccupancyMax"),
		hasOccupancyMin(DEFAULT_NAMESPACE + "hasOccupancyMin"),
		hasOverallFOMFreeRSet(DEFAULT_NAMESPACE + "hasOverallFOMFreeRSet"),
		hasOverallFOMWorkRSet(DEFAULT_NAMESPACE + "hasOverallFOMWorkRSet"),
		hasOverallSUB(DEFAULT_NAMESPACE + "hasOverallSUB"),
		hasOverallSUML(DEFAULT_NAMESPACE + "hasOverallSUML"),
		hasOverallSURCruickshankDPI(DEFAULT_NAMESPACE + "hasOverallSURCruickshankDPI"),
		hasOverallSURFree(DEFAULT_NAMESPACE + "hasOverallSURFree"),
		hasRFreeSelectionDetails(DEFAULT_NAMESPACE + "hasRFreeSelectionDetails"),
		hasDataCutoffHighAbsF(DEFAULT_NAMESPACE + "hasDataCutoffHighAbsF"),
		hasDataCutoffHighRmsAbsF(DEFAULT_NAMESPACE + "hasDataCutoffHighRmsAbsF"),
		hasDataCutoffLowAbsF(DEFAULT_NAMESPACE + "hasDataCutoffLowAbsF"),
		hasIsotropicThermalModel(DEFAULT_NAMESPACE + "hasIsotropicThermalModel"),
		hasLsCrossValidMethod(DEFAULT_NAMESPACE + "hasLsCrossValidMethod"),
		hasLsSigmaF(DEFAULT_NAMESPACE + "hasLsSigmaF"),
		hasLsSigmaI(DEFAULT_NAMESPACE + "hasLsSigmaI"),
		hasMethodToDetermineStruct(DEFAULT_NAMESPACE + "hasMethodToDetermineStruct"),
		hasOverallESUR(DEFAULT_NAMESPACE + "hasOverallESUR"),
		hasOverallESURFree(DEFAULT_NAMESPACE + "hasOverallESURFree"),
		hasOverallPhaseError(DEFAULT_NAMESPACE + "hasOverallPhaseError"),
		hasSolventIonProbeRadii(DEFAULT_NAMESPACE + "hasSolventIonProbeRadii"),
		hasSolventShrinkageRadii(DEFAULT_NAMESPACE + "hasSolventShrinkageRadii"),
		hasSolventVdwProbeRadii(DEFAULT_NAMESPACE + "hasSolventVdwProbeRadii"),
		hasStartingModel(DEFAULT_NAMESPACE + "hasStartingModel"),
		hasStereochemTargetValSpecCase(DEFAULT_NAMESPACE + "hasStereochemTargetValSpecCase"),
		hasStereochemistryTargetValues(DEFAULT_NAMESPACE + "hasStereochemistryTargetValues"),
		hasSolventModelDetails(DEFAULT_NAMESPACE + "hasSolventModelDetails"),
		hasSolventModelParamBsol(DEFAULT_NAMESPACE + "hasSolventModelParamBsol"),
		hasSolventModelParamKsol(DEFAULT_NAMESPACE + "hasSolventModelParamKsol"),
		hasConformerSelectionCriteria(DEFAULT_NAMESPACE + "hasConformerSelectionCriteria"),
		hasAverageConstraintViolationsPerResidue(DEFAULT_NAMESPACE + "hasAverageConstraintViolationsPerResidue"),
		hasCoordinate(DEFAULT_NAMESPACE + "hasCoordinate"),
		hasPart(DEFAULT_NAMESPACE + "hasPart"),
		isPartOf(DEFAULT_NAMESPACE + "isPartOf"),
		hasCrossReference(DEFAULT_NAMESPACE + "hasCrossReference");

		private final String uri;

		private ObjectProperty(String uri) {
			this.uri = uri;
		}

		@Override
		public String toString() {
			return uri;
		}

		public String uri() {
			return uri;
		}

		public com.hp.hpl.jena.ontology.ObjectProperty property() {
			synchronized (model) {
				return model.createObjectProperty(uri);
			}

		}
	};

	public static enum DataProperty {

		hasValue(DEFAULT_NAMESPACE + "hasValue"), hasStandardDeviation(DEFAULT_NAMESPACE + "hasStandardDeviation");

		private final String uri;

		private DataProperty(String uri) {
			this.uri = uri;
		}

		@Override
		public String toString() {
			return uri;
		}

		public String uri() {
			return uri;
		}

		public DatatypeProperty property() {
			synchronized (model) {
				return model.createDatatypeProperty(uri);
			}
		}
	};

	public static enum Indivudual {
	};

	public static enum Annotation {
		details(DEFAULT_NAMESPACE + "details"), description(DEFAULT_NAMESPACE + "description"), experimentalMethod(
				DEFAULT_NAMESPACE + "experimentalMethod"), modification(DEFAULT_NAMESPACE + "modification"), mutation(
				DEFAULT_NAMESPACE + "mutation");

		private final String uri;

		private Annotation(String uri) {
			this.uri = uri;
		}

		@Override
		public String toString() {
			return uri;
		}

		public String uri() {
			return uri;
		}

		public AnnotationProperty property() {
			synchronized (model) {
				return model.createAnnotationProperty(uri);
			}
		}
	}

	public static OntModel getOntology() {
		for (Class c : Class.values()) {
			c.resource();
		}
		for (ObjectProperty p : ObjectProperty.values()) {
			p.property();
		}
		for (DataProperty p : DataProperty.values()) {
			p.property();
		}
		for (Annotation p : Annotation.values()) {
			p.property();
		}
		return model;
	}

	public static void main(String[] args) {
		System.out.println("Classes: " + Class.values().length);
		System.out.println("Object Properties: " + ObjectProperty.values().length);
		System.out.println("Data Properties: " + DataProperty.values().length);
		System.out.println("Annotations: " + Annotation.values().length);
		getOntology().write(System.out);
	}

}
