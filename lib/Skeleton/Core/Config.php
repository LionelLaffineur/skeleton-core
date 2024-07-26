<?php

declare(strict_types=1);

/**
 * Config class
 * Configuration for Skeleton\Core
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Config {
	/**
	 * Config array
	 *
	 * @var array<string> $config_data
	 */
	protected array $config_data = [];

	/**
	 * Config object
	 *
	 * @access private
	 */
	private static ?self $config = null;

	/**
	 * Private (disabled) constructor
	 *
	 * @access private
	 */
	public function __construct() {
	}

	/**
	 * Get config vars as properties
	 *
	 * @throws Exception When accessing an unknown config variable, an Exception is thrown
	 * @access public
	 */
	public function __get(string $name): mixed {
		if (!isset($this->config_data[$name])) {
			throw new \Exception('Attempting to read unkown config key: '.$name);
		}
		return $this->config_data[$name];
	}

	/**
	 * Get config vars as properties
	 *
	 * @access public
	 */
	public function __set(string $name, mixed $value): void {
		$this->config_data[$name] = $value;
	}

	/**
	 * Check if config var exists
	 *
	 * @access public
	 */
	public function __isset(string $key): bool {
		if (!isset($this->config_data) or $this->config_data === null) {
			$this->read();
		}

		if (isset($this->config_data[$key])) {
			return true;
		}

		return false;
	}

	/**
	 * Get function, returns a Config object
	 *
	 * @access public
	 */
	public static function Get(): Config {
		try {
			$application = \Skeleton\Core\Application::get();
			return $application->config;
		} catch (\Exception $e) {
			if (self::$config === null) {
				throw new \Exception('No config set');
			}
			return self::$config;
		}
	}

	/**
	 * Read a config file into this config
	 *
	 * @access public
	 */
	public function read_file(string $file): void {
		if (!file_exists($file)) {
			throw new \Exception($file . ' cannot be included in config. File does not exist.');
		}

		$config_data = require $file;

		if (!is_array($config_data)) {
			return;
		}
		foreach ($config_data as $key => $value) {
			$this->$key = $value;
		}
	}

	/**
	 * Read config files from directory
	 *
	 * @access public
	 */
	public function read_path(string $path): void {
		if (!file_exists($path)) {
			throw new \Exception('Config directory does not exist');
		}

		$files = scandir($path);
		foreach ($files as $file) {
			if ($file[0] === '.') {
				continue;
			}
			$info = pathinfo($file);

			if (!isset($info['extension']) or $info['extension'] !== 'php') {
				continue;
			}

			if ($info['filename'] === 'environment') {
				continue;
			}

			$this->read_file($path . DIRECTORY_SEPARATOR . $info['basename']);
		}

		if (file_exists($path . DIRECTORY_SEPARATOR . 'environment.php')) {
			$this->read_file($path . DIRECTORY_SEPARATOR . 'environment.php');
		}
	}

	/**
	 * Include a config directory
	 *
	 * @access public
	 */
	public static function include_path(string $path): void {
		if (!file_exists($path)) {
			throw new \Exception('Config directory does not exist');
		}

		try {
			$config = self::get();
		} catch (\Exception $e) {
			$config = new self();
			self::$config = $config;
		}
		$config->read_path($path);
		self::$config = $config;
	}

	/**
	 * Include a config directory
	 *
	 * @access public
	 */
	public static function include_directory(string $directory): void {
		self::include_path($directory);
	}
}
