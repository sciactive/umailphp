<?php

$putYourEmailHere = 'someone@example.com'; // PUT YOUR EMAIL HERE TO TEST

function sendExampleEmail() {
  //
  // Let's send an example email!
  //

  // This could be just an email address, but then the user macros wouldn't be filled.
  $user = (object) [
    'name' => 'John Userman',
    'nameFirst' => 'John',
    'nameLast' => 'Userman',
    'email' => 'johnuserman@example.com',
    'username' => 'johnuserman@example.com',
  ];

  $link = 'https://example.com/verifyemail?email=johnuserman@example.com&secret=arandomlygeneratedsecret';
  $macros = [
    'verify_link' => htmlspecialchars($link),
    'to_phone' => htmlspecialchars(\uMailPHP\Mail::formatPhone('18005551212')),
    'to_timezone' => htmlspecialchars('America/Los_Angeles'),
    'to_address' => htmlspecialchars("123 Fake St.\nCity, State 00000"),
  ];

  $mail = new \uMailPHP\Mail('MyApp\UserVerifyMail', $user, $macros);

  echo $mail->send() ? "It worked! Go check your email." : "It didn't work. :(";
  echo "\n";
}



// Below here is all setup.

error_reporting(E_ALL);

if (php_sapi_name() != "cli") {
  die("You can only run this from the command line.");
}

// Composer autoloader would be fine in an outside project.
include '../vendor/autoload.php';
include '../src/autoload.php';

date_default_timezone_set('America/Los_Angeles');

\Nymph\Nymph::configure([
  'MySQL' => [
    'host' => '127.0.0.1',
    'database' => 'nymph_example',
    'user' => 'nymph_example',
    'password' => 'omgomg'
  ]
]);

// uMailPHP's config.
\SciActive\RequirePHP::_('uMailPHPConfig', [], function () use ($putYourEmailHere) {
  $config = include('../conf/defaults.php');
  $config->site_name = 'uMailPHP Example Site';
  $config->site_link = 'http://localhost/umailphp/';
  $config->master_address = 'someone@example.com';
  $config->testing_mode = true;
  $config->testing_email = $putYourEmailHere;
  return $config;
});

include 'UserVerifyMail.php';

sendExampleEmail();
