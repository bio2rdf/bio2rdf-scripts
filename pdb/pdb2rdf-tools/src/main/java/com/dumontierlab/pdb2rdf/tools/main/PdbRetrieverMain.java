/**
 * Copyright (c) 2011 by Jose Cruz-Toledo
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
package com.dumontierlab.pdb2rdf.tools.main;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.OutputStream;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.concurrent.ArrayBlockingQueue;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.RejectedExecutionHandler;
import java.util.concurrent.ThreadPoolExecutor;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.zip.GZIPOutputStream;

import org.apache.commons.cli.CommandLine;
import org.apache.commons.cli.CommandLineParser;
import org.apache.commons.cli.GnuParser;
import org.apache.commons.cli.HelpFormatter;
import org.apache.commons.cli.Option;
import org.apache.commons.cli.OptionBuilder;
import org.apache.commons.cli.Options;
import org.apache.commons.cli.ParseException;
import org.apache.commons.io.FileUtils;
import org.xml.sax.InputSource;

import com.dumontierlab.pdb2rdf.model.PdbRdfModel;
import com.dumontierlab.pdb2rdf.parser.DetailLevel;
import com.dumontierlab.pdb2rdf.parser.PdbXmlParser;
import com.dumontierlab.pdb2rdf.tools.bin.PDBRetriever;
import com.dumontierlab.pdb2rdf.util.DirectoryIterator;
import com.dumontierlab.pdb2rdf.util.Pdb2RdfInputIterator;
import com.dumontierlab.pdb2rdf.util.Pdb2RdfInputIteratorAdapter;
import com.hp.hpl.jena.rdf.model.RDFWriter;
import com.hp.hpl.jena.rdf.model.RDFWriterF;
import com.hp.hpl.jena.rdf.model.impl.RDFWriterFImpl;

/**
 * @author Jose Cruz-Toledo
 * 
 */
public class PdbRetrieverMain {

	public static void main(String[] args) {
		Options options = createOptions();
		CommandLineParser parser = createCliParser();
		HashMap<String, String> parameters = new HashMap<String, String>();
		try {
			CommandLine cmd = parser.parse(options, args);

			if (cmd.hasOption("help")) {
				printUsage();
				System.exit(1);
			}
			if (cmd.hasOption("pdbmldir")) {
				parameters.put("pdbmldir", cmd.getOptionValue("pdbmldir"));
			} else {
				System.out.println("You must specify where a PDBML directory");
				printUsage();
				System.exit(1);
			}

			if (cmd.hasOption("rdfDir")) {
				parameters.put("rdfDir", cmd.getOptionValue("rdfDir"));
			} else {
				System.out.println("You must specify your output directory");
				printUsage();
				System.exit(1);
			}

			if (cmd.hasOption("recordType")) {
				if (checkRecordType(cmd.getOptionValue("recordType"))) {
					parameters.put("recordType",
							cmd.getOptionValue("recordType"));
				} else {
					System.out.println("Invalid record type selection!");
					printUsage();
					System.exit(1);
				}
			} else {
				System.out.println("You must specify a record type");
				printUsage();
				System.exit(1);
			}

			if (cmd.hasOption("detailLevel")) {
				if (checkDetailLevel(cmd.getOptionValue("detailLevel"))) {
					parameters.put("detailLevel",
							cmd.getOptionValue("detailLevel"));
				}
			}
			// if everything went OK
			if (parameters.size() > 0) {
				makeRDF(parameters);
			}

		} catch (ParseException e) {
			printUsage();
			System.exit(1);
		}
	}

	private static void makeRDF(HashMap<String, String> parameters) {

		// check the output directory from the input
		final File outDir = new File(parameters.get("rdfDir"));
		if (!outDir.isDirectory()) {
			System.out.println("The rdfDir is not a valid directory!");
			System.exit(1);
		}

		final File inputDir = new File(parameters.get("pdbmldir"));
		if (!inputDir.isDirectory()) {
			System.out.println("The pdbmldir is not a valid directory!");
			System.exit(1);
		}

		// now get an RDF writer
		final RDFWriter writer = getRDFXMLWriter();
		// get an Iterator for the PDBML files that will get rdfized
		final PDBRetriever r = new PDBRetriever(parameters.get("recordType"));
		
		List<String> ids = r.getPdbIds();
		List<File> files = r.getPDBXMLPaths(inputDir, ids);
		//now that we know what files we need to rdfize copy them somewhere
		System.out.println("Copying XML files");
		File pdbmlDir = new File("/tmp/inputPDBML/");
		r.copyFiles(files, pdbmlDir);
		Pdb2RdfInputIterator i = getIterator(pdbmlDir);
		
		final int inputSize = files.size();
		final AtomicInteger progressCount = new AtomicInteger();
		ExecutorService pool = null;
		if (outDir != null) {
			pool = getThreadPool(2);
		} else {
			// if output is going to the STDOUT then we need to do process in
			// sequential mode.
			pool = Executors.newSingleThreadExecutor();
		}

		final Object lock = new Object();
		while (i.hasNext()) {
			final InputSource input = i.next();
			pool.execute(new Runnable() {
				
				@Override
				public void run() {
					OutputStream out = System.out;
					PdbXmlParser parser = new PdbXmlParser();
					PdbRdfModel model = null;
					try {
						
							try {
								DetailLevel detailLevel = Enum.valueOf(
										DetailLevel.class,
										"COMPLETE");
								model = parser.parse(input, new PdbRdfModel(),
										detailLevel);
							} catch (IllegalArgumentException e) {
								System.out.println("caca");
								e.printStackTrace();
								System.exit(1);
							}
						
						if (outDir != null) {
							File directory = new File(outDir, model.getPdbId()
									.substring(1, 3));
							synchronized (lock) {
								if (!directory.exists()) {
									directory.mkdir();
								}
							}
							File file = new File(directory, model.getPdbId()
									+ ".rdf.gz");
							out = new GZIPOutputStream(new FileOutputStream(
									file));
						}
						writer.write(model, out, null);
					
					} catch (Exception e) {
						String id = null;
						if (model != null) {
							id = model.getPdbId();
						}
						System.out.println("Unable to parse input for PDB: " + id);
						e.printStackTrace();
					} finally {
						try {
							out.close();
						} catch (IOException e) {
							System.out.println("Unable to close output stream");
							e.printStackTrace();
						}
					}
				}
			});

		}
	}

