<?php
/**
 * Skeleton Core Application class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Exception_Unknown_Application extends \Exception {}

abstract class Application {

	/**
	 * Application
	 *
	 * @var Application $application
	 * @access private
	 */
	private static $application = null;

	/**
	 * Path
	 *
	 * @var string $path
	 * @access public
	 */
	public $path = null;

	/**
	 * Event Path
	 *
	 * @var string $event_path
	 * @access public
	 */
	public $event_path = null;

	/**
	 * Event Namespace
	 *
	 * @var string $event_namespace
	 * @access public
	 */
	public $event_namespace = null;

	/**
	 * Name
	 *
	 * @var string $name
	 * @access public
	 */
	public $name = null;

	/**
	 * Hostname
	 *
	 * @var string $hostname
	 * @access public
	 */
	public $hostname = null;

	/**
	 * Matched hostname
	 * This variable contains the config value for the matched hostname
	 *
	 * @var string $matched_hostname
	 * @access public
	 */
	public $matched_hostname = null;

	/**
	 * Relative URI to the application's base URI
	 *
	 * @var string $request_relative_uri
	 * @access public
	 */
	public $request_relative_uri = null;

	/**
	 * Language
	 *
	 * @access public
	 * @var Language $language
	 */
	public $language = null;

	/**
	 * Config object
	 *
	 * @access public
	 * @var Config $config
	 */
	public $config = null;

	/**
	 * Events
	 *
	 * @access public
	 * @var array $events
	 */
	public $events = [];

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct($name) {
		$this->name = $name;
		$this->get_details();
	}

	/**
	 * Get details of application
	 *
	 * @access protected
	 */
	protected function get_details() {
		$config = clone Config::get();
		$this->config = $config;

		/**
		 * @deprecated: this is for backwards compatibility
		 */
		if (isset($config->application_dir) and !isset($config->application_path)) {
			$config->application_path = $config->application_dir;
		}

		$application_path = realpath($config->application_path . '/' . $this->name);

		if (!file_exists($application_path)) {
			throw new \Exception('Application with name "' . $this->name . '" not found');
		}
		$this->path = $application_path;
		$this->event_path = $this->path . '/event/';
		$this->event_namespace = "\\App\\" . ucfirst($this->name) . "\Event\\";

		if (file_exists($this->event_path)) {
			$autoloader = new \Skeleton\Core\Autoloader();
			$autoloader->add_namespace($this->event_namespace, $this->event_path);
			$autoloader->register();
		}

		$this->load_config();
	}

	/**
	 * Load the config
	 *
	 * @access private
	 */
	protected function load_config() {
		if (!file_exists($this->path . '/config')) {
			throw new \Exception('No config directory created in app ' . $this->path);
		}

		/**
		 * Set some defaults
		 */
		$this->config->application_type = '\Skeleton\Core\Application\Web';
		$this->config->read_path($this->path . '/config');
	}

	/**
	 * Accept the HTTP request
	 *
	 * @access public
	 */
	public function accept() {
		/**
		 * If this application is launched while another application has been
		 * set, we need to take over the request_relative_uri
		 * This happens when whitin an application, another application is
		 * started.
		 */
		$application = self::get();
		$this->request_relative_uri = $application->request_relative_uri;
		$this->hostname = $application->hostname;

		\Skeleton\Core\Application::set($this);	

		$continue = $this->call_event('application', 'bootstrap', []);
		if ($continue) {
			$this->run();
		}
		$this->call_event('application', 'teardown', []);
	}

	/**
	 * Run the application
	 *
	 * @access public
	 */
	abstract public function run();

	/**
	 * Check if an event exists
	 *
	 * @access public
	 * @param string $context
	 * @param string $action
	 * @return bool $exists
	 */
	public function load_event($requested_context) {
		if (isset($this->events[$requested_context])) {
			return $this->events[$requested_context];
		}

		$events = $this->get_events();

		foreach ($events as $context => $default) {
			if (strtolower($context) != strtolower($requested_context)) {
				continue;
			}
			$application_event = '\\App\\' . ucfirst($this->name) . '\\Event\\' . ucfirst($context);
			if (class_exists($application_event)) {
				if (!is_a($application_event, $default, true)) {
					throw new \Exception('Event ' . $application_event . ' should extend from ' . $default);
				}
				$event = new $application_event($this);
				$this->events[strtolower($context)] = $event;
				return $this->events[strtolower($context)];
			}

			$event = new $default($this);
			$this->events[strtolower($context)] = $event;
			return $this->events[strtolower($context)];
		}

		throw new \Exception('There is no event found for context ' . $requested_context);
	}

	/**
	 * Get events
	 *
	 * Get a list of events for this application.
	 * The returned array has the context as key, the value is the classname
	 * of the default event
	 *
	 * @access protected
	 * @return array $events
	 */
	protected function get_events(): array {
		return [
			'Application' => '\\Skeleton\\Core\\Application\\Event\\Application',
			'Error' => '\\Skeleton\\Core\\Application\\Event\\Error',
			'Media' => '\\Skeleton\\Core\\Application\\Event\\Media',
		];
	}

	/**
	 * Call event
	 *
	 * @access public
	 * @param string $context
	 * @param string $action
	 */
	public function call_event($context, $action, $arguments = []) {
		$event = $this->load_event($context);
		return call_user_func_array([$event, $action], $arguments);
	}

	/**
	 * Event exists
	 *
	 * @access public
	 * @param string $context
	 * @param string $action
	 */
	public function event_exists($context, $action) {
		try {
			$event = $this->load_event($context);
		} catch (\Exception $e) {
			return false;
		}
		if (is_callable([$event, $action])) {
			return true;
		}
		return false;
	}

	/**
	 * Get a callable for an event
	 *
	 * @access public
	 * @param string $context
	 * @param string $action
	 * @return array
	 */
	public function get_event_callable(string $context, string $action) {
		$event = $this->load_event($context);
		return [$event, $action];
	}

	/**
	 * Call event if exists
	 *
	 * @access public
	 * @param string $context
	 * @param string $action
	 */
	public function call_event_if_exists($context, $action, $arguments = []) {
		if (!$this->event_exists($context, $action)) {
			return;
		}

		return call_user_func_array($this->get_event_callable($context, $action), $arguments);
	}

	/**
	 * Get
	 *
	 * Try to fetch the current application
	 *
	 * @access public
	 * @return Application $application
	 */
	public static function get() {
		if (self::$application === null) {
			throw new \Exception('No application set');
		}

		return self::$application;
	}

	/**
	 * Set
	 *
	 * @access public
	 * @param Application $application
	 */
	public static function set(Application $application = null) {
		self::$application = $application;
	}

	/**
	 * Detect
	 *
	 * @param string $hostname
	 * @param string $request_uri
	 * @access public
	 * @return Application $application
	 */
	public static function detect($hostname, $request_uri) {

		// If we already have a cached application, return that one
		if (self::$application !== null) {
			return Application::get();
		}

		// If multiple host headers have been set, use the last one
		if (strpos($hostname, ', ') !== false) {
			list($hostname, $discard) = array_reverse(explode(', ', $hostname));
		}

		// Find matching applications
		$applications = self::get_all();
		$matched_applications = [];

		// Match via event
		foreach ($applications as $application) {
			if ($application->call_event('application', 'detect', [ $hostname, $request_uri ])) {
				$matched_applications[] = $application;
			}
		}

		// If we don't have any matched applications, try to match wildcards
		if (count($matched_applications) === 0) {
			foreach ($applications as $application) {
				$wildcard_hostnames = $application->config->hostnames;
				foreach ($wildcard_hostnames as $key => $wildcard_hostname) {
					if (strpos($wildcard_hostname, '*') === false) {
						unset($wildcard_hostnames[$key]);
					}
				}

				if (count($wildcard_hostnames) == 0) {
					continue;
				}

				foreach ($wildcard_hostnames as $wildcard_hostname) {
					if (fnmatch($wildcard_hostname, $hostname)) {
						$clone = clone $application;
						$clone->matched_hostname = $wildcard_hostname;
						$matched_applications[] = $clone;
					}
				}
			}
		}

		// Set required variables in the matched Application objects
		foreach ($matched_applications as $key => $application) {
			 // Set the relative request URI according to the application
			if (isset($application->config->base_uri) and ($application->config->base_uri !== '/')) {
				$application->request_relative_uri = str_replace($application->config->base_uri, '', $request_uri);
			} else {
				$application->request_relative_uri = $request_uri;
			}

			$application->hostname = $hostname;
			$matched_applications[$key] = $application;
		}

		// Now that we have matching applications, see if one matches the
		// request specifically. Otherwise, simply return the first one.
		$matched_applications_sorted = [];
		foreach ($matched_applications as $application) {
			if (isset($application->config->base_uri)) {
				// base_uri should not be empty, default to '/'
				if ($application->config->base_uri == '') {
					$application->config->base_uri = '/';
				}
				if (strpos($request_uri, $application->config->base_uri) === 0) {
					$matched_applications_sorted[strlen($application->matched_hostname)][strlen($application->config->base_uri)] = $application;
				}
			} else {
				$matched_applications_sorted[strlen($application->matched_hostname)][0] = $application;
			}
		}

		// Sort the matched array by key, so the most specific one is at the end
		ksort($matched_applications_sorted);
		$applications = array_pop($matched_applications_sorted);

		if (is_array($applications) && count($applications) > 0) {
			// Get the most specific one
			ksort($applications);
			$application = array_pop($applications);
			Application::set($application);
			return Application::get();
		}

		throw new Exception_Unknown_Application('No application found for ' . $hostname);
	}

	/**
	 * Get all
	 *
	 * @access public
	 * @return array $applications
	 */
	public static function get_all() {
		$config = Config::get();

		if (!isset($config->application_path)) {
			throw new \Exception('No application_path set. Please set "application_path" in project configuration');
		}
		$application_paths = scandir($config->application_path);
		$application = [];
		foreach ($application_paths as $application_path) {
			if ($application_path[0] == '.') {
				continue;
			}

			$application = self::get_by_name($application_path);
			$applications[] = $application;
		}
		return $applications;
	}

	/**
	 * Get application by name
	 *
	 * @access public
	 * @param string $name
	 * @return Application $application
	 */
	public static function get_by_name($name) {
		$application_type = self::get_application_type($name);
		return new $application_type($name);
	}

	/**
	 * Get application_type
	 *
	 * @access public
	 * @return string $classname
	 * @param string $application_name
	 */
	public static function get_application_type($application_name): string {
		$config = clone Config::get();
		$application_path = realpath($config->application_path . '/' . $application_name);

		if (!file_exists($application_path . '/config')) {
			throw new \Exception('No config directory created in app ' . $application_name);
		}

		/**
		 * Set some defaults
		 */
		$config->application_type = '\Skeleton\Application\Web';
		$config->read_path($application_path . '/config');
		return $config->application_type;
	}

}
