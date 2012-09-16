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
package com.dumontierlab.pdb2rdf.tools.rdf;

import java.text.MessageFormat;
import java.util.HashSet;
import java.util.Set;

import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.query.QueryExecution;
import com.hp.hpl.jena.query.QueryExecutionFactory;
import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.ResultSet;
import com.hp.hpl.jena.sparql.engine.http.QueryExceptionHTTP;
import com.hp.hpl.jena.vocabulary.OWL;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;

/**
 * @author Alexander De Leon
 */
public class Bio2rdfLinkCounter {

	private static final String ENDPOINT = "http://quebec.pdb.bio2rdf.org/sparql";

	public static void main(String[] args) {
		// countPubmedLinks();
		// countFoafLinks();
		// countTargetDbLinks();
		countNcbiTaxonLinks();
		// countPlasmidLinks();
		// countATCCLinks();
		// countDoiLinks();
		// countMedlineLinks();
		// countCSDLinks();
		// countCSDJournalsLinks();
	}

	private static void countCSDJournalsLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("_:e <" + RDF.type + "> <" + PdbOwlVocabulary.Class.Journal.uri() + "> . ");
		query.append("_:e <" + RDFS.seeAlso + "> ?p . ");
		query.append("filter regex(?p, \"^" + uriBuilder.buildUri(Bio2RdfPdbUriPattern.CSD, "") + "\") .} ");

		System.out.println("csd: " + count(query.toString()));
	}

	private static void countCSDLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("_:e <" + RDF.type + "> <" + PdbOwlVocabulary.Class.Experiment.uri() + "> . ");
		query.append("_:e <" + RDFS.seeAlso + "> ?p . ");
		query.append("filter regex(?p, \"^" + uriBuilder.buildUri(Bio2RdfPdbUriPattern.CSD, "") + "\") .} ");

		System.out.println("csd: " + count(query.toString()));
	}

	private static void countATCCLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("_:t <" + RDF.type + "> _:tt . ");
		query.append("_:tt <" + RDFS.subClassOf + "> <" + PdbOwlVocabulary.Class.Tissue + "> . ");
		query.append("_:t <" + RDFS.seeAlso + "> ?p . ");
		query.append("filter regex(?p, \"^"
				+ uriBuilder.buildUri(Bio2RdfPdbUriPattern.AMERICAN_TYPE_CULTURE_COLLECTION, "") + "\") .} ");

		System.out.println("atcc_dna: " + count(query.toString()));

	}

	private static void countPlasmidLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("?p <" + RDFS.subClassOf + "> <" + PdbOwlVocabulary.Class.Plasmid + "> . ");
		query.append("filter regex(?p, \"^" + uriBuilder.buildUri(Bio2RdfPdbUriPattern.PLASMID_ID, "") + "\") .} ");

		System.out.println("plasmid: " + count(query.toString()));

	}

	private static void countNcbiTaxonLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("_:a <" + RDF.type + "> ?p . ");
		query.append("filter regex(?p, \"^" + uriBuilder.buildUri(Bio2RdfPdbUriPattern.NCBI_TAXONOMY, "") + "\") .} ");

		System.out.println("taxon: " + count(query.toString()));

	}

	private static void countTargetDbLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("_:a <" + RDF.type + "> <" + PdbOwlVocabulary.Class.PolymerSequence.uri() + "> . ");
		query.append("_:a <" + RDFS.seeAlso + "> ?p . ");
		query.append("filter regex(?p, \"^" + uriBuilder.buildUri(Bio2RdfPdbUriPattern.TARGET_DB, "") + "\") .} ");

		System.out.println("targetdb: " + count(query.toString()));

	}

	private static void countFoafLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("_:a <" + RDF.type + "> <" + PdbOwlVocabulary.Class.Publication + "> . ");
		query.append("_:a <" + PdbOwlVocabulary.ObjectProperty.hasAuthor.uri() + "> _:b. ");
		query.append("_:b <" + OWL.sameAs + "> ?p . ");
		query.append("filter regex(?p, \"^" + uriBuilder.buildUri(Bio2RdfPdbUriPattern.FOAF_AUTHOR, "") + "\") .} ");

		System.out.println("foaf: " + count(query.toString()));

	}

	private static void countPubmedLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("_:a <" + RDF.type + "> <" + PdbOwlVocabulary.Class.Publication + "> . ");
		query.append("_:a <" + OWL.sameAs + "> ?p . ");
		query.append("filter regex(?p, \"^" + uriBuilder.buildUri(Bio2RdfPdbUriPattern.PUBMED, "") + "\") .} ");

		System.out.println("pubmed: " + count(query.toString()));
	}

	private static void countDoiLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("_:a <" + RDF.type + "> <" + PdbOwlVocabulary.Class.Publication + "> . ");
		query.append("_:a <" + OWL.sameAs + "> ?p . ");
		query.append("filter regex(?p, \"^" + uriBuilder.buildUri(Bio2RdfPdbUriPattern.DOI, "") + "\") .} ");

		System.out.println("doi: " + count(query.toString()));
	}

	private static void countMedlineLinks() {
		UriBuilder uriBuilder = new UriBuilder();
		StringBuilder query = new StringBuilder("select ?p where{ ");
		query.append("_:a <" + RDF.type + "> <" + PdbOwlVocabulary.Class.Publication + "> . ");
		query.append("_:a <" + OWL.sameAs + "> ?p . ");
		query.append("filter regex(?p, \"^" + uriBuilder.buildUri(Bio2RdfPdbUriPattern.MEDLINE, "") + "\") .} ");

		System.out.println("medline: " + count(query.toString()));
	}

	private static int count(String query) {
		int offset = 0;
		int limit = 10000;
		boolean done = false;
		Set<String> resources = new HashSet<String>();

		while (!done) {
			QueryExecution execution = QueryExecutionFactory.sparqlService(ENDPOINT,
					query + MessageFormat.format(" OFFSET {0} LIMIT " + limit, "" + offset));
			ResultSet resultSet = null;
			for (int i = 0; i < 5; i++) {
				try {
					resultSet = execution.execSelect();
					break;
				} catch (QueryExceptionHTTP e) {
					System.err.println("Http error: retrying " + i);
				}
			}
			if (resultSet == null) {
				System.err.println("Counted" + resources.size() + " resources");
				throw new RuntimeException("Unable to execute query");
			}
			if (!resultSet.hasNext()) {
				done = true;
			} else {
				while (resultSet.hasNext()) {
					QuerySolution solution = resultSet.next();
					resources.add(solution.getResource("p").getURI());
				}
			}
			offset += limit;
		}
		return resources.size();
	}
}
