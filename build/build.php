<?php
// @todo proper build tools

define('PATH_ROOT', str_replace('\\', '/', dirname(__DIR__)));

if (!is_dir(__DIR__ . '/zips'))
{
	mkdir(__DIR__ . '/zips', 0755);
}

$manifest = simplexml_load_file(PATH_ROOT . '/code/plugins/captcha/friendlycaptcha/friendlycaptcha.xml');
$version = (string) $manifest->children()->version;

$zip = new ZipArchive;
$zipFile = __DIR__ . '/zips/plg_captcha_friendlycaptcha-' . $version . '.zip';
$zip->open($zipFile, ZipArchive::CREATE);
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

$hashes = '';

foreach (array_intersect(['sha512', 'sha384', 'sha256'], hash_algos()) as $algo)
{
	$hashes .= '<' . $algo . '>' . hash_file($algo, $zipFile) . '</' . $algo . ">\n";
}

file_put_contents(__DIR__ . '/zips/hashes.xml', $hashes);
