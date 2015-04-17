<?php namespace µMailPHP;
/**
 * Rendition class.
 *
 * @package uMailPHP
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

/**
 * A rendition.
 *
 * @package uMailPHP
 */
class Rendition extends \Nymph\Entity {
	const ETYPE = 'umailphp_rendition';
	protected $clientClassName = 'Rendition';

	public function __construct($id = 0) {
		if (parent::__construct($id) !== null) {
			return;
		}
		// Defaults.
		$this->enabled = true;
		$this->ac_other = 1;
	}

	public function info($type) {
		switch ($type) {
			case 'name':
				return $this->name;
			case 'type':
				return 'rendition';
			case 'types':
				return 'renditions';
		}
		return null;
	}

	/**
	 * Save the rendition.
	 * @return bool True on success, false on failure.
	 */
	public function save() {
		if (!isset($this->name)) {
			return false;
		}
		return parent::save();
	}

	/**
	 * Print a form to edit the rendition.
	 * @return module The form's module.
	 */
	public function printForm() {
		$module = new module('com_mailer', 'rendition/form', 'content');
		$module->entity = $this;

		return $module;
	}

	/**
	 * Determine if this rendition is ready to use.
	 *
	 * This function will check the conditions of the rendition.
	 *
	 * @return bool True if the rendition is ready, false otherwise.
	 */
	public function ready() {
		if (!$this->enabled) {
			return false;
		}
		return true;
	}
}