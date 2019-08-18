<?php
/**
 * 新規登録完了画面のテンプレート
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
echo $this->element('Auth.meta');
?>

<h2>
	<?php echo __d('auth', 'Sign up'); ?>
</h2>
<?php echo $this->Wizard->navibar(AutoUserRegistController::WIZARD_COMPLETION); ?>

<?php echo $this->MessageFlash->description($message, array('class' => 'alert alert-success')); ?>

<?php echo $this->NetCommonsForm->create('AutoUserRegist'); ?>
	<article class="panel panel-default">
		<div class="panel-footer text-center">
			<?php echo $this->Button->cancel(__d('net_commons', 'Close'), $redirectUrl); ?>
		</div>
	</article>
<?php echo $this->NetCommonsForm->end();