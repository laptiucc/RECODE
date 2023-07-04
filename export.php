<?php
require_once 'includes/main.inc';

function exportXML ($row) {
	global $sql;
		print " <recode>\n  <identity>\n";
		print '   <name comment="'.htmlentities($row['phylum'], ENT_QUOTES, 'UTF-8').' ('.htmlentities($row['genome'], ENT_QUOTES, 'UTF-8').')">'.htmlentities($row['locus'], ENT_QUOTES, 'UTF-8')."</name>\n";
		if (!empty($row['description'])) { print '   <description>'.htmlentities($row['description'], ENT_QUOTES, 'UTF-8')."</description>\n"; }
		print '   <taxonomy' .(($row['organism']<0x40000000)?' taxonid="'.intval($row['organism']).'"':'').">\n";
		if (!empty($row['synonym'])) { print '    <name'. (!empty($row['acronym'])?' comment="'.htmlentities($row['acronym'], ENT_QUOTES, 'UTF-8').'"':'').'>'.htmlentities($row['synonym'], ENT_QUOTES, 'UTF-8')."</name>\n"; }
		if (empty($row['synonym']) && !empty($row['acronym'])) { print '    <name comment="'.htmlentities($row['acronym'], ENT_QUOTES, 'UTF-8')."\" />\n"; }
		if (!empty($row['genus'])) { print '    <genus>'.htmlentities($row['genus'], ENT_QUOTES, 'UTF-8')."</genus>\n"; }
		print '    <species>'.htmlentities($row['latin'], ENT_QUOTES, 'UTF-8')."</species>\n";
		if (!empty($row['variant'])) { print '    <strain>'.htmlentities($row['variant'], ENT_QUOTES, 'UTF-8')."</strain>\n"; }
		print "   </taxonomy>\n";
		if (!empty($row['legacy'])) { print '   <database-id name="recode">'.htmlentities($row['legacy'], ENT_QUOTES, 'UTF-8')."</database-id>\n"; }
		print '   <database-id name="recode2">'.htmlentities($row['id_recode'], ENT_QUOTES, 'UTF-8')."</database-id>\n";
		print "  </identity>\n  <molecules>\n";
		$result2=@sql_query('SELECT id, variant, ext_databases, coordinates, sequence, genetic, acid_type, strand, length, topology, annotation FROM molecules WHERE id_recode=\''.sql_escape_string($row['id_recode']).'\';', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
			while ($row2=sql_fetch_array($result2)) {
				print '   <sequence id="seq'.intval($row2['id']).'" type="'.$row2['acid_type'].'"'.(!empty($row2['strand'])?' strand="'.((intval($row2['strand'])>0)?'plus':'minus').'"':'') . (!empty($row2['length'])?' length="'.intval($row2['length']).'"':'') . (!empty($row2['coordinates'])?' coordinates="'.htmlentities($row2['coordinates'], ENT_QUOTES, 'UTF-8').'"':'') .  (!empty($row2['genetic'])?' genetic="'.intval($row2['genetic']).'"':'') . (!empty($row2['topology'])?' circular="'.(($row2['topology']=='circular')?'true':'false').'"':'') .">\n";
				if (!empty($row2['variant'])) { print '    <name>'.htmlentities($row2['variant'], ENT_QUOTES, 'UTF-8')."</name>\n"; }
				print '    <seq-data'. (!empty($row2['ext_databases'])?' comment="'.htmlentities($row2['ext_databases'], ENT_QUOTES, 'UTF-8').'"':'').'>'.bzdecompress(base64_decode($row2['sequence']))."</seq-data>\n";
				if (!empty($row2['annotation'])) {
					print "    <annotation>\n";
					foreach (explode('|', $row2['annotation']) as $annotation) {
						if (strpos($annotation, ':')) {
							$modif=explode(':', $annotation);
							print '     <modification id="mod'.intval($modif[0]).'" position="'.intval($modif[0]).'">'.htmlentities($modif[1], ENT_QUOTES, 'UTF-8')."</modification>\n";
						}
						//??
						if (strpos($annotation, '[')) {
							$segment=explode(array('[', '-', ']'), $annotation);
							print '     <segment id="seg'.intval($segment[1]).'"><name>'.htmlentities($segment[0], ENT_QUOTES, 'UTF-8').'</name><base-id-5p>'.intval($segment[1]).'</base-id-5p><base-id-3p>'.intval($segment[2])."</base-id-3p></segment>\n";
						}
					}
					print "    </annotation>\n";
				}
				print "   </sequence>\n";
			}
		}
		$result2=@sql_query('SELECT a.id, b.name, a.description, a.id_molecule FROM morphorna AS a, structures AS b WHERE a.id_recode=\''.sql_escape_string($row['id_recode']).'\' AND a.structure=b.structure;', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
			while ($row2=sql_fetch_array($result2)) {
				print '   <structure id="str'.intval($row2['id']).'" sequence-id="seq'. intval($row2['id_molecule']). '" type="' .htmlentities($row2['name'], ENT_QUOTES, 'UTF-8'). "\">\n";
				switch ($row2['name']) {
				case 'Complex structure': //6
				case 'SECIS structure': //7
				case 'Pseudoknot structure': //8
					$segment=explode('|', $row2['description'], 4);
          // ignore missing 'SECIS structure'!
					if (empty($segment[3])) { $segment[3]=str_repeat('.',$segment[2]); }
					print "    <complex>\n     <segment><base-id-5p>".intval($segment[0]).'</base-id-5p><base-id-3p>'.intval($segment[1])."</base-id-3p></segment>\n     <brackets>".htmlentities($segment[3], ENT_QUOTES, 'UTF-8')."</brackets>\n    </complex>\n";
					break;
				case 'Base-pair': //?
					$segment=explode('|', $row2['description']);
					print '    <base-pair><base-id-5p>'.intval($segment[0]).'</base-id-5p><base-id-3p>'.intval($segment[1])."</base-id-3p></base-pair>\n";
					break;
				case 'Pseudoknot': //5
					print "    <pseudoknot>\n";
					foreach (explode(';', $row2['description']) as $key => $segment) {
						print '     <helix comment="helix_'.intval($key)."\">\n";
						$subsegment = explode(',', $segment);
						for ($i=0; $i< count($subsegment); $i=$i+2) {
							$stem[0]=explode('|', $subsegment[$i]);
							$stem[1]=explode('|', $subsegment[$i+1]);
							print '      <stem><base-id-5p>'.intval($stem[0][0]).'</base-id-5p><base-id-3p>'.intval($stem[1][0]).'</base-id-3p><length>'.intval($stem[0][2])."</length></stem>\n";
						}
						print "     </helix>\n";
					}
					print "    </pseudoknot>\n";
					break;
				case 'SECIS': //4
				case 'Stemloop': //3
					print "    <helix>\n";
					$segment = explode(',', $row2['description']);
					for ($i=0; $i< count($segment); $i=$i+2) {
						$stem[0]=explode('|', $segment[$i]);
						$stem[1]=explode('|', $segment[$i+1]);
						print '     <stem><base-id-5p>'.intval($stem[0][0]).'</base-id-5p><base-id-3p>'.intval($stem[1][0]).'</base-id-3p><length>'.intval($stem[0][2])."</length></stem>\n";
					}
					print "    </helix>\n";
					break;
				case 'Sequence': //2
				case 'Trans/Interaction':  //1
				case 'Unknown': //0
					$segment=explode('|', $row2['description']);
					print '    <single-strand><segment><base-id-5p>'.intval($segment[0]).'</base-id-5p><base-id-3p>'.intval($segment[1])."</base-id-3p></segment></single-strand>\n";
					break;
				}
				print "   </structure>\n";
			}
		}
		$result2=@sql_query('SELECT id, id_product, id_molecule, name, sequence, modification, coordinates, description FROM products WHERE id_recode=\''.sql_escape_string($row['id_recode']).'\';', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
			while ($row2=sql_fetch_array($result2)) {
				print '   <product id="'.$row2['id_product'].'" sequence-id="seq'. intval($row2['id_molecule']). '" sequence-reading="'. htmlentities($row2['coordinates'], ENT_QUOTES, 'UTF-8') .'"' . (!empty($row2['description'])?' comment="'.htmlentities($row2['description'], ENT_QUOTES, 'UTF-8').'"':'').">\n";
				print '    <name>'.htmlentities($row2['name'], ENT_QUOTES, 'UTF-8')."</name>\n";
				print '    <seq-data>'.bzdecompress(base64_decode($row2['sequence']))."</seq-data>\n";
				if (!empty($row2['modification'])) {
					foreach (explode('|', $row2['modification']) as $modification) {
						if (strpos($modification, ':')) {
							$modif=explode(':', $modification);
							print '    <modification id="mod'.intval($modif[0]).'" position="'.$modif[0].'">'.htmlentities($modif[1], ENT_QUOTES, 'UTF-8')."</modification>\n";
						}
					}
				}
				$result3=@sql_query('SELECT id FROM recoding WHERE id_recode=\''.sql_escape_string($row['id_recode']).'\' AND id_product=\''.sql_escape_string($row2['id_product']).'\';', $sql);
				if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result3)>0)) {
					while ($row3=sql_fetch_array($result3)) {
						print '    <recoding-event>rec'.intval($row3['id'])."</recoding-event>\n";
					}
				}
				print "   </product>\n";
			}
		}
		print "  </molecules>\n";
		$result2=@sql_query('SELECT a.id, a.id_product, b.name, a.position, a.codon, a.upstream, a.esite, a.asite, a.psite, a.downstream, a.model, a.experimental, a.description FROM recoding AS a, events AS b WHERE a.id_recode=\''.sql_escape_string($row['id_recode']).'\' AND a.event=b.event ORDER BY a.position;', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
			print "  <recodings>\n";
			while ($row2=sql_fetch_array($result2)) {
				print '   <recoding id="rec'.intval($row2['id']).'" protein-id="'.$row2['id_product'].'" status="'.htmlentities($row['name'], ENT_QUOTES, 'UTF-8').'" experimental="'.((!empty($row2['experimental']) && ($row2['experimental']=='t'))?'true':'false')."\">\n";
				print '    <type>'.htmlentities($row2['name'], ENT_QUOTES, 'UTF-8')."</type>\n";
				print '    <position length="'.strlen((!empty($row2['upstream'])?$row2['upstream']:'').(!empty($row2['esite'])?$row2['esite']:'').(!empty($row2['asite'])?$row2['asite']:'').(!empty($row2['psite'])?$row2['psite']:'').(!empty($row2['downstream'])?$row2['downstream']:'')).'">'.htmlentities($row2['position'], ENT_QUOTES, 'UTF-8')."</position>\n";
				if (!empty($row2['codon'])) { print '    <codon>'.htmlentities($row2['codon'], ENT_QUOTES, 'UTF-8')."</codon>\n"; }
				if (!empty($row2['upstream'])) { print '    <upstream>'.htmlentities($row2['upstream'], ENT_QUOTES, 'UTF-8')."</upstream>\n"; }
				if (!empty($row2['esite'])) { print '    <esite>'.htmlentities($row2['esite'], ENT_QUOTES, 'UTF-8')."</esite>\n"; }
				if (!empty($row2['asite'])) { print '    <asite>'.htmlentities($row2['asite'], ENT_QUOTES, 'UTF-8')."</asite>\n"; }
				if (!empty($row2['psite'])) { print '    <psite>'.htmlentities($row2['psite'], ENT_QUOTES, 'UTF-8')."</psite>\n"; }
				if (!empty($row2['downstream'])) { print '    <downstream>'.htmlentities($row2['downstream'], ENT_QUOTES, 'UTF-8')."</downstream>\n"; }
				if (!empty($row2['model'])) { foreach (explode('|', $row2['model']) as $model) { print '    <model>'.htmlentities($model, ENT_QUOTES, 'UTF-8')."</model>\n"; } }
				if (!empty($row2['description'])) { print '    <function>'.htmlentities($row2['description'], ENT_QUOTES, 'UTF-8')."</function>\n"; }
				print "   </recoding>\n";
			}
			print "  </recodings>\n";
		}
		$result2=@sql_query('SELECT id, authors, title, journal, theyear, doi FROM journals WHERE id_recode=\''.sql_escape_string($row['id_recode']).'\' ;', $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>0)) {
			print "  <references>\n";
			while ($row2=sql_fetch_array($result2)) {
				print '   <reference id="ref'.intval($row2['id'])."\">\n";
				foreach (explode(', ', $row2['authors']) as $author) { print '    <author>'.htmlentities($author, ENT_QUOTES, 'UTF-8')."</author>\n"; }
				print '    <title>'.htmlentities($row2['title'], ENT_QUOTES, 'UTF-8')."</title>\n";
				//?? J Bacteriol. 179(22):7118-7128   1
				if (preg_match('/(.*).\s(\w+)\(?(\w+)?\)?:(\S+)$/', $row2['journal'], $matched)!==false) {
					print '    <journal>'.$matched[1]."</journal>\n";
					print '    <volume>'.$matched[2]."</volume>\n";
					if (!empty($matched[3])) { print '    <issue>'.$matched[3]."</issue>\n"; }
				}
				if (strpos($row2['doi'], 'oi:')) {
					if (!empty($matched[4])) { print '    <pages>'.$matched[4]."</pages>\n"; }
					print '    <doi>'.substr($row2['doi'], 4)."</doi>\n";
				} elseif (strpos($row2['doi'], 'mid:')) {
					print '    <pubmed-id>'.substr($row2['doi'], 5)."</pubmed-id>\n";
					if (!empty($matched[4])) { print '    <pages>'.$matched[4]."</pages>\n"; }
				}
				print '    <date><year>'.intval($row2['theyear'])."</year></date>\n";
				print "   </reference>\n";
			}
			print "  </references>\n";
		}
		print " </recode>\n";
}

