<?php
require_once 'includes/main.inc';

if (!empty($_SESSION['login']) && strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'xh')) $debug=get_switch('debug');

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

if (!empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server']) && !empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=3)) {
	if (!empty($_GET) && ($_SESSION['login']['rights']>=8) && !empty($_GET['reviewed'])) {
		$result = @sql_query('SELECT id, reviewed FROM blog WHERE id_recode=\''.sql_escape_string(urldecode($_GET['recode'])).'\' AND id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['reviewed'])))) . '\';', $sql);
		if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
			$row=sql_fetch_array($result);
			$result = @sql_query('UPDATE blog SET reviewed=\''. ((!empty($row['reviewed']) && $row['reviewed']=='f')?'t':'f').'\' WHERE id_recode=\''.sql_escape_string(urldecode($_GET['recode'])).'\' AND id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['reviewed'])))). '\';', $sql);
		}
	} elseif (!empty($_GET) && ($_SESSION['login']['rights']>=8) && !empty($_GET['remove'])) {
		$result = @sql_query('SELECT id FROM blog WHERE id_recode=\''.sql_escape_string(urldecode($_GET['recode'])).'\' AND id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
		if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
			$result = @sql_query('DELETE FROM blog WHERE id_recode=\''.sql_escape_string(urldecode($_GET['recode'])).'\' AND id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
		}
	} elseif (!empty($_POST) && !empty($_POST['comments'])) {
		$result = @sql_query('INSERT INTO blog (author, comments, id_recode, posted, reviewed) VALUES (\'' . sql_escape_string($_SESSION['login']['username']) . '\',\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['comments']))), ENT_QUOTES, 'ISO8859-1')) . '\',\'' . sql_escape_string(urldecode($_GET['recode'])) . '\', NOW(), \''.(($_SESSION['login']['rights']>7)?'t':'f').'\');', $sql);
		if (!strlen($r = sql_last_error($sql))) {
			unset( $_POST );
		}
	} elseif (!empty($_POST) && isset($_POST['review']) && ($_SESSION['login']['rights']>=8)) {
		if (intval($_POST['review'])==666) {
			$result = @sql_query('SELECT id FROM recode2 WHERE id_recode=\''.sql_escape_string(urldecode($_GET['recode'])).'\';', $sql);
			if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1) && ($_SESSION['login']['rights']>=9)) {
				$result = @sql_query('DELETE FROM recode2 WHERE id_recode=\''.sql_escape_string(urldecode($_GET['recode'])).'\';', $sql);
				if (!strlen($r = sql_last_error($sql))) {
					header('Location: ' . $config['server'] );
					exit;
				}
			}
		} else {
			$result = @sql_query('SELECT id FROM recode2 WHERE id_recode=\''.sql_escape_string(urldecode($_GET['recode'])).'\';', $sql);
			if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
				$row=sql_fetch_array($result);
				$result = @sql_query('UPDATE recode2 SET status=' . intval($_POST['review']) . ' WHERE id_recode=\''.sql_escape_string(urldecode($_GET['recode'])).'\';', $sql);
				if (intval($_POST['review'])==10) {
					$result = @sql_query('INSERT INTO revisions (author,comments,id_recode,revised) VALUES (\'' . sql_escape_string($_SESSION['login']['username']) . '\',\'Mark the entry as removed\',\'' . sql_escape_string(urldecode($_GET['recode'])) . '\', NOW());', $sql);
					if (!strlen($r = sql_last_error($sql)) && ($_SESSION['login']['rights']<9)) {
						header('Location: ' . $config['server'] );
						exit;
					}
				}
			}
		}
	}
}

htmlheader(false, true);
?>
      <p>&nbsp;</p>
