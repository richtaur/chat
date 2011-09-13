<div id="screen">

	<div id="sb">

		<fieldset id="users">
			<legend>people online</legend>
			<ul>
<?php foreach ($this->users as $user): ?>
				<li class="<?= Chat::formatStatus($user['status']); ?>" title="<?php echo $user['hostname']; ?>">
					<?php echo $user['name']; ?>
					<?php if ($user['idle'] != 'online now') { ?>
					<?php if ($user['offline_text']) { ?>
					<span class="date">(<?php echo $user['offline_text']; ?>)</span>
					<?php } ?>
					<?php } ?>
				</li>
<?php endforeach; ?>
			</ul>
		</fieldset>

		<fieldset id="commands">
			<legend>commands</legend>
			<ul>
				<li id="clear">/clear</li>
				<li>/echo</li>
				<li>/help</li>
				<li>/history</li>
				<li>/img /image</li>
				<li>/mp3</li>
				<li>/search</li>
				<li>/seen</li>
				<li>/tgen</li>
				<li>/theme</li>
				<li>/time</li>
				<li>/topic</li>
				<li>/yt /youtube</li>
			</ul>
			<p>
				Type <code>/help command</code> to learn more.
			</p>
		</fieldset>

	</div>

	<fieldset class="chats" id="chats">
<?php $this->showPage('chats'); ?>
	</fieldset>

</div>

<form
	action="/api/put/<?php echo $this->room['name']; ?>"
	enctype="multipart/form-data"
	id="controls"
	method="post"
>
	<p>
		<label for="name">Name:</label>
		<input maxlength="30" name="name" id="name" tabindex="1" type="text" value="">
		<span id="upload">
			<input name="file" type="file">
			<button>Upload a file</button>
			<iframe id="iframe-upload" name="iframe-upload" src="about:blank"></iframe>
		</span>
	</p>
	
	<p>
		<label for="message">Message:</label>
		<input autocomplete="off" id="message" tabindex="2" type="text" value="">
	</p>

	<div>
		<input type="submit" value="Send">
	</div>
</form>

<script src="/static/js/jquery-1.2.6.min.js" type="text/javascript"></script>
<script src="/static/js/chat.js?v<?= Chat::VERSION; ?>" type="text/javascript"></script>
<script type="text/javascript">
<!--//
Chat.init({
	roomName : '<?php echo Database::clean($this->room['name']); ?>',
	lastMessageId : <?php echo (int) Chat::$last_message_id; ?>
});
//-->
</script>
