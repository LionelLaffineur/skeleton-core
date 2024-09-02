<?php

declare(strict_types=1);

/**
 * Sticky session store
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Http\Session;

class Sticky {
	/**
	 * Module
	 *
	 * @access private
	 */
	public ?string $module = null;
	/**
	 * Session object
	 *
	 * @access private
	 */
	private static ?self $sticky_session = null;

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
	 */
	public function __set(string $key, mixed $value): void {
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
	 */
	public function __get(string $key): mixed {
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
	 */
	public function __isset(string $key): bool {
		$application = \Skeleton\Core\Application::get();

		if (!isset($_SESSION[$application->config->sticky_session_name][$key])) {
			return false;
		}

		return true;
	}

	/**
	 * Unset
	 *
	 * @access public
	 */
	public function __unset(string $key): void {
		$application = \Skeleton\Core\Application::get();
		unset($_SESSION[$application->config->sticky_session_name][$key]);
	}

	/**
	 * Get as array
	 *
	 * @access public
	 * @return array<string> $variables
	 */
	public function get_as_array(): array {
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
	 */
	public static function get(): self {
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
	public static function cleanup(): void {
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
