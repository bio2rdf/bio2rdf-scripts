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
package com.dumontierlab.pdb2rdf.parser.rna.vocabulary.uri;

import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriPattern;

/**
 * @author Alexander De Leon
 */
public enum RnaKbUriPattern implements UriPattern, RnaKbNamespace {

	BASE_PAIR(DEFAULT_NAMESPACE + "{0}_m{1}_BP_{2}{3}_{4}{5}"), OPENING(BASE_PAIR.pattern + "_opening"), STAGGER(
			BASE_PAIR.pattern + "_stagger"), STRETCH(BASE_PAIR.pattern + "_stretch"), SHEAR(BASE_PAIR.pattern + "_shear"), PROPELLER(
			BASE_PAIR.pattern + "_propeller"), BUCKLE(BASE_PAIR.pattern + "_buckle");

	private final String pattern;

	RnaKbUriPattern(String pattern) {
		this.pattern = pattern;
	}

	public boolean requiresEncoding(){
		return true;
	}
	
	public String getPattern() {
		return pattern;
	}

}

interface RnaKbNamespace {
	public static final String DEFAULT_NAMESPACE = "http://semanticscience.org/";
}