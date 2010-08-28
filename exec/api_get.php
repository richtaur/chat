<?php

$type = $this->tokens[2];
$room_name = $this->tokens[3];
$last_message_id = (int) $this->tokens[4];
$name = $_POST['name'];
$status = (int) $_POST['status'];

$room = Chat::getRoomByName($room_name);

if (!$room) {
	$this->error = 'Invalid room name';
	return;
}

Chat::putMessages($room['room_id'], $name, $_POST['messages']);
Chat::updateUserInRoom($room['room_id'], $name, $status);

$messages = Chat::getMessagesSinceId($room['room_id'], $last_message_id);
$users = Chat::getUsersByRoomId($room['room_id']);

$this->json->commands = Chat::getCommands();
$this->json->last_message_id = Chat::$last_message_id;
$this->json->messages = $messages;
$this->json->users = $users;
