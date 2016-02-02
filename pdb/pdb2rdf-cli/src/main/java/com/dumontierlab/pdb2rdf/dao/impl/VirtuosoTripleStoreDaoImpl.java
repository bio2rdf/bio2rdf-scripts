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
package com.dumontierlab.pdb2rdf.dao.impl;

import java.io.PrintWriter;
import java.io.StringWriter;

import org.apache.log4j.Logger;

import virtuoso.jena.driver.VirtGraph;
import virtuoso.jena.driver.VirtuosoUpdateFactory;
import virtuoso.jena.driver.VirtuosoUpdateRequest;

import com.dumontierlab.pdb2rdf.dao.DaoException;
import com.dumontierlab.pdb2rdf.dao.TripleStoreDao;
import com.dumontierlab.pdb2rdf.util.ProgressMonitor;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.rdf.model.StmtIterator;
import com.hp.hpl.jena.rdf.model.impl.NTripleWriter;

/**
 * @author Alexander De Leon
 */
public class VirtuosoTripleStoreDaoImpl implements TripleStoreDao {

	private static final Logger LOG = Logger.getLogger(VirtuosoTripleStoreDaoImpl.class);

	private final VirtGraph virtGraph;

	public VirtuosoTripleStoreDaoImpl(VirtGraph virtGraph) {
		this.virtGraph = virtGraph;
	}

	public void clearGraph(String graphUri) throws DaoException {
		try {
			String sparql = "CLEAR GRAPH <" + graphUri + ">";
			VirtuosoUpdateRequest vqe = VirtuosoUpdateFactory.create(sparql, virtGraph);
			vqe.exec();
		} catch (Exception e) {
			throw new DaoException(e);
		} finally {
			virtGraph.close();
		}

	}

	public void insert(Model rdf, String graphUri) throws DaoException {
		insert(rdf, graphUri, null);
	}

	public void insert(Model rdf, String graphUri, ProgressMonitor progressMonitor) throws DaoException {
		int total = (int) rdf.size();
		String sparql = null;
		try {
			int counter = 0;
			for (StmtIterator i = rdf.listStatements(); i.hasNext();) {
				Statement stmt = i.nextStatement();
				sparql = "INSERT INTO GRAPH <" + graphUri + "> { " + NTripleStamentWriter.writeStament(stmt) + " }";
				if (LOG.isDebugEnabled()) {
					LOG.debug(sparql);
				}
				VirtuosoUpdateRequest vqe = VirtuosoUpdateFactory.create(sparql, virtGraph);
				vqe.exec();
				if (progressMonitor != null) {
					progressMonitor.setProgress(++counter, total);
				}
			}
		} catch (Exception e) {
			throw new DaoException(sparql, e);
		} finally {
			virtGraph.close();
		}
	}

	private static class NTripleStamentWriter extends NTripleWriter {
		public static String writeStament(Statement stmt) {
			StringWriter strWriter = new StringWriter();
			PrintWriter pw = new PrintWriter(strWriter);
			writeResource(stmt.getSubject(), pw);
			pw.print(" ");
			writeResource(stmt.getPredicate(), pw);
			pw.print(" ");
			writeNode(stmt.getObject(), pw);
			pw.println(" .");

			return strWriter.toString();
		}
	}
}
