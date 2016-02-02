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
import com.dumontierlab.pdb2rdf.parser.vocabulary.AbsortCorrectionTypes;
import com.dumontierlab.pdb2rdf.parser.vocabulary.ExperimentalMethods;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.DCTerms;
import com.hp.hpl.jena.vocabulary.RDF;

/**
 * @author Alexander De Leon
 */
public class ExptlCategoryHandler extends ContentHandlerState {

	private final String pdbId;

	private Resource structureDeterminationResource;
	private Resource absorptCorrectionsResource;

	public ExptlCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.EXPTL)) {
			String method = attributes.getValue(PdbXmlVocabulary.METHOD_ATT);
			createExperimentalMethod(method);
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_COEFFICIENT_MU) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_CORRECTION_T_MAX) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_CORRECTION_T_MIN) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_CORRECTION_TYPE) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_PROCESS_DETAILS) && !isNil(attributes)) {
			startBuffering();
		}
		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.EXPTL)) {
			clear();
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_COEFFICIENT_MU) && isBuffering()) {
			createAbsortCoefficientMu(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_CORRECTION_T_MAX) && isBuffering()) {
			createAbsortCorrectionTMax(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_CORRECTION_T_MIN) && isBuffering()) {
			createAbsortCorrectionTMin(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_CORRECTION_TYPE) && isBuffering()) {
			createAbsortCorrectionType(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.ABSORT_PROCESS_DETAILS) && isBuffering()) {
			createAbsortDetails(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}

	private void createAbsortDetails(String details) {
		getRdfModel().add(getAbsorptCorrectionResource(), PdbOwlVocabulary.Annotation.details.property(), details);
	}

	private void createAbsortCorrectionType(String type) {
		if (!type.equals("none")) {
			Resource classResource = AbsortCorrectionTypes.get(type).resource();
			getRdfModel().add(getAbsorptCorrectionResource(), RDF.type, classResource);
		}

	}

	private void createAbsortCorrectionTMin(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.ABSORT_CORRECTION_T_MIN, pdbId);
		getRdfModel().add(getAbsorptCorrectionResource(),
				PdbOwlVocabulary.ObjectProperty.hasMinimumTransmissionFactor.property(), quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.MinimumTransmissionFactor.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
	}

	private void createAbsortCorrectionTMax(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.ABSORT_CORRECTION_T_MAX, pdbId);
		getRdfModel().add(getAbsorptCorrectionResource(),
				PdbOwlVocabulary.ObjectProperty.hasMaximumTransmissionFactor.property(), quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.MaximumTransmissionFactor.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
	}

	private void createAbsortCoefficientMu(String value) {
		Resource quality = createResource(Bio2RdfPdbUriPattern.ABSORT_COEFFICIENT_MU, pdbId);
		getRdfModel().add(getAbsorptCorrectionResource(), PdbOwlVocabulary.ObjectProperty.hasCoefficientMu.property(),
				quality);
		getRdfModel().add(quality, RDF.type, PdbOwlVocabulary.Class.CoefficientMu.resource());
		getRdfModel().add(quality, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
	}

	private void createExperimentalMethod(String method) {
		if (method.equalsIgnoreCase("other")) {
			return;
		}
		PdbOwlVocabulary.Class type = ExperimentalMethods.get(method);
		Resource structureDetermination = getStructureDeterminationResource();
		getRdfModel().add(structureDetermination, RDF.type, type.resource());
	}

	private Resource getStructureDeterminationResource() {
		if (structureDeterminationResource == null) {
			structureDeterminationResource = createResource(Bio2RdfPdbUriPattern.STRUCTURE_DETERMINATION, pdbId);
		}
		return structureDeterminationResource;
	}

	public Resource getAbsorptCorrectionResource() {
		if (absorptCorrectionsResource == null) {
			absorptCorrectionsResource = createResource(Bio2RdfPdbUriPattern.ABSORPTION_CORRECTION, pdbId);
			getRdfModel().add(getStructureDeterminationResource(), PdbOwlVocabulary.ObjectProperty.hasPart.property(), absorptCorrectionsResource);
		}
		return absorptCorrectionsResource;
	}
}
