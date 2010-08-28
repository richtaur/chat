<?php

/*
this file is massively not done
stuff like v_* should be in the config
*/

/**
 * Chats class
 */
class Chats {

	const
		MAX_LENGTH = 255,
		TYPE_NORMAL = 0,
		TYPE_SYSTEM = 1;

	private $config;

	/**
	 * Constructor. Sets the table name and stuff
	 */
	function __construct(&$config) {
		$this->config = $config;
	}

	/**
	 * Gets the most recent chats from the DB
	 * @param int $map_id The map id
	 * @param int $time The timestamp
	 * @return array The rows from the DB
	 * @member MHP
	 * @public
	 */
	function get($map_id, $time) {

		$map_id = (int) $map_id;
		$time = (int) $time;
		$system = self::TYPE_SYSTEM;
		$table = $this->config['Chats']['Table'];

		$qry = "
			SELECT
				v_chars.name,
				$table.*
			FROM
				$table
			LEFT JOIN
				v_chars
			ON
				(v_chars.char_id = $table.char_id)
			WHERE
				($table.time_posted > $time)
				AND (
					$table.map_id = $map_id
					OR
					$table.type = $system
				)
			ORDER BY
				$table.time_posted ASC,
				$table.chat_id ASC
		";

		$rows = $GLOBALS['MHP']->getRows($qry);

		foreach ($rows as &$row) {
			$row['message'] = Tools::stripHTML($row['message']);
			$row['message'] = Tools::formatClickable($row['message']);
		}

		return $rows;

	}
	
	/**
	 * Attempts to save a chat into the DB
	 * @param int $char_id The map's ID
	 * @param int $map_id The map's ID
	 * @param array $messages The messages
	 * @return bool|string Either boolean true on success, or a string representing the error message on failure
	 * @member MHP
	 * @public
	 */
	function put($char_id, $map_id, $messages) {

		if (!$messages || !is_array($messages)) {
			return 'Received no messages.';
		}

		$pairs = Array(
			'char_id' => $char_id,
			'map_id' => $map_id,
			'time_posted' => time()
		);

		foreach ($messages as $message) {

			if ($message) {
				$pairs['message'] = $message;
				$GLOBALS['MHP']->insertRow($this->config['Chats']['Table'], $pairs);
			}

		}

		return true;

	}

}
