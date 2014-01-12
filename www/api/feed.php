<?php

require_once('MDB2.php');
require_once('../../twitterdb.php');

/* Initialise */
$config = require('../../config.inc.php');
$db = new TwitterDB($config['db_dsn']);
$db->connect();

header("Content-Type: text/plain;charset=utf-8");

$count = 5;
if (isset($_REQUEST['count'])) {
	$count = intval($_REQUEST['count']);
	if ($count > 20)
		$count = 20;
}

$display_id = 0;
if (isset($_REQUEST['display_id'])) {
	$display_id = intval($_REQUEST['display_id']);
}

/* Return next tweets to be displayed, or nothing if nothing is to be shown... */
$tweets = $db->getTweets($display_id, $count);
foreach($tweets as $tweet) {
	print(sprintf("%s,%s,%s,%s,%s,%s\n",
			$tweet['id'],
			$tweet['status_id'],
			$tweet['shown'],
			$tweet['created_at'],
			$tweet['screen_name'],
			strtr($tweet['message'], "\n", " ")
	));
}

?>
