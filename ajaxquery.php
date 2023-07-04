<?php
$fields=array('species');
if (!empty($_GET['query']) && !empty($_GET['field']) && in_array(strtolower($_GET['field']), $fields)) {
	require_once 'includes/main.inc';
	$input = sql_escape_string( strtolower( $_GET['query'] ) );
	$field = strtolower( $_GET['field'] );
	$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 25;
	switch ($field) {
	case 'species':
		$result = sql_query('SELECT latin, synonym FROM organisms WHERE latin'.sql_reg($input).' ORDER BY latin'.sql_limit(0, $limit), $sql);
		break;
	}
	header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	header("Content-Type: text/xml");
	print '<'.'?xml version="1.0" encoding="utf-8" ?'.'><results>';
	if ((!strlen($r = sql_last_error($sql))) && (sql_num_rows($result) > 0)) {
		$i=1;
		while ($row = sql_fetch_array($result)) {
			print '<rs id="'.$i++.'" info="'.(isset($row['synonym'])?htmlentities($row['synonym']):'').'">'.htmlentities($row['latin']).'</rs>';
		}
	}
	print "</results>\n";
}
?>
