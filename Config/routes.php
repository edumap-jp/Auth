<?php

Router::connect('/auth/*', array(
	'plugin' => 'auth', 'controller' => 'auth'
));
