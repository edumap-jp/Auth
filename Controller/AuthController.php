<?php
App::uses('AuthAppController', 'Auth.Controller');
/**
 * Auth Controller
 *
 * @author   Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @link     http://www.netcommons.org NetCommons Project
 * @license  http://www.netcommons.org/license.txt NetCommons License
 */
class AuthController extends AuthAppController {

/**
 * beforeFilter
 *
 * @return void
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 **/
	public function beforeFilter() {
		$this->__loadAuthenticators();

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

/**
 * Load available authenticators
 *
 * @return void
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 **/
	private function __loadAuthenticators() {
		$authenticators = $this->getAuthenticators();
		$this->set('authenticators', $authenticators);

		$plugin = Inflector::camelize($this->request->offsetGet('plugin'));
		$scheme = strtr(Inflector::camelize($this->request->offsetGet('plugin')), array('Auth' => ''));
		$callee = array(sprintf('Auth%sAppController', $scheme), 'getAuthenticator');

		if (is_callable($callee)) {
			$authenticator = call_user_func($callee);
			$this->Auth->authenticate = array(sprintf('%s.%s', $plugin, $scheme) => array());
			CakeLog::info(sprintf('Will load %s.%s authenticator', $plugin, $scheme), true);
		} else {
			CakeLog::info(sprintf('Unknown authenticator %s.%s', $plugin, $scheme), true);
		}
	}
}
