<?php namespace µMailPHP;
/**
 * UnsubscribeStore class.
 *
 * @package uMailPHP
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

/**
 * Manage unsubscribes.
 *
 * @package uMailPHP
 * @todo Use Nymph to manage unsubscribes. (SQLite is not scalable.)
 */
class UnsubscribeStore {
	/**
	 * A SQLite database connection for the unsubscribed DB.
	 * @var mixed
	 * @access private
	 */
	private $db;

	public function __construct() {
		$config = \SciActive\RequirePHP::_('µMailPHPConfig');

		// Get the DB.
		$filename = $config->unsubscribe_db['value'];
		if (!$filename) {
			pines_log('Unsubscribed user database has not been set up yet. Please edit the config for com_mailer.', 'error');
			return 0;
		}
		if (file_exists($filename) && !is_readable($filename)) {
			pines_log('Unsubscribed user database file cannot be read!', 'error');
			return false;
		}

		if (class_exists('SQLite3')) {
			$this->db = new SQLite3($filename);
		} elseif (function_exists('sqlite_open')) {
			$this->db = sqlite_open($filename);
		} else {
			throw new \UnexpectedValueException('SQLite is not available! Please install the SQLite PHP extension.');
		}
	}

	/**
	 * Add an email address to the unsubscribed DB.
	 *
	 * @param string $email The email address to add.
	 * @return bool True on success, false on failure, 0 if the database hasn't been set up.
	 */
	public function unsubscribeAdd($email) {
		// Validate and lowercase email address.
		if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email)) {
			return false;
		}
		$email = strtolower($email);

		// Build the queries.
		$create_table = 'CREATE TABLE IF NOT EXISTS "unsubscribed" ("email" text NOT NULL UNIQUE);';
		$insert_email = 'INSERT OR REPLACE INTO "unsubscribed" VALUES ("'.$email.'");';
		// Now run the SQL.
		if (class_exists('SQLite3')) {
			if (!$this->db->query($create_table)) {
				pines_error("SQL Create Table error: ".$this->db->lastErrorMsg());
				return false;
			}
			if (!$this->db->query($insert_email)) {
				pines_error("SQL Insert error: ".$this->db->lastErrorMsg());
				return false;
			}
			$id = $this->db->lastInsertRowID();
			if (!$id && $id !== 0) {
				pines_error("SQL Insert error: ".$this->db->lastErrorMsg());
				return false;
			}
		} else {
			if (!sqlite_query($this->db, $create_table, SQLITE_NUM, $error)) {
				pines_error("SQL Create Table error: $error");
				return false;
			}
			if (!sqlite_query($this->db, $insert_email, SQLITE_NUM, $error)) {
				pines_error("SQL Insert error: $error");
				return false;
			}
			$id = sqlite_last_insert_rowid($this->db);
			if (!$id && $id !== 0) {
				pines_error("SQL Insert error: $error");
				return false;
			}
		}
		return true;
	}

	/**
	 * Determine if an email address is unsubscribed.
	 *
	 * Returning false if the database hasn't been set up allows emails to be
	 * sent without an unsubscribe DB. Returning true on error ensures no emails
	 * are sent to unsubscribed users if the DB can't be queried.
	 *
	 * @param string $email The email address in question.
	 * @return bool True if the address is unsubscribed or on failure, false if it isn't or the database hasn't been set up.
	 */
	public function unsubscribeQuery($email) {
		// Validate and lowercase email address.
		if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email)) {
			return false;
		}
		$email = strtolower($email);

		// Build the queries.
		$select_email = 'SELECT * FROM "unsubscribed" WHERE email="'.$email.'";';
		// Now run the SQL.
		if (class_exists('SQLite3')) {
			if (($result = $this->db->query($select_email)) === false) {
				pines_error("SQL Query error: ".$this->db->lastErrorMsg());
				return true;
			}
			$row = $result->fetchArray();
		} else {
			if (($result = sqlite_query($this->db, $select_email, SQLITE_NUM, $error)) === false) {
				pines_error("SQL Query error: $error");
				return true;
			}
			$row = sqlite_fetch_array($result, SQLITE_NUM);
		}
		return ($email == $row[0]);
	}

	/**
	 * Remove an email address from the unsubscribed DB.
	 *
	 * @param string $email The email address to remove.
	 * @return bool True on success, false on failure.
	 */
	public function unsubscribeRemove($email) {
		// Validate and lowercase email address.
		if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email)) {
			return false;
		}
		$email = strtolower($email);

		// Build the queries.
		$delete_email = 'DELETE FROM "unsubscribed" WHERE email="'.$email.'";';
		// Now run the SQL.
		if (class_exists('SQLite3')) {
			if (!$this->db->query($delete_email)) {
				pines_error("SQL Delete error: ".$this->db->lastErrorMsg());
				return false;
			}
		} else {
			if (!sqlite_query($this->db, $delete_email, SQLITE_NUM, $error)) {
				pines_error("SQL Delete error: $error");
				return false;
			}
		}
		return true;
	}
}
