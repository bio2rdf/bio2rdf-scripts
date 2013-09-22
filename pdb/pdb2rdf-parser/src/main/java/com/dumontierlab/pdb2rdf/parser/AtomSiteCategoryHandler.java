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

import java.util.Collection;
import java.util.HashSet;
import java.util.List;
import java.util.Map;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.external.Pdb2Rdf2Uniprot;
import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PeriodicTable;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.dumontierlab.pdb2rdf.util.UriUtil;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;
import com.hp.hpl.jena.vocabulary.XSD;

/**
 * @author Jose Cruz-Toledo
 * @author Alexander De Leon
 */
public class AtomSiteCategoryHandler extends ContentHandlerState {

	private final String pdbId;
	private String atomSiteId;
	private String chainId;
	private String atomName;
	private String residueId;
	private String chainPosition;
	private String modelNumber;

	private Resource modelResource;
	private Resource atomLocationResource;
	private Resource atomResource;
	private Resource residueResource;
	private Resource chainResource;

	private String bEquivGeomMean;
	private String bEquivGeomMeanEsd;
	private String bIsoOrEquiv;
	private String bIsoOrEquivEsd;
	private String cartnX;
	private String cartnXEsd;
	private String cartnY;
	private String cartnYEsd;
	private String cartnZ;
	private String cartnZEsd;
	private String uEquivGeomMean;
	private String uEquivGeomMeanEsd;
	private String uIsoOrEquiv;
	private String uIsoOrEquivEsd;
	private String wyckoffSymbol;
	private String anisoB11;
	private String anisoB11Esd;
	private String anisoB12;
	private String anisoB12Esd;
	private String anisoB13;
	private String anisoB13Esd;
	private String anisoB22;
	private String anisoB22Esd;
	private String anisoB23;
	private String anisoB23Esd;
	private String anisoB33;
	private String anisoB33Esd;
	private String anisoU11;
	private String anisoU11Esd;
	private String anisoU12;
	private String anisoU12Esd;
	private String anisoU13;
	private String anisoU13Esd;
	private String anisoU22;
	private String anisoU22Esd;
	private String anisoU23;
	private String anisoU23Esd;
	private String anisoU33;
	private String anisoU33Esd;
	private String anisoRatio;
	private String attachedHydrogens;
	private String calcAttachedAtom;
	private String chemicalConnNumber;
	private String constraints;
	private String disorderAssembly;
	private String disorderGroup;
	private String footnoteId;
	private String fractX;
	private String fractXEsd;
	private String fractY;
	private String fractYEsd;
	private String fractZ;
	private String fractZEsd;
	private String groupPDB;
	private String occupancy;
	private String occupancyEsd;
	private String pdbxFormalCharge;
	private String pdbxNcsDomId;
	private String pdbxTlsGroupId;
	private String restraints;
	private String symmetryMultiplicity;

	private String labelCompId;
	private String labelEntityId;

	private final Map<String, Collection<Resource>> residues;
	private Resource chemicalSubstanceResource;

