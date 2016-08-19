<?php
/**
 * 認証Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AuthAppController', 'Auth.Controller');
App::uses('UserAttributeChoice', 'UserAttributes.Model');

/**
 * 認証Controller
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Controller
 */
class AuthController extends AuthAppController {

/**
 * 使用するModels
 *
 * - [SiteManager.SiteSetting](../../SiteManager/classes/SiteSetting.html)
 * - [Users.User](../../Users/classes/User.html)
 *
 * @var array
 */
	public $uses = array(
		'SiteManager.SiteSetting',
		'Users.User',
	);

/**
 * beforeFilter
 *
 * @return void
 **/
	public function beforeFilter() {
		// Load available authenticators
		$authenticators = $this->getAuthenticators();
		$this->set('authenticators', $authenticators);

		$this->__setDefaultAuthenticator();

		parent::beforeFilter();
		$this->Auth->allow('login', 'logout');

		$this->Session->delete('AutoUserRegist');
		$this->Session->delete('ForgotPass');
	}

/**
 * index
 *
 * @return void
 **/
	public function index() {
		$this->redirect($this->Auth->loginAction);
	}

/**
 * ログイン処理
 *
 * @return void
 * @throws InternalErrorException
 **/
	public function login() {
		//ページタイトル
		$this->set('pageTitle', __d('auth', 'Login'));

		if ($this->request->is('post')) {
			//Auth->login()を実行すると、$this->UserがUsers.UserからModelAppに置き換わってしまい、
			//エラーになるため、変数に保持しておく。
			$User = $this->User;

			$this->Auth->authenticate['all']['scope'] = array(
				'User.status' => '1'
			);
			if ($this->Auth->login()) {
				$User->updateLoginTime($this->Auth->user('id'));
				Current::write('User', $this->Auth->user());
				if ($this->Auth->user('language') !== UserAttributeChoice::LANGUAGE_KEY_AUTO) {
					$this->Session->write('Config.language', $this->Auth->user('language'));
				}
				$this->Auth->loginRedirect = $this->SiteSetting->getDefaultStartPage();
				return $this->redirect($this->Auth->redirect());
			}

			$this->NetCommons->setFlashNotification(
				__d('auth', 'Invalid username or password, try again'),
				array(
					'class' => 'danger',
					'interval' => NetCommonsComponent::ALERT_VALIDATE_ERROR_INTERVAL,
				),
				400
			);
			//$this->redirect($this->Auth->loginAction);
		}
	}

/**
 * logout
 *
 * @return void
 **/
	public function logout() {
		$this->Session->delete('Config.language');
		$this->redirect($this->Auth->logout());
	}

/**
 * Set authenticator
 *
 * @return void
 **/
	private function __setDefaultAuthenticator() {
		$scheme = strtr(Inflector::camelize($this->request->offsetGet('plugin')), array('Auth' => ''));
		$callee = array(sprintf('Auth%sAppController', $scheme), '_getAuthenticator');

		if (is_callable($callee)) {
			$authenticator = call_user_func($callee);
			$this->Auth->authenticate = array($authenticator => array());
			//CakeLog::info(sprintf('Will load %s authenticator', $authenticator), true);
		} else {
			//CakeLog::info(sprintf('Unknown authenticator %s.%s', $plugin, $scheme), true);
		}
	}
}
