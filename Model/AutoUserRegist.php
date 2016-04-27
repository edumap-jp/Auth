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
App::uses('NetCommonsTime', 'NetCommons.Utility');

/**
 * 新規登録Model
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Model
 */
class AutoUserRegist extends AppModel {

/**
 * 認証キー用のランダム文字列
 *
 * @var const
 */
	const RANDAMSTR = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

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
 * ユーザステータス(利用不可)
 *
 * @var string
 */
	const STATUS_KEY_NOT_AVAILABLE = 'status_0';

/**
 * ユーザステータス(公開)
 *
 * @var string
 */
	const STATUS_KEY_AVAILABLE = 'status_1';

/**
 * ユーザステータス(管理者の承認待ち)
 *
 * @var string
 */
	const STATUS_KEY_WAIT_ACCEPTANCE = 'status_2';

/**
 * ユーザステータス(本人の確認待ち)
 *
 * @var string
 */
	const STATUS_KEY_WAIT_APPROVAL = 'status_3';

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
	private function __getUserStatusCode($statusKey) {
		$siteSettions = $this->getSiteSetting();
		$userAttributes = $this->getUserAttribures();

		$attrId = Hash::extract($userAttributes, '{n}.UserAttribute[key=status]')[0]['id'];
		$pathKey = $attrId . '.UserAttributeChoice.{n}[key=' . $statusKey . ']';
		$status = Hash::extract($userAttributes, $pathKey)[0]['code'];

		return $status;
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
		if ($confirmation === self::CONFIRMATION_USER_OWN) {
			//自分自身による確認
			$statusKey = self::STATUS_KEY_WAIT_APPROVAL;
		} elseif ($confirmation === self::CONFIRMATION_AUTO_REGIST) {
			//自動的に承認
			$statusKey = self::STATUS_KEY_AVAILABLE;
		} else {
			//管理者による承認
			$statusKey = self::STATUS_KEY_WAIT_ACCEPTANCE;
		}
		$status = $this->__getUserStatusCode($statusKey);

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

		try {
			$this->__setValidateRequest();

			//Userデータの登録
			$user = $this->User->saveUser($data);
			if (! $user) {
				return false;
			}

			//アクティベートキーの登録
			$result = $this->saveActivateKey($user['User']['id']);
			$user['User']['activate_key'] = $result['activate_key'];
			$user['User']['activated'] = $result['activated'];
			$user['User']['activate_parameter'] = $result['activate_parameter'];

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return $user;
	}

/**
 * アクティベートキーを登録する
 *
 * @param int $userId ユーザID
 * @return array アクティベートキーとアクティベート日時
 */
	public function saveActivateKey($userId) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		//トランザクションBegin
		$this->begin();

		try {
			//登録処理
			$this->User->id = $userId;

			$activateKey = substr(str_shuffle(self::RANDAMSTR), 0, 10);
			if (! $this->User->saveField('activate_key', $activateKey, ['callbacks' => false])) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			$activated = date('Y-m-d H:i:s');
			if (! $this->User->saveField('activated', $activated, ['callbacks' => false])) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		$parameter = '?id=' . $userId .
						'&activate_key=' . $activateKey .
						'&timestamp=' . strtotime($activated) .
						'&' . Security::hash($userId . $activateKey, 'md5', true);
		return array(
			'activate_key' => $activateKey,
			'activated' => $activated,
			'activate_parameter' => $parameter
		);
	}

/**
 * ステータスを更新する
 *
 * @param array $data リクエストデータ
 * @param int $status ステータス
 * @return bool|array バリデーションエラーの場合、falseを返す。<br>
 * 正常の場合で、管理者の承認有無は、本人の登録確認のためのユーザ情報を返す。<br>
 * また、本人の登録確認の場合、trueを返す。
 */
	public function saveUserStatus($data, $status) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		//トランザクションBegin
		$this->begin();

		$userId = Hash::get($data, 'id');
		$data = Hash::remove($data, 'id');

		$activateKey = Hash::get($data, 'activate_key');
		$data = Hash::remove($data, 'activate_key');

		$activateTime = date('Y-m-d H:i:s', Hash::get($data, 'timestamp'));
		$data = Hash::remove($data, 'timestamp');

		$hash = array_keys($data)[0];

		$user = $this->__validateUserStatus($userId, $status, $activateKey, $activateTime, $hash);
		if (! $user) {
			return false;
		}

		//ステータスチェック
		if ($status === self::CONFIRMATION_USER_OWN) {
			//自分自身による確認
			$updateStatus = $this->__getUserStatusCode(self::STATUS_KEY_AVAILABLE);
		} elseif ($status === self::CONFIRMATION_ADMIN_APPROVAL) {
			//管理者による承認
			$updateStatus = $this->__getUserStatusCode(self::STATUS_KEY_WAIT_APPROVAL);
		}

		try {
			//登録処理
			$this->User->id = $userId;

			$result = $this->User->saveField('status', $updateStatus, ['callbacks' => false]);
			if (! $result) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}
			$result = $this->User->saveField('activate_key', '', ['callbacks' => false]);
			if (! $result) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}
			$result = $this->User->saveField('activated', null, ['callbacks' => false]);
			if (! $result) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			if ($status === self::CONFIRMATION_ADMIN_APPROVAL) {
				$result = $this->saveActivateKey($userId);
			}

			//トランザクションCommit
			$this->commit();

		} catch (Exception $ex) {
			//トランザクションRollback
			$this->rollback($ex);
		}

		return $result;
	}

/**
 * メールでの承認バリデーション
 *
 * @return bool|array ユーザ情報 or エラー
 */
	private function __validateUserStatus($userId, $status, $activateKey, $activateTime, $hash) {
		//改竄チェック
		if (Security::hash($userId . $activateKey, 'md5', true) !== $hash) {
			//不正アクセスエラー
			$this->invalidate('bad_request1', __d('net_commons', 'Bad Request'));
			return false;
		}

		//ユーザデータ取得
		$user = $this->User->find('first', array(
			'recursive' => -1,
			'conditions' => array('id' => $userId),
		));

		if (! $user) {
			//既に削除されたエラー
			$this->invalidate('deletedError',
				__d('auth', 'The member was cancelled out.')
			);
			return false;
		}

		//ステータスチェック
		if ($status === self::CONFIRMATION_USER_OWN) {
			//自分自身による確認
			$statusKey = self::STATUS_KEY_WAIT_APPROVAL;
			$approvedError = array(
				$this->__getUserStatusCode(self::STATUS_KEY_AVAILABLE)
			);
		} elseif ($status === self::CONFIRMATION_ADMIN_APPROVAL) {
			//管理者による承認
			$statusKey = self::STATUS_KEY_WAIT_ACCEPTANCE;
			$approvedError = array(
				$this->__getUserStatusCode(self::STATUS_KEY_WAIT_APPROVAL),
				$this->__getUserStatusCode(self::STATUS_KEY_AVAILABLE)
			);
		} else {
			//不正アクセスエラー
			$this->invalidate('bad_request2', __d('net_commons', 'Bad Request'));
			return false;
		}

		if ($user['User']['status'] === $this->__getUserStatusCode($statusKey) &&
				$user['User']['activated'] === $activateTime) {
			//OK
		} elseif (in_array($user['User']['status'], $approvedError, true)) {
			//既に承認済みエラー
			$this->invalidate('approvedError',
				__d('auth', 'Selected account is already activated!')
			);
			return false;
		} else {
			//不正アクセスエラー
			$this->invalidate('bad_request3', __d('net_commons', 'Bad Request'));
			return false;
		}

		return $user;
	}

}
