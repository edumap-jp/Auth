<?php
/**
 * AuthController::logout()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');
App::uses('TestAuthGeneral', 'AuthGeneral.TestSuite');

/**
 * AuthController::logout()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller\AuthController
 */
class AuthControllerLogoutTest extends NetCommonsControllerTestCase {

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
		TestAuthGeneral::login($this);
	}

/**
 * ログアウトのテスト
 *
 * @return void
 */
	public function testLogout() {
		//ログイン状態と判定させるMock生成
		$this->_mockLoggedIn();
		$this->assertTrue($this->controller->Auth->loggedIn());

		$this->_testNcAction('/auth/auth/logout', array(
			'data' => array(),
		));

		$this->assertFalse($this->controller->Auth->loggedIn());
	}

/**
 * ログアウトのテスト(短いAction)
 *
 * @return void
 */
	public function testLogoutByShortAction() {
		$this->_mockLoggedIn();
		$this->assertTrue($this->controller->Auth->loggedIn());

		$this->_testNcAction('/auth/logout', array(
			'data' => array(),
		));

		$this->assertFalse($this->controller->Auth->loggedIn());
	}

}
