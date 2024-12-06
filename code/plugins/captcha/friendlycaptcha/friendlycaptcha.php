<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */

defined('_JEXEC') || exit;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Friendly Captcha plugin.
 *
 * @since  1.0.0
 */
final class PlgCaptchaFriendlyCaptcha extends CMSPlugin
{
	/**
	 * Challenge library version.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private const CHALLENGE_VERSION = '0.9.18';

	/**
	 * SDK version.
	 *
	 * @var    string
	 * @since  2.0.0
	 */
	private const SDK_VERSION = '0.1.11';

	/**
	 * Application instance.
	 *
	 * @var    Joomla\CMS\Application\CMSApplicationInterface
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Supported endpoints.
	 *
	 * @var    array
	 * @since  2.0.0
	 */
	private const ENDPOINTS = [
		'v1' => [
			'global' => 'https://api.friendlycaptcha.com/api/v1/siteverify',
			'eu' => 'https://eu-api.friendlycaptcha.eu/api/v1/siteverify,'
		],
		'v2' => [
			'global' => 'https://global.frcapi.com/api/v2/captcha/siteverify',
			'eu' => 'https://eu.frcapi.com/api/v2/captcha/siteverify',
		],
	];

	/**
	 * Supported error codes.
	 *
	 * @var    array
	 * @since  2.0.0
	 */
	private const ERROR_CODES = [
		// v2
		'auth_required',
		'auth_invalid',
		'sitekey_invalid',
		'response_missing',
		'response_invalid',
		'response_timeout',
		'response_duplicate',
		'bad_request',
		// v1
		'secret_missing',
		'secret_invalid',
		'solution_missing',
		'solution_invalid',
		'solution_timeout_or_duplicate',
		// v2 and v1
		'bad_request',
	];

	/**
	 * Supported widget's built-in languages.
	 *
	 * @var    array
	 * @since  2.0.0
	 */
	private const WIDGET_LANGUAGES = [
		'en',
		'fr',
		'de',
		'it',
		'nl',
		'pt',
		'es',
		'ca',
		'da',
		'ja',
		'sv',
		'ru',
		'tr',
		'el',
		'uk',
		'bg',
		'cs',
		'sk',
		'no',
		'nb',
		'fi',
		'lv',
		'lt',
		'pl',
		'et',
		'hr',
		'sr',
		'sl',
		'hu',
		'ro',
		'zh',
		'zh_tw',
		'vi',
		'he',
		'th',
		'kr',
		'ar',
	];

	/**
	 * Subresource integrity (SRI) hashes.
	 *
	 * @var    array
	 * @since  2.0.0
	 */
	private const SRI_HASHES = [
		'widget.js' => 'sha384-bDcCvqtL2t/3xHHjreWK/0cSxHzV1s2cDeCViSX1oVZqNf3fgKTqA0C6ateqCmip',
		'widget.min.js' => 'sha384-344krdL8/dCWbYTwPpunVq4cG1ZuhEV9s4F3EyEAjo9bvpROhBAZoqSu5M6n8EQY',
		'widget.module.js' => 'sha384-HoqNPMPnveXKHVAAzhUNV7cEDnCimiihyM8UK/NeGPrZDlvGEwpPeC9GHR6ybEw2',
		'widget.module.min.js' => 'sha384-lz4OKju2av+OYhIe9iWjPNIGhEktRtZ5LB5DiJBoQlP8sm/6yE9gzZltQcOy/Jea',
		'widget.polyfilled.min.js' => 'sha384-o6dGARcN5TQtRkzU1xuPKw5a59ZZRosPNEriWAVeL+nJzM7ADSXH7lvc5fxVeX5M',
		'site.js' => 'sha384-SQF3BbAihtyvygXqc3LeNHrl8xIClkjwSQTFWIySkF2Naj79oAEkmoXLsAkteiDa',
		'site.min.js' => 'sha384-wMMeVRh/wgZ+j3R6zaSs2Kid5B7klcKhwRFJQ6kDi2wb8xeB5lTfLQOOJxZvHzZM',
		'site.compat.js' => 'sha384-u3CJMnBKATQGSkKw21iUB6dggip6VNcJeM8L4AH7ZkC+U58yUjQdK7M9KKTqPOrM',
		'site.compat.min.js' => 'sha384-vQqAyn0b+6jTGPmhfs3asbnBonFvT1bziGRkUOgBMMrqtz3lXwXI6wGrgg2kW/t/',
	];