	private static ExecutorService getThreadPool(int numOfThreads) {
		// twice the number of PU
		final Object monitor = new Object();
		int numberOfThreads = numOfThreads;
		System.out.println("Using " + numberOfThreads + " threads.");
		ThreadPoolExecutor threadPool = new ThreadPoolExecutor(numberOfThreads,
				numberOfThreads, 10, TimeUnit.MINUTES,
				new ArrayBlockingQueue<Runnable>(1),
				new RejectedExecutionHandler() {
					@Override
					public void rejectedExecution(Runnable r,
							ThreadPoolExecutor executor) {
						synchronized (monitor) {
							try {
								monitor.wait();
							} catch (InterruptedException e) {
								Thread.currentThread().interrupt();
							}
						}
						executor.execute(r);
					}
				}) {
			@Override
			protected void afterExecute(Runnable r, Throwable t) {
				synchronized (monitor) {
					monitor.notify();
				}
				super.afterExecute(r, t);
			}
		};

		return threadPool;
	}

	private static Pdb2RdfInputIterator getIterator(File aDir) {
		try {
			if (!aDir.exists() || !aDir.canRead() || !aDir.canExecute()) {
				System.out.println("Cannot access directory: " + aDir);
				System.exit(1);
			}
			return new Pdb2RdfInputIteratorAdapter(new DirectoryIterator(aDir,
					true));
		} catch (IOException e) {
			e.printStackTrace();
		}
		return null;
	}

	/**
	 * Return an RDFXML writter
	 * 
	 * @return
	 */
	private static RDFWriter getRDFXMLWriter() {
		RDFWriterF writerFactory = new RDFWriterFImpl();
		RDFWriter writer = writerFactory.getWriter("RDF/XML");
		return writer;
	}

	/**
	 * @return
	 */
	private static CommandLineParser createCliParser() {
		return new GnuParser();
	}

	@SuppressWarnings("static-access")
	public static Options createOptions() {
		Options options = new Options();

		Option help = new Option("help", false, "Print this message");
		Option inputPDBMLFiles = OptionBuilder
				.withArgName("inputPDBMLDir")
				.hasArg(true)
				.withDescription(
						"Location of directory containing the PDBML files")
				.create("pdbmldir");
		Option recordType = OptionBuilder
				.withArgName("recordType")
				.hasArg(true)
				.withDescription(
						"The type of PDB record that you wish to retrieve. Allowed values: DNA, RNA, Nucleic Acid, rna_super_high_res")
				.create("recordType");

		Option outputRDFDirectory = OptionBuilder
				.withArgName("outputRDFDir")
				.hasArg(true)
				.withDescription(
						"Location of destination directory where you want your RDF files")
				.create("rdfDir");

		Option noAtomSitesOption = OptionBuilder
				.hasArg(true)
				.withDescription(
						"Specify detail level: COMPLETE | ATOM | RESIDUE | EXPERIMENT | METADATA ")
				.create("detailLevel");

		options.addOption(noAtomSitesOption);
		options.addOption(help);
		options.addOption(inputPDBMLFiles);
		options.addOption(recordType);
		options.addOption(outputRDFDirectory);

		return options;

	}

	/**
	 * @param optionValue
	 * @return
	 */
	private static boolean checkDetailLevel(String optionValue) {
		if (optionValue.equalsIgnoreCase("COMPLETE")
				|| optionValue.equalsIgnoreCase("ATOM")
				|| optionValue.equals("RESIDUE")
				|| optionValue.equalsIgnoreCase("EXPERIMENT")
				|| optionValue.equalsIgnoreCase("METADATA")){
			return true;
		}
			return false;
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

	private static boolean checkRecordType(String aRecordType) {
		if (aRecordType.equalsIgnoreCase("rna")
				|| aRecordType.equalsIgnoreCase("dna")
				|| aRecordType.equalsIgnoreCase("nucleic acid") || aRecordType.equalsIgnoreCase ("rna_super_high_res")) {
			return true;
		} else {
			return false;
		}
	}

	private static void printUsage() {
		HelpFormatter hf = new HelpFormatter();
		hf.printHelp("pdbretriever [OPTIONS]", createOptions());
	}
}
