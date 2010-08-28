<?php

// Set $this->error to output a 404
// Set $this->json to invoke json_encode
// Set $this->output to bypass json_encode and output manually

$this->error = false;
$this->json = false;

$req = $this->tokens[1];

switch ($req) {

	case 'comet':
		$this->execPage('api_comet');
		break;

	case 'get':
		$this->execPage('api_get');
		break;

	case 'put':
		$this->execPage('api_put');
		break;

	default:
		$this->error = 'Invalid request';
		break;

}

header('Content-type: text/javascript');
header('HTTP/1.0 200 OK');

if ($this->error) {

	$json = Array(
		error => true,
		message => $this->error
	);

	echo json_encode($json);

} else {

	if ($this->json) {
		echo json_encode($this->json);
	} else {
		echo $this->output;
	}

}

exit;
