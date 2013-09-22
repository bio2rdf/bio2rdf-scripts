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
public class SangerClassification {

	private static final Map<Integer, RnaKbOwlVocabulary.Class> map = new HashMap<Integer, RnaKbOwlVocabulary.Class>();

	static {
		map.put(1, RnaKbOwlVocabulary.Class.I);
		map.put(2, RnaKbOwlVocabulary.Class.II);
		map.put(3, RnaKbOwlVocabulary.Class.III);
		map.put(4, RnaKbOwlVocabulary.Class.IV);
		map.put(5, RnaKbOwlVocabulary.Class.V);
		map.put(6, RnaKbOwlVocabulary.Class.VI);
		map.put(7, RnaKbOwlVocabulary.Class.VII);
		map.put(8, RnaKbOwlVocabulary.Class.VIII);
		map.put(9, RnaKbOwlVocabulary.Class.IX);
		map.put(10, RnaKbOwlVocabulary.Class.X);
		map.put(11, RnaKbOwlVocabulary.Class.XI);
		map.put(12, RnaKbOwlVocabulary.Class.XII);
		map.put(13, RnaKbOwlVocabulary.Class.XIII);
		map.put(14, RnaKbOwlVocabulary.Class.XIV);
		map.put(15, RnaKbOwlVocabulary.Class.XV);
		map.put(16, RnaKbOwlVocabulary.Class.XVI);
		map.put(17, RnaKbOwlVocabulary.Class.XVII);
		map.put(18, RnaKbOwlVocabulary.Class.XVIII);
		map.put(19, RnaKbOwlVocabulary.Class.XIX);
		map.put(20, RnaKbOwlVocabulary.Class.XX);
		map.put(21, RnaKbOwlVocabulary.Class.XXI);
		map.put(22, RnaKbOwlVocabulary.Class.XXII);
		map.put(23, RnaKbOwlVocabulary.Class.XXIII);
		map.put(24, RnaKbOwlVocabulary.Class.XXIV);
		map.put(25, RnaKbOwlVocabulary.Class.XXV);
		map.put(26, RnaKbOwlVocabulary.Class.XXVI);
		map.put(27, RnaKbOwlVocabulary.Class.XXVII);
		map.put(28, RnaKbOwlVocabulary.Class.XXVIII);
	}

	public static RnaKbOwlVocabulary.Class get(int classNumber) {
		return map.get(classNumber);
	}

}
