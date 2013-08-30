/**
 * Copyright (c) 2013 Dumontierlab
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
package com.dumontierlab.pdb2rdf.cluster;

import java.io.File;

import org.apache.commons.cli.CommandLine;
import org.apache.commons.cli.CommandLineParser;
import org.apache.commons.cli.GnuParser;
import org.apache.commons.cli.HelpFormatter;
import org.apache.commons.cli.Option;
import org.apache.commons.cli.OptionBuilder;
import org.apache.commons.cli.Options;
import org.apache.commons.cli.ParseException;
import org.apache.log4j.Logger;
import org.mortbay.jetty.Server;
import org.mortbay.jetty.servlet.Context;
import org.mortbay.jetty.servlet.ServletHolder;

import com.dumontierlab.pdb2rdf.cluster.servlet.ClusterController;

/**
 * @author Alexander De Leon
 */
public class ClusterServer {
	private static final Logger LOG = Logger.getLogger(ClusterServer.class);

	private static final int DEFAULT_PORT = 9966;

	public static void main(String[] args) {

		Options options = createOptions();
		CommandLineParser parser = createCliParser();

		try {
			CommandLine cmd = parser.parse(options, args);

			if (!cmd.hasOption("dir")) {
				LOG.fatal("You need to specify the input directory");
				System.exit(1);
			}
			File inputDir = new File(cmd.getOptionValue("dir"));
			if (!inputDir.exists() || !inputDir.isDirectory()) {
				LOG.fatal("The specified input directory is not a valid directory");
				System.exit(1);
			}

			int port = DEFAULT_PORT;
			if (cmd.hasOption("port")) {
				try {
					port = Integer.parseInt(cmd.getOptionValue("port"));
				} catch (NumberFormatException e) {
					LOG.fatal("Invalid port number", e);
					System.exit(1);
				}
			}
			boolean gzip = cmd.hasOption("gzip");

			try {
				startServer(inputDir, gzip, port);
			} catch (Exception e) {
				LOG.fatal("Unable to start the server.", e);
				System.exit(1);
			}

		} catch (ParseException e) {
			LOG.fatal("Unable understand your command.");
			printUsage();
			System.exit(1);
		}

	}

	private static void startServer(File inputDir, boolean gzip, int port) throws Exception {
		Server server = new Server(port);
		Context root = new Context(server, "/", Context.SESSIONS);
		root.addServlet(new ServletHolder(new ClusterController(inputDir, gzip)), "/*");
		server.start();
	}

	private static CommandLineParser createCliParser() {
		return new GnuParser();
	}

	@SuppressWarnings("static-access")
	private static Options createOptions() {
		Options options = new Options();
		Option dirOption = OptionBuilder.withArgName("path").withDescription("Directory where input files are located")
				.hasArg(true).isRequired(true).create("dir");
		options.addOption(dirOption);
		options.addOption("gzip", false, "Input is given as gzip file(s)");

		Option portOption = OptionBuilder.withArgName("number").withDescription(
				"The port number to listen for client connections.").hasArg(true).create("port");
		options.addOption(portOption);
		return options;
	}

	private static void printUsage() {
		HelpFormatter helpFormatter = new HelpFormatter();
		helpFormatter.printHelp("pdb2rdf [OPTIONS]", createOptions());
	}

}
