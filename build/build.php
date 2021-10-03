<?php
// @todo proper build tools

define('PATH_ROOT', str_replace('\\', '/', dirname(__DIR__)));

$manifest = simplexml_load_file(PATH_ROOT . '/code/plugins/captcha/friendlycaptcha/friendlycaptcha.xml');
$version = (string) $manifest->children()->version;

if(!is_dir(__DIR__ . '/zips'))
{
	mkdir(__DIR__ . '/zips', 0755);
}

$zip = new ZipArchive;
$zip->open(__DIR__ . '/zips/plg_captcha_friendlycaptcha-' . $version . '.zip', ZipArchive::CREATE);
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
