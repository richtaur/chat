<?php

class Chat {

	const
		DEFAULT_NAME = 'Guest',
		DIR_UPLOADS_LOCAL = '/home/richtaur/dev/projects/chat/htdocs/static/uploads/',
		DIR_UPLOADS_REMOTE = '/static/uploads/',
		FORMAT_DATE = 'Y/m/d',
		MAX_IDLE = 600, // 10 minutes
		MESSAGE_LIMIT = 300,
		ONLINE_NOW = 'online now',
		STATUS_OFFLINE = 0,
		STATUS_ONLINE = 1,
		STATUS_AWAY = 2,
		STATUS_TYPING = 3,
		SALT = 'omgchat',
		TEXT_ENTER = '/me has entered the room',
		TEXT_EXIT = '/me has left the room',
		TIME_IDLE = 60, // 60 seconds until idle
		TYPE_NORMAL = 0,
		TYPE_HTML = 1,
		TYPE_EMOTE = 2,
		USE_IP = false,
		VERSION = 0.2;

	private static
		$commands = array();

	public static
		$last_message_id;

	public static function formatBbCode($msg) {

		$msg = str_replace('[tgen 2]', '<span class="app app-tgen">' . self::getTgen(2) . '</span>', $msg);
		$msg = str_replace('[tgen 3]', '<span class="app app-tgen">' . self::getTgen(3) . '</span>', $msg);

		return $msg;

	}

	public static function formatMessage($message, $type = 0) {

		if (substr($message, 0, 1) == '/') {

			$tokens = explode(' ', $message);

			switch ($tokens[0]) {
				case '/image': // Intentional fallthrough
				case '/img':
					$src = implode(' ', $tokens);
					$src = substr($src, (($tokens[0] == '/img') ? 5 : 7), strlen($src));
					$message = str_replace(
						'%',
						$src,
						'<a href="%" target="_blank" title="Click to view in a new tab/window"><img alt="%" src="%"></a>'
					);
					break;
				case '/mp3':
					$r = rand(1, 999999);
					$message = sprintf('<object type="application/x-shockwave-flash" data="/static/swf/player.swf" id="audioplayer%s" height="24" width="290"><param name="movie" value="/static/swf/player.swf"><param name="FlashVars" value="playerID=%s&amp;soundFile=%s"><param name="quality" value="high"><param name="menu" value="false"><param name="wmode" value="transparent"></object>', $r, $r, $tokens[1]);
					break;
				case '/time':
					$time = substr($message, strlen($tokens[0]), strlen($message));
					if (is_numeric($time)) {
						$converted = date('r', $time);
					} else if ($time) {
						$converted = strtotime($time);
					} else {
						$time_date = date('l, F jS, Y g:ia');
						$time_stamp = time();
						$message = "<span class='app app-time'>The time is currently <strong>$time_date</strong> ($time_stamp)</span>";
						break;
					}
					$message = "<span class='app app-time'>Time conversion:</span> <code>$time</code> = <strong>$converted</strong>";
					break;
				case '/video':
					$video = ($tokens[1] ? $tokens[1] : 'http://chrisdaneowens.com/video/shine_flash.swf');
					$width = ($tokens[2] ? $tokens[2] : 640);
					$height = ($tokens[3] ? $tokens[3] : 400);
					$message = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="' . $width . '" height="' . $height . '" id="v' . rand(1, 10000) . '" align="middle">';
					$message .= '<param name="allowScriptAccess" value="sameDomain" />';
					$message .= '<param name="movie" value="' . $video . '" />';
					$message .= '<param name="quality" value="high" />';
					$message .= '<param name="bgcolor" value="#000000" />';
					$message .= '<embed src="' . $video . '" quality="high" bgcolor="#000000" width="' . $width . '" height="' . $height . '" name="shine_flash" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />';
					$message .= '</object>';
					break;
				case '/youtube':
				case '/yt':
					$message = sprintf('<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/%s&hl=en&fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/%s&hl=en&fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>', $tokens[1], $tokens[1]);
					break;
			}

		} else if (!$type) {

			$message = Tools::stripHTML($message);
			$message = Tools::formatClickable($message);
			$message = str_replace("\n", '<br>', $message);
			$message = self::formatBbCode($message);

		}

		return $message;
		
	}

