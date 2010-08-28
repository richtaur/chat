<?php

$type = $this->tokens[2];
$room_name = $this->tokens[3];
$last_message_id = $this->tokens[4];
$name = $_POST['name'];

$room = Chat::getRoomByName($room_name);

if (!$room) {
	$this->error = 'Invalid room name';
	return;
}

Chat::putMessages($room['room_id'], $name, $_POST['messages']);
Chat::updateUserInRoom($room['room_id'], $name);

$messages = Chat::getMessagesSinceId($room['room_id'], $last_message_id);
$users = Chat::getUsersByRoomId($room['room_id']);

$this->json->commands = Chat::getCommands();
$this->json->last_message_id = Chat::$last_message_id;
$this->json->messages = $messages;
$this->json->users = $users;

header('Cache-Control: no-cache, must-revalidate');
header('Content-type: text/javascript');
header('HTTP/1.0 200 OK');

echo json_encode($this->json);
exit;
