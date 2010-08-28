<?php

class UGC extends Database {

	const HASH_SEP = '.:.';

	public $openid;

	function __construct($config) {

		parent::__construct();

		$this->config = &$config;
		$this->openid = new SimpleOpenID();

		$this->connect($config['DB']['Server'], $config['DB']['User'], $config['DB']['Pass'], $config['DB']['Name']);

	}

	/**
	 * Validate the POST vars for submitting a comment
	 * Note: this function assumes the item the comment is attached to is valid
	 * @param string $comment The comment to check against
	 * @return array An array of error messages (emtpy if valid)
	 * @member UGC
	 * @public
	 */
	function checkPutComment($section, $vars = null) {

		if ($vars === null) {
			$vars = &$_POST;
		}

		$errors = Array();

		// Setup vars for easy reference
		$comment = trim($_POST['comment']);
		$delay = $this->config['Comments'][$section]['Delay'];
		$len = strlen($comment);
		$len_min = $this->config['Comments']['Min_Length'];
		$len_max = $this->config['Comments']['Max_Length'];

		// First check the delay, since we should shut users down immediately if they have to wait
		if ($delay) {
			if ($this->checkCommentRecent($delay)) {
				$errors['comment'] = "Please wait $delay seconds between comments.";
			}
		}

		// Check the length
		if ($len == 0) {
			$errors['comment'] = 'Comment is a required field.';
		} else if ($len < $len_min) {
			$errors['comment'] = sprintf(
				'Comments must be at least %s characters (currently %s).',
				number_format($len_min),
				number_format($len)
			);
		} else if ($len > $len_max) {
			$errors['comment'] = sprintf(
				'Comments cannot be more than %s characters (currently %s).',
				number_format($len_max),
				number_format($len)
			);
		}

		return $errors;

	}

    /**
	 * Set a cookie on the user's harddrive and redirect if all went well
	 * @param int user_id The user ID to login as
	 * @param string password The password to secure
	 * @param bool redirect Whether to redirect the user or not
     */
    function execAuth($user_id, $redirect = false) {

		$user_id = $this->openid->openID_standardize($user_id);

		// Prepare hash
		$hash = $this->formatSalt($user_id);

        // Prep cookie vars
        $cookie_name = $this->config['Users']['Cookie_Name'];
        $cookie_time = (time() + $this->config['Users']['Cookie_Duration']);
        $cookie_value = base64_encode($user_id . self::HASH_SEP . $hash);

        setcookie($cookie_name, $cookie_value, $cookie_time, $this->config['Users']['Cookie_Path']);

        // Only redirect if the cookie was set successfully
        if ($redirect) {

			$red = $this->config['Users']['Auth_Redirect'];

			if ($red) {

				// Natural true means http referrer!
				if (($red === true) && $_SERVER['HTTP_REFERRER']) {
					$red = $_SERVER['HTTP_REFERRER'];
				}

				Tools::redirect($red);

			}

        }

    }

	/**
	 * Log a user out
	 * @param bool redirect Whether to redirect the user or not
	 */
    function execLogout($redirect = false) {

        $cookie_name = $this->config['Users']['Cookie_Name'];
		$cookie_time = (time() - 86400); // Expired yesterday

        setcookie($cookie_name, null, $cookie_time, $this->config['Users']['CookiePath']);
		unset($_COOKIE);

		if ($redirect) {
			Tools::redirect($this->config['Users']['Auth_Redirect']);
		}

		$this->user = false;

    }

	/**
	 * Formats the passed text with the salt set in the config
	 * @param {String} $txt The text to format
	 */
	function formatSalt($txt) {
		return sha1($txt . $this->config['Users']['Salt']);
	}

	/**
	 * Get comments from the database
	 * @param int section The section to search in
	 * @param int section_id The section ID to look for
	 * @param array An index array of comments
	 */
    function getComments($section, $section_id, $order = 'ASC') {

		$table = $this->config['Comments']['Table_Comments'];

        $qry = "
			SELECT
				*
			FROM
				$table
			WHERE
				(section = $section)
				AND (section_id = $section_id)
			ORDER BY
				time_created $order
		";

		$rows = $this->getRows($qry);

		return $rows;

    }

