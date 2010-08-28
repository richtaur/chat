<?php

/**
 * Homebrew UGC -- Users:
 * @param mixed Auth_Redirect Set to true to send users to HTTP_REFERRER, or the location, such as "/"
 * @param int Cookie_Duration The number of seconds that cookies last until they expire
 * @param string Cookie_Name The name of the cookie as it lives in a user's browser
 * @param string Cookie_Path The path that cookies are allowed access to (most commonly /)
 * @param bool Email_Verification Whether or not to require users to verify their email addresses
 * @param int Password_Min The minimum length required for passwords
 * @param int Password_Max The maximum length of passwords
 * @param string Password_Salt The salt to make passwords extra secure
 * @param array Settings (a work-in-progress -- not functional yet)
 * @param array Username_Blacklist An array of usernames that are not allowed
 * @param bool Username_Changes Whether users are allowed to change their usernames or not
 * @param string Username_Min The minimum length required for usernames
 * @param string Username_Max The maximum length of usernames
 * @constructor
 * @extends Database
 */
class UGC extends Database {

	const
		CLASS_NORMAL = 1,
		CLASS_MOD = 2,
		CLASS_ADMIN = 3,

		HASH_SEP = '.:.',

		STATUS_NORMAL = 1,
		STATUS_INACTIVE = 2,
		STATUS_BANNED = 3,
		STATUS_DELETED = 4;

	/**
	 * UGC constructor
	 * @member UGC
	 * @public
	 */
	function __construct($config) {

		parent::__construct();

		$this->config = &$config;
		$this->connect($config['DB']['Server'], $config['DB']['User'], $config['DB']['Pass'], $config['DB']['Name']);

	}

	/**
	 * Check if a comment has recently been posted to be used with the Delay setting
	 * @param int $seconds The number of seconds to match against
	 * @return bool Whether there are recent comments or not
	 * @member UGC
	 * @public
	 */
	function checkCommentRecent($seconds) {

		$time_created = (time() + $seconds);

		// Should we check based on user ID or ip address?
		if ($this->user) {
			$where = sprintf(
				'(user_id = %s)',
				$this->user['user_id']
			);
		} else {
			$where = sprintf(
				'(ip = "%s")',
				Tools::getIP()
			);
		}

		$qry = sprintf(
			'SELECT
				COUNT(*) AS num_comments
			FROM
				%s
			WHERE
				%s
				AND (time_created >= %s)',
			$this->config['Comments']['Table_Comments'],
			$where,
			$time_created
		);

		$row = $this->getRow($qry);

		return ($row['num_comments'] > 0);

	}

