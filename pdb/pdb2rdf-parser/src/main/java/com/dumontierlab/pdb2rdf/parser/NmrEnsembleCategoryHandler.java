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

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.DCTerms;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;


/**
 * @author  Alex De Leon
 * @author  Jose Cruz-Toledo
 *
 */
public class NmrEnsembleCategoryHandler extends ContentHandlerState {
	private final String pdbId;
	private Resource ensemble;

	public NmrEnsembleCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.PDBX_NMR_ENSEMBLE)) {
			// read the contents of the element
			createEnsemble();
		} else if (localName.equals(PdbXmlVocabulary.CONFORMER_SELECTION_CRITERIA) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.AVERAGE_CONSTRAINT_VIOLATIONS_PER_RESIDUE) && !isNil(attributes)) {
			startBuffering();
		}

		super.startElement(uri, localName, name, attributes);
	}// startElement

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.CONFORMER_SELECTION_CRITERIA) && isBuffering()) {
			createConformerSelectionCriteria(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.AVERAGE_CONSTRAINT_VIOLATIONS_PER_RESIDUE) && isBuffering()) {
			createAverageConstraintViolationsPerResidue(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}// endElement

	private void createAverageConstraintViolationsPerResidue(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.AVERAGE_CONSTRAINT_VIOLATIONS_PER_RESIDUE, pdbId);
		getRdfModel().add(x,RDF.type, PdbOwlVocabulary.Class.Resource.resource());
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.AverageConstraintViolationsPerResidue.resource());
		getRdfModel().add(x, RDFS.label,
				"The average number of constraint violations on a per residue basis for the ensemble. .", "en");
		getRdfModel().add(PdbOwlVocabulary.DataProperty.hasValue.property(), RDF.type, PdbOwlVocabulary.Class.Resource.resource());
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), createDecimalLiteral(value));
		getRdfModel().add(PdbOwlVocabulary.ObjectProperty.hasAverageConstraintViolationsPerResidue.property(), RDF.type, PdbOwlVocabulary.Class.Resource.resource());
		getRdfModel().add(ensemble, PdbOwlVocabulary.ObjectProperty.hasAverageConstraintViolationsPerResidue.property(),
				x);
	}

	private void createConformerSelectionCriteria(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.CONFORMER_SELECTION_CRITERIA, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.ConformerSelectionCriteria.resource());
		getRdfModel().add(x,RDF.type, PdbOwlVocabulary.Class.Resource.resource());
		getRdfModel().add(x, RDFS.label, "Description on how the submitted confomer (models) were selected.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(PdbOwlVocabulary.DataProperty.hasValue.property(),RDF.type, PdbOwlVocabulary.Class.Resource.resource());
		getRdfModel().add(ensemble, PdbOwlVocabulary.ObjectProperty.hasConformerSelectionCriteria.property(), x);
		getRdfModel().add(PdbOwlVocabulary.ObjectProperty.hasConformerSelectionCriteria.property(),RDF.type, PdbOwlVocabulary.Class.Resource.resource());
		
	}

	private void createEnsemble() {
		Resource structureDetermination = createResource(Bio2RdfPdbUriPattern.STRUCTURE_DETERMINATION, pdbId);
		ensemble = createResource(Bio2RdfPdbUriPattern.ENSEMBLE, pdbId);
		getRdfModel().add(structureDetermination, PdbOwlVocabulary.ObjectProperty.hasPart.property(), ensemble);
		
	}
}// NmrEnsembleCategoryHandler
