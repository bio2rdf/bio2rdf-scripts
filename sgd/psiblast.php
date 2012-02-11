<?php


class SGD_PSIBLAST {

	function __construct($infile, $outfile)
	{
		$this->_in = gzopen($infile,"r");
		if(!isset($this->_in)) {
			trigger_error("Unable to open $infile");
			return 1;
		}
		$this->_out = fopen($outfile,"w");
		if(!isset($this->_out)) {
			trigger_error("Unable to open $outfile");
			return 1;
		}
		
	}
	function __destruct()
	{
		fclose($this->_in);
		fclose($this->_out);
	}
	function Convert2RDF()
	{
		$buf = N3NSHeader();
		
		$z = 0;
		while($l = gzgets($this->_in,2048)) {
			$a = explode("\t",trim($l));
			
			$id1 = $a[0];
			$id2 = $a[7];
			$id = "aln_$id1_$id2";
		
			$buf .= QQuadL("sgd_resource:$id","rdfs:label","psiblast alignment between $id1 and $id2 [sgd_resource:$id]");
			$buf .= QQuad("sgd_resource:$id","rdf:type","sgd_vocabulary:PSIBLASTAlignment");
			$buf .= QQuad("sgd_resource:$id","sgd_vocabulary:query","sgd:$id1");
			$buf .= QQuad("sgd_resource:$id","sgd_vocabulary:target","sgd:$id2");
			$buf .= QQuadL("sgd_resource:$id","sgd_vocabulary:query_start",$a[1]);
			$buf .= QQuadL("sgd_resource:$id","sgd_vocabulary:query_stop",$a[2]);
			$buf .= QQuadL("sgd_resource:$id","sgd_vocabulary:target_start",$a[3]);
			$buf .= QQuadL("sgd_resource:$id","sgd_vocabulary:target_stop",$a[4]);
			$buf .= QQuadL("sgd_resource:$id","sgd_vocabulary:percent_aligned",$a[5]);
			$buf .= QQuadL("sgd:resource:$id","sgd_vocabulary:score",$a[6]);
			$buf .= QQuad("sgd:$id2","sgd_vocabulary:is-encoded-by","taxon:".$a[8]);
			//echo $buf;exit;

			if(++$z % 10000 == 1) {
				echo '.';
				fwrite($this->_out, $buf);
				$buf = '';
			}
		}
		fwrite($this->_out, $buf);
		
		return 0;
	}

};

?>
