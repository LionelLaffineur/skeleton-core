<?php

declare(strict_types=1);

/**
 * Media Context
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Application\Event;

class Media extends \Skeleton\Core\Application\Event {
	/**
	 * Media not found
	 *
	 * @access public
	 */
	public function not_found(): void {
		throw new \Skeleton\Core\Exception\Media\Not\Found('File not found');
	}
}
