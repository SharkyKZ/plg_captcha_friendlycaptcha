<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */

defined('_JEXEC') or exit;

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
	const CHALLENGE_VERSION = '0.9.1';

	/**
	 * Application instance.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplicationInterface
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
	);

	/**
	 * Subresource integrity (SRI) hashes.
	 *
	 * @var    array
	 * @since  1.1.0
	 */
	private static $sriHashes = array(
		'widget.js' => 'sha384-uPZMjx4UXN55d/DJwVxJC51OgKYbFVKeme91II1XJkHl5oH1xmD97qghYGyWTYF4',
		'widget.min.js' => 'sha384-yqBOvrvDyL32AH5x+tIzoo5I5kWw1r1ff31hAxRtf5igUHdgr6jF4i+Qpq3//T4C',
		'widget.module.js' => 'sha384-4ivaJeMa0JUvD+0we1fKARcGwTMbRsW4VI6OAZNbAkOKG0axwIq5StFlV+9DpGNZ',
		'widget.module.min.js' => 'sha384-w+QeEdeEp/WOlNVE57LGgH/QMDphu/YBJ0jMmN9xXkjlLcQ7xrpHSO7vS1sgDA4x',
		'widget.polyfilled.min.js' => 'sha384-fGVJEvKbe66XF4jezl34aFy6WZUfjA+yGHSySAev0ttw4QaCmDcAyBYPurfhLvEJ',
	);

	/**
	 * Makes HTTP request to remote service to verify user's answer.
	 *
	 * @param   string|null  $code  Answer provided by user.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 * @throws  \RuntimeException
	 */
	public function onCheckAnswer($code = null)
	{
		if ($code === null || $code === '')
		{
			// No answer provided, form was manipulated.
			return false;
		}

		try
		{
			$http = HttpFactory::getHttp();
		}
		catch (\RuntimeException $exception)
		{
			if (JDEBUG)
			{
				throw $exception;
			}

			// No HTTP transports supported.
			return !$this->params->get('strictMode');
		}

		try
		{
			$response = $http->post(
				'https://api.friendlycaptcha.com/api/v1/siteverify',
				array(
					'solution' => $code,
					'secret' => $this->params->get('secret'),
					'sitekey' => $this->params->get('siteKey'),
				)
			);
		}
		catch (\RuntimeException $exception)
		{
			if (JDEBUG)
			{
				throw $exception;
			}

			// Connection or transport error.
			return !$this->params->get('strictMode');
		}

		$body = json_decode($response->body);

		// Remote service error.
		if ($body === null)
		{
			if (JDEBUG)
			{
				throw new RuntimeException('Invalid response from Captcha service.');
			}

			return !$this->params->get('strictMode');
		}

		if (isset($body->success) && $body->success !== true)
		{
			// If error codes are pvovided, use them for language strings.
			if (!empty($body->errors) && is_array($body->errors))
			{
				$this->loadLanguage();
				$language = $this->app->getLanguage();

				if ($errors = array_intersect($body->errors, self::$errorCodes))
				{
					throw new RuntimeException($language->_('PLG_CAPTCHA_FRIENDLYCAPTCHA_ERROR_' . strtoupper(reset($errors))));
				}
			}

			return false;
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
		$this->loadLanguage();

		$attributes = array(
			'data-sitekey' => $this->params->get('siteKey'),
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

		$language = $this->app->getLanguage();

		// Use script's built-in language if available.
		if ($locales = $language->getLocale())
		{
			if ($matchedLanguages = array_intersect($locales, self::$languages))
			{
				$attributes['data-lang'] = reset($matchedLanguages);
			}
		}

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
	 * @throws  \RuntimeException
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
				array('type' => 'module', 'async' => true, 'defer' => true, 'crossorigin' => 'anonymous', 'integrity' => self::$sriHashes[$moduleFilename])
			);

			$document->addScript(
				$baseUrl . self::CHALLENGE_VERSION . '/' . $legacyFilename,
				array(),
				array('nomodule' => 'true', 'async' => true, 'defer' => true, 'crossorigin' => 'anonymous', 'integrity' => self::$sriHashes[$legacyFilename])
			);

			return true;
		}

		HTMLHelper::_(
			'script',
			'plg_captcha_friendlycaptcha/widget.module.min.js',
			array('relative' => true, 'version' => self::CHALLENGE_VERSION),
			array('type' => 'module', 'async' => true, 'defer' => true)
		);
		HTMLHelper::_(
			'script',
			'plg_captcha_friendlycaptcha/' . $legacyFilename,
			array('relative' => true, 'version' => self::CHALLENGE_VERSION),
			array('nomodule' => 'true', 'async' => true, 'defer' => true)
		);

		return true;
	}
}
