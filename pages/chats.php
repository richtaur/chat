<?php

$code = 'Y/m/d';
$day = false;
$today = date($code, time());

?>
		<legend>topic: <strong><?php echo Tools::stripHTML($this->room['topic']); ?></strong></legend>
		<ul>
<?php if ($this->message): ?>
			<li class="notice">
				<?php echo $this->message; ?>
			</li>
<?php endif; ?>
<?php if ($this->num_messages > count($this->messages)): ?>
			<li class="notice">
				There are <strong><?php echo Tools::formatMultiple($this->num_messages, 'message'); ?></strong> in this room's history. <a href="/room/<?php echo $this->room['name']; ?>/history">[view entire room history]</a>
			</li>
<?php endif; ?>
<?php foreach ($this->messages as $message): ?>
<?php

$new_day = date($code, $message['timestamp']);

if ($day != $new_day) {

	if ($new_day == $today) {
		$title = 'Today';
	} else {
		$title = date('l, F jS Y', $message['timestamp']);
	}
	
	echo '<li class="day">' . $title . "</li>\n";

	$day = $new_day;

}
	
?>
			<li id="message-<?php echo $message['message_id']; ?>">
				<a class="date" href="/room/<?php echo $this->room['name']; ?>/history/<?php echo date($code, $message['timestamp']); ?>#message-<?php echo $message['message_id']; ?>">[<?php echo $message['date']; ?>]</a>
<!-- type: <?php echo $message['type']; ?>-->
<?php if ($message['type'] == Chat::TYPE_EMOTE) { ?>
				<span title="<?php echo $message['hostname']; ?>"><?php echo $message['name']; ?></span>
<?php } else { ?>
				<span class="name" title="<?php echo $message['hostname']; ?>">&lt;<?php echo $message['name']; ?>&gt;</span>
<?php } ?>
				<span class="message"><?php echo $message['message']; ?></span>
			</li>
<?php endforeach; ?>
		</ul>
