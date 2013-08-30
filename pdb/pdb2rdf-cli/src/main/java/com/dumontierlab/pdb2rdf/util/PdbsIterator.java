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
package com.dumontierlab.pdb2rdf.util;

import java.io.IOException;

import org.apache.log4j.Logger;
import org.xml.sax.InputSource;

public class PdbsIterator implements Pdb2RdfInputIterator {

	private static final Logger LOG = Logger.getLogger(PdbsIterator.class);

	int counter;
	private final String[] pdbIds;
	private final PdbHttpClient client;

	public PdbsIterator(String[] pdbIds) {
		this.pdbIds = pdbIds;
		client = new PdbHttpClient();
	}

	public boolean hasNext() {
		return counter < size();
	}

	public InputSource next() {
		String pdbId = pdbIds[counter++];
		try {
			return client.getPdbXml(pdbId);
		} catch (IOException e) {
			LOG.error("Unable retrieve pdbId=" + pdbId + " from the Web.", e);
			if (!hasNext()) {
				System.exit(0);
			}
			return next();
		}
	}

	public void remove() {
		// do nothing
	}

	@Override
	public int size() {
		return pdbIds.length;
	}

}