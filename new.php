<?php
require_once 'includes/main.inc';

function mymap($map, $pos, $len, $colour='black') {
	for ($i=0; $i<$len; $i=$i+1) {
		if (!empty($map[$pos+$i])) {
			$map[$pos+$i].=' '.$colour;
		} else {
			$map[$pos+$i]=$colour;
		}
	}
	return $map;
}

function mysequence($seq, $map) {
	$length = strlen($seq);
	$pos=1;
	$string='';
	for ($i=0; $i<$length; $i=$i+1) {
		$letter=substr($seq, $i, 1);
		if (ord($letter)>64) {
			if (!empty($map[$pos])) {
				$string.='<span class="'. $map[$pos] .'">'.$letter.'</span>';
			} else {
				$string.=$letter;
			}
			$pos++;
		} else {
			$string.=$letter;
		}
	}
	return $string;
}

if (!empty($_SESSION['login']) && strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'xh')) $debug=get_switch('debug');

if (!empty($_SESSION['login']) && !empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>2)) {
	if (!empty($_POST) && !empty($_POST['step'])) {
    require_once 'includes/import.inc';
		if (intval($_POST['step'])==1) {
			if ( ( !empty($_POST['species_taxonid']) || !empty($_POST['species_name'])) ) {
				unset($_SESSION['entry']);
				if (!empty($_POST['species_name'])) {
					$result=@sql_query('SELECT taxonid,latin,phylum FROM organisms WHERE latin=\''.sql_escape_string(ucfirst(strtolower(preg_replace('/&amp;(#x?[0-9a-f]+);/', '&\1', htmlentities(html_entity_decode(trim($_POST['species_name'])), ENT_QUOTES, 'ISO8859-1'))))).'\';', $sql);
					if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
						$row=sql_fetch_array($result);
						$_SESSION['entry']['taxonid']=$row['taxonid'];
						$_SESSION['entry']['species']=$row['latin'];
						if (preg_match('/^Viruses/', $row['phylum'])) { $_SESSION['entry']['phylum']='Viral Genome'; }
						if (preg_match('/^cellular organisms; (\w+)/', $row['phylum'], $matches)) { $_SESSION['entry']['phylum']=$matches[1]; }
					} else {
						$ret = find_taxon(ucfirst(strtolower(preg_replace('/&amp;(#x?[0-9a-f]+);/', '&\1', htmlentities(html_entity_decode(trim($_POST['species_name'])), ENT_QUOTES, 'ISO8859-1')))));
						if (count($ret) == 1) {
							if ((($ret = get_taxon($ret[0]))!==false) && ($ret['rank']=='species')) {
								$_SESSION['entry']['taxon']=$ret;
								$_SESSION['entry']['species']=$ret['scientificname'];
								if (preg_match('/^Viruses/', $ret['taxon'])) {$_SESSION['entry']['phylum']='Viral Genome'; }
								if (preg_match('/^cellular organisms; (\w+)/', $ret['taxon'], $matches)) { $_SESSION['entry']['phylum']=$matches[1]; }
								if (!empty($ret['variant'])) { $_SESSION['entry']['variant']=$ret['variant']; }
							}
						}
					}
				} elseif (!empty($_POST['species_taxonid'])) {
					$result=@sql_query('SELECT taxonid,latin,phylum FROM organisms WHERE taxonid='.intval($_POST['species_taxonid']).';', $sql);
					if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
						$row=sql_fetch_array($result);
						$_SESSION['entry']['taxonid']=$row['taxonid'];
						$_SESSION['entry']['species']=$row['latin'];
						if (preg_match('/^Viruses/', $row['phylum'])) {$_SESSION['entry']['phylum']='Viral Genome'; }
						if (preg_match('/^cellular organisms; (\w+)/', $row['phylum'], $matches)) { $_SESSION['entry']['phylum']=$matches[1]; }
					} elseif ((($ret = get_taxon(intval($_POST['species_taxonid'])))!==false) && ($ret['rank']=='species')) {
						$_SESSION['entry']['taxon']=$ret;
						$_SESSION['entry']['species']=$ret['scientificname'];
						if (preg_match('/^Viruses/', $ret['taxon'])) {$_SESSION['entry']['phylum']='Viral Genome'; }
						if (preg_match('/^cellular organisms; (\w+)/', $ret['taxon'], $matches)) { $_SESSION['entry']['phylum']=$matches[1]; }
						if (!empty($ret['variant'])) { $_SESSION['entry']['variant']=$ret['variant']; }
					}
				}
				if (!empty($_SESSION['entry']['taxonid']) || !empty($_SESSION['entry']['taxon'])) {
					$step=2;
				}
			}
		} elseif (intval($_POST['step'])==2) {
			$step=2;
			unset($_SESSION['entry']['sequence']);
			if  (!empty($_POST['sequence_organelle']) && (!empty($_POST['sequence_acc']) || (!empty($_POST['sequence_raw']) && !empty($_POST['sequence_genetic']) && !empty($_POST['sequence_acid']) && !empty($_POST['sequence_topology'])))) {
				unset($_SESSION['entry']['sequence']);
				if (!empty($_POST['sequence_acc'])) {
					if (($ret=getSequence(trim($_POST['sequence_acc']), $_POST['sequence_begin'], $_POST['sequence_end'], (!empty($_POST['sequence_strand'])?true:false)))!==false) {
						$_SESSION['entry']['sequence']=$ret;
						if (!empty($_POST['ext_databases'])) {
							foreach (preg_split("/[\s,]+/", trim($_POST['ext_databases'])) as $reference) {
								if (getSequence(trim($reference), 1, 2)!==false) {$_SESSION['entry']['sequence']['ext_databases'][]=$reference; }
							}
							if (!empty($_SESSION['entry']['sequence']['ext_databases'][0])) { $_SESSION['entry']['sequence']['ext_databases']=implode(',', $_SESSION['entry']['sequence']['ext_databases']); }
						}
					}
				} elseif (!empty($_POST['sequence_raw']) && (strlen($_POST['sequence_raw'])<30000) && !empty($_POST['sequence_genetic']) && !empty($_POST['sequence_acid']) && !empty($_POST['sequence_topology'])) {
					$ret=array('start'=>1, 'strand'=>1);
					$ret['sequence']=strtoupper(preg_replace('/[^A-Za-z]/', '', $_POST['sequence_raw']));
					$ret['sequence']=strtoupper(preg_replace('/U/', 'T', $ret['sequence']));
					$ret['end']=$ret['start']+strlen($ret['sequence'])-1;
					$ret['size']=strlen($ret['sequence']);
					$ret['coord']=$ret['start'].'..'.$ret['end'];
					$ret['molecule'] = preg_replace('/[^A-Za-z-]/', '', $_POST['sequence_acid']);
					$ret['circular'] = ($_POST['sequence_topology']=='circular'?'circular':'linear');
					$ret['translation'] = intval($_POST['sequence_genetic']);
					$_SESSION['entry']['sequence']=$ret;
				}
				if (!empty($_SESSION['entry']['sequence'])) {
					$_SESSION['entry']['sequence']['organelle']=intval($_POST['sequence_organelle']);
					if (!empty($_SESSION['entry']['variant']) && !empty($_POST['origin_variant'])) { $_SESSION['entry']['variant']=ucfirst(strtolower(preg_replace('/&amp;(#x?[0-9a-f]+);/', '&\1', htmlentities(html_entity_decode(trim($_POST['origin_variant'])), ENT_QUOTES, 'ISO8859-1')))); }
					$step=3;
				}
			}
		} elseif (intval($_POST['step'])==3) {
			$step=3;
			unset($_SESSION['entry']['events']);
			if  (!empty($_POST['event_type']) && is_array($_POST['event_type']) && !empty($_POST['gene_start']) && !empty($_POST['gene_name']) && !empty($_POST['locus_name'])) {
				$ret['name']=trim($_POST['locus_name']);
				if (!empty($_POST['locus_comments'])) { $ret['comments']=trim($_POST['locus_comments']); }
				if (intval($_POST['gene_start']) > 0 ) { //coordinate
					$ret['start']=intval($_POST['gene_start']);
				} else { //sequence
					$ret['start']=strpos($_SESSION['entry']['sequence']['sequence'], preg_replace('/[^A-Z]/', '', strtoupper(trim($_POST['gene_start']))))+strlen(preg_replace('/[^A-Z]/', '', strtoupper(trim($_POST['gene_start']))))+1;
				}
/*************************************************/
/*       OK for 1 case at the time               */
/*************************************************/
				$subseq=substr($_SESSION['entry']['sequence']['sequence'], $ret['start']-1);
				$frame=translate_DNA_to_protein(substr($subseq, 0, floor(strlen($subseq)/3)*3), $_SESSION['entry']['sequence']['translation']);
				$ret['normal']['protein']=substr($frame, 0, strpos($frame, '*'));
				$ret['normal']['cds']=$ret['start'].'..'.($ret['start']+(strlen($ret['normal']['protein'])*3)-1);
				$ret['normal']['name']=trim($_POST['gene_name']);
				if (!empty($_POST['gene_description'])) { $ret['normal']['description']=trim($_POST['gene_description']); }
				if (!empty($_POST['gene_yes'])) { $ret['normal']['yes']=$_POST['gene_yes']; }
				if (strlen($ret['normal']['protein'])>10) {
					$ret['end']=($ret['start']+(strlen($ret['normal']['protein'])*3)-1);
					$_SESSION['entry']['events']=$ret;
					unset($subseq, $ret);
					foreach ($_POST['event_type'] as $key => $item) {
						if (!empty($item) && !empty($_POST['event_site'][$key]) && !empty($_POST['event_name'][$key]) && ( ($item!=4) || !empty($_POST['event_stop'][$key]))) {
							$ret=array('type'=>$item);
							if (intval($_POST['event_site'][$key]) > 0 ) { //coordinate
								$ret['site']=intval($_POST['event_site'][$key]);
							} else { //sequence
								$ret['site']=strpos($_SESSION['entry']['sequence']['sequence'], preg_replace('/[^A-Z]/', '', strtoupper(trim($_POST['event_site'][$key]))))+strlen(preg_replace('/[^A-Z]/', '', strtoupper(trim($_POST['event_site'][$key]))))+1;
							}
							if (!empty($_POST['event_stop'][$key])) {
								if (intval($_POST['event_stop'][$key]) > 0 ) { //coordinate
									$ret['stop']=intval($_POST['event_stop'][$key])-1;
								} else { //sequence
									$ret['stop']=strpos($_SESSION['entry']['sequence']['sequence'], preg_replace('/[^A-Z]/', '', strtoupper(trim($_POST['event_stop'][$key]))))+strlen(preg_replace('/[^A-Z]/', '', strtoupper(trim($_POST['event_stop'][$key]))));
								}
							}
							switch ($item) {
							case 1:
								$ret['stop']++;
								$subseq=substr($_SESSION['entry']['sequence']['sequence'], (empty($ret['stop'])?($_SESSION['entry']['events']['start']-1):($ret['stop']-1)), (empty($ret['stop'])?($ret['site']-$_SESSION['entry']['events']['start']+1):($ret['site']-$ret['stop']+1))) . substr($_SESSION['entry']['sequence']['sequence'], $ret['site']-1);
								$ret['cds']='join('.(empty($ret['stop'])?$_SESSION['entry']['events']['start']:($ret['stop'])) . '..' . $ret['site'] . ',' . $ret['site'] . '..';
								$ret['modification']=$ret['site'].'..'.$ret['site'].':ribosomal_frameshift';
								break;
							case 2:
								$subseq=substr($_SESSION['entry']['sequence']['sequence'], $_SESSION['entry']['events']['start']-1, $ret['site']-$_SESSION['entry']['events']['start']) . substr($_SESSION['entry']['sequence']['sequence'], $ret['site']);
								$ret['cds']='join('.$_SESSION['entry']['events']['start'] . '..' . ($ret['site']-1 ). ',' . ($ret['site']+1). '..';
								$ret['modification']=($ret['site']-1 ). '..' . ($ret['site']+1).':ribosomal_frameshift';
								break;
							case 3:
								$subseq=substr($_SESSION['entry']['sequence']['sequence'], $_SESSION['entry']['events']['start']-1);
								$ret['cds']=$_SESSION['entry']['events']['start'].'..';
								break;
							case 4:
								$subseq=substr($_SESSION['entry']['sequence']['sequence'], $_SESSION['entry']['events']['start']-1, $ret['stop']-$_SESSION['entry']['events']['start']+1);
								$ret['cds']=$_SESSION['entry']['events']['start'].'..';
								break;
							}
							if (!empty($subseq)) {
								$ret['protein']=translate_DNA_to_protein(substr($subseq, 0, floor(strlen($subseq)/3)*3), $_SESSION['entry']['sequence']['translation']);
								switch ($item) {
								case 4:
//The joint nomenclature committee of the IUPAC/IUBMB has officially recommended the three-letter symbol Sec and the one-letter symbol U for selenocysteine
//The joint nomenclature committee of the IUPAC/IUBMB has officially recommended the three-letter symbol Pyl and the one-letter symbol O for pyrrolysine.
									$s=0;
									$i=0;
									while (is_integer($i)) {
										$i = strpos($ret['protein'], '*', $s);
										if (is_integer($i)) {
											$mod[]=($i*3+$_SESSION['entry']['events']['start']).'..'.($i*3+$_SESSION['entry']['events']['start']+2).',aa:Sec';
											$s = $i+1;
										}
									}
									$ret['protein']=str_replace('*', 'U', $ret['protein']);
									$ret['modification']=implode('|', $mod);
									unset($mod);
									$ret['cds'].=($_SESSION['entry']['events']['start']+(strlen($ret['protein'])*3)-1);
									$end=($_SESSION['entry']['events']['start']+(strlen($ret['protein'])*3)-1);
									break;
								case 3:
//stop codon can be decoded not by the cognate release factor, but instead by a mis-cognate tRNA (4). Examples of tRNAs which decode stop codons include yeast tRNA-Gln (GUC) [decodes CAG and UAG], and tRNA-Gln (UUG) [decodes CAA and UAA]
									$ret['modification']=(strpos($ret['protein'], '*')*3+$_SESSION['entry']['events']['start']).'..'.(strpos($ret['protein'], '*')*3+$_SESSION['entry']['events']['start']+2).',aa:OTHER';
									$ret['site']=(strpos($ret['protein'], '*')*3+$_SESSION['entry']['events']['start']);
									$ret['protein']{strpos($ret['protein'], '*')}='Q';
									$ret['protein']=substr($ret['protein'], 0, strpos($ret['protein'], '*'));
									$ret['cds'].=($_SESSION['entry']['events']['start']+(strlen($ret['protein'])*3)-1);
									$end=($_SESSION['entry']['events']['start']+(strlen($ret['protein'])*3)-1);
									break;
								case 1:
									$ret['protein']=substr($ret['protein'], 0, strpos($ret['protein'], '*'));
									$ret['cds'].= ((empty($ret['stop'])?$_SESSION['entry']['events']['start']:($ret['stop']))+(strlen($ret['protein'])*3)-2).')';
									$end=((empty($ret['stop'])?$_SESSION['entry']['events']['start']:($ret['stop']))+(strlen($ret['protein'])*3)-2);
									break;
								case 2:
									$ret['protein']=substr($ret['protein'], 0, strpos($ret['protein'], '*'));
									$ret['cds'].= ($_SESSION['entry']['events']['start']+(strlen($ret['protein'])*3)).')';
									$end=($_SESSION['entry']['events']['start']+(strlen($ret['protein'])*3));
									break;
								}
								if (!empty($_POST['event_name'][$key])) { $ret['name']=trim($_POST['event_name'][$key]); }
								if (!empty($_POST['event_description'][$key])) { $ret['description']=trim($_POST['event_description'][$key]);}
								if (!empty($_POST['event_experimental'][$key])) { $ret['experimental']=$_POST['event_experimental'][$key]; }
								if (!empty($_POST['event_function'][$key])) { $ret['function']=trim($_POST['event_function'][$key]);}
								$ret['yes']='true';
								if ( $_SESSION['entry']['events']['end']<$end) {$_SESSION['entry']['events']['end']=$end; }
							}
							if ((strlen($ret['protein'])>10) && $end>$ret['site']) { $_SESSION['entry']['events'][]=$ret; }
						}
					}
/*************************************************/
					if (!empty($_SESSION['entry']['events'][0])) { $step=4; }
				}
			}
		} elseif (intval($_POST['step'])==4) {
			unset($_SESSION['entry']['structure']);
			if  (!empty($_POST['structure_type']) && is_array($_POST['structure_type'])) {
				foreach ($_POST['structure_type'] as $key => $item) {
					if (!empty($item) && !empty($_POST['structure_location'][$key]) && !empty($_POST['structure_string'][$key])) {
						$ret=array('type'=>$item);
						if (intval($_POST['structure_location'][$key]) > 0 ) { //coordinate
							$ret['location']=intval($_POST['structure_location'][$key]);
						} else { //sequence
							$ret['location']=strpos($_SESSION['entry']['sequence']['sequence'], preg_replace('/[^A-Z]/', '', strtoupper(trim($_POST['structure_location'][$key]))))+strlen(preg_replace('/[^A-Z]/', '', strtoupper(trim($_POST['structure_location'][$key]))))+1;
						}
						switch ($item) {
						case 1: //Trans/Interaction
						case 2: //Sequence
							$ret['length']=strlen(preg_replace('/[^A-Z]/', '', strtoupper(trim($_POST['structure_string'][$key]))));
							if ($ret['length']>0) {
								$ret['description']=$ret['location'].'|'.($ret['location']+$ret['length']-1).'|'.$ret['length'];
							}
							break;
						default: //Bracket structures
							$ret['brackets']=preg_replace('/[^\.\(\)\{\}\[\]:]/', '', strtoupper(trim($_POST['structure_string'][$key])));
							$ret['length']=strlen($ret['brackets']);
							if ($ret['length']>0) {
								$ret['description']=$ret['location'].'|'.($ret['location']+$ret['length']-1).'|'.$ret['length'].'|'.$ret['brackets'];
							}
						}
						if (!empty($ret['description'])) {
							$_SESSION['entry']['structure'][]=$ret;
						}
					}
				}
			}
			$step=5;
		} elseif (intval($_POST['step'])==5) {
			unset($_SESSION['entry']['comments']);
			unset($_SESSION['entry']['references']);
			if (!empty($_POST['reference']) && is_array($_POST['reference'])) {
				foreach ($_POST['reference'] as $item) {
					if (!empty($item) && intval($item)>0 && ($journal=getMedLine(intval($item)))!==false) {
						$_SESSION['entry']['references'][]=$journal;
					}
				}
			}
			if (!empty($_POST['comments'])) { $_SESSION['entry']['comments']=trim($_POST['comments']); }
			$step=6;
		} elseif (intval($_POST['step'])==6) {
			$step=6;
			if (!empty($_SESSION['entry']['taxon']) && empty($_SESSION['entry']['taxonid'])) {
				$result=@sql_query('SELECT taxonid FROM organisms WHERE taxonid='.intval($_SESSION['entry']['taxon']['taxonid']).';', $sql);
				if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==0)) {
					$result=@sql_query('INSERT INTO organisms (latin,variant,acronym,synonym,taxonid,genus,phylum) VALUES (\''.sql_escape_string($_SESSION['entry']['taxon']['scientificname']).'\', NULL, '.(!empty($_SESSION['entry']['taxon']['acronym'])? '\''.sql_escape_string($_SESSION['entry']['taxon']['acronym']).'\'' : 'NULL').', '.(!empty($_SESSION['entry']['taxon']['commonname'])? '\''.sql_escape_string($_SESSION['entry']['taxon']['commonname']).'\'' : 'NULL').', '.intval($_SESSION['entry']['taxon']['taxonid']).', '.(!empty($_SESSION['entry']['taxon']['genus'])? '\''.sql_escape_string($_SESSION['entry']['taxon']['genus']).'\'' : 'NULL').', \''.sql_escape_string($_SESSION['entry']['taxon']['taxon']).'\');', $sql);
					if (!strlen($r=sql_last_error($sql))) {
						$_SESSION['entry']['taxonid']=$_SESSION['entry']['taxon']['taxonid'];
						unset($_SESSION['entry']['taxon']);
					} else {
						$err='Critical database error (1): Contact the site admistrator!';
					}
				} else {
					$_SESSION['entry']['taxonid']=$_SESSION['entry']['taxon']['taxonid'];
					unset($_SESSION['entry']['taxon']);
				}
			}
			$result=@sql_query('SELECT id_recode FROM recode2 WHERE organism='.intval($_SESSION['entry']['taxonid']).' AND kingdom='.intval($_SESSION['entry']['sequence']['organelle']).' AND locus=\''.sql_escape_string($_SESSION['entry']['events']['name']).'\';', $sql);
			if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==0)) {
				$id_recode = md5($_SESSION['entry']['sequence']['sequence']);
				$result=@sql_query('INSERT INTO recode2 (id_recode,organism,kingdom,locus,description,status) VALUES (\''.sql_escape_string($id_recode).'\', '.intval($_SESSION['entry']['taxonid']).', '.intval($_SESSION['entry']['sequence']['organelle']).', \''.sql_escape_string(htmlentities($_SESSION['entry']['events']['name'], ENT_QUOTES)).'\', '.(!empty($_SESSION['entry']['events']['comments'])? '\''.sql_escape_string($_SESSION['entry']['events']['comments']).'\'' : 'NULL').', 0);', $sql);
				$result=@sql_query('SELECT id FROM recode2 WHERE id_recode=\''.sql_escape_string($id_recode).'\';', $sql);
				if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
					$row=sql_fetch_array($result);
					$id_recode = sprintf("r2%05d", $row['id']);
					$result=@sql_query('UPDATE recode2 SET id_recode=\''.sql_escape_string($id_recode).'\' WHERE id='.intval($row['id']).';', $sql);
					if (!empty($_SESSION['entry']['references'])) {
						foreach ($_SESSION['entry']['references'] as $biblio) {
							$result=@sql_query('INSERT INTO journals (id_recode,authors,title,journal,theyear,doi) VALUES (\''. sql_escape_string($id_recode).'\', \''. sql_escape_string($biblio['authors']).'\', \''. sql_escape_string($biblio['title']).'\', \''. sql_escape_string($biblio['journal'].'. '.$biblio['volume'].(!empty($biblio['issue']) ? '('.$biblio['issue'].')':'').':'.$biblio['start'].(!empty($biblio['end']) ? '-'.$biblio['end']:'')).'\', '. intval($biblio['year']) .', \''. sql_escape_string((!empty($biblio['doi']) ? 'doi:' . $biblio['doi'] : 'pmid:' . $biblio['pmid'])).'\');', $sql);
						}
					}
					$result=@sql_query('INSERT INTO molecules (id_recode,variant,ext_databases,coordinates,sequence,genetic,acid_type,strand,length,topology) VALUES (\''. sql_escape_string($id_recode).'\', '.(!empty($_SESSION['entry']['variant'])? '\''.sql_escape_string($_SESSION['entry']['variant']).'\'' : 'NULL').', '.(!empty($_SESSION['entry']['sequence']['reference'])?'\''.sql_escape_string($_SESSION['entry']['sequence']['reference'].(!empty($_SESSION['entry']['sequence']['ext_databases'])?','.$_SESSION['entry']['sequence']['ext_databases']:'')).'\'':'NULL').', \''. sql_escape_string($_SESSION['entry']['events']['start'].'..'.$_SESSION['entry']['events']['end']).'\', \''. sql_escape_string(base64_encode(bzcompress($_SESSION['entry']['sequence']['sequence'], 9))).'\', ' . intval($_SESSION['entry']['sequence']['translation']) .', \''. sql_escape_string($_SESSION['entry']['sequence']['molecule']).'\', ' . intval($_SESSION['entry']['sequence']['strand']) .', ' . intval($_SESSION['entry']['sequence']['size']) .', \''. sql_escape_string($_SESSION['entry']['sequence']['circular']).'\');', $sql);
					$result=@sql_query('SELECT id FROM molecules WHERE id_recode=\''.sql_escape_string($id_recode).'\';', $sql);
					if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
						$row=sql_fetch_array($result);
						$id_molecule = $row['id'];
						if (!empty($_SESSION['entry']['structure'])) {
							foreach ($_SESSION['entry']['structure'] as $item) {
								$result=@sql_query('INSERT INTO morphorna (id_recode,id_molecule,structure,description) VALUES (\''. sql_escape_string($id_recode).'\', '.intval($id_molecule).', '.intval($item['type']).', \''.sql_escape_string($item['description']).'\');', $sql);
							}
						}
						foreach ($_SESSION['entry']['events'] as $key => $item) {
							if (is_array($item) && !empty($item['yes'])) {
								$result=@sql_query('INSERT INTO products (id_product,id_molecule,id_recode,name,sequence,modification,coordinates,description) VALUES (\''. sql_escape_string($id_recode.'.'.(intval($key)+(is_numeric($key)?2:1))).'\', '.intval($id_molecule).', \''. sql_escape_string($id_recode).'\', \''. sql_escape_string($item['name']).'\', \''. sql_escape_string(base64_encode(bzcompress($item['protein'], 9))).'\', '.(!empty($item['modification'])? '\''.sql_escape_string($item['modification']).'\'' : 'NULL').', \''. sql_escape_string($item['cds']).'\', '.(!empty($item['description'])? '\''.sql_escape_string($item['description']).'\'' : 'NULL').');', $sql);
								if (is_numeric($key)) {
									$query='INSERT INTO recoding (id_recode,id_product,experimental,description,event,position,codon,upstream,esite,psite,asite,downstream) VALUES (\''. sql_escape_string($id_recode).'\', \''. sql_escape_string($id_recode.'.'.(intval($key)+2)).'\', \'' . sql_escape_string(!empty($item['experimental'])?'t':'f').'\', '. (!empty($item['function'])?'\''.sql_escape_string($item['function']).'\'':'NULL') .', '.intval($item['type']).', ';
									switch ($item['type']) {
									case 1: //-1 frameshifting
										$query.=intval($item['site']).', NULL, \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-7, 1) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-6, 3) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-3, 3) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-1, 3) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']+2, 5) . '\');';
										break;
									case 2: //+1 frameshifting
										$query.=intval($item['site']).', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-1, 3) . '\', NULL, \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-7, 3) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-4, 3) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site'], 3) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']+3, 5) . '\');';
										break;
									case 3: //stop codon readthrough
										$query.=intval($item['site']).', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-1, 3) . '\', NULL, \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-7, 3) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-4, 3) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']-1, 3) . '\', \'' . substr($_SESSION['entry']['sequence']['sequence'], $item['site']+2, 5) . '\');';
										break;
									case 4: //selenocysteine insertion
										$query.='NULL, \'UGA\', NULL, NULL, NULL, NULL, NULL);';
										break;
									}
									$result=@sql_query($query, $sql);
								}
							}
						}
						$result=@sql_query('INSERT INTO revisions (id_recode,revised,comments,author) VALUES (\''. sql_escape_string($id_recode).'\', NOW(), '.(!empty($_SESSION['entry']['comments'])? '\''.sql_escape_string($_SESSION['entry']['comments']).'\'' : 'NULL'). ', \''. sql_escape_string($_SESSION['login']['username']).'\');', $sql);
						if (!strlen($r=sql_last_error($sql))) {
							unset($_SESSION['entry']);
							header('Location: ' . $config['server'].'recode/'.urlencode($id_recode));
							exit(0);
						} else {
							$err='Critical database error (5): Contact the site admistrator!';
						}
					} else {
						$err='Critical database error (4): Contact the site admistrator!';
					}
				} else {
					$err='Critical database error (3): Contact the site admistrator!';
				}
			} else {
				if (!strlen($r=sql_last_error($sql))) {
					$row=sql_fetch_array($result);
					$err='The locus/gene already exist in the database. See the reference <a href="'.$config['server'].'recode/'.urlencode($row['id_recode']).'">'.$row['id_recode'].'</a>';
				} else {
					$err='Critical database error (2): Contact the site admistrator!';
				}
			}
		}
	}
	htmlheader(false, false, (empty($step)?true:false));
	if (isset($debug) && $debug) {print_r($_SESSION); }
	if (empty($step)) {
		unset($_SESSION['entry']);
?>
      <p>&nbsp;</p>
      <h2>Organism <small>step 1 of 6</small></h2>
      <form method="post" id="signin" action="<?php print $config['server'] . 'new'; ?>">
        <div>
          <label for="species_taxonid">NCBI TaxonID</label>
          <input type="text" maxlength="16" name="species_taxonid" id="species_taxonid" title="" <?php print (!empty($_POST['species_taxonid'])?'value="'.$_POST['species_taxonid'].'"':'autocomplete="off"'); ?> /><br />
          <strong>or</strong><br />
          <label for="species_name">Species Name</label>
          <input type="text" maxlength="32" name="species_name" id="species_name" title="" <?php print (!empty($_POST['species_name'])?'value="'.$_POST['species_name'].'"':'autocomplete="off"'); ?> /><br /><br />
          <input type="hidden" name="step" value="1" /><input value="Next" type="submit" />
        </div>
      </form>
      <script type="text/javascript">
//<![CDATA[
var species_name_xml = {
 script: function (input) { return '<?php print $config['server']; ?>ajaxquery.php?query='+input+'&field=species'; },
 varname:"input"
};
var as_species_name = new bsn.AutoSuggest('species_name', species_name_xml);
//]]>
      </script>
<?php
	} elseif ($step==2) {
?>
      <p>&nbsp;</p>
      <h2>Sequence <small>step 2 of 6</small></h2>
      <form method="post" id="signin" action="<?php print $config['server'] . 'new'; ?>">
        <div>
          <h3>Origin</h3>
          <label for="origin_species">Species</label>
          <input type="text" name="origin_species" id="origin_species" title="" value="<?php print $_SESSION['entry']['species']; ?>" disabled="disabled" /><br />
          <label for="origin_variant">Variant/sub-species</label>
          <input type="text" maxlength="32" name="origin_variant" id="origin_variant" title=""<?php print (!empty($_SESSION['entry']['variant'])?' value="'.$_SESSION['entry']['variant'].'" readonly="readonly"':(!empty($_POST['"origin_variant'])?' value="'.$_POST['"origin_variant'].'"':' autocomplete="off"')); ?> /><br />
          <label for="sequence_organelle" class="required">Organelle/Structure</label>
          <select name="sequence_organelle" id="sequence_organelle"><option></option><?php
		$result=@sql_query('SELECT kingdom, genome FROM kingdoms' . (!empty($_SESSION['entry']['phylum'])?' WHERE phylum=\''. $_SESSION['entry']['phylum'] .'\'':''). ' ORDER BY kingdom;', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
			while ($row=sql_fetch_array($result)) {
				print '<option value="'.$row['kingdom'].'"'.((!empty($_POST['sequence_organelle']) && ($_POST['sequence_organelle']==$row['kingdom']))?' selected="selected"':'').'>'.$row['genome'].'</option>';
			}
		}
		?></select><br />
          <h3>Reference sequence</h3>
          <div class="box">
            <label for="sequence_acc" class="required">Accession number</label>
            <input type="text" maxlength="16" name="sequence_acc" id="sequence_acc" title="" <?php print (!empty($_POST['sequence_acc'])?'value="'.$_POST['sequence_acc'].'"':'autocomplete="off"'); ?>/><br />
            <label for="sequence_begin">Restricted begin</label>
            <input type="text" maxlength="16" name="sequence_begin" id="sequence_begin" title="" <?php print (!empty($_POST['sequence_begin'])?'value="'.$_POST['sequence_begin'].'"':'autocomplete="off"'); ?>/><br />
            <label for="sequence_end">Restricted end</label>
            <input type="text" maxlength="16" name="sequence_end" id="sequence_end" title="" <?php print (!empty($_POST['sequence_end'])?'value="'.$_POST['sequence_end'].'"':'autocomplete="off"'); ?>/><br />
            <label for="sequence_strand">Use minus strand</label>
            <input type="checkbox" name="sequence_strand" id="sequence_strand" value="true" <?php print (!empty($_POST['sequence_strand'])?'checked="checked" ':''); ?>/><br />
            <label for="ext_databases">Other accessions</label>
            <input type="text" name="ext_databases" id="ext_databases" title="" <?php print (!empty($_POST['ext_databases'])?'value="'.$_POST['ext_databases'].'"':'autocomplete="off"'); ?>/><br />
          </div>
          <p><strong>or</strong></p>
          <div class="box">
            <label for="sequence_raw" class="required">Raw sequence</label>
            <textarea cols="60" rows="4" name="sequence_raw" id="sequence_raw"><?php print (!empty($_POST['sequence_raw'])?$_POST['sequence_raw']:''); ?></textarea><br />
            <label for="sequence_genetic" class="required">Genetic code</label>
            <select name="sequence_genetic" id="sequence_genetic"><option></option><?php
		$result=@sql_query('SELECT id, translation FROM translation WHERE id>0 ORDER BY id;', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
			while ($row=sql_fetch_array($result)) {
				print '<option value="'.$row['id'].'"'.((!empty($_POST['sequence_genetic']) && ($_POST['sequence_genetic']==$row['id']))?' selected="selected"':'').'>'.$row['translation'].'</option>';
			}
		}
		?></select><br />
            <label for="sequence_acid" class="required">Sequence type</label>
            <select name="sequence_acid" id="sequence_acid"><option></option><?php
		$result=array('DNA', 'RNA', 'NA', 'tRNA', 'rRNA', 'mRNA', 'uRNA', 'snRNA', 'snoRNA', 'ss-DNA', 'ss-RNA', 'ss-NA', 'ds-DNA', 'ds-RNA', 'ds-NA', 'ms-DNA', 'ms-RNA', 'ms-NA');
		foreach ($result as $item) {
			print '<option value="'.$item.'"'.((!empty($_POST['sequence_acid']) && ($_POST['sequence_acid']==$item))?' selected="selected"':'').'>'.$item.'</option>';
		}
		?></select><br />
            <label for="sequence_topology" class="required">Molecule topology</label>
            <select name="sequence_topology" id="sequence_topology"><?php
		$result=array('linear', 'circular');
		foreach ($result as $item) {
			print '<option value="'.$item.'"'.((!empty($_POST['sequence_topology']) && ($_POST['sequence_topology']==$item))?' selected="selected"':'').'>'.$item.'</option>';
		}
		?></select><br />
          </div>
          <br /><br />
          <input type="hidden" name="step" value="2" /><input value="Next" type="submit" />
        </div>
      </form>
<?php
	} elseif ($step==3) {
?>
      <p>&nbsp;</p>
      <h2>Recoding event(s) <small>step 3 of 6</small></h2>
      <div class="box"><code>
<?php print wordwrap(wordwrap($_SESSION['entry']['sequence']['sequence'], 10, ' ', true), 77, "\n", true); ?>
      </code></div>
      <form method="post" id="signin" action="<?php print $config['server'] . 'new'; ?>">
        <div>
          <div id="read_event" style="display: none">
            <div class="box">
              <label for="event_type" class="required">Recoding event</label>
              <select name="event_type[]" id="event_type"><option></option><?php
		$result=@sql_query('SELECT event, name FROM events WHERE event!=5 AND event>0 ORDER BY name;', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
			while ($row=sql_fetch_array($result)) {
				print '<option value="'.$row['event'].'">'.$row['name'].'</option>';
			}
		}
		?></select><br />
              <label for="event_site" class="required">Recoding site**</label>
              <input type="text" name="event_site[]" id="event_site" title="" /><br />
              <label for="event_stop" class="required">Stop codon***</label>
              <input type="text" name="event_stop[]" id="event_stop" title="" /><br />
              <label for="event_name" class="required">Gene/Locus Name</label>
              <input type="text" name="event_name[]" id="event_name" title="" /><br />
              <label for="event_description">Description</label>
              <input type="text" name="event_description[]" id="event_description" title="" /><br />
              <label for="event_experimental">Experimental</label>
              <input type="checkbox" name="event_experimental[]" id="event_experimental" value="true"/><br />
              <label for="event_function">Recoding function</label>
              <input type="text" name="event_function[]" id="event_function" title="" /><br />
<!--              <img onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="<?php print $config['server']; ?>images/minus.png" alt="" /> remove this event<br /> -->
            </div>
            <br />
          </div>
          <h3>Locus</h3>
          <label for="locus_name" class="required">Locus Name</label>
          <input type="text" name="locus_name" id="locus_name" title="" <?php print (!empty($_POST['locus_name'])?'value="'.$_POST['locus_name'].'" ':'autocomplete="off" '); ?>/><br />
          <label for="locus_comments">Locus comment</label>
          <textarea cols="60" rows="4" name="locus_comments" id="locus_comments"><?php print (!empty($_POST['locus_comments'])?$_POST['locus_comments']:''); ?></textarea><br />
          <h3>Conventional translation</h3>
          <label for="gene_name" class="required">Gene Name</label>
          <input type="text" name="gene_name" id="gene_name" title="" <?php print (!empty($_POST['gene_name'])?'value="'.$_POST['gene_name'].'" ':'autocomplete="off" '); ?>/><br />
          <label for="gene_start" class="required">Start codon*</label>
          <input type="text" name="gene_start" id="gene_start" title="" <?php print (!empty($_POST['gene_start'])?'value="'.$_POST['gene_start'].'" ':'autocomplete="off" '); ?>/><br />
          <label for="gene_yes" class="required">Translated?</label>
          <input type="checkbox" name="gene_yes" id="gene_yes" value="true" <?php print ((!empty($_POST['gene_yes']) || !isset($_POST['gene_start']))?'checked="checked" ':''); ?>/><br />
          <label for="gene_description">Description</label>
          <input type="text" name="gene_description" id="gene_description" title="" <?php print (!empty($_POST['gene_description'])?'value="'.$_POST['gene_description'].'" ':'autocomplete="off" '); ?>/><br />
          <h3>Recoding</h3>
<!--          <img id="moreFields" src="<?php print $config['server']; ?>images/plus.png" alt="" /> add more events<br /><br /> -->
          <span id="write_event"></span>
          * Specify the location of the start codon by either typing the position of the <strong>first</strong> nucleotide of the start codon (e.g. 1), or by copying at least 25 nucleotides of the sequence <strong>immediately before</strong> the start codon (e.g. <span class="eg">AGCTGCTCCA</span>ATG...)<br />
          ** Specify the location of the recoding site. This information is required for the frameshift events, but not for the selenoproteins, where the position of the final stop codon is then required. Specify either the position of the <strong>first</strong> nucleotide of the recoding site (i.e. <strong>the nucleotide read twice for the -1 frameshift, the codon inducing the +1 frameshift, or the translated stop codon in the case of the readthrough</strong>), or by copying at least 25 nucleotides of the sequence <strong>immediately before</strong> the site.<br />
          *** Specify the location of the stop codon (required for the selenoproteins only) by either typing the position of the <<strong>first</strong> nucleotide of the stop codon (e.g. 31), or by copying at least 25 nucleotides of the sequence <strong>immediately before</strong> the stop codon (e.g. <span class="eg">AGCTGCTCCA</span>TAG...)<br />
          <br />
          <input type="hidden" name="step" value="3" /><input value="Next" type="submit" />
        </div>
      </form>
      <script type="text/javascript">
//<![CDATA[
var counter = 0;
function init() {
/* document.getElementById('moreFields').onclick = moreFields; */
 moreFields();
}
function moreFields() {
 counter++;
 var newFields = document.getElementById('read_event').cloneNode(true);
 newFields.id = '';
 newFields.style.display = 'block';
 var newField = newFields.childNodes;
 for (var i=0;i<newField.length;i++) {
  var theName = newField[i].name
  if (theName)
   newField[i].name = theName + counter;
  }
 var insertHere = document.getElementById('write_event');
 insertHere.parentNode.insertBefore(newFields,insertHere);
}
init();
//]]>
      </script>
<?php
	} elseif ($step==4) {
?>
      <p>&nbsp;</p>
      <h2>Secondary structure(s) <small>step 4 of 6</small></h2>
      <div class="box"><code>
<?php print wordwrap(wordwrap($_SESSION['entry']['sequence']['sequence'], 10, ' ', true), 77, "\n", true); ?>
      </code></div>
      <form method="post" id="signin" action="<?php print $config['server'] . 'new'; ?>">
        <div>
          <div id="read_structure" style="display: none">
            <div class="box">
              <label for="structure_type" class="required">Type</label>
              <select name="structure_type[]" id="structure_type"><option></option><?php
		$result=@sql_query('SELECT structure, name FROM structures WHERE structure=1 OR structure=2 OR structure>=6 ORDER BY structure;', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
			while ($row=sql_fetch_array($result)) {
				print '<option value="'.$row['structure'].'">'.$row['name'].'</option>';
			}
		}
		?></select><br />
              <label for="structure_location" class="required">Position*</label>
              <input type="text" name="structure_location[]" id="structure_location" title="" /><br />
              <label for="structure_string" class="required">Sequence/structure**</label>
              <input type="text" name="structure_string[]" id="structure_string" title="" /><br />
              <img onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="<?php print $config['server']; ?>images/minus.png" alt="" /> remove this structure<br />
            </div>
            <br />
          </div>
          <img id="moreFields" src="<?php print $config['server']; ?>images/plus.png" alt="" /> add more structure<br /><br />
          <span id="write_structure"></span>
          * Location of the structure/motif. Specify either the position of the <strong>first</strong> nucleotide of the structure/motif, or by copying at least 25 nucleotides of the sequence <strong>immediately before</strong> the region.<br />
          ** Indicate the <strong>nucleotide sequence</strong> of the motif or the description of the secondary structure using the <strong>bracket notation</strong> (Vienna format: ., (), {} and [] only).<br />
          <br />
          <input type="hidden" name="step" value="4" /><input value="Next" type="submit" />
        </div>
      </form>
      <script type="text/javascript">
//<![CDATA[
var counter = 0;
function init() {
 document.getElementById('moreFields').onclick = moreFields;
 moreFields();
}
function moreFields() {
 counter++;
 var newFields = document.getElementById('read_structure').cloneNode(true);
 newFields.id = '';
 newFields.style.display = 'block';
 var newField = newFields.childNodes;
 for (var i=0;i<newField.length;i++) {
  var theName = newField[i].name
  if (theName)
   newField[i].name = theName + counter;
  }
 var insertHere = document.getElementById('write_structure');
 insertHere.parentNode.insertBefore(newFields,insertHere);
}
init();
//]]>
      </script>
<?php
	} elseif ($step==5) {
?>
      <p>&nbsp;</p>
      <h2>References and comments <small>step 5 of 6</small></h2>
      <form method="post" id="signin" action="<?php print $config['server'] . 'new'; ?>">
        <div>
          <div id="read_pubmed" style="display: none">
            <div class="box">
              <label for="reference">PubMedID</label>
              <input type="text" name="reference[]" id="reference" title="" /><br />
              <img onclick="this.parentNode.parentNode.removeChild(this.parentNode);" src="<?php print $config['server']; ?>images/minus.png" alt="" /> remove this reference<br />
            </div>
            <br />
          </div>
          <img id="moreFields" src="<?php print $config['server']; ?>images/plus.png" alt="" /> add more reference<br /><br />
          <span id="write_pubmed"></span>
          <label for="comments">Comments</label>
          <textarea cols="60" rows="4" name="comments" id="comments"><?php print (!empty($_POST['comments'])?$_POST['comments']:''); ?></textarea><br /><br />
          <input type="hidden" name="step" value="5" /><input value="Next" type="submit" />
        </div>
      </form>
      <script type="text/javascript">
//<![CDATA[
var counter = 0;
function init() {
 document.getElementById('moreFields').onclick = moreFields;
 moreFields();
}
function moreFields() {
 counter++;
 var newFields = document.getElementById('read_pubmed').cloneNode(true);
 newFields.id = '';
 newFields.style.display = 'block';
 var newField = newFields.childNodes;
 for (var i=0;i<newField.length;i++) {
  var theName = newField[i].name
  if (theName)
   newField[i].name = theName + counter;
  }
 var insertHere = document.getElementById('write_pubmed');
 insertHere.parentNode.insertBefore(newFields,insertHere);
}
init();
//]]>
      </script>
<?php
	} elseif ($step==6) {
?>
      <p>&nbsp;</p>
<?php if (!empty($err)) { print "      <div id=\"error\"><div>$err</div></div>\n"; } ?>
      <h2>Review <small>step 6 of 6</small></h2>
      <form method="post" id="signin" action="<?php print $config['server'] . 'new'; ?>">
        <div>
          <input type="hidden" name="step" value="6" /><input value="Submit" type="submit" />
        </div>
      </form>
<?php
		print "      <table summary=\"Recode2 details\"><tbody>\n";
		print '        <tr><td>Locus name</td><td>'.$_SESSION['entry']['events']['name']."</td></tr>\n";
		if (!empty($_SESSION['entry']['events']['comments'])) { print '        <tr><td>Description</td><td>'.$_SESSION['entry']['events']['comments']."</td></tr>\n"; }
		print "      </tbody></table>\n";
		print "      <h2>Organism</h2>\n      <table summary=\"Organism details\"><tbody>\n";
		$result=@sql_query('SELECT phylum, genome FROM kingdoms WHERE kingdom='.intval($_SESSION['entry']['sequence']['organelle']).';', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
			$row=sql_fetch_array($result);
			print '        <tr><td>Genomic class</td><td>'.$row['phylum']. ' ('.$row['genome'] . ")</td></tr>\n";
		}
		print '        <tr><td>Organism</td><td><em>'.$_SESSION['entry']['species']."</em></td></tr>\n";
		print "      </tbody></table>\n";
		print "      <h2>Sequence</h2>\n      <table summary=\"Sequence details\"><tbody>\n";
		if (!empty($_SESSION['entry']['variant'])) print '        <tr><td>Variant</td><td>'.$_SESSION['entry']['variant']."</td></tr>\n";
		print '        <tr><td>GenBank IDs</td><td>'.(!empty($_SESSION['entry']['sequence']['reference'])?$_SESSION['entry']['sequence']['reference'].(!empty($_SESSION['entry']['sequence']['ext_databases'])?','.$_SESSION['entry']['sequence']['ext_databases']:''):'')."</td></tr>\n";
		print '        <tr><td>Sequence</td><td>'.$_SESSION['entry']['sequence']['molecule'].' of ' .$_SESSION['entry']['sequence']['size']. " nucleotides</td></tr>\n";
		$seqmap=array();
		$seqmap=mymap($seqmap,  $_SESSION['entry']['events']['start'], 3, 'start');
		$seqmap=mymap($seqmap,  $_SESSION['entry']['events']['end']+1, 3, 'stop');
		if (!empty($_SESSION['entry']['structure'])) {
			print '        <tr><td>Organisation</td><td>';
			foreach ($_SESSION['entry']['structure'] as $item) {
				$result=@sql_query('SELECT name FROM structures WHERE structure='.intval($item['type']).';', $sql);
				if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
					$row=sql_fetch_array($result);
					print $row['name'].'<br />';
				}
			}
			print "</td></tr>\n";
		}
		print "      </tbody></table>\n      <h2>Recoding event(s)</h2>\n";
		foreach ($_SESSION['entry']['events'] as $item) {
			if (is_array($item) && !empty($item['type'])) {
				print "      <table summary=\"Recode2 details\"><tbody>\n";
				$result=@sql_query('SELECT name FROM events WHERE event='.intval($item['type']).';', $sql);
				if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
					$row=sql_fetch_array($result);
					if ($item['type'] != 4 && $item['type'] != 5) {
						print '        <tr><td>Event type</td><td>'.$row['name'].' (position: '.number_format($item['site']).")</td></tr>\n";
					} else {
						preg_match_all('/(\d+)\.\./', $item['modification'], $position, PREG_PATTERN_ORDER);
						print '        <tr><td>Event type</td><td>'.$row['name'].' (position'.(count($position[1])>1?'s':'').': '.implode(', ', $position[1]) . ")</td></tr>\n";
					}
					if (!empty($item['description'])) { print '        <tr><td>Description</td><td>'.$item['function']."</td></tr>\n"; }
					print "      </tbody></table>\n";
					switch ($item['type']) {
					case 1: //-1 frameshifting
						$seqmap=mymap($seqmap, $item['site']-6, 7, 'site');
						break;
					case 2: //+1 frameshifting
						$seqmap=mymap($seqmap, $item['site']-3, 6, 'site');
						break;
					case 3: //Readthrough
						$seqmap=mymap($seqmap, $item['site'], 3, 'site');
						break;
					case 4: //Selenocysteine
					case 5: //Pyrolysine
						foreach ($position[1] as $pos) { $seqmap=mymap($seqmap, $pos, 3, 'site'); }
						break;
					}
				}
			}
		}
		if (!empty($_SESSION['entry']['structure'])) {
			$colour=array('blue', 'green', 'red');
			foreach ($_SESSION['entry']['structure'] as $item) {
				if ($item['type'] <6) {
					$i=0;
					foreach ( explode(';', $item['description']) as $stem) {
						foreach ( explode(',', $stem) as $strand) {
							$coord=explode('|', $strand, 3);
							$seqmap=mymap($seqmap, $coord[0], $coord[2], (( $item['type']==2 ) ? 'strong' : (( $item['type']==1 ) ? 'light' : $colour[$i])));
						}
						$i++;
					}
				} else {
					$coord=explode('|', $item['description'], 4);
					$seqmap=mymap($seqmap, $coord[0], $coord[2], 'yellow');
				}
			}
		}
		print "      <div class=\"box\"><code>\n" . mysequence(wordwrap(wordwrap($_SESSION['entry']['sequence']['sequence'], 10, ' ', true), 77, "\n", true), $seqmap) . "      </code></div>\n";
		print "      <h2>Product(s)</h2>\n";
		foreach ($_SESSION['entry']['events'] as $key => $item) {
			if (is_array($item) && !empty($item['yes'])) {
				print "      <table summary=\"Recode2 details\"><tbody>\n";
				print '        <tr><td>Product</td><td>'.$item['name']."</td></tr>\n";
				if (!empty($item['description'])) print '        <tr><td>Description</td><td>'.$item['description']."</td></tr>\n";
				if (!empty($item['modification'])) print '        <tr><td>Modifications</td><td>'.$item['modification']."</td></tr>\n";
				print "      </tbody></table>\n";
				if (!empty($item['protein'])) {
?>
      <div class="box"><code>
<?php
					print wordwrap(wordwrap($item['protein'], 10, ' ', true), 77, "\n", true) . "\n";
?>
      </code></div>
<?php
				}
			}
		}
		if (!empty($_SESSION['entry']['references'])) {
			print "      <h2>Publication(s)</h2>\n      <ul>\n";
			foreach ($_SESSION['entry']['references'] as $item) {
				print '        <li><strong>' . $item['authors']. '</strong>. ('. $item['year'] .') ' . $item['title'] . '. <em>' . $item['journal'] ."</em></li>\n";
			}
			print "      </ul>\n";
		}
?>
      <form method="post" id="signin" action="<?php print $config['server'] . 'new'; ?>">
        <div>
          <input type="hidden" name="step" value="6" /><input value="Submit" type="submit" />
        </div>
      </form>
<?php
	}
	htmlfooter();
} else {
	header('Location: ' . $config['server'] );
	exit;
}
?>
