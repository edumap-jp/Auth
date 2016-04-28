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
?>

<h2>
	<?php echo __d('auth', 'Sign up'); ?>
</h2>
<?php echo $this->Wizard->navibar(AutoUserRegistController::WIZARD_COMPLETION); ?>

<?php echo $this->MessageFlash->description($message); ?>

<?php echo $this->NetCommonsForm->create('AutoUserRegist'); ?>
	<article class="panel panel-default">
		<div class="panel-heading">
			<?php echo __d('auth', 'Registered info'); ?>
		</div>

		<div class="panel-body">
			<?php foreach ($userAttributes as $id => $userAttribute) : ?>
				<?php echo $this->AutoUserRegistForm->input($userAttribute, true); ?>
			<?php endforeach; ?>
		</div>

		<div class="panel-footer text-center">
			<?php echo $this->Button->save(__d('net_commons', 'OK'), array('url' => $redirectUrl)); ?>
		</div>
	</article>
<?php echo $this->NetCommonsForm->end();