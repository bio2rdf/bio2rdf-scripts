/**
 * Copyright (c) 2013 Dumontierlab
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

import java.io.IOException;
import java.lang.reflect.Field;
import java.lang.reflect.Modifier;

import org.xml.sax.Attributes;
import org.xml.sax.ContentHandler;
import org.xml.sax.InputSource;
import org.xml.sax.Locator;
import org.xml.sax.SAXException;
import org.xml.sax.SAXParseException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriPattern;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.XSD;
import com.hp.hpl.jena.rdf.model.Statement;

/**
 * @author Alexander De Leon
 * @author  Jose Cruz-Toledo
 */
public class ContentHandlerState implements ContentHandler {

	private ContentHandlerState state;
	private final PdbRdfModel rdfModel;
	private final UriBuilder uriBuilder;
	protected StringBuilder buffer;
	private DetailLevel detailLevel = DetailLevel.DEFAULT;

	public ContentHandlerState(PdbRdfModel rdfModel, UriBuilder uriBuilder) {
		this.uriBuilder = uriBuilder;
		this.rdfModel = rdfModel;
	}

	public void characters(char[] ch, int start, int length) throws SAXException {
		if (isBuffering()) {
			buffer.append(ch, start, length);
		}
		if (state != null) {
			state.characters(ch, start, length);
		}
	}

	public void endDocument() throws SAXException {
		if (state != null) {
			state.endDocument();
		}
	}

	public void endElement(String uri, String localName, String name) throws SAXException {
		if (state != null) {
			state.endElement(uri, localName, name);

		}
	}

	public void endPrefixMapping(String prefix) throws SAXException {
		if (state != null) {
			state.endPrefixMapping(prefix);
		}
	}

	public void error(SAXParseException e) throws SAXException {
		if (state != null) {
			state.error(e);
		}
	}

	public void fatalError(SAXParseException e) throws SAXException {
		if (state != null) {
			state.fatalError(e);
		}
	}

	public void ignorableWhitespace(char[] ch, int start, int length) throws SAXException {
		if (state != null) {
			state.ignorableWhitespace(ch, start, length);
		}
	}

	public void notationDecl(String name, String publicId, String systemId) throws SAXException {
		if (state != null) {
			state.notationDecl(name, publicId, systemId);
		}
	}

	public void processingInstruction(String target, String data) throws SAXException {
		if (state != null) {
			state.processingInstruction(target, data);
		}
	}

	public InputSource resolveEntity(String publicId, String systemId) throws IOException, SAXException {
		if (state != null) {
			return state.resolveEntity(publicId, systemId);
		}
		return null;
	}

	public void setDocumentLocator(Locator locator) {
		if (state != null) {
			state.setDocumentLocator(locator);
		}
	}

	public void skippedEntity(String name) throws SAXException {
		if (state != null) {
			state.skippedEntity(name);
		}
	}

	public void startDocument() throws SAXException {
		if (state != null) {
			state.startDocument();
		}
	}

	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (state != null) {
			state.startElement(uri, localName, name, attributes);
		}
	}

	public void startPrefixMapping(String prefix, String uri) throws SAXException {
		if (state != null) {
			state.startPrefixMapping(prefix, uri);
		}
	}

	public void unparsedEntityDecl(String name, String publicId, String systemId, String notationName)
			throws SAXException {
		if (state != null) {
			state.unparsedEntityDecl(name, publicId, systemId, notationName);
		}
	}

	public void warning(SAXParseException e) throws SAXException {
		if (state != null) {
			state.warning(e);
		}
	}

	public void setDetailLevel(DetailLevel detailLevel) {
		this.detailLevel = detailLevel;
	}

	public DetailLevel getDetailLevel() {
		return detailLevel;
	}

	protected void setState(ContentHandlerState state) {
		if (state != null) {
			state.setDetailLevel(getDetailLevel());
		}
		this.state = state;
	}

	protected PdbRdfModel getRdfModel() {
		return rdfModel;
	}

	protected UriBuilder getUriBuilder() {
		return uriBuilder;
	}

	protected void startBuffering() {
		buffer = new StringBuilder();
	}

	protected void stopBuffering() {
		buffer = null;
	}

	protected boolean isBuffering() {
		return buffer != null;
	}

	protected boolean isNil(Attributes attributes) {
		return attributes.getValue(PdbXmlVocabulary.NIL_ATT) != null;
	}

	protected Resource createResource(UriPattern pattern, String... params) {
		Resource rm = getRdfModel().createResource(getUriBuilder().buildUri(pattern, params));
		return rm;
	}

	protected String getBufferContent() {
		if (!isBuffering()) {
			return null;
		}
		return buffer.toString();
	}

	protected void clear() {
		for (Field field : this.getClass().getDeclaredFields()) {
			try {
				if (Modifier.isFinal(field.getModifiers())) {
					continue;
				}
				if (!Modifier.isPublic(field.getModifiers())) {
					field.setAccessible(true);
					resetField(field);
					field.setAccessible(false);
				} else {
					resetField(field);
				}
			} catch (Exception e) {
				throw new RuntimeException(e);
			}
		}
	}

	protected RDFNode createLiteral(String value, String xsdType) {
		return getRdfModel().createTypedLiteral(value, xsdType);
	}

	protected RDFNode createDecimalLiteral(String value) {
		return createLiteral(value, XSD.decimal.getURI());
	}

	private void resetField(Field field) throws IllegalArgumentException, IllegalAccessException {
		if (field.getType() == boolean.class) {
			field.set(this, false);
		} else if (!field.getType().isPrimitive()) {
			field.set(this, null);
		} else {
			field.set(this, 0);
		}
	}
}
