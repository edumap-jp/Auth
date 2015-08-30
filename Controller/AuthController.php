<?php
/**
 * Auth Controller
 *
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AuthAppController', 'Auth.Controller');

/**
 * Auth Controller
 *
 * @author Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @package NetCommons\Auth\Controller
 */
class AuthController extends AuthAppController {

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'Users.User',
	);

/**
 * beforeFilter
 *
 * @return void
 **/
	public function beforeFilter() {
		// Load available authenticators
		$authenticators = $this->getAuthenticators();
		$this->set('authenticators', $authenticators);

		$this->__setDefaultAuthenticator();

		parent::beforeFilter();
		$this->Auth->allow('login', 'logout');
	}

/**
 * index
 *
 * @return void
 **/
	public function index() {
		$this->redirect($this->Auth->loginAction);
	}

/**
 * login
 *
 * @return void
 * @throws InternalErrorException
 **/
	public function login() {
		if ($this->request->is('post')) {
			if ($this->Auth->login()) {
				//トランザクションBegin
				$this->User->setDataSource('master');
				$dataSource = $this->User->getDataSource();
				$dataSource->begin();

				try {
					$update = array('User.last_login' => '\'' . date('Y-m-d H:i:s') . '\'');
					$conditions = array('User.id' => (int)$this->Auth->user('id'));
					$this->User->updateAll($update, $conditions);
					$dataSource->commit();
				} catch (Exception $ex) {
					$dataSource->rollback();
					CakeLog::error($ex);
					throw $ex;
				}

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
 **/
	public function logout() {
		$this->redirect($this->Auth->logout());
	}

/**
 * Set authenticator
 *
 * @return void
 **/
	private function __setDefaultAuthenticator() {
		$plugin = Inflector::camelize($this->request->offsetGet('plugin'));
		$scheme = strtr(Inflector::camelize($this->request->offsetGet('plugin')), array('Auth' => ''));
		$callee = array(sprintf('Auth%sAppController', $scheme), '_getAuthenticator');

		if (is_callable($callee)) {
			$authenticator = call_user_func($callee);
			$this->Auth->authenticate = array($authenticator => array());
			CakeLog::info(sprintf('Will load %s authenticator', $authenticator), true);
		} else {
			CakeLog::info(sprintf('Unknown authenticator %s.%s', $plugin, $scheme), true);
		}
	}
}
