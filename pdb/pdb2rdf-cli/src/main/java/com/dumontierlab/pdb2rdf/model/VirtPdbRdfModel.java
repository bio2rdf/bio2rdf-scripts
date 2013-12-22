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
package com.dumontierlab.pdb2rdf.model;

import org.apache.log4j.Logger;

import virtuoso.jena.driver.VirtModel;

import com.dumontierlab.pdb2rdf.dao.DaoException;
import com.dumontierlab.pdb2rdf.dao.TripleStoreDao;
import com.dumontierlab.pdb2rdf.dao.VirtGraphFactory;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriPattern;

/**
 * @author Alexander De Leon
 */
public class VirtPdbRdfModel extends PdbRdfModel {

	private static final Logger LOG = Logger.getLogger(VirtPdbRdfModel.class);

	private final VirtGraphFactory factory;
	private final UriPattern graphNamePattern;
	private final UriBuilder uriBuilder;
	private final TripleStoreDao dao;

	public VirtPdbRdfModel(VirtGraphFactory factory, UriPattern graphNamePattern, UriBuilder uriBuilder,
			TripleStoreDao dao) {
		this.factory = factory;
		this.graphNamePattern = graphNamePattern;
		this.uriBuilder = uriBuilder;
		this.dao = dao;
	}

	@Override
	public void setPdbId(String pdbId) {
		String graphName = uriBuilder.buildUri(graphNamePattern, pdbId);
		try {
			dao.clearGraph(graphName);
		} catch (DaoException e) {
			LOG.error("Unable to clear the graph: " + graphName, e);
		}
		setModel(new VirtModel(factory.getVirtuosoGraph(graphName)));
		super.setPdbId(pdbId);
	}
}
