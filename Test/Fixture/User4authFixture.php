<?php
/**
 * UserFixture
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('UserFixture', 'Users.Test/Fixture');

/**
 * UserFixture
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Users\Model
 */
class User4authFixture extends UserFixture {

/**
 * Model name
 *
 * @var string
 */
	public $name = 'User';

/**
 * Full Table Name
 *
 * @var string
 */
	public $table = 'users';

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => '1',
			'username' => 'system_administrator',
			'password' => 'system_administrator',
			'key' => 'system_admin',
			'is_deleted' => false,
			'handlename' => 'System Administrator',
			'is_handlename_public' => '1',
			'is_avatar_public' => '0',
			'role_key' => 'system_administrator',
			'status' => '1',
			'email' => 'system_admin_1@exapmle.com',
			'moblie_mail' => 'system_admin_2@exapmle.com',
			'last_login' => '2016-09-14 12:18:45',
			'created' => '2015-08-15 06:12:30',
			'created_user' => '1',
			'modified' => '2015-08-15 06:12:30',
			'modified_user' => '1',
		),
		array(
			'id' => '2',
			'username' => 'site_manager',
			'password' => 'site_manager',
			'key' => 'site_manager',
			'is_deleted' => false,
			'handlename' => 'Site Manager',
			'is_handlename_public' => '1',
			'is_avatar_public' => '0',
			'role_key' => 'administrator',
			'status' => '1',
			'email' => 'site_manager@exapmle.com',
			'created' => '2015-08-15 06:12:30',
			'created_user' => '1',
			'modified' => '2015-08-15 06:12:30',
			'modified_user' => '1',
		),
		array(
			'id' => '3',
			'username' => 'chief_editor',
			'password' => 'chief_editor',
			'key' => 'chief_editor',
			'is_deleted' => false,
			'handlename' => 'Chief Editor',
			'is_handlename_public' => '1',
			'is_avatar_public' => '1',
			'role_key' => 'common_user',
			'status' => '1',
			'created' => '2015-08-15 06:12:30',
			'created_user' => '1',
			'modified' => '2015-08-15 06:12:30',
			'modified_user' => '1',
		),
		array(
			'id' => '4',
			'username' => 'editor',
			'password' => 'editor',
			'key' => 'editor',
			'is_deleted' => false,
			'handlename' => 'Editor',
			'is_handlename_public' => '1',
			'is_avatar_public' => '0',
			'role_key' => 'common_user',
			'status' => '1',
			'created' => '2015-08-15 06:12:30',
			'created_user' => '1',
			'modified' => '2015-08-15 06:12:30',
			'modified_user' => '1',
		),
		array(
			'id' => '5',
			'username' => 'general_user',
			'password' => 'general_user',
			'key' => 'general_user',
			'is_deleted' => false,
			'handlename' => 'General User',
			'is_handlename_public' => '1',
			'is_avatar_public' => '0',
			'role_key' => 'common_user',
			'status' => '1',
			'created' => '2015-08-15 06:12:30',
			'created_user' => '1',
			'modified' => '2015-08-15 06:12:30',
			'modified_user' => '1',
		),
		array(
			'id' => '6',
			'username' => 'visitor',
			'password' => 'visitor',
			'key' => 'visitor',
			'is_deleted' => false,
			'handlename' => 'Visitor',
			'is_handlename_public' => '1',
			'is_avatar_public' => '0',
			'role_key' => 'common_user',
			'status' => '1',
			'created' => '2015-08-15 06:12:30',
			'created_user' => '1',
			'modified' => '2015-08-15 06:12:30',
			'modified_user' => '1',
		),
		array(
			'id' => '7',
			'username' => 'deleted',
			'password' => 'deleted',
			'key' => 'deleted',
			'is_deleted' => true,
			'handlename' => 'Deleted',
			'is_handlename_public' => '1',
			'is_avatar_public' => '0',
			'role_key' => 'common_user',
			'status' => '1',
			'email' => 'deleted_1@exapmle.com',
			'moblie_mail' => 'deleted_2@exapmle.com',
			'created' => '2015-08-15 06:12:30',
			'created_user' => '1',
			'modified' => '2015-08-15 06:12:30',
			'modified_user' => '1',
		),
	);

}
