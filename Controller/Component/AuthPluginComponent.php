<?php
/**
 * AuthPlugin Component
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Component', 'Controller');

/**
 * AuthPlugin Component
 *
 * @property SessionComponent $Session
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Auth\Controller\Component
 */
class AuthPluginComponent extends Component {

/**
 * @var Controller コントローラ
 */
	protected $_controller = null;

/**
 * Called before the Controller::beforeFilter().
 *
 * @param Controller $controller Instantiating controller
 * @return void
 * @link http://book.cakephp.org/2.0/ja/controllers/components.html#Component::initialize
 */
	public function initialize(Controller $controller) {
		// どのファンクションでも $controller にアクセスできるようにクラス内変数に保持する
		$this->_controller = $controller;
	}

/**
 * Return available authenticators (Camel)
 *
 * @return array authenticators (Camel)
 */
	public function getPlugins() {
		$authenticators = array();
		$plugins = App::objects('plugins');
		foreach ($plugins as $plugin) {
			if (preg_match('/^Auth([A-Z0-9_][\w]+)/', $plugin)) {
				$authenticators[] = $plugin;
			}
		}
		foreach ($plugins as $plugin) {
			if (preg_match('/^RmAuth([A-Z0-9_][\w]+)/', $plugin)) {
				$authenticators[] = $plugin;
				// RmAuthXXXプラグインがあったら、先頭Rm文字を取り除き、対象のプラグインを除外する
				$unsetPlugin = ltrim($plugin, 'Rm');
				$unsetKey = array_search($unsetPlugin, $authenticators);
				if ($unsetKey) {
					unset($authenticators[$unsetKey]);
				}
			}
		}

		return $authenticators;
	}

/**
 * Return available authenticators (under_score)
 *
 * @return array authenticators
 */
	public function getAuthenticators() {
		$authenticators = $this->getPlugins();
		foreach ($authenticators as &$plugin) {
			$plugin = Inflector::underscore($plugin);
		}

		return $authenticators;
	}

/**
 * AuthGeneral以外の外部認証プラグイン(AuthXXX)を取得
 *
 * @return array external authenticators
 */
	public function getExternals() {
		$authenticators = $this->getPlugins();
		// array_diffを利用して 配列の値AuthGeneralを削除
		$authenticators = array_diff($authenticators, array('AuthGeneral'));
		// 配列indexの再設定
		$authenticators = array_values($authenticators);
		return $authenticators;
	}

}
