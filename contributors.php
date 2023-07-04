<?php
require_once 'includes/main.inc';

if (!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8)) {
	if (!empty($_GET) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server']) && !empty($_GET['remove'])) {
		$result = @sql_query('SELECT name FROM users WHERE username=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\' AND rights<8;', $sql);
		if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
			$result = @sql_query('DELETE FROM users WHERE rights<8 AND username=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
		}
	} elseif (!empty($_POST) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server']) && !empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['institution'])) {
		$username=strtolower(str_replace(' ', '.', preg_replace('/[^\d\w\_\ ]/', '', preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil);/', '\1', htmlentities(strip_tags(html_entity_decode(stripslashes(trim($_POST['name'])))), ENT_NOQUOTES, 'ISO-8859-1')))));
		$id=md5(uniqid(mt_rand(), true));
		$_POST['email']=preg_replace('/[^\d\w\.\-\_]/', '', strtolower(strip_tags(html_entity_decode(stripslashes(trim($_POST['email']))))));
		$result = @sql_query('INSERT INTO users (username, plum, task, code, rights, activated, name, email, institution, url) VALUES (\'' . sql_escape_string($username) . '\',\'' . sql_escape_string($id) . '\',NULL,\'>\',1,1,\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['name']))), ENT_QUOTES, 'ISO8859-1')) . '\',\''.sql_escape_string(htmlentities($_POST['email'], ENT_QUOTES, 'ISO8859-1')) . '\',\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['institution']))), ENT_QUOTES, 'ISO8859-1')) . '\',' . (!empty($_POST['url']) ? '\''.sql_escape_string(urlencode(stripslashes(trim($_POST['url'])))).'\'' : 'NULL'). ');', $sql);
		if (!strlen($r = sql_last_error($sql))) {
			unset ( $_POST );
		}
	}
}

htmlheader();
?>
      <p>&nbsp;</p>
      <h2>Contributors</h2>
      <p>These are individuals who have contributed to the development of Recode database by providing data, tools, scientific advice, financial support, etc. The following list is ordered alphabetically using names of affiliated organizations.</p>
<?php
$result = @sql_query('SELECT username, name, institution, url, rights FROM users WHERE rights=1 OR rights >=6 ORDER BY institution, name;', $sql);
if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) > 0)) {
	while ($row = sql_fetch_array($result)) {
		if (empty($institution) || ($institution != $row['institution'])) {
			if (!empty($institution)) print "      </ul>\n";
			$institution = $row['institution'];
			print "      <h3>$institution</h3>\n      <ul>\n";
		}
		print '        <li>' . $row['name']. ((!empty($row['url']))?' [<a href="'.htmlentities($row['url']).'" rel="nofollow">link</a>]':'') . ((($row['rights']==1) && !empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8))?'<a class="remove" href="' . $config['server'] . 'contributors/remove/'.urlencode($row['username']).'" title="remove this contributor" rel="nofollow"><span>&#10008;</span></a>':'') . "</li>\n";
	}
	print "      </ul>\n";
} else {
	print "      <p>no contributor found, yet!</p>\n";
}
if (!empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8)) { ?>
      <p>&nbsp;</p>
      <h2>New contributor</h2>
      <form method="post" id="signin" action="<?php print $config['server']; ?>contributors">
        <div>
          <label for="name" class="required">Name</label>
          <input type="text" name="name" id="name" title="Full name" value="<?php print (!empty($_POST['name'])?stripslashes($_POST['name']):''); ?>" /><br />
          <label for="email" class="required">Email (private)</label>
          <input type="text" name="email" id="email" title="email (for administration purpuse only)" value="<?php print (!empty($_POST['email'])?stripslashes($_POST['email']):''); ?>" /><br />
          <label for="institution" class="required">Institution</label>
          <input type="text" name="institution" id="institution" title="Institution name (university, institute etc.)" value="<?php print (!empty($_POST['institution'])?stripslashes($_POST['institution']):''); ?>" /><br />
          <label for="url">Web page</label>
          <input type="text" name="url" id="url" title="Web address: e.g. <?php print $config['server']; ?>" value="<?php print (!empty($_POST['url'])?stripslashes($_POST['url']):''); ?>" /><br /><br />
          <input value="Add contributor" type="submit" />
        </div>
      </form>
<?php
}
htmlfooter();
?>
