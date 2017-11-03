<?php
namespace uMailPHP;

/**
 * Autoload uMailPHP classes.
 *
 * @package uMailPHP
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

spl_autoload_register(function ($class) {
  $prefix = 'uMailPHP\\';
  $base_dir = __DIR__.'/';
  // Does the class use the namespace prefix?
  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    // No, move to the next registered autoloader.
    return;
  }
  // Get the relative class name.
  $relative_class = substr($class, $len);

  // Replace the namespace prefix with the base directory, replace namespace
  // separators with directory separators in the relative class name, append
  // with .php.
  $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

  // If the file exists, require it.
  if (file_exists($file)) {
    require $file;
  }
});