<?php
if (!empty($_GET['recode'])) {
	$recodeid=urldecode($_GET['recode']);
	$result=@sql_query('SELECT a.id_recode, a.locus, a.description, a.legacy, d.name, a.organism, b.latin, b.acronym, c.phylum, c.genome, a.status FROM recode2 AS a, organisms AS b, kingdoms AS c, status AS d WHERE a.id_recode=\''.sql_escape_string($recodeid).'\' AND a.organism=b.taxonid AND a.kingdom=c.kingdom AND a.status=d.status;', $sql);
	if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
		$row=sql_fetch_array($result);
		//  print '      <h2>Details for "'.$row['id_recode'].'"'.((!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=5))?'  <small>[<a href="'.$config['server'].'recode/'.$recodeid.'/edit/" title="edit" rel="nofollow">edit</a>]</small>':'')."</h2>\n      <table summary=\"Recode2 details\"><tbody>\n";
		print '      <h2>Details for "'.$row['id_recode']."\"</h2>\n      <table summary=\"Recode2 details\"><tbody>\n";
		print '        <tr><td>Accession number</td><td>'.$row['id_recode'].' [<a href="' . $config['server'] . 'export/' . urlencode($row['id_recode']) . "\">export</a>]</td></tr>\n";
		print '        <tr><td>Locus name</td><td>'.$row['locus']."</td></tr>\n";
		if (!empty($row['legacy'])) { print '        <tr><td>Recode<sup class="r2">1</sup> name</td><td>'.$row['legacy']."</td></tr>\n"; }
		if (!empty($row['description'])) { print '        <tr><td>Description</td><td>'.$row['description']."</td></tr>\n"; }
		print '        <tr><td>Recode status</td><td>';
		if (!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8)) {
			print '<form action="'.$config['server'].'recode/'.$recodeid.'" method="post"><div><select name="review" onchange="this.form.submit()">';
			$result2=@sql_query('SELECT name, status, description FROM status ORDER BY status;', $sql);
			if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>1)) {
				while ($row2=sql_fetch_array($result2)) {
					print '<option value="'.$row2['status'].'"'.(($row2['status']==$row['status'])?' selected="selected"':'').'>'.$row2['name'].'</option>';
				}
			}
			print (($_SESSION['login']['rights']>=9)?'<option value="666">Erase!</option>':'').'</select></div></form>';
		} else {
			print $row['name'];
		}
		print "</td></tr>\n";
		print "      </tbody></table>\n";
		print "      <h2>Organism</h2>\n      <table summary=\"Organism details\"><tbody>\n";
		print '        <tr><td>Genomic class</td><td>'.$row['phylum']. ' ('.$row['genome'].")</td></tr>\n";
		print '        <tr><td>Organism</td><td><em>'.$row['latin'].'</em>'.(($row['organism']<0x40000000)?' [<a href="http://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?mode=Info&amp;id=' . $row['organism'] . '" rel="nofollow">details</a>]':'')."</td></tr>\n";
		if (!empty($row['acronym'])) { print '        <tr><td>Acronym</td><td>'.$row['acronym']."</td></tr>\n"; }
		print "      </tbody></table>\n";

		$result2=@sql_query('SELECT id, variant, ext_databases, acid_type, length, annotation, sequence, coordinates FROM molecules WHERE id_recode=\''.sql_escape_string($row['id_recode']).'\';', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
			print '      <h2>Sequence'.((sql_num_rows($result2)>1)?'s':'')."</h2>\n      <table summary=\"Sequence details\"><tbody>\n";
			while ($row2=sql_fetch_array($result2)) {
				if (!empty($row2['variant'])) print '        <tr><td>Variant</td><td>'.$row2['variant']."</td></tr>\n";
				print '        <tr><td>GenBank IDs</td><td><a href="http://www.ncbi.nlm.nih.gov/sites/entrez?cmd=Search&amp;db=nuccore&amp;term='.urlencode($row2['ext_databases']).'" rel="nofollow">'.$row2['ext_databases']."</a></td></tr>\n";
				print '        <tr><td>Sequence</td><td>'.$row2['acid_type'].' of ' .intval($row2['length']). ' nucleotides [<span class="over" id="sequence'.intval($row2['id'])."T\">sequence</span>]<script type=\"text/javascript\">\n/*<![CDATA[*/\n\$(document).ready(function () {\$(\"#sequence".intval($row2['id']).'").hide();$("#sequence'.intval($row2['id']).'T").click(function () {$("#sequence'.intval($row2['id'])."\").slideToggle();});});\n/*]]>*/</script></td></tr>\n";
				if (!empty($row2['annotation'])) { print '        <tr><td>Annotation</td><td>'.implode('<br/>', explode('|', $row2['annotation']))."</td></tr>\n"; }
				if (!empty($row2['sequence']) && !empty($row2['coordinates'])) {
					$coordinate=$row2['coordinates'];
					$sequence=bzdecompress(base64_decode($row2['sequence']));
					$seqmap=array();
					if (preg_match('/^(\d+)\..*\.(\d+)$/', $coordinate, $matches)) {
						$seqmap=mymap($seqmap,  $matches[1], 3, 'start');
						$seqmap=mymap($seqmap,  $matches[2]+1, 3, 'stop');
					}
				}
				$result3=@sql_query('SELECT b.name, count(b.name) as number FROM morphorna AS a, structures AS b WHERE a.id_molecule='.intval($row2['id']).' AND a.id_recode=\''.sql_escape_string($row['id_recode']).'\' AND a.structure=b.structure GROUP by b.name ORDER BY b.name;', $sql);
				if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result3)>0)) {
					print '        <tr><td>Organisation</td><td>';
					while ($row3=sql_fetch_array($result3)) {
						print $row3['name']. ' ('.intval($row3['number']).')';
						if ($row3['name']!='Unknown' && $row3['name']!='Trans/Interaction' && $row3['name']!='Sequence') {
							$result4=@sql_query('SELECT a.id, a.structure, a.description FROM morphorna AS a, structures AS b WHERE a.id_molecule='.intval($row2['id']).' AND a.id_recode=\''.sql_escape_string($row['id_recode']).'\' AND b.name=\''.sql_escape_string($row3['name']).'\' AND a.structure=b.structure ORDER BY id;', $sql);
							if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result4)>0)) {
								while ($row4=sql_fetch_array($result4)) {
									// ignore missing 'SECIS structure'!
									if (($row4['structure']!=7) && (strpos($row4['structure'], '(')===false)) {
										print ' [<a class="lightbox" type="image/gif" href="' . $config['server'] . 'rna/' . urlencode($row4['id']) . '" title="' . $row3['name'] . '" rel="nofollow">see structure</a>]';
									}
								}
							}
						}
						print '<br />';
					}
					print "</td></tr>\n";
				}
				print "        <script type=\"text/javascript\">\n/*<![CDATA[*/\n\$(function() {\$('a.lightbox').lightBox();});\n/*]]>*/</script>      </tbody></table>\n";

				$result3=@sql_query('SELECT a.id_product, b.name, a.position, a.model, a.event, a.description, a.downstream, c.modification FROM recoding AS a, events AS b, products AS c WHERE c.id_product=a.id_product AND c.id_molecule='.intval($row2['id']).' AND a.id_recode=\''.sql_escape_string($row['id_recode']).'\' AND a.event=b.event;', $sql);
				if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result3)>0)) {
					print '      <h2>Recoding event'.((sql_num_rows($result3)>1)?'s':'')."</h2>\n";
					while ($row3=sql_fetch_array($result3)) {
						print "      <table summary=\"Recode2 details\"><tbody>\n";
						if ($row3['event'] != 4 && $row3['event'] != 5) {
							print '        <tr><td>Event type</td><td>'.$row3['name'].' (position: '.number_format(intval($row3['position'])).")</td></tr>\n";
						} else {
							preg_match_all('/(\d+)\.\./', $row3['modification'], $position, PREG_PATTERN_ORDER);
							print '        <tr><td>Event type</td><td>'.$row3['name'].' (position'.(count($position[1])>1?'s':'').': '.implode(', ', $position[1]) . ")</td></tr>\n";
						}
						if (!empty($row3['description'])) { print '        <tr><td>Description</td><td>'.$row3['description']."</td></tr>\n"; }
						if (!empty($row3['model']) && ($row3['model'] != 'none')) print '        <tr><td>Models</td><td>'.implode('<br/>', explode('|', $row3['model']))."</td></tr>\n";
						print "      </tbody></table>\n";
						if (!empty($sequence)) {
							switch ($row3['event']) {
							case 1: //-1 frameshifting
								$seqmap=mymap($seqmap, $row3['position']-6, 7, 'site');
								break;
							case 2: //+1 frameshifting
								$seqmap=mymap($seqmap, $row3['position']-3, 6, 'site');
								break;
							case 3: //Readthrough
								$seqmap=mymap($seqmap, $row3['position'], 3, 'site');
								break;
							case 4: //Selenocysteine
							case 5: //Pyrolysine
								foreach ($position[1] as $pos) { $seqmap=mymap($seqmap, $pos, 3, 'site'); }
								break;
							case 6: //'Hopping ??
								$seqmap=mymap($seqmap, $row3['position']-3, 3, 'site');
								$seqmap=mymap($seqmap, $row3['position']+strlen($row3['downstream']), 3, 'site');
								break;
							}
						}
					}
				}
				if (!empty($sequence)) {
					$result4=@sql_query('SELECT id, structure, description FROM morphorna WHERE id_molecule='.intval($row2['id']).' AND id_recode=\''.sql_escape_string($row['id_recode']).'\' ORDER BY id, description DESC;', $sql);
					if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result4)>0)) {
						$colour=array('blue', 'green', 'red');
						while ($row4=sql_fetch_array($result4)) {
							if ($row4['structure'] <6) {
								$i=0;
								foreach ( explode(';', $row4['description']) as $stem) {
									foreach ( explode(',', $stem) as $strand) {
										$coord=explode('|', $strand, 3);
										$seqmap=mymap($seqmap, $coord[0], $coord[2], (( $row4['structure']==2 ) ? 'strong' : (( $row4['structure']==1 ) ? 'light' : $colour[$i])));
									}
									$i++;
								}
							} else {
								$coord=explode('|', $row4['description'], 4);
								$seqmap=mymap($seqmap, $coord[0], $coord[2], 'yellow');
							}
						}
					}
					print '      <div id="sequence'.intval($row2['id'])."\" class=\"box\"><code>\n" . mysequence(wordwrap(wordwrap($sequence, 10, ' ', true), 77, "\n", true), $seqmap) . "      </code></div>\n";
				}
				//products
				$result3=@sql_query('SELECT id_product, name, modification, description, sequence FROM products WHERE id_molecule='.intval($row2['id']).' AND  id_recode=\''.sql_escape_string($row['id_recode']).'\';', $sql);
				if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result3)>0)) {
					print '      <h2>Product'.((sql_num_rows($result3)>1)?'s':'')."</h2>\n";
					while ($row3=sql_fetch_array($result3)) {
						print "      <table summary=\"Recode2 details\"><tbody>\n";
						print '        <tr><td>Product</td><td>'.$row3['name']."</td></tr>\n";
						if (!empty($row3['description'])) print '        <tr><td>Description</td><td>'.$row3['description']."</td></tr>\n";
						if (!empty($row3['modification'])) print '        <tr><td>Modifications</td><td>'.$row3['modification']."</td></tr>\n";
						print "      </tbody></table>\n";
						if (!empty($row3['sequence'])) {
?>
      <div class="box"><code>
<?php
							$row3['sequence']=bzdecompress(base64_decode($row3['sequence']));
							print wordwrap(wordwrap($row3['sequence'], 10, ' ', true), 77, "\n", true) . "\n";
?>
      </code></div>
<?php
						}
					}
				}
			}
		}

		$result2=@sql_query('SELECT authors, title, journal, theyear, doi FROM journals WHERE id_recode=\''.sql_escape_string($row['id_recode']).'\' ;', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
			print '      <h2>Publication'.((sql_num_rows($result2)>1)?'s':'')."</h2>\n      <ul>\n";
			while ($row2=sql_fetch_array($result2)) {
				print '        <li><strong>' . $row2['authors']. '</strong>. ('. $row2['theyear'] .') ' . $row2['title'] . '. <em>' . $row2['journal'] .'</em>.' . (!empty($row2['doi'])? ' [<a href="' . ((substr($row2['doi'], 0, 4)=='doi:')? 'http://dx.doi.org/' . substr($row2['doi'], 4) : ((substr($row2['doi'], 0, 5)=='isbn:')? 'http://books.google.com/books?q=isbn%3A' . substr($row2['doi'], 5) : 'http://www.ncbi.nlm.nih.gov/pubmed/' . intval(substr($row2['doi'], 5)) ) ). '" rel="nofollow">abstract</a>]' :'') . "</li>\n";
			}
			print "      </ul>\n";
		}

		if (isset($debug) && $debug) {
			//analyses
			$result2=@sql_query('SELECT program, version, analysed, parameter FROM analyses WHERE id_recode=\''.sql_escape_string($row['id_recode']).'\' ;', $sql);
			if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
				print '      <h2>Analyse'.((sql_num_rows($result2)>1)?'s':'')."</h2>\n      <ul>\n";
				while ($row2=sql_fetch_array($result2)) {
					print '        <li><strong>' . $row2['analysed']. '</strong> - ' .$row2['program'].' '.$row2['version'] .(!empty($row2['parameter']) ? '<br />' . $row2['parameter'] . '.' : '') . "</li>\n";
				}
				print "      </ul>\n";
			}
			//revisions
			$result2=@sql_query('SELECT a.author, b.code, a.comments, a.revised FROM revisions AS a, users AS b WHERE a.id_recode=\''.sql_escape_string($row['id_recode']).'\' AND a.author=b.username;', $sql);
			if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
				print '      <h2>Revision'.((sql_num_rows($result2)>1)?'s':'')."</h2>\n      <ul>\n";
				while ($row2=sql_fetch_array($result2)) {
					print '        <li><strong>' . $row2['revised']. '</strong> - ' . htmlentities($row2['code'], ENT_QUOTES, 'ISO8859-1').$row2['author'] .'<br />' . $row2['comments'] . '.' . "</li>\n";
				}
				print "      </ul>\n";
			}
		}

		if (!empty($_SESSION['login']['rights']) ) {
			$result2=@sql_query('SELECT a.id, a.author, b.code, a.posted, a.comments, a.reviewed FROM blog AS a, users AS b WHERE id_recode=\'' . sql_escape_string($row['id_recode']) . '\' AND a.author=b.username ORDER BY a.posted DESC;', $sql);
			if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
				print '      <h2>Comment'.((sql_num_rows($result2)>1)?'s':'')."</h2>\n      <ul>\n";
				while ($row2=sql_fetch_array($result2)) {
					print '        <li><strong>' . $row2['posted']. '</strong> - ' . htmlentities($row2['code'], ENT_QUOTES, 'ISO8859-1').$row2['author'] .((!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8))?' <a class="review' . ((!empty($row2['reviewed']) && $row2['reviewed']=='t')?'on':'off') .'" href="' . $config['server'] . 'recode/'.urlencode($row['id_recode']).'/?reviewed='.urlencode($row2['id']).'" title="'. ((!empty($row2['reviewed']) && $row2['reviewed']=='t')?'unmark as reviewed" rel="nofollow"><span>&#9786;':'mark as reviewed"><span>&#9787;'). '</span></a><a class="remove" href="' . $config['server'] . 'recode/'.urlencode($row['id_recode']).'/?remove='.urlencode($row2['id']).'" title="remove this comment" rel="nofollow"><span>&#10008;</span></a>' :'') . '<br />' . $row2['comments'] . '.' . "</li>\n";
				}
				print "      </ul>\n";
			}

			if ($_SESSION['login']['rights']>=3) { ?>
      <div class="box">
        <form method="post" id="signin" action="<?php print $config['server'] . 'recode/' . urlencode($row['id_recode']); ?>/">
          <div>
            <label class="required">Authors</label>
            <?php print $_SESSION['login']['code'] . $_SESSION['login']['username']; ?><br />
            <label for="comments" class="required">Comments</label>
            <textarea rows="4" cols="60" name="comments" id="comments"><?php print (!empty($_POST['comments'])?stripslashes($_POST['comments']):''); ?></textarea><br /><br />
            <input value="Add comment" type="submit" />
          </div>
        </form>
      </div>
<?php
			}
		}
	} else {
		print "      <p>no result, yet!</p>\n";
	}
} else {
	print "      <p>no result, yet!</p>\n";
}
htmlfooter();
?>
