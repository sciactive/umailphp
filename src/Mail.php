<?php namespace µMailPHP;
/**
 * Mail class.
 *
 * @package uMailPHP
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

/**
 * Creates and sends emails.
 *
 * This class supports attachments and custom headers.
 *
 * This class is based on a class by Alejandro Gervasio
 * http://www.devshed.com/cp/bio/Alejandro-Gervasio/
 *
 * @package uMailPHP
 */
class Mail {
	const VERSION = '1.0.0';
	/**
	 * The sender's email address.
	 *
	 * @var string
	 */
	public $sender;
	/**
	 * The recipient's email address.
	 *
	 * @var string
	 */
	public $recipient;
	/**
	 * The message subject.
	 *
	 * @var string
	 */
	public $subject;
	/**
	 * The message text's MIME type.
	 *
	 * @var string
	 */
	public $textMimeType = 'text/html';
	/**
	 * An array of headers to include in the message.
	 *
	 * @var array
	 */
	public $headers = [];
	/**
	 * An array of known MIME types.
	 *
	 * The values are the mime types, and the keys are the file extensions.
	 *
	 * @var array
	 */
	public $mimeTypes = [];
	/**
	 * An array of attachments.
	 *
	 * @var array
	 */
	public $attachments = [];
	/**
	 * If all recipients are unsubscribed, the email should report success on send.
	 *
	 * @var bool
	 * @access private
	 */
	private $recipientsUnsubscribed = false;

