<?php
/**
 * Session class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Core\Http;

class Session {

	/**
	 * Sticky session variables
	 *
	 * @access private
	 * @var array $sticky
	 */
	private static $sticky = null;

	/**
	 * Start the Session
	 *
	 * @access public
	 */
	public static function start(&$properties = []) {
		$application = \Skeleton\Core\Application::get();
		$application->call_event('security', 'session_cookie');
		if (session_status() == PHP_SESSION_NONE) {
			session_name($application->config->session_name);
		}

		if (isset($_COOKIE[$application->config->session_name])) {
			$properties['resumed'] = true;
		} else {
			$properties['resumed'] = false;
		}

		return @session_start();
	}

	/**
	 * Redirect to
	 *
	 * @access public
	 * @param string $url
	 * @param bool $rewrite
	 */
	public static function redirect($url, $rewrite = true) {
		if ($rewrite) {
			$url = \Skeleton\Core\Util::rewrite_reverse($url);
		}
/*
		// Call teardown application event
		$application = \Skeleton\Core\Application::get();
		$application->call_event('application', 'teardown', []);
*/
		// Redirect
		header('Location: '.$url);
		echo 'Redirecting to : '.$url;
		exit;
	}

	/**
	 * Destroy
	 *
	 * @access public
	 */
	public static function destroy() {
		session_destroy();
	}

	/**
	 * Set a sticky session variable
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set_sticky($key, $value) {
		if (self::$sticky === null) {
			self::$sticky = new \Skeleton\Core\Http\Session\Sticky();
		}

		self::$sticky->$key = $value;
	}
}
