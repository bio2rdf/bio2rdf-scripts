/**
 * Copyright (c) 2011 "Jose Cruz-Toledo"
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
package com.dumontierlab.pdb2rdf.tools.taxonomy;

import java.io.File;
import java.io.IOException;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Iterator;
import java.util.List;

import org.apache.commons.io.FileUtils;
import org.apache.commons.lang.StringEscapeUtils;

import com.dumontierlab.pdb2rdf.tools.lib.DbConnector;

/**
 * @author "Jose Cruz-Toledo"
 * 
 */
public class NCBITaxonomyDbLoader {
	/**
	 * The DbConnector used to connect to the database
	 */
	public DbConnector conn;
	/**
	 * the url of the db
	 */
	public String dbUrl = "jdbc:mysql://localhost/ncbi_taxonomy";
	/**
	 * Db user
	 */
	public String username = "pdb2rdf";
	/**
	 * Db passwrd
	 */
	public String password = "pdb2rdf!";

	/**
	 * Files for each of the ones found in the taxonomy dump
	 */
	public File names;
	public File nodes;
	public File citations;
	public File divisions;
	/**
	 * Set to true if running a test
	 */
	public boolean testFlag = false;

	public NCBITaxonomyDbLoader() {
		conn = new DbConnector(dbUrl, username, password);
	}

	public NCBITaxonomyDbLoader(File aNames, File aNodes, File aCitations,
			File aDivisions) {
		this();
		names = aNames;
		nodes = aNodes;
		citations = aCitations;
		divisions = aDivisions;

	}

	public NCBITaxonomyDbLoader(File aNames, File aNodes, File aCitations,
			File aDivisions, boolean aTestFlag) {
		this(aNames, aNodes, aCitations, aDivisions);
		this.testFlag = aTestFlag;
	}

	public void populateNames() {
		try {
			List<String> lines = FileUtils.readLines(this.names);
			Iterator<String> itr = lines.iterator();
			int count = 0;
			int max = 10;
			while (itr.hasNext()) {
				String aLine = itr.next().trim();
				String[] aLineArr = aLine.split("\\t\\|");

				Integer taxid = Integer.parseInt(aLineArr[0]);
				String nameTxt = StringEscapeUtils
						.escapeSql(aLineArr[1].trim());
				String uniqueName = StringEscapeUtils.escapeSql(aLineArr[2]
						.trim());
				String nameClass = StringEscapeUtils.escapeSql(aLineArr[3]
						.trim());

				// check if this entry is already in the table
				String chkQry = "SELECT * FROM names WHERE " + "tax_id='"
						+ taxid + "' AND name_txt='" + nameTxt
						+ "' AND unique_name ='" + uniqueName
						+ "' AND name_class='" + nameClass + "'";
				if (!this.testFlag) {
					if (!checkRowExistence(chkQry)) {
						String insertQry = "INSERT INTO names VALUES('" + taxid
								+ "','" + nameTxt + "','" + uniqueName + "','"
								+ nameClass + "')";
						getConn().executeUpdate(insertQry);
					}
				} else {
					if (count < max) {
						if (!checkRowExistence(chkQry)) {
							String insertQry = "INSERT INTO names VALUES('"
									+ taxid + "','" + nameTxt + "','"
									+ uniqueName + "','" + nameClass + "')";
							getConn().executeUpdate(insertQry);
						}
					}
				}
				count++;
			}
		} catch (IOException e) {
			e.printStackTrace();
		}
	}

