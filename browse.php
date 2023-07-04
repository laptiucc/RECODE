<?php
require_once 'includes/main.inc';

if (isset($_GET['order']) && (intval($_GET['order'])>0) && (intval($_GET['order'])<6)) {
	$order=set_pref('order', intval($_GET['order']));
} else {
	$order=max(get_pref('order'), 1);
}

$size=30;
$min=(isset($_GET['page'])?(intval(trim($_GET['page']))*$size):0);
$max=$min+$size;
$tranlate=array('phylums'=>'Organism type', 'events'=>'Event type', 'organisms'=>'Organism', 'status'=>'Status', 'kingdoms'=>'Organism type');

htmlheader();
?>
      <p>&nbsp;</p>
<?php
if (!isset($_GET['a'])) {
	print "      <h2>Categories</h2>\n      <div>\n";
	$result=@sql_query("SELECT DISTINCT a.latin FROM organisms AS a, recode2 AS b WHERE a.taxonid=b.organism AND b.status<10 ORDER BY a.latin LIMIT 20;", $sql);
	if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
		print "        <div class=\"box_browse\">\n          <h3><a href=\"" . $config['server'] . "browse/organisms\" title=\"Organism name\">Organism</a></h3>\n          <ul>\n";
		while ($row=sql_fetch_array($result)) {
			print '            <li><a href="' . $config['server'] . 'browse/organisms/' . urlencode($row['latin']) . '"><em>'. $row['latin'] . "</em></a></li>\n";
		}
		if ( sql_num_rows($result)==20 ) { print "            <li>...</li>\n"; }
		print "          </ul>\n        </div>\n";
	}
	$result=@sql_query("SELECT DISTINCT a.phylum FROM kingdoms AS a, recode2 AS b WHERE a.kingdom=b.kingdom AND b.status<10 ORDER BY a.phylum LIMIT 7;", $sql);
	if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
		print "        <div class=\"box_browse\">\n          <h3><a href=\"" . $config['server'] . "browse/kingdoms\" title=\"Organism type [Kingdom]\">Organism type</a></h3>\n          <ul>\n";
		while ($row=sql_fetch_array($result)) {
			print '            <li><a href="' . $config['server'] . 'browse/kingdoms/' . urlencode($row['phylum']) . '">'. $row['phylum'] . "</a></li>\n";
		}
		if ( sql_num_rows($result)==7 ) { print "            <li>...</li>\n"; }
		print "          </ul>\n        </div>\n";
	}
	$result=@sql_query("SELECT DISTINCT a.name FROM events AS a, recoding AS b WHERE a.event=b.event ORDER BY a.name LIMIT 7;", $sql);
	if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
		print "         <div class=\"box_browse\">\n          <h3><a href=\"" . $config['server'] . "browse/events\" title=\"Type of recoding\">Event type</a></h3>\n          <ul>\n";
		while ($row=sql_fetch_array($result)) {
			print '            <li><a href="' . $config['server'] . 'browse/events/' . urlencode(str_replace('+', '_', $row['name'])) . '">'. $row['name'] . "</a></li>\n";
		}
		if ( sql_num_rows($result)==7 ) { print "            <li>...</li>\n"; }
		print "          </ul>\n        </div>\n";
	}
	$result=@sql_query("SELECT DISTINCT a.name FROM status AS a, recode2 AS b WHERE a.status=b.status AND b.status<10 ORDER BY a.name LIMIT 7;", $sql);
	if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
		print "        <div class=\"box_browse\">\n          <h3><a href=\"" . $config['server'] . "browse/status\" title=\"Status\">Status</a></h3>\n          <ul>\n";
		while ($row=sql_fetch_array($result)) {
			print '            <li><a href="' . $config['server'] . 'browse/status/' . urlencode($row['name']) . '">'. $row['name'] . "</a></li>\n";
		}
		if ( sql_num_rows($result)==7 ) { print "            <li>...</li>\n"; }
		print "          </ul>\n        </div>\n";
	}
	print "      </div>\n";
} elseif (!empty($_GET['a']) && empty($_GET['q']) && !empty($tranlate[$_GET['a']])) {
	print '      <h2>' . $tranlate[$_GET['a']] .  "</h2>\n";
	if ($_GET['a']=='kingdoms') {
		$result=@sql_query("SELECT DISTINCT a.kingdom, a.phylum, a.genome FROM kingdoms AS a, recode2 AS b WHERE a.kingdom=b.kingdom AND b.status<10  ORDER BY a.phylum, a.genome", $sql);
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
			print "      <div>\n        <ul>\n";
			while ($row=sql_fetch_array($result)) {
				print '          <li><a href="' . $config['server'] . 'browse/phylums/' . urlencode($row['kingdom']) . '">' . $row['phylum'] . ' (' . $row['genome'] . ")</a></li>\n";
			}
			print "        </ul>\n      </div>\n";
		} else {
			print '      <p>no ' . $tranlate[$_GET['a']] . " found, yet!</p>\n";
		}
	} else {
		switch ($_GET['a']) {
		case 'events':
			$result=@sql_query("SELECT DISTINCT a.name FROM events AS a, recoding AS b, recode2 AS c WHERE a.event=b.event AND b.id_recode=c.id_recode AND c.status<10 ORDER BY a.name;", $sql);
			break;
		case 'organisms':
			$result=@sql_query("SELECT DISTINCT a.latin AS name FROM organisms AS a, recode2 AS b WHERE a.taxonid=b.organism AND b.status<10  ORDER BY a.latin;", $sql);
			break;
		case 'status':
			$result=@sql_query("SELECT DISTINCT a.name FROM status AS a, recode2 AS b WHERE a.status=b.status AND a.status<10  ORDER BY a.name;", $sql);
			break;
		}
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
			print "      <div>\n        <ul>\n";
			while ($row=sql_fetch_array($result)) {
				print '          <li><a href="' . $config['server'] . 'browse/'.$_GET['a'].'/'.urlencode(str_replace('/', '@', $row['name'])).'">'.$row['name']."</a></li>\n";
			}
			print "        </ul>\n      </div>\n";
		} else {
			print '      <p>no ' . $tranlate[$_GET['a']] . " found, yet!</p>\n";
		}
	}
} elseif ( !empty($_GET['a']) && !empty($_GET['q']) && !empty($tranlate[$_GET['a']])) {
	if ($_GET['a'] == 'phylums' ) {
		$result=@sql_query('SELECT phylum, genome FROM kingdoms WHERE kingdom=' . intval(urldecode($_GET['q'])) . ';', $sql);
		if (!strlen($r=sql_last_error($sql)) && (sql_num_rows($result)==1)) {
			$phylym=sql_fetch_array($result);
			print '      <h2>' . $tranlate[$_GET['a']] . ' &gt; ' . $phylym['phylum'] . ' &gt; ' . $phylym['genome'];
			$where_sql='d.kingdom='.intval(urldecode($_GET['q'])).'';
		}
	} else {
		print '      <h2>' . $tranlate[$_GET['a']] . ' &gt; ' . str_replace('_', '+', urldecode($_GET['q']));
		$mapa=array('kingdoms'=>'d.phylum', 'events'=>'e.name', 'status'=>'f.name', 'organisms'=>'c.latin');
		$where_sql=$mapa[$_GET['a']].'=\''.sql_escape_string(str_replace('_', '+', str_replace('@', '/', urldecode($_GET['q'])))).'\'';
	}
	$result=@sql_query('SELECT count(a.id_recode) AS count FROM recode2 AS a, recoding AS b, organisms AS c, kingdoms AS d, events AS e, status AS f WHERE a.organism = c.taxonid AND a.kingdom = d.kingdom AND a.status=f.status AND a.id_recode = b.id_recode AND b.event = e.event AND a.status<10 AND ' . $where_sql .';', $sql);
	if (!strlen($r=sql_last_error($sql)) && (sql_num_rows($result)==1)) {
		$count=sql_fetch_array($result);
		$count=$count['count'];
		if ($count>0) {
			$ordermap=array('c.latin', 'd.phylum, d.genome', 'e.name', 'f.name', 'a.id_recode', 'c.genus');
			$result=@sql_query('SELECT DISTINCT a.id_recode, d.phylum, d.genome, e.name AS event, f.name AS status, c.latin, c.genus FROM recode2 AS a, recoding AS b, organisms AS c, kingdoms AS d, events AS e, status AS f WHERE a.organism = c.taxonid AND a.kingdom = d.kingdom AND a.status=f.status AND a.id_recode = b.id_recode AND b.event = e.event AND a.status<10  AND ' . $where_sql . ' ORDER BY ' . $ordermap[$order-1] . sql_limit($min, $size), $sql);
			print ' <small>' . ($min+1).' - '.(($max>$count)?$count:$max).' of '.$count .  "</small></h2>\n";
?>
      <table summary="summary of the results" id="results">
        <thead>
          <tr>
            <th>&nbsp;</th>
            <th><?php print (($order==1)?"Organism":('<a href="' . $config['server'] . 'browse/'.$_GET['a'].'/'.urlencode(urldecode($_GET['q'])).'?page='.($min/$size).'&amp;order=1">Organism</a>')); ?></th>
            <th><?php print (($order==2)?"Kingdom":('<a href="' . $config['server'] . 'browse/'.$_GET['a'].'/'.urlencode(urldecode($_GET['q'])).'?page='.($min/$size).'&amp;order=2">Kingdom</a>')); ?></th>
            <th><?php print (($order==3)?"Event":('<a href="' . $config['server'] . 'browse/'.$_GET['a'].'/'.urlencode(urldecode($_GET['q'])).'?page='.($min/$size).'&amp;order=3">Event</a>')); ?></th>
            <th><?php print (($order==4)?"Status":('<a href="' . $config['server'] . 'browse/'.$_GET['a'].'/'.urlencode(urldecode($_GET['q'])).'?page='.($min/$size).'&amp;order=4">Status</a>')); ?></th>
            <th><?php print (($order==5)?"Genus":('<a href="' . $config['server'] . 'browse/'.$_GET['a'].'/'.urlencode(urldecode($_GET['q'])).'?page='.($min/$size).'&amp;order=5">Genus</a>')); ?></th>
          </tr>
        </thead>
<?php
			if ($size<$count) {
				print '        <tfoot><tr'.((($max>$count)?($count-$min+1):$size)%2?'':' class="alt"').'><td colspan="7" class="prevnext">';
				if (($min/$size>5)) print '&nbsp;<a href="' . $config['server'] . 'browse/'.$_GET['a'].'/'.urlencode(urldecode($_GET['q'])).'?page='.($min/$size-6).'&amp;order='.$order.'"><img src="' . $config['server'] . 'images/prev.png" alt="Previous" /></a>';
				for ($i=(($min/$size>5)?($min/$size-5):0);$i<((($count/$size)-($min/$size)>6)?($min/$size+6):ceil($count/$size));$i++)
					print '&nbsp;'.(($i==($min/$size))?($i+1):('<a href="' . $config['server'] . 'browse/'.$_GET['a'].'/'.urlencode(urldecode($_GET['q']))."?page=$i&amp;order=$order\">".($i+1).'</a>'));
				if (($count/$size)-($min/$size)>6) print '&nbsp;<a href="' . $config['server'] . 'browse/'.$_GET['a'].'/'.urlencode(urldecode($_GET['q'])).'?page='.($min/$size+6).'&amp;order='.$order.'"><img src="' . $config['server'] . 'images/next.png" alt="Next" /></a>';
				print "</td></tr></tfoot>\n";
			}
			print "        <tbody>\n";
			$i=1;
			while ($row=sql_fetch_array($result)) {
?>
          <tr<?php print ($i++%2?' class="alt"':''); ?>>
            <td><a href="<?php print $config['server']; ?>recode/<?php print urlencode($row['id_recode']); ?>/" title="more details"><img src="<?php print $config['server']; ?>images/query.png" class="dl" alt="more details" /></a></td>
            <td><em><?php print $row['latin']; ?></em></td>
            <td><?php print $row['phylum']. ' ('.$row['genome']; ?>)</td>
            <td><?php print $row['event']; ?></td>
            <td><?php print $row['status']; ?></td>
            <td><?php print (isset($row['genus'])?$row['genus']:''); ?></td>
          </tr>
<?php
			}
			print "        </tbody>\n      </table>\n";
		} else {
			print "</h2>\n      <p>no result found, yet!</p>\n";
		}
	}
}
htmlfooter();
?>
