package com.dumontierlab.pdb2rdf.tools.rsync;

import java.io.BufferedInputStream;
import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.Writer;

public class RunRsync {
	static String sourceURI = "rsync.wwpdb.org::ftp_data/structures/divided/XML/";
	public String[] cmdArr = new String[8];
	File mirrorDirectory;
	File logFile;

	/**
	 * @param aLogFile
	 *            Log File which will be used to store the output of the rsync
	 *            execution
	 * @param mirrorDir
	 *            Directory where you wish to store the mirror of PDB
	 */
	public RunRsync(File aLogFile, File mirrorDir) {
		if (aLogFile == null) {
			System.out.println("a log file has not been specified");
			System.exit(-1);
		} else {
			setLogFile(aLogFile);
		}
		if (mirrorDir == null) {
			System.out.println("a mirror dir has not been specified");
			System.exit(-1);
		} else {
			setMirrorDirectory(mirrorDir);
		}
		populateCmdArr();
		try {
			System.out.println("Initiating Rsync request...");
			Process p = Runtime.getRuntime().exec(getCmdArr());
			p.waitFor();
			String output = RunRsync.writeProcessOutput(p);
			RunRsync.setContents(aLogFile, output);
			System.out.println("Finished transfering files");

		} catch (IOException e) {
			System.out.println("ca222caca");
			e.printStackTrace();
			System.exit(-1);
		} catch (InterruptedException e) {
			System.out.println("cacaca");
			System.exit(-1);
		}
	}

	public RunRsync() {
		// check if Rsync is available to the path
		try {
			Process p = Runtime.getRuntime().exec("/usr/bin/rsync");
			p.waitFor();
		} catch (IOException e) {
			e.printStackTrace();
		} catch (InterruptedException e) {
			e.printStackTrace();
		}
	}

	private void populateCmdArr() {
		this.cmdArr[0] = "/usr/bin/rsync";
		this.cmdArr[1] = "-ptrl";
		this.cmdArr[2] = "-v";
		this.cmdArr[3] = "-z";
		this.cmdArr[4] = "--delete";
		this.cmdArr[5] = "--port=33444";
		this.cmdArr[6] = getSourceURI();
		this.cmdArr[7] = getMirrorDirectory().getAbsolutePath();
	}

	public static String writeProcessOutput(Process aProc) {
		InputStreamReader tmpReader = new InputStreamReader(
				new BufferedInputStream(aProc.getInputStream()));
		BufferedReader reader = new BufferedReader(tmpReader);
		String returnMe = "";
		while (true) {
			String line = "";
			try {
				line = reader.readLine();
			} catch (IOException e) {
				System.out.println("Could not read input file");
				e.printStackTrace();
				System.exit(-1);
			}
			if (line == null)
				break;
			returnMe += line + "\n";
		}// while
		return returnMe;
	}// writeProcessOutput

	public static void setContents(File aFile, String aContents)
			throws FileNotFoundException, IOException {
		if (aFile == null) {
			throw new IllegalArgumentException("File should not be null.");
		}

		// use buffering
		Writer output = new BufferedWriter(new FileWriter(aFile));
		try {
			// FileWriter always assumes default encoding is OK!
			output.write(aContents);
		} finally {
			output.close();
		}
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
	 * @return the sourceURI
	 */
	public static String getSourceURI() {
		return sourceURI;
	}

	/**
	 * @param sourceURI
	 *            the sourceURI to set
	 */
	public static void setSourceURI(String sourceURI) {
		RunRsync.sourceURI = sourceURI;
	}

	/**
	 * @return the cmdArr
	 */
	public String[] getCmdArr() {
		return cmdArr;
	}

	/**
	 * @param cmdArr
	 *            the cmdArr to set
	 */
	public void setCmdArr(String[] cmdArr) {
		this.cmdArr = cmdArr;
	}

	/**
	 * @return the mirrorDirectory
	 */
	public File getMirrorDirectory() {
		return mirrorDirectory;
	}

	/**
	 * @param mirrorDirectory
	 *            the mirrorDirectory to set
	 */
	public void setMirrorDirectory(File mirrorDirectory) {
		this.mirrorDirectory = mirrorDirectory;
	}

}
