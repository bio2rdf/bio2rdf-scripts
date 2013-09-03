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
package com.dumontierlab.pdb2rdf.model;

import java.util.Date;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.io.InputStream;
import java.io.OutputStream;
import java.io.Reader;
import java.io.Writer;
import java.util.Calendar;
import java.util.Iterator;
import java.util.List;
import java.util.Map;

import com.dumontierlab.pdb2rdf.parser.vocabulary.PdbOwlVocabulary;
import com.hp.hpl.jena.datatypes.RDFDatatype;
import com.hp.hpl.jena.datatypes.xsd.XSDDatatype;
import com.hp.hpl.jena.datatypes.xsd.impl.XSDDateType;
import com.hp.hpl.jena.graph.Graph;
import com.hp.hpl.jena.graph.Node;
import com.hp.hpl.jena.graph.Triple;
import com.hp.hpl.jena.query.Dataset;
import com.hp.hpl.jena.rdf.model.Alt;
import com.hp.hpl.jena.rdf.model.AnonId;
import com.hp.hpl.jena.rdf.model.Bag;
import com.hp.hpl.jena.rdf.model.Literal;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelChangedListener;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.NodeIterator;
import com.hp.hpl.jena.rdf.model.NsIterator;
import com.hp.hpl.jena.rdf.model.Property;
import com.hp.hpl.jena.rdf.model.RDFList;
import com.hp.hpl.jena.rdf.model.RDFNode;
import com.hp.hpl.jena.rdf.model.RDFReader;
import com.hp.hpl.jena.rdf.model.RDFWriter;
import com.hp.hpl.jena.rdf.model.RSIterator;
import com.hp.hpl.jena.rdf.model.ReifiedStatement;
import com.hp.hpl.jena.rdf.model.ResIterator;
import com.hp.hpl.jena.rdf.model.Resource;
import com.hp.hpl.jena.rdf.model.ResourceF;
import com.hp.hpl.jena.rdf.model.Selector;
import com.hp.hpl.jena.rdf.model.Seq;
import com.hp.hpl.jena.rdf.model.Statement;
import com.hp.hpl.jena.rdf.model.StmtIterator;
import com.hp.hpl.jena.shared.Command;
import com.hp.hpl.jena.shared.Lock;
import com.hp.hpl.jena.shared.PrefixMapping;
import com.hp.hpl.jena.shared.ReificationStyle;
import com.hp.hpl.jena.sparql.vocabulary.FOAF;
import com.hp.hpl.jena.vocabulary.RDF;
import com.hp.hpl.jena.vocabulary.RDFS;

/**
 * @author Jose Cruz-Toledo
 * @author Alexander De Leon
 */
public class PdbRdfModel implements Model {

	private Model model;
	private String pdbId;
	private String date;
	private Resource dataset;
	private Property inDataset;

	public PdbRdfModel() {
		model = ModelFactory.createDefaultModel();
		DateFormat dateFormat = new SimpleDateFormat("yyyy/MM/dd-HH:mm:ss");
		Date d = new Date();
		date = dateFormat.format(d);
		dataset = model.createResource("http://bio2rdf.org/bio2rdf.dataset:"+date);
		model.add(dataset, RDF.type, model.createResource("http://rdfs.org/ns/void#Dataset"));
		inDataset = model.createProperty("http://rdfs.org/ns/void#inDataset");
	}

	public PdbRdfModel(Model rdfModel) {
		this();
		model = rdfModel;
	}

