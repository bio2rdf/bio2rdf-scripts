<?php
/**
Copyright (C) 2012 Michel Dumontier, Dana Klassen

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

/**
 * BioPAX RDFizer
 * @version 1.0
 * @author Michel Dumontier
 * @author Dana Klassen
 * @description 
*/
require('../../php-lib/rdfapi.php');
class BioPAXParser extends RDFFactory 
{		
	function __construct($argv) {
		parent::__construct();
		
		// set and print application parameters
		$this->AddParameter('files',true,null,'all','entries to process: comma-separated list or hyphen-separated range');
		$this->AddParameter('indir',false,null,'/tmp/biopax/download/','directory to download into and parse from');
        $this->AddParameter('outdir',false,null,'/tmp/biopax/data/','directory to place rdfized files');
        $this->AddParameter('download',false,'true|false','download remote file to indirectory');
        $this->AddParameter('download_url',false,null,'http://pathway-commons.googlecode.com/files/pathwaycommons2-Sept2012.owl.zip');
		$this->AddParameter('gzip',false,'true|false','true','gzip the output');
		if($this->SetParameters($argv) == FALSE) {
			$this->PrintParameters($argv);
			exit;
		}
		if($this->CreateDirectory($this->GetParameterValue('indir')) === FALSE) exit;
		if($this->CreateDirectory($this->GetParameterValue('outdir')) === FALSE) exit;
		
		return TRUE;
	}
	
	function Run()
	{	
        $indir  = $this->GetParameterValue('indir');
        $outdir = $this->GetParameterValue('outdir');
        $gz =  false;
        $file = $indir."pathwaycommons.owl.zip";
        $outfile = $outdir."pathwaycommons_map.nt";

        if($this->GetParameterValue('gzip') ){
            $gz=true;
            $outfile.=".gz";
        } 
        
        if ($this->GetParameterValue('download')){
            $download = $this->GetParameterValue('download');
            echo "INFO: Download file from ".$this->GetParameterValue('download_url');
            file_put_contents($file,file_get_contents($this->GetParameterValue('download_url')));
        }

        echo "INFO: Unzipping ".$file."\n";
        $zip = new ZipArchive;
        if ($zip->open($file) === TRUE) {
           echo "INFO: Extracting to".$indir."\n"; 
            $zip->extractTo($indir);
            $zip->close();
        } else {
            echo 'failed';
        }
        echo "INFO: Unzipped pathway commons file\n";

        $this->SetWriteFile($outfile,$gz);
        echo "INFO: Setting outfile to ".$outfile."\n";

        // Convert to ntriples
        // $cmd = "rapper ".$file." > ".$indir."pathwaycommons.nt";
        // exec($cmd);

        // Generate links mapping identifiers.org to bio2rdf.org
        // this would be so much easier with sed :) 

        $mapping = array(
        // match pubmed identifiers    
        // http://identifiers.org/pubmed/11447118
        'pubmed' => array('pattern'=> "/http:.*pubmed\/(\d+)/", 'ns'=>'pubmed'),

        // taxonomy
        // http://identifiers.org/taxonomy/9606
        'taxonomy' => array('pattern'=> "/http:.*taxonomy\/(\d+)/",'ns'=>'taxon'),
        
        // uniprot
        // http://identifiers.org/uniprot/Q9R1Z8
        'uniprot' => array('pattern'=>"/http:.*uniprot\/([A-Za-z0-9]+)/" ,'ns'=>'uniprot')
    ); // end mapping 


        preg_match('/.*(pathwaycommons[A-Za-z0-9-]+.owl)/',$this->GetParameterValue('download_url'),$filematch);
        $handle = fopen($indir.$filematch[1],"r");
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) != false){

               preg_match('/http:\/\/identifiers\.org\/([a-zA-Z0-9]+)\/[a-zA-Z0-9]+/',$buffer,$match);
                if($match){
                    if ( array_key_exists($match[1],$mapping) && $url = $mapping[$match[1]]){
                        preg_match($url['pattern'],$match[0],$m);
                        $this->AddRDF($this->QQuadO_URL($url['ns'].":".$m[1],"owl:sameAs",$match[0]));
                        $this->WriteRDFBufferToWriteFile();
                    }
                }
            }

            fclose($handle);
        }else{
            echo "ERROR: Unable to open file";
            exit();
        }

        // output triples
	}

}


set_error_handler('error_handler');
$parser = new BioPAXParser($argv);
$parser->Run();

	
	
