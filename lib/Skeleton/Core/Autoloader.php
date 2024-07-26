<?php

declare(strict_types=1);

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
	private string $file_extension = '.php';

	private ?string $namespace = null;

	/**
	 * @var array<string> $include_paths
	 */
	private array $include_paths = [];

	/**
	 * @var array<string> $namespaces
	 */
	private array $namespaces = [];

	private string $namespace_separator = '\\';

	/**
	 * Sets the namespace separator used by classes in the namespace of this
	 * class loader.
	 *
	 * @param string $sep The separator to use.
	 */
	public function set_namespace_separator(string $sep): void {
		$this->namespace_separator = $sep;
	}

	/**
	 * Gets the namespace seperator used by classes in the namespace of this
	 * class loader.
	 */
	public function get_namespace_separator(): string {
		return $this->namespace_separator;
	}

	/**
	 * Adds an include path for all class files in the namespace of this
	 * class loader.
	 */
	public function add_include_path(string $include_path, string $class_prefix = ''): void {
		$this->include_paths[] = [
			'include_path' => $include_path,
			'class_prefix' => $class_prefix,
		];
	}

	/**
	 * Search for a namespace in a given path
	 *
	 * @access public
	 */
	public function add_namespace(string $namespace, string $path): void {
		$this->namespaces[$namespace] = $path;
	}

	/**
	 * Gets the loaded include paths.
	 *
	 * @return string $include_path
	 */
	public function get_include_paths(): string {
		return $this->include_paths;
	}

	/**
	 * Sets the file extension of class files in the namespace of this class loader.
	 */
	public function set_file_extension(string $file_extension): void {
		$this->file_extension = $file_extension;
	}

	/**
	 * Gets the file extension of class files in the namespace of this class loader.
	 *
	 * @return string $file_extension
	 */
	public function get_file_extension(): string {
		return $this->file_extension;
	}

	/**
	 * Installs this class loader on the SPL autoload stack.
	 */
	public function register(): void {
		spl_autoload_register([$this, 'load_class']);
	}

	/**
	 * Uninstalls this class loader from the SPL autoloader stack.
	 */
	public function unregister(): void {
		spl_autoload_unregister([$this, 'load_class']);
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param string $class_name The name of the class to load.
	 */
	public function load_class(string $class_name): bool {
		foreach ($this->namespaces as $namespace => $namespace_path) {
			if (strpos('\\' . strtolower($class_name), strtolower($namespace)) !== 0) {
				continue;
			}

			$file_path = str_replace(strtolower($namespace), '', '\\' . strtolower($class_name));
			$file_path = str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('\\', ' ', $file_path))) . '.php';
			$file_path = $namespace_path . DIRECTORY_SEPARATOR . $file_path;

			try {
				$this->require_file($file_path);

				if (class_exists($class_name, false)) {
					class_parents($class_name, true);
					return true;
				}
			} catch (\Skeleton\Core\Exception\Autoloading $e) {
			}
		}

		foreach ($this->include_paths as $include_path) {
			$file_path = strtolower(str_replace($include_path['class_prefix'], '', $class_name));
			$file_path = ucwords(str_replace('_', ' ', str_replace('\\', ' ', $file_path)));
			$file_path = str_replace(' ', '/', $file_path) . '.php';

			try {
				$path = $include_path['include_path'] . '/' . $file_path;
				$this->require_file($path);

				if (class_exists($class_name, false)) {
					class_parents($class_name, true);
					return true;
				}
			} catch (\Skeleton\Core\Exception\Autoloading $e) {
			}

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
			} catch (\Skeleton\Core\Exception\Autoloading $e) {
			}
		}

		return false;
	}

	/**
	 * Require a file
	 *
	 * @access private
	 */
	private function require_file(string $path): bool {
		$path = realpath($path);

		if ($path !== false && file_exists($path)) {
			require_once $path;

			// Opcache compilation
			$opcache_enabled = ini_get('opcache.enable');
			$opcache_cli_enabled = ini_get('opcache.enable_cli');
			if ((PHP_SAPI === 'cli' and $opcache_cli_enabled) or (PHP_SAPI !== 'cli' and $opcache_enabled)) {
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
		}

		throw new \Skeleton\Core\Exception\Autoloading('File not found');
	}
}
