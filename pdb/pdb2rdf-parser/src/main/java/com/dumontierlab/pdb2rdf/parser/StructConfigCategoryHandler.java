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
import com.dumontierlab.pdb2rdf.parser.vocabulary.SecondaryStructures;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.XSD;

/**
 * @author Alexander De Leon
 */
public class StructConfigCategoryHandler extends ContentHandlerState {

	private final String pdbId;
	private String structId;
	private String startChainId;
	private String startChainPosition;
	private String endChainId;
	private String endChainPosition;

	private Resource startResidueResource;
	private Resource endResidueResource;
	private Resource secondaryStructureResource;

	public StructConfigCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.STRUCT_CONF.equals(localName)) {
			structId = attributes.getValue(PdbXmlVocabulary.ID_ATT);
		} else if (PdbXmlVocabulary.BEG_AUTH_ASYM_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.BEG_AUTH_SEQ_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CONF_TYPE_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.DETAILS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.END_AUTH_ASYM_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.END_AUTH_SEQ_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PDBX_PDB_HELIX_LENGTH.equals(localName) && !isNil(attributes)) {
			startBuffering();
		}
		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (PdbXmlVocabulary.STRUCT_CONF.equals(localName)) {
			clear();
		} else if (PdbXmlVocabulary.BEG_AUTH_ASYM_ID.equals(localName) && isBuffering()) {
			startChainId = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.BEG_AUTH_SEQ_ID.equals(localName) && isBuffering()) {
			startChainPosition = getBufferContent();
			createBegin();
			stopBuffering();
		} else if (PdbXmlVocabulary.CONF_TYPE_ID.equals(localName) && isBuffering()) {
			createType(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.DETAILS.equals(localName) && isBuffering()) {
			createDetails(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.END_AUTH_ASYM_ID.equals(localName) && isBuffering()) {
			endChainId = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.END_AUTH_SEQ_ID.equals(localName) && isBuffering()) {
			endChainPosition = getBufferContent();
			createEnd();
			stopBuffering();
		} else if (PdbXmlVocabulary.PDBX_PDB_HELIX_LENGTH.equals(localName) && isBuffering()) {
			createHelixLength(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}

	private void createHelixLength(String lenght) {
		Resource helixLengthQuality = createResource(Bio2RdfPdbUriPattern.HELIX_LENGTH, pdbId, structId);
		getRdfModel().add(getSecondaryStructureResource(), PdbOwlVocabulary.ObjectProperty.hasHelixLength.property(),
				helixLengthQuality);
		getRdfModel().add(helixLengthQuality, RDF.type, PdbOwlVocabulary.Class.HelixLength.resource());
		getRdfModel().add(helixLengthQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createLiteral(lenght, XSD.integer.getURI()));

	}

	private void createType(String typeId) {
		getRdfModel().add(getSecondaryStructureResource(), RDF.type, SecondaryStructures.get(typeId).resource());
	}

	private void createEnd() {
		getRdfModel().add(getSecondaryStructureResource(), PdbOwlVocabulary.ObjectProperty.endsAt.property(),
				getEndResidue());
	}

	private void createDetails(String details) {
		getRdfModel().add(getSecondaryStructureResource(), PdbOwlVocabulary.Annotation.details.property(), details);
	}

	private void createBegin() {
		getRdfModel().add(getSecondaryStructureResource(), PdbOwlVocabulary.ObjectProperty.beginsAt.property(),
				getStartResidue());
	}

	private Resource getStartResidue() {
		assert startChainId != null && startChainPosition != null : "The chain name and position is needed for creating this resource";
		if (startResidueResource == null) {
			startResidueResource = createResource(Bio2RdfPdbUriPattern.RESIDUE, pdbId, startChainId, startChainPosition);

		}
		return startResidueResource;
	}

	private Resource getEndResidue() {
		assert endChainId != null && endChainPosition != null : "The chain name and position is needed for creating this resource";
		if (endResidueResource == null) {
			endResidueResource = createResource(Bio2RdfPdbUriPattern.RESIDUE, pdbId, endChainId, endChainPosition);

		}
		return endResidueResource;
	}

	private Resource getSecondaryStructureResource() {
		if (secondaryStructureResource == null) {
			secondaryStructureResource = createResource(Bio2RdfPdbUriPattern.SECONDARY_STRUCTURE, pdbId, structId);
		}
		return secondaryStructureResource;
	}

}
