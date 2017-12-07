<?php
/**
 * AuthenticatorPlugin Utility
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

/**
 * AuthenticatorPlugin Utility
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Auth\Utility
 */
class AuthenticatorPlugin {

/**
 * Return available authenticators (Camel)
 *
 * @return array authenticators (Camel)
 */
	public static function getPlugins() {
		$authenticators = array();
		$plugins = App::objects('plugins');
		foreach ($plugins as $plugin) {
			if (preg_match('/^Auth([A-Z0-9_][\w]+)/', $plugin)) {
				$authenticators[] = $plugin;
			}
		}

		return $authenticators;
	}

/**
 * Return available authenticators (under_score)
 *
 * @return array authenticators
 */
	public static function getAuthenticators() {
		$authenticators = self::getPlugins();
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
	public static function getExternals() {
		$authenticators = self::getPlugins();
		// array_diffを利用して 配列の値AuthGeneralを削除
		$authenticators = array_diff($authenticators, array('AuthGeneral'));
		// 配列indexの再設定
		$authenticators = array_values($authenticators);
		return $authenticators;
	}
}