	/**
	 * Gets a user's settings
	 * @param str user_id The user's OpenID
	 * @return array The DB rows
	 */
	function getUserSettings($user_id) {

		$table = $this->config['Users']['Table_Settings'];

		$qry = "
			SELECT
				name,
				value
			FROM
				$table
			WHERE
				user_id = '$user_id'
		";

		$settings = Array();
		$rows = $this->getRows($qry);

		foreach ($rows as $row) {
			$settings[$row['name']] = $row['value'];
		}

		return $settings;

	}

	/**
	 * Checks if a user is an admin or not
	 * @return boolean True if the user is an admin, false otherwise
	 */
	function isAdmin() {
		return @in_array($this->user['user_id'], $this->config['Users']['Admin_Users']);
	}

    /**
	 * Attempt to add a comment into the DB
	 * @param int section The section to add the comment into
	 * @param int section_id The section ID to attach the comment to
	 * @param string comment The actual comment itself
	 * @param string user_id An optional field to override the actual user's ID
	 * @return int The inserted comment ID, or false on failure
     */
    function putComment($section, $section_id, $comment = null, $user_id = null) {

		if ($comment === null) {
			$comment = $_POST['comment'];
		}

		if ($user_id === null) {
			$user_id = $this->user['user_id'];
		}

        $pairs = Array(
			'section' => $section,
			'section_id' => $section_id,
			'time_created' => time(),
			'user_id' => $user_id,
			'comment' => $comment
		);

		$table = $this->config['Comments']['Table_Comments'];
		$res = $this->insertRow($table, $pairs, '"');
		$comment_id = $this->getID($res);

		$this->updateCommentCount($section, $section_id);

        return $comment_id;

    }

	/**
	 * Checks to see if the user cookie is available. If it is, parses it and checks its authenticity
	 */
    function setUser() {

        // Grab the cookie value
		$cookie_name = $this->config['Users']['Cookie_Name'];
        $cookie_value = base64_decode($_COOKIE[$cookie_name]);

        // Split the cookie value up so we can read it
        $values = explode(self::HASH_SEP, $cookie_value);

        // Translate for ease of programming
        $user_id = $values[0];
        $hash = $values[1];

		if ($user_id && $hash) {

			if ($hash == $this->formatSalt($user_id)) {

				// Set the user var
				$this->user = Array();
				$this->user['settings'] = $this->getUserSettings($user_id);
				$this->user['user_id'] = $user_id;
				$this->user['username'] = $user_id;

				// Reset the expiration
				$this->execAuth($user_id, $redirect = false);

				return;
				
			}

		}

		// Not good that we got here; kill the cookie
		$this->execLogout();

    }

	/**
	 * Set a setting for a user. Creates it if it doesn't exist
	 * @param str $user_id The user's OpenID
	 * @param str $name The name of the stat
	 * @param mixed $value The value to insert
	 */
	function setUserSetting($user_id, $name, $value) {

		$pairs = Array(
			'user_id' => $user_id,
			'name' => $name,
			'value' => $value
		);

		$table = $this->config['Users']['Table_Settings'];
		$where = "(user_id = $char_id) AND (name = '$name')";

		$this->deleteRows($table, $where, 1);
		$this->insertRow($table, $pairs, "'");

	}

	/**
	 * Increment a parent's number of comments
	 * @param int section The section to update
	 * @param int section_id The section ID to update
	 */
	private function updateCommentCount($section, $section_id) {

		$id = $this->config['Comments']['DB'][$section]['Field_ID'];
		$field = $this->config['Comments']['DB'][$section]['Field_Count'];
		$table = $this->config['Comments']['DB'][$section]['Table'];

		$qry = sprintf('
			UPDATE
				%s
			SET
				%s = (%s + 1)
			WHERE
				%s = %s
			LIMIT
				1
			',
			$table,
			$field,
			$field,
			$id,
			$section_id
		);

		$this->query($qry);

	}

}
