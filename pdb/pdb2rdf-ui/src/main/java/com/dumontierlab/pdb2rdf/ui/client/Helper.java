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
package com.dumontierlab.pdb2rdf.ui.client;

import java.util.Collection;
import java.util.HashMap;
import java.util.Map;

import com.dumontierlab.capac.rink.client.QuerySolution;
import com.dumontierlab.capac.rink.client.ResultSet;
import com.dumontierlab.capac.rink.client.RinkConfiguration;
import com.dumontierlab.capac.rink.client.SparqlEndpoint;
import com.dumontierlab.capac.rink.client.SparqlEndpointFactory;
import com.dumontierlab.capac.rink.client.rdf.AnnotatedResource;
import com.dumontierlab.capac.rink.client.rdf.RDF;
import com.dumontierlab.capac.rink.client.rdf.RDFS;
import com.dumontierlab.pdb2rdf.ui.client.model.ChemicalSubstance;
import com.dumontierlab.pdb2rdf.ui.client.model.Model;
import com.dumontierlab.pdb2rdf.ui.client.model.Publication;
import com.google.gwt.user.client.rpc.AsyncCallback;

/**
 * @author Alexander De Leon
 */
public class Helper {

	private static Helper instance;

	public static Helper getInstance() {
		if (instance == null) {
			instance = new Helper();
		}
		return instance;
	}

	private final SparqlEndpoint endpoint;

	private Helper() {
		RinkConfiguration config = RinkConfiguration.getInstance();
		endpoint = SparqlEndpointFactory.create(config.getServer(), config.getServerUrl());
	}

	public void getTitle(String pdbId, final AsyncCallback<String> callback) {
		endpoint.executeSelectQuery("select ?title where { <http://bio2rdf.org/pdb:" + pdbId
				+ "> <http://purl.org/dc/elements/1.1/title> ?title }", new AsyncCallback<ResultSet>() {

			public void onSuccess(ResultSet result) {
				QuerySolution solution = result.next(); // expecting one
				// result
				// only
				callback.onSuccess(solution.getLiteral("title").getValue());
			}

			public void onFailure(Throwable caught) {
				callback.onFailure(caught);
			}
		});
	}

	public void getRdf(String pdbId, AsyncCallback<String> callback) {
		endpoint.executeConstructQuery(
				"PREFIX dc: <http://purl.org/dc/terms/> " + "PREFIX pdb: <http://bio2rdf.org/pdb:> " + "CONSTRUCT { "
						+ "?atom a ?atomType. " + "?atom  pdb:hasSpatialLocation ?atom_sl. "
						+ "?atom_sl pdb:hasXCoordinate ?xcoord. " + "?atom_sl pdb:hasYCoordinate ?ycoord. "
						+ "?atom_sl pdb:hasZCoordinate ?zcoord. " + "?xcoord pdb:hasValue ?xval. "
						+ "?ycoord pdb:hasValue ?yval. " + "?zcoord pdb:hasValue ?zval. " + "}WHERE{ "
						+ "<http://bio2rdf.org/pdb:" + pdbId + "> pdb:hasPart " + "?structure_deter. "
						+ "?structure_deter pdb:hasProduct ?model. " + "?model pdb:hasPart ?atom_sl. "
						+ "?atom pdb:hasSpatialLocation ?atom_sl. ?atom a ?atomType. "
						+ "?atom_sl pdb:hasXCoordinate ?xcoord. " + "?atom_sl pdb:hasYCoordinate ?ycoord. "
						+ "?atom_sl pdb:hasZCoordinate ?zcoord. " + "?xcoord pdb:hasValue ?xval. "
						+ "?ycoord pdb:hasValue ?yval. " + "?zcoord pdb:hasValue ?zval. " + "}", callback);
	}

	public void getChemicalSubstances(String pdbId, final AsyncCallback<Collection<ChemicalSubstance>> callback) {
		endpoint
				.executeSelectQuery(
						"select ?cs ?type ?label ?formula ?amount where { <http://bio2rdf.org/pdb:"
								+ pdbId
								+ "> <http://bio2rdf.org/pdb:hasPart> ?ex . ?ex <"
								+ RDF.type
								+ "> <http://bio2rdf.org/pdb:ChemicalSubstanceExtraction> . ?ex <http://bio2rdf.org/pdb:hasProduct> ?cs. ?cs <"
								+ RDF.type
								+ "> ?type . ?cs <"
								+ RDFS.label
								+ "> ?label . optional { ?cs <http://bio2rdf.org/pdb:hasChemicalFormula> _:fr . _:fr <http://bio2rdf.org/pdb:hasValue> ?formula } . optional { ?cs <http://bio2rdf.org/pdb:hasChemicalSubstanceAmount> _:ar . _:ar <http://bio2rdf.org/pdb:hasValue> ?amount }}",
						new AsyncCallback<ResultSet>() {
							public void onFailure(Throwable caught) {
								callback.onFailure(caught);
							}

							public void onSuccess(ResultSet result) {
								Map<String, ChemicalSubstance> chemicalSubstances = new HashMap<String, ChemicalSubstance>();
								while (result.hasNext()) {
									QuerySolution solution = result.next();
									String uri = solution.getResource("cs").getUri();
									ChemicalSubstance cs = chemicalSubstances.get(uri);
									if (cs == null) {
										cs = new ChemicalSubstance(uri);
										chemicalSubstances.put(uri, cs);
									}
									cs.addType(solution.getResource("type"));
									cs.setLabel(solution.getLiteral("label").getValue());
									if (solution.contains("formula")) {
										cs.setFormula(solution.getLiteral("formula").getValue());
									}
									if (solution.contains("amount")) {
										cs.setAmount(solution.getLiteral("amount").getValue());
									}
								}
								callback.onSuccess(chemicalSubstances.values());
							}
						});
	}

