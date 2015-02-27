<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Freemius hooks collection:
	 *  fs_after_license_loaded
	 */

	if (!class_exists('Freemius')) {

		// Configuration should be loaded first.
		require_once dirname(__FILE__) . '/config.php';

		// Logger must be loaded before any other.
		require_once WP_FS__DIR_INCLUDES . '/class-fs-logger.php';

		require_once WP_FS__DIR_INCLUDES . '/fs-core-functions.php';
		require_once WP_FS__DIR_INCLUDES . '/class-fs-option-manager.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-entity.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-scope-entity.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-user.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-site.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin-tag.php';
		require_once WP_FS__DIR_INCLUDES . '/class-fs-api.php';
		require_once WP_FS__DIR_INCLUDES . '/class-fs-plugin-updater.php';
		require_once WP_FS__DIR_INCLUDES . '/class-fs-security.php';
		require_once WP_FS__DIR_INCLUDES . '/class-freemius.php';

		if (file_exists(WP_FS__DIR_INCLUDES . '/_class-fs-debug.php'))
			require_once WP_FS__DIR_INCLUDES . '/_class-fs-debug.php';

		/**
		 * Quick shortcut to get Freemius for specified plugin.
		 * Used by various templates.
		 *
		 * @param string $slug
		 *
		 * @return Freemius
		 */
		function fs($slug)
		{
			return Freemius::instance($slug);
		}

		/**
		 * @param string $slug
		 * @param string $developer_id
		 * @param string $public_key
		 *
		 * @return Freemius
		 */
		function fs_init($slug, $developer_id, $public_key)
		{
			$fs = Freemius::instance($slug);
			$fs->init($developer_id, $public_key);
			return $fs;
		}

		function fs_dump_log()
		{
			FS_Logger::dump();
		}
	}