<?php

return array(
	// Go to https://dev.twitter.com/apps to obtain these tokens
	'oauth_token' => '',
	'oauth_secret' => '',
	'consumer_key' => '',
	'consumer_secret' => '',

	'track' => array('word', '#hashtag', '@user'),
	'db_dsn' => array(
		'phptype' => 'mysql',
		'database' => 'twitter',
		'username' => 'twitter',
		'password' => 'password',
	),
);

?>
