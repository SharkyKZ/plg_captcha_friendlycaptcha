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

$hashes = [];

foreach ($mediaFiles as $file)
{
	copy(PATH_ROOT .  '/node_modules/friendly-challenge/' . $file, $mediaPath . '/' . $file);
	$hashes[$file] = base64_encode(hash_file('sha384', $mediaPath . '/' . $file, true));
}

$filename = PATH_ROOT . '/code/plugins/captcha/friendlycaptcha/friendlycaptcha.php';
$code = file_get_contents($filename);
$pattern = '/\'(widget.*\.js)\'\s+=>\s+\'sha384\-(.*)\'/';

$code = preg_replace_callback(
	$pattern,
	static function ($match) use ($hashes)
	{
		return str_replace($match[2], $hashes[$match[1]], $match[0]);
	},
	$code
);

$json = json_decode(file_get_contents(PATH_ROOT . '/node_modules/friendly-challenge/package.json'));
$pattern = '/(const\s+CHALLENGE_VERSION\s+=\s+\')(.*)(\';)/';
preg_match($pattern, $code, $matches);
$code = preg_replace($pattern, '${1}' . $json->version . '$3', $code);

file_put_contents($filename, $code);
