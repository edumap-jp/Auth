<?php
/**
 * ログインテンプレート
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

?>

<?php echo $this->NetCommonsForm->create('User', array(
		'id' => Inflector::camelize($plugin),
		'url' => array(
			'plugin' => $plugin,
			'controller' => $plugin,
			'action' => 'login')
	)
); ?>

	TestAuth/View/Elements/login.ctp

<?php echo $this->NetCommonsForm->end();
