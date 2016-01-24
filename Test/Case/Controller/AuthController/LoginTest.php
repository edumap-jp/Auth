<?php
/**
 * AuthController::login()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * AuthController::login()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller\AuthController
 */
class AuthControllerLoginTest extends NetCommonsControllerTestCase {

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
 * Controller name
 *
 * @var string
 */
	protected $_controller = 'auth';

/**
 * ログイン状態と判定させるMock生成する
 *
 * @return void
 */
	protected function _mockLoggedIn() {
		$this->controller->Auth
			->staticExpects($this->any())
			->method('user')
			->will($this->returnCallback(function ($key = null) {
				$role = Role::ROOM_ROLE_KEY_ROOM_ADMINISTRATOR;
				if (isset(TestAuthGeneral::$roles[$role][$key])) {
					return TestAuthGeneral::$roles[$role][$key];
				} else {
					return TestAuthGeneral::$roles[$role];
				}
			}));
	}

/**
 * ログインのテスト(GET)
 *
 * @return void
 */
	public function testLoginOnGet() {
		$this->_testNcAction('/auth/auth/login', array(
			'method' => 'get'
		));
		$this->assertEqual('login', $this->controller->view);
	}

/**
 * ログインのテスト(GET)
 *
 * @return void
 */
	public function testLoginByShortActionOnGet() {
		$this->_testNcAction('/auth/login', array(
			'method' => 'get'
		));
		$this->assertEqual('login', $this->controller->view);
	}

/**
 * POST用DataProvider
 *
 * #### 戻り値
 *  - data: リクエストdata
 *
 * @return array
 */
	public function dataProvider() {
		$results = array();

		//POSTデータ
		$results[0] = array(
			'User' => array(
				'username' => 'admin',
				'password' => 'admin',
			),
		);
		return $results;
	}

/**
 * ログインのテスト
 *
 * @param array $data リクエストPOSTデータ
 * @dataProvider dataProvider
 * @return void
 */
	public function testLogin($data) {
		$this->assertFalse($this->controller->Auth->loggedIn());

		//ログイン状態と判定させるMock生成
		$this->_mockLoggedIn();

		$this->_testNcAction('/auth/auth/login', array(
			'method' => 'post',
			'data' => $data
		));

		$this->assertTrue($this->controller->Auth->loggedIn());
	}

/**
 * ログインのテスト
 *
 * @param array $data リクエストPOSTデータ
 * @dataProvider dataProvider
 * @return void
 */
	public function testLoginByShortAction($data) {
		$this->assertFalse($this->controller->Auth->loggedIn());

		//ログイン状態と判定させるMock生成
		$this->_mockLoggedIn();

		$this->_testNcAction('/auth/login', array(
			'method' => 'post',
			'data' => $data
		));

		$this->assertTrue($this->controller->Auth->loggedIn());
	}

/**
 * ログインのテスト
 *
 * @param array $data リクエストPOSTデータ
 * @dataProvider dataProvider
 * @return void
 */
	public function testLoginError($data) {
		$this->assertFalse($this->controller->Auth->loggedIn());

		$this->_testNcAction('/auth/auth/login', array(
			'method' => 'post',
			'data' => $data
		));

		$this->assertFalse($this->controller->Auth->loggedIn());
		$this->assertEqual($this->headers['Location'], Router::url('/auth/login', true));
	}

/**
 * ログインのテスト(Auth%sAppController::_getAuthenticatorの読み込み)
 *
 * @param array $data リクエストPOSTデータ
 * @dataProvider dataProvider
 * @return void
 */
	public function testLoginCalleeLogin($data) {
		$this->assertFalse($this->controller->Auth->loggedIn());

		//ログイン状態と判定させるMock生成
		$this->_mockLoggedIn();

		$this->_testNcAction('/auth_general/auth_general/login', array(
			'method' => 'post',
			'data' => $data,
		));
		$this->assertTrue($this->controller->Auth->loggedIn());
	}

/**
 * ログインのテスト(Userのupdateエラー)
 *
 * @param array $data リクエストPOSTデータ
 * @dataProvider dataProvider
 * @return void
 */
	public function testLoginOnUserUpdateError($data) {
		$Mock = $this->getMockForModel('Users.User', ['updateAll']);
		$Mock->expects($this->once())
			->method('updateAll')
			->will($this->returnValue(false));

		$this->setExpectedException('InternalErrorException');
		$this->testLogin($data);
	}
}
