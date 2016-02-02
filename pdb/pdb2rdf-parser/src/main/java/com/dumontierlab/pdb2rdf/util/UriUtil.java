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

import java.io.UnsupportedEncodingException;
import java.math.BigInteger;
import java.net.URLEncoder;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

/**
 * @author Alexander De Leon
 */
public class UriUtil {

	public static String toCamelCase(String inputString) {
		if (inputString.length() == 0) {
			return inputString;
		}
		String[] parts = inputString.split("\\s");
		StringBuilder buffer = new StringBuilder();
		for (String part : parts) {
			if (parts.length > 0) {
				buffer.append(Character.toUpperCase(part.charAt(0)));
				if (part.length() > 1) {
					buffer.append(part.substring(1).toLowerCase());
				}
			}
		}
		return buffer.toString();
	}

	public static String removeSpaces(String inputString) {
		return inputString.replaceAll("\\s", "");
	}

	public static String replaceSpacesByUnderscore(String inputString) {
		return inputString.replaceAll("\\s", "_");
	}

	public static String replacePrimes(String inputString) {
		return inputString.replaceAll("'", "p");
	}
	public static String replaceDashes(String inputString){
		return inputString.replaceAll("-", "");
	}
	public static String replacePercents(String inputString){
		return inputString.replaceAll("%", "");
	}
	
	public static String makeHash(String inputString){
		
		try {
			MessageDigest md;
	        md = MessageDigest.getInstance("MD5");
	        byte[] md5hash = new byte[32];
	        md.update(inputString.getBytes("iso-8859-1"), 0, inputString.length());
	        md5hash = md.digest();
	        return convertToHex(md5hash);
		} catch (NoSuchAlgorithmException e) {
			e.printStackTrace();
		} catch (UnsupportedEncodingException e) {
			e.printStackTrace();
		} 
		return "ERROR";
	}

	private static String convertToHex(byte[] data) { 
        StringBuffer buf = new StringBuffer();
        for (int i = 0; i < data.length; i++) { 
            int halfbyte = (data[i] >>> 4) & 0x0F;
            int two_halfs = 0;
            do { 
                if ((0 <= halfbyte) && (halfbyte <= 9)) 
                    buf.append((char) ('0' + halfbyte));
                else 
                    buf.append((char) ('a' + (halfbyte - 10)));
                halfbyte = data[i] & 0x0F;
            } while(two_halfs++ < 1);
        } 
        return buf.toString();
    } 
	public static String urlEncode(String inputString) {
		try {
			return URLEncoder.encode(inputString, "UTF-8");
		} catch (UnsupportedEncodingException e) {
			assert false : "This should never happen";
			return null;
		}
	}

	public static String anonUri(String namespace, String hint) {
		if (hint == null) {
			hint = Double.toHexString(System.currentTimeMillis());
		}
		return namespace + hash(hint);
	}

	public static String hash(String message) {
		try {
			MessageDigest digest = java.security.MessageDigest.getInstance("MD5");
			digest.update(message.getBytes());
			BigInteger hash = new BigInteger(1, digest.digest());
			return hash.toString(16);
		} catch (NoSuchAlgorithmException e) {
			assert false : e.getMessage();
			return null;
		}

	}
}
