<?php

require (dirname(__DIR__)) . '/build-script/script.php';

(
	new PluginBuildScript(
		str_replace('\\', '/', dirname(__DIR__)),
		str_replace('\\', '/', __DIR__),
		'friendlycaptcha',
		'captcha',
		'plg_captcha_friendlycaptcha',
		'SharkyKZ',
		'Captcha - Friendly Captcha',
		'Friendly Captcha anti-spam plugin.',
		'(5\.|4\.|3\.([89]|10))',
		'5.3.10',
	)
)->build();
