<?php
/**
 * Auth routes configuration
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

Router::connect('/auth/:action', array(
	'plugin' => 'auth', 'controller' => 'auth'
));