	/**
	 * This method adds to this model the information about the input file used
	 * to create this RDF document
	 */
	public void addInputFileInformation() {
		if (this.getPdbId() != null) {
			Property retrievedOn = model
					.createProperty("http://purl.org/pav/retrievedOn");
			Property publisher = model
					.createProperty("http://purl.org/dc/terms/publisher");
			Property format = model
					.createProperty("http://purl.org/dc/terms/format");
			Property license = model
					.createProperty("http://purl.org/dc/terms/license");
			Property rights = model
					.createProperty("http://purl.org/dc/terms/rights");
			Resource lic = model
					.createResource("http://www.rcsb.org/pdb/static.do?p=general_information/about_pdb/pdb_advisory.html");
			
			model.add(this.getSourceFileResource(), RDFS.label, "PDB entry ID: " + this.getPdbId()
					+ " (retrieved on " + this.getDate() + ")");
			model.add(this.getSourceFileResource(), RDF.type,
					PdbOwlVocabulary.Class.Distribution.resource());
			model.add(this.getSourceFileResource(), retrievedOn, this.getDate(), XSDDateType.XSDdate);
			model.add(this.getSourceFileResource(), publisher, "http://www.rcsb.org/");
			model.add(this.getSourceFileResource(), format, "text/xml");
			model.add(this.getSourceFileResource(), license, lic);
			model.add(this.getSourceFileResource(), rights, "use");
		}
	}
	/**
	 * This method adds to this model the information about the output file generated
	 * by this program
	 */
	public void addRDFFileInformation(){
		if(this.getPdbId() != null){
			Property title = model
					.createProperty("http://purl.org/dc/terms/title");
			Property format = model
					.createProperty("http://purl.org/dc/terms/format");
			Property source = model.createProperty("http://purl.org/dc/terms/source");
			Property license = model
					.createProperty("http://purl.org/dc/terms/license");
			Property created = model.createProperty("http://purl.org/dc/terms/created");
			Property creator = model.createProperty("http://purl.org/dc/terms/creator");
			Property rights = model
					.createProperty("http://purl.org/dc/terms/rights");
			Property pu = model.createProperty("http://purl.org/dc/terms/publisher");
			Resource rdfFile = model.createResource("http://download.bio2rdf.org/release/3/pdb/"+this.getPdbId());
			Resource c = model.createResource("https://github.com/bio2rdf/bio2rdf-scripts/blob/master/pdb");
			Resource publisher = model.createResource("http://bio2rdf.org");
			Resource cc = model.createResource("http://creativecommons.org/licenses/by/3.0/");
			Property dist = model.createProperty("http://bio2rdf.org/dcat:distribution");
			model.add(rdfFile, RDF.type, PdbOwlVocabulary.Class.Distribution.resource());
			model.add(rdfFile, title, "Bio2RDF R3 version of PDB record "+this.getPdbId());
			model.add(rdfFile, license, cc);
			model.add(rdfFile, rights, "use-share-modify");
			model.add(rdfFile, rights, "by-attribution");
			model.add(rdfFile, rights, "restricted-by-source-license");
			model.add(rdfFile, RDFS.label, "Bio2RDF R3 version of PDB record "+this.getPdbId());
			model.add(rdfFile, created, this.getDate(),XSDDatatype.XSDdate);
			model.add(rdfFile, source, this.getSourceFileResource());
			model.add(rdfFile, creator, c);
			model.add(rdfFile, pu, publisher);
			model.add(rdfFile, format, "application/gzip");
			model.add(this.getDatasetResource(), dist, rdfFile);
		}
	}
	
	public Property getInDatasetProperty(){
		return this.inDataset;
	}

	public Resource getDatasetResource(){
		return this.dataset;
	}
	
	public Resource getSourceFileResource(){
		return  model
				.createResource("http://www.rcsb.org/pdb/files/"
						+ this.getPdbId() + ".xml.gz");
	}
	
	private String getDate() {
		return this.date;
	}

	public String getPdbId() {
		return pdbId;
	}

	public void setPdbId(String pdbId) {
		this.pdbId = pdbId;
	}

	/* --------------- delegate methods ---------- */
	public Model abort() {
		return model.abort();
	}

	public Model add(List<Statement> statements) {
		return model.add(statements);
	}

	public Model add(Model m, boolean suppressReifications) {
		return model.add(m, suppressReifications);
	}

	public Model add(Model m) {
		return model.add(m);
	}

