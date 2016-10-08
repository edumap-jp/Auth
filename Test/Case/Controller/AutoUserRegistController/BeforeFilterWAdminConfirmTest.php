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
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Case\Controller\AutoUserRegistController
 */
class AutoUserRegistControllerBeforeFilterWAdminConfirmTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.auth.site_setting4auto_regist_w_admin_confirm',
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
	public function testBeforeFilterOnRequest() {
		//テスト実行
		$this->_testGetAction(array('action' => 'request'), array('method' => 'assertNotEmpty'), null, 'view');

		//チェック
		$this->assertEquals($this->controller->viewVars['pageTitle'], __d('auth', 'Sign up'));

		$expected = array(
			'navibar' => array(
				'request' => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'request',
					),
					'label' => array(
						0 => 'auth', 1 => 'Registration?',
					),
				),
				'confirm' => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'confirm',
					),
					'label' => array(
						0 => 'auth', 1 => 'Entry confirm.',
					),
				),
				'completion' => array(
					'url' => array(
						'controller' => 'auto_user_regist', 'action' => 'update',
					),
					'label' => array(
						0 => 'auth', 1 => 'Complete request registration.',
					),
				),
			),
			'cancelUrl' => null,
		);
		$this->assertEquals($this->controller->helpers['NetCommons.Wizard'], $expected);
	}

/**
 * BeforeFileter()のテスト
 * - activate_keyがある場合
 *
 * @return void
 */
	public function testBeforeFilterOnApproval() {
		//事前準備
		$this->_mockForReturnTrue('Auth.AutoUserRegist', 'saveUserStatus');

		//テスト実行
		$this->_testGetAction(
			array('action' => 'approval', '?' => array('activate_key' => 'test')), null, null, 'view'
		);

		//チェック
		$this->assertEquals($this->vars['pageTitle'], __d('auth', 'Sign up'));

		$expected = array(
			'navibar' => array(
				'request' => array(
					'label' => array(
						0 => 'auth', 1 => 'Registration?',
					),
				),
				'confirm' => array(
					'label' => array(
						0 => 'auth', 1 => 'Entry confirm.',
					),
				),
				'completion' => array(
					'label' => array(
						0 => 'auth', 1 => 'Complete registration.',
					),
				),
			),
			'cancelUrl' => null,
		);
		$this->assertEquals($this->controller->helpers['NetCommons.Wizard'], $expected);
	}

}
