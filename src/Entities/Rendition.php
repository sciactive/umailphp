<?php namespace uMailPHP\Entities;

/**
 * Rendition class.
 *
 * @license https://www.apache.org/licenses/LICENSE-2.0
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://umailphp.org/
 */
class Rendition extends \Nymph\Entity {
  const ETYPE = 'umailphp_rendition';

  public function __construct($id = 0) {
    if (parent::__construct($id) !== null) {
      return;
    }
    // Defaults.
    $this->enabled = true;
    $this->acOther = 1;
  }

  /**
   * Save the rendition.
   * @return bool True on success, false on failure.
   */
  public function save() {
    if (!isset($this->name)) {
      return false;
    }
    if (
        class_exists('\Tilmeld\Tilmeld')
        && !\Tilmeld\Tilmeld::gatekeeper('umailphp/admin')
      ) {
      return false;
    }
    return parent::save();
  }
}
