<?php
/**
 * ログインテンプレート
 *
 * 独自ログインテンプレートを使いたい場合、
 * AuthXxxx/View/Element/login.ctpファイル作成すれば、自動的に読み込む
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
?>

<h2>
	<?php echo __d('auth', 'Login'); ?>
</h2>

<?php foreach ($authenticators as $plugin): ?>
	<article class="panel panel-default">
		<?php if ($plugin !== 'auth_general') : ?>
			<div class="panel-heading">
				<strong>
					<?php echo __d($plugin, Inflector::humanize($plugin)); ?>
				</strong>
			</div>
		<?php endif; ?>

		<?php echo $this->NetCommonsForm->create('User', array(
					'id' => Inflector::camelize($plugin),
					'url' => array(
						'plugin' => $plugin,
						'controller' => $plugin,
						'action' => 'login')
					)
			); ?>

			<?php if ($this->elementExists(Inflector::camelize($plugin) . '.login')) : ?>
				<?php echo $this->element(Inflector::camelize($plugin) . '.login'); ?>

			<?php else : ?>
				<div class="panel-body">
					<?php echo $this->NetCommonsForm->input('username',
									array('placeholder' => __d('auth', 'Please enter your username'))
								); ?>

					<?php echo $this->NetCommonsForm->input('password',
									array('placeholder' => __d('auth', 'Please enter your password'))
								); ?>

					<button class="btn btn-primary btn-block" type="submit">
						<?php echo __d('auth', 'Login'); ?>
					</button>
				</div>
			<?php endif; ?>
		<?php echo $this->NetCommonsForm->end(); ?>
	</article>
<?php endforeach;
