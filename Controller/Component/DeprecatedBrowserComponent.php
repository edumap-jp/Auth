<?php
/**
 * 非推奨ブラウザ関連 Component
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Component', 'Controller');

/**
 * 非推奨ブラウザ関連 Component
 *
 * @property \SessionComponent $Session
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Controller\Component
 */
class DeprecatedBrowserComponent extends Component {

/**
 * 使用するComponents
 *
 * @var array
 */
	public $components = [
		'Session',
	];

/**
 * コントローラ
 *
 * @var array
 */
	public $controller;

/**
 * Called before the Controller::beforeFilter().
 *
 * @param Controller $controller Instantiating controller
 * @return void
 */
	public function initialize(Controller $controller) {
		$this->controller = $controller;
	}

/**
 * 非推奨ブラウザのチェック
 *
 * @return bool
 */
	public function isDeprecatedBrowser() {
		$browser = strtolower(env('HTTP_USER_AGENT'));
		return (strstr($browser , 'trident') || strstr($browser , 'msie'));
	}

/**
 * 非推奨ブラウザエラーメッセージ
 *
 * @return void
 */
	public function setFlashNotification() {
		$this->controller->NetCommons->setFlashNotification(
			__d('auth', 'Internet Explorer(IE) is deprecated. ' .
				'If you are using IE, please change to the latest version such as Edge, Chrome, Firefox, etc.'),
			['class' => 'danger', 'interval' => 10000]
		);
	}

}
