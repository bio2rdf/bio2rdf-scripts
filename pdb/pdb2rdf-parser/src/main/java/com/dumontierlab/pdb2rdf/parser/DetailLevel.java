/**
 * Copyright (c) 2010 Alexander De Leon Battista
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
package com.dumontierlab.pdb2rdf.parser;

/**
 * @author Alexander De Leon
 */
public enum DetailLevel {

	COMPLETE(0x1111), ATOM(0x1110), RESIDUE(0x1100), EXPERIMENT(0x1000), METADATA(0x0000);

	public static final DetailLevel DEFAULT = COMPLETE;
	private int mask;

	DetailLevel(int mask) {
		this.mask = mask;
	}

	public boolean hasAtomLocation() {
		return (mask & 0x000F) == 0x0001;
	}

	public boolean hasAtom() {
		return (mask & 0x00F0) == 0x0010;
	}

	public boolean hasResidue() {
		return (mask & 0x0F00) == 0x0100;
	}

	public boolean hasExperimentalData() {
		return (mask & 0xF000) == 0x1000;
	}

}
