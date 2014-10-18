<?php
/**
 * Auth routes configuration
 *
 * @copyright Copyright 2014, NetCommons Project
 * @author   Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @link     http://www.netcommons.org NetCommons Project
 * @license  http://www.netcommons.org/license.txt NetCommons License
 */

Router::connect('/auth/:action', array(
	'plugin' => 'auth', 'controller' => 'auth'
));
