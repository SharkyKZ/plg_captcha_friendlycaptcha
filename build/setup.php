<?php
// @todo proper build tools

define('PATH_ROOT', str_replace('\\', '/', dirname(__DIR__)));

$mediaPath = PATH_ROOT . '/code/media/plg_captcha_friendlycaptcha/js';
$mediaFiles = [
	'widget.js',
	'widget.min.js',
	'widget.module.js',
	'widget.module.min.js',
	'widget.polyfilled.min.js',
];

if (!is_dir($mediaPath))
{
	mkdir($mediaPath, 0755, true);
}

foreach ($mediaFiles as $file)
{
	copy(PATH_ROOT .  '/node_modules/friendly-challenge/' . $file, $mediaPath . '/' . $file);
}
