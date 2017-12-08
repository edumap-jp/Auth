<?php
/**
 * AuthApp Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AppController', 'Controller');

/**
 * AuthApp Controller
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Controller
 */
class AuthAppController extends AppController {

/**
 * Other components
 *
 * @var array
 */
	public $components = array(
		'Auth.AuthPlugin',
	);

/**
 * Return authentication adapter name
 *
 * @return string Authentication adapter name
 **/
	protected static function _getAuthenticator() {
		return 'Form';
	}

/**
 * Return available authenticators
 *
 * @return array authenticators
 */
	protected function _getAuthenticators() {
		return $this->AuthPlugin->getAuthenticators();
	}

/**
 * デフォルト開始ページの取得
 *
 * @return string or null
 */
	protected function _getDefaultStartPage() {
		return $this->SiteSetting->getDefaultStartPage();
	}

}