	/**
	 * Makes HTTP request to remote service to verify user's answer.
	 *
	 * @param   string|null  $code  Answer provided by user.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 * @throws  RuntimeException
	 */
	public function onCheckAnswer($code = null): bool
	{
		$language = $this->app->getLanguage();
		$language->load('plg_captcha_friendlycaptcha', JPATH_ADMINISTRATOR);
		$apiVersion = $this->params->get('apiVersion', 'v1');

		if ($code === null || $code === '')
		{
			// No answer provided, form was manipulated.
			throw new RuntimeException($language->_('PLG_CAPTCHA_FRIENDLYCAPTCHA_ERROR_EMPTY_RESPONSE'));
		}

		try
		{
			$http = HttpFactory::getHttp();
		}
		// No HTTP transports supported.
		catch (Exception $exception)
		{
			if ($this->params->get('strictMode'))
			{
				throw new RuntimeException($language->_('PLG_CAPTCHA_FRIENDLTYCAPTCHA_ERROR_HTTP_TRANSPORTS'));
			}

			return true;
		}

		$body = null;

		if ($apiVersion === 'v1')
		{
			$headers = [];
			$data = [
				'solution' => $code,
				'secret' => $this->params->get('secret'),
				'sitekey' => $this->params->get('siteKey'),
			];
		}
		else
		{
			$headers = ['X-Api-Key' => $this->params->get('secret')];
			$data = ['response' => $code, 'sitekey' => $this->params->get('siteKey')];
		}


		// Try EU endpoint if selected.
		if ($this->params->get('euEndpoint'))
		{
			try
			{
				$response = $http->post(self::ENDPOINTS[$apiVersion]['eu'], $data, $headers);
				$body = json_decode($response->body);
			}
			// Connection or transport error.
			catch (RuntimeException $exception)
			{
				// If fallback endpoint is not used, return early.
				if (!$this->params->get('euEndpointFallback'))
				{
					if ($this->params->get('strictMode'))
					{
						throw new RuntimeException($language->_('PLG_CAPTCHA_FRIENDLTYCAPTCHA_ERROR_HTTP_CONNECTION'));
					}

					return true;
				}
			}
		}

		// Try global endpoint.
		if ($body === null)
		{
			try
			{
				$response = $http->post(self::ENDPOINTS[$apiVersion]['global'], $data, $headers);
				$body = json_decode($response->body);
			}
			// Connection or transport error.
			catch (RuntimeException $exception)
			{
				if ($this->params->get('strictMode'))
				{
					throw new RuntimeException($language->_('PLG_CAPTCHA_FRIENDLTYCAPTCHA_ERROR_HTTP_CONNECTION'));
				}

				return true;
			}
		}

		// Remote service error.
		if ($body === null)
		{
			if ($this->params->get('strictMode'))
			{
				throw new RuntimeException($language->_('PLG_CAPTCHA_FRIENDLYCAPTCHA_ERROR_INVALID_RESPONSE'));
			}

			return true;
		}

		if (!isset($body->success) || $body->success !== true)
		{
			// If error codes are pvovided, use them for language strings.
			if (!empty($body->errors) && is_array($body->errors))
			{
				if ($errors = array_intersect($body->errors, self::ERROR_CODES))
				{
					$languageKey = 'PLG_CAPTCHA_FRIENDLYCAPTCHA_ERROR_' . strtoupper($errors[array_key_first($errors)]);

					if ($language->hasKey($languageKey))
					{
						throw new RuntimeException($language->_($languageKey));
					}
				}
			}
			elseif(!empty($body->error->error_code))
			{
				$languageKey = 'PLG_CAPTCHA_FRIENDLYCAPTCHA_ERROR_' . strtoupper($body->error->error_code);

				if ($language->hasKey($languageKey))
				{
					throw new RuntimeException($language->_($languageKey));
				}
			}

			throw new RuntimeException($language->_('PLG_CAPTCHA_FRIENDLYCAPTCHA_ERROR_VERIFICATION_FAILED'));
		}

		return true;
	}

