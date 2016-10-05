<?php
/**
 * ForgotPass::validateAuthorizationKey()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsModelTestCase', 'NetCommons.TestSuite');

/**
 * ForgotPass::validateAuthorizationKey()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Model\ForgotPass
 */
class ForgotPassValidateAuthorizationKeyTest extends NetCommonsModelTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.auth.user4auth',
		'plugin.user_attributes.user_attribute_setting4test',
	);

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'auth';

/**
 * Model name
 *
 * @var string
 */
	protected $_modelName = 'ForgotPass';

/**
 * Method name
 *
 * @var string
 */
	protected $_methodName = 'validateAuthorizationKey';

/**
 * validateAuthorizationKey()テストのDataProvider
 *
 * ### 戻り値
 *  - data リクエストデータ
 *  - session Sessionデータ
 *  - errorMessage バリデーションエラー
 *
 * @return array データ
 */
	public function dataProvider() {
		//0: 正常
		$index = 0;
		$result[$index] = array();
		$result[$index]['data'] = array('ForgotPass' => array('authorization_key' => 'test@test'));
		$result[$index]['session'] = array(
			'user_id' => '2',
			'username' => 'site_manager',
			'handlename' => 'Site Manager',
			'authorization_key' => 'test@test',
		);
		$result[$index]['errorMessage'] = true;

		//1: バリデーションエラー
		$index = 1;
		$result[$index] = array();
		$result[$index]['data'] = array('ForgotPass' => array('authorization_key' => ''));
		$result[$index]['session'] = array(
			'user_id' => '2',
			'username' => 'site_manager',
			'handlename' => 'Site Manager',
			'authorization_key' => 'test@test',
		);
		$result[$index]['errorMessage'] = __d(
			'net_commons', 'Please input %s.', __d('auth', 'Authorization key')
		);

		//2: 不正メールアドレスエラー
		$index = 2;
		$result[$index] = array();
		$result[$index]['data'] = array('ForgotPass' => array('authorization_key' => 'test@test'));
		$result[$index]['session'] = array(
			'user_id' => '0',
			'username' => '',
			'handlename' => '',
			'authorization_key' => 'test@test',
		);
		$result[$index]['errorMessage'] = __d(
			'auth', 'Failed on validation errors. Please check the authorization key.'
		);

		//3: セッションデータなし
		$index = 3;
		$result[$index] = array();
		$result[$index]['data'] = array('ForgotPass' => array('authorization_key' => 'test@test'));
		$result[$index]['session'] = null;
		$result[$index]['errorMessage'] = __d(
			'auth', 'Failed on validation errors. Please check the authorization key.'
		);

		return $result;
	}

/**
 * validateAuthorizationKey()のテスト
 *
 * @param array $data リクエストデータ
 * @param array $session Sessionデータ
 * @param bool|string $errorMessage バリデーションエラー
 * @dataProvider dataProvider
 * @return void
 */
	public function testValidateAuthorizationKey($data, $session, $errorMessage) {
		$model = $this->_modelName;
		$methodName = $this->_methodName;

		//データ生成
		if (! empty($_SESSION)) {
			$backSession = $_SESSION;
		}
		CakeSession::write('ForgotPass', $session);

		//テスト実施
		$result = $this->$model->$methodName($data);

		//チェック
		if ($errorMessage === true) {
			$this->assertTrue($result);
		} else {
			$this->assertFalse($result);
			$this->assertEquals($this->$model->validationErrors['authorization_key'][0], $errorMessage);
		}

		//後処理
		if (! empty($backSession)) {
			$_SESSION = $backSession;
		} else {
			unset($_SESSION);
		}
	}

}
