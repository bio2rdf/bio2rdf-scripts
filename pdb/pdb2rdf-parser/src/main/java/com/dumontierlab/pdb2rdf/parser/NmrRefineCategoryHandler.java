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

public class NmrRefineCategoryHandler extends ContentHandlerState {
	private final String pdbId;
	private Resource refinement;

	public NmrRefineCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.PDBX_NMR_REFINE)) {
			// read the contents of the element
			createNMRRefinement();
		} else if (localName.equals(PdbXmlVocabulary.DETAILS) && !isNil(attributes)) {
			startBuffering();
		} else if (localName.equals(PdbXmlVocabulary.METHOD) && !isNil(attributes)) {
			startBuffering();
		}
		super.startElement(uri, localName, name, attributes);
	}// startElement

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.DETAILS) && isBuffering()) {
			createNMRDetails(getBufferContent());
			stopBuffering();
		} else if (localName.equals(PdbXmlVocabulary.METHOD) && isBuffering()) {
			createNMRMethod(getBufferContent());
			stopBuffering();
		}

		super.endElement(uri, localName, name);
	}// endElement

	private void createNMRMethod(String value) {
		Resource x = createResource(Bio2RdfPdbUriPattern.REFINEMENT_METHOD, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.RefinementMethod.resource());
		getRdfModel().add(x, RDFS.label, "The method used to determine the structure.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		// getRdfModel().add(refinement,
		// PdbOwlVocabulary.ObjectProperty.hasMeht.property(), x);
	}

	private void createNMRDetails(String value) {
		// TODO: should this be modeled with details annotation?
		Resource x = createResource(Bio2RdfPdbUriPattern.REFINEMENT_DETAILS, pdbId);
		getRdfModel().add(x, RDF.type, PdbOwlVocabulary.Class.RefinementDetails.resource());
		getRdfModel().add(x, RDFS.label, "Additional details about the NMR refinement.", "en");
		getRdfModel().add(x, PdbOwlVocabulary.DataProperty.hasValue.property(), value, "en");
		getRdfModel().add(refinement, PdbOwlVocabulary.ObjectProperty.hasDetails.property(), x);
	}

	private void createNMRRefinement() {
		Resource structureDetermination = createResource(Bio2RdfPdbUriPattern.STRUCTURE_DETERMINATION, pdbId);
		// I am using the same URI pattern for NMR refinement as for XRAY
		refinement = createResource(Bio2RdfPdbUriPattern.REFINEMENT, pdbId);
		getRdfModel().add(refinement, RDF.type, PdbOwlVocabulary.Class.Refinement.resource());
		getRdfModel().add(structureDetermination, PdbOwlVocabulary.ObjectProperty.hasPart.property(), refinement);
	}

}
