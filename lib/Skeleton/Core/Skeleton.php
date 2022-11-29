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
			$composer_path = realpath(__DIR__ . '/../../../../../');
			$installed = file_get_contents($composer_path . '/composer/installed.json');
			$installed = json_decode($installed);

			// The structure of the installed.json file in composer 2 is slightly different
			if (isset($installed->packages)) {
				$installed = $installed->packages;
			}

			$skeletons = [];
			foreach ($installed as $install) {
				$package = $install->name;
				list($vendor, $name) = explode('/', $package);
				if ($vendor != 'tigron') {
					continue;
				}

				$skeleton = new self();
				$skeleton->name = $name;
				$skeleton->path = $composer_path . '/tigron/' . $name;
				$skeleton->template_path = $composer_path . '/tigron/' . $name . '/template';
				$skeleton->asset_path = $composer_path . '/tigron/' . $name . '/media';
				$skeleton->migration_path = $composer_path . '/tigron/' . $name . '/migration';

				$skeletons[] = $skeleton;
			}
			self::$skeleton_cache = $skeletons;
		}
		return self::$skeleton_cache;
	}

}
