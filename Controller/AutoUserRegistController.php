<?php
/**
 * 新規登録Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AuthAppController', 'Auth.Controller');

/**
 * 新規登録Controller
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Controller
 */
class AutoUserRegistController extends AuthAppController {

/**
 * ウィザード定数(新規登録キーの入力)
 *
 * @var string
 */
	const WIZARD_ENTRY_KEY = 'entry_key';

/**
 * ウィザード定数(新規登録の受付)
 *
 * @var string
 */
	const WIZARD_REQUEST = 'request';

/**
 * ウィザード定数(新規登録の確認)
 *
 * @var string
 */
	const WIZARD_CONFIRM = 'confirm';

/**
 * ウィザード定数(新規登録の完了)
 *
 * @var string
 */
	const WIZARD_COMPLETION = 'completion';

/**
 * 使用するComponents
 *
 * - [SecurityComponent](http://book.cakephp.org/2.0/ja/core-libraries/components/security-component.html)
 *
 * @var array
 */
	public $components = array(
		'Security' => array(
			'csrfCheck' => false, //暫定対処
		),
	);

/**
 * 使用するModels
 *
 * - [Auth.AutoUserRegist](../../Auth/classes/AutoUserRegist.html)
 * - [Users.User](../../Users/classes/User.html)
 *
 * @var array
 */
	public $uses = array(
		'Auth.AutoUserRegist',
		'Auth.AutoUserRegistMail',
		'PluginManager.PluginsRole',
		'Users.User',
	);

/**
 * 使用するHelpers
 *
 * - [Auth.AutoUserRegistForm](../../Auth/classes/AutoUserRegistForm.html)
 * - [NetCommons.Wizard](../../NetCommons/classes/WizardHelper.html)
 *
 * @var array
 */
	public $helpers = array(
		'Auth.AutoUserRegistForm',
		'NetCommons.Wizard' => array(
			'navibar' => array(
				self::WIZARD_ENTRY_KEY => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'entry_key',
					),
					'label' => array('auth', 'Entry secret key?'),
				),
				self::WIZARD_REQUEST => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'request',
					),
					'label' => array('auth', 'Registration?'),
				),
				self::WIZARD_CONFIRM => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'confirm',
					),
					'label' => array('auth', 'Entry confirm.'),
				),
				self::WIZARD_COMPLETION => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'update',
					),
					'label' => array('auth', 'Complete registration.'),
				),
			),
			'cancelUrl' => null
		),
	);

/**
 * beforeFilter
 *
 * @return void
 **/
	public function beforeFilter() {
		parent::beforeFilter();

		$this->Auth->allow('entry_key', 'request', 'confirm', 'completion', 'approval', 'acceptance');

		//ページタイトル
		$this->set('pageTitle', __d('auth', 'Sign up'));

		SiteSettingUtil::setup(array(
			// * 入会設定
			// ** 自動会員登録を許可する
			'AutoRegist.use_automatic_register',
			// ** アカウント登録の最終決定
			'AutoRegist.confirmation',
			// ** 入力キーの使用
			'AutoRegist.use_secret_key',
			// ** 入力キー
			'AutoRegist.secret_key',
			// ** 自動登録時の権限
			'AutoRegist.role_key',
			// ** 自動登録時にデフォルトルームに参加する
			'AutoRegist.prarticipate_default_room',

			// ** 利用許諾文
			'AutoRegist.disclaimer',
			// ** 会員登録承認メールの件名
			'AutoRegist.approval_mail_subject',
			// ** 会員登録承認メールの本文
			'AutoRegist.approval_mail_body',
			// ** 会員登録受付メールの件名
			'AutoRegist.acceptance_mail_subject',
			// ** 会員登録受付メールの本文
			'AutoRegist.acceptance_mail_body',
		));

		if (! SiteSettingUtil::read('AutoRegist.use_automatic_register', false)) {
			return $this->setAction('throwBadRequest');
		}

		if (Hash::get($this->params['pass'], 'activate_key')) {
			$this->helpers['NetCommons.Wizard']['navibar'] = Hash::remove(
				$this->helpers['NetCommons.Wizard']['navibar'], self::WIZARD_ENTRY_KEY
			);
		} else {
			//管理者の承認が必要の場合、ウィザードの文言変更
			$value = SiteSettingUtil::read('AutoRegist.confirmation');
			if ($value === AutoUserRegist::CONFIRMATION_ADMIN_APPROVAL) {
				$this->helpers['NetCommons.Wizard']['navibar'] = Hash::insert(
					$this->helpers['NetCommons.Wizard']['navibar'],
					self::WIZARD_COMPLETION . '.label',
					array('auth', 'Complete request registration.')
				);
			}

			//入力キーのチェック
			$value = SiteSettingUtil::read('AutoRegist.use_secret_key');
			if ($value) {
				if (! $this->Session->read('AutoUserRegistKey')) {
					if ($this->params['action'] === 'entry_key') {
						$this->Session->delete('AutoUserRegistKey');
						$this->Session->write('AutoUserRegistRedirect', 'request');
					} else {
						$this->Session->write('AutoUserRegistRedirect', $this->params['action']);
					}
					$this->setAction('entry_key');
				}
			} else {
				$this->helpers['NetCommons.Wizard']['navibar'] = Hash::remove(
					$this->helpers['NetCommons.Wizard']['navibar'], self::WIZARD_ENTRY_KEY
				);
			}
		}
	}

