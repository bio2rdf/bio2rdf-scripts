/**
 * Copyright (c) 2011 Jose Miguel Cruz Toledo
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
package com.dumontierlab.pdb2rdf.tools.bin;

import java.io.BufferedReader;
import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.UnsupportedEncodingException;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLConnection;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;

import org.apache.commons.io.FileUtils;

/**
 * This class queries the PDB for getting the records of a given type Allowed
 * types: DNA, RNA, Nucleic Acid
 * 
 * @author "Jose Cruz-Toledo"
 * 
 */
public class PDBRetriever {
	/**
	 * Location of the PDB rest service
	 */
	public static final String SERVICELOCATION = "http://www.rcsb.org/pdb/rest/search";

	/**
	 * DNA only query
	 */
	public static final String DNAONLYQUERY = "<orgPdbQuery><queryType>org.pdb.query.simple.ChainTypeQuery</queryType><containsProtein>N</containsProtein><containsDna>?</containsDna><containsRna>N</containsRna><containsHybrid>N</containsHybrid></orgPdbQuery>";
	/**
	 * RNA only query
	 */
	public static final String RNAONLYQUERY = "<orgPdbQuery><queryType>org.pdb.query.simple.ChainTypeQuery</queryType><containsProtein>N</containsProtein><containsDna>N</containsDna><containsRna>?</containsRna><containsHybrid>N</containsHybrid></orgPdbQuery>";
	/**
	 * Nucleic acids query
	 */
	public static final String NUCLEICACIDONLYQUERY = "<orgPdbQuery><queryType>org.pdb.query.simple.ChainTypeQuery</queryType><containsProtein>N</containsProtein><containsDna>?</containsDna><containsRna>?</containsRna><containsHybrid>N</containsHybrid></orgPdbQuery>";

	/**
	 * Nucleic acids query, only Xray between 0 and 10 A
	 */
	public static final String NUCLEICACIDONLYQRY_XRAY_0_10 = "<orgPdbCompositeQuery> <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ChainTypeQuery</queryType>    <containsProtein>N</containsProtein>    <containsDna>?</containsDna>    <containsRna>?</containsRna>    <containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>10.0</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";
	/**
	 * Nucleic acids query, only Xray between 0 and 3.5 A
	 */
	public static final String NUCLEICACIDONLYQRY_XRAY_0_3p5 = "<orgPdbCompositeQuery> <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ChainTypeQuery</queryType>    <containsProtein>N</containsProtein>    <containsDna>?</containsDna>    <containsRna>?</containsRna>    <containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>3.5</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";

	/**
	 * Nucleic acids query, only Xray between 0 and 2.5 A
	 */
	public static final String NUCLEICACIDONLYQRY_XRAY_0_2p5 = "<orgPdbCompositeQuery> <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ChainTypeQuery</queryType>    <containsProtein>N</containsProtein>    <containsDna>?</containsDna>    <containsRna>?</containsRna>    <containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>2.5</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";

	/**
	 * RNA only Xray between 0 and 2.5 A
	 */
	public static final String RNAONLYQRY_XRAY_0_2p5 = "<orgPdbCompositeQuery> <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ChainTypeQuery</queryType>    <containsProtein>N</containsProtein>    <containsDna>N</containsDna>    <containsRna>?</containsRna>    <containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>2.5</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";

	/**
	 * RNA only Xray between 0 and 3.5 A
	 */
	public static final String RNAONLYQRY_XRAY_0_3p5 = "<orgPdbCompositeQuery> <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ChainTypeQuery</queryType>    <containsProtein>N</containsProtein>    <containsDna>N</containsDna>    <containsRna>?</containsRna>    <containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>3.5</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";

	/**
	 * RNA only Xray between 0 and 10 A
	 */
	public static final String RNAONLYQRY_XRAY_0_10 = "<orgPdbCompositeQuery> <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ChainTypeQuery</queryType>    <containsProtein>N</containsProtein>    <containsDna>N</containsDna>    <containsRna>?</containsRna>    <containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>10.0</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";
	
	/**
	 * Protein only Xray between 0 and 1 A
	 */
	