	public AtomSiteCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder,
			String pdbId, Map<String, Collection<Resource>> residues) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
		this.residues = residues;
	}

	@Override
	public void startElement(String uri, String localName, String name,
			Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.ATOM_SITE.equals(localName)) {
			atomSiteId = attributes.getValue(PdbXmlVocabulary.ID_ATT);
		} else if (PdbXmlVocabulary.AUTH_ASYM_ID.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.AUTH_ATOM_ID.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.AUTH_COMP_ID.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.AUTH_SEQ_ID.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.MODEL_NUMBER.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.B_EQUIVALENT_GEOMETRIC_MEAN
				.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.B_EQUIVALENT_GEOMETRIC_MEAN_ESD
				.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.B_ISO_OR_EQUIVALENT.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.B_ISO_OR_EQUIVALENT_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CARTN_X.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CARTN_X_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CARTN_Y.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CARTN_Y_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CARTN_Z.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CARTN_Z_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.U_EQUIVALENT_GEOMETRIC_MEAN
				.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.U_EQUIVALENT_GEOMETRIC_MEAN_ESD
				.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.U_ISO_OR_EQUIVALENT.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.U_ISO_OR_EQUIVALENT_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.WYCKOFF_SYMBOL.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B11.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B11_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B12.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B12_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B13.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B13_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B22.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B22_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B23.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B23_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B33.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_B33_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U11.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U11_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U12.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U12_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U13.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U13_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U22.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U22_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U23.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U23_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U33.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_U33_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ANISO_RATIO.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.ATTACHED_HYDROGENS.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CALC_ATTACHED_ATOM.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CHEMICAL_CONN_NUMBER.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CONSTRAINTS.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.DISORDER_ASSEMBLY.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.DISORDER_GROUP.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FOOTNOTE_ID.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FRACTION_X.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FRACTION_X_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FRACTION_Y.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FRACTION_Y_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FRACTION_Z.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FRACTION_Z_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.GROUP_PDB.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.OCCUPANCY.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.OCCUPANCY_ESD.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PDBX_FORMAL_CHARGE.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PDBX_NCS_DOM_ID.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PDBX_TLS_GROUP_ID.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.RESTRAINTS.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.SYMMETRY_MULTIPLICITY.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.TYPE_SYMBOL.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.LABEL_COMP_ID.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.LABEL_ENTITY_ID.equals(localName)
				&& !isNil(attributes)) {
			startBuffering();
		}

		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name)
			throws SAXException {
		if (PdbXmlVocabulary.ATOM_SITE.equals(localName)) {
			createRdf();
			clear();
		} else if (PdbXmlVocabulary.AUTH_ASYM_ID.equals(localName)
				&& isBuffering()) {
			chainId = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.AUTH_ATOM_ID.equals(localName)
				&& isBuffering()) {
			atomName = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.AUTH_COMP_ID.equals(localName)
				&& isBuffering()) {
			residueId = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.AUTH_SEQ_ID.equals(localName)
				&& isBuffering()) {
			chainPosition = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.AUTH_SEQ_ID.equals(localName)
				&& isBuffering()) {
			chainPosition = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.MODEL_NUMBER.equals(localName)
				&& isBuffering()) {
			modelNumber = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.B_EQUIVALENT_GEOMETRIC_MEAN
				.equals(localName) && isBuffering()) {
			bEquivGeomMean = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.B_EQUIVALENT_GEOMETRIC_MEAN_ESD
				.equals(localName) && isBuffering()) {
			bEquivGeomMeanEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.B_ISO_OR_EQUIVALENT.equals(localName)
				&& isBuffering()) {
			bIsoOrEquiv = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.B_ISO_OR_EQUIVALENT_ESD.equals(localName)
				&& isBuffering()) {
			bIsoOrEquivEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.CARTN_X.equals(localName) && isBuffering()) {
			cartnX = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.CARTN_X_ESD.equals(localName)
				&& isBuffering()) {
			cartnXEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.CARTN_Y.equals(localName) && isBuffering()) {
			cartnY = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.CARTN_Y_ESD.equals(localName)
				&& isBuffering()) {
			cartnYEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.CARTN_Z.equals(localName) && isBuffering()) {
			cartnZ = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.CARTN_Z_ESD.equals(localName)
				&& isBuffering()) {
			cartnZEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.U_EQUIVALENT_GEOMETRIC_MEAN
				.equals(localName) && isBuffering()) {
			uEquivGeomMean = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.U_EQUIVALENT_GEOMETRIC_MEAN_ESD
				.equals(localName) && isBuffering()) {
			uEquivGeomMeanEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.U_ISO_OR_EQUIVALENT.equals(localName)
				&& isBuffering()) {
			uIsoOrEquiv = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.U_ISO_OR_EQUIVALENT_ESD.equals(localName)
				&& isBuffering()) {
			uIsoOrEquivEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.WYCKOFF_SYMBOL.equals(localName)
				&& isBuffering()) {
			wyckoffSymbol = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B11.equals(localName)
				&& isBuffering()) {
			anisoB11 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B11_ESD.equals(localName)
				&& isBuffering()) {
			anisoB11Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B12.equals(localName)
				&& isBuffering()) {
			anisoB12 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B12_ESD.equals(localName)
				&& isBuffering()) {
			anisoB12Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B13.equals(localName)
				&& isBuffering()) {
			anisoB13 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B13_ESD.equals(localName)
				&& isBuffering()) {
			anisoB13Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B22.equals(localName)
				&& isBuffering()) {
			anisoB22 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B22_ESD.equals(localName)
				&& isBuffering()) {
			anisoB22Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B23.equals(localName)
				&& isBuffering()) {
			anisoB23 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B23_ESD.equals(localName)
				&& isBuffering()) {
			anisoB23Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B33.equals(localName)
				&& isBuffering()) {
			anisoB33 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_B33_ESD.equals(localName)
				&& isBuffering()) {
			anisoB33Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U11.equals(localName)
				&& isBuffering()) {
			anisoU11 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U11_ESD.equals(localName)
				&& isBuffering()) {
			anisoU11Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U12.equals(localName)
				&& isBuffering()) {
			anisoU12 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U12_ESD.equals(localName)
				&& isBuffering()) {
			anisoU12Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U13.equals(localName)
				&& isBuffering()) {
			anisoU13 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U13_ESD.equals(localName)
				&& isBuffering()) {
			anisoU13Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U22.equals(localName)
				&& isBuffering()) {
			anisoU22 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U22_ESD.equals(localName)
				&& isBuffering()) {
			anisoU22Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U23.equals(localName)
				&& isBuffering()) {
			anisoU23 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U23_ESD.equals(localName)
				&& isBuffering()) {
			anisoU23Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U33.equals(localName)
				&& isBuffering()) {
			anisoU33 = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_U33_ESD.equals(localName)
				&& isBuffering()) {
			anisoU33Esd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ANISO_RATIO.equals(localName)
				&& isBuffering()) {
			anisoRatio = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.ATTACHED_HYDROGENS.equals(localName)
				&& isBuffering()) {
			attachedHydrogens = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.CALC_ATTACHED_ATOM.equals(localName)
				&& isBuffering()) {
			calcAttachedAtom = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.CHEMICAL_CONN_NUMBER.equals(localName)
				&& isBuffering()) {
			chemicalConnNumber = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.CONSTRAINTS.equals(localName)
				&& isBuffering()) {
			constraints = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.DISORDER_ASSEMBLY.equals(localName)
				&& isBuffering()) {
			disorderAssembly = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.DISORDER_GROUP.equals(localName)
				&& isBuffering()) {
			disorderGroup = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.FOOTNOTE_ID.equals(localName)
				&& isBuffering()) {
			footnoteId = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.FRACTION_X.equals(localName)
				&& isBuffering()) {
			fractX = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.FRACTION_X_ESD.equals(localName)
				&& isBuffering()) {
			fractXEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.FRACTION_Y.equals(localName)
				&& isBuffering()) {
			fractY = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.FRACTION_Y_ESD.equals(localName)
				&& isBuffering()) {
			fractYEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.FRACTION_Z.equals(localName)
				&& isBuffering()) {
			fractZ = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.FRACTION_Z_ESD.equals(localName)
				&& isBuffering()) {
			fractZEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.GROUP_PDB.equals(localName)
				&& isBuffering()) {
			groupPDB = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.OCCUPANCY.equals(localName)
				&& isBuffering()) {
			occupancy = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.OCCUPANCY_ESD.equals(localName)
				&& isBuffering()) {
			occupancyEsd = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.PDBX_FORMAL_CHARGE.equals(localName)
				&& isBuffering()) {
			pdbxFormalCharge = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.PDBX_NCS_DOM_ID.equals(localName)
				&& isBuffering()) {
			pdbxNcsDomId = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.PDBX_TLS_GROUP_ID.equals(localName)
				&& isBuffering()) {
			pdbxTlsGroupId = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.RESTRAINTS.equals(localName)
				&& isBuffering()) {
			restraints = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.SYMMETRY_MULTIPLICITY.equals(localName)
				&& isBuffering()) {
			symmetryMultiplicity = getBufferContent();
			stopBuffering();

		} else if (PdbXmlVocabulary.LABEL_COMP_ID.equals(localName)
				&& isBuffering()) {
			labelCompId = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.LABEL_ENTITY_ID.equals(localName)
				&& isBuffering()) {
			labelEntityId = getBufferContent();
			stopBuffering();
		} else if (PdbXmlVocabulary.TYPE_SYMBOL.equals(localName)
				&& isBuffering()) {
			createAtomType(getBufferContent());
		}

		super.endElement(uri, localName, name);
	}

	private void createAtomType(String typeSymbol) {
		if (getDetailLevel().hasAtom()) {
			Resource atomType = PeriodicTable.get(typeSymbol).resource();
			Resource atom = getAtomResource();
			getRdfModel().add(atom, RDF.type, atomType);
		}
	}

	private void createRdf() {
		// createBEquivGeomMean();
		if (bIsoOrEquiv != null && getDetailLevel().hasAtomLocation()) {
			createBIsoOrEquiv();
		}
		if (cartnX != null && getDetailLevel().hasAtomLocation()) {
			createCartnX();
		}
		if (cartnY != null && getDetailLevel().hasAtomLocation()) {
			createCartnY();
		}
		if (cartnZ != null && getDetailLevel().hasAtomLocation()) {
			createCartnZ();
		}
		if (occupancy != null && getDetailLevel().hasAtomLocation()) {
			createOccupancy();
		}
		if (pdbxFormalCharge != null && getDetailLevel().hasAtomLocation()) {
			createFormalCharge();
		}
		// In the case where the detail level is at the residue deph then we
		// need to explicit call getResidue() in order to generate the residue
		// resource.
		if (getDetailLevel().hasResidue()) {
			getResidue();
		}
	}

	private void createFormalCharge() {
		Resource quality = createResource(Bio2RdfPdbUriPattern.FORMAL_CHARGE,
				pdbId, atomSiteId);
		getRdfModel().add(getAtomLocationResource(),
				PdbOwlVocabulary.ObjectProperty.hasFormalCharge.property(),
				quality);
		getRdfModel().add(quality,
				PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(pdbxFormalCharge));
		getRdfModel().add(quality, RDF.type,
				PdbOwlVocabulary.Class.PartialCharge.resource());
	}

	private void createOccupancy() {
		Resource quality = createResource(Bio2RdfPdbUriPattern.OCCUPANCY,
				pdbId, atomSiteId);
		getRdfModel().add(getAtomLocationResource(),
				PdbOwlVocabulary.ObjectProperty.hasOccupancy.property(),
				quality);
		getRdfModel().add(quality,
				PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(occupancy));
		getRdfModel().add(quality, RDF.type,
				PdbOwlVocabulary.Class.AtomOccupancy.resource());
		if (occupancyEsd != null) {
			getRdfModel().add(
					quality,
					PdbOwlVocabulary.DataProperty.hasStandardDeviation
							.property(), createDecimalLiteral(occupancyEsd));
		}
	}

	private void createCartnZ() {
		Resource quality = createResource(Bio2RdfPdbUriPattern.CARTN_Z, pdbId,
				atomSiteId);
		getRdfModel().add(getAtomLocationResource(),
				PdbOwlVocabulary.ObjectProperty.hasZCoordinate.property(),
				quality);
		getRdfModel().add(quality,
				PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(cartnZ));
		getRdfModel().add(quality, RDF.type,
				PdbOwlVocabulary.Class.ZCartesianCoordinate.resource());
		if (cartnZEsd != null) {
			getRdfModel().add(
					quality,
					PdbOwlVocabulary.DataProperty.hasStandardDeviation
							.property(), createDecimalLiteral(cartnZEsd));
		}
	}

	private void createCartnY() {
		Resource quality = createResource(Bio2RdfPdbUriPattern.CARTN_Y, pdbId,
				atomSiteId);
		getRdfModel().add(getAtomLocationResource(),
				PdbOwlVocabulary.ObjectProperty.hasYCoordinate.property(),
				quality);
		getRdfModel().add(quality,
				PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(cartnY));
		getRdfModel().add(quality, RDF.type,
				PdbOwlVocabulary.Class.YCartesianCoordinate.resource());
		if (cartnYEsd != null) {
			getRdfModel().add(
					quality,
					PdbOwlVocabulary.DataProperty.hasStandardDeviation
							.property(), createDecimalLiteral(cartnYEsd));
		}
	}

	private void createCartnX() {
		Resource quality = createResource(Bio2RdfPdbUriPattern.CARTN_X, pdbId,
				atomSiteId);
		getRdfModel().add(getAtomLocationResource(),
				PdbOwlVocabulary.ObjectProperty.hasXCoordinate.property(),
				quality);
		getRdfModel().add(quality,
				PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(cartnX));
		getRdfModel().add(quality, RDF.type,
				PdbOwlVocabulary.Class.XCartesianCoordinate.resource());
		if (cartnXEsd != null) {
			getRdfModel().add(
					quality,
					PdbOwlVocabulary.DataProperty.hasStandardDeviation
							.property(), createDecimalLiteral(cartnXEsd));
		}

	}

	private void createBIsoOrEquiv() {
		Resource quality = createResource(
				Bio2RdfPdbUriPattern.B_ISO_OR_EQUIVALENT, pdbId, atomSiteId);
		getRdfModel().add(
				getAtomLocationResource(),
				PdbOwlVocabulary.ObjectProperty.hasIsotropicAtomicDisplacement
						.property(), quality);
		getRdfModel().add(quality,
				PdbOwlVocabulary.DataProperty.hasValue.property(),
				createDecimalLiteral(bIsoOrEquiv));
		getRdfModel().add(quality, RDF.type,
				PdbOwlVocabulary.Class.IsotropicAtomicDisplacement.resource());
		if (bIsoOrEquivEsd != null) {
			getRdfModel().add(
					quality,
					PdbOwlVocabulary.DataProperty.hasStandardDeviation
							.property(), createDecimalLiteral(bIsoOrEquivEsd));
		}

	}

	private Resource getAtomResource() {
		assert chainId != null && atomName != null && residueId != null
				&& chainPosition != null : "The chainId, atomName, residueId and chainPosition are needed for creating this resorce";
		if (atomResource == null) {
			atomResource = createResource(Bio2RdfPdbUriPattern.ATOM, pdbId,
					chainId, chainPosition,
					UriUtil.urlEncode(UriUtil.replacePrimes(atomName)));

			if (groupPDB.equals(PdbXmlVocabulary.PDB_GROUP_ATOM_VALUE)) {
				getRdfModel().add(getResidue(),
						PdbOwlVocabulary.ObjectProperty.hasPart.property(),
						atomResource);
			} else {
				getRdfModel().add(atomResource,
						PdbOwlVocabulary.ObjectProperty.isPartOf.property(),
						getChemicalSubstanceResource());
			}
			getRdfModel().add(atomResource, RDFS.label, atomName);
		}
		return atomResource;
	}

	private Resource getChemicalSubstanceResource() {
		if (chemicalSubstanceResource == null) {
			chemicalSubstanceResource = createResource(
					Bio2RdfPdbUriPattern.CHEMICAL_SUBSTANCE, pdbId,
					labelEntityId);
		}
		return chemicalSubstanceResource;
	}

	private Resource getModelResource() {
		assert modelNumber != null : "The model number is needed for creating this resource";
		if (modelResource == null) {
			modelResource = createResource(Bio2RdfPdbUriPattern.MODEL, pdbId,
					modelNumber);
			// add the statement that the structure determination has product
			// this model
			Resource structureDetermination = createResource(
					Bio2RdfPdbUriPattern.STRUCTURE_DETERMINATION, pdbId);
			getRdfModel().add(structureDetermination,
					PdbOwlVocabulary.ObjectProperty.hasProduct.property(),
					modelResource);
			getRdfModel().add(modelResource, RDF.type,
					PdbOwlVocabulary.Class.Model.resource());
			getRdfModel()
					.add(modelResource, RDFS.label, "Model " + modelNumber);

			// add link to uniprot
			Pdb2Rdf2Uniprot uniprot = new Pdb2Rdf2Uniprot(pdbId);
			List<String> uniprotMappings = uniprot.getUniprotMappings();
			for (String uniprotId : uniprotMappings) {
				getRdfModel().add(
						modelResource,
						PdbOwlVocabulary.ObjectProperty.hasCrossReference
								.property(), createUniprotResource(uniprotId));
			}
			List<String> goMappings = uniprot.getGoMappings();
			for (String goId : goMappings) {
				getRdfModel().add(
						modelResource,
						PdbOwlVocabulary.ObjectProperty.hasCrossReference
								.property(), createGoResource(goId));
			}
		}
		return modelResource;
	}

	private Resource createUniprotResource(String uniprotId) {
		Resource uniprot = getRdfModel().createResource(
				getUriBuilder().buildUri(
						Bio2RdfPdbUriPattern.UNIPROT_CROSS_REFERENCE, pdbId,
						uniprotId));
		getRdfModel().add(uniprot, RDF.type,
				PdbOwlVocabulary.Class.UniprotCrossReference.resource());
		getRdfModel().add(uniprot,
				PdbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createLiteral(uniprotId));
		getRdfModel().add(
				uniprot,
				RDFS.seeAlso,
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.UNIPROT,
						uniprotId));
		return uniprot;
	}

	private Resource createGoResource(String goId) {
		Resource goResource = getRdfModel().createResource(
				getUriBuilder().buildUri(
						Bio2RdfPdbUriPattern.GO_CROSS_REFERENCE, pdbId, goId));
		getRdfModel().add(goResource, RDF.type,
				PdbOwlVocabulary.Class.GoCrossReference.resource());
		getRdfModel().add(goResource,
				PdbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createLiteral(goId));
		getRdfModel().add(goResource, RDFS.seeAlso,
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.GO, goId));
		return goResource;
	}

	private Resource getAtomLocationResource() {
		assert modelNumber != null : "The model number is needed for creating this resource";
		if (atomLocationResource == null) {
			atomLocationResource = createResource(
					Bio2RdfPdbUriPattern.ATOM_SPATIAL_LOCATION, pdbId,
					atomSiteId);
			getRdfModel().add(getModelResource(),
					PdbOwlVocabulary.ObjectProperty.hasPart.property(),
					atomLocationResource);
			getRdfModel().add(
					getAtomResource(),
					PdbOwlVocabulary.ObjectProperty.hasSpatialLocation
							.property(), atomLocationResource);
			getRdfModel().add(atomLocationResource, RDF.type,
					PdbOwlVocabulary.Class.AtomSpatialLocation.resource());

		}
		return atomLocationResource;
	}

	private Resource getResidue() {
		PdbRdfModel m = getRdfModel();
		assert chainId != null && chainPosition != null && labelCompId != null : "The chain name and position is needed for creating this resource";
		residueResource = createResource(Bio2RdfPdbUriPattern.RESIDUE, pdbId,
				chainId, chainPosition);
		if (residueResource != null && m != null) {
			addResidue(residueResource, labelCompId);
			// getRdfModel().add(residueResource, RDFS.label, residueId);
			getRdfModel().add(residueResource, RDF.type,
					PdbOwlVocabulary.Class.Residue.resource());
			int pos = Integer.parseInt(chainPosition);
			if (pos != 1) {
				Resource previousResidueResource = createResource(
						Bio2RdfPdbUriPattern.RESIDUE, pdbId, chainId,
						Integer.toString(pos - 1));
				getRdfModel().add(
						previousResidueResource,
						PdbOwlVocabulary.ObjectProperty.isImmediatelyBefore
								.property(), residueResource);
			}
			getRdfModel().add(residueResource,
					PdbOwlVocabulary.ObjectProperty.isPartOf.property(),
					getChemicalSubstanceResource());

			// Add its position on the chain
			Resource chainPositionResource = createResource(
					Bio2RdfPdbUriPattern.CHAIN_POSITION, pdbId, chainId,
					chainPosition);
			getRdfModel()
					.add(residueResource,
							PdbOwlVocabulary.ObjectProperty.hasChainPosition
									.property(), chainPositionResource);
			getRdfModel().add(chainPositionResource, RDF.type,
					PdbOwlVocabulary.Class.ChainPosition.resource());
			getRdfModel().add(chainPositionResource,
					PdbOwlVocabulary.DataProperty.hasValue.property(),
					createLiteral(chainPosition, XSD.integer.getURI()));
			getRdfModel().add(chainPositionResource,
					PdbOwlVocabulary.ObjectProperty.isPartOf.property(),
					getChain());
			getRdfModel().add(chainPositionResource, RDFS.label,
					"Position " + chainPosition + " on chain " + chainId);
		}

		return residueResource;
	}

	private void addResidue(Resource residue, String labelCompId) {
		if (getDetailLevel().hasResidue()) {
			Collection<Resource> residuesResources = residues.get(labelCompId);
			if (residuesResources == null) {
				residuesResources = new HashSet<Resource>();
				residues.put(labelCompId, residuesResources);
			}
			residuesResources.add(residue);
		}

	}

	private Resource getChain() {
		if (chainResource == null) {
			chainResource = createResource(Bio2RdfPdbUriPattern.CHAIN, pdbId,
					chainId);
			getRdfModel().add(chainResource, RDF.type,
					PdbOwlVocabulary.Class.Chain.resource());
			getRdfModel().add(RDFS.label, RDF.type,
					PdbOwlVocabulary.Class.Resource.resource());
			getRdfModel().add(chainResource, RDFS.label, "Chain " + chainId);

		}
		return chainResource;
	}

}
