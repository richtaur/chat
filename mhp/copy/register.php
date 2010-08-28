<?php

$headers = sprintf(
	'From: %s <%s>',
	$this->config['Core']['Title'],
	$this->config['Core']['Email']
);

$subject = sprintf(
	'New Account Activation',
	$this->config['Core']['Title']
);

$message = sprintf(
'Thanks for registering with %s!
Before you login, please verify your email address by visiting the below link:

%s

Thanks,
-%s',
	$this->config['Core']['Title'],
	$params['verifyURL'],
	$this->config['Core']['Title']
);