	/**
	 * @param \µMailPHP\Definition The name of the mail definition class.
	 * @param string|null $recipient The recipient's email address. If left null, the rendition must have a recipient.
	 * @param array $macros An associative array of macros. These override macros from the definition.
	 * @param string|null $sender The sender's email address. If left null, the rendition or default sender will be used.
	 * @param \µMailPHP\Rendition An optional rendition. If left null, the latest ready rendition will be used. If false, no rendition will be used.
	 * @param \µMailPHP\Template An optional template. If left null, the latest ready template will be used. If false, the default template will be used.
	 */
	public function __construct($definition, $recipient = null, $macros = [], $sender = null, $rendition = null, $template = null) {
		if (!class_exists($definition) || !is_subclass_of($definition, '\µMailPHP\Definition')) {
			throw new \InvalidArgumentException('Mail definition is required.');
		}

		$config = \SciActive\R::_('µMailPHPConfig');

		// Format recipient.
		if ($recipient && is_string($recipient)) {
			$recipient = (object) ['email' => $recipient];
		}

		// Find any renditions.
		if ($rendition === null) {
			$renditions = (array) \Nymph\Nymph::getEntities(
					['class' => '\µMailPHP\Rendition', 'reverse' => true],
					['&',
						'strict' => [
							['enabled', true],
							['definition', $definition]
						]
					]
				);
			foreach ($renditions as $cur_rendition) {
				if ($cur_rendition->ready()) {
					$rendition = $cur_rendition;
					break;
				}
			}
			unset($renditions, $cur_rendition);
		}

		// Get the email sender.
		if ($rendition && !isset($sender) && !empty($rendition->from)) {
			$sender = $rendition->from;
		}

		// Get the email recipient(s).
		if (!$recipient) {
			// If it's supposed to have a recipient already, report failure.
			if ($definition::expectsRecipient) {
				throw new \UnexpectedValueException('This email definition requires a recipient.');
			}
			if ($rendition) {
				if ($rendition->to) {
					// Check Tilmeld users/groups if Tilmeld is loaded.
					if (class_exists('\Tilmeld\User') && strpos($rendition->to, ',') === false) {
						if (preg_match('/<.+@.+>/', $rendition->to)) {
							$check_email = trim(preg_replace('/^.*<(.+@.+)>.*$/', '$1', $rendition->to));
						} else {
							$check_email = trim($rendition->to);
						}
						// Check for a user or group with that email.
						$user = \Nymph\Nymph::getEntity(
								['class' => '\Tilmeld\User'],
								['&',
									'strict' => ['email', $check_email]
								]
							);
						if ($user) {
							$recipient = $user;
						} else {
							$group = \Nymph\Nymph::getEntity(
									['class' => '\Tilmeld\Group'],
									['&',
										'strict' => ['email', $check_email]
									]
								);
							if ($group) {
								$recipient = $group;
							}
						}
					}
					if (!$recipient) {
						$recipient = (object) ['email' => $rendition->to];
					}
				}
			} else {
				// Send to the master address if there's no recipient.
				if (!$config->master_address['value']) {
					throw new \UnexpectedValueException('This email needs a recipient and no master address is set.');
				}
				$recipient = (object) ['email' => $config->master_address['value']];
			}
		}

		// Remove emails that are on the unsubscribed list if the definition obeys it.
		if ($definition::unsubscribe) {
			$unsub = new UnsubscribeStore();

			if (strpos($recipient->email, ',') !== false) {
				$emails = explode(',', $recipient->email);
			} else {
				$emails = array($recipient->email);
			}

			$changed = false;
			foreach ($emails as $key => &$cur_email) {
				$cur_email = trim($cur_email);
				if (preg_match('/<.+@.+>/', $cur_email)) {
					$check_email = trim(preg_replace('/^.*<(.+@.+)>.*$/', '$1', $cur_email));
				} else {
					$check_email = $cur_email;
				}
				if ($unsub->unsubscribeQuery($check_email)) {
					unset($emails[$key]);
					$changed = true;
				}
			}
			unset($cur_email);

			if ($changed) {
				$recipient->email = implode(', ', $emails);
			}
			// If every user is unsubscribed, report a success without sending
			// an email.
			if (!$recipient->email) {
				$this->recipientsUnsubscribed = true;
			}
		}

		// Get the email contents.
		$body = [];
		if ($rendition) {
			$body['subject'] = $rendition->subject;
			$body['content'] = $rendition->content;
		} else {
			$body['subject'] = $definition::getSubject();
			$body['content'] = $definition::getHTML();
		}

		// Get the template.
		if ($template === null) {
			$templates = (array) \Nymph\Nymph::getEntities(
					['class' => '\µMailPHP\Template', 'reverse' => true],
					['&',
						'strict' => ['enabled', true]
					]
				);
			// Get the first template that's ready.
			foreach ($templates as $cur_template) {
				if ($cur_template->ready()) {
					$template = $cur_template;
					break;
				}
			}
			unset($templates, $cur_template);
		}
		// If there is no template, use a default one.
		if (!$template) {
			$template = new Template();
		}

		// Build the body of the email.
		$body['content'] = str_replace(
				'#content#',
				$body['content'],
				str_replace(
						'#content#',
						$template->content,
						$template->document
					)
			);

		// Protects users from being unsubscribed by just anyone.
		$unsubscribe_secret = md5($recipient->email . $config->unsubscribe_key['value']);
		$unsubscribe_url = $config->unsubscribe_url['value'];
		$unsubscribe_url .= (strpos($unsubscribe_url, '?') === false ? '?' : '&').'email='.urlencode($recipient->email).'&verify='.urlencode($unsubscribe_secret);

		// Replace macros and search strings.
		foreach ($body as &$cur_field) {
			// Some of these str_replace calls are wrapped in a strpos call,
			// because they involve some processing just to make the call. In a
			// system sending out millions of emails, that adds up to a lot of
			// wasted CPU.
			foreach ((array) $template->replacements as $cur_string) {
				if (!$cur_string['macros']) {
					continue;
				}
				$cur_field = str_replace($cur_string['search'], $cur_string['replace'], $cur_field);
			}
			if (strpos($cur_field, '#subject#') !== false) {
				$cur_field = str_replace('#subject#', htmlspecialchars($body['subject']), $cur_field);
			}
			// Links
			if (strpos($cur_field, '#site_link#') !== false) {
				$cur_field = str_replace('#site_link#', htmlspecialchars($config->site_link['value']), $cur_field);
			}
			if (strpos($cur_field, '#unsubscribe_link#') !== false) {
				$cur_field = str_replace('#unsubscribe_link#', htmlspecialchars($unsubscribe_url), $cur_field);
			}
			// Recipient
			if (strpos($cur_field, '#to_username#') !== false) {
				$cur_field = str_replace('#to_username#', htmlspecialchars(isset($recipient->username) ? $recipient->username : (isset($recipient->groupname) ? $recipient->groupname : '')), $cur_field);
			}
			if (strpos($cur_field, '#to_name#') !== false) {
				$cur_field = str_replace('#to_name#', htmlspecialchars(isset($recipient->name) ? $recipient->name : ''), $cur_field);
			}
			if (strpos($cur_field, '#to_first_name#') !== false) {
				$cur_field = str_replace('#to_first_name#', htmlspecialchars(isset($recipient->name_first) ? $recipient->name_first : ''), $cur_field);
			}
			if (strpos($cur_field, '#to_last_name#') !== false) {
				$cur_field = str_replace('#to_last_name#', htmlspecialchars(isset($recipient->name_last) ? $recipient->name_last : ''), $cur_field);
			}
			if (strpos($cur_field, '#to_email#') !== false) {
				$cur_field = str_replace('#to_email#', htmlspecialchars(isset($recipient->email) ? $recipient->email : ''), $cur_field);
			}
			// Current User with Tilmeld.
			if (class_exists('\Tilmeld\User')) {
				if (strpos($cur_field, '#username#') !== false) {
					$cur_field = str_replace('#username#', htmlspecialchars($_SESSION['user']->username), $cur_field);
				}
				if (strpos($cur_field, '#name#') !== false) {
					$cur_field = str_replace('#name#', htmlspecialchars($_SESSION['user']->name), $cur_field);
				}
				if (strpos($cur_field, '#first_name#') !== false) {
					$cur_field = str_replace('#first_name#', htmlspecialchars($_SESSION['user']->name_first), $cur_field);
				}
				if (strpos($cur_field, '#last_name#') !== false) {
					$cur_field = str_replace('#last_name#', htmlspecialchars($_SESSION['user']->name_last), $cur_field);
				}
				if (strpos($cur_field, '#email#') !== false) {
					$cur_field = str_replace('#email#', htmlspecialchars($_SESSION['user']->email), $cur_field);
				}
			}
			// Date/Time
			if (strpos($cur_field, '#datetime_sort#') !== false) {
				$cur_field = str_replace('#datetime_sort#', htmlspecialchars(Mail::formatDate(time(), 'full_sort')), $cur_field);
			}
			if (strpos($cur_field, '#datetime_short#') !== false) {
				$cur_field = str_replace('#datetime_short#', htmlspecialchars(Mail::formatDate(time(), 'full_short')), $cur_field);
			}
			if (strpos($cur_field, '#datetime_med#') !== false) {
				$cur_field = str_replace('#datetime_med#', htmlspecialchars(Mail::formatDate(time(), 'full_med')), $cur_field);
			}
			if (strpos($cur_field, '#datetime_long#') !== false) {
				$cur_field = str_replace('#datetime_long#', htmlspecialchars(Mail::formatDate(time(), 'full_long')), $cur_field);
			}
			if (strpos($cur_field, '#date_sort#') !== false) {
				$cur_field = str_replace('#date_sort#', htmlspecialchars(Mail::formatDate(time(), 'date_sort')), $cur_field);
			}
			if (strpos($cur_field, '#date_short#') !== false) {
				$cur_field = str_replace('#date_short#', htmlspecialchars(Mail::formatDate(time(), 'date_short')), $cur_field);
			}
			if (strpos($cur_field, '#date_med#') !== false) {
				$cur_field = str_replace('#date_med#', htmlspecialchars(Mail::formatDate(time(), 'date_med')), $cur_field);
			}
			if (strpos($cur_field, '#date_long#') !== false) {
				$cur_field = str_replace('#date_long#', htmlspecialchars(Mail::formatDate(time(), 'date_long')), $cur_field);
			}
			if (strpos($cur_field, '#time_sort#') !== false) {
				$cur_field = str_replace('#time_sort#', htmlspecialchars(Mail::formatDate(time(), 'time_sort')), $cur_field);
			}
			if (strpos($cur_field, '#time_short#') !== false) {
				$cur_field = str_replace('#time_short#', htmlspecialchars(Mail::formatDate(time(), 'time_short')), $cur_field);
			}
			if (strpos($cur_field, '#time_med#') !== false) {
				$cur_field = str_replace('#time_med#', htmlspecialchars(Mail::formatDate(time(), 'time_med')), $cur_field);
			}
			if (strpos($cur_field, '#time_long#') !== false) {
				$cur_field = str_replace('#time_long#', htmlspecialchars(Mail::formatDate(time(), 'time_long')), $cur_field);
			}
			// System
			if (strpos($cur_field, '#site_name#') !== false) {
				$cur_field = str_replace('#site_name#', htmlspecialchars($config->site_name['value']), $cur_field);
			}
			// Argument Macros
			foreach ($macros as $cur_name => $cur_value) {
				$cur_field = str_replace("#$cur_name#", $cur_value, $cur_field);
			}
			// Definition Macros
			foreach ($definition::macros as $cur_name => $cur_desc) {
				if (strpos($cur_field, "#$cur_name#") !== false) {
					$cur_field = str_replace("#$cur_name#", $definition::getMacro($cur_name), $cur_field);
				}
			}
			foreach ((array) $template->replacements as $cur_string) {
				if ($cur_string['macros']) {
					continue;
				}
				$cur_field = str_replace($cur_string['search'], $cur_string['replace'], $cur_field);
			}
		}
		unset($cur_field);

		// Add additional recipients.
		if ($rendition) {
			if ($rendition->cc) {
				$email->addHeader('CC', $rendition->cc);
			}
			if ($rendition->bcc) {
				$email->addHeader('BCC', $rendition->bcc);
			}
		}


		// Get default values for missing parameters.
		if (!isset($sender)) {
			$sender = $config->from_address['value'];
		}
		$destination = isset($recipient->name) ? "\"".str_replace('"', '', $recipient->name)."\" <{$recipient->email}>" : (isset($recipient->email) ? $recipient->email : '');


		// Validate incoming parameters.
		if (!preg_match('/^.+@.+$/', $sender)) {
			throw new \InvalidArgumentException('Invalid value for email sender.');
		}
		if (!$this->recipientsUnsubscribed && !preg_match('/^.+@.+$/', $destination)) {
			throw new \InvalidArgumentException('Invalid value for email recipient.');
		}
		if (!isset($body['subject']) || !is_string($body['subject']) || strlen($body['subject']) > 255) {
			throw new \LengthException('Invalid length for email subject.');
		}
		if (!isset($body['content']) || !is_string($body['content']) || strlen($body['content']) < 1) {
			throw new \UnexpectedValueException('Invalid value for email message.');
		}


		// Assign the complete variables.
		$this->sender = $sender;
		$this->recipient = $destination;
		$this->subject = $body['subject'];
		$this->message = $body['content'];
		// Define some default MIME headers
		$this->headers['MIME-Version'] = '1.0';
		$this->headers['Content-Type'] = 'multipart/mixed;boundary="MIME_BOUNDRY"';
		//$this->headers['X-Mailer'] = 'PHP5';
		$this->headers['X-Priority'] = '3';
		$this->headers['User-Agent'] = 'µMailPHP '.Mail::VERSION;
		// Define some default MIME types
		$this->mimeTypes['doc'] = 'application/msword';
		$this->mimeTypes['pdf'] = 'application/pdf';
		$this->mimeTypes['gz'] = 'application/x-gzip';
		$this->mimeTypes['exe'] = 'application/x-msdos-program';
		$this->mimeTypes['rar'] = 'application/x-rar-compressed';
		$this->mimeTypes['swf'] = 'application/x-shockwave-flash';
		$this->mimeTypes['tgz'] = 'application/x-tar-gz';
		$this->mimeTypes['tar'] = 'application/x-tar';
		$this->mimeTypes['zip'] = 'application/zip';
		$this->mimeTypes['mid'] = 'audio/midi';
		$this->mimeTypes['mp3'] = 'audio/mpeg';
		$this->mimeTypes['au'] = 'audio/ulaw';
		$this->mimeTypes['aif'] = 'audio/x-aiff';
		$this->mimeTypes['aiff'] = 'audio/x-aiff';
		$this->mimeTypes['wma'] = 'audio/x-ms-wma';
		$this->mimeTypes['wav'] = 'audio/x-wav';
		$this->mimeTypes['gif'] = 'image/gif';
		$this->mimeTypes['jpg'] = 'image/jpeg';
		$this->mimeTypes['jpeg'] = 'image/jpeg';
		$this->mimeTypes['jpe'] = 'image/jpeg';
		$this->mimeTypes['png'] = 'image/png';
		$this->mimeTypes['tif'] = 'image/tiff';
		$this->mimeTypes['tiff'] = 'image/tiff';
		$this->mimeTypes['css'] = 'text/css';
		$this->mimeTypes['htm'] = 'text/html';
		$this->mimeTypes['html'] = 'text/html';
		$this->mimeTypes['txt'] = 'text/plain';
		$this->mimeTypes['rtf'] = 'text/rtf';
		$this->mimeTypes['xml'] = 'text/xml';
		$this->mimeTypes['flv'] = 'video/flv';
		$this->mimeTypes['mpe'] = 'video/mpeg';
		$this->mimeTypes['mpeg'] = 'video/mpeg';
		$this->mimeTypes['mpg'] = 'video/mpeg';
		$this->mimeTypes['mov'] = 'video/quicktime';
		$this->mimeTypes['asf'] = 'video/x-ms-asf';
		$this->mimeTypes['wmv'] = 'video/x-ms-wmv';
		$this->mimeTypes['avi'] = 'video/x-msvideo';
	}

