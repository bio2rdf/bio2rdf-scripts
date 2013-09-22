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
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.DCTerms;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;
import com.hp.hpl.jena.vocabulary.XSD;

/**
 * @author Alexander De Leon
 */
public class EntityCategoryHandler extends ContentHandlerState {

	private final String pdbId;
	private String entityId;

	public EntityCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.ENTITY.equals(localName)) {
			entityId = attributes.getValue(PdbXmlVocabulary.ID_ATT);
			createEntity();
		} else if (PdbXmlVocabulary.DETAILS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.DESCRIPTION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FORMULA_WEIGHT.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.EXPERIMENTAL_FORMULA_WEIGHT.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.EXPERIMENTAL_FORMULA_WEIGHT_METHOD.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.MODIFICATION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.MUTATION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CHEMICAL_AMOUNT.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.TYPE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.SOURCE_METHOD.equals(localName) && !isNil(attributes)) {
			startBuffering();
		}
		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (PdbXmlVocabulary.TYPE.equals(localName) && isBuffering()) {
			createEntityType(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.DETAILS.equals(localName) && isBuffering()) {
			createDetails(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.DESCRIPTION.equals(localName) && isBuffering()) {
			createDescription(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.FORMULA_WEIGHT.equals(localName) && isBuffering()) {
			createFormulaWeight(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.EXPERIMENTAL_FORMULA_WEIGHT.equals(localName) && isBuffering()) {
			createExperimentalFormulaWeight(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.EXPERIMENTAL_FORMULA_WEIGHT_METHOD.equals(localName) && isBuffering()) {
			createExperimentalFormulaWeightMethod(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.MODIFICATION.equals(localName) && isBuffering()) {
			createModification(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.MUTATION.equals(localName) && isBuffering()) {
			createMutation(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.CHEMICAL_AMOUNT.equals(localName) && isBuffering()) {
			createChemicalAmount(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.SOURCE_METHOD.equals(localName) && isBuffering()) {
			createSourceMethod(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}

	private void createEntity() {
		Resource experimentResource = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.EXPERIMENT, pdbId));
		Resource entity = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId));
		Resource entityExtraction = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE_EXTRACTION, pdbId, entityId));

		getRdfModel().add(entityExtraction, RDF.type, PdbOwlVocabulary.Class.ChemicalSubstanceExtraction.resource());
		getRdfModel().add(experimentResource, PdbOwlVocabulary.ObjectProperty.hasPart.property(), entityExtraction);

		getRdfModel().add(entityExtraction, PdbOwlVocabulary.ObjectProperty.hasProduct.property(), entity);

		Resource structureDetermination = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.STRUCTURE_DETERMINATION, pdbId));
		getRdfModel().add(entity, PdbOwlVocabulary.ObjectProperty.isParticipantIn.property(),
				structureDetermination);
	}

	private void createEntityType(String type) {
		Resource entity = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId));
		if (PdbXmlVocabulary.ENTITY_TYPE_POLYMER_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.Polymer.resource());
		} else if (PdbXmlVocabulary.ENTITY_TYPE_NON_POLYMER_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.NonPolymer.resource());
		} else if (PdbXmlVocabulary.ENTITY_TYPE_WATER_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.Water.resource());
		} else if (PdbXmlVocabulary.ENTITY_TYPE_MACROLIDE_VALUE.equals(type)) {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.Macrolide.resource());
		} else {
			getRdfModel().add(entity, RDF.type, PdbOwlVocabulary.Class.ChemicalSubstance.resource());
		}
	}

	private void createDetails(String details) {
		Resource entity = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId));
		getRdfModel().add(entity, PdbOwlVocabulary.Annotation.details.property(), details);
	}

	private void createDescription(String description) {
		Resource entity = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId));
		getRdfModel().add(entity, PdbOwlVocabulary.Annotation.description.property(), description);
		getRdfModel().add(entity, RDFS.label, description);
	}

	private void createFormulaWeight(String weight) {
		Resource entity = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId));
		Resource formulaWeight = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.THEORETICAL_FORMULA_WEIGHT, pdbId, entityId));
		getRdfModel().add(entity, PdbOwlVocabulary.ObjectProperty.hasTheoreticalFormulaWeight.property(), formulaWeight);
		getRdfModel().add(formulaWeight, RDF.type, PdbOwlVocabulary.Class.TheoreticalFormulaWeight.resource());
		getRdfModel().add(formulaWeight, PdbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(weight, XSD.decimal.getURI()));
	}

	private void createExperimentalFormulaWeight(String weight) {
		Resource entity = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId));
		Resource experimentalFormulaWeight = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.EXPERIMANTAL_FORMULA_WEIGHT, pdbId, entityId));
		getRdfModel().add(entity, PdbOwlVocabulary.ObjectProperty.hasExperimentalFormulaWeight.property(),
				experimentalFormulaWeight);
		getRdfModel().add(experimentalFormulaWeight, RDF.type,
				PdbOwlVocabulary.Class.ExperimentalFormulaWeight.resource());
		getRdfModel().add(experimentalFormulaWeight, PdbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(weight, XSD.decimal.getURI()));

	}

	private void createExperimentalFormulaWeightMethod(String method) {
		Resource experimentalFormulaWeight = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.EXPERIMANTAL_FORMULA_WEIGHT, pdbId, entityId));
		getRdfModel().add(experimentalFormulaWeight, PdbOwlVocabulary.Annotation.experimentalMethod.property(), method);
	}

	private void createMutation(String mutation) {
		Resource entity = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId));
		getRdfModel().add(entity, PdbOwlVocabulary.Annotation.mutation.property(), mutation);
	}

	private void createModification(String modification) {
		Resource entity = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId));
		getRdfModel().add(entity, PdbOwlVocabulary.Annotation.modification.property(), modification);
	}

	private void createChemicalAmount(String amount) {
		Resource entity = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId, entityId));
		Resource chemicalAmount = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_AMOUNT, pdbId, entityId));
		getRdfModel().add(entity, PdbOwlVocabulary.ObjectProperty.hasChemicalSubstanceAmount.property(), chemicalAmount);
		getRdfModel().add(chemicalAmount, RDF.type, PdbOwlVocabulary.Class.ChemicalSubstanceAmount.resource());
		getRdfModel().add(chemicalAmount, PdbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(amount, XSD.decimal.getURI()));
	}

	private void createSourceMethod(String method) {
		Resource entityExtraction = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE_EXTRACTION, pdbId, entityId));
		if (PdbXmlVocabulary.ENTITY_SOURCE_METHOD_NAT_VALUE.equals(method)) {
			getRdfModel().add(entityExtraction, RDF.type,
					PdbOwlVocabulary.Class.NaturalChemicalSubstanceExtraction.resource());
		} else if (PdbXmlVocabulary.ENTITY_SOURCE_METHOD_SYN_VALUE.equals(method)) {
			getRdfModel().add(entityExtraction, RDF.type,
					PdbOwlVocabulary.Class.SynthecticChemicalSubstanceExtraction.resource());
		} else if (PdbXmlVocabulary.ENTITY_SOURCE_METHOD_MAN_VALUE.equals(method)) {
			getRdfModel().add(entityExtraction, RDF.type,
					PdbOwlVocabulary.Class.GeneticallyManipulatedChemicalSubstanceExtraction.resource());
		}
	}

}
