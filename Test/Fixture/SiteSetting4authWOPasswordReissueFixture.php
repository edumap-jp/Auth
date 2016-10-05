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
class SiteSetting4authWOPasswordReissueFixture extends SiteSetting4testFixture {

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
		if ($this->records[4]['key'] === 'ForgotPass.use_password_reissue') {
			$this->records[4]['value'] = '0';
		}
	}

}
