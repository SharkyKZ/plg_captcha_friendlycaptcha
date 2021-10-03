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
	const CHALLENGE_VERSION = '0.9.0';

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
	private $errorCodes = array(
		'secret_missing',
		'secret_invalid',
		'solution_missing',
		'bad_request',
		'solution_invalid',
		'solution_timeout_or_duplicate',
	);

	/**
	 * Supported languages.
	 *
	 * @var    array
	 * @since  1.0.0
	 */
	private $languages = array(
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
			// No HTTP transports supported.
			return true;
		}

		try
		{
			$response = $http->post(
				'https://friendlycaptcha.com/api/v1/siteverify',
				array(
					'solution' => $code,
					'secret' => $this->params->get('secret'),
					'sitekey' => $this->params->get('siteKey'),
				)
			);
		}
		catch (\Exception $exception)
		{
			// Connection or transport error.
			return true;
		}

		$body = json_decode($response->body);

		// Remote service error.
		if ($body === null)
		{
			return true;
		}

		if (isset($body->success) && $body->success !== true)
		{
			// If error codes are pvovided, use them for language strings.
			if (!empty($body->errors) && is_array($body->errors))
			{
				$this->loadLanguage();
				$language = $this->app->getLanguage();

				$errors = array_intersect($body->errors, $this->errorCodes);
				$errorText = array();

				foreach ($errors as $error)
				{
					$errorText[] = $language->_('PLG_CAPTCHA_FRIENDLYCAPTCHA_ERROR_' . strtoupper($error));
				}

				throw new RuntimeException(implode("\n", $errorText));
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
			'class' => rtrim('frc-captcha ' . $class),
		);

		if ($id !== null && $id !== '')
		{
			$attributes['id'] = $id;
		}

		if ($name !== null && $name !== '')
		{
			$attributes['data-solution-field-name'] = $name;
		}

		$language = $this->app->getLanguage();

		// Use script's built-in language if available.
		if ($locales = $language->getLocale())
		{
			$locales = array_filter($locales,
				function($v)
				{
					return strlen($v) === 2;
				}
			);

			if ($matchedLanguages = array_intersect($locales, $this->languages))
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
	 * Initialises the captcha
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

			$document->addScript(
				$baseUrl . self::CHALLENGE_VERSION . '/widget.module.min.js',
				array(),
				array('type' => 'module', 'async' => true, 'defer' => true)
			);

			$document->addScript(
				$baseUrl . self::CHALLENGE_VERSION . '/widget.min.js',
				array(),
				array('nomodule' => 'true', 'async' => true, 'defer' => true)
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
			'plg_captcha_friendlycaptcha/widget.min.js',
			array('relative' => true, 'version' => self::CHALLENGE_VERSION),
			array('nomodule' => 'true', 'async' => true, 'defer' => true)
		);

		return true;
	}
}
