<?php
/**
 * パスワード再発行Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AuthAppController', 'Auth.Controller');
App::uses('NetCommonsMail', 'Mails.Utility');
App::uses('SiteSettingUtil', 'SiteManager.Utility');

/**
 * パスワード再発行Controller
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Controller
 */
class ForgotPassController extends AuthAppController {

/**
 * ウィザード定数(再発行の受付)
 *
 * @var string
 */
	const WIZARD_REQUEST = 'request';

/**
 * ウィザード定数(再発行受付確認画面)
 *
 * @var string
 */
	const WIZARD_CONFIRM = 'confirm';

/**
 * ウィザード定数(新しいパスワード登録)
 *
 * @var string
 */
	const WIZARD_UPDATE = 'update';

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
 * - [Auth.ForgotPass](../../Auth/classes/ForgotPass.html)
 * - [Users.User](../../Users/classes/User.html)
 *
 * @var array
 */
	public $uses = array(
		'Auth.ForgotPass',
		'Users.User',
	);

/**
 * 使用するHelpers
 *
 * - [NetCommons.Wizard](../../NetCommons/classes/WizardHelper.html)
 *
 * @var array
 */
	public $helpers = array(
		'NetCommons.Wizard' => array(
			'navibar' => array(
				self::WIZARD_REQUEST => array(
					'url' => array(
						'controller' => 'forgot_pass', 'action' => 'request',
					),
					'label' => array('auth', 'Forgot your Password?'),
				),
				self::WIZARD_CONFIRM => array(
					'url' => array(
						'controller' => 'forgot_pass', 'action' => 'confirm',
					),
					'label' => array('auth', 'Authorization key confirm?'),
				),
				self::WIZARD_UPDATE => array(
					'url' => array(
						'controller' => 'forgot_pass', 'action' => 'update',
					),
					'label' => array('auth', 'Entry new password'),
				),
			),
			'cancelUrl' => array('controller' => 'auth', 'action' => 'login')
		),
	);

/**
 * beforeFilter
 *
 * @return void
 **/
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('request', 'confirm', 'update');

		SiteSettingUtil::setup(array(
			// ** パスワード再発行を使う
			'ForgotPass.use_password_reissue',
			// ** 新規パスワード通知の件名
			'ForgotPass.issue_mail_subject',
			// ** パスワード通知メールの本文
			'ForgotPass.issue_mail_body',
			// ** 新規パスワード発行の件名
			'ForgotPass.request_mail_subject',
			// ** パスワード発行メールの本文
			'ForgotPass.request_mail_body',
		));

		if (! SiteSettingUtil::read('ForgotPass.use_password_reissue', '0')) {
			return $this->setAction('throwBadRequest');
		}
	}

/**
 * パスワード再発行の受付
 *
 * @return void
 **/
	public function request() {
		if ($this->request->is('post')) {
			$forgotPass = $this->ForgotPass->validateRequest($this->request->data);
			if ($forgotPass) {
				$forgotPass = $forgotPass['ForgotPass'];
				$this->Session->write('ForgotPass', $forgotPass);

				//対象のユーザがいる場合は、メール送る。
				//成否を出すと、悪意ある人がやった場合、メールアドレスがバレてしまうため、送ったことにする。
				if (Hash::get($forgotPass, 'user_id')) {
					$mail = new NetCommonsMail();

					$mail->mailAssignTag->setFixedPhraseSubject(
						SiteSettingUtil::read('ForgotPass.issue_mail_subject')
					);
					$mail->mailAssignTag->setFixedPhraseBody(
						SiteSettingUtil::read('ForgotPass.issue_mail_body')
					);
					$mail->mailAssignTag->assignTags(array(
						'X-AUTHORIZATION_KEY' => Hash::get($forgotPass, 'authorization_key'),
					));
					$mail->mailAssignTag->initPlugin(Current::read('Language.id'));
					$mail->initPlugin(Current::read('Language.id'));

					$mail->to(Hash::get($forgotPass, 'email'));
					$mail->setFrom(Current::read('Language.id'));
					if (! $mail->sendMailDirect()) {
						return $this->NetCommons->handleValidationError(array('SendMail Error'));
					}
				}

				$this->NetCommons->setFlashNotification(
					__d('auth',
						'We have sent you the key to obtain a new password to your registered e-mail address.'),
					array('class' => 'success')
				);

				return $this->redirect('/auth/forgot_pass/confirm');
			}
			$this->NetCommons->handleValidationError($this->ForgotPass->validationErrors);
		}
	}

/**
 * パスワード再発行
 *
 * @return void
 **/
	public function confirm() {
		if ($this->request->is('post')) {
			if ($this->ForgotPass->validateAuthorizationKey($this->request->data)) {
				$forgotPass = $this->Session->read('ForgotPass');

				$mail = new NetCommonsMail();

				$mail->mailAssignTag->setFixedPhraseSubject(
					SiteSettingUtil::read('ForgotPass.request_mail_subject')
				);
				$mail->mailAssignTag->setFixedPhraseBody(
					SiteSettingUtil::read('ForgotPass.request_mail_body')
				);
				$mail->mailAssignTag->assignTags(array(
					'X-HANDLENAME' => $forgotPass['handlename'],
					'X-USERNAME' => $forgotPass['username'],
				));
				$mail->mailAssignTag->initPlugin(Current::read('Language.id'));
				$mail->initPlugin(Current::read('Language.id'));

				$mail->to(Hash::get($forgotPass, 'email'));
				$mail->setFrom(Current::read('Language.id'));
				if (! $mail->sendMailDirect()) {
					return $this->NetCommons->handleValidationError(array('SendMail Error'));
				}

				$this->NetCommons->setFlashNotification(
					__d('auth', 'We have sent your login id to your registered e-mail address.'),
					array('class' => 'success')
				);
				return $this->redirect('/auth/forgot_pass/update');
			}
			$this->NetCommons->handleValidationError($this->ForgotPass->validationErrors);
		}
	}

/**
 * パスワード登録
 *
 * @return void
 **/
	public function update() {
		if ($this->request->is('put')) {
			if ($this->ForgotPass->savePassowrd($this->request->data)) {
				$this->NetCommons->setFlashNotification(
					__d('net_commons', 'Successfully saved.'), array('class' => 'success')
				);
				$this->Session->delete('ForgotPass');

				$this->Auth->authenticate['all']['scope'] = array(
					'User.status' => '1'
				);
				if ($this->Auth->login()) {
					$this->User->updateLoginTime($this->Auth->user('id'));
					Current::write('User', $this->Auth->user());
					$this->Auth->loginRedirect = $this->SiteSetting->getDefaultStartPage();
					return $this->redirect($this->Auth->redirect());
				}

				return $this->redirect('/auth/auth/login');
			}

			$this->NetCommons->handleValidationError($this->ForgotPass->validationErrors);
		} else {
			$this->request->data['User']['id'] = $this->Session->read('ForgotPass.user_id');
		}
	}
}
