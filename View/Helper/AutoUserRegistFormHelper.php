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
 * @param array $userAttributes 会員項目データ配列
 * @param bool $disabled Disabledの有無
 * @return string HTML
 */
	public function inputs($userAttributes, $disabled) {
		$output = '';

		foreach ($userAttributes as $userAttribute) {
			if ($this->_View->params['action'] === 'completion') {
				$output .= $this->__inputByCompletion($userAttribute);
			} else {
				$output .= $this->__input($userAttribute, $disabled);
			}
		}

		return $output;
	}

/**
 * データタイプに対するinputタグのHTML出力
 *
 * @param array $userAttribute 会員項目データ配列
 * @param bool $disabled Disabledの有無
 * @return string HTML
 */
	public function input($userAttribute, $disabled) {
		$output = '';

		$attributeKey = $userAttribute['UserAttribute']['key'];
		if (in_array($attributeKey, ['username', 'password'], true)) {
			$startTag = '<div class="row">';
			$colClass = ' col-xs-12 col-sm-4';
			$endTag = '</div>';
		} elseif (in_array($attributeKey, ['handlename', 'name'], true)) {
			$startTag = '<div class="row">';
			$colClass = ' col-xs-12 col-sm-6';
			$endTag = '</div>';
		} else {
			$startTag = '';
			$colClass = '';
			$endTag = '';
		}

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
				$output .= $startTag;
				$output .= $this->NetCommonsForm->hidden($field);
				$output .= $endTag;
			}
			return $output;
		}

		$options = array(
			'type' => $dataTypeKey,
			'label' => $userAttribute['UserAttribute']['name'],
			'required' => $userAttribute['UserAttributeSetting']['required'],
		);
		$options['disabled'] = $disabled;
		$options['help'] = $userAttribute['UserAttribute']['description'];

		$options['div'] = array('class' => 'form-group' . $colClass);
		if (in_array($dataTypeKey, ['radio', 'checkbox', 'select'], true)) {
			$options['options'] = Hash::combine(
				$userAttribute['UserAttributeChoice'], '{n}.code', '{n}.name'
			);
		}
		if (in_array($dataTypeKey, ['password', 'email'], true)) {
			$options['again'] = ! $disabled;
		}

		$output .= $startTag;
		$output .= $this->NetCommonsForm->input($field, $options);
		$output .= $endTag;

		return $output;
	}

/**
 * データタイプに対するinputタグのHTML出力（受付完了）
 *
 * @param array $userAttribute 会員項目データ配列
 * @return string HTML
 */
	public function inputByCompletion($userAttribute) {
		$output = '';

		$attributeKey = $userAttribute['UserAttribute']['key'];
		$editable = $userAttribute['UserAttributesRole']['self_editable'];

		if ($attributeKey === 'password') {
			return $output;
		}

		if (! $editable && $attributeKey !== 'username') {
			return $output;
		}

		$dataTypeKey = $userAttribute['UserAttributeSetting']['data_type_key'];
		if (Hash::get($userAttribute, 'UserAttributeSetting.is_multilingualization')) {
			$field = 'UsersLanguage.' . Current::read('Language.id') . '.' . $attributeKey;
		} else {
			$field = 'User' . '.' . $attributeKey;
		}

		$output .= '<div class="row form-group">';
		$output .= '<div class="col-xs-12 col-sm-2">';
		$output .= '<strong>' . $userAttribute['UserAttribute']['name'] . '</strong>';
		$output .= '</div>';
		$output .= '<div class="col-xs-12 col-sm-10">';
		if (in_array($dataTypeKey, ['radio', 'checkbox', 'select'], true)) {
			$options = Hash::combine(
				$userAttribute['UserAttributeChoice'], '{n}.code', '{n}.name'
			);
			$output .= Hash::get($options, Hash::get($this->_View->request->data, $field, ''));
		} else {
			$output .= h(Hash::get($this->_View->request->data, $field));
		}
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

}
