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
 * バリデーションエラーのキー(不正リクエスト)
 *
 * @var string
 */
	const INVALIDATE_BAD_REQUEST = 'bad_request';

/**
 * バリデーションエラーのキー(既に承認済み)
 *
 * @var string
 */
	const INVALIDATE_ALREADY_ACTIVATED = 'already_activated';

/**
 * バリデーションエラーのキー(既に削除された)
 *
 * @var string
 */
	const INVALIDATE_CANCELLED_OUT = 'cancelled_out';

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
		'Mails.MailQueue' => array(),
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
		//入力キーのチェック
		if (SiteSettingUtil::read('AutoRegist.use_secret_key')) {
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
						'rule' => array('equalTo', SiteSettingUtil::read('AutoRegist.secret_key')),
						'message' => __d('auth', 'Failed on validation errors. Please check the secret key.'),
						'required' => true
					),
				),
			));
		}

		return parent::beforeValidate($options);
	}

/**
 * model名(UserもしくはUsersLanguage)の取得
 *
 * @param array $userAttribute 会員項目データ
 * @return string model名(UserもしくはUsersLanguage)
 */
	private function __getModel($userAttribute) {
		if (Hash::get($userAttribute, 'UserAttribute.is_multilingualization')) {
			$model = 'UsersLanguage';
		} else {
			$model = 'User';
		}

		return $model;
	}

/**
 * 初期値データの取得
 *
 * @param string $statusKey ステータスのキー
 * @return string ステータス値
 */
	private function __getUserStatusCode($statusKey) {
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
		$userAttributes = $this->getUserAttribures();

		$confirmation = SiteSettingUtil::read('AutoRegist.confirmation');
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

			$model = $this->__getModel($userAttribute);

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
		Current::write('User.role_key', SiteSettingUtil::read('AutoRegist.role_key'));

		//UserAttributeデータ取得
		$this->__userAttributes = $this->UserAttribute->getUserAttriburesForAutoUserRegist(
			array(
				'OR' => array(
					'UserAttributeSetting.user_attribute_key' => array(
						UserAttribute::LOGIN_ID_FIELD,
						UserAttribute::PASSWORD_FIELD,
						UserAttribute::EMAIL_FIELD
					),
					array(
						'UserAttributeSetting.required' => true,
						'UserAttributeSetting.data_type_key' => array(
							DataType::DATA_TYPE_CHECKBOX,
							DataType::DATA_TYPE_RADIO,
							DataType::DATA_TYPE_SELECT,
						),
					),
					array(
						'UserAttributeSetting.required' => true,
						'UserAttributeSetting.only_administrator_editable' => false,
					),
					'UserAttributeSetting.auto_regist_display' => true,
				)
			)
		);

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
 * 新規登録の入力チェック
 *
 * @param array $data リクエストデータ
 * @return bool
 */
	public function validateRequest($data) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		$return = true;
		if (! Hash::get($data, 'AutoUserRegist.disclaimer')) {
			$this->invalidate(
				'disclaimer', __d('auth', 'You have not agreed to the terms of use.')
			);
			$return = false;
		}

		$this->__setValidateRequest();

		$this->User->set($data);
		if (! $this->User->validates()) {
			$this->validationErrors = Hash::merge(
				$this->validationErrors, $this->User->validationErrors
			);
			return false;
		} else {
			return $return;
		}
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

			$model = $this->__getModel($userAttribute);

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
			$user = Hash::merge($user, $result);

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
			$activateKey = substr(str_shuffle(self::RANDAMSTR), 0, 10);
			$activated = date('Y-m-d H:i:s');
			$update = array(
				'id' => $userId,
				'activate_key' => $activateKey,
				'activated' => $activated,
			);

			//登録処理
			$this->__saveUser($update);

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
		return array('User' => array(
			'activate_key' => $activateKey,
			'activated' => $activated,
			'activate_parameter' => $parameter
		));
	}

