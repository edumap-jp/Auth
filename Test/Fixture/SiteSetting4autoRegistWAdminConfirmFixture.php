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

App::uses('SiteSetting4authFixture', 'Auth.Test/Fixture');

/**
 * SiteSettingFixture
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Auth\Test\Fixture
 */
class SiteSetting4autoRegistWAdminConfirmFixture extends SiteSetting4authFixture {

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

		if ($this->records[22]['key'] === 'AutoRegist.confirmation') {
			$this->records[22]['value'] = '2';
		}
	}

}
