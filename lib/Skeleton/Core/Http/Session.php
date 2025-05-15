<?php

declare(strict_types=1);

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
	private static ?\Skeleton\Core\Http\Session\Sticky $sticky = null;

	/**
	 * Start the Session
	 *
	 * @access public
	 * @param $properties mixed[]
	 */
	public static function start(array &$properties = []): bool {
		$application = \Skeleton\Core\Application::get();
		$application->call_event('security', 'session_cookie');

		if (session_status() === PHP_SESSION_NONE) {
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
	public static function redirect(string $url, bool $rewrite = true): void {
		if ($rewrite === false) {
			header('Location: '.$url);
			echo 'Redirecting to : '.$url;
			exit;
		}

		$application = \Skeleton\Core\Application::get();
		if ($application->event_exists('rewrite', 'reverse_uri')) {
			$url = $application->call_event('rewrite', 'reverse_uri', [ $url ]);
		} else {
			$url = \Skeleton\Core\Util::rewrite_reverse($url);
		}

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
	public static function destroy(): void {
		session_destroy();
	}

	/**
	 * Set a sticky session variable
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set_sticky(string $key, mixed $value): void {
		if (self::$sticky === null) {
			self::$sticky = new \Skeleton\Core\Http\Session\Sticky();
		}

		self::$sticky->$key = $value;
	}
}
