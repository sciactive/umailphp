<?php namespace µMailPHP;
/**
 * Definition class.
 *
 * @package uMailPHP
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

abstract class Definition implements DefinitionInterface {
  /**
   * The name of the mail definition.
   *
   * @public string
   */
  public static $cname;
  /**
   * The description of the mail definition.
   *
   * @public string
   */
  public static $description;
  /**
   * Whether this email should have a place to be sent when instantiated.
   *
   * An example of an email where this is true would be a receipt being sent
   * to a customer. And an example where this would be false would be an email
   * notifying the sysadmin of an event. (Since an email like that would use
   * the rendition or master address.)
   *
   * @public bool
   */
  public static $expectsRecipient;
  /**
   * The macros available for this definition.
   *
   * The key is the name, the value is the description.
   *
   * @public array
   */
  public static $macros = [];
}