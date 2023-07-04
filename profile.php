<?php
require_once 'includes/main.inc';

if (!empty($_SESSION['login']) && !empty($_SESSION['login']['rights']) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server'])) {
	if ((empty($_GET['profile']) || ($_GET['profile']=='profile')) && !empty($_POST) && !empty($_POST['login']) && ($_POST['login'] == sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) ) ) && !empty($_POST['name']) && !empty($_POST['email']) ) {
		$email=preg_replace('/[^\d\w\.\-\_\@]/', '', strtolower(strip_tags(html_entity_decode(stripslashes(trim($_POST['email']))))));
		$result = @sql_query('UPDATE users SET name=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['name']))), ENT_QUOTES, 'ISO8859-1')) . '\', email=\''.sql_escape_string($email) . '\', institution=' . (!empty($_POST['institution']) ? '\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['institution']))), ENT_QUOTES, 'ISO8859-1')).'\'' : 'NULL') . ', url='. (!empty($_POST['url']) ? '\''.sql_escape_string(urlencode(stripslashes(trim($_POST['url'])))).'\'' : 'NULL'). ' WHERE username=\''.sql_escape_string($_SESSION['login']['username']).'\';', $sql);
		if (!strlen($r = sql_last_error($sql))) {
			$_SESSION['login']['email'] = $email;
			$_SESSION['login']['name'] = htmlentities(html_entity_decode(stripslashes(trim($_POST['name']))), ENT_QUOTES, 'ISO8859-1');
			if (!empty($_POST['institution'])) { $_SESSION['login']['institution'] = htmlentities(html_entity_decode(stripslashes(trim($_POST['institution']))), ENT_QUOTES, 'ISO8859-1'); } else {unset($_SESSION['login']['institution']); }
			if (!empty($_POST['url'])) { $_SESSION['login']['url'] = urlencode(stripslashes(trim($_POST['url']))); } else {unset($_SESSION['login']['url']); }
			header('Location: ' . $config['server'] );
			exit;
		}
	} elseif (!empty($_GET['profile']) && ($_GET['profile']=='change') && !empty($_POST) && !empty($_POST['login']) && ($_POST['login'] == sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) ) ) && !empty($_POST['orange']) && !empty($_POST['apple']) && !empty($_POST['plum']) && ($_POST['apple'] == $_POST['plum']) && (strlen($_POST['plum'])>7) ) {
		$password = md5(strip_tags(trim($_POST['orange'])));
		$newpassword = md5(strip_tags(trim($_POST['plum'])));
		$result = @sql_query('SELECT username, email, code, rights, name, institution, url FROM users WHERE (username=\''.sql_escape_string($_SESSION['login']['username'])."' AND plum='$password');", $sql);
		print 'SELECT username, email, code, rights, name, institution, url FROM users WHERE (username=\''.sql_escape_string($_SESSION['login']['username'])."' AND plum='$password');\n<br>";
		if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
			$result = @sql_query('UPDATE users SET plum=\''.sql_escape_string($newpassword) . '\' WHERE username=\''.sql_escape_string($_SESSION['login']['username']).'\';', $sql);
			if (!strlen($r = sql_last_error($sql))) {
				header('Location: ' . $config['server'] );
				exit;
			}
		}
	}

	htmlheader();
	if (empty($_GET['profile']) || ($_GET['profile']=='profile') ) {
?>
      <p>&nbsp;</p>
      <h2>Edit personal information</h2>
      <form method="post" id="signin" action="<?php print $config['server']; ?>profile">
        <div>
          <label for="name" class="required">Full name</label>
          <input type="text" name="name" id="name" title="Full name (to credit your contributions)" value="<?php print $_SESSION['login']['name']; ?>" /><br /><br />
          <label for="email" class="required">Email (private)</label>
          <input type="text" name="email" id="email" title="e.g. myname@example.com (for administration purpuse only)" value="<?php print $_SESSION['login']['email']; ?>" /><br />
          <label for="institution" class="required">Institution</label>
          <input type="text" name="institution" id="institution" title="Institution name (university, institute etc.)" value="<?php print (!empty($_SESSION['login']['institution'])?$_SESSION['login']['institution']:''); ?>" /><br />
          <label for="url">Web page</label>
          <input type="text" name="url" id="url" title="Web address: e.g. <?php print $config['server']; ?>" value="<?php print (!empty($_SESSION['login']['url'])?urldecode($_SESSION['login']['url']):''); ?>" /><br /><br />
          <input type="hidden" name="login" value="<?php print sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) ); ?>" /><input value="Save" type="submit" />
        </div>
      </form>
<?php
	} elseif (!empty($_GET['profile']) && ($_GET['profile']=='change')) {
?>
      <p>&nbsp;</p>
      <h2>Change password</h2>
      <form method="post" id="advanced" action="<?php print $config['server']; ?>change">
        <div>
          <label for="orange" class="required">Current Password</label>
          <input type="password" maxlength="32" name="orange" id="orange" /><br /><br />
          <label for="plum" class="required">New Password</label>
          <input type="password" maxlength="32" name="plum" id="plum" title="Minimum of 8 characters in length." value=""/><br />
          <label for="apple" class="required">New Password (again)</label>
          <input type="password" maxlength="32" name="apple" id="apple" title="Minimum of 8 characters in length." value=""/><br />
          <input type="hidden" name="login" value="<?php print sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) ); ?>" /><input value="Save" type="submit" />
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
