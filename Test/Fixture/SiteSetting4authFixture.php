<?php
/**
 * SiteSettingFixture
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('SiteSetting4testFixture', 'SiteManager.Test/Fixture');

/**
 * SiteSettingFixture
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\SiteManager\Test\Fixture
 */
class SiteSetting4authFixture extends SiteSetting4testFixture {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'SiteSetting';

/**
 * Full Table Name
 *
 * @var string
 */
	public $table = 'site_settings';

/**
 * Records
 *
 * @var array
 */
	public $records = array();

/**
 * Initialize the fixture.
 *
 * @return void
 */
	public function init() {
		parent::init();

		$targets = array(
			// * パスワード再発行
			// ** 新規パスワード通知の件名
			'ForgotPass.issue_mail_subject',
			// ** パスワード通知メールの本文
			'ForgotPass.issue_mail_body',
			// ** 新規パスワード発行の件名
			'ForgotPass.request_mail_subject',
			// ** パスワード発行メールの本文
			'ForgotPass.request_mail_body',

			// * 入会設定
			// ** 利用許諾文
			'AutoRegist.disclaimer',
			// ** 会員登録承認メールの件名
			'AutoRegist.approval_mail_subject',
			// ** 会員登録承認メールの本文
			'AutoRegist.approval_mail_body',
			// ** 会員登録受付メールの件名
			'AutoRegist.acceptance_mail_subject',
			// ** 会員登録受付メールの本文
			'AutoRegist.acceptance_mail_body',
		);
		foreach ([5, 6, 7, 8, 9, 10, 11, 12, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36] as $index) {
			if (in_array($this->records[$index]['key'], $targets, true)) {
				$records = $this->records[$index];
				$records['value'] = $records['key'] . ' ' . $records['language_id'];
				$this->records[$index] = $records;
			}
		}
		if ($this->records[21]['key'] === 'AutoRegist.use_automatic_register') {
			$this->records[21]['value'] = '1';
		}
	}

}
