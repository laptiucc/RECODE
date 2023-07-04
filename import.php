<?php
require_once 'includes/main.inc';

function crc24($data) {
	$crc = 0xb704ce;
	$len=strlen($data);
	for ($i = 0; $i < strlen($data); $i++) {
		$crc ^= ord($data[$i]) << 16;
		for ($j = 0; $j < 8; $j++) {
			$crc <<= 1;
			if ($crc & 0x1000000) $crc ^= 0x1864cfb;
		}
	}
	return $crc & 0xffffff;
}

function read_date($childNodes) {
	if ($childNodes) {
		$dater=array('day'=>0, 'month'=>0, 'year'=>date('Y'));
		foreach ($childNodes as $node) {
			$dater[$node->nodeName]=$node->nodeValue;
		}
//		return gmdate('Y'.(!empty($dater['month'])?'-m'.(!empty($dater['day'])?'-d':''):''), mktime(0, 0, 0, (!empty($dater['month'])?$dater['month']:1), (!empty($dater['day'])?$dater['day']:1), $dater['year']));
		return gmdate('Y-m-d', mktime(0, 0, 0, (!empty($dater['month'])?$dater['month']:1), (!empty($dater['day'])?$dater['day']:1), $dater['year']));
	}
}

function import($dom) {
	global $sql, $config;
	$identity=$dom->getElementsByTagName('identity');
	foreach ($identity->item(0)->childNodes as $node) {
		switch ($node->nodeName) {
		case 'name':
			if ( $node->hasAttributes() ) {
				foreach ($node->attributes as $attrNode) {
					$name['comment']=$attrNode->value;
				}
			}
			$name['name']=$node->nodeValue;
			break;
		case 'description':
			$description=$node->nodeValue;
			break;
		case 'taxonomy':
			foreach ($node->childNodes as $subnode) {
				if ($subnode->nodeType==1) $taxonomy[$subnode->nodeName]=$subnode->nodeValue;
			}
			if ( $node->hasAttributes() ) {
				foreach ($node->attributes as $attrName => $attrNode) {
					$taxonomy[$attrName]=$attrNode->value;
				}
			}
			break;
		case 'database-id':
			foreach ($node->attributes as $attrNode) {
				$data=$attrNode->value;
			}
			$databaseid[$data]=$node->nodeValue;
			break;
		case 'analysis-id':
			foreach ($node->attributes as $attrNode) {
				$data=$attrNode->value;
			}
			$analysisid[$data]=$node->nodeValue;
			break;
		case 'reference-id':
			foreach ($node->attributes as $attrNode) {
				$data=$attrNode->value;
			}
			$referenceid[$data]=$node->nodeValue;
			break;
		}
	}
	if (!empty($databaseid['recode2'])) {
		$result=@sql_query('SELECT id_recode, locus, description FROM recode2 WHERE id_recode=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($databaseid['recode2']))), ENT_QUOTES, 'ISO8859-1')).'\';', $sql);
		if (strlen($r=sql_last_error($sql))) {
			return 'Critical database error (1): Contact the site admistrator!';
		} elseif (sql_num_rows($result)!=0) {
			return 'The entry is already in the database!';
		}
	}
	if (!empty($databaseid['recode'])) {
		$result=@sql_query('SELECT id_recode, locus, description FROM recode2 WHERE legacy=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($databaseid['recode']))), ENT_QUOTES, 'ISO8859-1')).'\';', $sql);
		if (strlen($r=sql_last_error($sql))) {
			return 'Critical database error (2): Contact the site admistrator!';
		} elseif (sql_num_rows($result)!=0) {
			return 'The entry is already in the database!';
		}
	}
	if (!empty($taxonomy['species'])) {
		$result=@sql_query('SELECT taxonid FROM organisms WHERE (latin=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($taxonomy['species']))), ENT_QUOTES, 'ISO8859-1')).'\' AND variant' . (!empty($taxonomy['strain'])?'=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($taxonomy['strain']))), ENT_QUOTES, 'ISO8859-1')).'\'':' IS NULL').')'. (!empty($taxonomy['taxonid'])?' OR taxonid='.intval($taxonomy['taxonid']):'') .';', $sql);
		if (strlen($r=sql_last_error($sql))) {
			return 'Critical database error (3): Contact the site admistrator!';
		} elseif (sql_num_rows($result)==1) {
			$row = sql_fetch_array($result);
			$taxonid=$row['taxonid'];
		} elseif (!empty($taxonomy['taxonid']) && (($ret = get_taxon(intval($taxonomy['taxonid'])))!==false) && ($ret['rank']=='species')) {
			if (!empty($ret['variant'])) { $taxonomy['strain']=$ret['variant']; }
			$result=@sql_query('INSERT INTO organisms (latin,variant,acronym,synonym,taxonid,genus,phylum) VALUES (\''. sql_escape_string($ret['scientificname']).'\', NULL, '.(!empty($ret['acronym'])? '\''.sql_escape_string($ret['acronym']).'\'' : 'NULL').', '.(!empty($ret['commonname'])? '\''.sql_escape_string($ret['commonname']).'\'' : 'NULL').', '.intval($ret['taxonid']).', '.(!empty($ret['genus'])? '\''.sql_escape_string($ret['genus']).'\'' : 'NULL').', \''.sql_escape_string($ret['taxon']).'\');', $sql);
			if (strlen($r=sql_last_error($sql))) {
				return 'Critical database error (4): Contact the site admistrator!';
			}
			$taxonid=$ret['taxonid'];
		} else {
			$taxonid=(!empty($taxonomy['taxonid'])?intval($taxonomy['taxonid']):0x40000000 | crc24(uniqid(mt_rand(), true)));
			foreach (array('domain', 'kingdom', 'phylum', 'class', 'order', 'family', 'genus') as $key => $value) {
				if (!empty($taxonomy[$value])) { $taxon[$key]=$taxonomy[$value].'['.$value.']'; }
			}
			$result=@sql_query('INSERT INTO organisms (latin,variant,acronym,synonym,taxonid,genus,phylum) VALUES (\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($taxonomy['species']))), ENT_QUOTES, 'ISO8859-1')).'\', NULL, '.(!empty($taxonomy['name'])?'\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($taxonomy['name']))), ENT_QUOTES, 'ISO8859-1')).'\'':'NULL').', '. $taxonid.', '. (!empty($taxonomy['genus'])?'\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($taxonomy['genus']))), ENT_QUOTES, 'ISO8859-1')).'\'':'NULL').', '. (!empty($taxon)?'\''.sql_escape_string(implode('; ', $taxon)).'\'':'NULL').');', $sql);
			if (strlen($r=sql_last_error($sql))) {
				return 'Critical database error (5): Contact the site admistrator!';
			}
		}
		$kingdom=0;
		if (!empty($name['comment']) && preg_match('/^([^\(]+)\s+\(([^\)]+)\)/', $name['comment'], $matches)) {
			$result=@sql_query('SELECT kingdom FROM kingdoms WHERE phylum=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($matches[1]))), ENT_QUOTES, 'ISO8859-1')).'\' AND genome=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($matches[2]))), ENT_QUOTES, 'ISO8859-1')).'\';', $sql);
			if (strlen($r=sql_last_error($sql))) {
				return 'Critical database error (6): Contact the site admistrator!';
			} elseif (sql_num_rows($result)==1) {
				$row = sql_fetch_array($result);
				$kingdom=intval($row['kingdom']);
			} else {
				return 'Unknown classification \'Kingdom\'';
			}
		}
		$id_recode = md5($_SERVER['UNIQUE_ID'].$taxonid);
		$result=@sql_query('INSERT INTO recode2 (id_recode,legacy,organism,kingdom,locus,description,status) VALUES (\''.sql_escape_string($id_recode).'\', '. (!empty($databaseid['recode'])?'\''.sql_escape_string($databaseid['recode']).'\'':'NULL').', '.intval($taxonid).', '.intval($kingdom).', \''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($name['name']))), ENT_QUOTES, 'ISO8859-1')).'\','. (!empty($description)?'\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($description))), ENT_QUOTES, 'ISO8859-1')).'\'':'NULL').', 0);', $sql);
		$result=@sql_query('SELECT id FROM recode2 WHERE id_recode=\''.sql_escape_string($id_recode).'\';', $sql);
		if (strlen($r=sql_last_error($sql)) || (sql_num_rows($result)!=1)) {
			return 'Critical database error: Contact the site admistrator!<br/>reference: 7/'.$id_recode;
		}
		$row=sql_fetch_array($result);
		$id_recode = sprintf("r2%05d", $row['id']);
		$result=@sql_query('UPDATE recode2 SET id_recode=\''.sql_escape_string($id_recode).'\' WHERE id='.intval($row['id']).';', $sql);
	} else {
		return 'Unknown species';
	}
	$molecules = $dom->getElementsByTagName('molecules');
	foreach ($molecules->item(0)->childNodes as $node) {
		switch ($node->nodeName) {
		case 'sequence':
			$sequence=array();
			foreach ($node->childNodes as $subnode) {
				switch ($subnode->nodeName) {
				case 'name':
					$sequence['name']=$subnode->nodeValue;
					break;
				case 'seq-data':
					if ( $subnode->hasAttributes() ) {
						foreach ($subnode->attributes as $attrNode) {
							$sequence['data']['comment']=$attrNode->value;
						}
					}
					$sequence['data']['sequence']=$subnode->nodeValue;
					break;
				case 'annotation':
					foreach ($subnode->childNodes as $annotation) {
						switch ($annotation->nodeName) {
						case 'modification':
							$tmp=array();
							if ( $annotation->hasAttributes() ) {
								foreach ($annotation->attributes as $attrName => $attrNode) {
									$tmp[$attrName]=$attrNode->value;
								}
							}
							if (!empty($tmp['position'])) $sequence['annotation'][]=$tmp['position'].':'.$annotation->nodeValue;
							break;
						case 'segment':
							$tmp=array();
							foreach ($annotation->childNodes as $seg) {
								if ($seg->nodeType==1) $tmp[$seg->nodeName]=$seg->nodeValue;
							}
							if (empty($tmp['base-id-3p']) && !empty($tmp['length']) && !empty($tmp['base-id-5p'])) {
								$tmp['base-id-3p']=$tmp['base-id-5p']+$tmp['length']-1;
							}
							if (empty($tmp['name']) && !empty($tmp['base-id-5p'])) {
								$tmp['name']='seg'.$tmp['base-id-5p'];
							}
							if (!empty($tmp['base-id-5p']) && !empty($tmp['base-id-3p']))  $sequence['annotation'][]=$tmp['name'].'['.$tmp['base-id-5p'].'-'.$tmp['base-id-3p'].']';
							break;
						}
					}
					break;
				}
			}
			if ( $node->hasAttributes() ) {
				foreach ($node->attributes as $attrName => $attrNode) {
					$sequence[$attrName]=$attrNode->value;
				}
			}
			$sequences[$sequence['id']]=$sequence;
			break;
		case 'structure':
			$structure=array();
			if ( $node->hasAttributes() ) {
				foreach ($node->attributes as $attrName => $attrNode) {
					$structure[$attrName]=$attrNode->value;
				}
			}
			foreach ($node->childNodes as $subnode) {
				switch ($subnode->nodeName) {
				case 'base-pair':
					$tmp=array();
					foreach ($annotation->childNodes as $seg) {
						if ($seg->nodeType==1) $tmp[$seg->nodeName]=$seg->nodeValue;
					}
					if (!empty($tmp['base-id-5p']) && !empty($tmp['base-id-3p'])) {
						$structure['location']=$tmp['base-id-5p'].'|'.$tmp['base-id-3p'].'|'.($tmp['base-id-3p']-$tmp['base-id-5p']+1);
						if (!in_array($structure['type'], array('base-pair'))) { $structure['type']='base-pair'; }
					}
					break;
				case 'helix':
					$tmp=array();
					foreach ($subnode->childNodes as $seg) {
						if ($seg->nodeName=='stem') {
							$tmp2=array();
							foreach ($seg->childNodes as $helix) {
								if ($helix->nodeType==1) $tmp2[$helix->nodeName]=$helix->nodeValue;
							}
							if (!empty($tmp2['base-id-5p']) && !empty($tmp2['base-id-3p']) && !empty($tmp2['length']))  $tmp[]=$tmp2['base-id-5p'].'|'.($tmp2['base-id-5p']+$tmp2['length']-1).'|'.$tmp2['length'].','.$tmp2['base-id-3p'].'|'.($tmp2['base-id-3p']+$tmp2['length']-1).'|'.$tmp2['length'];
						}
					}
					if (!empty($tmp[0])) {
						$structure['location']=implode(',', $tmp);
						if (!in_array($structure['type'], array('SECIS', 'Stemloop'))) { $structure['type']='Stemloop'; }
					}
				case 'pseudoknot':
					$tmp=array();
					foreach ($subnode->childNodes as $seg) {
						if ($seg->nodeName=='helix') {
							$tmp2=array();
							foreach ($seg->childNodes as $helix) {
								if ($helix->nodeName=='stem') {
									$tmp3=array();
									foreach ($helix->childNodes as $stem) {
										if ($stem->nodeType==1) $tmp3[$stem->nodeName]=$stem->nodeValue;
									}
									if (!empty($tmp3['base-id-5p']) && !empty($tmp3['base-id-3p']) && !empty($tmp3['length']))  $tmp2[]=$tmp3['base-id-5p'].'|'.($tmp3['base-id-5p']+$tmp3['length']-1).'|'.$tmp3['length'].','.$tmp3['base-id-3p'].'|'.($tmp3['base-id-3p']+$tmp3['length']-1).'|'.$tmp3['length'];
								}
							}
							if (!empty($tmp2[0])) {
								$tmp[]=implode(',', $tmp2);
							}
						}
					}
					if (!empty($tmp[0])) {
						$structure['location']=implode(';', $tmp);
						if (!in_array($structure['type'], array('Pseudoknot'))) { $structure['type']='Pseudoknot'; }
					}
					break;
				case 'single-strand':
					$tmp=array();
					foreach ($subnode->childNodes as $seg) {
						if ($seg->nodeName=='segment') {
							foreach ($seg->childNodes as $segment) {
								if ($segment->nodeType==1) $tmp[$segment->nodeName]=$segment->nodeValue;
							}
						}
					}
					if (!empty($tmp['base-id-5p']) && !empty($tmp['base-id-3p'])) {
						$structure['location']=$tmp['base-id-5p'].'|'.$tmp['base-id-3p'].'|'.($tmp['base-id-3p']-$tmp['base-id-5p']+1);
						if (!in_array($structure['type'], array('Sequence', 'Trans/Interaction', 'Unknown'))) { $structure['type']='Unknown'; }
					}
					break;
				case 'complex':
					$tmp=array();
					foreach ($subnode->childNodes as $complex) {
						switch ($complex->nodeName) {
						case 'segment':
							foreach ($complex->childNodes as $seg) {
								if ($seg->nodeType==1) $tmp[$seg->nodeName]=$seg->nodeValue;
							}
							if (empty($tmp['base-id-3p']) && !empty($tmp['length']) && !empty($tmp['base-id-5p'])) {
								$tmp['base-id-3p']=$tmp['base-id-5p']+$tmp['length']-1;
							}
							break;
						case 'brackets':
							$tmp['brackets']=$complex->nodeValue;
							break;
						}
					}
					if (!empty($tmp['brackets']) && !empty($tmp['base-id-5p']) && !empty($tmp['base-id-3p'])) {
						$structure['location']=$tmp['base-id-5p'].'|'.$tmp['base-id-3p'].'|'.($tmp['base-id-3p']-$tmp['base-id-5p']+1).'|'.$tmp['brackets'];
						if (!in_array($structure['type'], array('Complex structure', 'SECIS structure', 'Pseudoknot structure'))) { $structure['type']='Complex structure'; }
					}
					break;
				}
			}
			if (!empty($structure['location'])) { $structures[$structure['id']]=$structure;}
			break;
		case 'product':
			$product=array();
			foreach ($node->childNodes as $subnode) {
				switch ($subnode->nodeName) {
				case 'name':
					$product['name']=$subnode->nodeValue;
					break;
				case 'seq-data':
					$product['sequence']=$subnode->nodeValue;
					break;
				case 'modification':
					$tmp=array();
					if ( $subnode->hasAttributes() ) {
						foreach ($subnode->attributes as $attrName => $attrNode) {
							$tmp[$attrName]=$attrNode->value;
						}
					}
					if (!empty($tmp['position'])) $product['modification'][]=$tmp['position'].':'.$subnode->nodeValue;
					break;
				case 'recoding-event':
					$product['event']=$subnode->nodeValue;
					break;
				}
			}
			if ( $node->hasAttributes() ) {
				foreach ($node->attributes as $attrName => $attrNode) {
					$product[$attrName]=$attrNode->value;
				}
			}
			$products[$product['id']]=$product;
			break;
		}
	}
	$recodings = $dom->getElementsByTagName('recodings');
	if ($recodings->length) {
		$recoding=array();
		foreach ($recodings->item(0)->childNodes as $node) {
			if ($node->childNodes) {
				$tmp=array();
				foreach ($node->childNodes as $subnode) {
					if ($subnode->nodeType==1) {
						if ($subnode->nodeName=='model') {
							$tmp['model'][]=$subnode->nodeValue;
						} else {
							$tmp[$subnode->nodeName]=$subnode->nodeValue;
							if ($subnode->hasAttributes()) {
								foreach ($subnode->attributes as $attrName => $attrNode) {
									$tmp[$attrName]=$attrNode->value;
								}
							}
						}
					}
				}
				if ( $node->hasAttributes() ) {
					foreach ($node->attributes as $attrName => $attrNode) {
						$tmp[$attrName]=$attrNode->value;
					}
				}
				$recoding[$tmp['id']]=$tmp;
			}
		}
	}
	if (!empty($sequences) && count($sequences)>0) {
		foreach ($sequences as $key => $value) {
			$result=@sql_query('INSERT INTO molecules (id_recode,variant,ext_databases,coordinates,sequence,genetic,acid_type,strand,length,topology) VALUES (\'' . sql_escape_string($id_recode) . '\', ' . (!empty($value['name'])?'\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($value['name']))), ENT_QUOTES, 'ISO8859-1')).'\'':(!empty($taxonomy['strain'])?'\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($taxonomy['strain']))), ENT_QUOTES, 'ISO8859-1')).'\'':'NULL')) . ', ' . (!empty($value['data']['comment'])?'\''.sql_escape_string($value['data']['comment']).'\'':'NULL') . ', \'' . (!empty($value['coordinates'])?sql_escape_string($value['coordinates']):'1..'.strlen($value['data']['sequence'])) . '\', \'' . sql_escape_string(base64_encode(bzcompress($value['data']['sequence'], 9))) . '\', ' . (!empty($value['genetic'])?intval($value['genetic']):0) . ', \'' . sql_escape_string($value['type']) . '\', ' . (!empty($value['strand'])?(($value['strand']=='minus')?-1:1):0) . ', ' . intval(strlen($value['data']['sequence'])) . ', \'' . (!empty($value['circular'])?(($value['circular']=='true')?'circular':'linear'):'linear') . '\');', $sql);
			$result=@sql_query('SELECT id FROM molecules WHERE id_recode=\''.sql_escape_string($id_recode).'\';', $sql);
			if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
				$row=sql_fetch_array($result);
				$id_molecule = $row['id'];
				if (!empty($structures) && count($structures)>0) {
					foreach ($structures as $item) {
						if ($item['sequence-id']==$key) {
							$result=@sql_query('SELECT structure FROM structures WHERE name=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($item['type']))), ENT_QUOTES, 'ISO8859-1')).'\';', $sql);
							if (strlen($r=sql_last_error($sql))) {
								return 'Critical database error: Contact the site admistrator!<br/>reference: 8/'.$id_recode;
							} elseif (sql_num_rows($result)==1) {
								$row = sql_fetch_array($result);
								$item['type']=intval($row['structure']);
							} else {
								return 'Unknown structure type \''.$item['type'].'\': Contact the site admistrator!<br/>reference: 9/'.$id_recode;
							}
							$result=@sql_query('INSERT INTO morphorna (id_recode,id_molecule,structure,description) VALUES (\''. sql_escape_string($id_recode).'\', '.intval($id_molecule).', '.intval($item['type']).', \''.sql_escape_string($item['location']).'\');', $sql);
						}
					}
				}
				if (!empty($products) && count($products)>0) {
					$ref=1;
					foreach ($products as $item) {
						if ($item['sequence-id']==$key) {
							$result=@sql_query('INSERT INTO products (id_product,id_molecule,id_recode,name,sequence,modification,coordinates,description) VALUES (\'' . sql_escape_string($id_recode.'.'.(empty($item['event'])?1:++$ref)) . '\', ' . intval($id_molecule) . ', \'' . sql_escape_string($id_recode) . '\', \'' . sql_escape_string($item['name']) . '\', \'' . sql_escape_string(base64_encode(bzcompress($item['sequence'], 9))) . '\', ' . (!empty($item['modification'][0])? '\''.sql_escape_string(implode('|', $item['modification'])).'\'' : 'NULL') . ', \'' . sql_escape_string($item['sequence-reading']) . '\', ' . (!empty($item['comment'])? '\''.sql_escape_string($item['comment']).'\'' : 'NULL') . ');', $sql);
							if (!empty($item['event']) && !empty($recoding[$item['event']])) {
								$item2=$recoding[$item['event']];
								$query='INSERT INTO recoding (id_recode,id_product,experimental,description,model,event,position,codon,upstream,esite,psite,asite,downstream) VALUES (\''. sql_escape_string($id_recode).'\', \''. sql_escape_string($id_recode.'.'.intval($ref)).'\', \'' . sql_escape_string((!empty($item2['experimental']) && $item2['experimental']=='true')?'t':'f').'\', '. (!empty($item2['function'])?'\''.sql_escape_string($item2['function']).'\'':'NULL') .', '.(!empty($item2['model'][0])? '\''.sql_escape_string(implode('|', $item2['model'])).'\'' : 'NULL').', ';
								$result=@sql_query('SELECT event FROM events WHERE name=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($item2['type']))), ENT_QUOTES, 'ISO8859-1')).'\';', $sql);
								if (strlen($r=sql_last_error($sql))) {
									return 'Critical database error: Contact the site admistrator!<br/>reference: '.$id_recode;
								} elseif (sql_num_rows($result)==1) {
									$row = sql_fetch_array($result);
									$item2['type']=intval($row['event']);
								} else {
									return 'Unknown event \''.$item2['type'].'\': Contact the site admistrator!<br/>reference: A/'.$id_recode;
								}
								$query .= $item2['type'] . ', ' . intval($item2['position']) . ', ' . (!empty($item2['codon'])? '\''.sql_escape_string($item2['codon']).'\'' : 'NULL') . ', ' . (!empty($item2['upstream'])? '\''.sql_escape_string($item2['upstream']).'\'' : 'NULL') . ', ' . (!empty($item2['esite'])? '\''.sql_escape_string($item2['esite']).'\'' : 'NULL') . ', ' . (!empty($item2['psite'])? '\''.sql_escape_string($item2['psite']).'\'' : 'NULL') . ', ' . (!empty($item2['asite'])? '\''.sql_escape_string($item2['asite']).'\'' : 'NULL') . ', ' . (!empty($item2['downstream'])? '\''.sql_escape_string($item2['downstream']).'\'' : 'NULL') . ');';
								$result=@sql_query($query, $sql);
								if (strlen($r=sql_last_error($sql))) {
									return 'Critical database error: Contact the site admistrator!<br/>reference: B/'.$id_recode;
								}
                $validation[]=$item2['status'];
							}
						}
					}
				}
			} else {
				return 'Critical database error: Contact the site admistrator!<br/>reference: C/'.$id_recode;
			}
		}
	}
	$references = $dom->getElementsByTagName('references');
	if ($references->length) {
		$reference=array();
		foreach ($references->item(0)->childNodes as $node) {
			if ($node->childNodes) {
				$tmp=array();
				foreach ($node->childNodes as $subnode) {
					if ($subnode->nodeType==1) {
						if ($subnode->nodeName=='date') {
							$tmp['date']=read_date($subnode->childNodes);
						} elseif ($subnode->nodeName=='author') {
							$tmp['author'][]=$subnode->nodeValue;
						} else {
							$tmp[$subnode->nodeName]=$subnode->nodeValue;
						}
					}
				}
				if ( $node->hasAttributes() ) {
					foreach ($node->attributes as $attrName => $attrNode) {
						$tmp[$attrName]=$attrNode->value;
					}
				}
				$reference[$tmp['id']]=$tmp;
			}
		}
		if (!empty($reference) && count($reference)>0) {
			foreach ($reference as $biblio) {
				if (!empty($biblio['pubmed-id']) && intval($biblio['pubmed-id'])>0 && ($journal=getMedLine(intval($biblio['pubmed-id'])))!==false) {
					$result=@sql_query('INSERT INTO journals (id_recode,authors,title,journal,theyear,doi) VALUES (\''. sql_escape_string($id_recode).'\', \''. sql_escape_string($journal['authors']).'\', \''. sql_escape_string($journal['title']).'\', \''. sql_escape_string($journal['journal'].'. '.$journal['volume'].(!empty($journal['issue']) ? '('.$journal['issue'].')':'').':'.$journal['start'].(!empty($journal['end']) ? '-'.$journal['end']:'')).'\', '. intval($journal['year']) .', \''. sql_escape_string((!empty($journal['doi']) ? 'doi:' . $journal['doi'] : 'pmid:' . $journal['pmid'])).'\');', $sql);
				} else {
					$result=@sql_query('INSERT INTO journals (id_recode,authors,title,journal,theyear,doi) VALUES (\''. sql_escape_string($id_recode).'\', \''. sql_escape_string(implode(', ', $biblio['author'])).'\', \''. sql_escape_string($biblio['title']).'\', \''. sql_escape_string((!empty($biblio['journal'])?$biblio['journal'].(!empty($biblio['volume'])?'. '.$biblio['volume'].(!empty($biblio['issue']) ? '('.$biblio['issue'].')':'').(!empty($biblio['pages']) ? ':'.$biblio['pages']:''):''):'In '.$biblio['book-title'].(!empty($biblio['editor'])?', '.$biblio['editor'].' ed.':'').(!empty($biblio['pages']) ? ':'.$biblio['pages']:''))) .'\', '. (!empty($biblio['date']) ? intval(substr($biblio['date'], 0, 4)):'NULL') .', '. (!empty($biblio['doi'])?'\''.sql_escape_string( 'doi:' . $biblio['doi'] ).'\'':'').');', $sql);
				}
			}
		}
	}

	$analysis = $dom->getElementsByTagName('analysis');
	if ($analysis->length) {
		$analyse=array();
		foreach ($analysis->item(0)->childNodes as $node) {
			if ($node->childNodes) {
				$tmp=array();
				foreach ($node->childNodes as $subnode) {
					if ($subnode->nodeType==1) {
						if ($subnode->nodeName=='date') {
							$tmp['date']=read_date($subnode->childNodes);
						} elseif ($subnode->nodeName=='parameter') {
							$tmp['parameter'][]=$subnode->nodeValue;
						} else {
							$tmp[$subnode->nodeName]=$subnode->nodeValue;
						}
					}
				}
				if ( $node->hasAttributes() ) {
					foreach ($node->attributes as $attrName => $attrNode) {
						$tmp[$attrName]=$attrNode->value;
					}
				}
				$analyse[$tmp['id']]=$tmp;
			}
		}
		if (!empty($analyse) && count($analyse)>0) {
			foreach ($analyse as $item) {
				$result=@sql_query('INSERT INTO analyses (id_recode, program, version, analysed, parameter) VALUES (\''. sql_escape_string($id_recode).'\', \''.sql_escape_string($item['program']).'\', '.(!empty($item['version'])? '\''.sql_escape_string($item['version']).'\'' : '0').', \''.sql_escape_string($item['date']).'\', '.(!empty($item['parameter'][0])? '\''.sql_escape_string(implode('|', $item['parameter'])).'\'' : 'NULL').');', $sql);
			}
		}
	}

	$revisions = $dom->getElementsByTagName('revisions');
	if ($revisions->length) {
		$revision=array();
		foreach ($revisions->item(0)->childNodes as $node) {
			if ($node->childNodes) {
				$tmp=array();
				foreach ($node->childNodes as $subnode) {
					if ($subnode->nodeName=='date') {
						$tmp['date']=read_date($subnode->childNodes);
					} else {
						$tmp[$subnode->nodeName]=$subnode->nodeValue;
					}
				}
				if ( $node->hasAttributes() ) {
					foreach ($node->attributes as $attrName => $attrNode) {
						$tmp[$attrName]=$attrNode->value;
					}
				}
				$revision[$tmp['id']]=$tmp;
			}
		}
		if (!empty($revision) && count($revision)>0) {
			foreach ($revision as $item) {
				$result=@sql_query('SELECT username FROM users WHERE name=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($item['author']))), ENT_QUOTES, 'ISO8859-1')).'\';', $sql);
				if (strlen($r=sql_last_error($sql))) {
					return 'Critical database error: Contact the site admistrator!<br/>reference: D/'.$id_recode;
				} elseif (sql_num_rows($result)==1) {
					$row = sql_fetch_array($result);
					$item['author']=intval($row['event']);
				} else {
					$item['author']=$_SESSION['login']['username'];
				}
				$result=@sql_query('INSERT INTO revisions (id_recode,revised,comments,author) VALUES (\''. sql_escape_string($id_recode).'\', \''.sql_escape_string($item['date']).'\', '.(!empty($item['comment'])? '\''.sql_escape_string($item['comment']).'\'' : 'NULL'). ', \''. sql_escape_string($item['author']).'\');', $sql);
			}
		}
	}
	$result=@sql_query('INSERT INTO revisions (id_recode,revised,comments,author) VALUES (\''. sql_escape_string($id_recode).'\', NOW(), \'Direct RecodeML importation'.(!empty($validation[0])?' ('.implode(', ',$validation).')':'').'\', \''. sql_escape_string($_SESSION['login']['username']).'\');', $sql);
	if (!strlen($r=sql_last_error($sql))) {
		return 'Importation successful [<a href="'. $config['server'].'recode/'.urlencode($id_recode) .'">'.$id_recode.'</a>]';
	}
}


