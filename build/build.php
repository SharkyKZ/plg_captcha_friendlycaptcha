<?php

use Sharky\Joomla\PluginBuildScript\Script;

require __DIR__ . '/vendor/autoload.php';

(
	new Script(
		str_replace('\\', '/', dirname(__DIR__)),
		str_replace('\\', '/', __DIR__),
		'friendlycaptcha',
		'captcha',
		'plg_captcha_friendlycaptcha',
		'SharkyKZ',
		'Captcha - Friendly Captcha',
		'Friendly Captcha anti-spam plugin.',
		'(5\.|4\.|3\.([89]|10))',
		'5.4',
		$argv[1] ?? null,
	)
)->build();
