<?php

declare(strict_types=1);

/**
 * Event
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Application;

class Event {
	/**
	 * Application object
	 *
	 * @access protected
	 */
	protected \Skeleton\Core\Application $application;

	/**
	 * Constructor
	 */
	public function __construct(\Skeleton\Core\Application &$application) {
		$this->application = $application;
	}
}
