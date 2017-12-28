<?php
/**
 * ExternalIdpUserFixture
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

/**
 * ExternalIdpUserFixture
 *
 * @author Mitsuru Mutaguchi <mutaguchi@opensource-workshop.jp>
 * @package NetCommons\Auth\Test\Fixture
 */
class ExternalIdpUserFixture extends CakeTestFixture {

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => '1',
			'user_id' => 'test1@idp.example.com',
			'is_shib_eptid' => '0',
			'status' => '2',
		),
	);

/**
 * Initialize the fixture.
 *
 * @return void
 */
	public function init() {
		require_once CakePlugin::path('Auth') . 'Config' . DS . 'Schema' . DS . 'schema.php';
		$this->fields = (new AuthShibbolethSchema())->tables[Inflector::tableize($this->name)];
		parent::init();
	}

}
