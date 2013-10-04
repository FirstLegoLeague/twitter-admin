<?php

require_once('MDB2.php');
require_once('../../twitterdb.php');

/* Initialise */
$config = require('../../config.inc.php');
$db = new TwitterDB($config['db_dsn']);
$db->connect();

header("Content-Type: text/plain;charset=utf-8");

$ids = array();
if (isset($_REQUEST['ids'])) {
	$ids = explode(',',$_REQUEST['ids']);
}
if (count($ids) == 0) {
	print("Need ID's\n");
	exit(1);
}

$display_id = 0;
if (isset($_REQUEST['display_id'])) {
	$display_id = intval($_REQUEST['display_id']);
}

/* Mark tweets as deleted from display */
$db->setDeleted($display_id, $ids);

?>
