<?php
require_once 'includes/main.inc';

if (!empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server'])) {
	if (empty($_COOKIE['user_id'])) session_start();
	unset($_SESSION['login']);
	session_unset();
	session_destroy();
	setcookie('user_id', '', 0, $config['path']);
	setcookie(session_name(), '', 0, '/');
	if (!empty($_POST) && !empty($_POST['login']) && ($_POST['login'] == sha1( mktime( 0, 0, 0, date( 'm' ) , date( 'd' ) + 1, date( 'Y' ) ) ) ) && !empty($_POST['username']) && 
!empty($_POST['apple']) && !empty($_POST['plum']) && ($_POST['apple'] == $_POST['plum']) && (strlen($_POST['plum'])>7) && !empty($_POST['name']) && !empty($_POST['email']) ) {
		$username=strtolower(str_replace(' ', '.', preg_replace('/[^\d\w\_\ ]/', '', preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil);/', '\1', htmlentities(strip_tags(html_entity_decode(stripslashes(trim($_POST['username'])))), ENT_NOQUOTES, 'ISO-8859-1')))));
		$password = md5(strip_tags(trim($_POST['plum'])));
		$email=preg_replace('/[^\d\w\.\-\_\@]/', '', strtolower(strip_tags(html_entity_decode(stripslashes(trim($_POST['email']))))));
		$result = @sql_query('INSERT INTO users (username, plum, task, code, rights, activated, name, email, institution, url) VALUES (\'' . sql_escape_string($username) . '\',\'' . sql_escape_string($password) . '\',NULL,\'~\',0,0,\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['name']))), ENT_QUOTES, 'ISO8859-1')) . '\',\''.sql_escape_string($email) . '\','.(!empty($_POST['institution']) ? '\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['institution']))), ENT_QUOTES, 'ISO8859-1')).'\'' : 'NULL') . ',' . (!empty($_POST['url']) ? '\''.sql_escape_string(urlencode(stripslashes(trim($_POST['url'])))).'\'' : 'NULL'). ');', $sql);
		if (!strlen($r = sql_last_error($sql))) {
			unset ( $_POST );
			header('Location: ' . $config['server'].'signin' );
			exit;
		}
	}

	htmlheader();
?>
      <p>&nbsp;</p>
      <h2>Create a new account</h2>
      <form method="post" id="signin" action="<?php print $config['server']; ?>newaccount">
        <div>
          <p>Your account must be validated by one of the administrator before the full activated.</p>
          <label for="username" class="required">Username</label>
          <input type="text" maxlength="32" name="username" id="username" value="<?php print (!empty($_POST['username'])?stripslashes($_POST['username']):''); ?>" /><br />
          <label for="plum" class="required">Password</label>
          <input type="password" maxlength="32" name="plum" id="plum" title="Minimum of 8 characters in length." value=""/><br />
          <label for="apple" class="required">Password (again)</label>
          <input type="password" maxlength="32" name="apple" id="apple" title="Minimum of 8 characters in length." value=""/><br />
          <label for="name" class="required">Full name</label>
          <input type="text" name="name" id="name" title="Full name (to credit your contributions)" value="<?php print (!empty($_POST['name'])?stripslashes($_POST['name']):''); ?>" /><br />
          <label for="email" class="required">Email (private)</label>
          <input type="text" name="email" id="email" title="e.g. myname@example.com (for administration purpuse only)" value="<?php print (!empty($_POST['email'])?stripslashes($_POST['email']):''); ?>" /><br />
          <label for="institution" class="required">Institution</label>
          <input type="text" name="institution" id="institution" title="Institution name (university, institute etc.)" value="<?php print (!empty($_POST['institution'])?stripslashes($_POST['institution']):''); ?>" /><br />
          <label for="url">Web page</label>
          <input type="text" name="url" id="url" title="Web address: e.g. <?php print $config['server']; ?>" value="<?php print (!empty($_POST['url'])?stripslashes($_POST['url']):''); ?>" /><br /><br />
          <input type="hidden" name="login" value="<?php print sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) ); ?>" /><input value="Create my account" type="submit" />
        </div>
      </form>
<?php
	htmlfooter();
} else {
	header('Location: ' . $config['server'] );
	exit;
}
?>
