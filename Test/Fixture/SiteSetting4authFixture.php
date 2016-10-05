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
			'ForgotPass.issue_mail_subject',
			'ForgotPass.issue_mail_body',
			'ForgotPass.request_mail_subject',
			'ForgotPass.request_mail_body',
		);
		foreach ([5, 6, 7, 8, 9, 10, 11, 12] as $index) {
			if (in_array($this->records[$index]['key'], $targets, true)) {
				$records = $this->records[$index];
				$records['value'] = $records['key'] . ' ' . $records['language_id'];
				$this->records[$index] = $records;
			}
		}
	}

}
