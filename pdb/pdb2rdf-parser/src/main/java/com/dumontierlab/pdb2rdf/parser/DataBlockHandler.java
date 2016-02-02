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
import java.util.HashMap;
import java.util.Map;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.DC_11;
import com.hp.hpl.jena.vocabulary.RDF;

/**
 * @author Alexander De Leon
 */
public class DataBlockHandler extends ContentHandlerState {

	private String pdbId;
	private final Map<String, Collection<Resource>> residues;
	private final boolean parseAtomSites;

	public DataBlockHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder) {
		this(rdfModel, uriBuilder, true);
	}

	public DataBlockHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, boolean parseAtomSites) {
		super(rdfModel, uriBuilder);
		residues = new HashMap<String, Collection<Resource>>();
		this.parseAtomSites = parseAtomSites;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes atts) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.DATABLOCK)) {
			pdbId = atts.getValue(PdbXmlVocabulary.DATABLOCK_NAME_ATT);
			getRdfModel().setPdbId(pdbId);
			createExperiment();
		} else if (localName.equals(PdbXmlVocabulary.ENTITY_CATEGORY)) {
			setState(new EntityCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.CITATION_CATEGORY)) {
			setState(new CitationCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.CITATION_AUTHOR_CATEGORY)) {
			setState(new CitationAuthorCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.ENTITY_POLY_CATEGORY)) {
			setState(new EntityPolyCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.ENTITY_SOURCE_NATURAL_CATEGORY)) {
			setState(new EntitySourceNaturalCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.ENTITY_SOURCE_GEN_CATEGORY)) {
			setState(new EntitySourceGeneticallyManipulatedHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.ATOM_SITE_CATEGORY)) {
			setState(new AtomSiteCategoryHandler(getRdfModel(), getUriBuilder(), pdbId, residues));
		} else if (localName.equals(PdbXmlVocabulary.CHEMICAL_COMPONENT_CATEGORY)) {
			setState(new ChemCompCategory(getRdfModel(), getUriBuilder(), pdbId, residues));
		} else if (localName.equals(PdbXmlVocabulary.STRUCT_CONFIG_CATEGORY)) {
			setState(new StructConfigCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.CELL_CATEGORY)) {
			setState(new CellCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.EXPTL_CATEGORY)) {
			setState(new ExptlCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.STRUCT_CATEGORY)) {
			setState(new StructCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		} else if (localName.equals(PdbXmlVocabulary.REFINE_CATEGORY)) {
			setState(new RefineCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		}
		super.startElement(uri, localName, name, atts);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.DATABLOCK) || localName.equals(PdbXmlVocabulary.ENTITY_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.CITATION_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.CITATION_AUTHOR_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.ENTITY_POLY_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.ENTITY_SOURCE_NATURAL_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.ATOM_SITE_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.STRUCT_CONFIG_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.CELL_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.EXPTL_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.STRUCT_CATEGORY)
				|| localName.equals(PdbXmlVocabulary.REFINE_CATEGORY)) {
			setState(null);
		}
		super.endElement(uri, localName, name);
	}
	

	private void createExperiment() {
		Resource experimentResource = createResource(Bio2RdfPdbUriPattern.EXPERIMENT, pdbId);
		getRdfModel().add(experimentResource, RDF.type, PdbOwlVocabulary.Class.Experiment.resource());
		getRdfModel().add(experimentResource, DC_11.identifier, "pdb:" + pdbId);
		// structure determination
		Resource structureDetermination = createResource(Bio2RdfPdbUriPattern.STRUCTURE_DETERMINATION, pdbId);
		getRdfModel().add(structureDetermination, RDF.type, PdbOwlVocabulary.Class.StructureDetermination.resource());
		getRdfModel().add(experimentResource, PdbOwlVocabulary.ObjectProperty.hasPart.property(),
				structureDetermination);
	}

}
