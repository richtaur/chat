<?php
error_log('api_put');

$room_name = $this->tokens[2];
$name = $_POST['name'];
$room = Chat::getRoomByName($room_name);

if (!$room) {
	$this->error = 'Invalid room name';
	return;
}

Chat::putFiles($room['room_id'], $name, $_FILES);
