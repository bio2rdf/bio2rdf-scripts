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

import java.util.HashMap;
import java.util.Map;

/**
 * @author Alexander De Leon
 */
public class SecondaryStructures {
	private static final Map<String, PdbOwlVocabulary.Class> map = new HashMap<String, PdbOwlVocabulary.Class>();

	static {
		map.put("HELX_P", PdbOwlVocabulary.Class.Helix);
		map.put("HELX_OT_P", PdbOwlVocabulary.Class.NonStandardHelix);
		map.put("HELX_RH_P", PdbOwlVocabulary.Class.RightHandedHelix);
		map.put("HELX_RH_OT_P", PdbOwlVocabulary.Class.NonStandardRightHandedHelix);
		map.put("HELX_RH_AL_P", PdbOwlVocabulary.Class.RightHandedAlphaHelix);
		map.put("HELX_RH_GA_P", PdbOwlVocabulary.Class.RightHandedGammaHelix);
		map.put("HELX_RH_OM_P", PdbOwlVocabulary.Class.RightHandedOmegaHelix);
		map.put("HELX_RH_PI_P", PdbOwlVocabulary.Class.RightHandedPiHelix);
		map.put("HELX_RH_27_P", PdbOwlVocabulary.Class.RightHanded22_7Helix);
		map.put("HELX_RH_3T_P", PdbOwlVocabulary.Class.RightHanded3_10Helix);
		map.put("HELX_RH_PP_P", PdbOwlVocabulary.Class.RightHandedPolyprolineHelix);
		map.put("HELX_LH_P", PdbOwlVocabulary.Class.LeftHandedHelix);
		map.put("HELX_LH_OT_P", PdbOwlVocabulary.Class.NonStandardLeftHandedHelix);
		map.put("HELX_LH_AL_P", PdbOwlVocabulary.Class.LeftHandedAlphaHelix);
		map.put("HELX_LH_GA_P", PdbOwlVocabulary.Class.LeftHandedGammaHelix);
		map.put("HELX_LH_OM_P", PdbOwlVocabulary.Class.LeftHandedOmegaHelix);
		map.put("HELX_LH_PI_P", PdbOwlVocabulary.Class.LeftHandedPiHelix);
		map.put("HELX_LH_27_P", PdbOwlVocabulary.Class.LeftHanded22_7Helix);
		map.put("HELX_LH_3T_P", PdbOwlVocabulary.Class.LeftHanded3_10Helix);
		map.put("HELX_LH_PP_P", PdbOwlVocabulary.Class.LeftHandedPolyprolineHelix);
		map.put("HELX_N", PdbOwlVocabulary.Class.DoubleHelix);
		map.put("HELX_OT_N", PdbOwlVocabulary.Class.NonStandardDoubleHelix);
		map.put("HELX_RH_N", PdbOwlVocabulary.Class.RightHandedDoubleHelix);
		map.put("HELX_RH_OT_N", PdbOwlVocabulary.Class.NonStandardRightHandedDoubleHelix);
		map.put("HELX_RH_A_N", PdbOwlVocabulary.Class.RightHandedADoubleHelix);
		map.put("HELX_RH_B_N", PdbOwlVocabulary.Class.RightHandedBDoubleHelix);
		map.put("HELX_RH_Z_N", PdbOwlVocabulary.Class.RightHandedZDoubleHelix);
		map.put("HELX_LH_N", PdbOwlVocabulary.Class.LeftHandedDoubleHelix);
		map.put("HELX_LH_OT_N", PdbOwlVocabulary.Class.NonStandardLeftHandedDoubleHelix);
		map.put("HELX_LH_A_N", PdbOwlVocabulary.Class.LeftHandedADoubleHelix);
		map.put("HELX_LH_B_N", PdbOwlVocabulary.Class.LeftHandedBDoubleHelix);
		map.put("HELX_LH_Z_N", PdbOwlVocabulary.Class.LeftHandedZDoubleHelix);
		map.put("TURN_P", PdbOwlVocabulary.Class.Turn);
		map.put("TURN_OT_P", PdbOwlVocabulary.Class.NonStandardTurn);
		map.put("TURN_TY1_P", PdbOwlVocabulary.Class.TypeITurn);
		map.put("TURN_TY1P_P", PdbOwlVocabulary.Class.TypeIPrimeTurn);
		map.put("TURN_TY2_P", PdbOwlVocabulary.Class.TypeIITurn);
		map.put("TURN_TY2P_P", PdbOwlVocabulary.Class.TypeIIPrimeTurn);
		map.put("TURN_TY3_P", PdbOwlVocabulary.Class.TypeIIITurn);
		map.put("TURN_TY3P_P", PdbOwlVocabulary.Class.TypeIIIPrimeTurn);
		map.put("STRN", PdbOwlVocabulary.Class.BetaStrand);
	}

	public static PdbOwlVocabulary.Class get(String typeSymbol) {
		PdbOwlVocabulary.Class c = map.get(typeSymbol.toUpperCase());
		assert c != null : "Unknonw secondary sctructure type sybol: " + typeSymbol;
		return c;
	}
}
