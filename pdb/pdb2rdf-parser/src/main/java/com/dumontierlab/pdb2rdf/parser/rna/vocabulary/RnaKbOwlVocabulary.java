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
package com.dumontierlab.pdb2rdf.parser.rna.vocabulary;

import com.hp.hpl.jena.ontology.DatatypeProperty;
import com.hp.hpl.jena.ontology.OntClass;
import com.hp.hpl.jena.ontology.OntModel;
import com.hp.hpl.jena.rdf.model.ModelFactory;

/**
 * @author Alexander De Leon
 */
public class RnaKbOwlVocabulary {

	public static final String DEFAULT_NAMESPACE = "http://semanticscience.org/";

	private static final OntModel model = ModelFactory.createOntologyModel();
	static {
		model.setNsPrefix("ss", DEFAULT_NAMESPACE);
	}

	public static enum Class {
		Opening(DEFAULT_NAMESPACE + "Opening"), Propeller(DEFAULT_NAMESPACE + "Propeller"), Shear(DEFAULT_NAMESPACE
				+ "Shear"), Stagger(DEFAULT_NAMESPACE + "Stagger"), Stretch(DEFAULT_NAMESPACE + "Stretch"), CisWatsonWatsonBasePair(
				DEFAULT_NAMESPACE + "CisWatsonWatsonBasePair"), TransWatsonWatsonBasePair(DEFAULT_NAMESPACE
				+ "TransWatsonWatsonBasePair"), CisWatsonHoogsteenBasePair(DEFAULT_NAMESPACE
				+ "CisWatsonHoogsteenBasePair"), TransWatsonHoogsteenBasePair(DEFAULT_NAMESPACE
				+ "TransWatsonHoogsteenBasePair"), CisWatsonSugarBasePair(DEFAULT_NAMESPACE + "CisWatsonSugarBasePair"), TransWatsonSugarBasePair(
				DEFAULT_NAMESPACE + "TransWatsonSugarBasePair"), CisHoogsteenHoogsteenBasePair(DEFAULT_NAMESPACE
				+ "CisHoogsteenHoogsteenBasePair"), TransHoogsteenHogsteenBasePair(DEFAULT_NAMESPACE
				+ "TransHoogsteenHogsteenBasePair"), TransHoogsteenSugarBasePair(DEFAULT_NAMESPACE
				+ "TransHoogsteenSugarBasePair"), TransSugarSugarBasePair("TransSugarSugarBasePair"), CisHoogsteenSugarBasePair(
				DEFAULT_NAMESPACE + "CisHoogsteenSugarBasePair"), CisSugarSugarBasePair(DEFAULT_NAMESPACE
				+ "CisSugarSugarBasePair"), Buckle(DEFAULT_NAMESPACE + "Buckle"), I(DEFAULT_NAMESPACE + "I"), II(
				DEFAULT_NAMESPACE + "II"), III(DEFAULT_NAMESPACE + "III"), IV(DEFAULT_NAMESPACE + "IV"), V(
				DEFAULT_NAMESPACE + "V"), VI(DEFAULT_NAMESPACE + "VI"), VII(DEFAULT_NAMESPACE + "VII"), VIII(
				DEFAULT_NAMESPACE + "VIII"), IX(DEFAULT_NAMESPACE + "IX"), X(DEFAULT_NAMESPACE + "X"), XI(
				DEFAULT_NAMESPACE + "XI"), XII(DEFAULT_NAMESPACE + "XII"), XIII(DEFAULT_NAMESPACE + "XIII"), XIV(
				DEFAULT_NAMESPACE + "XIV"), XV(DEFAULT_NAMESPACE + "XV"), XVI(DEFAULT_NAMESPACE + "XVI"), XVII(
				DEFAULT_NAMESPACE + "XVII"), XVIII(DEFAULT_NAMESPACE + "XVIII"), XIX(DEFAULT_NAMESPACE + "XIX"), XX(
				DEFAULT_NAMESPACE + "XX"), XXI(DEFAULT_NAMESPACE + "XXI"), XXII(DEFAULT_NAMESPACE + "XXII"), XXIII(
				DEFAULT_NAMESPACE + "XXIII"), XXIV(DEFAULT_NAMESPACE + "XXIV"), XXV(DEFAULT_NAMESPACE + "XXV"), XXVI(
				DEFAULT_NAMESPACE + "XXVI"), XXVII(DEFAULT_NAMESPACE + "XXVII"), XXVIII(DEFAULT_NAMESPACE + "XXVIII");

		private final String uri;

		private Class(String uri) {
			this.uri = uri;
		}

		@Override
		public String toString() {
			return uri;
		}

		public String uri() {
			return uri;
		}

		public OntClass resource() {
			return model.createClass(uri);
		}
	};

	public static enum ObjectProperty {
		hasQuality(DEFAULT_NAMESPACE + "hasQuality");

		private final String uri;

		private ObjectProperty(String uri) {
			this.uri = uri;
		}

		@Override
		public String toString() {
			return uri;
		}

		public String uri() {
			return uri;
		}

		public com.hp.hpl.jena.ontology.ObjectProperty property() {
			return model.createObjectProperty(uri);
		}
	};

	public static enum DataProperty {

		hasValue(DEFAULT_NAMESPACE + "hasValue");

		private final String uri;

		private DataProperty(String uri) {
			this.uri = uri;
		}

		@Override
		public String toString() {
			return uri;
		}

		public String uri() {
			return uri;
		}

		public DatatypeProperty property() {
			return model.createDatatypeProperty(uri);
		}
	};

}
