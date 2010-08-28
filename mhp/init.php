<?php

// Tell other scripts we've been here
define('MHP', 1);

// Get a shortcut to the root path
$root = $config['Path']['MHP_Local'];

// Include the necessary classes
require_once($root . 'classes/tools.php');
require_once($root . 'classes/core.php');
require_once($root . 'classes/database.php');

// What kind of framework are we using?
if ($config['Core']['Auth'] == 'OpenID') {

	require_once($root . 'libraries/openid/class_openid_v3.php');
	require_once($root . 'classes/auth_openid.php');

} else if ($config['Core']['Auth'] == 'SMF') {

	// We're using SMF -- pull in the server side includes
	require($config['Path']['Auth_Local'] . 'SSI.php');
	require_once($root . 'classes/auth_smf.php');

} else {

	// Default to using the Simple users class
	require_once($root . 'classes/auth_simple.php');

}

// Include the empty class or the user's custom class
$custom_mhp = $config['Path']['Custom_Local'] . 'mhp.php';

if (is_file($custom_mhp)) {
	require_once($custom_mhp);
} else {
	require_once($root . 'classes/mhp_empty.php');
}

// Create an instance of the MHP object
$MHP = new MHP($config);

// Are we in debug mode?
$debug_file = $config['Path']['Custom_Local'] . 'debug.on';

if (is_file($debug_file)) {
	$MHP->setDebug(true);
}

// Get the DB object if we're using SMF
if ($config['Core']['Auth'] == 'SMF') {

	// Point our DB handler to the right DB
	$MHP->select($config['DB']['Name'], $db_connection);

	// Get references to SMF's data
	if ($GLOBALS['ID_MEMBER']) {
		$MHP->user = Array(
			'is_admin' => &$GLOBALS['user_info']['is_admin'],
			'user_id'  => &$GLOBALS['ID_MEMBER'],
			'username' => &$GLOBALS['user_info']['username']
		);
	} else {
		$MHP->user = false;
	}

}

// Include optional plugins
$MHP->setupPlugins();

// Extra security steps
unset($config);
unset($MHP->config['DB']['Pass']);

// Make sure MHP is in the global scope
$GLOBALS['MHP'] = &$MHP;
