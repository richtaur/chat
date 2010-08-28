<?php

$headers = sprintf(
	'From: %s <%s>',
	$this->config['Core']['Title'],
	$this->config['Core']['Email']
);

$subject = sprintf(
	'Email Change',
	$this->config['Core']['Title']
);

$message = sprintf(
'Hi,

Looks like your email has changed. To re-verify your account, please visit the below link:

%s

Thanks,
-%s',
	$params['verifyURL'],
	$this->config['Core']['Title']
);
