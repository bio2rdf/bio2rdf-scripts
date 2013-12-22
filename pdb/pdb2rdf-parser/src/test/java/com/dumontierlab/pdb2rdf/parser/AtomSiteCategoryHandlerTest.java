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
import java.util.Collection;
import java.util.HashMap;

import org.junit.Test;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Resource;

/**
 * @author Alexander De Leon
 */
public class AtomSiteCategoryHandlerTest extends AbstractPdbXmlParserTest {

	private static final String TEST_PDB_ID = "1Y26";
	private static final String TEST_FILE = "1Y26_atomSiteCategory.xml";

	@Test
	public void testRdf() throws IOException, SAXException {
		Model model = ModelFactory.createDefaultModel();
		parseTestFile(TEST_FILE, model);

		// you can do some asserts here

		// now just print it
		model.write(System.out, "RDF/XML");

	}

	@Override
	protected ContentHandlerState getContentHandler(PdbRdfModel model) {
		return new AtomSiteCategoryHandler(model, new UriBuilder(), TEST_PDB_ID,
				new HashMap<String, Collection<Resource>>());
	}
}