/**
 * 登録処理
 *
 * @param array $update 更新データ
 * @return bool
 * @throws InternalErrorException
 */
	private function __saveUser($update) {
		//不要なビヘイビアを一時的にアンロードする
		$this->User->Behaviors->unload('Files.Attachment');
		$this->User->Behaviors->unload('Users.Avatar');

		$result = $this->User->save($update, false, array_keys($update));
		if (! $result) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		//一時的にアンロードしたビヘイビアをロードする
		$this->User->Behaviors->load('Files.Attachment');
		$this->User->Behaviors->load('Users.Avatar');

		return true;
	}

/**
 * ステータスを更新する
 *
 * @param array $data リクエストデータ
 * @param int $status ステータス
 * @param bool $validate バリデーションの有無
 * @return bool|array バリデーションエラーの場合、falseを返す。<br>
 * 正常の場合で、管理者の承認有無は、本人の登録確認のためのユーザ情報を返す。<br>
 * また、本人の登録確認の場合、trueを返す。
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
	public function saveUserStatus($data, $status, $validate = true) {
		$this->loadModels([
			'User' => 'Users.User',
		]);

		//トランザクションBegin
		$this->begin();

		$userId = Hash::get($data, 'id');
		$data = Hash::remove($data, 'id');

		if ($validate) {
			$activateKey = Hash::get($data, 'activate_key');
			$data = Hash::remove($data, 'activate_key');

			$activateTime = date('Y-m-d H:i:s', Hash::get($data, 'timestamp'));
			$data = Hash::remove($data, 'timestamp');

			$hash = array_keys($data)[0];

			$user = $this->__validateUserStatus($userId, $status, $activateKey, $activateTime, $hash);
			if (! $user) {
				return false;
			}
		}

		//ステータスチェック
		if ($status === self::CONFIRMATION_USER_OWN) {
			//自分自身による確認
			$updateStatus = $this->__getUserStatusCode(self::STATUS_KEY_AVAILABLE);
		} else {
			//管理者による承認
			$updateStatus = $this->__getUserStatusCode(self::STATUS_KEY_WAIT_APPROVAL);
		}

		try {
			$update = array(
				'id' => $userId,
				'status' => $updateStatus,
				'activate_key' => '',
				'activated' => null
			);

			//登録処理
			$result = $this->__saveUser($update);
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
 * @param int $userId ユーザID
 * @param int $status ステータス値
 * @param string $activateKey アクティベートキー
 * @param int $activateTime アクティベート日時(int)
 * @param string $hash ユーザIDとアクティベートキーのハッシュ値(改竄チェック用)
 * @return bool|array ユーザ情報 or エラー
 */
	private function __validateUserStatus($userId, $status, $activateKey, $activateTime, $hash) {
		//改竄チェック
		if (Security::hash($userId . $activateKey, 'md5', true) !== $hash) {
			//不正アクセスエラー
			$this->invalidate(self::INVALIDATE_BAD_REQUEST, __d('net_commons', 'Bad Request'));
			return false;
		}

		//ユーザデータ取得
		$user = $this->User->find('first', array(
			'recursive' => -1,
			'conditions' => array('id' => $userId, 'is_deleted' => '0'),
		));

		if (! $user) {
			//既に削除されたエラー
			$this->invalidate(self::INVALIDATE_CANCELLED_OUT,
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
		} else {
			//管理者による承認
			$statusKey = self::STATUS_KEY_WAIT_ACCEPTANCE;
			$approvedError = array(
				$this->__getUserStatusCode(self::STATUS_KEY_WAIT_APPROVAL),
				$this->__getUserStatusCode(self::STATUS_KEY_AVAILABLE)
			);
		}

		if ($user['User']['status'] === $this->__getUserStatusCode($statusKey) &&
				$user['User']['activated'] === $activateTime) {
			//OK
		} elseif (in_array($user['User']['status'], $approvedError, true)) {
			//既に承認済みエラー
			$this->invalidate(self::INVALIDATE_ALREADY_ACTIVATED,
				__d('auth', 'Selected account is already activated!')
			);
			return false;
		} else {
			//不正アクセスエラー
			$this->invalidate(self::INVALIDATE_BAD_REQUEST, __d('net_commons', 'Bad Request'));
			return false;
		}

		return $user;
	}

}
