/**
 * This class creates a jdbc connection to a mysql server
 * If no username is specified the defaults are
 * user: pdb_user
 * pass: pdb_password!
 * 
 * SeeAlso: http://www.vogella.de/articles/MySQLJava/article.html#jdbc
 */
package com.dumontierlab.pdb2rdf.tools.sql;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;

public class DbConnector {
	private Connection connection = null;
	private Statement statement = null;
	private PreparedStatement preparedStatement = null;
	private ResultSet resultSet = null;
	private static final String DRIVER = "com.mysql.jdbc.Driver";
	private static final String DB_URL = "jdbc:mysql://localhost/pdb_updates";
	private static final String username = "pdb_user";
	private static final String password = "pdb_password!";

	public DbConnector() {
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

	public DbConnector(String dbURL, String user, String passwd) {
		try {
			Class.forName(DRIVER);
			this.connection = DriverManager.getConnection(dbURL, user, passwd);
		} catch (SQLException e1) {
			System.out.println("caca");
			e1.printStackTrace();
		} catch (ClassNotFoundException e) {
			System.out.println("caca2");
			e.printStackTrace();
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
	
	public int executeUpdate(String aQry){
		int returnMe = -1;
		Statement st = null;
		try{
			st = this.getConnect().createStatement();
			returnMe = st.executeUpdate(aQry);
		}catch(SQLException e){
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
