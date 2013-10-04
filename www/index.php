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
	elseif ($action == 'disapprove')
		$db->disapprove(intval($_REQUEST['id']));
	elseif( $action == 'auto_approve')
	{
		$db->approve(intval($_REQUEST['id']));
		$db->autoApprove($_REQUEST['screen_name'], true);
	}
	elseif ($action == 'auto_disapprove')
	{
		$db->disapprove(intval($_REQUEST['id']));
		$db->autoApprove($_REQUEST['screen_name'], false);
	}
	else
		die("Unknown action '$action'");
	/* Redirect back to index */
	header('Location: http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
	exit(0);
}

/* Prepare state for displaying next page */
$stats = $db->getStats();
$tweet = $db->getPendingApproval();

header("Content-Type: text/html;charset=utf-8");

?>
<html>
<head>
	<title>FLL Twitter approval</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link href="moderate.css" rel="stylesheet" type="text/css" />
	<?php if ($stats['pending'] == 0) { ?>
	<meta http-equiv="refresh" content="10" />
	<?php } ?>
</head>
<body>
<table>
	<tr><th>Type</th><th>#msgs</th></tr>
	<tr><td>Approved:</td><td><?= $stats['approved'] ?></td></tr>
	<tr><td>Disapproved:</td><td><?= $stats['disapproved'] ?></td></tr>
	<tr><td>Pending:</td><td><?= $stats['pending'] ?></td></tr>
</table>
<br />
<table>
	<tr><th>Field</th><th>Content</th></tr>
	<tr><td>Time:</td><td><?= $tweet['created_at'] ?></td></tr>
	<tr><td>User:</td><td><?= $tweet['screen_name'] ?></td></tr>
	<tr><td>Text:</td><td><?= $tweet['message'] ?></td></tr>
	<tr>
		<td>Actions:</td>
		<td>
			<?php if( $tweet[ 'id' ] ) { ?>
			<a href="?action=approve&id=<?= $tweet['id'] ?>" class="default_action">Approve</a> -
			<a href="?action=disapprove&id=<?= $tweet['id'] ?>">Disapprove</a> -
			<a href="?action=auto_approve&id=<?= $tweet[ 'id' ] ?>&screen_name=<?= $tweet[ 'screen_name' ] ?>">Auto approve this user</a> -
			<a href="?action=auto_disapprove&id=<?= $tweet[ 'id' ] ?>&screen_name=<?= $tweet[ 'screen_name' ] ?>">Auto disapprove this user</a>
			<?php } ?>
		</td>
	</tr>
</table>
<ul>
	<li><a href="auto_approved.php">Auto-(dis-)approved users</a></li>
	<li><a href="list.php">List of all tweets</a></li>
</ul>
</body>
</html>
