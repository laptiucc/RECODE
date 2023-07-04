<?php
$regsql = ' REGEXP ';

function sql_connect($database) {
	global $config;
	if ($config['sqlhost'] == 'localhost') {
		if ($result = mysqli_connect('localhost', (!empty($config['sqllogin'])?$config['sqllogin']:null), (!empty($config['sqllpassword'])?$config['sqllpassword']:null),$database)) {
			return $result;
		}
	} else {
		if ($result = mysqli_connect($config['sqlhost'] . (!empty($config['sqlport'])?':' . $config['sqlhost']:''), (!empty($config['sqllogin'])?$config['sqllogin']:null), (!empty($config['sqllpassword'])?$config['sqllpassword']:null),$database)) {
			return $result;
		}
	}
}

function sql_query($query, $link) {
	if ($result = mysqli_query($link,$query)) return $result;
}

function sql_reg($name) {
	return " LIKE \"%$name%\"";
}

function sql_last_error($link) {
	return mysqli_error($link);
}

function sql_num_rows($result) {
	if ($row = mysqli_num_rows($result)) return $row;
}

function sql_escape_string($result, $link) {
	return mysqli_escape_string($link, $result);
}

function sql_fetch_array($result) {
	if ($array = mysqli_fetch_array($result, MYSQLI_ASSOC)) return $array;
}

function sql_limit($min = 0, $size = 30) {
	return " LIMIT $min, $size;";
}


?>
