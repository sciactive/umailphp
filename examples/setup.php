<?php
error_reporting(E_ALL);

// Composer autoloader would be fine in an outside project.
include '../vendor/autoload.php';
include '../src/autoload.php';

date_default_timezone_set('America/Los_Angeles');

// µMailPHP's config.
\SciActive\RequirePHP::_('µMailPHPConfig', [], function(){
	$config = include('../conf/defaults.php');
	$config->site_name['value'] = 'µMailPHP Example Site';
	$config->site_link['value'] = 'http://localhost/umailphp/';
	$config->master_address['value'] = 'hperrin@gmail.com';
	$config->testing_mode['value'] = true;
	$config->testing_email['value'] = 'hperrin@gmail.com';
	return $config;
});

// This is how you enter the setup app.
include 'UserVerifyMail.php'; // Make sure all of your definition classes are loaded.
$baseURL = '../'; // This is the URL of the µMailPHP root.
$sciactiveBaseURL = '../bower_components/'; // This is the URL of the SciActive libraries.
$restEndpoint = 'rest.php'; // This is the URL of the Nymph endpoint.
include '../src/setup.php'; // And this will load the µMailPHP setup app.