<?php
/**
 * AuthControllerのテスト
 *
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AuthController', 'Controller');
App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');
App::uses('Role', 'Roles.Model');

/**
 * AuthControllerのテスト
 *
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller
 */
class AuthControllerTest extends NetCommonsControllerTestCase {

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
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->generateNc('Auth.Auth');

		$this->controller->plugin = 'Auth';
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
 * ログイン表示のテスト
 *
 * @return void
 */
	public function testIndex() {
		$this->testAction('/auth/index');
		$this->assertEqual($this->headers['Location'], Router::url('/auth/login', true));
	}

/**
 * ログインのテスト
 *
 * @return void
 */
	public function testLogin() {
		$this->testAction('/auth_general/auth_general/login', array(
			'data' => array(
				'User' => array(
					'username' => 'admin',
					'password' => 'admin',
				),
			),
		));
		$this->assertTrue($this->controller->Auth->loggedIn());
	}

/**
 * ログインのテスト(Userのupdateエラー)
 *
 * @return void
 */
	public function testLoginOnUserUpdateError() {
		$Mock = $this->getMockForModel('Users.User', ['updateAll']);
		$Mock->expects($this->once())
			->method('updateAll')
			->will($this->returnValue(false));

		$this->setExpectedException('InternalErrorException');
		$this->testAction('/auth_general/auth_general/login', array(
			'data' => array(
				'User' => array(
					'username' => 'admin',
					'password' => 'admin',
				),
			),
		));
	}

/**
 * ログアウトのテスト
 *
 * @return void
 */
	public function testLogout() {
		$this->testLogin();

		$this->testAction('/auth_general/auth_general/logout', array(
			'data' => array(),
		));
		$this->assertEqual(null, CakeSession::read('Auth.User'));
	}
}
