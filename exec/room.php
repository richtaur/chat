<?php

$this->room_name = $this->tokens[1];
$history = ($this->tokens[2] == 'history');
$dates = $this->tokens;
array_shift($dates);
array_shift($dates);
array_shift($dates);
$date = implode('/', $dates);

if (!$this->room_name) {
	return 'error';
}

$this->room = Chat::getRoomByName($this->room_name);

if ($this->room) {

	if ($history) {

		$this->page_title = $this->room['name'] . ' history';

		if ($date) {
			$this->messages = Chat::getMessagesByRoomIdAndDate($this->room['room_id'], $date);
		} else {
			$this->dates = Chat::getDatesByRoomId($this->room['room_id']);
		}

		return 'history';

	}

} else {

	$this->room = Chat::putRoom($this->room_name);

	if (is_string($this->room)) {
		$this->error_message = $this->room;
		return 'error';
	}

	$this->message = 'Room created! The code is <strong>' . $this->room['code'] . '</strong>. Keep it secret, keep it safe. Type <code>/help</code> for more info.';

}

$this->page_title = $this->room['name'];
$this->users = Chat::getUsersByRoomId($this->room['room_id']);
$this->messages = Chat::getMessagesByRoomId($this->room['room_id']);
$this->num_messages = Chat::getNumMessagesByRoomId($this->room['room_id']);

return 'room';
