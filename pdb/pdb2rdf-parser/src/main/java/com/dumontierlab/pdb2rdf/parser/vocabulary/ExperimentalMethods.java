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
public class ExperimentalMethods {

	private static final Map<String, PdbOwlVocabulary.Class> map = new HashMap<String, PdbOwlVocabulary.Class>();

	static {
		map.put("X-RAY DIFFRACTION", PdbOwlVocabulary.Class.XRayDiffraction);
		map.put("NEUTRON DIFFRACTION", PdbOwlVocabulary.Class.NeutronDiffraction);
		map.put("FIBER DIFFRACTION", PdbOwlVocabulary.Class.FiberDiffraction);
		map.put("ELECTRON CRYSTALLOGRAPHY", PdbOwlVocabulary.Class.ElectronCrystallography);
		map.put("ELECTRON MICROSCOPY", PdbOwlVocabulary.Class.ElectronMicroscopy);
		map.put("SOLUTION NMR", PdbOwlVocabulary.Class.SolutionNmr);
		map.put("SOLID-STATE NMR", PdbOwlVocabulary.Class.SolidStateNmr);
		map.put("SOLUTION SCATTERING", PdbOwlVocabulary.Class.SolutionScattering);
		map.put("POWDER DIFFRACTION", PdbOwlVocabulary.Class.PowderDiffraction);
		map.put("INFRARED SPECTROSCOPY", PdbOwlVocabulary.Class.InfraredSpectroscopy);
		map.put("THEORETICAL MODEL", PdbOwlVocabulary.Class.TheoreticalModel);
		map.put("HYBRID", PdbOwlVocabulary.Class.HybridExperimentalMethod);
		map.put("FLUORESCENCE TRANSFER", PdbOwlVocabulary.Class.FluorescenceTransfer);
	}

	public static PdbOwlVocabulary.Class get(String typeSymbol) {
		PdbOwlVocabulary.Class c = map.get(typeSymbol.toUpperCase());
		assert c != null : "Unknonw experimental method: " + typeSymbol;
		return c;
	}

}