if (!empty($_GET['export'])) {
	$recodeid=urldecode($_GET['export']);
	$result=@sql_query('SELECT a.id_recode, a.legacy, a.locus, a.description, d.name, a.organism, b.latin, b.variant, b.acronym, b.synonym, b.genus, c.phylum, c.genome FROM recode2 AS a, organisms AS b, kingdoms AS c, status AS d WHERE a.id_recode=\''.sql_escape_string($recodeid).'\' AND a.organism=b.taxonid AND a.kingdom=c.kingdom AND a.status=d.status;', $sql);
	if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
		header('X-Powered-By: ' . $config['powered']);
		header('content-type: application/xml; charset=UTF-8');
		header('Content-Disposition: attachment; filename="recode2.'.$recodeid.'.xml";');
		print '<'.'?xml version="1.0" encoding="UTF-8" standalone="no"?'.">\n";
		print '<!DOCTYPE recodeml PUBLIC "-//Recode//DTD Recodeml 0.4//EN" "'.$config['master'].'dtd/recodeml-0.4.dtd"'.">\n";;
		print "<recodeml version=\"0.4\">\n";
		exportXML(sql_fetch_array($result));
		print "</recodeml>\n";
	}
}elseif (!empty($_GET['exportall'])) {
	$result=@sql_query('SELECT a.id_recode, a.legacy, a.locus, a.description, d.name, a.organism, b.latin, b.variant, b.acronym, b.synonym, b.genus, c.phylum, c.genome FROM recode2 AS a, organisms AS b, kingdoms AS c, status AS d WHERE a.status!=10 AND a.organism=b.taxonid AND a.kingdom=c.kingdom AND a.status=d.status;', $sql);
	if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
		set_time_limit(sql_num_rows($result));
		header('X-Powered-By: ' . $config['powered']);
		header('content-type: application/xml; charset=UTF-8');
		header('Content-Disposition: attachment; filename="recode2.'.date('Y-m-d').'.xml";');
		print '<'.'?xml version="1.0" encoding="UTF-8" standalone="no"?'.">\n";
		print '<!DOCTYPE recodeml PUBLIC "-//Recode//DTD Recodeml 0.4//EN" "'.$config['master'].'dtd/recodeml-0.4.dtd"'.">\n";;
		print "<recodeml version=\"0.4\">\n";
		while ($row=sql_fetch_array($result)) {
			exportXML($row);
		}
		print "</recodeml>\n";
	}
} elseif (!empty($_GET['sequence']) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server'])) {
	$sequenceid=urldecode($_GET['sequence']);
	$result=@sql_query('SELECT id, id_recode, variant,sequence FROM molecules WHERE id=\''.sql_escape_string($sequenceid).'\';', $sql);
	if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)==1)) {
		$row = sql_fetch_array($result);
		header('X-Powered-By: ' . $config['powered']);
		header('Content-Type: application/xhtml+xml; charset=iso-8859-1');
		print '<' . '?xml version="1.0" encoding="ISO-8859-1"?' . ">\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <meta name="robots" content="noindex,nofollow" />
  <link rel="shortcut icon" href="<?php print $config['server']; ?>/favicon.ico" />
  <title><?php print $row['id_recode'] . (!empty($row['variant'])?' (' . $row['variant'] . ')':''); ?></title>
  <style type="text/css">
/*<![CDATA[*/
body { background: #ffffff; padding: 0 0 6em 0; margin: 1em; text-align: center; color: #000000; font-size: 10pt; font-family: sans-serif; }
a{ color: #000099; text-decoration: none; }
a:hover{ text-decoration: underline; }
#dna { text-align: left; width: 30em; margin: 0 auto; }
#footer { text-align: center; }
/*]]>*/
  </style>
 </head>
 <body>
  <div id="dna">
<?php print '    <pre>' . wordwrap(wordwrap(bzdecompress(base64_decode($row['sequence'])), 10, ' ', 1), 55) . '</pre>'; ?>
  </div>
  <div id="footer">
   [<a href="javascript:window.close();">close</a>]
  </div>
 </body>
</html>
<?php
	}
}
?>
