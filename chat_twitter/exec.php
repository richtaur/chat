#!/usr/bin/php
<?php

require('rss.php');
require('/home/www/richtaur.com/chat/common/init.php');

$room_id = (int) $argv[1];
$url = $argv[2];
$rss = new RSS($url, $room_id);
$json = json_decode($rss->get());

if (!$json || !$json->guid) exit;

$log .= "----------\n";
$log .= "Time: " . date('r') . "\n";

$threshold = (time() - 300); // 5 minutes ago
$time = strtotime($json->pubDate);

if ($time < $threshold) {
	$log .= "$time < $threshold, exiting ...\n";
	file_put_contents('/home/richter/tmp/chat.log', $log, FILE_APPEND);
	exit;
}

$log .= "going\n";

$tokens = explode(':', $json->description);
$name = $tokens[0];
$message = substr($json->description, (strlen($name) + 2));
$message .= " (via http://twitter.com/$name)";

/*
echo "going ahead\n";
echo "twitter time: $time\n";
echo "php time: " . time() . "\n";

echo "twitter date: " . date('r', $time) . "\n";
echo "php date: ".date('r')."\n";
*/

$GLOBALS['MHP']->insertRow('messages', array(
	'room_id' => $room_id,
	'hostname' => $_SERVER['REMOTE_ADDR'],
	'name' => "@$name",
	'timestamp' => time(),
	'type' => Chat::TYPE_NORMAL,
	'message' => $message
));

file_put_contents('/home/richter/tmp/chat.log', $log, FILE_APPEND);

/*
stdClass Object
(
    [title] => Brelston: Er, scratch that. Not finally seeing Basterds. Finally seeing District 9. PS it is hot as balls.
    [description] => Brelston: Er, scratch that. Not finally seeing Basterds. Finally seeing District 9. PS it is hot as balls.
    [pubDate] => Sun, 30 Aug 2009 02:06:44 +0000
    [guid] => http://twitter.com/Brelston/statuses/3637035542
    [link] => http://twitter.com/Brelston/statuses/3637035542
)
*/
