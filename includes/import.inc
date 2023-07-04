<?php
function utf2latin($text) {
	$text=htmlentities($text, ENT_COMPAT, 'UTF-8');
	return html_entity_decode($text, ENT_COMPAT, 'ISO-8859-1');
}

function getMedLine($accession) {
	$url = "http://www.hubmed.org/export/mods.cgi?uids=$accession";
	$sequence = '';
	if ($medlineFile = fopen($url, "r")) {
		$biblio=array();
		$flag_path=0;
		$flag_authors=0;
		$last='';
		$first='';
		while (!feof($medlineFile)) {
			$buffer = fgets($medlineFile, 4096);
			if (empty($biblio['title']) && preg_match('/<title>(.*)<\/title>/', $buffer, $matches)) {
				$biblio['title'] = utf2latin($matches[1]);
			} elseif (strpos($buffer, 'name type="personal">')) {
				$flag_authors++;
			}
			elseif (($flag_authors == 1) && preg_match('/<namePart type="given">(\w)<\/namePart><namePart type="given">(\w)<\/namePart>/', $buffer, $matches)) {
				$first=$matches[1].$matches[2];
			}
			elseif (($flag_authors == 1) && preg_match('/<namePart type="given">(\w)<\/namePart>/', $buffer, $matches)) {
				$first=$matches[1];
			}
			elseif (($flag_authors == 1) && preg_match('/<namePart type="family">(.*)<\/namePart>/', $buffer, $matches)) {
				$last=$matches[1];
			}
			elseif (($flag_authors == 1) && strpos($buffer, '/name>')) {
				$biblio['authors'] = utf2latin((empty($biblio['authors']) ? '': $biblio['authors'] . ', ') .  $last . ' ' . $first);
				$last = $first = '';
				$flag_authors--;
			}
			elseif (preg_match('/<identifier type="doi">(.*)<\/identifier>/', $buffer, $matches)) {
				$biblio['doi'] = $matches[1];
			}
			elseif (preg_match('/<identifier type="pmid">(.*)<\/identifier>/', $buffer, $matches)) {
				$biblio['pmid'] = $matches[1];
			}
			elseif (strpos($buffer, 'relatedItem type="host">')) {
				$flag_path++;
			}
			elseif (($flag_path == 1) && preg_match('/<title>(.*)<\/title>/', $buffer, $matches)) {
				$biblio['journal'] = $matches[1];
				$flag_path--;
			}
			elseif (preg_match('/<dateIssued>(\d+)/', $buffer, $matches)) {
				$biblio['year'] = $matches[1];
			}
			elseif (preg_match('/<detail type="volume"><number>(.*)<\/number><\/detail>/', $buffer, $matches)) {
				$biblio['volume'] = $matches[1];
			}
			elseif (preg_match('/<detail type="number"><number>(.*)<\/number><\/detail>/', $buffer, $matches)) {
				$biblio['issue'] = $matches[1];
			}
			elseif (preg_match('/<start>(.*)<\/start>/', $buffer, $matches)) {
				$biblio['start'] = $matches[1];
			}
			elseif (preg_match('/<end>(.*)<\/end>/', $buffer, $matches)) {
				$biblio['end'] = $matches[1];
			}
		}
		if (
			!empty($biblio['title'])
			&& !empty($biblio['authors'])
			&& !empty($biblio['year'])
			&& !empty($biblio['journal'])
			&& !empty($biblio['volume'])
			&& !empty($biblio['start'])
			&& (!empty($biblio['pmid'])
				|| !empty($biblio['doi']))
		) {
			return $biblio;
		}
	}
	return;
}

