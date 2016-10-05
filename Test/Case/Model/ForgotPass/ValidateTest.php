<?php
/**
 * ForgotPass::validate()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsValidateTest', 'NetCommons.TestSuite');
App::uses('ForgotPassFixture', 'Auth.Test/Fixture');

/**
 * ForgotPass::validate()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Model\ForgotPass
 */
class ForgotPassValidateTest extends NetCommonsValidateTest {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
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
	protected $_methodName = 'validates';

/**
 * ValidationErrorのDataProvider
 *
 * ### 戻り値
 *  - data 登録データ
 *  - field フィールド名
 *  - value セットする値
 *  - message エラーメッセージ
 *  - overwrite 上書きするデータ(省略可)
 *
 * @return array テストデータ
 */
	public function dataProviderValidationError() {
		$result = array();

		//email入力
		$result[] = array(
			'data' => array('ForgotPass' => array('email' => 'test@test.aa.aa')),
			'field' => 'email', 'value' => '',
			'message' => __d('net_commons', 'Please input %s.', __d('auth', 'email'))
		);
		$result[] = array(
			'data' => array('ForgotPass' => array('email' => 'test@test.aa.aa')),
			'field' => 'email', 'value' => 'testtest.aa.aa',
			'message' => sprintf(
				__d('net_commons', 'Unauthorized pattern for %s. Please input the data in %s format.'),
				__d('auth', 'email'),
				__d('auth', 'email')
			)
		);
		$result[] = array(
			'data' => array('ForgotPass' => array('email' => 'test@test.aa.aa')),
			'field' => 'email', 'value' => null,
			'message' => true
		);
		$result[] = array(
			'data' => array('ForgotPass' => array('email' => 'test@test.aa.aa')),
			'field' => 'email', 'value' => 'test@test.aa.aa',
			'message' => true
		);
		//authorization_key入力
		$result[] = array(
			'data' => array('ForgotPass' => array('authorization_key' => 'test@test')),
			'field' => 'authorization_key', 'value' => '',
			'message' => __d('net_commons', 'Please input %s.', __d('auth', 'Authorization key')),
		);
		$result[] = array(
			'data' => array('ForgotPass' => array('authorization_key' => 'test@test')),
			'field' => 'authorization_key', 'value' => 'test#test',
			'message' => __d('auth', 'Failed on validation errors. Please check the authorization key.')
		);
		$result[] = array(
			'data' => array('ForgotPass' => array('authorization_key' => 'test@test')),
			'field' => 'authorization_key', 'value' => null,
			'message' => true
		);
		$result[] = array(
			'data' => array('ForgotPass' => array('authorization_key' => 'test@test')),
			'field' => 'authorization_key', 'value' => 'test@test',
			'message' => true
		);

		return $result;
	}

/**
 * Validatesのテスト
 *
 * @param array $data 登録データ
 * @param string $field フィールド名
 * @param string $value セットする値
 * @param string $message エラーメッセージ
 * @param array $overwrite 上書きするデータ
 * @dataProvider dataProviderValidationError
 * @return void
 */
	public function testValidationError($data, $field, $value, $message, $overwrite = array()) {
		if (! empty($_SESSION)) {
			$backSession = $_SESSION;
		}
		if ($field === 'authorization_key') {
			CakeSession::write('ForgotPass.authorization_key', 'test@test');
		}

		parent::testValidationError($data, $field, $value, $message, $overwrite);

		if (! empty($backSession)) {
			$_SESSION = $backSession;
		} else {
			unset($_SESSION);
		}
	}

}
