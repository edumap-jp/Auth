<?php
/**
 * 新規登録受付画面のテンプレート
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
<?php echo $this->Wizard->navibar(AutoUserRegistController::WIZARD_REQUEST); ?>

<?php echo $this->MessageFlash->description(
		__d('auth', 'Fill out the following items, and press [NEXT] button.')
	); ?>

<?php echo $this->NetCommonsForm->create('AutoUserRegist'); ?>
	<article class="panel panel-default">
		<div class="panel-body">
			<?php echo $this->NetCommonsForm->hidden('User.id'); ?>
			<?php echo $this->NetCommonsForm->hidden('UsersLanguage.' . Current::read('Language.id') . '.id'); ?>
			<?php echo $this->NetCommonsForm->hidden('UsersLanguage.' . Current::read('Language.id') . '.language_id'); ?>
			<input type="password" value="" class="hidden">

			<?php
				foreach ($userAttributes as $userAttribute) {
					echo $this->AutoUserRegistForm->input($userAttribute, false);
				}
			?>

			<div class="form-group">
				<?php echo $this->NetCommonsForm->label(
					__d('auth', 'Terms of use')
				); ?>
				<div class="auto-user-regist-disclaimer form-control">
					<?php echo SiteSettingUtil::read('AutoRegist.disclaimer'); ?>
				</div>
			</div>

			<div class="form-group">
				<?php echo $this->NetCommonsForm->inlineCheckbox('AutoUserRegist.disclaimer', array(
					'label' => __d('auth', 'I agree to the above.'),
				)); ?>

				<?php echo $this->NetCommonsForm->error('AutoUserRegist.disclaimer'); ?>
			</div>
		</div>

		<div class="panel-footer text-center">
			<?php echo $this->Wizard->buttons(AutoUserRegistController::WIZARD_REQUEST); ?>
		</div>
	</article>
<?php echo $this->NetCommonsForm->end();