	public Model add(Resource s, Property p, RDFNode o) {
		Statement x = model.createStatement(s, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		Statement y = model.createStatement(p, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		// Statement z = model.createStatement(o.asResource(),
		// RDF.type,PdbOwlVocabulary.Class.Resource.resource());
		model.add(x);
		model.add(y);
		// model.add(z);
		return model.add(s, p, o);
	}

	public Model add(Resource s, Property p, String o, boolean wellFormed) {
		Statement x = model.createStatement(s, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		Statement y = model.createStatement(p, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		model.add(x);
		model.add(y);
		return model.add(s, p, o, wellFormed);
	}

	public Model add(Resource s, Property p, String lex, RDFDatatype datatype) {
		Statement x = model.createStatement(s, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		Statement y = model.createStatement(p, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		model.add(x);
		model.add(y);
		return model.add(s, p, lex, datatype);
	}

	public Model add(Resource s, Property p, String o, String l) {
		Statement x = model.createStatement(s, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		Statement y = model.createStatement(p, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		model.add(x);
		model.add(y);
		return model.add(s, p, o, l);
	}

	public Model add(Resource s, Property p, String o) {
		Statement x = model.createStatement(s, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		Statement y = model.createStatement(p, RDF.type,
				PdbOwlVocabulary.Class.Resource.resource());
		model.add(x);
		model.add(y);
		return model.add(s, p, o);
	}

	public Model add(Statement s) {
		return model.add(s);
	}

	public Model add(Statement[] statements) {
		return model.add(statements);
	}

	public Model add(StmtIterator iter) {
		return model.add(iter);
	}

	public Model addLiteral(Resource s, Property p, boolean o) {
		return model.addLiteral(s, p, o);
	}

	public Model addLiteral(Resource s, Property p, char o) {
		return model.addLiteral(s, p, o);
	}

	public Model addLiteral(Resource s, Property p, double o) {
		return model.addLiteral(s, p, o);
	}

	public Model addLiteral(Resource s, Property p, float o) {
		return model.addLiteral(s, p, o);
	}

	public Model addLiteral(Resource s, Property p, int o) {
		return model.addLiteral(s, p, o);
	}

	public Model addLiteral(Resource s, Property p, long o) {
		return model.addLiteral(s, p, o);
	}

	public Model addLiteral(Resource s, Property p, Object o) {
		return model.addLiteral(s, p, o);
	}

	public RDFNode asRDFNode(Node n) {
		return model.asRDFNode(n);
	}

	public Statement asStatement(Triple t) {
		return model.asStatement(t);
	}

	public Model begin() {
		return model.begin();
	}

	public void close() {
		model.close();
	}

	public Model commit() {
		return model.commit();
	}

	public boolean contains(Resource s, Property p, RDFNode o) {
		return model.contains(s, p, o);
	}

	public boolean contains(Resource s, Property p, String o, String l) {
		return model.contains(s, p, o, l);
	}

	public boolean contains(Resource s, Property p, String o) {
		return model.contains(s, p, o);
	}

	public boolean contains(Resource s, Property p) {
		return model.contains(s, p);
	}

	public boolean contains(Statement s) {
		return model.contains(s);
	}

	public boolean containsAll(Model model) {
		return model.containsAll(model);
	}

	public boolean containsAll(StmtIterator iter) {
		return model.containsAll(iter);
	}

	public boolean containsAny(Model model) {
		return model.containsAny(model);
	}

	public boolean containsAny(StmtIterator iter) {
		return model.containsAny(iter);
	}

	public boolean containsLiteral(Resource s, Property p, boolean o) {
		return model.containsLiteral(s, p, o);
	}

	public boolean containsLiteral(Resource s, Property p, char o) {
		return model.containsLiteral(s, p, o);
	}

	public boolean containsLiteral(Resource s, Property p, double o) {
		return model.containsLiteral(s, p, o);
	}

	public boolean containsLiteral(Resource s, Property p, float o) {
		return model.containsLiteral(s, p, o);
	}

	public boolean containsLiteral(Resource s, Property p, int o) {
		return model.containsLiteral(s, p, o);
	}

	public boolean containsLiteral(Resource s, Property p, long o) {
		return model.containsLiteral(s, p, o);
	}

	public boolean containsLiteral(Resource s, Property p, Object o) {
		return model.containsLiteral(s, p, o);
	}

	public boolean containsResource(RDFNode r) {
		return model.containsResource(r);
	}

	public Alt createAlt() {
		return model.createAlt();
	}

	public Alt createAlt(String uri) {
		return model.createAlt(uri);
	}

	public Bag createBag() {
		return model.createBag();
	}

	public Bag createBag(String uri) {
		return model.createBag(uri);
	}

	public RDFList createList() {
		return model.createList();
	}

	public RDFList createList(Iterator<? extends RDFNode> members) {
		return model.createList(members);
	}

	public RDFList createList(RDFNode[] members) {
		return model.createList(members);
	}

	public Literal createLiteral(String v, boolean wellFormed) {
		return model.createLiteral(v, wellFormed);
	}

	public Literal createLiteral(String v, String language) {
		return model.createLiteral(v, language);
	}

	public Literal createLiteral(String v) {
		return model.createLiteral(v);
	}

	public Statement createLiteralStatement(Resource s, Property p, boolean o) {
		return model.createLiteralStatement(s, p, o);
	}

	public Statement createLiteralStatement(Resource s, Property p, char o) {
		return model.createLiteralStatement(s, p, o);
	}

	public Statement createLiteralStatement(Resource s, Property p, double o) {
		return model.createLiteralStatement(s, p, o);
	}

	public Statement createLiteralStatement(Resource s, Property p, float o) {
		return model.createLiteralStatement(s, p, o);
	}

	public Statement createLiteralStatement(Resource s, Property p, int o) {
		return model.createLiteralStatement(s, p, o);
	}

	public Statement createLiteralStatement(Resource s, Property p, long o) {
		return model.createLiteralStatement(s, p, o);
	}

	public Statement createLiteralStatement(Resource s, Property p, Object o) {
		return model.createLiteralStatement(s, p, o);
	}

	public Property createProperty(String nameSpace, String localName) {
		return model.createProperty(nameSpace, localName);
	}

	public Property createProperty(String uri) {
		return model.createProperty(uri);
	}

	public ReifiedStatement createReifiedStatement(Statement s) {
		return model.createReifiedStatement(s);
	}

	public ReifiedStatement createReifiedStatement(String uri, Statement s) {
		return model.createReifiedStatement(uri, s);
	}

	public Resource createResource() {
		return model.createResource();
	}

	public Resource createResource(AnonId id) {
		return model.createResource(id);
	}

	public Resource createResource(Resource type) {
		return model.createResource(type);
	}

	public Resource createResource(ResourceF f) {
		return model.createResource(f);
	}

	public Resource createResource(String uri, Resource type) {
		return model.createResource(uri, type);
	}

	public Resource createResource(String uri, ResourceF f) {
		return model.createResource(uri, f);
	}

	public Resource createResource(String uri) {
		Resource rm = model.createResource(uri);
		model.add(rm, this.getInDatasetProperty(), this.getDatasetResource());
		return rm;
	}

	public Seq createSeq() {
		return model.createSeq();
	}

	public Seq createSeq(String uri) {
		return model.createSeq(uri);
	}

	public Statement createStatement(Resource s, Property p, RDFNode o) {
		return model.createStatement(s, p, o);
	}

	public Statement createStatement(Resource s, Property p, String o,
			boolean wellFormed) {
		return model.createStatement(s, p, o, wellFormed);
	}

	public Statement createStatement(Resource s, Property p, String o,
			String l, boolean wellFormed) {
		return model.createStatement(s, p, o, l, wellFormed);
	}

	public Statement createStatement(Resource s, Property p, String o, String l) {
		return model.createStatement(s, p, o, l);
	}

	public Statement createStatement(Resource s, Property p, String o) {
		return model.createStatement(s, p, o);
	}

	public Literal createTypedLiteral(boolean v) {
		return model.createTypedLiteral(v);
	}

	public Literal createTypedLiteral(Calendar d) {
		return model.createTypedLiteral(d);
	}

	public Literal createTypedLiteral(char v) {
		return model.createTypedLiteral(v);
	}

	public Literal createTypedLiteral(double v) {
		return model.createTypedLiteral(v);
	}

	public Literal createTypedLiteral(float v) {
		return model.createTypedLiteral(v);
	}

	public Literal createTypedLiteral(int v) {
		return model.createTypedLiteral(v);
	}

	public Literal createTypedLiteral(long v) {
		return model.createTypedLiteral(v);
	}

	public Literal createTypedLiteral(Object value, RDFDatatype dtype) {
		return model.createTypedLiteral(value, dtype);
	}

	public Literal createTypedLiteral(Object value, String typeURI) {
		return model.createTypedLiteral(value, typeURI);
	}

	public Literal createTypedLiteral(Object value) {
		return model.createTypedLiteral(value);
	}

	public Literal createTypedLiteral(String lex, RDFDatatype dtype) {
		return model.createTypedLiteral(lex, dtype);
	}

	public Literal createTypedLiteral(String lex, String typeURI) {
		return model.createTypedLiteral(lex, typeURI);
	}

	public Literal createTypedLiteral(String v) {
		return model.createTypedLiteral(v);
	}

	public Model difference(Model model) {
		return model.difference(model);
	}

	public void enterCriticalSection(boolean readLockRequested) {
		model.enterCriticalSection(readLockRequested);
	}

	@Override
	public boolean equals(Object m) {
		return model.equals(m);
	}

	public Object executeInTransaction(Command cmd) {
		return model.executeInTransaction(cmd);
	}

	public String expandPrefix(String prefixed) {
		return model.expandPrefix(prefixed);
	}

	public Alt getAlt(Resource r) {
		return model.getAlt(r);
	}

	public Alt getAlt(String uri) {
		return model.getAlt(uri);
	}

	public Resource getAnyReifiedStatement(Statement s) {
		return model.getAnyReifiedStatement(s);
	}

	public Bag getBag(Resource r) {
		return model.getBag(r);
	}

	public Bag getBag(String uri) {
		return model.getBag(uri);
	}

	public Graph getGraph() {
		return model.getGraph();
	}

	public Lock getLock() {
		return model.getLock();
	}

	public Map getNsPrefixMap() {
		return model.getNsPrefixMap();
	}

	public String getNsPrefixURI(String prefix) {
		return model.getNsPrefixURI(prefix);
	}

	public String getNsURIPrefix(String uri) {
		return model.getNsURIPrefix(uri);
	}

	public Statement getProperty(Resource s, Property p) {
		return model.getProperty(s, p);
	}

	public Property getProperty(String nameSpace, String localName) {
		return model.getProperty(nameSpace, localName);
	}

	public Property getProperty(String uri) {
		return model.getProperty(uri);
	}

	public RDFNode getRDFNode(Node n) {
		return model.getRDFNode(n);
	}

	public RDFReader getReader() {
		return model.getReader();
	}

	public RDFReader getReader(String lang) {
		return model.getReader(lang);
	}

	public ReificationStyle getReificationStyle() {
		return model.getReificationStyle();
	}

	public Statement getRequiredProperty(Resource s, Property p) {
		return model.getRequiredProperty(s, p);
	}

	public Resource getResource(String uri, ResourceF f) {
		return model.getResource(uri, f);
	}

	public Resource getResource(String uri) {
		return model.getResource(uri);
	}

	public Seq getSeq(Resource r) {
		return model.getSeq(r);
	}

	public Seq getSeq(String uri) {
		return model.getSeq(uri);
	}

	public RDFWriter getWriter() {
		return model.getWriter();
	}

	public RDFWriter getWriter(String lang) {
		return model.getWriter(lang);
	}

	public boolean independent() {
		return model.independent();
	}

	public Model intersection(Model model) {
		return model.intersection(model);
	}

	public boolean isClosed() {
		return model.isClosed();
	}

	public boolean isEmpty() {
		return model.isEmpty();
	}

	public boolean isIsomorphicWith(Model g) {
		return model.isIsomorphicWith(g);
	}

	public boolean isReified(Statement s) {
		return model.isReified(s);
	}

	public void leaveCriticalSection() {
		model.leaveCriticalSection();
	}

	public StmtIterator listLiteralStatements(Resource subject,
			Property predicate, boolean object) {
		return model.listLiteralStatements(subject, predicate, object);
	}

	public StmtIterator listLiteralStatements(Resource subject,
			Property predicate, char object) {
		return model.listLiteralStatements(subject, predicate, object);
	}

	public StmtIterator listLiteralStatements(Resource subject,
			Property predicate, double object) {
		return model.listLiteralStatements(subject, predicate, object);
	}

	public StmtIterator listLiteralStatements(Resource subject,
			Property predicate, long object) {
		return model.listLiteralStatements(subject, predicate, object);
	}

	public StmtIterator listLiteralStatements(Resource arg0, Property arg1,
			float arg2) {
		return model.listLiteralStatements(arg0, arg1, arg2);
	}

	public NsIterator listNameSpaces() {
		return model.listNameSpaces();
	}

	public NodeIterator listObjects() {
		return model.listObjects();
	}

	public NodeIterator listObjectsOfProperty(Property p) {
		return model.listObjectsOfProperty(p);
	}

	public NodeIterator listObjectsOfProperty(Resource s, Property p) {
		return model.listObjectsOfProperty(s, p);
	}

	public RSIterator listReifiedStatements() {
		return model.listReifiedStatements();
	}

	public RSIterator listReifiedStatements(Statement st) {
		return model.listReifiedStatements(st);
	}

	public ResIterator listResourcesWithProperty(Property p, boolean o) {
		return model.listResourcesWithProperty(p, o);
	}

	public ResIterator listResourcesWithProperty(Property p, char o) {
		return model.listResourcesWithProperty(p, o);
	}

	public ResIterator listResourcesWithProperty(Property p, double o) {
		return model.listResourcesWithProperty(p, o);
	}

	public ResIterator listResourcesWithProperty(Property p, float o) {
		return model.listResourcesWithProperty(p, o);
	}

	public ResIterator listResourcesWithProperty(Property p, long o) {
		return model.listResourcesWithProperty(p, o);
	}

	public ResIterator listResourcesWithProperty(Property p, Object o) {
		return model.listResourcesWithProperty(p, o);
	}

	public ResIterator listResourcesWithProperty(Property p, RDFNode o) {
		return model.listResourcesWithProperty(p, o);
	}

	public ResIterator listResourcesWithProperty(Property p) {
		return model.listResourcesWithProperty(p);
	}

	public StmtIterator listStatements() {
		return model.listStatements();
	}

	public StmtIterator listStatements(Resource s, Property p, RDFNode o) {
		return model.listStatements(s, p, o);
	}

	public StmtIterator listStatements(Resource subject, Property predicate,
			String object, String lang) {
		return model.listStatements(subject, predicate, object, lang);
	}

	public StmtIterator listStatements(Resource subject, Property predicate,
			String object) {
		return model.listStatements(subject, predicate, object);
	}

	public StmtIterator listStatements(Selector s) {
		return model.listStatements(s);
	}

	public ResIterator listSubjects() {
		return model.listSubjects();
	}

	public ResIterator listSubjectsWithProperty(Property p, RDFNode o) {
		return model.listSubjectsWithProperty(p, o);
	}

	public ResIterator listSubjectsWithProperty(Property p, String o, String l) {
		return model.listSubjectsWithProperty(p, o, l);
	}

	public ResIterator listSubjectsWithProperty(Property p, String o) {
		return model.listSubjectsWithProperty(p, o);
	}

	public ResIterator listSubjectsWithProperty(Property p) {
		return model.listSubjectsWithProperty(p);
	}

	public PrefixMapping lock() {
		return model.lock();
	}

	public Model notifyEvent(Object e) {
		return model.notifyEvent(e);
	}

	public String qnameFor(String uri) {
		return model.qnameFor(uri);
	}

	public Model query(Selector s) {
		return model.query(s);
	}

	public Model read(InputStream in, String base, String lang) {
		return model.read(in, base, lang);
	}

	public Model read(InputStream in, String base) {
		return model.read(in, base);
	}

	public Model read(Reader reader, String base, String lang) {
		return model.read(reader, base, lang);
	}

	public Model read(Reader reader, String base) {
		return model.read(reader, base);
	}

	public Model read(String url, String base, String lang) {
		return model.read(url, base, lang);
	}

	public Model read(String url, String lang) {
		return model.read(url, lang);
	}

	public Model read(String url) {
		return model.read(url);
	}

	public Model register(ModelChangedListener listener) {
		return model.register(listener);
	}

	public Model remove(List<Statement> statements) {
		return model.remove(statements);
	}

	public Model remove(Model m, boolean suppressReifications) {
		return model.remove(m, suppressReifications);
	}

	public Model remove(Model m) {
		return model.remove(m);
	}

	public Model remove(Resource s, Property p, RDFNode o) {
		return model.remove(s, p, o);
	}

	public Model remove(Statement s) {
		return model.remove(s);
	}

	public Model remove(Statement[] statements) {
		return model.remove(statements);
	}

	public Model remove(StmtIterator iter) {
		return model.remove(iter);
	}

	public Model removeAll() {
		return model.removeAll();
	}

	public Model removeAll(Resource s, Property p, RDFNode r) {
		return model.removeAll(s, p, r);
	}

	public void removeAllReifications(Statement s) {
		model.removeAllReifications(s);
	}

	public PrefixMapping removeNsPrefix(String prefix) {
		return model.removeNsPrefix(prefix);
	}

	public void removeReification(ReifiedStatement rs) {
		model.removeReification(rs);
	}

	public boolean samePrefixMappingAs(PrefixMapping other) {
		return model.samePrefixMappingAs(other);
	}

	public PrefixMapping setNsPrefix(String prefix, String uri) {
		return model.setNsPrefix(prefix, uri);
	}

	public PrefixMapping setNsPrefixes(Map<String, String> map) {
		return model.setNsPrefixes(map);
	}

	public PrefixMapping setNsPrefixes(PrefixMapping other) {
		return model.setNsPrefixes(other);
	}

	public String setReaderClassName(String lang, String className) {
		return model.setReaderClassName(lang, className);
	}

	public String setWriterClassName(String lang, String className) {
		return model.setWriterClassName(lang, className);
	}

	public String shortForm(String uri) {
		return model.shortForm(uri);
	}

	public long size() {
		return model.size();
	}

	public boolean supportsSetOperations() {
		return model.supportsSetOperations();
	}

	public boolean supportsTransactions() {
		return model.supportsTransactions();
	}

	public Model union(Model model) {
		return model.union(model);
	}

	public Model unregister(ModelChangedListener listener) {
		return model.unregister(listener);
	}

	public PrefixMapping withDefaultMappings(PrefixMapping map) {
		return model.withDefaultMappings(map);
	}

	public Model write(OutputStream out, String lang, String base) {
		return model.write(out, lang, base);
	}

	public Model write(OutputStream out, String lang) {
		return model.write(out, lang);
	}

	public Model write(OutputStream out) {
		return model.write(out);
	}

	public Model write(Writer writer, String lang, String base) {
		return model.write(writer, lang, base);
	}

	public Model write(Writer writer, String lang) {
		return model.write(writer, lang);
	}

	public Model write(Writer writer) {
		return model.write(writer);
	}

	@Override
	public Model addLiteral(Resource arg0, Property arg1, Literal arg2) {
		return model.addLiteral(arg0, arg1, arg2);
	}

	/* -------------------------------------- */
	protected void setModel(Model model) {
		this.model = model;
	}

	/*
	 * (non-Javadoc)
	 * 
	 * @see
	 * com.hp.hpl.jena.rdf.model.ModelGraphInterface#wrapAsResource(com.hp.hpl
	 * .jena.graph.Node)
	 */
	@Override
	public Resource wrapAsResource(Node n) {
		// TODO Auto-generated method stub
		return null;
	}

}
