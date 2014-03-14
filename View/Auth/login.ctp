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
			<h2 class="form-signin-heading"><?php echo __('Please enter your username and password') ?></h2>
				<?php echo $this->Form->input('username',
						array("class"=>"form-control",
							"placeholder"=>_("Username"))) ?>
				<?php echo $this->Form->input('password',
							array(
								"class"=>"form-control",
								"placeholder"=>_("Password")
							)
				) ?>
		</fieldset>
	<button class="btn btn-lg btn-primary btn-block" type="submit"><?php echo __('Login')?></button>
<?php endforeach ?>

</div>



