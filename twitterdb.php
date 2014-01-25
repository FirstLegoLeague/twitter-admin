<?php

class TwitterDB {
	public function __construct($dsn) {
		$this->dsn = $dsn;
	}

	/**
	 * Check whether result is not an error, if it is print a message and abort.
	 */
	public function assert($result) {
		if (PEAR::isError($result)) {
			die($result->getMessage() . ' - ' . $result->getUserInfo());
		}
	}

	/**
	 * Connect to the database. Aborts if connection fails.
	 */
	public function connect() {
		$this->db = MDB2::connect($this->dsn);
		$this->assert($this->db);
	}

	public function idle() {
		$result = $this->db->queryOne("SELECT 1;");
		$this->assert($result);
	}

	/**
	 * Insert given tweet in database.
	 */
	public function insert($tweet) {
		/* Insert in database */
		$query = sprintf("INSERT INTO tweets " .
				"(status_id, created_at, screen_name, message, approved, data) " .
				"VALUES (%s, %s, %s, %s, %s, %s)",
				$this->db->quote($tweet['status_id']),
				$this->db->quote(date('Y-m-d H:i:s', $tweet['created_at'])),
				$this->db->quote($tweet['screen_name']),
				$this->db->quote($tweet['text']),
				$tweet['approved'],
				$this->db->quote($tweet['data']));
		$result = $this->db->exec($query);
		$this->assert($result);
	}

	/**
	 * Check whether user is auto-approved (1), auto-disapproved (-1) or
	 * should be moderated as usual.
	 */
	public function getApprovedStatus($screen_name) {
		$query = "SELECT approved FROM auto_approve
				WHERE screen_name = " . $this->db->quote($screen_name);
		$result = $this->db->queryOne($query);

		if (PEAR::isError($result)) {
			$approve = 0; /* pending */
		} else {
			$approve = intval($result);
		}
		return $approve;
	}

	/**
	 * Obtain basic statistics out of DB.
	 * Returns an assoc array.
	 */
	public function getStats() {
		/* Get number of approved/disapproved/pending tweets */
		$query = "
			SELECT approved, COUNT(*)
			FROM tweets
			GROUP BY approved
		";
		$appr = $this->db->queryAll($query, NULL, MDB2_FETCHMODE_DEFAULT,
			true);
		$this->assert($appr);
		$pend = isset($appr[0]) ? $appr[0] : 0;
		$app = isset($appr[1]) ? $appr[1] : 0;
		$dis = isset($appr[-1]) ? $appr[-1] : 0;
		return array(
			'approved' => $app,
			'pending' => $pend,
			'disapproved' => $dis,
			'total' => $app + $pend + $dis
		);
	}

	/**
	 * Return the latest $count tweets, irrespective of their moderation
	 * status.
	 */
	public function getList($count = 20) {
		$query = "
			SELECT *
			FROM tweets AS t
			ORDER BY t.id DESC
			LIMIT $count;
		";
		$tweets = $this->db->queryAll($query, NULL, MDB2_FETCHMODE_ASSOC);
		$this->assert($tweets);
		return $tweets;
	}

	/**
	 * Get oldest unmoderated tweet.
	 */
	public function getPendingApproval() {
		$query = "
			SELECT *
			FROM tweets
			WHERE approved=0
			ORDER BY id
			LIMIT 1
		";
		$result = $this->db->queryRow($query, NULL, MDB2_FETCHMODE_ASSOC);
		$this->assert($result);
		return $result;
	}

	/**
	 * Update the tweets database based on the (changed) auto_approved status of the user
	 */
	public function updateOnApproved($id) {
		$query = "
			UPDATE tweets
			SET approved = (
				SELECT approved
				FROM auto_approve
				WHERE screen_name = ".$this->db->quote($id)."
			) WHERE screen_name = ".$this->db->quote($id);
		$result = $this->db->exec($query);
		$this->assert($result);
	}

	/**
	 * Get automatically (dis-)approved users
	 */
	 public function getApprovedUsers() {
		$query = "
			SELECT a.screen_name, a.approved, COUNT(t.screen_name)
			FROM auto_approve AS a
			LEFT JOIN tweets AS t USING(screen_name)
			GROUP BY a.screen_name, a.approved, t.screen_name
			ORDER BY approved DESC, screen_name ASC
		";
		$result = $this->db->queryAll($query, NULL, MDB2_FETCHMODE_DEFAULT, true);
		$this->assert($result);
		return $result;
	 }

	/**
	 * Approve the given tweet (where id is the ID in the local DB).
	 */
	public function approve($id) {
		$query = "UPDATE tweets SET approved=1 WHERE id=$id";
		$result = $this->db->exec($query);
		$this->assert($result);
	}

	/**
	 * Unset auto (dis)approved user
	 */
	public function unsetApproved($id)
	{
		$query = "
			DELETE FROM auto_approve
			WHERE screen_name = ".$this->db->quote($id);
		$result = $this->db->exec($query);
		$this->assert($result);
	}

