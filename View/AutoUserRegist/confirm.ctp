<?php
/**
 * 新規登録確認画面のテンプレート
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
<?php echo $this->Wizard->navibar(AutoUserRegistController::WIZARD_CONFIRM); ?>

<?php echo $this->MessageFlash->description(
		__d('auth', 'Check the input contents. ' .
					'If there is no content is a problem, please click on the [NEXT] button. ' .
					'If you re-fix, please click on the [BACK] button.')
	); ?>

<?php echo $this->NetCommonsForm->create('AutoUserRegist'); ?>
	<article class="panel panel-default">
		<div class="panel-body">
			<?php
				foreach ($userAttributes as $userAttribute) {
					echo $this->AutoUserRegistForm->input($userAttribute, true);
				}
			?>
		</div>

		<div class="panel-footer text-center">
			<?php echo $this->Wizard->buttons(AutoUserRegistController::WIZARD_CONFIRM); ?>
		</div>
	</article>
<?php echo $this->NetCommonsForm->end();

