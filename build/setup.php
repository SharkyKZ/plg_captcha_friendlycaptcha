<?php
// @todo proper build tools

define('PATH_ROOT', str_replace('\\', '/', dirname(__DIR__)));

if (!is_dir(__DIR__ . '/zips'))
{
	mkdir(__DIR__ . '/zips', 0755);
}

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

$hashes = [];

foreach ($mediaFiles as $file)
{
	copy(PATH_ROOT .  '/node_modules/friendly-challenge/' . $file, $mediaPath . '/' . $file);
	$hashes[] = base64_encode(hash('sha384', file_get_contents($mediaPath . '/' . $file), true));
}

file_put_contents(__DIR__ . '/zips/sri.txt', implode("\n", $hashes));
