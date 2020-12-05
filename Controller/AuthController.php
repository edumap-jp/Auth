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
App::uses('User', 'Users.Model');

/**
 * 認証Controller
 *
 * @property AuthComponent $Auth
 * @property SessionComponent $Session
 * @property ForgotPass $ForgotPass
 * @property NetCommonsComponent $NetCommons
 * @property User $User
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Controller
 */
class AuthController extends AuthAppController {

/**
 * 使用するComponents
 *
 * @var array
 */
	public $components = array(
		'Security',
	);

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
		'SiteManager.SiteSetting',
		'Topics.Topic',
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
		// @see https://book.cakephp.org/2.0/ja/core-libraries/components/authentication.html#id5
		// 認証ハンドラは $this->Auth->authenticate を使って設定します。
		// コントローラの beforeFilter の中、もしくは $components 配列の中に、 認証ハンドラ()を設定することができます。
		$this->_setNc2Authenticate();

		parent::beforeFilter();
		$this->Auth->allow('login', 'logout');

		$this->Session->delete('AutoUserRegist');
		$this->Session->delete('ForgotPass');

		$this->Auth->authenticate['all']['userModel'] = 'Users.User';
		$this->Auth->authenticate['all']['scope'] = array(
			'User.status' => UserAttributeChoice::STATUS_CODE_ACTIVE
		);
		$this->Auth->authenticate['all']['passwordHasher'] = [
			'className' => 'Simple',
			'hashType' => User::PASSWORD_HASH_TYPE,
		];
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
		// ログイン機能を別ドメインにする変更のために、App.memberUrl を追加して
		// ログイン画面にアクセスしたさいの FullBaseURL と App.memberUrl が違ったら転送する
		$memberUrl = Configure::read('App.memberUrl');
		if ($memberUrl != "" && Router::fullBaseUrl() != $memberUrl) {
			$this->redirect($memberUrl . '/auth/login');
			return;
		}

		//ページタイトル
		$this->set('pageTitle', __d('auth', 'Login'));

		//メールを送れるかどうか
		$this->set('isMailSend', $this->ForgotPass->isMailSendCommon('auth', 'auth'));

		if ($this->request->is('post')) {
			//$this->_setNc2Authenticate();

			if ($this->Auth->login()) {
				ClassRegistry::removeObject('User');
				$this->User = ClassRegistry::init('Users.User');

				$this->User->updateLoginTime($this->Auth->user('id'));
				Current::write('User', $this->Auth->user());
				if ($this->Auth->user('language') !== UserAttributeChoice::LANGUAGE_KEY_AUTO) {
					$this->Session->write('Config.language', $this->Auth->user('language'));
				}
				$this->Auth->loginRedirect = $this->_getDefaultStartPage();

				// Display motivating flash notification.
				// See also `updateMotivatingFlashMessage` in app/Plugin/NetCommons/webroot/js/base.js
				CurrentLibRoom::getInstance()->resetInstance();
				CurrentLibUser::getInstance()->initialize($this);
				CurrentLibRoom::getInstance()->initialize($this);
				$topPageRoomId = CurrentLibPage::getInstance()->findTopPage()['Page']['room_id'];
				$roomRoleKey = CurrentLibRoom::getInstance()->getRoomRoleKeyByRoomId($topPageRoomId);
				$isEditor = $roomRoleKey === Role::ROOM_ROLE_KEY_ROOM_ADMINISTRATOR
					|| $roomRoleKey === Role::ROOM_ROLE_KEY_CHIEF_EDITOR
					|| $roomRoleKey === Role::ROOM_ROLE_KEY_EDITOR;
				if ($isEditor) {
					$date = new DateTime(date('Y-m-d', strtotime("+9 hours")));
					$date->sub(new DateInterval('PT9H'));
					$options = array(
						'conditions' => array(
							'Topic.plugin_key' => array('bbses', 'blogs'),
							'Topic.publish_start >=' => $date->format('Y-m-d H:i:s'),
						),
					);
					$topicCount = $this->Topic->find('count', $this->Topic->getQueryOptions(0, $options));
					$userId = $this->Auth->user('key');
					$params = h(json_encode($userId)) . ',' . h(json_encode($topicCount));
					$this->NetCommons->setFlashNotification(
						'<div ng-init="updateMotivatingFlashMessage(' . $params . ');"></div>',
						array('class' => 'info', 'interval' => 0, 'isDismissed' => true)
					);
				}

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

/**
 * Set nc2 authenticator
 *
 * @return void
 **/
	protected function _setNc2Authenticate() {
		if (CakePlugin::loaded('Nc2ToNc3')) {
			$this->Auth->authenticate['Nc2ToNc3.Nc2'] = [];
		}
	}

}
