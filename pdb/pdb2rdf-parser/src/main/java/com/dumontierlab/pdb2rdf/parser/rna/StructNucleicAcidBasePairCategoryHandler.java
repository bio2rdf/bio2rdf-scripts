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
package com.dumontierlab.pdb2rdf.parser.rna;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.ContentHandlerState;
import com.dumontierlab.pdb2rdf.parser.rna.vocabulary.LeontisWesthofClassification;
import com.dumontierlab.pdb2rdf.parser.rna.vocabulary.RnaKbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.rna.vocabulary.SangerClassification;
import com.dumontierlab.pdb2rdf.parser.rna.vocabulary.uri.RnaKbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.XSD;

/**
 * @author Alexander De Leon
 */
public class StructNucleicAcidBasePairCategoryHandler extends ContentHandlerState {

	private final String pdbId;
	private String modelNumber;
	private String iChain;
	private String iSequenceNum;
	private String jChain;
	private String jSequenceNum;

	private String buckle;
	private String hbound12Type;
	private String hbound28Type;

	private Resource pair;

	public StructNucleicAcidBasePairCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.NDB_STRUCT_NUCLIC_ACID_BASE_PAIR.equals(localName)) {
			modelNumber = attributes.getValue(PdbXmlVocabulary.MODEL_NUMBER_ATT);
		} else if (PdbXmlVocabulary.BUCKLE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HBOND_TYPE_12.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HBOND_TYPE_28.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.I_AUTH_ASYM_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.I_AUTH_SEQ_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.J_AUTH_ASYM_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.J_AUTH_SEQ_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.OPENING.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PROPELLER.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.SHEAR.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.STAGGER.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.STRETCH.equals(localName) && !isNil(attributes)) {
			startBuffering();
		}
		super.startElement(uri, localName, name, attributes);

	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (PdbXmlVocabulary.NDB_STRUCT_NUCLIC_ACID_BASE_PAIR.equals(localName)) {
			clear();
		} else if (PdbXmlVocabulary.BUCKLE.equals(localName) && isBuffering()) {
			buckle = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.HBOND_TYPE_12.equals(localName) && isBuffering()) {
			hbound12Type = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.HBOND_TYPE_28.equals(localName) && isBuffering()) {
			hbound28Type = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.I_AUTH_ASYM_ID.equals(localName) && isBuffering()) {
			iChain = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.I_AUTH_SEQ_ID.equals(localName) && isBuffering()) {
			iSequenceNum = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.J_AUTH_ASYM_ID.equals(localName) && isBuffering()) {
			jChain = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.J_AUTH_SEQ_ID.equals(localName) && isBuffering()) {
			jSequenceNum = getBufferContent();
			stopBuffering();
			if (buckle != null) {
				createBuckle();
			}
			if (hbound12Type != null) {
				createLeontisWesthofType();
			}
			if (hbound28Type != null) {
				createSangerType();
			}
		} else if (PdbXmlVocabulary.OPENING.equals(localName) && isBuffering()) {
			createOpening(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.PROPELLER.equals(localName) && isBuffering()) {
			createPropeller(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.SHEAR.equals(localName) && isBuffering()) {
			createShear(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.STAGGER.equals(localName) && isBuffering()) {
			createStagger(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.STRETCH.equals(localName) && isBuffering()) {
			createStretch(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}

	private void createLeontisWesthofType() {
		Resource pair = getPairResource();
		Resource type = LeontisWesthofClassification.get(Integer.parseInt(hbound12Type)).resource();
		getRdfModel().add(pair, RDF.type, type);
	}

	private void createSangerType() {
		Resource pair = getPairResource();
		Resource type = SangerClassification.get(Integer.parseInt(hbound28Type)).resource();
		getRdfModel().add(pair, RDF.type, type);
	}

	private void createBuckle() {
		Resource pair = getPairResource();
		Resource stretch = createResource(RnaKbUriPattern.BUCKLE, pdbId, modelNumber, iChain, iSequenceNum, jChain,
				jSequenceNum);
		getRdfModel().add(pair, RnaKbOwlVocabulary.ObjectProperty.hasQuality.property(), stretch);
		getRdfModel().add(stretch, RDF.type, RnaKbOwlVocabulary.Class.Buckle.resource());
		getRdfModel().add(stretch, RnaKbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(buckle, XSD.decimal.getURI()));

	}

	private void createStretch(String value) {
		Resource pair = getPairResource();
		Resource stretch = createResource(RnaKbUriPattern.STRETCH, pdbId, modelNumber, iChain, iSequenceNum, jChain,
				jSequenceNum);
		getRdfModel().add(pair, RnaKbOwlVocabulary.ObjectProperty.hasQuality.property(), stretch);
		getRdfModel().add(stretch, RDF.type, RnaKbOwlVocabulary.Class.Stretch.resource());
		getRdfModel().add(stretch, RnaKbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(value, XSD.decimal.getURI()));
	}

	private void createStagger(String value) {
		Resource pair = getPairResource();
		Resource stagger = createResource(RnaKbUriPattern.STAGGER, pdbId, modelNumber, iChain, iSequenceNum, jChain,
				jSequenceNum);
		getRdfModel().add(pair, RnaKbOwlVocabulary.ObjectProperty.hasQuality.property(), stagger);
		getRdfModel().add(stagger, RDF.type, RnaKbOwlVocabulary.Class.Stagger.resource());
		getRdfModel().add(stagger, RnaKbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(value, XSD.decimal.getURI()));
	}

	private void createShear(String value) {
		Resource pair = getPairResource();
		Resource shear = createResource(RnaKbUriPattern.SHEAR, pdbId, modelNumber, iChain, iSequenceNum, jChain,
				jSequenceNum);
		getRdfModel().add(pair, RnaKbOwlVocabulary.ObjectProperty.hasQuality.property(), shear);
		getRdfModel().add(shear, RDF.type, RnaKbOwlVocabulary.Class.Shear.resource());
		getRdfModel().add(shear, RnaKbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(value, XSD.decimal.getURI()));
	}

	private void createPropeller(String propellerValue) {
		Resource pair = getPairResource();
		Resource propeller = createResource(RnaKbUriPattern.PROPELLER, pdbId, modelNumber, iChain, iSequenceNum, jChain,
				jSequenceNum);
		getRdfModel().add(pair, RnaKbOwlVocabulary.ObjectProperty.hasQuality.property(), propeller);
		getRdfModel().add(propeller, RDF.type, RnaKbOwlVocabulary.Class.Propeller.resource());
		getRdfModel().add(propeller, RnaKbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(propellerValue, XSD.decimal.getURI()));
	}

	private void createOpening(String openingValue) {
		Resource pair = getPairResource();
		Resource opening = createResource(RnaKbUriPattern.OPENING, pdbId, modelNumber, iChain, iSequenceNum, jChain,
				jSequenceNum);
		getRdfModel().add(pair, RnaKbOwlVocabulary.ObjectProperty.hasQuality.property(), opening);
		getRdfModel().add(opening, RDF.type, RnaKbOwlVocabulary.Class.Opening.resource());
		getRdfModel().add(opening, RnaKbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(openingValue, XSD.decimal.getURI()));
	}

	private Resource getPairResource() {
		if (pair == null) {
			pair = createResource(RnaKbUriPattern.BASE_PAIR, pdbId, modelNumber, iChain, iSequenceNum, jChain,
					jSequenceNum);
		}
		return pair;
	}
}
