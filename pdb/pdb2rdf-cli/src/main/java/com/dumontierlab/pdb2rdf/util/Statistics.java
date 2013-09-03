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

import java.util.HashMap;
import java.util.Map;

import com.hp.hpl.jena.query.QueryExecution;
import com.hp.hpl.jena.query.QueryExecutionFactory;
import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.ResultSet;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.vocabulary.RDF;

/**
 * @author Alexander De Leon
 */
public class Statistics {

	public Map<String, Double> getStatistics(Model model) {
		Map<String, Double> stats = new HashMap<String, Double>();
		mergeStats(getClassCounts(model), stats);
		// mergeStats(getRelationshipCounts(model), stats);
		stats.put("TotalNumberOfTriples", getNumberOfTriples(model));
		return stats;
	}

	public Map<String, Double> getRelationshipCounts(Model model) {
		QueryExecution execution = QueryExecutionFactory.create("select ?c ?t where { ?s <" + RDF.type + "> ?c . ?o <"
				+ RDF.type + "> ?t. ?s ?p ?o. }", model);

		ResultSet result = execution.execSelect();
		Map<String, Double> stats = new HashMap<String, Double>();
		while (result.hasNext()) {
			QuerySolution solution = result.next();
			String rel = getRelationshipPair(solution.getResource("c").getURI(), solution.getResource("t").getURI());
			if (stats.containsKey(rel)) {
				stats.put(rel, stats.get(rel) + 1);
			} else {
				stats.put(rel, 1d);
			}
		}
		return stats;
	}

	public Map<String, Double> getClassCounts(Model model) {
		QueryExecution execution = QueryExecutionFactory
				.create("select ?c ?i where { ?i <" + RDF.type + "> ?c }", model);
		ResultSet result = execution.execSelect();
		Map<String, Double> stats = new HashMap<String, Double>();
		while (result.hasNext()) {
			QuerySolution solution = result.next();
			String className = solution.getResource("c").getURI();
			if (stats.containsKey(className)) {
				stats.put(className, stats.get(className) + 1);
			} else {
				stats.put(className, 1d);
			}
		}
		return stats;
	}

	public double getNumberOfTriples(Model model) {
		return model.size();
	}

	public String getRelationshipPair(String uri1, String uri2) {
		return "(" + uri1 + "," + uri2 + ")";
	}

	public Map<String, Double> mergeStats(Map<String, Double> source, Map<String, Double> target) {
		for (Map.Entry<String, Double> stat : source.entrySet()) {
			String key = stat.getKey();
			if (target.containsKey(key)) {
				target.put(key, new Double(target.get(key).doubleValue() + stat.getValue().doubleValue()));
			} else {
				target.put(key, stat.getValue());
			}
		}
		return target;
	}
}
