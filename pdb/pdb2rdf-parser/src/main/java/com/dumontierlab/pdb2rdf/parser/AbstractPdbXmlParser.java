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
package com.dumontierlab.pdb2rdf.parser;

import java.io.IOException;

import org.xml.sax.InputSource;
import org.xml.sax.SAXException;
import org.xml.sax.XMLReader;
import org.xml.sax.helpers.XMLReaderFactory;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.util.PdbHttpClient;

/**
 * @author Alexander De Leon
 */
public abstract class AbstractPdbXmlParser {

	public PdbRdfModel parse(String pdbId) throws IOException, SAXException {
		return parse(pdbId, new PdbRdfModel());
	}

	public PdbRdfModel parse(String pdbId, DetailLevel detailLevel) throws IOException, SAXException {
		return parse(pdbId, new PdbRdfModel(), detailLevel);
	}

	public PdbRdfModel parse(String pdbId, PdbRdfModel model) throws IOException, SAXException {
		PdbHttpClient client = new PdbHttpClient();
		return parse(client.getPdbXml(pdbId), model, null);
	}

	public PdbRdfModel parse(String pdbId, PdbRdfModel model, DetailLevel detailLevel) throws IOException, SAXException {
		PdbHttpClient client = new PdbHttpClient();
		return parse(client.getPdbXml(pdbId), model, detailLevel);
	}

	public PdbRdfModel parse(InputSource input) throws IOException, SAXException {
		return parse(input, new PdbRdfModel(), null);
	}

	public PdbRdfModel parse(InputSource input, DetailLevel detailLevel) throws IOException, SAXException {
		return parse(input, new PdbRdfModel(), detailLevel);
	}

	public PdbRdfModel parse(InputSource input, PdbRdfModel model) throws IOException, SAXException {
		return parse(input, model, null);
	}

	public PdbRdfModel parse(InputSource input, PdbRdfModel model, DetailLevel detailLevel) throws IOException,
			SAXException {

		XMLReader parser = XMLReaderFactory.createXMLReader();
		ContentHandlerState handler = getContentHandler(model);
		if (detailLevel != null) {
			handler.setDetailLevel(detailLevel);
		}
		parser.setContentHandler(handler);
		parser.parse(input);

		return handler.getRdfModel();
	}

	protected abstract ContentHandlerState getContentHandler(PdbRdfModel model);
}
