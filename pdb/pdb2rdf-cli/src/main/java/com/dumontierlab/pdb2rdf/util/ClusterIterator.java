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
import java.net.MalformedURLException;
import java.net.URL;

import org.apache.log4j.Logger;
import org.xml.sax.InputSource;

/**
 * @author Alexander De Leon
 */
public class ClusterIterator implements Pdb2RdfInputIterator {

	private static final Logger LOG = Logger.getLogger(ClusterIterator.class);

	private final String clusterUrl;
	private InputSource input = null;

	public ClusterIterator(String clusterUrl) {
		this.clusterUrl = clusterUrl;
	}

	@Override
	public int size() {
		return Integer.MAX_VALUE;
	}

	@Override
	public boolean hasNext() {
		getInput();
		return input != null;
	}

	private void getInput() {
		try {
			URL url = new URL(clusterUrl);
			input = new InputSource(url.openStream());
		} catch (MalformedURLException e) {
			LOG.error("Invalid cluster URL: " + clusterUrl, e);
		} catch (IOException e) {
			LOG.warn("Unable to connect to the cluster assuming that the input is finished");
			input = null;
		}
	}

	@Override
	public InputSource next() {
		return input;
	}

	@Override
	public void remove() {
		// empty
	}

}
