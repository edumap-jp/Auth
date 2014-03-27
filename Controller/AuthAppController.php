<?php
App::uses('AppController', 'Controller');
/**
 * Auth App Controller
 *
 * @author   Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @link     http://www.netcommons.org NetCommons Project
 * @license  http://www.netcommons.org/license.txt NetCommons License
 */
class AuthAppController extends AppController {

/**
 * Return authentication adapter name
 *
 * @return string Authentication adapter name
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 **/
	protected static function _getAuthenticator() {
		return 'Form';
	}

/**
 * Return available authenticators
 *
 * @author   Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @return   array authenticators
 */
	public function getAuthenticators() {
		$authenticators = array();
		$plugins = App::objects('plugins');
		foreach ($plugins as $plugin) {
			if (preg_match('/^Auth([\w]+)/', $plugin, $matches)) {
				$authenticators[] = $matches[1];
			}
		}

		return $authenticators;
	}
}
