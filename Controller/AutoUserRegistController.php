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
		$this->Auth->allow('entry_key', 'request', 'confirm', 'completion');

		$siteSettions = $this->AutoUserRegist->getSiteSetting();
		$this->set('siteSettions', $siteSettions);

		if (! $this->AutoUserRegist->hasAutoUserRegist()) {
			return $this->setAction('throwBadRequest');
		}

		//管理者の承認が必要の場合、ウィザードの文言変更
		$value = Hash::get($siteSettions['AutoRegist.confirmation'], '0.value');
		if ($value === AutoUserRegist::CONFIRMATION_ADMIN_APPROVAL) {
			$keyPath = self::WIZARD_COMPLETION . '.label';
			$this->helpers['NetCommons.Wizard']['navibar'] = Hash::insert(
				$this->helpers['NetCommons.Wizard']['navibar'],
				$keyPath,
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

/**
 * キーの入力
 *
 * @return void
 **/
	public function entry_key() {
		if ($this->request->is('post')) {
			if ($this->AutoUserRegist->validateSecretKey($this->request->data)) {
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
CakeLog::debug(var_export($this->request->data, true));
CakeLog::debug(var_export($user, true));
			if ($user) {
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
		$siteSettings = $this->viewVars['siteSettions'];

		$value = Hash::get($siteSettings['AutoRegist.confirmation'], '0.value');
		if ($value === AutoUserRegist::CONFIRMATION_USER_OWN) {
			$message = __d('auth', 'Thank you for your registration. Click on the link, please login.');
			$url = '/auth/auth/login';
		} elseif ($value === AutoUserRegist::CONFIRMATION_AUTO_REGIST) {
			$message = __d('auth', 'Confirmation e-mail will be sent to the registered address, ' .
								'after the system administrator approve your registration.');
			$url = '/';

			$data['subject'] = Hash::get($siteSettings['AutoRegist.approval_mail_subject'], '0.value');
			$data['body'] = Hash::get($siteSettings['AutoRegist.approval_mail_body'], '0.value');
		} else {
			$message = __d('auth', 'Your registration will be confirmed by the system administrator. <br>' .
								'When confirmed, it will be notified by e-mail.');
			$url = '/';

			$data['subject'] = Hash::get($siteSettings['AutoRegist.acceptance_mail_subject'], '0.value');
			$data['body'] = Hash::get($siteSettings['AutoRegist.acceptance_mail_body'], '0.value');


		}
		$this->set('message', $message);
		$this->set('url', $url);

		$userAttributes = $this->AutoUserRegist->getUserAttribures();
		$this->set('userAttributes', $userAttributes);

		$this->request->data = $this->Session->read('AutoUserRegist');
		$this->Session->delete('AutoUserRegist');
	}
}
