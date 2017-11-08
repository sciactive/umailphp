<?php
error_reporting(E_ALL);

// Composer autoloader would be fine in an outside project.
include '../vendor/autoload.php';
include '../src/autoload.php';

date_default_timezone_set('America/Los_Angeles');

// uMailPHP's config.
\uMailPHP\Mail::configure([
  'site_name' => 'uMailPHP Example Site',
  'site_link' => 'http://localhost/umailphp/',
  'master_address' => 'someone@example.com',
  'testing_mode' => true,
  'testing_email' => 'someone@example.com',
]);

// This is how you enter the setup app.
include 'UserVerifyMail.php'; // Make sure all of your definition classes are loaded.
$baseURL = '../'; // This is the URL of the uMailPHP root.
$sciactiveBaseURL = '../node_modules/'; // This is the URL of the SciActive libraries.
$restEndpoint = 'rest.php'; // This is the URL of the Nymph endpoint.
include '../setup/setup.php'; // And this will load the uMailPHP setup app.
