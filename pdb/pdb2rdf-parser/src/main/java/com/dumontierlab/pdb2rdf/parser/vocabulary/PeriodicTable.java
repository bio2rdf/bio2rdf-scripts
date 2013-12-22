package com.dumontierlab.pdb2rdf.parser.vocabulary;

import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.AluminumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.ArgonAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.ArsenicAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.Atom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.BerylliumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.BoronAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.BromineAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.CadmiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.CalciumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.CarbonAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.ChlorineAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.ChromiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.CobaltAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.CopperAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.FluorineAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.GalliumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.GermaniumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.HeliumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.HydrogenAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.IodineAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.IronAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.KryptonAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.LeadAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.LithiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.MagnesiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.ManganeseAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.MercuryAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.MolybdenumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.NeonAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.NickelAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.NitrogenAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.OxygenAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.PalladiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.PhosphorusAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.PotassiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.RhodiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.RubidiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.SamariumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.ScandiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.SeleniumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.SiliconAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.SilverAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.SodiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.StrontiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.SulfurAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.TitaniumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.VanadiumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.XenonAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.YttriumAtom;
import static com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary.Class.ZincAtom;

import java.util.HashMap;
import java.util.Map;

/**
 * A one to one mapping between symbols from the periodic table and atom
 * classes.
 * 
 * @author Alexander De Leon
 */
public class PeriodicTable {
	private static final Map<String, PdbOwlVocabulary.Class> symbol2class = new HashMap<String, PdbOwlVocabulary.Class>();
	private static final Map<String, String> class2symbol = new HashMap<String, String>();