	/**
	 * Change auto approved to auto-disapproved and vv
	 */
	public function switchAutoApproved($screen_name)
	{
		$query = "
			UPDATE auto_approve
			SET approved = -approved
			WHERE screen_name = ".$this->db->quote($screen_name);
		$result = $this->db->exec($query);
		$this->assert($result);
	}

	/**
	 * Dispprove the given tweet (where id is the ID in the local DB).
	 */
	public function disapprove($id) {
		$query = "UPDATE tweets SET approved=-1 WHERE id=$id";
		$result = $this->db->exec($query);
		$this->assert($result);
	}

	/**
	 * Auto (dis-)approve the give user
	 */
	public function autoApprove($screen_name, $approval) {
		// Prevent errors when implicitly changing approval
		// state for this user.
		$this->unsetApproved($screen_name);
		$approval = ($approval) ? 1 : -1;
		$query = "
			INSERT INTO auto_approve (screen_name, approved)
			VALUES (" . $this->db->quote($screen_name) . ", $approval)
		";
		$result = $this->db->exec($query);
		$this->assert($result);
	}

	/**
	 * Mark the given tweet ID's as 'shown' in DB.
	 */
	public function setShown($display_id, $ids) {
		/* Force ID's to be integers */
		// TODO: make this work for 64bit ints
		/* Find list of ID's already inserted in DB */
		$id_str = implode(" OR status_id=", $ids);
		$query = "
			SELECT status_id
			FROM shown
			WHERE display_id=$display_id AND
				(status_id=$id_str)
		";
		$existing_ids = $this->db->queryCol($query);
		$this->assert($existing_ids);
		/* Update existing ID's */
		print_r($existing_ids);
		if (count($existing_ids) > 0) {
			$id_str = implode(" OR status_id=", $existing_ids);
			$query = "
				UPDATE shown SET shown=NOW()
				WHERE display_id=$display_id
					AND (status_id=$id_str)
			";
			$result = $this->db->exec($query);
			$this->assert($result);
		}
		/* Insert remaining ID's */
		$rem_ids = array_diff($ids, $existing_ids);
		print_r($rem_ids);
		if (count($rem_ids) > 0) {
			$ins_str = array();
			foreach($rem_ids as $id) {
				$ins_str[] = "('" . $id . "', " . $display_id . ", now())";
			}
			$ins_str = implode(",", $ins_str);
			$query = "INSERT INTO shown VALUES $ins_str";
			$result = $this->db->exec($query);
			$this->assert($result);
		}
	}

	/**
	 * Mark the given tweet ID's as 'deleted from display' in DB.
	 */
	public function setDeleted($display_id, $ids) {
		/* Force ID's to be integers */
		// TODO: make this work for 64bit ints
		/* Delete ID's from DB, to indicate that they have been
		 * 'unshown' */
		$id_str = implode(" OR status_id=", $ids);
		$query = "
			DELETE FROM shown
			WHERE display_id=$display_id AND
				(status_id=$id_str)
		";
		$result = $this->db->exec($query);
		$this->assert($result);
	}

	/**
	 * Return the next tweet to be displayed.
	 */
	public function getTweets($display_id, $count) {
		$tweets = array();
		do {
			/* Get tweets that need to be deleted first */
			$query = "
				SELECT t.status_id, t.id, s.shown, 0 as created_at,
					t.screen_name, t.message
				FROM tweets AS t
				LEFT JOIN shown AS s ON s.status_id = t.status_id AND s.display_id = $display_id
				WHERE t.approved<1 AND s.shown IS NOT NULL
				ORDER BY t.id DESC
				LIMIT 100;
			";
			$result = $this->db->queryAll($query, NULL, MDB2_FETCHMODE_ASSOC);
			$this->assert($result);
			$tweets = array_merge($tweets, $result);
			$count -= count($tweets);
			if ($count <= 0)
				break;

			/* Get non-shown approved tweets first */
			$query = "
				SELECT t.status_id, t.id, s.shown, t.created_at,
					t.screen_name, t.message
				FROM tweets AS t
				LEFT JOIN shown AS s ON s.status_id = t.status_id AND s.display_id = $display_id
				WHERE t.approved=1 AND s.shown IS NULL
				ORDER BY t.id DESC
				LIMIT $count
			";
			$result = $this->db->queryAll($query, NULL, MDB2_FETCHMODE_ASSOC);
			$this->assert($result);
			$tweets = array_merge($tweets, $result);
			$count -= count($tweets);
			if ($count <= 0)
				break;

			/* Get shown approved tweets next (if needed) */
			$query = "
				SELECT t.status_id status_id, t.id, s.shown, t.created_at,
					t.screen_name, t.message
				FROM tweets AS t
				LEFT JOIN shown AS s ON s.status_id = t.status_id AND s.display_id = $display_id
				WHERE t.approved=1 AND s.shown IS NOT NULL
				ORDER BY s.shown DESC
				LIMIT $count
			";
			$result = $this->db->queryAll($query, NULL, MDB2_FETCHMODE_ASSOC);
			$this->assert($result);
			$tweets = array_merge($tweets, $result);
		} while (false);
		return $tweets;
	}
};

?>