	public static final String PROTEINONLYQRY_XRAY_0_1 = "<orgPdbCompositeQuery > <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel>  <orgPdbQuery>    <version>head</version>   <queryType>org.pdb.query.simple.ChainTypeQuery</queryType>    <containsProtein>Y</containsProtein>    <containsDna>N</containsDna>    <containsRna>N</containsRna>    <containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel> <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <version>head</version>    <queryType>org.pdb.query.simple.ExpTypeQuery</queryType>    <mvStructure.expMethod.value>X-RAY</mvStructure.expMethod.value>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>2</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <version>head</version>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>1.0</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";

	/**
	 * DNA only Xray between 0 and 2.5 A
	 */
	public static final String DNAONLYQRY_XRAY_0_2p5 = "<orgPdbCompositeQuery> <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel><orgPdbQuery><queryType>org.pdb.query.simple.ChainTypeQuery</queryType><containsProtein>N</containsProtein><containsDna>?</containsDna><containsRna>N</containsRna><containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>2.5</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";

	/**
	 * DNA only Xray between 0 and 3.5 A
	 */
	public static final String DNAONLYQRY_XRAY_0_3p5 = "<orgPdbCompositeQuery> <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel><orgPdbQuery><queryType>org.pdb.query.simple.ChainTypeQuery</queryType><containsProtein>N</containsProtein><containsDna>?</containsDna><containsRna>N</containsRna><containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>3.5</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";

	/**
	 * DNA only Xray between 0 and 10 A
	 */
	public static final String DNAONLYQRY_XRAY_0_10 = "<orgPdbCompositeQuery> <queryRefinement>  <queryRefinementLevel>0</queryRefinementLevel><orgPdbQuery><queryType>org.pdb.query.simple.ChainTypeQuery</queryType><containsProtein>N</containsProtein><containsDna>?</containsDna><containsRna>N</containsRna><containsHybrid>N</containsHybrid>  </orgPdbQuery> </queryRefinement> <queryRefinement>  <queryRefinementLevel>1</queryRefinementLevel>  <conjunctionType>and</conjunctionType>  <orgPdbQuery>    <queryType>org.pdb.query.simple.ResolutionQuery</queryType>    <refine.ls_d_res_high.comparator>between</refine.ls_d_res_high.comparator>    <refine.ls_d_res_high.min>0.0</refine.ls_d_res_high.min>    <refine.ls_d_res_high.max>10.0</refine.ls_d_res_high.max>  </orgPdbQuery> </queryRefinement></orgPdbCompositeQuery>";

	/**
	 * A list with all of the PDBIds returned from the query
	 */
	List<String> pdbIds = new ArrayList<String>();

	/**
	 * Constructor. Pass in one of the allowed values i.e.: RNA, DNA or Nucleic
	 * acid
	 * 
	 * @param aQry
	 */
	public PDBRetriever(String aQry) {

		if (aQry.equalsIgnoreCase("Nucleic Acid")) {
			pdbIds = postQuery(NUCLEICACIDONLYQUERY);
		} else if (aQry.equalsIgnoreCase("RNA")) {
			pdbIds = postQuery(RNAONLYQUERY);
		} else if (aQry.equalsIgnoreCase("DNA")) {
			pdbIds = postQuery(DNAONLYQUERY);
		} else if (aQry.equalsIgnoreCase("RNA xray")) {
			pdbIds = postQuery(RNAONLYQRY_XRAY_0_10);
		} else if (aQry.equalsIgnoreCase("RNA high res")) {
			pdbIds = postQuery(RNAONLYQRY_XRAY_0_3p5);
		} else if (aQry.equalsIgnoreCase("DNA xray")) {
			pdbIds = postQuery(DNAONLYQRY_XRAY_0_10);
		} else if (aQry.equalsIgnoreCase("DNA high res")) {
			pdbIds = postQuery(DNAONLYQRY_XRAY_0_3p5);
		} else if (aQry.equalsIgnoreCase("Nucleic Acid xray")) {
			pdbIds = postQuery(NUCLEICACIDONLYQRY_XRAY_0_10);
		} else if (aQry.equalsIgnoreCase("Nucleic acid high res")) {
			pdbIds = postQuery(NUCLEICACIDONLYQRY_XRAY_0_3p5);
		} else if (aQry.equalsIgnoreCase("dna super high res")) {
			pdbIds = postQuery(DNAONLYQRY_XRAY_0_2p5);
		} else if (aQry.equalsIgnoreCase("rna super high res") || aQry.equalsIgnoreCase("rna_super_high_res")) {
			pdbIds = postQuery(RNAONLYQRY_XRAY_0_2p5);
		} else if (aQry.equalsIgnoreCase("na super high res")) {
			pdbIds = postQuery(NUCLEICACIDONLYQRY_XRAY_0_2p5);
		} else if (aQry.equals("protein super high res")){
			pdbIds= postQuery(PROTEINONLYQRY_XRAY_0_1);
		}
	}

