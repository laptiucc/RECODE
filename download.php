<?php
require_once 'includes/main.inc';

htmlheader(true);
?>
      <p>&nbsp;</p>
      <h2>Download</h2>
      <p>Individual entries can be download in <a href="<?php print $config['server']; ?>dtd">RecodeML</a> format. The entiere database in available in SQL format for an easy integration. (RecodeML format is only available upon request). You can download the <a href="<?php print $config['server']; ?>db/schema-1.2.pgsql.7z" type="application/x-7z-compressed">Recode2 SQL schema</a> (version 1.2). The updates are generated once per month.</p>
<?php
$result = @sql_query('SELECT filename, version, entries, size, backuped, signature FROM downloads ORDER BY backuped DESC;', $sql);
if (!strlen($r = sql_last_error($sql)) && (sql_num_rows($result) > 0)) {
	print "      <ul>\n";
	while ($row = sql_fetch_array($result)) {
		if (is_readable('db/'.$row['filename'])) {
			print '        <li><strong>' . date('F Y', strtotime($row['backuped'])) . '</strong> - <a href="' . $config['server'] . 'db/' . $row['filename'] . '" type="application/octet-stream">' . $row['filename'] . '</a> 
<small>('.round($row['size']/1048576, 1).' Mo)</small> - Database (SQL schema ' . $row['version'] . '), ' . $row['entries'] . ' entries<br/>sha1: '.wordwrap($row['signature'],4,' ',true)."</li>\n";
		}
	}
	print "      </ul>\n";
} else {
	print "      <p>no download found, yet!</p>\n";
}
?>
      <h2>Database Licence</h2>
      <p id="licence"><a href="http://creativecommons.org/licenses/by-nc-sa/3.0/" rel="nofollow"><img class="licence" alt="Creative Commons Licence" src="<?php print $config['server']; ?>images/byncsa.png" /></a>Recode<sup class="r2">2</sup> database is licenced under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/">Creative Commons Attribution-Non-Commercial-Share Alike 3.0 Unported Licence</a>. Permissions beyond the scope of this licence may be granted by contacting <a href="#" class="zulu" rel="nofollow" rev="NhTnVnQ1IuMZZhjF">Pasha V Baranov</a>.</p>
<?php
htmlfooter();
?>
