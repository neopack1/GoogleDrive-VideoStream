<?php

require('GoogleDriveAPI2.php');
require('dbm.php');

$username = '';
$code = '';

parse_str($_SERVER['QUERY_STRING']);

if ($code != ''){
	print "code = ".$code."\n";
}
if ($username == ''){
	print "Please specify a username.\n";
	exit;
}

$gd = new GoogleDrive($username, $code);


?>
