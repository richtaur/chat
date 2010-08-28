<?php

/**
*/
class UGC extends Database {

	const
		FLD_NUM_REPLIES = 'numReplies',
		FLD_TIMESTAMP = 'posterTime',
		FLD_TOPIC_ID = 'ID_TOPIC',
		FLD_USER_ID = 'ID_MEMBER',
		FLD_USERNAME = 'memberName',
		TBL_MESSAGES = 'smf_messages',
		TBL_TOPICS = 'smf_topics',
		TBL_USERS = 'smf_members';

	function __construct() {

		parent::__construct();

		$this->config = &$config;

	}

	/**
	 * Convert bbCode into HTML
	*/
	function formatBBCode($text, $smileys = true) {

		// We doing this the SMF way?
		if ($this->config['Core']['Auth'] == 'SMF') {

			require_once($this->config['Path']['Auth_Local'] . 'Sources/Subs.php');

			return parse_bbc($text, $smileys);

		} else {

			// We don't HAVE bbcode, bitch!
			return $text;

		}

	}

	/**
	 * Formats a link to a forum user's profile
	 * @param int user_id The forum user's id
	 * @param string text The text to include in the anchor [optional]
	 * @param string extra The extra nodes to include in the <a> tag [optional]
	 * @return string The URL to the profile
	*/
	function formatLinkProfile($user_id, $text = null, $extra = null) {

		$url = $this->config['Path']['Auth_Remote'] . '?action=profile&amp;u=' . $user_id;

		return $this->formatLink($url, $text, $extra);

	}

	/**
	 * Formats a link to a forum topic
	 * @param int topic_id The topic id
	 * @param string text The text to include in the anchor [optional]
	 * @param string extra The extra nodes to include in the <a> tag [optional]
	 * @return string The URL to the topic
	*/
	function formatLinkTopic($topic_id, $text = null, $extra = null) {

		$url = $this->config['Path']['Auth_Remote'] . '?topic=' . $topic_id . '.0';

		return $this->formatLink($url, $text, $extra);

	}

	/**
	 * Formats strings to be put into SMF; the way an exterior framework handles the sanitation might be different
	 * @param string qry The query to format
	 * @return string The formatted query
	*/
	private function formatPutTopic($qry) {

		$qry = stripslashes($qry);
		$qry = str_replace("'", "\'", $qry);

		return $qry;

	}

	/**
	 * Gets messages from the database based on their parent topic
	 * @param int topic_id The topic id to search for
	*/
	function getMessagesByTopicId($topic_id, $limit = null) {

		$qry = sprintf("
				SELECT * FROM
					%s AS m
				WHERE
					m.%s = $topic_id
				ORDER BY %s DESC
			",
			Library::TBL_MESSAGES,
			Library::FLD_TOPIC_ID,
			Library::FLD_TIMESTAMP
		);

		if ($limit) {
			$qry .= "LIMIT $limit";
		}

		return $this->getRows($qry);

	}

	/**
	 * Checks if a user is an admin or not
	 * @return boolean True if the user is an admin, false otherwise
	 */
	function isAdmin() {
		return (boolean) $this->user['is_admin'];
	}

	/**
	 * Puts a topic into the SMF topics table
	 * @param integer board_id The id of the board to put the topic into
	 * @param string subject The subject of the topic
	 * @param string message The content of the first post in the topic
	 * @param integer user_id The id of the first poster (defaults to the current user)
	 * @param array options A mixed set of options, currently:
	 * @param mixed icon I have no idea what this does
	 * @param bool locked The locked status of the topic
	 * @param bool sticky Whether the topic should be stickied or not
	 * @param bool update_post_count Whether to increase the poster's post count
	 * @return integer The id of the newly created topic
	*/
	function putTopic($board_id, $subject, $message, $user_id = 0, $options = Array()) {
	global $db_prefix, $ID_MEMBER, $modSettings, $user_info;

		require_once($this->config['Path']['Auth_Local'] . 'Sources/Subs-Post.php');

		// Clean the strings
		$subject = $this->formatPutTopic($subject);
		$message = $this->formatPutTopic($message);

		$msgOptions = Array(
			'icon' => $options['icon'],
			'smileys_enabled' => true,
			'subject' => $subject,
			'body' => $message,
		);

		$topicOptions = Array(
			'board' => $board_id,
			'lock_mode' => $options['locked'],
			'sticky_mode' => $options['sticky'],
			'mark_as_read' => true,
		);

		$posterOptions = Array(
			'id' => ($user_id ? $user_id : $ID_MEMBER),
			'update_post_count' => $options['update_post_count'],
		);

		$topic_id = createPost($msgOptions, $topicOptions, $posterOptions);

		return $topic_id;

	}

}
