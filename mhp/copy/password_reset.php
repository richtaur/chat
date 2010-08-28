<?php

$headers = sprintf(
	'From: %s <%s>',
	$this->config['Core']['Title'],
	$this->config['Core']['Email']
);

$subject = sprintf(
	'Password Reset',
	$this->config['Core']['Title']
);

$message = sprintf(
'Hi %s,

Did you forget your password? No problem! Please follow the below link to reset your password:

%s

Thanks,
-%s

(Note: this request was sent from %s)',
	$user['username'],
	$params['resetURL'],
	$this->config['Core']['Title'],
	Tools::getIP()
);