	/**
	 * Validate the POST vars for submitting a comment
	 * Note: this function assumes the item the comment is attached to is valid
	 * @param string $comment The comment to check against
	 * @return array An array of error messages (emtpy if valid)
	 * @member UGC
	 * @public
	 */
	function checkPutComment($section) {

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
	 * Makes sure the sent password is valid
	 * @param string $password The password to check
	 * @param mixed Either an error message on failure or false if no errors found
	 * @member UGC
	 * @private
     */
    private function checkPassword($password) {

        $length = strlen($password);

		$min = $this->config['Users']['Password_Min'];
		$max = $this->config['Users']['Password_Max'];

        if (($length < $min) || ($length > $max)) {
            return "Password must range from $min to $max characters.";
        }

        return false;

    }

    /**
	 * Check a username against length, valid characters and the blacklist
	 * @param string $username The username to check against
	 * @return mixed Either an error message on failure or false if no errors found
	 * @member UGC
	 * @public
     */
    function checkUsername($username, $user_id = 0) {

        $length = strlen($username);
		$min = $this->config['Users']['Username_Min'];
		$max = $this->config['Users']['Username_Max'];

		// Length
        if (($length < $min) || ($length > $max)) {
            return "Username must range from $min to $max characters.";
        }

		// Blacklist
        if (in_array(strtolower($username), $this->config['Users']['Username_Blacklist'])) {
            return 'That username is not allowed.';
        }

		// Valid characters
		$chars_arr = str_split($this->config['Users']['Username_Chars']);

        for ($i = 0; $i < strlen($username); $i++) {
            if (!in_array($username{$i}, $chars_arr)) {
				return 'That username contains invalid characters.';
            }
        }

		// And finally -- is this name taken?
		if ($user_id) {
			$and = "(user_id != $user_id)";
		}

        if ($this->checkField($this->config['Users']['Table_Users'], 'username', $username, $and)) {
            return 'That username is taken.';
		}

        return false;

    }

    /**
	 * Set a cookie on the user's harddrive and redirect if all went well
	 * @param int $user_id The user ID to login as
	 * @param string $password The password to secure
	 * @param bool $redirect Whether to redirect the user or not [default: false]
	 * @member UGC
	 * @private
     */
    private function execAuth($user_id, $password, $redirect = false) {

		// Prepare password hash
		$pass_hash = $this->formatPassword($password, $user_id);

        // Prep cookie vars
        $cookie_name = $this->config['Users']['Cookie_Name'];
        $cookie_time = (time() + $this->config['Users']['Cookie_Duration']);
        $cookie_value = base64_encode($user_id . self::HASH_SEP . $pass_hash);

        // Set the cookie.
        setcookie($cookie_name, $cookie_value, $cookie_time, $this->config['Users']['Cookie_Path']);

		$this->updateUserLogin($user_id);

		// Should we redirect?
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
	 * Check the login form
	 * @return string The error message or false if no error message
	 * @member UGC
	 * @public
     */
    function execLogin() {

		$error = false;
        $password = $_POST['password'];
        $username = $_POST['username'];

		// Make sure both required fields are set
        if ($password && $username) {

			// Grab the user from the DB
            $user = $this->getUser($username, 'username');
			$no_user = 'That user does not exist.';

            if ($user) {

				// Make sure this user CAN login
                switch ($user['status']) {

                    case self::STATUS_DELETED :
						$error = $no_user;
                        break;

                    case self::STATUS_INACTIVE :
                        $error = 'That user has not yet been activated.';
                        break;

                    case self::STATUS_NORMAL :

                        if ($this->formatPassword($password) == $user['password']) {
                            $this->execAuth($user['user_id'], $this->formatPassword($password), true);
                        } else {
                            $error = 'Incorrect password.';
                        }
                        break;

                    default :
                        $error = 'An unknown error has occurred.';
                        break;

                }

            } else {
				$error = $no_user;
            }

        } else {
            $error = 'Username and password are both required.';
        }

		return $error;

    }

	/**
	 * Log a user out
	 * @param bool $redirect Whether to redirect the user or nont
	 * @member UGC
	 * @public
	 */
    function execLogout($redirect = false) {

        $cookie_name = $this->config['Users']['Cookie_Name'];
		$cookie_time = (time() - 86400); // Expired yesterday

        setcookie($cookie_name, null, $cookie_time, $this->config['Users']['CookiePath']);
		unset($_COOKIE);

		if ($redirect) {
			Tools::redirect($this->config['Users']['Auth_Redirect']);
		}

    }

	/**
	 * Attempts to execute a user password reset
	 * @param string $code The code requested to reset the password
	 * @return mixed Either the randomly generated password or false if the code was invalid
	 * @member UGC
	 * @public
	 */
    function execPasswordReset($str_code) {

		$decoded = base64_decode($str_code);
		$codes = explode(self::HASH_SEP, $decoded);

		if ($codes) {

			$user_id = (int) $codes[0];
			$stamp_code = $codes[1];
			$user = $this->getUserById($user_id);

			if ($user) {

				$stamp_user = $this->formatPassword($user['time_login']);

				if ($stamp_code == $stamp_user) {

					$password = $this->makeRandomPassword(false);
					$password_db = $this->formatPassword($password);
					
					$pairs = Array(
						'password' => $password_db
					);

					$where = "(user_id = $user_id)";

					$this->updateRows($this->config['Users']['Table_Users'], $pairs, $where, 1, '"');

					return $password;

				}

			}

		}

        return false;

    }

	/**
	 * Execute a user password reset
	 * @return array An array of error messages (emtpy if valid)
	 * @member UGC
	 * @public
	 */
    function execPasswordResetForm() {

		$errors = Array();
        $email = $_POST['email'];
        $username = $_POST['username'];

        if ($email && $username) {

            $user = $this->getUserByUsername($username);

            if ($user) {

				// Something up with this user?
                switch ($user['status']) {

                    case self::STATUS_BANNED :
                        $errors[] = 'That user has been banned.';
                        break;

                    case self::STATUS_DELETED :
                        $errors[] = 'That user does not exist.';
                        break;

                    case self::STATUS_INACTIVE :
                        $errors[] = 'That user has not yet been activated.';
                        break;

                    case self::STATUS_NORMAL :

						// User's status is good-to-go. Was the email in the code correct?
                        if ($email == $user['email']) {

							$code = base64_encode($user['user_id'] . self::HASH_SEP . $this->formatPassword($user['time_login']));
							$params['resetURL'] = $this->config['Core']['URL'] . $this->config['Users']['URL_Password'] . $code;

							if (!$this->sendUserNotice($email, 'password_reset', $params)) {
								$errors[] = 'Failed to send an email to that address.';
							}

                        } else {
                            $errors[] = 'Incorrect email address.';
                        }

                        break;

                    default :
                        $errors[] = 'An unknown error has occurred.';
                        break;

                }

            } else {
                $errors[] = 'That user does not exist.';
            }

        } else {
            $errors[] = 'Username and email are both required.';
        }

		return $errors;

    }

    /**
	 * Check the registration form
	 * @return mixed The error messages array (empty if no error messages)
	 * @member UGC
	 * @public
     */
    function execRegister() {

		$errors = Array();

        $captcha = $_POST['captcha'];
        $captcha_verify = $_POST['captcha_verify'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $verify = $_POST['verify'];
        $tos = $_POST['tos'];
        $username = $_POST['username'];

		// Username
        $unError = $this->checkUsername($username);

        if ($unError) {
            $errors['username'] = $unError;
        }

		// Email
        if (!Tools::checkEmail($email)) {
            $errors['email'] = 'That email address is invalid.';
        } else if ($this->checkField($this->config['Users']['Table_Users'], 'email', $email)) {
            $errors['email'] = 'That email address is already being used.';
        }

		// Password
		$pwError = $this->checkPassword($password);

        if ($password != $verify) {
            $errors['verify'] = 'Passwords do not match.';
        } else if ($pwError) {
            $errors['password'] = $pwError;
        }

        // Prove you're human
        if ($captcha != $captcha_verify) {
            $errors['captcha'] = "Are you a robot? Please prove you're human or at least a decent spambot.";
        }

        // Terms of Service
        if (!$tos) {
            $errors[] = 'You must agree to the terms of service.';
        }

		// Should we bother proceeding?
        if (!$errors) {

            $status = ($this->config['Users']['Email_Verification'] ? self::STATUS_INACTIVE : self::STATUS_NORMAL);
            $password_db = $this->formatPassword($password);
			$time_created = time();

            $pairs = Array(
				'class' => self::CLASS_NORMAL,
				'status' => $status,
				'time_created' => $time_created,
				'email' => $email,
				'password' => $password_db,
				'username' => $username
			);

			$user_id = $this->putUser($pairs);

            if ($this->config['Users']['Email_Verification']) {

				$code = base64_encode($user_id . self::HASH_SEP . $this->formatPassword($time_created));
				$params['verifyURL'] = $this->config['Core']['URL'] . $this->config['Users']['URL_Verify'] . $code;

				if (!$this->sendUserNotice($email, 'register', $params)) {
					$errors[] = 'Failed to send an email to that address.';
				}

            }

        }

        return $errors;

    }

    /**
	 * Check the user edit page
	 * @param array $user The user's current associative array
	 * @return mixed The error messages array (empty if no error messages)
	 * @member UGC
	 * @public
     */
    function execUserEdit($user) {

		$errors = Array();
		$user_id = $user['user_id'];

        $email = $_POST['email'];
        $password = $_POST['password'];
        $verify = $_POST['verify'];
        $username = $_POST['username'];

		if ($this->config['Users']['Username_Changes']) {

			// Username
			$unError = $this->checkUsername($username, $user_id);

			if ($unError) {
				$errors['username'] = $unError;
			}

		} else {
			unset($username);
		}

		// Email
		$email_change = false;

		if ($email != $user['email']) {

			$and = "(user_id != $user_id)";
			$email_change = true;

			if (!Tools::checkEmail($email)) {
				$errors['email'] = 'That email address is invalid.';
			} else if ($this->checkField($this->config['Users']['Table_Users'], 'email', $email, $and)) {
				$errors['email'] = 'That email address is already being used.';
			}

		}

		// Password
		if ($password) {

			$pwError = $this->checkPassword($password);

			if ($password != $verify) {
				$errors['verify'] = 'Passwords do not match.';
			} else if ($pwError) {
				$errors['password'] = $pwError;
			}

		}

		// Should we bother proceeding?
        if (!$errors) {

			// What's happening with status? Normal users have to reactivate their accounts if they change email
			if ($this->isAdmin()) {
				$status = self::STATUS_NORMAL;
			} else {
				$status = ($email_change ? self::STATUS_INACTIVE : self::STATUS_NORMAL);
			}

			// Create a copy of this so we can be sure it'll be the same when we access it in the rest of the method
			$timestamp = time();

            $pairs = Array(
				'status' => $status,
				'time_login' => $timestamp
			);

			// Email is not required so make sure it's changed
			if ($email_change) {
				$pairs['email'] = $email;
			}

			// Password is optional so make sure it's changed
			if ($password) {
				$password_db = $this->formatPassword($password);
				$pairs['password'] = $password_db;
			}

			// Username is optional so make sure it's changed
			if ($username) {
				$pairs['username'] = $username;
			}

			// Fire off the DB query
			$this->updateUser($user_id, $pairs);

			// Is this user good to go or do we need to verify the account?
            if (!$this->isAdmin() && $email_change && $this->config['Users']['Email_Verification']) {

				// Let the user know to check email
				$this->email_verify = true;

				// The email script needs to know where to send the user
				$code = base64_encode($user_id . self::HASH_SEP . $this->formatPassword($timestamp));
				$params['verifyURL'] = $this->config['Core']['URL'] . $this->config['Users']['URL_Verify'] . $code;

				if ($this->sendUserNotice($email, 'email_change', $params)) {
					// Log the user out since email changed and needs to be verified
					$this->execLogout();
				} else {
					$errors[] = 'Failed to send an email to that address.';
				}

            } else if ($password) {

				// Password changed so this user needs to be logged in again
				$this->execAuth($user_id, $password_db);

			}

        }

        return $errors;

    }

    /**
	 * Execute a user verification
	 * @param string $code The verification code
	 * @return mixed Either a string containing the error message on failure or false on success
	 * @member UGC
	 * @public
     */
    function execVerify($code) {

        $decoded = base64_decode($code);
        $codes = explode(self::HASH_SEP, $decoded);

        if ($codes) {

            $user_id = (int) $codes[0];
            $stamp_code = $codes[1];
            $user = $this->getUserById($user_id);

            if ($user) {

                if ($user['status'] == self::STATUS_INACTIVE) {

                    // Here's how this works -- if the user has logged in, use the logged in timestamp
					// If the user has NOT logged in, use the created timestamp
                    if ($user['time_login']) {
                        $stamp_user = $this->formatPassword($user['time_login']);
                    } else {
                        $stamp_user = $this->formatPassword($user['time_created']);
                    }

					// Check the code
                    if ($stamp_code == $stamp_user) {
 
						$this->updateUserStatus($user_id, self::STATUS_NORMAL);

                        return false;

                    } else {
						return 'Invalid verification code.';
					}

                } else {
                    return 'That account has already been activated.';
                }

            }

        }

        return 'Sorry, invalid verification code.';

    }

    /**
	 * Formats a password into a secure hash
	 * @param string $password The password to format
	 * @param int $user_id The user ID to include in the hash (optional)
	 * @return string The formatted password
	 * @member UGC
	 * @public
     */
    function formatPassword($password, $user_id = false) {

		$salt = $this->config['Users']['Password_Salt'];

		// We further protect the cookies set on hard drives
		if ($user_id) {
			$hash = md5($user_id . $password . $salt);
		} else {
			$hash = md5($password . $salt);
		}

		return $hash;

    }

	/**
	 * Formats a username into a URL-friendly slug
	 * @param string $username The username to convert
	 * @member UGC
	 * @public
	 */
	function formatUserSlug($username) {

		// Take the space out if it's there
		$slug_chars = str_replace(' ', '', $this->config['Users']['Username_Chars']);

		return Tools::formatSlug($username, $slug_chars);

	}

	/**
	 * Get comments from the database
	 * @param int $section The section to search in
	 * @param int $section_id The section ID to look for
	 * @return array An index array of comments
	 * @member UGC
	 * @public
	 */
    function getComments($section, $section_id) {

        $qry = sprintf('
			SELECT
				c.*,
				u.class,
				u.username
			FROM
				%s AS c,
				%s AS u
			WHERE
				c.section = %s
				AND c.section_id = %s
				AND c.user_id = u.user_id
			ORDER BY
				c.time_created ASC
			',
			$this->config['Comments']['Table_Comments'],
			$this->config['Users']['Table_Users'],
			(int) $section,
			(int) $section_id
		);

		$rows = $this->getRows($qry);

		foreach ($rows as &$row) {
            $row['user_slug'] = $this->formatUserSlug($row['username']);
        }

		return $rows;

    }

    /**
	 * Get a user from the database
	 * @param string $value The value to search on
	 * @param string $field The field to search
	 * @param bool $get_settings Whether to get the settings or not
	 * @return bool|array The user's row from the DB on success or false on failure
	 * @member UGC
	 * @public
     */
    function getUser($value, $field, $get_settings = false) {

        // Query for this user
        $qry = sprintf('
				SELECT
					*
				FROM
					%s
				WHERE
					(%s = "%s")
				LIMIT 1
			',
			$this->config['Users']['Table_Users'],
			$field,
			$value
		);

		$user = $this->getRow($qry);

        // Return the user or false on failure
        if ($user) {

            // Get the settings if we need to
            if ($get_settings) {
                $user = $this->setUserSettings($user);
            }

            $user['slug'] = $this->formatUserSlug($user['username']);

            return $user;

        }

        return false;

    }

	/**
	 * Get a user from the database by ID
	 * @param int $user_id The user's ID
	 * @param bool $get_settings Whether to get the settings or not
	 * @return array The user's associative array
	 * @member UGC
	 * @public
	 */
	function getUserById($user_id, $get_settings = false) {
		return $this->getUser($user_id, 'user_id', $get_settings);
	}

	/**
	 * Get a user from the database by slug
	 * @param string $user_slug The user's slug
	 * @param bool $get_settings Whether to get the settings or not
	 * @return array The user's associative array
	 * @member UGC
	 * @public
	 */
	function getUserBySlug($user_slug, $get_settings = false) {

		$user_slug = Tools::cleanQuery($user_slug);
		$user_slug = str_replace('-', ' ', $user_slug);

		return $this->getUser($user_slug, 'username', $get_settings);

	}

	/**
	 * Get a user from the database by name
	 * @param string $username The user's username
	 * @param bool $get_settings Whether to get the settings or not
	 * @return array The user's associative array
	 * @member UGC
	 * @public
	 */
	function getUserByUsername($username, $get_settings = false) {

		$username = Tools::cleanQuery($username);

		return $this->getUser($username, 'username', $get_settings);

	}

	/**
	 * Get users from the database based on a sorting parameter
	 * @param string $sort The sorting method
	 * @param string $dir The direction to sort in (ASC, DESC)
	 * @param integer $page The page number
	 * @param integer $status The status to check
	 * @member UGC
	 * @public
	 */
    function getUsers($sort, $dir, $page = 1, $status = self::STATUS_NORMAL) {

        $valid_dirs = Array(
            'asc',
            'desc'
        );

        $valid_sorts = Array(
            'joined'   => 'time_created',
            'login'    => 'time_login',
            'username' => 'username'
        );

        $dir = ($valid_dirs[$str_dir] ? $dir : 'desc');
        $sort = ($valid_sorts[$str_sort] ? $sort : 'joined');

        $qry = sprintf('
			SELECT
				*
			FROM 
				%s
			WHERE
				(status = ' . $status . ')
			ORDER BY
				%s %s
			',
			$this->config['Users']['Table_Users'],
			$valid_sorts[$sort],
			strtoupper($dir)
		);

		$rows = $this->getRows($qry);

        return $rows;

    }

    /**
	 * Gets a user's settings from the DB
	 * @param int $user_id The user's ID to fetch settings from
	 * @return array An associative array of the user's settings
	 * @member UGC
	 * @public
     */
    function getUserSettingsById($user_id) {

		$settings = Array();

        $qry = sprintf('
			SELECT
				key, value
			FROM
				%s
			WHERE
				(user_id = %s)',
			$this->config['Users']['Table_Settings'],
			$user_id
		);

		$rows = $this->getRows($qry);

		foreach ($rows as $row) {
			$settings[$row['key']] = $row['value'];
		}

        return $settings;

    }

	/**
	 * Checks whether the user is logged in as an admin or not
	 * @return bool Whether is an admin
	 * @member UGC
	 * @public
	 */
	function isAdmin() {
		return ($this->user['class'] == self::CLASS_ADMIN);
	}

    /**
	 * Generate a random password
	 * @param bool $format Whether to also format the password into a secure hash
	 * @return string The generated password
	 * @member UGC
	 * @public
     */
    function makeRandomPassword($format = false) {

        $len = rand($this->config['Users']['Password_Min'], $this->config['Users']['Password_Max']);

        for ($i = 1; $i <= $len; $i++) {

			// 25% numbers, 75% letters
            if (rand(1, 4) == 1) {
                // 25% chance of a number
                $password .= rand(0, 9);
            } else {
				if (rand(1, 2) == 1) {
					$password .= chr(rand(97, 122)); // lowercoase
				} else {
					$password .= chr(rand(65, 90)); // UPPERCASE
				}
            }

        }

        if ($format) {
            $password = $this->formatPassword($password);
        }

        return $password;

    }

    /**
	 * Attempt to add a comment into the DB
	 * @param int $section The section to add the comment into
	 * @param int $section_id The section ID to attach the comment to
	 * @param string $comment The actual comment itself
	 * @param int $user_id An optional field to override the actual user's ID
	 * @return bool|int The inserted comment ID, or false on failure
	 * @member UGC
	 * @public
     */
    function putComment($section, $section_id, $comment = null, $user_id = null) {

		if ($comment == null) {
			$comment = $_POST['comment'];
		}

		if ($user_id == null) {
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
	 * Replace a user's settings into the DB
	 * Note: would REPLACE be faster/better?
	 * @param int $user_id The user's ID
	 * @param array $settings The key/value pair of settings to add/modify
	 * @member UGC
	 * @public
     */
    function putSettings($user_id, $settings) {

        // Loop through and collect data
        foreach ($settings as $key => $value) {

            $keys .= '"' . $key . '",';

			$pairs = Array(
				'user_id' => $user_id,
				'key' => $key,
				'value' => $value
			);

            $inserts[] = $this->formatInsert($this->config['Users']['Table_Settings'], $pairs);

        }

        // Kill the previous settings
        $keys = substr($keys, 0, -1);

		$where = sprintf('
			(user_id = %s)
			AND (key IN (%s))
			',
			$user_id,
			$keys
		);

		$this->deleteRows($this->config['Users']['Table_Settings'], $where, null);

        // Loop through and insert the new settings
        foreach ($inserts as $qry) {
            $this->query($qry);
        }

    }

	/**
	 * Insert a user row into the database
	 * @param array $pairs The key value pairs to put into the database
	 * @return int The new user id
	 * @member UGC
	 * @private
	 */
	private function putUser($pairs) {

		$table = $this->config['Users']['Table_Users'];
		$res = $this->insertRow($table, $pairs, '"');
		$user_id = $this->getID($res);

		return $user_id;

	}


	/**
	 * Send an email notice to a user based
	 * @param string $email The email address to send to
	 * @param string $type The type of notice to send
	 * @param array $params An associative array of extra parameters to send to the messages script
	 * @return bool Whether the email sent successfully
	 * @member UGC
	 * @private
	 */
	private function sendUserNotice($email, $type, $params) {

		// Find out if we have a custom message or if we're using the framework's default
		$file = $this->config['Path']['Custom_Local'] . "copy/$type.php";

		if (!is_file($file)) {
			$file = $this->config['Path']['MHP_Local'] . "copy/$type.php";
		}

		require($file);

		return Tools::sendEmail($email, $subject, $message, $headers);

	}
    
	/**
	 * Checks to see if the user cookie is available. If it is, parses it and checks its authenticity
	 * @member UGC
	 * @public
	 */
    function setUser() {

        // Grab the cookie value
		$cookie_name = $this->config['Users']['Cookie_Name'];
        $cookie_value = base64_decode($_COOKIE[$cookie_name]);

        // Split the cookie value up so we can read it
        $values = explode(self::HASH_SEP, $cookie_value);

        // Translate for ease of programming
        $user_id  = $values[0];
        $password = $values[1];

		if ($user_id && $password) {

			// Get for this user
			$user = $this->getUserById($user_id);

			// See if the password matches
			if ($user) {
			
				// Make sure this user is active
				if ($user['status'] == self::STATUS_NORMAL) {

					$pass_check = $this->formatPassword($user['password'], $user_id);

					// Make sure password check is ok
					if ($password == $pass_check) {

						// Reset the expiration on the cookie
						$this->execAuth($user['user_id'], $user['password']);

						// Kill the password for extra security
						unset($user['password']);

						// Assign the user record set to our public array
						$this->user = $user;

						return;
						
					}

				}

			}

		}

		// Not good that we got here; kill the cookie
		$this->execLogout();

    }

	/**
	 * Attach user settings to a user array
	 * @param array $user The user array
	 * @return array The new user, with settings attached
	 * @member UGC
	 * @public
	 */
	function setUserSettings($user) {

		$user['settings'] = $this->getUserSettings($user['user_id']);

		return $user;

	}

	/**
	 * Translate the classes into human-readable format
	 * @param int $class The class to translate
	 * @return string The translated class
	 * @member UGC
	 * @public
	 */
	function translateClass($class) {

		$trans = Array(
			self::CLASS_NORMAL => 'Normal',
			self::CLASS_MOD => 'Mod',
			self::CLASS_ADMIN => 'Admin'
		);

		return $trans[$class];

	}

	/**
	 * Translate the statuses into human-readable format
	 * @param int $status The status to translate
	 * @return string The translated status
	 * @member UGC
	 * @public
	 */
	function translateStatus($status) {

		$trans = Array(
			self::STATUS_NORMAL => 'Normal',
			self::STATUS_INACTIVE => 'Inactive',
			self::STATUS_BANNED => 'Banned',
			self::STATUS_DELETED => 'Deleted'
		);

		return $trans[$status];

	}

	/**
	 * Increment a parent's number of comments
	 * @param int $section The section to update
	 * @param int $section_id The section ID to update
	 * @member UGC
	 * @private
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

	/**
	 * Update an entire user row in the DB
	 * @param int $user_id The user's ID
	 * @param array $pairs The key/value pairs to be updated
	 * @member UGC
	 * @private
	 */
	private function updateUser($user_id, $pairs) {

		$table = $this->config['Users']['Table_Users'];
		$where = "(user_id = $user_id)";

		$this->updateRows($table, $pairs, $where, 1, '"');

	}

	/**
	 * Update a user's login time
	 * @param int $user_id The user's ID
	 * @member UGC
	 * @private
	 */
	private function updateUserLogin($user_id) {

		$qry = sprintf('
			UPDATE
				%s
			SET
				time_login = %s
			WHERE
				user_id = %s
			LIMIT 1
			',
			$this->config['Users']['Table_Users'],
			time(),
			$user_id
		);

		$this->query($qry);

	}

	/**
	 * Updates a user's status in the database
	 * @param int $user_id The user's ID
	 * @param int $status The new user status
	 * @member UGC
	 * @private
	 */
	private function updateUserStatus($user_id, $status) {
 
		$pairs = Array(
			'status' => $status
		);

		$table = $this->config['Users']['Table_Users'];
		$where = "(user_id = $user_id)";

		$this->updateRows($table, $pairs, $where, 1);

	}

}
