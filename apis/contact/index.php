<?php
	require_once('../../vendor/autoload.php');
	require_once('../../globals.php');
	require_once('../JsonPDO.php');
	require_once('../SlimAPI.php');
	require('routes.php');
	
	$api = new SlimAPI([
		'debug' => API_DEBUG,
		'name' => 'Contacts API'
	]);
	$api->addReadme('/','./README.md');
	$api = addAPIRoutes($api);
	$api->run(); 