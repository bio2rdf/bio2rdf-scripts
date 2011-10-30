#!/usr/bin/perl

use PerlIO::gzip;

%hash = ();
%hash = {};

while(<STDIN>){
	$_ =~ /\s+(\d+)\s+NNNN\s+(\w+)\s+(\w+)/;
	if(exists $hash{"$2-$3"}){
		$temp = $hash{"$2-$3"};
		$temp = $temp+$1;
		$hash{"$2-$3"}=$temp;
	}
	else{
		$hash{"$2-$3"} = $1;
	}
}

while ( ($key, $value) = each(%hash) ) {
	($first, $second) = split(/-/, $key);
	print "$value\t$first\t$second\n";
}
