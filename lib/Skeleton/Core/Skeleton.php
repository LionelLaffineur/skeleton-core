<?php
/**
 * Skeleton Core Skeleton class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Skeleton {

	/**
	 * Name
	 *
	 * @access public
	 * @var string $name
	 */
	public $name = null;

	/**
	 * Path
	 *
	 * @access public
	 * @var string $path
	 */
	public $path = null;

	/**
	 * Template path
	 *
	 * @access public
	 * @var string $path
	 */
	public $template_path = null;

	/**
	 * Asset dir
	 *
	 * @access public
	 * @var string $path
	 */
	public $asset_path = null;

	/**
	 * Migration path
	 *
	 * @access public
	 * @var string $migration_path
	 */
	public $migration_path = null;

	/**
	 * Package cache
	 *
	 * @access private
	 * @var array $package_cache
	 */
	private static $skeleton_cache = null;

	/**
	 * Get all
	 *
	 * @access public
	 * @return array $packages
	 */
	public static function get_all() {
		if (self::$skeleton_cache === null) {
			/**
			 * Search for other Skeleton packages installed
			 */
			$installed = \Composer\InstalledVersions::getAllRawData();
			$installed = array_shift($installed);

			$skeletons = [];
			foreach ($installed['versions'] as $package => $details) {
				if (strpos($package, '/') === false) {
					continue;
				}

				list($vendor, $name) = explode('/', $package);

				if ($vendor !== 'tigron' || $details['type'] !== 'library') {
					continue;
				}

				$skeleton = new self();
				$skeleton->name = $name;
				$skeleton->path = realpath($details['install_path']);
				$skeleton->template_path = $skeleton->path . '/template';
				$skeleton->asset_path = $skeleton->path . '/media';
				$skeleton->migration_path = $skeleton->path . '/migration';

				$skeletons[] = $skeleton;
			}

			self::$skeleton_cache = $skeletons;
		}

		return self::$skeleton_cache;
	}
}
