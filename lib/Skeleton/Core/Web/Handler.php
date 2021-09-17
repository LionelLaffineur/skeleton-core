<?php
/**
 * HTTP request Handler
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Web;

use Skeleton\Core\Application;
use Skeleton\Database\Database;

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

		/**
		 * Handle the media
		 */
		$is_media = false;
		if (isset($application->config->detect_media) === false ||$application->config->detect_media === true) {
			try {
				$is_media = Media::detect($application->request_relative_uri);
			} catch (\Skeleton\Core\Exception\Media\Not\Found $e) {
				HTTP\Status::code_404('media');
			}
		}

		if ($is_media === true) {
			exit;
		}

		/**
		 * Start the session
		 */
		$session_properties = [];
		Session::start($session_properties);

		/**
		 * Find the module to load
		 *
		 * FIXME: this nested try/catch is not the prettiest of things
		 */
		$module = null;
		try {
			// Attempt to find the module by matching defined routes
			$module = $application->route($request_uri);
		} catch (\Exception $e) {
			try {
				// Attempt to find a module by matching paths
				$module = Module::get($application->request_relative_uri);
			} catch (\Exception $e) {
				if ($application->event_exists('module', 'not_found')) {
					$application->call_event_if_exists('module', 'not_found');
				} else {
					HTTP\Status::code_404('module');
				}
			}
		}

		/**
		 * Set language
		 */
		// Set the language to something sensible if it isn't set yet
		if (class_exists('\Skeleton\I18n\Config') AND isset(\Skeleton\I18n\Config::$language_interface)) {
			$language_interface = \Skeleton\I18n\Config::$language_interface;
			if (!class_exists($language_interface)) {
				throw new \Exception('The language interface does not exists: ' . $language_interface);
			}

			if (!isset($_SESSION['language'])) {
				try {
					$language = $language_interface::detect();
					$_SESSION['language'] = $language;
				} catch (\Exception $e) {
					$language = $language_interface::get_by_name_short($application->config->default_language);
					$_SESSION['language'] = $language;
				}
			}

			if (isset($_GET['language'])) {
				try {
					$language = $language_interface::get_by_name_short($_GET['language']);
					$_SESSION['language'] = $language;
				} catch (\Exception $e) {
					$_SESSION['language'] = $language_interface::get_by_name_short($application->config->default_language);
				}
			}
			$application->language = $_SESSION['language'];
		}

		/**
		 * Validate CSRF
		 */
		$csrf = Security\Csrf::get();

		if ($session_properties['resumed'] === true && !$csrf->validate()) {
			if ($application->event_exists('security', 'csrf_validation_failed')) {
				$application->call_event_if_exists('security', 'csrf_validation_failed');
			} else {
				HTTP\Status::code_403('CSRF validation failed');
			}
		}

		/**
		 * Check for replays
		 */
		$replay = Security\Replay::get();
		if ($replay->check() == false) {
			$application->call_event('security', 'replay_detected');
		}

		if ($module !== null) {
			$module->accept_request();
		}
	}
}
