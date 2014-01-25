#!/usr/bin/php5
<?php

if (php_sapi_name() !== 'cli')
	die("This script must be run in CLI-mode");

/* You'll need the following extra packages:
 * php-mdb2-driver-mysql
 */

require_once('phirehose/lib/Phirehose.php');
require_once('phirehose/lib/OauthPhirehose.php');
require_once('twitterdb.php');
require_once('MDB2.php');

class Poller extends OauthPhirehose {
	/**
	 * Construct twitter stream collecter.
	 */
	public function __construct() {
		$this->config = require("./config.inc.php");
		$this->db = new TwitterDB($this->config['db_dsn']);
		define("OAUTH_TOKEN", $this->config['oauth_token']);
		define("OAUTH_SECRET", $this->config['oauth_secret']);
		define("TWITTER_CONSUMER_KEY", $this->config['consumer_key']);
		define("TWITTER_CONSUMER_SECRET", $this->config['consumer_secret']);
		return parent::__construct(
			$this->config['oauth_token'],
			$this->config['oauth_secret'],
			Phirehose::METHOD_FILTER,
			Phirehose::FORMAT_JSON
		);
	}

	public function reloadConfig() {
		$config = include("./config.inc.php");
		/* TODO: error checking */
		$this->config = $config;
	}

	/**
	 * Convert raw status string to more easily handled associate array.
	 */
	public function rawToTweet($status) {
		/* Convert raw status string to associative array */
		$data = json_decode($status, true);

		/* Check sanity */
		if (!is_array($data) || !isset($data['user']['screen_name']))
			return NULL;

		/* Convert raw tweet to parsed version */
		$tweet = array(
			'status_id' => $data['id_str'],
			'screen_name' => $data['user']['screen_name'],
			'text' => $data['text'],
			'created_at' => strtotime($data['created_at']),
			'data' => $status,
		);

		return $tweet;
	}

	/**
	 * Called when new raw tweet (as string) arrives.
	 */
	public function enqueueStatus($status) {
		$tweet = $this->rawToTweet($status);
		if ($tweet !== NULL) {
			/* Determine auto-(dis)approve status of tweet, if any */
			/* TODO: Maybe cache this list (e.g. only update it checkFilterPredicates()) */
			$tweet['approved'] = $this->db->getApprovedStatus($tweet['screen_name']);

			print_r($tweet);

			/* Insert in DB */
			$this->db->insert($tweet);
		}
	}

	/**
	 * Periodically called to update filter keywords. It's possible to
	 * dynamically update the predicate by changing it in the config file.
	 * It can take up to 2 minutes for the change to take effect.
	 */
	public function checkFilterPredicates() {
		$this->reloadConfig();
		$this->setTrack($this->config['track']);
		$this->db->idle();
	}

	public function run() {
		$this->db->connect();
		$this->consume();
	}
}

$poller = new Poller();
$poller->run();

?>
