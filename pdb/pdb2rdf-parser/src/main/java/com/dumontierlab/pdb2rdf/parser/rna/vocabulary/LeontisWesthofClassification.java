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
package com.dumontierlab.pdb2rdf.parser.rna.vocabulary;

import java.util.HashMap;
import java.util.Map;

/**
 * @author Alexander De Leon
 */
public class LeontisWesthofClassification {

	private static final Map<Integer, RnaKbOwlVocabulary.Class> map = new HashMap<Integer, RnaKbOwlVocabulary.Class>();

	static {
		map.put(1, RnaKbOwlVocabulary.Class.CisWatsonWatsonBasePair);
		map.put(2, RnaKbOwlVocabulary.Class.TransWatsonWatsonBasePair);
		map.put(3, RnaKbOwlVocabulary.Class.CisWatsonHoogsteenBasePair);
		map.put(4, RnaKbOwlVocabulary.Class.TransWatsonHoogsteenBasePair);
		map.put(5, RnaKbOwlVocabulary.Class.CisWatsonSugarBasePair);
		map.put(6, RnaKbOwlVocabulary.Class.TransWatsonSugarBasePair);
		map.put(7, RnaKbOwlVocabulary.Class.CisHoogsteenHoogsteenBasePair);
		map.put(8, RnaKbOwlVocabulary.Class.TransHoogsteenHogsteenBasePair);
		map.put(9, RnaKbOwlVocabulary.Class.CisHoogsteenSugarBasePair);
		map.put(10, RnaKbOwlVocabulary.Class.TransHoogsteenSugarBasePair);
		map.put(11, RnaKbOwlVocabulary.Class.CisSugarSugarBasePair);
		map.put(12, RnaKbOwlVocabulary.Class.TransSugarSugarBasePair);
	}

	public static RnaKbOwlVocabulary.Class get(int classNumber) {
		return map.get(classNumber);
	}
}
