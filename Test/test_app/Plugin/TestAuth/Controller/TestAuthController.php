<?php
/**
 * TestAuth Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AuthAppController', 'Auth.Controller');

/**
 * TestAuth Controller
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\test_app\Plugin\TestAuth\Controller
 */
class TestAuthController extends AuthAppController {

/**
 * beforeFilter
 *
 * @return void
 **/
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('index_only_auth_general', 'index_no_auth_general');

		$siteSettions['ForgotPass.use_password_reissue'] = array(['value' => '0']);
		$this->set('siteSettions', $siteSettions);
	}

/**
 * index
 *
 * @return void
 **/
	public function index() {
		$authenticators = array(
			'auth_general',
			'test_auth'
		);
		$this->set('authenticators', $authenticators);
		$this->view = 'Auth.Auth/login';
	}

/**
 * index
 *
 * @return void
 **/
	public function index_only_auth_general() {
		$authenticators = array(
			'auth_general'
		);
		$this->set('authenticators', $authenticators);
		$this->view = 'Auth.Auth/login';
	}

/**
 * index
 *
 * @return void
 **/
	public function index_no_auth_general() {
		$authenticators = array(
			'test_auth'
		);
		$this->set('authenticators', $authenticators);
		$this->view = 'Auth.Auth/login';
	}
}
