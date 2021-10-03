<?php
/**
 * @copyright   (C) 2021 SharkyKZ
 * @license     GPL-2.0-or-later
 */

defined('_JEXEC') or exit;

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
	 */
	const PHP_MINIMUM = '5.3.10';

	/**
	 * Maximum supported PHP version.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	const PHP_MAXIMUM_MINOR = '8.0';

	/**
	 * Minimum supported Joomla! version.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	const JOOMLA_MINIMUM = '3.8';

	/**
	 * Maximum supported Joomla! version.
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	const JOOMLA_MAXIMUM_MINOR = '4.0';

	/**
	 * Function called before extension installation/update/removal procedure commences
	 *
	 * @param   string                                 $type    The type of change (install, update or discover_install, not uninstall)
	 * @param   Joomla\CMS\Installer\InstallerAdapter  $parent  The class calling this method
	 *
	 * @return  bool  True on success
	 *
	 * @since   1.0.0
	 */
	public function preflight($type, $parent)
	{
		if (version_compare(PHP_VERSION, self::PHP_MINIMUM, '<'))
		{
			return false;
		}

		if (version_compare(PHP_MINOR_VERSION, self::PHP_MAXIMUM_MINOR, '>'))
		{
			return false;
		}

		if (version_compare(JVERSION, self::JOOMLA_MINIMUM, '<'))
		{
			return false;
		}

		if (version_compare(Version::MAJOR_VERSION . '.' . Version::MINOR_VERSION, self::JOOMLA_MAXIMUM_MINOR, '>'))
		{
			return false;
		}

		return true;
	}
}