if (!empty($_SESSION['login']) && !empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>2)) {
	if (!empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server']) && !empty($_FILES['recodeml']) && ($_FILES['recodeml']['error'] == UPLOAD_ERR_OK) && ($_FILES['recodeml']['size'] > 0) && is_uploaded_file($_FILES['recodeml']['tmp_name'])) {
		require_once 'includes/import.inc';
		$xml=file_get_contents($_FILES['recodeml']['tmp_name']);
		$dom = new DOMDocument();
		$dom->loadxml($xml, LIBXML_NOERROR|LIBXML_NOWARNING);
		if (!empty($dom->doctype) && ($dom->doctype->name == 'recodeml') && ($dom->doctype->publicId == '-//Recode//DTD Recodeml 0.3//EN') && ($dom->doctype->systemId == $config['master'].'dtd/recodeml.dtd') ) {
			if (@$dom->validate()) {
				$err=import($dom);
			} else {
				$err='This document is invalid! (Recodeml v0.3 required)';
			}
		} else {
			$err='No DTD found!';
		}
	}
	htmlheader();
?>
      <p>&nbsp;</p>
<?php if (!empty($err)) { print "      <div id=\"error\"><div>$err</div></div>\n"; } ?>
      <h2>Importation <small>(recommended)</small></h2>
      <form method="post" enctype="multipart/form-data" id="signid" action="<?php print $config['server'] . 'import'; ?>/">
        <div>
          <label for="recodeml" class="required">RecodeML file</label>
          <input type="file" name="recodeml" id="recodeml" /><br /><br />
          <input value="Import" type="submit" />
        </div>
      </form>
      <p>A properly-constructed RecodeML (XML) file ensures that the data handler can correctly convert the data. The purpose of the <a href="<?php print $config['server'] . 'dtd'; ?>/" title="RecodeML Document Type Definition">document type definition</a> (DTD) is to define the legal building blocks of an XML document. It defines the document structure with a list of legal elements.</p>
      <h2>Add</h2>
      <p>A manual procedure can be used for a very limited number of entry to add. The use of an RecodeML (XML) file is strongly recommended to ensure that the data are correctly handled. <a href="<?php print $config['server'] . 'new'; ?>">Start the manual procedure</a>...</p>
<?php
	htmlfooter();
} else {
	header('Location: ' . $config['server'] );
	exit;
}
?>
