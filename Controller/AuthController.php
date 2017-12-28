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
App::uses('UserRole', 'UserRoles.Model');

/**
 * 認証Controller
 *
 * @property AuthComponent $Auth
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Controller
 * @property SessionComponent $Session
 * @property ExternalIdpUser $ExternalIdpUser
 * @property ForgotPass $ForgotPass
 * @property NetCommonsComponent $NetCommons
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
		'Auth.ForgotPass',
		'Auth.ExternalIdpUser',
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
		$authenticators = $this->_getAuthenticators();
		$this->set('authenticators', $authenticators);

		$this->__setDefaultAuthenticator();

		parent::beforeFilter();
		$this->Auth->allow('login', 'logout', 'exlogin');

		$this->Session->delete('AutoUserRegist');
		$this->Session->delete('ForgotPass');

		$this->Auth->authenticate['all']['scope'] = array(
			'User.status' => '1'
		);
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

		//メールを送れるかどうか
		$this->set('isMailSend', $this->ForgotPass->isMailSendCommon('auth', 'auth'));

		if ($this->request->is('post')) {
			//Auth->login()を実行すると、$this->UserがUsers.UserからModelAppに置き換わってしまい、
			//エラーになるため、変数に保持しておく。
			$User = $this->User;

			$this->__setNc2Authenticate();

			if ($this->Auth->login()) {
				$User->updateLoginTime($this->Auth->user('id'));
				Current::write('User', $this->Auth->user());
				if ($this->Auth->user('language') !== UserAttributeChoice::LANGUAGE_KEY_AUTO) {
					$this->Session->write('Config.language', $this->Auth->user('language'));
				}
				$this->Auth->loginRedirect = $this->_getDefaultStartPage();
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
 * 他のサービスを用いてログイン
 *
 * @return CakeResponse
 **/
	public function exlogin() {
		$redirect = $this->_exLoginRedirect();
		if ($redirect === 'login') {
			// 関連付けされているのはこちら。ID関連付けを行った会員でログイン処理を行い権限毎のデフォルト表示画面へリダイレクト
			return $this->_exloginCall();
		} elseif ($redirect) {
			// 関連付けされていないのはこちら。対象画面にリダイレクト
			return $this->redirect($redirect);
		}

		return $this->throwBadRequest();
	}

/**
 * 「他サービスを用いたログイン処理」後の処理・画面遷移先
 * (外部認証系プラグインのコントローラでオーバーライトして実装)
 *
 * @return string リダイレクト先
 * @see AuthShibbolethController::_exLoginRedirect() オーバーライト参考
 **/
	protected function _exLoginRedirect() {
		return '/';
	}

/**
 * 他のサービスを用いてログイン (ログイン処理呼び出し)
 *
 * @return CakeResponse
 **/
	protected function _exloginCall() {
		$user = $this->_getMappingUser();
		if (! $user) {
			return $this->throwBadRequest();
		}

		// ログイン処理
		return $this->_exLogin($user);
	}

/**
 * ID関連付けを行ったユーザ情報 取得
 *
 * @return array ユーザ情報
 **/
	protected function _getMappingUser() {
		// IdPによる個人識別番号 取得
		$idpUserid = $this->_getIdpUserid();

		$idpUser = $this->ExternalIdpUser->findByIdpUserid($idpUserid);
		if (!$idpUser) {
			return array();
		}

		// ユーザ検索
		$user = $this->User->findByIdAndStatus($idpUser['ExternalIdpUser']['user_id'],
			UserAttributeChoice::STATUS_CODE_ACTIVE);
		if (!$user) {
			return $user;
		}

		// $this->Auth->_user と同じ配列構成にする
		$user = Hash::merge($user, $user['User']);
		$user = Hash::remove($user, 'User');
		return $user;
	}

/**
 * 外部認証から呼び出されるログイン処理（ユーザ情報を指定可能）
 *
 * @param array $user ユーザ情報
 * @throws BadRequestException
 * @return CakeResponse
 * @see AuthController::login() よりコピー
 **/
	protected function _exLogin($user = null) {
		//Auth->login()を実行すると、$this->UserがUsers.UserからModelAppに置き換わってしまい、
		//エラーになるため、変数に保持しておく。
		$User = $this->User;

		if ($this->Auth->login($user)) {
			// ログイン後の追加処理
			$this->_exLoggedin();

			// user情報更新
			$User->updateLoginTime($this->Auth->user('id'));
			Current::write('User', $this->Auth->user());
			if ($this->Auth->user('language') !== UserAttributeChoice::LANGUAGE_KEY_AUTO) {
				$this->Session->write('Config.language', $this->Auth->user('language'));
			}

			// メッセージ表示
			$this->NetCommons->setFlashNotification(
				__d('auth', 'Auth.exlogin.success'),
				array(
					'class' => 'success',
					'interval' => 4000,
				)
			);

			// リダイレクト
			$this->Auth->loginRedirect = $this->_getDefaultStartPage();
			return $this->redirect($this->Auth->redirectUrl());
		}

		$this->NetCommons->setFlashNotification(
			__d('auth', 'Invalid username or password, try again'),
			array(
				'class' => 'danger',
				'interval' => NetCommonsComponent::ALERT_VALIDATE_ERROR_INTERVAL,
			),
			400
		);
	}

/**
 * _exLogin()でログイン後の追加処理
 * (外部認証系プラグインのコントローラでオーバーライトして実装)
 *
 * @return void
 * @see AuthShibbolethController::_exLoggedin() オーバーライト参考
 **/
	protected function _exLoggedin() {
	}

/**
 * ID関連付け (他のサービスを用いてログイン)
 * (外部認証系プラグインのコントローラでオーバーライトして実装)
 *
 * @return CakeResponse
 * @see AuthController::beforeFilter() オーバーライト用のため、ここではアクセスさせない
 * @see AuthShibbolethController::mapping() オーバーライト参考
 **/
	public function mapping() {
		$this->view = 'Auth.Auth/mapping';

		//メールを送れるかどうか
		$this->set('isMailSend', $this->ForgotPass->isMailSendCommon('auth', 'auth'));

		if ($this->request->is('post')) {
			// ログイン
			return $this->_exlogin();
		}
	}

/**
 * IdPによる個人識別番号 取得
 * (外部認証系プラグインのコントローラでオーバーライトして実装)
 *
 * @return string IdPによる個人識別番号
 * @see AuthShibbolethController::_getIdpUserid() オーバーライト参考
 **/
	protected function _getIdpUserid() {
		return null;
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

/**
 * Set nc2 authenticator
 *
 * @return void
 **/
	private function __setNc2Authenticate() {
		if (CakePlugin::loaded('Nc2ToNc3')) {
			$this->Auth->authenticate['Nc2ToNc3.Nc2'] = [];
		}
	}

}
