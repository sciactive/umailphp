<?php
namespace uMailPHP;

/**
 * Mail class.
 *
 * Creates and sends emails.
 *
 * This class supports attachments and custom headers.
 *
 * This class is based on a class by Alejandro Gervasio
 * http://www.devshed.com/cp/bio/Alejandro-Gervasio/
 *
 * @package uMailPHP
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */
class Mail {
  const VERSION = '2.0.0';
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
   * @param string The name of the mail definition class.
   * @param mixed $recipient The recipient's email address, or a recipient object. If left null, the rendition must have a recipient.
   * @param array $macros An associative array of macros. These override macros from the definition.
   * @param string|null $sender The sender's email address. If left null, the rendition or default sender will be used.
   * @param \uMailPHP\Entities\Rendition An optional rendition. If left null, the latest ready rendition will be used. If false, no rendition will be used.
   * @param \uMailPHP\Entities\Template An optional template. If left null, the latest ready template will be used. If false, the default template will be used.
   */
  public function __construct($definition, $recipient = null, $macros = [], $sender = null, $rendition = null, $template = null) {
    if (!class_exists($definition) || !is_subclass_of($definition, '\uMailPHP\Definition')) {
      throw new \InvalidArgumentException('Mail definition is required.');
    }

    $config = \SciActive\RequirePHP::_('uMailPHPConfig');

    // Format recipient.
    if ($recipient && is_string($recipient)) {
      $recipient = (object) ['email' => $recipient];
    }

    // Find any renditions.
    if ($rendition === null) {
      $renditions = (array) \Nymph\Nymph::getEntities(
          ['class' => '\uMailPHP\Entities\Rendition', 'reverse' => true],
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
      if ($definition::$expectsRecipient) {
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
        if (!$config->master_address) {
          throw new \UnexpectedValueException('This email needs a recipient and no master address is set.');
        }
        $recipient = (object) ['email' => $config->master_address];
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
          ['class' => '\uMailPHP\Entities\Template', 'reverse' => true],
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
        $cur_field = str_replace('#site_link#', htmlspecialchars($config->site_link), $cur_field);
      }
      // Recipient
      if (strpos($cur_field, '#to_username#') !== false) {
        $cur_field = str_replace('#to_username#', htmlspecialchars(isset($recipient->username) ? $recipient->username : (isset($recipient->groupname) ? $recipient->groupname : '')), $cur_field);
      }
      if (strpos($cur_field, '#to_name#') !== false) {
        $cur_field = str_replace('#to_name#', htmlspecialchars(isset($recipient->name) ? $recipient->name : ''), $cur_field);
      }
      if (strpos($cur_field, '#to_first_name#') !== false) {
        $cur_field = str_replace('#to_first_name#', htmlspecialchars(isset($recipient->nameFirst) ? $recipient->nameFirst : ''), $cur_field);
      }
      if (strpos($cur_field, '#to_last_name#') !== false) {
        $cur_field = str_replace('#to_last_name#', htmlspecialchars(isset($recipient->nameLast) ? $recipient->nameLast : ''), $cur_field);
      }
      if (strpos($cur_field, '#to_email#') !== false) {
        $cur_field = str_replace('#to_email#', htmlspecialchars(isset($recipient->email) ? $recipient->email : ''), $cur_field);
      }
      // Current User with Tilmeld.
      if (class_exists('\Tilmeld\User') && \Tilmeld\User::current()) {
        if (strpos($cur_field, '#username#') !== false) {
          $cur_field = str_replace('#username#', htmlspecialchars(\Tilmeld\User::current()->username), $cur_field);
        }
        if (strpos($cur_field, '#name#') !== false) {
          $cur_field = str_replace('#name#', htmlspecialchars(\Tilmeld\User::current()->name), $cur_field);
        }
        if (strpos($cur_field, '#first_name#') !== false) {
          $cur_field = str_replace('#first_name#', htmlspecialchars(\Tilmeld\User::current()->nameFirst), $cur_field);
        }
        if (strpos($cur_field, '#last_name#') !== false) {
          $cur_field = str_replace('#last_name#', htmlspecialchars(\Tilmeld\User::current()->nameLast), $cur_field);
        }
        if (strpos($cur_field, '#email#') !== false) {
          $cur_field = str_replace('#email#', htmlspecialchars(\Tilmeld\User::current()->email), $cur_field);
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
        $cur_field = str_replace('#site_name#', htmlspecialchars($config->site_name), $cur_field);
      }
      // Argument Macros
      foreach ($macros as $cur_name => $cur_value) {
        $cur_field = str_replace("#$cur_name#", $cur_value, $cur_field);
      }
      // Definition Macros
      foreach ($definition::$macros as $cur_name => $cur_desc) {
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
      $sender = $config->from_address;
    }
    $destination = isset($recipient->name) ? "\"".str_replace('"', '', $recipient->name)."\" <{$recipient->email}>" : (isset($recipient->email) ? $recipient->email : '');


    // Validate incoming parameters.
    if (!preg_match('/^.+@.+$/', $sender)) {
      throw new \InvalidArgumentException('Invalid value for email sender.');
    }
    if (!preg_match('/^.+@.+$/', $destination)) {
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
    $this->headers['User-Agent'] = 'uMailPHP '.Mail::VERSION;
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
    return implode("\r\n", $headers)."\nThis is a multi-part message in MIME format.\n";
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

    $config = \SciActive\RequirePHP::_('uMailPHPConfig');

    // Headers that must be in the sent message.
    $required_headers = [];

    // Are we in testing mode?
    if ($config->testing_mode) {
      // If the testing email is empty, just return true.
      if (empty($config->testing_email)) {
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
      $to = $config->testing_email;
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
    return mail($to, $subject, $message, $headers, $config->additional_parameters);
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

  /**
   * Format a date range into a human understandable phrase.
   *
   * $format is built using macros, which are substrings replaced by the
   * corresponding number of units. There are singular macros, such as #year#,
   * which are used if the number of that unit is 1. For example, if the range
   * is 1 year and both #year# and #years# are present, #year# will be used
   * and #years# will be ignored. This allows you to use a different
   * description for each one. You accomplish this by surrounding the macro
   * and its description in curly brackets. If the unit is 0, everything in
   * that curly bracket will be removed. This allows you to place both #year#
   * and #years# and always end up with the right one.
   *
   * Since the units in curly brackets that equal 0 are removed, you can
   * include as many as you want and only the relevant ones will be used. If
   * you choose not to include one, such as year, then the next available one
   * will include the time that would have been placed in it. For example, if
   * the time range is 2 years, but you only include months, then months will
   * be set to 24.
   *
   * After formatting, any leading and trailing whitespace is trimmed before
   * the result is returned.
   *
   * $format can contain the following macros:
   *
   * - #years# - The number of years.
   * - #year# - The number 1 if applicable.
   * - #months# - The number of months.
   * - #month# - The number 1 if applicable.
   * - #weeks# - The number of weeks.
   * - #week# - The number 1 if applicable.
   * - #days# - The number of days.
   * - #day# - The number 1 if applicable.
   * - #hours# - The number of hours.
   * - #hour# - The number 1 if applicable.
   * - #minutes# - The number of minutes.
   * - #minute# - The number 1 if applicable.
   * - #seconds# - The number of seconds.
   * - #second# - The number 1 if applicable.
   *
   * If $format is left null, it defaults to the following:
   *
   * "{#years# years}{#year# year} {#months# months}{#month# month} {#days# days}{#day# day} {#hours# hours}{#hour# hour} {#minutes# minutes}{#minute# minute} {#seconds# seconds}{#second# second}"
   *
   * Here are some examples of formats and what would be outputted given a
   * time range of 2 years 5 months 1 day and 4 hours. (These values were
   * calculated on Fri Oct 14 2011 in San Diego, which has DST. 2012 is a leap
   * year.)
   *
   * - "#years# years {#days# days}{#day# day}" - 2 years 152 days
   * - "{#months# months}{#month# month} {#days# days}{#day# day}" - 29 months 1 day
   * - "{#weeks# weeks}{#week# week} {#days# days}{#day# day}" - 126 weeks 1 day
   * - "#days# days #hours# hours #minutes# minutes" - 883 days 4 hours 0 minutes
   * - "{#minutes#min} {#seconds#sec}" - 1271760min
   * - "#seconds#" - 76305600
   *
   * @param int $startTimestamp The timestamp of the beginning of the date range.
   * @param int $endTimestamp The timestamp of the end of the date range.
   * @param string $format The format to use. See the function description for details on the format.
   * @param DateTimeZone|string|null $timezone The timezone to use for formatting. Defaults to date_default_timezone_get().
   * @return string The formatted date range.
   */
  public static function formatDateRange($startTimestamp, $endTimestamp, $format = null, $timezone = null) {
    if (!$format) {
      $format = '{#years# years}{#year# year} {#months# months}{#month# month} {#days# days}{#day# day} {#hours# hours}{#hour# hour} {#minutes# minutes}{#minute# minute} {#seconds# seconds}{#second# second}';
    }

    // If it's a negative range, flip the values.
    $negative = ($endTimestamp < $startTimestamp) ? '-' : '';
    if ($negative == '-') {
      $tmp = $endTimestamp;
      $endTimestamp = $startTimestamp;
      $startTimestamp = $tmp;
    }
    // Create a date object from the timestamp.
    try {
      $start_date = new DateTime(gmdate('c', (int) $startTimestamp));
      $end_date = new DateTime(gmdate('c', (int) $endTimestamp));
      if (isset($timezone)) {
        if ((object) $timezone !== $timezone) {
          $timezone = new DateTimeZone($timezone);
        }
        $start_date->setTimezone($timezone);
        $end_date->setTimezone($timezone);
      } else {
        $start_date->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $end_date->setTimezone(new DateTimeZone(date_default_timezone_get()));
      }
    } catch (Exception $e) {
      return '';
    }

    if (strpos($format, '#year#') !== false || strpos($format, '#years#') !== false) {
      // Calculate number of years between the two dates.
      $years = (int) $end_date->format('Y') - (int) $start_date->format('Y');
      // Be sure we didn't go too far.
      $test_date = clone $start_date;
      $test_date->modify('+'.$years.' years');
      $test_timestamp = (int) $test_date->format('U');
      if ($test_timestamp > $endTimestamp) {
        $years--;
      }
      if (strpos($format, '#year#') !== false && $years == 1) {
        $format = preg_replace('/\{?([^{}]*)#year#([^{}]*)\}?/s', '${1}'.$negative.$years.'${2}', $format);
        $format = preg_replace('/\{([^{}]*)#years#([^{}]*)\}/s', '', $format);
      } elseif (strpos($format, '#years#') !== false) {
        if ($years <> 0) {
          $format = preg_replace('/\{?([^{}]*)#years#([^{}]*)\}?/s', '${1}'.$negative.$years.'${2}', $format);
        } else {
          $format = preg_replace(array('/\{([^{}]*)#years#([^{}]*)\}/s', '/#years#/'), array('', '0'), $format);
        }
        $format = preg_replace('/\{([^{}]*)#year#([^{}]*)\}/s', '', $format);
      }
      $start_date->modify('+'.$years.' years');
      $startTimestamp = (int) $start_date->format('U');
    }

    if (strpos($format, '#month#') !== false || strpos($format, '#months#') !== false) {
      // Calculate number of months.
      $years = (int) $end_date->format('Y') - (int) $start_date->format('Y');
      $months = ($years * 12) + ((int) $end_date->format('n') - (int) $start_date->format('n'));
      // Be sure we didn't go too far.
      $test_date = clone $start_date;
      $test_date->modify('+'.$months.' months');
      $test_timestamp = (int) $test_date->format('U');
      if ($test_timestamp > $endTimestamp) {
        $months--;
      }
      if (strpos($format, '#month#') !== false && $months == 1) {
        $format = preg_replace('/\{?([^{}]*)#month#([^{}]*)\}?/s', '${1}'.$negative.$months.'${2}', $format);
        $format = preg_replace('/\{([^{}]*)#months#([^{}]*)\}/s', '', $format);
      } elseif (strpos($format, '#months#') !== false) {
        if ($months <> 0) {
          $format = preg_replace('/\{?([^{}]*)#months#([^{}]*)\}?/s', '${1}'.$negative.$months.'${2}', $format);
        } else {
          $format = preg_replace(array('/\{([^{}]*)#months#([^{}]*)\}/s', '/#months#/'), array('', '0'), $format);
        }
        $format = preg_replace('/\{([^{}]*)#month#([^{}]*)\}/s', '', $format);
      }
      $start_date->modify('+'.$months.' months');
      $startTimestamp = (int) $start_date->format('U');
    }

    if (strpos($format, '#week#') !== false || strpos($format, '#weeks#') !== false) {
      // Calculate number of weeks.
      $weeks = floor(($endTimestamp - $startTimestamp) / 604800);
      // Be sure we didn't go too far.
      $test_date = clone $start_date;
      $test_date->modify('+'.$weeks.' weeks');
      $test_timestamp = (int) $test_date->format('U');
      if ($test_timestamp > $endTimestamp) {
        $weeks--;
      }
      if (strpos($format, '#week#') !== false && $weeks == 1) {
        $format = preg_replace('/\{?([^{}]*)#week#([^{}]*)\}?/s', '${1}'.$negative.$weeks.'${2}', $format);
        $format = preg_replace('/\{([^{}]*)#weeks#([^{}]*)\}/s', '', $format);
      } elseif (strpos($format, '#weeks#') !== false) {
        if ($weeks <> 0) {
          $format = preg_replace('/\{?([^{}]*)#weeks#([^{}]*)\}?/s', '${1}'.$negative.$weeks.'${2}', $format);
        } else {
          $format = preg_replace(array('/\{([^{}]*)#weeks#([^{}]*)\}/s', '/#weeks#/'), array('', '0'), $format);
        }
        $format = preg_replace('/\{([^{}]*)#week#([^{}]*)\}/s', '', $format);
      }
      $start_date->modify('+'.$weeks.' weeks');
      $startTimestamp = (int) $start_date->format('U');
    }

    if (strpos($format, '#day#') !== false || strpos($format, '#days#') !== false) {
      // Calculate number of days.
      $days = floor(($endTimestamp - $startTimestamp) / 86400);
      // Be sure we didn't go too far.
      $test_date = clone $start_date;
      $test_date->modify('+'.$days.' days');
      $test_timestamp = (int) $test_date->format('U');
      if ($test_timestamp > $endTimestamp) {
        $days--;
      }
      if (strpos($format, '#day#') !== false && $days == 1) {
        $format = preg_replace('/\{?([^{}]*)#day#([^{}]*)\}?/s', '${1}'.$negative.$days.'${2}', $format);
        $format = preg_replace('/\{([^{}]*)#days#([^{}]*)\}/s', '', $format);
      } elseif (strpos($format, '#days#') !== false) {
        if ($days <> 0) {
          $format = preg_replace('/\{?([^{}]*)#days#([^{}]*)\}?/s', '${1}'.$negative.$days.'${2}', $format);
        } else {
          $format = preg_replace(array('/\{([^{}]*)#days#([^{}]*)\}/s', '/#days#/'), array('', '0'), $format);
        }
        $format = preg_replace('/\{([^{}]*)#day#([^{}]*)\}/s', '', $format);
      }
      $start_date->modify('+'.$days.' days');
      $startTimestamp = (int) $start_date->format('U');
    }

    if (strpos($format, '#hour#') !== false || strpos($format, '#hours#') !== false) {
      // Calculate number of hours.
      $hours = floor(($endTimestamp - $startTimestamp) / 3600);
      // Hours are constant, so we didn't go too far.
      if (strpos($format, '#hour#') !== false && $hours == 1) {
        $format = preg_replace('/\{?([^{}]*)#hour#([^{}]*)\}?/s', '${1}'.$negative.$hours.'${2}', $format);
        $format = preg_replace('/\{([^{}]*)#hours#([^{}]*)\}/s', '', $format);
      } elseif (strpos($format, '#hours#') !== false) {
        if ($hours <> 0) {
          $format = preg_replace('/\{?([^{}]*)#hours#([^{}]*)\}?/s', '${1}'.$negative.$hours.'${2}', $format);
        } else {
          $format = preg_replace(array('/\{([^{}]*)#hours#([^{}]*)\}/s', '/#hours#/'), array('', '0'), $format);
        }
        $format = preg_replace('/\{([^{}]*)#hour#([^{}]*)\}/s', '', $format);
      }
      // Because hours are affected by DST, we need to add to the timestamp, and not the date object.
      $startTimestamp += $hours * 3600;
      // Create a date object from the timestamp.
      $start_date = new DateTime(gmdate('c', (int) $startTimestamp));
      if (isset($timezone)) {
        if ((object) $timezone !== $timezone) {
          $timezone = new DateTimeZone($timezone);
        }
        $start_date->setTimezone($timezone);
      } else {
        $start_date->setTimezone(new DateTimeZone(date_default_timezone_get()));
      }
    }

    if (strpos($format, '#minute#') !== false || strpos($format, '#minutes#') !== false) {
      // Calculate number of minutes.
      $minutes = floor(($endTimestamp - $startTimestamp) / 60);
      // Minutes are constant, so we didn't go too far.
      if (strpos($format, '#minute#') !== false && $minutes == 1) {
        $format = preg_replace('/\{?([^{}]*)#minute#([^{}]*)\}?/s', '${1}'.$negative.$minutes.'${2}', $format);
        $format = preg_replace('/\{([^{}]*)#minutes#([^{}]*)\}/s', '', $format);
      } elseif (strpos($format, '#minutes#') !== false) {
        if ($minutes <> 0) {
          $format = preg_replace('/\{?([^{}]*)#minutes#([^{}]*)\}?/s', '${1}'.$negative.$minutes.'${2}', $format);
        } else {
          $format = preg_replace(array('/\{([^{}]*)#minutes#([^{}]*)\}/s', '/#minutes#/'), array('', '0'), $format);
        }
        $format = preg_replace('/\{([^{}]*)#minute#([^{}]*)\}/s', '', $format);
      }
      // Because minutes are affected by DST, we need to add to the timestamp, and not the date object.
      $startTimestamp += $minutes * 60;
      // Create a date object from the timestamp.
      $start_date = new DateTime(gmdate('c', (int) $startTimestamp));
      if (isset($timezone)) {
        if ((object) $timezone !== $timezone) {
          $timezone = new DateTimeZone($timezone);
        }
        $start_date->setTimezone($timezone);
      } else {
        $start_date->setTimezone(new DateTimeZone(date_default_timezone_get()));
      }
    }

    if (strpos($format, '#second#') !== false || strpos($format, '#seconds#') !== false) {
      // Calculate number of seconds.
      $seconds = (int) $endTimestamp - (int) $startTimestamp;
      if (strpos($format, '#second#') !== false && $seconds == 1) {
        $format = preg_replace('/\{?([^{}]*)#second#([^{}]*)\}?/s', '${1}'.$negative.$seconds.'${2}', $format);
        $format = preg_replace('/\{([^{}]*)#seconds#([^{}]*)\}/s', '', $format);
      } elseif (strpos($format, '#seconds#') !== false) {
        if ($seconds <> 0) {
          $format = preg_replace('/\{?([^{}]*)#seconds#([^{}]*)\}?/s', '${1}'.$negative.$seconds.'${2}', $format);
        } else {
          $format = preg_replace(array('/\{([^{}]*)#seconds#([^{}]*)\}/s', '/#seconds#/'), array('', '0'), $format);
        }
        $format = preg_replace('/\{([^{}]*)#second#([^{}]*)\}/s', '', $format);
      }
    }

    return trim($format);
  }

  /**
   * Get a fuzzy time string.
   *
   * Converts a timestamp from the past into a human readable estimation of
   * the time that has passed.
   *
   * Ex: a few minutes ago
   *
   * Credit: http://www.byteinn.com/res/426/Fuzzy_Time_function/
   *
   * @param int $timestamp The timestamp to format.
   * @return string Fuzzy time string.
   */
  public static function formatFuzzyTime($timestamp) {
    $now = time();
    $one_minute = 60;
    $one_hour = 3600;
    $one_day = 86400;
    $one_week = $one_day * 7;
    $one_month = $one_day * 30.42;
    $one_year = $one_day * 365;

    // sod = start of day :)
    $sod = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
    $sod_now = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));

    // used to convert numbers to strings
    $convert = array(
      1 => 'one',
      2 => 'two',
      3 => 'three',
      4 => 'four',
      5 => 'five',
      6 => 'six',
      7 => 'seven',
      8 => 'eight',
      9 => 'nine',
      10 => 'ten',
      11 => 'eleven',
      12 => 'twelve',
      13 => 'thirteen',
      14 => 'fourteen',
      15 => 'fifteen',
      16 => 'sixteen',
      17 => 'seventeen',
      18 => 'eighteen',
      19 => 'nineteen',
      20 => 'twenty',
    );

    // today (or yesterday, but less than 1 hour ago)
    if ($sod_now == $sod || $timestamp > $now - $one_hour) {
      if ($timestamp > $now - $one_minute) {
        return 'just now';
      } elseif ($timestamp > $now - ($one_minute * 3)) {
        return 'just a moment ago';
      } elseif ($timestamp > $now - ($one_minute * 7)) {
        return 'a few minutes ago';
      } elseif ($timestamp > $now - $one_hour) {
        return 'less than an hour ago';
      }
      return 'today at ' . date('g:ia', $timestamp);
    }

    // yesterday
    if (($sod_now - $sod) <= $one_day) {
      if (date('i', $timestamp) > ($one_minute + 30)) {
        $timestamp += $one_hour / 2;
      }
      return 'yesterday around ' . date('ga', $timestamp);
    }

    // within the last 5 days
    if (($sod_now - $sod) <= ($one_day * 5)) {
      $str = date('l', $timestamp);
      $hour = date('G', $timestamp);
      if ($hour < 12) {
        $str .= ' morning';
      } elseif ($hour < 17) {
        $str .= ' afternoon';
      } elseif ($hour < 20) {
        $str .= ' evening';
      } else {
        $str .= ' night';
      }
      return $str;
    }

    // number of weeks (between 1 and 3)...
    if (($sod_now - $sod) < ($one_week * 3.5)) {
      if (($sod_now - $sod) < ($one_week * 1.5)) {
        return 'about a week ago';
      } elseif (($sod_now - $sod) < ($one_day * 2.5)) {
        return 'about two weeks ago';
      } else {
        return 'about three weeks ago';
      }
    }

    // number of months (between 1 and 11)...
    if (($sod_now - $sod) < ($one_month * 11.5)) {
      for ($i = ($one_week * 3.5), $m = 0; $i < $one_year; $i += $one_month, $m++) {
        if (($sod_now - $sod) <= $i) {
          return 'about ' . $convert[$m] . ' month' . (($m > 1) ? 's' : '') . ' ago';
        }
      }
    }

    // number of years...
    for ($i = ($one_month * 11.5), $y = 0; $i < ($one_year * 21); $i += $one_year, $y++) {
      if (($sod_now - $sod) <= $i) {
        return 'about ' . $convert[$y] . ' year' . (($y > 1) ? 's' : '') . ' ago';
      }
    }

    // more than twenty years...
    return 'more than twenty years ago';
  }

  /**
   * Format a phone number.
   *
   * Uses US phone number format. E.g. "(800) 555-1234 x56".
   *
   * @param string $number The phone number to format.
   * @return string The formatted phone number.
   */
  public static function formatPhone($number) {
    if (!isset($number)) {
      return '';
    }
    $return = preg_replace('/\D*0?1?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d*)\D*/', '($1$2$3) $4$5$6-$7$8$9$10 x$11', (string) $number);
    return preg_replace('/\D*$/', '', $return);
  }
}
