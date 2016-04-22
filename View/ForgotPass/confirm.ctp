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
<?php echo $this->Wizard->navibar(ForgotPassController::WIZARD_CONFIRM); ?>

<?php echo $this->MessageFlash->description(
		__d('auth', 'Enter the authentication key that has been notified to the entered email address, please click on the [OK] button. <br>' .
					'If the authentication key does not reach, please also check the junk mail. ' .
					'If you do not arrive in junk e-mail, please try again. ' .
					'If you do not reach even to try several times, please consult your system administrator.')
	); ?>

<article class="panel panel-default">
	<?php echo $this->NetCommonsForm->create('ForgotPass'); ?>
		<div class="panel-body">
			<?php echo $this->NetCommonsForm->input('ForgotPass.authorization_key', array(
				'type' => 'text',
				'label' => __d('auth', 'Authorization key'),
				'required' => true,
			)); ?>
		</div>

		<div class="panel-footer text-center">
			<?php echo $this->Wizard->buttons(ForgotPassController::WIZARD_CONFIRM); ?>
		</div>
	<?php echo $this->NetCommonsForm->end(); ?>
</article>