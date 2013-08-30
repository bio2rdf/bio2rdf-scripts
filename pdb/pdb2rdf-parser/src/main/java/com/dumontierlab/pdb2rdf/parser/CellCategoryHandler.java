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
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.RDF;

/**
 * @author Alexander De Leon
 */
public class CellCategoryHandler extends ContentHandlerState {

	private final String pdbId;

	private Resource unitCellResource;

	public CellCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.ANGLE_ALPHA) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_ALPHA_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_BETA) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_BETA_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_GAMMA) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_GAMMA_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.DETAILS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_A) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_A_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_B) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_B_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_C) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_C_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_ALPHA) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_ALPHA_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_BETA) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_BETA_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_GAMMA) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_GAMMA_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_GAMMA) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_GAMMA_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_A) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_A_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_B) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_B_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_C) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_C_ESD) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.VOLUME) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.VOLUME_ESD) && !isNil(attributes)) {
			startBuffering();
		}
		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.ANGLE_ALPHA) && isBuffering()) {
			createAngleAlpha(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_ALPHA_ESD) && isBuffering()) {
			createAngleAlphaEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_BETA) && isBuffering()) {
			createAngleBeta(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_BETA_ESD) && isBuffering()) {
			createAngleBetaEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_GAMMA) && isBuffering()) {
			createAngleGamma(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ANGLE_GAMMA_ESD) && isBuffering()) {
			createAngleGammaEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.DETAILS) && isBuffering()) {
			createDetails(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_A) && isBuffering()) {
			createLengthA(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_A_ESD) && isBuffering()) {
			createLengthAEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_B) && isBuffering()) {
			createLengthB(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_B_ESD) && isBuffering()) {
			createLengthBEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_C) && isBuffering()) {
			createLengthC(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.LENGTH_C_ESD) && isBuffering()) {
			createLengthCEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_ALPHA) && isBuffering()) {
			createReciprocalAngleAlpha(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_ALPHA_ESD) && isBuffering()) {
			createReciprocalAngleAlphaEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_BETA) && isBuffering()) {
			createReciprocalAngleBeta(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_BETA_ESD) && isBuffering()) {
			createReciprocalAngleBetaEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_GAMMA) && isBuffering()) {
			createReciprocalAngleGamma(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_ANGLE_GAMMA_ESD) && isBuffering()) {
			createReciprocalAngleGammaEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_A) && isBuffering()) {
			createReciprocalLengthA(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_A_ESD) && isBuffering()) {
			createReciprocalLengthAEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_B) && isBuffering()) {
			createReciprocalLengthB(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_B_ESD) && isBuffering()) {
			createReciprocalLengthBEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_C) && isBuffering()) {
			createReciprocalLengthC(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.RECIPROCAL_LENGTH_C_ESD) && isBuffering()) {
			createReciprocalLengthCEsd(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.VOLUME) && isBuffering()) {
			createVolume(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.VOLUME_ESD) && isBuffering()) {
			createVolumeEsd(getBufferContent());
			stopBuffering();
		}

		super.endElement(uri, localName, name);
	}

	private void createVolumeEsd(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.UNIT_CELL_VOLUME, pdbId);
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(value));
	}

	private void createVolume(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.UNIT_CELL_VOLUME, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasVolume.property(), quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.Volume.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
	}

	private void createReciprocalLengthCEsd(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_EDGE_C_LENGTH, pdbId);
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(value));
	}

	private void createReciprocalLengthC(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_EDGE_C_LENGTH, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasReciprocalEdgeCLength.property(),
				quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.ReciprocalEdgeCLength.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));

	}

	private void createReciprocalLengthBEsd(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_EDGE_B_LENGTH, pdbId);
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(value));
	}

	private void createReciprocalLengthB(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_EDGE_B_LENGTH, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasReciprocalEdgeBLength.property(),
				quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.ReciprocalEdgeBLength.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));

	}

	private void createReciprocalLengthAEsd(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_EDGE_A_LENGTH, pdbId);
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(value));
	}

	private void createReciprocalLengthA(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_EDGE_A_LENGTH, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasReciprocalEdgeALength.property(),
				quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.ReciprocalEdgeALength.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));

	}

	private void createReciprocalAngleGammaEsd(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_ANGLE_GAMMA, pdbId);
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(value));
	}

	private void createReciprocalAngleGamma(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_ANGLE_GAMMA, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasReciprocalAngleGamma.property(),
				quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.ReciprocalAngleGamma.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));

	}

	private void createReciprocalAngleBetaEsd(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_ANGLE_BETA, pdbId);
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(value));
	}

	private void createReciprocalAngleBeta(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_ANGLE_BETA, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasReciprocalAngleBeta.property(),
				quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.ReciprocalAngleBeta.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));

	}

	private void createReciprocalAngleAlphaEsd(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_ANGLE_ALPHA, pdbId);
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(value));
	}

	private void createReciprocalAngleAlpha(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.RECIPROCAL_ANGLE_ALPHA, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasReciprocalAngleAlpha.property(),
				quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.ReciprocalAngleAlpha.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));

	}

	private void createLengthCEsd(String esd) {
		Resource lengthCQuality = createResource(Bio2RdfPdbUriPattern.EDGE_C_LENGTH, pdbId);
		getRdfModel().add(lengthCQuality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(esd));
	}

	private void createLengthC(String length) {
		Resource lengthCQuality = createResource(Bio2RdfPdbUriPattern.EDGE_C_LENGTH, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasEdgeCLength.property(),
				lengthCQuality);
		getRdfModel().add(lengthCQuality, RDF.type, PdbOwlVocabulary.Class.EdgeCLength.resource());
		getRdfModel().add(lengthCQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(length));
	}

	private void createLengthBEsd(String esd) {
		Resource lengthBQuality = createResource(Bio2RdfPdbUriPattern.EDGE_B_LENGTH, pdbId);
		getRdfModel().add(lengthBQuality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(esd));
	}

	private void createLengthB(String length) {
		Resource lengthBQuality = createResource(Bio2RdfPdbUriPattern.EDGE_B_LENGTH, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasEdgeBLength.property(),
				lengthBQuality);
		getRdfModel().add(lengthBQuality, RDF.type, PdbOwlVocabulary.Class.EdgeBLength.resource());
		getRdfModel().add(lengthBQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(length));

	}

	private void createLengthAEsd(String esd) {
		Resource lengthAQuality = createResource(Bio2RdfPdbUriPattern.EDGE_A_LENGTH, pdbId);
		getRdfModel().add(lengthAQuality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(esd));
	}

	private void createLengthA(String length) {
		Resource lengthAQuality = createResource(Bio2RdfPdbUriPattern.EDGE_A_LENGTH, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasEdgeALength.property(),
				lengthAQuality);
		getRdfModel().add(lengthAQuality, RDF.type, PdbOwlVocabulary.Class.EdgeALength.resource());
		getRdfModel().add(lengthAQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(length));
	}

	private void createDetails(String details) {
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.Annotation.details.property(), details);
	}

	private void createAngleGammaEsd(String esd) {
		Resource angleGammaQuality = createResource(Bio2RdfPdbUriPattern.ANGLE_GAMMA, pdbId);
		getRdfModel().add(angleGammaQuality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(esd));
	}

	private void createAngleGamma(String angleGamma) {
		Resource angleGammaQuality = createResource(Bio2RdfPdbUriPattern.ANGLE_GAMMA, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasAngleGamma.property(),
				angleGammaQuality);
		getRdfModel().add(angleGammaQuality, RDF.type, PdbOwlVocabulary.Class.AngleGamma.resource());
		getRdfModel().add(angleGammaQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(angleGamma));

	}

	private void createAngleBetaEsd(String esd) {
		Resource angleBetaQuality = createResource(Bio2RdfPdbUriPattern.ANGLE_BETA, pdbId);
		getRdfModel().add(angleBetaQuality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(esd));
	}

	private void createAngleBeta(String angleBeta) {
		Resource angleBetaQuality = createResource(Bio2RdfPdbUriPattern.ANGLE_BETA, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasAngleBeta.property(),
				angleBetaQuality);
		getRdfModel().add(angleBetaQuality, RDF.type, PdbOwlVocabulary.Class.AngleBeta.resource());
		getRdfModel().add(angleBetaQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(angleBeta));
	}

	private void createAngleAlphaEsd(String esd) {
		Resource angleAlphaQuality = createResource(Bio2RdfPdbUriPattern.ANGLE_ALPHA, pdbId);
		getRdfModel().add(angleAlphaQuality, PdbOwlVocabulary.DataProperty.hasStandardDeviation.property(),
				createDecimalLiteral(esd));
	}

	private void createAngleAlpha(String angleAlpha) {
		Resource angleAlphaQuality = createResource(Bio2RdfPdbUriPattern.ANGLE_ALPHA, pdbId);
		getRdfModel().add(getUnitCellResource(), PdbOwlVocabulary.ObjectProperty.hasAngleAlpha.property(),
				angleAlphaQuality);
		getRdfModel().add(angleAlphaQuality, RDF.type, PdbOwlVocabulary.Class.AngleAlpha.resource());
		getRdfModel().add(angleAlphaQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(angleAlpha));
	}

	private Resource getUnitCellResource() {
		if (unitCellResource == null) {
			unitCellResource = createResource(Bio2RdfPdbUriPattern.UNIT_CELL, pdbId);
			Resource crystal = createResource(Bio2RdfPdbUriPattern.CRYSTAL, pdbId);
			if (!getRdfModel().containsResource(crystal)) {
				Resource structureDetermination = createResource(Bio2RdfPdbUriPattern.STRUCTURE_DETERMINATION, pdbId);
				getRdfModel().add(structureDetermination, PdbOwlVocabulary.ObjectProperty.hasParticipant.property(),
						crystal);
			}
			getRdfModel().add(crystal, PdbOwlVocabulary.ObjectProperty.hasUnitCell.property(), unitCellResource);
		}
		return unitCellResource;
	}
}
