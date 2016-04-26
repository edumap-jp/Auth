<?php
/**
 * 新規登録Model
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AppModel', 'Model');
App::uses('NetCommonsMail', 'Mails.Utility');
App::uses('NetCommonsTime', 'NetCommons.Utility');

/**
 * 新規登録Model
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Model
 */
class AutoUserRegist extends AppModel {

/**
 * アカウント登録の最終決定(ユーザ自身による確認)
 *
 * @var string
 */
	const CONFIRMATION_USER_OWN = '0';

/**
 * アカウント登録の最終決定(自動で登録する)
 *
 * @var string
 */
	const CONFIRMATION_AUTO_REGIST = '1';

/**
 * アカウント登録の最終決定(管理者の承認が必要)
 *
 * @var string
 */
	const CONFIRMATION_ADMIN_APPROVAL = '2';

/**
 * 自動登録の有無
 *
 * @var bool
 */
	private $__hasAutoUserRegist = null;

/**
 * SiteSettionデータ
 *
 * @var array
 */
	private $__siteSettions = null;

/**
 * テーブル名
 *
 * @var bool
 */
	public $useTable = false;

/**
 * 使用するBehaviors
 *
 * - [Mails.MailQueueBehavior](../../Mails/classes/MailQueueBehavior.html)
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
		$siteSettions = $this->getSiteSetting();

		//入力キーのチェック
		if (Hash::get($siteSettions['AutoRegist.use_secret_key'], '0.value')) {
			$this->validate = Hash::merge($this->validate, array(
				'secret_key' => array(
					'notBlank' => array(
						'rule' => array('notBlank'),
						'message' => sprintf(
							__d('net_commons', 'Please input %s.'), __d('auth', 'Secret key')
						),
						'required' => true
					),
					'equalTo' => array(
						'rule' => array('equalTo', Hash::get($siteSettions['AutoRegist.secret_key'], '0.value')),
						'message' => __d('auth', 'Failed on validation errors. Please check the secret key.'),
						'required' => false
					),
				),
			));
		}

		return parent::beforeValidate($options);
	}

/**
 * SiteSettingデータ取得
 *
 * @return array
 */
	public function getSiteSetting() {
		if ($this->__siteSettions) {
			return $this->__siteSettions;
		}

		$this->loadModels([
			'SiteSetting' => 'SiteManager.SiteSetting',
		]);

		$siteSettions = $this->SiteSetting->getSiteSettingForEdit(
			array('SiteSetting.key' => array(
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

				// ** 自動登録時の入力項目(後で、、、会員項目設定で行う？)

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
				// ** 会員登録メールの件名
				'AutoRegist.mail_subject',
				// ** 会員登録メールの本文
				'AutoRegist.mail_body',
			))
		);

		if (! $siteSettions) {
			$siteSettions['AutoRegist.use_automatic_register'] = array(['value' => '0']);
		}

		$value = Hash::get($siteSettions['AutoRegist.use_automatic_register'], '0.value', false);
		$this->__hasAutoUserRegist = (bool)$value;
		$this->__siteSettions = $siteSettions;

		return $siteSettions;
	}

/**
 * 初期値データの取得
 *
 * @return array
 */
	private function __getDefaultData() {
		$siteSettions = $this->getSiteSetting();
		$userAttributes = $this->getUserAttribures();

		$confirmation = Hash::get($siteSettions['AutoRegist.confirmation'], '0.value');
		$attrId = Hash::extract($userAttributes, '{n}.UserAttribute[key=status]')[0]['id'];
		if ($confirmation === self::CONFIRMATION_USER_OWN) {
			//自分自身による確認
			$pathKey = $attrId . '.UserAttributeChoice.{n}[key=status_3]';
		} elseif ($confirmation === self::CONFIRMATION_AUTO_REGIST) {
			//自動的に承認
			$pathKey = $attrId . '.UserAttributeChoice.{n}[key=status_1]';
		} else {
			//管理者による承認
			$pathKey = $attrId . '.UserAttributeChoice.{n}[key=status_2]';
		}
		$status = Hash::extract($userAttributes, $pathKey)[0]['code'];

		$default = array(
			'User' => array(
				'id' => null,
				'status' => $status,
				'role_key' => Current::read('User.role_key'),
				'timezone' => (new NetCommonsTime())->getSiteTimezone(),
			),
			'UsersLanguage' => array(
				'id' => null,
				'language_id' => Current::read('Language.id'),
			),
		);

		foreach ($userAttributes as $userAttribute) {
			$key = Hash::get($userAttribute, 'UserAttribute.key');
			$editable = Hash::get($userAttribute, 'UserAttributesRole.self_editable');

			if ($editable || in_array($key, ['role_key', 'status'], true)) {
				continue;
			}
			if (! isset($userAttribute['UserAttributeChoice'])) {
				continue;
			}

			if (Hash::get($userAttribute, 'UserAttribute.is_multilingualization')) {
				$model = 'UsersLanguage';
			} else {
				$model = 'User';
			}

			$tmp = array_shift($userAttribute['UserAttributeChoice']);
			$default = Hash::insert($default, $model . '.' . $key, $tmp['code']);
		}

		return $default;
	}

/**
 * UserAttribureデータ取得
 *
 * @return array
 */
	public function getUserAttribures() {
		if ($this->__userAttributes) {
			return $this->__userAttributes;
		}

		//モデルのロード
		$this->loadModels([
			'UserAttribute' => 'UserAttributes.UserAttribute',
		]);

		//SiteSettingデータ取得
		$siteSettions = $this->getSiteSetting();
		Current::write('User.role_key', Hash::get($siteSettions['AutoRegist.role_key'], '0.value'));

		//UserAttributeデータ取得
		$this->__userAttributes = $this->UserAttribute->getUserAttriburesForAutoUserRegist();

		return $this->__userAttributes;
	}

/**
 * Userデータ生成
 *
 * @return array
 */
	public function createUser() {
		$this->loadModels([
			'User' => 'Users.User',
			'UsersLanguage' => 'Users.UsersLanguage',
		]);

		$results = array();
		$langId = Current::read('Language.id');

		$default = $this->__getDefaultData();

		$results['User'] = $this->User->create($default['User'])['User'];
		$results['UsersLanguage'][$langId] =
				$this->UsersLanguage->create($default['UsersLanguage'])['UsersLanguage'];

		return $results;
	}

/**
 * 新規登録機能の利用有無
 *
 * @return bool
 */
	public function hasAutoUserRegist() {
		if (! isset($this->__hasAutoUserRegist)) {
			$this->loadModels([
				'SiteSetting' => 'SiteManager.SiteSetting',
			]);

			$siteSettions = $this->SiteSetting->getSiteSettingForEdit(
				array('SiteSetting.key' => array(
					// * 入会設定
					// ** 自動会員登録を許可する
					'AutoRegist.use_automatic_register',
				))
			);

			if (! $siteSettions) {
				$siteSettions['AutoRegist.use_automatic_register'] = array(['value' => '0']);
			}

			$value = Hash::get($siteSettions['AutoRegist.use_automatic_register'], '0.value', false);
			$this->__hasAutoUserRegist = (bool)$value;
		}

		return $this->__hasAutoUserRegist;
	}

/**
 * キーの入力チェック
 *
 * @param array $data リクエストデータ
 * @return bool
 */
	public function validateSecretKey($data) {
		$this->set($data);
		if (! $this->validates()) {
			return false;
		}
		return true;
	}

/**
 * 新規登録の入力チェック
 *
 * @param array $data リクエストデータ
 * @return bool
 */
	public function validateRequest($data) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		if (! Hash::get($data, 'AutoUserRegist.disclaimer')) {
			$this->invalidate(
				'disclaimer', __d('auth', 'You have not agreed to the terms of use.')
			);
			return false;
		}

