<p>
Get a room!
</p>

<table cellpadding="0" cellspacing="0" id="rooms">
	<tr>
		<th>Room Name</th>
		<th>Topic</th>
		<th>Messages</th>
		<th>Online Now</th>
	</tr>
<?php foreach ($this->rooms as $room): ?>
<?php $toggle = !$toggle; ?>
	<tr<?php echo ($toggle ? '' : ' class="toggle"'); ?>>
		<td><a href="/room/<?php echo $room['name']; ?>"><?php echo $room['name']; ?></a></td>
		<td><?php echo $room['topic']; ?></td>
		<td><?php echo number_format($room['num_messages']); ?></td>
		<td><?php echo number_format($room['num_online']); ?></td>
	</tr>
<?php endforeach; ?>
</table>

<h3>Create a new room:</h3>

<form action="" id="create" method="post">
	<label for="name">Name:</label>
	<input id="name" type="text">
	<input type="submit" value="Create">
</form>

<script type="text/javascript">
<!--//

document.getElementById('create').onsubmit = function() {

	var el = document.getElementById('name');

	if (el.value) {
		location.href = '/room/' + el.value;
	}

	return false;

};

//-->
</script>
