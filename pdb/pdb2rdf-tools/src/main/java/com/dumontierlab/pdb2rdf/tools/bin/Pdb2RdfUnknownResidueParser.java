/**
 * Parse the unknown_residue.log from the output of Pdb2RDF
 * Generates a HashMap with a unique set of residue names
 */
package com.dumontierlab.pdb2rdf.tools.bin;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.util.HashSet;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * @author Jose Cruz-Toledo
 * 
 */
public class Pdb2RdfUnknownResidueParser {
	/**
	 * The log file with the unknown residues
	 */
	private File logFile;

	private final Set<String> cleanUnkownResidues;

	public Pdb2RdfUnknownResidueParser(File aFile) {
		logFile = aFile;
		cleanUnkownResidues = cleanUnknownResidues(aFile);
	}

	/**
	 * Populate the HashMap with the unique unknown residues
	 * 
	 * @param aFile
	 * @return
	 */
	public Set<String> cleanUnknownResidues(File aFile) {
		HashSet<String> returnMe = new HashSet<String>();
		try {
			BufferedReader reader = new BufferedReader(new FileReader(aFile));
			String aLine;
			while ((aLine = reader.readLine()) != null) {

				String linePattern = ".*type:\\s+(.*)";
				Pattern lPattern = Pattern.compile(linePattern);
				Matcher matches = lPattern.matcher(aLine);
				if (matches.matches()) {
					returnMe.add(matches.group(1));

				}
			}
		} catch (IOException e) {
			e.printStackTrace();
		}
		return returnMe;
	}

	/**
	 * @return the logFile
	 */
	public File getLogFile() {
		return logFile;
	}

	/**
	 * @param logFile
	 *            the logFile to set
	 */
	public void setLogFile(File logFile) {
		this.logFile = logFile;
	}

	/**
	 * @return the cleanUnkownResidues
	 */
	public Set<String> getCleanUnkownResidues() {
		return cleanUnkownResidues;
	}

}
