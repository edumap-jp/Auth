<?php
/**
 * パスワード再発行受付画面のテンプレート
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
?>

<h2>
	<?php echo __d('auth', 'Forgot your Password?'); ?>
</h2>

<?php echo $this->MessageFlash->description(
		__d('auth', 'Please enter your registered e-mail address, and click on the [OK] button. <br>' .
					'We will send the activation key to obtain a new password to your registered e-mail address.')
	); ?>

<article class="panel panel-default">
	<?php echo $this->NetCommonsForm->create('ForgotPass'); ?>
		<div class="panel-body">
			<?php echo $this->NetCommonsForm->input('ForgotPass.email', array(
				'type' => 'text',
				'label' => __d('auth', 'email'),
				'required' => true,
			)); ?>
		</div>

		<div class="panel-footer text-center">
			<?php echo $this->Button->cancelAndSave(
						__d('net_commons', 'Cancel'),
						__d('net_commons', 'OK'),
						array('action' => 'login')
					);
				?>
		</div>
	<?php echo $this->NetCommonsForm->end(); ?>
</article>