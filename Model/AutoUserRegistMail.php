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
App::uses('UserAttribute', 'UserAttributes.Model');

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
 * NetCommonsMail
 *
 * @var mixed
 */
	public $mail = null;

/**
 * Constructor. Binds the model's database table to the object.
 *
 * @param bool|int|string|array $id Set this ID for this model on startup,
 * can also be an array of options, see above.
 * @param string $table Name of database table to use.
 * @param string $ds DataSource connection name.
 * @see Model::__construct()
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
	public function __construct($id = false, $table = null, $ds = null) {
		parent::__construct($id, $table, $ds);

		//メール通知の場合、NetCommonsMailUtilityをメンバー変数にセットする。Mockであれば、newをしない。
		//テストでMockに差し替えが必要なための処理であるので、カバレッジレポートから除外する。
		//@codeCoverageIgnoreStart
		if (substr(get_class($this->mail), 0, 4) !== 'Mock') {
			$this->mail = new NetCommonsMail();
		}
		//@codeCoverageIgnoreEnd
	}

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

		foreach ($data['email'] as $email) {
			$this->mail->mailAssignTag->setFixedPhraseSubject($data['subject']);
			$this->mail->mailAssignTag->setFixedPhraseBody($data['body']);
			$this->mail->mailAssignTag->assignTags(array('X-URL' => $data['url']));
			$this->mail->mailAssignTag->initPlugin(Current::read('Language.id'));
			$this->mail->initPlugin(Current::read('Language.id'));
			$this->mail->to($email);
			$this->mail->setFrom(Current::read('Language.id'));

			$this->mail->sendMailDirect();
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

		$conditions = array(
			'role_key' => $roleKeys
		);
		$emailFields = $this->User->getEmailFields();
		$fields = $emailFields;
		$conditions['OR'] = array();
		foreach ($emailFields as $field) {
			$fields[] = sprintf(UserAttribute::MAIL_RECEPTION_FIELD_FORMAT, $field);
			$conditions['OR'][] = array(
				$field . ' !=' => '',
				sprintf(UserAttribute::MAIL_RECEPTION_FIELD_FORMAT, $field) => true
			);
		}
		$mails = $this->User->find('all', array(
			'recursive' => -1,
			'fields' => $fields,
			'conditions' => $conditions,
		));

		$result = array();
		foreach ($mails as $mail) {
			foreach ($emailFields as $field) {
				if ($mail['User'][sprintf(UserAttribute::MAIL_RECEPTION_FIELD_FORMAT, $field)]) {
					$result[] = $mail['User'][$field];
				}
			}
		}

		return $result;
	}

}
