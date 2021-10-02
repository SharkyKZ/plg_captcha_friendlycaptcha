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
	copy('node_modules/friendly-challenge/' . $file, $mediaPath . '/' . $file);
}

$zip = new ZipArchive;
$zip->open(PATH_ROOT .'/plg_captcha_friendlycaptcha-1.0.0.zip', ZipArchive::CREATE);
$directories = [PATH_ROOT . '/code/plugins/captcha/friendlycaptcha', PATH_ROOT . '/code/media/plg_captcha_friendlycaptcha'];

foreach ($directories as $directory)
{
	$iterator = new RecursiveDirectoryIterator($directory);
	$iterator2 = new RecursiveIteratorIterator($iterator);

	foreach ($iterator2 as $file)
	{
		if ($file->isFile())
		{
			$zip->addFile(
				$file->getPathName(),
				str_replace(['\\', PATH_ROOT . '/code/', 'plugins/captcha/friendlycaptcha/', 'media/plg_captcha_friendlycaptcha/'], ['/', '', '', 'media/'], $file->getPathName())
			);
		}
	}
}

$zip->close();
