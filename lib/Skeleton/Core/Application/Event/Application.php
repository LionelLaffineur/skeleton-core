<?php
/**
 * Application Context
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Application\Event;

class Application extends \Skeleton\Core\Application\Event {

	/**
	 * Bootstrap the application
	 *
	 * @access public
	 * @param \Skeleton\Core\Web\Module $module
	 */
	public function bootstrap(\Skeleton\Core\Application\Module $module): void {
		// No default action
	}

	/**
	 * Teardown the application
	 *
	 * @access public
	 * @param \Skeleton\Core\Web\Module $module
	 */
	public function teardown(\Skeleton\Core\Application\Module $module): void {
		// No default action
	}

	/**
	 * Detect if this is the application to run
	 *
	 * @access public
	 * @param string $hostname
	 * @param string $request_uri
	 * @return bool $detected
	 */
	public function detect($hostname, $request_uri): bool {
		if (in_array($hostname, $this->application->config->hostnames)) {
			return true;
		}
		return false;
	}

}
