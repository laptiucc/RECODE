<?php
require_once 'includes/main.inc';

if (!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server'])) {
	if (!empty($_GET) && !empty($_GET['remove'])) {
		$result = @sql_query('SELECT title FROM resources WHERE title=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
		if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
			$result = @sql_query('DELETE FROM resources WHERE title=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
		}
	} elseif (!empty($_POST) && !empty($_POST['title']) && !empty($_POST['url']) && !empty($_POST['description'])) {
		$result = @sql_query('INSERT INTO resources (title, description, url, released) VALUES (\'' . sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['title']))), ENT_QUOTES, 'ISO8859-1')) . '\',\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['description']))), ENT_QUOTES, 'ISO8859-1')) . '\',\'' . sql_escape_string(urlencode(stripslashes(trim($_POST['url'])))) . '\', NOW());', $sql);
		if (!strlen($r = sql_last_error($sql))) {
			unset ( $_POST );
		}
	}
}

htmlheader();
?>
      <p>&nbsp;</p>
      <h2>Recoding resources on the Net</h2>
<?php
$result = @sql_query('SELECT title, description, url FROM resources ORDER BY title;', $sql);
if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) > 0)) {
	while ($row = sql_fetch_array($result)) {
		print '      <h3>'.htmlentities($row['title'], ENT_QUOTES, 'ISO8859-1') . ((!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8))?'<a class="remove" href="' . $config['server'] . 'resources/remove/'.urlencode($row['title']).'" title="remove this resource" rel="nofollow"><span>&#10008;</span></a>' :'') . "</h3>\n";
		print '      <p>' . htmlentities($row['description'], ENT_QUOTES, 'ISO8859-1') . '. [<a href="'.htmlentities(urldecode($row['url']))."\" rel=\"nofollow\">link</a>]</p>\n";
	}
} else {
	print "      <p>no resources found, yet!</p>\n";
}

if (!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8)) { ?>
      <p>&nbsp;</p>
      <h2>New resource</h2>
      <form method="post" id="signin" action="<?php print $config['server']; ?>resources">
        <div>
          <label for="title" class="required">Name</label>
          <input type="text" name="title" id="title" title="Acronym" value="<?php print (!empty($_POST['title'])?stripslashes($_POST['title']):''); ?>" /><br />
          <label for="url" class="required">URL</label>
          <input type="text" name="url" id="url" title="Web address: e.g. <?php print $config['server']; ?>" value="<?php print (!empty($_POST['url'])?stripslashes($_POST['url']):''); ?>" /><br />
          <label for="description" class="required">Description</label>
          <textarea rows="3" cols="40" name="description" id="description"><?php print (!empty($_POST['description'])?stripslashes($_POST['description']):''); ?></textarea><br /><br />
          <input value="Add resource" type="submit" />
        </div>
      </form>
<?php
}
htmlfooter();
?>