	/**
	 * Create text part of the message.
	 *
	 * @access private
	 * @return string The text.
	 */
	private function buildTextPart() {
		return "--MIME_BOUNDRY\nContent-Type: {$this->textMimeType}; charset=utf-8\nContent-Transfer-Encoding: 7bit\n\n\n{$this->message}\n\n";
	}

	/**
	 * Create attachments part of the message.
	 *
	 * @access private
	 * @return string The attachment section.
	 */
	private function buildAttachmentPart() {
		if (count($this->attachments) > 0) {
			$attachment_part = '';
			foreach ($this->attachments as $attachment) {
				$file_str = chunk_split(base64_encode(file_get_contents($attachment)));
				$attachment_part .= "--MIME_BOUNDRY\nContent-Type: ".$this->getMimeType($attachment)."; name=".basename($attachment)."\nContent-disposition: attachment\nContent-Transfer-Encoding: base64\n\n{$file_str}\n\n";
			}
			return $attachment_part;
		}
	}

	/**
	 * Create message headers.
	 *
	 * @access private
	 * @param array $required_headers Any headers that should append/replace the defined headers.
	 * @return string The headers.
	 */
	private function buildHeaders($required_headers = []) {
		$build_headers = array_merge($this->headers, $required_headers);
		$headers = [];
		foreach ($build_headers as $name => $value) {
			$headers[] = "{$name}: {$value}";
		}
		return implode("\n", $headers)."\nThis is a multi-part message in MIME format.\n";
	}

