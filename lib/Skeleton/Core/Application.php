<?php

declare(strict_types=1);

/**
 * Skeleton Core Application class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Exception_Unknown_Application extends \Exception {
}

abstract class Application {
	/**
	 * Path
	 *
	 * @access public
	 */
	public ?string $path = null;

	/**
	 * Event Path
	 *
	 * @access public
	 */
	public ?string $event_path = null;

	/**
	 * Event Namespace
	 *
	 * @access public
	 */
	public ?string $event_namespace = null;

	/**
	 * Name
	 *
	 * @access public
	 */
	public ?string $name = null;

	/**
	 * Hostname
	 *
	 * @access public
	 */
	public ?string $hostname = null;

	/**
	 * Matched hostname
	 * This variable contains the config value for the matched hostname
	 *
	 * @access public
	 */
	public ?string $matched_hostname = null;

	/**
	 * Relative URI to the application's base URI
	 *
	 * @access public
	 */
	public ?string $request_relative_uri = null;

	/**
	 * Language
	 *
	 * @access public
	 */
	//public ?Language $language = null;

	/**
	 * Config object
	 *
	 * @access public
	 */
	public ?Config $config = null;

	/**
	 * Events
	 *
	 * @var array<string> $events
	 * @access public
	 */
	public array $events = [];

	/**
	 * Application
	 *
	 * @access private
	 */
	private static ?Application $application = null;

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct(string $name) {
		$this->name = $name;
		$this->get_details();
	}

	/**
	 * Accept the HTTP request
	 *
	 * @access public
	 */
	public function accept(): void {
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

		register_shutdown_function([$this, 'call_event'], 'application', 'teardown', []);

		if ($continue) {
			$this->run();
		}
	}

	/**
	 * Run the application
	 *
	 * @access public
	 */
	abstract public function run(): void;

	/**
	 * Load all events
	 *
	 * @access public
	 */
	public function load_event(string $requested_context): object {
		if (isset($this->events[$requested_context])) {
			return $this->events[$requested_context];
		}

		$events = $this->get_events();

		foreach ($events as $context => $default) {
			if (strtolower($context) !== strtolower($requested_context)) {
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
	 * Call event
	 *
	 * @access public
	 * @param $arguments mixed[]
	 */
	public function call_event(string $context, string $action, array $arguments = []): mixed {
		$event = $this->load_event($context);
		return call_user_func_array([$event, $action], $arguments);
	}

	/**
	 * Event exists
	 *
	 * @access public
	 */
	public function event_exists(string $context, string $action): bool {
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
	 * @return array<\Skeleton\Core\Application\Event\Application, string>
	 * @access public
	 */
	public function get_event_callable(string $context, string $action): array {
		$event = $this->load_event($context);
		return [$event, $action];
	}

	/**
	 * Call event if exists
	 *
	 * @access public
	 * @param $arguments mixed[]
	 */
	public function call_event_if_exists(string $context, string $action, array $arguments = []): mixed {
		if (!$this->event_exists($context, $action)) {
			return null;
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
	public static function get(): Application {
		if (self::$application === null) {
			throw new \Exception('No application set');
		}

		return self::$application;
	}

	/**
	 * Set
	 *
	 * @access public
	 */
	public static function set(?Application $application = null): void {
		self::$application = $application;
	}

	/**
	 * Detect
	 *
	 * @access public
	 * @return Application $application
	 */
	public static function detect(string $hostname, string $request_uri): Application {
		// If we already have a cached application, return that one
		if (self::$application !== null) {
			return Application::get();
		}

		// If multiple host headers have been set, use the last one
		if (strpos($hostname, ', ') !== false) {
			[$hostname, $discard] = array_reverse(explode(', ', $hostname));
		}

		unset($discard);

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

				if (count($wildcard_hostnames) === 0) {
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
		$sorted = [];
		foreach ($matched_applications as $application) {
			/**
			 * Matched hostname can be null
			 * NULL cannot be used in strlen, replace it with empty string
			 */
			$matched_hostname = $application->matched_hostname;
			if ($matched_hostname === null) {
				$matched_hostname = '';
			}

			if (isset($application->config->base_uri)) {
				// base_uri should not be empty, default to '/'
				if ($application->config->base_uri === '') {
					$application->config->base_uri = '/';
				}

				if (strpos($request_uri, $application->config->base_uri) === 0) {
					$sorted[strlen($matched_hostname)][strlen($application->config->base_uri)] = $application;
				}
			} else {
				$sorted[strlen($matched_hostname)][0] = $application;
			}
		}

		// Sort the matched array by key, so the most specific one is at the end
		ksort($sorted);
		$applications = array_pop($sorted);

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
	 * @return array<self> $applications
	 */
	public static function get_all(): array {
		$config = Config::get();

		if (!isset($config->application_path)) {
			throw new \Exception('No application_path set. Please set "application_path" in project configuration');
		}

		$application_paths = scandir($config->application_path);
		$applications = [];

		foreach ($application_paths as $application_path) {
			if ($application_path[0] === '.') {
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
	 * @return Application $application
	 */
	public static function get_by_name(string $name): Application {
		$application_type = self::get_application_type($name);
		return new $application_type($name);
	}

	/**
	 * Get application_type
	 *
	 * @access public
	 * @return string $classname
	 */
	public static function get_application_type(string $application_name): string {
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

	/**
	 * Get details of application
	 *
	 * @access protected
	 */
	protected function get_details(): void {
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
		$this->event_namespace = '\\App\\' . ucfirst($this->name) . "\Event\\";

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
	protected function load_config(): void {
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
	 * Get events
	 *
	 * Get a list of events for this application.
	 * The returned array has the context as key, the value is the classname
	 * of the default event
	 *
	 * @access protected
	 * @return array<string> $events
	 */
	protected function get_events(): array {
		return [
			'Application' => '\\Skeleton\\Core\\Application\\Event\\Application',
			'Error' => '\\Skeleton\\Core\\Application\\Event\\Error',
			'Media' => '\\Skeleton\\Core\\Application\\Event\\Media',
		];
	}
}
