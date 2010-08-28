<h2>History for <a href="/room/<?php echo $this->room['name']; ?>"><?php echo $this->room['name']; ?></a></h2>

<?php if ($this->dates): ?>

<ul id="dates">
<?php

$last_year = null;
$last_month = null;
$last_day = null;

foreach ($this->dates as $date => $num) {

	$timestamp = strtotime($date);
	$year = date('Y', $timestamp);
	$month = date('F', $timestamp);
	$day = date('j', $timestamp);

	if ($year != $last_year) echo "<h2>$year</h2>\n";
	if ($month != $last_month) echo "<h3>$month</h3>\n";

?>
	<li><a href="/room/<?php echo $this->room['name']; ?>/history/<?php echo $date; ?>"><?php echo $date; ?></a> (<?php echo Tools::formatMultiple($num, 'message'); ?>)</li>
<?php

	$last_year = $year;
	$last_month = $month;
	$last_day = $day;

}

?>
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
