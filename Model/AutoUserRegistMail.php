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
		if (! isset($this->mail) || substr(get_class($this->mail), 0, 4) !== 'Mock') {
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
			$this->loadModels([
				'User' => 'Users.User',
			]);
			$data['subject'] = SiteSettingUtil::read('AutoRegist.acceptance_mail_subject');
			$data['body'] = SiteSettingUtil::read('AutoRegist.acceptance_mail_body');
			$data['email'] = $this->User->getMailAddressForAdmin();
			$data['url'] = Router::url('/auth/auto_user_regist/acceptance', true) .
						$user['User']['activate_parameter'];
		} else {
			return true;
		}

		$originalTags = $this->getOriginalTags($user);

		foreach ($data['email'] as $email) {
			$this->mail->mailAssignTag->setFixedPhraseSubject($data['subject']);
			$this->mail->mailAssignTag->setFixedPhraseBody($data['body']);
			$this->mail->mailAssignTag->assignTags($originalTags);
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
 * オリジナルタグを取得する
 *
 * @param array $user ユーザ情報
 * @return array
 */
	public function getOriginalTags($user) {
		$originalTags = [];

		if (isset($user['User'])) {
			foreach ($user['User'] as $key => $value) {
				$tagKey = 'X-' . strtoupper($key);
				$originalTags[$tagKey] = $value;
			}
		}

		if (isset($user['UsersLanguage'])) {
			foreach ($user['UsersLanguage'] as $langId => $userLanguages) {
				foreach ($userLanguages as $key => $value) {
					$tagKey = 'X-' . strtoupper($key) . '-' . $langId;
					$originalTags[$tagKey] = $value;
				}
			}
		}

		return $originalTags;
	}

}