	public static function formatMessages($messages, $set_last = true) {

		foreach ($messages as &$message) {
			$date = date('g:ia', $message['timestamp']);
			$message['date'] = substr($date, 0, -1); // converts "am" to "a", etc
			$message['message'] = self::formatMessage($message['message'], $message['type']);
			$message['name'] = Tools::stripHTML($message['name']);
		}

		if ($set_last) {
			self::$last_message_id = $message['message_id'];
error_log(self::$last_message_id);
		}
	
		return $messages;

	}

	public static function formatMessageLink($message) {

		$room = self::getRoomById($message['room_id']);
		$id = $message['message_id'];
		$room_name = $room['name'];
		$year = date('Y', $message['timestamp']);
		$month = date('m', $message['timestamp']);
		$day = date('d', $message['timestamp']);
		
		return "/room/$room_name/history/$year/$month/$day/#message-$id";

	}

	public static function formatStatus($status) {

		$statuses = array(
			self::STATUS_OFFLINE => 'offline',
			self::STATUS_ONLINE => 'online',
			self::STATUS_AWAY => 'away',
			self::STATUS_TYPING => 'typing'
		);

		return $statuses[$status];

	}

	private static function formatUsers($users) {

		foreach ($users as &$user) {

			if ($user['status'] == self::STATUS_OFFLINE) {
				$minutes = (time() - $user['timestamp']);
				$minutes = round($minutes / 60);
				$user['offline_text'] = Tools::formatMultiple($minutes, 'minute');
			} else {
				$user['offline_text'] = '';
			}

			$user['status_text'] = self::formatStatus($user['status']);

		}

		return $users;

	}

	public static function generateCode($room_name) {
		return md5($room_name . self::SALT);
	}

	public static function getCommands() {
		return self::$commands;
	}

	public static function getDatesByRoomId($id) {

		$id = (int) $id;

		$qry = "
			SELECT
				timestamp
			FROM
				messages
			WHERE
				room_id = $id
		";

		$dates = array();
		$messages = $GLOBALS['MHP']->getRows($qry);

		foreach ($messages as $message) {
			$dates[date(self::FORMAT_DATE, $message['timestamp'])]++;
		}

		return $dates;

	}

/*
	public static function getMessageById($message_id) {

		$message_id = (int) $message_id;

		$qry = "
			SELECT
				*
			FROM
				messages
			WHERE
				message_id = $message_id
			LIMIT 1
		";

		$messages = $GLOBALS['MHP']->getRows($qry);
		$message = $messages[0];
		$message = self::formatMessage($message, $message['type']);

		return $message;

	}
*/

	public static function getMessagesByRoomId($id, $limit = true) {

		$id = (int) $id;
		$limit = ($limit ? 'LIMIT ' . self::MESSAGE_LIMIT : '');

		$qry = "
			SELECT
				*
			FROM
				messages
			WHERE
				room_id = $id
			ORDER BY
				timestamp DESC
			$limit
		";

		$messages = $GLOBALS['MHP']->getRows($qry);
		$messages = array_reverse($messages);
		$messages = self::formatMessages($messages);

		return $messages;

	}

	public static function getMessagesByRoomIdAndDate($id, $date) {

		$id = (int) $id;
		$date_start = strtotime($date . ' 12:00AM');
		$date_stop = strtotime($date . ' 11:59PM');

		$qry = "
			SELECT
				*
			FROM
				messages
			WHERE
				room_id = $id
			AND
				(timestamp >= $date_start)
			AND
				(timestamp <= $date_stop)
			ORDER BY
				timestamp DESC
			$limit
		";

		$messages = $GLOBALS['MHP']->getRows($qry);
		$messages = array_reverse($messages);
		$messages = self::formatMessages($messages);

		return $messages;

	}

