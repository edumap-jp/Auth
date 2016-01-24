<?php
/**
 * AuthControllerのテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * AuthControllerのテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller
 */
class AuthViewAuthLoginTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array();

/**
 * Plugin name
 *
 * @var array
 */
	public $plugin = 'auth';

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		NetCommonsControllerTestCase::loadTestPlugin($this, 'Auth', 'TestAuth');
	}

/**
 * 通常ログインの評価
 *
 * @param string $result 結果
 * @return void
 */
	protected function _assertAuthGeneral($result) {
		//チェック
		// * AuthGeneralのチェック
		$expected = '/<form.*?' . preg_quote('id="AuthGeneral"', '/') . '.*?">/';
		$this->assertRegExp($expected, $result);

		$this->assertInput('form', null, NetCommonsUrl::actionUrl('/auth_general/auth_general/login'), $result);
		$this->assertInput('input', 'data[User][username]', null, $result);
		$this->assertInput('input', 'data[User][password]', null, $result);
	}

/**
 * カスタムログインの評価
 *
 * @param string $result 結果
 * @return void
 */
	protected function _assertCustomAuth($result) {
		//チェック
		// * testAuthのチェック
		$expected = '/<form.*?' . preg_quote('id="TestAuth"', '/') . '.*?">/';
		$this->assertRegExp($expected, $result);

		$this->assertInput('form', null, NetCommonsUrl::actionUrl('/test_auth/test_auth/login'), $result);

		$expected = '/' . preg_quote('TestAuth/View/Elements/login.ctp', '/') . '/';
		$this->assertRegExp($expected, $result);
	}

/**
 * ログイン表示のテスト(auth_general, test_auth)
 *
 * @return void
 */
	public function testIndex() {
		$result = $this->_testNcAction('/test_auth/test_auth/index', array(
			'method' => 'get'
		));

		//チェック
		// * AuthGeneralのチェック
		$this->_assertAuthGeneral($result);

		// * testAuthのチェック
		$this->_assertCustomAuth($result);
	}

/**
 * ログイン表示のテスト(auth_general, no test_auth)
 *
 * @return void
 */
	public function testIndexOnlyAuthGeneral() {
		$result = $this->_testNcAction('/test_auth/test_auth/index_only_auth_general', array(
			'method' => 'get'
		));

		//チェック
		// * AuthGeneralのチェック
		$this->_assertAuthGeneral($result);

		// * testAuthのチェック
		$expected = '/<form.*?' . preg_quote('id="TestAuth"', '/') . '.*?">/';
		$this->assertNotRegExp($expected, $result);
	}

/**
 * ログイン表示のテスト(no auth_general, test_auth)
 *
 * @return void
 */
	public function testIndexNoAuthGeneral() {
		$result = $this->_testNcAction('/test_auth/test_auth/index_no_auth_general', array(
			'method' => 'get'
		));

		//チェック
		// * AuthGeneralのチェック
		$expected = '/<form.*?' . preg_quote('id="AuthGeneral"', '/') . '.*?">/';
		$this->assertNotRegExp($expected, $result);

		// * testAuthのチェック
		$this->_assertCustomAuth($result);
	}
}
