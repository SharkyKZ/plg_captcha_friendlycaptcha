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
	 * @deprecated  2.0.0  Visibility will be changed to private.
	 *
	 */
	const PHP_MINIMUM = '5.3.10';

	/**
	 * Maximum supported PHP version.
	 *
	 * @var    string
	 * @since  1.0.0
	 *
	 * @deprecated  2.0.0
	 *
	 */
	const PHP_MAXIMUM_MINOR = '8.0';

	/**
	 * Minimum supported Joomla! version.
	 *
	 * @var    string
	 * @since  1.0.0
	 *
	 * @deprecated  2.0.0  Visibility will be changed to private.
	 *
	 */
	const JOOMLA_MINIMUM = '3.8';

	/**
	 * Maximum supported Joomla! version.
	 *
	 * @var    string
	 * @since  1.0.0
	 *
	 * @deprecated  2.0.0
	 *
	 */
	const JOOMLA_MAXIMUM_MINOR = '4.0';

	/**
	 * Minimum supported Joomla! version.
	 *
	 * @var    string
	 * @since  1.0.1
	 */
	private $joomlaMinimum = '3.8';

	/**
	 * Next unsupported Joomla! version.
	 *
	 * @var    string
	 * @since  1.0.1
	 */
	private $joomlaUnsupported = '6.0';

	/**
	 * Minimum supported PHP version.
	 *
	 * @var    string
	 * @since  1.0.1
	 */
	private $phpMinimum = '5.4';

	/**
	 * Next unsupported PHP version.
	 *
	 * @var    string
	 * @since  1.0.1
	 */
	private $phpUnsupported = '8.5';

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
	public function preflight($type, $parent)
	{
		if ($type === 'uninstall')
		{
			return true;
		}

		if (version_compare(JVERSION, $this->joomlaMinimum, '<'))
		{
			return false;
		}

		if (version_compare(JVERSION, $this->joomlaUnsupported, '>=') && !(new Version)->isInDevelopmentState())
		{
			return false;
		}

		if (version_compare(PHP_VERSION, $this->phpMinimum, '<'))
		{
			Log::add(Text::sprintf('PLG_CAPTCHA_FRIENDLYCAPTCHA_INSTALL_PHP_MINIMUM', $this->phpMinimum), Log::WARNING, 'jerror');

			return false;
		}

		if (version_compare(PHP_VERSION, $this->phpUnsupported, '>='))
		{
			Log::add(Text::sprintf('PLG_CAPTCHA_FRIENDLYCAPTCHA_INSTALL_PHP_UNSUPPORTED', $this->phpUnsupported), Log::WARNING, 'jerror');

			return false;
		}

		return true;
	}
}