	public static function getMessagesByRoomIdAndUser($id, $user, $limit = null) {

		$id = (int) $id;
		$user = Database::clean($user);
		$limit = ($limit ? "LIMIT $limit" : '');

		$qry = "
			SELECT
				*
			FROM
				messages
			WHERE
				room_id = $id
				AND name = '$user'
			ORDER BY
				timestamp DESC
			$limit
		";

		$messages = $GLOBALS['MHP']->getRows($qry);
		$messages = array_reverse($messages);
		$messages = self::formatMessages($messages);

		return $messages;

	}

	public static function getMessagesSinceId($room_id, $message_id) {

		$message_id = (int) $message_id;

		$qry = "
			SELECT
				*
			FROM
				messages
			WHERE
				room_id = $room_id
			AND
				message_id > $message_id
			ORDER BY
				message_id ASC
			LIMIT " . self::MESSAGE_LIMIT . "
		";

		$messages = $GLOBALS['MHP']->getRows($qry);
		$messages = self::formatMessages($messages);

		return $messages;

	}

	public static function getNumMessagesByRoomId($id) {

		$id = (int) $id;

		$qry = "
			SELECT
				COUNT(*)
			AS
				num
			FROM
				messages
			WHERE
				room_id = $id
		";

		$num = $GLOBALS['MHP']->getRow($qry);
		return $num['num'];


	}

	public static function getNumOnline() {

		$time = (time() - self::MAX_IDLE);

		$qry = "
			SELECT
				room_id, 
				COUNT(name) AS num_online
			FROM
				rooms_users
			WHERE
				(timestamp > $time)
			GROUP BY
				room_id
		";

		$online = array();
		$users = $GLOBALS['MHP']->getRows($qry);

		foreach ($users as $user) {
			$online[$user['room_id']] = $user['num_online'];
		}

		return $online;

	}

	public static function getRoomById($id) {

		$id = (int) $id;

		$qry = "
			SELECT
				*
			FROM
				rooms
			WHERE
				(room_id = $id)
			LIMIT 1
		";

		$room = $GLOBALS['MHP']->getRow($qry);

		return $room;

	}

	public static function getRoomByName($name) {

		$name = Database::clean($name);

		$qry = "
			SELECT
				*
			FROM
				rooms
			WHERE
				(name = '$name')
			LIMIT 1
		";

		$room = $GLOBALS['MHP']->getRow($qry);

		return $room;

	}

	public static function getRooms($num_messages = true, $num_online = true, $get_hidden = false) {

		$where = ($get_hidden ? '(1)' : '(hidden = 0)');

		$qry = "
			SELECT
				*
			FROM
				rooms
			WHERE
				$where
			ORDER BY
				name ASC
		";

		$rooms = $GLOBALS['MHP']->getRows($qry);

		if ($num_messages) {
			foreach ($rooms as &$room) {
				$room['num_messages'] = self::getNumMessagesByRoomId($room['room_id']);
			}
		}

		if ($num_online) {
			$online = self::getNumOnline();
			foreach ($rooms as &$room) {
				$room['num_online'] = $online[$room['room_id']];
			}
		}

		return $rooms;

	}

	public static function getSearchResults($term, $room_id = null) {

		$qry = "
			SELECT
				*
			FROM
				messages
			WHERE
				(message LIKE '%$term%')
			ORDER BY
				timestamp DESC
		";

		$messages = $GLOBALS['MHP']->getRows($qry);
		#$messages = array_reverse($messages);
		$messages = self::formatMessages($messages);

		return $messages;

	}

	public static function getTgen($words) {

		$words = (int) $words;

		if (($words < 2) || ($words > 10)) {
			$words = 2;
		}

		$file = file($GLOBALS['MHP']->config['Path']['Custom_Local'] . 'tgen.txt');
		$len = (count($file) - 1);
		$tgen = '';

		for ($i = 0; $i < $words; $i++) {
			$tgen .= $file[rand(0, ($len))] . ' ';
		}

		return '<span class="app app-tgen">' . str_replace("\n", '', $tgen) . '</span>';

	}

	public static function getTgenBest() {

		$file = file($GLOBALS['MHP']->config['Path']['Custom_Local'] . 'tgen_best.txt');
		$tgen = $file[rand(0, (count($file) - 1))];

		return '<span class="app app-tgen">TGEN BEST:</span> ' . str_replace("\n", '', $tgen);

	}

