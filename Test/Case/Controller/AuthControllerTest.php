<?php
/**
 * AuthController Test Case
 *
 * @author   Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @link     http://www.netcommons.org NetCommons Project
 * @license  http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('AuthController', 'Controller');

/**
 * Summary for AuthController Test Case
 */
class AuthControllerTest extends ControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.users.user',
	);

/**
 * testIndex method
 *
 * @return void
 */
	public function testIndex() {
		$this->testAction('/auth/index');
		$this->assertTrue(true);
	}

/**
 * testLogin method
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
		$this->assertTrue(true);
	}

/**
 * testLogout method
 *
 * @return void
 */
	public function testLogout() {
		$this->testAction('/auth_general/auth_general/logout', array(
			'data' => array(
			),
		));
		$this->assertTrue(true);
	}
}
