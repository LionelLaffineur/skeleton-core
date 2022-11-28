<?php
/**
 * Module Context
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Application\Event;

class Module extends \Skeleton\Core\Application\Event {

	/**
	 * Access denied
	 *
	 * @access public
	 * @param \Skeleton\Core\Application\Web\Module
	 */
	public function access_denied(\Skeleton\Core\Application\Module $module): void {
		throw new \Exception('Access denied');
	}

	/**
	 * Media not found
	 *
	 * @access public
	 */
	public function not_found(): void {
		\Skeleton\Core\Web\HTTP\Status::code_404('module');
	}

}