/**
 * キーの入力
 *
 * @return void
 **/
	public function entry_key() {
		if ($this->request->is('post')) {
			$this->AutoUserRegist->set($this->request->data);
			if ($this->AutoUserRegist->validates()) {
				$this->Session->write('AutoUserRegistKey', true);
				return $this->redirect(
					'/auth/auto_user_regist/' . $this->Session->read('AutoUserRegistRedirect')
				);
			} else {
				$this->NetCommons->handleValidationError($this->AutoUserRegist->validationErrors);
			}
		} else {
			if (! SiteSettingUtil::read('AutoRegist.use_secret_key')) {
				return $this->redirect(
					'/auth/auto_user_regist/' . $this->Session->read('AutoUserRegistRedirect')
				);
			}
		}
	}

/**
 * 新規登録の受付
 *
 * @return void
 **/
	public function request() {
		if ($this->request->is('post')) {
			if ($this->AutoUserRegist->validateRequest($this->request->data)) {
				$this->Session->write('AutoUserRegist', $this->request->data);
				return $this->redirect('/auth/auto_user_regist/confirm');
			} else {
				$this->NetCommons->handleValidationError($this->AutoUserRegist->validationErrors);
			}
		} else {
			if ($this->Session->read('AutoUserRegist')) {
				$this->request->data = $this->Session->read('AutoUserRegist');
			} else {
				$this->request->data = $this->AutoUserRegist->createUser();
			}
		}

		$userAttributes = $this->AutoUserRegist->getUserAttribures();
		$this->set('userAttributes', $userAttributes);
	}

/**
 * 新規登録の確認
 *
 * @return void
 **/
	public function confirm() {
		$this->request->data = $this->Session->read('AutoUserRegist');

		if ($this->request->is('post')) {
			$user = $this->AutoUserRegist->saveAutoUserRegist($this->request->data);
			if ($user) {
				$user = Hash::merge($this->request->data, Hash::remove($user, 'User.password'));
				$this->Session->write('AutoUserRegist', Hash::remove($this->request->data, 'User.password'));

				//メール送信
				$this->AutoUserRegistMail->sendMail(SiteSettingUtil::read('AutoRegist.confirmation'), $user);

				return $this->redirect('/auth/auto_user_regist/completion');
			} else {
				$this->view = 'request';
				$this->NetCommons->handleValidationError($this->AutoUserRegist->validationErrors);
			}
		}

		$userAttributes = $this->AutoUserRegist->getUserAttribures();
		$this->set('userAttributes', $userAttributes);
	}

