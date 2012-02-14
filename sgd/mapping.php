<?php
/**
Copyright (C) 2011 Michel Dumontier

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

class SGD_MAPPING {

	function __construct($infile = null, $outfile)
	{
		$this->_out = fopen($outfile,"w");
		if(!isset($this->_out)) {
			trigger_error("Unable to open $outfile");
			return 1;
		}
	}
	function __destruct()
	{
		fclose($this->_out);
	}
	function Convert2RDF()
	{		
		$buf = N3NSHeader();

		$buf .= QQuad("sgd_vocabulary:has-proper-part","owl:equivalentProperty","sio:SIO_000053");
		$buf .= QQuad("sgd_vocabulary:encodes","owl:equivalentProperty","sio:SIO_010078");
		$buf .= QQuad("sgd_vocabulary:is-about","owl:equivalentProperty","sio:SIO_000332");
		$buf .= QQuad("sgd_vocabulary:is-proper-part-of","owl:equivalentProperty","sio:SIO_000093");
		$buf .= QQuad("sgd_vocabulary:article","owl:equivalentProperty","sio:SIO_000212");
		$buf .= QQuad("sgd_vocabulary:has-participant","owl:equivalentProperty","sio:SIO_000132");
		$buf .= QQuad("sgd_vocabulary:is-described-by","owl:equivalentProperty","sio:SIO_000557");
	
		$buf .= QQuad("sgd_vocabulary:Protein","owl:equivalentClass","chebi:36080");
		$buf .= QQuad("sgd_vocabulary:RNA","owl:equivalentClass","chebi:33697");
		$buf .= QQuad("sgd_vocabulary:Chromosome","owl:equivalentClass","so:0000340");	
		
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
