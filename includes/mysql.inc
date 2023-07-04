<?php
$regsql = ' REGEXP ';

function sql_connect($database) {
	global $config;
	if ($config['sqlhost'] == 'localhost') {
		if ($result = mysql_connect('localhost:/tmp/mysql.lock', (!empty($config['sqllogin'])?$config['sqllogin']:null), (!empty($config['sqllpassword'])?$config['sqllpassword']:null))) {
			if (mysql_select_db($database, $result)) return $result;
		}
	} else {
		if ($result = mysql_connect($config['sqlhost'] . (!empty($config['sqlport'])?':' . $config['sqlhost']:''), (!empty($config['sqllogin'])?$config['sqllogin']:null), (!empty($config['sqllpassword'])?$config['sqllpassword']:null))) {
			if (mysql_select_db($database, $result)) return $result;
		}
	}
}

function sql_query($query, $link) {
	if ($result = mysql_query($query, $link)) return $result;
}

function sql_reg($name) {
	return " LIKE \"%$name%\"";
}

function sql_last_error($link) {
	return mysql_error();
}

function sql_num_rows($result) {
	if ($row = mysql_num_rows($result)) return $row;
}

function sql_escape_string($result) {
	return mysql_escape_string($result);
}

function sql_fetch_array($result) {
	if ($array = mysql_fetch_array($result, MYSQL_ASSOC)) return $array;
}

function sql_limit($min = 0, $size = 30) {
	return " LIMIT $min, $size;";
}


?>
