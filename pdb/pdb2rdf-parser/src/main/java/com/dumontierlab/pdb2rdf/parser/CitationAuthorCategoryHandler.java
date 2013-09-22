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
package com.dumontierlab.pdb2rdf.parser;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.dumontierlab.pdb2rdf.util.UriUtil;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.OWL;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;

/**
 * @author Alexander De Leon
 */
public class CitationAuthorCategoryHandler extends ContentHandlerState {

	private final String pdbid;

	public CitationAuthorCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbid) {
		super(rdfModel, uriBuilder);
		this.pdbid = pdbid;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.CITATION_AUTHOR.equals(localName)) {
			String citationId = attributes.getValue(PdbXmlVocabulary.CITATION_ID_ATT);
			String authorName = attributes.getValue(PdbXmlVocabulary.NAME_ATT);
			String ordinal = attributes.getValue(PdbXmlVocabulary.ORDINAL_ATT);
			createAuthor(citationId, authorName, ordinal);
		}
		super.startElement(uri, localName, name, attributes);
	}

	private void createAuthor(String citationId, String authorName, String ordinal) {
		Resource publication = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.PUBLICATION, pdbid, citationId));
		Resource author = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.AUTHOR, pdbid, citationId, ordinal));

		Resource authorNameResource = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.AUTHOR_NAME, pdbid, citationId, ordinal));

		getRdfModel().add(publication, PdbOwlVocabulary.ObjectProperty.hasAuthor.property(), author);
		getRdfModel().add(author, PdbOwlVocabulary.ObjectProperty.hasName.property(), authorNameResource);
		getRdfModel().add(authorNameResource, PdbOwlVocabulary.DataProperty.hasValue.property(), authorName);
		getRdfModel().add(authorNameResource, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(author, RDFS.label, authorName);

		Resource foafAuthor = getRdfModel().createResource(
				getUriBuilder()
						.buildUri(Bio2RdfPdbUriPattern.FOAF_AUTHOR, UriUtil.replaceSpacesByUnderscore(authorName)));
		getRdfModel().add(author, OWL.sameAs, foafAuthor);

	}
}
