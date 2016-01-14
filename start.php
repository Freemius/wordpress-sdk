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
	 */
	#region Hooks & Filters Collection --------------------------------------------------------------------

	/**
	 * Freemius hooks (actions & filters) tags structure:
	 *
	 *      fs_{filter/action_name}_{plugin_slug}
	 *
	 * --------------------------------------------------------
	 *
	 * Usage with WordPress' add_action() / add_filter():
	 *
	 *      add_action('fs_{filter/action_name}_{plugin_slug}', $callable);
	 *
	 * --------------------------------------------------------
	 *
	 * Usage with Freemius' instance add_action() / add_filter():
	 *
	 *      // No need to add 'fs_' prefix nor '_{plugin_slug}' suffix.
	 *      my_freemius()->add_action('{action_name}', $callable);
	 *
	 * --------------------------------------------------------
	 *
	 * Freemius filters collection:
	 *
	 *      fs_connect_url_{plugin_slug}
	 *      fs_trial_promotion_message_{plugin_slug}
	 *      fs_is_long_term_user_{plugin_slug}
	 *      fs_uninstall_reasons_{plugin_slug}
	 *      fs_is_plugin_update_{plugin_slug}
	 *      fs_api_domains_{plugin_slug}
	 *      fs_email_template_sections_{plugin_slug}
	 *      fs_support_forum_submenu_{plugin_slug}
	 *      fs_support_forum_url_{plugin_slug}
	 *      fs_connect_message_{plugin_slug}
	 *      fs_connect_message_on_update_{plugin_slug}
	 *      fs_uninstall_confirmation_message_{plugin_slug}
	 *      fs_pending_activation_message_{plugin_slug}
	 *
	 * --------------------------------------------------------
	 *
	 * Freemius actions collection:
	 *
	 *     fs_after_license_loaded_{plugin_slug}
	 *      fs_after_license_change_{plugin_slug}
	 *      fs_after_plans_sync_{plugin_slug}
	 *
	 *      fs_after_account_details_{plugin_slug}
	 *      fs_after_account_user_sync_{plugin_slug}
	 *      fs_after_account_plan_sync_{plugin_slug}
	 *      fs_before_account_load_{plugin_slug}
	 *      fs_after_account_connection_{plugin_slug}
	 *      fs_account_property_edit_{plugin_slug}
	 *      fs_account_email_verified_{plugin_slug}
	 *      fs_account_page_load_before_departure_{plugin_slug}
	 *      fs_before_account_delete_{plugin_slug}
	 *      fs_after_account_delete_{plugin_slug}
	 *
	 *      fs_sdk_version_update_{plugin_slug}
	 *      fs_plugin_version_update_{plugin_slug}
	 *
	 *      fs_initiated_{plugin_slug}
	 *      fs_after_init_plugin_registered_{plugin_slug}
	 *      fs_after_init_plugin_anonymous_{plugin_slug}
	 *      fs_after_init_plugin_pending_activations_{plugin_slug}
	 *      fs_after_init_addon_registered_{plugin_slug}
	 *      fs_after_init_addon_anonymous_{plugin_slug}
	 *      fs_after_init_addon_pending_activations_{plugin_slug}
	 *
	 *      fs_after_premium_version_activation_{plugin_slug}
	 *      fs_after_free_version_reactivation_{plugin_slug}
	 *
	 *      fs_after_uninstall_{plugin_slug}
	 *      fs_before_admin_menu_init_{plugin_slug}
	 */

	#endregion Hooks & Filters Collection --------------------------------------------------------------------

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