	public void populateCitations() {
		List<String> lines;
		try {
			lines = FileUtils.readLines(this.citations);
			Iterator<String> itr = lines.iterator();
			int count = 0;
			int max = 10;
			while (itr.hasNext()) {
				try {
					String aLine = itr.next().trim();
					String[] aLineArr = aLine.split("\\t\\|");
					Integer citId = Integer.parseInt(aLineArr[0]);
					String citKey = StringEscapeUtils.escapeSql(aLineArr[1]
							.trim());
					Integer pubmedId = Integer.parseInt(aLineArr[2].trim());
					String medlineId = StringEscapeUtils.escapeSql(aLineArr[3]
							.trim());
					String url = StringEscapeUtils
							.escapeSql(aLineArr[4].trim());
					String text = StringEscapeUtils.escapeSql(aLineArr[5]
							.trim());
					String taxidList = StringEscapeUtils.escapeSql(aLineArr[6]
							.trim());
					String chkQry = "SELECT * FROM citations WHERE "
							+ "cit_id='" + citId + "' AND cit_key='" + citKey
							+ "' AND pubmed_Id='" + pubmedId
							+ "' AND medline_Id='" + medlineId + "' AND url='"
							+ url + "' AND text='" + text
							+ "' AND taxid_list='" + taxidList + "'";

					if (!this.testFlag) {
						if (!checkRowExistence(chkQry)) {
							String insertQry = "INSERT INTO citations VALUES('"
									+ citId + "','" + citKey + "','" + pubmedId
									+ "','" + medlineId + "','" + url + "','"
									+ text + "','" + taxidList + "')";
							getConn().executeUpdate(insertQry);
						}
					} else {
						if (count < max) {
							if (!checkRowExistence(chkQry)) {
								String insertQry = "INSERT INTO citations VALUES('"
										+ citId
										+ "','"
										+ citKey
										+ "','"
										+ pubmedId
										+ "','"
										+ medlineId
										+ "','"
										+ url
										+ "','"
										+ text
										+ "','"
										+ taxidList + "')";

								getConn().executeUpdate(insertQry);
							}
						}
					}
				} catch (IndexOutOfBoundsException e) {
					continue;
				}catch(NumberFormatException e){
					continue;
				}
				count++;

			}
		} catch (IOException e) {
			e.printStackTrace();
		}

	}

	public void populateNodes() {
		try {
			List<String> lines = FileUtils.readLines(this.nodes);
			Iterator<String> itr = lines.iterator();
			int count = 0;
			int max = 10;
			while (itr.hasNext()) {
				String aLine = itr.next().trim();
				String[] aLineArr = aLine.split("\\t\\|\\t");
				Integer taxid = Integer.parseInt(aLineArr[0]);
				Integer parentTaxid = Integer.parseInt(aLineArr[1]);
				String rank = StringEscapeUtils.escapeSql(aLineArr[2].trim());
				String emblCode = StringEscapeUtils.escapeSql(aLineArr[3]
						.trim());
				Integer divisionId = Integer.parseInt(aLineArr[4]);
				Integer inheritedDivFlag = Integer.parseInt(aLineArr[5]);
				Integer geneticCodeId = Integer.parseInt(aLineArr[6]);
				Integer inheritedGcFlag = Integer.parseInt(aLineArr[7]);
				Integer mitochondrialGeneticCodeId = Integer
						.parseInt(aLineArr[8]);
				Integer inheritedMgcFlag = Integer.parseInt(aLineArr[9]);
				Integer genBankHiddenFlag = Integer.parseInt(aLineArr[10]);
				Integer hiddenSubtreeRoot = Integer.parseInt(aLineArr[11]);
				String comments = StringEscapeUtils.escapeSql(aLineArr[12]
						.trim());
				// Check if this entry is already in the table
				String chkQry = "SELECT * FROM nodes WHERE " + "tax_id ='"
						+ taxid + "' AND parent_tax_id='" + parentTaxid + "' "
						+ "AND rank='" + rank + "' AND embl_code='" + emblCode
						+ "' AND division_id='" + divisionId
						+ "' AND inherited_div_flag='" + inheritedDivFlag
						+ "' AND genetic_code_id='" + geneticCodeId
						+ "' AND mitochondrial_genetic_code_id ='"
						+ mitochondrialGeneticCodeId
						+ "' AND inherited_mgc_flag='" + inheritedMgcFlag
						+ "' " + "AND genbank_hidden_flag ='"
						+ genBankHiddenFlag + "' AND hidden_subtree_root ='"
						+ hiddenSubtreeRoot + "' AND  comments='" + comments
						+ "'";
				if (!this.testFlag) {
					if (!checkRowExistence(chkQry)) {
						if (comments.equals("|")) {
							comments = "";
						}
						String insertQry = "INSERT INTO nodes VALUES('" + taxid
								+ "','" + parentTaxid + "','" + rank + "','"
								+ emblCode + "','" + divisionId + "','"
								+ inheritedDivFlag + "','" + geneticCodeId
								+ "','" + inheritedGcFlag + "','"
								+ mitochondrialGeneticCodeId + "','"
								+ inheritedMgcFlag + "','" + genBankHiddenFlag
								+ "','" + hiddenSubtreeRoot + "','" + comments
								+ "')";
						getConn().executeUpdate(insertQry);
					}
				} else {
					if (count < max) {
						if (!checkRowExistence(chkQry)) {
							if (comments.equals("|")) {
								comments = "";
							}
							String insertQry = "INSERT INTO nodes VALUES('"
									+ taxid + "','" + parentTaxid + "','"
									+ rank + "','" + emblCode + "','"
									+ divisionId + "','" + inheritedDivFlag
									+ "','" + geneticCodeId + "','"
									+ inheritedGcFlag + "','"
									+ mitochondrialGeneticCodeId + "','"
									+ inheritedMgcFlag + "','"
									+ genBankHiddenFlag + "','"
									+ hiddenSubtreeRoot + "','" + comments
									+ "')";
							getConn().executeUpdate(insertQry);
						}
					}
				}
				count++;
			}
		} catch (IOException e) {
			e.printStackTrace();
		}
	}