	public static function getUsersByRoomId($id) {

		$id = (int) $id;
		$time = (time() - self::MAX_IDLE);

		$qry = "
			SELECT
				*
			FROM
				rooms_users
			WHERE
				room_id = $id
			AND
				timestamp >= $time
			ORDER BY
				name ASC
		";

		$users = $GLOBALS['MHP']->getRows($qry);
		$users = self::formatUsers($users);

		self::setUsersStatus($id, $users);

		return $users;

	}

	public static function parseMessage($room_id, $message) {

		if (substr($message, 0, 1) == '/') {

			$tokens = explode(' ', $message);
			$remainder = substr($message, strlen($tokens[0]), strlen($message));
			$type = self::TYPE_NORMAL;

			switch ($tokens[0]) {
/*
				case '/id':
					$type = self::TYPE_HTML;
					$id = implode(' ', $tokens);
					$id = Database::clean(substr($id, 3, strlen($id)));
					$msg = self::getMessageById($id);
					$url = 'TODO';
					#$message = sprintf('<span class="app app-search">Found the search term "%s" %s</span>', $term, Tools::formatMultiple($results, 'time'));
					$message = sprintf('<a class="app app-id" href="%s" target="_blank">&lt;%s&gt; %s</a>', $url, $msg['name'], $msg['message']);
					break;
*/
				case '/me':
					$message = $remainder;
					$type = self::TYPE_EMOTE;
					break;
				case '/search':
					$type = self::TYPE_HTML;
					$term = implode(' ', $tokens);
					$term = Database::clean(substr($term, 8, strlen($term)));
					$results = self::getSearchResults($term, $room_id);
					$message = sprintf('<span class="app app-search">Found the search term "%s" %s</span>', $term, Tools::formatMultiple($results, 'time'));
					break;
				case '/seen':
					$type = self::TYPE_HTML;
					$user = implode(' ', $tokens);
					if ($user) {

						$user = Database::clean(substr($user, 6, strlen($user)));
						$results = self::getMessagesByRoomIdAndUser($room_id, $user, 1);

						if ($results) {
							$last_seen = Tools::formatTimeDiff(2, $results[0]['timestamp']);
							$url = self::formatMessageLink($results[0]);
							$message = sprintf('<span class="app app-seen">%s was last seen <a href="%s" target="_blank">%s</a></span>', $user, $url, $last_seen);
						} else {
							$message = sprintf('<span class="app app-seen">%s has never posted in this room!</span>', $user);
						}

					} else {
						$message = '<span class="app app-seen">Seen who?</span>';
					}
					break;
				case '/tgen':
					$type = self::TYPE_HTML;
					if ($tokens[1] == 'best') {
						$message = self::getTgenBest();
					} else {
						$message = self::getTgen($tokens[1]);
					}
					break;
				case '/topic':
					$type = self::TYPE_HTML;
					$topic = implode(' ', $tokens);
					$topic = Database::clean(substr($topic, 7, strlen($topic)));
					$results = self::setTopicByRoomId($room_id, $topic);
					$message = sprintf('<span class="app app-topic">The topic is now "%s"</span>', $topic);
					break;

			}

			return array(
				'message' => $message,
				'type' => $type
			);

		}

		return false;

	}

	public static function putFiles($room_id, $username, $files) {

		if (!is_array($files)) return;

		foreach ($files as $file) {

			if (Tools::checkFile($file)) {

				$dir_local = self::DIR_UPLOADS_LOCAL . $room_id;
				$file_local = $dir_local . '/' . $file['name'];
				$file_remote = self::DIR_UPLOADS_REMOTE . $room_id . '/' . $file['name'];

				move_uploaded_file($file['tmp_name'], $file_local);

				if (Tools::hasImageExtension($file_remote)) {
					$message = sprintf('/img %s', $file_remote);
					$type = self::TYPE_NORMAL;
				} else {
					$message = sprintf('I uploaded <a href="%s" target="_blank">%s</a>', $file_remote, $file['name']);
					$type = self::TYPE_HTML;
				}

				self::putMessage(
					$room_id,
					($username ? $username : self::DEFAULT_NAME),
					$type,
					$message
				);

			}

		}

	}