		$this->__setValidateRequest();

		$this->User->set($data);
		if (! $this->User->validates()) {
			return false;
		}

		return true;
	}

/**
 * 新規登録の入力チェック用のバリデーションルールをセット
 *
 * @return bool
 */
	private function __setValidateRequest() {
		$default = $this->__getDefaultData();
		$userAttributes = $this->getUserAttribures();
		$this->User->userAttributeData = $userAttributes;

		foreach ($userAttributes as $id => $userAttribute) {
			$key = Hash::get($userAttribute, 'UserAttribute.key');
			$dataTypeKey = $userAttribute['UserAttributeSetting']['data_type_key'];
			$editable = Hash::get($userAttribute, 'UserAttributesRole.self_editable');

			if ($editable) {
				continue;
			}
			if (! isset($userAttribute['UserAttributeChoice'])) {
				continue;
			}

			if (Hash::get($userAttribute, 'UserAttribute.is_multilingualization')) {
				$model = 'UsersLanguage';
			} else {
				$model = 'User';
			}

			//eメールは、確認を含める
			if ($dataTypeKey === DataType::DATA_TYPE_EMAIL) {
				if ($userAttribute['UserAttributeSetting']['required']) {
					$this->$model->validate[$key . '_again']['notBlank'] = array(
						'rule' => array('notBlank'),
						'allowEmpty' => false,
						'message' => sprintf(
							__d('net_commons', 'Please input %s.'), __d('net_commons', 'Re-enter')
						),
						'required' => true,
					);
				}
				$this->$model->validate[$key . '_again']['equalToField'] = array(
					'rule' => array('equalToField', $key),
					'message' => __d('net_commons', 'The input data does not match. Please try again.'),
					'allowEmpty' => false,
					'required' => true,
				);
			}

			//self_editable=falseものに対して、選択肢の初期値セットして登録する
			if (isset($default[$model][$key])) {
				$this->$model->validate[$key]['equalTo'] = array(
					'rule' => array('equalTo', $default[$model][$key]),
					'message' => __d('net_commons', 'Invalid request.'),
					'allowEmpty' => true,
					'required' => false,
				);

				$this->User->userAttributeData[$id] = Hash::insert(
					$this->User->userAttributeData[$id],
					'UserAttributesRole.self_editable',
					true
				);

				$this->User->userAttributeData[$id] = Hash::insert(
					$this->User->userAttributeData[$id],
					'UserAttributeSetting.only_administrator_editable',
					false
				);
			}
		}
	}

/**
 * 新規登録処理
 *
 * @param array $data リクエストデータ
 * @return bool
 */
	public function saveAutoUserRegist($data) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		//トランザクションBegin
		$this->begin();

		$this->__setValidateRequest();

		//Userデータの登録
		$user = $this->User->saveUser($data);
		if (! $user) {
			return false;
		}

		return $user;
	}

/**
 * 新規登録のメール処理
 *
 * @param array $data リクエストデータ
 * @return bool
 */
	public function sendAutoUserRegist($data) {
		$mail = new NetCommonsMail();

		$mail->mailAssignTag->setFixedPhraseSubject($data['subject']);
		$mail->mailAssignTag->setFixedPhraseBody($data['body']);
		$mail->mailAssignTag->assignTags(array('X-URL' => $data['url']));
		$mail->mailAssignTag->initPlugin(Current::read('Language.id'));
		$mail->initPlugin(Current::read('Language.id'));
		$mail->to($data['email']);
		$mail->setFrom(Current::read('Language.id'));

		if (! $mail->sendMailDirect()) {
			throw new InternalErrorException(__d('net_commons', 'SendMail Error'));
		}

		return true;
	}

}
