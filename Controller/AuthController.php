<?php
App::uses('AppController', 'Controller');
/**
 * Auth Controller
 *
 * @author   Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @link     http://www.netcommons.org NetCommons Project
 * @license  http://www.netcommons.org/license.txt NetCommons License
 */
class AuthController extends AppController {

/**
 * beforeFilter
 *
 * @return void
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 **/
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('login', 'logout');
	}

/**
 * index
 *
 * @return void
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 **/
	public function index() {
		$this->redirect($this->Auth->loginAction);
	}

/**
 * login
 *
 * @return void
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 **/
	public function login() {
		if ($this->request->is('post')) {
			if ($this->Auth->login()) {
				$this->redirect($this->Auth->redirect());
			}
			$this->Session->setFlash(__('Invalid username or password, try again'));
			$this->redirect($this->Auth->loginAction);
		}
	}

/**
 * logout
 *
 * @return void
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 **/
	public function logout() {
		$this->redirect($this->Auth->logout());
	}
}
