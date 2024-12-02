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
	const CHALLENGE_VERSION = '0.9.14';

	/**
	 * Application instance.
	 *
	 * @var    Joomla\CMS\Application\CMSApplicationInterface
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Supported error codes.
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private static $errorCodes = array(
		'secret_missing',
		'secret_invalid',
		'solution_missing',
		'bad_request',
		'solution_invalid',
		'solution_timeout_or_duplicate',
	);

	/**
	 * Supported script's built-in languages.
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private static $languages = array(
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
	);

	/**
	 * Subresource integrity (SRI) hashes.
	 *
	 * @var    array
	 * @since  1.1.0
	 */
	private static $sriHashes = array(
		'widget.js' => 'sha384-mj5IHfz68+gZRS5sHXMYDk2Wx1J2VNjO+J19eWOZO/jSeReX5oz8a0Ym/0sGS5x2',
		'widget.min.js' => 'sha384-XyrHu8VURGef+JzBBalYdVjjws2k3+HYqjTM4VFGEKfaFqS4P6oylakWj8pSC3rp',
		'widget.module.js' => 'sha384-rUiN50rLaVrYvL/5JWY+Ct51117vgJH4MsblfxC2xtmfvihVW2On2nNCi0DAaZ9I',
		'widget.module.min.js' => 'sha384-baaFQwVx50RTScDEcqA9/cCzDT/qfNjglV95flgbiQSvB1gvdsRU+BmfQhz40Gks',
		'widget.polyfilled.min.js' => 'sha384-zVqUX3mHTjdFR5pVacQjHT/DPP6/GFMhrptMVKPnpkb/2fv0RaUBPWp6bMvruflI',
	);

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
	public function onCheckAnswer($code = null)
	{
		$language = $this->app->getLanguage();
		$language->load('plg_captcha_friendlycaptcha', JPATH_ADMINISTRATOR);

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
		$data = array(
			'solution' => $code,
			'secret' => $this->params->get('secret'),
			'sitekey' => $this->params->get('siteKey'),
		);

		// Try EU endpoint if selected.
		if ($this->params->get('euEndpoint'))
		{
			try
			{
				$response = $http->post('https://eu-api.friendlycaptcha.eu/api/v1/siteverify', $data);
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
				$response = $http->post('https://api.friendlycaptcha.com/api/v1/siteverify', $data);
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
				if ($errors = array_intersect($body->errors, self::$errorCodes))
				{
					throw new RuntimeException($language->_('PLG_CAPTCHA_FRIENDLYCAPTCHA_ERROR_' . strtoupper(reset($errors))));
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
	public function onDisplay($name = null, $id = null, $class = '')
	{
		$language = $this->app->getLanguage();
		$language->load('plg_captcha_friendlycaptcha', JPATH_ADMINISTRATOR);

		$attributes = array(
			'data-sitekey' => $this->params->get('siteKey', ''),
			'class' => rtrim('frc-captcha ' . $this->params->get('theme') . ' ' . $class),
		);

		if ($id !== null && $id !== '')
		{
			$attributes['id'] = $id;
		}

		if ($name !== null && $name !== '')
		{
			$attributes['data-solution-field-name'] = $name;
		}

		if ($this->params->get('euEndpoint'))
		{
			$attributes['data-puzzle-endpoint'] = 'https://eu-api.friendlycaptcha.eu/api/v1/puzzle';

			if ($this->params->get('euEndpointFallback'))
			{
				$attributes['data-puzzle-endpoint'] .= ',https://api.friendlycaptcha.com/api/v1/puzzle';
			}
		}

		if (is_string($this->params->get('startMode', null)))
		{
			$attributes['data-start'] = $this->params->get('startMode');
		}

		// Use script's built-in language if available.
		$languageTag = strtolower(str_replace('-', '_', $language->getTag()));

		// Use full tag first, fall back to short tag.
		$languageTags = array(
			$languageTag,
			strstr($languageTag, '_', true),
		);

		if ($foundLanguages = array_intersect($languageTags, self::$languages))
		{
			$attributes['data-lang'] = reset($foundLanguages);
		}

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
	public function onInit($id = null)
	{
		if ($this->params->get('polyfill'))
		{
			$legacyFilename = 'widget.polyfilled.min.js';
		}
		else
		{
			$legacyFilename = JDEBUG ? 'widget.js' : 'widget.min.js';
		}

		if ($this->params->get('useCdn'))
		{
			if ($this->params->get('cdn') === 'jsdelivr')
			{
				$baseUrl = 'https://cdn.jsdelivr.net/npm/friendly-challenge@';
			}
			else
			{
				$baseUrl = 'https://unpkg.com/friendly-challenge@';
			}

			$document = $this->app->getDocument();
			$moduleFilename = JDEBUG ? 'widget.module.js' : 'widget.module.min.js';

			$document->addScript(
				$baseUrl . self::CHALLENGE_VERSION . '/' . $moduleFilename,
				array(),
				array(
					'type' => 'module',
					'defer' => true,
					'crossorigin' => 'anonymous',
					'referrerpolicy' => 'no-referrer',
					'integrity' => self::$sriHashes[$moduleFilename],
				)
			);

			$document->addScript(
				$baseUrl . self::CHALLENGE_VERSION . '/' . $legacyFilename,
				array(),
				array(
					'nomodule' => 'true',
					'defer' => true,
					'crossorigin' => 'anonymous',
					'referrerpolicy' => 'no-referrer',
					'integrity' => self::$sriHashes[$legacyFilename],
				)
			);

			return true;
		}

		HTMLHelper::_(
			'script',
			'plg_captcha_friendlycaptcha/widget.module.min.js',
			array('relative' => true, 'version' => self::CHALLENGE_VERSION),
			array('type' => 'module', 'defer' => true)
		);
		HTMLHelper::_(
			'script',
			'plg_captcha_friendlycaptcha/' . $legacyFilename,
			array('relative' => true, 'version' => self::CHALLENGE_VERSION),
			array('nomodule' => 'true', 'defer' => true)
		);

		return true;
	}
}
