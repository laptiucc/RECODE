<?php
require_once 'includes/main.inc';

if (!empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server'])) {
	if (empty($_COOKIE['user_id'])) session_start();
	mt_srand(time());
	if (empty($logged) && !empty($_POST['login']) && ($_POST['login'] == sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) . md5(floor(intval(date('b')))))) && !empty($_POST['username']) && !empty($_POST['plum'])) {
		$login = strip_tags(trim($_POST['username']));
		$password = md5(strip_tags(trim($_POST['plum'])));
		$result = @sql_query("SELECT username, email, code, rights, name, institution, url FROM users WHERE (username='$login' AND plum='$password' AND rights>=0 AND activated=1);", $sql);
		if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
			$row = sql_fetch_array($result);
			$_SESSION['login']['user_id'] = md5(uniqid(mt_rand(), true));
			$_SESSION['login']['ip'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['login']['username'] = $row['username'];
			$_SESSION['login']['code'] = $row['code'];
			$_SESSION['login']['email'] = $row['email'];
			$_SESSION['login']['rights'] = intval($row['rights']);
			$_SESSION['login']['name'] = $row['name'];
			if (!empty($row['institution'])) { $_SESSION['login']['institution'] = $row['institution']; }
			if (!empty($row['url'])) { $_SESSION['login']['url'] = $row['url']; }
			setcookie('user_id', $_SESSION['login']['user_id'], mktime(1, 0, 0, date("m"), date("d") + 1, date("Y")), $config['path']);
			$logged=true;
		} else {
			$logged=false;
		}
	}
	if (empty($logged)) {
		unset($_SESSION['login']);
		session_unset();
		session_destroy();
		setcookie('user_id', '', 0, $config['path']);
		setcookie(session_name(), '', 0, '/');
		htmlheader();
?>
      <p>&nbsp;</p>
      <h2>Sign-In</h2>
      <form method="post" id="signin" action="<?php print $config['server']; ?>signin">
        <div>
          <label for="username" class="required">Username</label>
          <input type="text" maxlength="32" name="username" id="username" /><br />
          <label for="plum" class="required">Password</label>
          <input type="password" maxlength="32" name="plum" id="plum" /><br /><br />
          <input type="hidden" name="login" value="<?php print sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) . md5(floor(intval(date('b'))))); ?>" /><input value="Sign-In" type="submit" />
        </div>
      </form>
      <p>Don&#39;t have a Recode Account? <a href="<?php print $config['server']; ?>newaccount">Create an account now</a>.</p>
<?php
		htmlfooter();
	}else {
		header('Location: ' . $config['server'] );
		exit;
	}
} else {
	header('Location: ' . $config['server'] );
	exit;
}
?>
