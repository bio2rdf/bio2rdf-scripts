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

import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.List;
import java.util.zip.GZIPInputStream;

import org.apache.log4j.Logger;

public class FileCollectionIterator implements InputInterator {

	private static final Logger LOG = Logger.getLogger(FileCollectionIterator.class);

	private final List<File> files;
	private final boolean gzip;
	private int counter;

	public FileCollectionIterator(List<File> files, boolean gzip) throws IOException {
		this.files = files;
		this.gzip = gzip;
	}

	public boolean hasNext() {
		return counter < size();
	}

	public InputStream next() {
		File f = files.get(counter++);
		try {
			return getInputStream(f);
		} catch (IOException e) {
			LOG.error("Unable to open file=" + f.getPath() + ".", e);
			if (!hasNext()) {
				System.exit(0);
			}
			return next();
		}
	}

	public void remove() {
		// do nothing
	}

	private InputStream getInputStream(File f) throws IOException {
		FileInputStream fileInputStream = new FileInputStream(f);
		if (gzip) {
			return new GZIPInputStream(fileInputStream);
		}
		return fileInputStream;
	}

	@Override
	public int size() {
		return files.size();
	}
}