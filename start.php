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

	if ( ! class_exists( 'Freemius' ) ) {

		// Configuration should be loaded first.
		require_once dirname( __FILE__ ) . '/config.php';

		// Logger must be loaded before any other.
		require_once WP_FS__DIR_INCLUDES . '/class-fs-logger.php';

		require_once WP_FS__DIR_INCLUDES . '/fs-core-functions.php';
//		require_once WP_FS__DIR_INCLUDES . '/managers/class-fs-abstract-manager.php';
		require_once WP_FS__DIR_INCLUDES . '/managers/class-fs-option-manager.php';
		require_once WP_FS__DIR_INCLUDES . '/managers/class-fs-admin-notice-manager.php';
		require_once WP_FS__DIR_INCLUDES . '/managers/class-fs-admin-menu-manager.php';
		require_once WP_FS__DIR_INCLUDES . '/managers/class-fs-key-value-storage.php';
		require_once WP_FS__DIR_INCLUDES . '/managers/class-fs-license-manager.php';
		require_once WP_FS__DIR_INCLUDES . '/managers/class-fs-plan-manager.php';
		require_once WP_FS__DIR_INCLUDES . '/managers/class-fs-plugin-manager.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-entity.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-scope-entity.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-user.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-site.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin-info.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin-tag.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin-plan.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-plugin-license.php';
		require_once WP_FS__DIR_INCLUDES . '/entities/class-fs-subscription.php';
		require_once WP_FS__DIR_INCLUDES . '/class-fs-api.php';
		require_once WP_FS__DIR_INCLUDES . '/class-fs-plugin-updater.php';
		require_once WP_FS__DIR_INCLUDES . '/class-fs-security.php';
		require_once WP_FS__DIR_INCLUDES . '/class-freemius-abstract.php';
		require_once WP_FS__DIR_INCLUDES . '/class-freemius.php';

		/**
		 * Quick shortcut to get Freemius for specified plugin.
		 * Used by various templates.
		 *
		 * @param string $slug
		 *
		 * @return Freemius
		 */
		function freemius( $slug ) {
			return Freemius::instance( $slug );
		}

		/**
		 * @param string $slug
		 * @param number $plugin_id
		 * @param string $public_key
		 * @param bool   $is_live    Is live or test plugin.
		 * @param bool   $is_premium Hints freemius if running the premium plugin or not.
		 *
		 * @return Freemius
		 */
		function fs_init( $slug, $plugin_id, $public_key, $is_live = true, $is_premium = true ) {
			$fs = Freemius::instance( $slug );
			$fs->init( $plugin_id, $public_key, $is_live, $is_premium );

			return $fs;
		}

		/**
		 * @param array [string]string $plugin
		 *
		 * @return Freemius
		 * @throws Freemius_Exception
		 */
		function fs_dynamic_init( $plugin ) {
			$fs = Freemius::instance( $plugin['slug'] );
			$fs->dynamic_init( $plugin );

			return $fs;
		}

		function fs_dump_log() {
			FS_Logger::dump();
		}
	}