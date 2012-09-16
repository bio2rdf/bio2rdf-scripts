package com.dumontierlab.pdb2rdf.tools.rsync;

import static org.junit.Assert.assertEquals;

import java.util.LinkedList;

import org.junit.Ignore;
import org.junit.Test;

public class RsyncParserTest {

	@Ignore
	@Test
	public void testRsyncParser() {
		RsyncParser r = new RsyncParser();
		LinkedList<String> ll = r.getModifiedEntries();
		LinkedList<String> ll2 = r.getDeletedEntries();
		assertEquals(0, ll.size());
		assertEquals(0, ll2.size());
	}

}
