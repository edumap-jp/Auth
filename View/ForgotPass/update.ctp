<?php
/**
 * パスワード再発行画面のテンプレート
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
<?php echo $this->Wizard->navibar(ForgotPassController::WIZARD_UPDATE); ?>

<?php echo $this->MessageFlash->description(
		__d('auth', 'Enter the new password, please click the [OK] button.')
	); ?>

<article class="panel panel-default">
	<?php echo $this->NetCommonsForm->create('User'); ?>
		<div class="panel-body">
			<?php echo $this->NetCommonsForm->input('User.username', array(
				'label' => __d('auth', 'Username'),
				'placeholder' => __d('auth', 'Please enter your username.'),
				'required' => true
			)); ?>

			<?php echo $this->NetCommonsForm->input('User.password', array(
				'type' => 'password',
				'label' => __d('auth', 'New password'),
				'placeholder' => __d('net_commons', 'Only alphabets and numbers are allowed.'),
				'required' => true,
				'again' => true
			)); ?>

			<?php echo $this->NetCommonsForm->hidden('User.id'); ?>
		</div>

		<div class="panel-footer text-center">
			<?php echo $this->Wizard->buttons(ForgotPassController::WIZARD_UPDATE); ?>
		</div>

	<?php echo $this->NetCommonsForm->end(); ?>
</article>