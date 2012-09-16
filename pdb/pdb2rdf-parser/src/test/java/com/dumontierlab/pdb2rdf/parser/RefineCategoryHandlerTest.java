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

import org.junit.Assert;
import org.junit.Test;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.query.QueryExecution;
import com.hp.hpl.jena.query.QueryExecutionFactory;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.vocabulary.RDF;

/**
 * @author Alexander De Leon
 */
public class RefineCategoryHandlerTest extends AbstractPdbXmlParserTest {

	private static final String TEST_PDB_ID = "1Y26";
	private static final String TEST_FILE = "1Y26_refineCategory.xml";

	private static final UriBuilder URI_BUILDER = new UriBuilder();

	@Test
	public void testRdf() throws IOException, SAXException {
		Model model = ModelFactory.createDefaultModel();
		parseTestFile(TEST_FILE, model);

		// you can do some asserts here

		// now just print it
		model.write(System.out, "RDF/XML");

	}

	@Test
	public void testRefinementType() throws IOException, SAXException {
		Model model = ModelFactory.createDefaultModel();
		parseTestFile(TEST_FILE, model);

		QueryExecution exec = QueryExecutionFactory.create(
				"ASK { <" + URI_BUILDER.buildUri(Bio2RdfPdbUriPattern.REFINEMENT, TEST_PDB_ID) + "> <" + RDF.type
						+ ">  <" + PdbOwlVocabulary.Class.Refinement.uri() + ">}", model);
		Assert.assertTrue(exec.execAsk());
	}

	@Override
	protected ContentHandlerState getContentHandler(PdbRdfModel model) {
		return new RefineCategoryHandler(model, new UriBuilder(), TEST_PDB_ID);
	}

}
