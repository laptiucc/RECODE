<?php
require_once 'includes/main.inc';

if (!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8)) {
	if (!empty($_GET) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server']) && !empty($_GET['star'])) {
		$result = @sql_query('SELECT id FROM publications WHERE id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['star'])))) . '\';', $sql);
		if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
			$result = @sql_query('UPDATE publications SET preferred=\'f\';', $sql);
			$result = @sql_query('UPDATE publications SET preferred=\'t\' WHERE id=\'' . sql_escape_string(urldecode(trim(stripslashes($_GET['star'])))) . '\';', $sql);
		}
	} elseif (!empty($_GET) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server']) && !empty($_GET['remove'])) {
		$result = @sql_query('SELECT id FROM publications WHERE id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
		if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
			$result = @sql_query('DELETE FROM publications WHERE id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
		}
	} elseif (!empty($_POST) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server']) && !empty($_POST['authors']) && !empty($_POST['title']) && !empty($_POST['journal']) && !empty($_POST['year'])) {
		$doi = html_entity_decode(stripslashes(trim($_POST['doi'])));
		if ( preg_match( '/^\d+\.\d+\//', $doi )) {
			$doi = 'doi:'.$doi;
		} elseif ( preg_match( '/^\d+$/', $doi )) {
			$doi = 'pmid:'.$doi;
		} else {
			unset ( $doi );
		}
		$id=substr(md5(uniqid(mt_rand(), true)), -16);
		$result = @sql_query('INSERT INTO publications (id, authors, title, journal, theyear, doi, published, preferred) VALUES (\'' . sql_escape_string($id) . '\',\'' . sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['authors']))), ENT_QUOTES, 'ISO8859-1')) . '\',\'' . sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['title']))), ENT_QUOTES, 'ISO8859-1')) . '\',\'' . sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['journal']))), ENT_QUOTES, 'ISO8859-1')) . '\',' . intval($_POST['year']) . ',' . ( !empty($doi) ? '\''.sql_escape_string($doi) . '\'' : 'NULL' ) .', NOW(), \'f\');', $sql);
		if (!strlen($r = sql_last_error($sql))) {
			unset ( $_POST );
		}
	}
}

htmlheader();
?>
      <p>&nbsp;</p>
      <h2>Publications</h2>
<?php
$result = @sql_query('SELECT authors, title, journal, theyear, doi FROM publications WHERE preferred=\'t\';', $sql);
if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
	$row = sql_fetch_array($result);
?>
      <p class="box">If you use Recode<sup class="r2">2</sup> for your research, please cite the following paper:<br /><strong><?php print $row['authors']. '</strong>. ('. $row['theyear'] .') ' . $row['title'] . '. <em>' . $row['journal']; ?></em>.<?php
	print (!empty($row['doi'])? ' [<a href="' . ((substr($row['doi'], 0, 4)=='doi:')? 'http://dx.doi.org/' . substr($row['doi'], 4) : 'http://www.ncbi.nlm.nih.gov/pubmed/' . intval(substr($row['doi'], 5))). '">abstract</a>]' :''); ?></p>
      <p>&nbsp;</p>
<?php
}

$result = @sql_query('SELECT id, authors, title, journal, theyear, doi, preferred FROM publications ORDER BY theyear DESC, published DESC;', $sql);
if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) > 0)) {
	while ($row = sql_fetch_array($result)) {
		if (empty($year) || ($year != $row['theyear'])) {
			if (!empty($year)) print "      </ul>\n";
			$year = $row['theyear'];
			print "      <h3>$year</h3>\n      <ul>\n";
		}
		print '        <li><strong>' . $row['authors']. '</strong>. ('. $row['theyear'] .') ' . $row['title'] . '. <em>' . $row['journal'] .'</em>.' . ((!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8))?'<a class="remove" href="' . $config['server'] . 'publications/remove/'.urlencode($row['id']).'" title="remove this reference" rel="nofollow"><span>&#10008;</span></a><a class="star' . ((!empty($row['preferred']) && $row['preferred']=='t')?'on':'off'). '" href="' . $config['server'] . 'publications/star/'.urlencode($row['id']).'" title="' . ((!empty($row['preferred']) && $row['preferred']=='t')?'Do not use this reference to cite Recode':'Use this reference to cite Recode'). '" rel="nofollow"><span>' . ((!empty($row['preferred']) && $row['preferred']=='t')?'&#9734;':'&#9733;'). '</span></a>' : '') . (!empty($row['doi'])? ((!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8))?'<br />':' ').'[<a href="' . ((substr($row['doi'], 0, 4)=='doi:')? 'http://dx.doi.org/' . substr($row['doi'], 4) : 'http://www.ncbi.nlm.nih.gov/pubmed/' . intval(substr($row['doi'], 5))). '" rel="nofollow">abstract</a>]' :'') . "</li>\n";
	}
	print "      </ul>\n";
} else {
	print "      <p>no publication found, yet!</p>\n";
}

if (!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8)) { ?>
      <p>&nbsp;</p>
      <h2>New publication</h2>
      <form method="post" id="signin" action="<?php print $config['server']; ?>publications">
        <div>
          <label for="authors" class="required">Authors</label>
          <input type="text" name="authors" id="authors" title="Surname and Initial(s): Darwin C and Wallace AR" value="<?php print (!empty($_POST['authors'])?stripslashes($_POST['authors']):''); ?>" /><br />
          <label for="title" class="required">Title</label>
          <input type="text" name="title" id="title" title="Full title: On the Tendency of Species to form Varieties; and on the Perpetuation of Varieties and Species by Natural Means of Selection" value="<?php print (!empty($_POST['title'])?stripslashes($_POST['title']):''); ?>" /><br />
          <label for="journal" class="required">Journal</label>
          <input type="text" name="journal" id="journal" title="Journal, Issue:Pages : Journal of the Proceedings of the Linnean Society of London, Zoology 3: 46-50" value="<?php print (!empty($_POST['journal'])?stripslashes($_POST['journal']):''); ?>" /><br />
          <label for="year" class="required">Year</label>
          <select name="year" id="year"><?php
	$date=getdate();
	for ( $i=$date['year']; $i>$date['year']-150; $i-- ) {
		print '<option' . ( ( !empty($_POST['year']) && (intval($_POST['year'])==$i) ) ? ' selected="selected"' : '' ) . ">$i</option>";
	}
	?></select><br />
          <label for="doi"><acronym title="Digital Object Identifier">DOI</acronym> or <acronym title="PubMed Identifier">PMID</acronym></label>
          <input type="text" name="doi" id="doi" title="doi (preferred) or NCBI PMID" value="<?php print (!empty($_POST['doi'])?stripslashes($_POST['doi']):''); ?>" /><br /><br />
          <input value="Add publication" type="submit" />
        </div>
      </form>
<?php
}
htmlfooter();
?>
