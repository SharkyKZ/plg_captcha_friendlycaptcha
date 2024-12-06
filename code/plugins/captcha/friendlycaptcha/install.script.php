<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */

defined('_JEXEC') || exit;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Version;

/**
 * Plugin installer script.
 */
final class PlgCaptchaFriendlyCaptchaInstallerScript
{
	/**
	 * Minimum supported PHP version.
	 *
	 * @var    string
	 * @since  1.0.0
	 *
	 */
	private const PHP_MINIMUM = '7.2';

	/**
	 * Next unsupported PHP version.
	 *
	 * @var    string
	 * @since  2.0.0
	 *
	 */
	private const PHP_UNSUPPORTED = '9.0';

	/**
	 * Minimum supported Joomla! version.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private const JOOMLA_MINIMUM = '3.8';

	/**
	 * Next unsupported Joomla! version.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	private const JOOMLA_UNSUPPORTED = '6.0';

	/**
	 * Function called before extension installation/update/removal procedure commences
	 *
	 * @param   string                                 $type    The type of change (install, update, discover_install or uninstall)
	 * @param   Joomla\CMS\Installer\InstallerAdapter  $parent  The class calling this method
	 *
	 * @return  bool  True on success
	 *
	 * @since   1.0.0
	 */
	public function preflight($type, $parent): bool
	{
		if ($type === 'uninstall')
		{
			return true;
		}

		if (version_compare(JVERSION, self::JOOMLA_MINIMUM, '<'))
		{
			return false;
		}

		if (version_compare(JVERSION, self::JOOMLA_UNSUPPORTED, '>=') && !(new Version)->isInDevelopmentState())
		{
			return false;
		}

		if (version_compare(PHP_VERSION, self::PHP_MINIMUM, '<'))
		{
			Log::add(Text::sprintf('PLG_CAPTCHA_FRIENDLYCAPTCHA_INSTALL_PHP_MINIMUM', self::PHP_MINIMUM), Log::WARNING, 'jerror');

			return false;
		}

		if (version_compare(PHP_VERSION, self::PHP_UNSUPPORTED, '>='))
		{
			Log::add(Text::sprintf('PLG_CAPTCHA_FRIENDLYCAPTCHA_INSTALL_PHP_UNSUPPORTED', self::PHP_UNSUPPORTED), Log::WARNING, 'jerror');

			return false;
		}

		return true;
	}
}
