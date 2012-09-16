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
package com.dumontierlab.pdb2rdf.gae;

import java.io.IOException;
import java.io.OutputStream;

import org.restlet.data.MediaType;
import org.restlet.representation.OutputRepresentation;
import org.restlet.representation.Representation;
import org.restlet.resource.Get;
import org.restlet.resource.ResourceException;
import org.restlet.resource.ServerResource;

import com.dumontierlab.pdb2rdf.parser.PdbXmlParser;
import com.hp.hpl.jena.rdf.model.Model;

/**
 * @author Alexander De Leon
 */
public class PdbResource extends ServerResource {

	@Get
	public Representation toRdf() throws ResourceException {
		final String pdbId = (String) getRequestAttributes().get("pdbid");
		PdbXmlParser parser = new PdbXmlParser();
		try {
			final Model rdf = parser.parse(pdbId);
			OutputRepresentation representation = new OutputRepresentation(MediaType.APPLICATION_RDF_XML) {
				@Override
				public void write(OutputStream outputStream) throws IOException {
					rdf.write(outputStream);
				}
			};
			return representation;
		} catch (Exception e) {
			throw new ResourceException(500, e);
		}
	}
}
