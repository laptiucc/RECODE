<?php
require_once 'includes/main.inc';

$version=phpversion();
if ($version{0}>4) require_once 'includes/stemming.class.inc';

if (isset($_GET['order']) && (intval($_GET['order'])>0) && (intval($_GET['order'])<6)) {
	$order=set_pref('order', intval($_GET['order']));
} else {
	$order=max(get_pref('order'), 1);
}
$history=get_switch('history');
if (!empty($_SESSION['login']) && strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'xh')) $debug=get_switch('debug');
$size=30;
$min=(isset($_GET['page'])?(intval(trim($_GET['page']))*$size):0);
$max=$min+$size;

htmlheader();
print "      <p>&nbsp;</p>\n";
if ( !empty($_GET['q']) ) {
	$search = preg_replace('/\s\s*/', ' ', stripslashes(strip_tags(urldecode(trim($_GET['q'])))));
	$search = preg_replace('/[^\w\d\.\-\# ]/', '', $search);
	$search = str_replace(array(' the ', ' and ', ' or ', ' not ', ' each '), ' ', ' '.$search.' ');
	$search = trim($search);
	if ( !empty($search) ) {
		if ( $search{0}=='#') {
			$search=intval(substr($search, 1));
			if (!empty($_COOKIE['search'][$search])) { $search=$_COOKIE['search'][$search];}
			$memo=true;
		}
		if (preg_match('/^(\w+)_(\w+)_(\w+)(_(\w+))?/', $search, $matches)) { //recode1 ref
			$where_sql='a.legacy' . sql_reg(sql_escape_string($matches[0]));
		} elseif (preg_match('/^r2(\d+)(\.\d+)?$/', $search, $matches)) { //recode2 ref
			if (empty($matches[2])) {
				$where_sql='a.id_recode=\'' . $matches[0] . '\'';
			} else {
				$where_sql='b.id_product=\'' . $matches[0] . '\'';
			}
		} else {
			$tmp_sql = array();
			foreach ( split(' ', $search) as $value) {
				if ($version{0}>4) $value = PorterStemmer::Stem($value);
				$tmp_sql[] = '(c.latin' . sql_reg(sql_escape_string($value)) . ' OR d.phylum' . sql_reg(sql_escape_string($value)) . ' OR d.genome' . sql_reg(sql_escape_string($value)) . ' OR e.name' . sql_reg(sql_escape_string($value)) . ' OR f.name' . sql_reg(sql_escape_string($value)) .' OR c.genus' . sql_reg(sql_escape_string($value)) . ' OR c.phylum' . sql_reg(sql_escape_string($value)) . ' OR c.acronym' . sql_reg(sql_escape_string($value)) . ' OR c.synonym' . sql_reg(sql_escape_string($value)) .' OR a.description' . sql_reg(sql_escape_string($value)) .' OR a.locus' . sql_reg(sql_escape_string($value)) .')';
			}
			if (count($tmp_sql) > 0) {
				$where_sql = implode(' AND ', $tmp_sql);
			}
		}
		if (!empty($where_sql)) {
			if (empty($memo)) {
				if (!empty($_COOKIE['search']) && is_array($_COOKIE['search'])) {
					end($_COOKIE['search']);
					$id=key($_COOKIE['search'])+1;
				} else {
					$id=0;
				}
				setcookie('search['.$id.']', $search, mktime(1, 0, 0, date("m"), date("d") + 1, date("Y")), $config['path']);
			}
			$result=@sql_query('SELECT count(a.id_recode) AS count FROM recode2 AS a, recoding AS b, organisms AS c, kingdoms AS d, events AS e, status AS f WHERE a.organism = c.taxonid AND a.kingdom = d.kingdom AND a.status=f.status AND a.id_recode = b.id_recode AND b.event = e.event AND a.status<10 AND ' . $where_sql .';', $sql);
			if (!strlen($r=sql_last_error($sql)) && (sql_num_rows($result)==1)) {
				$count=sql_fetch_array($result);
				$count=$count['count'];
				if ($count>0) {
					$ordermap=array('c.latin', 'd.phylum, d.genome', 'e.name', 'f.name', 'a.id_recode', 'c.genus');

					$result=@sql_query('SELECT DISTINCT a.id_recode, d.phylum, d.genome, e.name AS event, f.name AS status, c.latin, c.genus FROM recode2 AS a, recoding AS b, organisms AS c, kingdoms AS d, events AS e, status AS f WHERE a.organism = c.taxonid AND a.kingdom = d.kingdom AND a.status=f.status AND a.id_recode = b.id_recode AND b.event = e.event AND a.status<10  AND (' . $where_sql . ') ORDER BY ' . $ordermap[$order-1] . sql_limit($min, $size), $sql);
					print "      <h2>Search for &quot;$search&quot; <small>result" . (($count>1)?'s':'').' '.($min+1).' - '.(($max>$count)?$count:$max).' of '.$count .  "</small></h2>\n";
?>
      <table summary="summary of the results" id="results">
        <thead>
          <tr>
            <th>&nbsp;</th>
            <th><?php print (($order==1)?"Organism":('<a href="' . $config['server'] . 'search?q='.urlencode(urldecode($_GET['q'])).'&amp;page='.($min/$size).'&amp;order=1">Organism</a>')); ?></th>
            <th><?php print (($order==2)?"Kingdom":('<a href="' . $config['server'] . 'search?q='.urlencode(urldecode($_GET['q'])).'&amp;page='.($min/$size).'&amp;order=2">Kingdom</a>')); ?></th>
            <th><?php print (($order==3)?"Event":('<a href="' . $config['server'] . 'search?q='.urlencode(urldecode($_GET['q'])).'&amp;page='.($min/$size).'&amp;order=3">Event</a>')); ?></th>
            <th><?php print (($order==4)?"Status":('<a href="' . $config['server'] . 'search?q='.urlencode(urldecode($_GET['q'])).'&amp;page='.($min/$size).'&amp;order=4">Status</a>')); ?></th>
            <th><?php print (($order==5)?"Genus":('<a href="' . $config['server'] . 'search?q='.urlencode(urldecode($_GET['q'])).'&amp;page='.($min/$size).'&amp;order=5">Genus</a>')); ?></th>
          </tr>
        </thead>
<?php
					if ($size<$count) {
						print '        <tfoot><tr'.((($max>$count)?($count-$min+1):$size)%2?'':' class="alt"').'><td colspan="7" class="prevnext">';
						if (($min/$size>5)) print '&nbsp;<a href="' . $config['server'] . 'search?q='.urlencode(urldecode($_GET['q'])).'&amp;page='.($min/$size-6).'&amp;order='.$order.'"><img src="' . $config['server'] . 'images/prev.png" alt="Previous" /></a>';
						for ($i=(($min/$size>5)?($min/$size-5):0);$i<((($count/$size)-($min/$size)>6)?($min/$size+6):ceil($count/$size));$i++)
							print '&nbsp;'.(($i==($min/$size))?($i+1):('<a href="' . $config['server'] . 'search?q='.urlencode(urldecode($_GET['q']))."&amp;page=$i&amp;order=$order\">".($i+1).'</a>'));
						if (($count/$size)-($min/$size)>6) print '&nbsp;<a href="' . $config['server'] . 'search?q='.urlencode(urldecode($_GET['q'])).'&amp;page='.($min/$size+6).'&amp;order='.$order.'"><img src="' . $config['server'] . 'images/next.png" alt="Next" /></a>';
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
            <td><?php print (!empty($row['genus'])?$row['genus']:''); ?></td>
          </tr>
<?php
					}
					print "        </tbody>\n      </table>\n";
				} else {
					print "      <h2>Search for &quot;$search&quot;</h2>\n      <p>no result found, yet!</p>\n";
				}
			} else {
				print "      <h2>Search for &quot;$search&quot;</h2>\n      <p>no result found, yet!</p>\n";
			}
			print "      <p>&nbsp;</p>\n      <h2>New search</h2>";
		}
	}
}
?>
      <form id="search" method="get" action="<?php print $config['server']; ?>search">
