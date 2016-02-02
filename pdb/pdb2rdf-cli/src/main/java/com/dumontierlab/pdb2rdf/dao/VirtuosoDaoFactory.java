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
package com.dumontierlab.pdb2rdf.dao;

import virtuoso.jena.driver.VirtGraph;

import com.dumontierlab.pdb2rdf.dao.impl.VirtuosoTripleStoreDaoImpl;

/**
 * @author Alexander De Leon
 */
public class VirtuosoDaoFactory implements VirtGraphFactory {

	private final String virtuosoUrl;
	private final String username;
	private final String password;

	public VirtuosoDaoFactory(String host, int port, String username, String password) {
		virtuosoUrl = "jdbc:virtuoso://" + host + ":" + port;
		this.username = username;
		this.password = password;
	}

	public TripleStoreDao getTripleStoreDao() {
		return new VirtuosoTripleStoreDaoImpl(getVirtuosoGraph());
	}

	public VirtGraph getVirtuosoGraph() {
		return new VirtGraph(virtuosoUrl, username, password);
	}

	@Override
	public VirtGraph getVirtuosoGraph(String graphUri) {
		return new VirtGraph(graphUri, virtuosoUrl, username, password);
	}
}
