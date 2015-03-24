<?php
/**
 * Unsubscribe a user.
 *
 * @package Components\mailer
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */
/* @var $_ core */
defined('P_RUN') or die('Direct access prohibited');

$email = $_REQUEST['email'];
$secret = $_REQUEST['verify'];

$secret_check = md5($email.$_->config->com_mailer->unsubscribe_key);

// Check the verification secret.
if ($secret !== $secret_check) {
	$module = new module('com_mailer', 'unsubscribe_result', 'content');
	$module->secret = true;
	return;
}

// Are they already unsubscribed?
if ($_->com_mailer->unsubscribeQuery($email)) {
	pines_notice('You\'ve already been unsubscribed from our mailing list. Please note that you may still receive emails from us of an important nature, such as notifications of an account change.');
	pines_redirect(pines_url());
	return;
}

// Add them to the unsubscribed database.
if (!($result = $_->com_mailer->unsubscribeAdd($email))) {
	$module = new module('com_mailer', 'unsubscribe_result', 'content');
	// Report the right type of failure.
	if ($result === 0)
		$module->not_set_up = true;
	else
		$module->error = true;
	return;
}

// Report success.
$module = new module('com_mailer', 'unsubscribe_result', 'content');
$module->success = true;