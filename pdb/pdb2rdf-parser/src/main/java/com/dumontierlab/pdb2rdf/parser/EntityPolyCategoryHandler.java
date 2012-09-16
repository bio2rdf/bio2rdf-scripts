package com.dumontierlab.pdb2rdf.parser;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;
import com.hp.hpl.jena.vocabulary.XSD;

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

/**
 * @author Alexander De Leon
 */
public class EntityPolyCategoryHandler extends ContentHandlerState {

	private final String pdbId;
	private String entityId;
	private boolean hasCanonicalSequence;

	private Resource entityResource;

	public EntityPolyCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.ENTITY_POLY.equals(localName)) {
			entityId = attributes.getValue(PdbXmlVocabulary.ENTITY_ID_ATT);
		} else if (PdbXmlVocabulary.NUMBER_OF_MONOMERS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ONE_LETTER_SEQUENCE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ONE_LETTER_SEQUENCE_CANONICAL.equals(localName) && !isNil(attributes)) {
			hasCanonicalSequence = true;
			startBuffering();
		} else if (PdbXmlVocabulary.TARGET_IDENTIFIER.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.TYPE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		}

		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (PdbXmlVocabulary.ENTITY_POLY.equals(localName)) {
			clear();
		} else if (PdbXmlVocabulary.NUMBER_OF_MONOMERS.equals(localName) && isBuffering()) {
			createNumberOfMonomers(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.ONE_LETTER_SEQUENCE.equals(localName) && isBuffering()) {
			createSequence(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.ONE_LETTER_SEQUENCE_CANONICAL.equals(localName) && isBuffering()) {
			createCanonicalSequence(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.TARGET_IDENTIFIER.equals(localName) && isBuffering()) {
			createTargetDbReference(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.TYPE.equals(localName) && isBuffering()) {
			createPolymerType(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}

	private void createPolymerType(String type) {
		Resource entity = getEntityResource();
		if (PdbXmlVocabulary.POLYMER_TYPE_POLYPEPTIDE_D_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.PolypeptideD.resource());
		} else if (PdbXmlVocabulary.POLYMER_TYPE_POLYPEPTIDE_L_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.PolypeptideL.resource());
		} else if (PdbXmlVocabulary.POLYMER_TYPE_POLYDEOXYRIBONUCLEOTIDE_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.Polydeoxyribonucleotide.resource());
		} else if (PdbXmlVocabulary.POLYMER_TYPE_POLYRIBONUCLEOTIDE_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.Polyribonucleotide.resource());
		} else if (PdbXmlVocabulary.POLYMER_TYPE_POLYSACCHARIDE_D_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.PolysaccharideD.resource());
		} else if (PdbXmlVocabulary.POLYMER_TYPE_POLYSACCHARIDE_L_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.PolysaccharideL.resource());
		} else if (PdbXmlVocabulary.POLYMER_TYPE_HYBRID_L_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type,
					PdbOwlVocabulary.Class.PolydeoxyribonucleotidePolyribonucleotide.resource());
		} else if (PdbXmlVocabulary.POLYMER_TYPE_CYCLIC_PSEUDO_PEPTIDE_L_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.CyclicPseudoPeptide.resource());
		}
	}

	private void createTargetDbReference(String targetDbId) {
		Resource polymerSequence = createResource(Bio2RdfPdbUriPattern.POLYMER_SEQUENCE, pdbId, entityId);
		Resource targetDb = createResource(Bio2RdfPdbUriPattern.TARGET_DB, targetDbId);
		getRdfModel().add(polymerSequence, RDFS.seeAlso, targetDb);
		if (hasCanonicalSequence) {
			Resource canonicalPolymerSequence = createResource(Bio2RdfPdbUriPattern.CANONICAL_POLYMER_SEQUENCE, pdbId,
					entityId);
			getRdfModel().add(canonicalPolymerSequence, RDFS.seeAlso, targetDb);
		}
	}

	private void createCanonicalSequence(String sequence) {
		sequence = sequence.replace("\n", "");
		Resource entity = getEntityResource();
		Resource polymerSequence = createResource(Bio2RdfPdbUriPattern.CANONICAL_POLYMER_SEQUENCE, pdbId, entityId);
		getRdfModel().add(entity, PdbOwlVocabulary.ObjectProperty.hasPolymerSequence.property(), polymerSequence);
		getRdfModel().add(polymerSequence, PdbOwlVocabulary.DataProperty.hasValue.property(), sequence);
		getRdfModel().add(polymerSequence, RDF.type, PdbOwlVocabulary.Class.CanonicalPolymerSequence.resource());
	}

	private void createSequence(String sequence) {
		sequence = sequence.replace("\n", "");
		Resource entity = getEntityResource();
		Resource polymerSequence = createResource(Bio2RdfPdbUriPattern.POLYMER_SEQUENCE, pdbId, entityId);
		getRdfModel().add(entity, PdbOwlVocabulary.ObjectProperty.hasPolymerSequence.property(), polymerSequence);
		getRdfModel().add(polymerSequence, PdbOwlVocabulary.DataProperty.hasValue.property(), sequence);
		getRdfModel().add(polymerSequence, RDF.type, PdbOwlVocabulary.Class.PolymerSequence.resource());
	}

	private void createNumberOfMonomers(String numberOfMonomers) {
		Resource entity = getEntityResource();
		Resource numberOfMonomersQuality = createResource(Bio2RdfPdbUriPattern.NUMBER_OF_MONOMERS, pdbId, entityId);
		getRdfModel().add(entity, PdbOwlVocabulary.ObjectProperty.hasNumberOfMonomers.property(),
				numberOfMonomersQuality);
		getRdfModel().add(numberOfMonomersQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(numberOfMonomers, XSD.integer.getURI()));
		getRdfModel().add(numberOfMonomersQuality, RDF.type, PdbOwlVocabulary.Class.NumberOfMonomers.resource());
	}

	private Resource getEntityResource() {
		if (entityResource == null) {
			entityResource = createResource(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId);
		}
		return entityResource;
	}

}
