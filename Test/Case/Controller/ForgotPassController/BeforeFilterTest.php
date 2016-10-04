<?php
/**
 * ForgotPassController::beforeFilter()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * ForgotPassController::beforeFilter()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller\ForgotPassController
 */
class ForgotPassControllerBeforeFilterTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.auth.site_setting4auth',
	);

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'auth';

/**
 * Controller name
 *
 * @var string
 */
	protected $_controller = 'forgot_pass';

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		//ログイン
		TestAuthGeneral::login($this);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		//ログアウト
		TestAuthGeneral::logout($this);

		parent::tearDown();
	}

/**
 * BeforeFileteのテスト
 *
 * @return void
 */
	public function testBeforeFilter() {
		//テスト実行
		$this->_testGetAction(array('action' => 'request'), array('method' => 'assertNotEmpty'), null, 'view');

		//チェック
		$this->assertEquals($this->vars['pageTitle'], __d('auth', 'Forgot your Password?'));
	}

}
