<?php
/**
 * HTTP status code collection
 *
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core\Http;

class Status {

	/**
	 * Send a 304 status
	 *
	 * @access public
	 */
	public static function code_304() {
		header('HTTP/1.1 304 Not Modified', true);
		exit();
	}

	/**
	 * Throw a 403 error
	 *
	 * @access public
	 * @param string $message An additional message to add to the error
	 * @param bool
	 */
	public static function code_403($message = null, $exit = true) {
		if ($message !== null) {
			$message = ' (' . $message . ')';
		}

		header('HTTP/1.1 403 Forbidden', true);
		echo '403 Forbidden' . $message;

		if ($exit) {
			exit();
		}
	}

	/**
	 * Throw a 404 error
	 *
	 * @access public
	 * @param string $message An additional message to add to the error
	 */
	public static function code_404($message = null, $exit = true) {
		if ($message !== null) {
			$message = ' (' . $message . ')';
		}

		header('HTTP/1.1 404 Not Found' . $message, true);
		echo '404 Not Found' . $message;

		if ($exit) {
			exit();
		}
	}

	/**
	 * Throw a 418 error
	 *
	 * @access public
	 */
	public static function code_418($exit = true) {
		header('HTTP/1.1 418 I\'m a teapot' . $message, true);
		echo '418 I\'m a teapot';

		if ($exit) {
			exit();
		}
	}
}
