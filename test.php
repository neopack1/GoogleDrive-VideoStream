<?php

require('config.php');

require('GoogleDriveAPI2.php');
require('dbm.php');

$username = '';
$code = '';
$folder = '';
$file = '';
$playback = '';

parse_str($_SERVER['QUERY_STRING']);

if ($code != ''){
	print "code = ".$code."\n";
}
if ($username == ''){
	print "Please specify a username.\n";
	exit;
}

$gd = new GoogleDrive($username, $code);

if ($file != ''){
	$gd->getVideoURLs($file, $playback);

}elseif($folder != ''){
	$gd->getFolder($folder);
}else{
	$gd->getFolder('root');

}

?>
