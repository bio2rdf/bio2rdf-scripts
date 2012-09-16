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

import org.apache.log4j.Logger;
import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.Bio2RdfPdbUriPattern;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;
import com.dumontierlab.pdb2rdf.util.UriUtil;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.vocabulary.DCTerms;
import com.hp.hpl.jena.vocabulary.OWL;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;
import com.hp.hpl.jena.vocabulary.XSD;

/**
 * @author Alexander De Leon
 */
public class CitationCategoryHandler extends ContentHandlerState {

	private static final Logger LOG = Logger.getLogger(CitationCategoryHandler.class);

	private final String pdbId;
	private String citationId;
	private String bookIsbn;
	private String journalAbbreviation;

	private Resource publication;
	private Resource book;
	private Resource journal;

	public CitationCategoryHandler(PdbRdfModel rdfModel, UriBuilder uriBuilder, String pdbId) {
		super(rdfModel, uriBuilder);
		this.pdbId = pdbId;
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes attributes) throws SAXException {
		if (PdbXmlVocabulary.CITATION.equals(localName)) {
			citationId = attributes.getValue(PdbXmlVocabulary.ID_ATT);
		} else if (PdbXmlVocabulary.ABSTRACT.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.BOOK_ISBN.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.BOOK_PUBLISHER.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.BOOK_TITLE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.COUNTRY.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.CSD_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.MEDLINE_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.DETAILS.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_ABBREVIATION.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_FULL.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_CSD.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_ISSN.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_VOLUME.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.LANGUAGE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.FIRST_PAGE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.LAST_PAGE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.DOI_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.PUBMED_ID.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.TITLE.equals(localName) && !isNil(attributes)) {
			startBuffering();
		} else if (PdbXmlVocabulary.YEAR.equals(localName) && !isNil(attributes)) {
			startBuffering();
		}

		super.startElement(uri, localName, name, attributes);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (PdbXmlVocabulary.CITATION.equals(localName)) {
			clear();
		} else if (PdbXmlVocabulary.ABSTRACT.equals(localName) && isBuffering()) {
			createAbstract(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.BOOK_ISBN.equals(localName) && isBuffering()) {
			bookIsbn = getBufferContent();
			createISBN();
			stopBuffering();
		} else if (PdbXmlVocabulary.BOOK_PUBLISHER.equals(localName) && isBuffering()) {
			createPublisher(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.BOOK_TITLE.equals(localName) && isBuffering()) {
			createBookTitle(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.COUNTRY.equals(localName) && isBuffering()) {
			createCountryOfOrigin(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.CSD_ID.equals(localName) && isBuffering()) {
			createCSDRefernce(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.MEDLINE_ID.equals(localName) && isBuffering()) {
			createMedlineRefernce(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.DETAILS.equals(localName) && isBuffering()) {
			createDetails(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_ABBREVIATION.equals(localName) && isBuffering()) {
			createJournalAbbreviation(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_FULL.equals(localName) && isBuffering()) {
			createJournalName(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_CSD.equals(localName) && isBuffering()) {
			createJournalCSDReference(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_ISSN.equals(localName) && isBuffering()) {
			createIssn(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.JOURNAL_VOLUME.equals(localName) && isBuffering()) {
			createJournalVolume(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.LANGUAGE.equals(localName) && isBuffering()) {
			createLanguage(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.FIRST_PAGE.equals(localName) && isBuffering()) {
			createFirstPage(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.LAST_PAGE.equals(localName) && isBuffering()) {
			createLastPage(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.DOI_ID.equals(localName) && isBuffering()) {
			createDoiReference(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.PUBMED_ID.equals(localName) && isBuffering()) {
			createPubmedReference(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.TITLE.equals(localName) && isBuffering()) {
			createTitle(getBufferContent());
			stopBuffering();
		} else if (PdbXmlVocabulary.YEAR.equals(localName) && isBuffering()) {
			createYear(getBufferContent());
			stopBuffering();
		}
		super.endElement(uri, localName, name);
	}

	private void createYear(String year) {
		Resource yearQuality = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.PUBLICATION_YEAR, pdbId, citationId));
		Resource publication = getPublicationResource();
		getRdfModel().add(publication, PdbOwlVocabulary.ObjectProperty.hasPublicationYear.property(), yearQuality);
		getRdfModel().add(yearQuality, RDF.type, PdbOwlVocabulary.Class.PublicationYear.resource());
		getRdfModel().add(yearQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(year, XSD.integer.getURI()));
	}

	private void createTitle(String title) {
		Resource titleQuality = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.PUBLICATION_TITLE, pdbId, citationId));
		Resource publication = getPublicationResource();
		getRdfModel().add(publication, PdbOwlVocabulary.ObjectProperty.hasTitle.property(), titleQuality);
		getRdfModel().add(publication, RDFS.label, title);
		getRdfModel().add(titleQuality, RDF.type, PdbOwlVocabulary.Class.Title.resource());
		getRdfModel().add(titleQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), title);
	}

	private void createPubmedReference(String pubmedId) {
		Resource pubmedResource = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.PUBMED, pubmedId));
		Resource pubmedIdQuality = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.PUBMED_ID, pdbId, citationId));
		Resource publication = getPublicationResource();
		getRdfModel().add(publication, OWL.sameAs, pubmedResource);
		getRdfModel().add(publication, PdbOwlVocabulary.ObjectProperty.hasPubmedId.property(), pubmedIdQuality);
		getRdfModel().add(pubmedIdQuality, RDF.type, PdbOwlVocabulary.Class.PubmedId.resource());
		getRdfModel().add(pubmedIdQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), pubmedId);
	}

	private void createDoiReference(String doi) {
		if (doi.startsWith("DOI:")) {
			doi = doi.substring(3);
		}
		Resource doiResource = getRdfModel().createResource(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.DOI, doi));
		Resource doiQuality = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.DOI_NUMBER, pdbId, citationId));
		Resource publication = getPublicationResource();
		getRdfModel().add(publication, OWL.sameAs, doiResource);
		getRdfModel().add(publication, PdbOwlVocabulary.ObjectProperty.hasDOI.property(), doiQuality);
		getRdfModel().add(doiQuality, RDF.type, PdbOwlVocabulary.Class.DOI.resource());
		getRdfModel().add(doiQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), doi);
	}

	private void createFirstPage(String fitstPage) {
		Resource firstPageQuality = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.FIRST_PAGE_NUMBER, pdbId, citationId));
		getRdfModel().add(getPublicationResource(), PdbOwlVocabulary.ObjectProperty.hasFirstPageNumber.property(),
				firstPageQuality);
		getRdfModel().add(firstPageQuality, RDF.type, PdbOwlVocabulary.Class.PageNumber.resource());
		getRdfModel().add(firstPageQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(fitstPage, XSD.integer.getURI()));
	}

	private void createLastPage(String lastPage) {
		Resource lastPageQuality = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.LAST_PAGE_NUMBER, pdbId, citationId));
		getRdfModel().add(getPublicationResource(), PdbOwlVocabulary.ObjectProperty.hasLastPageNumber.property(),
				lastPageQuality);
		getRdfModel().add(lastPageQuality, RDF.type, PdbOwlVocabulary.Class.PageNumber.resource());
		getRdfModel().add(lastPageQuality, PdbOwlVocabulary.DataProperty.hasValue.property(),
				getRdfModel().createTypedLiteral(lastPage, XSD.integer.getURI()));

	}

	private void createLanguage(String lang) {
		Resource language = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.LANGUAGE, UriUtil.urlEncode(UriUtil.toCamelCase(lang))));
		Resource languageName = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.LANGUAGE_NAME, UriUtil.toCamelCase(lang)));

		getRdfModel().add(getPublicationResource(), PdbOwlVocabulary.ObjectProperty.hasLanguage.property(), language);
		getRdfModel().add(language, RDF.type, PdbOwlVocabulary.Class.Language.resource());
		getRdfModel().add(language, PdbOwlVocabulary.ObjectProperty.hasName.property(), languageName);
		getRdfModel().add(languageName, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(languageName, PdbOwlVocabulary.DataProperty.hasValue.property(), lang);
		getRdfModel().add(language, RDFS.label, lang);
	}

	private void createJournalVolume(String volumeNum) {
		Resource volumeResource = null;
		Resource volumeNumResource = null;
		if (journalAbbreviation != null) {
			volumeResource = getRdfModel().createResource(
					getUriBuilder().buildUri(Bio2RdfPdbUriPattern.JOURNAL_VOLUME, journalAbbreviation, volumeNum));
			volumeNumResource = getRdfModel().createResource(
					getUriBuilder().buildUri(Bio2RdfPdbUriPattern.VOLUME_NUMBER, journalAbbreviation, volumeNum));
		} else {
			LOG
					.warn("Using blank node for journal volume because there is no Journal Abbreviation to construct a URI (pdb="
							+ pdbId + ", citationId=" + citationId + ")");
			LOG
					.warn("Using blank node for journal volume number because there is no Journal Abbreviation to construct a URI (pdb="
							+ pdbId + ", citationId=" + citationId + ")");
			volumeResource = getRdfModel().createResource(
					UriUtil
							.anonUri(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ANONYMOUS_NS, pdbId),
									"volumeResource"));
			volumeNumResource = getRdfModel().createResource(
					UriUtil.anonUri(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ANONYMOUS_NS, pdbId),
							"volumeResource:" + volumeNum));
		}
		getRdfModel().add(getPublicationResource(), PdbOwlVocabulary.ObjectProperty.isPublishedIn.property(),
				volumeResource);
		getRdfModel().add(volumeResource, RDF.type, PdbOwlVocabulary.Class.DocumentVolume.resource());
		getRdfModel().add(volumeResource, PdbOwlVocabulary.ObjectProperty.hasVolumeNumber.property(), volumeNumResource);
		getRdfModel().add(volumeNumResource, RDF.type, PdbOwlVocabulary.Class.VolumeNumber.resource());
		getRdfModel().add(volumeNumResource, PdbOwlVocabulary.DataProperty.hasValue.property(), volumeNum);
		getRdfModel().add(volumeResource, PdbOwlVocabulary.ObjectProperty.isPartOf.property(), getJournal());

	}

	private void createIssn(String issn) {
		Resource issnResource = getRdfModel().createResource(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ISSN, issn));
		getRdfModel().add(getJournal(), OWL.sameAs, issnResource);

		Resource issnQuality = getRdfModel()
				.createResource(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ISBN_ID, issn));
		getRdfModel().add(issnQuality, RDF.type, PdbOwlVocabulary.Class.ISSN.resource());
		getRdfModel().add(issnQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), issn);
		getRdfModel().add(getJournal(), PdbOwlVocabulary.ObjectProperty.hasISSN.property(), issnQuality);
	}

	private void createJournalCSDReference(String csdId) {
		Resource csd = getRdfModel().createResource(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CSD, csdId));
		getRdfModel().add(getJournal(), RDFS.seeAlso, csd);
	}

	private void createJournalName(String journalName) {
		Resource journal = getJournal();
		Resource journalNameResource = null;
		if (journalAbbreviation == null) {
			journalNameResource = getRdfModel().createResource(
					UriUtil.anonUri(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ANONYMOUS_NS, pdbId), journalName));
			LOG
					.warn("Using blank node for journal name because there is no Journal Abbreviation to construct a URI (pdb="
							+ pdbId + ", citationId=" + citationId + ")");
		} else {
			journalNameResource = getRdfModel().createResource(
					getUriBuilder().buildUri(Bio2RdfPdbUriPattern.JOURNAL_NAME, journalAbbreviation));
		}
		getRdfModel().add(journal, PdbOwlVocabulary.ObjectProperty.hasName.property(), journalNameResource);
		getRdfModel().add(journalNameResource, PdbOwlVocabulary.DataProperty.hasValue.property(), journalName);
		getRdfModel().add(journalNameResource, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(journal, RDFS.label, journalName);
	}

	private void createJournalAbbreviation(String abbrv) {
		journalAbbreviation = UriUtil.urlEncode(UriUtil.toCamelCase(abbrv));
		Resource journal = getJournal();
		Resource abbrvResource = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.JOURNAL_ABBREVIATION, journalAbbreviation));
		getRdfModel().add(journal, PdbOwlVocabulary.ObjectProperty.hasJournalAbbreviation.property(), abbrvResource);
		getRdfModel().add(abbrvResource, PdbOwlVocabulary.DataProperty.hasValue.property(), abbrv);
		getRdfModel().add(abbrvResource, RDF.type, PdbOwlVocabulary.Class.JournalAbbreviation.resource());

	}

	private void createDetails(String details) {
		Resource publication = getPublicationResource();
		getRdfModel().add(publication, PdbOwlVocabulary.Annotation.details.property(), details);
	}

	private void createMedlineRefernce(String medlineId) {
		Resource publication = getPublicationResource();
		Resource medlineResource = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.MEDLINE, UriUtil.removeSpaces(medlineId)));
		getRdfModel().add(publication, OWL.sameAs, medlineResource);

		Resource medlineIdQuality = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.MEDLINE_ID, pdbId, citationId));
		getRdfModel().add(publication, PdbOwlVocabulary.ObjectProperty.hasMedlineId.property(), medlineIdQuality);
		getRdfModel().add(medlineIdQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), medlineId);
		getRdfModel().add(medlineIdQuality, RDF.type, PdbOwlVocabulary.Class.MedlineId.resource());
	}

	private void createCSDRefernce(String csdId) {
		// delete this publication because the citation is a cross-reference to
		// a database not an actual document
		deletePublication();
		Resource experiment = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.EXPERIMENT, pdbId));
		Resource csd = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.CSD, UriUtil.removeSpaces(csdId)));
		getRdfModel().add(experiment, RDFS.seeAlso, csd);
	}

	private void createCountryOfOrigin(String country) {
		Resource publication = getPublicationResource();
		Resource countryResource = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.COUNTRY, UriUtil.urlEncode(UriUtil.toCamelCase(country))));
		getRdfModel().add(publication, PdbOwlVocabulary.ObjectProperty.hasCountryOfPublication.property(),
				countryResource);
		getRdfModel().add(countryResource, RDFS.label, country);
		getRdfModel().add(countryResource, RDF.type, PdbOwlVocabulary.Class.Country.resource());

		Resource countryName = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.COUNTRY_NAME, UriUtil.toCamelCase(country)));
		getRdfModel().add(countryResource, PdbOwlVocabulary.ObjectProperty.hasName.property(), countryName);
		getRdfModel().add(countryName, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(countryName, PdbOwlVocabulary.DataProperty.hasValue.property(), country);
	}

	private void createBookTitle(String title) {
		Resource book = getBook();
		Resource titleResource = null;
		if (bookIsbn == null) {
			LOG.warn(pdbId + " - Using annonymous node for book title because there is no ISBN to construct a URI");
			titleResource = getRdfModel().createResource(
					UriUtil.anonUri(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ANONYMOUS_NS, pdbId), title));
		} else {
			titleResource = getRdfModel().createResource(
					getUriBuilder().buildUri(Bio2RdfPdbUriPattern.BOOK_TITLE, bookIsbn));
		}
		getRdfModel().add(book, PdbOwlVocabulary.ObjectProperty.hasTitle.property(), titleResource);
		getRdfModel().add(titleResource, PdbOwlVocabulary.DataProperty.hasValue.property(), title);
		getRdfModel().add(titleResource, RDF.type, PdbOwlVocabulary.Class.Title.resource());
	}

	private void createPublisher(String publisherName) {
		Resource book = getBook();
		Resource publisher = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.PUBLISHER,
						UriUtil.urlEncode(UriUtil.toCamelCase(publisherName))));
		getRdfModel().add(book, PdbOwlVocabulary.ObjectProperty.hasPublisher.property(), publisher);
		getRdfModel().add(publisher, RDF.type, PdbOwlVocabulary.Class.Publisher.resource());
		getRdfModel().add(publisher, RDFS.label, publisherName);
		Resource nameQuality = createResource(Bio2RdfPdbUriPattern.PUBLISHER_NAME, UriUtil.toCamelCase(publisherName));
		getRdfModel().add(publisher, PdbOwlVocabulary.ObjectProperty.hasName.property(), nameQuality);
		getRdfModel().add(nameQuality, RDF.type, PdbOwlVocabulary.Class.Name.resource());
		getRdfModel().add(nameQuality, PdbOwlVocabulary.DataProperty.hasValue.property(), publisherName);
	}

	private void createISBN() {
		Resource book = getBook();
		Resource isbnResource = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ISBN_ID, bookIsbn));
		getRdfModel().add(book, PdbOwlVocabulary.ObjectProperty.hasISBN.property(), isbnResource);
		getRdfModel().add(isbnResource, RDF.type, PdbOwlVocabulary.Class.ISBN.resource());
		getRdfModel().add(isbnResource, PdbOwlVocabulary.DataProperty.hasValue.property(), bookIsbn);
	}

	private void createAbstract(String abstractText) {
		Resource publication = getPublicationResource();
		Resource abstractResource = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ABSTRACT, pdbId, citationId));
		getRdfModel().add(publication, PdbOwlVocabulary.ObjectProperty.hasDocumentSection.property(), abstractResource);
		getRdfModel().add(abstractResource, RDF.type, PdbOwlVocabulary.Class.Abstrat.resource());
		getRdfModel().add(abstractResource, PdbOwlVocabulary.DataProperty.hasValue.property(), abstractText);

	}

	private void deletePublication() {
		Resource experiment = getRdfModel().createResource(
				getUriBuilder().buildUri(Bio2RdfPdbUriPattern.EXPERIMENT, pdbId));
		Resource publication = getPublicationResource();
		getRdfModel().remove(experiment, PdbOwlVocabulary.ObjectProperty.hasPublication.property(), publication);
		getRdfModel().remove(publication, RDF.type, PdbOwlVocabulary.Class.Publication.resource());
	}

	private Resource getPublicationResource() {
		if (publication == null) {
			assert citationId != null : "Creating a publication but no citationId exists. PDB=" + pdbId;
			publication = getRdfModel().createResource(
					getUriBuilder().buildUri(Bio2RdfPdbUriPattern.PUBLICATION, pdbId, citationId));
			Resource experiment = getRdfModel().createResource(
					getUriBuilder().buildUri(Bio2RdfPdbUriPattern.EXPERIMENT, pdbId));
			getRdfModel().add(experiment, PdbOwlVocabulary.ObjectProperty.hasPublication.property(), publication);
			getRdfModel().add(publication, RDF.type, PdbOwlVocabulary.Class.Publication.resource());
		}
		return publication;
	}

	private Resource getBook() {
		if (book == null) {
			if (bookIsbn == null) {
				LOG.warn(pdbId + " - Refering to a book which has no ISBN. A blank node will be used.");
				book = getRdfModel().createResource(
						UriUtil.anonUri(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ANONYMOUS_NS, pdbId), null));

			} else {
				book = getRdfModel().createResource(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.BOOK, bookIsbn));
			}
			Resource publication = getPublicationResource();
			getRdfModel().add(book, RDF.type, PdbOwlVocabulary.Class.Book.resource());
			getRdfModel().add(publication, PdbOwlVocabulary.ObjectProperty.isPublishedIn.property(), book);
		}
		return book;
	}

	private Resource getJournal() {
		if (journal == null) {
			if (journalAbbreviation == null) {
				LOG.warn(pdbId + " - No Journal abbreviation (pdb=" + pdbId + ", citationId=" + citationId
						+ "). Using blanknode for journal");
				journal = getRdfModel().createResource(
						UriUtil.anonUri(getUriBuilder().buildUri(Bio2RdfPdbUriPattern.ANONYMOUS_NS, pdbId), null));
			} else {
				journal = getRdfModel().createResource(
						getUriBuilder().buildUri(Bio2RdfPdbUriPattern.JOURNAL, journalAbbreviation));
			}
			getRdfModel().add(journal, RDF.type, PdbOwlVocabulary.Class.Journal.resource());
			getRdfModel().add(getPublicationResource(), PdbOwlVocabulary.ObjectProperty.isPublishedIn.property(),
					journal);
		}
		return journal;
	}

}
