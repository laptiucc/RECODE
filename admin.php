<?php
require_once 'includes/main.inc';

if (!empty($_SESSION['login']) && !empty($_SESSION['login']['rights']) && ($_SESSION['login']['rights']>=8) && !empty($_SERVER['HTTP_REFERER']) && (substr($_SERVER['HTTP_REFERER'], 0, strlen($config['server']))==$config['server'])) {
	if (strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'xh')) $debug=get_switch('debug');

	htmlheader();
	if (!empty($_GET['admin']) && ($_GET['admin']=='users') && ($_SESSION['login']['rights']>=9)) {
		if (empty($_GET['user']) && !empty($_GET['reviewed'])) {
			$result = @sql_query('SELECT username FROM users WHERE username=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['reviewed'])))) . '\';', $sql);
			if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
				$row=sql_fetch_array($result);
				$result = @sql_query('UPDATE users SET code=\'*\', activated=1, rights=3 WHERE username=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['reviewed'])))). '\';', $sql);
			}
		} elseif (empty($_GET['user']) && !empty($_GET['remove'])) {
			$result = @sql_query('SELECT username FROM users WHERE username=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
			if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
				$result = @sql_query('DELETE FROM users WHERE username=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
			}
		}
		if (!empty($_GET['user'])) {
			if (!empty($_POST) && !empty($_POST['info']) && ($_POST['info'] == sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) . md5(floor(intval(date('b')))))) && isset($_POST['rights']) && !empty($_POST['name']) && !empty($_POST['email']) ) {
				$result = @sql_query('SELECT rights, code FROM usercodes WHERE rights=' . sql_escape_string(intval($_POST['rights'])) . ';', $sql);
				if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
					$row=sql_fetch_array($result);
					$email=preg_replace('/[^\d\w\.\-\_\@]/', '', strtolower(strip_tags(html_entity_decode(stripslashes(trim($_POST['email']))))));
					$result2 = @sql_query('UPDATE users SET name=\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['name']))), ENT_QUOTES, 'ISO8859-1')) . '\', email=\''.sql_escape_string($email) . '\', institution=' . (!empty($_POST['institution']) ? '\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['institution']))), ENT_QUOTES, 'ISO8859-1')).'\'' : 'NULL') . ', url='. (!empty($_POST['url']) ? '\''.sql_escape_string(urlencode(stripslashes(trim($_POST['url'])))).'\'' : 'NULL'). ', task='. (!empty($_POST['task']) ? '\''.sql_escape_string(htmlentities(html_entity_decode(stripslashes(trim($_POST['task']))), ENT_QUOTES, 'ISO8859-1')).'\'' : 'NULL'). ', activated='. (!empty($_POST['activated']) ? '1' : '0').', rights='.sql_escape_string(intval($row['rights'])).', code=\''.sql_escape_string($row['code']).'\' WHERE username=\''.sql_escape_string(urldecode(stripslashes(trim($_GET['user'])))).'\';', $sql);
					if (!strlen($r = sql_last_error($sql))) {
						header('Location: ' . $config['server'] .'admin/users' );
						exit;
					}
				}
			} elseif (!empty($_POST) && !empty($_POST['pass']) && ($_POST['pass'] == sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) . md5(floor(intval(date('b')))))) && !empty($_POST['apple']) && !empty($_POST['plum']) && ($_POST['apple'] == $_POST['plum']) && (strlen($_POST['plum'])>8) ) {
				$newpassword = md5(strip_tags(trim($_POST['plum'])));
				$result = @sql_query('UPDATE users SET plum=\''.sql_escape_string($newpassword) . '\' WHERE username=\''.sql_escape_string(urldecode(stripslashes(trim($_GET['user'])))).'\';', $sql);
				if (!strlen($r = sql_last_error($sql))) {
					header('Location: ' . $config['server'] .'admin/users' );
					exit;
				}
			}
			$result = @sql_query('SELECT username, email, name, code, rights, task, institution, url, activated FROM users WHERE username=\''.sql_escape_string(urldecode(stripslashes(trim($_GET['user'])))).'\';', $sql);
			if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) ==1)) {
				$row = sql_fetch_array($result);
?>
      <p>&nbsp;</p>
      <h2>Edit user information</h2>
      <form method="post" id="signin" action="<?php print $config['server'].'admin/users/'.urlencode(urldecode(stripslashes(trim($_GET['user'])))); ?>">
        <div>
          <label for="name" class="required">Full name</label>
          <input type="text" name="name" id="name" title="Full name (to credit the contributions)" value="<?php print $row['name']; ?>" /><br />
          <label for="rights" class="required">Rights</label>
          <select name="rights" id="rights"><?php
				$result2=@sql_query('SELECT rights, code, name FROM usercodes ORDER BY rights;', $sql);
				if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result2)>1)) {
					while ($row2=sql_fetch_array($result2)) {
						print '<option value="'.$row2['rights'].'"'.(($row2['rights']==$row['rights'])?' selected="selected"':'').'>'.htmlentities($row2['code'], ENT_QUOTES, 'ISO8859-1') .' ' . $row2['name'].'</option>';
					}
				}
				?></select><br />
          <label for="activated">Activated</label>
          <input type="checkbox" name="activated" id="activated" value="true"<?php print (!empty($row['activated'])?' checked="checked"':''); ?> /><br />
          <label for="email" class="required">Email</label>
          <input type="text" name="email" id="email" title="e.g. myname@example.com (for administration purpuse only)" value="<?php print $row['email']; ?>" /><br />
          <label for="institution" class="required">Institution</label>
          <input type="text" name="institution" id="institution" title="Institution name (university, institute etc.)" value="<?php print (!empty($row['institution'])?$row['institution']:''); ?>" /><br />
          <label for="url">Web page</label>
          <input type="text" name="url" id="url" title="Web address: e.g. <?php print $config['server']; ?>" value="<?php print (!empty($row['url'])?urldecode($row['url']):''); ?>" /><br />
          <label for="task">Task</label>
          <input type="text" name="task" id="task" title="Members, curators..." value="<?php print (!empty($row['task'])?$row['task']:''); ?>" /><br />
          <input type="hidden" name="info" value="<?php print sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) . md5(floor(intval(date('b'))))); ?>" /><input value="Save" type="submit" />
        </div>
      </form>
      <p>&nbsp;</p>
      <h2>Change user password</h2>
      <form method="post" id="signin2" action="<?php print $config['server'].'admin/users/'.urlencode(urldecode(stripslashes(trim($_GET['user'])))); ?>">
        <div>
          <label for="plum" class="required">New Password</label>
          <input type="password" maxlength="32" name="plum" id="plum" title="Minimum of 8 characters in length." value="" /><br />
          <label for="apple" class="required">New Password (again)</label>
          <input type="password" maxlength="32" name="apple" id="apple" title="Minimum of 8 characters in length." value="" /><br />
          <input type="hidden" name="pass" value="<?php print sha1( mktime( 0, 0, 0, date( "m" ) , date( "d" ) + 1, date( "Y" ) ) . md5(floor(intval(date('b'))))); ?>" /><input value="Save" type="submit" />
        </div>
      </form>
