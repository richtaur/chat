<h2>History for <a href="/room/<?php echo $this->room['name']; ?>"><?php echo $this->room['name']; ?></a></h2>

<?php if ($this->dates): ?>

<ul id="dates">
<?php foreach ($this->dates as $date => $num): ?>
	<li><a href="/room/<?php echo $this->room['name']; ?>/history/<?php echo $date; ?>"><?php echo $date; ?></a> (<?php echo Tools::formatMultiple($num, 'message'); ?>)</li>
<?php endforeach; ?>
</ul>

<?php else: ?>

<fieldset id="chats">
<?php $this->showPage('chats'); ?>
</fieldset>

<?php endif; ?>

<script type="text/javascript">
<!--//
(function() {

	var hash = location.hash.substr(1, location.hash.length),
		el = document.getElementById(hash);

	if (el) el.className = 'sel';

})();
//-->
</script>