	/**
	 * Generates HTML field markup.
	 *
	 * @param   string|null  $name   The name of the field.
	 * @param   string|null  $id     The id of the field.
	 * @param   string|null  $class  The class of the field.
	 *
	 * @return  string  The HTML to be embedded in the form.
	 *
	 * @since  1.0.0
	 */
	public function onDisplay($name = null, $id = null, $class = ''): string
	{
		$apiVersion = $this->params->get('apiVersion', 'v1');
		$language = $this->app->getLanguage();
		$language->load('plg_captcha_friendlycaptcha', JPATH_ADMINISTRATOR);

		$attributes = [
			'id' => $id,
			'data-sitekey' => $this->params->get('siteKey', ''),
			'data-start' => $this->params->get('startMode'),
		];

		$class = 'frc-captcha ' . $class;

		if ($apiVersion === 'v1')
		{
			$attributes['data-solution-field-name'] = $name;
			$class .= ' ' . $this->params->get('theme', '');

			// Use script's built-in language if available.
			$languageTag = strtolower(str_replace('-', '_', $language->getTag()));

			// Use full tag first, fall back to short tag.
			$languageTags = [$languageTag, strstr($languageTag, '_', true)];

			if ($foundLanguages = array_intersect($languageTags, self::WIDGET_LANGUAGES))
			{
				$attributes['data-lang'] = $foundLanguages[array_key_first($foundLanguages)];
			}

			if ($this->params->get('euEndpoint'))
			{
				$attributes['data-puzzle-endpoint'] = 'https://eu-api.friendlycaptcha.eu/api/v1/puzzle';

				if ($this->params->get('euEndpointFallback'))
				{
					$attributes['data-puzzle-endpoint'] .= ',https://api.friendlycaptcha.com/api/v1/puzzle';
				}
			}
		}
		else
		{
			$attributes['data-theme'] = $this->params->get('theme');
			$attributes['data-form-field-name'] = $name;

			if ($this->params->get('euEndpoint'))
			{
				$attributes['data-api-endpoint'] = 'eu';
			}
		}

		$attributes['class'] = rtrim($class);

		// Filter out empty attributes.
		$attributes = array_filter(
			$attributes,
			static function ($v)
			{
				return $v || is_numeric($v);
			}
		);

		// Escape attributes.
		$attributes = array_map(
			static function ($value)
			{
				return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
			},
			$attributes
		);

		$html = '<div ' . ArrayHelper::toString($attributes) . '></div>';

		ob_start();
		include PluginHelper::getLayoutPath($this->_type, $this->_name, 'noscript');
		$html .= ob_get_clean();

		return $html;
	}

	/**
	 * Initialises the captcha.
	 *
	 * @param   string|null  $id  The id of the field.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 * @throws  RuntimeException
	 */
	public function onInit($id = null): bool
	{
		if ($this->params->get('apiVersion', 'v1') === 'v1')
		{
			$version = self::CHALLENGE_VERSION;
			$moduleFilename = JDEBUG ? 'widget.module.js' : 'widget.module.min.js';

			if ($this->params->get('polyfill'))
			{
				$legacyFilename = 'widget.polyfilled.min.js';
			}
			else
			{
				$legacyFilename = JDEBUG ? 'widget.js' : 'widget.min.js';
			}
		}
		else
		{
			$version = self::SDK_VERSION;
			$moduleFilename = JDEBUG ? 'site.js' : 'site.min.js';
			$legacyFilename = JDEBUG ? 'site.compat.js' : 'site.compat.min.js';
		}

		if ($this->params->get('useCdn'))
		{
			$baseUrl = $this->getCdnBaseUrl();
			$document = $this->app->getDocument();

			$document->addScript(
				$baseUrl . '/' . $moduleFilename,
				[],
				[
					'type' => 'module',
					'defer' => true,
					'crossorigin' => 'anonymous',
					'referrerpolicy' => 'no-referrer',
					'integrity' => self::SRI_HASHES[$moduleFilename],
				]
			);

			$document->addScript(
				$baseUrl . '/' . $legacyFilename,
				[],
				[
					'nomodule' => true,
					'defer' => true,
					'crossorigin' => 'anonymous',
					'referrerpolicy' => 'no-referrer',
					'integrity' => self::SRI_HASHES[$legacyFilename],
				]
			);

			return true;
		}

		HTMLHelper::_(
			'script',
			'plg_captcha_friendlycaptcha/' . $moduleFilename,
			['relative' => true, 'version' => $version],
			['type' => 'module', 'defer' => true]
		);
		HTMLHelper::_(
			'script',
			'plg_captcha_friendlycaptcha/' . $legacyFilename,
			['relative' => true, 'version' => $version],
			['nomodule' => true, 'defer' => true]
		);

		return true;
	}

	private function getCdnBaseUrl(): string
	{
		$baseUrl = $this->params->get('cdn') === 'jsdelivr' ? 'https://cdn.jsdelivr.net/npm/' : 'https://unpkg.com/';

		if ($this->params->get('apiVersion', 'v1') === 'v1')
		{
			return $baseUrl . 'friendly-challenge@' . self::CHALLENGE_VERSION;
		}

		return $baseUrl . '@friendlycaptcha/sdk@' . self::SDK_VERSION;
	}
}
