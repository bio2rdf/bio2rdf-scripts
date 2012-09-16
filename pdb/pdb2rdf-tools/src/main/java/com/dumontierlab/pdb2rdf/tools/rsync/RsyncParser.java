package com.dumontierlab.pdb2rdf.tools.rsync;

import java.io.BufferedReader;
import java.io.DataInputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.LinkedList;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class RsyncParser {
	private File rsyncLogFile;
	private LinkedList<String> modifiedEntries;
	private LinkedList<String> deletedEntries;
	
	public RsyncParser(){
		modifiedEntries = new LinkedList<String>();
		deletedEntries = new LinkedList<String>();
	}
	
	public RsyncParser(File logFile){
		modifiedEntries = new LinkedList<String>();
		deletedEntries = new LinkedList<String>();
		
		if(logFile == null){
			System.out.println("You did not provide a Log File");
			System.exit(-1);
		}else{
			readLogFile(logFile);
		}
	}

	private void readLogFile(File logFile) {
		// This method 
		FileInputStream fis = null;
		DataInputStream dis = null;
		try{
			fis = new FileInputStream(logFile);
			dis = new DataInputStream(fis);
			BufferedReader br = new BufferedReader(new InputStreamReader(dis));
			String aLine;
			
			String dPattern = "^deleting\\s+\\w{2}\\/(.*)\\.xml\\.gz$";
			Pattern deletePattern = Pattern.compile(dPattern);
			
			String mPattern = "^\\w{2}\\/(.*)\\.xml\\.gz$";
			Pattern modifyPattern = Pattern.compile(mPattern);
			
			while((aLine = br.readLine()) != null){
				//System.out.println(aLine);
				Matcher delMatch = deletePattern.matcher(aLine.trim());
				Matcher modMatch = modifyPattern.matcher(aLine.trim());
				
				//parse lines that are going to be deleted and add to deletedEntries
				if(delMatch.matches()){
					this.deletedEntries.add(delMatch.group(1).trim());
				}
				//parse the lines that are going to be modified and add to modifiedEntries
				if(modMatch.matches()){
					this.modifiedEntries.add(modMatch.group(1).trim());
				}				
			}	
			fis.close();
			dis.close();
		} catch(FileNotFoundException e){
			e.printStackTrace();
		} catch (IOException e){
			e.printStackTrace();
		}
	}

	/**
	 * @return the rsyncLogFile
	 */
	public File getRsyncLogFile() {
		return rsyncLogFile;
	}

	/**
	 * @param rsyncLogFile the rsyncLogFile to set
	 */
	public void setRsyncLogFile(File rsyncLogFile) {
		this.rsyncLogFile = rsyncLogFile;
	}

	/**
	 * @return the modifiedEntries
	 */
	public LinkedList<String> getModifiedEntries() {
		return modifiedEntries;
	}

	/**
	 * @param modifiedEntries the modifiedEntries to set
	 */
	public void setModifiedEntries(LinkedList<String> modifiedEntries) {
		this.modifiedEntries = modifiedEntries;
	}

	/**
	 * @return the deletedEntries
	 */
	public LinkedList<String> getDeletedEntries() {
		return deletedEntries;
	}

	/**
	 * @param deletedEntries the deletedEntries to set
	 */
	public void setDeletedEntries(LinkedList<String> deletedEntries) {
		this.deletedEntries = deletedEntries;
	}
	
	

}
