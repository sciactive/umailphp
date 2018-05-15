<?php namespace uMailPHP;

/**
 * Definition interface.
 *
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://umailphp.org/
 */
interface DefinitionInterface {
  /**
   * Retrieve a macro, by name.
   *
   * @param string $name The name of the macro to retrieve.
   */
  public static function getMacro($name);

  /**
   * Get the subject of the email.
   *
   * @return string The subject in UTF-8 encoding.
   */
  public static function getSubject();

  /**
   * Get the HTML content of the email. Return null to not include HTML content.
   *
   * @return string|null The HTML content in UTF-8 encoding.
   */
  public static function getHTML();

  /*
   * Get the text content of the email. Return null to not include text content.
   *
   * @return string|null The text content in UTF-8 encoding.
   *
  public static function getText();*/
}
