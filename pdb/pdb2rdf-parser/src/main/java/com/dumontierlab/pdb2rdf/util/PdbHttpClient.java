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
import java.net.URL;
import java.text.MessageFormat;
import java.util.zip.GZIPInputStream;

import org.xml.sax.InputSource;

/**
 * @author Alexander De Leon
 */
public class PdbHttpClient {

	private static final String PDB_XML_URL = "http://www.rcsb.org/pdb/files/{0}.xml.gz";

	public InputSource getPdbXml(String pdbId) throws IOException {
		URL xmlDocUrl = new URL(MessageFormat.format(PDB_XML_URL, pdbId));
		InputSource input = new InputSource(new GZIPInputStream(xmlDocUrl.openStream()));

		return input;
	}
}