function translate_DNA_to_protein($seq, $genetic_code) {
	// $aminoacids is the array of aminoacids
	$aminoacids=array("F", "L", "I", "M", "V", "S", "P", "T", "A", "Y", "*", "H", "Q", "N", "K", "D", "E", "C", "W", "R", "G", "X");
	// $triplets is the array containning the genetic codes
	// Info has been extracted from http://www.ncbi.nlm.nih.gov/Taxonomy/Utils/wprintgc.cgi?mode
	// Standard genetic code
	$triplets[1]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG |TGA )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Vertebrate Mitochondrial
	$triplets[2]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG |AGA |AGG )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG |TGA )", "(CG. )", "(GG. )", "(\S\S\S )");
	// Yeast Mitochondrial
	$triplets[3]=array("(TTT |TTC )", "(TTA |TTG )", "(ATT |ATC )", "(ATG |ATA )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. |CT. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG |TGA )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Mold, Protozoan and Coelenterate Mitochondrial. Mycoplasma, Spiroplasma
	$triplets[4]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG |TGA )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Invertebrate Mitochondrial
	$triplets[5]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC )", "(ATG |ATA )", "(GT. )", "(TC. |AG. )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG |TGA )", "(CG. )", "(GG. )", "(\S\S\S )");
	// Ciliate Nuclear; Dasycladacean Nuclear; Hexamita Nuclear
	$triplets[6]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TGA )", "(CAT |CAC )",
		"(CAA |CAG |TAA |TAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Echinoderm Mitochondrial
	$triplets[9]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AG. )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAA |AAT |AAC )", "(AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG |TGA )", "(CG. )", "(GG. )", "(\S\S\S )");
	// Euplotid Nuclear
	$triplets[10]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC |TGA )",
		"(TGG )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Bacterial and Plant Plastid
	$triplets[11]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG |TGA )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Alternative Yeast Nuclear
	$triplets[12]=array("(TTT |TTC )", "(TTA |TTG |CTA |CTT |CTC )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC |CTG )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG |TGA )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Ascidian Mitochondrial
	$triplets[13]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC )", "(ATG |ATA )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG |TGA )", "(CG. )", "(GG. |AGA |AGG )", "(\S\S\S )");
	// Flatworm Mitochondrial
	$triplets[14]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AG. )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC |TAA )", "(TAG )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC |AAA )", "(AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG |TGA )", "(CG. )", "(GG. )", "(\S\S\S )");
	// Blepharisma Macronuclear
	$triplets[15]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TGA )", "(CAT |CAC )",
		"(CAA |CAG |TAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Chlorophycean Mitochondrial
	$triplets[16]=array("(TTT |TTC )", "(TTA |TTG |CT. |TAG )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TGA )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Trematode Mitochondrial
	$triplets[21]=array("(TTT |TTC )", "(TTA |TTG |CT. )", "(ATT |ATC )", "(ATG |ATA )", "(GT. )", "(TC. |AG. )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TAG )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC |AAA )", "(AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG |TGA )", "(CG. )", "(GG. )", "(\S\S\S )");
	// Scenedesmus obliquus mitochondrial
	$triplets[22]=array("(TTT |TTC )", "(TTA |TTG |CT. |TAG )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TCT |TCC |TCG |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TAA |TGA |TCA )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// Thraustochytrium mitochondrial code
	$triplets[23]=array("(TTT |TTC )", "(TTG |CT. )", "(ATT |ATC |ATA )", "(ATG )", "(GT. )", "(TC. |AGT |AGC )",
		"(CC. )", "(AC. )", "(GC. )", "(TAT |TAC )", "(TTA |TAA |TAG |TGA )", "(CAT |CAC )",
		"(CAA |CAG )", "(AAT |AAC )", "(AAA |AAG )", "(GAT |GAC )", "(GAA |GAG )", "(TGT |TGC )",
		"(TGG )", "(CG. |AGA |AGG )", "(GG. )", "(\S\S\S )");
	// place a space after each triplete in the sequence
	$temp = chunk_split($seq, 3, ' ');
	// replace triplets by corresponding amnoacid
	$peptide = preg_replace($triplets[$genetic_code], $aminoacids, $temp);
	return $peptide;
}

function getSequence($accession, $start=1, $end=30000, $strand=false) {
	if (!empty($accession)) {
		$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=nucleotide&id=$accession".($strand?'&strand=2':'').(!empty($start)?'&seq_start='.intval($start):'').(!empty($end)?'&seq_stop='.intval($end):'')."&rettype=gb";
		if ($seqfile = file_get_contents($url)) {
			$ret=array('start'=>(!empty($start)?intval($start):1), 'strand'=>($strand?1:-1), 'translation'=>1);
			foreach (explode("\n", $seqfile) as $line) {
				if (preg_match('/^LOCUS /', $line)) {
					$ret['molecule'] = trim(substr($line, 44, 7));
					$ret['circular'] = (((substr($line, 55, 8)) == 'circular') ? 'circular' : 'linear');
				} elseif (preg_match('/^VERSION     ([\d\w\_]+).\d+/', $line, $matches)) {
					$ret['reference'] = strtoupper($matches[1]);
				} elseif (preg_match('/^ {21}/', $line)) {
					if (preg_match('/\/organelle\=\"(.*)\"$/', $line, $matches)) { $ret['organelle']   = $matches[1]; }
					if (preg_match('/\/transl_table\=(\d+)$/', $line, $matches)) { $ret['translation'] = $matches[1]; }
				}
			}
			$seqfile=preg_replace('/\n/m', ' ', $seqfile);
			if (preg_match('/\sORIGIN\s(.*)$/m', $seqfile, $matches)) {
				$ret['sequence']=strtoupper(preg_replace('/[^A-Za-z]/', '', $matches[1]));
				$ret['end']=$ret['start']+strlen($ret['sequence'])-1;
				$ret['size']=strlen($ret['sequence']);
				$ret['coord']=($strand?'complement(':'').$ret['start'].'..'.$ret['end'].($strand?')':'');
				return $ret;
			}
		}
	}
}

function get_taxon($taxonid) {
	if (!empty($taxonid)) {
		$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=taxonomy&id=$taxonid&retmode=xml";
		if ($taxonfile = file_get_contents($url)) {
			if ((($nodes = readXML($taxonfile)) !== false) && isset($nodes['children'][0]['children'][0])) {
				foreach ($nodes['children'][0]['children'] as $ref) {
					switch ($ref['name']) {
					case 'TAXID':
						$ret['taxonid'] = $ref['cdata'];
						break;
					case 'SCIENTIFICNAME':
						$ret['scientificname'] = $ref['cdata'];
						break;
					case 'RANK':
						$ret['rank'] = $ref['cdata'];
						break;
					case 'DIVISION':
						$ret['division'] = $ref['cdata'];
						break;
					case 'ACRONYM':
						$ret['acronym'] = $ref['cdata'];
						break;
					case 'OTHERNAMES':
						foreach ($ref['children'] as $value) {
							if ($value['name'] == 'GENBANKCOMMONNAME') $ret['commonname'] = $value['cdata'];
						}
						break;
					case 'LINEAGEEX':
						foreach ($ref['children'] as $value) {
							foreach ($value['children'] as $value2) {
								if ($value2['name'] == 'SCIENTIFICNAME') $tmp = $value2['cdata'];
								if ($value2['name'] == 'TAXID') $tmp2 = $value2['cdata'];
								if ($value2['name'] == 'RANK') $tmp3 = $value2['cdata'];
								if (!empty($tmp) && !empty($tmp2) && !empty($tmp3)) {
									$ret['taxon'][] = $tmp . (($tmp3 != 'no rank') ? '[' . $tmp3 . ']' : '');
									if ($tmp3 == 'species') { $ret['specie']=$tmp2; }
									if ($tmp3 == 'genus') { $ret['genus']=$tmp; }
									unset($tmp, $tmp2, $tmp3);
								}
							}
						}
						break;
					}
				}
				$ret['taxon']=implode('; ', $ret['taxon']);
				if (!empty($ret['specie'])) {
					$ret2=get_taxon($ret['specie']);
					$ret2['variant']=$ret['scientificname'];
					return $ret2;
				} else {
					return $ret;
				}
			}
		}
	}
}

function find_taxon($name) {
	if (!empty($name)) {
		$name=urlencode($name);
		$url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=taxonomy&term=$name&retmode=xml";
		if ($taxonfile = file_get_contents($url)) {
			if ( preg_match_all('/<Id>(\d+)<\/Id>/m', $taxonfile, $matches)) {
				return $matches[1];
			}
		}
	}
}

function readXML($xml) {
	$xml_parser = xml_parser_create();
	$my_parser = new XMLParser();
	xml_set_object($xml_parser, $my_parser);
	xml_set_element_handler($xml_parser, "startTag", "endTag");
	xml_set_character_data_handler($xml_parser, "cdata");
	$data = xml_parse($xml_parser, $xml);
	if (!$data) {
		return false;
	}
	xml_parser_free($xml_parser);
	return $my_parser->stack[0];
}
?>