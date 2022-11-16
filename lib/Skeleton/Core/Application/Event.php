<?php
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
	 * @var \Skeleton\Core\Application $application
	 */
	protected $application;

	/**
	 * Constructor
	 *
	 * @param \Skeleton\Core\Application $application
	 */
	public function __construct(\Skeleton\Core\Application &$application) {
		$this->application = $application;
	}

}
