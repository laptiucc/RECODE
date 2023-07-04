<?php
$config = array(
	'powered' => 'Recode2 v1.18 (2011-01-14)',
	'path' => '/',
	'server' => 'http://recode.ucc.ie/',
//	'server' => 'http://143.239.109.104:1000/',
	'google' => 'UA-8953560-1',
	'login' => ((isset($_SESSION['login']['user_id']) && isset($_SESSION['login']['ip']) && isset($_COOKIE['user_id']) && ($_SESSION['login']['user_id'] == strip_tags(trim($_COOKIE['user_id']))) && ($_SESSION['login']['ip'] == $_SERVER['REMOTE_ADDR']))?true:false),
	'mirror' => 'cork',
 	'master' => 'http://recode.ucc.ie/',
// 	'master' => 'http://143.239.109.104:1000/',
	'sqlreplica' => 'master',
	'sqlserver' => 'postgresql',
	'sqlport' => '',
	'sqlhost' => 'localhost',
	'sqlpassword' => '',
	'sqllogin' => 'apache',
	'db' => 'recode'
);

$mirror=array(
	'cork' => array ( 'name'=>'Irish', 'url'=>'http://recode.ucc.ie/', 'begin'=>-30, 'end'=>150 ),
	'utah' => array ( 'name'=>'US', 'url'=>'http://recode.genetics.utah.edu/', 'begin'=>150, 'end'=>-30, 'thanks'=>'This mirror is provided by the <a href="http://genetics.utah.edu/">Eccles Institute of Human Genetics</a> at the <a href="http://www.utah.edu/">University of Utah</a>' )
);
?>