	public void getPublications(String pdbId, final AsyncCallback<Collection<Publication>> callback) {
		endpoint.executeSelectQuery("select ?pub ?label  where { <http://bio2rdf.org/pdb:" + pdbId
				+ "> <http://bio2rdf.org/pdb:hasPublication> ?pub . ?pub <" + RDF.type
				+ "> <http://bio2rdf.org/pdb:Publication> . ?pub <" + RDFS.label + "> ?label }",
				new AsyncCallback<ResultSet>() {
					public void onFailure(Throwable caught) {
						callback.onFailure(caught);
					}

					public void onSuccess(ResultSet result) {
						Map<String, Publication> publications = new HashMap<String, Publication>();
						while (result.hasNext()) {
							QuerySolution solution = result.next();
							String uri = solution.getResource("pub").getUri();
							Publication pub = publications.get(uri);
							if (pub == null) {
								pub = new Publication(uri);
								publications.put(uri, pub);
							}
							pub.setLabel(solution.getLiteral("label").getValue());

						}
						callback.onSuccess(publications.values());
					}
				});
	}

	public void getAuthors(String pdbId, String publicationUri,
			final AsyncCallback<Collection<AnnotatedResource>> callback) {
		endpoint.executeSelectQuery("select ?author ?label  where { <" + publicationUri
				+ "> <http://bio2rdf.org/pdb:hasAuthor> ?author. ?author <" + RDFS.label + "> ?label }",
				new AsyncCallback<ResultSet>() {
					public void onFailure(Throwable caught) {
						callback.onFailure(caught);
					}

					public void onSuccess(ResultSet result) {
						Map<String, AnnotatedResource> authors = new HashMap<String, AnnotatedResource>();
						while (result.hasNext()) {
							QuerySolution solution = result.next();
							String uri = solution.getResource("author").getUri();
							AnnotatedResource author = authors.get(uri);
							if (author == null) {
								author = new AnnotatedResource(uri);
								authors.put(uri, author);
							}
							author.setLabel(solution.getLiteral("label").getValue());

						}
						callback.onSuccess(authors.values());
					}
				});
	}

	public void getModels(String pdbId, final AsyncCallback<Collection<Model>> callback) {
		endpoint.executeSelectQuery("select ?model ?label  ?features where { <http://bio2rdf.org/pdb:" + pdbId
				+ "/structureDetermination> <http://bio2rdf.org/pdb:hasProduct> ?model . ?model <" + RDF.type
				+ "> <http://bio2rdf.org/pdb:Model> . ?model <" + RDFS.label
				+ "> ?label . optional { ?model <http://bio2rdf.org/mcannotate:isParticipantIn> ?features . ?features <"
				+ RDF.type + "> <http://bio2rdf.org/mcannotate:NucleicAcidStructureFeatureDetermination> }}",
				new AsyncCallback<ResultSet>() {
					public void onFailure(Throwable caught) {
						callback.onFailure(caught);
					}

					public void onSuccess(ResultSet result) {
						Map<String, Model> models = new HashMap<String, Model>();
						while (result.hasNext()) {
							QuerySolution solution = result.next();
							String uri = solution.getResource("model").getUri();
							Model model = models.get(uri);
							if (model == null) {
								model = new Model(uri);
								models.put(uri, model);
							}
							model.setLabel(solution.getLiteral("label").getValue());
							if (solution.contains("features")) {
								model.setFeatures(solution.getResource("features"));
							}

						}
						callback.onSuccess(models.values());
					}
				});
	}

	public void getStructureDetermination(final String pdbId, final AsyncCallback<AnnotatedResource> callback) {
		endpoint.executeSelectQuery("select  ?label  ?type where { optional { <http://bio2rdf.org/pdb:" + pdbId
				+ "/structureDetermination> <" + RDFS.label + "> ?label }. optional { <http://bio2rdf.org/pdb:" + pdbId
				+ "/structureDetermination>  <" + RDF.type + "> ?type } }", new AsyncCallback<ResultSet>() {
			public void onFailure(Throwable caught) {
				callback.onFailure(caught);
			}

			public void onSuccess(ResultSet result) {
				AnnotatedResource structureDetermination = new AnnotatedResource("http://bio2rdf.org/pdb:" + pdbId
						+ "/structureDetermination");
				while (result.hasNext()) {
					QuerySolution solution = result.next();
					if (solution.contains("label")) {
						structureDetermination.setLabel(solution.getLiteral("label").getValue());
					}
					if (solution.contains("type")) {
						structureDetermination.addType(solution.getResource("type"));
					}
				}
				callback.onSuccess(structureDetermination);
			}
		});
	}

	private String getGraphUri(String pdbId) {
		return "http://bio2rdf.org/graph/pdb:" + pdbId;
	}

}
