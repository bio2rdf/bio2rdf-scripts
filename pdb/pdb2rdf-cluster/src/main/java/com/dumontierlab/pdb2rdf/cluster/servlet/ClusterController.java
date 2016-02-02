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
package com.dumontierlab.pdb2rdf.cluster.servlet;

import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.concurrent.atomic.AtomicInteger;

import javax.servlet.ServletException;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.apache.log4j.Logger;

import com.dumontierlab.pdb2rdf.util.DirectoryIterator;

/**
 * @author Alexander De Leon
 */
public class ClusterController extends HttpServlet {

	private static final Logger LOG = Logger.getLogger(ClusterController.class);

	private final DirectoryIterator input;
	private final AtomicInteger requestCount;
	private volatile boolean shutingdown;
	private final Object monitor = new Object();

	public ClusterController(File inputDir, boolean gzip) throws IOException {
		input = new DirectoryIterator(inputDir, gzip);
		requestCount = new AtomicInteger();
	}

	@Override
	protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
		synchronized (monitor) {
			if (shutingdown) {
				resp.sendError(HttpServletResponse.SC_GONE);
				return;
			}
		}
		InputStream xmlStream = null;
		synchronized (monitor) {
			if (!input.hasNext()) {
				exit();
				resp.sendError(HttpServletResponse.SC_GONE);
				return;
			}
			LOG.info("Assigning input file to: " + req.getRemoteHost());
			requestCount.incrementAndGet();
			xmlStream = input.next();
		}
		resp.setContentType("text/xml");
		OutputStream out = resp.getOutputStream();
		byte[] buffer = new byte[1024];
		int read = -1;
		while ((read = xmlStream.read(buffer)) != -1) {
			out.write(buffer, 0, read);
			out.flush();
		}
		xmlStream.close();
		requestCount.decrementAndGet();
		synchronized (monitor) {
			monitor.notifyAll();
		}
	}

	private void exit() {
		if (shutingdown) {
			return;
		}
		shutingdown = true;
		while (requestCount.get() > 0) {
			LOG.info("Shuting down waiting for " + requestCount + " requests to complete.");
			try {
				monitor.wait();
			} catch (InterruptedException e) {
				LOG.warn("Servlet thread interrupted", e);
				break;
			}
		}
		LOG.info("All files have been delivered. Done!");

	}
}
