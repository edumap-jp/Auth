<?php
/**
 * AutoUserRegistController::beforeFilter()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * AutoUserRegistController::beforeFilter()のテスト
 * - 自動登録OFFのテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller\AutoUserRegistController
 */
class AutoUserRegistControllerBeforeFilterWOAutoRegistTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.auth.site_setting4auth_w_o_auto_regist',
		'plugin.user_attributes.user_attribute4test',
		'plugin.user_attributes.user_attribute_choice4test',
		'plugin.user_attributes.user_attribute_layout',
		'plugin.user_attributes.user_attribute_setting4test',
		'plugin.user_attributes.user_attributes_role4test',
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
	protected $_controller = 'auto_user_regist';

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
	}

/**
 * BeforeFileter()のテスト
 *
 * @return void
 */
	public function testBeforeFilter() {
		//テスト実行
		$this->_testGetAction(array('action' => 'request'), null, 'BadRequestException', 'view');
	}

}
