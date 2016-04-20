<?php
/**
 * Auth Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AuthAppController', 'Auth.Controller');
App::uses('MailSend', 'Mails.Utility');

/**
 * Auth Controller
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Controller
 */
class AuthController extends AuthAppController {

/**
 * use component
 *
 * @var array
 */
	public $components = array(
		'Security',
	);

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'Rooms.Room',
		'Users.User',
		'UserRoles.UserRole',
		'Auth.ForgotPass',
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
		$this->Auth->allow('login', 'logout', 'forgot_password', 'request_password');

		$siteSettions = $this->ForgotPass->getSiteSetting();
		$this->set('siteSettions', $siteSettions);
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
		if ($this->request->is('post')) {
			//ログイン
			if ($this->Auth->login()) {
				$this->User->updateLoginTime($this->Auth->user('id'));
				Current::write('User', $this->Auth->user());
				$this->Auth->loginRedirect = $this->SiteSetting->getDefaultStartPage();
				return $this->redirect($this->Auth->redirect());
			}

			//パスワード再発行でログイン
			$user = $this->ForgotPass->loginRescuePassowrd($this->request->data);
			if ($this->Auth->login($user)) {
				$this->User->updateLoginTime($this->Auth->user('id'));
				Current::write('User', $this->Auth->user());
				$this->Auth->loginRedirect = '/auth/auth/update_password';
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
			$this->redirect($this->Auth->loginAction);
		}
	}

/**
 * パスワード再発行の受付
 *
 * @return void
 **/
	public function forgot_password() {
		if (! Hash::get($$this->viewVars['siteSettions']['ForgotPass.use_password_reissue'], '0.value')) {
			return $this->throwBadRequest();
		}

		if ($this->request->is('post')) {
			$forgotPass = $this->ForgotPass->saveForgotPassowrd($this->request->data);
			if ($forgotPass) {
				// キューからメール送信
				MailSend::send();

				$this->NetCommons->setFlashNotification(
					__d('auth',
						'We have sent you the key to obtain a new password to your registered e-mail address.'),
					array('class' => 'success')
				);

				$this->Session->write($forgotPass);
				return $this->redirect('/auth/auth/request_password');
			}
			$this->NetCommons->handleValidationError($this->ForgotPass->validationErrors);
		}
	}

/**
 * パスワード再発行
 *
 * @return void
 **/
	public function request_password() {
		if (! Hash::get($$this->viewVars['siteSettions']['ForgotPass.use_password_reissue'], '0.value')) {
			return $this->throwBadRequest();
		}

		if ($this->request->is('post')) {
			if ($this->ForgotPass->saveRequestPassowrd($this->request->data)) {
				// キューからメール送信
				MailSend::send();

				$this->NetCommons->setFlashNotification(
					__d('auth', 'We have sent your new password to your registered e-mail address.'),
					array('class' => 'success')
				);

				$this->Session->delete('ForgotPass');
				return $this->redirect('/auth/auth/login');
			}
			$this->NetCommons->handleValidationError($this->ForgotPass->validationErrors);
		}
	}

/**
 * パスワード再発行
 *
 * @return void
 **/
	public function update_password() {
		if ($this->request->is('put')) {
			if ($this->ForgotPass->savePassowrd($this->request->data)) {
				$this->NetCommons->setFlashNotification(
					__d('net_commons', 'Successfully saved.'), array('class' => 'success')
				);
				$this->Auth->loginRedirect = $this->SiteSetting->getDefaultStartPage();
				return $this->redirect($this->Auth->redirect());
			}

			$this->NetCommons->handleValidationError($this->ForgotPass->validationErrors);
		} else {
			$this->request->data['User']['id'] = Current::read('User.id');
		}
	}

/**
 * logout
 *
 * @return void
 **/
	public function logout() {
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
