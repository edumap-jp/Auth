<?php
/**
 * パスワード再発行用Model
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AppModel', 'Model');

/**
 * パスワード再発行用Model
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Model
 */
class ForgotPass extends AppModel {

/**
 * エクスポート用のランダム文字列
 *
 * @var const
 */
	const RANDAMSTR = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!#$%&=-~+*?@_';

/**
 * テーブル名
 *
 * @var mixed
 */
	public $useTable = false;

/**
 * 使用ビヘイビア
 *
 * @var array
 */
	public $actsAs = array(
		'Mails.MailQueue' => array(
		),
	);

/**
 * Called during validation operations, before validation. Please note that custom
 * validation rules can be defined in $validate.
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if validate operation should continue, false to abort
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforevalidate
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		$this->validate = Hash::merge($this->validate, array(
			'email' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'message' => sprintf(__d('net_commons', 'Please input %s.'), __d('auth', 'email')),
					'required' => false
				),
				'email' => array(
					'rule' => array('email'),
					'message' => sprintf(
						__d('net_commons', 'Unauthorized pattern for %s. Please input the data in %s format.'),
						__d('auth', 'email'),
						__d('auth', 'email')
					)
				)
			),
			'authorization_key' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'message' => sprintf(__d('net_commons', 'Please input %s.'), __d('auth', 'Authorization key')),
					'required' => false
				),
			),
		));

		return parent::beforeValidate($options);
	}

/**
 * SiteSettingデータ取得
 *
 * @return array
 */
	public function getSiteSetting() {
		$this->loadModels([
			'SiteSetting' => 'SiteManager.SiteSetting',
		]);

		$siteSettions = $this->SiteSetting->getSiteSettingForEdit(
			array('SiteSetting.key' => array(
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
			))
		);

		if (! $siteSettions) {
			$siteSettions['ForgotPass.use_password_reissue'] = array(['value' => '0']);
		}

		return $siteSettions;
	}

/**
 * パスワード再発行通知処理
 *
 * @param array $data リクエストデータ
 * @return mixed ForgotPassデータ配列
 * @throws InternalErrorException
 */
	public function saveForgotPassowrd($data) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		//トランザクションBegin
		$this->begin();

		//$data['ForgotPass']['key'] = ''; //←無駄だが、セットしないと動かないため。

		//バリデーション
		$this->set($data);
		if (! $this->validates()) {
			return false;
		}

		try {
			$email = trim($data['ForgotPass']['email']);
			//if (! $this->saveQueuePostMail(Current::read('Language.id'), null, null, $email)) {
			//	throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			//}

			$user = $this->User->find('first', array(
				'recursive' => -1,
				'conditions' => array(
					'email' => $email,
				),
			));
			$forgotPass = $this->create(array(
				'user_id' => Hash::get($user, 'User.id', '0'),
				'username' => Hash::get($user, 'User.username'),
				'handlename' => Hash::get($user, 'User.handlename'),
				'authorization_key' => substr(str_shuffle(self::RANDAMSTR), 0, 10),
				'email' => $email
			));

			CakeLog::debug(var_export($forgotPass, true)); //TODO: とりあえず

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return $forgotPass;
	}

/**
 * パスワード再発行処理
 *
 * @param array $data リクエストデータ
 * @return bool
 * @throws InternalErrorException
 */
	public function saveRequestPassowrd($data) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		//トランザクションBegin
		$this->begin();

		//$data['ForgotPass']['key'] = ''; //←無駄だが、セットしないと動かないため。

		$forgotPass = CakeSession::read('ForgotPass');

		//バリデーション
		$this->set($data);
		if (! $this->validates()) {
			return false;
		}
		$data['ForgotPass']['authorization_key'] = trim($data['ForgotPass']['authorization_key']);
		if (! $forgotPass || ! Hash::get($forgotPass, 'user_id') ||
				$data['ForgotPass']['authorization_key'] !== Hash::get($forgotPass, 'authorization_key')) {

			$this->invalidate(
				'authorization_key',
				__d('auth', 'Failed on validation errors. Please check the authorization key.')
			);
			return false;
		}

		try {
			App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
			$passwordHasher = new SimplePasswordHasher();
			$rescuePassowrd = substr(str_shuffle(self::RANDAMSTR), 0, 10);

			CakeLog::debug($rescuePassowrd); //TODO: とりあえず
			$hashRescuePassowrd = $passwordHasher->hash($rescuePassowrd);

			//$email = Hash::get($forgotPass, 'email');
			//if (! $this->saveQueuePostMail(Current::read('Language.id'), null, null, $email)) {
			//	throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			//}

			$this->User->id = Hash::get($forgotPass, 'user_id');
			if (! $this->User->saveField('rescue_password', $hashRescuePassowrd, ['callbacks' => false])) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return true;
	}

/**
 * パスワードチェック
 *
 * @param array $data リクエストデータ
 * @return mixed Userデータ配列
 * @throws InternalErrorException
 */
	public function loginRescuePassowrd($data) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
		$passwordHasher = new SimplePasswordHasher();

		$user = $this->User->find('first', array(
			'recursive' => 0,
			'conditions' => array(
				'User.username' => $data['User']['username'],
				'User.rescue_password' => $passwordHasher->hash($data['User']['password']),
			),
		));

		return Hash::get($user, 'User');
	}

/**
 * パスワード再登録処理
 *
 * @param array $data リクエストデータ
 * @return bool
 * @throws InternalErrorException
 */
	public function savePassowrd($data) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		//トランザクションBegin
		$this->begin();

		//バリデーション
		$this->User->Behaviors->unload('Users.SaveUser');
		$this->User->Behaviors->unload('Files.Attachment');
		$this->User->Behaviors->unload('Users.Avatar');
		$this->User->Behaviors->unload('NetCommons.OriginalKey');

		$this->User->validate = Hash::merge($this->User->validate, array(
			'password' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'message' => sprintf(
						__d('net_commons', 'Please input %s.'), __d('users', 'password')
					),
					'allowEmpty' => false,
					'required' => true,
				),
			),
			'password_again' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'allowEmpty' => false,
					'message' => sprintf(__d('net_commons', 'Please input %s.'), __d('net_commons', 'Re-enter')),
					'required' => true,
				),
			),
		));

		$this->User->set($data);
		if (! $this->User->validates()) {
			$this->validationErrors = Hash::merge(
				$this->validationErrors, $this->User->validationErrors
			);
			return false;
		}

		try {
			//Userデータの登録
			if (! $this->User->save(null, false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return true;
	}

}
