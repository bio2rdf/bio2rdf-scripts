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
import com.dumontierlab.pdb2rdf.util.UriUtil;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.OWL;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;

/**
 * @author Alexander De Leon
 */
public class EntitySourceNaturalCategoryHandler extends ContentHandlerState {

	private final String pdbId;
	private String entityId;

	private Resource entityExtractionResource;
	private Resource cellResource;
	private Resource organismResource;
	private Resource organResource;
	private Resource organelleResource;
	private Resource plasmidResource;
	private Resource secretionResource;
	private Resource tissueResource;
	private Resource tissueFractionResource;

	private String atcc;

	public EntitySourceNaturalCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.ENTITY_SOURCE_NATURAL.equals(localName)) {
			entityId = attributes.getValue(PdbXmlVocabulary.ENTITY_ID_ATT);
		} else if (PdbXmlVocabulary.DETAILS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CELL_TYPE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CELL_LINE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.NCBI_TAXONOMY_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ORGAN.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ORGANELLE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ORGANISM_SCIENTIFIC_NAME.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PLASMID_DETAILS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PLASMID_NAME.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.SECRETION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.TISSUE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.TISSUE_FRACTION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ATCC.equals(localName) && !isNil(attributes)) {
			startBuffering();
		}
		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (PdbXmlVocabulary.ENTITY_SOURCE_NATURAL.equals(localName)) {
			clear();
		} else if (PdbXmlVocabulary.DETAILS.equals(localName) && isBuffering()) {
			createDetails(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.CELL_TYPE.equals(localName) && isBuffering()) {
			createCellType(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.CELL_LINE.equals(localName) && isBuffering()) {
			createCellLine(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.NCBI_TAXONOMY_ID.equals(localName) && isBuffering()) {
			createOrganism(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.ORGAN.equals(localName) && isBuffering()) {
			createOrgan(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.ORGANELLE.equals(localName) && isBuffering()) {
			createOrganelle(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.ORGANISM_SCIENTIFIC_NAME.equals(localName) && isBuffering()) {
			createOrganismName(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.PLASMID_DETAILS.equals(localName) && isBuffering()) {
			createPlasmidDetails(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.PLASMID_NAME.equals(localName) && isBuffering()) {
			createPlasmidType(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.SECRETION.equals(localName) && isBuffering()) {
			createSecretion(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.TISSUE.equals(localName) && isBuffering()) {
			createTissue(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.TISSUE_FRACTION.equals(localName) && isBuffering()) {
			createTissueFraction(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.ATCC.equals(localName) && isBuffering()) {
			atcc = getBufferContent();
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}

	private void createTissueFraction(String tissueFractionName) {
		Resource tissueFraction = getTissueFractionResource();
		Resource tissueFractionType = createResource(Bio2RdfPdbUriPattern.TISSUE_FRACTION_TYPE, UriUtil
				.urlEncode(UriUtil.toCamelCase(tissueFractionName)));
		getRdfModel().add(tissueFraction, RDFS.label, tissueFractionName);
		getRdfModel().add(tissueFraction, RDF.type, tissueFractionType);
		getRdfModel().add(tissueFractionType, RDF.type, OWL.Class);
		getRdfModel().add(tissueFractionType, RDFS.subClassOf, PdbOwlVocabulary.Class.TissueFraction.resource());
		getRdfModel().add(tissueFractionType, RDFS.label, tissueFractionName);

	}

	private void createTissue(String tissueName) {
		if (tissueName.length() == 0) {
			return;
		}
		Resource tissue = getTissueResource();
		Resource tissueType = createResource(Bio2RdfPdbUriPattern.TISSUE_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(tissueName)));
		getRdfModel().add(tissue, RDFS.label, tissueName);
		getRdfModel().add(tissue, RDF.type, tissueType);
		getRdfModel().add(tissueType, RDF.type, OWL.Class);
		getRdfModel().add(tissueType, RDFS.subClassOf, PdbOwlVocabulary.Class.Tissue.resource());
		getRdfModel().add(tissueType, RDFS.label, tissueName);
	}

	private void createSecretion(String secretionName) {
		Resource secretion = getSecretionResource();
		Resource secreationType = createResource(Bio2RdfPdbUriPattern.SECRETION_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(secretionName)));
		getRdfModel().add(secretion, RDFS.label, secretionName);
		getRdfModel().add(secretion, RDF.type, secreationType);
		getRdfModel().add(secreationType, RDF.type, OWL.Class);
		getRdfModel().add(secreationType, RDFS.subClassOf, PdbOwlVocabulary.Class.Secretion.resource());
		getRdfModel().add(secreationType, RDFS.label, secretionName);
	}

	private void createPlasmidType(String plasmidName) {
		Resource plasmid = getPlasmidResource();
		Resource plasmidType = createResource(Bio2RdfPdbUriPattern.PLASMID_ID, plasmidName);
		getRdfModel().add(plasmid, RDFS.label, plasmidName);
		getRdfModel().add(plasmid, RDF.type, plasmidType);
		getRdfModel().add(plasmidType, RDFS.label, plasmidName);
		getRdfModel().add(plasmidType, RDF.type, OWL.Class);
		getRdfModel().add(plasmidType, RDFS.subClassOf, PdbOwlVocabulary.Class.Plasmid.resource());
	}

	private void createPlasmidDetails(String plasmidDetails) {
		Resource plasmid = getPlasmidResource();
		getRdfModel().add(plasmid, PdbOwlVocabulary.Annotation.details.property(), plasmidDetails);
	}

	private void createOrganismName(String name) {
		Resource organism = getOrganismResource();
		Resource nameQuality = createResource(Bio2RdfPdbUriPattern.ORGANISM_NAME, pdbId, entityId);
		getRdfModel().add(organism, RDFS.label, name);
		getRdfModel().add(organism, PdbOwlVocabulary.ObjectProperty.hasName.property(), nameQuality);
		getRdfModel().add(nameQuality, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(nameQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), name);
	}

	private void createOrganelle(String organelleName) {
		Resource organelle = getOrganelleResource();
		getRdfModel().add(organelle, RDFS.label, organelleName);
		Resource organType = createResource(Bio2RdfPdbUriPattern.ORGANELLE_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(organelleName)));
		getRdfModel().add(organType, RDFS.label, organelleName);
		getRdfModel().add(organType, RDF.type, OWL.Class);
		getRdfModel().add(organType, RDFS.subClassOf, PdbOwlVocabulary.Class.Organelle.resource());
	}

	private void createOrgan(String organName) {
		Resource organ = getOrganResource();
		getRdfModel().add(organ, RDFS.label, organName);
		Resource organType = createResource(Bio2RdfPdbUriPattern.ORGAN_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(organName)));
		getRdfModel().add(organType, RDFS.label, organName);
		getRdfModel().add(organType, RDF.type, OWL.Class);
		getRdfModel().add(organType, RDFS.subClassOf, PdbOwlVocabulary.Class.Organ.resource());
	}

	private void createOrganism(String ncbiTaxonId) {
		Resource organism = getOrganismResource();
		Resource ncbiTaxonomy = createResource(Bio2RdfPdbUriPattern.NCBI_TAXONOMY, ncbiTaxonId);
		getRdfModel().add(organism, RDF.type, ncbiTaxonomy);
	}

	private void createCellLine(String cellLineName) {
		Resource cell = getCellResource();
		Resource cellType = createResource(Bio2RdfPdbUriPattern.CELL_LINE, UriUtil.urlEncode(UriUtil
				.toCamelCase(cellLineName)));
		getRdfModel().add(cell, RDF.type, cellType);
		getRdfModel().add(cellType, RDF.type, OWL.Class);
		getRdfModel().add(cellType, RDFS.subClassOf, PdbOwlVocabulary.Class.Cell.resource());
		getRdfModel().add(cellType, RDFS.label, cellLineName);
	}

	private void createCellType(String cellTypeName) {
		Resource cell = getCellResource();
		Resource cellType = createResource(Bio2RdfPdbUriPattern.CELL_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(cellTypeName)));
		getRdfModel().add(cell, RDF.type, cellType);
		getRdfModel().add(cellType, RDF.type, OWL.Class);
		getRdfModel().add(cellType, RDFS.subClassOf, PdbOwlVocabulary.Class.Cell.resource());
		getRdfModel().add(cellType, RDFS.label, cellTypeName);
		getRdfModel().add(cell, RDFS.label, cellTypeName);
	}

	private void createDetails(String details) {
		Resource entityExtraction = getEntityExtractionResource();
		getRdfModel().add(entityExtraction, PdbOwlVocabulary.Annotation.details.property(), details);
	}

	private Resource getEntityExtractionResource() {
		if (entityExtractionResource == null) {
			entityExtractionResource = createResource(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE_EXTRACTION, pdbId,
					entityId);
		}
		return entityExtractionResource;
	}

	private Resource getCellResource() {
		if (cellResource == null) {
			cellResource = createResource(Bio2RdfPdbUriPattern.CELL, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					cellResource);
			getRdfModel().add(cellResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getOrganismResource());
		}
		return cellResource;
	}

	private Resource getOrganismResource() {
		if (organismResource == null) {
			organismResource = createResource(Bio2RdfPdbUriPattern.ORGANISM, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					organismResource);
		}
		return organismResource;
	}

	private Resource getOrganResource() {
		if (organResource == null) {
			organResource = createResource(Bio2RdfPdbUriPattern.ORGAN, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					organResource);
			getRdfModel().add(organResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getOrganismResource());
		}
		return organResource;
	}

	private Resource getOrganelleResource() {
		if (organelleResource == null) {
			organelleResource = createResource(Bio2RdfPdbUriPattern.ORGANELLE, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					organelleResource);

			if (cellResource != null) {
				getRdfModel().add(organelleResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), cellResource);
			}
			getRdfModel().add(organelleResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getOrganismResource());

		}
		return organelleResource;
	}

	private Resource getPlasmidResource() {
		if (plasmidResource == null) {
			plasmidResource = createResource(Bio2RdfPdbUriPattern.PLASMID, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					plasmidResource);

			getRdfModel().add(plasmidResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getOrganismResource());

		}
		return plasmidResource;
	}

	private Resource getSecretionResource() {
		if (secretionResource == null) {
			secretionResource = createResource(Bio2RdfPdbUriPattern.SECRETION, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					secretionResource);

			getRdfModel().add(secretionResource, PdbOwlVocabulary.ObjectProperty.isProducedBy.property(),
					getOrganismResource());

		}
		return secretionResource;
	}

	private Resource getTissueResource() {
		if (tissueResource == null) {
			tissueResource = createResource(Bio2RdfPdbUriPattern.TISSUE, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					tissueResource);

			if (organResource != null) {
				getRdfModel().add(tissueResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), organResource);
			}
			getRdfModel().add(tissueResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getOrganismResource());

			if (cellResource != null) {
				getRdfModel().add(cellResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), tissueResource);
			}

			if (secretionResource != null) {
				getRdfModel().add(secretionResource, PdbOwlVocabulary.ObjectProperty.isProducedBy.property(),
						tissueResource);
			}
			if (atcc != null) {
				Resource atccResource = createResource(Bio2RdfPdbUriPattern.AMERICAN_TYPE_CULTURE_COLLECTION, atcc);
				getRdfModel().add(getTissueResource(), RDFS.seeAlso, atccResource);
			}

		}
		return tissueResource;
	}

	private Resource getTissueFractionResource() {
		if (tissueFractionResource == null) {
			tissueFractionResource = createResource(Bio2RdfPdbUriPattern.TISSUE_FRACTION, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					tissueFractionResource);

			if (tissueResource != null) {
				getRdfModel().add(tissueFractionResource, PdbOwlVocabulary.ObjectProperty.isDerivedFrom.property(),
						tissueResource);
			}
			if (organelleResource != null) {
				getRdfModel().add(organelleResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), tissueFractionResource);
			}
			getRdfModel().add(tissueFractionResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getOrganismResource());

		}
		return tissueFractionResource;
	}

}
