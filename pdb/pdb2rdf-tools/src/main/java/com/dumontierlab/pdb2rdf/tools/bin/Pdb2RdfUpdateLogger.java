/**
 * This class does several things
 * - 
 */
package com.dumontierlab.pdb2rdf.tools.bin;

import com.dumontierlab.pdb2rdf.tools.rsync.RsyncParser;
import com.dumontierlab.pdb2rdf.tools.rsync.RunRsync;
import com.dumontierlab.pdb2rdf.tools.sql.DbConnector;

/**
 * @author Jose Cruz-Toledo
 *
 */
public class Pdb2RdfUpdateLogger {
	DbConnector db;
	RsyncParser rsyncParser;
	RunRsync rsyncRunner;
	
	public Pdb2RdfUpdateLogger(){
		//initialize everything
		db = new DbConnector();
		rsyncRunner = new RunRsync();
		rsyncParser = new RsyncParser();
	}
	
	public Pdb2RdfUpdateLogger(DbConnector aDb, RunRsync aRsyncRunner, RsyncParser aRsyncParser){
		db = aDb;
		rsyncParser = aRsyncParser;
		rsyncRunner = aRsyncRunner;
	}
	

	/**
	 * @return the db
	 */
	public DbConnector getDb() {
		return db;
	}

	/**
	 * @param db the db to set
	 */
	public void setDb(DbConnector db) {
		this.db = db;
	}

	/**
	 * @return the rsyncParser
	 */
	public RsyncParser getRsyncParser() {
		return rsyncParser;
	}

	/**
	 * @param rsyncParser the rsyncParser to set
	 */
	public void setRsyncParser(RsyncParser rsyncParser) {
		this.rsyncParser = rsyncParser;
	}

	/**
	 * @return the rsyncRunner
	 */
	public RunRsync getRsyncRunner() {
		return rsyncRunner;
	}

	/**
	 * @param rsyncRunner the rsyncRunner to set
	 */
	public void setRsyncRunner(RunRsync rsyncRunner) {
		this.rsyncRunner = rsyncRunner;
	}
}