	/**
	 * post am XML query (PDB XML query format) to the RESTful RCSB web service
	 * 
	 * @param xml
	 * @return a list of PDB ids.
	 */
	private List<String> postQuery(String xml) {
		URL u;
		List<String> returnMe = new ArrayList<String>();
		try {
			u = new URL(SERVICELOCATION);
			String encodedXML = URLEncoder.encode(xml, "UTF-8");
			InputStream in = doPOST(u, encodedXML);
			BufferedReader rd = new BufferedReader(new InputStreamReader(in));
			String line;
			while ((line = rd.readLine()) != null) {
				returnMe.add(line);
			}
			rd.close();
		} catch (MalformedURLException e) {
			e.printStackTrace();
		} catch (UnsupportedEncodingException e) {
			e.printStackTrace();
		} catch (IOException e) {
			e.printStackTrace();
		}
		return returnMe;
	}

	/**
	 * do a POST to a URL and return the response stream for further processing
	 * elsewhere.
	 * 
	 * 
	 * @param url
	 * @return
	 * @throws IOException
	 */
	public static InputStream doPOST(URL url, String data) {
		// Send data
		URLConnection conn;
		try {
			conn = url.openConnection();
			conn.setDoOutput(true);
			OutputStreamWriter wr = new OutputStreamWriter(
					conn.getOutputStream());
			wr.write(data);
			wr.flush();
			// Get the response
			return conn.getInputStream();
		} catch (IOException e) {
			e.printStackTrace();
		}
		return null;
	}

	/**
	 * Iterates over the sourceDirectory in search for the pdb files
	 * corresponding to someIds
	 * 
	 * @param sourceDirectory
	 *            Directory that holds all of your PDB RDF files
	 * @param someIds
	 *            PDBIDs retrieved from PDB's REST service
	 * @return A list of Files that correspond to the someIds parameter
	 */
	public List<File> getPDBRDFPaths(File sourceDirectory, List<String> someIds) {
		List<File> returnMe = new ArrayList<File>();
		Iterator<String> itr = someIds.iterator();
		// Iterate over ids
		while (itr.hasNext()) {
			String anId = itr.next();
			String middleLetters = getMiddleTwoLetters(anId);
			if (middleLetters.length() == 2) {
				String aFilePathCompressed = sourceDirectory.getAbsolutePath()
						+ "/" + middleLetters + "/" + anId.toUpperCase()
						+ ".rdf.gz";
				String aFilePath = sourceDirectory.getAbsolutePath() + "/"
						+ middleLetters + "/" + anId.toUpperCase() + ".rdf";

				File aCompressedFile = new File(aFilePathCompressed);
				File aFile = new File(aFilePath);
				boolean exists = aCompressedFile.exists();
				boolean b = aFile.exists();

				if (exists) {
					returnMe.add(aCompressedFile);
				} else if (b) {
					// files were not compressed
					returnMe.add(aFile);

				}
			}
		}
		return returnMe;
	}
	/**
	 * Search the sourceDirectory for the PDBML records in someIds 
	 * @param sourceDirectory 
	 * @param someIds
	 * @return a list of files that matches someIds
	 */
	public List<File> getPDBXMLPaths(File sourceDirectory, List<String> someIds) {
		List<File> returnMe = new ArrayList<File>();
		Iterator<String> itr = someIds.iterator();
		while (itr.hasNext()) {
			String anId = itr.next();
			String middleLetters = getMiddleTwoLetters(anId);
			if (middleLetters.length() == 2) {
				String aFP = sourceDirectory.getAbsolutePath() + "/"
						+ middleLetters.toLowerCase() + "/"
						+ anId.toLowerCase() + ".xml.gz";
				File aFile = new File(aFP);
				boolean e = aFile.exists();
				if (e) {
					returnMe.add(aFile);
				}
			}
		}
		return returnMe;
	}