<?php if (empty($where_sql)) { ?>
        <div>
          <img src="<?php print $config['server']; ?>images/recode2_logo.png" alt="Recode2" height="110" width="276" />
        </div>
<?php } ?>
        <div>
          &nbsp;<strong>Search recoding events</strong>&nbsp;|&nbsp;<a href="<?php print $config['server']; ?>browse" title="Browse into recoding events">Browse</a>&nbsp;|&nbsp;<a href="<?php print $config['server']; ?>help" title="How to use Recode">Help</a>&nbsp;
        </div>
        <table summary="">
          <tr>
            <td rowspan="3" class="sidetext">&nbsp;</td>
            <td class="middletext"><input type="text" tabindex="1" class="search" size="50" name="q" /></td>
            <td class="sidetext">
              <a href="<?php print $config['server']; ?>?history=<?php print $history ? 'false' : 'true'; ?>" title="Last searches">History</a> (<?php print $history ? 'on' : 'off'; ?>)
<?php   if (isset($debug)) print '              <br /><a href="' . $config['server'] . '?debug=' . ($debug ? 'false' : 'true') .'" title="Last searches">Debug</a> (' . ($debug ?  'on' : 'off') .")\n"; ?>
            </td>
          </tr>
        </table>
        <div>
          <input value="Recode Search" type="submit" tabindex="2" />
        </div>
      </form>
<?php
if ($history && !empty($_COOKIE['search']) && is_array($_COOKIE['search'])) {
?>
      <p>&nbsp;</p>
      <h2>Recent activity</h2>
      <ul>
<?php
	end($_COOKIE['search']);
	$j=0;
	while (($value=current($_COOKIE['search']))!==FALSE && $j++<5) {
		$name= key($_COOKIE['search']);
		print '        <li><span class="search">#'. $name . '</span> Search for "<a href="'. $config['server'] . 'search?q=%23' . $name . '">' . $value . "</a>\"</li>\n";
		prev($_COOKIE['search']);
	}
?>
      </ul>
<?php
}
htmlfooter();
?>
