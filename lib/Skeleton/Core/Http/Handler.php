<?php
/**
 * HTTP request Handler
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Http;

use Skeleton\Core\Application;

class Handler {
	/**
	 * Handle the request and send it to the correct module
	 *
	 * @access public
	 */
	public static function run() {
		/**
		 * Record the start time in microseconds
		 */
		$start = microtime(true);
		mb_internal_encoding('utf-8');

		/**
		 * Hide PHP powered by
		 */
		header('X-Powered-By: Me');

		/**
		 * Parse the requested URL
		 */
		$components = parse_url($_SERVER['REQUEST_URI']);

		if (isset($components['query'])) {
			$query_string = $components['query'];
		} else {
			$query_string = '';
		}

		if (isset($components['path']) and $components['path'] !== '/') {
			$request_uri_parts = explode('/', $components['path']);
			array_shift($request_uri_parts);
		} else {
			$request_uri_parts = [];
		}

		$request_uri = '/' . implode('/', $request_uri_parts) . '/';

		 // Find out what the hostname is, if none was found, bail out
		if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			$elements = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
			$hostname = trim(end($elements));
		} elseif (isset($_SERVER['HTTP_HOST'])) {
			$hostname = $_SERVER['HTTP_HOST'];
		} elseif (isset($_SERVER['SERVER_NAME'])) {
			$hostname = $_SERVER['SERVER_NAME'];
		} elseif (isset($_SERVER['SERVER_ADDR'])) {
			$hostname = $_SERVER['SERVER_ADDR'];
		} else {
			throw new \Exception('Not a web request');
		}

		// Remove port number from host
		$hostname = preg_replace('/:\d+$/', '', $hostname);

		/**
		 * Define the application
		 */
		try {
			$application = Application::detect($hostname, $request_uri);
		} catch (\Skeleton\Core\Exception_Unknown_Application $e) {
			HTTP\Status::code_404('application');
		}
		\Skeleton\Core\Application::set($application);
		$application->run();
	}
}