<?php
			}
		}elseif (!empty($_POST['u'])) {
?>
      <p>&nbsp;</p>
      <h2>User managment</h2>
<?php
			$result = @sql_query('SELECT a.username, a.email, a.name, b.name AS code FROM users AS a, usercodes AS b WHERE (a.username' . sql_reg(addslashes(strip_tags(trim($_POST['u'])))) . ' OR a.name' . sql_reg(addslashes(strip_tags(trim($_POST['u'])))) . ' OR a.email' . sql_reg(addslashes(strip_tags(trim($_POST['u'])))) . ' OR a.institution' . sql_reg(addslashes(strip_tags(trim($_POST['u'])))) . ' OR a.task' . sql_reg(addslashes(strip_tags(trim($_POST['u'])))). ' OR  b.name' . sql_reg(addslashes(strip_tags(trim($_POST['u'])))) . ') AND a.code=b.code ORDER BY a.rights, a.name;', $sql);
			if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) > 0)) {
				print "      <ul>\n";
				while ($row = sql_fetch_array($result)) {
					print '        <li><a href="' . $config['server'] . 'admin/users/'.urlencode($row['username']).'" title="edit user profile">' . $row['name'] . '</a> &lt;' . $row['email'] . '&gt; ['. htmlentities($row['code'], ENT_QUOTES, 'ISO8859-1') ."]</li>\n";
				}
				print "      </ul>\n";
			} else {
				print "      <p>No user found, yet!</p>\n";
			}
		} else {
			$result=@sql_query('SELECT username, email, name, url FROM users WHERE rights=0 AND activated=0 ORDER BY name, rights;', $sql);
?>
      <p>&nbsp;</p>
      <h2>User managment</h2>
      <form method="post" id="signin" action="<?php print $config['server']; ?>admin/users">
        <div >
          <label for="u" class="required">Username</label>
          <input type="text" maxlength="32" name="u" id="u" /><br /><br />
          <input value="Search user" type="submit" />
        </div>
      </form>
<?php
			if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
				print '      <h3>New user'. ((sql_num_rows($result)>1)?'s':'')."</h3>\n      <ul>\n";
				while ($row=sql_fetch_array($result)) {
					print '        <li>' . $row['name'] . ' &lt;' . $row['email'] . '&gt;' . ((!empty($row['url']))?' [<a href="'.htmlentities($row['url']).'" rel="nofollow">link</a>]':'') . ' <a class="reviewoff" href="' . $config['server'] . 'admin/users?reviewed='.urlencode($row['username']).'" title="upgrade as member" rel="nofollow"><span>&#9787;</span></a><a class="remove" href="' . $config['server'] . 'admin/users?remove='.urlencode($row['username']).'" title="remove this guest" rel="nofollow"><span>&#10008;</span></a>' . "</li>\n";
				}
				print "      </ul>\n";
			}
		}
	} elseif (!empty($_GET['admin']) && ($_GET['admin']=='entries') ) {
		$result=@sql_query('SELECT a.id_recode, d.phylum, d.genome, e.name AS event, c.latin FROM recode2 AS a, recoding AS b, organisms AS c, kingdoms AS d, events AS e, status AS f WHERE a.organism = c.taxonid AND a.kingdom = d.kingdom AND a.status=f.status AND a.id_recode = b.id_recode AND b.event = e.event AND a.status=0  ORDER BY a.id', $sql);
?>
      <p>&nbsp;</p>
      <h2>Entry managment</h2>
<?php
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
?>
      <table summary="summary of the results" id="results">
        <thead>
          <tr>
            <th>&nbsp;</th>
            <th>Organism</th>
            <th>Kingdom</th>
            <th>Event</th>
            <th>Revision</th>
          </tr>
        </thead>
<?php
			print "        <tbody>\n";
			$i=1;
			while ($row=sql_fetch_array($result)) {
				$result2=@sql_query('SELECT max(revised) AS revised, count(*) AS cycles FROM revisions WHERE id_recode=\'' . $row['id_recode'] . '\';', $sql);
				if (!strlen($r=sql_last_error($sql)) && (sql_num_rows($result2)==1)) { $release=sql_fetch_array($result2); }
?>
          <tr<?php print ($i++%2?' class="alt"':''); ?>>
            <td><a href="<?php print $config['server']; ?>recode/<?php print urlencode($row['id_recode']); ?>/" title="more details"><img src="<?php print $config['server']; ?>images/query.png" class="dl" alt="more details" /></a></td>
            <td><em><?php print $row['latin']; ?></em></td>
            <td><?php print $row['phylum']. ' ('.$row['genome']; ?>)</td>
            <td><?php print $row['event']; ?></td>
            <td><?php print date('d-m-Y', strtotime($release['revised'])). ' (rev. '. $release['cycles'] .')'; ?></td>
          </tr>
<?php
			}
			print "        </tbody>\n      </table>\n";
		} else {

			print "      <p>No new entry, yet!</p>\n";

		}

		if ($_SESSION['login']['rights']>=9) {
			$result=@sql_query('SELECT a.id_recode, d.phylum, d.genome, e.name AS event, c.latin FROM recode2 AS a, recoding AS b, organisms AS c, kingdoms AS d, events AS e, status AS f WHERE a.organism = c.taxonid AND a.kingdom = d.kingdom AND a.status=f.status AND a.id_recode = b.id_recode AND b.event = e.event AND a.status=10 ORDER BY a.id', $sql);
			if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
?>
      <h2>Entry deleted</h2>
      <table summary="summary of the results" id="results">
        <thead>
          <tr>
            <th>&nbsp;</th>
            <th>Organism</th>
            <th>Kingdom</th>
            <th>Event</th>
            <th>Revision</th>
          </tr>
        </thead>
<?php
				print "        <tbody>\n";
				$i=1;
				while ($row=sql_fetch_array($result)) {
					$result2=@sql_query('SELECT max(revised) AS revised, count(*) AS cycles FROM revisions WHERE id_recode=\'' . $row['id_recode'] . '\';', $sql);
					if (!strlen($r=sql_last_error($sql)) && (sql_num_rows($result2)==1)) { $release=sql_fetch_array($result2); }
?>
          <tr<?php print ($i++%2?' class="alt"':''); ?>>
            <td><a href="<?php print $config['server']; ?>recode/<?php print urlencode($row['id_recode']); ?>/" title="more details"><img src="<?php print $config['server']; ?>images/query.png" class="dl" alt="more details" /></a></td>
            <td><em><?php print $row['latin']; ?></em></td>
            <td><?php print $row['phylum']. ' ('.$row['genome']; ?>)</td>
            <td><?php print $row['event']; ?></td>
            <td><?php print date('d-m-Y', strtotime($release['revised'])). ' (rev. '. $release['cycles'] .')'; ?></td>
          </tr>
<?php
				}
				print "        </tbody>\n      </table>\n";
			}
		}

	} elseif (!empty($_GET['admin']) && ($_GET['admin']=='comments') ) {
		if (!empty($_GET['reviewed'])) {
			$result = @sql_query('SELECT id, reviewed FROM blog WHERE id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['reviewed'])))) . '\';', $sql);
			if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
				$row=sql_fetch_array($result);
				$result = @sql_query('UPDATE blog SET reviewed=\''. ((!empty($row['reviewed']) && $row['reviewed']=='f')?'t':'f').'\' WHERE id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['reviewed'])))). '\';', $sql);
			}
		} elseif (!empty($_GET['remove'])) {
			$result = @sql_query('SELECT id FROM blog WHERE id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
			if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) == 1)) {
				$result = @sql_query('DELETE FROM blog WHERE id=\'' . sql_escape_string(urldecode(stripslashes(trim($_GET['remove'])))) . '\';', $sql);
			}
		}
		$result=@sql_query('SELECT a.id, a.id_recode, a.author, b.code, a.posted, a.comments, a.reviewed FROM blog AS a, users AS b WHERE a.author=b.username AND a.reviewed=\'f\' ORDER BY a.posted DESC;', $sql);
?>
      <p>&nbsp;</p>
      <h2>Comment managment</h2>
<?php
		if ((!strlen($r=sql_last_error($sql))) && (sql_num_rows($result)>0)) {
			print "      <ul>\n";
			while ($row=sql_fetch_array($result)) {
				print '        <li><strong><a href="' . $config['server'] . 'recode/'.urlencode($row['id_recode']).'" title="View details: '. $row['id_recode'] .'">' . $row['posted'] . '</a></strong> - ' . htmlentities($row['code'], ENT_QUOTES, 'ISO8859-1').$row['author'] . ' <a class="reviewoff" href="' . $config['server'] . 'admin/comments?reviewed='.urlencode($row['id']).'" title="mark as reviewed" rel="nofollow"><span>&#9787;</span></a><a class="remove" href="' . $config['server'] . 'admin/comments?remove='.urlencode($row['id']).'" title="remove this comment" rel="nofollow"><span>&#10008;</span></a><br />' . $row['comments'] . '.' . "</li>\n";
			}
			print "      </ul>\n";
		} else {
			print "      <p>No new comment, yet!</p>\n";
		}
	}
	htmlfooter();
} else {
  header("HTTP/1.0 404 Not Found");
	exit;
}
?>
