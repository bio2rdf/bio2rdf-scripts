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

import org.junit.Test;

import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.ResourceFactory;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.XSD;

/**
 * @author Alexander De Leon
 */
public class JenaExample {

	private static final String NAMESPACE = "http://example.org/";

	@Test
	public void example() {
		// create an RDF model
		Model rdf = ModelFactory.createDefaultModel();

		// Add a namespace prefix
		rdf.setNsPrefix("ex", NAMESPACE);

		// Create two resource: a and b
		Resource a = rdf.createResource(NAMESPACE + "a");
		Resource b = rdf.createResource(NAMESPACE + "b");

		// Add the triple <a, rdf:type, b>
		rdf.add(a, RDF.type, b);

		// Add the triple <a, c, 3^^xsd:integer>
		rdf.add(a, MyOntology.c, rdf.createTypedLiteral("3", XSD.integer.getURI()));
		// or you can also use:
		int anInt = 4;
		rdf.add(a, MyOntology.c, rdf.createTypedLiteral(anInt));

		// print the RDF in RDF/XML
		rdf.write(System.out, "RDF/XML");

	}

	/**
	 * An class containg static definitions of ontological entities.
	 */
	static class MyOntology {
		public static Property c = ResourceFactory.createProperty(NAMESPACE + "c");
	}
}
