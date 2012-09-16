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
package com.dumontierlab.pdb2rdf.tools.lib;

import java.io.File;
import java.io.FileNotFoundException;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.Scanner;

/**
 * A simple handler for jdbc
 * @author "Jose Cruz-Toledo"
 * http://www.vogella.de/articles/MySQLJava/article.html#jdbc
 */
public class DbConnector {
	private Connection connection = null;
	private Statement statement = null;
	private PreparedStatement preparedStatement = null;
	private ResultSet resultSet = null;
	private static final String DRIVER = "com.mysql.jdbc.Driver";
	private String database = "ncbi_taxonomy";
	private String DB_URL = "jdbc:mysql://localhost/"+database;
	private String username = "pdb_user";
	private String password = "pdb_password!";
	
	public DbConnector(){
		try {
			Class.forName(DRIVER).newInstance();
			this.connection = DriverManager.getConnection(DB_URL, username,
					password);
		} catch (ClassNotFoundException e) {
			e.printStackTrace();
		} catch (SQLException e) {
			e.printStackTrace();
		} catch (InstantiationException e) {
			e.printStackTrace();
		} catch (IllegalAccessException e) {
			e.printStackTrace();
		} catch (NullPointerException e) {
			e.printStackTrace();
		}
	}
	

	/**
	 * 
	 * @param dbURL the jdbc type url for the database i.e: jdbc:mysql://localhost/someDb 
	 * @param user the user name 
	 * @param passwd the password
	 */
	public DbConnector(String dbURL, String user, String passwd) {
		try {
			DB_URL = dbURL;
			username = user;
			password = passwd;
			
			Class.forName(DRIVER);
			this.connection = DriverManager.getConnection(dbURL, user, passwd);
			
		} catch (SQLException e1) {
			System.out.println("Could not connect to Server (---)");
			e1.printStackTrace();
		} catch (ClassNotFoundException e) {
			System.out.println("caca2");
			e.printStackTrace();
		}
	}

	public void executeSqlScript( File inputFile) {
	    // Delimiter
	    String delimiter = ";";

	    // Create scanner
	    Scanner scanner;
	    try {
	        scanner = new Scanner(inputFile).useDelimiter(delimiter);
	    } catch (FileNotFoundException e1) {
	        e1.printStackTrace();
	        return;
	    }

	    // Loop through the SQL file statements 
	    Statement currentStatement = null;
	    while(scanner.hasNext()) {

	        // Get statement 
	        String rawStatement = scanner.next() + delimiter;
	        try {
	            // Execute statement
	            currentStatement = getConnect().createStatement();
	            currentStatement.execute(rawStatement);
	        } catch (SQLException e) {
	            e.printStackTrace();
	        } finally {
	            // Release resources
	            if (currentStatement != null) {
	                try {
	                    currentStatement.close();
	                } catch (SQLException e) {
	                    e.printStackTrace();
	                }
	            }
	            currentStatement = null;
	        }
	    }
	}
	/**
	 * This method creates a statement and executes it on the Server.
	 * 
	 * @return ResultSet
	 */
	public ResultSet executeQuery(String aQry) {
		ResultSet returnMe = null;
		Statement st = null;
		try {
			st = this.getConnect().createStatement();
			returnMe = st.executeQuery(aQry);
		} catch (SQLException e) {
			e.printStackTrace();
		}
		return returnMe;
	}

	public int executeUpdate(String aQry) {
		int returnMe = -1;
		Statement st = null;
		try {
			st = this.getConnect().createStatement();
			returnMe = st.executeUpdate(aQry);
		} catch (SQLException e) {
			e.printStackTrace();
		}
		return returnMe;
	}

	/**
	 * This method returns the column names for a result set
	 * 
	 * @param resultSet
	 * @return column names
	 */
	public ArrayList<String> getColumnNames(ResultSet resultSet) {
		ArrayList<String> returnMe = new ArrayList<String>();

		try {
			for (int i = 1; i <= resultSet.getMetaData().getColumnCount(); i++) {
				returnMe.add(resultSet.getMetaData().getColumnClassName(i));
			}
		} catch (SQLException e) {
			e.printStackTrace();
		}
		return returnMe;
	}

	public void close() {
		try {
			if (resultSet != null) {
				resultSet.close();
			}

			if (statement != null) {
				statement.close();
			}

			if (connection != null) {
				connection.close();
			}
		} catch (Exception e) {
			System.out
					.println("closing stuff problem\nThey are obviously chasing you!");
		}
	}

	/**
	 * @return the connection
	 */
	public Connection getConnect() {
		return connection;
	}

	/**
	 * @param connection
	 *            the connection to set
	 */
	public void setConnect(Connection connect) {
		this.connection = connect;
	}

	/**
	 * @return the statement
	 */
	public Statement getStatement() {
		return statement;
	}

	/**
	 * @param statement
	 *            the statement to set
	 */
	public void setStatement(Statement statement) {
		this.statement = statement;
	}

	/**
	 * @return the resultSet
	 */
	public ResultSet getResultSet() {
		return resultSet;
	}

	/**
	 * @param resultSet
	 *            the resultSet to set
	 */
	public void setResultSet(ResultSet resultSet) {
		this.resultSet = resultSet;
	}
	
	
}
