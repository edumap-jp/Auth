<?php
/**
 * 自動登録Helper
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AppHelper', 'View/Helper');

/**
 * 自動登録Helper
 *
 * @package NetCommons\Auth\View\Helper
 */
class AutoUserRegistFormHelper extends AppHelper {

/**
 * 使用するHelpers
 *
 * - [NetCommons.NetCommonsForm](../../NetCommons/classes/NetCommonsForm.html)
 * - [NetCommons.NetCommonsHtml](../../NetCommons/classes/NetCommonsHtml.html)
 *
 * @var array
 */
	public $helpers = array(
		'NetCommons.NetCommonsForm',
		'NetCommons.NetCommonsHtml',
	);

/**
 * Before render callback. beforeRender is called before the view file is rendered.
 *
 * Overridden in subclasses.
 *
 * @param string $viewFile The view file that is going to be rendered
 * @return void
 */
	public function beforeRender($viewFile) {
		$this->NetCommonsHtml->css(array(
			'/auth/css/style.css'
		));
		parent::beforeRender($viewFile);
	}

/**
 * データタイプに対するinputタグのHTML出力
 *
 * @param array $userAttribute 会員項目データ配列
 * @param bool $disabled Disabledの有無
 * @param string $colClass colのclass属性
 * @return string HTML
 */
	public function input($userAttribute, $disabled, $colClass) {
		$output = '';

		$key = $userAttribute['UserAttribute']['key'];
		$editable = $userAttribute['UserAttributesRole']['self_editable'];
		$dataTypeKey = $userAttribute['UserAttributeSetting']['data_type_key'];
		if (Hash::get($userAttribute, 'UserAttributeSetting.is_multilingualization')) {
			$field = 'UsersLanguage.' . Current::read('Language.id') . '.' . $key;
		} else {
			$field = 'User' . '.' . $key;
		}

		if (! $editable && ! in_array($key, ['username', 'password'], true)) {
			if (! $disabled) {
				$output .= $this->NetCommonsForm->hidden($field);
			}
			return $output;
		}

		$options = array(
			'type' => $dataTypeKey,
			'label' => $userAttribute['UserAttribute']['name'],
			'required' => $userAttribute['UserAttributeSetting']['required'],
		);
		if ($disabled) {
			$options['disabled'] = true;
		} else {
			$options['help'] = $userAttribute['UserAttribute']['description'];
		}

		$options['div'] = array('class' => 'form-group' . $colClass);
		if (in_array($dataTypeKey, ['radio', 'checkbox', 'select'], true)) {
			$options['options'] = Hash::combine(
				$userAttribute['UserAttributeChoice'], '{n}.key', '{n}.name'
			);
		}
		if (in_array($dataTypeKey, ['password', 'email'], true)) {
			$options['again'] = ! $disabled;
		}

		$output .= $this->NetCommonsForm->input($field, $options);

		return $output;
	}

}
