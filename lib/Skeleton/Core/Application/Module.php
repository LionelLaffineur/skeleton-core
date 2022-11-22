<?php
/**
 * Module management class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Core\Application;

use Skeleton\Core\Application;

abstract class Module {

	/**
	 * Accept the request
	 *
	 * @access public
	 */
	abstract public function accept_request();

}
