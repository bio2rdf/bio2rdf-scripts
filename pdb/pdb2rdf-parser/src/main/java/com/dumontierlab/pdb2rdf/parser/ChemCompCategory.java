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

import java.util.Collection;
import java.util.Map;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.dumontierlab.pdb2rdf.util.UriUtil;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;

/**
 * @author Alexander De Leon
 */
public class ChemCompCategory extends ContentHandlerState {

	private final String pdbId;
	private String componentId;
	private Resource componentType;
	private final Map<String, Collection<Resource>> residues;

	public ChemCompCategory(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId,
			Map<String, Collection<Resource>> residues) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
		this.residues = residues;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.CHEMICAL_COMPONENT.equals(localName)) {
			componentId = attributes.getValue(PdbXmlVocabulary.ID_ATT);
		} else if (PdbXmlVocabulary.FORMULA_WEIGHT.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.NAME.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.NUMBER_OF_ATOMS_ALL.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.NUMBER_OF_NON_HYDROGEN_ATOMS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FORMULA.equals(localName) && !isNil(attributes)) {
			startBuffering();
		}
		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (PdbXmlVocabulary.CHEMICAL_COMPONENT.equals(localName)) {
			clear();
		} else if (PdbXmlVocabulary.FORMULA_WEIGHT.equals(localName) && isBuffering()) {
			createFormulaWeight(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.NAME.equals(localName) && isBuffering()) {
			createResidueType(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.NUMBER_OF_ATOMS_ALL.equals(localName) && isBuffering()) {
			createNumberOfAtoms(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.NUMBER_OF_NON_HYDROGEN_ATOMS.equals(localName) && isBuffering()) {
			createNumberOfNonHydrogenAtoms(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.FORMULA.equals(localName) && isBuffering()) {
			createFormula(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}

	private void createFormula(String formula) {
		if (!residues.containsKey(componentId)) {
			return;
		}
		for (Resource residue : residues.get(componentId)) {
			Resource formulaQuality = createResource(Bio2RdfPdbUriPattern.RESIDUE_FORMULA, residue.getURI());
			getRdfModel().add(residue, PdbOwlVocabulary.ObjectProperty.hasChemicalFormula.property(), formulaQuality);
			getRdfModel().add(formulaQuality, RDF.type, PdbOwlVocabulary.Class.ChemicalFormula.resource());
			getRdfModel().add(formulaQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), formula);
		}
	}

	private void createNumberOfNonHydrogenAtoms(String numberOfAtoms) {
		if (!residues.containsKey(componentId)) {
			return;
		}
		for (Resource residue : residues.get(componentId)) {
			Resource numberOfAtomsQuality = createResource(Bio2RdfPdbUriPattern.RESIDUE_NUMBER_OF_NON_HYDROGEN_ATOMS,
					residue.getURI());
			getRdfModel().add(residue, PdbOwlVocabulary.ObjectProperty.hasNumberOfNonHydrogenAtoms.property(),
					numberOfAtomsQuality);
			getRdfModel().add(numberOfAtomsQuality, RDF.type,
					PdbOwlVocabulary.Class.NumberOfNonHydrogenAtoms.resource());
			getRdfModel().add(numberOfAtomsQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
					createDecimalLiteral(numberOfAtoms));
		}

	}

	private void createNumberOfAtoms(String numberOfAtoms) {
		if (!residues.containsKey(componentId)) {
			return;
		}
		for (Resource residue : residues.get(componentId)) {
			Resource numberOfAtomsQuality = createResource(Bio2RdfPdbUriPattern.RESIDUE_NUMBER_OF_ATOMS,
					residue.getURI());
			getRdfModel().add(residue, PdbOwlVocabulary.ObjectProperty.hasNumberOfAtoms.property(),
					numberOfAtomsQuality);
			getRdfModel().add(numberOfAtomsQuality, RDF.type, PdbOwlVocabulary.Class.NumberOfAtoms.resource());
			getRdfModel().add(numberOfAtomsQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
					createDecimalLiteral(numberOfAtoms));
		}

	}

	private void createResidueType(String typeName) {
		if (!residues.containsKey(componentId)) {
			return;
		}

		componentType = createResource(Bio2RdfPdbUriPattern.RESIDUE_TYPE, UriUtil.hash(typeName));

		for (Resource residue : residues.get(componentId)) {
			getRdfModel().add(residue, RDF.type, componentType);
			// getRdfModel().add(componentType, RDF.type, OWL.Class);
			getRdfModel().add(residue, RDFS.label, typeName);
		}
	}

	private void createFormulaWeight(String formulaWeight) {
		if (!residues.containsKey(componentId)) {
			return;
		}
		for (Resource residue : residues.get(componentId)) {
			Resource formulaWeightQuality = createResource(Bio2RdfPdbUriPattern.RESIDUE_FORMULA_WEIGH, residue.getURI());
			getRdfModel().add(residue, PdbOwlVocabulary.ObjectProperty.hasTheoreticalFormulaWeight.property(),
					formulaWeightQuality);
			getRdfModel().add(formulaWeightQuality, RDF.type,
					PdbOwlVocabulary.Class.TheoreticalFormulaWeight.resource());
			getRdfModel().add(formulaWeightQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
					createDecimalLiteral(formulaWeight));
		}
	}

}
