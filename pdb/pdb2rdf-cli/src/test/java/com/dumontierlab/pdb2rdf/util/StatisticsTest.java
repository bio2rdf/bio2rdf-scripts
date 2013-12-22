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

import java.util.Map;

import org.junit.Assert;
import org.junit.Test;

import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.RDF;

/**
 * @author Alexander De Leon
 */
public class StatisticsTest {

	@Test
	public void testClassCount() {
		Model model = ModelFactory.createDefaultModel();

		model.add(model.createResource("http://exp.com/a"), RDF.type, PdbOwlVocabulary.Class.StructureDetermination
				.resource());

		Statistics statistics = new Statistics();
		Map<String, Double> stats = statistics.getClassCounts(model);
		Assert.assertTrue(stats.containsKey(PdbOwlVocabulary.Class.StructureDetermination.uri()));
		Assert.assertEquals(1d, stats.get(PdbOwlVocabulary.Class.StructureDetermination.uri()), 0);
	}

	@Test
	public void testRelationshipCount() {
		Model model = ModelFactory.createDefaultModel();

		Resource a = model.createResource("http://exp.com/a");
		Resource b = model.createResource("http://exp.com/b");
		Property c = model.createProperty("http://exp.com/c");

		model.add(a, RDF.type, PdbOwlVocabulary.Class.StructureDetermination.resource());
		model.add(b, RDF.type, PdbOwlVocabulary.Class.Model.resource());
		model.add(a, c, b);

		Statistics statistics = new Statistics();
		Map<String, Double> stats = statistics.getRelationshipCounts(model);

		String rel = statistics.getRelationshipPair(PdbOwlVocabulary.Class.StructureDetermination.uri(),
				PdbOwlVocabulary.Class.Model.uri());
		Assert.assertTrue(stats.containsKey(rel));
		Assert.assertEquals(1d, stats.get(rel), 0);
	}
}
