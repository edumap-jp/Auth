<?php
/**
 * 新規登録Model
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AppModel', 'Model');
App::uses('NetCommonsMail', 'Mails.Utility');
App::uses('AutoUserRegist', 'Auth.Model');

/**
 * 新規登録Model
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Model
 */
class AutoUserRegistMail extends AppModel {

/**
 * テーブル名
 *
 * @var bool
 */
	public $useTable = false;

/**
 * 新規登録のメール処理
 *
 * @param int $confirmation 完了確認ステータス
 * @param array $user ユーザ情報
 * @return bool
 */
	public function sendMail($confirmation, $user) {
		if ($confirmation === AutoUserRegist::CONFIRMATION_USER_OWN) {
			$data['subject'] = SiteSettingUtil::read('AutoRegist.approval_mail_subject');
			$data['body'] = SiteSettingUtil::read('AutoRegist.approval_mail_body');
			$data['email'] = array($user['User']['email']);
			$data['url'] = Router::url('/auth/auto_user_regist/approval', true) .
						$user['User']['activate_parameter'];

		} elseif ($confirmation === AutoUserRegist::CONFIRMATION_ADMIN_APPROVAL) {
			$data['subject'] = SiteSettingUtil::read('AutoRegist.acceptance_mail_subject');
			$data['body'] = SiteSettingUtil::read('AutoRegist.acceptance_mail_body');
			$data['email'] = $this->__getMailAddressForAdmin();
			$data['url'] = Router::url('/auth/auto_user_regist/acceptance', true) .
						$user['User']['activate_parameter'];
		} else {
			return true;
		}

		$mail = new NetCommonsMail();

		foreach ($data['email'] as $email) {
			$mail->mailAssignTag->setFixedPhraseSubject($data['subject']);
			$mail->mailAssignTag->setFixedPhraseBody($data['body']);
			$mail->mailAssignTag->assignTags(array('X-URL' => $data['url']));
			$mail->mailAssignTag->initPlugin(Current::read('Language.id'));
			$mail->initPlugin(Current::read('Language.id'));
			$mail->to($email);
			$mail->setFrom(Current::read('Language.id'));

			$mail->sendMailDirect();
		}

		return true;
	}

/**
 * 管理者ユーザのメールアドレス取得
 * ここでいう管理者権限とは、会員管理が使える権限のこと。
 *
 * @return array
 */
	private function __getMailAddressForAdmin() {
		$this->loadModels([
			'PluginsRole' => 'PluginManager.PluginsRole',
			'User' => 'Users.User',
		]);
		$roleKeys = $this->PluginsRole->find('list', array(
			'recursive' => -1,
			'fields' => array('id', 'role_key'),
			'conditions' => array(
				'plugin_key' => 'user_manager',
			),
		));

		//その他のメールアドレスも含める必要あり
		$mails = $this->User->find('all', array(
			'recursive' => -1,
			'fields' => array('email'),
			'conditions' => array(
				'role_key' => $roleKeys,
				'email !=' => '',
			),
		));

		$result = array();
		foreach ($mails as $mail) {
			$result = array_merge($result, Hash::extract($mail, '{s}.{s}'));
		}

		return $result;
	}

}
