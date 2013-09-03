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
package com.dumontierlab.pdb2rdf.parser.rna;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.ContentHandlerState;
import com.dumontierlab.pdb2rdf.parser.rna.vocabulary.RnaKbOwlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbXmlVocabulary;
import com.dumontierlab.pdb2rdf.parser.vocabulary.uri.UriBuilder;

/**
 * @author Alexander De Leon
 */
public class RnaPdbXmlContentHandler extends ContentHandlerState {

	private String pdbId;

	public RnaPdbXmlContentHandler(PdbRdfModel model) {
		super(model, new UriBuilder());
		getRdfModel().setNsPrefix("ss", RnaKbOwlVocabulary.DEFAULT_NAMESPACE);
	}

	@Override
	public void startElement(String uri, String localName, String name, Attributes atts) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.DATABLOCK)) {
			pdbId = atts.getValue(PdbXmlVocabulary.DATABLOCK_NAME_ATT);
			getRdfModel().setPdbId(pdbId);
		} else if (localName.equals(PdbXmlVocabulary.NDB_STRUCT_NUCLIC_ACID_BASE_PAIR_CATEGORY)) {
			setState(new StructNucleicAcidBasePairCategoryHandler(getRdfModel(), getUriBuilder(), pdbId));
		}
		super.startElement(uri, localName, name, atts);
	}

	@Override
	public void endElement(String uri, String localName, String name) throws SAXException {
		if (localName.equals(PdbXmlVocabulary.DATABLOCK)
				|| localName.equals(PdbXmlVocabulary.NDB_STRUCT_NUCLIC_ACID_BASE_PAIR_CATEGORY)) {
			setState(null);
		}
		super.endElement(uri, localName, name);
	}

}
