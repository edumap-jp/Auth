<div class="users form">
<?php echo $this->Session->flash('auth') ?>
	<h2 class="form-signin-heading"><?php echo __('Please enter your username and password') ?></h2>
<?php foreach ($authenticators as $authenticator): ?>
	<?php 	$plugin = Inflector::underscore(sprintf('Auth%s', $authenticator)); ?>
	<h3 class="form-signin-heading"><?php echo Inflector::humanize($authenticator) ?></h3>
	<?php echo $this->Form->create('User',
					array(
						'id' => $authenticator,
						'url' => array(
							'plugin' => $plugin,
							'controller' => $plugin,
							'action' => 'login'))) ?>
		<fieldset>
			<?php echo $this->Form->input('username',
							array(
								"class" => "form-control",
								"placeholder" => _("Username"))) ?>
			<?php echo $this->Form->input('password',
							array(
								"class" => "form-control",
								"placeholder" => _("Password")
							)
				) ?>
		</fieldset>
		<button class="btn btn-lg btn-primary btn-block" type="submit"><?php echo __d('net_commons', 'Login')?></button>
	<?php echo $this->Form->end() ?>
<?php endforeach ?>
</div>
