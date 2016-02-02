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
import com.dumontierlab.pdb2rdf.parser.vocabulary.Vectors;
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
public class EntitySourceGeneticallyManipulatedHandler extends ContentHandlerState {

	private final String pdbId;
	private String entityId;

	private String geneSourceDevelopmentStage;

	private Resource geneResource;
	private Resource geneOrganismResource;
	private Resource geneTissueResource;
	private Resource geneTissueFractionResource;
	private Resource vectorResource;
	private Resource organismResource;
	private Resource entityExtractionResource;
	private Resource geneCellResource;
	private Resource geneCellularLocationResource;
	private Resource geneOrganResource;
	private Resource geneOrganelleResource;
	private Resource genePlasmidResource;
	private Resource cellResource;
	private Resource cellularLocationResource;
	private Resource hostGeneResource;
	private Resource organResource;
	private Resource organelleResource;
	private Resource tissueResource;
	private Resource tissueFractionResource;

	public EntitySourceGeneticallyManipulatedHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.ENTITY_SOURCE_GEN.equals(localName)) {
			entityId = attributes.getValue(PdbXmlVocabulary.ENTITY_ID_ATT);
		} else if (PdbXmlVocabulary.GENE_SRC_DETAILS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SRC_DEVELOMENT_STAGE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SRC_TISSUE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SRC_TISSUE_FRACTION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_ORGANISM_DETAILS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.DESCRIPTION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ATCC.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_CELL.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_CELL_LINE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_CELLULAR_LOCATION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_GENE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ORGANISM_TAXONOMY.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ORGAN.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ORGANELLE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_PLASMID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_PLASMID_NAME.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ORGANISM_SCIENTIFIC_NAME.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_CELL.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_CELL_LINE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_CELLULAR_LOCATION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_GENE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_ORGANISM_TAXONOMY.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_ORGAN.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_ORGANELLE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_SCIENTIFIC_NAME.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_TISSUE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_TISSUE_FRACTION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.HOST_VECTOR_TYPE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PLASMID_DETAILS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PLASMID_NAME.equals(localName) && !isNil(attributes)) {
			startBuffering();
		}
		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (PdbXmlVocabulary.ENTITY_SOURCE_GEN.equals(localName)) {
			// if there is not cell resource then the development stage quality
			// is associated with the organism
			if (geneSourceDevelopmentStage != null) {
				Resource devStageQuality = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_ORGANISM_DEV_STAGE, pdbId,
						entityId);
				getRdfModel().add(getGeneOrganismResource(),
						PdbOwlVocabulary.ObjectProperty.hasDevelopmentStage.property(), devStageQuality);
				getRdfModel().add(devStageQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
						geneSourceDevelopmentStage);
				getRdfModel().add(devStageQuality, RDF.type, PdbOwlVocabulary.Class.DevelopmentStage.resource());

				geneSourceDevelopmentStage = null;
			}
			clear();
		} else if (PdbXmlVocabulary.GENE_SRC_DETAILS.equals(localName) && isBuffering()) {
			createGeneSourceDetails(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SRC_DEVELOMENT_STAGE.equals(localName) && isBuffering()) {
			// NOTE : This has to be added to the gene_src_cell or
			// gene_src_organism when the entity source element closes.
			geneSourceDevelopmentStage = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SRC_TISSUE.equals(localName) && isBuffering()) {
			createGeneTissue(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SRC_TISSUE_FRACTION.equals(localName) && isBuffering()) {
			createGeneTissueFraction(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_ORGANISM_DETAILS.equals(localName) && isBuffering()) {
			createHostOrganismDetails(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.DESCRIPTION.equals(localName) && isBuffering()) {
			createDescription(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ATCC.equals(localName) && isBuffering()) {
			createGeneATCCReference(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_CELL.equals(localName) && isBuffering()) {
			createGeneCellType(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_CELL_LINE.equals(localName) && isBuffering()) {
			createGeneCellLine(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_CELLULAR_LOCATION.equals(localName) && isBuffering()) {
			createGeneCellularLocation(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_GENE.equals(localName) && isBuffering()) {
			createGeneName(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ORGANISM_TAXONOMY.equals(localName) && isBuffering()) {
			createGeneOrganismTaxonomy(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ORGAN.equals(localName) && isBuffering()) {
			createGeneOrgan(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ORGANELLE.equals(localName) && isBuffering()) {
			createGeneOrganelle(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_PLASMID.equals(localName) && isBuffering()) {
			createGenePlasmidName(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_PLASMID_NAME.equals(localName) && isBuffering()) {
			createGenePlasmidName(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.GENE_SOURCE_ORGANISM_SCIENTIFIC_NAME.equals(localName) && isBuffering()) {
			createGeneOrganismName(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_CELL.equals(localName) && isBuffering()) {
			createHostCell(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_CELL_LINE.equals(localName) && isBuffering()) {
			createHostCellLine(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_CELLULAR_LOCATION.equals(localName) && isBuffering()) {
			createHostCellularLocation(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_GENE.equals(localName) && isBuffering()) {
			createHostGene(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_ORGANISM_TAXONOMY.equals(localName) && isBuffering()) {
			createHostTaxonomy(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_ORGAN.equals(localName) && isBuffering()) {
			createHostOrgan(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_ORGANELLE.equals(localName) && isBuffering()) {
			createHostOrganelle(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_SCIENTIFIC_NAME.equals(localName) && isBuffering()) {
			createHostOrganismName(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_TISSUE.equals(localName) && isBuffering()) {
			createHostTissue(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_TISSUE_FRACTION.equals(localName) && isBuffering()) {
			createHostTissueFraction(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.HOST_VECTOR_TYPE.equals(localName) && isBuffering()) {
			createVectorType(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.PLASMID_DETAILS.equals(localName) && isBuffering()) {
			createPlasmidDetails(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.PLASMID_NAME.equals(localName) && isBuffering()) {
			createPlasmidName(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}

	private void createPlasmidName(String plasmidName) {
		Resource plasmid = getVectorResource();
		Resource plasmidType = createResource(Bio2RdfPdbUriPattern.PLASMID_ID, UriUtil.urlEncode(UriUtil
				.replaceSpacesByUnderscore(plasmidName)));
		getRdfModel().add(plasmid, RDF.type, plasmidType);
		getRdfModel().add(plasmidType, RDFS.label, plasmidName);
		getRdfModel().add(plasmidType, RDF.type, OWL.Class);
		getRdfModel().add(plasmidType, RDFS.subClassOf, PdbOwlVocabulary.Class.Plasmid.resource());
	}

	private void createPlasmidDetails(String detais) {
		getRdfModel().add(getVectorResource(), PdbOwlVocabulary.Annotation.details.property(), detais);
	}

	private void createVectorType(String vectorTypeName) {
		if (vectorTypeName.length() == 0) {
			return;
		}
		for (String type : vectorTypeName.split(",")) {
			PdbOwlVocabulary.Class typeResource = Vectors.get(type.trim());
			if (typeResource != null) {
				getRdfModel().add(getVectorResource(), RDF.type, typeResource.resource());
			} else {
				getRdfModel().add(getVectorResource(), RDFS.comment, type);
			}
		}
	}

	private void createHostTissueFraction(String tissueFractionName) {
		Resource tissueFraction = getHostTissueFractionResource();
		Resource tissueFractionType = createResource(Bio2RdfPdbUriPattern.TISSUE_FRACTION_TYPE, UriUtil
				.urlEncode(UriUtil.toCamelCase(tissueFractionName)));
		getRdfModel().add(tissueFraction, RDFS.label, tissueFractionName);
		getRdfModel().add(tissueFraction, RDF.type, tissueFractionType);
		getRdfModel().add(tissueFractionType, RDF.type, OWL.Class);
		getRdfModel().add(tissueFractionType, RDFS.subClassOf, PdbOwlVocabulary.Class.TissueFraction.resource());
		getRdfModel().add(tissueFractionType, RDFS.label, tissueFractionName);

	}

	private void createHostTissue(String tissueName) {
		Resource tissue = getHostTissueResource();
		Resource tissueType = createResource(Bio2RdfPdbUriPattern.TISSUE_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(tissueName)));
		getRdfModel().add(tissue, RDFS.label, tissueName);
		getRdfModel().add(tissue, RDF.type, tissueType);
		getRdfModel().add(tissueType, RDF.type, OWL.Class);
		getRdfModel().add(tissueType, RDFS.subClassOf, PdbOwlVocabulary.Class.Tissue.resource());
		getRdfModel().add(tissueType, RDFS.label, tissueName);

	}

	private void createHostOrganismName(String scientificName) {
		Resource organims = getHostOrganismResource();
		Resource name = createResource(Bio2RdfPdbUriPattern.ORGANISM_NAME, pdbId, entityId);
		getRdfModel().add(organims, PdbOwlVocabulary.ObjectProperty.hasName.property(), name);
		getRdfModel().add(name, PdbOwlVocabulary.DataProperty.hasValue.property(), scientificName);
		getRdfModel().add(name, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(organims, RDFS.label, scientificName);

	}

	private void createHostOrganelle(String organelleName) {
		Resource organelle = getHostOrganelleResource();
		getRdfModel().add(organelle, RDFS.label, organelleName);
		Resource organType = createResource(Bio2RdfPdbUriPattern.ORGANELLE_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(organelleName)));
		getRdfModel().add(organType, RDFS.label, organelleName);
		getRdfModel().add(organType, RDF.type, OWL.Class);
		getRdfModel().add(organType, RDFS.subClassOf, PdbOwlVocabulary.Class.Organelle.resource());

	}

	private void createHostOrgan(String organName) {
		Resource organ = getHostOrganResource();
		getRdfModel().add(organ, RDFS.label, organName);
		Resource organType = createResource(Bio2RdfPdbUriPattern.ORGAN_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(organName)));
		getRdfModel().add(organType, RDFS.label, organName);
		getRdfModel().add(organType, RDF.type, OWL.Class);
		getRdfModel().add(organType, RDFS.subClassOf, PdbOwlVocabulary.Class.Organ.resource());

	}

	private void createHostTaxonomy(String taxId) {
		Resource taxonomy = createResource(Bio2RdfPdbUriPattern.NCBI_TAXONOMY, taxId);
		getRdfModel().add(getHostOrganismResource(), RDF.type, taxonomy);
	}

	private void createHostGene(String geneName) {
		Resource gene = getHostGeneReouce();
		Resource nameQuality = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_NAME, pdbId, entityId);
		getRdfModel().add(gene, RDFS.label, geneName);
		getRdfModel().add(gene, PdbOwlVocabulary.ObjectProperty.hasName.property(), nameQuality);
		getRdfModel().add(nameQuality, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(nameQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), geneName);
	}

	private void createHostCellularLocation(String cellularLocationName) {
		Resource cellularLocation = getHostCellularLocationResource();
		Resource cellularLocationType = createResource(Bio2RdfPdbUriPattern.CELLULAR_LOCATION_TYPE, UriUtil
				.urlEncode(UriUtil.toCamelCase(cellularLocationName)));
		getRdfModel().add(cellularLocation, RDFS.label, cellularLocationName);
		getRdfModel().add(cellularLocation, RDF.type, cellularLocationType);
		getRdfModel().add(cellularLocationType, RDF.type, OWL.Class);
		getRdfModel().add(cellularLocationType, RDFS.subClassOf, PdbOwlVocabulary.Class.CellularLocation.resource());
		getRdfModel().add(cellularLocationType, RDFS.label, cellularLocationName);

	}

	private void createHostCellLine(String cellLineName) {
		Resource cell = getHostCellResource();
		Resource cellType = createResource(Bio2RdfPdbUriPattern.CELL_LINE, UriUtil.urlEncode(UriUtil
				.toCamelCase(cellLineName)));
		getRdfModel().add(cell, RDF.type, cellType);
		getRdfModel().add(cellType, RDF.type, OWL.Class);
		getRdfModel().add(cellType, RDFS.subClassOf, PdbOwlVocabulary.Class.Cell.resource());
		getRdfModel().add(cellType, RDFS.label, cellLineName);
	}

	private void createHostCell(String cellTypeName) {
		Resource cell = getHostCellResource();
		Resource cellType = createResource(Bio2RdfPdbUriPattern.CELL_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(cellTypeName)));
		getRdfModel().add(cell, RDF.type, cellType);
		getRdfModel().add(cellType, RDF.type, OWL.Class);
		getRdfModel().add(cellType, RDFS.subClassOf, PdbOwlVocabulary.Class.Cell.resource());
		getRdfModel().add(cellType, RDFS.label, cellTypeName);
		getRdfModel().add(cell, RDFS.label, cellTypeName);

	}

	private void createGeneOrganismName(String scientificName) {
		Resource organims = getGeneOrganismResource();
		Resource name = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_ORGANISM_NAME, pdbId, entityId);
		getRdfModel().add(organims, PdbOwlVocabulary.ObjectProperty.hasName.property(), name);
		getRdfModel().add(name, PdbOwlVocabulary.DataProperty.hasValue.property(), scientificName);
		getRdfModel().add(name, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(organims, RDFS.label, scientificName);

	}

	private void createGenePlasmidName(String name) {
		Resource plasmid = getGenePlasmidResource();
		Resource plasmidType = createResource(Bio2RdfPdbUriPattern.PLASMID_ID, name);
		getRdfModel().add(plasmid, RDF.type, plasmidType);
		getRdfModel().add(plasmidType, RDFS.label, name);
		getRdfModel().add(plasmidType, RDF.type, OWL.Class);
		getRdfModel().add(plasmidType, RDFS.subClassOf, PdbOwlVocabulary.Class.Plasmid.resource());

	}

	private void createGeneOrganelle(String organelleName) {
		Resource organelle = getGeneOrganelleResource();
		getRdfModel().add(organelle, RDFS.label, organelleName);
		Resource organType = createResource(Bio2RdfPdbUriPattern.ORGANELLE_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(organelleName)));
		getRdfModel().add(organType, RDFS.label, organelleName);
		getRdfModel().add(organType, RDF.type, OWL.Class);
		getRdfModel().add(organType, RDFS.subClassOf, PdbOwlVocabulary.Class.Organelle.resource());

	}

	private void createGeneOrgan(String organName) {
		Resource organ = getGeneOrganResource();
		getRdfModel().add(organ, RDFS.label, organName);
		Resource organType = createResource(Bio2RdfPdbUriPattern.ORGAN_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(organName)));
		getRdfModel().add(organType, RDFS.label, organName);
		getRdfModel().add(organType, RDF.type, OWL.Class);
		getRdfModel().add(organType, RDFS.subClassOf, PdbOwlVocabulary.Class.Organ.resource());

	}

	private void createGeneOrganismTaxonomy(String taxId) {
		Resource organism = getGeneOrganismResource();
		Resource taxonomy = createResource(Bio2RdfPdbUriPattern.NCBI_TAXONOMY, taxId);
		getRdfModel().add(organism, RDF.type, taxonomy);
	}

	private void createGeneName(String geneName) {
		Resource gene = getGeneResource();
		Resource nameQuality = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_NAME, pdbId, entityId);
		getRdfModel().add(gene, RDFS.label, geneName);
		getRdfModel().add(gene, PdbOwlVocabulary.ObjectProperty.hasName.property(), nameQuality);
		getRdfModel().add(nameQuality, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(nameQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), geneName);
	}

	private void createGeneCellularLocation(String cellularLocationName) {
		Resource cellularLocation = getGeneCellularLocationResource();
		Resource cellularLocationType = createResource(Bio2RdfPdbUriPattern.CELLULAR_LOCATION_TYPE, UriUtil
				.urlEncode(UriUtil.toCamelCase(cellularLocationName)));
		getRdfModel().add(cellularLocation, RDFS.label, cellularLocationName);
		getRdfModel().add(cellularLocation, RDF.type, cellularLocationType);
		getRdfModel().add(cellularLocationType, RDF.type, OWL.Class);
		getRdfModel().add(cellularLocationType, RDFS.subClassOf, PdbOwlVocabulary.Class.CellularLocation.resource());
		getRdfModel().add(cellularLocationType, RDFS.label, cellularLocationName);
	}

	private void createGeneCellLine(String cellLineName) {
		Resource cell = getGeneCellResource();
		Resource cellType = createResource(Bio2RdfPdbUriPattern.CELL_LINE, UriUtil.urlEncode(UriUtil
				.toCamelCase(cellLineName)));
		getRdfModel().add(cell, RDF.type, cellType);
		getRdfModel().add(cellType, RDF.type, OWL.Class);
		getRdfModel().add(cellType, RDFS.subClassOf, PdbOwlVocabulary.Class.Cell.resource());
		getRdfModel().add(cellType, RDFS.label, cellLineName);
	}

	private void createGeneCellType(String cellTypeName) {
		Resource cell = getGeneCellResource();
		Resource cellType = createResource(Bio2RdfPdbUriPattern.CELL_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(cellTypeName)));
		getRdfModel().add(cell, RDF.type, cellType);
		getRdfModel().add(cellType, RDF.type, OWL.Class);
		getRdfModel().add(cellType, RDFS.subClassOf, PdbOwlVocabulary.Class.Cell.resource());
		getRdfModel().add(cellType, RDFS.label, cellTypeName);
		getRdfModel().add(cell, RDFS.label, cellTypeName);
	}

	private void createGeneATCCReference(String atccId) {
		Resource atcc = createResource(Bio2RdfPdbUriPattern.AMERICAN_TYPE_CULTURE_COLLECTION, atccId);
		getRdfModel().add(getGeneTissueResource(), RDFS.seeAlso, atcc);
	}

	private void createDescription(String description) {
		getRdfModel()
				.add(getEntityExtractionResource(), PdbOwlVocabulary.Annotation.description.property(), description);
	}

	private void createHostOrganismDetails(String details) {
		getRdfModel().add(getHostOrganismResource(), PdbOwlVocabulary.Annotation.details.property(), details);
	}

	private void createGeneTissueFraction(String tissueFractionName) {
		Resource tissueFraction = getGeneTissueFractionResource();
		Resource tissueFractionType = createResource(Bio2RdfPdbUriPattern.TISSUE_FRACTION_TYPE, UriUtil
				.urlEncode(UriUtil.toCamelCase(tissueFractionName)));
		getRdfModel().add(tissueFraction, RDFS.label, tissueFractionName);
		getRdfModel().add(tissueFraction, RDF.type, tissueFractionType);
		getRdfModel().add(tissueFractionType, RDF.type, OWL.Class);
		getRdfModel().add(tissueFractionType, RDFS.subClassOf, PdbOwlVocabulary.Class.TissueFraction.resource());
		getRdfModel().add(tissueFractionType, RDFS.label, tissueFractionName);

	}

	private void createGeneTissue(String tissueName) {
		Resource tissue = getGeneTissueResource();
		Resource tissueType = createResource(Bio2RdfPdbUriPattern.TISSUE_TYPE, UriUtil.urlEncode(UriUtil
				.toCamelCase(tissueName)));
		getRdfModel().add(tissue, RDFS.label, tissueName);
		getRdfModel().add(tissue, RDF.type, tissueType);
		getRdfModel().add(tissueType, RDF.type, OWL.Class);
		getRdfModel().add(tissueType, RDFS.subClassOf, PdbOwlVocabulary.Class.Tissue.resource());
		getRdfModel().add(tissueType, RDFS.label, tissueName);
	}

	private void createGeneSourceDetails(String details) {
		getRdfModel().add(getGeneOrganismResource(), PdbOwlVocabulary.Annotation.details.property(), details);
	}

	private Resource getGeneOrganismResource() {
		if (geneOrganismResource == null) {
			geneOrganismResource = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_ORGANISM, pdbId, entityId);
		}
		return geneOrganismResource;
	}

	private Resource getGeneResource() {
		if (geneResource == null) {
			geneResource = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE, pdbId, entityId);
			getRdfModel().add(geneResource, PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					getGeneOrganismResource());
		}
		return geneResource;
	}

	private Resource getVectorResource() {
		if (vectorResource == null) {
			vectorResource = createResource(Bio2RdfPdbUriPattern.VECTOR, pdbId, entityId);
			getRdfModel().add(getGeneResource(), PdbOwlVocabulary.ObjectProperty.isPartOf.property(), vectorResource);
			getRdfModel().add(vectorResource, RDF.type, PdbOwlVocabulary.Class.Vector.resource());
		}
		return vectorResource;
	}

	private Resource getGeneTissueResource() {
		if (geneTissueResource == null) {
			geneTissueResource = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_TISSUE, pdbId, entityId);
			getRdfModel().add(getGeneResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					geneTissueResource);
			getRdfModel().add(geneTissueResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getGeneOrganismResource());
		}
		return geneTissueResource;
	}

	private Resource getGeneTissueFractionResource() {
		if (geneTissueFractionResource == null) {
			geneTissueFractionResource = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_TISSUE_FRACTION, pdbId,
					entityId);
			getRdfModel().add(getGeneResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					geneTissueFractionResource);

			if (geneTissueResource != null) {
				getRdfModel().add(geneTissueFractionResource, PdbOwlVocabulary.ObjectProperty.isDerivedFrom.property(),
						geneTissueResource);
			}
			getRdfModel().add(geneTissueFractionResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getGeneOrganismResource());

		}
		return geneTissueFractionResource;
	}

	private Resource getHostOrganismResource() {
		if (organismResource == null) {
			organismResource = createResource(Bio2RdfPdbUriPattern.ORGANISM, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					organismResource);
			getRdfModel().add(organismResource, PdbOwlVocabulary.ObjectProperty.hasPart.property(), getVectorResource());
		}
		return organismResource;
	}

	private Resource getEntityExtractionResource() {
		if (entityExtractionResource == null) {
			entityExtractionResource = createResource(Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE_EXTRACTION, pdbId,
					entityId);
		}
		return entityExtractionResource;
	}

	private Resource getGeneCellResource() {
		if (geneCellResource == null) {
			geneCellResource = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_CELL, pdbId, entityId);
			getRdfModel().add(getGeneResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(), geneCellResource);
			getRdfModel().add(geneCellResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getGeneOrganismResource());
			if (geneSourceDevelopmentStage != null) {
				Resource devStageQuality = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_CELL_DEV_STAGE, pdbId,
						entityId);
				getRdfModel().add(geneCellResource, PdbOwlVocabulary.ObjectProperty.hasDevelopmentStage.property(),
						devStageQuality);
				getRdfModel().add(devStageQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
						geneSourceDevelopmentStage);
				getRdfModel().add(devStageQuality, RDF.type, PdbOwlVocabulary.Class.DevelopmentStage.resource());
				// reset geneSourceDevelopmentStage to null so it is not added
				// to the organism
				geneSourceDevelopmentStage = null;
			}
		}
		return geneCellResource;
	}

	private Resource getGeneCellularLocationResource() {
		if (geneCellularLocationResource == null) {
			geneCellularLocationResource = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_CELLULAR_LOCATION, pdbId,
					entityId);
			if (geneCellResource != null) {
				getRdfModel().add(geneCellularLocationResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), geneCellResource);
			}
			getRdfModel().add(geneCellularLocationResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getGeneOrganismResource());
		}
		return geneCellularLocationResource;
	}

	private Resource getGeneOrganResource() {
		if (geneOrganResource == null) {
			geneOrganResource = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_ORGAN, pdbId, entityId);
			getRdfModel()
					.add(getGeneResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(), geneOrganResource);
			getRdfModel().add(geneOrganResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getGeneOrganismResource());
			if (geneTissueResource != null) {
				getRdfModel().add(geneTissueResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), geneOrganResource);
			}
		}
		return geneOrganResource;
	}

	private Resource getGeneOrganelleResource() {
		if (geneOrganelleResource == null) {
			geneOrganelleResource = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_ORGANELLE, pdbId, entityId);
			getRdfModel().add(getGeneResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					geneOrganelleResource);

			if (geneCellResource != null) {
				getRdfModel().add(geneOrganelleResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), geneCellResource);
			}
			getRdfModel().add(geneOrganelleResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getGeneOrganismResource());

		}
		return geneOrganelleResource;
	}

	private Resource getGenePlasmidResource() {
		if (genePlasmidResource == null) {
			genePlasmidResource = createResource(Bio2RdfPdbUriPattern.GENE_SOURCE_PLASMID, pdbId, entityId);
			getRdfModel().add(getGeneResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					genePlasmidResource);

			getRdfModel().add(genePlasmidResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getGeneOrganismResource());

		}
		return genePlasmidResource;
	}

	private Resource getHostCellResource() {
		if (cellResource == null) {
			cellResource = createResource(Bio2RdfPdbUriPattern.CELL, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					cellResource);
			getRdfModel().add(cellResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getHostOrganismResource());
		}
		return cellResource;
	}

	private Resource getHostCellularLocationResource() {
		if (cellularLocationResource == null) {
			cellularLocationResource = createResource(Bio2RdfPdbUriPattern.CELLULAR_LOCATION, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					cellularLocationResource);
			getRdfModel().add(cellularLocationResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getHostOrganismResource());
		}
		return cellularLocationResource;
	}

	private Resource getHostGeneReouce() {
		if (hostGeneResource == null) {
			hostGeneResource = createResource(Bio2RdfPdbUriPattern.HOST_GENE, pdbId, entityId);
			getRdfModel().add(hostGeneResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getHostOrganismResource());
		}
		return hostGeneResource;
	}

	private Resource getHostOrganResource() {
		if (organResource == null) {
			organResource = createResource(Bio2RdfPdbUriPattern.ORGAN, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					organResource);
			getRdfModel().add(organResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getHostOrganismResource());
		}
		return organResource;
	}

	private Resource getHostOrganelleResource() {
		if (organelleResource == null) {
			organelleResource = createResource(Bio2RdfPdbUriPattern.ORGANELLE, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					organelleResource);

			if (cellResource != null) {
				getRdfModel().add(organelleResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), cellResource);
			}
			getRdfModel().add(organelleResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getHostOrganismResource());

		}
		return organelleResource;
	}

	private Resource getHostTissueResource() {
		if (tissueResource == null) {
			tissueResource = createResource(Bio2RdfPdbUriPattern.TISSUE, pdbId, entityId);
			getRdfModel().add(getEntityExtractionResource(), PdbOwlVocabulary.ObjectProperty.hasSource.property(),
					tissueResource);

			if (organResource != null) {
				getRdfModel().add(tissueResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), organResource);
			}
			getRdfModel().add(tissueResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getHostOrganismResource());

			if (cellResource != null) {
				getRdfModel().add(cellResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), tissueResource);
			}

		}
		return tissueResource;
	}

	private Resource getHostTissueFractionResource() {
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
			getRdfModel().add(tissueFractionResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getHostOrganismResource());

		}
		return tissueFractionResource;
	}

}
