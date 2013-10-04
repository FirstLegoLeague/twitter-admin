<?php

require_once('MDB2.php');
require_once('../twitterdb.php');

/* Initialise */
$config = require('../config.inc.php');
$db = new TwitterDB($config['db_dsn']);
$db->connect();

/* Handle requested action, if any */
if (isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
	if ($action == 'approve')
		$db->approve(intval($_REQUEST['id']));
	elseif( $action == 'auto_approve')
	{
		$db->approve(intval($_REQUEST['id']));
		$db->autoApprove($_REQUEST['screen_name'], true);
	}
	elseif ($action == 'disapprove')
		$db->disapprove(intval($_REQUEST['id']));
	elseif ($action == 'auto_disapprove')
	{
		$db->disapprove(intval($_REQUEST['id']));
		$db->autoApprove($_REQUEST['screen_name'], false);
	}
	else
		die("Unknown action '$action'");
	/* Redirect back to list */
	header('Location: http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
	exit(0);
}

$count = 20;
if (isset($_REQUEST['count'])) {
	$count = intval($_REQUEST['count']);
}

/* Prepare state for displaying next page */
$tweets = $db->getList($count);

header("Content-Type: text/html;charset=utf-8");

$classes = array(
	'0' => 'pending',
	'1' => 'approved',
	'-1' => 'disapproved'
);

?>
<html>
<head>
	<title>FLL Twitter approval</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link href="moderate.css" rel="stylesheet" type="text/css" />
</head>
<body>
Displaying up to <?= $count ?> latest tweets.<br />
<br />
<table>
	<tr>
		<th>Actions</th><th>Time</th><th>User</th><th>Message</th>
	</tr>
<?php foreach ($tweets as $i => $tweet) { ?>
	<tr class="<?= ($i % 2)?'odd':'even' ?> <?= $classes[$tweet['approved']] ?>">
		<td class="nowrap">
			<?php
				if ($tweet['approved'] <= 0) {
			?>
			<a href="?action=approve&id=<?= $tweet['id'] ?>" title="Approve">App</a>
			<a href="?action=auto_approve&id=<?= $tweet[ 'id' ] ?>&screen_name=<?= $tweet[ 'screen_name' ] ?>" title="Auto-approve user">AutoA</a>
			<?php
				}
				if ($tweet['approved'] >= 0) {
			?>
			<a href="?action=disapprove&id=<?= $tweet['id'] ?>" title="Disapprove">Dis</a>
			<a href="?action=auto_disapprove&id=<?= $tweet[ 'id' ] ?>&screen_name=<?= $tweet[ 'screen_name' ] ?>" title="Auto-disapprove user">AutoD</a>
			<?php
				}
			?>
		</td>
		<td class="nowrap"><?= $tweet['created_at'] ?></td>
		<td class="nowrap"><?= $tweet['screen_name'] ?></td>
		<td><?= $tweet['message'] ?></td>
	</tr>
<?php } ?>
</body>
</html>
