<?php

declare(strict_types=1);

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
	 * Teardown the application
	 *
	 * @access public
	 */
	public function teardown(): void {
		// No default action
	}
	/**
	 * Bootstrap the application
	 *
	 * @access public
	 * @return bool $proceed
	 */
	public function bootstrap(): bool {
		// No default action
		return true;
	}

	/**
	 * Detect if this is the application to run
	 *
	 * @access public
	 * @return bool $detected
	 */
	public function detect(string $hostname, string $request_uri): bool {
		if (in_array($hostname, $this->application->config->hostnames)) {
			return true;
		}
		return false;
	}
}