	public static function putMessage($room_id, $name, $type, $message) {

			$GLOBALS['MHP']->insertRow('messages', array(
				'room_id' => $room_id,
				'hostname' => $_SERVER['REMOTE_ADDR'],
				'name' => $name,
				'timestamp' => time(),
				'type' => $type,
				'message' => $message
			));

	}

	public static function putMessages($room_id, $name, $messages) {

		if (!is_array($messages)) {
			return;
		}

		foreach ($messages as $message) {

			if ($message) {

				$parsed = self::parseMessage($room_id, $message);

				if ($parsed) {
					$message = $parsed['message'];
					$type = $parsed['type'];
				}

				$GLOBALS['MHP']->insertRow('messages', array(
					'room_id' => $room_id,
					'hostname' => $_SERVER['REMOTE_ADDR'],
					'name' => $name,
					'timestamp' => time(),
					'type' => $type,
					'message' => $message
				));

			}

		}

	}

	/**
	 * This function assumes room_name doesn't already exist
	 * @param str $room_name The name of the room to create
	 * @return mixed Either the string of the error on failure, or the room array on success
	 */
	public static function putRoom($room_name) {

		$valid_chars = Tools::makeAlphaNumericArray();

		if (Tools::isValidString($room_name, $valid_chars)) {

			$room = array(
				'name' => $room_name,
				'code' => self::generateCode($room_name),
				'hidden' => false,
				'topic' => 'Brand new room!'
			);

			$GLOBALS['MHP']->insertRow('rooms', $room);

			return $room;

		}

		return 'Room names must be alphanumeric.';

	}

	public static function setCommand($action, $value) {

		self::$commands[] = (object) array(
			'action' => $action,
			'value' => $value
		);

	}

	public static function setTopicByRoomId($room_id, $topic) {

		$room_id = (int) $room_id;
		$pairs = array(
			'topic' => Database::clean($topic)
		);
		$where = "room_id = $room_id";

		$GLOBALS['MHP']->updateRows('rooms', $pairs, $where, 1);
		self::setCommand('topic', $topic);

	}

	private static function setUsersStatus($room_id, $users) {
return;

		$threshold = (time() - self::TIME_IDLE);
		$pairs = array(
			'status' => self::STATUS_OFFLINE
		);
		$where = "(timestamp <= $threshold)";

		foreach ($users as $user) {

			if (
				($user['status'] != self::STATUS_OFFLINE)
				&& ($user['timestamp'] < $threshold)
			) {
				self::putMessages($room_id, $user['name'], array(self::TEXT_EXIT));
			}

		}

		// Set users offline
		$GLOBALS['MHP']->updateRows('rooms_users', $pairs, $where);

	}

	public static function updateUserInRoom($room_id, $name, $status) {

		if (!$name) return;

		// Set up the data
		$ip = $_SERVER['REMOTE_ADDR'];
		$name = Database::clean($name);
		$status = (int) $status;

		// Check if this user has been in this room before
		$room_user = null;
		$users = self::getUsersByRoomId($room_id);

		foreach ($users as $user) {

			if (
/*
				($user['hostname'] == $ip)
				|| ($user['name'] == $name)
				($user['hostname'] == $ip)
*/
				($user['name'] == $name)
			) {
					$room_user = $user;
					break;
			}

		}

		if ($room_user) {

			// Update the user
			$pairs = array(
				'hostname' => $ip,
				'name' => $name,
				'status' => $status
			);

			$where = "(name = '$name')";
/*
			$where = "((hostname = '$ip') || (name = '$name'))";
			$where .= "AND (room_id = $room_id)";
*/

			$GLOBALS['MHP']->updateRows('rooms_users', $pairs, $where);

		} else {

			// "user has entered the room"
			#self::putMessages($room_id, $name, array(self::TEXT_ENTER));

			// Insert the user
			$GLOBALS['MHP']->insertRow('rooms_users', array(
				'hostname' => $ip,
				'name' => $name,
				'room_id' => $room_id,
				'status' => $status,
				'timestamp' => time()
			));

		}

	}

}
