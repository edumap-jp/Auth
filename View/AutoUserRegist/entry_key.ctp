<?php
/**
 * 認証キー確認画面のテンプレート
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
?>

<h2>
	<?php echo __d('auth', 'Sign up'); ?>
</h2>
<?php echo $this->Wizard->navibar(AutoUserRegistController::WIZARD_ENTRY_KEY); ?>

<?php echo $this->MessageFlash->description(
		__d('auth', 'Entry secret key, and press [NEXT] button.')
	); ?>

<?php echo $this->NetCommonsForm->create('AutoUserRegist'); ?>
	<article class="panel panel-default">
		<div class="panel-body">
			<?php echo $this->NetCommonsForm->input('AutoUserRegist.secret_key', array(
				'type' => 'password',
				'label' => __d('auth', 'Secret key'),
				'required' => true,
			)); ?>
		</div>

		<div class="panel-footer text-center">
			<?php echo $this->Wizard->buttons(AutoUserRegistController::WIZARD_ENTRY_KEY); ?>
		</div>
	</article>
<?php echo $this->NetCommonsForm->end();