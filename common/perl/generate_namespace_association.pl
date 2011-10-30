#!/usr/bin/perl

use PerlIO::gzip;

$file = shift;

$file =~ /^.*\.gz$/ ? open(INPUT,"<:gzip", $file) : open(INPUT, "<$file");

while($line = <INPUT>){
	# If the line is in ntriples format
	if($line =~ /^<http:\/\/bio2rdf\.org\/([^:]\S+):\S+> <\S+> <http:\/\/bio2rdf\.org\/([^:]\S+):\S+> \.$/){
		$from = $1;
		$to = $2;
		if($from =~ /\//){
			($temp,$from) = split(/\//,$from);
		}
		if($to =~ /\//){
			($temp,$to) = split(/\//,$to);
		}
		if($from =~ /:/){
			($from,$temp) = split(/:/,$from);
		}
		if($to =~ /:/){
			($to,$temp) = split(/:/,$to);
		}
		if($from !~ /record/){print "NNNN\t".$from."\t".$to."\n";}
	}
	# If the line is in nquads format
	if($line =~ /^<http:\/\/bio2rdf\.org\/([^:]\S+):\S+> <\S+> <http:\/\/bio2rdf\.org\/([^:]\S+):\S+> <\S+> \.$/){
		$from = $1;
		$to = $2;
		if($from =~ /\//){
			($temp,$from) = split(/\//,$from);
		}
		if($to =~ /\//){
			($temp,$to) = split(/\//,$to);
		}
		if($from =~ /:/){
			($from,$temp) = split(/:/,$from);
		}
		if($to =~ /:/){
			($to,$temp) = split(/:/,$to);
		}
		if($from !~ /record/){print "NNNN\t".$from."\t".$to."\n";}
	}
	# If the line is using N3 syntax simplification with @prefix
	if($line !~ /^\@prefix/ && $line !~ /"/ && $line !~ /</){
		$line =~ /^(\S+):\S+\s+\S+\s+(\S+):\S+\s*\.$/;
		$from = $1;
		$to = $2;
 		if($from !~ /^$/ && $to !~ /^$/) {print "NNNN\t".$from."\t".$to."\n";}
	}
}
