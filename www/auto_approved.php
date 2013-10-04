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
	if ($action == 'unset')
		$db->unsetApproved($_REQUEST['screen_name']);
	elseif( $action == 'toggle_approve')
	{
		$db->switchAutoApproved( $_REQUEST[ 'screen_name' ] );
	}
	elseif ($action == 'toggle_approve_all')
	{
		$db->switchAutoApproved( $_REQUEST[ 'screen_name' ] );
		$db->updateOnApproved( $_REQUEST[ 'screen_name' ] );
	}
	else
		die("Unknown action '$action'");
	/* Redirect back to index */
	header('Location: http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);
	exit(0);
}

/* Prepare state for displaying next page */
$stats = $db->getApprovedUsers();

$approved = array();
$disapproved = array();

if (count($stats) > 0) {
	foreach( $stats as $key => $value ) {
		if ($value[0] == 1) {
			$approved[] = array($key, $value[1]);
		} else if ($value[0] == -1) {
			$disapproved[] = array($key, $value[1]);
		}
	}
}

header("Content-Type: text/html;charset=utf-8");

?>
<html>
<head>
	<title>FLL Twitter automatically approved users</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link href="moderate.css" rel="stylesheet" type="text/css" />
</head>
<body>


Approved:<br />
<table>
	<tr>
		<th>User</th><th>#msgs</th><th>Actions</th>
	</tr>
<?php foreach ($approved as $i => $value) { ?>
	<tr class="<?= ($i % 2)?'odd':'even' ?>">
		<td><?= $value[0] ?></td>
		<td><?= $value[1] ?></td>
		<td>
			<a href="?action=unset&screen_name=<?= $value[0] ?>">unset</a> -
			<a href="?action=toggle_approve&screen_name=<?= $value[0] ?>">disapprove user</a> -
			<a href="?action=toggle_approve_all&screen_name=<?= $value[0] ?>">disapprove user and all past messages</a>
		</td>
	</tr>
<?php } ?>
</table>
<br />
Disapproved:<br />
<table>
	<tr>
		<th>User</th><th>#msgs</th><th>Actions</th>
	</tr>
<?php foreach ($disapproved as $i => $value) { ?>
	<tr class="<?= ($i % 2)?'odd':'even' ?>">
		<td><?= $value[0] ?></td>
		<td><?= $value[1] ?></td>
		<td>
			<a href="?action=unset&screen_name=<?= $value[0] ?>">unset</a> -
			<a href="?action=toggle_approve&screen_name=<?= $value[0] ?>">approve user</a> -
			<a href="?action=toggle_approve_all&screen_name=<?= $value[0] ?>">approve user and all past messages</a>
		</td>
	</tr>
<?php } ?>
</table>
</body>
</html>
