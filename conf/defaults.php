<?php
/**
 * µMailPHP's configuration defaults.
 *
 * @package uMailPHP
 * @license http://www.gnu.org/licenses/lgpl.html
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
	'unsubscribe_url' => [
		'cname' => 'Unsubscribe URL',
		'description' => 'The URL where the user can unsubscribe from the mailing system.',
		'value' => '',
	],
	'unsubscribe_key' => [
		'cname' => 'Unsubscribe Key',
		'description' => 'This key is used to secure unsubscribe links. You should change it to something else.',
		'value' => 'oUVY&(VF&5F&%64d78g08b97U^FC864d$#5s7R^TYfuiyg*(]&^g64%C79tvityF456dc',
	],
	'unsubscribe_db' => [
		'cname' => 'Unsubscribe Database',
		'description' => 'The SQLite database file for unsubscribed users. This file shouldn\'t be accessible to the web.',
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