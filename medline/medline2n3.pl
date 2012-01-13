###############################################################################
#Copyright (C) 2011 Alison Callahan, Marc-Alexandre Nolin
#
#Permission is hereby granted, free of charge, to any person obtaining a copy of
#this software and associated documentation files (the "Software"), to deal in
#the Software without restriction, including without limitation the rights to
#use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
#of the Software, and to permit persons to whom the Software is furnished to do
#so, subject to the following conditions:
#
#The above copyright notice and this permission notice shall be included in all
#copies or substantial portions of the Software.
#
#THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
#SOFTWARE.
###############################################################################

#!/usr/bin/perl

use Bio::Biblio;
use Bio::Biblio::IO;

binmode STDIN, ':utf8';
binmode STDOUT, ':utf8';

$stream = Bio::Biblio::IO->new('-fh' => \*STDIN , '-format' => 'medlinexml', '-result' => 'medline2ref');

while ( $citation = $stream->next_bibref() ) {
	$nbr_authors = 0;
	$nbr_contributors = 0;
	$nbr_chemicals = 0;
	$nbr_cins = 0;	
	$nbr_cons = 0;	
	$nbr_errf = 0;	
	$nbr_erri = 0;	
	$nbr_gn = 0;	
	$nbr_grants = 0;
	$nbr_mesh = 0;
	$nbr_orri = 0;	
	$nbr_otha = 0;
	$nbr_othi = 0;
	$nbr_repf = 0;
	$nbr_repi = 0;
	$nbr_reti = 0;
	$nbr_reto = 0;
	$nbr_updi = 0;
	$nbr_updo = 0;
	$nbr_sfpi = 0;
	$nbr_xref = 0;

	$pmid = $citation->pmid;
	$type = $citation->type;
	$title = $citation->title;
	$title =~ s/"/&quot;/g;
	print "<http://bio2rdf.org/pubmed:$pmid> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:$type> .\n";
	print "<http://bio2rdf.org/pubmed:$pmid> <http://www.w3.org/2000/01/rdf-schema#label> ".'"'."$title [pubmed:$pmid]".'"'. " .\n";
	print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/identifier> ".'"'."pubmed:$pmid".'"'. " .\n";
	print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/title> ".'"'.$title.'"'. " .\n"; 

	$abstract = $citation->abstract;
	$abstract =~ s/"/&quot;/g;
	if($abstract !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:abstract> <http://bio2rdf.org/pubmed_resource:$pmid"."_ABSTRACT> .\n";
		print "<http://bio2rdf.org/pubmed_resource:$pmid"."_ABSTRACT> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:ArticleAbstract> .\n";
		print "<http://bio2rdf.org/pubmed_resource:$pmid"."_ABSTRACT> <http://purl.org/dc/terms/abstract> ".'"'.$abstract.'"'." .\n";
		if($citation->abstract_language !~ /^$/){print "<http://bio2rdf.org/pubmed:$pmid"."_ABSTRACT> <http://purl.org/dc/terms/language> ".'"'.$citation->abstract_language.'"'." .\n";}
		if($citation->abstract_type !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_ABSTRACT> <http://bio2rdf.org/pubmed_vocabulary:abstract_type> ".'"'.$citation->abstract_type.'"'." .\n";}
	}

	$language = $citation->language;
	if($language !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/language> ".'"'.$language.'"'. " .\n";
	}

	$olanguage = $citation->other_languages;;
	if($olanguage !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:other_language> ".'"'.$olanguage.'"'. " .\n";
	}

	$affiliation = $citation->affiliation;
	$affiliation =~ s/"/&quot;/g;
	if($affiliation !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:affiliation> ".'"'.$affiliation.'"'. " .\n";
	}

	$gs = $citation->gene_symbols;
	if($gs !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:gene_symbols> ".'"'.$gs.'"'. " .\n";
	}

	$citation_owner = $citation->citation_owner;
	$citation_owner =~ s/"/&quot;/g;
	if($citation_owner !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:citation_owner> ".'"'.$citation_owner.'"'. " .\n";
	}

	$medline_id = $citation->medline_id;
	if($medline_id !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:medline_id> ".'"'.$medline_id.'"'. " .\n";
	}

	$medline_page = $citation->medline_page;
	if($medline_page !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:medline_page> ".'"'.$medline_page.'"'. " .\n";
	}

	$nor = $citation->number_of_references;
	if($nor !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:number_of_references> ".'"'.$nor.'"'. " .\n";
	}

	$vt = $citation->vernacular_title;
	$vt =~ s/"/&quot;/g;
	if($vt !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:vernacular_title> ".'"'.$vt.'"'. " .\n";
	}

	$rights = $citation->rights;
	$rights =~ s/"/&quot;/g;
	if($rights !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/rights> ".'"'.$rights.'"'. " .\n";
	}

	$season = $citation->season;
	if($season !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:season> ".'"'.$season.'"'. " .\n";
	}

	$rs = $citation->repository_subset;
	$rs =~ s/"/&quot;/g;
	if($rs !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:repository_subset> ".'"'.$rs.'"'. " .\n";
	}

	$shs = $citation->subject_headings_source;
	if($shs !~ /^$/){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:subject_headings_source> ".'"'.$shs.'"'. " .\n";
	}

	$sh = $citation->subject_headings;
	if(keys%$sh > 0){
		while (($key_sh, $value_sh) = each(%$sh)){
                        print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:subject_headings> ".'"'.$key_sh.'"'." .\n";
                }
	}

	$chemicals = $citation->chemicals;
	if(@$chemicals > 0){
		foreach(@$chemicals){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:chemicals> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."CHEM_$nbr_chemicals> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CHEM_$nbr_chemicals> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Chemical> .\n";
			if($_->{'registryNumber'} =~ /^0$/){
				$nos = $_->{'nameOfSubstance'};
				$nos =~ s/"/&quot;/g;
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CHEM_$nbr_chemicals> <http://purl.org/dc/terms/title> ".'"'.$nos.'"'." .\n";
			}
			elsif($_->{'registryNumber'} =~ /^EC\s{1}(.*)$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CHEM_$nbr_chemicals> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/ec:$1> .\n";
			}
			elsif($_->{'registryNumber'} =~ /^\d+?-\d+?-\d+?$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CHEM_$nbr_chemicals> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/cas:".$_->{'registryNumber'}."> .\n";
			}
			elsif($_->{'registryNumber'} =~ /^\D{2,}_.*$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CHEM_$nbr_chemicals> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/ncbi:".$_->{'registryNumber'}."> .\n";
			}
			else{
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CHEM_$nbr_chemicals> <http://purl.org/dc/terms/title> ".'"'.$_->{'registryNumber'}.":".$_->{'nameOfSubstance'}.'"'." .\n";
			}
		$nbr_chemicals++;
		}
	}

	$cins = $citation->comment_ins;
	if(@$cins > 0){
		foreach(@$cins){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_resource:comment_ins> <http://bio2rdf.org/pubmed_vocabulary:$pmid"."_"."CINS_$nbr_cins> .\n";
                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CINS_$nbr_cins> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Comment_ins> .\n";
			while (($key_cins, $value_cins) = each(%$_)){
				if($key_cins =~ /^refSource$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CINS_$nbr_cins> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_cins.'"'." .\n";
				}
				elsif($key_cins =~ /^PMID$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CINS_$nbr_cins> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_cins."> .\n";
				}
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CINS_$nbr_cins> <http://bio2rdf.org/pubmed_vocabulary:other_comment_ins>".'"'.$key_cins.":".$value_cins.'"'." .\n";
				}
			}
			$nbr_cins++;
		}
	}

	$cons = $citation->comment_ons;
	if(@$cons > 0){
		foreach(@$cons){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:comment_ons> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONS_$nbr_cons> .\n";
                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONS_$nbr_cons> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Comment_ons> .\n";
			while (($key_cons, $value_cons) = each(%$_)){
				if($key_cons =~ /^refSource$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONS_$nbr_cons> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_cons.'"'." .\n";
				}
				elsif($key_cons =~ /^PMID$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONS_$nbr_cons> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_cons."> .\n";
				}
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONS_$nbr_cons> <http://bio2rdf.org/pubmed_vocabulary:other_comment_ons>".'"'.$key_cons.":".$value_cons.'"'." .\n";
				}
			}
			$nbr_cons++;
		}
	}

	$errf = $citation->erratum_fors;
	if(@$errf > 0){
		foreach(@$errf){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_resource:erratum_fors> <http://bio2rdf.org/pubmed_vocabulary:$pmid"."_"."ERRF_$nbr_errf>  .\n";
                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ERRF_$nbr_errf> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Erratum_fors> .\n";
			while (($key_errf, $value_errf) = each(%$_)){
				if($key_errf =~ /^refSource$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ERRF_$nbr_errf> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_errf.'"'." .\n";
				}
				elsif($key_errf =~ /^PMID$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ERRF_$nbr_errf> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_errf."> .\n";
				}
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ERRF_$nbr_errf> <http://bio2rdf.org/pubmed_vocabulary:other_erratum_fors>".'"'.$key_errf.":".$value_errf.'"'." .\n";
				}
			}
			$nbr_errf++;
		}
	}

	$erri = $citation->erratum_ins;
	if(@$erri > 0){
		foreach(@$erri){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:erratum_ins> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."ERRI_$nbr_erri> .\n";
                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ERRI_$nbr_erri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Erratum_ins> .\n";
			while (($key_erri, $value_erri) = each(%$_)){
				if($key_erri =~ /^refSource$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ERRI_$nbr_erri> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_erri.'"'." .\n";
				}
				elsif($key_erri =~ /^PMID$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ERRI_$nbr_erri> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_erri."> .\n";
				}
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ERRI_$nbr_erri> <http://bio2rdf.org/pubmed_vocabulary:other_erratum_ins>".'"'.$key_erri.":".$value_erri.'"'." .\n";
				}
			}
			$nbr_erri++;
		}
	}

	$gn = $citation->general_notes;
	if(@$gn > 0){
		foreach(@$gn){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:general_notes> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."GENO_$nbr_gn> .\n";
                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."GENO_$nbr_gn> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:General_notes> .\n";
			while (($key_gn, $value_gn) = each(%$_)){
				$value_gn =~ s/"/&quot;/g;
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."GENO_$nbr_gn> <http://bio2rdf.org/pubmed_vocabulary:other_general_notes>".'"'.$key_gn.":".$value_gn.'"'." .\n";
			}
			$nbr_gn++;
		}
	}

	$orri = $citation->original_report_ins;
	if(@$orri > 0){
		foreach(@$orri){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:original_report_ins> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."ORRI_$nbr_orri> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ORRI_$nbr_orri> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Original_report_ins> .\n";
			while (($key_orri, $value_orri) = each(%$_)){
				if($key_orri =~ /^refSource$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ORRI_$nbr_orri> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_orri.'"'." .\n";
				}
				elsif($key_orri =~ /^PMID$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ORRI_$nbr_orri> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_orri."> .\n";
				}
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."ORRI_$nbr_orri> <http://bio2rdf.org/pubmed_vocabulary:other_original_report_ins>".'"'.$key_orri.":".$value_orri.'"'." .\n";
				}
			}
			$nbr_orri++;
		}
	}

	$otha = $citation->other_abstracts;
	if(@$otha > 0){
		foreach(@$otha){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:other_abstracts> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHA_$nbr_otha> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHA_$nbr_otha> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Other_abstracts> .\n";
			while (($key_otha, $value_otha) = each(%$_)){
				if($key_otha =~ /^abstractText$/){
					$value_otha =~ s/"/&quot;/g;
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHA_$nbr_otha> <http://bio2rdf.org/pubmed_vocabulary:abstractText> ".'"'.$value_otha.'"'." .\n";
				}
				elsif($key_otha =~ /^type$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHA_$nbr_otha> <http://bio2rdf.org/pubmed_vocabulary:abstractType> ".'"'.$value_otha.'"'." .\n";
				}
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHA_$nbr_otha> <http://bio2rdf.org/pubmed_vocabulary:other_abstract_info>".'"'.$key_otha.":".$value_otha.'"'." .\n";
				}
			}
			$nbr_otha++;
		}
	}

	$othi = $citation->other_ids;
	if(@$othi > 0){
		foreach(@$othi){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:other_ids> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHI_$nbr_othi> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHI_$nbr_othi> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Other_ids> .\n";
			while (($key_othi, $value_othi) = each(%$_)){
				if($key_othi =~ /^source$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHI_$nbr_othi> <http://bio2rdf.org/pubmed_vocabulary:source> ".'"'.$value_othi.'"'." .\n";
				}
				elsif($key_othi =~ /^otherID$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHI_$nbr_othi> <http://bio2rdf.org/pubmed_vocabulary:otherID> ".'"'.$value_othi.'"'." .\n";
				}
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHI_$nbr_othi> <http://bio2rdf.org/pubmed_vocabulary:other_ids_info>".'"'.$key_othi.":".$value_othi.'"'." .\n";
				}
				if($value_othi =~ /^PMC\d+?$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."OTHI_$nbr_othi> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/ncbi_pmc:$value_othi> .\n";}
			}
			$nbr_othi++;
		}
	}

	$repf = $citation->republished_froms;
	if(@$repf > 0){
		foreach(@$repf){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:republished_froms> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPF_$nbr_repf> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPF_$nbr_repf> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Republished_froms> .\n";
			while (($key_repf, $value_repf) = each(%$_)){
				if($key_repf =~ /^refSource$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPF_$nbr_repf> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_repf.'"'." .\n";
                                }
                                elsif($key_repf =~ /^PMID$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPF_$nbr_repf> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_repf."> .\n";
                                }
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPF_$nbr_repf> <http://bio2rdf.org/pubmed_vocabulary:other_republished_froms>".'"'.$key_repf.":".$value_repf.'"'." .\n";
				}
			}
			$nbr_repf++;
		}
	}

	$repi = $citation->republished_ins;
	if(@$repi > 0){
		foreach(@$repi){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:republished_ins> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPI_$nbr_repi> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPI_$nbr_repi> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Republished_ins> .\n";
			while (($key_repi, $value_repi) = each(%$_)){
				if($key_repi =~ /^refSource$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPI_$nbr_repi> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_repi.'"'." .\n";
                                }
                                elsif($key_repi =~ /^PMID$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPI_$nbr_repi> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_repi."> .\n";
                                }
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."REPI_$nbr_repi> <http://bio2rdf.org/pubmed_vocabulary:other_republished_ins>".'"'.$key_repi.":".$value_repi.'"'." .\n";
				}
			}
			$nbr_repi++;
		}
	}

	$reti = $citation->retraction_ins;
	if(@$reti > 0){
		foreach(@$reti){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:retraction_ins> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETI_$nbr_reti> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETI_$nbr_reti> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Retraction_ins> .\n";
			while (($key_reti, $value_reti) = each(%$_)){
				if($key_reti =~ /^refSource$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETI_$nbr_reti> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_reti.'"'." .\n";
                                }
                                elsif($key_reti =~ /^PMID$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETI_$nbr_reti> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_reti."> .\n";
                                }
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETI_$nbr_reti> <http://bio2rdf.org/pubmed_vocabulary:other_retraction_ins>".'"'.$key_reti.":".$value_reti.'"'." .\n";
				}
			}
			$nbr_reti++;
		}
	}

	$reto = $citation->retraction_ofs;
	if(@$reto > 0){
		foreach(@$reto){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:retraction_ofs> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETO_$nbr_reto> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETO_$nbr_reto> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Retraction_ofs> .\n";
			while (($key_reto, $value_reto) = each(%$_)){
				if($key_reto =~ /^refSource$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETO_$nbr_reto> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_reto.'"'." .\n";
                                }
                                elsif($key_reto =~ /^PMID$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETO_$nbr_reto> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_reto."> .\n";
                                }
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."RETO_$nbr_reto> <http://bio2rdf.org/pubmed_vocabulary:other_retraction_ofs>".'"'.$key_reto.":".$value_reto.'"'." .\n";
				}
			}
			$nbr_reto++;
		}
	}

	$updi = $citation->update_ins;
	if(@$updi > 0){
		foreach(@$updi){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:update_ins> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDI_$nbr_updi> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDI_$nbr_updi> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Update_ins> .\n";
			while (($key_updi, $value_updi) = each(%$_)){
				if($key_updi =~ /^refSource$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDI_$nbr_updi> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_updi.'"'." .\n";
                                }
                                elsif($key_updi =~ /^PMID$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDI_$nbr_updi> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_updi."> .\n";
                                }
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDI_$nbr_updi> <http://bio2rdf.org/pubmed_vocabulary:other_update_ins>".'"'.$key_updi.":".$value_updi.'"'." .\n";
				}
			}
			$nbr_updi++;
		}
	}

	$updo = $citation->update_ofs;
	if(@$updo > 0){
		foreach(@$updo){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:update_ofs> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDO_$nbr_updo> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDO_$nbr_updo> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Update_ofs> .\n";
			while (($key_updo, $value_updo) = each(%$_)){
				if($key_updo =~ /^refSource$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDO_$nbr_updo> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_updo.'"'." .\n";
                                }
                                elsif($key_updo =~ /^PMID$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDO_$nbr_updo> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_updo."> .\n";
                                }
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."UPDO_$nbr_updo> <http://bio2rdf.org/pubmed_vocabulary:other_update_ofs>".'"'.$key_updo.":".$value_updo.'"'." .\n";
				}
			}
			$nbr_updo++;
		}
	}

	$sfpi = $citation->summary_for_patients_ins;
	if(@$sfpi > 0){
		foreach(@$sfpi){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:summary_for_patients_ins> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."SFPI_$nbr_sfpi> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."SFPI_$nbr_sfpi> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Summary_for_patients_ins> .\n";
			while (($key_sfpi, $value_sfpi) = each(%$_)){
				if($key_sfpi =~ /^refSource$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."SFPI_$nbr_sfpi> <http://bio2rdf.org/pubmed_vocabulary:refSource> ".'"'.$value_sfpi.'"'." .\n";
                                }
                                elsif($key_sfpi =~ /^PMID$/){
                                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."SFPI_$nbr_sfpi> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubmed:".$value_sfpi."> .\n";
                                }
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."SFPI_$nbr_sfpi> <http://bio2rdf.org/pubmed_vocabulary:other_summary_for_patients_info>".'"'.$key_sfpi.":".$value_sfpi.'"'." .\n";
				}
			}
			$nbr_sfpi++;
		}
	}

	$xref = $citation->cross_references;
	if(@$xref > 0){
		foreach(@$xref){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:cross_references> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Cross_references> .\n";
			if($_->database !~ /^$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:database> ".'"'.$_->database.'"'." .\n";
			}
			if($_->primary_id !~ /^$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:primary_id> ".'"'.$_->primary_id.'"'." .\n";
			}
			if($_->optional_id !~ /^$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:optional_id> ".'"'.$_->optional_id.'"'." .\n";
			}
			if($_->version !~ /^$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:version> ".'"'.$_->version.'"'." .\n";
			}
			if($_->url !~ /^$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:url> ".'"'.$_->url.'"'." .\n";
			}
			if($_->authority !~ /^$/){
				$authority = $_->authority;
				$authority =~ s/"/&quot;/g;
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:authority> ".'"'.$authority.'"'." .\n";
			}
			if($_->comment !~ /^$/){
				$comment = $_->comment;
				$comment =~ s/"/&quot;/g;
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:comment> ".'"'.$comment.'"'." .\n";
			}
			if($_->database =~ /^GENBANK$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/ncbi:".$_->primary_id."> .\n";
                        }
			if($_->database =~ /^PDB$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pdb:".$_->primary_id."> .\n";
                        }
			if($_->database =~ /^OMIM$/){
				$OMIM = $_->primary_id;
				if($_->primary_id =~ /^MIM(\d+?)$/){$OMIM = $1;}
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/omim:".$OMIM."> .\n";
                        }
			if($_->database =~ /^RefSeq$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/ncbi:".$_->primary_id."> .\n";
                        }
			elsif($_->database =~ /^PIR$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pir:".$_->primary_id."> .\n";
                        }
			elsif($_->database =~ /^SWISSPROT$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/uniprot:".$_->primary_id."> .\n";
                        }
			elsif($_->database =~ /^ClinicalTrials.gov$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/clinicaltrials:".$_->primary_id."> .\n";
                        }
			elsif($_->database =~ /^GEO$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/ncbi_geo:".$_->primary_id."> .\n";
                        }
			elsif($_->database =~ /^ISRCTN$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/isrctn:".$_->primary_id."> .\n";
                        }
			elsif($_->database =~ /^GDB$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/gdb:".$_->primary_id."> .\n";
                        }
			elsif($_->database =~ /^PubChem-Substance$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubchem_sid:".$_->primary_id."> .\n";
                        }
			elsif($_->database =~ /^PubChem-Compound$/){
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/pubchem_cid:".$_->primary_id."> .\n";
                        }
			else{
                                print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."XREF_$nbr_xref> <http://bio2rdf.org/pubmed_vocabulary:xRef> <http://bio2rdf.org/".lc($_->database).":".$_->primary_id."> .\n";
                        }
			$nbr_xref++;
		}
	}

	$grants = $citation->grants;
	if(@$grants > 0){
		foreach(@$grants){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:grants> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."GRAN_$nbr_grants> .\n";
                        print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."GRAN_$nbr_grants> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Grants> .\n";
			while (($key_grants, $value_grants) = each(%$_)){
				if($key_grants =~ /^country$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."GRAN_$nbr_grants> <http://bio2rdf.org/pubmed_vocabulary:grant_country> ".'"'.$value_grants.'"'." .\n";
				}
				elsif($key_grants =~ /^grantID$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."GRAN_$nbr_grants> <http://bio2rdf.org/pubmed_vocabulary:grantID> ".'"'.$value_grants.'"'." .\n";
				}
				elsif($key_grants =~ /^acronym$/){
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."GRAN_$nbr_grants> <http://bio2rdf.org/pubmed_vocabulary:grant_acronym> ".'"'.$value_grants.'"'." .\n";
				}
				elsif($key_grants =~ /^agency$/){
					$value_grants =~ s/"/&quot;/g;
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."GRAN_$nbr_grants> <http://bio2rdf.org/pubmed_vocabulary:grant_agency> ".'"'.$value_grants.'"'." .\n";
				}
				else{
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."GRAN_$nbr_grants> <http://bio2rdf.org/pubmed_vocabulary:other_grant_info>".'"'.$key_grants.":".$value_grants.'"'." .\n";
				}
			}
			$nbr_grants++;
		}
	}

	$mesh = $citation->mesh_headings;
	if(@$mesh > 0){
		foreach(@$mesh){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:meshref> <http://bio2rdf.org/pubmed_resource:$pmid"."_MESH_$nbr_mesh> .\n";
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_MESH_$nbr_mesh> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:MeSH> .\n";
			while (($key_mesh_headings, $value_mesh_headings) = each(%$_)){
				if($key_mesh_headings eq 'subHeadings'){
					foreach $sub (@$value_mesh_headings){
						$qualifier = $sub->{subHeading};
						$qualifier =~ tr/ /_/;
						if($sub->{majorTopic} =~ /Y/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_MESH_$nbr_mesh> <http://bio2rdf.org/pubmed_vocabulary:major_qualifier> <http://bio2rdf.org/mesh:$qualifier> .\n";}
						else{print "<http://bio2rdf.org/pubmed_resource:$pmid"."_MESH_$nbr_mesh> <http://bio2rdf.org/pubmed_vocabulary:qualifier> <http://bio2rdf.org/mesh:$qualifier> .\n";}
					}
				}
				else{
					$descriptor = $value_mesh_headings;
					$descriptor =~ tr/ /_/;
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_MESH_$nbr_mesh> <http://bio2rdf.org/pubmed_vocabulary:descriptor> <http://bio2rdf.org/mesh:$descriptor> .\n";
				}
               	        }
			$nbr_mesh++;
		}
	}

# An publisher can be a Person or an Organization

	$publisher = $citation->publisher;
	if(keys%$publisher > 0){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/publisher> <http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> .\n";
		if($_->type =~ /^PersonalName$/){
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .\n";
			if($_->firstname !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://xmlns.com/foaf/0.1/firstName> ".'"'.$_->firstname.'"'." .\n";}
			if($_->forename !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://xmlns.com/foaf/0.1/firstName> ".'"'.$_->forename.'"'." .\n";}
			if($_->lastname !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://xmlns.com/foaf/0.1/lastName> ".'"'.$_->lastname.'"'." .\n";}
			if($_->email !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://xmlns.com/foaf/0.1/mbox> ".'"'.$_->email.'"'." .\n";}
			if($_->middlename !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://bio2rdf.org/pubmed_resource:middlename> ".'"'.$_->middlename.'"'." .\n";}
			if($_->initials !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://bio2rdf.org/pubmed_vocabulary:initials> ".'"'.$_->initials.'"'." .\n";}
			if($_->affiliation !~ /^$/){
				$affiliation = $_->affiliation;
				$affiliation =~ s/"/&quot;/g;
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://bio2rdf.org/pubmed_vocabulary:affiliation> ".'"'.$affiliation.'"'." .\n";
			}
			if($_->postal_address !~ /^$/){
				$postal_address = $_->postal_address;
				$postal_address =~ s/"/&quot;/g;
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://bio2rdf.org/pubmed_vocabulary:postal_address> ".'"'.$postal_address.'"'." .\n";
			}
			if($_->suffix !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://bio2rdf.org/pubmed_vocabulary:suffix> ".'"'.$_->suffix.'"'." .\n";}
			}
		else{
			print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Organization> .\n";
			if($_->name !~ /^$/){
				$name = $_->name;
				$name =~ s/"/&quot;/g;
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_PUBLISHER> <http://xmlns.com/foaf/0.1/name> ".'"'.$name.'"'." .\n";
			}
		}
	}

	$date = $citation->date;
	if($date !~ /^$/){
		($year,$month,$day) = split(/-/,$date);
		if($month =~ /Jan/){$month = "01";}
		elsif($month =~ /Feb/){$month = "02";}
		elsif($month =~ /Mar/){$month = "03";}
		elsif($month =~ /Apr/){$month = "04";}
		elsif($month =~ /May/){$month = "05";}
		elsif($month =~ /Jun/){$month = "06";}
		elsif($month =~ /Jul/){$month = "07";}
		elsif($month =~ /Aug/){$month = "08";}
		elsif($month =~ /Sep/){$month = "09";}
		elsif($month =~ /Oct/){$month = "10";}
		elsif($month =~ /Nov/){$month = "11";}
		elsif($month =~ /Dec/){$month = "12";}

		if($month =~ /^$/){print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/date> ".'"'."$year".'"'. "^^xsd:gYear .\n";}
		elsif($day =~ /^$/){print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/date> ".'"'."$year-$month".'"'. "^^xsd:gYearMonth .\n";}
		else{print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/date> ".'"'."$year-$month-$day".'"'. "^^xsd:date .\n";}
	}

	$medline_date = $citation->medline_date;
	if($medline_date !~ /^$/){
		$count = 0; $count++ while $medline_date =~ /-/g;
		if($count == 2){print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:medline_date> ".'"'."$medline_date".'"'. "^^xsd:date <http://bio2rdf.org/pubmed_record:$pmid> .\n";}
		elsif($count == 1){print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:medline_date> ".'"'."$medline_date".'"'. "^^xsd:gYearMonth .\n";}
		else{print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:medline_date> ".'"'."$medline_date".'"'. "^^xsd:gYear .\n";}
	}

	$last_modified_date = $citation->last_modified_date;
	if($last_modified_date !~ /^$/){
		$count = 0; $count++ while $last_modified_date =~ /-/g;
		if($count == 2){print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/modified> ".'"'."$last_modified_date".'"'. "^^xsd:date .\n";}
		elsif($count == 1){print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/modified> ".'"'."$last_modified_date".'"'. "^^xsd:gYearMonth .\n";}
		else{print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/modified> ".'"'."$last_modified_date".'"'. "^^xsd:gYear .\n";}
	}

	$date_created = $citation->date_created;
	if($date_created !~ /^$/){
		$count = 0; $count++ while $date_created =~ /-/g;
		if($count == 2){print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/created> ".'"'."$date_created".'"'. "^^xsd:date .\n";}
		elsif($count == 1){print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/created> ".'"'."$date_created".'"'. "^^xsd:gYearMonth .\n";}
		else{print "<http://bio2rdf.org/pubmed:$pmid> <http://purl.org/dc/terms/created> ".'"'."$date_created".'"'. "^^xsd:gYear .\n";}
	}

# Journal information are to be linked to the journal list provided by NCBI at ftp://ftp.ncbi.nih.gov/pubmed through the nlm_unique_id
	$journal = $citation->journal;
	if(keys%$journal > 0){
		print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:journal_issue> <http://bio2rdf.org/pubmed_resource:".$pmid."_JOURNAL> .\n";
		print "<http://bio2rdf.org/pubmed_resource:$pmid"."_JOURNAL> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://bio2rdf.org/pubmed_vocabulary:Journal> .\n";
		if($citation->issue !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_JOURNAL> <http://bio2rdf.org/pubmed_vocabulary:issue> ".'"'.$citation->issue.'"'." .\n";}
		if($citation->issue_supplement !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_JOURNAL> <http://bio2rdf.org/pubmed_vocabulary:issue_supplement> ".'"'.$citation->issue_supplement.'"'." .\n";}
		if($citation->volume !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_JOURNAL> <http://bio2rdf.org/pubmed_vocabulary:volume> ".'"'.$citation->volume.'"'." .\n";}
		print "<http://bio2rdf.org/pubmed_resource:".$pmid."_JOURNAL> <http://bio2rdf.org/pubmed_vocabulary:journal> <http://bio2rdf.org/ncbi_journal:".$journal->nlm_unique_id."> .\n";
		if($journal->country !~ /^$/){print "<http://bio2rdf.org/ncbi_journal:".$journal->nlm_unique_id."> <http://bio2rdf.org/ncbi_journal_vocabulary:country> ".'"'.$journal->country.'"'." > .\n";}
	}

# An author can be a Person or an Organization
	$authors = $citation->authors;
	if(@$authors > 0){
		foreach (@$authors){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:author> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> .\n";
			if($_->type =~ /^PersonalName$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .\n";
				if($_->firstname !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://xmlns.com/foaf/0.1/firstName> ".'"'.$_->firstname.'"'." .\n";}
				if($_->forename !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://xmlns.com/foaf/0.1/firstName> ".'"'.$_->forename.'"'." .\n";}
				if($_->lastname !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://xmlns.com/foaf/0.1/lastName> ".'"'.$_->lastname.'"'." <http://bio2rdf.org/pubmed_record:$pmid> .\n";}
				if($_->email !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://xmlns.com/foaf/0.1/mbox> ".'"'.$_->email.'"'." .\n";}
				if($_->middlename !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:middlename> ".'"'.$_->middlename.'"'." .\n";}
				if($_->initials !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:initials> ".'"'.$_->initials.'"'." .\n";}
				if($_->affiliation !~ /^$/){
					$affiliation = $_->affiliation;
					$affiliation =~ s/"/&quot;/g;
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:affiliation> ".'"'.$affiliation.'"'." .\n";
				}
				if($_->postal_address !~ /^$/){
					$postal_address = $_->postal_address;
					$postal_address =~ s/"/&quot;/g;
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:postal_address> ".'"'.$postal_address.'"'." .\n";
				}
				if($_->suffix !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:suffix> ".'"'.$_->suffix.'"'." .\n";}
			}
			else{
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Organization> .\n";
				if($_->name !~ /^$/){
					$name = $_->name;
					$name =~ s/"/&quot;/g;
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."AUTH_$nbr_authors> <http://xmlns.com/foaf/0.1/name> ".'"'.$name.'"'." .\n";
				}
			}
			$nbr_authors++;
		} 
	}

# A contributor is into the same kind of object than an author
	$contributors = $citation->contributors;
	if(@$contributors > 0){
		foreach (@$contributors){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:contributor> <http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_contributors> .\n";
			if($_->type =~ /^PersonalName$/){
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Person> .\n";
				if($_->firstname !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://xmlns.com/foaf/0.1/firstName> ".'"'.$_->firstname.'"'." .\n";}
				if($_->forename !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://xmlns.com/foaf/0.1/firstName> ".'"'.$_->forename.'"'." .\n";}
				if($_->lastname !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://xmlns.com/foaf/0.1/lastName> ".'"'.$_->lastname.'"'." .\n";}
				if($_->email !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://xmlns.com/foaf/0.1/mbox> ".'"'.$_->email.'"'." .\n";}
				if($_->middlename !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:middlename> ".'"'.$_->middlename.'"'." .\n";}
				if($_->initials !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:initials> ".'"'.$_->initials.'"'." .\n";}
				if($_->affiliation !~ /^$/){
					$affiliation = $_->affiliation;
					$affiliation =~ s/"/&quot;/g;
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:affiliation> ".'"'.$affiliation.'"'." .\n";
				}
				if($_->postal_address !~ /^$/){
					$postal_address = $_->postal_address;
					$postal_address =~ s/"/&quot;/g;
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:postal_address> ".'"'.$postal_address.'"'." .\n";
				}
				if($_->suffix !~ /^$/){print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://bio2rdf.org/pubmed_vocabulary:suffix> ".'"'.$_->suffix.'"'." .\n";}
			}
			else{
				print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Organization> .\n";
				if($_->name !~ /^$/){
					$name = $_->name;
					$name =~ s/"//g;
					print "<http://bio2rdf.org/pubmed_resource:$pmid"."_"."CONT_$nbr_authors> <http://xmlns.com/foaf/0.1/name> ".'"'.$name.'"'." .\n";
				}
			}
			$nbr_contributors++;
		} 
	}

	$keywords = $citation->keywords;
	if(keys%$keywords > 0){
		while (($key_keywords, $value_keywords) = each(%$keywords)){
			print "<http://bio2rdf.org/pubmed:$pmid> <http://bio2rdf.org/pubmed_vocabulary:keyword> ".'"'.$key_keywords.'"'." .\n";
		}
	}
}