	/**
	 * Add new header.
	 *
	 * @param string $name The header's name.
	 * @param string $value The header's value.
	 */
	public function addHeader($name, $value) {
		$this->headers[$name] = $value;
	}

	/**
	 * Add new attachment.
	 *
	 * @param string $attachment The attachment filename.
	 * @return bool True on success, false on failure.
	 */
	public function addAttachment($attachment) {
		if (!file_exists($attachment)) {
			pines_error('Invalid attachment.');
			return false;
		}
		$this->attachments[] = $attachment;
		return true;
	}

	/**
	 * Get MIME Type of attachment.
	 *
	 * @param string $attachment The attachment filename.
	 * @return mixed MIME type on success, null on failure.
	 */
	public function getMimeType($attachment) {
		$attachment = explode('.', basename($attachment));
		if (!isset($this->mimeTypes[strtolower($attachment[count($attachment) - 1])])) {
			pines_error('MIME Type not found.');
			return null;
		}
		return $this->mimeTypes[strtolower($attachment[count($attachment) - 1])];
	}

	/**
	 * Send email.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function send() {
		if ($this->recipientsUnsubscribed) {
			return true;
		}

		// First verify values.
		if (!preg_match('/^.+@.+$/', $this->sender)) {
			return false;
		}
		if (!preg_match('/^.+@.+$/', $this->recipient)) {
			return false;
		}
		if (!$this->subject || strlen($this->subject) > 255) {
			return false;
		}

		$config = \SciActive\R::_('µMailPHPConfig');

		// Headers that must be in the sent message.
		$required_headers = [];

		// Are we in testing mode?
		if ($config->testing_mode['value']) {
			// If the testing email is empty, just return true.
			if (empty($config->testing_email['value'])) {
				return true;
			}
			// The testing email isn't empty, so replace stuff now.
			// Save the original to, cc, and bcc in additional headers.
			$required_headers['X-Testing-Original-To'] = $this->recipient;
			foreach ($this->headers as $name => $value) {
				switch (strtolower($name)) {
					case 'cc':
						$this->headers['X-Testing-Original-Cc'] = $value;
						$required_headers[$name] = '';
						break;
					case 'bcc':
						$this->headers['X-Testing-Original-Bcc'] = $value;
						$required_headers[$name] = '';
						break;
				}
			}
			$to = $config->testing_email['value'];
			$subject = '*Test* '.$this->subject;
		} else {
			$to = $this->recipient;
			$subject = $this->subject;
		}
		// Add from headers.
		$required_headers['From'] = $this->sender;
		$required_headers['Return-Path'] = $this->sender;
		$required_headers['Reply-To'] = $this->sender;
		$required_headers['X-Sender'] = $this->sender;
		$headers = $this->buildHeaders($required_headers);
		$message = $this->buildTextPart().$this->buildAttachmentPart()."--MIME_BOUNDRY--\n";

		// Now send the mail.
		return mail($to, $subject, $message, $headers, $config->additional_parameters['value']);
	}

	/**
	 * Format a date using the DateTime class.
	 *
	 * $type can be any of the following:
	 *
	 * - full_sort - Date and time, big endian and 24 hour format so it is sortable.
	 * - full_long - Date and time, long format.
	 * - full_med - Date and time, medium format.
	 * - full_short - Date and time, short format.
	 * - date_sort - Only the date, big endian so it is sortable.
	 * - date_long - Only the date, long format.
	 * - date_med - Only the date, medium format.
	 * - date_short - Only the date, short format.
	 * - time_sort - Only the time, 24 hour format so it is sortable.
	 * - time_long - Only the time, long format.
	 * - time_med - Only the time, medium format.
	 * - time_short - Only the time, short format.
	 * - custom - Use whatever is passed in $format.
	 *
	 * @param int $timestamp The timestamp to format.
	 * @param string $type The type of formatting to use.
	 * @param string $format The format to use if type is 'custom'.
	 * @param DateTimeZone|string|null $timezone The timezone to use for formatting. Defaults to date_default_timezone_get().
	 * @return string The formatted date.
	 */
	public static function formatDate($timestamp, $type = 'full_sort', $format = '', $timezone = null) {
		// Determine the format to use.
		switch ($type) {
			case 'date_sort':
				$format = 'Y-m-d';
				break;
			case 'date_long':
				$format = 'l, F j, Y';
				break;
			case 'date_med':
				$format = 'j M Y';
				break;
			case 'date_short':
				$format = 'n/d/Y';
				break;
			case 'time_sort':
				$format = 'H:i T';
				break;
			case 'time_long':
				$format = 'g:i:s A T';
				break;
			case 'time_med':
				$format = 'g:i:s A';
				break;
			case 'time_short':
				$format = 'g:i A';
				break;
			case 'full_sort':
				$format = 'Y-m-d H:i T';
				break;
			case 'full_long':
				$format = 'l, F j, Y g:i A T';
				break;
			case 'full_med':
				$format = 'j M Y g:i A T';
				break;
			case 'full_short':
				$format = 'n/d/Y g:i A T';
				break;
			case 'custom':
			default:
				break;
		}
		// Create a date object from the timestamp.
		try {
			$date = new \DateTime(gmdate('c', (int) $timestamp));
			if (isset($timezone)) {
				if ((object) $timezone !== $timezone) {
					$timezone = new \DateTimeZone($timezone);
				}
				$date->setTimezone($timezone);
			} else {
				$date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			}
		} catch (\Exception $e) {
			throw new \Exception("Error formatting date: $e");
		}
		return $date->format($format);
	}
}