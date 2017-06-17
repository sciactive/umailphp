<?php

/**
 * uMailPHP's configuration defaults.
 *
 * @package uMailPHP
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

return (object) [
  'site_name' => [
    'cname' => 'Site Name',
    'description' => 'The name of your site.',
    'value' => '',
  ],
  'site_link' => [
    'cname' => 'Site Link URL',
    'description' => 'The URL of your site.',
    'value' => '',
  ],
  'master_address' => [
    'cname' => 'Master Address',
    'description' => 'The master address receives all mails that don\'t have a recipient. This includes system information emails.',
    'value' => '',
  ],
  'from_address' => [
    'cname' => 'From Address',
    'description' => 'The default address used when sending emails.',
    'value' => 'noreply@'.$_SERVER['SERVER_NAME'],
  ],
  'testing_mode' => [
    'cname' => 'Testing Mode',
    'description' => 'In testing mode, emails are not actually sent.',
    'value' => false,
  ],
  'testing_email' => [
    'cname' => 'Testing Email',
    'description' => 'In testing mode, if this is not empty, all emails are sent here instead. "*Test* " is prepended to their subject line.',
    'value' => '',
  ],
  'additional_parameters' => [
    'cname' => 'Additional Parameters',
    'description' => 'If your emails are not being sent correctly, try removing this option.',
    'value' => '-femail@example.com',
  ],
];
