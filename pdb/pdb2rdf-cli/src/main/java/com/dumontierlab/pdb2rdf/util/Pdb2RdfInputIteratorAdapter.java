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
package com.dumontierlab.pdb2rdf.util;

import org.xml.sax.InputSource;

/**
 * @author Alexander De Leon
 */
public class Pdb2RdfInputIteratorAdapter implements Pdb2RdfInputIterator {

	private final InputInterator inputInterator;

	public Pdb2RdfInputIteratorAdapter(InputInterator inputInterator) {
		this.inputInterator = inputInterator;
	}

	@Override
	public boolean hasNext() {
		return inputInterator.hasNext();
	}

	@Override
	public InputSource next() {
		return new InputSource(inputInterator.next());
	}

	@Override
	public void remove() {
		inputInterator.remove();
	}

	@Override
	public int size() {
		return inputInterator.size();
	}

}