	/**
	 * Returns true if the query returned any rows, if the query had no results
	 * it returns false
	 * 
	 * @param aQry
	 * @return
	 */
	private boolean checkRowExistence(String aQry) {
		boolean returnMe = true;
		ResultSet rs = getConn().executeQuery(aQry);
		// get the number of rows in the result set
		try {
			rs.last();
			int rowCount = rs.getRow();
			if (rowCount == 0) {
				returnMe = false;
			} else {
				returnMe = true;
			}
		} catch (SQLException e) {
			e.printStackTrace();
		}
		return returnMe;
	}

	/**
	 * @return the conn
	 */
	public DbConnector getConn() {
		return conn;
	}

	/**
	 * @param conn
	 *            the conn to set
	 */
	public void setConn(DbConnector conn) {
		this.conn = conn;
	}

	/**
	 * @return the dbUrl
	 */
	public String getDbUrl() {
		return dbUrl;
	}

	/**
	 * @param dbUrl
	 *            the dbUrl to set
	 */
	public void setDbUrl(String dbUrl) {
		this.dbUrl = dbUrl;
	}

	/**
	 * @return the username
	 */
	public String getUsername() {
		return username;
	}

	/**
	 * @param username
	 *            the username to set
	 */
	public void setUsername(String username) {
		this.username = username;
	}

	/**
	 * @return the password
	 */
	public String getPassword() {
		return password;
	}

	/**
	 * @param password
	 *            the password to set
	 */
	public void setPassword(String password) {
		this.password = password;
	}

	/**
	 * @return the names
	 */
	public File getNames() {
		return names;
	}

	/**
	 * @param names
	 *            the names to set
	 */
	public void setNames(File names) {
		this.names = names;
	}

	/**
	 * @return the nodes
	 */
	public File getNodes() {
		return nodes;
	}

	/**
	 * @param nodes
	 *            the nodes to set
	 */
	public void setNodes(File nodes) {
		this.nodes = nodes;
	}

	/**
	 * @return the citations
	 */
	public File getCitations() {
		return citations;
	}

	/**
	 * @param citations
	 *            the citations to set
	 */
	public void setCitations(File citations) {
		this.citations = citations;
	}

	/**
	 * @return the divisions
	 */
	public File getDivisions() {
		return divisions;
	}

	/**
	 * @param divisions
	 *            the divisions to set
	 */
	public void setDivisions(File divisions) {
		this.divisions = divisions;
	}

}
