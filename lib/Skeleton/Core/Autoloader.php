<?php
/**
 * Skeleton PSR-0 compliant autoloader
 *
 * Based on the SplClassLoader example by Jonathan H. Wage and others
 * https://gist.github.com/jwage/221634
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Autoloader {

	/**
	 * @var string $file_extension
	 */
	private $file_extension = '.php';

	/**
	 * @var string $namespace
	 */
	private $namespace = null;

	/**
	 * @var string $include_paths
	 */
	private $include_paths = [];

	/**
	 * @var string $namespaces
	 */
	private $namespaces = [];

	/**
	 * @var string $namespace_separator
	 */
	private $namespace_separator = '\\';

	/**
	 * Sets the namespace separator used by classes in the namespace of this
	 * class loader.
	 *
	 * @param string $sep The separator to use.
	 */
	public function set_namespace_separator($sep) {
		$this->namespace_separator = $sep;
	}

	/**
	 * Gets the namespace seperator used by classes in the namespace of this
	 * class loader.
	 *
	 * @return void
	 */
	public function get_namespace_separator() {
		return $this->namespace_separator;
	}

	/**
	 * Adds an include path for all class files in the namespace of this
	 * class loader.
	 *
	 * @param string $include_path
	 */
	public function add_include_path($include_path, $class_prefix = '') {
		$this->include_paths[] = [
			'include_path' => $include_path,
			'class_prefix' => $class_prefix
		];
	}

	/**
	 * Search for a namespace in a given path
	 *
	 * @access public
	 * @param string $namespace
	 * @param string $path
	 */
	public function add_namespace($namespace, $path) {
		$this->namespaces[$namespace] = $path;
	}

	/**
	 * Gets the loaded include paths.
	 *
	 * @return string $include_path
	 */
	public function get_include_paths() {
		return $this->include_paths;
	}



	/**
	 * Sets the file extension of class files in the namespace of this class loader.
	 *
	 * @param string $file_extension
	 */
	public function set_file_extension($file_extension) {
		$this->file_extension = $file_extension;
	}

	/**
	 * Gets the file extension of class files in the namespace of this class loader.
	 *
	 * @return string $file_extension
	 */
	public function get_file_extension() {
		return $this->file_extension;
	}

	/**
	 * Installs this class loader on the SPL autoload stack.
	 */
	public function register() {
		spl_autoload_register([$this, 'load_class']);
	}

	/**
	 * Uninstalls this class loader from the SPL autoloader stack.
	 */
	public function unregister() {
		spl_autoload_unregister([$this, 'load_class']);
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param string $class_name The name of the class to load.
	 * @return void
	 */
	public function load_class($class_name) {
		foreach ($this->namespaces as $namespace => $namespace_path) {
			if (strpos('\\' . strtolower($class_name), strtolower($namespace)) !== 0) {
				continue;
			}

			$file_path = str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('\\', ' ', str_replace(strtolower($namespace), '', '\\' . strtolower($class_name))))) . '.php';
			$file_path = $namespace_path . DIRECTORY_SEPARATOR . $file_path;

			try {
				$this->require_file($file_path);

				if (class_exists($class_name, false)) {
					class_parents($class_name, true);
					return true;
				}
			} catch (\Skeleton\Core\Exception\Autoloading $e) { }
		}

		foreach ($this->include_paths as $include_path) {
			$file_path = str_replace(' ', '/', ucwords(str_replace('_', ' ', str_replace('\\', ' ', strtolower(str_replace($include_path['class_prefix'], '', $class_name)))))) . '.php';

			try {
				$path = $include_path['include_path'] . '/' . $file_path;
				$this->require_file($path);

				if (class_exists($class_name, false)) {
					class_parents($class_name, true);
					return true;
				}
			} catch (\Skeleton\Core\Exception\Autoloading $e) { }

			/**
			 * If the file is not found, try with all lower case. This should be
			 * improved with PSR loading techniques
			 */
			try {
				$path = strtolower($include_path['include_path'] . '/' . $file_path);
				$this->require_file($path);

				if (class_exists($class_name, false)) {
					class_parents($class_name, true);
					return true;
				}
			} catch (\Skeleton\Core\Exception\Autoloading $e) { }

		}
	}

	/**
	 * Require a file
	 *
	 * @access private
	 * @param string $path
	 */
	private function require_file($path) {
		$path = realpath($path);
		if (file_exists($path)) {
			require_once $path;

			// Opcache compilation
			$opcache_enabled = ini_get('opcache.enable');
			$opcache_cli_enabled = ini_get('opcache.enable_cli');
			if ( (php_sapi_name() == 'cli' and $opcache_cli_enabled) or (php_sapi_name() != 'cli' and $opcache_enabled)) {
				if (function_exists('opcache_is_script_cached') and function_exists('opcache_compile_file')) {
					if (!opcache_is_script_cached($path)) {
						// We have noticed OPcache sometimes yields a warning when compiling a file,
						// while that exact same file compiled just fine moments earlier. Worse, it
						// seems the OPcache for said file becomes corrupt somehow. This might
						// be a bug somewhere. We'll try to work around this issue by suppressing the
						// warning and explicitly invalidate the cache for the file if compilation fails.
						if (@opcache_compile_file($path) === false) {
							opcache_invalidate($path, true);
						}
					}
				}
			}

			return true;
		} else {
			throw new \Skeleton\Core\Exception\Autoloading('File not found');
		}
	}
}
