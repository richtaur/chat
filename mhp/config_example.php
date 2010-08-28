<?php

$config = Array(

	'Core' => Array(

		'Auth' => 'SMF',
		'Email' => 'webmaster@valadria.com',
		'Title' => 'Valadria',
		'URL' => 'http://valadria.com/'

	),

	'DB' => Array(

		'Name'   => 'valadria',
		'Pass'   => 'user',
		'Server' => 'localhost',
		'User'   => 'user'

	),

	'Path' => Array(

		'Auth_Local' => '/home/richter/www/valadria/public_html/forum/',
		'Auth_Remote' => '/forum/',
		'Custom_Local' => '/home/richter/www/valadria/common/',
		'Exec_Local' => '/home/richter/www/valadria/exec/',
		'Pages_Local' => '/home/richter/www/valadria/pages/',
		'MHP_Local' => '/home/richter/www/valadria/mhp/'

	)

);

$config['Comments'] = Array(
	'DB' => Array(
		COMMENT_SECTION => Array(
			'Allow_Replies' => false,
			'Delay' => 0, // Number of seconds between comments allowed
			'Field_Count' => 'guestbook_signatures',
			'Field_ID' => 'guestbook_id',
			'Table' => 'v_guestbook'
		),
		'Min_Length' => 5,
		'Max_Length' => 2000,
		'Table_Comments' => 'v_comments'
	)
);

$config['Users'] = Array(
	'Auth_Redirect' => '/',
	'Cookie_Duration' => 2592000, // 30 days
	'Cookie_Name' => 'Valadria',
	'Cookie_Path' => '/',
	'Email_Verification' => true,
	'Password_Min' => 6,
	'Password_Max' => 20,
	'Password_Salt' => 'VaLaDRIA',
	'Settings' => Array(),
	'Table_Settings' => 'v_user_settings'
	'Table_Users' => 'v_users',
	'URL_Password' => 'password/',
	'URL_Verify' => 'verify/',
	'Username_Blacklist' => Array(),
	'Username_Changes' => false,
	'Username_Min' => 2,
	'Username_Max' => 20
);
