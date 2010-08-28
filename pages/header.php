<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en-US">
<head>
<?php

$cookie_theme = $_COOKIE[$this->room_name . '_theme'];
$theme = ($cookie_theme ? $cookie_theme : 'default');

?>
	<link href="/static/css/<?= $theme; ?>.css" rel="stylesheet" type="text/css">
	<link rel="icon" href="/favicon.ico" type="image/ico">
	<link rel="shortcut icon" href="/favicon.ico">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="Copyright" content="<?php echo date('r'); ?> - <?php echo $this->config['Core']['Title']; ?>">
	<meta name="Description" content="OMG insanely simple chat rooms!">
	<meta name="Generator" content="Matt Hackett">
	<meta name="Keywords" content="chat omg chat gtfo">
	<title><?php echo ($this->page_title ? $this->page_title . ' | ' : '') . $this->config['Core']['Title']; ?></title>
</head>
<body>

<h1>chat</h1>

<noscript>
	<p id="js-notice">
		Note: JavaScript is required to use <?php echo $this->config['Core']['Title']; ?>.
		<a href="http://scriptnode.com/article/how-to-enable-javascript/#browsers">How do I enable JavaScript?</a>
	</p>
</noscript>

<div id="bd">
