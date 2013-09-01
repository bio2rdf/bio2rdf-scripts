/**
 * Copyright (c) 2013 Dumontierlab
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
 * @author Jose Cruz-Toledo
 * @author Alexander De Leon
 */
public class PdbOwlVocabulary {

	public static final String VOCABULARY_NAMESPACE = "http://bio2rdf.org/pdb_vocabulary:";
	public static final String RESOURCE_NAMESPACE = "http://bio2rdf.org/pdb_resource:";
	

	private static final OntModel model = ModelFactory.createOntologyModel(OntModelSpec.OWL_MEM);
	static {
		model.setNsPrefix("pdb", PdbOwlVocabulary.VOCABULARY_NAMESPACE);
		model.setNsPrefix("dcterms", "http://purl.org/dc/terms/");
		model.setNsPrefix("foaf", "http://xmlns.com/foaf/0.1/");
		
	}

	public static enum Class {
		Distribution("http://bio2rdf.org/dcat:Distribution"),
		Resource(VOCABULARY_NAMESPACE+"Resource"),
		PdbRecord(VOCABULARY_NAMESPACE + "PdbRecord"),
		Experiment(VOCABULARY_NAMESPACE + "Experiment"),
		ChemicalSubstance(VOCABULARY_NAMESPACE + "ChemicalSubstance"),
		Polymer(VOCABULARY_NAMESPACE + "Polymer"),
		NonPolymer(VOCABULARY_NAMESPACE + "NonPolymer"),
		Water(VOCABULARY_NAMESPACE + "Water"),
		Macrolide(VOCABULARY_NAMESPACE + "Macrolide"),
		StructureDetermination(VOCABULARY_NAMESPACE + "StructureDetermination"),
		ChemicalSubstanceExtraction(VOCABULARY_NAMESPACE + "ChemicalSubstanceExtraction"),
		TheoreticalFormulaWeight(VOCABULARY_NAMESPACE + "TheoreticalFormulaWeight"),
		ExperimentalFormulaWeight(VOCABULARY_NAMESPACE + "ExperimentalFormulaWeight"),
		ChemicalSubstanceAmount(VOCABULARY_NAMESPACE + "ChemicalSubstanceAmount"),
		NaturalChemicalSubstanceExtraction(VOCABULARY_NAMESPACE + "NaturalChemicalSubstanceExtraction"),
		SynthecticChemicalSubstanceExtraction(VOCABULARY_NAMESPACE + "SynthecticChemicalSubstanceExtraction"),
		GeneticallyManipulatedChemicalSubstanceExtraction(VOCABULARY_NAMESPACE
				+ "GeneticallyManipulatedChemicalSubstanceExtraction"),
		Abstrat(VOCABULARY_NAMESPACE + "Abstract"),
		Publication(VOCABULARY_NAMESPACE + "Publication"),
		Book(VOCABULARY_NAMESPACE + "Book"),
		Journal(VOCABULARY_NAMESPACE + "Journal"),
		Publisher(VOCABULARY_NAMESPACE + "Publisher"),
		Title(VOCABULARY_NAMESPACE + "Title"),
		Country(VOCABULARY_NAMESPACE + "Country"),
		MedlineId(VOCABULARY_NAMESPACE + "MedlineId"),
		JournalAbbreviation(VOCABULARY_NAMESPACE + "JournalAbbreviation"),
		Name(VOCABULARY_NAMESPACE + "Name"),
		VolumeNumber(VOCABULARY_NAMESPACE + "VolumeNumber"),
		ISBN(VOCABULARY_NAMESPACE + "ISBN"),
		DocumentVolume(VOCABULARY_NAMESPACE + "DocumentVolume"),
		ISSN(VOCABULARY_NAMESPACE + "ISSN"),
		Language(VOCABULARY_NAMESPACE + "Language"),
		PageNumber(VOCABULARY_NAMESPACE + "PageNumber"),
		DOI(VOCABULARY_NAMESPACE + "DOI"),
		PubmedId(VOCABULARY_NAMESPACE + "PubmedId"),
		PublicationYear(VOCABULARY_NAMESPACE + "PublicationYear"),
		NumberOfMonomers(VOCABULARY_NAMESPACE + "NumberOfMonomers"),
		PolymerSequence(VOCABULARY_NAMESPACE + "PolymerSequence"),
		CanonicalPolymerSequence(VOCABULARY_NAMESPACE + "CanonicalPolymerSequence"),
		PolypeptideD(VOCABULARY_NAMESPACE + "Polypeptide(D)"),
		PolypeptideL(VOCABULARY_NAMESPACE + "Polypeptide(L)"),
		Polydeoxyribonucleotide(VOCABULARY_NAMESPACE + "Polydeoxyribonucleotide"),
		Polyribonucleotide(VOCABULARY_NAMESPACE + "Polyribonucleotide"),
		PolysaccharideD(VOCABULARY_NAMESPACE + "Polysaccharide(D)"),
		PolysaccharideL(VOCABULARY_NAMESPACE + "polysaccharide(L)"),
		PolydeoxyribonucleotidePolyribonucleotide(VOCABULARY_NAMESPACE + "Polydeoxyribonucleotide-Polyribonucleotide"),
		CyclicPseudoPeptide(VOCABULARY_NAMESPACE + "CyclicPseudoPeptide"),
		Cell(VOCABULARY_NAMESPACE + "Cell"),
		Organ(VOCABULARY_NAMESPACE + "Organ"),
		Organelle(VOCABULARY_NAMESPACE + "Organelle"),
		Secretion(VOCABULARY_NAMESPACE + "Secretion"),
		Tissue(VOCABULARY_NAMESPACE + "Tissue"),
		TissueFraction(VOCABULARY_NAMESPACE + "TissueFraction"),
		CellularLocation(VOCABULARY_NAMESPACE + "CellularLocation"),
		IsotropicAtomicDisplacement(VOCABULARY_NAMESPACE + "IsotropicAtomicDisplacement"),
		CartesianCoordinate(VOCABULARY_NAMESPACE + "CartesianCoordinate"),
		XCartesianCoordinate(VOCABULARY_NAMESPACE + "XCartesianCoordinate"),
		YCartesianCoordinate(VOCABULARY_NAMESPACE + "YCartesianCoordinate"),
		ZCartesianCoordinate(VOCABULARY_NAMESPACE + "ZCartesianCoordinate"),
		PartialCharge(VOCABULARY_NAMESPACE + "PartialCharge"),
		Cosmid(VOCABULARY_NAMESPACE + "Cosmid"),
		Plasmid(VOCABULARY_NAMESPACE + "Plasmid"),
		Virus(VOCABULARY_NAMESPACE + "Virus"),
		AtomOccupancy(VOCABULARY_NAMESPACE + "AtomOccupancy"),
		HydrogenAtom(VOCABULARY_NAMESPACE + "HydrogenAtom"),
		HeliumAtom(VOCABULARY_NAMESPACE + "HeliumAtom"),
		LithiumAtom(VOCABULARY_NAMESPACE + "LithiumAtom"),
		BerylliumAtom(VOCABULARY_NAMESPACE + "BerylliumAtom"),
		BoronAtom(VOCABULARY_NAMESPACE + "BoronAtom"),
		CarbonAtom(VOCABULARY_NAMESPACE + "CarbonAtom"),
		NitrogenAtom(VOCABULARY_NAMESPACE + "NitrogenAtom"),
		OxygenAtom(VOCABULARY_NAMESPACE + "OxygenAtom"),
		FluorineAtom(VOCABULARY_NAMESPACE + "FluorineAtom"),
		NeonAtom(VOCABULARY_NAMESPACE + "NeonAtom"),
		SodiumAtom(VOCABULARY_NAMESPACE + "SodiumAtom"),
		MagnesiumAtom(VOCABULARY_NAMESPACE + "MagnesiumAtom"),
		AluminumAtom(VOCABULARY_NAMESPACE + "AluminumAtom"),
		SiliconAtom(VOCABULARY_NAMESPACE + "SiliconAtom"),
		PhosphorusAtom(VOCABULARY_NAMESPACE + "PhosphorusAtom"),
		SulfurAtom(VOCABULARY_NAMESPACE + "SulfurAtom"),
		ChlorineAtom(VOCABULARY_NAMESPACE + "ChlorineAtom"),
		ArgonAtom(VOCABULARY_NAMESPACE + "ArgonAtom"),
		PotassiumAtom(VOCABULARY_NAMESPACE + "PotassiumAtom"),
		CalciumAtom(VOCABULARY_NAMESPACE + "CalciumAtom"),
		ScandiumAtom(VOCABULARY_NAMESPACE + "ScandiumAtom"),
		TitaniumAtom(VOCABULARY_NAMESPACE + "TitaniumAtom"),
		VanadiumAtom(VOCABULARY_NAMESPACE + "VanadiumAtom"),
		ChromiumAtom(VOCABULARY_NAMESPACE + "ChromiumAtom"),
		ManganeseAtom(VOCABULARY_NAMESPACE + "ManganeseAtom"),
		IronAtom(VOCABULARY_NAMESPACE + "IronAtom"),
		CobaltAtom(VOCABULARY_NAMESPACE + "CobaltAtom"),
		NickelAtom(VOCABULARY_NAMESPACE + "NickelAtom"),
		CopperAtom(VOCABULARY_NAMESPACE + "CopperAtom"),
		ZincAtom(VOCABULARY_NAMESPACE + "ZincAtom"),
		GalliumAtom(VOCABULARY_NAMESPACE + "GalliumAtom"),
		GermaniumAtom(VOCABULARY_NAMESPACE + "GermaniumAtom"),
		ArsenicAtom(VOCABULARY_NAMESPACE + "ArsenicAtom"),
		SeleniumAtom(VOCABULARY_NAMESPACE + "SeleniumAtom"),
		BromineAtom(VOCABULARY_NAMESPACE + "BromineAtom"),
		KryptonAtom(VOCABULARY_NAMESPACE + "KryptonAtom"),
		RubidiumAtom(VOCABULARY_NAMESPACE + "RubidiumAtom"),
		StrontiumAtom(VOCABULARY_NAMESPACE + "StrontiumAtom"),
		YttriumAtom(VOCABULARY_NAMESPACE + "YttriumAtom"),
		MolybdenumAtom(VOCABULARY_NAMESPACE + "MolybdenumAtom"),
		RhodiumAtom(VOCABULARY_NAMESPACE + "RhodiumAtom"),
		PalladiumAtom(VOCABULARY_NAMESPACE + "PalladiumAtom"),
		SilverAtom(VOCABULARY_NAMESPACE + "SilverAtom"),
		CadmiumAtom(VOCABULARY_NAMESPACE + "CadmiumAtom"),
		IodineAtom(VOCABULARY_NAMESPACE + "IodineAtom"),
		MercuryAtom(VOCABULARY_NAMESPACE + "MercuryAtom"),
		LeadAtom(VOCABULARY_NAMESPACE + "LeadAtom"),
		XenonAtom(VOCABULARY_NAMESPACE + "XenonAtom"),
		AtomSpatialLocation(VOCABULARY_NAMESPACE + "AtomSpatialLocation"),
		Model(VOCABULARY_NAMESPACE + "Model"),
		Chain(VOCABULARY_NAMESPACE + "Chain"),
		ChainPosition(VOCABULARY_NAMESPACE + "ChainPosition"),
		DevelopmentStage(VOCABULARY_NAMESPACE + "DevelopmentStage"),
		NumberOfAtoms(VOCABULARY_NAMESPACE + "NumberOfAtoms"),
		NumberOfNonHydrogenAtoms(VOCABULARY_NAMESPACE + "NumberOfNonHydrogenAtoms"),
		ChemicalFormula(VOCABULARY_NAMESPACE + "ChemicalFormula"),
		Residue(VOCABULARY_NAMESPACE + "Residue"),
		Helix(VOCABULARY_NAMESPACE + "Helix"),
		RightHandedHelix(VOCABULARY_NAMESPACE + "RightHandedHelix"),
		RightHandedAlphaHelix(VOCABULARY_NAMESPACE + "RightHandedAlphaHelix"),
		RightHandedGammaHelix(VOCABULARY_NAMESPACE + "RightHandedGammaHelix"),
		RightHandedOmegaHelix(VOCABULARY_NAMESPACE + "RightHandedOmegaHelix"),
		RightHandedPiHelix(VOCABULARY_NAMESPACE + "RightHandedPiHelix"),
		RightHanded22_7Helix(VOCABULARY_NAMESPACE + "RightHanded2.2-7Helix"),
		RightHanded3_10Helix(VOCABULARY_NAMESPACE + "RightHanded3-10Helix"),
		RightHandedPolyprolineHelix(VOCABULARY_NAMESPACE + "RightHandPolyprolineHelix"),
		LeftHandedHelix(VOCABULARY_NAMESPACE + "LeftHandedHelix"),
		LeftHandedAlphaHelix(VOCABULARY_NAMESPACE + "LeftHandedAlphaHelix"),
		LeftHandedGammaHelix(VOCABULARY_NAMESPACE + "LeftHandedGammaHelix"),
		LeftHandedOmegaHelix(VOCABULARY_NAMESPACE + "LeftHandedOmegaHelix"),
		LeftHandedPiHelix(VOCABULARY_NAMESPACE + "LeftHandedPiHelix"),
		LeftHanded22_7Helix(VOCABULARY_NAMESPACE + "LeftHanded2.2-7Helix"),
		LeftHanded3_10Helix(VOCABULARY_NAMESPACE + "LeftHanded3-10Helix"),
		LeftHandedPolyprolineHelix(VOCABULARY_NAMESPACE + "LeftHandedPolyprolineHelix"),
		NonStandardHelix(VOCABULARY_NAMESPACE + "NonStandardHelix"),
		NonStandardRightHandedHelix(VOCABULARY_NAMESPACE + "NonStandardRightHandedHelix"),
		DoubleHelix(VOCABULARY_NAMESPACE + "DoubleHelix"),
		NonStandardLeftHandedHelix(VOCABULARY_NAMESPACE + "NonStandardLeftHandedHelix"),
		NonStandardDoubleHelix(VOCABULARY_NAMESPACE + "NonStandardDoubleHelix"),
		RightHandedDoubleHelix(VOCABULARY_NAMESPACE + "RightHandedDoubleHelix"),
		NonStandardRightHandedDoubleHelix(VOCABULARY_NAMESPACE + "NonStandardRightHandedDoubleHelix"),
		RightHandedADoubleHelix(VOCABULARY_NAMESPACE + "RightHandedADoubleHelix"),
		RightHandedBDoubleHelix(VOCABULARY_NAMESPACE + "RightHandedBDoubleHelix"),
		RightHandedZDoubleHelix(VOCABULARY_NAMESPACE + "RightHandedZDoubleHelix"),
		LeftHandedDoubleHelix(VOCABULARY_NAMESPACE + "LeftHandedDoubleHelix"),
		NonStandardLeftHandedDoubleHelix(VOCABULARY_NAMESPACE + "NonStandardLeftHandedDoubleHelix"),
		LeftHandedADoubleHelix(VOCABULARY_NAMESPACE + "LeftHandedADoubleHelix"),
		LeftHandedBDoubleHelix(VOCABULARY_NAMESPACE + "LeftHandedBDoubleHelix"),
		LeftHandedZDoubleHelix(VOCABULARY_NAMESPACE + "LeftHandedZDoubleHelix"),
		Turn(VOCABULARY_NAMESPACE + "Turn"),
		NonStandardTurn(VOCABULARY_NAMESPACE + "NonStandardTurn"),
		TypeITurn(VOCABULARY_NAMESPACE + "TypeITurn"),
		TypeIPrimeTurn(VOCABULARY_NAMESPACE + "TypeIPrimeTurn"),
		TypeIITurn(VOCABULARY_NAMESPACE + "TypeIITurn"),
		TypeIIPrimeTurn(VOCABULARY_NAMESPACE + "TypeIIPrimeTurn"),
		TypeIIITurn(VOCABULARY_NAMESPACE + "TypeIIITurn"),
		TypeIIIPrimeTurn(VOCABULARY_NAMESPACE + "TypeIIIPrimeTurn"),
		BetaStrand(VOCABULARY_NAMESPACE + "BetaStrand"),
		HelixLength(VOCABULARY_NAMESPACE + "HelixLength"),
		AngleAlpha(VOCABULARY_NAMESPACE + "AngleAlpha"),
		AngleBeta(VOCABULARY_NAMESPACE + "AngleBeta"),
		AngleGamma(VOCABULARY_NAMESPACE + "AngleGamma"),
		EdgeALength(VOCABULARY_NAMESPACE + "EdgeALength"),
		EdgeBLength(VOCABULARY_NAMESPACE + "EdgeBLength"),
		EdgeCLength(VOCABULARY_NAMESPACE + "EdgeCLength"),
		ReciprocalAngleAlpha(VOCABULARY_NAMESPACE + "ReciprocalAngleAlpha"),
		ReciprocalAngleGamma(VOCABULARY_NAMESPACE + "ReciprocalAngleGamma"),
		ReciprocalAngleBeta(VOCABULARY_NAMESPACE + "ReciprocalAngleBeta"),
		ReciprocalEdgeALength(VOCABULARY_NAMESPACE + "ReciprocalEdgeALength"),
		ReciprocalEdgeBLength(VOCABULARY_NAMESPACE + "ReciprocalEdgeBLength"),
		ReciprocalEdgeCLength(VOCABULARY_NAMESPACE + "ReciprocalEdgeCLength"),
		Volume(VOCABULARY_NAMESPACE + "Volume"),
		XRayDiffraction(VOCABULARY_NAMESPACE + "XRayDiffraction"),
		NeutronDiffraction(VOCABULARY_NAMESPACE + "NeutronDiffraction"),
		FiberDiffraction(VOCABULARY_NAMESPACE + "FiberDiffraction"),
		ElectronCrystallography(VOCABULARY_NAMESPACE + "ElectronCrystallography"),
		ElectronMicroscopy(VOCABULARY_NAMESPACE + "ElectronMicroscopy"),
		SolutionNmr(VOCABULARY_NAMESPACE + "SolutionNmr"),
		SolidStateNmr(VOCABULARY_NAMESPACE + "SolidStateNmr"),
		SolutionScattering(VOCABULARY_NAMESPACE + "SolutionScattering"),
		PowderDiffraction(VOCABULARY_NAMESPACE + "PowderDiffraction"),
		InfraredSpectroscopy(VOCABULARY_NAMESPACE + "InfraredSpectroscopy"),
		CoefficientMu(VOCABULARY_NAMESPACE + "CoefficientMu"),
		MaximumTransmissionFactor(VOCABULARY_NAMESPACE + "MaximumTransmissionFactor"),
		MinimumTransmissionFactor(VOCABULARY_NAMESPACE + "MinimumTransmissionFactor"),
		AnalyticalAbsortCorrection(VOCABULARY_NAMESPACE + "AnalyticalAbsortCorrection"),
		CylinderAbsortCorrection(VOCABULARY_NAMESPACE + "CylinderAbsortCorrection"),
		EmpiricalAbsortCorrection(VOCABULARY_NAMESPACE + "EmpiricalAbsortCorrection"),
		GaussianAbsortCorrection(VOCABULARY_NAMESPACE + "GaussianAbsortCorrection"),
		IntegrationAbsortCorrection(VOCABULARY_NAMESPACE + "IntegrationAbsortCorrection"),
		MultiScanAbsortCorrection(VOCABULARY_NAMESPACE + "MultiScanAbsortCorrection"),
		NumericalAbsortCorrection(VOCABULARY_NAMESPACE + "NumericalAbsortCorrection"),
		PsiScanAbsortCorrection(VOCABULARY_NAMESPACE + "PsiScanAbsortCorrection"),
		RefdelfAbsortCorrection(VOCABULARY_NAMESPACE + "RefdelfAbsortCorrection"),
		SphereAbsortCorrection(VOCABULARY_NAMESPACE + "SphereAbsortCorrection"),
		Macrophage(VOCABULARY_NAMESPACE + "Macrophage"),
		SamariumAtom(VOCABULARY_NAMESPACE + "SamariumAtom"),
		Baculovirus(VOCABULARY_NAMESPACE + "Baculovirus"),
		Bacteria(VOCABULARY_NAMESPACE + "Bacteria"),
		Vector(VOCABULARY_NAMESPACE + "Vector"),
		Atom(VOCABULARY_NAMESPACE + "Atom"),
		TheoreticalModel(VOCABULARY_NAMESPACE + "TheoreticalModel"),
		HybridExperimentalMethod(VOCABULARY_NAMESPACE + "HybridExperimentalMethod"),
		ZirconiumAtom(VOCABULARY_NAMESPACE + "ZirconiumAtom"),
		NiobiumAtom(VOCABULARY_NAMESPACE + "NiobiumAtom"),
		TechnetiumAtom(VOCABULARY_NAMESPACE + "TechnetiumAtom"),
		RutheniumAtom(VOCABULARY_NAMESPACE + "RutheniumAtom"),
		IndiumAtom(VOCABULARY_NAMESPACE + "IndiumAtom"),
		TinAtom(VOCABULARY_NAMESPACE + "TinAtom"),
		AntimonyAtom(VOCABULARY_NAMESPACE + "AntimonyAtom"),
		TellurimAtom(VOCABULARY_NAMESPACE + "TellurimAtom"),
		CesiumAtom(VOCABULARY_NAMESPACE + "CesiumAtom"),
		BariumAtom(VOCABULARY_NAMESPACE + "BariumAtom"),
		LanthanumAtom(VOCABULARY_NAMESPACE + "LanthanumAtom"),
		HafniumAtom(VOCABULARY_NAMESPACE + "HafniumAtom"),
		TantalumAtom(VOCABULARY_NAMESPACE + "TantalumAtom"),
		TungstenAtom(VOCABULARY_NAMESPACE + "TungstenAtom"),
		RheniumAtom(VOCABULARY_NAMESPACE + "RheniumAtom"),
		OsmiumAtom(VOCABULARY_NAMESPACE + "OsmiumAtom"),
		IridiumAtom(VOCABULARY_NAMESPACE + "IridiumAtom"),
		PlatinumAtom(VOCABULARY_NAMESPACE + "PlatinumAtom"),
		GoldAtom(VOCABULARY_NAMESPACE + "GoldAtom"),
		ThalliumAtom(VOCABULARY_NAMESPACE + "ThalliumAtom"),
		BismuthAtom(VOCABULARY_NAMESPACE + "BismuthAtom"),
		PoloniumAtom(VOCABULARY_NAMESPACE + "PoloniumAtom"),
		AstatineAtom(VOCABULARY_NAMESPACE + "AstatineAtom"),
		RadonAtom(VOCABULARY_NAMESPACE + "RadonAtom"),
		FranciumAtom(VOCABULARY_NAMESPACE + "FranciumAtom"),
		RadiumAtom(VOCABULARY_NAMESPACE + "RadiumAtom"),
		ActiniumAtom(VOCABULARY_NAMESPACE + "ActiniumAtom"),
		RuthefordiumAtom(VOCABULARY_NAMESPACE + "RuthefordiumAtom"),
		DubniumAtom(VOCABULARY_NAMESPACE + "DubniumAtom"),
		SeaborgiumAtom(VOCABULARY_NAMESPACE + "SeaborgiumAtom"),
		BohriumAtom(VOCABULARY_NAMESPACE + "BohriumAtom"),
		HassiumAtom(VOCABULARY_NAMESPACE + "HassiumAtom"),
		MeitneriumAtom(VOCABULARY_NAMESPACE + "MeitneriumAtom"),
		DarmstadtiumAtom(VOCABULARY_NAMESPACE + "DarmstadtiumAtom"),
		RoentgeniumAtom(VOCABULARY_NAMESPACE + "RoentgeniumAtom"),
		CeriumAtom(VOCABULARY_NAMESPACE + "CeriumAtom"),
		PraseodymiumAtom(VOCABULARY_NAMESPACE + "PraseodymiumAtom"),
		NeodymiumAtom(VOCABULARY_NAMESPACE + "NeodymiumAtom"),
		PromethiumAtom(VOCABULARY_NAMESPACE + "PromethiumAtom"),
		EuropiumAtom(VOCABULARY_NAMESPACE + "EuropiumAtom"),
		GadoliniumAtom(VOCABULARY_NAMESPACE + "GadoliniumAtom"),
		TerbiumAtom(VOCABULARY_NAMESPACE + "TerbiumAtom"),
		DysprosiumAtom(VOCABULARY_NAMESPACE + "DysprosiumAtom"),
		HolmiumAtom(VOCABULARY_NAMESPACE + "HolmiumAtom"),
		ErbiumAtom(VOCABULARY_NAMESPACE + "ErbiumAtom"),
		ThuliumAtom(VOCABULARY_NAMESPACE + "ThuliumAtom"),
		YtterbiumAtom(VOCABULARY_NAMESPACE + "YtterbiumAtom"),
		LutetiumAtom(VOCABULARY_NAMESPACE + "LutetiumAtom"),
		ThoriumAtom(VOCABULARY_NAMESPACE + "ThoriumAtom"),
		ProtactiniumAtom(VOCABULARY_NAMESPACE + "ProtactiniumAtom"),
		UraniumAtom(VOCABULARY_NAMESPACE + "UraniumAtom"),
		NeptuniumAtom(VOCABULARY_NAMESPACE + "NeptuniumAtom"),
		PlutoniumAtom(VOCABULARY_NAMESPACE + "PlutoniumAtom"),
		AmericiumAtom(VOCABULARY_NAMESPACE + "AmericiumAtom"),
		CuriumAtom(VOCABULARY_NAMESPACE + "CuriumAtom"),
		BerkeliumAtom(VOCABULARY_NAMESPACE + "BerkeliumAtom"),
		CaliforniumAtom(VOCABULARY_NAMESPACE + "CaliforniumAtom"),
		EinsteiniumAtom(VOCABULARY_NAMESPACE + "EinsteiniumAtom"),
		FermiumAtom(VOCABULARY_NAMESPACE + "FermiumAtom"),
		MendeleviumAtom(VOCABULARY_NAMESPACE + "MendeleviumAtom"),
		NobeliumAtom(VOCABULARY_NAMESPACE + "NobeliumAtom"),
		LawrenciumAtom(VOCABULARY_NAMESPACE + "LawrenciumAtom"),
		FluorescenceTransfer(VOCABULARY_NAMESPACE + "FluorescenceTransfer"),
		DeuteriumAtom(VOCABULARY_NAMESPACE + "DeuteriumAtom"),
		BIsoMean(VOCABULARY_NAMESPACE + "BIsoMean"),
		BIsoMax(VOCABULARY_NAMESPACE + "BIsoMax"),
		BIsoMin(VOCABULARY_NAMESPACE + "BIsoMin"),
		AnisoB11(VOCABULARY_NAMESPACE + "Aniso_B11"),
		AnisoB12(VOCABULARY_NAMESPACE + "Aniso_B12"),
		AnisoB13(VOCABULARY_NAMESPACE + "Aniso_B13"),
		AnisoB22(VOCABULARY_NAMESPACE + "Aniso_B22"),
		AnisoB23(VOCABULARY_NAMESPACE + "Aniso_B23"),
		AnisoB33(VOCABULARY_NAMESPACE + "Aniso_B33"),
		CorrelationCoefficientFoToFc(VOCABULARY_NAMESPACE + "CorrelationCoefficientFoToFc"),
		CorrelationCoefficientFoToFcFree(VOCABULARY_NAMESPACE + "CorrelationCoefficientFoToFcFree"),
		RefinementDetails(VOCABULARY_NAMESPACE + "RefinementDetails"),
		RefinementMethod(VOCABULARY_NAMESPACE + "RefinementMethod"),
		LsRFactorRFree(VOCABULARY_NAMESPACE + "LsRFactorRFree"),
		LsRFactorRFreeError(VOCABULARY_NAMESPACE + "LsRFactorRFreeError"),
		LsRFactorRFreeErrorDetails(VOCABULARY_NAMESPACE + "LsRFactorRFreeErrorDetails"),
		LsRFactorRWork(VOCABULARY_NAMESPACE + "LsRFactorRWork"),
		LsRFactorAll(VOCABULARY_NAMESPACE + "LsRFactorAll"),
		LsRFactorObs(VOCABULARY_NAMESPACE + "LsRFactorObs"),
		LsDResHigh(VOCABULARY_NAMESPACE + "LsDResHigh"),
		LsDResLow(VOCABULARY_NAMESPACE + "LsDResLow"),
		LsNumberParameters(VOCABULARY_NAMESPACE + "LsNumberParameters"),
		LsNumberReflnsRFree(VOCABULARY_NAMESPACE + "LsNumberReflnsRFree"),
		LsNumberReflnsAll(VOCABULARY_NAMESPACE + "LsNumberReflnsAll"),
		LsNumberReflnsObs(VOCABULARY_NAMESPACE + "LsNumberReflnsObs"),
		LsNumberRestraints(VOCABULARY_NAMESPACE + "LsNumberRestraints"),
		LsPercentReflnsRFree(VOCABULARY_NAMESPACE + "LsPercentReflnsRFree"),
		LsPercentReflnsObs(VOCABULARY_NAMESPACE + "LsPercentReflnsObs"),
		LsRedundancyReflnsObs(VOCABULARY_NAMESPACE + "LsRedundancyReflnsObs"),
		LsWRFactorRFree(VOCABULARY_NAMESPACE + "LsWRFactorRFree"),
		LsWRFactorRWork(VOCABULARY_NAMESPACE + "LsWRFactorRWork"),
		OccupancyMax(VOCABULARY_NAMESPACE + "OccupancyMax"),
		OccupancyMin(VOCABULARY_NAMESPACE + "OccupancyMin"),
		OverallFOMFreeRSet(VOCABULARY_NAMESPACE + "OverallFOMFreeRSet"),
		OverallFOMWorkRSet(VOCABULARY_NAMESPACE + "OverallFOMWorkRSet"),
		OverallSUB(VOCABULARY_NAMESPACE + "OverallSUB"),
		OverallSUML(VOCABULARY_NAMESPACE + "OverallSUML"),
		OverallSURCruishankDPI(VOCABULARY_NAMESPACE + "OverallSURCruishankDPI"),
		OverallSURFree(VOCABULARY_NAMESPACE + "OverallSURFree"),
		RFreeSelectionDetails(VOCABULARY_NAMESPACE + "RFreeSelectionDetails"),
		DataCutoffHighAbsF(VOCABULARY_NAMESPACE + "DataCutoffHighAbsF"),
		DataCutoffHighRmsAbsF(VOCABULARY_NAMESPACE + "DataCutoffHighRmsAbsF"),
		DataCutoffLowAbsF(VOCABULARY_NAMESPACE + "DataCutoffLowAbsF"),
		IsotropicThermalModel(VOCABULARY_NAMESPACE + "IsotropicThermalModel"),
		LsCrossValidMethod(VOCABULARY_NAMESPACE + "LsCrossValidMethod"),
		LsSigmaF(VOCABULARY_NAMESPACE + "LsSigmaF"),
		LsSigmaI(VOCABULARY_NAMESPACE + "LsSigmaI"),
		MethodToDetermineStruct(VOCABULARY_NAMESPACE + "MethodToDetermineStruct"),
		OverallESUR(VOCABULARY_NAMESPACE + "OverallESUR"),
		OverallESURFree(VOCABULARY_NAMESPACE + "OverallESURFree"),
		OverallPhaseError(VOCABULARY_NAMESPACE + "OverallPhaseError"),
		SolventIonProbeRadii(VOCABULARY_NAMESPACE + "SolventIonProbeRadii"),
		SolventShrinkageRadii(VOCABULARY_NAMESPACE + "SolventShrinkageRadii"),
		SolventVdwProbeRadii(VOCABULARY_NAMESPACE + "SolventVdwProbeRadii"),
		StartingModel(VOCABULARY_NAMESPACE + "StartingModel"),
		StereochemTargetValSpecCase(VOCABULARY_NAMESPACE + "StereochemTargetValSpecCase"),
		StereochemistryTargetValues(VOCABULARY_NAMESPACE + "StereochemistryTargetValues"),
		SolventModelDetails(VOCABULARY_NAMESPACE + "SolventModelDetails"),
		SolventModelParamBsol(VOCABULARY_NAMESPACE + "SolventModelParamBsol"),
		SolventModelParamKsol(VOCABULARY_NAMESPACE + "SolventModelParamKsol"),
		ConformerSelectionCriteria(VOCABULARY_NAMESPACE + "ConformerSelectionCriteria"),
		AverageConstraintViolationsPerResidue(VOCABULARY_NAMESPACE + "AverageConstraintViolationsPerResidue"),
		Refinement(VOCABULARY_NAMESPACE + "Refinement"),
		Alanine(VOCABULARY_NAMESPACE + "Alanine"),
		Arginine(VOCABULARY_NAMESPACE + "Arginine"),
		Asparagine(VOCABULARY_NAMESPACE + "Asparagine"),
		AsparticAcid(VOCABULARY_NAMESPACE + "AsparticAcid"),
		Cysteine(VOCABULARY_NAMESPACE + "Cysteine"),
		GlutamicAcid(VOCABULARY_NAMESPACE + "GlutamicAcid"),
		Glutamine(VOCABULARY_NAMESPACE + "Glutamine"),
		Glycine(VOCABULARY_NAMESPACE + "Glycine"),
		Histidine(VOCABULARY_NAMESPACE + "Histidine"),
		Isoleucine(VOCABULARY_NAMESPACE + "Isoleucine"),
		Leucine(VOCABULARY_NAMESPACE + "Leucine"),
		Lysine(VOCABULARY_NAMESPACE + "Lysine"),
		Methionine(VOCABULARY_NAMESPACE + "Methionine"),
		Phenylalanine(VOCABULARY_NAMESPACE + "Phenylalanine"),
		Proline(VOCABULARY_NAMESPACE + "Proline"),
		Serine(VOCABULARY_NAMESPACE + "Serine"),
		Threonine(VOCABULARY_NAMESPACE + "Threonine"),
		Tryptophan(VOCABULARY_NAMESPACE + "Tryptophan"),
		Tyrosine(VOCABULARY_NAMESPACE + "Tyrosine"),
		Valine(VOCABULARY_NAMESPACE + "Valine"),
		Selenocysteine(VOCABULARY_NAMESPACE + "Selenocysteine"),
		Pyrrolysine(VOCABULARY_NAMESPACE + "Pyrrolysine"),
		CytidineFivePrimeMonophosphate(VOCABULARY_NAMESPACE + "Cytidine5PrimeMonophosphate"),
		GuanosineFivePrimeMonophosphate(VOCABULARY_NAMESPACE + "Guanosine5PrimeMonophosphate"),
		UridineFivePrimeMonophosphate(VOCABULARY_NAMESPACE + "Uridine5PrimeMonophosphate"),
		AdenosineFivePrimeMonophosphate(VOCABULARY_NAMESPACE + "Adenosine5PrimeMonophosphate"),
		TwoPrimeDeoxyAdenosineFivePrimeMonophosphate(VOCABULARY_NAMESPACE + "TwoPrimeDeoxyAdenosineMonophosphate"),
		TwoPrimeDeoxyCytidineFivePrimeMonophosphate(VOCABULARY_NAMESPACE + "TwoPrimeDeoxyCitidineMonophosphate"),
		TwoPrimeDeoxyGuanosineFivePrimeMonophosphate(VOCABULARY_NAMESPACE + "TwoPrimeDeoxyGuanosineFivePrimeMonophosphate"),
		ThymidineFivePrimeMonophosphate(VOCABULARY_NAMESPACE + "Thymidine5PrimeMonophosphate"),
		PhosphoThreonine(VOCABULARY_NAMESPACE + "PhosphoThreonine"),
		SOxyCysteine(VOCABULARY_NAMESPACE + "SOxyCysteine"),
		Adenine(VOCABULARY_NAMESPACE + "Adenine"),
		NOneNAcetamidylOneCyclohexymethylTwoHydroxyFourIsoPropylGlutaminylArginylAmide(VOCABULARY_NAMESPACE
				+ "NOneNAcetamidylOneCyclohexymethylTwoHydroxyFourIsoPropylGlutaminylArginylAmide"),
		FivePrimeBromo2PrimeDeoxyCytidineFivePrimeMonophosphate(VOCABULARY_NAMESPACE
				+ "FivePrimeBromo2PrimeDeoxyCytidineFivePrimeMonophosphate"),
		OneROneFourAnhydroTwoDeoxyOneThreeFluoroPhenylFiveOPhosphonoDErythroPentinol(VOCABULARY_NAMESPACE
				+ "OneROneFourAnhydroTwoDeoxyOneThreeFluoroPhenylFiveOPhosphonoDErythroPentinol"),
		PotassiumIon(VOCABULARY_NAMESPACE + "PotassiumIon"),
		ChlorideIon(VOCABULARY_NAMESPACE + "ChlorideIon"),
		UnknownResidue(VOCABULARY_NAMESPACE + "UnknownResidue"),
		TwoPrimeDeoxyinosine5PrimeMonophosphate(VOCABULARY_NAMESPACE + "2PrimeDeoxyinosine5PrimeMonophosphate"),
		NapdNicotinamideAdenineDinucleotidePhosphate(VOCABULARY_NAMESPACE + "NapdNicotinamideAdenineDinucleotidePhosphate"),
		FiveMethylTwoPrimeDeoxyCytidineFivePrimeMonophosphate(VOCABULARY_NAMESPACE
				+ "FiveMethylTwoPrimeDeoxyCytidineFivePrimeMonophosphate"),
		Glycerol(VOCABULARY_NAMESPACE + "Glycerol"),
		DGammaGlutamylLCysteinylGlycine(VOCABULARY_NAMESPACE + "DGammaGlutamylLCysteinylGlycine"),
		GalactoseUridineFivePrimeDiphosphate(VOCABULARY_NAMESPACE + "GalactoseUridineFivePrimeDiphosphate"),
		PhosphoSerine(VOCABULARY_NAMESPACE + "PhosphoSerine"),
		MagnesiumIon(VOCABULARY_NAMESPACE + "MagnesiumIon"),
		SelenoMethionine(VOCABULARY_NAMESPACE + "SelenoMethionine"),
		NickelIIIon(VOCABULARY_NAMESPACE + "NickelIIIon"),
		OleicAcid(VOCABULARY_NAMESPACE + "OleicAcid"),
		CalciumIon(VOCABULARY_NAMESPACE + "CalciumIon"),
		SulfateIon(VOCABULARY_NAMESPACE + "SulfateIon"),
		IronSulfurCluster(VOCABULARY_NAMESPACE + "IronSulfurCluster"),
		ManganeseIonII(VOCABULARY_NAMESPACE + "ManganeseIonII"),
		AdenosineFivePrimeTriPhosphate(VOCABULARY_NAMESPACE + "AdenosineFivePrimeTriPhosphate"),
		NicotinamideAdenineDinucleotide(VOCABULARY_NAMESPACE + "NicotinamideAdenineDinucleotide"),
		TwoTwoSTwoMethylPyrrolidinTwoYLOneHBenzimiazoleSevenCarboxamide(VOCABULARY_NAMESPACE
				+ "TwoTwoSTwoMethylPyrrolidinTwoYLOneHBenzimiazoleSevenCarboxamide"),
		FeIIIIon(VOCABULARY_NAMESPACE + "FeIIIIon"),
		HypoPhosphite(VOCABULARY_NAMESPACE + "HypoPhosphite"),
		TwoAminoTwoHydroxyMethylPropaneOneThreeDiol(VOCABULARY_NAMESPACE + "TwoAminoTwoHydroxyMethylPropaneOneThreeDiol"),
		PyroglutamicAcid(VOCABULARY_NAMESPACE + "PyroglutamicAcid"),
		TwoHydroxymethylSixOctylsulfanylTetrahydroPyranThreeFourFiveTriol(VOCABULARY_NAMESPACE
				+ "TwoHydroxymethylSixOctylsulfanylTetrahydroPyranThreeFourFiveTriol"),
		ArachidonicAcid(VOCABULARY_NAMESPACE + "ArachidonicAcid"),
		ProtoporphyrinIxContainingFe(VOCABULARY_NAMESPACE + "ProtoporphyrinIxContainingFe"),
		NAcetylDGlucosamine(VOCABULARY_NAMESPACE + "NAcetylDGlucosamine"),
		AminoGroup(VOCABULARY_NAMESPACE + "AminoGroup"),
		FlavinMononucleotide(VOCABULARY_NAMESPACE + "FlavinMononucleotide"),
		ZincIon(VOCABULARY_NAMESPACE + "ZincIon"),
		GuanosineFivePrimeDiphosphate(VOCABULARY_NAMESPACE + "GuanosineFivePrimeDiphosphate"),
		FourRFourAlphaThiazolyBenzamide(VOCABULARY_NAMESPACE + "FourRFourAlphaThiazolyBenzamide"),
		ThreeCycloPentylNHydroxyPropanamide(VOCABULARY_NAMESPACE + "ThreeCycloPentylNHydroxyPropanamide"),
		SodiumIon(VOCABULARY_NAMESPACE + "SodiumIon"),
		UniprotCrossReference(VOCABULARY_NAMESPACE + "UnitprotCrossReference"),
		GoCrossReference(VOCABULARY_NAMESPACE + "GoCrossReference"), ;
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
		distribution("http://bio2rdf.org/dcat:distribution"),
		isParticipantIn(VOCABULARY_NAMESPACE + "isParticipantIn"),
		hasProduct(VOCABULARY_NAMESPACE + "hasProduct"),
		hasPublication(VOCABULARY_NAMESPACE + "hasPublication"),
		hasDocumentSection(VOCABULARY_NAMESPACE + "hasDocumentSection"),
		hasPublisher(VOCABULARY_NAMESPACE + "hasPublisher"),
		hasTitle(VOCABULARY_NAMESPACE + "hasTitle"),
		hasCountryOfPublication(VOCABULARY_NAMESPACE + "hasCountryOfPublication"),
		hasMedlineId(VOCABULARY_NAMESPACE + "hasMedlineId"),
		hasJournalAbbreviation(VOCABULARY_NAMESPACE + "hasJournalAbbreviation"),
		hasName(VOCABULARY_NAMESPACE + "hasName"),
		isPublishedIn(VOCABULARY_NAMESPACE + "isPublishedIn"),
		hasVolumeNumber(VOCABULARY_NAMESPACE + "hasVolumeNumber"),
		hasISBN(VOCABULARY_NAMESPACE + "hasISBN"),
		hasISSN(VOCABULARY_NAMESPACE + "hasISSN"),
		hasLanguage(VOCABULARY_NAMESPACE + "hasLanguage"),
		hasFirstPageNumber(VOCABULARY_NAMESPACE + "hasFirstPageNumber"),
		hasLastPageNumber(VOCABULARY_NAMESPACE + "hasLastPageNumber"),
		hasDOI(VOCABULARY_NAMESPACE + "hasDOI"),
		hasPubmedId(VOCABULARY_NAMESPACE + "hasPubmedId"),
		hasPublicationYear(VOCABULARY_NAMESPACE + "hasPublicationYear"),
		hasAuthor(VOCABULARY_NAMESPACE + "hasAuthor"),
		hasNumberOfMonomers(VOCABULARY_NAMESPACE + "hasNumberOfMonomers"),
		hasTheoreticalFormulaWeight(VOCABULARY_NAMESPACE + "hasTheoreticalFormulaWeight"),
		hasExperimentalFormulaWeight(VOCABULARY_NAMESPACE + "hasExperimentalFormulaWeight"),
		hasChemicalSubstanceAmount(VOCABULARY_NAMESPACE + "hasChemicalSubstanceAmount"),
		hasPolymerSequence(VOCABULARY_NAMESPACE + "hasPolymerSequence"),
		hasSource(VOCABULARY_NAMESPACE + "hasSource"),
		isProducedBy(VOCABULARY_NAMESPACE + "isProducedBy"),
		isDerivedFrom(VOCABULARY_NAMESPACE + "isDerivedFrom"),
		hasSpatialLocation(VOCABULARY_NAMESPACE + "hasSpatialLocation"),
		hasIsotropicAtomicDisplacement(VOCABULARY_NAMESPACE + "hasIsotropicAtomicDisplacement"),
		hasXCoordinate(VOCABULARY_NAMESPACE + "hasXCoordinate"),
		hasYCoordinate(VOCABULARY_NAMESPACE + "hasYCoordinate"),
		hasZCoordinate(VOCABULARY_NAMESPACE + "hasZCoordinate"),
		hasOccupancy(VOCABULARY_NAMESPACE + "hasOccupancy"),
		hasFormalCharge(VOCABULARY_NAMESPACE + "hasFormalCharge"),
		isImmediatelyBefore(VOCABULARY_NAMESPACE + "isImmediatelyBefore"),
		hasChainPosition(VOCABULARY_NAMESPACE + "hasChainPosition"),
		hasDevelopmentStage(VOCABULARY_NAMESPACE + "hasDevelopmentStage"),
		hasNumberOfAtoms(VOCABULARY_NAMESPACE + "hasNumberOfAtoms"),
		hasNumberOfNonHydrogenAtoms(VOCABULARY_NAMESPACE + "hasNumberOfNonHydrogenAtoms"),
		hasChemicalFormula(VOCABULARY_NAMESPACE + "hasChemicalFormula"),
		beginsAt(VOCABULARY_NAMESPACE + "beginsAt"),
		endsAt(VOCABULARY_NAMESPACE + "endsAt"),
		hasHelixLength(VOCABULARY_NAMESPACE + "hasHelixLegth"),
		hasUnitCell(VOCABULARY_NAMESPACE + "hasUnitCell"),
		hasAngleAlpha(VOCABULARY_NAMESPACE + "hasAngleAlpha"),
		hasParticipant(VOCABULARY_NAMESPACE + "hasParticipant"),
		hasAngleBeta(VOCABULARY_NAMESPACE + "hasAngleBeta"),
		hasAngleGamma(VOCABULARY_NAMESPACE + "hasAngleGamma"),
		hasEdgeALength(VOCABULARY_NAMESPACE + "hasEdgeALength"),
		hasEdgeBLength(VOCABULARY_NAMESPACE + "hasEdgeBLength"),
		hasEdgeCLength(VOCABULARY_NAMESPACE + "hasEdgeCLength"),
		hasReciprocalAngleAlpha(VOCABULARY_NAMESPACE + "hasReciprocalAngleAlpha"),
		hasReciprocalAngleGamma(VOCABULARY_NAMESPACE + "hasReciprocalAngleGamma"),
		hasReciprocalAngleBeta(VOCABULARY_NAMESPACE + "hasReciprocalAngleBeta"),
		hasReciprocalEdgeALength(VOCABULARY_NAMESPACE + "hasReciprocalEdgeALength"),
		hasReciprocalEdgeBLength(VOCABULARY_NAMESPACE + "hasReciprocalEdgeBLength"),
		hasReciprocalEdgeCLength(VOCABULARY_NAMESPACE + "hasReciprocalEdgeCLength"),
		hasVolume(VOCABULARY_NAMESPACE + "hasVolume"),
		hasCoefficientMu(VOCABULARY_NAMESPACE + "hasCoefficientMu"),
		hasMaximumTransmissionFactor(VOCABULARY_NAMESPACE + "hasMaximumTransmissionFactor"),
		hasMinimumTransmissionFactor(VOCABULARY_NAMESPACE + "hasMinimumTransmissionFactor"),
		hasBIsoMean(VOCABULARY_NAMESPACE + "hasBIsoMean"),
		hasBIsoMax(VOCABULARY_NAMESPACE + "hasBIsoMax"),
		hasBIsoMin(VOCABULARY_NAMESPACE + "hasBIsoMin"),
		hasAnisoB11(VOCABULARY_NAMESPACE + "hasAnisoB11"),
		hasAnisoB12(VOCABULARY_NAMESPACE + "hasAnisoB12"),
		hasAnisoB13(VOCABULARY_NAMESPACE + "hasAnisoB13"),
		hasAnisoB22(VOCABULARY_NAMESPACE + "hasAnisoB22"),
		hasAnisoB23(VOCABULARY_NAMESPACE + "hasAnisoB23"),
		hasAnisoB33(VOCABULARY_NAMESPACE + "hasAnisoB33"),
		hasCorrelationCoefficientFoToFc(VOCABULARY_NAMESPACE + "hasCorrelationCoefficientFoToFc"),
		hasCorrelationCoefficientFoToFcFree(VOCABULARY_NAMESPACE + "hasCorrelationCoefficientFoToFcFree"),
		hasDetails(VOCABULARY_NAMESPACE + "hasDetails"),
		hasLsRFactorRFree(VOCABULARY_NAMESPACE + "hasLsRFactorRFree"),
		hasLsRFactorRFreeError(VOCABULARY_NAMESPACE + "hasLsRFactorRFreeError"),
		hasLsRFactorRFreeErrorDetails(VOCABULARY_NAMESPACE + "hasLsRFactorRFreeError"),
		hasLsRFactorRWork(VOCABULARY_NAMESPACE + "hasLsRFactorRWork"),
		hasLsRFactorObs(VOCABULARY_NAMESPACE + "hasLsRFactorObs"),
		hasLsDResHigh(VOCABULARY_NAMESPACE + "hasLsDResHigh"),
		hasLsDResLow(VOCABULARY_NAMESPACE + "hasLsDResLow"),
		hasLsNumberParameters(VOCABULARY_NAMESPACE + "hasLsNumberParameters"),
		hasLsNumberReflnsRFree(VOCABULARY_NAMESPACE + "hasLsNumberReflnsRFree"),
		hasLsNumberReflnsAll(VOCABULARY_NAMESPACE + "hasLsNumberReflnsAll"),
		hasLsNumberReflnsObs(VOCABULARY_NAMESPACE + "hasLsNumberReflnsObs"),
		hasLsNumberRestraints(VOCABULARY_NAMESPACE + "hasLsNumberRestraints"),
		hasLsPercentReflnsRFree(VOCABULARY_NAMESPACE + "hasLsPercentReflnsRFree"),
		hasLsPercentReflnsObs(VOCABULARY_NAMESPACE + "hasLsPercentReflnsObs"),
		hasLsRedundancyReflnsObs(VOCABULARY_NAMESPACE + "hasLsRedundancyReflnsObs"),
		hasLsWRFactorRFree(VOCABULARY_NAMESPACE + "hasLsWRFactorRFree"),
		hasLsWRFactorRWork(VOCABULARY_NAMESPACE + "hasLsWRFactorRWork"),
		hasLsRFactorAll(VOCABULARY_NAMESPACE + "hasLsRFactorAll"),
		hasOccupancyMax(VOCABULARY_NAMESPACE + "hasOccupancyMax"),
		hasOccupancyMin(VOCABULARY_NAMESPACE + "hasOccupancyMin"),
		hasOverallFOMFreeRSet(VOCABULARY_NAMESPACE + "hasOverallFOMFreeRSet"),
		hasOverallFOMWorkRSet(VOCABULARY_NAMESPACE + "hasOverallFOMWorkRSet"),
		hasOverallSUB(VOCABULARY_NAMESPACE + "hasOverallSUB"),
		hasOverallSUML(VOCABULARY_NAMESPACE + "hasOverallSUML"),
		hasOverallSURCruickshankDPI(VOCABULARY_NAMESPACE + "hasOverallSURCruickshankDPI"),
		hasOverallSURFree(VOCABULARY_NAMESPACE + "hasOverallSURFree"),
		hasRFreeSelectionDetails(VOCABULARY_NAMESPACE + "hasRFreeSelectionDetails"),
		hasDataCutoffHighAbsF(VOCABULARY_NAMESPACE + "hasDataCutoffHighAbsF"),
		hasDataCutoffHighRmsAbsF(VOCABULARY_NAMESPACE + "hasDataCutoffHighRmsAbsF"),
		hasDataCutoffLowAbsF(VOCABULARY_NAMESPACE + "hasDataCutoffLowAbsF"),
		hasIsotropicThermalModel(VOCABULARY_NAMESPACE + "hasIsotropicThermalModel"),
		hasLsCrossValidMethod(VOCABULARY_NAMESPACE + "hasLsCrossValidMethod"),
		hasLsSigmaF(VOCABULARY_NAMESPACE + "hasLsSigmaF"),
		hasLsSigmaI(VOCABULARY_NAMESPACE + "hasLsSigmaI"),
		hasMethodToDetermineStruct(VOCABULARY_NAMESPACE + "hasMethodToDetermineStruct"),
		hasOverallESUR(VOCABULARY_NAMESPACE + "hasOverallESUR"),
		hasOverallESURFree(VOCABULARY_NAMESPACE + "hasOverallESURFree"),
		hasOverallPhaseError(VOCABULARY_NAMESPACE + "hasOverallPhaseError"),
		hasSolventIonProbeRadii(VOCABULARY_NAMESPACE + "hasSolventIonProbeRadii"),
		hasSolventShrinkageRadii(VOCABULARY_NAMESPACE + "hasSolventShrinkageRadii"),
		hasSolventVdwProbeRadii(VOCABULARY_NAMESPACE + "hasSolventVdwProbeRadii"),
		hasStartingModel(VOCABULARY_NAMESPACE + "hasStartingModel"),
		hasStereochemTargetValSpecCase(VOCABULARY_NAMESPACE + "hasStereochemTargetValSpecCase"),
		hasStereochemistryTargetValues(VOCABULARY_NAMESPACE + "hasStereochemistryTargetValues"),
		hasSolventModelDetails(VOCABULARY_NAMESPACE + "hasSolventModelDetails"),
		hasSolventModelParamBsol(VOCABULARY_NAMESPACE + "hasSolventModelParamBsol"),
		hasSolventModelParamKsol(VOCABULARY_NAMESPACE + "hasSolventModelParamKsol"),
		hasConformerSelectionCriteria(VOCABULARY_NAMESPACE + "hasConformerSelectionCriteria"),
		hasAverageConstraintViolationsPerResidue(VOCABULARY_NAMESPACE + "hasAverageConstraintViolationsPerResidue"),
		hasCoordinate(VOCABULARY_NAMESPACE + "hasCoordinate"),
		hasPart(VOCABULARY_NAMESPACE + "hasPart"),
		isPartOf(VOCABULARY_NAMESPACE + "isPartOf"),
		hasCrossReference(VOCABULARY_NAMESPACE + "hasCrossReference");

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

		hasValue(VOCABULARY_NAMESPACE + "hasValue"), hasStandardDeviation(VOCABULARY_NAMESPACE + "hasStandardDeviation");

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
		details(VOCABULARY_NAMESPACE + "details"), description(VOCABULARY_NAMESPACE + "description"), experimentalMethod(
				VOCABULARY_NAMESPACE + "experimentalMethod"), modification(VOCABULARY_NAMESPACE + "modification"), mutation(
				VOCABULARY_NAMESPACE + "mutation");

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
