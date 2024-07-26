<?php

declare(strict_types=1);

/**
 * Error Context
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Application\Event;

class Error extends \Skeleton\Core\Application\Event {
	/**
	 * Exception denied
	 *
	 * @access public
	 * @param \Exception $exception
	 * @return bool $proceed_error_handlers
	 */
	public function exception(\Throwable $exception): bool {
		return true;
	}

	/**
	 * Sentry before send
	 *
	 * Add additional data to a sentry event
	 * This will only be executed when skeleton-error is used combined
	 * with sentry\sdk
	 *
	 * @access public
	 */
	public function sentry_before_send(\Sentry\Event $event): \Sentry\Event {
		return $event;
	}
}
