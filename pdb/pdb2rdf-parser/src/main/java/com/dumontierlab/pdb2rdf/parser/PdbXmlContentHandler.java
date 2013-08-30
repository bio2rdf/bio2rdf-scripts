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

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;

/**
 * @author Alexander De Leon
 */
public class PdbXmlContentHandler extends ContentHandlerState {

	private final boolean parseAtomSites;

	public PdbXmlContentHandler(PdbRdfModel model) {
		this(model, true);
	}

	public PdbXmlContentHandler(PdbRdfModel model, boolean parseAtomSites) {
		super(model, new UriBuilder());
		this.parseAtomSites = parseAtomSites;
		getRdfModel().setNsPrefix("dcterms", "http://purl.org/dc/terms/");
		getRdfModel().setNsPrefix("ss", PdbOwlVocabulary.VOCABULARY_NAMESPACE);
		getRdfModel().setNsPrefix("pdb", PdbOwlVocabulary.VOCABULARY_NAMESPACE);
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes atts) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.DATABLOCK)) {
			setState(new DataBlockHandler(getRdfModel(), getUriBuilder(), parseAtomSites));
		}
		super.startElement(uri, localName, name, atts);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.DATABLOCK)) {
			setState(null);
		}
		super.endElement(uri, localName, name);
	}

}
