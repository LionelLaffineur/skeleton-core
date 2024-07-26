<?php

declare(strict_types=1);

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
	 */
	public ?string $name = null;

	/**
	 * Path
	 *
	 * @access public
	 */
	public ?string $path = null;

	/**
	 * Template path
	 *
	 * @access public
	 */
	public ?string $template_path = null;

	/**
	 * Asset dir
	 *
	 * @access public
	 */
	public ?string $asset_path = null;

	/**
	 * Migration path
	 *
	 * @access public
	 */
	public ?string $migration_path = null;

	/**
	 * Package cache
	 *
	 * @var array<self> $skeleton_cache
	 * @access private
	 */
	private static ?array $skeleton_cache = null;

	/**
	 * Get all
	 *
	 * @access public
	 * @return array<self>
	 */
	public static function get_all(): array {
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

				[$vendor, $name] = explode('/', $package);

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
