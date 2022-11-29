<?php
/**
 * Sticky session store
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Http\Session;

use Skeleton\Core\Config;

class Sticky {

	/**
	 * Session object
	 *
	 * @access private
	 * @var Web_Session $session
	 */
	private static $sticky_session = null;

	/**
	 * Module
	 *
	 * @var string $module
	 * @access private
	 */
	public $module = null;

	/**
	 * Contructor
	 *
	 * @access private
	 * @param string $username
	 * @param string $password
	 */
	public function __construct() {
	}

	/**
	 * Set
	 *
	 * @access public
	 * @param string $key
	 * @param string $value
	 */
	public function __set($key, $value) {
		$application = \Skeleton\Core\Application::get();
		if (!isset($_SESSION[$application->config->sticky_session_name])) {
			$_SESSION[$application->config->sticky_session_name] = [];
		}
		$_SESSION[$application->config->sticky_session_name][$key] = ['counter' => 0, 'data' => $value];
	}

	/**
	 * Get
	 *
	 * @access public
	 * @param string $key
	 * @param bool $remove_after_get
	 */
	public function __get($key) {
		$application = \Skeleton\Core\Application::get();
		if (!isset($_SESSION[$application->config->sticky_session_name][$key])) {
			throw new Exception('Key not found');
		}
		return $_SESSION[$application->config->sticky_session_name][$key]['data'];
	}

	/**
	 * Isset
	 *
	 * @access public
	 * @param string $key
	 */
	public function __isset($key) {
		$application = \Skeleton\Core\Application::get();
		if (!isset($_SESSION[$application->config->sticky_session_name][$key])) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Unset
	 *
	 * @access public
	 * @param string $key
	 */
	public function __unset($key) {
		$application = \Skeleton\Core\Application::get();
		unset($_SESSION[$application->config->sticky_session_name][$key]);
	}

	/**
	 * Get as array
	 *
	 * @access public
	 * @return array $variables
	 */
	public function get_as_array() {
		$application = \Skeleton\Core\Application::get();
		$variables = [];
		if (!isset($_SESSION[$application->config->sticky_session_name])) {
			return [];
		}
		foreach ($_SESSION[$application->config->sticky_session_name] as $key => $data) {
			$variables[$key] = $data['data'];
		}
		return $variables;
	}

	/**
	 * Get a Session object
	 *
	 * @access public
	 * @return Session
	 */
	public static function get() {
		if (self::$sticky_session === null) {
			self::$sticky_session = new self();
		}
		return self::$sticky_session;
	}

	/**
	 * Sticky clear
	 *
	 * @access public
	 * @param string $module
	 */
	public static function cleanup() {
		$application = \Skeleton\Core\Application::get();
		if (!isset($_SESSION[$application->config->sticky_session_name])) {
			return;
		}

		foreach ($_SESSION[$application->config->sticky_session_name] as $key => $variables) {
			if (isset($variables['counter']) and $variables['counter'] < 1) {
				$_SESSION[$application->config->sticky_session_name][$key]['counter']++;
				continue;
			}
			unset($_SESSION[$application->config->sticky_session_name][$key]);
		}
	}
}
