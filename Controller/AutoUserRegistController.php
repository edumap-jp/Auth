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
App::uses('NetCommonsMail', 'Mails.Utility');

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
		'Security',
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

		$siteSettions = $this->AutoUserRegist->getSiteSetting();
		$this->set('siteSettions', $siteSettions);

		if (! $this->AutoUserRegist->hasAutoUserRegist()) {
			return $this->setAction('throwBadRequest');
		}

		if (Hash::get($this->params['pass'], 'activate_key')) {
			$this->helpers['NetCommons.Wizard']['navibar'] = Hash::remove(
				$this->helpers['NetCommons.Wizard']['navibar'], self::WIZARD_ENTRY_KEY
			);
		} else {
			//管理者の承認が必要の場合、ウィザードの文言変更
			$value = Hash::get($siteSettions['AutoRegist.confirmation'], '0.value');
			if ($value === AutoUserRegist::CONFIRMATION_ADMIN_APPROVAL) {
				$this->helpers['NetCommons.Wizard']['navibar'] = Hash::insert(
					$this->helpers['NetCommons.Wizard']['navibar'],
					self::WIZARD_COMPLETION . '.label',
					array('auth', 'Complete request registration.')
				);
			}

			//入力キーのチェック
			$value = Hash::get($siteSettions['AutoRegist.use_secret_key'], '0.value');
			if ($value) {
				if (! $this->Session->read('AutoUserRegistKey')) {
					$this->Session->write('AutoUserRegistRedirect', $this->params['action']);
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
			if (! $this->AutoUserRegist->validates()) {
				$this->Session->write('AutoUserRegistKey', true);
				return $this->redirect(
					'/auth/auto_user_regist/' . $this->Session->read('AutoUserRegistRedirect')
				);
			} else {
				$this->NetCommons->handleValidationError($this->AutoUserRegist->validationErrors);
			}
		} else {
			$value = Hash::get($this->viewVars['siteSettions']['AutoRegist.use_secret_key'], '0.value');
			if (! $value) {
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
				$this->Session->write('AutoUserRegist', $user);

				$siteSettings = $this->viewVars['siteSettions'];
				$value = Hash::get($siteSettings['AutoRegist.confirmation'], '0.value');
				$this->__sendMail($value, $user);

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

		$siteSettings = $this->viewVars['siteSettions'];
		$this->request->data = $this->Session->read('AutoUserRegist');

		$value = Hash::get($siteSettings['AutoRegist.confirmation'], '0.value');
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

		$this->request->data = $this->Session->read('AutoUserRegist');
		$this->Session->delete('AutoUserRegist');
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

		if ($this->AutoUserRegist->saveUserStatus($this->request->query, AutoUserRegist::CONFIRMATION_USER_OWN)) {
			$message = __d('auth', 'Thank you for your registration. Click on the link, please login.');
			$this->NetCommons->setFlashNotification($message, array('class' => 'success'));
			return $this->redirect('/auth/auth/login');
		} else {
CakeLog::debug(var_export($this->AutoUserRegist->validationErrors, true));

			$this->view = 'acceptance';
			$message = __d('auth', 'Your registration was not approved.<br>' .
									'Please consult with the system administrator.');
			$options = array('class' => 'alert alert-danger');

			$this->set('message', $message);
			$this->set('redirectUrl', '/');
			$this->set('options', $options);
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
			array('auth', 'Complete request registration.')
		);

		if ($this->AutoUserRegist->saveUserStatus($this->request->query, AutoUserRegist::CONFIRMATION_ADMIN_APPROVAL)) {
			$message = __d('auth', 'Your registration will be confirmed by the system administrator. <br>' .
									'When confirmed, it will be notified by e-mail.');
			$options = array();
		} else {
CakeLog::debug(var_export($this->AutoUserRegist->validationErrors, true));

			$this->view = 'acceptance';
			$message = __d('auth', 'Your registration was not approved.<br>' .
									'Please consult with the system administrator.');
			$options = array('class' => 'alert alert-danger');
		}

		$this->set('message', $message);
		$this->set('redirectUrl', '/');
		$this->set('options', $options);
	}

/**
 * 新規登録のメール処理
 *
 * @param int $confirmation 完了確認ステータス
 * @param array $user ユーザ情報
 * @return bool
 */
	private function __sendMail($confirmation, $user) {
		$siteSettings = $this->viewVars['siteSettions'];
		if ($confirmation === AutoUserRegist::CONFIRMATION_USER_OWN) {
			$data['subject'] = Hash::get(
				$siteSettings['AutoRegist.approval_mail_subject'], Current::read('Language.id') . '.value'
			);
			$data['body'] = Hash::get(
				$siteSettings['AutoRegist.approval_mail_body'], Current::read('Language.id') . '.value'
			);
			$data['email'] = array($user['User']['email']);
			$data['url'] = Configure::read('App.fullBaseUrl') . '/auth/auto_user_regist/approval' .
						$user['User']['activate_parameter'];

		} elseif ($confirmation === AutoUserRegist::CONFIRMATION_ADMIN_APPROVAL) {
			$data['subject'] = Hash::get(
				$siteSettings['AutoRegist.acceptance_mail_subject'], Current::read('Language.id') . '.value'
			);
			$data['body'] = Hash::get(
				$siteSettings['AutoRegist.acceptance_mail_body'], Current::read('Language.id') . '.value'
			);
			$data['email'] = $this->__getMailAddressForAdmin();
			$data['url'] = Configure::read('App.fullBaseUrl') . '/auth/auto_user_regist/acceptance' .
						$user['User']['activate_parameter'];
		} else {
			return true;
		}

		$mail = new NetCommonsMail();

		foreach ($data['email'] as $email) {
			$mail->mailAssignTag->setFixedPhraseSubject($data['subject']);
			$mail->mailAssignTag->setFixedPhraseBody($data['body']);
			$mail->mailAssignTag->assignTags(array('X-URL' => $data['url']));
			$mail->mailAssignTag->initPlugin(Current::read('Language.id'));
			$mail->initPlugin(Current::read('Language.id'));
			$mail->to($email);
			$mail->setFrom(Current::read('Language.id'));

			if (! $mail->sendMailDirect()) {
				//throw new InternalErrorException(__d('net_commons', 'SendMail Error'));
			}
		}

		return true;
	}

/**
 * 管理者ユーザのメールアドレス取得
 * ここでいう管理者権限とは、会員管理が使える権限のこと。
 *
 * @return array
 */
	private function __getMailAddressForAdmin() {
		$roleKeys = $this->PluginsRole->find('list', array(
			'recursive' => -1,
			'fields' => array('id', 'role_key'),
			'conditions' => array(
				'plugin_key' => 'user_manager',
			),
		));

		//その他のメールアドレスも含める必要あり
		$mails = $this->User->find('all', array(
			'recursive' => -1,
			'fields' => array('email'),
			'conditions' => array(
				'role_key' => $roleKeys,
			),
		));

		$result = array();
		foreach ($mails as $mail) {
			$result = array_merge($result, Hash::extract($mail, '{s}.{s}'));
		}

		return $result;
	}

}
