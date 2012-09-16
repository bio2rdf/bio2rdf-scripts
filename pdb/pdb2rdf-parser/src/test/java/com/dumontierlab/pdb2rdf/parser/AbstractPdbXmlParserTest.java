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

import java.io.IOException;

import org.xml.sax.InputSource;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.hp.hpl.jena.rdf.model.Model;

/**
 * @author Alexander De Leon
 */
public abstract class AbstractPdbXmlParserTest {

	public void parseTestFile(String fileName, Model model) throws IOException, SAXException {
		InnerParser parser = new InnerParser();
		parser.parse(new InputSource(getClass().getResourceAsStream("/" + fileName)), new PdbRdfModel(model));
	}

	protected abstract ContentHandlerState getContentHandler(PdbRdfModel model);

	private class InnerParser extends AbstractPdbXmlParser {

		@Override
		protected ContentHandlerState getContentHandler(PdbRdfModel model) {
			return AbstractPdbXmlParserTest.this.getContentHandler(model);
		}

	}

}
