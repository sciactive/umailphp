<?php

/**
 * uMailPHP's configuration defaults.
 *
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://umailphp.org/
 */

return [
  /*
   * Site Name
   * The name of your site.
   */
  'site_name' => '',
  /*
   * Site Link URL
   * The URL of your site.
   */
  'site_link' => '',
  /*
   * Master Address
   * The master address receives all mails that don\'t have a recipient. This includes system information emails.
   */
  'master_address' => '',
  /*
   * From Address
   * The default address used when sending emails.
   */
  'from_address' => 'noreply@'.(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost'),
  /*
   * Testing Mode
   * In testing mode, emails are not actually sent.
   */
  'testing_mode' => false,
  /*
   * Testing Email
   * In testing mode, if this is not empty, all emails are sent here instead. "*Test* " is prepended to their subject line.
   */
  'testing_email' => '',
  /*
   * Additional Parameters
   * If your emails are not being sent correctly, try removing this option.
   */
  'additional_parameters' => '-femail@example.com',
];