/**
 * 新規登録の完了
 *
 * @return void
 **/
	public function completion() {
		//ウィザードのリンク削除
		$this->helpers['NetCommons.Wizard']['navibar'] = Hash::remove(
			$this->helpers['NetCommons.Wizard']['navibar'],
			'{s}.url'
		);

		$value = SiteSettingUtil::read('AutoRegist.confirmation');
		if ($value === AutoUserRegist::CONFIRMATION_USER_OWN) {
			$message = __d('auth', 'Confirmation e-mail will be sent to the registered address, ' .
								'after the system administrator approve your registration.');
			$redirectUrl = '/';
		} elseif ($value === AutoUserRegist::CONFIRMATION_AUTO_REGIST) {
			$message = __d('auth', 'Thank you for your registration. Click on the link, please login.');
			$redirectUrl = '/auth/auth/login';
		} else {
			$message = __d('auth', 'Your registration will be confirmed by the system administrator. <br>' .
								'When confirmed, it will be notified by e-mail.');
			$redirectUrl = '/';
		}
		$this->set('message', $message);
		$this->set('redirectUrl', $redirectUrl);

		$userAttributes = $this->AutoUserRegist->getUserAttribures();
		$this->set('userAttributes', $userAttributes);

		if ($this->Session->read('AutoUserRegist')) {
			$this->request->data = $this->Session->read('AutoUserRegist');
			$this->Session->delete('AutoUserRegist');
		} else {
			$this->request->data = array();
		}
	}

/**
 * 本人の登録確認
 *
 * @return void
 **/
	public function approval() {
		//ウィザードのリンク削除
		$this->helpers['NetCommons.Wizard']['navibar'] = Hash::remove(
			$this->helpers['NetCommons.Wizard']['navibar'],
			'{s}.url'
		);

		$result = $this->AutoUserRegist->saveUserStatus(
			$this->request->query,
			AutoUserRegist::CONFIRMATION_USER_OWN
		);
		if ($result) {
			$message = __d('auth', 'Thank you for your registration. Click on the link, please login.');
			$this->NetCommons->setFlashNotification($message, array('class' => 'success'));
			return $this->redirect('/auth/auth/login');
		} else {
			$this->view = 'acceptance';
			return $this->__setValidationError();
		}
	}

/**
 * 管理者の承認確認
 *
 * @return void
 **/
	public function acceptance() {
		//ウィザードのリンク削除
		$this->helpers['NetCommons.Wizard']['navibar'] = Hash::remove(
			$this->helpers['NetCommons.Wizard']['navibar'],
			'{s}.url'
		);

		$this->helpers['NetCommons.Wizard']['navibar'] = Hash::insert(
			$this->helpers['NetCommons.Wizard']['navibar'],
			self::WIZARD_COMPLETION . '.label',
			array('auth', 'Approval completion.')
		);

		$result = $this->AutoUserRegist->saveUserStatus(
			$this->request->query,
			AutoUserRegist::CONFIRMATION_ADMIN_APPROVAL
		);
		if ($result) {
			$message = __d('auth', 'hank you for your registration.<br>' .
							'We have sent you the registration key to your registered e-mail address.');
			$this->set('message', $message);
			$this->set('options', array());
			$this->set('redirectUrl', '/');

			$user = $this->User->find('first', array(
				'recursive' => -1,
				'conditions' => array('id' => $this->request->query['id'])
			));
			$user = Hash::merge($user, $result);
			$this->AutoUserRegistMail->sendMail(AutoUserRegist::CONFIRMATION_USER_OWN, $user);

		} else {
			$this->view = 'acceptance';
			return $this->__setValidationError();
		}
	}

/**
 * バリデーションエラーメッセージのセット
 *
 * @return void
 */
	private function __setValidationError() {
		$errorKey = array_keys($this->AutoUserRegist->validationErrors)[0];
		if ($errorKey === AutoUserRegist::INVALIDATE_BAD_REQUEST) {
			//不正リクエスト
			return $this->throwBadRequest();
		} elseif ($errorKey === AutoUserRegist::INVALIDATE_CANCELLED_OUT ||
					$errorKey === AutoUserRegist::INVALIDATE_ALREADY_ACTIVATED) {
			//既に削除された or 既に承認済み
			$message = array_shift($this->AutoUserRegist->validationErrors[$errorKey]);
			$options = array('class' => 'alert alert-warning');
		} else {
			//それ以外
			$message = __d('auth', 'Your registration was not approved.<br>' .
									'Please consult with the system administrator.');
			$options = array('class' => 'alert alert-danger');
		}

		$this->set('redirectUrl', '/');
		$this->set('message', $message);
		$this->set('options', $options);
	}

}