	/**
	 * 
	 * Get a list of files of the PDB files found in the sourceDirectory given a
	 * list of PDBIds
	 * 
	 * @param sourceDirectory
	 *            the directory where the PDB files are located
	 * @param someIds
	 *            a list of PDBIds
	 * @return the list of PDB files corresponding to someIds
	 */
	public List<File> getPDBPaths(File sourceDirectory, List<String> someIds) {
		List<File> returnMe = new ArrayList<File>();
		Iterator<String> itr = someIds.iterator();
		while (itr.hasNext()) {
			String anId = itr.next();
			String middleLetters = getMiddleTwoLetters(anId);
			if (middleLetters.length() == 2) {
				String aFP = sourceDirectory.getAbsolutePath() + "/"
						+ middleLetters.toLowerCase() + "/pdb"
						+ anId.toLowerCase() + ".ent.gz";
				File aFile = new File(aFP);
				boolean e = aFile.exists();
				if (e) {
					returnMe.add(aFile);
				}
			}
		}

		return returnMe;
	}

	/**
	 * Get a list of files of the PDB files found in the sourceDirectory
	 * 
	 * @param sourceDirectory
	 *            the directory where the PDB files are located
	 * @return a list of PDB Files
	 */
	public List<File> getPDBPaths(File sourceDirectory) {
		List<File> returnMe = this.getPDBPaths(sourceDirectory,
				this.getPdbIds());
		return returnMe;
	}

	/**
	 * Get a list of files of the PDBML files found in the sourceDirectory
	 * 
	 * @param sourceDirectory
	 *            the directory where the PDBML files are located
	 * @return a list of PDBML Files
	 */
	public List<File> getPDBXMLPaths(File sourceDirectory) {
		List<File> returnMe = this.getPDBXMLPaths(sourceDirectory,
				this.getPdbIds());
		return returnMe;
	}

	/**
	 * Get a list of files that match the query posed to the PDB service
	 * 
	 * @param sourceDirectory
	 *            directory where the RDF files exist
	 * @return a list of files
	 */
	public List<File> getPDBRDFPaths(File sourceDirectory) {
		List<File> returnMe = this.getPDBRDFPaths(sourceDirectory,
				this.getPdbIds());
		return returnMe;
	}

	/**
	 * Iterate over source files and copy the files over to destination
	 * directory
	 * 
	 * @param sourceFiles
	 * @param destinationDirectory
	 */
	public void copyFiles(List<File> sourceFiles, File destinationDirectory) {
		Iterator<File> itr = sourceFiles.iterator();
		while (itr.hasNext()) {
			File sourceFile = itr.next();
			try {
				FileUtils.copyFile(sourceFile,
						new File(destinationDirectory.getAbsolutePath() + "/"
								+ sourceFile.getName()));
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

	}

	/**
	 * Return the middle two letters of a PDBID in Uppercase 2j0s.xml.gz
	 * 
	 * @param anId
	 * @return
	 */
	public String getMiddleTwoLetters(String anId) {
		String returnMe = "";
		returnMe = anId.substring(1, 3).toUpperCase();
		return returnMe;
	}

	/**
	 * Return the middle two letters of a PDBID in Uppercase pdb2gu5.ent.gz
	 * 
	 * @param anId
	 * @return
	 */
	public String getEntFileMiddleTwoLetters(String anId) {
		String returnMe = "";
		returnMe = anId.substring(4, 6).toLowerCase();
		return returnMe;
	}

	/**
	 * @return the pdbIds
	 */
	public List<String> getPdbIds() {
		return pdbIds;
	}

	/**
	 * @param pdbIds
	 *            the pdbIds to set
	 */
	public void setPdbIds(List<String> pdbIds) {
		this.pdbIds = pdbIds;
	}

}
