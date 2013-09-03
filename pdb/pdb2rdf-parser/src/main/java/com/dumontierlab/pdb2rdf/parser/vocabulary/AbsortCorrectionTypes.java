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
public class AbsortCorrectionTypes {

	private static final Map<String, PdbOwlVocabulary.Class> map = new HashMap<String, PdbOwlVocabulary.Class>();

	static {
		map.put("analytical", PdbOwlVocabulary.Class.AnalyticalAbsortCorrection);
		map.put("cylinder", PdbOwlVocabulary.Class.CylinderAbsortCorrection);
		map.put("empirical", PdbOwlVocabulary.Class.EmpiricalAbsortCorrection);
		map.put("gaussian", PdbOwlVocabulary.Class.GaussianAbsortCorrection);
		map.put("integration", PdbOwlVocabulary.Class.IntegrationAbsortCorrection);
		map.put("multi-scan", PdbOwlVocabulary.Class.MultiScanAbsortCorrection);
		map.put("numerical", PdbOwlVocabulary.Class.NumericalAbsortCorrection);
		map.put("psi-scan", PdbOwlVocabulary.Class.PsiScanAbsortCorrection);
		map.put("refdelf", PdbOwlVocabulary.Class.RefdelfAbsortCorrection);
		map.put("sphere", PdbOwlVocabulary.Class.SphereAbsortCorrection);
	}

	public static PdbOwlVocabulary.Class get(String typeSymbol) {
		PdbOwlVocabulary.Class c = map.get(typeSymbol.toUpperCase());
		assert c != null : "Unknonw absort correction type: " + typeSymbol;
		return c;
	}

}
