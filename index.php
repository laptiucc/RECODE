<?php
require_once 'includes/main.inc';

if (count($mirror) > 1) {
	if ( isset( $_GET[ 'mirror' ] ) ) {
		setcookie('mirror', $mirror[$config['mirror']]['name']. ' node', mktime(1, 0, 0, date('m')+1, date('d'), date('Y')), $config['path']);
		$err = 'You have been redirected to the '.$mirror[$config['mirror']]['name'].' mirror.';
	} elseif ( empty( $_COOKIE[ 'mirror' ] ) ) {
		$ip = $_SERVER[ 'REMOTE_ADDR' ];
		if ( ( $results = file_get_contents( "http://api.hostip.info/get_html.php?ip=$ip&position=true" ) ) !== FALSE ) {
			preg_match( '/Longitude: (.*)/', $results, $lon );
			if ( $lon[1] != '' ) {
				foreach ($mirror as $key => $value) {
					if ( ( $key!=$config['mirror'] ) && ( floatval( $lon[1] ) >= $value['start'] ) && ( floatval( $lon[1] ) < $value['end'] ) ) {
						header('Location: ' . $value['url'] .'?mirror='.$config['mirror'],TRUE,307);
						exit;
					}
				}
			}
			setcookie('mirror', $mirror[$config['mirror']]['name']. ' node', mktime(1, 0, 0, date('m')+1, date('d'), date('Y')), $config['path']);
		}
	}
}

$history=get_switch('history');
if (!empty($_SESSION['login']) && strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'xh')) $debug=get_switch('debug');

htmlheader();
if (!empty($err)) { print "      <p>&nbsp;</p>\n      <div id=\"error\"><div>$err</div></div>\n"; }
?>
      <form id="search" method="get" action="<?php print $config['server']; ?>search">
        <div>
          <img src="<?php print $config['server']; ?>images/recode2_logo.png" alt="Recode2" height="110" width="276" />
        </div>
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
if (!empty($_SESSION['login']) && !empty($_SESSION['login']['rights']) ) { print '      <p>Edit your <a href="' . $config['server'] . 'profile">personal info</a> or change your <a href="' . $config['server'] . 'change">password</a>.' . (($_SESSION['login']['rights']>=8 ) ? (($_SESSION['login']['rights']>=9 ) ? ' Manage the <a href="' . $config['server'] . 'admin/users">new users</a>, r':'R').'eview the <a href="' . $config['server'] . 'admin/entries">new entries</a> and <a href="' . $config['server'] . 'admin/comments">comments</a>' : '') . "</p>\n";
}

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
