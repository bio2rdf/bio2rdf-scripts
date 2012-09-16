/**
 * This class maps a given pdb id to a uniprot id by using uniprot's rest service
 */
package com.dumontierlab.pdb2rdf.external;

import java.util.ArrayList;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import com.hp.hpl.jena.query.QueryExecution;
import com.hp.hpl.jena.query.QueryExecutionFactory;
import com.hp.hpl.jena.query.QuerySolution;
import com.hp.hpl.jena.query.ResultSet;
import com.hp.hpl.jena.rdf.model.Model;
import com.hp.hpl.jena.rdf.model.ModelFactory;
import com.hp.hpl.jena.rdf.model.Resource;

/**
 * @author "Jose Cruz-Toledo"
 * 
 */
public class Pdb2Rdf2Uniprot {

	/**
	 * The query PDBId
	 */
	String pdbId;
	/**
	 * The jena model created from the Uniprot record
	 */
	Model uniprotModel;
	/**
	 * A list of Strings to all of the GO mappings found in uniprot for a given
	 * PDB id
	 */
	List<String> goMappings;

	/**
	 * A list of Strings to all of the Uniprot ids that map to the input PDBid
	 */
	List<String> uniprotMappings;

	public Pdb2Rdf2Uniprot(String aPdbId) {
		pdbId = aPdbId;
		uniprotModel = getUniprotModel();
		goMappings = getGoMappings(uniprotModel);
		uniprotMappings = getUniprotMappings(uniprotModel);
	}

	private List<String> getUniprotMappings(Model aUpModel) {
		QueryExecution ex = QueryExecutionFactory
				.create("SELECT ?x WHERE{?x <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://purl.uniprot.org/core/Protein>}",
						aUpModel);
		ResultSet rs = ex.execSelect();
		List<String> returnMe = new ArrayList<String>();
		while (rs.hasNext()) {
			QuerySolution sol = rs.next();
			Resource r = sol.getResource("x");
			String lPattern = "http:\\/\\/purl.uniprot.org\\/uniprot\\/(\\w+)";
			Pattern p = Pattern.compile(lPattern);
			Matcher m = p.matcher(r.getURI());
			if (m.matches()) {
				returnMe.add(m.group(1).trim());
			}
		}
		return returnMe;
	}

	/**
	 * Get the mappings to GO given a Uniprot RDF model
	 * 
	 * @param aUpModel
	 *            a Model populated with an RDF representation of a Uniprot
	 *            record
	 * @return a list of Strings to GO ids
	 */
	private List<String> getGoMappings(Model aUpModel) {
		List<String> returnMe = new ArrayList();
		QueryExecution ex = QueryExecutionFactory.create(
				"SELECT ?z WHERE{_:x <http://purl.uniprot.org/core/classifiedWith> ?z}", aUpModel);
		ResultSet rs = ex.execSelect();
		while (rs.hasNext()) {
			QuerySolution sol = rs.next();
			Resource r = sol.getResource("z");
			// Check if the url is from go
			String lPattern = "http:\\/\\/purl.uniprot.org\\/go\\/(\\w+)";
			Pattern p = Pattern.compile(lPattern);
			Matcher m = p.matcher(r.getURI());
			if (m.matches()) {
				returnMe.add(m.group(1).trim());
			}

		}
		return returnMe;
	}

	/**
	 * Retrieves an RDF representation of the Uniprot mapping
	 * 
	 * @return
	 */
	public Model getUniprotModel() {
		Model returnMe;
		String baseURL = "http://www.uniprot.org/uniprot/?query=%22pdb:";
		if (this.getPdbId().length() != 0) {
			returnMe = ModelFactory.createDefaultModel();
			try {
				returnMe.read(baseURL + this.getPdbId() + "\"&format=rdf");
			} catch (Exception e) {
				// no found.
			}
			return returnMe;
		} else {
			return null;
		}
	}

	/**
	 * @return the pdbId
	 */
	public String getPdbId() {
		return pdbId;
	}

	/**
	 * @param pdbId
	 *            the pdbId to set
	 */
	public void setPdbId(String pdbId) {
		this.pdbId = pdbId;
	}

	/**
	 * @return the goMappings
	 */
	public List<String> getGoMappings() {
		return goMappings;
	}

	/**
	 * @param goMappings
	 *            the goMappings to set
	 */
	public void setGoMappings(List<String> goMappings) {
		this.goMappings = goMappings;
	}

	/**
	 * @return the uniprotMappings
	 */
	public List<String> getUniprotMappings() {
		return uniprotMappings;
	}

	/**
	 * @param uniprotMappings
	 *            the uniprotMappings to set
	 */
	public void setUniprotMappings(List<String> uniprotMappings) {
		this.uniprotMappings = uniprotMappings;
	}

	/**
	 * @param uniprotModel
	 *            the uniprotModel to set
	 */
	public void setUniprotModel(Model uniprotModel) {
		this.uniprotModel = uniprotModel;
	}

}
