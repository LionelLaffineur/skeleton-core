<?php
/**
 * Automated CSRF handling
 *
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core\Web\Security;

class Csrf {
	/**
	 * Local Csrf instance
	 *
	 * @var self $csrf
	 * @access private
	 */
	private static $csrf = null;

	/**
	 * Session token name
	 *
	 * @var string $session_token_name
	 * @access private
	 */
	private $session_token_name = '__request-token';

	/**
	 * POST token name
	 *
	 * @var string $post_token_name
	 * @access private
	 */
	private $post_token_name = '__request-token';

	/**
	 * Header token name
	 *
	 * @var string $header_token_name
	 * @access private
	 */
	private $header_token_name = 'x-request-token';

	/**
	 * Session token
	 *
	 * @var string $session_token
	 * @access private
	 */
	private $session_token = null;

	/**
	 * Is CSRF enabled for the current request
	 *
	 * @var boolean $enabled
	 * @access private
	 */
	private $enabled = true;

	/**
	 * Constructor
	 *
	 * @access private
	 */
	private function __construct() {
		$application = \Skeleton\Core\Application::get();

		if (isset($application->config->csrf_session_token_name)) {
			$this->session_token_name = $application->config->csrf_session_token_name;
		}

		if (isset($application->config->csrf_header_token_name)) {
			$this->sheader_token_name = $application->config->csrf_header_token_name;
		}

		if (isset($application->config->csrf_post_token_name)) {
			$this->post_token_name = $application->config->csrf_post_token_name;
		}

		if (\Skeleton\Core\Config::$csrf_enabled && !(isset($application->config->csrf_enabled) && $application->config->csrf_enabled == false))  {
			$this->set_session_token();
		} else {
			$this->enabled = false;
		}
	}

	/**
	 * Create a static instance
	 *
	 * @return Csrf
	 * @access public
	 */
	public static function get(): self {
		if (!isset(self::$csrf)) {
			self::$csrf = new self();
		}

		return self::$csrf;
	}

	/**
	 * Generate a secure CSRF token or set the existing one if we have one
	 *
	 * @access public
	 */
	private function set_session_token() {
		if (!isset($_SESSION[$this->session_token_name])) {
			$application = \Skeleton\Core\Application::get();

			if ($application->event_exists('security', 'csrf_generate_session_token')) {
				$this->session_token = $application->call_event('security', 'csrf_generate_session_token');
			} else {
				$this->session_token = bin2hex(random_bytes(32));
			}

			$_SESSION[$this->session_token_name] = $this->session_token;
		} else {
			$this->session_token = $_SESSION[$this->session_token_name];
		}
	}

	/**
	 * Get the current CSRF session token name
	 *
	 * @access public
	 * @return ?string $csrf_session_token_name
	 */
	public function get_session_token_name() {
		return $this->session_token_name;
	}

	/**
	 * Get the current CSRF header token name
	 *
	 * @access public
	 * @return ?string $csrf_header_token_name
	 */
	public function get_header_token_name() {
		return $this->header_token_name;
	}

	/**
	 * Get the current CSRF post token name
	 *
	 * @access public
	 * @return ?string $csrf_post_token_name
	 */
	public function get_post_token_name() {
		return $this->post_token_name;
	}

	/**
	 * Get the current CSRF token
	 *
	 * @access public
	 * @return ?string $csrf_token
	 */
	public function get_session_token() {
		return $this->session_token;
	}

	/**
	 * Inject a CSRF token form element into rendered HTML
	 *
	 * @param string $html
	 * @access public
	 */
	public function inject(string $html): string {
		if ($this->enabled == false) {
			return $html;
		}

		$application = \Skeleton\Core\Application::get();

		if ($application->event_exists('security', 'csrf_inject')) {
			$html = $application->call_event('security', 'csrf_inject', [$html]);
		} else {
			$html = preg_replace_callback(
				'/<form\s.*>/siU',
				function ($matches) {
					return sprintf("%s\n<input name=\"%s\" type=\"hidden\" value=\"%s\" />\n", $matches[0], $this->post_token_name, $this->session_token);
				},
				$html
			);
		}

		return $html;
	}

	/**
	 * Validate
	 *
	 * @access public
	 */
	public function validate() {
		$application = \Skeleton\Core\Application::get();

		// Allow the application to override running the validation process completely
		if ($this->enabled && $application->event_exists('security', 'csrf_validate_enabled')) {
			if ($application->call_event('security', 'csrf_validate_enabled') === false) {
				return true;
			}
		}

		// Save the token locally so we can unset it later, as to not hinder
		// further processing
		if (isset($_POST[$this->post_token_name])) {
			$submitted_token = $_POST[$this->post_token_name];
		} elseif (isset($_SERVER[strtoupper('http_' . str_replace('-', '_', $this->header_token_name))])) {
			$submitted_token = $_SERVER[strtoupper('http_' . str_replace('-', '_', $this->header_token_name))];
		} else {
			$submitted_token = null;
		}

		if ($this->enabled && $application->event_exists('security', 'csrf_validate')) {
			return $application->call_event('security', 'csrf_validate');
		}

		unset($_POST[$this->post_token_name]);

		if ($this->enabled == false) {
			return true;
		}

		// We only validate POST requests
		// This is probably not the most complete implementation, but let's agree that GET requests should never modify data and we're mostly covered
		if (getenv('REQUEST_METHOD') === 'POST') {
			if (empty($submitted_token) || $_SESSION[$this->session_token_name] !== $submitted_token) {
				if ($application->event_exists('security', 'csrf_validate_failed')) {
					return $application->call_event('security', 'csrf_validate_failed');
				} else {
					return false;
				}
			} else {
				if ($application->event_exists('security', 'csrf_validate_success')) {
					return $application->call_event_if_exists('security', 'csrf_validate_success');
				} else {
					return true;
				}
			}
		}

		return true;
	}
}
