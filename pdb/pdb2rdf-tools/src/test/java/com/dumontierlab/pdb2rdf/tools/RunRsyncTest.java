package com.dumontierlab.pdb2rdf.tools;

import java.io.File;
import java.io.IOException;

import org.apache.commons.io.FileUtils;
import org.junit.Ignore;
import org.junit.Test;

import com.dumontierlab.pdb2rdf.tools.rsync.RunRsync;

public class RunRsyncTest {

	@Ignore
	@Test
	public void testRunRsync() {
		RunRsync r = new RunRsync();
	}

	@Ignore
	@Test
	public void testRunRsyncFileFile() {
		File log = new File("/tmp/mylog.log");
		File mirrorDir = new File("/opt/data/pdb/xml");
		RunRsync r = new RunRsync(log, mirrorDir);
		try {
			String content = FileUtils.readFileToString(log);
			System.out.println(content);
		} catch (IOException e) {
			e.printStackTrace();
		}
	}
}
