<?php
namespace MyApp;

/**
 * Example definition class.
 */

class UserVerifyMail extends \uMailPHP\Definition {
  public static $cname = 'Verify Email (Example)';
  public static $description = 'This email is sent to a new user to let them verify their address.';
  public static $expectsRecipient = true;
  public static $macros = [
    'verify_link' => 'The URL to verify the email address, to be used in a link.',
    'to_phone' => 'The recipient\'s phone number.',
    'to_fax' => 'The recipient\'s fax number.',
    'to_timezone' => 'The recipient\'s timezone.',
    'to_address' => 'The recipient\'s address.',
  ];

  public static function getMacro($name) {
    switch ($name) {
      case 'verify_link':
        return 'todo';
      case 'to_phone':
        return 'todo';
      case 'to_fax':
        return 'todo';
      case 'to_timezone':
        return 'todo';
      case 'to_address':
        return 'todo';
    }
  }

  public static function getSubject() {
    return 'Hi #to_first_name#, please verify your email at #site_name#.';
  }

  public static function getHTML() {
    return <<<EOF
(This is an example definition.)<br>
Hi #to_name#,<br>
<br>
Please verify your account by clicking this link:<br>
<br>
<a href="#verify_link#">Verify Account</a><br>
<br>
If you can't click that link, copy this address into your browser:<br>
<br>
#verify_link#<br>
<br>
Thanks,<br>
#site_name#
EOF;
  }
}
