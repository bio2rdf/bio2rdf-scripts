<?php

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

		QQuad("sgd_vocabulary:has-proper-part","owl:equivalentProperty","sio:SIO_000053");
		QQuad("sgd_vocabulary:encodes","owl:equivalentProperty","sio:SIO_010078");
		QQuad("sgd_vocabulary:is-about","owl:equivalentProperty","sio:SIO_000332");
		QQuad("sgd_vocabulary:is-proper-part-of","owl:equivalentProperty","sio:SIO_000093");
		QQuad("sgd_vocabulary:article","owl:equivalentProperty","sio:SIO_000212");
		QQuad("sgd_vocabulary:has-participant","owl:equivalentProperty","sio:SIO_000132");
		QQuad("sgd_vocabulary:is-described-by","owl:equivalentProperty","sio:SIO_000557");
	
		QQuad("sgd_vocabulary:Protein","owl:equivalentClass","chebi:36080");
		QQuad("sgd_vocabulary:RNA","owl:equivalentClass","chebi:33697");
		QQuad("sgd_vocabulary:Chromosome","owl:equivalentClass","so:0000340");	
		
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
