<?php
// Get all available authentication plugins
$authenticators = array();
$plugins = App::objects('plugins');
foreach ($plugins as $plugin) {
	if (preg_match('/^Auth[\w]+/', $plugin)) {
		$authenticators[] = $plugin;
	}
}
?>
<div class="users form">
<?php echo $this->Session->flash('auth') ?>
<?php foreach ($authenticators as $authenticator): ?>
<?php 	$plugin = Inflector::underscore($authenticator); ?>
<?php 	echo $this->Form->create('User',
					array(
						'url' => array(
							'plugin'     => $plugin,
							'controller' => $plugin,
							'action'     => 'login'))) ?>
		<fieldset>
				<legend>
						<?php echo __('Please enter your username and password') ?>
				</legend>
				<?php echo $this->Form->input('username') ?>
				<?php echo $this->Form->input('password') ?>
		</fieldset>
<?php 	echo $this->Form->end(__('Login')) ?>
<?php endforeach ?>

</div>