	static {
		put("H", HydrogenAtom);
		put("HE", HeliumAtom);
		put("LI", LithiumAtom);
		put("BE", BerylliumAtom);
		put("B", BoronAtom);
		put("C", CarbonAtom);
		put("N", NitrogenAtom);
		put("O", OxygenAtom);
		put("F", FluorineAtom);
		put("NE", NeonAtom);
		put("NA", SodiumAtom);
		put("MG", MagnesiumAtom);
		put("AL", AluminumAtom);
		put("SI", SiliconAtom);
		put("P", PhosphorusAtom);
		put("S", SulfurAtom);
		put("CL", ChlorineAtom);
		put("AR", ArgonAtom);
		put("K", PotassiumAtom);
		put("CA", CalciumAtom);
		put("SC", ScandiumAtom);
		put("TI", TitaniumAtom);
		put("V", VanadiumAtom);
		put("CR", ChromiumAtom);
		put("MN", ManganeseAtom);
		put("FE", IronAtom);
		put("CO", CobaltAtom);
		put("NI", NickelAtom);
		put("CU", CopperAtom);
		put("ZN", ZincAtom);
		put("GA", GalliumAtom);
		put("GE", GermaniumAtom);
		put("AS", ArsenicAtom);
		put("SE", SeleniumAtom);
		put("BR", BromineAtom);
		put("KR", KryptonAtom);
		put("RB", RubidiumAtom);
		put("SR", StrontiumAtom);
		put("Y", YttriumAtom);
		put("ZR", PdbOwlVocabulary.Class.ZirconiumAtom);
		put("NB", PdbOwlVocabulary.Class.NiobiumAtom);
		put("MO", MolybdenumAtom);
		put("TC", PdbOwlVocabulary.Class.TechnetiumAtom);
		put("RU", PdbOwlVocabulary.Class.RutheniumAtom);
		put("RH", RhodiumAtom);
		put("PD", PalladiumAtom);
		put("AG", SilverAtom);
		put("CD", CadmiumAtom);
		put("IN", PdbOwlVocabulary.Class.IndiumAtom);
		put("SN", PdbOwlVocabulary.Class.TinAtom);
		put("SB", PdbOwlVocabulary.Class.AntimonyAtom);
		put("TE", PdbOwlVocabulary.Class.TellurimAtom);
		put("I", IodineAtom);
		put("XE", XenonAtom);
		put("CS", PdbOwlVocabulary.Class.CesiumAtom);
		put("BA", PdbOwlVocabulary.Class.BariumAtom);
		put("LA", PdbOwlVocabulary.Class.LanthanumAtom);
		put("HF", PdbOwlVocabulary.Class.HafniumAtom);
		put("TA", PdbOwlVocabulary.Class.TantalumAtom);
		put("W", PdbOwlVocabulary.Class.TungstenAtom);
		put("RE", PdbOwlVocabulary.Class.RheniumAtom);
		put("OS", PdbOwlVocabulary.Class.OsmiumAtom);
		put("IR", PdbOwlVocabulary.Class.IridiumAtom);
		put("PT", PdbOwlVocabulary.Class.PlatinumAtom);
		put("AU", PdbOwlVocabulary.Class.GoldAtom);
		put("HG", MercuryAtom);
		put("TL", PdbOwlVocabulary.Class.ThalliumAtom);
		put("PB", LeadAtom);
		put("BI", PdbOwlVocabulary.Class.BismuthAtom);
		put("PO", PdbOwlVocabulary.Class.PoloniumAtom);
		put("AT", PdbOwlVocabulary.Class.AstatineAtom);
		put("RN", PdbOwlVocabulary.Class.RadonAtom);
		put("FR", PdbOwlVocabulary.Class.FranciumAtom);
		put("RA", PdbOwlVocabulary.Class.RadiumAtom);
		put("AC", PdbOwlVocabulary.Class.ActiniumAtom);
		put("RF", PdbOwlVocabulary.Class.RuthefordiumAtom);
		put("DB", PdbOwlVocabulary.Class.DubniumAtom);
		put("SG", PdbOwlVocabulary.Class.SeaborgiumAtom);
		put("BH", PdbOwlVocabulary.Class.BohriumAtom);
		put("HS", PdbOwlVocabulary.Class.HassiumAtom);
		put("MT", PdbOwlVocabulary.Class.MeitneriumAtom);
		put("DS", PdbOwlVocabulary.Class.DarmstadtiumAtom);
		put("RG", PdbOwlVocabulary.Class.RoentgeniumAtom);
		put("CE", PdbOwlVocabulary.Class.CeriumAtom);
		put("PR", PdbOwlVocabulary.Class.PraseodymiumAtom);
		put("ND", PdbOwlVocabulary.Class.NeodymiumAtom);
		put("PM", PdbOwlVocabulary.Class.PromethiumAtom);
		put("SM", SamariumAtom);
		put("EU", PdbOwlVocabulary.Class.EuropiumAtom);
		put("GD", PdbOwlVocabulary.Class.GadoliniumAtom);
		put("TB", PdbOwlVocabulary.Class.TerbiumAtom);
		put("DY", PdbOwlVocabulary.Class.DysprosiumAtom);
		put("HO", PdbOwlVocabulary.Class.HolmiumAtom);
		put("ER", PdbOwlVocabulary.Class.ErbiumAtom);
		put("TM", PdbOwlVocabulary.Class.ThuliumAtom);
		put("YB", PdbOwlVocabulary.Class.YtterbiumAtom);
		put("LU", PdbOwlVocabulary.Class.LutetiumAtom);
		put("TH", PdbOwlVocabulary.Class.ThoriumAtom);
		put("PA", PdbOwlVocabulary.Class.ProtactiniumAtom);
		put("U", PdbOwlVocabulary.Class.UraniumAtom);
		put("NP", PdbOwlVocabulary.Class.NeptuniumAtom);
		put("PU", PdbOwlVocabulary.Class.PlutoniumAtom);
		put("AM", PdbOwlVocabulary.Class.AmericiumAtom);
		put("CM", PdbOwlVocabulary.Class.CuriumAtom);
		put("BK", PdbOwlVocabulary.Class.BerkeliumAtom);
		put("CF", PdbOwlVocabulary.Class.CaliforniumAtom);
		put("ES", PdbOwlVocabulary.Class.EinsteiniumAtom);
		put("FM", PdbOwlVocabulary.Class.FermiumAtom);
		put("MD", PdbOwlVocabulary.Class.MendeleviumAtom);
		put("NO", PdbOwlVocabulary.Class.NobeliumAtom);
		put("LR", PdbOwlVocabulary.Class.LawrenciumAtom);

		put("D", PdbOwlVocabulary.Class.DeuteriumAtom);
		put("X", Atom); // Unknown atom
	}

	public static PdbOwlVocabulary.Class get(String typeSymbol) {
		PdbOwlVocabulary.Class c = symbol2class.get(typeSymbol.toUpperCase());
		assert c != null : "Unknonw atom type symbol: " + typeSymbol;
		return c;
	}

	public static String getSymbol(String atomClassUri) {
		String symbol = class2symbol.get(atomClassUri);
		assert symbol != null : "No a Atom class: " + atomClassUri;
		return symbol;
	}

	private static void put(String symbol, PdbOwlVocabulary.Class atomClass) {
		symbol2class.put(symbol, atomClass);
		class2symbol.put(atomClass.uri(), symbol);
	}
}
