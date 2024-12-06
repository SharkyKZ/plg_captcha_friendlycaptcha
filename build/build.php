#!/usr/bin/env php
<?php

use Sharky\Joomla\PluginBuildScript\Script;

require __DIR__ . '/vendor/autoload.php';

$script = new class(
	str_replace('\\', '/', dirname(__DIR__)),
	str_replace('\\', '/', __DIR__),
	'friendlycaptcha',
	'captcha',
	'plg_captcha_friendlycaptcha',
	'SharkyKZ',
	'Captcha - Friendly Captcha',
	'Friendly Captcha anti-spam plugin.',
	'(5\.|4\.|3\.([89]|10))',
	'7.2',
	$argv[1] ?? null,
) extends Script
{
	public function build(): void
	{
		passthru('npm install');

		$mediaFiles = [
			'friendly-challenge' => [
				'widget.js',
				'widget.min.js',
				'widget.module.js',
				'widget.module.min.js',
				'widget.polyfilled.min.js',
			],
			'@friendlycaptcha/sdk' => [
				'site.js',
				'site.min.js',
				'site.compat.js',
				'site.compat.min.js',
			],
		];

		$jsPath = $this->mediaDirectory . '/js';

		if (!is_dir($jsPath))
		{
			mkdir($jsPath, 0755, true);
		}

		$hashes = [];

		foreach ($mediaFiles as $directory => $files)
		{
			foreach ($files as $file)
			{
				$destinationFile = $jsPath . '/' . $file;
				$sourceFile = $this->rootPath .  '/node_modules/' . $directory . '/' . $file;
				$sourceHash = hash_file('sha384', $sourceFile, true);

				if (!is_file($destinationFile) || hash_file('sha384', $destinationFile, true) !== $sourceHash)
				{
					copy($sourceFile, $destinationFile);
				}

				$hashes[$file] = base64_encode($sourceHash);
			}
		}

		$filename = $this->pluginDirectory . '/friendlycaptcha.php';
		$sourceCode = file_get_contents($filename);
		$pattern = '/\'([a-z\.]+\.js)\'\s+=>\s+\'sha384\-(.*)\'/';

		$code = preg_replace_callback(
			$pattern,
			static function ($match) use ($hashes)
			{
				return str_replace($match[2], $hashes[$match[1]], $match[0]);
			},
			$sourceCode
		);

		foreach (['friendly-challenge' => 'CHALLENGE_VERSION', '@friendlycaptcha/sdk' => 'SDK_VERSION'] as $directory => $constant)
		{
			$json = json_decode(file_get_contents($this->rootPath . '/node_modules/' . $directory . '/package.json'));
			$pattern = '/(const\s+' . $constant . '\s+=\s+\')(.*)(\';)/';
			preg_match($pattern, $code, $matches);
			$code = preg_replace($pattern, '${1}' . $json->version . '$3', $code);
		}

		if ($sourceCode !== $code)
		{
			file_put_contents($filename, $code);
		}

		parent::build();
	}
};

$script->build();
