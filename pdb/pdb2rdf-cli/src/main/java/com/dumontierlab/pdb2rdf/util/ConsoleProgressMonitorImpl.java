/**
 * Copyright (c) 2009 Dumontierlab
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
package com.dumontierlab.pdb2rdf.util;

import java.io.IOException;

import jline.ConsoleReader;
import jline.Terminal;

/**
 * @author Alexander De Leon
 */
public class ConsoleProgressMonitorImpl implements ProgressMonitor {

	private final Terminal terminal;
	private final ConsoleReader reader;

	public ConsoleProgressMonitorImpl() throws IOException {
		terminal = Terminal.setupTerminal();
		reader = new ConsoleReader();
		terminal.beforeReadLine(reader, "", (char) 0);
	}

	public void setProgress(int completed, int total) {
		int w = reader.getTermwidth();
		int progress = (completed * 50) / total;
		String totalStr = String.valueOf(total);
		String percent = String.format(" %0" + totalStr.length() + "d/%s [", completed, totalStr);
		String result = percent + repetition("=", progress) + repetition(" ", 50 - progress) + "]";
		try {
			reader.getCursorBuffer().clearBuffer();
			reader.getCursorBuffer().write(result);
			reader.setCursorPosition(w);
			reader.redrawLine();
		} catch (IOException e) {
			e.printStackTrace();
		}

	}

	private String repetition(String string, int progress) {
		StringBuilder buffer = new StringBuilder();
		for (int i = 0; i < progress; i++) {
			buffer.append(string);
		}
		return buffer.toString();
	}

}
